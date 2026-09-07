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
 * The trading catalogue: the cameras, recorders, alarms, access control and
 * cabling the business actually sells.
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
     * Cable, connectors and the cheap analogue cameras move by the box, so they
     * sit on `bulk`. Recorders and PTZ domes are bought one at a time and are
     * held in single figures, so they sit on `machine`.
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
     * `image` names the demo photo set the product draws from, so a dome camera
     * shows a dome rather than whatever the next stock photo happened to be.
     *
     * @var list<array{sku: string, name: string, brand: ?string, category: string, price: float, image: string, stock: string, short: string, body: string, specs: list<string>, dimensions: array{0: float, 1: float, 2: float, 3: float}, featured?: bool, delivery?: bool}>
     */
    private const PRODUCTS = [
        // --- IP cameras ----------------------------------------------------
        [
            'sku' => 'HIK-2CD1043G2', 'name' => 'Hikvision DS-2CD1043G2-LIU 4MP Bullet Network Camera',
            'brand' => 'hikvision', 'category' => 'ip-cameras', 'price' => 3450, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'The 4MP bullet we fit more of than anything else — one cable carries the picture and the power.',
            'body' => 'A 4MP fixed bullet on a single Cat6 run: the switch feeds it and it sends the picture back down the same cable, which is why a four-camera job is a morning rather than a day. Infrared reaches about 30 metres, and the built-in microphone records audio where the law and the site allow it.',
            'specs' => ['4MP (2560 x 1440) at 25fps', '2.8mm fixed lens, 103 degree view', 'Up to 30m infrared night vision', 'Built-in microphone', 'IP67 weatherproof, PoE powered'],
            'dimensions' => [17.9, 7.0, 7.0, 0.45], 'featured' => true,
        ],
        [
            'sku' => 'HIK-2CD1343G2', 'name' => 'Hikvision DS-2CD1343G2-LIU 4MP Turret Network Camera',
            'brand' => 'hikvision', 'category' => 'ip-cameras', 'price' => 3550, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'The same sensor as the bullet in a turret body — the one to fit under a low eave or a ceiling.',
            'body' => 'Identical electronics to the DS-2CD1043G2, in a turret housing that sits closer to the surface and is far harder to knock out of aim. This is the body to specify indoors, under a carport, or anywhere a bullet would be within arm\'s reach.',
            'specs' => ['4MP (2560 x 1440) at 25fps', '2.8mm fixed lens, 103 degree view', 'Up to 30m infrared night vision', 'Built-in microphone', 'IP67 weatherproof, PoE powered'],
            'dimensions' => [11.0, 11.0, 9.4, 0.4],
        ],
        [
            'sku' => 'HIK-2CD2086G2', 'name' => 'Hikvision DS-2CD2086G2-IU 8MP AcuSense Bullet Network Camera',
            'brand' => 'hikvision', 'category' => 'ip-cameras', 'price' => 8950, 'image' => 'camera', 'stock' => 'stocked',
            'short' => '8MP with on-camera person and vehicle detection, so the alerts you get are worth reading.',
            'body' => 'AcuSense classifies what moved before it raises an alert, which is the difference between a night of notifications and three that matter. At 8MP a plate or a face survives being cropped out of a wide shot, which is the whole point of buying resolution.',
            'specs' => ['8MP (3840 x 2160) at 20fps', 'On-camera person and vehicle classification', '2.8mm fixed lens', 'Up to 40m infrared night vision', 'IP67, PoE, built-in microphone'],
            'dimensions' => [26.7, 9.6, 9.6, 0.85], 'featured' => true,
        ],
        [
            'sku' => 'HIK-2CD2143G2', 'name' => 'Hikvision DS-2CD2143G2-IU 4MP AcuSense Dome Network Camera',
            'brand' => 'hikvision', 'category' => 'ip-cameras', 'price' => 6250, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'A vandal-resistant dome with person and vehicle filtering, for entrances that get handled.',
            'body' => 'The IK10 dome body takes a deliberate knock without losing aim, which is why it goes above shop doors and in stairwells. Same AcuSense filtering as the 8MP bullet, so the recorder is not full of footage of a moth.',
            'specs' => ['4MP (2688 x 1520) at 25fps', 'On-camera person and vehicle classification', 'IK10 vandal-resistant housing', 'Up to 30m infrared night vision', 'IP67, PoE, built-in microphone'],
            'dimensions' => [11.1, 11.1, 8.5, 0.5],
        ],
        [
            'sku' => 'HIK-2CD2T47G2', 'name' => 'Hikvision DS-2CD2T47G2-L 4MP ColorVu Bullet Network Camera',
            'brand' => 'hikvision', 'category' => 'ip-cameras', 'price' => 7450, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'Colour at night rather than grey — the camera to fit where the description matters.',
            'body' => 'A conventional camera drops to infrared after dark and everything it records is grey, so a red shirt and a green one look the same on the tape. ColorVu keeps colour down to near-total darkness using an f/1.0 lens and a warm supplement light, which is what makes a statement usable afterwards.',
            'specs' => ['4MP (2688 x 1520) at 25fps', '24-hour colour imaging, f/1.0 lens', 'Up to 40m white supplement light', '2.8mm fixed lens', 'IP67, PoE, built-in microphone'],
            'dimensions' => [26.7, 9.6, 9.6, 0.86],
        ],
        [
            'sku' => 'DAH-HFW1430S1', 'name' => 'Dahua IPC-HFW1430S1-A 4MP Bullet Network Camera',
            'brand' => 'dahua', 'category' => 'ip-cameras', 'price' => 3290, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'Dahua\'s workhorse 4MP bullet — the direct alternative to the Hikvision, priced a little under it.',
            'body' => 'A straightforward 4MP fixed bullet with a built-in microphone and 30 metres of infrared. If the site already runs a Dahua recorder this is the camera to add to it; mixing brands across a recorder works over ONVIF but you lose the smarter event handling.',
            'specs' => ['4MP (2560 x 1440) at 20fps', '2.8mm fixed lens, 105 degree view', 'Up to 30m infrared night vision', 'Built-in microphone', 'IP67 weatherproof, PoE powered'],
            'dimensions' => [17.3, 7.1, 7.1, 0.42],
        ],
        [
            'sku' => 'DAH-HDW1439T1', 'name' => 'Dahua IPC-HDW1439T1-A-LED 4MP Full-colour Eyeball Network Camera',
            'brand' => 'dahua', 'category' => 'ip-cameras', 'price' => 3890, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'Full colour after dark in an eyeball body, at close to the price of an ordinary infrared camera.',
            'body' => 'Dahua\'s answer to ColorVu, and the cheapest way onto colour night footage that we stock. The warm LED is always on at low level, which some households dislike indoors and every shop owner likes outdoors, because a lit camera is also a deterrent.',
            'specs' => ['4MP (2688 x 1520) at 20fps', '24-hour full-colour imaging', 'Up to 30m warm supplement light', '2.8mm fixed lens', 'IP67, PoE, built-in microphone'],
            'dimensions' => [10.9, 10.9, 8.9, 0.35], 'featured' => true,
        ],
        [
            'sku' => 'DAH-HDBW2841E', 'name' => 'Dahua IPC-HDBW2841E-S 8MP WizSense Vandal Dome Network Camera',
            'brand' => 'dahua', 'category' => 'ip-cameras', 'price' => 9250, 'image' => 'dome', 'stock' => 'stocked',
            'short' => '8MP in a vandal-resistant dome, with people and vehicle filtering done on the camera.',
            'body' => 'The dome to specify where the resolution has to survive a crop and the housing has to survive a customer. WizSense does the person and vehicle classification on the camera itself, so it works the same whichever recorder it is plugged into.',
            'specs' => ['8MP (3840 x 2160) at 20fps', 'WizSense person and vehicle classification', 'IK10 vandal-resistant housing', 'Up to 40m infrared night vision', 'IP67, PoE, built-in microphone'],
            'dimensions' => [12.2, 12.2, 9.3, 0.62],
        ],
        [
            'sku' => 'DAH-HFW2449S', 'name' => 'Dahua IPC-HFW2449S-S-IL 4MP Smart Dual Light Bullet Network Camera',
            'brand' => 'dahua', 'category' => 'ip-cameras', 'price' => 5150, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'Runs on infrared until something moves, then switches to white light and colour.',
            'body' => 'Dual illumination is the sensible compromise between a camera that glows all night and one that only records grey. It sits on infrared until the analytics see a person or a vehicle, then lights up and records in colour. Neighbours stop complaining and the footage is still usable.',
            'specs' => ['4MP (2688 x 1520) at 25fps', 'Smart Dual Light: infrared plus white light', 'Person and vehicle classification', 'Active deterrence siren and strobe', 'IP67, PoE, built-in microphone'],
            'dimensions' => [21.0, 8.2, 8.2, 0.55],
        ],
        [
            'sku' => 'UNV-2124LB', 'name' => 'Uniview IPC2124LB-SF28-A 4MP Mini Bullet Network Camera',
            'brand' => 'uniview', 'category' => 'ip-cameras', 'price' => 3150, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'The budget 4MP bullet — small, honest, and the cheapest way to fill out a channel count.',
            'body' => 'Uniview sits under both Hikvision and Dahua on price without dropping to an unbranded camera, which matters when a job needs twelve cameras and only four of them are in positions that will ever be reviewed closely. Full ONVIF support, so it records to any NVR.',
            'specs' => ['4MP (2592 x 1520) at 20fps', '2.8mm fixed lens, 103 degree view', 'Up to 30m infrared night vision', 'ONVIF Profile S and G', 'IP67 weatherproof, PoE powered'],
            'dimensions' => [16.0, 6.5, 6.5, 0.35],
        ],
        [
            'sku' => 'UNV-3614LE', 'name' => 'Uniview IPC3614LE-ADF28K 4MP Dome Network Camera',
            'brand' => 'uniview', 'category' => 'ip-cameras', 'price' => 4250, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'A 4MP dome with basic line-crossing and intrusion detection built in.',
            'body' => 'The dome counterpart to the 2124LB, with enough on-camera analytics — line crossing, intrusion, and a rough person filter — to cut down the review time on a small site. Good value where a full AcuSense or WizSense camera is more than the position warrants.',
            'specs' => ['4MP (2592 x 1520) at 20fps', 'Line crossing and intrusion detection', '2.8mm fixed lens', 'Up to 30m infrared night vision', 'IP67, IK10, PoE powered'],
            'dimensions' => [11.0, 11.0, 8.2, 0.48],
        ],
        [
            'sku' => 'UNV-2325EBR', 'name' => 'Uniview IPC2325EBR-DPZ28 5MP Motorised Zoom Bullet Network Camera',
            'brand' => 'uniview', 'category' => 'ip-cameras', 'price' => 8950, 'image' => 'camera', 'stock' => 'machine',
            'short' => 'Motorised zoom, so the frame is set from the recorder rather than from a ladder.',
            'body' => 'A fixed-lens camera is aimed once and lives with it. A motorised zoom is adjusted from the recorder afterwards, which is worth the extra on any camera mounted above three metres or on a run you would rather not scaffold twice.',
            'specs' => ['5MP (2880 x 1620) at 20fps', '2.8-12mm motorised zoom and focus', 'Up to 50m infrared night vision', 'Smart infrared, avoids blowing out close subjects', 'IP67, PoE powered'],
            'dimensions' => [28.0, 9.5, 9.5, 1.1],
        ],
        [
            'sku' => 'EZV-C3WN', 'name' => 'Ezviz C3WN 1080p Outdoor Wi-Fi Camera',
            'brand' => 'ezviz', 'category' => 'ip-cameras', 'price' => 2450, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'Outdoor Wi-Fi with no recorder and no cabling — a card in the camera and an app on the phone.',
            'body' => 'For a house or a stall where running cable to a recorder is not going to happen. It records to a microSD card in the camera and streams to the Ezviz app. Understand the trade: if the camera is taken, so is the footage, unless you also pay for cloud storage.',
            'specs' => ['1080p (1920 x 1080) at 15fps', '2.4GHz Wi-Fi, no Ethernet required', 'Up to 30m infrared night vision', 'microSD card slot to 256GB', 'IP66 weatherproof'],
            'dimensions' => [15.6, 6.3, 6.3, 0.3],
        ],
        [
            'sku' => 'EZV-C6N', 'name' => 'Ezviz C6N 1080p Indoor Pan and Tilt Wi-Fi Camera',
            'brand' => 'ezviz', 'category' => 'ip-cameras', 'price' => 1850, 'image' => 'dome', 'stock' => 'bulk',
            'short' => 'The indoor camera we sell most of — it pans, it tilts, and it costs less than a month of a guard.',
            'body' => 'A motorised indoor camera that covers a whole room from one corner, with two-way audio and motion tracking. Bought for shops, sari-sari stores, clinics and for keeping an eye on a house while everyone is at work. Sets up from a phone in about five minutes.',
            'specs' => ['1080p (1920 x 1080) at 15fps', '340 degree pan, 105 degree tilt', 'Motion tracking and two-way audio', 'Up to 10m infrared night vision', 'microSD card slot to 256GB'],
            'dimensions' => [8.5, 8.5, 11.5, 0.24], 'featured' => true,
        ],
        [
            'sku' => 'EZV-H6C2K', 'name' => 'Ezviz H6c Pro 2K Pan and Tilt Wi-Fi Camera',
            'brand' => 'ezviz', 'category' => 'ip-cameras', 'price' => 3150, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'The C6N at 2K with colour night vision and a siren — the step up worth taking indoors.',
            'body' => 'Where the C6N gives you a grey room after dark, the H6c Pro keeps colour, and it will sound a siren and flash rather than just send a notification nobody reads. The extra resolution matters most for reading a face across a shop floor.',
            'specs' => ['2K (2304 x 1296) at 15fps', '355 degree pan, 80 degree tilt', 'Colour night vision with supplement light', 'Built-in siren and strobe', 'microSD card slot to 512GB'],
            'dimensions' => [9.6, 9.6, 12.4, 0.32],
        ],
        [
            'sku' => 'IMO-BULLET2E', 'name' => 'Imou Bullet 2E 1080p Outdoor Wi-Fi Camera',
            'brand' => 'imou', 'category' => 'ip-cameras', 'price' => 1990, 'image' => 'camera', 'stock' => 'bulk',
            'short' => 'The cheapest weatherproof Wi-Fi camera we will put our name to.',
            'body' => 'Imou is Dahua\'s consumer line, so the app and the firmware are maintained rather than abandoned, which is not true of most cameras at this price. A sensible pick for a gate, a back door or a yard where the alternative was no camera at all.',
            'specs' => ['1080p (1920 x 1080) at 20fps', '2.4GHz Wi-Fi', 'Up to 30m infrared night vision', 'Human detection with app alerts', 'IP67 weatherproof'],
            'dimensions' => [14.5, 6.0, 6.0, 0.27],
        ],
        [
            'sku' => 'IMO-RANGER2', 'name' => 'Imou Ranger 2 2MP Indoor Pan and Tilt Wi-Fi Camera',
            'brand' => 'imou', 'category' => 'ip-cameras', 'price' => 2290, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'Indoor pan and tilt with a privacy shutter that physically covers the lens.',
            'body' => 'The Ranger 2 rotates its lens down into the base when privacy mode is on, so it is obvious at a glance that it is not watching. That is a real advantage in a home or a consultation room, where a camera nobody trusts gets unplugged and then forgotten about.',
            'specs' => ['2MP (1920 x 1080) at 25fps', '355 degree pan, 90 degree tilt', 'Mechanical privacy shutter', 'Human detection and two-way audio', 'microSD card slot to 256GB'],
            'dimensions' => [8.9, 8.9, 11.7, 0.26],
        ],
        [
            'sku' => 'REO-RLC810A', 'name' => 'Reolink RLC-810A 4K PoE Bullet Network Camera',
            'brand' => 'reolink', 'category' => 'ip-cameras', 'price' => 6450, 'image' => 'camera', 'stock' => 'stocked',
            'short' => '4K over PoE with no subscription and no cloud account required.',
            'body' => 'Reolink cameras record to their own NVR or to a network share and are usable entirely on a local network, which suits customers who do not want footage leaving the building. Person and vehicle detection runs on the camera. Works over ONVIF with third-party recorders.',
            'specs' => ['4K (3840 x 2160) at 25fps', 'Person and vehicle detection on camera', 'Up to 30m infrared night vision', 'ONVIF and RTSP, no subscription required', 'IP66, PoE powered'],
            'dimensions' => [20.5, 7.4, 7.4, 0.48],
        ],
        [
            'sku' => 'TPL-VIGIC340', 'name' => 'TP-Link VIGI C340 4MP Outdoor Bullet Network Camera',
            'brand' => 'tp-link-vigi', 'category' => 'ip-cameras', 'price' => 4150, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'For sites already on TP-Link networking — one controller for the switches and the cameras.',
            'body' => 'The VIGI line is managed from the same Omada controller as TP-Link\'s switches and access points, which is genuinely convenient on a site that already runs them. As a camera it is a competent 4MP fixed bullet, competitive with the Uniview on price.',
            'specs' => ['4MP (2560 x 1440) at 30fps', 'Smart detection: person and vehicle', 'Up to 30m infrared night vision', 'Omada controller integration, ONVIF', 'IP67, PoE powered'],
            'dimensions' => [18.0, 7.2, 7.2, 0.44],
        ],
        [
            'sku' => 'AXI-M2036LE', 'name' => 'Axis M2036-LE 4MP Outdoor Bullet Network Camera',
            'brand' => 'axis', 'category' => 'ip-cameras', 'price' => 24500, 'image' => 'camera', 'stock' => 'low',
            'short' => 'Specified where a standard has to be met rather than a price — banks, ports and utilities.',
            'body' => 'Axis costs several times what the volume brands do and is bought for reasons that have nothing to do with picture quality: signed firmware, a documented security posture, a ten-year support horizon and an approved-vendor list that a bank or a port authority will accept. Ordered in for projects rather than held deep.',
            'specs' => ['4MP (2688 x 1512) at 25fps', 'Signed firmware and secure boot', 'Lightfinder 2.0 low-light imaging', 'Zipstream bandwidth reduction', 'IP66/IP67, IK08, PoE powered'],
            'dimensions' => [15.0, 7.4, 7.4, 0.55],
        ],
        [
            'sku' => 'HAN-QNO8010R', 'name' => 'Hanwha Vision QNO-8010R 5MP Outdoor Bullet Network Camera',
            'brand' => 'hanwha-vision', 'category' => 'ip-cameras', 'price' => 16950, 'image' => 'camera', 'stock' => 'low',
            'short' => 'A Korean-built 5MP bullet for specifications that exclude Chinese-manufactured cameras.',
            'body' => 'Some tenders — particularly government and defence-adjacent work — exclude several of the volume brands outright. Hanwha is the alternative we carry for those jobs: a well-built 5MP camera with a five-year warranty and no procurement argument attached to it.',
            'specs' => ['5MP (2592 x 1944) at 20fps', '2.8mm fixed lens', 'Up to 20m infrared night vision', 'Hallway view and WiseStream II compression', 'IP66, PoE powered'],
            'dimensions' => [18.7, 7.0, 7.0, 0.5],
        ],
        [
            'sku' => 'BOS-NDE3502AL', 'name' => 'Bosch FLEXIDOME IP 3000i IR 5MP Dome Network Camera',
            'brand' => 'bosch', 'category' => 'ip-cameras', 'price' => 21500, 'image' => 'dome', 'stock' => 'low',
            'short' => 'Built-in video analytics as standard, on a dome designed to be commissioned once and left.',
            'body' => 'Bosch ships Essential Video Analytics on the camera rather than as a licence, so intrusion, loitering and object-removal rules run without a server behind them. Bought for warehouses and industrial sites where the alternative is paying for analytics per channel forever.',
            'specs' => ['5MP (2880 x 1620) at 30fps', 'Essential Video Analytics included', 'Up to 30m infrared night vision', 'Intelligent Dynamic Noise Reduction', 'IP66, IK10, PoE powered'],
            'dimensions' => [13.0, 13.0, 9.8, 0.9],
        ],

        // --- Analogue cameras ----------------------------------------------
        [
            'sku' => 'HIK-2CE16D0T', 'name' => 'Hikvision DS-2CE16D0T-IRPF 2MP HD-TVI Bullet Camera',
            'brand' => 'hikvision', 'category' => 'analogue-cameras', 'price' => 850, 'image' => 'camera', 'stock' => 'bulk',
            'short' => 'The cheapest camera in the catalogue, and the right answer when the coax is already in the wall.',
            'body' => 'If a building already has coaxial cable run to every corner, replacing the cameras and the DVR costs a fraction of pulling network cable through the same walls. This is the camera that job is built on: 2MP over HD-TVI, twenty metres of infrared, and a price that lets a customer do eight positions at once.',
            'specs' => ['2MP (1920 x 1080) over HD-TVI', '2.8mm fixed lens, 92 degree view', 'Up to 20m infrared night vision', 'Switchable TVI, AHD, CVI and CVBS output', 'IP66 weatherproof, 12V DC'],
            'dimensions' => [15.5, 6.3, 6.3, 0.3], 'featured' => true,
        ],
        [
            'sku' => 'HIK-2CE76D0T', 'name' => 'Hikvision DS-2CE76D0T-ITMF 2MP HD-TVI Turret Camera',
            'brand' => 'hikvision', 'category' => 'analogue-cameras', 'price' => 950, 'image' => 'dome', 'stock' => 'bulk',
            'short' => 'The turret body of the same 2MP analogue camera, for indoor and low-mounted positions.',
            'body' => 'Same sensor and the same coax as the DS-2CE16D0T, in a turret that is harder to knock askew and less obtrusive on a ceiling. Most eight-camera analogue jobs end up as a mix of the two bodies rather than all of one.',
            'specs' => ['2MP (1920 x 1080) over HD-TVI', '2.8mm fixed lens, 92 degree view', 'Up to 30m infrared night vision', 'Switchable TVI, AHD, CVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [9.1, 9.1, 7.7, 0.28],
        ],
        [
            'sku' => 'HIK-2CE16H0T', 'name' => 'Hikvision DS-2CE16H0T-ITPF 5MP HD-TVI Bullet Camera',
            'brand' => 'hikvision', 'category' => 'analogue-cameras', 'price' => 1450, 'image' => 'camera', 'stock' => 'stocked',
            'short' => '5MP down existing coax — the upgrade that does not need a single new cable.',
            'body' => 'HD-TVI carries 5MP over the same RG59 that used to carry a 700-line analogue picture, so an old system can more than double its detail for the cost of the cameras and a recorder. Check the cable is real copper first; copper-clad aluminium will not make the distance at this resolution.',
            'specs' => ['5MP (2560 x 1944) over HD-TVI', '2.8mm fixed lens, 85 degree view', 'Up to 20m infrared night vision', 'Switchable TVI, AHD, CVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [15.5, 6.3, 6.3, 0.32],
        ],
        [
            'sku' => 'DAH-HACB1A21', 'name' => 'Dahua HAC-B1A21 2MP HDCVI Bullet Camera',
            'brand' => 'dahua', 'category' => 'analogue-cameras', 'price' => 790, 'image' => 'camera', 'stock' => 'bulk',
            'short' => 'Dahua\'s entry analogue bullet — the volume camera for a budget eight-channel job.',
            'body' => 'A plain, reliable 2MP HDCVI bullet with a metal housing. It does one thing and it does it for years. Where a customer wants sixteen cameras and has the budget for eight, this is the camera that makes sixteen possible.',
            'specs' => ['2MP (1920 x 1080) over HDCVI', '2.8mm fixed lens, 103 degree view', 'Up to 20m infrared night vision', 'Switchable CVI, AHD, TVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [14.2, 6.0, 6.0, 0.26],
        ],
        [
            'sku' => 'DAH-HACT1A51', 'name' => 'Dahua HAC-T1A51 5MP HDCVI Eyeball Camera',
            'brand' => 'dahua', 'category' => 'analogue-cameras', 'price' => 1390, 'image' => 'dome', 'stock' => 'stocked',
            'short' => '5MP in an eyeball body, on coax — detail for the door without rewiring the building.',
            'body' => 'The camera to put on the two or three positions in an analogue system that actually need to identify somebody, while the cheaper 2MP cameras cover the rest. A recorder handles mixed resolutions on different channels without complaint.',
            'specs' => ['5MP (2880 x 1620) over HDCVI', '2.8mm fixed lens, 105 degree view', 'Up to 20m infrared night vision', 'Switchable CVI, AHD, TVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [9.5, 9.5, 8.0, 0.24],
        ],
        [
            'sku' => 'DAH-HACHFW1509C', 'name' => 'Dahua HAC-HFW1509C-A-LED 5MP Full-colour HDCVI Bullet Camera',
            'brand' => 'dahua', 'category' => 'analogue-cameras', 'price' => 1950, 'image' => 'camera', 'stock' => 'stocked',
            'short' => 'Colour night footage on an analogue system, which used to be impossible at this price.',
            'body' => 'Full-colour night imaging has come down to the analogue range, and on a shop front it changes what the recording is worth: a description with a colour in it is evidence, a grey silhouette generally is not. Built-in microphone, so the audio comes down the same coax.',
            'specs' => ['5MP (2880 x 1620) over HDCVI', '24-hour full-colour imaging', 'Up to 40m warm supplement light', 'Built-in microphone, audio over coax', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [17.6, 7.0, 7.0, 0.38],
        ],
        [
            'sku' => 'UNV-UACB112', 'name' => 'Uniview UAC-B112-F28 2MP Analogue Bullet Camera',
            'brand' => 'uniview', 'category' => 'analogue-cameras', 'price' => 820, 'image' => 'camera', 'stock' => 'bulk',
            'short' => 'A third analogue option so a large job is never held up by one brand being out of stock.',
            'body' => 'Functionally interchangeable with the Hikvision and Dahua 2MP bullets and priced between them. Carried mainly so a sixteen-camera order can be filled from one branch on the day rather than waiting on a transfer.',
            'specs' => ['2MP (1920 x 1080) analogue', '2.8mm fixed lens, 106 degree view', 'Up to 20m infrared night vision', 'Switchable TVI, AHD, CVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [14.0, 6.2, 6.2, 0.27],
        ],
        [
            'sku' => 'UNV-UACT115', 'name' => 'Uniview UAC-T115-F28 5MP Analogue Turret Camera',
            'brand' => 'uniview', 'category' => 'analogue-cameras', 'price' => 1420, 'image' => 'dome', 'stock' => 'stocked',
            'short' => 'The 5MP turret that pairs with the UAC-B112 on a mixed-resolution analogue system.',
            'body' => 'Five megapixels over coax in a turret housing, for the indoor positions on a job where the outdoor runs are bullets. Same switchable output as the rest of the analogue range, so it will talk to any DVR in the catalogue.',
            'specs' => ['5MP (2880 x 1620) analogue', '2.8mm fixed lens, 102 degree view', 'Up to 30m infrared night vision', 'Switchable TVI, AHD, CVI and CVBS output', 'IP67 weatherproof, 12V DC'],
            'dimensions' => [9.8, 9.8, 8.2, 0.26],
        ],

        // --- PTZ and speed domes -------------------------------------------
        [
            'sku' => 'HIK-2DE4225IW', 'name' => 'Hikvision DS-2DE4225IW-DE 2MP 25x Network Speed Dome',
            'brand' => 'hikvision', 'category' => 'ptz-and-speed-domes', 'price' => 32500, 'image' => 'ptz', 'stock' => 'machine',
            'short' => 'One camera that covers a yard, a car park or a compound from a single pole.',
            'body' => 'Twenty-five times optical zoom on a motorised head, with patrol patterns and the ability to lock onto and follow a moving subject. On a wide site it replaces four or five fixed cameras, and unlike them it can be driven to read a plate at the far gate.',
            'specs' => ['2MP (1920 x 1080) at 25fps', '25x optical zoom, 4.8-120mm', '360 degree continuous pan, 16x digital zoom', 'Up to 100m infrared night vision', 'IP66, IK10, PoE+ or 24V AC'],
            'dimensions' => [20.0, 20.0, 30.0, 3.2], 'featured' => true, 'delivery' => true,
        ],
        [
            'sku' => 'HIK-2AE4225TI', 'name' => 'Hikvision DS-2AE4225TI-D 2MP 25x HD-TVI Speed Dome',
            'brand' => 'hikvision', 'category' => 'ptz-and-speed-domes', 'price' => 24500, 'image' => 'ptz', 'stock' => 'machine',
            'short' => 'The same 25x head, on coax, for a site that is staying analogue.',
            'body' => 'A PTZ for an HD-TVI system, controlled over the coax itself rather than a separate RS-485 pair, which removes the wiring mistake that used to make half of these calls a service visit. Pairs with any of the Hikvision DVRs in the catalogue.',
            'specs' => ['2MP (1920 x 1080) over HD-TVI', '25x optical zoom, 4.8-120mm', '360 degree continuous pan', 'Up to 100m infrared night vision', 'PTZ control over coax, 24V AC'],
            'dimensions' => [20.0, 20.0, 30.0, 3.0],
        ],
        [
            'sku' => 'DAH-SD49225XA', 'name' => 'Dahua SD49225XA-HNR 2MP 25x Starlight IR Network PTZ Camera',
            'brand' => 'dahua', 'category' => 'ptz-and-speed-domes', 'price' => 29500, 'image' => 'ptz', 'stock' => 'machine',
            'short' => 'Starlight sensor and auto-tracking — it follows a person across the yard without an operator.',
            'body' => 'The Starlight sensor is what makes this usable on an unlit site: it holds colour at light levels where an ordinary PTZ has already given up. Auto-tracking picks up a person or vehicle from the analytics and keeps the head on them until they leave the area.',
            'specs' => ['2MP (1920 x 1080) at 50fps', '25x optical zoom, 4.8-120mm', 'Starlight low-light imaging', 'Auto-tracking and IVS rules', 'IP66, IK10, PoE+ or 24V AC'],
            'dimensions' => [20.4, 20.4, 30.6, 3.5],
        ],
        [
            'sku' => 'DAH-SD1A404XB', 'name' => 'Dahua SD1A404XB-GNR 4MP 4x Wi-Fi PTZ Camera',
            'brand' => 'dahua', 'category' => 'ptz-and-speed-domes', 'price' => 8950, 'image' => 'ptz', 'stock' => 'stocked',
            'short' => 'A small outdoor PTZ on Wi-Fi, for a house or a yard that does not warrant a full speed dome.',
            'body' => 'Four times zoom rather than twenty-five, and Wi-Fi rather than a cable run, which puts a genuinely motorised camera within reach of a household budget. Full colour at night, active deterrence with a siren and a spotlight, and a card slot so it works without a recorder.',
            'specs' => ['4MP (2560 x 1440) at 25fps', '4x optical zoom, 2.7-11mm', 'Full-colour night vision', 'Active deterrence: siren and spotlight', 'IP66, 2.4/5GHz Wi-Fi, microSD to 256GB'],
            'dimensions' => [12.5, 12.5, 17.0, 0.95],
        ],
        [
            'sku' => 'UNV-IPC6412LR', 'name' => 'Uniview IPC6412LR-X16-VG 2MP 16x Network PTZ Dome Camera',
            'brand' => 'uniview', 'category' => 'ptz-and-speed-domes', 'price' => 26500, 'image' => 'ptz', 'stock' => 'machine',
            'short' => 'Sixteen times zoom at a price that lets a mid-sized site have a PTZ at all.',
            'body' => 'Less reach than the 25x domes and correspondingly cheaper. On a school, a compound or a medium warehouse, sixteen times is enough to cover the ground, and the saving usually pays for two more fixed cameras somewhere else on the site.',
            'specs' => ['2MP (1920 x 1080) at 30fps', '16x optical zoom, 5-80mm', '360 degree continuous pan', 'Up to 100m infrared night vision', 'IP66, IK10, PoE+ or 24V AC'],
            'dimensions' => [18.5, 18.5, 27.0, 2.6],
        ],
        [
            'sku' => 'REO-RLC823A', 'name' => 'Reolink RLC-823A 4K 5x Optical Zoom PoE PTZ Camera',
            'brand' => 'reolink', 'category' => 'ptz-and-speed-domes', 'price' => 12950, 'image' => 'ptz', 'stock' => 'stocked',
            'short' => '4K on a motorised head for the price of a good fixed camera, with no subscription.',
            'body' => 'The gap between a household Wi-Fi PTZ and a commercial speed dome, filled properly: 4K, PoE, five times zoom and auto-tracking, recording to a card or to a local NVR with no cloud account involved. The one we recommend for a large house or a small yard.',
            'specs' => ['4K (3840 x 2160) at 20fps', '5x optical zoom, 2.7-13.5mm', '360 degree pan, 90 degree tilt', 'Auto-tracking of people and vehicles', 'IP66, PoE, microSD to 256GB'],
            'dimensions' => [14.0, 14.0, 19.5, 1.2], 'featured' => true,
        ],

        // --- Network video recorders ---------------------------------------
        [
            'sku' => 'HIK-7104NIQ14P', 'name' => 'Hikvision DS-7104NI-Q1/4P 4-Channel PoE Network Video Recorder',
            'brand' => 'hikvision', 'category' => 'network-video-recorders', 'price' => 5450, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Four PoE ports built in, so four cameras need four cables and nothing else.',
            'body' => 'The recorder at the centre of most small jobs. The four PoE ports power and record the cameras directly, which means no separate switch, no separate power supply, and one box to explain to the customer. Takes one drive of up to 6TB.',
            'specs' => ['4 channels, up to 4K decoding', '4 built-in PoE ports, 35W budget', 'One SATA bay, up to 6TB', '40Mbps incoming bandwidth', 'HDMI and VGA output, Hik-Connect app'],
            'dimensions' => [26.0, 22.5, 4.8, 1.15], 'featured' => true,
        ],
        [
            'sku' => 'HIK-7108NIQ18P', 'name' => 'Hikvision DS-7108NI-Q1/8P 8-Channel PoE Network Video Recorder',
            'brand' => 'hikvision', 'category' => 'network-video-recorders', 'price' => 8250, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Eight PoE ports — the size most shops and small offices actually end up needing.',
            'body' => 'Almost every four-camera job comes back within two years wanting more cameras. Where the budget allows, start here: eight ports costs about half again as much as four and saves buying the recorder twice.',
            'specs' => ['8 channels, up to 4K decoding', '8 built-in PoE ports, 75W budget', 'One SATA bay, up to 6TB', '80Mbps incoming bandwidth', 'HDMI and VGA output, Hik-Connect app'],
            'dimensions' => [31.5, 24.0, 4.8, 1.5],
        ],
        [
            'sku' => 'HIK-7616NIK216P', 'name' => 'Hikvision DS-7616NI-K2/16P 16-Channel 4K PoE Network Video Recorder',
            'brand' => 'hikvision', 'category' => 'network-video-recorders', 'price' => 22500, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Sixteen PoE ports and two drive bays — the recorder for a warehouse or a whole building.',
            'body' => 'Two drive bays matter more than the channel count at this size: sixteen 4MP cameras recording continuously will fill a single 8TB drive in under a month. Delivered and commissioned as standard, because the retention calculation is worth doing properly before the first recording.',
            'specs' => ['16 channels, 4K live view and playback', '16 built-in PoE ports, 200W budget', 'Two SATA bays, up to 10TB each', '160Mbps incoming bandwidth', 'Dual HDMI output, RAID not supported'],
            'dimensions' => [38.5, 31.5, 5.2, 3.2], 'delivery' => true,
        ],
        [
            'sku' => 'DAH-NVR2104HSP', 'name' => 'Dahua NVR2104HS-P-4KS3 4-Channel PoE Network Video Recorder',
            'brand' => 'dahua', 'category' => 'network-video-recorders', 'price' => 5150, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'The Dahua four-channel — pair it with Dahua cameras and the smart events line up.',
            'body' => 'Functionally the equal of the Hikvision four-channel and a little cheaper. The reason to pick one over the other is the cameras: a WizSense camera on a WizSense recorder gives you searchable person and vehicle events, and mixing brands loses that.',
            'specs' => ['4 channels, up to 8MP decoding', '4 built-in PoE ports, 48W budget', 'One SATA bay, up to 10TB', '80Mbps incoming bandwidth', 'HDMI and VGA output, DMSS app'],
            'dimensions' => [23.7, 20.7, 4.5, 1.05],
        ],
        [
            'sku' => 'DAH-NVR4108HS8P', 'name' => 'Dahua NVR4108HS-8P-4KS3 8-Channel PoE Network Video Recorder',
            'brand' => 'dahua', 'category' => 'network-video-recorders', 'price' => 8950, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Eight channels with WizSense search — find every person who crossed a line, not every frame.',
            'body' => 'The recorder half of the eight-camera business bundle. Its real advantage over the cheaper models is search: with WizSense cameras attached you can ask it for people rather than motion, which turns an afternoon of scrubbing into a couple of minutes.',
            'specs' => ['8 channels, up to 12MP decoding', '8 built-in PoE ports, 96W budget', 'One SATA bay, up to 10TB', '80Mbps incoming bandwidth', 'AI search by person and vehicle attributes'],
            'dimensions' => [26.0, 22.6, 4.8, 1.4], 'featured' => true,
        ],
        [
            'sku' => 'DAH-NVR5232', 'name' => 'Dahua NVR5232-16P-EI 32-Channel WizSense Network Video Recorder',
            'brand' => 'dahua', 'category' => 'network-video-recorders', 'price' => 34500, 'image' => 'recorder', 'stock' => 'low',
            'short' => 'Thirty-two channels and two bays, for a campus, a plant or a multi-storey building.',
            'body' => 'At this size the recorder is running analytics of its own — face detection, people counting and perimeter rules — on cameras that may not have them built in. Specified on projects; ordered in with a site survey rather than carried deep at every branch.',
            'specs' => ['32 channels, 16 with built-in PoE', 'Face detection and perimeter protection on the recorder', 'Two SATA bays, up to 16TB each', '384Mbps incoming bandwidth', 'Dual HDMI 4K output, e-SATA'],
            'dimensions' => [44.0, 34.0, 4.9, 4.1], 'delivery' => true,
        ],
        [
            'sku' => 'UNV-NVR30104LBP', 'name' => 'Uniview NVR301-04LB-P4 4-Channel PoE Network Video Recorder',
            'brand' => 'uniview', 'category' => 'network-video-recorders', 'price' => 4850, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'The cheapest four-channel PoE recorder we are willing to warrant.',
            'body' => 'Where the budget is genuinely fixed, this and four Uniview bullets will put a working, recorded system on a small shop for less than any other combination in the catalogue. Full ONVIF, so better cameras can be added to it later.',
            'specs' => ['4 channels, up to 8MP decoding', '4 built-in PoE ports, 50W budget', 'One SATA bay, up to 6TB', '40Mbps incoming bandwidth', 'ONVIF Profile S and G, EZView app'],
            'dimensions' => [21.5, 20.5, 4.5, 0.95],
        ],
        [
            'sku' => 'UNV-NVR30208SP', 'name' => 'Uniview NVR302-08S2-P8 8-Channel PoE Network Video Recorder',
            'brand' => 'uniview', 'category' => 'network-video-recorders', 'price' => 9450, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Eight PoE ports and two drive bays — unusual at this price, and the reason we stock it.',
            'body' => 'Most eight-channel recorders take one drive. This takes two, which means a site can run a longer retention period without moving up to a sixteen-channel chassis it does not need. That is the whole argument for it, and on a site that has to keep ninety days it is a strong one.',
            'specs' => ['8 channels, up to 8MP decoding', '8 built-in PoE ports, 96W budget', 'Two SATA bays, up to 8TB each', '160Mbps incoming bandwidth', 'ONVIF Profile S and G, EZView app'],
            'dimensions' => [30.0, 25.5, 4.8, 1.9],
        ],
        [
            'sku' => 'TPL-VIGINVR1004', 'name' => 'TP-Link VIGI NVR1004H-4P 4-Channel PoE Network Video Recorder',
            'brand' => 'tp-link-vigi', 'category' => 'network-video-recorders', 'price' => 5950, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'The recorder for a VIGI camera system, managed from the same Omada controller as the network.',
            'body' => 'Chosen almost entirely for the integration: on a site already running TP-Link switches and access points, the cameras, the recorder and the network appear in one console with one login. As a recorder on its own it is unremarkable, and that is fine.',
            'specs' => ['4 channels, up to 8MP decoding', '4 built-in PoE ports, 53W budget', 'One SATA bay, up to 10TB', '80Mbps incoming bandwidth', 'Omada controller integration, VIGI app'],
            'dimensions' => [24.0, 21.5, 4.4, 1.1],
        ],

        // --- Digital video recorders ---------------------------------------
        [
            'sku' => 'HIK-7204HGHIK1', 'name' => 'Hikvision DS-7204HGHI-K1 4-Channel 1080p Lite Digital Video Recorder',
            'brand' => 'hikvision', 'category' => 'digital-video-recorders', 'price' => 3450, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'The four-channel coax recorder — the cheapest complete system in the catalogue starts here.',
            'body' => 'Four analogue cameras, this recorder and a 2TB drive is the entry system, and it still accounts for a large share of what leaves the counter. It also takes one IP camera on top of the four coax channels, which is how most of these grow.',
            'specs' => ['4 analogue channels plus 1 IP channel', 'Records HD-TVI, AHD, CVI and CVBS', 'One SATA bay, up to 10TB', 'H.265 Pro+ compression', 'HDMI and VGA output, Hik-Connect app'],
            'dimensions' => [26.0, 22.2, 4.5, 1.1], 'featured' => true,
        ],
        [
            'sku' => 'HIK-7208HQHIK1', 'name' => 'Hikvision DS-7208HQHI-K1 8-Channel 4MP Lite Digital Video Recorder',
            'brand' => 'hikvision', 'category' => 'digital-video-recorders', 'price' => 6250, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Eight coax channels at 4MP, plus two IP channels for cameras added later.',
            'body' => 'The recorder for an eight-camera analogue upgrade. Recording at 4MP rather than 1080p is what makes a 5MP camera worth fitting; on a Lite recorder capped at 1080p the extra resolution is thrown away before it reaches the drive.',
            'specs' => ['8 analogue channels plus 2 IP channels', 'Records up to 4MP Lite on every channel', 'One SATA bay, up to 10TB', 'H.265 Pro+ compression', 'Motion detection 2.0, human and vehicle filtering'],
            'dimensions' => [31.5, 24.2, 4.5, 1.35],
        ],
        [
            'sku' => 'HIK-7216HQHIK1', 'name' => 'Hikvision DS-7216HQHI-K1 16-Channel 4MP Lite Digital Video Recorder',
            'brand' => 'hikvision', 'category' => 'digital-video-recorders', 'price' => 11500, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Sixteen coax channels — the recorder for a warehouse or a building already wired for analogue.',
            'body' => 'Large analogue systems are usually inherited rather than designed, and replacing sixteen cameras and one recorder is a fraction of the cost of pulling network cable through an occupied building. This is the box that job lands on.',
            'specs' => ['16 analogue channels plus 8 IP channels', 'Records up to 4MP Lite on every channel', 'One SATA bay, up to 10TB', 'H.265 Pro+ compression', 'Human and vehicle filtering on all channels'],
            'dimensions' => [38.5, 32.0, 4.8, 2.6],
        ],
        [
            'sku' => 'DAH-XVR1B04', 'name' => 'Dahua XVR1B04-I 4-Channel Penta-brid Digital Video Recorder',
            'brand' => 'dahua', 'category' => 'digital-video-recorders', 'price' => 3290, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Takes five signal types on any channel, which is what makes it useful on an inherited site.',
            'body' => 'Penta-brid means it will record CVI, TVI, AHD, CVBS and IP on the same box, so a building with cameras from three different eras can be brought onto one recorder without replacing any of them first. That is worth more on a service call than any specification on the sheet.',
            'specs' => ['4 channels, CVI/TVI/AHD/CVBS/IP', 'Up to 5MP Lite recording', 'One SATA bay, up to 10TB', 'Smart motion detection: human and vehicle', 'HDMI and VGA output, DMSS app'],
            'dimensions' => [23.5, 20.0, 4.5, 0.95],
        ],
        [
            'sku' => 'DAH-XVR5108HS', 'name' => 'Dahua XVR5108HS-I3 8-Channel WizSense Digital Video Recorder',
            'brand' => 'dahua', 'category' => 'digital-video-recorders', 'price' => 7450, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'Runs person and vehicle analytics on ordinary analogue cameras, on the recorder.',
            'body' => 'The interesting one in the analogue range: the analytics run on the recorder, so eight-year-old coax cameras get searchable person and vehicle events without being replaced. On a site with a lot of old cameras and a small budget, this is the upgrade to lead with.',
            'specs' => ['8 channels, CVI/TVI/AHD/CVBS/IP', 'WizSense analytics on the recorder', 'Up to 5MP recording', 'One SATA bay, up to 16TB', 'Perimeter protection and SMD Plus'],
            'dimensions' => [26.0, 22.6, 4.8, 1.3],
        ],
        [
            'sku' => 'UNV-XVR30104Q', 'name' => 'Uniview XVR301-04Q 4-Channel Hybrid Digital Video Recorder',
            'brand' => 'uniview', 'category' => 'digital-video-recorders', 'price' => 3150, 'image' => 'recorder', 'stock' => 'machine',
            'short' => 'The budget hybrid recorder, for a four-camera coax system with nothing else asked of it.',
            'body' => 'Four channels, records what you plug into it, and gets out of the way. Bought where the customer wants a working recorded system at the lowest defensible price and is not going to use anything beyond playback and export.',
            'specs' => ['4 channels, TVI/AHD/CVI/CVBS/IP', 'Up to 5MP Lite recording', 'One SATA bay, up to 8TB', 'H.265 compression', 'HDMI and VGA output, EZView app'],
            'dimensions' => [22.0, 20.0, 4.4, 0.9],
        ],

        // --- Surveillance drives -------------------------------------------
        [
            'sku' => 'SEA-SKYHAWK2TB', 'name' => 'Seagate SkyHawk 2TB Surveillance Hard Drive',
            'brand' => 'seagate', 'category' => 'surveillance-drives', 'price' => 3450, 'image' => 'storage', 'stock' => 'stocked',
            'short' => 'Rated for continuous write. A desktop drive in a recorder is a failure with a date on it.',
            'body' => 'A recorder writes twenty-four hours a day, every day, which is not what a desktop drive is built for — they are rated for around 2,400 hours of use a year and a recorder asks for 8,760. SkyHawk is rated for the full load and tuned so that writing does not stall while somebody is reviewing playback.',
            'specs' => ['2TB, 5,900rpm class, SATA 6Gb/s', 'Rated for 24/7 continuous write', 'Up to 64 camera streams', '180TB/year workload rating', '3-year limited warranty'],
            'dimensions' => [14.7, 10.2, 2.7, 0.42], 'featured' => true,
        ],
        [
            'sku' => 'SEA-SKYHAWK4TB', 'name' => 'Seagate SkyHawk 4TB Surveillance Hard Drive',
            'brand' => 'seagate', 'category' => 'surveillance-drives', 'price' => 5950, 'image' => 'storage', 'stock' => 'stocked',
            'short' => 'The size most eight-camera systems should be fitted with, not the 2TB they usually get.',
            'body' => 'Eight 4MP cameras recording continuously produce roughly 1TB a week, so a 2TB drive holds about a fortnight. Most incidents are reported later than that. Work the retention out before choosing the drive, and if in doubt fit the larger one — it is cheaper than the second visit.',
            'specs' => ['4TB, 5,900rpm class, SATA 6Gb/s', 'Rated for 24/7 continuous write', 'Up to 64 camera streams', '180TB/year workload rating', '3-year limited warranty'],
            'dimensions' => [14.7, 10.2, 2.7, 0.49],
        ],
        [
            'sku' => 'SEA-SKYHAWK8TB', 'name' => 'Seagate SkyHawk 8TB Surveillance Hard Drive',
            'brand' => 'seagate', 'category' => 'surveillance-drives', 'price' => 11450, 'image' => 'storage', 'stock' => 'low',
            'short' => 'For sixteen channels, or for anyone who has to keep ninety days of footage.',
            'body' => 'Bought for two reasons: a large channel count, or a retention requirement written into a contract or a permit. Barangay and LGU installations increasingly specify how long footage must be held, and this is the size that makes ninety days realistic on sixteen cameras.',
            'specs' => ['8TB, 7,200rpm, SATA 6Gb/s', 'Rated for 24/7 continuous write', 'Up to 64 camera streams', '550TB/year workload rating', '3-year limited warranty'],
            'dimensions' => [14.7, 10.2, 2.7, 0.65],
        ],
        [
            'sku' => 'WDC-PURPLE2TB', 'name' => 'Western Digital WD Purple 2TB Surveillance Hard Drive',
            'brand' => 'western-digital', 'category' => 'surveillance-drives', 'price' => 3550, 'image' => 'storage', 'stock' => 'stocked',
            'short' => 'The alternative to SkyHawk at the same size, carried so a drive is never the thing holding up a job.',
            'body' => 'AllFrame firmware reduces the dropped frames that appear when a drive is writing several streams and being read at the same time. In practice SkyHawk and Purple are interchangeable at this size; we stock both so a branch is never out of a 2TB drive.',
            'specs' => ['2TB, 5,400rpm class, SATA 6Gb/s', 'Rated for 24/7 continuous write', 'AllFrame technology reduces dropped frames', '180TB/year workload rating', '3-year limited warranty'],
            'dimensions' => [14.7, 10.2, 2.6, 0.4],
        ],
        [
            'sku' => 'WDC-PURPLE4TB', 'name' => 'Western Digital WD Purple 4TB Surveillance Hard Drive',
            'brand' => 'western-digital', 'category' => 'surveillance-drives', 'price' => 6150, 'image' => 'storage', 'stock' => 'stocked',
            'short' => 'Four terabytes of continuous-write storage, supporting up to 64 cameras on one drive.',
            'body' => 'The Purple equivalent of the 4TB SkyHawk and the sensible default for an eight-channel recorder. If the recorder has two bays, two 4TB drives beat one 8TB: the recorder keeps writing when one of them fails, which is the failure that actually happens.',
            'specs' => ['4TB, 5,400rpm class, SATA 6Gb/s', 'Rated for 24/7 continuous write', 'AllFrame technology reduces dropped frames', '180TB/year workload rating', '3-year limited warranty'],
            'dimensions' => [14.7, 10.2, 2.6, 0.45],
        ],

        // --- Alarms and detection ------------------------------------------
        [
            'sku' => 'PAR-SP4000', 'name' => 'Paradox SP4000 4-Zone Expandable Alarm Control Panel',
            'brand' => 'paradox', 'category' => 'alarms-and-detection', 'price' => 4850, 'image' => 'alarm', 'stock' => 'machine',
            'short' => 'Four hardwired zones, expandable to thirty-two — the panel most of our alarm jobs start on.',
            'body' => 'A wired panel is still the right answer for a new building or a renovation, where the cable can go in before the walls close. Four zones covers a house; the same board expands to thirty-two as a business grows, so the panel is not the thing that has to be replaced.',
            'specs' => ['4 hardwired zones, expandable to 32', 'ATZ zone doubling supported', '2 partitions', 'StayD mode: armed while occupied', 'Supports IP, GPRS and voice reporting modules'],
            'dimensions' => [28.0, 22.0, 8.0, 1.8],
        ],
        [
            'sku' => 'PAR-K10V', 'name' => 'Paradox K10V 10-Zone LED Keypad',
            'brand' => 'paradox', 'category' => 'alarms-and-detection', 'price' => 2150, 'image' => 'alarm', 'stock' => 'stocked',
            'short' => 'The keypad by the door — the only part of an alarm system anybody actually touches.',
            'body' => 'Ten zone lights and a keypad, which is all most users want: a light showing which door is open and four digits to arm it. The fancier LCD keypads generate more support calls, not fewer, because people stop reading them.',
            'specs' => ['10 zone indicator LEDs', 'Adjustable backlight and buzzer', 'One-touch arming keys', 'Panic keys', 'Connects on the Paradox 4-wire bus'],
            'dimensions' => [12.0, 9.5, 2.4, 0.16],
        ],
        [
            'sku' => 'PAR-NV5', 'name' => 'Paradox NV5 Digital PIR Motion Detector',
            'brand' => 'paradox', 'category' => 'alarms-and-detection', 'price' => 1450, 'image' => 'alarm', 'stock' => 'stocked',
            'short' => 'The indoor motion detector we fit by default — pet-immune to about 18 kilos.',
            'body' => 'Nearly every false alarm we are called out to is a detector that was mounted badly or was not pet-immune. The NV5 ignores an animal up to about eighteen kilograms and has enough digital signal processing to reject a curtain moving over an air-conditioning vent.',
            'specs' => ['11m x 11m coverage, 110 degree lens', 'Pet immune to 18kg', 'Digital signal processing', 'Auto pulse-count adjustment', 'Wall or corner mount, 12V DC'],
            'dimensions' => [10.5, 6.0, 4.5, 0.11], 'featured' => true,
        ],
        [
            'sku' => 'PAR-MG2WPGD', 'name' => 'Paradox MG-2WPGD Wireless Door and Window Contact',
            'brand' => 'paradox', 'category' => 'alarms-and-detection', 'price' => 1650, 'image' => 'alarm', 'stock' => 'stocked',
            'short' => 'A wireless contact for the door the cable never reached.',
            'body' => 'Every wired alarm job has one or two openings that would need a wall chased to reach. A wireless contact solves them for less than the labour of the alternative. Battery life is around three years and the panel reports a low battery before it goes flat.',
            'specs' => ['433MHz wireless, supervised', 'Built-in reed switch plus external input', 'Around 3-year battery life', 'Tamper switch on cover and back', 'Requires a Paradox wireless receiver module'],
            'dimensions' => [7.5, 3.5, 2.2, 0.06],
        ],
        [
            'sku' => 'HIK-DSPWA64', 'name' => 'Hikvision DS-PWA64-Kit-WE AX PRO Wireless Alarm Kit',
            'brand' => 'hikvision', 'category' => 'alarms-and-detection', 'price' => 18500, 'image' => 'alarm', 'stock' => 'machine',
            'short' => 'A complete wireless alarm for a finished building, with no cable to chase into a wall.',
            'body' => 'Panel, keypad, two detectors, two door contacts and a siren, all wireless and all supervised, commissioned from a phone. This is what to fit in an occupied house or a leased unit where a wired system would mean making good afterwards.',
            'specs' => ['64 wireless zones, Tri-X two-way protocol', 'Kit: panel, keypad, 2 PIRs, 2 contacts, siren', 'Up to 1,600m open-air range', 'Wi-Fi and Ethernet, optional 4G', 'Integrates with Hik-Connect alongside cameras'],
            'dimensions' => [35.0, 28.0, 12.0, 3.4], 'featured' => true,
        ],
        [
            'sku' => 'HIK-DSPDPG2', 'name' => 'Hikvision DS-PDPG2-EG Wireless Glass Break Detector',
            'brand' => 'hikvision', 'category' => 'alarms-and-detection', 'price' => 3250, 'image' => 'alarm', 'stock' => 'stocked',
            'short' => 'Listens for breaking glass, so the alarm goes off at the window rather than in the room.',
            'body' => 'A motion detector triggers once somebody is already inside. A glass break detector triggers at the point of entry, which is several seconds earlier and, on a shop front, the difference between a siren and an empty display case. Covers a 7.5 metre radius of glazing.',
            'specs' => ['7.5m detection radius', 'Dual-stage flex and acoustic analysis', 'Around 3-year battery life', 'Tamper protection', 'Pairs with AX PRO panels'],
            'dimensions' => [8.0, 8.0, 2.5, 0.09],
        ],
        [
            'sku' => 'BOS-ISCBDL2', 'name' => 'Bosch Blue Line Gen 2 PIR Motion Detector',
            'brand' => 'bosch', 'category' => 'alarms-and-detection', 'price' => 2450, 'image' => 'alarm', 'stock' => 'stocked',
            'short' => 'The detector to fit where false alarms have become an argument with the neighbours.',
            'body' => 'Bosch\'s First Step Processing reacts fast to a real intrusion while its dynamic temperature compensation holds steady through the swings that set cheaper detectors off. Specified on sites that have had a false-alarm history and cannot afford another one.',
            'specs' => ['12m x 12m coverage', 'Dynamic temperature compensation', 'First Step Processing', 'Pet immunity options available', 'Sealed optical chamber, 12V DC'],
            'dimensions' => [11.0, 6.1, 4.3, 0.12],
        ],
        [
            'sku' => 'GEN-SIREN30W', 'name' => '30W Outdoor Weatherproof Alarm Siren with Strobe',
            'brand' => null, 'category' => 'alarms-and-detection', 'price' => 1250, 'image' => 'alarm', 'stock' => 'bulk',
            'short' => 'The box on the wall — half of what an alarm does is being visible before it is triggered.',
            'body' => 'A polycarbonate outdoor sounder with a strobe and a tamper switch, at around 110dB. Fit it high, on the front elevation, where it can be seen from the street: an alarm nobody knows is there deters nobody.',
            'specs' => ['110dB at 1 metre, 30W', 'High-intensity strobe', 'Front and rear tamper switches', 'Polycarbonate weatherproof housing', '12V DC, works with any panel'],
            'dimensions' => [26.0, 19.0, 6.5, 0.85],
        ],
        [
            'sku' => 'GEN-SMOKE2W', 'name' => '2-Wire Conventional Photoelectric Smoke Detector',
            'brand' => null, 'category' => 'alarms-and-detection', 'price' => 890, 'image' => 'alarm', 'stock' => 'bulk',
            'short' => 'A conventional smoke head that wires onto an alarm zone — not a substitute for a fire system.',
            'body' => 'Two-wire photoelectric detection, which responds better to the slow smouldering fires that start in wiring and furnishings than an ionisation head does. It reports to the alarm panel. Where a building code requires a certified fire alarm system, that is a different specification and a different quotation.',
            'specs' => ['Photoelectric smoke sensing', '2-wire, 12-24V DC', 'Around 60m2 coverage per head', 'Built-in alarm LED', 'Removable chamber for cleaning'],
            'dimensions' => [10.2, 10.2, 4.6, 0.14],
        ],
        [
            'sku' => 'GEN-PANICBTN', 'name' => 'Wired Panic Button with Key Reset',
            'brand' => null, 'category' => 'alarms-and-detection', 'price' => 650, 'image' => 'alarm', 'stock' => 'bulk',
            'short' => 'Under the counter, where a cashier can reach it without looking down.',
            'body' => 'A latching panic button that stays triggered until it is turned off with the key, so an alarm cannot be cancelled by whoever is standing on the other side of the counter. Fitted in pawnshops, pharmacies, clinics and any till that handles cash.',
            'specs' => ['Latching contact, key reset', 'Normally closed and normally open outputs', 'Surface or under-counter mount', 'No power required, dry contact', 'Works with any alarm panel'],
            'dimensions' => [8.6, 5.4, 2.6, 0.08],
        ],

        // --- Access control -------------------------------------------------
        [
            'sku' => 'ZKT-F18', 'name' => 'ZKTeco F18 Fingerprint and RFID Access Control Terminal',
            'brand' => 'zkteco', 'category' => 'access-control', 'price' => 6450, 'image' => 'access', 'stock' => 'machine',
            'short' => 'Fingerprint or card on the door, with attendance records as a by-product.',
            'body' => 'The terminal that ends the problem of keys being copied and staff signing each other in. It holds 3,000 fingerprints and 30,000 transactions on the device, so it keeps working through a network outage and syncs when the connection returns.',
            'specs' => ['3,000 fingerprint templates, 5,000 cards', '30,000 transaction records on device', 'TCP/IP, RS485 and Wiegand output', 'Access control plus time attendance', 'Built-in relay for a lock, exit button input'],
            'dimensions' => [18.4, 8.4, 3.9, 0.4], 'featured' => true,
        ],
        [
            'sku' => 'ZKT-MB360', 'name' => 'ZKTeco MB360 Face and Fingerprint Time Attendance Terminal',
            'brand' => 'zkteco', 'category' => 'access-control', 'price' => 9950, 'image' => 'access', 'stock' => 'machine',
            'short' => 'Face recognition for attendance, with fingerprint and card as fallbacks.',
            'body' => 'Face recognition removed the last excuse — no touching, no card left at home, and a queue that moves at shift change. Fingerprint and card readers are still on the unit, which matters for the small number of people whose faces the sensor struggles with.',
            'specs' => ['1,500 face templates, 2,000 fingerprints', '10,000 cards, 100,000 transactions', 'Recognition in under 1 second', 'TCP/IP, USB host, Wi-Fi optional', 'Works with ZKTime attendance software'],
            'dimensions' => [19.5, 15.0, 4.2, 0.62],
        ],
        [
            'sku' => 'ZKT-SA40', 'name' => 'ZKTeco SA40 RFID Standalone Access Controller',
            'brand' => 'zkteco', 'category' => 'access-control', 'price' => 2450, 'image' => 'access', 'stock' => 'stocked',
            'short' => 'Card and PIN on one door, with no network and no software behind it.',
            'body' => 'For a single door — a stock room, a server cupboard, a clinic back office — where a networked system is more than the job needs. Cards are enrolled at the unit itself. No cabling beyond power and the lock.',
            'specs' => ['1,000 users, card and PIN', 'EM 125kHz card reader built in', 'Standalone, no software required', 'Doorbell and exit button inputs', 'IP66 weatherproof keypad, 12V DC'],
            'dimensions' => [12.0, 7.8, 2.2, 0.22],
        ],
        [
            'sku' => 'ZKT-LB200', 'name' => 'ZKTeco LB-200 Electric Bolt Lock',
            'brand' => 'zkteco', 'category' => 'access-control', 'price' => 1850, 'image' => 'access', 'stock' => 'stocked',
            'short' => 'A drop bolt for a timber or aluminium door, with a signal back to say it is actually locked.',
            'body' => 'The bolt drops into the frame when power is removed or applied depending on how it is wired, and its status output tells the controller whether the door is genuinely secure rather than merely closed. Fitted where a magnetic lock would spoil the look of a glass door.',
            'specs' => ['Fail-safe operation, 12V DC', 'Lock status output signal', 'Time delay adjustable 0-9 seconds', 'Suits timber, aluminium and glass doors', 'Anti-residual magnetism design'],
            'dimensions' => [19.0, 4.0, 3.2, 0.75],
        ],
        [
            'sku' => 'ZKT-AL280', 'name' => 'ZKTeco AL-280 280kg Electromagnetic Lock',
            'brand' => 'zkteco', 'category' => 'access-control', 'price' => 2650, 'image' => 'access', 'stock' => 'stocked',
            'short' => '280 kilograms of holding force with no moving parts to wear out.',
            'body' => 'A magnetic lock has nothing in it to jam or wear, which is why it is the default on a door that gets used several hundred times a day. It is fail-safe by design: cut the power and the door opens, which is what a fire officer will want to see.',
            'specs' => ['280kg (600lb) holding force', 'Fail-safe: releases on power loss', '12V or 24V DC selectable', 'Door status and lock status outputs', 'Includes L-bracket for inward-opening doors'],
            'dimensions' => [25.0, 4.8, 2.6, 1.55],
        ],
        [
            'sku' => 'HIK-K1T341AMF', 'name' => 'Hikvision DS-K1T341AMF Face Recognition Access Terminal',
            'brand' => 'hikvision', 'category' => 'access-control', 'price' => 18500, 'image' => 'access', 'stock' => 'machine',
            'short' => 'Face recognition on the door, on the same platform as the cameras.',
            'body' => 'Where a site already runs Hikvision cameras and a recorder, this puts the door events into the same system, so an access record and the footage of the person using it sit side by side. That is the argument for it over a cheaper standalone terminal.',
            'specs' => ['6,000 faces, 6,000 cards, 6,000 fingerprints', '150,000 event records', 'Recognition in 0.2 seconds, 0.3-1.5m range', 'Anti-spoofing against photos and video', 'TCP/IP, Wiegand, works with HikCentral'],
            'dimensions' => [21.0, 8.5, 2.9, 0.55],
        ],
        [
            'sku' => 'HIK-KIS603P', 'name' => 'Hikvision DS-KIS603-P Video Door Intercom Kit',
            'brand' => 'hikvision', 'category' => 'access-control', 'price' => 14500, 'image' => 'access', 'stock' => 'machine',
            'short' => 'See and speak to whoever is at the gate, from inside or from a phone.',
            'body' => 'Outdoor station, indoor monitor and the power supply, on a single Cat5 run. The call also goes to the Hik-Connect app, so a gate can be answered from anywhere — which is what most customers actually want, whatever they said they were buying.',
            'specs' => ['2MP outdoor station with fisheye lens', '7-inch touch indoor monitor', 'Answer and unlock from the Hik-Connect app', 'Single Cat5e run, PoE powered', 'Relay output for gate or door release'],
            'dimensions' => [35.0, 26.0, 10.0, 2.2], 'featured' => true,
        ],
        [
            'sku' => 'DAH-ASI1201AD', 'name' => 'Dahua ASI1201A-D Fingerprint Standalone Access Controller',
            'brand' => 'dahua', 'category' => 'access-control', 'price' => 3950, 'image' => 'access', 'stock' => 'stocked',
            'short' => 'Fingerprint and card on one door, at about half the price of a full terminal.',
            'body' => 'The middle option between a card-only reader and a full attendance terminal: fingerprints, cards and PINs on a single door, managed from the unit or over the network, without the attendance reporting nobody on a two-door site is going to use.',
            'specs' => ['3,000 users, 3,000 fingerprints', '150,000 records', 'Card, fingerprint and password entry', 'TCP/IP and RS485, Wiegand output', 'IP55 weatherproof, tamper alarm'],
            'dimensions' => [16.0, 8.2, 2.5, 0.3],
        ],
        [
            'sku' => 'GEN-EXITBTN', 'name' => 'Stainless Exit Button with LED Backplate',
            'brand' => null, 'category' => 'access-control', 'price' => 450, 'image' => 'access', 'stock' => 'bulk',
            'short' => 'The button on the inside of the door. Every access-controlled door needs one.',
            'body' => 'It is the cheapest part of an access control job and the one most often left off the quotation. A stainless plate, a no-touch-required press and an LED so it can be found in the dark. Buy one per door, and a spare.',
            'specs' => ['Stainless steel faceplate', 'Normally open and normally closed contacts', 'Red and green status LED', 'Standard single-gang box mount', 'No power required for the contact'],
            'dimensions' => [8.6, 8.6, 2.0, 0.11],
        ],

        // --- Cabling and power ------------------------------------------------
        [
            'sku' => 'CBL-RG59SIAM', 'name' => 'RG59 Siamese Coaxial Cable with Power, 305m Box',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 5450, 'image' => 'cable', 'stock' => 'bulk',
            'short' => 'Video and power in one jacket — the cable every analogue job is wired with.',
            'body' => 'Solid copper core and a real braid, not the copper-clad aluminium sold at half this price, which loses enough signal over a long run to make a 5MP camera look like a 2MP one. If a customer\'s existing picture is soft at distance, this is usually the reason.',
            'specs' => ['305m (1,000ft) box, RG59 with 2x0.5mm power', 'Solid bare copper core', '95% copper braid shield', 'Rated for runs to 300m at 1080p', 'PVC jacket, indoor and sheltered outdoor use'],
            'dimensions' => [40.0, 40.0, 25.0, 14.5], 'featured' => true,
        ],
        [
            'sku' => 'CBL-CAT6UTP', 'name' => 'Cat6 UTP Solid Copper Network Cable, 305m Box',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 6950, 'image' => 'cable', 'stock' => 'bulk',
            'short' => 'Solid copper Cat6 — the only cable that will carry PoE the full 100 metres.',
            'body' => 'PoE puts current down the same pairs that carry the data, and copper-clad aluminium heats up and drops voltage over distance, so the camera at the end of the run reboots at night when its infrared comes on. That fault takes a day to diagnose and this cable prevents it.',
            'specs' => ['305m (1,000ft) box, Cat6 U/UTP', '23AWG solid bare copper', 'Supports PoE, PoE+ and PoE++ to 100m', 'Sweep tested to 550MHz', 'CM-rated PVC jacket'],
            'dimensions' => [40.0, 40.0, 28.0, 12.8],
        ],
        [
            'sku' => 'CBL-RJ45100', 'name' => 'RJ45 Cat6 Pass-Through Connectors, Pack of 100',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 450, 'image' => 'cable', 'stock' => 'bulk',
            'short' => 'Pass-through plugs, because a badly terminated end is the most common fault on any job.',
            'body' => 'Pass-through connectors let the conductors run out the front so the colour order can be checked before crimping, which turns termination from a skill into a procedure. Gold-plated contacts. Needs a pass-through crimp tool, not an ordinary one.',
            'specs' => ['100 pieces, Cat6 pass-through RJ45', '50-micron gold-plated contacts', 'Accepts 23-26AWG solid and stranded', 'Load bar built into the housing', 'Requires a pass-through crimping tool'],
            'dimensions' => [12.0, 8.0, 5.0, 0.28],
        ],
        [
            'sku' => 'CBL-BNC20', 'name' => 'BNC Twist-On Connectors, Pack of 20',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 380, 'image' => 'cable', 'stock' => 'bulk',
            'short' => 'Twist-on BNC ends for RG59, terminated with a stripper and nothing else.',
            'body' => 'No crimp tool and no soldering, which is what makes a repair on a ladder possible. They are not as durable as a compression fitting on an exposed outdoor run, so for a permanent external termination ask for the compression type at the counter.',
            'specs' => ['20 pieces, twist-on BNC male', 'For RG59 coaxial cable', 'Nickel-plated body, gold-plated pin', 'No crimp tool required', 'Indoor and sheltered use'],
            'dimensions' => [10.0, 7.0, 3.5, 0.14],
        ],
        [
            'sku' => 'PSU-12V10A', 'name' => '12V 10A Centralised CCTV Power Supply Box, 9 Channel',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 1950, 'image' => 'cable', 'stock' => 'stocked',
            'short' => 'One supply, nine fused outputs — instead of nine adaptors on nine sockets.',
            'body' => 'Individually fused outputs mean one shorted camera cable does not take the whole system down, and a lockable metal box means the power for the cameras is not sitting on a shelf where anyone can unplug it. Fit it beside the recorder, not in the ceiling.',
            'specs' => ['12V DC, 10A total, 9 fused outputs', 'Individual PTC fuse per channel', 'Lockable metal enclosure', 'Power and output status LEDs', 'AC 220V input, overload protected'],
            'dimensions' => [26.0, 20.0, 7.5, 1.65],
        ],
        [
            'sku' => 'PSU-POEINJ', 'name' => 'Gigabit PoE+ Injector, 30W',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 1150, 'image' => 'cable', 'stock' => 'stocked',
            'short' => 'Adds power to one network run, for the camera that landed outside the recorder\'s PoE ports.',
            'body' => 'Every job eventually has one camera more than the recorder has PoE ports, or one that has to reach a switch that does not do PoE. Rather than replacing either, put an injector on that single run. Thirty watts covers any camera in this catalogue, PTZ domes included.',
            'specs' => ['IEEE 802.3af/at, 30W output', 'Gigabit data pass-through', 'Auto-detects the powered device', 'Surge and short-circuit protection', 'AC 100-240V input'],
            'dimensions' => [14.5, 6.5, 3.2, 0.32],
        ],
        [
            'sku' => 'PSU-SURGE4', 'name' => '4-Port PoE Surge Protector for Outdoor Camera Runs',
            'brand' => null, 'category' => 'cabling-and-power', 'price' => 1650, 'image' => 'cable', 'stock' => 'stocked',
            'short' => 'The cheapest insurance on the system: it dies instead of the recorder.',
            'body' => 'A cable run to an outdoor camera on a pole is an aerial, and a nearby strike puts a surge straight down it into the PoE ports of the recorder. Fit this on the entry side of any external run. Replacing a surge protector costs a fraction of replacing a sixteen-channel NVR.',
            'specs' => ['4 gigabit ports, PoE pass-through', '6kV surge withstand per line', 'Response time under 1 nanosecond', 'Requires a proper earth connection', 'DIN rail or wall mount'],
            'dimensions' => [15.5, 9.0, 4.0, 0.42],
        ],
    ];

    /**
     * Bundles. A bundle is an ordinary product row with `is_bundle` set and a
     * list of the items it contains; a null `sku` on an item is a line with no
     * stock behind it, such as labour.
     *
     * @var list<array{sku: string, name: string, category: string, price: float, image: string, short: string, body: string, items: list<array{sku: ?string, label: string, quantity: int}>}>
     */
    private const BUNDLES = [
        [
            'sku' => 'BDL-SHOP4', 'name' => '4-Camera Shop Bundle', 'category' => 'bundles',
            'price' => 24950, 'image' => 'camera',
            'short' => 'Four 4MP cameras, a PoE recorder, a surveillance drive and a box of cable — a shop, covered.',
            'body' => 'The system we quote most often: four 4MP bullets on the entrance, the till, the stock room door and the back exit, recording to a four-channel PoE recorder with a 2TB drive. The cable is in the box because a job stalls without it. Saves around 1,800 pesos against buying the items separately.',
            'items' => [
                ['sku' => 'HIK-2CD1043G2', 'label' => 'Hikvision DS-2CD1043G2-LIU 4MP Bullet Camera', 'quantity' => 4],
                ['sku' => 'HIK-7104NIQ14P', 'label' => 'Hikvision DS-7104NI-Q1/4P 4-Channel PoE NVR', 'quantity' => 1],
                ['sku' => 'SEA-SKYHAWK2TB', 'label' => 'Seagate SkyHawk 2TB Surveillance Hard Drive', 'quantity' => 1],
                ['sku' => 'CBL-CAT6UTP', 'label' => 'Cat6 UTP Solid Copper Cable, 305m Box', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'BDL-BUSINESS8', 'name' => '8-Camera Business Bundle', 'category' => 'bundles',
            'price' => 52500, 'image' => 'recorder',
            'short' => 'Eight cameras, an eight-channel recorder, a 4TB drive and installation, delivered and commissioned.',
            'body' => 'Sized for a warehouse, a restaurant or a two-storey office. Eight 4MP cameras onto a WizSense recorder, so the footage can be searched for people and vehicles rather than for motion, with a 4TB drive giving roughly a month of continuous recording. Site survey, installation and commissioning are included.',
            'items' => [
                ['sku' => 'DAH-HFW1430S1', 'label' => 'Dahua IPC-HFW1430S1-A 4MP Bullet Camera', 'quantity' => 8],
                ['sku' => 'DAH-NVR4108HS8P', 'label' => 'Dahua NVR4108HS-8P 8-Channel PoE NVR', 'quantity' => 1],
                ['sku' => 'SEA-SKYHAWK4TB', 'label' => 'Seagate SkyHawk 4TB Surveillance Hard Drive', 'quantity' => 1],
                ['sku' => 'CBL-CAT6UTP', 'label' => 'Cat6 UTP Solid Copper Cable, 305m Box', 'quantity' => 2],
                ['sku' => null, 'label' => 'Site survey, installation and commissioning', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'BDL-HOMEWIFI', 'name' => 'Home Wi-Fi Starter Bundle', 'category' => 'bundles',
            'price' => 6450, 'image' => 'dome',
            'short' => 'Two indoor cameras and one outdoor, on Wi-Fi — no recorder, no cabling, set up at the branch.',
            'body' => 'For a house where nobody is going to pull cable through a ceiling. Two indoor pan-and-tilt cameras for the living area and the stairs, one weatherproof camera for the gate, all recording to cards and viewable from one app. We pair them to your phone at the counter before you leave.',
            'items' => [
                ['sku' => 'EZV-C6N', 'label' => 'Ezviz C6N 1080p Indoor Pan and Tilt Wi-Fi Camera', 'quantity' => 2],
                ['sku' => 'EZV-C3WN', 'label' => 'Ezviz C3WN 1080p Outdoor Wi-Fi Camera', 'quantity' => 1],
                ['sku' => null, 'label' => 'Setup and app pairing at your branch', 'quantity' => 1],
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
                    'requires_delivery' => $row['sku'] === 'BDL-BUSINESS8',
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
