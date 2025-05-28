-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 03:55 PM
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
(1, 'Samsung', 'This is Brand', 0, NULL, '2025-05-28 11:57:39', '2025-05-28 12:22:33'),
(2, 'Oneplus', '', 0, NULL, '2025-05-28 12:08:19', '2025-05-28 12:08:19');

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
(1, 'Electronics', 'All Electronics', 0, NULL, '2025-05-28 11:22:23', '2025-05-28 11:22:23');

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
(1, 1, 'Laptop', 'Only Laptop', 0, NULL, '2025-05-28 11:24:39', '2025-05-28 11:24:39'),
(2, 1, 'Pc', 'Only Pc', 0, NULL, '2025-05-28 11:25:00', '2025-05-28 11:25:00'),
(3, 2, 'Android', 'Only Android', 0, NULL, '2025-05-28 11:26:21', '2025-05-28 11:26:21');

-- --------------------------------------------------------

--
-- Stand-in structure for view `current_stock`
-- (See below for the actual view)
--
CREATE TABLE `current_stock` (
`product_id` int(11)
,`product_name` varchar(100)
,`barcode` varchar(50)
,`current_quantity` decimal(33,0)
,`price` decimal(10,2)
,`sell_price` decimal(10,2)
);

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
(1, 'Oneplus', '56663224244', 1, 2, 3, 2, 45000.00, 50000.00, 0, NULL, '2025-05-28 13:39:48', '2025-05-28 13:47:39');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `before_product_price_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
        IF NEW.sell_price < NEW.price THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sell price cannot be lower than purchase price';
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT 'cash',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(5,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `purchase_items`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_item_change` AFTER INSERT ON `purchase_items` FOR EACH ROW BEGIN
        DECLARE v_subtotal DECIMAL(10,2);
        DECLARE v_total DECIMAL(10,2);
        
        SELECT SUM(total_price), SUM(total_price) + COALESCE(MAX(vat), 0)
        INTO v_subtotal, v_total
        FROM purchase_items
        WHERE purchase_id = NEW.purchase_id;
        
        UPDATE purchase
        SET subtotal = v_subtotal,
            total = v_total,
            updated_at = NOW()
        WHERE id = NEW.purchase_id;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_item_insert` AFTER INSERT ON `purchase_items` FOR EACH ROW BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, purchase_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM purchase WHERE id = NEW.purchase_id), 
               'purchase', NEW.quantity, NEW.unit_price, NEW.purchase_id, NOW());
        
        UPDATE products 
        SET price = NEW.unit_price, 
            updated_at = NOW()
        WHERE id = NEW.product_id AND (price <> NEW.unit_price OR price IS NULL);
    END
$$
DELIMITER ;

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
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(5,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','partial','pending') DEFAULT 'paid',
  `user_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `sales_return_items`
--
DELIMITER $$
CREATE TRIGGER `after_sales_return_item_insert` AFTER INSERT ON `sales_return_items` FOR EACH ROW BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, sales_return_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM sales_returns WHERE id = NEW.sales_return_id), 
               'sales_return', NEW.quantity, NEW.unit_price, NEW.sales_return_id, NOW());
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `sales_summary` (
`sale_date` date
,`total_sales` bigint(21)
,`total_amount` decimal(32,2)
,`total_discount` decimal(27,2)
,`total_vat` decimal(32,2)
,`total_returns` bigint(21)
,`total_refunds` decimal(32,2)
);

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
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `sale_items`
--
DELIMITER $$
CREATE TRIGGER `after_sale_item_change` AFTER INSERT ON `sale_items` FOR EACH ROW BEGIN
        DECLARE v_subtotal DECIMAL(10,2);
        DECLARE v_total DECIMAL(10,2);
        DECLARE v_discount DECIMAL(5,2);
        
        SELECT discount INTO v_discount FROM sales WHERE id = NEW.sale_id;
        
        SELECT SUM(total_price), 
               SUM(total_price) - (SUM(total_price) * COALESCE(v_discount, 0)/100) + COALESCE(MAX(vat), 0)
        INTO v_subtotal, v_total
        FROM sale_items
        WHERE sale_id = NEW.sale_id;
        
        UPDATE sales
        SET subtotal = v_subtotal,
            total = v_total,
            updated_at = NOW()
        WHERE id = NEW.sale_id;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_sale_item_insert` AFTER INSERT ON `sale_items` FOR EACH ROW BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, sale_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM sales WHERE id = NEW.sale_id), 
               'sale', NEW.quantity, NEW.unit_price, NEW.sale_id, NOW());
    END
$$
DELIMITER ;

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
(1, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 17:06:51'),
(2, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 17:07:15'),
(3, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 17:36:35'),
(4, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 23:22:10'),
(5, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 23:27:49'),
(6, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-27 23:53:29'),
(7, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-28 11:05:03'),
(8, 1, '127.0.0.1', 'login', 'Successful login', 'success', '2025-05-28 12:28:38');

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
-- Triggers `stock`
--
DELIMITER $$
CREATE TRIGGER `after_stock_update` AFTER INSERT ON `stock` FOR EACH ROW BEGIN
        DECLARE v_current_qty INT;
        DECLARE v_product_name VARCHAR(100);
        DECLARE v_threshold INT DEFAULT 5;
        
        SELECT current_quantity INTO v_current_qty
        FROM current_stock
        WHERE product_id = NEW.product_id;
        
        SELECT name INTO v_product_name FROM products WHERE id = NEW.product_id;
        
        IF v_current_qty <= v_threshold THEN
            INSERT INTO system_logs (user_id, ip_address, category, message)
            VALUES (NEW.user_id, NULL, 'stock', 
                    CONCAT('Low stock alert: ', v_product_name, ' (', v_current_qty, ' remaining)'));
        END IF;
    END
$$
DELIMITER ;

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
(1, 1, 'Computer', 'All Desktop', 0, NULL, '2025-05-28 11:23:28', '2025-05-28 11:23:28'),
(2, 1, 'Smartphone', 'All Smartphone', 0, NULL, '2025-05-28 11:25:34', '2025-05-28 11:25:34');

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
(1, NULL, '127.0.0.1', NULL, 'user', 'Created new user: admin123 (admin)', '2025-05-27 17:05:06'),
(2, 1, '127.0.0.1', NULL, 'user', 'Created new user: oneplus (inventory)', '2025-05-27 17:08:17'),
(3, 1, '127.0.0.1', NULL, 'user', 'Created new user: hasan (manager)', '2025-05-27 23:29:02'),
(4, 1, '127.0.0.1', NULL, 'user', 'Created new user: kmt_hasan (cashier)', '2025-05-28 00:07:25'),
(5, 1, '127.0.0.1', NULL, 'user', 'Updated user #1 (admin123)', '2025-05-28 00:28:45'),
(6, 1, '127.0.0.1', NULL, 'user', 'Created new user: Taki2 (manager)', '2025-05-28 01:54:39'),
(7, 1, '127.0.0.1', NULL, 'user', 'Updated user #5 (Taki2)', '2025-05-28 01:55:01');

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
(1, 'uploads/profile_pics/user_1_1748392125.png', 'admin123', '$2y$10$ZNddYegddabcHJdIW0IqaevzNfqgOn6PJ2ebgwIzGcOgZnGtHzii2', 'System Administrator', 'mdtakiulhasan@gmail.com', 'admin', 1, '2025-05-28 18:28:38', '127.0.0.1', 0, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-27 17:05:05', '2025-05-28 12:28:38', 0, NULL),
(4, 'uploads/profile_pics/profile_683653bda3bc0.png', 'kmt_hasan', '$2y$10$kj4vs1ugvQKcwbEHVHK3ben.rt4yH6EhLEHbinxK4b1.BHlmm/tiW', 'kmt Hasane', 'kmth444asan@gmail.com', 'cashier', 1, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-28 00:07:25', '2025-05-28 00:27:19', 1, '2025-05-28 00:27:19'),
(5, NULL, 'Taki2', '$2y$10$a7m67lFyf4g7/9nhwbWcFu/LFisS0fnnu1yDAOxsC.zMepbjE1gHG', 'kmt Hasane', 'admeein@example.com', 'inventory', 1, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-28 01:54:38', '2025-05-28 01:55:59', 1, '2025-05-28 01:55:59');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_login_attempt` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
        IF OLD.login_attempts <> NEW.login_attempts AND NEW.login_attempts >= 5 THEN
            INSERT INTO security_logs (user_id, ip_address, action, details, status)
            VALUES (NEW.id, NEW.last_login_ip, 'account_lock', 
                    'Account locked due to too many failed attempts', 'failure');
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `current_stock`
--
DROP TABLE IF EXISTS `current_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `current_stock`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, `p`.`barcode` AS `barcode`, coalesce(sum(case when `s`.`change_type` = 'purchase' then `s`.`qty` when `s`.`change_type` = 'sales_return' then `s`.`qty` when `s`.`change_type` = 'purchase_return' then -`s`.`qty` when `s`.`change_type` = 'sale' then -`s`.`qty` when `s`.`change_type` = 'adjustment' then case when (select `inventory_adjustments`.`adjustment_type` from `inventory_adjustments` where `inventory_adjustments`.`id` = `s`.`adjustment_id`) = 'add' then `s`.`qty` else -`s`.`qty` end end),0) AS `current_quantity`, `p`.`price` AS `price`, `p`.`sell_price` AS `sell_price` FROM (`products` `p` left join `stock` `s` on(`p`.`id` = `s`.`product_id`)) WHERE `p`.`is_deleted` = 0 GROUP BY `p`.`id`, `p`.`name`, `p`.`barcode`, `p`.`price`, `p`.`sell_price` ;

-- --------------------------------------------------------

--
-- Structure for view `sales_summary`
--
DROP TABLE IF EXISTS `sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_summary`  AS SELECT cast(`s`.`created_at` as date) AS `sale_date`, count(0) AS `total_sales`, sum(`s`.`total`) AS `total_amount`, sum(`s`.`discount`) AS `total_discount`, sum(`s`.`vat`) AS `total_vat`, (select count(0) from `sales_returns` `sr` where cast(`sr`.`created_at` as date) = cast(`s`.`created_at` as date)) AS `total_returns`, (select coalesce(sum(`sr`.`refund_amount`),0) from `sales_returns` `sr` where cast(`sr`.`created_at` as date) = cast(`s`.`created_at` as date)) AS `total_refunds` FROM `sales` AS `s` WHERE `s`.`is_deleted` = 0 GROUP BY cast(`s`.`created_at` as date) ;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `child_category`
--
ALTER TABLE `child_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_payment`
--
ALTER TABLE `purchase_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_payment`
--
ALTER TABLE `sales_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_category`
--
ALTER TABLE `sub_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
