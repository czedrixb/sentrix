<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The trading catalogue: the machines, consumables, parts and accessories the
 * business actually sells.
 *
 * Model names and part numbers are the real products carried by the brands in
 * BrandSeeder. Prices are indicative Philippine street prices and stock levels
 * are illustrative -- both are ordinary admin-editable columns and are expected
 * to be replaced with the client's own figures.
 */
class CatalogSeeder extends Seeder
{
    /**
     * Stock profiles, keyed by how the item moves. `base` is the quantity a
     * pickup branch holds; `threshold` is the level the dashboard treats as low.
     *
     * Nothing is seeded at zero: the storefront's add-to-cart path asserts stock
     * at the selected branch, so a zero would make whichever product happened to
     * sort first unpurchasable.
     *
     * @var array<string, array{base: int, threshold: int}>
     */
    private const STOCK_PROFILES = [
        'bulk' => ['base' => 80, 'threshold' => 20],
        'stocked' => ['base' => 30, 'threshold' => 8],
        'machine' => ['base' => 8, 'threshold' => 3],
        'low' => ['base' => 2, 'threshold' => 5],
    ];

    /**
     * The catalogue.
     *
     * `image` names the demo photo set the product draws from, so a toner shows
     * a toner rather than whatever the next stock photo happened to be.
     *
     * @var list<array{sku: string, name: string, brand: ?string, category: string, price: float, image: string, stock: string, short: string, body: string, specs: list<string>, dimensions: array{0: float, 1: float, 2: float, 3: float}, featured?: bool, delivery?: bool}>
     */
    private const PRODUCTS = [
        // --- Printers ------------------------------------------------------
        [
            'sku' => 'EPS-L3210', 'name' => 'Epson EcoTank L3210 A4 All-in-One Ink Tank Printer',
            'brand' => 'epson', 'category' => 'printers', 'price' => 8995, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Print, scan and copy from refillable tanks — the workhorse for a home office watching its cost per page.',
            'body' => 'The EcoTank L3210 replaces cartridges with four refillable tanks, so a full set of 003 bottles carries the machine through thousands of pages instead of a few hundred. It is the printer we sell most of, and the one we recommend to any office printing under a thousand pages a month.',
            'specs' => ['Print, scan and copy', 'Up to 33 ppm draft black', '5,760 x 1,440 dpi', 'USB 2.0, no wireless', 'Uses Epson 003 ink bottles'],
            'dimensions' => [37.5, 34.7, 17.9, 3.9],
        ],
        [
            'sku' => 'EPS-L3250', 'name' => 'Epson EcoTank L3250 A4 Wi-Fi All-in-One Ink Tank Printer',
            'brand' => 'epson', 'category' => 'printers', 'price' => 10495, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The L3210 with Wi-Fi and mobile printing, for a desk that is not next to the computer.',
            'body' => 'Same tank system and the same 003 bottles as the L3210, with Wi-Fi Direct and the Epson Smart Panel app added. If more than one person needs to print, this is the model to buy — the price difference is recovered the first time somebody does not have to move a cable.',
            'specs' => ['Print, scan and copy', 'Wi-Fi and Wi-Fi Direct', 'Up to 33 ppm draft black', 'Epson Smart Panel mobile printing', 'Uses Epson 003 ink bottles'],
            'dimensions' => [37.5, 34.7, 17.9, 3.9], 'featured' => true,
        ],
        [
            'sku' => 'EPS-L5290', 'name' => 'Epson EcoTank L5290 A4 Wi-Fi All-in-One Ink Tank Printer with ADF',
            'brand' => 'epson', 'category' => 'printers', 'price' => 15995, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Adds a 30-sheet document feeder and fax — the small-office model for offices that still send forms.',
            'body' => 'Where the L3250 stops, the L5290 adds a 30-sheet automatic document feeder and a fax line, which still matters for permits, bank forms and government filings. Ethernet is included alongside Wi-Fi, so it can sit on a wired network without an adapter.',
            'specs' => ['Print, scan, copy and fax', '30-sheet automatic document feeder', 'Wi-Fi, Wi-Fi Direct and Ethernet', 'Up to 33 ppm draft black', 'Uses Epson 003 ink bottles'],
            'dimensions' => [37.5, 34.7, 23.7, 5.5],
        ],
        [
            'sku' => 'EPS-L15150', 'name' => 'Epson EcoTank L15150 A3+ Wi-Fi Duplex All-in-One Ink Tank Printer',
            'brand' => 'epson', 'category' => 'printers', 'price' => 49995, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A3+ duplex printing with tanks, for layout studios, schools and anyone printing plans in-house.',
            'body' => 'The largest EcoTank we carry. It prints to A3+ on both sides, holds two paper trays, and runs on 008 bottles that yield thousands of pages — which is what makes in-house A3 work cheaper than sending it out. Delivery and setup are included within city limits.',
            'specs' => ['A3+ print, scan, copy and fax', 'Automatic duplex to A3', 'Two 250-sheet trays plus rear feed', 'Wi-Fi, Ethernet and USB', 'Uses Epson 008 ink bottles'],
            'dimensions' => [52.6, 44.6, 34.1, 22.4], 'featured' => true, 'delivery' => true,
        ],
        [
            'sku' => 'CAN-G2010', 'name' => 'Canon PIXMA G2010 A4 All-in-One Ink Tank Printer',
            'brand' => 'canon', 'category' => 'printers', 'price' => 8450, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Canon\'s entry ink tank all-in-one, with a front tank window you can read at a glance.',
            'body' => 'The G2010 prints, scans and copies from integrated tanks refilled with GI-790 bottles. The tanks sit at the front behind a clear window, so nobody has to guess how much ink is left. A straightforward machine with very few things on it to break.',
            'specs' => ['Print, scan and copy', 'Up to 8.8 ipm black', '4,800 x 1,200 dpi', 'Front-mounted visible ink tanks', 'Uses Canon GI-790 ink bottles'],
            'dimensions' => [44.5, 33.0, 16.3, 6.3],
        ],
        [
            'sku' => 'CAN-G3020', 'name' => 'Canon PIXMA G3020 A4 Wi-Fi All-in-One Ink Tank Printer',
            'brand' => 'canon', 'category' => 'printers', 'price' => 10250, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The G2010 with wireless and mobile printing from the Canon PRINT app.',
            'body' => 'Wi-Fi and Wi-Fi Direct added to the G2010 platform, printable from phones and tablets through the Canon PRINT app. The same GI-790 bottles, so a branch that already stocks ink for a G2010 does not need a second line item.',
            'specs' => ['Print, scan and copy', 'Wi-Fi and Wi-Fi Direct', 'Canon PRINT mobile app', 'Up to 8.8 ipm black', 'Uses Canon GI-790 ink bottles'],
            'dimensions' => [44.5, 33.0, 16.3, 6.4],
        ],
        [
            'sku' => 'CAN-MF3010', 'name' => 'Canon imageCLASS MF3010 A4 Monochrome Laser Multifunction Printer',
            'brand' => 'canon', 'category' => 'printers', 'price' => 12900, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The small-office mono laser that will not smudge — print, scan and copy in one compact frame.',
            'body' => 'Laser output does not run when a page gets wet, which is why records offices, clinics and law offices keep asking for the MF3010. It prints, scans and copies at 18 pages per minute and takes the widely stocked Cartridge 325 or 337.',
            'specs' => ['Print, scan and copy', 'Up to 18 ppm A4 mono', '1,200 x 600 dpi effective', 'USB 2.0 Hi-Speed', 'Uses Canon Cartridge 337'],
            'dimensions' => [37.2, 27.6, 25.3, 8.4], 'featured' => true,
        ],
        [
            'sku' => 'CAN-LBP2900', 'name' => 'Canon imageCLASS LBP2900 A4 Monochrome Laser Printer',
            'brand' => 'canon', 'category' => 'printers', 'price' => 9750, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A print-only mono laser that has outlasted most of what was sold alongside it.',
            'body' => 'No scanner, no screen, no network — just a reliable 12 ppm laser engine on a USB cable. We still sell these to counters and cashier stations where the job is receipts and forms and nothing else, and parts remain easy to source.',
            'specs' => ['Print only', 'Up to 12 ppm A4 mono', '2,400 x 600 dpi', 'USB 2.0', 'Uses Canon Cartridge 303'],
            'dimensions' => [36.4, 24.9, 21.7, 5.7],
        ],
        [
            'sku' => 'BRO-DCPT420W', 'name' => 'Brother DCP-T420W A4 Wi-Fi All-in-One Ink Tank Printer',
            'brand' => 'brother', 'category' => 'printers', 'price' => 8890, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Brother\'s refillable-tank all-in-one, with a leak-resistant bottle design.',
            'body' => 'The DCP-T420W fills from BT5000 colour and BTD60BK black bottles that key into the tank rather than pouring free-hand, which is the difference between a refill and a mess. Wi-Fi is built in and setup runs from the Brother Mobile Connect app.',
            'specs' => ['Print, scan and copy', 'Wi-Fi and Wi-Fi Direct', 'Up to 16.5 ipm draft black', 'Leak-resistant refill design', 'Uses Brother BT5000 and BTD60BK ink'],
            'dimensions' => [43.5, 36.0, 16.1, 6.6],
        ],
        [
            'sku' => 'BRO-DCPT720DW', 'name' => 'Brother DCP-T720DW A4 Wi-Fi Duplex All-in-One Ink Tank Printer',
            'brand' => 'brother', 'category' => 'printers', 'price' => 13450, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Automatic two-sided printing and a document feeder, on the same refillable tanks.',
            'body' => 'Automatic duplex halves paper spend on anything longer than a page, and the 20-sheet feeder means a stack of documents scans without standing over it. The obvious upgrade for an office that has outgrown a single-sided printer.',
            'specs' => ['Print, scan and copy', 'Automatic duplex printing', '20-sheet automatic document feeder', 'Wi-Fi, Wi-Fi Direct and Ethernet', 'Uses Brother BT5000 and BTD60BK ink'],
            'dimensions' => [43.5, 36.0, 25.0, 8.4],
        ],
        [
            'sku' => 'BRO-HLL2350DW', 'name' => 'Brother HL-L2350DW A4 Monochrome Laser Printer with Duplex',
            'brand' => 'brother', 'category' => 'printers', 'price' => 11990, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A 32 ppm duplex mono laser — fast, wireless, and cheap to keep in toner.',
            'body' => 'At 32 pages per minute with automatic duplex, the HL-L2350DW clears a print queue quickly and quietly. The TN-2xxx toner line is stocked at every branch, and a high-yield unit brings the cost per page down further.',
            'specs' => ['Print only', 'Up to 32 ppm A4 mono', 'Automatic duplex printing', 'Wi-Fi, Wi-Fi Direct and USB', '250-sheet paper tray'],
            'dimensions' => [35.6, 36.0, 18.3, 7.2],
        ],
        [
            'sku' => 'HP-M111W', 'name' => 'HP LaserJet M111w A4 Monochrome Wireless Laser Printer',
            'brand' => 'hp', 'category' => 'printers', 'price' => 7950, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The smallest wireless mono laser we carry — it fits on a shelf beside the monitor.',
            'body' => 'A compact 21 ppm laser with Wi-Fi, aimed at a desk that has no room for a multifunction. Prints from the HP Smart app on a phone without a computer in the middle.',
            'specs' => ['Print only', 'Up to 21 ppm A4 mono', 'Wi-Fi and USB 2.0', 'HP Smart app printing', 'Uses HP 136A toner'],
            'dimensions' => [34.2, 18.9, 15.9, 4.3],
        ],
        [
            'sku' => 'HP-M141W', 'name' => 'HP LaserJet MFP M141w A4 Monochrome Wireless Multifunction Printer',
            'brand' => 'hp', 'category' => 'printers', 'price' => 11250, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'Print, scan and copy over Wi-Fi from a laser small enough for a reception counter.',
            'body' => 'The M111w with a flatbed scanner on top. Twenty-one pages per minute, wireless, and light enough that one person can move it between rooms — which is what reception desks and small clinics actually need.',
            'specs' => ['Print, scan and copy', 'Up to 21 ppm A4 mono', 'Wi-Fi and USB 2.0', 'HP Smart app printing', 'Uses HP 136A toner'],
            'dimensions' => [36.0, 27.4, 25.3, 7.4],
        ],
        [
            'sku' => 'HP-IT415', 'name' => 'HP Ink Tank 415 A4 Wireless All-in-One Printer',
            'brand' => 'hp', 'category' => 'printers', 'price' => 9690, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'HP\'s refillable-tank all-in-one, with spill-free filling and wireless printing.',
            'body' => 'A tank printer for households and small offices printing in colour: photos, school work, flyers. GT52 and GT53 bottles key into the tank so a refill does not stain the desk.',
            'specs' => ['Print, scan and copy', 'Wi-Fi and Wi-Fi Direct', 'Up to 19 ppm draft black', 'Spill-free refilling', 'Uses HP GT52 and GT53 ink'],
            'dimensions' => [52.5, 31.0, 15.8, 5.2],
        ],
        [
            'sku' => 'PAN-P2500W', 'name' => 'Pantum P2500W A4 Monochrome Wireless Laser Printer',
            'brand' => 'pantum', 'category' => 'printers', 'price' => 6450, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The lowest-priced wireless laser on the floor, and the toner is cheap to replace.',
            'body' => 'Pantum sells on price without giving up the things that matter in a laser: 22 pages per minute, Wi-Fi, and a toner unit that costs a fraction of the branded alternatives. A common first laser for students and start-up offices.',
            'specs' => ['Print only', 'Up to 22 ppm A4 mono', 'Wi-Fi and USB 2.0', '150-sheet input tray', 'Uses Pantum PC-210 toner'],
            'dimensions' => [33.7, 22.0, 17.8, 5.0],
        ],
        [
            'sku' => 'PAN-M6500NW', 'name' => 'Pantum M6500NW A4 Monochrome Network Laser Multifunction Printer',
            'brand' => 'pantum', 'category' => 'printers', 'price' => 9150, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A networked laser all-in-one at an ink-tank price.',
            'body' => 'Print, scan and copy with both Wi-Fi and Ethernet, which is unusual at this price. Shops and internet cafes buy these in pairs because a spare costs less than a service call on something bigger.',
            'specs' => ['Print, scan and copy', 'Up to 22 ppm A4 mono', 'Wi-Fi and Ethernet', 'Uses Pantum PC-210 toner', '150-sheet input tray'],
            'dimensions' => [41.5, 30.5, 25.4, 7.3],
        ],
        [
            'sku' => 'KYO-P2040DN', 'name' => 'Kyocera ECOSYS P2040dn A4 Monochrome Network Laser Printer',
            'brand' => 'kyocera', 'category' => 'printers', 'price' => 18900, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A long-life drum and 40 ppm duplex output — built for offices that print all day.',
            'body' => 'ECOSYS separates the drum from the toner, so a toner change is only toner. Over a machine\'s life that is the single biggest saving available on a mono laser, and it is why these end up in registrar\'s offices and accounting departments.',
            'specs' => ['Print only', 'Up to 40 ppm A4 mono', 'Automatic duplex printing', 'Gigabit Ethernet and USB', 'Uses Kyocera TK-1175 toner kit'],
            'dimensions' => [37.5, 39.3, 26.0, 11.5], 'delivery' => true,
        ],
        [
            'sku' => 'KYO-M2040DN', 'name' => 'Kyocera ECOSYS M2040dn A4 Monochrome Laser Multifunction Printer',
            'brand' => 'kyocera', 'category' => 'printers', 'price' => 27500, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'The P2040dn plus a 50-sheet feeder, colour scanning and a touch panel.',
            'body' => 'A departmental multifunction on the same ECOSYS engine: 40 ppm duplex printing, colour scan to email or folder, and a 50-sheet reversing document feeder. Sold with an on-site service plan.',
            'specs' => ['Print, scan and copy', 'Up to 40 ppm A4 mono', '50-sheet reversing document feeder', 'Colour scan to email, USB and folder', 'Uses Kyocera TK-1175 toner kit'],
            'dimensions' => [47.7, 41.2, 41.0, 22.5], 'delivery' => true,
        ],
        [
            'sku' => 'OKI-B432DN', 'name' => 'OKI B432dn A4 Monochrome Laser Printer',
            'brand' => 'oki', 'category' => 'printers', 'price' => 29900, 'image' => 'printer', 'stock' => 'low',
            'short' => 'A heavy-duty 40 ppm mono laser rated for 80,000 pages a month.',
            'body' => 'OKI builds for duty cycle. The B432dn is specified at up to 80,000 pages a month, which is several times what a typical office laser will take, and it is the machine we put in place where a printer has already been worn out once.',
            'specs' => ['Print only', 'Up to 40 ppm A4 mono', 'Automatic duplex printing', 'Gigabit Ethernet and USB', '80,000 pages per month duty cycle'],
            'dimensions' => [39.0, 39.0, 24.5, 12.6], 'delivery' => true,
        ],
        [
            'sku' => 'SAM-M2020', 'name' => 'Samsung Xpress M2020 A4 Monochrome Laser Printer',
            'brand' => 'samsung', 'category' => 'printers', 'price' => 5950, 'image' => 'printer', 'stock' => 'machine',
            'short' => 'A compact 20 ppm mono laser, still the cheapest way onto laser output.',
            'body' => 'Print-only, USB, and small enough to sit under a counter. The MLT-D111S toner is stocked everywhere, which is the main reason these keep running years after the box was opened.',
            'specs' => ['Print only', 'Up to 20 ppm A4 mono', 'USB 2.0', '150-sheet input tray', 'Uses Samsung MLT-D111S toner'],
            'dimensions' => [33.1, 21.5, 17.8, 4.0],
        ],

        // --- Copiers and duplicators ---------------------------------------
        [
            'sku' => 'RIC-MP2014', 'name' => 'Ricoh MP 2014 A3 Monochrome Digital Copier',
            'brand' => 'ricoh', 'category' => 'copiers', 'price' => 79500, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'The entry A3 office copier — 20 pages per minute, and simple enough that staff need no training.',
            'body' => 'A workgroup copier that prints and copies to A3 at 20 pages a minute from a 350-sheet capacity, expandable to 1,350. The control panel is deliberately plain, which is why it ends up in schools and barangay offices where whoever is at the counter has to be able to use it.',
            'specs' => ['A3 print and copy', 'Up to 20 ppm A3 mono', '350-sheet standard capacity, 1,350 maximum', 'Optional network printing and scanning', 'Uses Ricoh MP 2014 toner'],
            'dimensions' => [58.7, 55.0, 46.2, 32.0], 'featured' => true, 'delivery' => true,
        ],
        [
            'sku' => 'RIC-IM2500', 'name' => 'Ricoh IM 2500 A3 Monochrome Multifunction Copier',
            'brand' => 'ricoh', 'category' => 'copiers', 'price' => 128000, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A 25 ppm A3 multifunction with a touch panel and scan to email, folder and cloud.',
            'body' => 'The IM series runs Ricoh\'s Smart Operation Panel, so scanning to email or a shared folder is two taps rather than a driver setup. Twenty-five pages a minute to A3, with a document feeder and duplex as standard. Installed and configured on site.',
            'specs' => ['A3 print, copy and scan', 'Up to 25 ppm A3 mono', '10.1-inch Smart Operation Panel', 'Scan to email, folder and USB', 'Automatic duplex and document feeder'],
            'dimensions' => [58.7, 68.5, 76.0, 68.0], 'delivery' => true,
        ],
        [
            'sku' => 'KYO-TA2020', 'name' => 'Kyocera TASKalfa 2020 A3 Monochrome Digital Copier',
            'brand' => 'kyocera', 'category' => 'copiers', 'price' => 74900, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A durable A3 copier on the long-life ECOSYS drum, for high-volume mono work.',
            'body' => 'Twenty pages a minute to A3 with the same separated drum and toner approach as the ECOSYS printers. Print shops and copy centres buy these for the running cost, not the sticker price.',
            'specs' => ['A3 print and copy', 'Up to 20 ppm A3 mono', 'Long-life drum, toner-only replacement', '300-sheet standard capacity', 'Optional network and scan kit'],
            'dimensions' => [59.4, 60.5, 47.0, 34.0], 'delivery' => true,
        ],
        [
            'sku' => 'KYO-TA2321', 'name' => 'Kyocera TASKalfa 2321 A3 Monochrome Multifunction Copier',
            'brand' => 'kyocera', 'category' => 'copiers', 'price' => 96500, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'Network printing, colour scanning and 23 ppm A3 output as standard.',
            'body' => 'Where the TASKalfa 2020 needs option kits, the 2321 arrives with network printing and colour scanning already fitted. The step most offices actually want, and the one that stops a copier from becoming a standalone island.',
            'specs' => ['A3 print, copy and colour scan', 'Up to 23 ppm A3 mono', 'Standard network printing', 'Automatic duplex', 'Uses Kyocera TK-4108 toner'],
            'dimensions' => [59.4, 62.0, 58.5, 46.0], 'delivery' => true,
        ],
        [
            'sku' => 'KON-B226', 'name' => 'Konica Minolta bizhub 226 A3 Monochrome Multifunction Copier',
            'brand' => 'konica', 'category' => 'copiers', 'price' => 82000, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A 22 ppm A3 multifunction with a tilting panel and a genuinely simple menu.',
            'body' => 'The bizhub 226 covers print, copy and scan to A3 at 22 pages a minute. The panel tilts, which sounds trivial until a copier is installed against a wall in a room with overhead lights. Widely serviced and easy to source parts for.',
            'specs' => ['A3 print, copy and scan', 'Up to 22 ppm A3 mono', 'Tilting control panel', 'Scan to email, SMB and FTP', 'Uses Konica Minolta TN-116 toner'],
            'dimensions' => [57.0, 60.0, 55.0, 42.0], 'delivery' => true,
        ],
        [
            'sku' => 'KON-B300I', 'name' => 'Konica Minolta bizhub 300i A3 Monochrome Multifunction Copier',
            'brand' => 'konica', 'category' => 'copiers', 'price' => 165000, 'image' => 'photocopier', 'stock' => 'low',
            'short' => 'A 30 ppm production-grade A3 multifunction with a 10-inch tablet panel.',
            'body' => 'The largest machine on the floor. Thirty pages a minute to A3, a 10.1-inch panel that behaves like a tablet, and finishing options for stapling and sorting. Specified for departments producing bound reports and examination sets in-house.',
            'specs' => ['A3 print, copy and colour scan', 'Up to 30 ppm A3 mono', '10.1-inch tablet control panel', 'Optional stapling and finishing', 'Scan to email, folder, USB and cloud'],
            'dimensions' => [61.5, 66.0, 78.0, 79.0], 'delivery' => true,
        ],
        [
            'sku' => 'XER-B215', 'name' => 'Xerox B215 A4 Monochrome Multifunction Printer',
            'brand' => 'xerox', 'category' => 'copiers', 'price' => 24900, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A desktop A4 multifunction with fax and a 50-sheet feeder, at a fraction of a floor-standing copier.',
            'body' => 'For an office that needs copier behaviour without copier footprint: 30 pages a minute, duplex, a 50-sheet document feeder, fax, Wi-Fi and Ethernet. Sits on a table rather than requiring floor space and a power circuit of its own.',
            'specs' => ['Print, copy, scan and fax', 'Up to 30 ppm A4 mono', '50-sheet automatic document feeder', 'Wi-Fi and Ethernet', 'Automatic duplex printing'],
            'dimensions' => [41.4, 38.9, 36.2, 12.4], 'delivery' => true,
        ],
        [
            'sku' => 'FUJ-S2011', 'name' => 'Fuji DocuCentre S2011 A3 Monochrome Multifunction Copier',
            'brand' => 'fuji', 'category' => 'copiers', 'price' => 68500, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A compact A3 copier that fits where a full workgroup machine will not.',
            'body' => 'The DocuCentre S2011 prints and copies to A3 at 20 pages a minute from a noticeably smaller frame than its competitors. The usual choice when the space allocated for a copier turns out to be a corner rather than a room.',
            'specs' => ['A3 print, copy and scan', 'Up to 20 ppm A3 mono', 'Compact footprint', 'Scan to email and folder', 'Uses Fuji CT202384 toner'],
            'dimensions' => [59.5, 53.0, 47.0, 30.0], 'delivery' => true,
        ],
        [
            'sku' => 'RIS-RZ220', 'name' => 'Riso RZ 220 A4 Digital Stencil Duplicator',
            'brand' => 'riso', 'category' => 'copiers', 'price' => 118000, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'The examination-and-handout machine: 130 pages a minute at a fraction of a peso a copy.',
            'body' => 'A duplicator cuts a master once and then prints from it at 130 pages a minute, which is why every school division office has one at examination time. Past a few hundred copies of the same page nothing else comes close on cost. Sold with the first master roll and ink cartridge.',
            'specs' => ['Up to 130 pages per minute', 'A4 and B4 stencil duplicating', 'Automatic master making', 'Uses Riso S-4253 ink and master rolls', 'Ideal above 200 copies per original'],
            'dimensions' => [125.0, 65.5, 68.0, 96.0], 'featured' => true, 'delivery' => true,
        ],
        [
            'sku' => 'DUP-DPA100', 'name' => 'Duplo DP-A100 A4 Digital Duplicator',
            'brand' => 'duplo', 'category' => 'copiers', 'price' => 105000, 'image' => 'photocopier', 'stock' => 'machine',
            'short' => 'A 130 ppm digital duplicator for schools, churches and campaign printing.',
            'body' => 'The Duplo alternative to the Riso, at 130 pages a minute with automatic master making and a smaller footprint. Consumables are stocked in Davao and Cebu, and the service interval is long enough that most owners see us once a year.',
            'specs' => ['Up to 130 pages per minute', 'A4 and B4 duplicating', 'Automatic master making', 'Uses Duplo DP-A100 ink and masters', '600 x 600 dpi scanning'],
            'dimensions' => [118.0, 61.0, 63.5, 84.0], 'delivery' => true,
        ],

        // --- Scanners -------------------------------------------------------
        [
            'sku' => 'EPS-DS1630', 'name' => 'Epson WorkForce DS-1630 A4 Flatbed Document Scanner',
            'brand' => 'epson', 'category' => 'scanners', 'price' => 21900, 'image' => 'scanner', 'stock' => 'machine',
            'short' => 'Flatbed plus a 50-sheet feeder — for archives that contain both stacks and bound books.',
            'body' => 'Back-file conversion runs into bound registers and stapled documents that a sheet-fed scanner cannot take. The DS-1630 has both a 50-sheet automatic feeder for loose pages and a flatbed for everything else, at 25 pages a minute.',
            'specs' => ['A4 flatbed and 50-sheet feeder', 'Up to 25 ppm simplex', '1,200 dpi optical resolution', 'Single-pass duplex scanning', 'USB 3.0'],
            'dimensions' => [45.2, 33.5, 17.8, 4.0],
        ],
        [
            'sku' => 'CAN-DRC225', 'name' => 'Canon imageFORMULA DR-C225 A4 Sheet-fed Document Scanner',
            'brand' => 'canon', 'category' => 'scanners', 'price' => 24500, 'image' => 'scanner', 'stock' => 'machine',
            'short' => 'An upright sheet-fed scanner with a J-shaped path — it takes up almost no desk.',
            'body' => 'The DR-C225 stands vertically and returns pages to the front, so it occupies about the area of a sheet of paper. Twenty-five pages a minute in duplex, and it handles ID cards and receipts without a carrier sheet.',
            'specs' => ['30-sheet automatic document feeder', 'Up to 25 ppm duplex', '600 dpi optical resolution', 'Upright J-path design', 'Scans ID cards and receipts'],
            'dimensions' => [30.0, 15.6, 25.0, 2.6],
        ],
        [
            'sku' => 'BRO-ADS1200', 'name' => 'Brother ADS-1200 A4 Portable Document Scanner',
            'brand' => 'brother', 'category' => 'scanners', 'price' => 15750, 'image' => 'scanner', 'stock' => 'machine',
            'short' => 'A bus-powered scanner that fits in a bag — for field audits and site inspections.',
            'body' => 'Runs off the USB cable with no separate power brick, so an auditor or field officer can scan on site and carry nothing else. Twenty-five pages a minute in duplex from a 20-sheet feeder.',
            'specs' => ['20-sheet automatic document feeder', 'Up to 25 ppm duplex', 'USB bus powered', '600 dpi optical resolution', 'Scan to USB drive or PC'],
            'dimensions' => [30.6, 9.4, 8.4, 1.6],
        ],
        [
            'sku' => 'FUJ-IX1400', 'name' => 'Fuji ScanSnap iX1400 A4 Desktop Document Scanner',
            'brand' => 'fuji', 'category' => 'scanners', 'price' => 26900, 'image' => 'scanner', 'stock' => 'machine',
            'short' => 'One button, 40 pages a minute — the scanner for staff who will not learn a scanning application.',
            'body' => 'The ScanSnap is built around a single large button: load the stack, press it, and the profile decides where the file goes. Forty pages a minute in duplex from a 50-sheet feeder. Records rooms adopt these because nothing has to be explained twice.',
            'specs' => ['50-sheet automatic document feeder', 'Up to 40 ppm duplex', 'One-touch scanning profiles', '600 dpi optical resolution', 'USB 3.2 Gen 1'],
            'dimensions' => [29.2, 16.1, 15.2, 3.2],
        ],

        // --- Ink and toner ---------------------------------------------------
        [
            'sku' => 'EPS-003BK', 'name' => 'Epson 003 Black Ink Bottle 65ml',
            'brand' => 'epson', 'category' => 'ink-and-toner', 'price' => 285, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'Genuine 003 black for the L1110, L3110, L3210, L3250 and L5290 series.',
            'body' => 'A 65ml bottle rated at roughly 4,500 pages. Genuine ink keeps the printhead warranty intact — most of the head failures we repair trace back to a refill of unknown origin.',
            'specs' => ['65ml black ink bottle', 'Approximately 4,500 pages', 'For EcoTank L1110, L3110, L3210, L3250, L5290', 'Genuine Epson consumable', 'Keyed nozzle for spill-free filling'],
            'dimensions' => [4.0, 4.0, 12.5, 0.09],
        ],
        [
            'sku' => 'EPS-664SET', 'name' => 'Epson 664 Ink Bottle Set — Black, Cyan, Magenta, Yellow',
            'brand' => 'epson', 'category' => 'ink-and-toner', 'price' => 1150, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'A full four-colour refill for the L120, L220, L360 and L565 generation.',
            'body' => 'Four 70ml bottles covering the older L-series tanks that are still working in offices across the country. Buying the set is cheaper than four separate bottles and means a machine is never down waiting on one colour.',
            'specs' => ['Four 70ml bottles: black, cyan, magenta, yellow', 'Approximately 7,500 pages black', 'For L120, L220, L360, L365, L565', 'Genuine Epson consumable', 'Sold as a complete set'],
            'dimensions' => [16.0, 8.0, 12.5, 0.36],
        ],
        [
            'sku' => 'CAN-GI790BK', 'name' => 'Canon GI-790 Black Ink Bottle 135ml',
            'brand' => 'canon', 'category' => 'ink-and-toner', 'price' => 320, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'Genuine GI-790 black for the PIXMA G1010, G2010, G3010 and G3020.',
            'body' => 'A 135ml bottle rated at about 6,000 pages — the largest black bottle in the consumer tank range, and the reason a G-series machine can go months between refills in a home office.',
            'specs' => ['135ml black ink bottle', 'Approximately 6,000 pages', 'For PIXMA G1010, G2010, G3010, G3020', 'Genuine Canon consumable', 'Pigment black for sharp text'],
            'dimensions' => [4.5, 4.5, 13.0, 0.16],
        ],
        [
            'sku' => 'BRO-BT5000SET', 'name' => 'Brother BT5000 Colour Ink Bottle Set — Cyan, Magenta, Yellow',
            'brand' => 'brother', 'category' => 'ink-and-toner', 'price' => 1290, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'The three-colour refill for the DCP-T series ink tank machines.',
            'body' => 'Three 48.8ml bottles at roughly 5,000 pages each. The keyed bottle design mates with the tank before ink flows, so a refill can be done at the desk without covering it in newspaper first.',
            'specs' => ['Three 48.8ml bottles: cyan, magenta, yellow', 'Approximately 5,000 pages each', 'For DCP-T420W, DCP-T720DW, DCP-T820DW', 'Genuine Brother consumable', 'Leak-resistant keyed design'],
            'dimensions' => [13.5, 6.0, 12.0, 0.21],
        ],
        [
            'sku' => 'BRO-BTD60BK', 'name' => 'Brother BTD60BK Black Ink Bottle 108ml',
            'brand' => 'brother', 'category' => 'ink-and-toner', 'price' => 495, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'The high-yield black bottle for the DCP-T series — about 6,500 pages.',
            'body' => 'Black runs out first in almost every office, which is why this bottle is more than twice the volume of the colours. Rated at 6,500 pages and stocked at every branch.',
            'specs' => ['108ml black ink bottle', 'Approximately 6,500 pages', 'For DCP-T420W, DCP-T720DW, DCP-T820DW', 'Genuine Brother consumable', 'Leak-resistant keyed design'],
            'dimensions' => [4.5, 4.5, 13.5, 0.14],
        ],
        [
            'sku' => 'HP-GT52SET', 'name' => 'HP GT52 Ink Bottle Set — Cyan, Magenta, Yellow',
            'brand' => 'hp', 'category' => 'ink-and-toner', 'price' => 1395, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'The colour refill for HP Ink Tank and Smart Tank printers.',
            'body' => 'Three 70ml bottles at roughly 8,000 pages each — the highest colour yield of any tank set we stock. The nozzle locks to the tank before it opens, so nothing spills even if the bottle is knocked.',
            'specs' => ['Three 70ml bottles: cyan, magenta, yellow', 'Approximately 8,000 pages each', 'For Ink Tank 315, 415 and Smart Tank series', 'Genuine HP consumable', 'Spill-free locking nozzle'],
            'dimensions' => [14.0, 6.5, 12.0, 0.27],
        ],
        [
            'sku' => 'HP-682TRI', 'name' => 'HP 682 Tri-colour Ink Cartridge',
            'brand' => 'hp', 'category' => 'ink-and-toner', 'price' => 545, 'image' => 'toner', 'stock' => 'bulk',
            'short' => 'The tri-colour cartridge for the DeskJet Ink Advantage 2300 and 2700 series.',
            'body' => 'A standard-yield tri-colour cartridge at about 150 pages. Cartridge machines cost less on the day and more per page than tank machines — worth knowing before the second replacement.',
            'specs' => ['Tri-colour cartridge', 'Approximately 150 pages', 'For DeskJet Ink Advantage 2336, 2337, 2775, 2776', 'Genuine HP consumable', 'Integrated printhead'],
            'dimensions' => [11.4, 3.7, 9.0, 0.06],
        ],
        [
            'sku' => 'HP-Q2612A', 'name' => 'HP 12A (Q2612A) Black LaserJet Toner Cartridge',
            'brand' => 'hp', 'category' => 'ink-and-toner', 'price' => 3450, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'The most widely used mono toner in the country, for the LaserJet 1010 to 3050 family.',
            'body' => 'Rated at about 2,000 pages. Twenty years after launch these machines are still in daily service, and this cartridge is the reason — it has never gone out of stock and it has never been hard to find.',
            'specs' => ['Black toner cartridge', 'Approximately 2,000 pages', 'For LaserJet 1010, 1020, 1022, 3015, 3050', 'Genuine HP consumable', 'Integrated drum'],
            'dimensions' => [35.5, 12.0, 15.0, 0.9],
        ],
        [
            'sku' => 'CAN-CRG337', 'name' => 'Canon Cartridge 337 Black Toner',
            'brand' => 'canon', 'category' => 'ink-and-toner', 'price' => 3890, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'The all-in-one toner and drum unit for the imageCLASS MF210, MF230 and MF3010.',
            'body' => 'Toner and drum in a single cartridge, which means a replacement restores print quality completely rather than only topping up the toner. About 2,400 pages.',
            'specs' => ['Black toner cartridge with integrated drum', 'Approximately 2,400 pages', 'For imageCLASS MF3010, MF211, MF212w, MF232w', 'Genuine Canon consumable', 'Restores print quality on replacement'],
            'dimensions' => [35.0, 13.5, 16.0, 1.0],
        ],
        [
            'sku' => 'KYO-TK1175', 'name' => 'Kyocera TK-1175 Black Toner Kit',
            'brand' => 'kyocera', 'category' => 'ink-and-toner', 'price' => 5650, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'Toner only — the drum stays in the machine, which is where the ECOSYS saving comes from.',
            'body' => 'A 12,000-page toner kit for the ECOSYS M2040dn and P2040dn. Because the drum is a separate long-life part, this replacement is toner and a waste container and nothing else, at roughly half the per-page cost of an all-in-one cartridge.',
            'specs' => ['Black toner kit', 'Approximately 12,000 pages', 'For ECOSYS M2040dn, M2540dn, P2040dn', 'Genuine Kyocera consumable', 'Includes waste toner container'],
            'dimensions' => [36.0, 15.0, 18.0, 1.2],
        ],
        [
            'sku' => 'RIC-SP200', 'name' => 'Ricoh SP 200 Black Toner Cartridge',
            'brand' => 'ricoh', 'category' => 'ink-and-toner', 'price' => 2950, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'The standard black toner for the Ricoh SP 200, 210 and 212 series.',
            'body' => 'A 1,500-page cartridge for Ricoh\'s compact desktop lasers. Held at every branch because these machines are common in satellite and field offices where a courier run is a two-day delay.',
            'specs' => ['Black toner cartridge', 'Approximately 1,500 pages', 'For SP 200, SP 210, SP 212 series', 'Genuine Ricoh consumable', 'Integrated drum'],
            'dimensions' => [33.0, 11.5, 14.0, 0.8],
        ],
        [
            'sku' => 'KON-TN116', 'name' => 'Konica Minolta TN-116 Black Toner Cartridge',
            'brand' => 'konica', 'category' => 'ink-and-toner', 'price' => 4250, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'The bizhub 164, 165, 184 and 226 toner — about 11,000 pages a unit.',
            'body' => 'A copier toner rather than a printer one: a single unit carries a bizhub 226 through roughly 11,000 copies. Ordered by the box by schools and copy centres before an examination period.',
            'specs' => ['Black toner cartridge', 'Approximately 11,000 pages', 'For bizhub 164, 165, 184, 195, 226', 'Genuine Konica Minolta consumable', 'Bulk pricing available by the box'],
            'dimensions' => [36.5, 8.0, 9.5, 0.6],
        ],
        [
            'sku' => 'SAM-MLTD111S', 'name' => 'Samsung MLT-D111S Black Toner Cartridge',
            'brand' => 'samsung', 'category' => 'ink-and-toner', 'price' => 3150, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'The Xpress M2020 and M2070 toner, at about 1,000 pages.',
            'body' => 'A standard-yield cartridge for Samsung\'s compact laser range. A high-yield MLT-D111L is available on order for offices printing enough to notice the difference.',
            'specs' => ['Black toner cartridge', 'Approximately 1,000 pages', 'For Xpress M2020, M2020W, M2070, M2070FW', 'Genuine Samsung consumable', 'High-yield MLT-D111L available on order'],
            'dimensions' => [32.0, 11.0, 13.5, 0.7],
        ],
        [
            'sku' => 'OKI-45807111', 'name' => 'OKI 45807111 Black Toner Cartridge',
            'brand' => 'oki', 'category' => 'ink-and-toner', 'price' => 6900, 'image' => 'toner', 'stock' => 'low',
            'short' => 'The high-yield 7,000-page toner for the OKI B412, B432 and B512.',
            'body' => 'A workgroup toner sized to match the B432dn\'s duty cycle. Ordered against demand rather than held deep, so call ahead if a branch is expected to have one on the shelf.',
            'specs' => ['Black toner cartridge', 'Approximately 7,000 pages', 'For B412dn, B432dn, B512dn', 'Genuine OKI consumable', 'Ordered against demand — call to reserve'],
            'dimensions' => [35.0, 13.0, 16.5, 1.1],
        ],
        [
            'sku' => 'RIS-S4253', 'name' => 'Riso S-4253 Black Ink Cartridge 1000ml',
            'brand' => 'riso', 'category' => 'ink-and-toner', 'price' => 4780, 'image' => 'toner', 'stock' => 'stocked',
            'short' => 'A litre of duplicator ink for the RZ and EZ series — tens of thousands of copies.',
            'body' => 'Duplicator ink is bought by the litre because a duplicator prints by the tens of thousands. One cartridge with one master roll covers an entire examination period for a medium-sized school.',
            'specs' => ['1,000ml black ink cartridge', 'For Riso RZ and EZ series duplicators', 'Genuine Riso consumable', 'Pairs with Riso A4 master roll', 'Bulk pricing by the case'],
            'dimensions' => [8.5, 8.5, 32.0, 1.15],
        ],

        // --- Paper and media --------------------------------------------------
        [
            'sku' => 'PAP-A4S20', 'name' => 'A4 Bond Paper Substance 20 — Ream of 500 Sheets',
            'brand' => null, 'category' => 'paper-and-media', 'price' => 245, 'image' => 'paper', 'stock' => 'bulk',
            'short' => 'The default office ream: 70gsm A4, sold singly or by the box of ten.',
            'body' => 'Substance 20 is the weight almost every office letter, report and form is printed on. Sold by the ream at the counter and by the box of ten at a lower unit price — ask a branch for box pricing before ordering ten singly.',
            'specs' => ['A4, 210 x 297mm', 'Substance 20 (70gsm)', '500 sheets per ream', 'Suitable for inkjet and laser', 'Box of 10 reams available'],
            'dimensions' => [29.7, 21.0, 5.2, 2.5],
        ],
        [
            'sku' => 'PAP-LGLS20', 'name' => 'Legal Bond Paper Substance 20 — Ream of 500 Sheets',
            'brand' => null, 'category' => 'paper-and-media', 'price' => 285, 'image' => 'paper', 'stock' => 'bulk',
            'short' => 'Long bond paper for contracts, government forms and filings.',
            'body' => 'Legal, or long, remains the required size for a great many Philippine forms and filings. Same substance 20 stock as the A4 ream, cut to 8.5 x 13 inches.',
            'specs' => ['Legal, 8.5 x 13 inches', 'Substance 20 (70gsm)', '500 sheets per ream', 'Suitable for inkjet and laser', 'Box of 10 reams available'],
            'dimensions' => [33.0, 21.6, 5.2, 2.8],
        ],
        [
            'sku' => 'PAP-RISOMSTR', 'name' => 'Riso A4 Thermal Master Roll',
            'brand' => 'riso', 'category' => 'paper-and-media', 'price' => 3950, 'image' => 'paper', 'stock' => 'stocked',
            'short' => 'The master roll a duplicator cuts each original onto — about 200 masters per roll.',
            'body' => 'A duplicator makes one thermal master per original and then prints from it. At roughly 200 masters a roll, a school running an examination period will use two or three. Buy it with the ink; running out of either stops the machine.',
            'specs' => ['A4 thermal master roll', 'Approximately 200 masters per roll', 'For Riso RZ and EZ series', 'Genuine Riso consumable', 'Store sealed and away from heat'],
            'dimensions' => [32.0, 11.0, 11.0, 1.4],
        ],
        [
            'sku' => 'PAP-GLOSSA4', 'name' => 'A4 Glossy Photo Paper 200gsm — 20 Sheets',
            'brand' => null, 'category' => 'paper-and-media', 'price' => 165, 'image' => 'paper', 'stock' => 'bulk',
            'short' => 'Heavyweight glossy stock for photos, certificates and presentation covers.',
            'body' => 'A 200gsm resin-coated gloss that holds inkjet colour without bleeding. Used as much for certificates and awards as for photographs, which is what most of these packs end up printing.',
            'specs' => ['A4, 210 x 297mm', '200gsm resin-coated gloss', '20 sheets per pack', 'Inkjet only — not for laser', 'Instant-dry coating'],
            'dimensions' => [29.7, 21.0, 0.6, 0.25],
        ],
        [
            'sku' => 'PAP-CARBLESS', 'name' => 'Carbonless Form Paper 2-Ply Legal — 500 Sets',
            'brand' => null, 'category' => 'paper-and-media', 'price' => 1290, 'image' => 'paper', 'stock' => 'stocked',
            'short' => 'Two-ply white and yellow carbonless stock for receipts, delivery slips and invoices.',
            'body' => 'Pre-collated white-over-yellow sets that copy under pen pressure without carbon paper. Standard stock for delivery receipts and provisional invoices; overprinting with a business name and permit number can be arranged.',
            'specs' => ['Legal, 8.5 x 13 inches', '2-ply: white over yellow', '500 collated sets', 'No carbon sheet required', 'Custom overprinting available on order'],
            'dimensions' => [33.0, 21.6, 10.5, 4.6],
        ],
        [
            'sku' => 'PAP-STICKA4', 'name' => 'A4 Matte Sticker Paper — 50 Sheets',
            'brand' => null, 'category' => 'paper-and-media', 'price' => 225, 'image' => 'paper', 'stock' => 'bulk',
            'short' => 'Full-sheet self-adhesive matte stock for labels, asset tags and packaging.',
            'body' => 'A full-sheet adhesive that cuts cleanly with scissors or a cutter, printable on both inkjet and laser. Bought steadily by inventory offices for asset tagging and by small businesses for product labels.',
            'specs' => ['A4, 210 x 297mm', 'Matte self-adhesive, full sheet', '50 sheets per pack', 'Inkjet and laser compatible', 'Cuts cleanly without fraying'],
            'dimensions' => [29.7, 21.0, 1.2, 0.5],
        ],

        // --- Spare parts ------------------------------------------------------
        [
            'sku' => 'SPR-FUSERHP12', 'name' => 'Fuser Assembly for HP LaserJet 1020 and 1022',
            'brand' => 'hp', 'category' => 'spare-parts', 'price' => 3850, 'image' => 'part', 'stock' => 'stocked',
            'short' => 'The heat unit that bonds toner to the page — replaced when print rubs off or jams recur.',
            'body' => 'A worn fuser shows up as toner that smudges under a thumb, or as repeated jams at the exit. Replacing the assembly restores both. Fitting is a workshop job and is included when the part is bought with service.',
            'specs' => ['Fuser assembly, 220V', 'For LaserJet 1020, 1022, 3050', 'Fixes smudging and exit jams', 'Installation available at any branch', '90-day parts warranty'],
            'dimensions' => [36.0, 12.0, 10.0, 1.1],
        ],
        [
            'sku' => 'SPR-PICKROLL', 'name' => 'Paper Pickup Roller Set — Universal Laser',
            'brand' => null, 'category' => 'spare-parts', 'price' => 685, 'image' => 'part', 'stock' => 'stocked',
            'short' => 'The first thing to replace when a printer pulls two sheets, or none.',
            'body' => 'Pickup rollers glaze over with age and stop gripping paper. It is the cheapest repair in the catalogue and it resolves most misfeed complaints outright. Fits the common HP, Canon and Samsung desktop laser trays.',
            'specs' => ['Rubber pickup roller and separation pad', 'Fits common HP, Canon and Samsung trays', 'Resolves misfeeds and double-feeds', 'Consumable wear part', 'Installation available at any branch'],
            'dimensions' => [12.0, 6.0, 4.0, 0.08],
        ],
        [
            'sku' => 'SPR-HEADL3110', 'name' => 'Printhead Assembly for Epson L3110 and L3210 Series',
            'brand' => 'epson', 'category' => 'spare-parts', 'price' => 4650, 'image' => 'part', 'stock' => 'low',
            'short' => 'The replacement head for tank printers with permanently blocked nozzles.',
            'body' => 'Where cleaning cycles and a soak have failed, the head is replaced. Nearly every one we fit comes off a machine run on unbranded refill ink — worth weighing against the price of genuine bottles. Fitted in the workshop with a test print before release.',
            'specs' => ['Printhead assembly', 'For L1110, L3110, L3150, L3210, L3250', 'For nozzles that cleaning cannot recover', 'Workshop fitting with test print', '90-day parts warranty'],
            'dimensions' => [10.0, 8.0, 5.0, 0.14],
        ],
        [
            'sku' => 'SPR-WASTEBOX', 'name' => 'Waste Toner Container for Kyocera TASKalfa 2020 and 2321',
            'brand' => 'kyocera', 'category' => 'spare-parts', 'price' => 1450, 'image' => 'part', 'stock' => 'stocked',
            'short' => 'The container that catches surplus toner — a full one halts the copier.',
            'body' => 'A copier stops rather than risks spilling toner through its transport. Keeping a spare container on site turns an unplanned outage into a two-minute swap, which is why we suggest ordering one with every second toner kit.',
            'specs' => ['Waste toner container', 'For TASKalfa 2020, 2011, 2321', 'Genuine Kyocera part', 'Swaps out in under two minutes', 'Recommended: one spare per site'],
            'dimensions' => [33.0, 10.0, 12.0, 0.55],
        ],
        [
            'sku' => 'SPR-DRUMBR', 'name' => 'Drum Unit DR-2355 for Brother HL-L2350DW',
            'brand' => 'brother', 'category' => 'spare-parts', 'price' => 3290, 'image' => 'part', 'stock' => 'stocked',
            'short' => 'The 12,000-page drum that outlives several toner cartridges.',
            'body' => 'Brother separates drum from toner, so the drum is changed once every few toners rather than every time. The machine warns before quality falls off; replacing it on that warning avoids a run of streaked pages.',
            'specs' => ['Drum unit DR-2355', 'Approximately 12,000 pages', 'For HL-L2350DW, HL-L2375DW, DCP-L2540DW', 'Genuine Brother part', 'Separate from the TN toner cartridge'],
            'dimensions' => [35.5, 18.0, 17.0, 0.9],
        ],

        // --- Accessories -------------------------------------------------------
        [
            'sku' => 'ACC-USBPRT3M', 'name' => 'USB 2.0 Printer Cable — 3 Metres',
            'brand' => null, 'category' => 'accessories', 'price' => 285, 'image' => 'accessory', 'stock' => 'bulk',
            'short' => 'A shielded A-to-B printer cable, because most printers ship without one.',
            'body' => 'Almost no printer includes a USB cable in the box, and the metre-long cable in the drawer never reaches. Three metres, shielded, with moulded connectors that survive being unplugged repeatedly.',
            'specs' => ['USB 2.0 Type-A to Type-B', '3 metres', 'Shielded with ferrite core', 'Moulded strain-relief connectors', 'Gold-plated contacts'],
            'dimensions' => [12.0, 12.0, 3.0, 0.16],
        ],
        [
            'sku' => 'ACC-PRTSTAND', 'name' => 'Steel Printer Stand with Paper Shelf',
            'brand' => null, 'category' => 'accessories', 'price' => 2450, 'image' => 'accessory', 'stock' => 'stocked',
            'short' => 'Gets the printer off the desk and the paper stock underneath it.',
            'body' => 'A powder-coated steel stand rated to 50kg, with a lower shelf sized for boxed reams. Takes a desktop multifunction or a compact copier and puts the paper where whoever is refilling it can reach without bending.',
            'specs' => ['Powder-coated steel frame', '50kg load rating', 'Lower shelf fits boxed reams', 'Locking castors', 'Assembly required, tools included'],
            'dimensions' => [60.0, 45.0, 65.0, 9.5], 'delivery' => true,
        ],
        [
            'sku' => 'ACC-AVR500', 'name' => 'Automatic Voltage Regulator 500VA',
            'brand' => null, 'category' => 'accessories', 'price' => 1250, 'image' => 'accessory', 'stock' => 'stocked',
            'short' => 'Protects a printer\'s board from the brownouts and surges that kill them.',
            'body' => 'Most dead control boards we replace were killed by mains voltage, not by age. A 500VA regulator costs a fraction of the board and holds output steady through the sags and spikes that are ordinary on many provincial lines.',
            'specs' => ['500VA automatic voltage regulator', 'Three universal outlets', 'Surge and overload protection', 'Suitable for desktop printers and scanners', 'One-year warranty'],
            'dimensions' => [22.0, 16.0, 12.0, 2.9],
        ],
        [
            'sku' => 'ACC-CLEANKIT', 'name' => 'Printer and Copier Cleaning Kit',
            'brand' => null, 'category' => 'accessories', 'price' => 495, 'image' => 'accessory', 'stock' => 'bulk',
            'short' => 'Blower, lint-free cloths, swabs and safe solution — the maintenance most machines never get.',
            'body' => 'Dust on a scanner glass shows on every copy, and paper dust on the rollers causes misfeeds. A quarterly clean with this kit prevents the two most common complaints we are called out for.',
            'specs' => ['Air blower and anti-static brush', 'Five lint-free microfibre cloths', 'Cotton swabs for roller contact points', '100ml alcohol-free cleaning solution', 'Safe on scanner glass and platens'],
            'dimensions' => [24.0, 16.0, 8.0, 0.45],
        ],
        [
            'sku' => 'ACC-PRTSERVER', 'name' => 'USB Network Print Server — 10/100 Ethernet',
            'brand' => null, 'category' => 'accessories', 'price' => 1890, 'image' => 'accessory', 'stock' => 'stocked',
            'short' => 'Puts a USB-only printer on the network without replacing it.',
            'body' => 'A working USB printer that everyone now needs to reach does not have to be thrown out. This adapter takes the USB port onto Ethernet and gives the printer an address of its own. Configured free at any branch when bought with the printer.',
            'specs' => ['USB 2.0 to 10/100 Ethernet', 'Supports most USB printers and multifunctions', 'Web-based configuration', 'Windows and macOS drivers included', 'Free configuration in-branch'],
            'dimensions' => [8.5, 6.0, 2.5, 0.12],
        ],
    ];

    /**
     * Bundles: an ordinary product flagged `is_bundle`, with its contents in
     * bundle_items rather than a free-text list. Seeded last so the storefront's
     * default newest-first sort leads with them.
     *
     * @var list<array{sku: string, name: string, category: string, price: float, image: string, short: string, body: string, items: list<array{sku: ?string, label: string, quantity: int}>}>
     */
    private const BUNDLES = [
        [
            'sku' => 'BDL-SMALLOFFICE', 'name' => 'Small Office Starter Bundle', 'category' => 'bundles',
            'price' => 12450, 'image' => 'printer',
            'short' => 'A wireless tank printer, a full set of spare ink and a box of paper — everything a new office opens with.',
            'body' => 'The three things every new office buys in the same week, priced together. An EcoTank L3250 for wireless printing, spare black bottles so the first refill is already on the shelf, and two reams of A4 to start on. Saves roughly 900 pesos against buying the items separately.',
            'items' => [
                ['sku' => 'EPS-L3250', 'label' => 'Epson EcoTank L3250 Wi-Fi All-in-One Printer', 'quantity' => 1],
                ['sku' => 'EPS-003BK', 'label' => 'Epson 003 Black Ink Bottle 65ml', 'quantity' => 2],
                ['sku' => 'PAP-A4S20', 'label' => 'A4 Bond Paper Substance 20 (ream)', 'quantity' => 2],
                ['sku' => 'ACC-USBPRT3M', 'label' => 'USB 2.0 Printer Cable, 3 metres', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'BDL-PRINTSHOP', 'name' => 'Print Shop Duplicator Bundle', 'category' => 'bundles',
            'price' => 124500, 'image' => 'photocopier',
            'short' => 'A Riso RZ 220 with a term\'s worth of ink and masters, delivered and installed.',
            'body' => 'Sized for a school division office or parish through one examination or bulletin season: the duplicator, two litres of ink and two master rolls, with delivery, installation and operator training included. Buying the consumables in the bundle avoids the usual scramble halfway through a print run.',
            'items' => [
                ['sku' => 'RIS-RZ220', 'label' => 'Riso RZ 220 A4 Digital Stencil Duplicator', 'quantity' => 1],
                ['sku' => 'RIS-S4253', 'label' => 'Riso S-4253 Black Ink Cartridge 1000ml', 'quantity' => 2],
                ['sku' => 'PAP-RISOMSTR', 'label' => 'Riso A4 Thermal Master Roll', 'quantity' => 2],
                ['sku' => null, 'label' => 'Delivery, installation and operator training', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'BDL-HOMESTUDY', 'name' => 'Home Study Print Bundle', 'category' => 'bundles',
            'price' => 9750, 'image' => 'printer',
            'short' => 'A colour tank printer, paper and photo stock — the bundle parents buy in June.',
            'body' => 'Assembled for households printing school work: a Canon G2010 for colour, black ink for the text-heavy weeks, a ream of A4 and a pack of glossy stock for projects that have to be mounted. Includes the cable, which the printer does not.',
            'items' => [
                ['sku' => 'CAN-G2010', 'label' => 'Canon PIXMA G2010 All-in-One Ink Tank Printer', 'quantity' => 1],
                ['sku' => 'CAN-GI790BK', 'label' => 'Canon GI-790 Black Ink Bottle 135ml', 'quantity' => 1],
                ['sku' => 'PAP-A4S20', 'label' => 'A4 Bond Paper Substance 20 (ream)', 'quantity' => 1],
                ['sku' => 'PAP-GLOSSA4', 'label' => 'A4 Glossy Photo Paper 200gsm (20 sheets)', 'quantity' => 1],
            ],
        ],
    ];

    /**
     * Seeding the catalogue is several hundred small writes -- a row, its stock
     * at every branch, its photo. One transaction turns that into one commit,
     * which is the difference between seconds and minutes on SQLite, and it also
     * means a failure part-way leaves no half-built catalogue behind.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $brands = Brand::query()->pluck('id', 'slug');
            $categories = Category::query()->pluck('id', 'slug');
            $branches = Branch::query()->get();
            $photos = $this->photosByTopic();

            foreach (self::PRODUCTS as $row) {
                $product = $this->upsertProduct($row, $brands, $categories);

                $this->stock($product, $branches, $row['stock']);
                $this->image($product, $row['image'], $photos);
            }

            $this->seedBundles($categories, $branches, $photos);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, int>  $brands
     * @param  Collection<string, int>  $categories
     */
    private function upsertProduct(array $row, Collection $brands, Collection $categories): Product
    {
        return Product::query()->updateOrCreate(
            ['sku' => $row['sku']],
            [
                'name' => $row['name'],
                'slug' => Str::slug($row['name']),
                'brand_id' => $row['brand'] === null ? null : ($brands[$row['brand']] ?? null),
                'category_id' => $categories[$row['category']] ?? null,
                'short_description' => $row['short'],
                'description' => $this->description($row['body'], $row['specs']),
                'price' => $row['price'],
                'is_featured' => $row['featured'] ?? false,
                'is_bundle' => false,
                'requires_delivery' => $row['delivery'] ?? false,
                'length_cm' => $row['dimensions'][0],
                'width_cm' => $row['dimensions'][1],
                'height_cm' => $row['dimensions'][2],
                'weight_kg' => $row['dimensions'][3],
                'status' => ProductStatus::Active,
            ]
        );
    }

    /**
     * Render the stored HTML body: a lead paragraph followed by the spec list.
     *
     * @param  list<string>  $specs
     */
    private function description(string $body, array $specs): string
    {
        $items = implode('', array_map(fn (string $spec): string => '<li>'.e($spec).'</li>', $specs));

        return '<p>'.e($body).'</p><h3>Specifications</h3><ul>'.$items.'</ul>';
    }

    /**
     * Set the product's stock at every branch from its movement profile.
     *
     * Pickup branches vary around the profile's base so the figures do not read
     * as copy-pasted; the warehouse holds four times the depth, which is what
     * makes it the warehouse.
     *
     * @param  Collection<int, Branch>  $branches
     */
    private function stock(Product $product, Collection $branches, string $profileName): void
    {
        $profile = self::STOCK_PROFILES[$profileName];

        $product->branches()->sync(
            $branches->mapWithKeys(function (Branch $branch) use ($profile): array {
                $quantity = $branch->is_pickup_location
                    ? max(1, $profile['base'] - ($branch->position % 4) * intdiv($profile['base'], 8))
                    : $profile['base'] * 4;

                return [$branch->id => [
                    'quantity' => $quantity,
                    'low_stock_threshold' => $profile['threshold'],
                ]];
            })->all()
        );
    }

    /**
     * The demo product photos on disk, grouped by the subject in their filename.
     *
     * Read once rather than per product: this runs for every row in the
     * catalogue and the disk listing does not change while it does.
     *
     * @return array<string, list<string>>
     */
    private function photosByTopic(): array
    {
        $grouped = [];

        foreach (Storage::disk('public')->files('demo/products') as $path) {
            $topic = Str::before(basename($path), '-');

            $grouped[$topic][] = $path;
        }

        foreach ($grouped as $topic => $paths) {
            sort($paths);
            $grouped[$topic] = $paths;
        }

        return $grouped;
    }

    /**
     * Attach the primary photo from the set matching the product's subject, so
     * a toner shows a toner. Skipped silently when the media has not been
     * fetched, so a clone without images still seeds cleanly.
     *
     * @param  array<string, list<string>>  $photos
     */
    private function image(Product $product, string $topic, array $photos): void
    {
        if (($candidates = $photos[$topic] ?? []) === []) {
            return;
        }

        ProductImage::query()->updateOrCreate(
            ['product_id' => $product->id, 'is_primary' => true],
            [
                'path' => $candidates[$product->id % count($candidates)],
                'alt_text' => $product->name,
                'position' => 0,
            ]
        );
    }

    /**
     * @param  Collection<string, int>  $categories
     * @param  Collection<int, Branch>  $branches
     * @param  array<string, list<string>>  $photos
     */
    private function seedBundles(Collection $categories, Collection $branches, array $photos): void
    {
        $productIds = Product::query()->pluck('id', 'sku');

        foreach (self::BUNDLES as $row) {
            $bundle = Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'slug' => Str::slug($row['name']),
                    'brand_id' => null,
                    'category_id' => $categories[$row['category']] ?? null,
                    'short_description' => $row['short'],
                    'description' => '<p>'.e($row['body']).'</p>',
                    'price' => $row['price'],
                    'is_featured' => true,
                    'is_bundle' => true,
                    'requires_delivery' => $row['sku'] === 'BDL-PRINTSHOP',
                    'length_cm' => 60,
                    'width_cm' => 45,
                    'height_cm' => 40,
                    'weight_kg' => 15,
                    'status' => ProductStatus::Active,
                ]
            );

            $this->stock($bundle, $branches, 'machine');
            $this->image($bundle, $row['image'], $photos);

            $bundle->bundleItems()->delete();

            foreach ($row['items'] as $position => $item) {
                BundleItem::query()->create([
                    'bundle_product_id' => $bundle->id,
                    'product_id' => $item['sku'] === null ? null : ($productIds[$item['sku']] ?? null),
                    'label' => $item['label'],
                    'quantity' => $item['quantity'],
                    'position' => $position,
                ]);
            }
        }
    }
}
