-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 11:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel-agency-panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `agenda_items`
--

CREATE TABLE `agenda_items` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL COMMENT 'e.g., 1 for Day 1, 2 for Day 2',
  `item_time` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_class` varchar(255) DEFAULT 'fas fa-info-circle' COMMENT 'Font Awesome class, e.g., fas fa-utensils',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'For reordering items'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agenda_items`
--

INSERT INTO `agenda_items` (`id`, `organization_id`, `day_number`, `item_time`, `title`, `description`, `icon_class`, `sort_order`) VALUES
(3, 4, 1, '11:00 – 12:00', 'Arrivals and check-in', '<p><em>Please contact at group check in area at ground level for registration and handover of your welcome kit.</em></p>', 'fa-solid fa-plane-arrival', 0),
(4, 4, 1, '12:45 – 14:30', 'Lunch', '<p><em>Kitchen District Restaurant</em></p>', 'fa-solid fa-utensils', 0),
(5, 4, 1, '14:30 – 15:30', 'Free time', '<p><em>See the venue page for more information.</em></p>', 'fa-solid fa-clock', 0),
(6, 4, 1, '15:30 – 16:30', 'Fan zone interaction', '<p><em>Pre function area two outside Regency ballroom</em></p>', 'fas fa-info-circle', 0),
(7, 4, 1, '17:30 – 18:00', 'Networking', '<p><em>Pre function area two outside Regency ballroom</em></p>', 'fas fa-info-circle', 0),
(8, 4, 1, '18:45 – 19:00', 'Keynote Address', '<p><em>Regency ballroom one and two</em></p>', 'fas fa-info-circle', 0),
(9, 4, 1, '19:15 – 20:00', 'A New Era of Shell Helix', '', 'fas fa-info-circle', 0),
(11, 4, 1, '20:55 – 21:45', 'Rewards and gala night', '<h4>Dress code:</h4>\r\n<p>Dress to impress in your personal modern/traditional style</p>\r\n<div class=\"dress-code-images\"><img class=\"img-fluid agenda-dress-image\" src=\"https://shellhelix2025.in/images/dress-code2.jpg\" alt=\"Women\'s Formal Attire\"><img class=\"img-fluid agenda-dress-image\" src=\"https://shellhelix2025.in/images/dress-code3.jpg\" alt=\"Men\'s Formal Attire\"><img class=\"img-fluid agenda-dress-image\" src=\"https://shellhelix2025.in/images/dress-code4.jpg\" alt=\"Women\'s Formal Attire\"></div>\r\n<ul>\r\n<li>Men: Formal black-tie attire (preferably black suit)</li>\r\n<li>Women: Formal modern/traditional attire (preferably black)</li>\r\n</ul>', 'fas fa-info-circle', 0);

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `user_session_id` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sender_type` enum('user','bot','admin') NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `organization_id`, `user_session_id`, `message`, `sender_type`, `status`, `created_at`) VALUES
(1, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 08:48:11'),
(2, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'yo', 'user', 'closed', '2025-09-20 09:02:18'),
(3, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 09:06:15'),
(4, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 09:17:57'),
(5, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 09:21:13'),
(6, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 09:24:43'),
(7, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'The dress code for the event is Business Casual.', 'bot', 'closed', '2025-09-20 09:24:43'),
(8, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'admin', 'user', 'closed', '2025-09-20 09:34:22'),
(9, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'Please wait while I connect you to an available admin.', 'bot', 'closed', '2025-09-20 09:34:22'),
(10, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'admin', 'user', 'closed', '2025-09-20 09:55:36'),
(11, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'Please wait while I connect you to an available admin.', 'bot', 'closed', '2025-09-20 09:55:36'),
(12, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'admin', 'user', 'closed', '2025-09-20 09:58:50'),
(13, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'Please wait while I connect you to an available admin.', 'bot', 'closed', '2025-09-20 09:58:50'),
(14, 1, 'guest_16mhj8g571gna9vmgaq64sioos', 'hello', 'admin', 'closed', '2025-09-20 10:25:59'),
(15, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'i want to know the timings', 'user', 'closed', '2025-09-20 10:26:22'),
(16, 1, 'guest_16mhj8g571gna9vmgaq64sioos', 'wassup', 'admin', 'closed', '2025-09-20 10:36:22'),
(17, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'yo', 'user', 'closed', '2025-09-20 11:04:37'),
(18, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'dress code', 'user', 'closed', '2025-09-20 11:04:47'),
(19, 2, 'guest_16mhj8g571gna9vmgaq64sioos', 'heelow2wq', 'user', 'closed', '2025-09-20 11:27:36'),
(20, 2, 'guest_s7r7njk46grpgad10c9ddsg7tk', 'dress code', 'user', 'closed', '2025-09-20 11:34:34'),
(21, 2, 'guest_s7r7njk46grpgad10c9ddsg7tk', 'The dress code for the event is Business Casual.', 'bot', 'closed', '2025-09-20 11:34:34'),
(22, 2, 'guest_s7r7njk46grpgad10c9ddsg7tk', 'admin', 'user', 'closed', '2025-09-20 11:34:37'),
(23, 2, 'guest_s7r7njk46grpgad10c9ddsg7tk', 'Please wait while I connect you to an available admin.', 'bot', 'closed', '2025-09-20 11:34:37'),
(24, 1, 'guest_s7r7njk46grpgad10c9ddsg7tk', 'hello', 'admin', 'closed', '2025-09-20 11:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `event_details`
--

CREATE TABLE `event_details` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `event_date` date DEFAULT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_address` text DEFAULT NULL,
  `venue_url` text DEFAULT NULL,
  `home_about_title` varchar(255) DEFAULT 'About The Event',
  `home_about_content` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_details`
--

INSERT INTO `event_details` (`id`, `organization_id`, `event_date`, `venue_name`, `venue_address`, `venue_url`, `home_about_title`, `home_about_content`, `updated_at`) VALUES
(1, 4, '2025-10-29', NULL, NULL, NULL, 'About The Event', '<div style=\"font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, \'Noto Sans\', \'Liberation Sans\', sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\', \'Noto Color Emoji\'; color: #212529;\">\r\n<p style=\"font-size: 1.25rem; font-weight: 300;\">Join us for an unforgettable event where power takes center stage and celebrations turn into memorable moments.</p>\r\n<p style=\"font-size: 1.25rem; font-weight: 300;\">You are invited to witness historic milestones and discover the power of OEM motorsport partnerships at <br><strong style=\"font-weight: bold;\">Unleash the Power in New Delhi &ndash; a new era of Shell Helix.</strong></p>\r\n<p style=\"font-size: 1.25rem; font-weight: 300; margin-bottom: 1.5rem;\">An exclusive activation in <strong style=\"font-weight: bold;\">Delhi NCR on August 19, 2025</strong></p>\r\n<ul style=\"list-style: none; padding-left: 0; margin-top: 1.5rem;\">\r\n<li style=\"margin-bottom: 0.5rem;\"><span style=\"color: #ffcc00; margin-right: 0.5rem;\">✔</span> <span style=\"font-weight: bold;\">Power Unleashed &ndash;</span> Tap into your inner power through immersive experiences and fan zone activities.</li>\r\n<li style=\"margin-bottom: 0.5rem;\"><span style=\"color: #ffcc00; margin-right: 0.5rem;\">✔</span> <span style=\"font-weight: bold;\">Exclusive brand and product reveals &ndash;</span> Be among the first to witness the future of Shell Helix.</li>\r\n<li style=\"margin-bottom: 0.5rem;\"><span style=\"color: #ffcc00; margin-right: 0.5rem;\">✔</span> <span style=\"font-weight: bold;\">Exciting OEM showcases &ndash;</span> Get up close with some of the biggest names in motorsports.</li>\r\n<li style=\"margin-bottom: 0.5rem;\"><span style=\"color: #ffcc00; margin-right: 0.5rem;\">✔</span> <span style=\"font-weight: bold;\">Rewards and gala night &ndash;</span> See the winners take center stage. #onlywithShellHelix</li>\r\n</ul>\r\n<p style=\"margin-top: 1.5rem;\">Drive On,</p>\r\n<p style=\"margin-bottom: 0;\">Shell Helix Power Unit</p>\r\n</div>', '2025-09-30 11:52:23');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'For reordering FAQs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `organization_id`, `question`, `answer`, `sort_order`) VALUES
(2, 4, 'How will I be able to book my flight / train for the event?', '<div>\r\n<div>Fill all details in the dedicated registration website for Unleash the Power in New Delhi - a new era of Shell Helix event. Shell team will enable return travel for the event back to base location.</div>\r\n<div>&nbsp;</div>\r\n</div>', 0),
(3, 4, 'Will I need to share KYC details for flight/train bookings?', '<div>\r\n<div>Yes, kindly share your KYC details onto the dedicated event website to enable ticket bookings.</div>\r\n</div>', 0),
(4, 4, 'I don’t have direct flight to New Delhi from my base location.', '<div>\r\n<div>If you don&rsquo;t have direct flight from your base location, you will need to reach the nearest airport to take the flight. Shell will provide flight tickets from the nearest airport to New Delhi and back to your base location.</div>\r\n</div>', 0),
(5, 4, 'Once I arrive at the New Delhi airport how do I travel to the hotel?', '<div>\r\n<div>Shell team coordinators will be available at the airport arrival just outside the terminal holding the Shell Helix logo, kindly follow them to the designated HSSE compliant coach for your travel to the Hotel.</div>\r\n<div>&nbsp;</div>\r\n</div>', 0),
(6, 4, 'What does my trip include?', '<ul>\r\n<li>Airfare to and from New Delhi.</li>\r\n<li>Round-trip airport transportation during the programme dates of August 19-20, 2025.</li>\r\n<li>Accommodation for one guest at the Hyatt Regency, Gurugram.</li>\r\n<li>Exclusive dining experiences and meals during the programme dates.</li>\r\n<li>Activities as shown on the agenda.</li>\r\n<li>Not included: Transportation to/from your home city airport; transportation to/from the hotels on non-programme location or dates; luggage fees; hotel incidentals such as telephone calls, room service, mini-bar purchases, internet charges, spa and laundry.</li>\r\n</ul>', 0),
(7, 4, 'Can I request to cancel/transfer my participation to someone I nominate?', '<div>Yes, you may nominate a business associate for attending the event on your behalf or to represent your business only at the time of registering on the dedicated brand event website. However, once the ticket booking is done. No changes will be entertained.</div>\r\n<div>&nbsp;</div>', 0),
(8, 4, 'Can I extend the date of return after the event is concluded?', '<div>\r\n<div>Yes, you may extend your date of return post the Unleash the Power in New Delhi &ndash; a new era of Shell Helix event. However, all arrangements need to be done by the guest on their own cost. Shell will provide the return day ticket provided there is no substantial increase in the air fare.</div>\r\n<div>&nbsp;</div>\r\n</div>', 0),
(9, 4, 'What happens if my request to extend my return to the base location results in a higher airfare?', '<div>\r\n<div>In case your return day ticket air fare is high. The guest will need to make their individual arrangement for returning to their respective base locations. Shell will be unable to support ticket date extension with higher costs.</div>\r\n<div>&nbsp;</div>\r\n</div>', 0),
(10, 4, 'How will I travel back to the airport on my day of departure?', '<div>\r\n<div>Shell team will provide a HSSE compliant coach for drop to New Delhi IGI airport post the conclusion of Unleash the Power in New Delhi &ndash; a new era of Shell Helix event. In case of an extended stay the guest must arrange for their own airport transfers.</div>\r\n<div>&nbsp;</div>\r\n</div>', 0),
(11, 4, 'What are the dress codes for Unleash the Power in New Delhi – a new era of Shell Helix?', '<div>&nbsp;Dress code for day one &ndash; Dress to impress in your personal modern/traditional style-</div>\r\n<div>&nbsp; &bull; Men: Formal black-tie attire (preferably black suit)</div>\r\n<div>&nbsp; &bull; Women: Formal modern/traditional attire (preferably black)&nbsp;</div>\r\n<div>Dress code for day two:&nbsp;</div>\r\n<div>&bull; Helix red T-shirt provided in the registration kit, along with red caps.</div>', 0);

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `bg_color` varchar(7) NOT NULL,
  `text_color` varchar(7) DEFAULT '#ffffff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `user_id`, `title`, `logo_url`, `bg_color`, `text_color`, `created_at`) VALUES
(2, 2, 'Demo', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/Demo/logos/demo.jpg', '#eb1e1e', '#ffffff', '2025-09-09 10:32:58'),
(3, 4, 'XYZ', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/XYZ/logos/xyz.PNG', '#316add', '#ffffff', '2025-09-15 10:58:33'),
(4, 5, 'Shell-Helix', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/Shell-Helix/logos/20250923124654_68d24966a20c2_shell-helix.png', '#ffcc00', '#fffff#', '2025-09-23 07:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `registered`
--

CREATE TABLE `registered` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `govt_id` varchar(255) DEFAULT NULL,
  `govt_id_link` varchar(255) DEFAULT NULL,
  `food` varchar(255) DEFAULT NULL,
  `otp` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `is_arrived_on_airport` tinyint(1) DEFAULT 0,
  `is_arrived_on_bus` tinyint(1) DEFAULT 0,
  `is_arrived_at_hotel` tinyint(1) DEFAULT 0,
  `is_attended_the_session` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registered`
--

INSERT INTO `registered` (`id`, `organization_id`, `name`, `gender`, `email`, `phone`, `location`, `govt_id`, `govt_id_link`, `food`, `otp`, `password`, `qr_code`, `is_arrived_on_airport`, `is_arrived_on_bus`, `is_arrived_at_hotel`, `is_attended_the_session`, `created_at`) VALUES
(1, 2, 'Tumul Shukla', 'Male', 'admin@tcp.com', '7905411328', 'sdsdfsfd', NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/admin%40tcp.com/govt-ids/admin%40tcp.com.jpg', NULL, NULL, NULL, NULL, 0, 0, 0, 0, '2025-09-11 10:53:53'),
(2, 2, 'xyz', 'Male', 'admin@tcp.com', '2233445566', 'sdsdfsfd', NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/admin%40tcp.com/govt-ids/admin%40tcp.com.jpeg', NULL, NULL, NULL, NULL, 0, 0, 0, 0, '2025-09-11 11:20:50'),
(3, 2, 'Tumul Shukla', 'Male', 'admin@example.com', '2233445566', 'sdsdfsfd', NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/admin%40example.com/govt-ids/admin%40example.com.jpg', NULL, NULL, NULL, NULL, 0, 0, 0, 0, '2025-09-11 11:22:48'),
(4, 2, 'Tumulukla', 'Male', 'team@sid.com', '9988776677', 'sdsdfsfd', NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/demo/govt-ids/demo.PNG', NULL, NULL, NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/demo/qrcodes/demo.png', 0, 0, 0, 0, '2025-09-12 10:46:59'),
(5, 3, 'TumulShukla', 'Male', 'tutututu@email.com', '2211221122', 'fefefefe', 'adhar', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/xyz/govt-ids/xyz.PNG', 'veg', NULL, NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/xyz/qrcodes/xyz.png', 0, 0, 0, 0, '2025-09-15 11:07:36'),
(6, 2, 'TumulShukla99', 'Male', 'email@email.com', '1122334455', 'sdsdfsfd', NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/demo/govt-ids/demo.jpg', NULL, NULL, '654321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/demo/qrcodes/demo.png', 0, 0, 0, 0, '2025-09-16 06:31:25'),
(7, 4, 'shelldemouser', 'Male', NULL, '3344334433', NULL, NULL, NULL, NULL, NULL, '4321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926110238_68d625768dd6f_shell-helix.png', 0, 0, 0, 0, '2025-09-26 05:32:39'),
(8, 4, 'shelldemouser', 'Male', NULL, '2233223344', NULL, NULL, NULL, NULL, NULL, '4321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926110344_68d625b8883cb_shell-helix.png', 0, 0, 0, 0, '2025-09-26 05:33:45'),
(9, 4, 'shelldemouser', 'Male', NULL, '2233223344', NULL, NULL, NULL, NULL, NULL, '4321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926111644_68d628c4225f7_shell-helix.png', 0, 0, 0, 0, '2025-09-26 05:46:44'),
(10, 4, 'qqqq', 'Male', NULL, '534324324242', NULL, NULL, NULL, NULL, NULL, '4321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926111702_68d628d6a2cb1_shell-helix.png', 0, 0, 0, 0, '2025-09-26 05:47:03'),
(11, 4, 'qqqq', 'Male', NULL, '22333121212121', NULL, NULL, NULL, NULL, NULL, '321', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926112007_68d6298f394fe_shell-helix.png', 0, 0, 0, 0, '2025-09-26 05:50:07'),
(14, 4, 'qqqq3', 'Male', 'ttt@tt.com', '4444444444', NULL, NULL, NULL, NULL, NULL, '1111', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250926150150_68d65d8684319_shell-helix.png', 0, 0, 0, 0, '2025-09-26 09:31:50'),
(15, 4, 'aqq', 'Male', 'q@q.com', '11111111', NULL, NULL, 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/govt-ids/20250929110823_68da1b4f63fcd_shell-helix.jpg', NULL, NULL, '1111', 'https://commanbucetforevents.s3.ap-south-1.amazonaws.com/travel-agency-panel/shell-helix/qrcodes/20250929110824_68da1b5010022_shell-helix.png', 0, 0, 0, 0, '2025-09-29 05:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `registration_fields`
--

CREATE TABLE `registration_fields` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_fields`
--

INSERT INTO `registration_fields` (`id`, `organization_id`, `fields`, `created_at`) VALUES
(1, 2, '[{\"field\":\"name\",\"label\":\"Name\"},{\"field\":\"gender\",\"label\":\"Gender\"},{\"field\":\"email\",\"label\":\"Email\"},{\"field\":\"phone\",\"label\":\"Phone\"},{\"field\":\"location\",\"label\":\"Location\"},{\"field\":\"govt_id_link\",\"label\":\"Government ID Photo\"}]', '2025-09-11 09:51:19'),
(2, 3, '[{\"field\":\"name\",\"label\":\"Name\"},{\"field\":\"gender\",\"label\":\"Gender\"},{\"field\":\"email\",\"label\":\"Email\"},{\"field\":\"phone\",\"label\":\"Phone\"},{\"field\":\"location\",\"label\":\"Location\"},{\"field\":\"govt_id\",\"label\":\"Government ID\"},{\"field\":\"govt_id_link\",\"label\":\"Government ID Photo\"},{\"field\":\"food\",\"label\":\"Food Preference\"}]', '2025-09-15 11:06:04'),
(3, 4, '[{\"field\":\"name\",\"label\":\"Name\"},{\"field\":\"gender\",\"label\":\"Gender\"},{\"field\":\"email\",\"label\":\"Email\"},{\"field\":\"phone\",\"label\":\"Phone\"},{\"field\":\"govt_id_link\",\"label\":\"Government ID Photo\"}]', '2025-09-29 05:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','user') NOT NULL DEFAULT 'user',
  `organization_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `password`, `role`, `organization_id`, `created_at`) VALUES
(1, 'admin@tcp.com', '123456', 'super_admin', NULL, '2025-09-08 11:22:56'),
(2, 'demouser', '123456', 'admin', NULL, '2025-09-09 10:32:58'),
(4, 'xyz', '4321', 'admin', NULL, '2025-09-15 10:58:33'),
(5, 'ShellHelix', '1234', 'admin', NULL, '2025-09-23 07:16:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agenda_items`
--
ALTER TABLE `agenda_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_details`
--
ALTER TABLE `event_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organization_id` (`organization_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registered`
--
ALTER TABLE `registered`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_fields`
--
ALTER TABLE `registration_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organization_id` (`organization_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agenda_items`
--
ALTER TABLE `agenda_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `event_details`
--
ALTER TABLE `event_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `registered`
--
ALTER TABLE `registered`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `registration_fields`
--
ALTER TABLE `registration_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agenda_items`
--
ALTER TABLE `agenda_items`
  ADD CONSTRAINT `fk_agenda_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_details`
--
ALTER TABLE `event_details`
  ADD CONSTRAINT `event_details_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faqs`
--
ALTER TABLE `faqs`
  ADD CONSTRAINT `fk_faq_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_fields`
--
ALTER TABLE `registration_fields`
  ADD CONSTRAINT `fk_organization_id` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
