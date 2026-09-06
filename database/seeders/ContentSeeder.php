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
        ['headline' => 'Ink tank printers from ₱6,450 — stocked at every branch', 'link_url' => '/shop?category=printers'],
        ['headline' => 'Genuine ink and toner, matched to the machine you own', 'link_url' => '/shop?category=ink-and-toner'],
        ['headline' => 'Duplicators for examination season — delivered and installed', 'link_url' => '/products/riso-rz-220-a4-digital-stencil-duplicator'],
    ];

    /**
     * Showroom and service photographs.
     *
     * @var list<string>
     */
    private const GALLERY_CAPTIONS = [
        'The Davao City showroom on McArthur Highway',
        'Ink tank demonstration units, Davao City branch',
        'Toner and consumables aisle, Cebu City',
        'Service bay — a Kyocera TASKalfa in for its annual clean',
        'Riso duplicator handover to a school division office',
        'Delivery loading at the Central Warehouse',
        'Copier installation and staff training in Iloilo City',
        'The counter at General Santos City on a Saturday morning',
        'Technicians at the annual service refresher, Davao City',
    ];

    /**
     * The news feed. Written pieces, ordered newest first; `days_ago` sets the
     * publication date relative to the seed run so the feed is never dated.
     *
     * @var list<array{title: string, author: string, days_ago: int, excerpt: string, paragraphs: list<string>}>
     */
    private const POSTS = [
        [
            'title' => 'Ink tank or laser? How to choose without overspending',
            'author' => 'Kompra Sales Team',
            'days_ago' => 4,
            'excerpt' => 'The honest answer depends on two numbers: how many pages you print a month, and whether any of them need to survive getting wet.',
            'paragraphs' => [
                'It is the question we are asked most across the counter, and the answer is not the one most price lists imply. An ink tank printer costs less to buy and far less to run than a cartridge machine, but a laser still beats both on speed and on the durability of the page.',
                'Under about 500 pages a month, buy the tank. An EcoTank L3250 or a PIXMA G3020 will handle school work, letters and the occasional photo, and a set of bottles lasts most households the better part of a year. Colour costs almost nothing, which matters if anyone in the house is printing projects.',
                'Above roughly a thousand pages a month, or where the output is contracts, receipts and records, buy the laser. Toner does not run when a page gets damp, which is why clinics, law offices and records rooms have never moved away from it. A Brother HL-L2350DW or a Canon MF3010 will clear a queue several times faster than any tank machine.',
                'The case that catches people out is the middle: a small office printing 600 to 800 pages a month, mostly text. There a duplex laser usually wins over the life of the machine, because it halves paper spend on anything longer than a page. Bring your monthly volume to any branch and we will work the cost per page out with you rather than guess at it.',
            ],
        ],
        [
            'title' => 'Why we keep telling you not to use unbranded refill ink',
            'author' => 'Service Department',
            'days_ago' => 12,
            'excerpt' => 'Nearly every printhead we replace comes off a machine that was refilled with ink of unknown origin. Here is what actually happens inside.',
            'paragraphs' => [
                'A printhead fires ink through nozzles measured in tens of microns. Genuine ink is formulated so that what dries in those nozzles between jobs re-dissolves the next time the printer runs a cleaning cycle. Ink that was not formulated for the head does not always re-dissolve.',
                'The failure is gradual and it looks like something else. First a colour bands. Then cleaning cycles fix it for a day. Then they stop fixing it, and by that point the blockage has hardened past anything a cleaning cycle can shift. The head is replaced, which on most tank printers costs more than a year of genuine bottles.',
                'We are not arguing that genuine ink is cheap. We are pointing out that a 65ml bottle of Epson 003 covers about 4,500 pages, which works out to a few centavos a page, and that the printhead it protects costs several thousand pesos to replace. The arithmetic is not close.',
                'If a machine is already refusing to clear its nozzles, bring it in before buying anything. Some blockages soak out. The ones that do not are quoted before any work starts.',
            ],
        ],
        [
            'title' => 'Getting a duplicator ready for examination season',
            'author' => 'Service Department',
            'days_ago' => 21,
            'excerpt' => 'A duplicator that sat idle since the last examination period will not start cleanly. Four things to check in the week before you need it.',
            'paragraphs' => [
                'Schools run their duplicators hard for two weeks and then leave them alone for months. That idle period, not the workload, is what causes most of the call-outs we get at the start of an examination period.',
                'Check the ink first. Duplicator ink thickens in a cartridge left partly used, and a machine fed thickened ink prints faint and then streaks. If the cartridge has been open since the last term, plan on a fresh one.',
                'Check the master roll next. A partly used roll stored in a hot room can deform enough that the master feeds crookedly, which shows up as a skewed image on every copy. Rolls should be kept sealed and out of direct sun.',
                'Then run fifty test copies of a page with solid black on it, before the day you need the machine. That clears the drum and shows any banding while there is still time to do something about it. Finally, check the paper: duplicator paper wants to be dry, and a ream that has been in a humid store room will jam.',
                'Any branch will run a pre-season service on a Riso or Duplo machine. Book it two weeks out; the week itself is always fully booked.',
            ],
        ],
        [
            'title' => 'Tagbilaran branch now stocks the full consumables range',
            'author' => 'Kompra Management',
            'days_ago' => 33,
            'excerpt' => 'Bohol customers no longer wait on a Cebu transfer for toner and ink — the CPG Avenue branch now holds the complete line.',
            'paragraphs' => [
                'Until this month, the Tagbilaran branch carried machines and the fastest-moving ink, and anything else came across on the Cebu transfer. That added two to four days to an order, which is a long time to have a copier standing idle.',
                'The branch now holds the full consumables range: every ink bottle and toner cartridge in the catalogue, master rolls and duplicator ink, plus the common spare parts — fusers, pickup rollers and waste containers.',
                'The stock figures shown on this site are per branch. Selecting Tagbilaran City before you browse will show what is actually on that shelf rather than a national total, so a reserved item is genuinely there when you arrive.',
                'The branch is on CPG Avenue in Poblacion II and can be reached on (038) 411 3094.',
            ],
        ],
        [
            'title' => 'What a printer service actually includes',
            'author' => 'Service Department',
            'days_ago' => 45,
            'excerpt' => 'Not a spray of contact cleaner and a wipe. Here is the checklist our technicians work through, and what it costs.',
            'paragraphs' => [
                'A service starts with a test print, because the fault a customer describes and the fault the machine has are not always the same thing. That page tells the technician whether the problem is in the head, the drum, the fuser or the paper path.',
                'The paper path is cleaned first: pickup rollers, separation pad and transport rollers, all of which glaze over with paper dust and stop gripping. This alone resolves most misfeed complaints. Rollers past cleaning are quoted for replacement before anything is fitted.',
                'On lasers the drum and transfer assembly are inspected for wear and the fuser is checked for the scoring that causes repeat marks down a page. On tank machines the head goes through a controlled clean, and on a blocked head a soak, which takes a day.',
                'Then the scanner glass and platen are cleaned, the firmware checked, and a second test print run and kept on file. Standard service is quoted at the counter, parts separate and always approved before fitting. Machines bought from us are serviced at a lower rate for the life of the machine.',
            ],
        ],
        [
            'title' => 'Reserving stock online, and what happens when you arrive',
            'author' => 'Kompra Sales Team',
            'days_ago' => 58,
            'excerpt' => 'Orders placed here are held at the branch you chose and paid for at the counter. No card details are taken online.',
            'paragraphs' => [
                'Every order on this site is a reservation against a specific branch. Choose the branch first and the catalogue shows that branch\'s stock; the quantity you see is the quantity on the shelf.',
                'At checkout you pick a pick-up date and time. The order number is issued immediately and the stock is set aside under it. Nothing is charged online — payment is made at the counter when you collect, in cash or by the terminal.',
                'Bring the order number. Staff pull the reservation by that number, and it is faster than describing what you ordered. If somebody else is collecting on your behalf, they need the number and nothing more.',
                'Bulky items — copiers, duplicators, printer stands — are marked as delivery-only, because they need two people and a vehicle. Those are scheduled by the branch after the order is placed.',
            ],
        ],
        [
            'title' => 'Five signs a printer needs a new pickup roller, not a new printer',
            'author' => 'Service Department',
            'days_ago' => 72,
            'excerpt' => 'The cheapest repair in the catalogue fixes the complaint that most often gets a working machine replaced.',
            'paragraphs' => [
                'A printer that pulls two sheets at once, or none at all, is almost never worn out. It has a glazed pickup roller, and the part costs a few hundred pesos.',
                'The signs are consistent: paper feeds crookedly; two sheets go through together; the tray reports empty when it is not; a page stalls halfway and reports a jam that is not there; or feeding works from the rear tray but not the main one.',
                'Any of those, on a machine that otherwise prints cleanly, points at the rollers rather than the engine. The rubber hardens and polishes with age until it slides across the top sheet instead of gripping it.',
                'Replacement takes a few minutes on most desktop lasers and the universal roller set fits the common HP, Canon and Samsung trays. Bring the machine to any branch, or buy the set and fit it yourself — it is the one repair we are happy to talk a customer through over the phone.',
            ],
        ],
        [
            'title' => 'Bulk pricing on paper and toner for schools and LGUs',
            'author' => 'Kompra Sales Team',
            'days_ago' => 88,
            'excerpt' => 'Ordering by the box rather than the ream, and what we can quote for an institutional purchase order.',
            'paragraphs' => [
                'Bond paper is priced per ream at the counter, but almost nobody buying for a school or a municipal office wants ten separate reams. A box of ten carries a lower unit price, and the discount grows past five boxes.',
                'The same applies to toner. Copier toners such as the Konica Minolta TN-116 and the Kyocera TK-1175 are quoted by the box, which is how a division office covers an examination period without a mid-run reorder.',
                'We supply against institutional purchase orders and can provide the quotations, delivery receipts and official receipts that a procurement file needs. Terms are arranged per account rather than advertised.',
                'Send the item list and quantities to the branch nearest you, or to sales@kompra.ph, and a written quotation follows within a working day.',
            ],
        ],
    ];

    /**
     * Open vacancies, keyed to the branch slug they are posted at.
     *
     * @var list<array{title: string, branch: ?string, vacancies: int, employment_type: string, summary: string, responsibilities: list<string>, requirements: list<string>}>
     */
    private const CAREERS = [
        [
            'title' => 'Sales Associate', 'branch' => 'davao-city', 'vacancies' => 2, 'employment_type' => 'Full-time',
            'summary' => 'Serve walk-in customers on the showroom floor, quote machines and consumables, and follow through on institutional accounts.',
            'responsibilities' => [
                'Assist walk-in customers and match them to the right machine for their print volume',
                'Prepare quotations for schools, LGUs and corporate accounts',
                'Maintain product knowledge across the printer, copier and consumables range',
                'Coordinate with the warehouse on stock availability and transfers',
                'Follow up on open quotations and purchase orders',
            ],
            'requirements' => [
                'Graduate of any four-year course, or equivalent retail sales experience',
                'At least one year in retail or technical sales, preferably office equipment',
                'Comfortable explaining technical differences in plain language',
                'Willing to work weekends on a rotating schedule',
            ],
        ],
        [
            'title' => 'Service Technician', 'branch' => 'cebu-city', 'vacancies' => 2, 'employment_type' => 'Full-time',
            'summary' => 'Diagnose and repair printers, copiers and duplicators in the workshop and on site across Cebu and the surrounding provinces.',
            'responsibilities' => [
                'Diagnose and repair inkjet, laser and duplicator equipment',
                'Carry out preventive maintenance on customer machines under service contract',
                'Perform on-site installation and operator training',
                'Keep accurate service records and parts usage per job',
                'Advise the branch on parts that should be held in stock',
            ],
            'requirements' => [
                'Graduate of Electronics Technology, Computer Technology or a related course',
                'TESDA NC II in Consumer Electronics Servicing an advantage',
                'At least one year of hands-on repair experience with office equipment',
                'Valid driver\'s licence preferred; on-site work is part of the role',
            ],
        ],
        [
            'title' => 'Branch Manager', 'branch' => 'general-santos-city', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Run the General Santos branch: its sales targets, its stock position, and the team on the floor.',
            'responsibilities' => [
                'Own the branch sales target and report performance monthly',
                'Manage stock levels, reordering and transfers with the Central Warehouse',
                'Supervise sales, service and administrative staff at the branch',
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
                'Support internal and external audit requirements',
            ],
            'requirements' => [
                'Graduate of Accountancy, Accounting Technology or Financial Management',
                'At least one year of general accounting experience',
                'Working knowledge of BIR reporting requirements',
                'Proficient in spreadsheets and accounting software',
            ],
        ],
        [
            'title' => 'Delivery Driver', 'branch' => 'quezon-city', 'vacancies' => 1, 'employment_type' => 'Full-time',
            'summary' => 'Deliver and help install equipment across Metro Manila and nearby provinces.',
            'responsibilities' => [
                'Deliver machines and consumables to customer sites on schedule',
                'Assist technicians with unloading and positioning of copiers and duplicators',
                'Secure signed delivery receipts and return them to the branch daily',
                'Carry out daily vehicle checks and keep the vehicle roadworthy',
                'Report delivery issues and customer concerns to the branch promptly',
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
                    'apply_email' => 'careers@kompra.ph',
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
