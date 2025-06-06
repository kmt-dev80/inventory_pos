-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2025 at 06:21 PM
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
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`id`, `brand_name`, `details`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Oneplus', 'All Oneplus Device', 0, NULL, '2025-06-01 10:30:03', '2025-06-01 10:30:11'),
(2, 'Samsung', 'All Products', 0, NULL, '2025-06-01 16:57:52', '2025-06-01 16:57:52'),
(3, 'realme', 'All realme phones', 0, NULL, '2025-06-06 11:22:19', '2025-06-06 11:22:34');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`, `details`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'All Electronics', 0, NULL, '2025-06-01 10:28:25', '2025-06-01 10:28:25');

-- --------------------------------------------------------

--
-- Table structure for table `child_category`
--

CREATE TABLE `child_category` (
  `id` int(11) NOT NULL,
  `sub_category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `child_category`
--

INSERT INTO `child_category` (`id`, `sub_category_id`, `category_name`, `details`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Android', 'All Android', 0, NULL, '2025-06-01 10:29:07', '2025-06-01 10:29:07');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_adjustments`
--

CREATE TABLE `inventory_adjustments` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `adjustment_type` enum('add','remove') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `child_category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sell_price` decimal(10,2) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `barcode`, `category_id`, `sub_category_id`, `child_category_id`, `brand_id`, `price`, `sell_price`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(4, 'Onelplus Nord  200 5G', '12345678910', 1, 1, 1, 1, 14000.00, 15000.00, 0, NULL, '2025-06-04 12:10:45', '2025-06-04 12:10:45'),
(5, 'Oneplus Nord 3', '45334662626', 1, 1, 1, 1, 25000.00, 28000.00, 0, NULL, '2025-06-04 12:11:28', '2025-06-04 12:11:28');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `payment_method` varchar(20) DEFAULT 'cash',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `discount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `supplier_id`, `reference_no`, `purchase_date`, `payment_method`, `payment_status`, `discount`, `subtotal`, `vat`, `total`, `user_id`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(10, 3, 'PUR-68403844132B1', '2025-06-04', 'bank_transfer', 'paid', 14000.00, 280000.00, 26600.00, 292600.00, 1, 0, NULL, '2025-06-04 12:12:52', '2025-06-04 12:14:29'),
(11, 2, 'PUR-6840387753C33', '2025-06-04', 'cash', 'paid', 35000.00, 500000.00, 46500.00, 511500.00, 1, 0, NULL, '2025-06-04 12:13:43', '2025-06-04 12:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(5,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `discount`, `subtotal`, `vat`, `total_price`, `created_at`) VALUES
(11, 10, 4, 20, 14000.00, 5.00, 0.00, 10.00, 292600.00, '2025-06-04 12:12:52'),
(12, 11, 5, 20, 25000.00, 7.00, 0.00, 10.00, 511500.00, '2025-06-04 12:13:43');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_payment`
--

CREATE TABLE `purchase_payment` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `purchase_return_id` int(11) DEFAULT NULL,
  `type` enum('payment','return') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit','card','bank_transfer') NOT NULL DEFAULT 'cash',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_payment`
--

INSERT INTO `purchase_payment` (`id`, `supplier_id`, `purchase_id`, `purchase_return_id`, `type`, `amount`, `payment_method`, `description`, `created_at`) VALUES
(7, 3, 10, NULL, 'payment', 292600.00, 'cash', 'All Given', '2025-06-04 12:14:29'),
(8, 2, 11, NULL, 'payment', 511500.00, 'cash', 'All Given', '2025-06-04 12:15:10'),
(9, 3, 10, 2, 'return', 14000.00, 'cash', 'Refund for purchase return #PUR-68403844132B1', '2025-06-04 12:40:29'),
(11, 3, 10, 4, 'return', 14000.00, 'cash', 'Refund for purchase return #PUR-68403844132B1', '2025-06-04 14:31:32'),
(12, 3, 10, 5, 'return', 14000.00, 'cash', 'Refund for purchase return #PUR-68403844132B1', '2025-06-04 14:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `return_reason` enum('defective','wrong_item','supplier_error','other') NOT NULL,
  `return_note` text DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_method` enum('cash','credit','exchange') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `purchase_id`, `return_reason`, `return_note`, `refund_amount`, `refund_method`, `user_id`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(2, 10, 'wrong_item', 'Display Issue', 14000.00, 'cash', 1, 0, NULL, '2025-06-04 12:40:29', '2025-06-04 12:40:29'),
(4, 10, 'defective', '', 14000.00, 'cash', 1, 0, NULL, '2025-06-04 14:31:32', '2025-06-04 14:31:32'),
(5, 10, 'defective', '', 14000.00, 'cash', 1, 0, NULL, '2025-06-04 14:49:49', '2025-06-04 14:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` int(11) NOT NULL,
  `purchase_return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `purchase_return_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 2, 4, 1, 14000.00, 14000.00, '2025-06-04 12:40:29'),
(3, 4, 4, 1, 14000.00, 14000.00, '2025-06-04 14:31:32'),
(4, 5, 4, 1, 14000.00, 14000.00, '2025-06-04 14:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','partial','pending') DEFAULT 'paid',
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `customer_name`, `customer_email`, `invoice_no`, `discount`, `subtotal`, `vat`, `total`, `payment_status`, `user_id`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(12, NULL, 'Walk-in Customer', 'kmthasan1715@gmail.com', 'INV-684039505039B', 450.00, 15000.00, 0.00, 14550.00, 'paid', 1, 0, NULL, '2025-06-04 12:17:20', '2025-06-04 12:17:20');

-- --------------------------------------------------------

--
-- Table structure for table `sales_payment`
--

CREATE TABLE `sales_payment` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sales_id` int(11) DEFAULT NULL,
  `sales_return_id` int(11) DEFAULT NULL,
  `type` enum('payment','return') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit','card','bank_transfer') NOT NULL DEFAULT 'cash',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_payment`
--

INSERT INTO `sales_payment` (`id`, `customer_id`, `sales_id`, `sales_return_id`, `type`, `amount`, `payment_method`, `description`, `created_at`) VALUES
(10, NULL, 12, NULL, 'payment', 14550.00, 'cash', 'Payment for invoice #INV-684039505039B', '2025-06-04 12:17:20');

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `return_reason` enum('defective','wrong_item','customer_change_mind','other') NOT NULL,
  `return_note` text DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_method` enum('cash','credit','exchange') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` int(11) NOT NULL,
  `sales_return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(8, 12, 4, 1, 15000.00, 15000.00, '2025-06-04 12:17:20');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('success','failure') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `user_id`, `ip_address`, `action`, `details`, `status`, `created_at`) VALUES
(1, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-01 10:27:47'),
(2, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-01 11:52:54'),
(3, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-01 12:56:21'),
(4, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-02 01:25:19'),
(5, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-02 10:37:55'),
(6, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-02 11:39:02'),
(7, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-02 13:32:01'),
(8, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-03 01:21:27'),
(9, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-03 09:30:30'),
(10, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-03 14:01:45'),
(11, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-03 15:41:12'),
(12, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-04 00:56:47'),
(13, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-04 11:51:02'),
(14, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-05 00:05:38'),
(15, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-06 02:27:31'),
(16, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-06 02:43:05'),
(17, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-06 03:53:04'),
(18, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-06 11:18:10'),
(19, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-06-06 13:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `change_type` enum('purchase','sale','adjustment','purchase_return','sales_return') NOT NULL,
  `qty` int(11) DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `adjustment_id` int(11) DEFAULT NULL,
  `purchase_return_id` int(11) DEFAULT NULL,
  `sales_return_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `product_id`, `user_id`, `change_type`, `qty`, `price`, `purchase_id`, `sale_id`, `adjustment_id`, `purchase_return_id`, `sales_return_id`, `note`, `created_at`) VALUES
(21, 4, 1, 'purchase', 20, 14000.00, 10, NULL, NULL, NULL, NULL, 'Purchase added', '2025-06-04 12:12:52'),
(22, 5, 1, 'purchase', 20, 25000.00, 11, NULL, NULL, NULL, NULL, 'Purchase added', '2025-06-04 12:13:43'),
(23, 4, 1, 'sale', -1, 15000.00, NULL, 12, NULL, NULL, NULL, 'POS sale', '2025-06-04 12:17:20'),
(24, 4, 1, 'purchase_return', -1, 14000.00, NULL, NULL, NULL, 2, NULL, 'Purchase return', '2025-06-04 12:40:29'),
(26, 4, 1, 'purchase_return', -1, 14000.00, NULL, NULL, NULL, 4, NULL, 'Purchase return', '2025-06-04 14:31:32'),
(27, 4, 1, 'purchase_return', -1, 14000.00, NULL, NULL, NULL, 5, NULL, 'Purchase return', '2025-06-04 14:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `sub_category`
--

CREATE TABLE `sub_category` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_category`
--

INSERT INTO `sub_category` (`id`, `category_id`, `category_name`, `details`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Smartphone', 'All  Smartphones', 0, NULL, '2025-06-01 10:28:48', '2025-06-01 10:28:48');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `email`, `address`, `company_name`, `created_at`, `updated_at`) VALUES
(2, 'MD. Amran', '01319028685', 'admeein@example.com', 'Baherdderhat, Chittagong', 'Jamuna', '2025-06-01 16:59:24', '2025-06-01 16:59:24'),
(3, 'Md Jaber', '01319028682', 'admin2@example.com', 'Baherdderhat', 'Toptech', '2025-06-02 13:04:10', '2025-06-02 13:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `category` enum('auth','product','sale','stock','user','security','system') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `ip_address`, `user_agent`, `category`, `message`, `created_at`) VALUES
(1, NULL, '127.0.0.1', NULL, 'user', 'Created new user: admin123 (admin)', '2025-06-01 10:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','manager','cashier','inventory') NOT NULL DEFAULT 'cashier',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_attempts` tinyint(4) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `profile_pic`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `last_login`, `last_login_ip`, `login_attempts`, `locked_until`, `reset_token`, `reset_token_expires`, `email_verified`, `verification_token`, `password_changed_at`, `created_at`, `updated_at`, `is_deleted`, `deleted_at`) VALUES
(1, NULL, 'admin123', '$2y$10$1zK282mpn4mIFuh61g0nmeOt74.ZBP3PLuAdZaMLkTKp8ZptsUqfW', 'Takiul Hasan', 'mdtakiulhasan@gmail.com', 'admin', 1, '2025-06-06 19:27:53', '127.0.0.1', 0, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-01 10:27:06', '2025-06-06 13:27:53', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_brand_name` (`brand_name`,`is_deleted`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_category_name` (`category`,`is_deleted`);

--
-- Indexes for table `child_category`
--
ALTER TABLE `child_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_childcat_name` (`sub_category_id`,`category_name`,`is_deleted`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_barcode` (`barcode`,`is_deleted`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `sub_category_id` (`sub_category_id`),
  ADD KEY `child_category_id` (`child_category_id`),
  ADD KEY `brand_id` (`brand_id`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_search` (`name`,`barcode`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reference` (`reference_no`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_payment`
--
ALTER TABLE `purchase_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `purchase_return_id` (`purchase_return_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_id` (`purchase_return_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_invoice` (`invoice_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sales_payment`
--
ALTER TABLE `sales_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sales_id` (`sales_id`),
  ADD KEY `sales_return_id` (`sales_return_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_return_id` (`sales_return_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `sub_category`
--
ALTER TABLE `sub_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_subcat_name` (`category_id`,`category_name`,`is_deleted`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`,`is_deleted`),
  ADD UNIQUE KEY `idx_email` (`email`,`is_deleted`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `child_category`
--
ALTER TABLE `child_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `purchase_payment`
--
ALTER TABLE `purchase_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sales_payment`
--
ALTER TABLE `sales_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `sub_category`
--
ALTER TABLE `sub_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `child_category`
--
ALTER TABLE `child_category`
  ADD CONSTRAINT `child_category_ibfk_1` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_category` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD CONSTRAINT `inventory_adjustments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `inventory_adjustments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_category` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`child_category_id`) REFERENCES `child_category` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_4` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `purchase_payment`
--
ALTER TABLE `purchase_payment`
  ADD CONSTRAINT `purchase_payment_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_payment_ibfk_2` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_payment_ibfk_3` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`),
  ADD CONSTRAINT `purchase_returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_ibfk_1` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_payment`
--
ALTER TABLE `sales_payment`
  ADD CONSTRAINT `sales_payment_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_payment_ibfk_2` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_payment_ibfk_3` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `sales_returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sales_returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `sales_return_items_ibfk_1` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_ibfk_3` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_ibfk_4` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sub_category`
--
ALTER TABLE `sub_category`
  ADD CONSTRAINT `sub_category_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
