<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Career;
use App\Models\GalleryImage;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The editorial side of the site: home page banners, the showroom gallery, the
 * news feed and open vacancies.
 *
 * All of it is admin-managed content. What is here is the copy the site launches
 * with, not a fixture -- it is written to be read by a customer.
 */
class ContentSeeder extends Seeder
{
    /**
     * Home page banners. `link_url` points at a route the SPA actually has, so
     * a banner never lands the visitor on a 404.
     *
     * @var list<array{headline: string, link_url: string}>
     */
    private const BANNERS = [
        ['headline' => 'IP cameras from ₱3,150 — stocked at every branch', 'link_url' => '/shop?category=ip-cameras'],
        ['headline' => 'Recorders and surveillance drives, matched to the cameras you own', 'link_url' => '/shop?category=recorders-and-storage'],
        ['headline' => 'Four cameras, a recorder and a drive — one bundle, fitted in a morning', 'link_url' => '/products/4-camera-shop-bundle'],
    ];

    /**
     * Showroom and installation photographs.
     *
     * @var list<string>
     */
    private const GALLERY_CAPTIONS = [
        'The Davao City showroom on McArthur Highway',
        'Live camera demonstration wall, Davao City branch',
        'Recorders and surveillance drives, Cebu City',
        'Bench testing a PTZ dome before it goes out on site',
        'An eight-camera handover to a division office',
        'Delivery loading at the Central Warehouse',
        'Commissioning a sixteen-channel system in Iloilo City',
        'The counter at General Santos City on a Saturday morning',
        'Technicians at the annual installation refresher, Davao City',
    ];

    /**
     * The news feed. Written pieces, ordered newest first; `days_ago` sets the
     * publication date relative to the seed run so the feed is never dated.
     *
     * @var list<array{title: string, author: string, days_ago: int, excerpt: string, paragraphs: list<string>}>
     */
    private const POSTS = [
        [
            'title' => 'IP or analogue? How to choose without overspending',
            'author' => 'Sentrix Sales Team',
            'days_ago' => 4,
            'excerpt' => 'The honest answer depends on one thing more than any other: whether there is already cable in the walls.',
            'paragraphs' => [
                'It is the question we are asked most across the counter, and the answer is not the one most price lists imply. An IP system gives better pictures, better search and a single cable per camera. An analogue system on cable that is already in the building costs a fraction of the labour, and on a finished building the labour is most of the quotation.',
                'If the building is new, or is being renovated with the walls open, run Cat6 and buy IP. There is no argument. One cable carries the picture and the power, the recorder does the person and vehicle filtering, and adding a camera later is a switch port rather than a second run.',
                'If the building already has coaxial cable to every corner, price the analogue upgrade before anything else. HD-TVI carries 5MP down the same RG59 that used to carry a fuzzy 700-line picture, so replacing eight cameras and one recorder can more than double the detail without a single new cable being pulled.',
                'The case that catches people out is the middle: an older building with coax in some places and not others. A penta-brid recorder takes both, so the existing runs stay and only the new positions get network cable. Bring a floor plan to any branch and we will mark it up with you rather than guess at it.',
            ],
        ],
        [
            'title' => 'Why we keep telling you not to buy copper-clad aluminium cable',
            'author' => 'Installation Department',
            'days_ago' => 12,
            'excerpt' => 'Nearly every camera that reboots at night is on cable that was bought at half price. Here is what actually happens in the wire.',
            'paragraphs' => [
                'Power over Ethernet sends current down the same pairs that carry the data. Copper carries that current with very little loss. Copper-clad aluminium — an aluminium core with a thin copper skin — has roughly 60 per cent more resistance, and resistance turns into heat and lost voltage over distance.',
                'The failure is gradual and it looks like something else. The camera works perfectly during the day. At dusk the infrared illuminators switch on, the camera draws its full power, the voltage at the end of a 70-metre run sags below what it needs, and it reboots. Then it comes back, and reboots again. In the morning everything is fine and nothing shows on the recorder except gaps.',
                'We are not arguing that solid copper is cheap. We are pointing out that the difference on a 305-metre box is about 2,000 pesos, and that a technician spending a day chasing an intermittent fault costs more than that before any cable is replaced. Half the service calls we take on other people\'s installations end at the cable.',
                'If a system is already dropping cameras at night, bring the make and the run lengths to any branch before buying anything. Some runs can be saved with a mid-span injector. The ones that cannot are quoted before any work starts.',
            ],
        ],
        [
            'title' => 'How much storage do you actually need?',
            'author' => 'Installation Department',
            'days_ago' => 21,
            'excerpt' => 'Most systems are fitted with a 2TB drive because that is what the box came with. Four things to work out before you choose.',
            'paragraphs' => [
                'Retention is the first question to answer and the last one anybody asks. Incidents are usually reported days after they happen — a missing delivery, a discrepancy found at stocktake, a complaint that took a week to escalate. A system that holds nine days of footage is not much use for any of them.',
                'Start with the camera count and the resolution. Eight 4MP cameras recording continuously produce roughly a terabyte a week. Sixteen produce two. That is the number to work from, and it is why a 2TB drive on an eight-camera system holds about a fortnight and no more.',
                'Then decide whether the cameras need to record continuously at all. Motion recording with a five-second pre-buffer can cut storage by half or more on a site that is empty at night, at the cost of the occasional missed event where the motion detection was set too tight. On a shop front we record continuously; on a stock room we usually do not.',
                'Finally, check whether anything obliges you to keep a specific period. Barangay and LGU permits increasingly specify thirty or sixty days, and some insurers do too. If a number is written down anywhere, fit the drive that meets it. And use a surveillance-rated drive: a desktop drive in a recorder is a failure with a date on it.',
            ],
        ],
        [
            'title' => 'Tagbilaran branch now stocks the full recorder and drive range',
            'author' => 'Sentrix Management',
            'days_ago' => 33,
            'excerpt' => 'Bohol customers no longer wait on a Cebu transfer for a recorder or a drive — the CPG Avenue branch now holds the complete line.',
            'paragraphs' => [
                'Until this month, the Tagbilaran branch carried cameras and the fastest-moving cable, and anything else came across on the Cebu transfer. That added two to four days to an order, which is a long time to have a site recording nothing.',
                'The branch now holds the full range: every recorder in the catalogue, all five surveillance drives, the alarm and access control lines, and the cable, connectors and power supplies that go with them.',
                'The stock figures shown on this site are per branch. Selecting Tagbilaran City before you browse will show what is actually on that shelf rather than a national total, so a reserved item is genuinely there when you arrive.',
                'The branch is on CPG Avenue in Poblacion II and can be reached on (038) 411 3094.',
            ],
        ],
        [
            'title' => 'What a system health check actually includes',
            'author' => 'Installation Department',
            'days_ago' => 45,
            'excerpt' => 'Not a look at the monitor and a wipe of the lenses. Here is the checklist our technicians work through, and what it costs.',
            'paragraphs' => [
                'A health check starts at the recorder, not the cameras, because the fault a customer describes and the fault the system has are not always the same thing. The log tells the technician whether channels have been dropping, whether the disk has been reporting errors, and how far back the footage actually goes.',
                'The drive is checked first: SMART attributes, reallocated sectors and the real retention period against what the customer believes it is. A drive that has been writing continuously for four years is near the end of its life whether or not it has failed yet, and it is cheaper to replace on a Tuesday than on the morning after an incident.',
                'Then every channel is reviewed at night as well as during the day, because a camera that looks fine at noon can be washed out by a security light or blinded by a spider web after dark. Lenses and domes are cleaned, focus is checked, and infrared cut filters are tested for the click that says they are still switching.',
                'Finally the terminations, the power supply voltages under load, and the surge protection are checked, and the remote app access is tested from outside the network — which is the part customers most often find has quietly stopped working. Standard health checks are quoted at the counter, parts separate and always approved before fitting. Systems bought from us are checked at a lower rate for the life of the system.',
            ],
        ],
        [
            'title' => 'Reserving stock online, and what happens when you arrive',
            'author' => 'Sentrix Sales Team',
            'days_ago' => 58,
            'excerpt' => 'The stock figure on this site is per branch and it is real. Here is what it commits us to.',
            'paragraphs' => [
                'The quantity shown against a product is the quantity at the branch you have selected, not a national total. If Cebu shows three recorders and Davao shows none, that is exactly what it means, and switching branches at the top of the page changes every figure on the screen.',
                'Placing an order reserves those items at that branch. The stock is decremented when the order is placed, not when you collect, so nobody can buy the last drive out from under a reservation that is already made.',
                'Orders are prepared for collection at the counter. Bring the order number — it starts with SNX — and the name the order was placed under. Payment is at the branch, so nothing has to be settled online before you have seen what you are buying.',
                'If something is wrong when you arrive, say so at the counter rather than taking it away. Anything unopened goes back on the shelf without argument, and a camera that has been mounted and drilled cannot.',
            ],
        ],
        [
            'title' => 'Five signs a camera needs replacing, not cleaning',
            'author' => 'Installation Department',
            'days_ago' => 71,
            'excerpt' => 'A cloudy picture is sometimes dirt. These five faults are the camera itself, and no amount of wiping will fix them.',
            'paragraphs' => [
                'A milky, low-contrast picture that does not improve after the dome is cleaned is usually moisture that has been inside the housing long enough to fog the lens from within. Once the seal has failed the camera will keep taking water in, and it is not economic to reseal one in the field.',
                'A picture that stays grey all day means the infrared cut filter has stuck. You can sometimes hear it fail to click at dusk. The camera still records, but every colour in the footage is gone, and colour is most of what makes a description usable.',
                'Purple or washed-out infrared at night, with the centre of the frame blown out, means the illuminators have aged unevenly. Replacing them is not a field repair on a sealed housing.',
                'Bands or a rolling flicker that follow the mains frequency point at the power supply rather than the camera — check that before condemning anything. But a fixed pattern of dead pixels, or a frame that has visibly lost focus with no zoom to adjust, is the sensor or the lens assembly, and both mean a new camera.',
            ],
        ],
        [
            'title' => 'Project pricing for schools, LGUs and cooperatives',
            'author' => 'Sentrix Sales Team',
            'days_ago' => 84,
            'excerpt' => 'Institutional pricing, purchase orders and staged installation across multiple sites.',
            'paragraphs' => [
                'Schools, local government units and cooperatives buy differently from a shop owner: the requirement is written down, the payment runs on a purchase order, and the work usually has to be staged across a term or a budget year rather than done in one week.',
                'We quote those jobs per site with a single price list across all of them, so a second campus fitted six months later does not cost more than the first. The quotation shows equipment, cable and labour separately, because most procurement offices need to see them that way.',
                'Purchase orders are accepted from institutions with a supplier accreditation on file. Delivery, installation and commissioning are quoted as line items rather than bundled, and staged installations are invoiced per site as each one is signed off.',
                'Send the site list, the camera positions if you have them, and any retention period written into your permit to the branch nearest you, or to sales@sentrix.ph, and a written quotation follows within two working days.',
            ],
        ],
    ];

    /**
     * Open vacancies. `branch` is a branch slug, or null for head office.
     *
     * @var list<array{title: string, branch: ?string, vacancies: int, employment_type: string, summary: string, responsibilities: list<string>, requirements: list<string>}>
     */
    private const CAREERS = [
        [
            'title' => 'Sales Associate', 'branch' => 'davao-city', 'vacancies' => 2, 'employment_type' => 'Full-time',
            'summary' => 'Serve walk-in customers on the showroom floor, quote systems and equipment, and follow through on institutional accounts.',
            'responsibilities' => [
                'Assist walk-in customers and match them to the right system for their site',
                'Prepare quotations for schools, LGUs and corporate accounts',
                'Maintain product knowledge across the camera, recorder, alarm and access control range',
                'Coordinate with the warehouse on stock availability and transfers',
                'Follow up on open quotations and purchase orders',
            ],
            'requirements' => [
                'Graduate of any four-year course, or equivalent retail sales experience',
                'At least one year in retail or technical sales, preferably security or IT equipment',
                'Comfortable explaining technical differences in plain language',
                'Willing to work weekends on a rotating schedule',
            ],
        ],
        [
            'title' => 'CCTV Installation Technician', 'branch' => 'cebu-city', 'vacancies' => 2, 'employment_type' => 'Full-time',
            'summary' => 'Install, commission and repair camera, alarm and access control systems on site across Cebu and the surrounding provinces.',
            'responsibilities' => [
                'Install and terminate coaxial and network cable, and mount cameras at height',
                'Commission recorders, configure recording schedules and set up remote access',
                'Diagnose and repair faults on existing camera, alarm and access control systems',
                'Carry out scheduled health checks on systems under service contract',
                'Keep accurate job records, as-built notes and parts usage per site',
            ],
            'requirements' => [
                'Graduate of Electronics Technology, Computer Technology or a related course',
                'TESDA NC II in Consumer Electronics Servicing or Computer Systems Servicing an advantage',
                'At least one year of hands-on installation experience with CCTV or structured cabling',
                'Comfortable working at height on a ladder or scaffold',
                'Valid driver\'s licence preferred; on-site work is the whole of the role',
            ],
        ],
        [
            'title' => 'Branch Manager', 'branch' => 'general-santos-city', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Run the General Santos branch: its sales targets, its stock position, and the team on the floor.',
            'responsibilities' => [
                'Own the branch sales target and report performance monthly',
                'Manage stock levels, reordering and transfers with the Central Warehouse',
                'Supervise sales, installation and administrative staff at the branch',
                'Handle escalated customer concerns and institutional accounts',
                'Ensure branch compliance with company policy and local permits',
            ],
            'requirements' => [
                'Graduate of Business Administration, Management or a related course',
                'At least three years of supervisory experience in retail or distribution',
                'Demonstrated ability to manage inventory and hit a sales target',
                'Resident of General Santos City or willing to relocate',
            ],
        ],
        [
            'title' => 'Stock Custodian', 'branch' => 'central-warehouse', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Keep the Central Warehouse count accurate and the branch transfers moving.',
            'responsibilities' => [
                'Receive, verify and put away incoming deliveries',
                'Prepare and dispatch branch transfers and customer deliveries',
                'Maintain accurate stock records in the inventory system',
                'Conduct cycle counts and reconcile discrepancies',
                'Flag slow-moving and low stock to the purchasing team',
            ],
            'requirements' => [
                'Graduate of any two- or four-year course',
                'At least one year in warehouse or inventory work',
                'Careful with numbers and comfortable with inventory software',
                'Forklift certification an advantage',
            ],
        ],
        [
            'title' => 'Accounting Staff', 'branch' => 'davao-city', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Handle branch collections, supplier payables and the reports that close the month.',
            'responsibilities' => [
                'Process branch collections and daily sales reconciliation',
                'Maintain accounts payable and supplier payment schedules',
                'Prepare BIR-compliant invoices and official receipts',
                'Assist with monthly closing and management reporting',
                'Support the annual audit with schedules and supporting documents',
            ],
            'requirements' => [
                'Graduate of Accountancy, Financial Management or a related course',
                'At least one year of accounting experience, retail or distribution preferred',
                'Working knowledge of BIR requirements for a retail business',
                'Proficient with spreadsheets and accounting software',
            ],
        ],
        [
            'title' => 'Installation and Delivery Driver', 'branch' => 'quezon-city', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Deliver equipment across Metro Manila and assist the installation team on site.',
            'responsibilities' => [
                'Deliver customer orders and branch transfers safely and on schedule',
                'Assist the installation team with equipment handling and site setup',
                'Maintain the delivery vehicle and keep its records current',
                'Secure customer acknowledgement on every delivery',
                'Plan routes around the Metro Manila truck ban',
            ],
            'requirements' => [
                'Valid professional driver\'s licence, restriction codes 1, 2 and 3',
                'At least two years of delivery driving experience',
                'Familiar with Metro Manila routes and truck ban schedules',
                'Physically able to handle equipment with a second person',
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $branches = Branch::query()->pluck('id', 'slug');

            $this->seedBanners();
            $this->seedGallery();
            $this->seedPosts();
            $this->seedCareers($branches);
        });
    }

    private function seedBanners(): void
    {
        foreach (self::BANNERS as $position => $banner) {
            Banner::query()->updateOrCreate(
                ['headline' => $banner['headline']],
                [
                    'image_path' => $this->media('banners', $position + 1) ?? 'demo/banners/banners-01.jpg',
                    'link_url' => $banner['link_url'],
                    'is_active' => true,
                    'position' => $position,
                ]
            );
        }
    }

    private function seedGallery(): void
    {
        foreach (self::GALLERY_CAPTIONS as $position => $caption) {
            GalleryImage::query()->updateOrCreate(
                ['caption' => $caption],
                [
                    'path' => $this->media('gallery', $position + 1) ?? 'demo/gallery/gallery-01.jpg',
                    'is_active' => true,
                    'position' => $position,
                ]
            );
        }
    }

    private function seedPosts(): void
    {
        foreach (self::POSTS as $index => $post) {
            Post::query()->updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    'title' => $post['title'],
                    'author' => $post['author'],
                    'excerpt' => $post['excerpt'],
                    'body' => $this->body($post['paragraphs']),
                    'thumbnail_path' => $this->media('posts', $index + 1),
                    'video_url' => null,
                    'published_at' => now()->subDays($post['days_ago'])->setTime(9, 0),
                ]
            );
        }
    }

    /**
     * @param  Collection<string, int>  $branches
     */
    private function seedCareers(Collection $branches): void
    {
        foreach (self::CAREERS as $career) {
            Career::query()->updateOrCreate(
                ['slug' => Str::slug($career['title'].' '.($career['branch'] ?? 'head office'))],
                [
                    'branch_id' => $career['branch'] === null ? null : ($branches[$career['branch']] ?? null),
                    'title' => $career['title'],
                    'vacancies' => $career['vacancies'],
                    'employment_type' => $career['employment_type'],
                    'description' => $this->vacancyDescription($career),
                    'apply_email' => 'careers@sentrix.ph',
                    'is_open' => true,
                ]
            );
        }
    }

    /**
     * @param  array{summary: string, responsibilities: list<string>, requirements: list<string>}  $career
     */
    private function vacancyDescription(array $career): string
    {
        return '<p>'.e($career['summary']).'</p>'
            .'<h3>Responsibilities</h3>'.$this->bulletList($career['responsibilities'])
            .'<h3>Requirements</h3>'.$this->bulletList($career['requirements']);
    }

    /**
     * @param  list<string>  $items
     */
    private function bulletList(array $items): string
    {
        return '<ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $items)).'</ul>';
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function body(array $paragraphs): string
    {
        return implode('', array_map(fn (string $text): string => '<p>'.e($text).'</p>', $paragraphs));
    }

    /**
     * A demo media path, or null when that file was never downloaded, so a clone
     * without media still seeds cleanly rather than pointing at a missing file.
     */
    private function media(string $area, int $index): ?string
    {
        $path = sprintf('demo/%s/%s-%02d.jpg', $area, $area, $index);

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
