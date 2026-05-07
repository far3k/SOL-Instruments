-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 07, 2026 at 08:36 AM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `login`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(3, 'Guitar', '', '2026-05-05 14:11:11', '2026-05-06 17:39:07'),
(4, 'Piano', '', '2026-05-05 18:14:13', '2026-05-05 18:14:13'),
(5, 'Violinset', '', '2026-05-05 18:28:48', '2026-05-05 18:28:48'),
(6, 'Acoustic Drums', '', '2026-05-05 18:34:19', '2026-05-05 18:34:19'),
(10, 'Saxophones', '', '2026-05-05 18:41:44', '2026-05-05 18:41:44'),
(11, 'Electric Guitars', '', '2026-05-05 18:46:11', '2026-05-05 18:46:11'),
(12, 'DJ Equipment', '', '2026-05-05 18:49:18', '2026-05-05 18:49:18'),
(13, 'Classical Guitars', '', '2026-05-05 18:55:00', '2026-05-05 18:55:00'),
(14, 'Handpan', '', '2026-05-05 19:51:26', '2026-05-05 19:51:26'),
(15, 'Trumpets', '', '2026-05-05 19:55:25', '2026-05-05 19:55:25'),
(22, 'Accordion', '', '2026-05-06 17:40:14', '2026-05-06 17:40:14'),
(23, 'Clarinet', '', '2026-05-06 17:41:22', '2026-05-06 17:41:22'),
(24, 'Flute', '', '2026-05-06 17:41:48', '2026-05-06 17:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` varchar(1000) NOT NULL,
  `answer` varchar(2000) NOT NULL,
  `category` varchar(32) NOT NULL DEFAULT 'Rental',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_faq_category` (`category`),
  KEY `idx_faq_sort` (`category`,`sort_order`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`, `category`, `sort_order`, `created_at`) VALUES
(1, 'How do I rent an instrument?', 'Sign in, open the rent catalog, pick an instrument and your start and end dates, then submit a rental request. Staff review each request; you can track status under My rentals.', 'Rental', 1, '2026-05-04 10:46:16'),
(2, 'What happens after I submit a rental request?', 'Your request appears as pending until staff approve or decline it. If approved, follow pickup or delivery instructions from the shop.', 'Rental', 2, '2026-05-04 10:46:16'),
(3, 'Can I change dates or cancel a rental?', 'Contact the shop via Contact us. Changes depend on availability and the shop policy; early cancellation may still incur fees if stated in your agreement.', 'Rental', 3, '2026-05-04 10:46:16'),
(4, 'Are deposits or ID required?', 'Many music shops require a refundable deposit and valid ID. Exact amounts and documents are confirmed when your rental is approved.', 'Rental', 4, '2026-05-04 10:46:16'),
(5, 'What if the instrument is damaged?', 'Use the gear carefully and return it in good condition. Report any damage to the shop right away; repair or replacement costs may apply per the rental terms.', 'Rental', 5, '2026-05-04 10:46:16'),
(6, 'How do I buy from the online store?', 'Browse products while signed in, add items to your cart, and complete checkout. Payment options shown at checkout depend on how the store is configured.', 'Store', 1, '2026-05-04 10:46:16'),
(7, 'Do you ship orders or offer in-store pickup?', 'Shipping versus pickup depends on the shop. If checkout does not list your preference, use Contact us before placing a large or time-sensitive order.', 'Store', 2, '2026-05-04 10:46:16'),
(8, 'What is your return policy for store purchases?', 'Return windows and restocking rules vary by item. Keep your receipt or order confirmation and contact the shop with your order number for a return or exchange.', 'Store', 3, '2026-05-04 10:46:16'),
(9, 'How do I create an account?', 'Choose Register on the home page, fill in the required fields, then sign in with your email and password.', 'Account', 1, '2026-05-04 10:46:16'),
(10, 'I cannot sign in. What should I check?', 'Verify email and password, turn off caps lock, and try again. If your account is blocked or you still fail, use Contact us for help.', 'Account', 2, '2026-05-04 10:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `home_slides`
--

DROP TABLE IF EXISTS `home_slides`;
CREATE TABLE IF NOT EXISTS `home_slides` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `audience` enum('all','guest','user') NOT NULL DEFAULT 'all',
  `background_image` varchar(255) DEFAULT NULL,
  `overlay_pct` tinyint(3) UNSIGNED NOT NULL DEFAULT 45,
  `heading` varchar(255) NOT NULL DEFAULT '',
  `subheading` text DEFAULT NULL,
  `button1_label` varchar(120) DEFAULT NULL,
  `button1_url` varchar(500) DEFAULT NULL,
  `button2_label` varchar(120) DEFAULT NULL,
  `button2_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_home_slides_order` (`is_active`,`sort_order`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `home_slides`
--

INSERT INTO `home_slides` (`id`, `sort_order`, `is_active`, `audience`, `background_image`, `overlay_pct`, `heading`, `subheading`, `button1_label`, `button1_url`, `button2_label`, `button2_url`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'all', 'home_slides/slide_6b26542d1496c7e5.jpg', 48, 'Rent instruments with confidence', 'Daily rates on guitars, pianos, and more. Pick your dates and submit a rental request in minutes.', 'Browse rentals', 'rent/rentcatalog.php', 'Shop accessories', 'shop/catalog.php', '2026-05-05 01:31:05', '2026-05-05 01:44:11'),
(2, 2, 1, 'all', 'home_slides/slide_0c95c93545ea6568.jpg', 52, 'Your stage starts here', 'Quality gear for practice and performance curated for students and working musicians.', 'Go to shop', 'shop/catalog.php', 'Rent catalog', 'rent/rentcatalog.php', '2026-05-05 01:31:05', '2026-05-06 17:00:32'),
(3, 3, 1, 'all', 'home_slides/slide_8849d2390b4137e8.jpg', 45, 'Accessories & essentials', 'Stands, cables, metronomes, and care supplies everything around your instrument.', 'Full shop', 'shop/catalog.php', 'Contact us', 'contact.php', '2026-05-05 01:31:05', '2026-05-06 17:00:43'),
(4, 4, 1, 'all', 'home_slides/slide_2e533fb6dfd565f0.jpg', 48, 'Find the Perfect Instrument to Play, Learn, or Teach', 'Browse high-quality instruments available for flexible rental periods. Affordable, fast, and easy booking in just a few clicks.', 'Browse rentals', 'rent/rentcatalog.php', 'Shop accessories', 'shop/catalog.php', '2026-05-04 23:31:05', '2026-05-05 13:48:38'),
(5, 5, 1, 'all', 'home_slides/slide_52b63654ff3f4c18.jpg', 52, 'Start Your Musical Journey Without Buying', 'Try different instruments before you commit. Rent affordably and discover what fits your learning style best.', 'Go to shop', 'shop/catalog.php', 'Rent catalog', 'rent/rentcatalog.php', '2026-05-04 23:31:05', '2026-05-05 13:44:04'),
(6, 6, 1, 'all', 'home_slides/slide_7fca6deb6715dd82.jpg', 45, 'Reliable Instruments for Every Lesson and Performance', 'Get fast access to well-maintained instruments for your students or sessions with simple booking and guaranteed availability.', 'Rent an Instrument', 'rent/rentcatalog.php', 'Contact us', 'contact.php', '2026-05-04 23:31:05', '2026-05-05 13:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `instruments`
--

DROP TABLE IF EXISTS `instruments`;
CREATE TABLE IF NOT EXISTS `instruments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `daily_price` decimal(10,2) NOT NULL,
  `condition` enum('excellent','good','fair','needs_service') NOT NULL DEFAULT 'good',
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_instruments_category` (`category_id`),
  KEY `idx_instruments_name` (`name`),
  KEY `idx_instruments_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instruments`
--

INSERT INTO `instruments` (`id`, `category_id`, `name`, `daily_price`, `condition`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(9, 3, 'Gitar', 20.00, 'good', '', 'img_69f9fa9dd4d121.56358518.webp', 1, '2026-05-05 14:11:41', '2026-05-05 18:07:50'),
(10, 4, 'Yamaha P-525 B', 28.98, 'excellent', 'Digital Piano\r\n88 weighted keys with hammer action (GrandTouch-S)\r\nWooden keys with escapement and synthetic ivory top layers\r\n542 sounds (incl. Yamaha CFX Piano, Bösendorfer Imperial)\r\nDual/layer\r\nSplit\r\nDuo\r\n40 styles\r\nStorable registration memory (6 x 4 banks)\r\n21 voice demo songs + 50 classics\r\n256-voice polyphonic\r\nLCD display 198 x 100 dots\r\nMIDI recording: 250 songs (16 tracks each), format 0\r\nAudio recording (USB memory): WAV (44.1 kHz, 16-bit, stereo), up to 80 minutes/song\r\nPlayback: SMF (Standard MIDI File, format 0 and 1), WAV (44.1 kHz, 16-bit, stereo)\r\nEffects: Reverb (7), Chorus (3), Master EQ (3 Preset + 1 User), 12 Insert Effects, Intelligent Acoustic Control (IAC), Stereophonic Optimizer, Sound Boost (3)\r\nBluetooth Audio & MIDI\r\nConnectors: Stereo output (2x 6.3 mm jack L/L+R, R), headphone outputs (2x 6.3 mm stereo jack), MIDI In/Out, Aux In (3.5 mm mini jack stereo), USB to Device (USB A), USB to Host (USB C), power supply connection, sustain pedal, pedal unit (LP-1 or FC-35, sold separately)\r\nSpeaker: 2x 20W + 6W\r\nIncludes sheet music holder, foot pedal (FC-3A), mains adapter (PA-300C), operating instructions and online member registration\r\nDimensions (W × H × D): 1336 × 145 × 376 mm\r\nWeight: 22 kg', 'img_69fa339aa6bb09.61504019.jpg', 1, '2026-05-05 18:14:50', '2026-05-05 18:14:50'),
(11, 5, 'Thomann Student Violinset', 13.00, 'excellent', 'Violin Set\r\nSize: 1/16\r\nTop: Solid spruce\r\nBottom, sides and neck: Maple\r\nFingerboard made of blackwood (Pinus radiata)\r\nTuning pegs made of jujube (Ziziphus jujuba)\r\n4 Fine tuners\r\nIncludes fibreglass bow, case and rosin\r\nMade ready to play in Germany in the Thomann specialist string workshop', 'img_69fa374e73ace4.24715923.jpg', 1, '2026-05-05 18:30:38', '2026-05-05 18:30:38'),
(12, 6, 'Millenium MX420 Studio Set', 20.00, 'excellent', 'Complete Drum Kit\r\nStudio configuration\r\n9-Ply poplar / birchwood shell\r\nShells with wrap finish\r\n1.5 mm metal hoops on snare and toms\r\nWooden hoops on bass drum in shell colour\r\nColour: Blue Lining\r\nShell set consists of:\r\n\r\n20\" x 16\" Bass drum (bored)\r\n10\" x 08\" Tom Tom\r\n12\" x 09\" Tom Tom\r\n14\" x 14\" Floor tom\r\n14\" x 5.5\" Snare drum\r\nHardware set consists of:\r\n\r\nCymbal boom stand\r\nStraight cymbal stand\r\nHi-Hat stand\r\nSnare drum stand\r\nSingle pedal\r\nDrum throne\r\nCymbal set consists of:\r\n\r\n14\" Hi-hat\r\n16\" Crash\r\n20\" Ride', 'img_69fa395d966974.23918061.jpg', 1, '2026-05-05 18:39:25', '2026-05-06 15:43:24'),
(13, 10, 'Yamaha  Alto Sax', 22.98, 'excellent', 'Alto Saxophone\r\nNew model with improved S-bow receiver and improved deep H/Cis connection\r\nNarrow scale\r\nBody and action made of brass\r\nFull length keyguard\r\nAdjustable thumb rest\r\nHigh F# key\r\nF-key\r\nWeight: 2.40 kg\r\nMechanics and body gold lacquered\r\nIncl. light case with backpack straps, Yamaha 4C mouthpiece, saxophone strap and cork grease', 'img_69fa3a2aa02b02.81416525.jpg', 1, '2026-05-05 18:42:50', '2026-05-06 15:41:38'),
(14, 11, 'Squier Aff. Strat HSS MN PACK', 23.99, 'excellent', 'Electric Guitar Set SQ Affinity Strat Electric Guitar\r\nBody: Poplar\r\nBolt-on neck: Maple\r\nFretboard: Maple\r\nBlack dot fretboard inlays\r\nNeck profile: C\r\nFretboard radius: 241 mm (9.49\")\r\nScale length: 648 mm (25.51\")\r\nNut width: 42 mm (1.65\")\r\nSynthetic bone nut\r\n21 Medium jumbo frets\r\nPickups: ceramic humbucker (bridge) and 2 ceramic single coils (neck and middle)\r\nMaster volume control\r\n2 Tone controls\r\n5-Way switch\r\n2-Point synchronized tremolo with block saddles\r\n3-Ply white pickguard\r\nChrome hardware\r\nClosed DieCast tuners with split shafts\r\nColour: Lake Placid Blue\r\nStrings: NPS .009 - .042\r\nColour: Charcoal Frost Metallic\r\nAmp Frontman 15G guitar combo\r\n\r\nPower: 15 W\r\n8\" Speaker\r\nChannel switching\r\nVolume\r\nGain\r\nDrive\r\n3-Vand EQ\r\nHeadphone output\r\nOther accessories:\r\n\r\nCable\r\nCarrying strap\r\nPicks\r\nGig bag\r\nFender Play Trial (free online school for 90 days, Fender Play is only available in selected countries, current overview available at Fender)', 'img_69fa3b2e0e7f53.24306335.jpg', 1, '2026-05-05 18:47:10', '2026-05-06 15:45:15'),
(15, 12, 'Denon DJ SC Live 4', 19.99, 'excellent', 'DJ Console\r\nWorld\'s first DJ integration with the streaming service Amazon Music Unlimited\r\nUnlocks Serato DJ Pro for free when connected to the software\r\nSupported streaming services: Apple Music, Amazon Music, Unlimited TIDAL, Beatsource, Beatport Soundcloud GO+, Dropbox\r\nCompatible with Serato DJ, Virtual DJ and Engine DJ\r\n7\" multi-touch screen and WIFI music streaming\r\nBuilt-in sweep and BPM effects\r\nBuilt-in speakers with volume control\r\nSample rate: 44.1 kHz\r\nD/A converter: 24 bit\r\n6\" high resolution jog wheels\r\nEight performance pads\r\n3-band EQ per channel\r\nPitch bend buttons and dedicated pitch slider with variable ranges\r\nOptional upgrade for STEMS support (drums, bass, melody, vocals) possible\r\nAutomatic synchronisation and control of DMX, Philips Hue and Nanoleaf lights\r\nTwo USB-A inputs\r\nUSB-B input\r\nSD-card input\r\n6.3 mm mono jack microphone input\r\nXLR microphone input\r\nTwo RCA aux-in\r\nTwo XLR male main out\r\nTwo RCA main out\r\nTwo 6.3 mm jack / 3.5 mm mini jack headphone outputs\r\nDimensions (W x H x D): 719 x 99 x 404 mm\r\nWeight: 5.8 kg\r\nIncludes power supply, USB cable, display cleaning cloth', 'img_69fa3c308ff375.03821495.jpg', 1, '2026-05-05 18:51:28', '2026-05-05 18:51:28'),
(16, 13, 'Yamaha GL1 Tobacco ', 23.99, 'excellent', 'Guitalele\r\nWith 6 strings\r\nTuning: A/D/G/C/E/A\r\nTop: Spruce\r\nBody: Meranti\r\nFingerboard: Sonokeling\r\nNut width: 47.5 mm\r\nDimensions: 10 x 29 x 7 cm\r\nColour: Tobacco Brown Sunburst\r\nIncludes gig bag', 'img_69fa3d3c939311.80637174.jpg', 1, '2026-05-05 18:55:56', '2026-05-06 15:44:58'),
(17, 14, 'Sela Harmony Handpan D Kurd', 25.00, 'excellent', 'Handpan\r\nTuning: D Kurd (440 Hz)\r\nTones: D3/A3, Bb3, C4, D4, E4, F4, G4, A4\r\nMaterial: Stainless steel\r\nProfessionally tuned\r\nHandmade\r\nColour: Gold\r\nIncludes a padded bag with backpack straps', 'img_69fa4a90e240b6.61217522.jpg', 1, '2026-05-05 19:52:48', '2026-05-06 15:44:17'),
(18, 15, 'Yamaha YTR-3335 Bb- Trumpet', 26.00, 'excellent', 'Bb Trumpet\r\nBrass body\r\nML-Bore: 11.65 mm\r\nBell: Ø 123 mm\r\n\"Reversed Type\" Lead Pipe\r\nRest on the main tuning slide\r\nSlightly modified bell with optimal material thickness\r\nMonel valves\r\nWater key on 3rd valve slide and on main tuning slide\r\nNew valve cover and finger buttons\r\nAdjustable finger hook on 3rd slide\r\nSaddle on the first valve slide\r\nWeight. 1.09 kg\r\nFinish: Gold lacquered\r\nIncl. mouthpiece Yamaha TR11B4 and TRC-201EII case with backpack straps', 'img_69fa4b5f9562f0.01185724.jpg', 1, '2026-05-05 19:56:15', '2026-05-05 19:56:15'),
(19, 5, 'Stentor Student Violin II (4/4)', 6.00, 'excellent', 'Size: Full-size 4/4 violin\r\nIncludes: Bow, rosin, hard case\r\nTop: Solid spruce\r\nFingerboard: Ebonized hardwood\r\nSetup: Pre-adjusted bridge and strings', 'img_69fa06a5ab8595.08035652.jpg', 1, '2026-05-05 13:03:01', '2026-05-06 17:02:41'),
(20, 3, 'Yamaha C40 Classical Guitar', 5.00, 'good', 'Top: Spruce\r\nBack and sides: Meranti\r\nNeck: Nato wood\r\nStrings: Nylon\r\nFinish: Natural gloss', 'img_69fa06de41f0c9.01798705.jpg', 1, '2026-05-05 13:03:58', '2026-05-06 17:02:52'),
(21, 4, 'Yamaha P-45 Digital Piano', 9.99, 'excellent', 'Keys: 88 weighted hammer-action keys\r\nPolyphony: 64 notes\r\nVoices: 10 instrument sounds\r\nIncludes: Sustain pedal, power supply\r\nPortability: Lightweight design', 'img_69fa071dd284e2.99438598.jpg', 1, '2026-05-05 13:05:01', '2026-05-06 17:03:01'),
(22, 10, 'Yamaha  Alto Saxophone', 14.00, 'excellent', 'Body: Yellow brass\r\nFinish: Gold lacquer\r\nKey: High F# key included\r\nIncludes: Case, mouthpiece, neck strap\r\nSound: Balanced student-friendly tone', 'img_69fa074f655504.20640247.jpg', 1, '2026-05-05 13:05:51', '2026-05-06 17:03:12'),
(24, 24, 'Yamaha YFL-222 Flute', 6.00, 'excellent', 'Material: Silver-plated nickel silver\r\nKey system: Offset G\r\nIncludes: Cleaning rod, case\r\nSound: Clear and stable tone', 'img_69fa07b62dc716.09309764.jpg', 1, '2026-05-05 13:07:34', '2026-05-06 17:41:57'),
(25, 23, 'Yamaha YCL-255 Clarinet', 7.00, 'fair', 'Body: ABS resin\r\nKey system: Boehm system\r\nIncludes: Case, mouthpiece, reed\r\nSound: Warm, stable intonation', 'img_69fa07fab21346.54667667.jpg', 1, '2026-05-05 13:08:42', '2026-05-06 17:41:35'),
(26, 15, 'Bach TR300H2 Trumpet', 8.00, 'excellent', 'Material: Yellow brass\r\nFinish: Lacquered\r\nIncludes: Case, mouthpiece\r\nSound: Bright, classical tone', 'img_69fa0829d78365.87758803.jpg', 1, '2026-05-05 13:09:29', '2026-05-06 17:40:42'),
(27, 3, 'Kala KA-15S Ukulele', 3.95, 'good', 'Size: Soprano ukulele\r\nBody: Mahogany\r\nStrings: Nylon\r\nIncludes: Standard tuning setup\r\nSound: Bright and warm tone', 'img_69fa0873870dd7.69087626.jpg', 1, '2026-05-05 13:10:43', '2026-05-06 17:39:42'),
(28, 22, 'Hohner Bravo III 72 Accordion', 18.00, 'excellent', 'Keys: 34 treble keys\r\nBass buttons: 72\r\nIncludes: Shoulder straps, case\r\nSound: Rich folk and classical tone', 'img_69fa08a384c977.21405167.jpg', 1, '2026-05-05 13:11:31', '2026-05-06 17:40:23'),
(29, 10, 'Yamaha  Tenor Saxophone', 15.99, 'excellent', 'Body: Yellow brass\r\nFinish: Gold lacquer\r\nIncludes: Case, mouthpiece, strap\r\nSound: Deep and warm tone', 'img_69fa08da919b66.93734301.jpg', 1, '2026-05-05 13:12:26', '2026-05-06 17:04:15'),
(30, 5, 'Yamaha Violin V5 SC 1/4', 9.99, 'excellent', 'Size: 1/4 violin\r\nTop: Solid spruce\r\nIncludes: Case, bow\r\nDesigned for: Children and beginners', 'img_69fa090e6d06f1.97677403.jpg', 1, '2026-05-05 13:13:18', '2026-05-06 17:04:46'),
(31, 4, 'Yamaha U1 Upright Piano', 35.00, 'excellent', 'Model: Yamaha U1\r\nType: Upright acoustic piano\r\nSize: Approx. 121 cm height\r\nKeys: 88 full-size weighted wooden keys\r\nPedals: 3 (soft, practice/mute, sustain)\r\nAction: Yamaha precision upright action mechanism\r\nSoundboard: Solid spruce\r\nFinish: Polished black (standard concert style)', 'img_69fa0969a765a0.11800177.jpg', 1, '2026-05-05 13:14:49', '2026-05-06 17:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `delivery_mode` varchar(16) DEFAULT NULL COMMENT 'pickup|delivery',
  `payment_method` varchar(16) NOT NULL DEFAULT 'store' COMMENT 'store|iban',
  `shipping_address` text DEFAULT NULL,
  `cart_snapshot` longtext DEFAULT NULL COMMENT 'JSON lines + currency',
  `order_subtotal` decimal(10,2) DEFAULT NULL,
  `order_shipping` decimal(10,2) DEFAULT NULL,
  `order_total` decimal(10,2) DEFAULT NULL,
  `placed_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `delivery_mode`, `payment_method`, `shipping_address`, `cart_snapshot`, `order_subtotal`, `order_shipping`, `order_total`, `placed_at`, `status`, `admin_note`) VALUES
(7, 2, 'pickup', 'store', '', '{\"currency\":\"EUR\",\"lines\":[{\"product_id\":14,\"name\":\"Sennheiser E 945\",\"qty\":1,\"unit_price\":166,\"line_total\":166,\"picture\":\"img_69f9f5ec8ec966.09096508.jpg\"}]}', 166.00, 0.00, 166.00, '2026-05-05 17:57:35', 'paid', NULL),
(8, 2, 'pickup', 'store', '', '{\"currency\":\"EUR\",\"lines\":[{\"product_id\":42,\"name\":\"Thomann Violin Rosin Medium\",\"qty\":2,\"unit_price\":1.99,\"line_total\":3.98,\"picture\":\"img_69fa0350c85f60.85338376.jpg\"}],\"payment_method\":\"iban\"}', 3.98, 0.00, 3.98, '2026-05-06 20:59:38', 'pending', NULL),
(9, 2, 'pickup', 'store', '', '{\"currency\":\"EUR\",\"lines\":[{\"product_id\":35,\"name\":\"Acoustic Guitar Strings 10-46\",\"qty\":1,\"unit_price\":1.99,\"line_total\":1.99,\"picture\":\"img_69fa00aba1bdb1.97371387.jpg\"}],\"payment_method\":\"iban\"}', 1.99, 0.00, 1.99, '2026-05-06 21:06:30', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `picture` varchar(255) DEFAULT 'product.jpg',
  `description` text DEFAULT NULL,
  `fk_supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_products_supplier` (`fk_supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `picture`, `description`, `fk_supplier_id`) VALUES
(14, 'Sennheiser E 945', 166.00, 'img_69f9f5ec8ec966.09096508.jpg', 'Dynamic vocal microphone\r\nPolar pattern: Supercardioid\r\nFrequency range: 40 - 18,000 Hz\r\nImpedance: 350 Ohm\r\nSensitivity: 2.0 mV/Pa @ 1KHz\r\nDimensions: 47 x 186 mm\r\nWeight: 365 g\r\nIncludes microphone bag and microphone clamp MZQ 800', 4),
(15, 'Dunlop Stubby Jazz Small 3.00', 23.00, 'img_69fa32b8228e09.36945921.jpg', 'Guitar Pick / Plectrum Set\r\nStubby Jazz small\r\nGauge: 3.00 mm\r\nColour code: Light purple\r\nSet of 24 units', 4),
(19, 'Orchestra Stand', 16.50, 'img_69fa42c45c5b82.53806478.jpg', 'Orchestra Stand\r\n1-Fold extensible\r\nPerforated sheet music holder\r\nTotal height: 135 cm\r\nDimensions of sheet music holder 47.5 x 34.5 x 5 cm\r\nHeight of stand: 62 - 110 cm\r\nFolded stand diameter: 10.5 cm\r\nFolded length of stand: 62 cm\r\nMaterial of stand: Steel\r\nMaterial sheet music holder: Steel\r\nWeight: 2.5 kg\r\nColour: Black\r\nMatching bag: art. 373306 (not included)', 4),
(20, 'Art of Music Pin Trumpet', 17.60, 'img_69fa43a3ae43e5.63076119.jpg', 'Noble pin\r\nMotive: Trumpet\r\nLength: 24 mm\r\nWidth: 5 mm\r\nColour: Gold-plated/Rhodium (silver-plated)', 4),
(21, 'Art of Music Pin Violin Small', 14.60, 'img_69fa440ed0e585.71397899.jpg', 'Classy Pin\r\nDesign: Violin small\r\nColour: Gold plated/rhodium plated (silver plated)', 4),
(22, 'Art of Music Pin Akkordeon', 14.60, 'img_69fa44b0983d60.14201596.jpg', 'Classic broach\r\nTheme: Accordion\r\nLength: 15 mm\r\nWidth: 11 mm\r\nColour: Golden, partly rhodium-plated (silvered)', 4),
(23, 'Zultan Cap Ivory', 12.30, 'img_69fa452ab26781.13624236.jpg', 'Baseball Cap\r\nEngraved buckle\r\nSpan: 58.0 cm\r\nAdjustable size\r\nMaterial: Cotton\r\nColour: Ivory', 4),
(24, 'Thomann KB-15RM', 63.00, 'img_69fa460116c0d7.38537139.jpg', 'Piano Bench\r\nSuitable for children and adults\r\nMaterial: Birch wood\r\nHeight adjustable from 48 - 56 cm\r\nSeat: 52 x 29 cm\r\nSeat cushion: Black velour\r\nWeight: 7.8 kg\r\nFrame colour: Rosewood matt\r\nNote: Additional seat cushions are separately available.', 4),
(25, 'E-Guitar Case ABS', 68.00, 'img_69fa476979f5e0.16113816.jpg', 'Electric Guitar Case\r\nFits single cut guitar models and many more\r\nMade of ABS plastic\r\nThick, soft inner lining made of black plush\r\nInner accessory pocket\r\nPadded neck support\r\nFive snap fasteners (1 x lockable)\r\nErgonomically designed handle\r\nInner dimensions (L x W x H): 100 x 33 x 9 cm\r\nWeight: 3.2 kg', 4),
(26, 'LightCase-Classic', 55.00, 'img_69fa48044fe754.53942404.jpg', 'Case for Classical Guitar\r\nLight case in gigbag style\r\nFor standard classical guitar models\r\nHigh-density foam construction that offers both the solid protection of a hard case and the lightweight, transport-friendly backpack characteristics of a gig bag\r\nDurable, weatherproof nylon exterior with removable padded shoulder strap and ergonomic carry handle\r\nVelvet interior with neck holder and 2 accessory compartments\r\nInterior dimensions:\r\n\r\nOverall length: 1027 mm (40.43\")\r\nLower body width: 385 mm (15.16\")\r\nMiddle body width: 270 mm (10.63\")\r\nUpper body width: 305 mm (12\")\r\nBody length: 500 mm (19.68\")\r\nBody height: 110 mm (4.33\")', 4),
(27, 'Millenium GS-2001 A', 9.70, 'img_69fa486bc5f235.94425459.jpg', 'Robust Acoustic Guitar Stand\r\nCompact size and very stable\r\nRubber tubing protects the instrument\r\nColour: Black\r\nNot suitable for long-term use for guitars with nitrocellulose lacquer finish!', 4),
(28, 'Millenium KS-1001', 21.60, 'img_69fa495b077e92.35049069.jpg', 'Keyboard Stand\r\nWith quick lock (X stand)\r\nAdjustable rubber supports\r\nSupport depth: 40 cm\r\nHeight adjustable from 50 cm (width: 87 cm) to 92 cm (width: 46 cm)\r\nDimensions when collapsed: 101 x 50 x 8 cm\r\nMax. load-bearing capacity: 20 kg\r\nWeight: 2.5 kg\r\nColour: Black\r\nSuitable bags: Article Nr. 501567 and Article Nr. 501567 (neither included)', 4),
(29, 'agifty Writing-Set Mini', 5.80, 'img_69fa4ca44f6992.67710096.jpg', 'Stationery Set\r\nNotebook in DIN A7 format with clef and keyboard\r\nEraser in clef design\r\nPencil', 4),
(30, 'Rohema Groove Cubes', 19.50, 'img_69fa4d5a69c242.73256926.jpg', 'Practice help for drummers\r\nWith the Rohema Groove Cubes you bring more variety into your drum practice routine. They randomly decide what you drum. The set contains 9 cubes made of beech wood. Different note patterns are printed on them so that you can combine rhythms. Two cubes together form the bass drum / snare drum combination. The hi-hat figure is combined with another cube. The fill cubes are four cubes that together form a fill combination that you might not have thought of. An additional tempo cube offers 6 different playing speeds in BPM (beats per minute). A little gimmick that allows you to set either the actual speed or a tempo as a \"practice target\"The Rohema Groove Cubes open up completely new possibilities for drummers, especially drum teachers and their students, in their daily practice routine.How often do you find yourself practising the same rhythms, beats or fills over and over again? With the rhythm cubes, you can let chance decide and have a new challenge every day that you can implement on the drum set.\r\n\r\nDrum students can roll their own exercises until the next lesson. In the process, they improve and sharpen their note-reading, sticking and orchestration skills\r\nThe Groove Cubes have a side length of 30 millimetres\r\nThe small box with all 9 cubes fits easily into any pocket and you always have the cubes with you\r\nIncl. Compact transport box\r\nMaterial: Beech wood', 4),
(31, '6.3mm Instrument Cable', 11.09, 'img_69fa047805a5a5.86055950.jpg', 'Reliable shielded cable for clean guitar or keyboard signal.\r\n\r\nLength: 3 m (varies)\r\nColor: Black\r\nConnector: 6.3 mm jack\r\nMaterial: Copper core + PVC\r\nShielding: Yes\r\nCompatibility: Guitar, bass, keyboard\r\nPrice: ~8–12 €', NULL),
(32, 'FT-1 Pro Clip Tuner', 10.00, 'img_69fa00022f51f1.03673360.png', 'Simple beginner-friendly clip tuner with clear display and fast tuning.\r\nSize: ~5 cm\r\nWeight: ~25 g\r\nColor: Black\r\nMaterial: Plastic + rubber clip\r\nDisplay: LCD\r\nPower: Button battery\r\nCompatibility: Guitar, bass, violin, ukulele', NULL),
(33, 'Generic Guitar Capo', 5.13, 'img_69fa00270148b1.82492541.jpg', 'Basic spring capo for quick key changes on acoustic or electric guitar.\r\n\r\nSize: ~8 cm\r\nWeight: ~50 g\r\nColor: Black / silver\r\nMaterial: Metal + silicone padding\r\nType: Spring clamp\r\nCompatibility: Acoustic & electric guitar', NULL),
(34, 'Guitar Pick Pack', 1.49, 'img_69fa04d52284c0.70535778.jpg', 'Affordable multi-pack picks for everyday playing.\r\n\r\nSize: ~2–3 cm\r\nThickness: Medium (~0.7–0.8 mm)\r\nColor: Mixed\r\nMaterial: Plastic\r\nQuantity: Pack (5+)', NULL),
(35, 'Acoustic Guitar Strings 10-46', 1.99, 'img_69fa00aba1bdb1.97371387.jpg', 'Budget-friendly string set for acoustic guitar with balanced tone.\r\n\r\nType: Acoustic steel strings\r\nGauge: Light (10–46)\r\nMaterial: Steel / bronze\r\nColor: Metallic bronze\r\nCompatibility: Acoustic guitar', NULL),
(36, 'Foldable Music Stand', 14.90, 'img_69fa00ea279cc1.03213084.jpg', 'Lightweight foldable stand for sheet music or tablets.\r\n\r\nHeight: 60–150 cm adjustable\r\nWeight: ~1–2 kg\r\nColor: Black\r\nMaterial: Steel\r\nFoldable: Yes', NULL),
(37, 'DM-51 Digital Metronome', 17.90, 'img_69fa014704c966.42420040.jpg', 'Compact digital metronome with precise tempo control.\r\n\r\nSize: ~6–9 cm\r\nColor: Black\r\nTempo Range: ~30–250 BPM\r\nPower: Battery\r\nCompatibility: All instruments', NULL),
(38, 'Guitar Pick Tray', 17.95, 'img_69fa01f6710d73.57359688.jpg', 'Wall-mounted holder for guitar storage and accessories.\r\n\r\nSize: ~10–15 cm\r\nColor: Black / wood\r\nMaterial: Metal + rubber padding\r\nFeatures: Space-saving wall mount\r\nCompatibility: Guitar, bass', NULL),
(39, 'Guitar Repair & Practice Tool', 33.49, 'img_69fa023c885330.08493694.jpg', 'Tool kit including hand strength and maintenance accessories.\r\n\r\nFinger Trainer · € 33,49 · 4,7\r\nBuilds finger strength and speed.\r\n\r\nSize: Handheld (~8–10 cm)\r\nColor: Black / colored buttons\r\nMaterial: Plastic + springs\r\nFeatures: Adjustable resistance\r\nCompatibility: All musicians', NULL),
(40, 'Blue Bb Clarinet Reeds', 10.39, 'img_69fa02a2c367f9.67386757.jpg', 'High-quality reeds with consistent response and warm tone.\r\n\r\nSize: Standard Bb clarinet\r\nStrength: 1.5 – 3.5 (varies)\r\nColor: Natural wood\r\nMaterial: Cane (bamboo-like)\r\nCompatibility: Clarinet', NULL),
(41, 'Evans RealFeel Practice Pad', 45.38, 'img_69fa02fc7863a4.14824495.jpg', 'Quiet practice pad with realistic rebound for drummers.\r\n\r\nSize: ~6–12 inch\r\nColor: Gray / black\r\nMaterial: Rubber + wood base\r\nFeatures: Realistic stick rebound\r\nCompatibility: Drummers (all levels)', NULL),
(42, 'Thomann Violin Rosin Medium', 1.99, 'img_69fa0350c85f60.85338376.jpg', 'Affordable rosin for better bow grip and sound on violin.\r\n\r\nSize: Small block (~3–5 cm)\r\nColor: Amber\r\nMaterial: Tree resin\r\nCompatibility: Violin, viola, cello', NULL),
(43, 'Trumpet Practice Mute', 47.00, 'img_69fa03a9ae6883.40113133.jpg', 'Mute for quiet trumpet practice without disturbing others.\r\n\r\nSize: Fits inside bell\r\nColor: Silver / black\r\nMaterial: Aluminum\r\nFeatures: Strong sound reduction\r\nCompatibility: Trumpet', NULL),
(44, 'Original Violin Shoulder Rest', 36.95, 'img_69fa03f5e6a9a8.14315354.jpg', 'Classic adjustable shoulder rest with ergonomic shape and secure grip for violin players.\r\n\r\nSize: Adjustable (1/2 – 4/4 violin sizes)\r\nColor: Black / wood-style variants\r\nMaterial: Thermoplastic frame + foam rubber padding\r\nCompatibility: Violin\r\nDetails: Adjustable height, width & angle for comfortable fit', NULL),
(45, 'Rubber Violin Practice Mute', 6.00, 'img_69fa043db36cc3.31503107.jpg', 'Rubber practice mute that significantly reduces volume for quiet violin playing.\r\n\r\nSize: Fits standard violin bridge (4/4 and smaller sizes available)\r\nColor: Black\r\nMaterial: Rubber\r\nCompatibility: Violin (also versions for viola/cello)\r\nDetails: Sits on the bridge and dampens vibrations to make the instrument much quieter', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rental_requests`
--

DROP TABLE IF EXISTS `rental_requests`;
CREATE TABLE IF NOT EXISTS `rental_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `purpose` varchar(500) NOT NULL,
  `payment_method` varchar(32) NOT NULL DEFAULT 'store' COMMENT 'store|iban',
  `delivery_method` varchar(32) NOT NULL DEFAULT 'pickup' COMMENT 'pickup|courier',
  `delivery_notes` text DEFAULT NULL COMMENT 'Courier / formatted delivery address',
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rental_user` (`user_id`),
  KEY `idx_rental_instrument` (`instrument_id`),
  KEY `idx_rental_status` (`status`),
  KEY `idx_rental_dates` (`start_date`,`end_date`),
  KEY `idx_rental_instrument_status_dates` (`instrument_id`,`status`,`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_requests`
--

INSERT INTO `rental_requests` (`id`, `user_id`, `instrument_id`, `start_date`, `end_date`, `purpose`, `payment_method`, `delivery_method`, `delivery_notes`, `status`, `admin_note`, `requested_at`, `decided_at`, `created_at`, `updated_at`) VALUES
(42, 2, 9, '2026-05-05', '2026-05-08', 'Cart checkout (confirm)', 'store', 'pickup', NULL, 'approved', NULL, '2026-05-05 17:55:21', '2026-05-05 18:03:27', '2026-05-05 17:55:21', '2026-05-05 18:03:27'),
(43, 2, 20, '2026-05-06', '2026-05-20', 'Cart checkout (confirm)', 'iban', 'pickup', NULL, 'pending', NULL, '2026-05-06 21:05:31', NULL, '2026-05-06 21:05:31', '2026-05-06 21:05:31'),
(44, 2, 19, '2026-05-06', '2026-05-20', 'Cart checkout (confirm)', 'iban', 'pickup', NULL, 'pending', NULL, '2026-05-06 21:05:31', NULL, '2026-05-06 21:05:31', '2026-05-06 21:05:31');

--
-- Triggers `rental_requests`
--
DROP TRIGGER IF EXISTS `trg_rental_requests_bi`;
DELIMITER $$
CREATE TRIGGER `trg_rental_requests_bi` BEFORE INSERT ON `rental_requests` FOR EACH ROW BEGIN
  IF NEW.end_date <= NEW.start_date THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'end_date must be greater than start_date';
  END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_rental_requests_bu`;
DELIMITER $$
CREATE TRIGGER `trg_rental_requests_bu` BEFORE UPDATE ON `rental_requests` FOR EACH ROW BEGIN
  IF NEW.end_date <= NEW.start_date THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'end_date must be greater than start_date';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `rental_status_logs`
--

DROP TABLE IF EXISTS `rental_status_logs`;
CREATE TABLE IF NOT EXISTS `rental_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_request_id` int(11) NOT NULL,
  `old_status` enum('pending','approved','rejected','completed','cancelled') DEFAULT NULL,
  `new_status` enum('pending','approved','rejected','completed','cancelled') NOT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `changed_by_user_id` int(11) NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_statuslog_rental` (`rental_request_id`),
  KEY `idx_statuslog_changed_by` (`changed_by_user_id`),
  KEY `idx_statuslog_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_status_logs`
--

INSERT INTO `rental_status_logs` (`id`, `rental_request_id`, `old_status`, `new_status`, `change_reason`, `changed_by_user_id`, `changed_at`) VALUES
(33, 42, NULL, 'pending', 'checkout confirm', 2, '2026-05-05 17:55:21'),
(34, 42, 'pending', 'approved', 'admin approved', 1, '2026-05-05 18:03:27'),
(35, 43, NULL, 'pending', 'checkout confirm', 2, '2026-05-06 21:05:31'),
(36, 44, NULL, 'pending', 'checkout confirm', 2, '2026-05-06 21:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplierId` int(11) NOT NULL AUTO_INCREMENT,
  `sup_name` varchar(100) NOT NULL,
  `sup_email` varchar(100) DEFAULT NULL,
  `sup_website` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`supplierId`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplierId`, `sup_name`, `sup_email`, `sup_website`) VALUES
(4, 'Thomannmusic', 'info@thomannmusic.com', 'https://www.thomannmusic.com');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `picture` varchar(255) DEFAULT 'avatar.png',
  `status` varchar(10) NOT NULL DEFAULT 'user',
  `account_blocked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `pass`, `email`, `dob`, `picture`, `status`, `account_blocked`) VALUES
(1, 'Admin', 'Name', '$2y$10$sWTiJLw/Ub/vwnlcJsZvf./adI9IMS2CdqXchTleI7y2g9c5JF4e.', 'admin@admin.com', NULL, 'img_69f9e967e916f9.48462398.jpg', 'adm', 0),
(2, 'User', 'Name', '$2y$10$gNPgH/F0G7F8zP11UiKtFuP0jYzOkiTIqwnSyGOXfRmuwih8Dm06W', 'user@user.com', '2001-10-10', 'img_69f9ea78771f42.62067695.png', 'user', 0),
(3, 'test', 'test', '$2y$10$75A4jCUKSAViQrDckgLk8uSKpP3YacI/g0HFWmKZFgxy3iZjmqqvy', 'test@test.com', '1993-07-07', 'img_69f9ea640a3e54.71423529.png', 'user', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `item_type` varchar(20) NOT NULL DEFAULT 'instrument',
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `uk_wishlist_user_item` (`user_id`,`product_id`,`item_type`),
  KEY `idx_wishlist_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `instruments`
--
ALTER TABLE `instruments`
  ADD CONSTRAINT `fk_instruments_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`fk_supplier_id`) REFERENCES `suppliers` (`supplierId`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rental_requests`
--
ALTER TABLE `rental_requests`
  ADD CONSTRAINT `fk_rental_requests_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `instruments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `rental_status_logs`
--
ALTER TABLE `rental_status_logs`
  ADD CONSTRAINT `fk_status_logs_changed_by` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_logs_rental` FOREIGN KEY (`rental_request_id`) REFERENCES `rental_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
