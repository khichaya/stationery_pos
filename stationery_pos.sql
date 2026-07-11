-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2026 at 06:28 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stationery_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `backups`
--

INSERT INTO `backups` (`id`, `filename`, `status`, `created_at`, `updated_at`) VALUES
(1, 'bayane_backup_auto_2026_07_04_195206.sql', 'success', '2026-07-04 18:52:07', '2026-07-04 18:52:07'),
(2, 'bayane_backup_auto_2026_07_05_101202.sql', 'success', '2026-07-05 09:12:03', '2026-07-05 09:12:03'),
(3, 'bayane_backup_auto_2026_07_08_204112.sql', 'success', '2026-07-08 19:41:13', '2026-07-08 19:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1783083060),
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1783083060;', 1783083060);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'ادوات مدرسية', '2026-07-02 19:32:08', '2026-07-02 19:32:08'),
(2, 'اعشاب', '2026-07-05 18:05:00', '2026-07-05 18:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `total_debt` decimal(50,0) DEFAULT NULL,
  `observation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `balance`, `created_at`, `updated_at`, `total_debt`, `observation`) VALUES
(3, 'بن علي الطاهر ', '0772154622', 0.00, '2026-07-03 07:01:01', '2026-07-05 10:15:18', 0, NULL),
(7, 'خيشة يحيى ', '0665936276', 0.00, '2026-07-03 08:15:53', '2026-07-05 06:18:41', 0, 'مشتريات فاتورة رقم #11'),
(9, 'بن علي الطاهر ', '0772154622', 0.00, '2026-07-03 09:04:20', '2026-07-05 10:15:23', 0, NULL),
(10, 'مكتبة هيمة ', '06655884215', 0.00, '2026-07-03 09:05:09', '2026-07-05 10:15:07', 0, NULL),
(11, 'مكتبة الباحث ', '0665266276', 0.00, '2026-07-03 09:07:03', '2026-07-05 10:15:13', 0, NULL),
(12, 'حسان', '06666669565', 0.00, '2026-07-03 09:08:53', '2026-07-05 10:14:49', 0, NULL),
(13, 'بالعيد', '066296236929', 0.00, '2026-07-03 09:09:23', '2026-07-05 10:15:02', 0, NULL),
(16, 'حسان', '06666669565', 0.00, '2026-07-03 11:49:34', '2026-07-05 10:15:29', 0, 'مشتريات فاتورة رقم #19'),
(17, 'خيشة يحيى ', '0665936276', 0.00, '2026-07-03 11:52:34', '2026-07-05 06:18:36', 0, 'مشتريات فاتورة رقم #20'),
(18, 'عبد الكريم تليه ', '0665331245', 0.00, '2026-07-03 11:53:33', '2026-07-05 10:14:56', 0, 'سلف'),
(19, 'خيشة يحيى ', '0665936276', 0.00, '2026-07-04 17:05:33', '2026-07-05 06:18:21', 0, 'مشتريات فاتورة رقم #27'),
(21, 'خيشة يحيى ', '0665936276', 0.00, '2026-07-04 21:33:58', '2026-07-05 06:18:26', 0, 'مشتريات فاتورة رقم #38');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `amount`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 5000.00, 'تصليح السيارة', '2026-07-03 10:10:00', '2026-07-03 10:10:00'),
(2, 1, 500.00, 'فطور ', '2026-07-03 11:41:36', '2026-07-03 11:41:36'),
(4, 1, 1500.00, 'مصروف ', '2026-07-04 21:35:49', '2026-07-04 21:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institution_settings`
--

CREATE TABLE `institution_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `nif` varchar(255) DEFAULT NULL,
  `nis` varchar(255) DEFAULT NULL,
  `rc` varchar(255) DEFAULT NULL,
  `ai` varchar(255) DEFAULT NULL,
  `invoice_footer` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `institution_settings`
--

INSERT INTO `institution_settings` (`id`, `name`, `manager_name`, `phone`, `email`, `address`, `nif`, `nis`, `rc`, `ai`, `invoice_footer`, `logo_path`, `created_at`, `updated_at`) VALUES
(1, 'مكتبة السلام', 'تارزي تلية ', '0665936276', 'khichaya@gmail.com', 'الوادي ', '123456859565959', '26262629262656565', '2652656565188', '192623662', 'مرحبا بكم دائما وابدا', 'institution/9jov8VV61KSO0QMBHviG9rHjKsJlnNX3EfgvZsuw.jpg', '2026-07-04 18:34:29', '2026-07-04 19:58:58');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
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
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_02_175716_create_settings_table', 1),
(5, '2026_07_02_175717_create_roles_table', 1),
(6, '2026_07_02_175718_add_role_id_to_users_table', 1),
(7, '2026_07_02_175718_create_categories_table', 1),
(8, '2026_07_02_175719_create_units_table', 1),
(9, '2026_07_02_175720_create_storage_locations_table', 1),
(10, '2026_07_02_175721_create_products_table', 1),
(11, '2026_07_02_175722_create_suppliers_table', 1),
(12, '2026_07_02_175723_create_purchases_table', 1),
(13, '2026_07_02_175724_create_purchase_items_table', 1),
(14, '2026_07_02_175725_create_customers_table', 1),
(15, '2026_07_02_175726_create_sales_table', 1),
(16, '2026_07_02_175727_create_sale_items_table', 1),
(17, '2026_07_02_175729_create_expenses_table', 1),
(18, '2026_07_02_175730_create_stock_movements_table', 1),
(19, '2026_07_02_175734_create_backups_table', 1),
(20, '2026_07_02_200755_create_product_barcodes_table', 2),
(21, '2026_07_02_213215_add_box_barcode_to_products_table', 2),
(22, '2026_07_03_095852_create_services_table', 3),
(23, '2026_07_04_164240_add_role_and_permissions_to_users_table', 4),
(24, '2026_07_04_193335_create_institution_settings_table', 5),
(25, '2026_07_05_101408_create_backups_table', 0),
(26, '2026_07_05_101408_create_cache_table', 0),
(27, '2026_07_05_101408_create_cache_locks_table', 0),
(28, '2026_07_05_101408_create_categories_table', 0),
(29, '2026_07_05_101408_create_customers_table', 0),
(30, '2026_07_05_101408_create_expenses_table', 0),
(31, '2026_07_05_101408_create_failed_jobs_table', 0),
(32, '2026_07_05_101408_create_institution_settings_table', 0),
(33, '2026_07_05_101408_create_job_batches_table', 0),
(34, '2026_07_05_101408_create_jobs_table', 0),
(35, '2026_07_05_101408_create_password_reset_tokens_table', 0),
(36, '2026_07_05_101408_create_product_barcodes_table', 0),
(37, '2026_07_05_101408_create_products_table', 0),
(38, '2026_07_05_101408_create_purchase_items_table', 0),
(39, '2026_07_05_101408_create_purchases_table', 0),
(40, '2026_07_05_101408_create_roles_table', 0),
(41, '2026_07_05_101408_create_sale_items_table', 0),
(42, '2026_07_05_101408_create_sales_table', 0),
(43, '2026_07_05_101408_create_services_table', 0),
(44, '2026_07_05_101408_create_sessions_table', 0),
(45, '2026_07_05_101408_create_settings_table', 0),
(46, '2026_07_05_101408_create_stock_movements_table', 0),
(47, '2026_07_05_101408_create_storage_locations_table', 0),
(48, '2026_07_05_101408_create_suppliers_table', 0),
(49, '2026_07_05_101408_create_units_table', 0),
(50, '2026_07_05_101408_create_users_table', 0),
(51, '2026_07_05_101411_add_foreign_keys_to_expenses_table', 0),
(52, '2026_07_05_101411_add_foreign_keys_to_product_barcodes_table', 0),
(53, '2026_07_05_101411_add_foreign_keys_to_products_table', 0),
(54, '2026_07_05_101411_add_foreign_keys_to_purchase_items_table', 0),
(55, '2026_07_05_101411_add_foreign_keys_to_purchases_table', 0),
(56, '2026_07_05_101411_add_foreign_keys_to_sale_items_table', 0),
(57, '2026_07_05_101411_add_foreign_keys_to_sales_table', 0),
(58, '2026_07_05_101411_add_foreign_keys_to_services_table', 0),
(59, '2026_07_05_101411_add_foreign_keys_to_stock_movements_table', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `box_barcode` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `storage_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_service` tinyint(1) DEFAULT 0,
  `package_items_count` int(11) NOT NULL DEFAULT 1,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_1` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_2` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_3` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_4` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` decimal(12,2) NOT NULL DEFAULT 5.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `barcode`, `box_barcode`, `name`, `category_id`, `unit_id`, `storage_location_id`, `is_service`, `package_items_count`, `purchase_price`, `price_1`, `price_2`, `price_3`, `price_4`, `discount`, `current_stock`, `min_stock_alert`, `created_at`, `updated_at`, `supplier_id`, `location`, `image`) VALUES
(1, '210390082760', '629262626262626', 'كراس رسم ', 1, 1, NULL, 0, 242424, 1100.06, 10.08, 100.00, 1000.00, 10000.00, 0.00, 0.00, 5.00, '2026-07-02 20:39:45', '2026-07-05 10:13:56', 1, '242', 'products/rwWN6MM7sEQaAhAL65luGomsFJnvQrKwbuOGy5lf.jpg'),
(3, '210107869313', NULL, 'كراس 120', 1, 1, NULL, 0, 1, 1500.00, 3000.00, 3500.00, 3600.00, 3900.00, 0.00, -2.00, 5.00, '2026-07-03 10:20:17', '2026-07-05 21:07:03', 1, 'َ623', NULL),
(4, '210836427605', NULL, 'كراس رسم 120', 1, 1, NULL, 0, 1, 100.00, 150.00, 160.00, 180.00, 190.00, 0.00, 0.00, 3.00, '2026-07-03 11:40:20', '2026-07-05 10:14:11', 1, 'A4C1R1', 'products/0D1goBqAXiqKzQVtayiwjBURHGQK0tluKOttdhHN.jpg'),
(5, '210730464428', NULL, 'كراس رسم 32', 1, 1, NULL, 0, 1, 50.00, 100.00, 90.00, 80.00, 65.00, 0.00, 0.00, 5.00, '2026-07-03 11:50:21', '2026-07-05 10:14:18', 1, 'A4C2R5', 'products/VWijJxGgNt4MLIGRRMxDpFb9Wd7S9EET28DwyRf8.jpg'),
(6, '210538810245', NULL, 'عسل والمرة والسدر ', 2, 1, NULL, 0, 1, 1000.00, 200.00, 200.00, -0.06, 0.00, 0.00, -2.00, 5.00, '2026-07-05 18:05:24', '2026-07-05 18:06:39', 1, 'كرش يحي', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_barcodes`
--

CREATE TABLE `product_barcodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `receipt_number` varchar(255) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `purchase_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin', '2026-07-02 19:15:11', '2026-07-02 19:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) NOT NULL DEFAULT 'cash',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `user_id`, `total_amount`, `paid_amount`, `discount_amount`, `payment_method`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 20.00, 20.00, 0.00, 'cash', '2026-07-02 20:40:47', '2026-07-02 20:40:47'),
(2, NULL, 1, 150.00, 150.00, 0.00, 'cash', '2026-07-03 06:48:10', '2026-07-03 06:48:10'),
(3, NULL, 1, 150.00, 150.00, 0.00, 'cash', '2026-07-03 06:48:48', '2026-07-03 06:48:48'),
(7, NULL, 1, 600.00, 0.00, 0.00, 'debt', '2026-07-03 07:22:07', '2026-07-03 07:22:07'),
(8, NULL, 1, 15000.00, 0.00, 0.00, 'debt', '2026-07-03 07:54:32', '2026-07-03 07:54:32'),
(14, NULL, 1, 0.00, 0.00, 0.00, 'full', '2026-07-03 10:20:36', '2026-07-03 10:20:36'),
(19, 12, 1, 150.00, 0.00, 0.00, 'debt', '2026-07-03 11:49:34', '2026-07-03 11:49:34'),
(20, NULL, 1, 950.00, 142.00, 0.00, 'partial', '2026-07-03 11:52:33', '2026-07-03 11:52:33'),
(26, NULL, 4, 200.00, 200.00, 0.00, 'full', '2026-07-04 17:04:08', '2026-07-04 17:04:08'),
(27, 7, 1, 1000.00, 0.00, 0.00, 'debt', '2026-07-04 17:05:33', '2026-07-04 17:05:33'),
(29, NULL, 1, 500.00, 500.00, 0.00, 'full', '2026-07-04 19:59:29', '2026-07-04 19:59:29'),
(30, NULL, 1, 10.00, 10.00, 0.00, 'full', '2026-07-04 20:06:29', '2026-07-04 20:06:29'),
(31, NULL, 1, 10.00, 10.00, 0.00, 'full', '2026-07-04 20:17:28', '2026-07-04 20:17:28'),
(33, NULL, 1, 20.00, 20.00, 0.00, 'full', '2026-07-04 20:20:54', '2026-07-04 20:20:54'),
(35, NULL, 1, 10.00, 10.00, 0.00, 'full', '2026-07-04 20:33:55', '2026-07-04 20:33:55'),
(36, NULL, 1, 10.00, 10.00, 0.00, 'full', '2026-07-04 20:34:35', '2026-07-04 20:34:35'),
(38, NULL, 1, 1500.00, 0.00, 0.00, 'debt', '2026-07-04 21:33:58', '2026-07-04 21:33:58'),
(39, NULL, 1, 100.00, 100.00, 0.00, 'full', '2026-07-05 08:11:49', '2026-07-05 08:11:49'),
(40, NULL, 1, 540100.00, 540100.00, 0.00, 'full', '2026-07-05 08:36:00', '2026-07-05 08:36:00'),
(41, NULL, 1, 3000.00, 3000.00, 0.00, 'full', '2026-07-05 08:40:47', '2026-07-05 08:40:47'),
(42, NULL, 1, 100.00, 100.00, 0.00, 'full', '2026-07-05 08:41:10', '2026-07-05 08:41:10'),
(43, NULL, 1, 3250.00, 3250.00, 0.00, 'full', '2026-07-05 08:43:49', '2026-07-05 08:43:49'),
(44, NULL, 1, 3000.00, 3000.00, 0.00, 'full', '2026-07-05 08:45:19', '2026-07-05 08:45:19'),
(45, NULL, 1, 3000.00, 3000.00, 0.00, 'full', '2026-07-05 08:46:56', '2026-07-05 08:46:56'),
(46, NULL, 1, 3000.00, 3000.00, 0.00, 'full', '2026-07-05 08:49:13', '2026-07-05 08:49:13'),
(47, NULL, 1, 12300.00, 12300.00, 0.00, 'full', '2026-07-05 08:49:36', '2026-07-05 08:49:36'),
(48, NULL, 1, 1431.36, 1431.36, 0.00, 'full', '2026-07-05 08:52:05', '2026-07-05 08:52:05'),
(49, NULL, 1, 200.00, 200.00, 0.00, 'full', '2026-07-05 18:06:39', '2026-07-05 18:06:39'),
(50, NULL, 1, 3000.00, 3000.00, 0.00, 'full', '2026-07-05 21:07:03', '2026-07-05 21:07:03');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `is_returned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `is_returned`, `created_at`, `updated_at`, `product_name`) VALUES
(1, 1, 1, 500.00, 10.00, 20.00, 0, '2026-07-02 20:40:47', '2026-07-02 20:40:47', NULL),
(6, 7, 1, 60.00, 10.00, 600.00, 0, '2026-07-03 07:22:07', '2026-07-03 07:22:07', 'كراس رسم '),
(13, 14, 3, 10.00, 0.00, 0.00, 0, '2026-07-03 10:20:36', '2026-07-03 10:20:36', 'كراس 120'),
(19, 19, 4, 1.00, 150.00, 150.00, 0, '2026-07-03 11:49:34', '2026-07-03 11:49:34', 'كراس رسم 120'),
(20, 20, 1, 20.00, 10.00, 200.00, 0, '2026-07-03 11:52:33', '2026-07-03 11:52:33', 'كراس رسم '),
(21, 20, 4, 5.00, 150.00, 750.00, 0, '2026-07-03 11:52:33', '2026-07-03 11:52:33', 'كراس رسم 120'),
(26, 26, NULL, 2.00, 100.00, 200.00, 0, '2026-07-04 17:04:08', '2026-07-04 17:04:08', 'فتوكبي '),
(27, 27, NULL, 20.00, 50.00, 1000.00, 0, '2026-07-04 17:05:33', '2026-07-04 17:05:33', 'طباعة الوان '),
(29, 29, NULL, 50.00, 10.00, 500.00, 0, '2026-07-04 19:59:29', '2026-07-04 19:59:29', 'فتوكبي '),
(30, 30, NULL, 1.00, 10.00, 10.00, 0, '2026-07-04 20:06:29', '2026-07-04 20:06:29', 'فتوكبي'),
(31, 31, NULL, 1.00, 10.00, 10.00, 0, '2026-07-04 20:17:28', '2026-07-04 20:17:28', 'فتوكبي '),
(34, 33, NULL, 2.00, 10.00, 20.00, 0, '2026-07-04 20:20:54', '2026-07-04 20:20:54', 'قلم احمر '),
(36, 35, NULL, 1.00, 10.00, 10.00, 0, '2026-07-04 20:33:55', '2026-07-04 20:33:55', 'فتوكبي'),
(37, 36, NULL, 1.00, 10.00, 10.00, 0, '2026-07-04 20:34:35', '2026-07-04 20:34:35', 'فوتكبي '),
(39, 38, NULL, 50.00, 30.00, 1500.00, 0, '2026-07-04 21:33:58', '2026-07-04 21:33:58', 'فتوكبي كولار'),
(40, 39, 1, 10.00, 10.00, 100.00, 0, '2026-07-05 08:11:49', '2026-07-05 08:11:49', 'كراس رسم '),
(41, 40, 3, 150.00, 3000.00, 450000.00, 0, '2026-07-05 08:36:00', '2026-07-05 08:36:00', 'كراس 120'),
(42, 40, 4, 600.00, 150.00, 90000.00, 0, '2026-07-05 08:36:00', '2026-07-05 08:36:00', 'كراس رسم 120'),
(43, 40, 5, 1.00, 100.00, 100.00, 0, '2026-07-05 08:36:00', '2026-07-05 08:36:00', 'كراس رسم 32'),
(44, 41, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 08:40:47', '2026-07-05 08:40:47', 'كراس 120'),
(45, 42, 5, 1.00, 100.00, 100.00, 0, '2026-07-05 08:41:10', '2026-07-05 08:41:10', 'كراس رسم 32'),
(46, 43, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 08:43:49', '2026-07-05 08:43:49', 'كراس 120'),
(47, 43, 5, 1.00, 100.00, 100.00, 0, '2026-07-05 08:43:49', '2026-07-05 08:43:49', 'كراس رسم 32'),
(48, 43, 4, 1.00, 150.00, 150.00, 0, '2026-07-05 08:43:49', '2026-07-05 08:43:49', 'كراس رسم 120'),
(49, 44, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 08:45:19', '2026-07-05 08:45:19', 'كراس 120'),
(50, 45, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 08:46:56', '2026-07-05 08:46:56', 'كراس 120'),
(51, 46, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 08:49:13', '2026-07-05 08:49:13', 'كراس 120'),
(52, 47, 5, 123.00, 100.00, 12300.00, 0, '2026-07-05 08:49:36', '2026-07-05 08:49:36', 'كراس رسم 32'),
(53, 48, 1, 142.00, 10.08, 1431.36, 0, '2026-07-05 08:52:05', '2026-07-05 08:52:05', 'كراس رسم '),
(54, 49, 6, 1.00, 200.00, 200.00, 0, '2026-07-05 18:06:39', '2026-07-05 18:06:39', 'عسل والمرة والسدر '),
(55, 50, 3, 1.00, 3000.00, 3000.00, 0, '2026-07-05 21:07:03', '2026-07-05 21:07:03', 'كراس 120');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_type` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_type`, `price`, `user_id`, `customer_id`, `payment_method`, `paid_amount`, `created_at`, `updated_at`) VALUES
(1, 'كتابة بحث علمي', 2500.00, 1, 3, 'partial', 1500.00, '2026-07-03 09:04:20', '2026-07-03 09:04:20'),
(2, 'تنسيق خطابات ومستندات', 8500.00, 1, NULL, 'partial', 2500.00, '2026-07-03 09:05:09', '2026-07-03 09:05:09'),
(3, 'تسجيل في المنصات', 9000.00, 1, 3, 'full', 9000.00, '2026-07-03 09:06:28', '2026-07-03 09:06:28'),
(4, 'طباعة وتصوير مستندات', 2600.00, 1, NULL, 'full', 2600.00, '2026-07-03 09:06:38', '2026-07-03 09:06:38'),
(5, 'كتابة وثائق وبحوث', 6200.00, 1, NULL, 'partial', 1500.00, '2026-07-03 09:06:48', '2026-07-03 09:07:03'),
(6, 'تنسيق ملفات وتصاميم', 5000.00, 1, NULL, 'full', 5000.00, '2026-07-03 09:49:28', '2026-07-03 09:49:28'),
(8, 'تسجيل في المنصات', 300.00, 1, NULL, 'full', 300.00, '2026-07-03 11:41:07', '2026-07-03 11:41:07'),
(9, 'طباعة وتصوير مستندات', 150.00, 1, NULL, 'full', 150.00, '2026-07-03 11:51:44', '2026-07-03 11:51:44');

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
('CH7u2IhTe8kstgBIeXb6HhXpIqiJvPJTrCxFzaoX', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ0TU5xN3RIYkhUbVJMT0c0Q1lxNWI3UE1pbmtKRDdtd0RtNnFZelA2IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2Rhc2hib2FyZCIsInJvdXRlIjoiZGFzaGJvYXJkIn0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==', 1783546311),
('e4YBOV3ALxdpgvG4qCQuPAwWPxc11NHPjHgjuSmv', 1, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJQcEt4S1Q2YW5LQnhBNERQcFZoQVlwdDdIaGJaRnlrSVh1bjJWRlpVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4xMDM6ODAwMFwvZGFzaGJvYXJkIiwicm91dGUiOiJkYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1783540380);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'backup_config', '{\"auto_backup\":true,\"backup_time\":\"10:00\",\"backup_frequency\":\"daily\",\"backup_destination\":\"local\"}', NULL, '2026-07-04 18:50:36'),
(2, 'last_auto_backup_date', '2026-07-08', NULL, '2026-07-08 19:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('purchase','sale','return_customer','return_supplier','damage','adjustment') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `user_id`, `type`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'sale', -500.00, '2026-07-02 20:40:47', '2026-07-02 20:40:47'),
(4, 1, 1, 'sale', -39.00, '2026-07-03 06:49:24', '2026-07-03 06:49:24'),
(6, 1, 1, 'sale', -60.00, '2026-07-03 07:22:07', '2026-07-03 07:22:07'),
(9, 1, 1, 'sale', -10.00, '2026-07-03 08:15:13', '2026-07-03 08:15:13'),
(10, 1, 1, 'sale', -16.00, '2026-07-03 08:15:53', '2026-07-03 08:15:53'),
(11, 1, 1, 'sale', -20.00, '2026-07-03 08:28:29', '2026-07-03 08:28:29'),
(12, 1, 1, 'sale', -20.00, '2026-07-03 08:44:02', '2026-07-03 08:44:02'),
(13, 3, 1, 'sale', -10.00, '2026-07-03 10:20:36', '2026-07-03 10:20:36'),
(17, 1, 1, 'sale', -20.00, '2026-07-03 11:42:12', '2026-07-03 11:42:12'),
(18, 4, 1, 'sale', -1.00, '2026-07-03 11:42:12', '2026-07-03 11:42:12'),
(19, 4, 1, 'sale', -1.00, '2026-07-03 11:49:34', '2026-07-03 11:49:34'),
(20, 1, 1, 'sale', -20.00, '2026-07-03 11:52:33', '2026-07-03 11:52:33'),
(21, 4, 1, 'sale', -5.00, '2026-07-03 11:52:33', '2026-07-03 11:52:33'),
(22, 1, 1, 'sale', -10.00, '2026-07-05 08:11:49', '2026-07-05 08:11:49'),
(23, 3, 1, 'sale', -150.00, '2026-07-05 08:36:00', '2026-07-05 08:36:00'),
(24, 4, 1, 'sale', -600.00, '2026-07-05 08:36:00', '2026-07-05 08:36:00'),
(25, 5, 1, 'sale', -1.00, '2026-07-05 08:36:00', '2026-07-05 08:36:00'),
(26, 3, 1, 'sale', -1.00, '2026-07-05 08:40:47', '2026-07-05 08:40:47'),
(27, 5, 1, 'sale', -1.00, '2026-07-05 08:41:10', '2026-07-05 08:41:10'),
(28, 3, 1, 'sale', -1.00, '2026-07-05 08:43:49', '2026-07-05 08:43:49'),
(29, 5, 1, 'sale', -1.00, '2026-07-05 08:43:49', '2026-07-05 08:43:49'),
(30, 4, 1, 'sale', -1.00, '2026-07-05 08:43:49', '2026-07-05 08:43:49'),
(31, 3, 1, 'sale', -1.00, '2026-07-05 08:45:19', '2026-07-05 08:45:19'),
(32, 3, 1, 'sale', -1.00, '2026-07-05 08:46:56', '2026-07-05 08:46:56'),
(33, 3, 1, 'sale', -1.00, '2026-07-05 08:49:13', '2026-07-05 08:49:13'),
(34, 5, 1, 'sale', -123.00, '2026-07-05 08:49:36', '2026-07-05 08:49:36'),
(35, 1, 1, 'sale', -142.00, '2026-07-05 08:52:05', '2026-07-05 08:52:05'),
(36, 6, 1, 'sale', -1.00, '2026-07-05 18:06:39', '2026-07-05 18:06:39'),
(37, 3, 1, 'sale', -1.00, '2026-07-05 21:07:03', '2026-07-05 21:07:03');

-- --------------------------------------------------------

--
-- Table structure for table `storage_locations`
--

CREATE TABLE `storage_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `balance`, `created_at`, `updated_at`) VALUES
(1, 'حسان ', '0665936276', 0.00, '2026-07-02 20:24:43', '2026-07-02 20:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'وحدة', '2026-07-02 19:32:21', '2026-07-02 19:32:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `permissions`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'المدير العام', 'khichaya@gmail.com', 'manager', '\"[\\\"view_dashboard_stats\\\",\\\"access_pos\\\",\\\"manage_products\\\",\\\"manage_services\\\",\\\"manage_expenses\\\",\\\"manage_debts\\\",\\\"database_backup\\\"]\"', NULL, '$2y$12$9Hljl2gdhPPYUz01DlFHQOR3Fnvl7RrKIfLMazzzUXI5L7fElaWh2', 'RbQwqp1fCa9HUPIOP6HagPxePuoDYec1mnHruTLQEEd9MEbOsNq8I0vVHT9Q', '2026-07-02 19:15:12', '2026-07-04 15:55:58'),
(4, 'يحيى خيشة ', 'infolab392020@gmail.com', 'cashier', '\"[\\\"access_pos\\\",\\\"manage_services\\\",\\\"manage_products\\\"]\"', NULL, '$2y$12$ZywhiKDfMnH3PpgQzCKJ8etJ8ais.2I8W.s1aJB0nwBaunMk12tli', 'jGLbOcy638ouhgJwMJjXvA3uXLDfEdpBGYMeFD8TKlz9uCh1BhPMvYpoFySN', '2026-07-04 16:11:06', '2026-07-04 17:54:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_name_index` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customers_name_index` (`name`),
  ADD KEY `customers_phone_index` (`phone`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expenses_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `institution_settings`
--
ALTER TABLE `institution_settings`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_barcode_unique` (`barcode`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_unit_id_foreign` (`unit_id`),
  ADD KEY `products_storage_location_id_foreign` (`storage_location_id`),
  ADD KEY `products_name_index` (`name`),
  ADD KEY `products_current_stock_index` (`current_stock`),
  ADD KEY `products_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_barcodes_barcode_unique` (`barcode`),
  ADD KEY `product_barcodes_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchases_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchases_user_id_foreign` (`user_id`),
  ADD KEY `purchases_receipt_number_index` (`receipt_number`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_customer_id_foreign` (`customer_id`),
  ADD KEY `sales_user_id_foreign` (`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_items_sale_id_foreign` (`sale_id`),
  ADD KEY `sale_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_user_id_foreign` (`user_id`),
  ADD KEY `services_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_user_id_foreign` (`user_id`);

--
-- Indexes for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `storage_locations_name_index` (`name`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_name_index` (`name`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institution_settings`
--
ALTER TABLE `institution_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `storage_locations`
--
ALTER TABLE `storage_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_storage_location_id_foreign` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD CONSTRAINT `product_barcodes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `services_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
