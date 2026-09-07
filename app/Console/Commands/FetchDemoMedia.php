<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fills the demo catalogue with photography that can actually be deployed.
 *
 * Images come from the Openverse API, restricted to CC0 and the Public Domain
 * Mark. That restriction is the point of this command: those two carry no
 * attribution requirement, no share-alike term and no non-commercial term, so
 * the seeded site can go to production without an audit.
 *
 * The obvious alternatives do not clear that bar. LoremFlickr serves Creative
 * Commons photographs whose flavour varies per image -- some are BY-NC, which
 * forbids commercial use outright -- and hands back a bare JPEG with no record
 * of which licence or which photographer, so the obligations cannot be met even
 * in principle. Picsum is Unsplash-licensed and safe, but has no subject search,
 * and a hero photograph of a mountain range on a surveillance site is worse than
 * no photograph.
 *
 * Provenance for every file is written to demo/credits.json and demo/credits.md
 * beside the images. Neither licence requires it; it is there so the question
 * "where did this picture come from" has an answer a year from now.
 *
 * Files land on the public disk under demo/ and the command skips anything
 * already present, which keeps re-runs -- and the end-to-end suite, which never
 * calls out to the network -- entirely offline. When the network is unreachable
 * a brand-coloured placeholder is generated locally instead, so a seeded
 * database always has renderable images.
 */
class FetchDemoMedia extends Command
{
    protected $signature = 'sentrix:fetch-demo-media
                            {--force : Re-download files that already exist}
                            {--offline : Skip the network entirely and generate placeholders}';

    protected $description = 'Download CC0 / public-domain photos for the demo catalogue onto the public disk';

    private const ENDPOINT = 'https://api.openverse.org/v1/images/';

    /**
     * Only these two licences are ever requested. CC0 is a rights waiver and the
     * Public Domain Mark labels work already out of copyright; neither obliges
     * the user of the image to do anything. Every other Creative Commons licence
     * requires at least attribution, and some forbid commercial use.
     */
    private const LICENCES = 'cc0,pdm';

    /**
     * What to download: one entry per photo set, in the order the seeders draw
     * on them. `dir` and `prefix` build the path, so a product set lands at
     * demo/products/dome-01.jpg and CatalogSeeder can ask for a dome camera by
     * subject rather than by position in one shared cycle.
     *
     * `queries` are tried in turn until the set has enough candidates, so a
     * narrow subject can fall back to a broader one rather than to a
     * placeholder. Sets drawing on the same subject need their own distinct
     * terms as well: results are claimed globally so the gallery does not repeat
     * the product tiles, and a set listing only terms an earlier set already
     * drained would starve.
     *
     * @var array<string, array{dir: string, prefix: string, count: int, width: int, height: int, queries: list<string>}>
     */
    private const MANIFEST = [
        'products/camera' => [
            'dir' => 'products', 'prefix' => 'camera', 'count' => 10,
            'width' => 800, 'height' => 800,
            'queries' => ['security camera', 'surveillance camera', 'video surveillance'],
        ],
        'products/dome' => [
            'dir' => 'products', 'prefix' => 'dome', 'count' => 8,
            'width' => 800, 'height' => 800,
            'queries' => ['cctv camera', 'dome camera', 'cctv', 'closed circuit television'],
        ],
        'products/ptz' => [
            'dir' => 'products', 'prefix' => 'ptz', 'count' => 5,
            'width' => 800, 'height' => 800,
            'queries' => ['ptz camera', 'pan tilt zoom camera', 'speed dome camera', 'surveillance camera'],
        ],
        'products/recorder' => [
            'dir' => 'products', 'prefix' => 'recorder', 'count' => 6,
            'width' => 800, 'height' => 800,
            'queries' => ['server rack', 'network switch', 'rack mount server', 'data center server', 'network equipment'],
        ],
        'products/storage' => [
            'dir' => 'products', 'prefix' => 'storage', 'count' => 4,
            'width' => 800, 'height' => 800,
            'queries' => ['hard drive', 'hard disk drive', 'hard disk'],
        ],
        'products/alarm' => [
            'dir' => 'products', 'prefix' => 'alarm', 'count' => 5,
            'width' => 800, 'height' => 800,
            'queries' => ['smoke detector', 'fire alarm', 'alarm siren', 'motion detector'],
        ],
        'products/access' => [
            'dir' => 'products', 'prefix' => 'access', 'count' => 5,
            'width' => 800, 'height' => 800,
            'queries' => ['door lock', 'access control', 'intercom', 'door keypad', 'turnstile'],
        ],
        'products/cable' => [
            'dir' => 'products', 'prefix' => 'cable', 'count' => 5,
            'width' => 800, 'height' => 800,
            'queries' => ['network cable', 'ethernet cable', 'coaxial cable', 'rj45 connector', 'cable bundle'],
        ],
        'banners' => [
            'dir' => 'banners', 'prefix' => 'banners', 'count' => 3,
            'width' => 1600, 'height' => 900,
            'queries' => ['cctv', 'video surveillance', 'closed circuit television', 'surveillance camera'],
        ],
        'gallery' => [
            'dir' => 'gallery', 'prefix' => 'gallery', 'count' => 9,
            'width' => 1000, 'height' => 750,
            'queries' => ['cctv camera', 'security camera', 'closed circuit television', 'video surveillance'],
        ],
        'posts' => [
            'dir' => 'posts', 'prefix' => 'posts', 'count' => 8,
            'width' => 1200, 'height' => 675,
            'queries' => ['cctv', 'surveillance camera', 'security camera', 'video surveillance'],
        ],
    ];

    /**
     * Openverse ids already used, so the gallery does not repeat the product
     * tiles when both draw on "security camera".
     *
     * @var array<string, true>
     */
    private array $claimed = [];

    /**
     * Provenance rows, written out at the end.
     *
     * @var list<array<string, string|null>>
     */
    private array $credits = [];

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
                // Over-fetch: a good share of Openverse rows point at upstream
                // links that have since died, so the pool has to be several
                // times the slot count for the set to fill.
                $candidates = $this->option('offline')
                    ? []
                    : $this->candidates($spec['queries'], $spec['count'] * 6);

                for ($index = 1; $index <= $spec['count']; $index++) {
                    $path = sprintf('demo/%s/%s-%02d.jpg', $spec['dir'], $spec['prefix'], $index);

                    if ($disk->exists($path) && ! $this->option('force')) {
                        $skipped++;

                        continue;
                    }

                    [$bytes, $credit] = $this->take($candidates, $spec['width'], $spec['height']);

                    if ($bytes === null) {
                        $bytes = $this->placeholder($spec['width'], $spec['height'], $index);
                        $generated++;
                    } else {
                        $written++;
                        $this->credits[] = ['file' => $path] + $credit;
                    }

                    $disk->put($path, $bytes);
                }

                return true;
            });
        }

        $this->writeCredits($disk);

        $this->components->info(
            "{$written} downloaded, {$generated} generated, {$skipped} already present."
        );

        if ($generated > 0) {
            $this->components->warn(
                "{$generated} placeholder(s) generated -- rerun with --force once the network is reachable."
            );
        }

        return self::SUCCESS;
    }

    /**
     * Pull the first candidate that downloads and decodes, dropping it from the
     * list so the next slot gets a different photograph.
     *
     * @param  list<array<string, string|null>>  $candidates
     * @return array{0: ?string, 1: array<string, string|null>}
     */
    private function take(array &$candidates, int $width, int $height): array
    {
        while (($candidate = array_shift($candidates)) !== null) {
            // Full size first; Openverse's cached thumbnail if the upstream
            // host has gone away, which for an aggregated index is common.
            foreach ([$candidate['url'], $candidate['thumbnail']] as $source) {
                if (! is_string($source) || $source === '') {
                    continue;
                }

                $bytes = $this->download($source);

                if ($bytes === null) {
                    continue;
                }

                $cover = $this->cover($bytes, $width, $height);

                if ($cover !== null) {
                    return [$cover, $candidate];
                }
            }
        }

        return [null, []];
    }

    /**
     * Search Openverse across a set's queries, paging until there are `$target`
     * unique candidates or the queries are exhausted.
     *
     * @param  list<string>  $queries
     * @return list<array<string, string|null>>
     */
    private function candidates(array $queries, int $target): array
    {
        $found = [];

        foreach ($queries as $query) {
            for ($page = 1; $page <= 8; $page++) {
                $results = $this->search($query, $page);

                if ($results === []) {
                    break;
                }

                foreach ($results as $result) {
                    $id = (string) ($result['id'] ?? '');

                    if ($id === '' || isset($this->claimed[$id]) || ! is_string($result['url'] ?? null)) {
                        continue;
                    }

                    $this->claimed[$id] = true;

                    $found[] = [
                        'url' => $result['url'],
                        // Openverse's own cached copy, used when the upstream
                        // link is dead. Only 600px wide, so it is the fallback
                        // rather than the first choice.
                        'thumbnail' => is_string($result['thumbnail'] ?? null) ? $result['thumbnail'] : null,
                        'title' => $result['title'] ?? null,
                        'creator' => $result['creator'] ?? null,
                        'creator_url' => $result['creator_url'] ?? null,
                        'licence' => trim(($result['license'] ?? '').' '.($result['license_version'] ?? '')),
                        'licence_url' => $result['license_url'] ?? null,
                        'source' => $result['source'] ?? null,
                        'landing_url' => $result['foreign_landing_url'] ?? null,
                    ];
                }

                if (count($found) >= $target) {
                    return $found;
                }
            }
        }

        return $found;
    }

    /**
     * One page of results. Returns an empty list on any failure so a set falls
     * back to placeholders rather than aborting the run.
     *
     * @return list<array<string, mixed>>
     */
    private function search(string $query, int $page): array
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'sentrix-demo-media/1.0'])
                ->get(self::ENDPOINT, [
                    'q' => $query,
                    'license' => self::LICENCES,
                    'page_size' => 20,
                    'page' => $page,
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return $response->json('results') ?? [];
    }

    /**
     * Fetch one image. Anything that is not an image, or is implausibly large
     * for a demo asset, is rejected rather than written to the disk.
     */
    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'sentrix-demo-media/1.0'])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        if (! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        $body = $response->body();

        return strlen($body) > 25_000_000 ? null : $body;
    }

    /**
     * Scale and centre-crop to the target box.
     *
     * Openverse returns whatever aspect the photographer shot at. The grids all
     * use object-cover, so an uncropped image would display correctly but ship
     * several megabytes to do it; cropping here keeps the seeded site quick.
     */
    private function cover(string $bytes, int $width, int $height): ?string
    {
        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            return null;
        }

        // Cover: scale so the shorter side fills, then take the middle.
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);

        $canvas = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            (int) round(($sourceWidth - $cropWidth) / 2),
            (int) round(($sourceHeight - $cropHeight) / 2),
            $width,
            $height,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagejpeg($canvas, null, 82);
        $out = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $out;
    }

    /**
     * Record where every image came from. CC0 and the Public Domain Mark do not
     * require this; it is kept so provenance is auditable later.
     */
    private function writeCredits(Filesystem $disk): void
    {
        if ($this->credits === []) {
            return;
        }

        $existing = json_decode((string) ($disk->get('demo/credits.json') ?: '[]'), true) ?: [];

        // Keyed by file so a partial re-run updates rows rather than duplicating.
        $merged = collect($existing)->concat($this->credits)->keyBy('file')->values()->all();

        $disk->put('demo/credits.json', (string) json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $lines = [
            '# Demo media credits',
            '',
            'Every file below is CC0 or Public Domain Mark, retrieved through the Openverse API',
            'by `php artisan sentrix:fetch-demo-media`. Neither licence requires attribution;',
            'this file records provenance so it can be audited.',
            '',
            '| File | Title | Creator | Licence | Source |',
            '|---|---|---|---|---|',
        ];

        foreach ($merged as $row) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $row['file'] ?? '',
                str_replace('|', '\|', (string) ($row['title'] ?? '')),
                str_replace('|', '\|', (string) ($row['creator'] ?? 'Unknown')),
                strtoupper((string) ($row['licence'] ?? '')),
                $row['landing_url'] ?? ''
            );
        }

        $disk->put('demo/credits.md', implode("\n", $lines)."\n");
    }

    /**
     * A brand-coloured gradient, so an offline run still produces something that
     * reads as an image rather than a broken tile.
     */
    private function placeholder(int $width, int $height, int $seed): string
    {
        $image = imagecreatetruecolor($width, $height);

        // The brand steel blue, darkened progressively down the canvas.
        for ($y = 0; $y < $height; $y++) {
            $shade = 1 - ($y / $height) * 0.45;
            $colour = imagecolorallocate(
                $image,
                (int) (0x5B * $shade),
                (int) (0x7C * $shade),
                (int) (0x9D * $shade),
            );
            imagefilledrectangle($image, 0, $y, $width, $y, $colour);
        }

        $ink = imagecolorallocate($image, 14, 15, 17);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width / 3), (int) ($width / 3), $ink);

        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, (int) ($width / 2) - 32, (int) ($height / 2) - 8, 'SENTRIX '.$seed, $white);

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }
}
