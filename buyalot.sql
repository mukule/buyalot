-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2025 at 02:55 PM
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
-- Database: `buyalot`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subcategory_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `subcategory_id`, `name`, `slug`, `active`, `logo_path`, `created_at`, `updated_at`) VALUES
(1, 7, 'CLINIQUE', 'clinique', 1, 'brands/26612921-43c1-49c6-8d9b-07a5e07c87aa.webp', '2025-07-16 07:56:09', '2025-07-16 07:56:09'),
(2, 7, 'NIVEA', 'nivea', 1, 'brands/eb03169f-ea7a-4652-af33-fed9e73c29f2.webp', '2025-07-16 10:34:12', '2025-07-16 10:34:12'),
(3, 7, 'NICE & LOVELY', 'nice-lovely', 1, 'brands/e8a8b737-6752-4381-babb-8c912c5d504c.webp', '2025-07-16 10:34:37', '2025-07-16 10:34:37'),
(4, 7, 'DOVE', 'dove', 1, 'brands/6cfeef8f-1ad3-4f27-95e2-50dd41ad84c7.webp', '2025-07-16 10:35:00', '2025-07-16 10:35:00'),
(5, 1, 'NESCAFE', 'nescafe', 1, 'brands/cd8300b6-84dc-4293-aece-347f0185aa08.webp', '2025-07-16 10:35:16', '2025-07-20 19:50:23'),
(6, 7, 'TP LINK', 'tp-link', 1, 'brands/51be02bb-7eb3-4ea4-9687-61fe7990ce6d.webp', '2025-07-16 10:35:31', '2025-07-16 10:35:31'),
(7, 7, 'SAMSUNG', 'samsung', 1, 'brands/87c099c6-db32-477a-8bd6-ae3fffc871bd.webp', '2025-07-16 10:35:58', '2025-07-16 11:09:00'),
(10, 7, 'Skyworth', 'skyworth-687cd3331a7e0', 1, 'brands/81a1cf7d-d9e7-4ec6-a896-1fd3a2f36c0d.webp', '2025-07-20 08:29:55', '2025-07-20 08:29:55'),
(11, 7, 'TCL', 'tcl', 1, 'brands/9dd5d592-8a40-4f8d-8fe4-d4e3c899eb43.webp', '2025-07-20 17:34:19', '2025-07-20 17:34:19'),
(12, 7, 'Vitron', 'vitron', 1, 'brands/7018ebe5-6049-486d-9ef7-ed854ad2ca3e.webp', '2025-07-20 17:42:11', '2025-07-20 17:42:11'),
(14, 7, 'Hisense', 'hisense-687d57b063fd7', 1, 'brands/66577291-d35b-4091-99af-8a01a3139423.webp', '2025-07-20 17:55:12', '2025-07-20 17:55:12'),
(15, 7, 'GLD', 'gld', 1, 'brands/def82b9a-7335-4566-8e13-8151f4592fbc.webp', '2025-07-20 17:59:14', '2025-07-20 17:59:14'),
(16, 7, 'LG', 'lg', 1, 'brands/fc66278a-eb95-4b0e-9209-dd2a7e6f0ae4.webp', '2025-07-20 18:38:39', '2025-07-20 18:38:39'),
(17, 7, 'Synix', 'synix', 1, 'brands/34d5bdb1-d9ea-44c9-90a4-6a2082aed7c2.webp', '2025-07-20 19:22:06', '2025-07-20 19:22:06'),
(18, 7, 'CTC', 'ctc', 1, 'brands/7cce6e47-618a-42f1-84e5-137fb9302054.webp', '2025-07-20 20:33:45', '2025-07-20 20:49:26'),
(19, 1, 'Apple', 'apple', 1, 'brands/5b4d1db1-f979-4b53-90a9-d3747517321e.webp', '2025-07-22 19:09:20', '2025-07-22 19:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('buyalot_online_shopping_platform_cache_melson.masibo@kenyaweb.com|127.0.0.1', 'i:1;', 1753532965),
('buyalot_online_shopping_platform_cache_melson.masibo@kenyaweb.com|127.0.0.1:timer', 'i:1753532965;', 1753532965);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'electronics', 1, '2025-07-16 02:28:08', '2025-07-16 02:28:08'),
(2, 'Gaming', 'gaming', 1, '2025-07-16 02:34:32', '2025-07-16 02:34:32'),
(3, 'Fashion', 'fashion', 1, '2025-07-16 02:34:51', '2025-07-16 02:34:51'),
(4, 'Computing', 'computing', 1, '2025-07-16 02:35:06', '2025-07-16 02:35:06');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Business Permit', 'Provident quod nulls', '2025-07-13 12:04:39', '2025-07-13 12:17:39'),
(3, 'KRA', 'Officia sit hic ali', '2025-07-13 12:19:41', '2025-07-13 12:19:41'),
(4, 'CR12', 'Aspernatur deserunt', '2025-07-13 12:20:02', '2025-07-13 12:20:02'),
(5, 'Dakota Robles', 'Ipsum nulla adipisic', '2025-07-15 11:51:08', '2025-07-15 11:51:08'),
(6, 'test', 'sssss', '2025-07-22 17:42:06', '2025-07-22 17:42:06');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(5, '0001_01_01_000000_create_users_table', 1),
(6, '0001_01_01_000001_create_cache_table', 1),
(7, '0001_01_01_000002_create_jobs_table', 1),
(8, '2025_07_03_101832_create_permission_tables', 1),
(9, '2025_07_08_123512_create_seller_applications_table', 2),
(10, '2025_07_08_133953_create_seller_application_images_table', 2),
(11, '2025_07_09_100635_remove_unwanted_fields_from_seller_applications_table', 3),
(12, '2025_07_12_144203_add_status_and_reason_to_seller_applications', 4),
(13, '2025_07_13_130950_add_seller_application_id_to_users_table', 5),
(14, '2025_07_13_132959_create_seller_documents_table', 6),
(15, '2025_07_13_133347_create_document_types_table', 6),
(16, '2025_07_14_115103_create_seller_documents_table', 7),
(17, '2025_07_14_144738_add_verified_to_seller_applications_table', 8),
(18, '2025_07_15_150033_create_warehouses_table', 9),
(19, '2025_07_16_050216_create_categories_table', 10),
(20, '2025_07_16_081115_create_subcategories_table', 11),
(21, '2025_07_16_090042_create_brands_table', 12),
(24, '2025_07_16_142248_create_unit_types_table', 13),
(25, '2025_07_16_150019_create_unit_types_table', 14),
(26, '2025_07_16_150031_create_units_table', 14),
(27, '2025_07_16_152618_create_units_table', 15),
(28, '2025_07_18_093400_create_variant_categories_table', 16),
(29, '2025_07_18_094704_create_variants_table', 17),
(30, '2025_07_18_125204_create_products_table', 17),
(31, '2025_07_18_125305_create_product_images_table', 18),
(32, '2025_07_18_130922_update_products_table_add_subcategory_id', 18),
(33, '2025_07_19_114515_add_is_active_to_variants_table', 19),
(34, '2025_07_19_120619_create_product_variant_values_table', 20),
(35, '2025_07_20_212705_add_pricing_fields_to_products_table', 21),
(36, '2025_07_20_214700_add_subcategory_id_to_brands_table', 22);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 7),
(7, 'App\\Models\\User', 7),
(9, 'App\\Models\\User', 8);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('nelson.masibo@kenyaweb.com', '$2y$12$fm1PZh9ef0XOqdY7NxSfNuL3rQ9t037qidoS9FTUvJH0bTCZqd/jO', '2025-07-26 09:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Dashboard Accesss', 'web', '2025-07-05 01:19:41', '2025-07-05 04:27:43'),
(2, 'View sellers', 'web', '2025-07-05 01:21:13', '2025-07-05 01:21:13'),
(3, 'Approve Products', 'web', '2025-07-05 01:22:44', '2025-07-05 01:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `whats_in_the_box` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `discount` decimal(5,2) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `owner_type` varchar(255) NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subcategory_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `features`, `specifications`, `whats_in_the_box`, `price`, `stock`, `discount`, `meta_title`, `meta_keywords`, `meta_description`, `status`, `owner_type`, `owner_id`, `brand_id`, `unit_id`, `created_at`, `updated_at`, `subcategory_id`) VALUES
(8, 'Skyworth 43G6500G, 43\" 4K UHD Google TV 2025 - Black (1YR WRTY)', 'skyworth-43g6500g-43-4k-uhd-google-tv-2025-black-1yr-wrty', '<p><strong>Step into the World of Stunning Entertainment!</strong></p><p>&nbsp;</p><p>Say hello to the<strong>&nbsp;</strong>new&nbsp;<strong>Skyworth 43G6500G, 43\" 4K UHD(3840 x 2160) Google TV&nbsp;</strong>that’s here to transform your viewing experience. With breathtaking UHD resolution (3840 x 2160), every detail pops to life, making your favorite shows and movies more vivid than ever.</p><p>This ultra-slim TV doesn’t just perform—it stuns! Its sleek narrow-bezel design adds a modern, stylish vibe to any space. Dive into crystal-clear visuals with incredible depth and clarity, while the built-in apps like Netflix, Prime Video, and YouTube bring endless entertainment right to your fingertips.</p><p>The Skyworth 43G6500G is more than just a TV—it’s your gateway to premium home entertainment.</p><p><br></p><p>TV Specifications</p><h1>Model Information</h1><p>Model Number: 43G6500G</p><p>Chassis Number: 9K8FK3</p><p>Chipset (SOC): MT9602</p><p>Smart OS: Google TV</p><p>Screen Size: 43\"</p><p>Product Type: 4K LED-TV</p><h1>Panel Specification</h1><p>Open Cell Vendor: BOE</p><p>Open Cell Part Number: HV430QUB-F70</p><p>Resolution: UHD (3840 x 2160)</p><p>Brightness (typ.; Set): 250 nits</p><p>Color Gamut: 68%NTSC</p><p>Refresh Rate: 60 Hz</p><p>Viewing Angle: 178°(H)/178°(V)((Typ.)</p><p>Brightness Uniformity: &gt; 55%</p><p>Response time Typ.: 8 ms</p><p>Aspect Ratio: 16：9</p><p>Type of Backlight: DLED (OD35)</p><h1>Major Features</h1><p>Digital TV Reception: DVB-C/T2/S2</p><p>CPU: A53*4-1.5GHz</p><p>GPU: Mali-G52</p><p>eMMC Flash Storage Capacity: 16GB</p><p>DDR RAM Capacity: 2GB</p><p>HDR (High Dynamic Range): HDR10/HLG</p><p>Dolby Audio: Yes</p><p>Web Browser: Yes</p><p>Game Mode: Yes</p><p>Film Maker Mode: Yes</p><p>Wi-Fi Version: 802.11a/b/g/n</p><p>Wi-Fi Band - Single or Dual: Dual (2.4G / 5G)</p><p>Bluetooth and Bluetooth Version: Yes / BT 5.1</p><p>OSD Languages: English</p><p>Audio power output: 2 x 10W</p><p>YouTube app: Yes</p><p>Netflix app: Yes</p><p>Amazon Prime Video app: Yes</p><p>Disney Plus: Yes</p><p>App Store: Yes</p><h1>Connectivity</h1><p>RF Input (Antenna): 2</p><p>Composite (AV): 1</p><p>Component Input (YPbPr): No</p><p>CI/CI+: 0</p><p>HDMI: 3</p><p>USB: 2</p><p>Earphone Jack: 1</p><p>AV Output: No</p><p>Ethernet Input: 1</p><p>SPDIF Output (Optical/Coaxial): 1 (Optical)</p><p>VGA/RS-232: No</p><h1>Power Supply</h1><p>Power Supply (Voltage, Hz): 100-240V</p>', '<ul><li><strong>SKYWORTH 43G6500G, 43\" 4K UHD GOOGLE TV</strong></li><li><strong>Internal Memory:</strong>&nbsp;<strong>16GB</strong>&nbsp;<strong>(16GB Inclusive of Pre-Installed Applications)</strong></li><li><strong>Resolution: UHD(3840 x 2160)</strong></li><li>RAM:&nbsp;2GB</li><li>Google TV</li><li>Frameless Design</li><li>Google Assistant</li><li>Google Play Store</li><li>Netflix, Prime Video, YouTube</li><li>Remote Voice Control</li><li>Bluetooth 5.1</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: SK493EA674BS4NAFAMZ</li><li><strong>GTIN Barcode</strong>: 04895018246535</li><li><strong>Model</strong>: 43G6500G</li><li><strong>Production Country</strong>: China</li><li><strong>Size (L x W x H cm)</strong>: 63 x 103.5 x 11.8</li><li><strong>Weight (kg)</strong>: 8</li><li><strong>Main Material</strong>: PVC</li><li><strong>Warranty Address</strong>: &lt;p&gt;1 Year Warranty (Dama House, Luthuli Avenue)&lt;/p&gt;</li><li><strong>Warranty Type</strong>: Service Center - Nairobi</li></ul><p><br></p>', '<ul><li>1 X<strong>&nbsp;</strong>SKYWORTH 43G6500G, 43\" 4K UHD GOOGLE TV</li><li>1 X REMOTE CONTROL</li><li>1 X USER GUIDE</li></ul><p><br></p>', 0.00, 0, NULL, 'Skyworth 43G6500G, 43\" 4K UHD Google TV 2025 - Black (1YR WRTY)', 'Skyworth, 43G6500G,, 43\", 4K, UHD, Google, TV, 2025, -, Black, (1YR, WRTY)', 'Step into the World of Stunning Entertainment!&nbsp;Say hello to the&nbsp;new&nbsp;Skyworth 43G6500G, 43\" 4K UHD(3840 x 2160) Google TV&nbsp;that’s here to tr', 0, 'admin', 1, 10, 4, '2025-07-20 16:53:09', '2025-07-20 16:53:09', 7),
(9, 'Skyworth 43E3500G, 43-inches Full HD 2024 Frameless Google TV, Black (1YR WRTY)', 'skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty', '<p><span style=\"color: rgb(49, 49, 51);\">Immerse yourself in the new breathtaking</span><strong style=\"color: rgb(49, 49, 51);\">&nbsp;Skyworth 43\" 43E3500G Full HD Google TV&nbsp;</strong><span style=\"color: rgb(49, 49, 51);\">that delivers pin-sharp picture quality. Breathe life into your living room with this ultra slim Google TV&nbsp;that will blow you away with stunning and much clearer video and audio home entertainment‎.‎ It‎\'‎s narrow bezel design gives this TV a striking display that will add to your home a modern and stylish look‎.‎ Enjoy crystal clear visuals in depth thanks to the super clear high‎-resolution capabilities‎. The tv features in-built apps such as Netflix, Prime Video and You-tube.</span></p><p><br></p><h2><strong><span class=\"ql-cursor\">﻿</span>Model Information</strong></h2><ul><li><strong>Model Number:</strong> 43E3500G</li><li><strong>Chassis Number:</strong> 7K5LT</li><li><strong>Chipset (SoC):</strong> MT9216</li><li><strong>Smart OS:</strong> Google TV</li><li><strong>Screen Size:</strong> 43 inches</li><li><strong>Product Type:</strong> 2K LED TV</li></ul><h2><strong>Panel Specifications</strong></h2><ul><li><strong>Open Cell Vendor:</strong> HKC</li><li><strong>Open Cell Part Number:</strong> PT430CT05-1</li><li><strong>Resolution:</strong> Full HD (1920 x 1080)</li><li><strong>Typical Brightness (Set):</strong> 200 nits</li><li><strong>Color Gamut:</strong> 68% NTSC</li><li><strong>Refresh Rate:</strong> 60 Hz</li><li><strong>Viewing Angle (Typical):</strong> 176° horizontal / 176° vertical</li><li><strong>Brightness Uniformity:</strong> Greater than 55%</li><li><strong>Typical Response Time:</strong> 9.5 ms</li><li><strong>Aspect Ratio:</strong> 16:9</li><li><strong>Backlight Type:</strong> Direct LED (OD35)</li></ul><h2><strong>Major Features</strong></h2><ul><li><strong>Digital TV Reception:</strong> DVB-C / T / T2</li><li><strong>CPU:</strong> Quad-core ARM Cortex-A55 @ 1.5 GHz</li><li><strong>GPU:</strong> Mali-G31 MP2</li><li><strong>Internal Storage (eMMC):</strong> 8 GB</li><li><strong>RAM:</strong> 1.5 GB DDR</li><li><strong>HDR Support:</strong> HDR10 and HLG</li><li><strong>Dolby Audio:</strong> Supported</li><li><strong>Dolby Atmos:</strong> Not supported</li><li><strong>Far-field Voice Interaction:</strong> Not available</li><li><strong>Web Browser:</strong> Not available</li><li><strong>Game Mode:</strong> Not available</li><li><strong>Filmmaker Mode:</strong> Not available</li><li><strong>Wi-Fi Standard:</strong> IEEE 802.11 a/b/g/n</li><li><strong>Wi-Fi Band Support:</strong> Dual-band (2.4 GHz / 5 GHz)</li><li><strong>Bluetooth Support and Version:</strong> Yes, Bluetooth 5.0</li><li><strong>On-Screen Display Languages:</strong> English</li><li><strong>Audio Output Power:</strong> 2 x 10W</li><li><strong>Speaker Configuration:</strong> 2-box speakers</li><li><strong>Subwoofer:</strong> Not available</li><li><strong>Pre-installed Apps:</strong> YouTube, Netflix, Amazon Prime Video, Disney+</li><li><strong>App Store:</strong> Available</li><li><strong>Lighting / Radar Sensor:</strong> Not available</li></ul><h2><strong>Connectivity Options</strong></h2><ul><li><strong>RF (Antenna) Input:</strong> 1</li><li><strong>AV Composite Input:</strong> 1</li><li><strong>Component Input (YPbPr):</strong> Not available</li><li><strong>CI / CI+ Slot:</strong> Not available</li><li><strong>HDMI Ports:</strong> 2</li><li><strong>USB Ports:</strong> 2</li><li><strong>Earphone Jack:</strong> 1</li><li><strong>AV Output:</strong> Not available</li><li><strong>Ethernet (LAN) Port:</strong> 1</li><li><strong>SPDIF (Optical Digital Audio Output):</strong> 1 (Optical)</li><li><strong>VGA / RS-232:</strong> Not available</li></ul><h2><strong>Power Supply</strong></h2><ul><li><strong>Power Supply Requirements:</strong> 100–240V AC, 50/60 Hz</li></ul><p><br></p>', '<ul><li><strong>SKYWORTH 43\" 43E3500G FHD GOOGLE TV</strong></li><li><strong>Internal Memory:</strong>&nbsp;<strong>8GB</strong>&nbsp;<strong>(8GB Inclusive of Pre-Installed Applications)</strong></li><li>RAM:&nbsp;1.5GB</li><li>Google TV</li><li>Frameless Design</li><li>Google Assistant</li><li>Google Play Store</li><li>Netflix, Prime Video, YouTube</li><li>Remote Voice Control</li><li>Bluetooth 5.0</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: SK493EA5H71XWNAFAMZ</li><li><strong>GTIN Barcode</strong>: 04895018248966</li><li><strong>Model</strong>: 43E3500G</li><li><strong>Production Country</strong>: China</li><li><strong>Size (L x W x H cm)</strong>: 95.4x61.1x21.4</li><li><strong>Weight (kg)</strong>: 7</li><li><strong>Main Material</strong>: PVC</li><li><strong>Warranty Address</strong>: 1 Year (Dama House, Luthuli Avenue)</li><li><strong>Warranty Type</strong>: Service Center - Nairobi</li></ul><p><br></p>', '<ul><li>1 X SKYWORTH 43\" 43E3500G FHD GOOGLE TV</li><li>1 X REMOTE CONTROL</li><li>1 X USER GUIDE</li></ul><p><br></p>', 0.00, 0, NULL, 'Skyworth 43E3500G, 43-inches Full HD 2024 Frameless Google TV, Black (1YR WRTY)', 'Skyworth, 43E3500G,, 43-inches, Full, HD, 2024, Frameless, Google, TV,, Black, (1YR, WRTY)', 'Immerse yourself in the new breathtaking&nbsp;Skyworth 43\" 43E3500G Full HD Google TV&nbsp;that delivers pin-sharp picture quality. Breathe life into your livin', 0, 'admin', 1, 10, 4, '2025-07-20 17:28:34', '2025-07-20 17:28:34', 7),
(10, 'TCL 43\" Inch 4K ULTRA HD,Smart GOOGLE TV,NETFLIX,APPSTORE+GIFTS', 'tcl-43-inch-4k-ultra-hdsmart-google-tvnetflixappstoregifts', '<p><span style=\"color: rgb(49, 49, 51);\">The entertainment you love with the help from Google.</span></p><p><span style=\"color: rgb(49, 49, 51);\">Watch 700,000+ movies and TV episodes all in one place. A Google TV brings your favorite contents from across your apps and subscriptions and organizes them just for you</span></p><p><br></p><p>Your TV is more helpful than ever</p><p>Use your voice to find movies, stream apps, play music, and control the TV. Ask Google to find a specific title, search by genre, or get personalized recommendations by saying, “what should I watch?” Even get answers like sports scores, control smart home devices, and more.</p><p><br></p><p><span style=\"color: rgb(49, 49, 51);\">See details in a detail</span></p><p><span style=\"color: rgb(49, 49, 51);\">High Dynamic Range (HDR), the latest standard for UHD contents, provides a superior experience with striking brightness, exceptional shadow details and vivid colours. Sit still and enjoy incredible picture details as the film makers intended.</span></p><p><br></p><p><br></p><p>HDR 10</p><p>With the stunning 4K display that applies the latest open high-dynamic-range video standard, dynamic tone mapping optimizes picture quality, and range of tones, brightness, and contrast are considerably enhanced on a frame-by-frame basis. More nuanced content makes your screening experience most entertaining and enjoyable.</p><p>&nbsp;</p><p><span style=\"color: rgb(49, 49, 51);\">AIPQ 2.0</span></p><p><span style=\"color: rgb(49, 49, 51);\">A new quad-core processor is built to optimizing overall hardware and software performance, enhancing the entertaining experience.</span></p><p><span style=\"color: rgb(49, 49, 51);\">A chipset enabling TCL algorithm processes content in real time, detecting environment and upscaling display and audio. Pictures are optimized according to content, so oceans appear bluer, and rainforests more lush.</span></p><p><span style=\"color: rgb(49, 49, 51);\">Meanwhile, audio quality is compensated dynamically based on volume, eliminating distortion of signal and speakers, and providing a more authentic auditory experience at any sound level.</span></p>', '<ul><li>TCL 43\" FRAMELESS 4K ULTRA HD ANDROID TV</li><li>43\" 4K ULTRA HD ANDROID TV&nbsp;</li><li>FRAMELESS TV&nbsp;</li><li>4K ULTRA HD TV&nbsp;</li><li>ANDROID TV</li><li>NETFLIX</li><li>YOUTUBE&nbsp;</li><li>CHROMECAST&nbsp;</li><li>GOOGLE CHROME&nbsp;</li><li>BLUETOOTH&nbsp;</li><li>VOICE CONTROL&nbsp;</li><li>GOOGLE PLAYSTORE&nbsp;</li><li>TCL AI-IN TV</li><li>4K HDR</li><li>4K UPSCALING</li><li>HANDS-FREE VOICE CONTROL</li><li>RICH COLOR EXPANSION</li><li>LATEST ANDROID OS</li><li>MICRO DIMMING</li><li>FULL-SCREEN DESIGN</li><li>GOOGLE ASSISTANT</li><li>WORK WITH ALEXA</li><li>GOOGLE PLAY</li><li>MINIMALIST DESIGN</li><li>CHROMECAST BUILT-IN</li><li>VOICE SEARCH</li><li>FREEVIEW CERTIFIED</li><li>IPQ ENQINE</li><li>CLEAR MOTION RATE 100</li><li>T-CHANNEL</li><li>NETFLIX</li><li>YOU-TUBE</li><li>BLUETOOTH</li><li>DOLBY AUDIO</li><li>WI-FI ENABLE</li><li>USB PVR</li><li>HDMI 3.0</li><li>USB 2.0</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: TC187EA285UW4NAFAMZ</li><li><strong>Weight (kg)</strong>: 5</li></ul><p><br></p>', '<ul><li><strong>TCL 43” FRAMELESS 4K ULTRA HD Google TV</strong></li><li><strong>USER MANUAL</strong></li><li><strong>VOICE CONTROL REMOTE</strong></li><li><strong>FREE TV GUARD</strong></li><li><strong>FREE WALL BRACKET</strong></li></ul><p><br></p>', 0.00, 0, NULL, 'TCL 43\" Inch 4K ULTRA HD,Smart GOOGLE TV,NETFLIX,APPSTORE+GIFTS', 'TCL, 43\", Inch, 4K, ULTRA, HD,Smart, GOOGLE, TV,NETFLIX,APPSTORE+GIFTS', 'The entertainment you love with the help from Google.Watch 700,000+ movies and TV episodes all in one place. A Google TV brings your favorite contents from acro', 0, 'admin', 1, 11, 4, '2025-07-20 17:39:08', '2025-07-20 17:39:08', 5),
(11, 'Vitron HTC3200S 32\" Inch Smart Android TV-Bluetooth, Built-in Wi-Fi, Netflix, YouTube, Appstore, Inbuilt Decoder+Free Bracket+TV Guard+Extension', 'vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension', '<p><span style=\"color: rgb(49, 49, 51);\">The&nbsp;HTC3200S VITRON&nbsp;32 inch, HD&nbsp;</span><strong style=\"color: rgb(49, 49, 51);\">Smart Android LED TV</strong><span style=\"color: rgb(49, 49, 51);\">&nbsp;features&nbsp;sharply and vividly conveys the colour of the image making it true to life as you view it. Enjoy access&nbsp;to youtube , netflix, Facebook and many more apps with ease.&nbsp;It carries a powerful multi-surround sound effect the increases the excitement and takes your normal sports watching a pleasurable experience. TV comes with Smart features like a dedicated web browser and popular apps like YouTube and a range of movie on demands services. Playback your stored content directly on the big screen for all to enjoy via a USB device or use it to record TV programmes and even pause live TV. Place your order for this amazing product from Jumia Kenya and have it delivered to your doorstep at an amazing price…</span></p><p><br></p><p><span class=\"ql-cursor\">﻿</span>Technical Specifications</p><p>Display</p><ul><li>Screen size: 32\"</li><li>Resolution:&nbsp;1366*768</li><li>Tv Technology:&nbsp;SMART Android LED</li></ul><p>Connectivity</p><ul><li>Ports: USB(2), HDMI (3), AV input(1)</li></ul><p>Cabinet Audio System&nbsp;</p><ul><li>Bright treble, full mid range, deep bass, bring you and your family a thrilling listening experience.</li></ul><p>DNR Noise Reduction Technology&nbsp;</p><ul><li>Built-in 3D noise reduction make the signal stable and the picture realistic.</li></ul><p>Additional Features</p><ul><li>HD&nbsp;- More Vivid Images.</li><li>No light dot, restore natural colors, 360 degree reproduction of scene.</li><li>Multi-interface, multi-play, enjoy streaming experience.</li><li>Save energy, Save your money.</li><li>14 Months Warranty</li></ul><p>After Sales&nbsp;Information</p><p>&nbsp;</p><ul><li>Excellent quality, we dare to 14 Months warranty</li></ul><p><br></p>', '<ul><li><strong>A Class Display: 32 Inch (1366 X 768)</strong></li><li><strong>FRAMELESS DESIGN</strong></li><li><strong>Smart Android tv</strong></li><li><strong>Wi-fi Inbuilt</strong></li><li><strong>Two year warranty</strong></li><li><strong>Ports: USB(2), HDMI (3)、AV input(1)</strong></li><li><strong>Enjoy photos, music, and video on the screen.</strong></li><li><strong>Eco-Friendly</strong></li><li><strong>Automatic Volume Leveller (AVL)</strong></li><li><strong>FREE WALL BRACKET</strong></li><li><strong>FREE TV GUARD</strong></li><li><strong>FREE EXTENSION</strong></li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: VI505EA55TT7VNAFAMZ</li><li><strong>Weight (kg)</strong>: 7</li></ul><p><br></p>', '<ul><li><strong>FREE WALL BRACKET</strong></li><li><strong>FREE TV GUARD</strong></li><li><strong>FREE EXTENSION</strong></li></ul><p><br></p>', 0.00, 0, NULL, 'Vitron HTC3200S 32\" Inch Smart Android TV-Bluetooth, Built-in Wi-Fi, Netflix, YouTube, Appstore, Inbuilt Decoder+Free Bracket+TV Guard+Extension', 'Vitron, HTC3200S, 32\", Inch, Smart, Android, TV-Bluetooth,, Built-in, Wi-Fi,, Netflix,, YouTube,, Appstore,, Inbuilt, Decoder+Free, Bracket+TV, Guard+Extension', 'The&nbsp;HTC3200S VITRON&nbsp;32 inch, HD&nbsp;Smart Android LED TV&nbsp;features&nbsp;sharply and vividly conveys the colour of the image making it true to lif', 0, 'admin', 1, 12, 4, '2025-07-20 17:45:07', '2025-07-20 17:45:07', 7),
(12, 'Samsung 65Q60D 65 inches QLED Quantum Processor Lite 4K Tizen OS With 100% Color Volume With Quantum Dot, 4K Upscaling & AirSlim Design Smart TV New 2024', 'samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024', '<h2><strong>Samsung Tizen OS</strong></h2><h3><strong>Upscale your entertainment with Samsung Tizen OS</strong></h3><p>Get more out of your TV with the latest apps &amp; services on Samsung Tizen OS. Enjoy free live TV channels and thousands of movies with Samsung TV Plus, and seamless cloud gaming with Gaming Hub. You can even manage your daily activities with Daily+ and control your smart devices from your TV with SmartThings.</p>', '<ul><li>100% Color Volume with Quantum Dot</li><li>4K Upscaling</li><li>Samsung Tizen OS</li><li>AirSlim Design</li><li>50Hz Refresh Rate</li><li>Quantum Processor Lite 4K</li><li>Supreme UHD Dimming</li><li>Adaptive Sound</li><li>Tizen™ Smart TV</li><li>Multi-View Up to 2 videos</li><li>3 HDMI Ports</li><li>2 USB-A Ports</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: SA948EA4G5GBRNAFAMZ</li><li><strong>Model</strong>: QA65Q60DAUXKE</li><li><strong>Production Country</strong>: Egypt</li><li><strong>Weight (kg)</strong>: 28</li><li><strong>Warranty Address</strong>: &lt;p&gt;Vision Plaza, Mombasa Road, Nairobi Kenya.&lt;/p&gt;</li><li><strong>Warranty Type</strong>: Service Center - Nairobi</li></ul><p><br></p>', '<ul><li>Samsung 65Q60D</li><li>SolarCell Remote Control</li><li>Table Top Stand</li><li>Power Cable</li><li>User Manual</li><li>Warranty Card</li></ul><p><br></p>', 0.00, 0, NULL, 'Samsung 65Q60D 65 inches QLED Quantum Processor Lite 4K Tizen OS With 100% Color Volume With Quantum Dot, 4K Upscaling & AirSlim Design Smart TV New 2024', 'Samsung, 65Q60D, 65, inches, QLED, Quantum, Processor, Lite, 4K, Tizen, OS, With, 100%, Color, Volume, With, Quantum, Dot,, 4K, Upscaling, &, AirSlim, Design, Smart, TV, New, 2024', 'Samsung Tizen OSUpscale your entertainment with Samsung Tizen OSGet more out of your TV with the latest apps &amp; services on Samsung Tizen OS. Enjoy free live', 0, 'admin', 1, 7, 4, '2025-07-20 17:49:35', '2025-07-20 17:49:35', 7),
(13, 'Hisense 43A6K 43 Inch 4K UHD Smart TV (2YRs WRTY)', 'hisense-43a6k-43-inch-4k-uhd-smart-tv-2yrs-wrty', '<p>Hisense 43A6K 43 inch 4K UHD Smart TV. The Hisense 43A6K 43 inch TV will take your entertainment experience to the next level. With the 4K AI Upscaler upgrading any lower resolution to 4K-quality, Dolby Vision™ HDR and Precision Colour, the purest expression of color, pristine visual, and audio is your new standard.</p><p>Bluetooth Audio headphone capability lets you enjoy your experience on those quieter nights. Whether you’re a pro gamer or sports fanatic, Sports and Game Mode Plus gives you a shorter delay between input and reactions to on-screen developments, boosting your chances of scoring legendary wins by significantly reducing input lag.</p><p>Watch all your content, new and old in clear and detailed Ultra HD with the TV’s 4K AI Upscaler. Turn every night into a movie night with the inclusion of the Dolby Vision HDR visual and Dolby Digital audio enhancement technologies. Whether it’s Hollywood classics or the newest action flicks, sit back and watch them all, just like at the cinemas.</p><p>Aside from TV and movie watching, sports fans will love this TV’s Sports Mode. When in use, the Sports Mode enhances and optimizes visuals and audio to ensure you enjoy the crowd feeling; think sharper and brighter images and greater surround sound audio. Gamers will also benefit from the next-gen gaming experiences with Auto Low Latency Mode (ALLM) and Variable Refresh Rate (VRR) that provides competitive play.<strong>&nbsp;</strong></p><h3>Hisense 43A6K 43 inch 4K UHD Smart TV specs and price in Kenya</h3><p>&nbsp;&nbsp;Screen size43 inchesDisplay technologyLCD/ LED LitColor Depth8 bit + FRCMax white brightness300 cd/m²Refresh rate60Hz</p><p><strong>AUDIO</strong></p><p>Dolby DigitalYesAudio EnhancementDolby Audio, DTS Virtual-XSound output12W + 12WSpeaker number2 TweetersAudio equalizerYes</p><p><strong>CONNECTIVITY</strong></p><p>Radio Frequency Input1USB 2.02Bluetooth4.2HDMI Inputs3Wifi bands2.4, 5 GHzEthernet1CEC via HDMIYes</p><p><strong>SMART TV FEATURES</strong></p><p>Remote control AppYes phonePre-installed appsNetflix/ Youtube/ YouTube Kids/ Prime Video/ Hungama Play/ AppsNOW/ Icflix/ Media/ AccuWeather/ TV-Browser/ Game Center/ Any view Cast/ Icflix/ Toon Goggles/ PlexWeb browserOdinScreen mirroringAny view castFavorite channel listYes</p><p><strong>ADDITIONAL INFORMATION</strong></p><p>Remote controllerBluetooth controlStandby power0.5WDimensions without stand87x558x95mmDimensions with stand182x606x955mmWeight with stand–Weight without stand</p>', '<ul><li>Hisense 43” frameless 4k ultra hd smart tv</li><li>Frameless 4K ultra HD smart TV</li><li>Netflix</li><li>YouTube</li><li>Resolution: 3840 * 2160</li><li>HDR(High Dyanimic Range)</li><li>Quad core processor</li><li>4K upscaling</li><li>Dts studio&nbsp;sound</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: HI200EA5YZA07NAFAMZ</li><li><strong>Weight (kg)</strong>: 14</li><li><strong>Warranty Address</strong>: &lt;ul&gt;&lt;li&gt;&amp;nbsp;(Hisense Service Center, Moi Avenue)&lt;/li&gt;&lt;/ul&gt;</li></ul><p><br></p>', '<ul><li>43A6K 43 Inch Smart TV (2YRs WRTY)</li></ul><p><br></p>', 0.00, 0, NULL, 'Hisense 43A6K 43 Inch 4K UHD Smart TV (2YRs WRTY)', 'Hisense, 43A6K, 43, Inch, 4K, UHD, Smart, TV, (2YRs, WRTY)', 'Hisense 43A6K 43 inch 4K UHD Smart TV. The Hisense 43A6K 43 inch TV will take your entertainment experience to the next level. With the 4K AI Upscaler upgrading', 0, 'admin', 1, 14, 4, '2025-07-20 17:58:15', '2025-07-20 17:58:15', 7),
(14, 'Gld 32\" Inch Smart Android 11 TV NETFLIX,YOUTUBE-InbuiltDecoder+Bracket+Power Guard', 'gld-32-inch-smart-android-11-tv-netflixyoutube-inbuiltdecoderbracketpower-guard', '<p>The&nbsp;GLD&nbsp;32” HD Ready SMART ANDROID FRAMELESS TV, NETFLIX, YOUTUBE&nbsp;features&nbsp;sharply and vividly conveys the colour of the image making it true to life as you view it. Enjoy access&nbsp;to youtube, netflix, Facebook and many more apps with ease.&nbsp;It carries a powerful multi-surround sound effect that&nbsp;increases the excitement and takes your normal sports watching a pleasurable experience. This TV comes with Smart features like a dedicated web browser and popular apps like YouTube and a range of movie on demands services. Playback your stored content directly on the big screen for all to enjoy via a USB device or use it to record TV programmes and even pause live TV.&nbsp;Order for this&nbsp;<strong>GLD 32” HD Ready SMART ANDROID TV, NETFLIX, YOUTUBE&nbsp;&nbsp;</strong>online on JUMIA today and have it delivered straight to your door step.&nbsp;Comes with a FREE Wall Bracket+Tv Guard</p><p>The GLD&nbsp;32” HD Ready SMART ANDROID FRAMELESS TV, NETFLIX, YOUTUBE&nbsp;features sharply and vividly conveys the colour of the image making it true to life as you view it. Enjoy access to youtube, netflix, Facebook and many more apps with ease. It carries a powerful multi-surround sound effect that increases the excitement and takes your normal sports watching a pleasurable experience. This TV comes with Smart features like a dedicated web browser and popular apps like YouTube and a range of movie on demands services. Playback your stored content directly on the big screen for all to enjoy via a USB device or use it to record TV programmes and even pause live TV.&nbsp;</p><p>&nbsp;</p><p>Technical Specifications</p><p>Display</p><p>&nbsp;</p><p>Screen size: 32\"</p><p>Resolution: HD</p><p>Tv Technology: SMART Android LED</p><p>Connectivity</p><p>&nbsp;</p><p>Ports: USB(2), HDMI (3), AV input(1)</p><p>Cabinet Audio System&nbsp;</p><p>&nbsp;</p><p>Bright treble, full mid range, deep bass, bring you and your family a thrilling listening experience.</p><p>DNR Noise Reduction Technology ..</p><p>&nbsp;</p><p>Built-in 3D noise reduction make the signal stable and the picture realistic.</p><p>Additional Features</p><p>&nbsp;</p><p>HD - More Vivid Images&nbsp;</p><p>No light dot, restore natural colors, 360 degree reproduction of scene.</p><p>Multi-interface, multi-play, enjoy streaming experience.</p><p>Save energy, Save your money.</p><p>&nbsp;</p><p><br></p>', '<ul><li><strong>GLD 32”&nbsp;HD Ready SMART ANDROID FRAMELESS TV</strong></li><li><strong>HD ANDROID TV&nbsp;</strong></li><li><strong>Free Bracket and Extension</strong></li><li><strong>ANDROID TV</strong></li><li><strong>NETFLIX</strong></li><li><strong>YOUTUBE</strong></li><li><strong>GOOGLE Appstore</strong></li><li><strong>Inbuilt Decoder</strong></li><li><strong>HDMI PORTs</strong></li><li><strong>USB ports</strong></li><li><strong>WIDE VIEWING ANGLE (H/V) 178°/178°</strong></li><li><strong>IN-BUILT WI-FI AND ETHERNET</strong></li><li><strong>SMART VOLUME</strong></li><li><strong>DYNAMIC PICTURE ENHANCEMENT</strong></li><li><strong>BRIGHTNESS ENHANCEMENT</strong></li></ul><p><br></p><p><br></p>', '<ul><li><strong>SKU</strong>: GL585EA2N1L8TNAFAMZ</li><li><strong>Weight (kg)</strong>: 4</li></ul><p><br></p>', '<ul><li><strong>FREE FREE Wall Bracket+FREE Power Guard</strong></li></ul><p><br></p>', 0.00, 0, NULL, 'Gld 32\" Inch Smart Android 11 TV NETFLIX,YOUTUBE-InbuiltDecoder+Bracket+Power Guard', 'Gld, 32\", Inch, Smart, Android, 11, TV, NETFLIX,YOUTUBE-InbuiltDecoder+Bracket+Power, Guard', 'The&nbsp;GLD&nbsp;32” HD Ready SMART ANDROID FRAMELESS TV, NETFLIX, YOUTUBE&nbsp;features&nbsp;sharply and vividly conveys the colour of the image making it t', 0, 'admin', 1, 15, 4, '2025-07-20 18:03:04', '2025-07-20 18:03:04', 7),
(15, 'Syinix Tv 32 inch Digital 32E62M Inbuilt decoder with iCast Screen Mirroring', 'syinix-tv-32-inch-digital-32e62m-inbuilt-decoder-with-icast-screen-mirroring', '<p>The Syinix 32-inch Smart TV (Model: 32S65) is designed with the thinnest SLED technology, boasting an ultra-slim 6mm frame. It is a smart Android TV that comes with access to the Playstore, allowing users to download apps such as YouTube, Netflix, and more. Equipped with inbuilt Wi-Fi and Ethernet, it offers seamless connectivity. The TV delivers premium picture quality with Full HD 1080p resolution and features an inbuilt decoder for free-to-air channels. Its frameless design adds a modern touch to any space, making it a sleek and functional entertainment centerpiece.</p><p>i-Cast&nbsp;<a href=\"https://ke.syinix.com/\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: inherit;\"><strong>TV</strong></a></p><p>Screen Mirroring without Wifi, Data or Cable</p><p>HIFI Speakers</p><p>ImerSound</p><p>HD Ready</p><p>A Panel</p><p>Frameless Design</p><p>DVB T2+S2</p><p>Inbuilt decoder</p><p>Free-to-air channels</p><p>Plug n Play</p><p>HDMI, USB, RF, AV Ports</p>', '<ul><li>i-Cast&nbsp;<a href=\"https://ke.syinix.com/\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: inherit;\"><strong>TV</strong></a></li><li>Screen Mirroring without Wifi, Data or Cable</li><li>HIFI Speakers</li><li>ImerSound</li><li>HD Ready</li><li>A Panel</li><li>Frameless Design</li><li>DVB T2+S2</li><li>Inbuilt decoder</li><li>Free-to-air channels</li><li>Plug n Play</li><li>HDMI, USB, RF, AV Ports</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: SY793EA5BHW7INAFAMZ</li><li><strong>Weight (kg)</strong>: 7</li></ul><p><br></p>', '<p>1x tv</p><p>1x user manual</p><p>1x warranty card&nbsp;</p>', 13999.00, 0, 18.00, 'Syinix Tv 32 inch Digital 32E62M Inbuilt decoder with iCast Screen Mirroring', 'Syinix, Tv, 32, inch, Digital, 32E62M, Inbuilt, decoder, with, iCast, Screen, Mirroring', 'The Syinix 32-inch Smart TV (Model: 32S65) is designed with the thinnest SLED technology, boasting an ultra-slim 6mm frame. It is a smart Android TV that comes ', 3, 'admin', 1, 17, 4, '2025-07-20 20:24:10', '2025-07-20 20:24:10', 7),
(17, 'CTC 32\" inches,Smart/Android 12 TV,Bluetooth/NETFLIX/YOUTUBE+14 MONTHS WARRANTY', 'ctc-32-inchessmartandroid-12-tvbluetoothnetflixyoutube14-months-warranty', '<p><span style=\"color: rgb(49, 49, 51);\">Get the all new CT32F1S, Android ,Built in Appstore CTC Smart Digital TV available on Jumia Kenya! CTC is a professional home appliance brand. Since its foundation, it adheres to its ultimate goal of letting people’s life smarter and more easily. With its design style and always making it better, CTC continuously pursues innovative functional design and a more exquisite detail of its products. Its finish design is embedded with understated and simple lines, thus it can help its customers restore a cozy family atmosphere. Order for this&nbsp;</span><strong style=\"color: rgb(49, 49, 51);\">CTC 32\" Smart Digital TV online at Jumia&nbsp;</strong><span style=\"color: rgb(49, 49, 51);\">Kenya and have it delivered right at your doorstep.</span></p>', '<ul><li><strong>A Class Display: 32 Inch (1366 X 768)</strong></li><li><strong>FRAMELESS DESIGN</strong></li><li><strong>BLUETOOTH</strong></li><li><strong>Smart Android tv</strong></li><li><strong>Wi-fi Inbuilt</strong></li><li><strong>Two year warranty</strong></li><li><strong>Ports: USB(2), HDMI (3)、AV input(1)</strong></li><li><strong>Enjoy photos, music, and video on the screen.</strong></li><li><strong>Eco-Friendly</strong></li><li><strong>Automatic Volume Leveller (AVL)</strong></li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: CT303EA3D2E05NAFAMZ</li><li><strong>Weight (kg)</strong>: 5</li></ul><p><br></p>', NULL, 24000.00, 10, 53.00, 'CTC 32\" inches,Smart/Android 12 TV,Bluetooth/NETFLIX/YOUTUBE+14 MONTHS WARRANTY', 'CTC, 32\", inches,Smart/Android, 12, TV,Bluetooth/NETFLIX/YOUTUBE+14, MONTHS, WARRANTY', 'Get the all new CT32F1S, Android ,Built in Appstore CTC Smart Digital TV available on Jumia Kenya! CTC is a professional home appliance brand. Since its foundat', 1, 'admin', 1, 18, 4, '2025-07-20 20:45:23', '2025-07-20 20:45:23', 7),
(18, 'Apple MacBook Pro 13\" Core I5 2.4GHz 8GB RAM, 500GB HDD (2012) Silver Refurbished', 'apple-macbook-pro-13-core-i5-24ghz-8gb-ram-500gb-hdd-2012-silver-refurbished', '<h2><strong>Refurbished Apple MacBook Pro 8,1 13-inch, i5, 8GB RAM, 500GB HDD, Intel HD 4000, B, (Mid- 2012)</strong></h2><p>&nbsp;</p><p>Product Description Be it the design, the performance or just the little features, you are definitely going to fall in love with this Apple Macbook Pro.</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/3a7e2799f3932b68986cdb594add4438.jpg\" alt=\"image\"></span>Captivating Display</p><p>With support for millions of colours, the brilliant LED backlit 33.782 cm display of this Macbook Pro is sure to keep your eyes locked on to it all along. The display features a resolution of 1280 x 800 pixels and is also developed with IPS technology.</p><p>LED Backlit 280 x 800 pixels Power-loaded Performance</p><p>Experience extremely smooth and fast performance with the 2.5 GHz Intel i5 Dual Core processor that powers this laptop. With the option of Turbo Boost up to 3.1 GHz and a 4 GB DDR3 RAM, the device delivers incredible speeds while running applications. The Intel HD Graphics 4000 provides fluid graphics while watching videos and gaming.</p><p>Processor Processor 4 GB DDR3 RAM Storage</p><p>Store all your favourite movies, music, picture, applications and more with the 500 GB storage space this Macbook Pro offers.</p><p>500 GB HDD</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/d66257986da949dc46aa2c2af4cf07cf.jpg\" alt=\"image\"></span>Multimedia</p><p>Integrated with a 720p FaceTime HD web camera, you are sure to love video chatting using this laptop. The device comes with stereo speakers for excellent sound along with a headphone port in case you want to connect your headphones. The omnidirectional microphone is designed to pick up even when you speak in a low tone of voice.</p><p>HD Web Camera Built-in Mic Stereo Speakers Easy Connectivity</p><p>The device comes with Wi-Fi to connect to the internet, along with 2 USB 3.0 ports and Bluetooth v4.0 to transfer data to other devices effortlessly. The laptop also features a FireWire 800 and Thunderbolt port, while the SDXC card slot makes it convenient to transfer pictures and videos from your memory card to the laptop.</p><p>Wi-Fi 2 x USB 3.0 Bluetooth v4.0</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/ab73cacfc1c3f5ec888429c248333805.jpg\" alt=\"image\"></span>Lasting Power</p><p>Providing up to an incredible 7 hours back up, you can wirelessly browse the web or even watch movies without interruptions. The laptop features a lithium-polymer battery. You can charge the device using the 60 watts MagSafe power adapter from a power source.</p><p>&nbsp;</p><p>&nbsp;</p><h2><strong>Specifications</strong></h2><h3><strong>General</strong></h3><ul><li>Sales Package - MacBook Pro, AC Wall Plug, Power Lead, Magsafe 2 Power Adapter</li><li>Model Number - A1278</li><li>Part Number - MD101HN/A</li><li>Model Name - Macbook Pro</li><li>Series - Macbook Pro</li><li>Color - Silver</li><li>Type - Laptop</li><li>Suitable For - Travel &amp; Business, Processing &amp; Multitasking</li><li>Battery Backup - Up to 7 hours</li><li>Power Supply - 60W MagSafe Power Adapter</li></ul><p><strong>Processor And Memory Features</strong></p><ul><li>Processor Brand - Intel</li><li>Processor Name - Core i5</li><li>RAM - 8 GB DDR3</li><li>HDD Capacity - 500 GB</li><li>Processor Variant - 3210M</li><li>Clock Speed - 2.5 GHz Turbo boost upto 3.1 Ghz</li><li>RAM Frequency - 1600 MHz</li><li>Cache - 3 MB</li><li>RPM - 5400</li><li>Graphic Processor - Intel Integrated HD 4000</li><li>Operating System - Mac OS El Capitan 10.11.6</li><li>Port And Slot Features</li><li>Firewire Port</li><li>Mic In</li><li>USB Port</li><li>2 x USB 3.0</li><li>HDMI Port</li><li>Multi Card Slot</li><li>SDXC Card Slot</li></ul><p><strong>Display And Audio Features</strong></p><ul><li>Screen Size - 33.78 cm (13.3 inch)</li><li>Screen Resolution - 1280 x 800 pixels</li><li>Screen Type - HD LED Backlit Glossy Widescreen Display</li><li>Speakers - Yes</li><li>Internal Mic - Dual Microphones</li><li>Sound Properties - Stereo Speakers with Subwoofer</li></ul><p>​<strong>Connectivity Features</strong></p><ul><li>Wireless LAN - IEEE 802.11a/b/g/n</li><li>Bluetooth - v4.0</li><li>Ethernet - 10/100/1000BASE-T Gigabit Ethernet</li><li>Dimensions</li><li>Dimensions - 325 x 227 x 24.1 mm</li><li>Weight - 2.06 kg</li><li>Additional Features</li><li>Web Camera</li><li>720p FaceTime HD Camera</li><li>Lock Port</li><li>Kensington Lock Slot</li><li>Keyboard</li><li>Full-size Backlit Keyboard</li><li>Pointer Device</li><li>Multi-Touch trackpad for precise cursor control</li></ul><p><br></p>', '<h2><strong>Refurbished Apple MacBook Pro 8,1 13-inch, i5, 8GB RAM, 500GB HDD, Intel HD 4000, B, (Mid- 2012).</strong></h2><p>&nbsp;</p><p>Product Description Be it the design, the performance or just the little features, you are definitely going to fall in love with this Apple Macbook Pro.</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/3a7e2799f3932b68986cdb594add4438.jpg\" alt=\"image\"></span>Captivating Display</p><p>With support for millions of colours, the brilliant LED backlit 33.782 cm display of this Macbook Pro is sure to keep your eyes locked on to it all along. The display features a resolution of 1280 x 800 pixels and is also developed with IPS technology.</p><p>LED Backlit 280 x 800 pixels Power-loaded Performance</p><p>Experience extremely smooth and fast performance with the 2.5 GHz Intel i5 Dual Core processor that powers this laptop. With the option of Turbo Boost up to 3.1 GHz and a 4 GB DDR3 RAM, the device delivers incredible speeds while running applications. The Intel HD Graphics 4000 provides fluid graphics while watching videos and gaming.</p><p>Processor Processor 4 GB DDR3 RAM Storage</p><p>Store all your favourite movies, music, picture, applications and more with the 500 GB storage space this Macbook Pro offers.</p><p>500 GB HDD</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/d66257986da949dc46aa2c2af4cf07cf.jpg\" alt=\"image\"></span>Multimedia</p><p>Integrated with a 720p FaceTime HD web camera, you are sure to love video chatting using this laptop. The device comes with stereo speakers for excellent sound along with a headphone port in case you want to connect your headphones. The omnidirectional microphone is designed to pick up even when you speak in a low tone of voice.</p><p>HD Web Camera Built-in Mic Stereo Speakers Easy Connectivity</p><p>The device comes with Wi-Fi to connect to the internet, along with 2 USB 3.0 ports and Bluetooth v4.0 to transfer data to other devices effortlessly. The laptop also features a FireWire 800 and Thunderbolt port, while the SDXC card slot makes it convenient to transfer pictures and videos from your memory card to the laptop.</p><p>Wi-Fi 2 x USB 3.0 Bluetooth v4.0</p><p><span style=\"color: rgb(49, 49, 51);\"><img src=\"https://ke.jumia.is/cms/external/pet/AP044CL1EDIZ0NAFAMZ/ab73cacfc1c3f5ec888429c248333805.jpg\" alt=\"image\"></span>Lasting Power</p><p>Providing up to an incredible 7 hours back up, you can wirelessly browse the web or even watch movies without interruptions. The laptop features a lithium-polymer battery. You can charge the device using the 60 watts MagSafe power adapter from a power source.</p><p>&nbsp;</p><p>&nbsp;</p><h2><strong>Specifications</strong></h2><h3><strong>General</strong></h3><ul><li>Sales Package - MacBook Pro, AC Wall Plug, Power Lead, Magsafe 2 Power Adapter</li><li>Model Number - A1278</li><li>Part Number - MD101HN/A</li><li>Model Name - Macbook Pro</li><li>Series - Macbook Pro</li><li>Color - Silver</li><li>Type - Laptop</li><li>Suitable For - Travel &amp; Business, Processing &amp; Multitasking</li><li>Battery Backup - Up to 7 hours</li><li>Power Supply - 60W MagSafe Power Adapter</li></ul><p><strong>Processor And Memory Features</strong></p><ul><li>Processor Brand - Intel</li><li>Processor Name - Core i5</li><li>RAM - 8 GB DDR3</li><li>HDD Capacity - 500 GB</li><li>Processor Variant - 3210M</li><li>Clock Speed - 2.5 GHz Turbo boost upto 3.1 Ghz</li><li>RAM Frequency - 1600 MHz</li><li>Cache - 3 MB</li><li>RPM - 5400</li><li>Graphic Processor - Intel Integrated HD 4000</li><li>Operating System - Mac OS El Capitan 10.11.6</li><li>Port And Slot Features</li><li>Firewire Port</li><li>Mic In</li><li>USB Port</li><li>2 x USB 3.0</li><li>HDMI Port</li><li>Multi Card Slot</li><li>SDXC Card Slot</li></ul><p><strong>Display And Audio Features</strong></p><ul><li>Screen Size - 33.78 cm (13.3 inch)</li><li>Screen Resolution - 1280 x 800 pixels</li><li>Screen Type - HD LED Backlit Glossy Widescreen Display</li><li>Speakers - Yes</li><li>Internal Mic - Dual Microphones</li><li>Sound Properties - Stereo Speakers with Subwoofer</li></ul><p>​<strong>Connectivity Features</strong></p><ul><li>Wireless LAN - IEEE 802.11a/b/g/n</li><li>Bluetooth - v4.0</li><li>Ethernet - 10/100/1000BASE-T Gigabit Ethernet</li><li>Dimensions</li><li>Dimensions - 325 x 227 x 24.1 mm</li><li>Weight - 2.06 kg</li><li>Additional Features</li><li>Web Camera</li><li>720p FaceTime HD Camera</li><li>Lock Port</li><li>Kensington Lock Slot</li><li>Keyboard</li><li>Full-size Backlit Keyboard</li><li>Pointer Device</li><li>Multi-Touch trackpad for precise cursor control</li></ul><p><br></p>', '<ul><li><strong>SKU</strong>: AP044CL23MIHQNAFAMZ</li><li><strong>Weight (kg)</strong>: 0.04</li><li><strong>Warranty Address</strong>: &lt;p&gt;family trade center stall f23 along tom mboya street Nairobi&amp;nbsp;&lt;/p&gt;</li><li><strong>Warranty Type</strong>: Replacement by Vendor|Repair by Vendor</li></ul><p><br></p>', NULL, 30000.00, 1, 18.00, 'Apple MacBook Pro 13\" Core I5 2.4GHz 8GB RAM, 500GB HDD (2012) Silver Refurbished', 'Apple, MacBook, Pro, 13\", Core, I5, 2.4GHz, 8GB, RAM,, 500GB, HDD, (2012), Silver, Refurbished', 'Refurbished Apple MacBook Pro 8,1 13-inch, i5, 8GB RAM, 500GB HDD, Intel HD 4000, B, (Mid- 2012)&nbsp;Product Description Be it the design, the performance or j', 0, 'admin', 1, 19, 4, '2025-07-22 19:12:17', '2025-07-22 19:12:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 8, 'products/skyworth-43g6500g-43-4k-uhd-google-tv-2025-black-1yr-wrty-687d4925722af.webp', NULL, 1, 0, '2025-07-20 16:53:09', '2025-07-20 16:53:09'),
(2, 8, 'products/skyworth-43g6500g-43-4k-uhd-google-tv-2025-black-1yr-wrty-687d49257c870.webp', NULL, 0, 1, '2025-07-20 16:53:09', '2025-07-20 16:53:09'),
(3, 8, 'products/skyworth-43g6500g-43-4k-uhd-google-tv-2025-black-1yr-wrty-687d49258518a.webp', NULL, 0, 2, '2025-07-20 16:53:09', '2025-07-20 16:53:09'),
(4, 9, 'products/skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty-687d517295037.webp', NULL, 1, 0, '2025-07-20 17:28:34', '2025-07-20 17:28:34'),
(5, 9, 'products/skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty-687d51729fc68.webp', NULL, 0, 1, '2025-07-20 17:28:34', '2025-07-20 17:28:34'),
(6, 9, 'products/skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty-687d5172a83f7.webp', NULL, 0, 2, '2025-07-20 17:28:34', '2025-07-20 17:28:34'),
(7, 9, 'products/skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty-687d5172b241f.webp', NULL, 0, 3, '2025-07-20 17:28:34', '2025-07-20 17:28:34'),
(8, 9, 'products/skyworth-43e3500g-43-inches-full-hd-2024-frameless-google-tv-black-1yr-wrty-687d5172bb36b.webp', NULL, 0, 4, '2025-07-20 17:28:34', '2025-07-20 17:28:34'),
(9, 10, 'products/tcl-43-inch-4k-ultra-hdsmart-google-tvnetflixappstoregifts-687d53ec861d9.webp', NULL, 1, 0, '2025-07-20 17:39:08', '2025-07-20 17:39:08'),
(10, 10, 'products/tcl-43-inch-4k-ultra-hdsmart-google-tvnetflixappstoregifts-687d53ec942ae.webp', NULL, 0, 1, '2025-07-20 17:39:08', '2025-07-20 17:39:08'),
(11, 10, 'products/tcl-43-inch-4k-ultra-hdsmart-google-tvnetflixappstoregifts-687d53eca3b85.webp', NULL, 0, 2, '2025-07-20 17:39:08', '2025-07-20 17:39:08'),
(12, 10, 'products/tcl-43-inch-4k-ultra-hdsmart-google-tvnetflixappstoregifts-687d53ecafc62.webp', NULL, 0, 3, '2025-07-20 17:39:08', '2025-07-20 17:39:08'),
(13, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d5553c6267.webp', NULL, 1, 0, '2025-07-20 17:45:07', '2025-07-20 17:45:07'),
(14, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d5553d4b12.webp', NULL, 0, 1, '2025-07-20 17:45:07', '2025-07-20 17:45:07'),
(15, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d5553e11c9.webp', NULL, 0, 2, '2025-07-20 17:45:07', '2025-07-20 17:45:07'),
(16, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d5553eda17.webp', NULL, 0, 3, '2025-07-20 17:45:07', '2025-07-20 17:45:07'),
(17, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d55540916f.webp', NULL, 0, 4, '2025-07-20 17:45:08', '2025-07-20 17:45:08'),
(18, 11, 'products/vitron-htc3200s-32-inch-smart-android-tv-bluetooth-built-in-wi-fi-netflix-youtube-appstore-inbuilt-decoderfree-brackettv-guardextension-687d555418592.webp', NULL, 0, 5, '2025-07-20 17:45:08', '2025-07-20 17:45:08'),
(19, 12, 'products/samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024-687d56616529e.webp', NULL, 0, 0, '2025-07-20 17:49:37', '2025-07-20 17:49:37'),
(20, 12, 'products/samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024-687d566174b8e.webp', NULL, 1, 1, '2025-07-20 17:49:37', '2025-07-20 17:49:37'),
(21, 12, 'products/samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024-687d56617f676.webp', NULL, 0, 2, '2025-07-20 17:49:37', '2025-07-20 17:49:37'),
(22, 12, 'products/samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024-687d566189897.webp', NULL, 0, 3, '2025-07-20 17:49:37', '2025-07-20 17:49:37'),
(23, 12, 'products/samsung-65q60d-65-inches-qled-quantum-processor-lite-4k-tizen-os-with-100-color-volume-with-quantum-dot-4k-upscaling-airslim-design-smart-tv-new-2024-687d566193c9a.webp', NULL, 0, 4, '2025-07-20 17:49:37', '2025-07-20 17:49:37'),
(24, 13, 'products/hisense-43a6k-43-inch-4k-uhd-smart-tv-2yrs-wrty-687d58677a242.webp', NULL, 1, 0, '2025-07-20 17:58:15', '2025-07-20 17:58:15'),
(25, 13, 'products/hisense-43a6k-43-inch-4k-uhd-smart-tv-2yrs-wrty-687d5867836e4.webp', NULL, 0, 1, '2025-07-20 17:58:15', '2025-07-20 17:58:15'),
(26, 13, 'products/hisense-43a6k-43-inch-4k-uhd-smart-tv-2yrs-wrty-687d58678c96f.webp', NULL, 0, 2, '2025-07-20 17:58:15', '2025-07-20 17:58:15'),
(27, 13, 'products/hisense-43a6k-43-inch-4k-uhd-smart-tv-2yrs-wrty-687d58679552e.webp', NULL, 0, 3, '2025-07-20 17:58:15', '2025-07-20 17:58:15'),
(28, 14, 'products/gld-32-inch-smart-android-11-tv-netflixyoutube-inbuiltdecoderbracketpower-guard-687d5988f09db.webp', NULL, 1, 0, '2025-07-20 18:03:04', '2025-07-20 18:03:04'),
(29, 14, 'products/gld-32-inch-smart-android-11-tv-netflixyoutube-inbuiltdecoderbracketpower-guard-687d5989074a2.webp', NULL, 0, 1, '2025-07-20 18:03:05', '2025-07-20 18:03:05'),
(30, 14, 'products/gld-32-inch-smart-android-11-tv-netflixyoutube-inbuiltdecoderbracketpower-guard-687d5989122a1.webp', NULL, 0, 2, '2025-07-20 18:03:05', '2025-07-20 18:03:05'),
(31, 14, 'products/gld-32-inch-smart-android-11-tv-netflixyoutube-inbuiltdecoderbracketpower-guard-687d59891ba0c.webp', NULL, 0, 3, '2025-07-20 18:03:05', '2025-07-20 18:03:05'),
(32, 15, 'products/syinix-tv-32-inch-digital-32e62m-inbuilt-decoder-with-icast-screen-mirroring-687d7a9a9c44b.webp', NULL, 1, 0, '2025-07-20 20:24:10', '2025-07-20 20:24:10'),
(36, 17, 'products/ctc-32-inchessmartandroid-12-tvbluetoothnetflixyoutube14-months-warranty-687d7f93372c1.webp', NULL, 1, 0, '2025-07-20 20:45:23', '2025-07-20 20:45:23'),
(37, 17, 'products/ctc-32-inchessmartandroid-12-tvbluetoothnetflixyoutube14-months-warranty-687d7f933a096.webp', NULL, 0, 1, '2025-07-20 20:45:23', '2025-07-20 20:45:23'),
(38, 17, 'products/ctc-32-inchessmartandroid-12-tvbluetoothnetflixyoutube14-months-warranty-687d7f9343598.webp', NULL, 0, 2, '2025-07-20 20:45:23', '2025-07-20 20:45:23'),
(39, 18, 'products/apple-macbook-pro-13-core-i5-24ghz-8gb-ram-500gb-hdd-2012-silver-refurbished-68800cc15e382.webp', NULL, 1, 0, '2025-07-22 19:12:17', '2025-07-22 19:12:17'),
(40, 18, 'products/apple-macbook-pro-13-core-i5-24ghz-8gb-ram-500gb-hdd-2012-silver-refurbished-68800cc167a72.webp', NULL, 0, 1, '2025-07-22 19:12:17', '2025-07-22 19:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_values`
--

CREATE TABLE `product_variant_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2025-07-03 12:03:22', '2025-07-03 12:03:22'),
(2, 'superuser', 'web', '2025-07-04 15:03:47', '2025-07-13 14:35:58'),
(5, 'manager', 'web', '2025-07-05 01:36:15', '2025-07-13 14:36:09'),
(6, 'salesman', 'web', '2025-07-05 04:25:58', '2025-07-13 14:36:21'),
(7, 'seller', 'web', '2025-07-12 14:17:35', '2025-07-13 14:36:31'),
(9, 'user', 'web', '2025-07-14 06:15:57', '2025-07-14 06:15:57');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 5),
(1, 6),
(2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

CREATE TABLE `seller_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `agreed_to_privacy` tinyint(1) NOT NULL DEFAULT 0,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `identification_type` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `passport_number` varchar(255) DEFAULT NULL,
  `primary_product_category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `owner_first_name` varchar(255) DEFAULT NULL,
  `owner_last_name` varchar(255) DEFAULT NULL,
  `owner_email` varchar(255) DEFAULT NULL,
  `owner_phone` varchar(255) DEFAULT NULL,
  `vat_registered` varchar(255) DEFAULT NULL,
  `vat_number` varchar(255) DEFAULT NULL,
  `company_legal_name` varchar(255) DEFAULT NULL,
  `ke_business_reg_number` varchar(255) DEFAULT NULL,
  `non_ke_business_reg_number` varchar(255) DEFAULT NULL,
  `ke_id_number` varchar(255) DEFAULT NULL,
  `passport_number_sp` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `monthly_revenue` varchar(255) DEFAULT NULL,
  `owns_physical_store` varchar(255) DEFAULT NULL,
  `retail_store_count` int(11) DEFAULT NULL,
  `is_supplier_to_retailers` varchar(255) DEFAULT NULL,
  `operates_other_marketplaces` varchar(255) DEFAULT NULL,
  `marketplace_details` text DEFAULT NULL,
  `supplier_retail_count` int(11) DEFAULT NULL,
  `product_count` int(11) DEFAULT NULL,
  `stock_handling` varchar(255) DEFAULT NULL,
  `product_website` varchar(255) DEFAULT NULL,
  `product_origin` varchar(255) DEFAULT NULL,
  `owned_brands` varchar(255) DEFAULT NULL,
  `licensed_brands` varchar(255) DEFAULT NULL,
  `product_branding` varchar(255) DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `business_summary` text DEFAULT NULL,
  `discovery_source` varchar(255) DEFAULT NULL,
  `referrer_email` varchar(255) DEFAULT NULL,
  `share_with_distributors` varchar(255) DEFAULT NULL,
  `is_submitted` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = pending, 1 = approved, 2 = rejected',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `status_reason` text DEFAULT NULL COMMENT 'Reason for rejection or status explanation',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `business_type`, `agreed_to_privacy`, `first_name`, `last_name`, `contact_email`, `contact_phone`, `identification_type`, `id_number`, `passport_number`, `primary_product_category`, `description`, `owner_first_name`, `owner_last_name`, `owner_email`, `owner_phone`, `vat_registered`, `vat_number`, `company_legal_name`, `ke_business_reg_number`, `non_ke_business_reg_number`, `ke_id_number`, `passport_number_sp`, `country`, `nationality`, `monthly_revenue`, `owns_physical_store`, `retail_store_count`, `is_supplier_to_retailers`, `operates_other_marketplaces`, `marketplace_details`, `supplier_retail_count`, `product_count`, `stock_handling`, `product_website`, `product_origin`, `owned_brands`, `licensed_brands`, `product_branding`, `social_media`, `business_summary`, `discovery_source`, `referrer_email`, `share_with_distributors`, `is_submitted`, `status`, `verified`, `status_reason`, `created_at`, `updated_at`) VALUES
(11, 'international', 1, 'DAVID', 'KIAMA', 'nelson.masibo@kenyawebsolutions.co.ke', '+254722921960', 'id_number', '592', NULL, 'Gaming', 'Iusto pariatur Inve', 'Ashely', 'Jefferson', 'dofoj@mailinator.com', '+1 (166) 815-8594', 'yes', '68566', 'Hiteh solutions', '678', '448', '447', '780', 'Consequatur quidem e', 'Faroe Islands', 'KSh 20,000 - KSh 50,000', 'yes', 0, 'yes', 'yes', 'jumia', 0, 1, 'full_stock', 'https://www.qocyfetyryra.tv', 'mixed', 'Jenette Hart', 'Emmanuel Moses', 'combination', 'https://www.cygaxyco.tv', NULL, 'Referral', 'nelson.masibo@kenyaweb.com', 'yes', 0, 1, 0, 'Approved', '2025-07-12 11:11:17', '2025-07-26 09:46:44'),
(12, 'business', 1, 'Michael', 'Terik', 'nelsonmasibo6@gmail.com', '+254704122212', 'passport', '622', '1194444', 'Toys', 'Reiciendis modi quo', 'Jason', 'Francis', 'dazyfipor@mailinator.com', '+1 (496) 144-4234', 'yes', '68566', 'Walton Pennington Traders', '40', '897', '503', '910', 'Aspernatur illum ex', 'Armenia', 'KSh 20,000 - KSh 50,000', 'yes', 80, 'yes', 'yes', 'jumia', 10, 134, 'partial_stock', 'https://www.wajuzinif.org.uk', 'mixed', 'Daria Morse', 'Dahlia Knight', 'branded', 'https://www.pixujoqas.me', NULL, 'Online Advertisement', NULL, 'no', 0, 0, 0, NULL, '2025-07-12 11:21:35', '2025-07-12 11:21:35');

-- --------------------------------------------------------

--
-- Table structure for table `seller_application_images`
--

CREATE TABLE `seller_application_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `seller_application_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seller_application_images`
--

INSERT INTO `seller_application_images` (`id`, `seller_application_id`, `path`, `original_name`, `created_at`, `updated_at`) VALUES
(4, 12, '/storage/seller_images/fe9GRiR2iOuMt05nkcXTdJVQVzVMCUvqUYHCcaWm.jpg', 'fe9GRiR2iOuMt05nkcXTdJVQVzVMCUvqUYHCcaWm.jpg', '2025-07-12 11:21:35', '2025-07-12 11:21:35'),
(5, 12, '/storage/seller_images/PwJHRs7Uk6O1YsyWuW7M2gM7YM132kqOzQr0Y6XP.png', 'PwJHRs7Uk6O1YsyWuW7M2gM7YM132kqOzQr0Y6XP.png', '2025-07-12 11:21:35', '2025-07-12 11:21:35'),
(6, 12, '/storage/seller_images/CS0gSOlBP9t8MngunSmhFkLWz20naYxuSmz9uzz6.png', 'CS0gSOlBP9t8MngunSmhFkLWz20naYxuSmz9uzz6.png', '2025-07-12 11:21:35', '2025-07-12 11:21:35');

-- --------------------------------------------------------

--
-- Table structure for table `seller_documents`
--

CREATE TABLE `seller_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seller_documents`
--

INSERT INTO `seller_documents` (`id`, `user_id`, `document_type_id`, `file_path`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 7, 1, '/storage/seller_documents/eYF3AVlkIeUPB95WEkUu30zATLCrJLBCU6RjtPaO.pdf', 'rejected', 'fake document, sisi sio wajnga bana', '2025-07-15 04:41:03', '2025-07-22 17:40:52'),
(2, 7, 3, '/storage/seller_documents/SRWWwueGtkPoiazOfZK3frRRoHAUbsFwpNLDlJYO.pdf', 'approved', NULL, '2025-07-15 04:58:00', '2025-07-15 06:37:36'),
(3, 7, 4, '/storage/seller_documents/fSxUWQgGT3GkWv9Vlk8KUDvmJj6SxTilpHAzIYDy.pdf', 'approved', NULL, '2025-07-15 05:02:02', '2025-07-15 05:51:27'),
(4, 7, 5, '/storage/seller_documents/y7hFz04ABjvCoU1SO3eJ1i6V9LwVKqcyDyD2Huku.pdf', 'rejected', 'dfvfdfddffdfdfddffd', '2025-07-15 11:51:57', '2025-07-15 11:53:13');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Hk561rq1Od8KgvEtXPankKajRAqM5kdVf6AZ3urJ', 7, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoienJkdGQzbWR3OVR0V1l0dHFkRkd4Y1BwS0F0Y3NxS0NKNFREaGJHaSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTQ3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvcmVzZXQtcGFzc3dvcmQvYzkxNDcxMjk1MWE0YjRhM2NkZDg1MzdjN2ZhNjZkNjY0ZjdmMjI5MGRjOGZlZmYxMzA5NzhkMGQyNDQ0Nzg3ZD9lbWFpbD1uZWxzb24ubWFzaWJvJTQwa2VueWF3ZWJzb2x1dGlvbnMuY28ua2UiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo3O30=', 1753533864),
('QxAa2QMluA4OGZ7MKJ1d91mHa01fTsrUeAA2tfUU', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMUhoN1NNb2dQNzF5NlFkMERFOWtlVE95c1VBSEhzNzJybHhmRnJlQyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1753534356);

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `slug`, `active`, `created_at`, `updated_at`) VALUES
(1, 4, 'Laptops', 'laptops', 1, '2025-07-16 05:52:12', '2025-07-16 05:52:12'),
(2, 4, 'Computers & accesories', 'computers-accesories', 1, '2025-07-16 05:52:27', '2025-07-16 05:52:27'),
(3, 4, 'Computer Data storage', 'computer-data-storage', 1, '2025-07-16 05:52:53', '2025-07-16 05:52:53'),
(4, 4, 'Refurb corner', 'refurb-corner', 1, '2025-07-16 05:53:04', '2025-07-16 05:53:04'),
(5, 4, 'Computer components', 'computer-components', 1, '2025-07-16 05:53:43', '2025-07-16 05:53:43'),
(7, 1, 'TVs', 'tvs', 1, '2025-07-20 08:33:01', '2025-07-20 08:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(255) NOT NULL,
  `unit_type_id` bigint(20) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `symbol`, `unit_type_id`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Kilogram', 'KG', 1, 1, '2025-07-16 12:32:53', '2025-07-16 12:32:53'),
(2, 'Littres', 'L', 1, 1, '2025-07-16 12:33:20', '2025-07-16 12:33:20'),
(4, 'pieces', 'PCS', 4, 1, '2025-07-20 08:34:50', '2025-07-20 08:34:50');

-- --------------------------------------------------------

--
-- Table structure for table `unit_types`
--

CREATE TABLE `unit_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_types`
--

INSERT INTO `unit_types` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Weight', 'weight', '2025-07-16 12:05:08', '2025-07-16 12:05:08'),
(2, 'Volume', 'volume', '2025-07-16 12:05:57', '2025-07-16 12:05:57'),
(4, 'count', 'count', '2025-07-20 08:34:32', '2025-07-20 08:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `seller_application_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `seller_application_id`) VALUES
(1, 'Masibo', 'nelsonmasibo6@gmail.com', '2025-07-03 12:03:22', '$2y$12$uoGbzpkhcTOvuJTLpBf1mumy3RvH3zV0YJDCv7nc0hK9E.MC7p0jG', 'JqpNUKNjAPFMGbuXAZdWphUMINWOpQxxxSfkB53WPyLvV7u7BP3FfnlI1I6u', '2025-07-03 12:03:22', '2025-07-26 08:56:23', NULL),
(2, 'Kush', 'kushdinesh98@gmail.com', NULL, '$2y$12$SqW8U5/YuoDJVsTVzUcN4uQPBtDwwM5bejTZTIRkNRilx0KujVPTW', NULL, '2025-07-03 14:37:40', '2025-07-03 14:37:40', NULL),
(7, 'DAVID KIAMA', 'nelson.masibo@kenyawebsolutions.co.ke', NULL, '$2y$12$SDomCh5eDmEF2zpq1SV2FesMUMvS4a/Z9Wqr.n366UvJxMNLTxQVi', 'Nn5N2yEzKxLjrzTk3TGZeSHaqapJhg9Nrke02i2DYCCOVOwdE6RdNPMitwlT', '2025-07-12 15:11:10', '2025-07-26 09:43:34', 11),
(8, 'Neilah Nafula', 'nelson.masibo@kenyaweb.com', NULL, '$2y$12$0AQ4iTahz8HRTiEAJLya5.f1s7dJJo9F2SnqAw2Ev/z0OYZIT674i', NULL, '2025-07-14 06:19:56', '2025-07-14 06:19:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `variants`
--

CREATE TABLE `variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_category_id` bigint(20) UNSIGNED NOT NULL,
  `value` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `variants`
--

INSERT INTO `variants` (`id`, `variant_category_id`, `value`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 1, 'Black', 1, '2025-07-20 16:53:09', '2025-07-20 16:53:09'),
(4, 2, '43\"', 1, '2025-07-20 16:53:09', '2025-07-20 16:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `variant_categories`
--

CREATE TABLE `variant_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `variant_categories`
--

INSERT INTO `variant_categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Color', '2025-07-18 06:37:52', '2025-07-18 06:37:52'),
(2, 'Size', '2025-07-18 06:58:56', '2025-07-18 06:58:56'),
(4, 'test', '2025-07-22 17:55:53', '2025-07-22 17:55:53');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `slug`, `location`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Komarok', 'komarok', 'Nairobi', 1, '2025-07-15 12:24:07', '2025-07-15 12:24:07'),
(2, 'Kipevu', 'kipevu', 'Nakuru', 1, '2025-07-15 12:52:58', '2025-07-15 12:52:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_slug_unique` (`slug`),
  ADD KEY `brands_subcategory_id_foreign` (`subcategory_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `products_brand_id_foreign` (`brand_id`),
  ADD KEY `products_unit_id_foreign` (`unit_id`),
  ADD KEY `products_subcategory_id_foreign` (`subcategory_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_variant_values`
--
ALTER TABLE `product_variant_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variant_values_product_id_variant_id_unique` (`product_id`,`variant_id`),
  ADD KEY `product_variant_values_variant_id_foreign` (`variant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seller_application_images`
--
ALTER TABLE `seller_application_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_application_images_seller_application_id_foreign` (`seller_application_id`);

--
-- Indexes for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `seller_documents_user_doc_unique` (`user_id`,`document_type_id`),
  ADD KEY `seller_documents_document_type_id_foreign` (`document_type_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subcategories_slug_unique` (`slug`),
  ADD KEY `subcategories_category_id_foreign` (`category_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `units_unit_type_id_foreign` (`unit_type_id`);

--
-- Indexes for table `unit_types`
--
ALTER TABLE `unit_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_types_name_unique` (`name`),
  ADD UNIQUE KEY `unit_types_slug_unique` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `fk_seller_application` (`seller_application_id`);

--
-- Indexes for table `variants`
--
ALTER TABLE `variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variants_variant_category_id_value_unique` (`variant_category_id`,`value`);

--
-- Indexes for table `variant_categories`
--
ALTER TABLE `variant_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variant_categories_name_unique` (`name`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouses_slug_unique` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `product_variant_values`
--
ALTER TABLE `product_variant_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `seller_application_images`
--
ALTER TABLE `seller_application_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `seller_documents`
--
ALTER TABLE `seller_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unit_types`
--
ALTER TABLE `unit_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `variants`
--
ALTER TABLE `variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `variant_categories`
--
ALTER TABLE `variant_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant_values`
--
ALTER TABLE `product_variant_values`
  ADD CONSTRAINT `product_variant_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_variant_values_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_application_images`
--
ALTER TABLE `seller_application_images`
  ADD CONSTRAINT `seller_application_images_seller_application_id_foreign` FOREIGN KEY (`seller_application_id`) REFERENCES `seller_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_documents`
--
ALTER TABLE `seller_documents`
  ADD CONSTRAINT `seller_documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_unit_type_id_foreign` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_seller_application` FOREIGN KEY (`seller_application_id`) REFERENCES `seller_applications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_seller_application_id_foreign` FOREIGN KEY (`seller_application_id`) REFERENCES `seller_applications` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `variants`
--
ALTER TABLE `variants`
  ADD CONSTRAINT `variants_variant_category_id_foreign` FOREIGN KEY (`variant_category_id`) REFERENCES `variant_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
