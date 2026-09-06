<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fills the demo catalogue with free stock photography.
 *
 * The old site's imagery is not carried forward, so the demo content needs
 * pictures of its own. Files land on the public disk under demo/ and the command
 * skips anything already present, which keeps re-runs -- and the end-to-end
 * suite, which never calls out to the network -- entirely offline.
 *
 * When the network is unreachable a brand-coloured placeholder is generated
 * locally instead, so a seeded database always has renderable images.
 */
class FetchDemoMedia extends Command
{
    protected $signature = 'kompra:fetch-demo-media
                            {--force : Re-download files that already exist}
                            {--offline : Skip the network entirely and generate placeholders}';

    protected $description = 'Download free stock photos for the demo catalogue onto the public disk';

    /**
     * What to download: one entry per photo set, in the order the seeders draw
     * on them. `dir` and `prefix` build the path, so a product set lands at
     * demo/products/toner-01.jpg and CatalogSeeder can ask for a toner photo by
     * subject rather than by position in one shared cycle.
     *
     * `topical` prefers Flickr's tag search: a product tile showing an actual
     * printer beats a well-shot landscape. `curated` prefers Picsum, whose set
     * is consistently good and where subject matter barely matters.
     *
     * @var array<string, array{dir: string, prefix: string, count: int, width: int, height: int, prefer: string, topics: list<string>}>
     */
    private const MANIFEST = [
        // Single, unambiguous tags: loose ones such as "ink" or "office" match
        // tattoos and street scenes rather than office equipment.
        'products/printer' => [
            'dir' => 'products', 'prefix' => 'printer', 'count' => 10,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['printer'],
        ],
        'products/toner' => [
            'dir' => 'products', 'prefix' => 'toner', 'count' => 8,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['toner', 'inkcartridge'],
        ],
        'products/scanner' => [
            'dir' => 'products', 'prefix' => 'scanner', 'count' => 4,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['scanner'],
        ],
        'products/photocopier' => [
            'dir' => 'products', 'prefix' => 'photocopier', 'count' => 6,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['photocopier'],
        ],
        'products/paper' => [
            'dir' => 'products', 'prefix' => 'paper', 'count' => 4,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['paper', 'stationery'],
        ],
        'products/part' => [
            'dir' => 'products', 'prefix' => 'part', 'count' => 3,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['machineparts'],
        ],
        'products/accessory' => [
            'dir' => 'products', 'prefix' => 'accessory', 'count' => 4,
            'width' => 800, 'height' => 800, 'prefer' => 'topical',
            'topics' => ['usbcable', 'keyboard'],
        ],
        'banners' => [
            'dir' => 'banners', 'prefix' => 'banners', 'count' => 3,
            'width' => 1600, 'height' => 900, 'prefer' => 'curated',
            'topics' => ['printshop', 'printer', 'workspace'],
        ],
        'gallery' => [
            'dir' => 'gallery', 'prefix' => 'gallery', 'count' => 9,
            'width' => 1000, 'height' => 750, 'prefer' => 'curated',
            'topics' => ['showroom', 'warehouse', 'printshop'],
        ],
        'posts' => [
            'dir' => 'posts', 'prefix' => 'posts', 'count' => 8,
            'width' => 1200, 'height' => 675, 'prefer' => 'curated',
            'topics' => ['technology', 'printer', 'workspace'],
        ],
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $written = 0;
        $skipped = 0;
        $generated = 0;

        foreach (self::MANIFEST as $label => $spec) {
            $this->components->task("demo/{$label}", function () use (
                $spec, $disk, &$written, &$skipped, &$generated
            ): bool {
                for ($index = 1; $index <= $spec['count']; $index++) {
                    $path = sprintf('demo/%s/%s-%02d.jpg', $spec['dir'], $spec['prefix'], $index);

                    if ($disk->exists($path) && ! $this->option('force')) {
                        $skipped++;

                        continue;
                    }

                    $topic = $spec['topics'][($index - 1) % count($spec['topics'])];
                    $bytes = $this->option('offline')
                        ? null
                        : $this->download($topic, $spec['width'], $spec['height'], $index, $spec['prefer']);

                    if ($bytes === null) {
                        $bytes = $this->placeholder($spec['width'], $spec['height'], $index);
                        $generated++;
                    } else {
                        $written++;
                    }

                    $disk->put($path, $bytes);
                }

                return true;
            });
        }

        $this->components->info(
            "{$written} downloaded, {$generated} generated, {$skipped} already present."
        );

        return self::SUCCESS;
    }

    /**
     * Fetch one photo, trying the preferred source first and the other as a
     * fallback. Returns null when neither answers, so the caller can generate a
     * placeholder instead.
     */
    private function download(string $topic, int $width, int $height, int $seed, string $prefer): ?string
    {
        $topical = sprintf('https://loremflickr.com/%d/%d/%s?lock=%d', $width, $height, $topic, $seed);
        $curated = sprintf('https://picsum.photos/seed/kompra-%s-%d/%d/%d', $topic, $seed, $width, $height);

        $sources = $prefer === 'topical' ? [$topical, $curated] : [$curated, $topical];

        foreach ($sources as $url) {
            try {
                $response = Http::timeout(20)->get($url);
            } catch (Throwable) {
                continue;
            }

            if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                return $response->body();
            }
        }

        return null;
    }

    /**
     * A brand-coloured gradient, so an offline run still produces something that
     * reads as an image rather than a broken tile.
     */
    private function placeholder(int $width, int $height, int $seed): string
    {
        $image = imagecreatetruecolor($width, $height);

        // The brand orange, darkened progressively down the canvas.
        for ($y = 0; $y < $height; $y++) {
            $shade = 1 - ($y / $height) * 0.45;
            $colour = imagecolorallocate(
                $image,
                (int) (0xE4 * $shade),
                (int) (0x88 * $shade),
                (int) (0x01 * $shade) + 20,
            );
            imagefilledrectangle($image, 0, $y, $width, $y, $colour);
        }

        $ink = imagecolorallocate($image, 8, 16, 163);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width / 3), (int) ($width / 3), $ink);

        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, (int) ($width / 2) - 30, (int) ($height / 2) - 8, 'KOMPRA '.$seed, $white);

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }
}
