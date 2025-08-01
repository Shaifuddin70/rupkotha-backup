-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 12, 2025 at 03:43 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rpecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`) VALUES
(2, 'ROKIBa', 'admin@gmail.com', '$2y$10$JLlfSjU8JiL//lrLBqSYVuoZPrL/Pv1CyIVKJBP7/ffin5SBP2XHm');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(8, 'Gadgets', '2025-06-27 20:53:06', '2025-07-05 17:37:31'),
(16, 'Bags', '2025-06-27 21:31:29', '2025-07-05 17:36:57'),
(24, 'Clothing', '2025-07-05 17:23:36', '2025-07-05 17:36:45'),
(25, 'Shoes', '2025-07-05 17:37:04', '2025-07-12 08:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `hero_products`
--

CREATE TABLE `hero_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `hero_products`
--

INSERT INTO `hero_products` (`id`, `product_id`, `title`, `subtitle`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(22, 19, 'Indo Western Gown', 'Indo Western Gown', '377bcb07bc24d442cb5f0a65.webp', 1, '2025-07-05 17:46:09', NULL),
(24, 21, 'S@%', 'Order Yours Now!!', '09ab0683a439b37dc20b29c1.png', 1, '2025-07-12 09:34:44', '2025-07-12 15:41:52');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_trx_id` varchar(255) DEFAULT NULL,
  `payment_sender_no` varchar(20) DEFAULT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `payment_method`, `payment_trx_id`, `payment_sender_no`, `shipping_fee`, `created_at`) VALUES
(10, 1, '1560.00', 'Completed', 'cod', '', '', '60.00', '2025-07-12 06:35:00'),
(11, 1, '575060.00', 'Cancelled', 'cod', '', '', '60.00', '2025-07-12 07:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(13, 10, 19, 1, '1500.00'),
(14, 11, 21, 5, '115000.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `stock` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `price`, `cost_price`, `created_at`, `stock`, `is_active`, `updated_at`, `deleted_at`) VALUES
(18, 24, 'Indo-Wester', 'New Collection', '3928887fbbf145c1f3c5b337.webp', '1299.00', '1000.00', '2025-07-12 06:57:30', 5, 1, '2025-07-12 06:57:30', NULL),
(19, 24, 'Indo Western Gown', 'Indo Western Gown', '8c21a75f1bc1a74a43e166e1.webp', '1500.00', '1200.00', '2025-07-12 06:57:52', 9, 1, '2025-07-12 06:57:52', NULL),
(21, 8, 'Galaxy S25 Edge 5', 'Durability with titanium frame and IP68 rating.', '1b7794fbc7637ab559c0cca0.jpg', '115000.00', '105000.00', '2025-07-12 08:28:48', 6, 1, '2025-07-12 08:28:48', NULL),
(22, 8, 'Tecno Camon 40', 'Network	Technology	: GSM / HSPA / LTE\r\nLaunch	Announced	2025, March 03\r\nStatus	Available. Released 2025, May\r\nBody	Dimensions	164.1 x 74.6 x 7.3 mm (6.46 x 2.94 x 0.29 in)\r\nWeight	177.2 g (6.24 oz)\r\nSIM	Nano-SIM + Nano-SIM\r\n 	IP66 dust tight and water resistant (high pressure water jets)\r\nDisplay	Type	AMOLED, 120Hz\r\nSize	6.78 inches, 109.9 cm2 (~89.8% screen-to-body ratio)\r\nResolution	1080 x 2436 pixels (~393 ppi density)\r\nProtection	Panda King Glass\r\n 	HDR image support\r\nPlatform	OS	Android 15, up to 3 major Android upgrades, HIOS 15\r\nChipset	Mediatek Helio G100 Ultimate (6 nm)\r\nCPU	Octa-core (2x2.2 GHz Cortex-A76 & 6x2.0 GHz Cortex-A55)\r\nGPU	Mali-G57 MC2\r\nMemory	Card slot	Unspecified\r\nInternal	128GB 8GB RAM, 128GB 12GB RAM, 256GB 8GB RAM, 256GB 12GB RAM\r\nMain Camera	Dual	50 MP, f/1.9, 23mm (wide), 1/1.56\", 1.0µm, PDAF, OIS\r\n8 MP, (ultrawide)\r\nFeatures	Dual-LED flash, HDR, panorama\r\nVideo	Yes\r\nSelfie camera	Single	32 MP, (wide)\r\nVideo	Yes\r\nSound	Loudspeaker	Yes, with stereo speakers\r\n3.5mm jack	Unspecified\r\nComms	WLAN	Yes\r\nBluetooth	Yes\r\nPositioning	GPS\r\nNFC	Yes\r\nInfrared port	Yes\r\nRadio	FM radio\r\nUSB	USB Type-C 2.0, OTG\r\nFeatures	Sensors	Fingerprint (under display, optical), accelerometer, gyro, proximity, compass\r\n 	Circle to Search\r\nBattery	Type	5200 mAh\r\nCharging	45W wired, 50% in 23 min, 100% in 43 min\r\nMisc	Colors	Emerald Lake Green, Galaxy Black, Glacier White, Emerald Glow Green\r\nModels	CM5', '29ccf61ea7b1410d63bf563a.jpg', '24000.00', '22600.00', '2025-07-12 09:08:05', 10, 1, '2025-07-12 09:08:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `created_at`) VALUES
(1, 22, '69ca6139161d42d70e46a299.jpg', '2025-07-12 08:55:56'),
(3, 22, '6a42f885bda120dec4e64e39.jpg', '2025-07-12 09:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `shipping_fee_dhaka` decimal(10,2) NOT NULL DEFAULT '60.00',
  `shipping_fee_outside` decimal(10,2) NOT NULL DEFAULT '120.00',
  `bkash_number` varchar(20) DEFAULT NULL,
  `nagad_number` varchar(20) DEFAULT NULL,
  `rocket_number` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `logo`, `phone`, `email`, `address`, `facebook`, `instagram`, `twitter`, `shipping_fee_dhaka`, `shipping_fee_outside`, `bkash_number`, `nagad_number`, `rocket_number`, `updated_at`) VALUES
(1, 'Rupkotha Properties Bangladeshs', '29532d60dff44d17d4b4a4a1.jpg', '01234554', 'info@rpproperty.com', 'Dhaka, Bangladesh', 'https://www.facebook.com/', 'https://www.facebook.com/', 'https://www.facebook.com/', '60.00', '120.00', '01791912323', '01812345678', '01538347152', '2025-07-12 09:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `address`, `created_at`) VALUES
(1, 'Shaifuddin Ahammed', 'shaifuddin70@gmail.com', '$2y$10$TU4FOibTfughwZ4EhnYs/uQi8VdBf9F8BtxNs2HPZWuwIyTWZz2ra', '01635485720', 'Army Society, Mazar road, Uttara, Dhaka-1230', '2025-07-05 08:49:22'),
(2, 'rokib', 'dardentimothy3@gmail.com', '$2y$10$/byoqCnj2gUim.hMXWklIOHu20uU7p9x33X9l7owJG5nybEgg429q', '18002122120', 'Bangalore, karnataka, india', '2025-07-05 08:53:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_products`
--
ALTER TABLE `hero_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_items_ibfk_2` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `hero_products`
--
ALTER TABLE `hero_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `hero_products`
--
ALTER TABLE `hero_products`
  ADD CONSTRAINT `hero_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
