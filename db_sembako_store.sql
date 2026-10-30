-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 12:58 PM
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
-- Database: `db_sembako_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `receiver` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `button_text` varchar(50) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `logo`, `description`, `created_at`) VALUES
(1, 'Indomie', NULL, 'Produk mie instan', '2026-10-13 09:49:51'),
(2, 'Aqua', NULL, 'Air mineral', '2026-10-13 09:49:51'),
(3, 'Bimoli', NULL, 'Minyak goreng', '2026-10-13 09:49:51'),
(4, 'Royco', NULL, 'Bumbu penyedap', '2026-10-13 09:49:51'),
(5, 'Blue Band', NULL, 'Margarin', '2026-10-13 09:49:51'),
(6, 'ABC', NULL, 'Saus dan kecap', '2026-10-13 09:49:51'),
(7, 'Sariwangi', NULL, 'Produk teh', '2026-10-13 09:49:51'),
(8, 'Kapal Api', NULL, 'Produk kopi', '2026-10-13 09:49:51'),
(9, 'Rinso', NULL, 'Deterjen', '2026-10-13 09:49:51'),
(10, 'MamyPoko', NULL, 'Popok bayi', '2026-10-13 09:49:51'),
(11, 'Pepsodent', NULL, 'Pasta gigi', '2026-10-13 09:49:51');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) NOT NULL,
  `cart_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Bahan Pokok Utama', 'bahan-pokok', NULL, 'Produk kebutuhan pokok sehari-hari', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(2, 'Bumbu Dapur', 'bumbu-dapur', NULL, 'Bumbu dan bahan memasak', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(3, 'Makanan Instan', 'makanan-instan', NULL, 'Produk makanan cepat saji', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(4, 'Minuman', 'minuman', NULL, 'Minuman dan bahan minuman', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(5, 'Jajanan & Snack', 'snack', NULL, 'Makanan ringan', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(6, 'Rumah Tangga', 'rumah-tangga', NULL, 'Kebutuhan kebersihan rumah', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13'),
(7, 'Perlengkapan Bayi', 'perlengkapan-bayi', NULL, 'Produk bayi dan wanita', 1, '2026-10-13 09:27:13', '2026-10-13 09:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `shipping_cost` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `payment_status` enum('belum_bayar','sudah_bayar') DEFAULT 'belum_bayar',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) NOT NULL,
  `category_id` bigint(20) DEFAULT NULL,
  `brand_id` bigint(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `discount_price` decimal(12,2) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `shipping_info` text DEFAULT NULL,
  `return_policy` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `sku`, `price`, `discount_price`, `weight`, `stock`, `description`, `shipping_info`, `return_policy`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Minyak Goreng Bimoli 2 Liter', NULL, 'MIN001', 38000.00, NULL, 2000, 100, 'Minyak goreng berkualitas untuk kebutuhan memasak', 'Produk dikemas aman menggunakan bubble wrap', 'Retur maksimal 1x24 jam jika barang rusak', 'active', '2026-10-13 09:50:39', '2026-10-13 09:50:39'),
(2, 1, 3, 'Minyak Goreng Bimoli 1 Liter', NULL, 'MIN002', 19000.00, NULL, 1000, 150, 'Minyak goreng kemasan praktis', 'Pengiriman menggunakan packing aman', 'Barang dapat ditukar jika cacat', 'active', '2026-10-13 09:50:39', '2026-10-13 09:50:39'),
(3, 1, 1, 'Beras Premium 5 Kg', NULL, 'BR001', 75000.00, NULL, 5000, 80, 'Beras premium pulen dan berkualitas', 'Beras dikemas menggunakan plastik tebal', 'Retur jika kemasan rusak', 'active', '2026-10-13 09:50:39', '2026-10-13 09:50:39'),
(4, 1, 1, 'Beras Medium 5 Kg', NULL, 'BR002', 65000.00, NULL, 5000, 100, 'Beras medium kebutuhan rumah tangga', 'Pengiriman menggunakan packing standar', 'Retur sesuai kebijakan toko', 'active', '2026-10-13 09:50:39', '2026-10-13 09:50:39'),
(5, 1, 1, 'Gula Pasir 1 Kg', NULL, 'GL001', 17000.00, NULL, 1000, 200, 'Gula pasir putih berkualitas', 'Produk dikemas rapi', 'Barang rusak dapat diklaim', 'active', '2026-10-13 09:50:39', '2026-10-13 09:50:39'),
(6, 2, 6, 'Kecap ABC Manis 600ml', NULL, 'KC001', 15000.00, NULL, 600, 100, 'Kecap manis untuk masakan', 'Botol dilindungi bubble wrap', 'Retur jika botol pecah', 'active', '2026-10-13 09:51:16', '2026-10-13 09:51:16'),
(7, 2, 4, 'Royco Ayam 230 gram', NULL, 'RY001', 9000.00, NULL, 230, 150, 'Penyedap rasa ayam', 'Packing aman', 'Retur maksimal 24 jam', 'active', '2026-10-13 09:51:16', '2026-10-13 09:51:16'),
(8, 2, 5, 'Blue Band 200 gram', NULL, 'BB001', 7000.00, NULL, 200, 120, 'Margarine untuk memasak dan membuat kue', 'Pengiriman cepat', 'Produk rusak dapat ditukar', 'active', '2026-10-13 09:51:16', '2026-10-13 09:51:16'),
(9, 2, 6, 'Saus Sambal ABC', NULL, 'SC001', 12000.00, NULL, 500, 90, 'Saus sambal pedas', 'Botol aman', 'Retur jika kerusakan produk', 'active', '2026-10-13 09:51:16', '2026-10-13 09:51:16'),
(10, 3, 1, 'Indomie Goreng Original', NULL, 'IND001', 3500.00, NULL, 85, 500, 'Mie instan rasa original', 'Packing kardus', 'Retur jika kemasan rusak', 'active', '2026-10-13 09:51:39', '2026-10-13 09:51:39'),
(11, 3, 1, 'Indomie Soto Special', NULL, 'IND002', 3500.00, NULL, 85, 400, 'Mie instan rasa soto', 'Packing aman', 'Barang cacat dapat diklaim', 'active', '2026-10-13 09:51:39', '2026-10-13 09:51:39'),
(12, 3, 1, 'Indomie Ayam Bawang', NULL, 'IND003', 3500.00, NULL, 85, 350, 'Mie instan rasa ayam bawang', 'Pengiriman standar', 'Retur sesuai aturan', 'active', '2026-10-13 09:51:39', '2026-10-13 09:51:39'),
(13, 4, 2, 'Aqua Botol 600ml', NULL, 'AQ001', 4000.00, NULL, 600, 300, 'Air mineral kemasan', 'Kemasan kardus', 'Retur jika bocor', 'active', '2026-10-13 09:51:57', '2026-10-13 09:51:57'),
(14, 4, 7, 'Sariwangi Teh Celup', NULL, 'TH001', 9000.00, NULL, 100, 200, 'Teh celup praktis', 'Packing aman', 'Retur produk rusak', 'active', '2026-10-13 09:51:57', '2026-10-13 09:51:57'),
(15, 4, 8, 'Kapal Api Kopi Sachet', NULL, 'KP001', 2000.00, NULL, 25, 500, 'Kopi hitam sachet', 'Packing standar', 'Retur jika rusak', 'active', '2026-10-13 09:51:57', '2026-10-13 09:51:57'),
(16, 5, 1, 'Biskuit Roma Kelapa', NULL, 'BS001', 8000.00, NULL, 300, 150, 'Biskuit renyah', 'Packing aman', 'Retur kemasan rusak', 'active', '2026-10-13 09:52:18', '2026-10-13 09:52:18'),
(17, 5, 1, 'Kerupuk Udang', NULL, 'KR001', 12000.00, NULL, 500, 100, 'Kerupuk siap makan', 'Packing plastik', 'Retur jika rusak', 'active', '2026-10-13 09:52:18', '2026-10-13 09:52:18'),
(18, 6, 9, 'Rinso Deterjen Bubuk 800gr', NULL, 'RN001', 15000.00, NULL, 800, 200, 'Deterjen pencuci pakaian', 'Packing aman', 'Retur produk cacat', 'active', '2026-10-13 09:52:52', '2026-10-13 09:52:52'),
(19, 6, 11, 'Pepsodent Pasta Gigi', NULL, 'PD001', 10000.00, NULL, 150, 150, 'Pasta gigi perlindungan gigi', 'Packing standar', 'Retur jika rusak', 'active', '2026-10-13 09:52:52', '2026-10-13 09:52:52'),
(20, 7, 10, 'MamyPoko Popok Bayi S', NULL, 'MP001', 45000.00, NULL, 1000, 100, 'Popok bayi nyaman', 'Packing tertutup', 'Tidak menerima retur jika kemasan terbuka', 'active', '2026-10-13 09:54:11', '2026-10-13 09:54:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `is_primary`) VALUES
(1, 1, 'beras-premium.jpg', 1),
(2, 2, 'minyak-bimoli.jpg', 1),
(3, 3, 'gula-pasir.jpg', 1),
(4, 4, 'indomie-goreng.jpg', 1),
(5, 5, 'aqua.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `discount` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping`
--

CREATE TABLE `shipping` (
  `id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `courier` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_histories`
--

CREATE TABLE `stock_histories` (
  `id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `type` enum('IN','OUT') DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipping`
--
ALTER TABLE `shipping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `stock_histories`
--
ALTER TABLE `stock_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping`
--
ALTER TABLE `shipping`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_histories`
--
ALTER TABLE `stock_histories`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `shipping`
--
ALTER TABLE `shipping`
  ADD CONSTRAINT `shipping_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `stock_histories`
--
ALTER TABLE `stock_histories`
  ADD CONSTRAINT `stock_histories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
