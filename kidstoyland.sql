-- Kết hợp SQL Dump cho KidsToyLand (đã bỏ bảng news và favorites)
-- Máy chủ: localhost
-- Ngày tạo: 12/05/2025

DROP DATABASE IF EXISTS `kidstoyland`;
CREATE DATABASE `kidstoyland`;
USE `kidstoyland`;

-- --------------------------------------------------------
-- BẢNG USERS
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Lâm Khang', NULL, 'lamkhang.lv.9d2@gmail.com', '$2y$10$Cwy/ZJHxPb1EmzJzEixy3uQfW5m7Pj/L.35/pcFXKNpy8cP6UMOhi', '2025-05-07 15:45:44'),
(2, 'Lâm Vỹ', NULL, 'lamvy060205@gmail.com', '$2y$10$xfVY1l3kS2Y8lT/Z8HmfVO4Mf5qr/AYkE0RIZOoGjNCb3K84LJTmK', '2025-05-11 08:45:58'),
(3, NULL, 'Huu Duc', 'duc@gmail.com', '$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', CURRENT_TIMESTAMP);

-- --------------------------------------------------------
-- BẢNG STAFF
-- --------------------------------------------------------
CREATE TABLE `staff` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff` (`username`, `password`, `email`) VALUES
('staff1','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff1@gmail.com'),
('staff2','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff2@gmail.com'),
('staff3','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff3@gmail.com');

-- --------------------------------------------------------
-- BẢNG ADMIN
-- --------------------------------------------------------
CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `admin` (`username`, `password`, `email`) VALUES
('admin','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'admin@gmail.com');

-- --------------------------------------------------------
-- BẢNG PRODUCTS
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `created_at`, `is_featured`) VALUES
(1, 'Gấu bông Teddy', 'Gấu bông mềm mại, kích thước 50cm, phù hợp cho trẻ em.', 250000.00, 'images/teddy_bear.jpg', 100, '2025-05-08 16:53:56', 0),
(2, 'Xe đồ chơi điều khiển', 'Xe điều khiển từ xa, pin sạc, tốc độ cao.', 450000.00, 'images/remote_car.jpg', 50, '2025-05-08 16:53:56', 0),
(3, 'Bộ xếp hình LEGO', 'Bộ xếp hình 500 mảnh, phát triển tư duy sáng tạo.', 350000.00, 'images/lego_set.jpg', 75, '2025-05-08 16:53:56', 1),
(4, 'Gấu bông Disney', 'Gấu bông nhỏ, dành cho trẻ em', 125000.00, 'images/disney_bear.jpg', 10, '2025-09-05 09:30:01', 1),
(5, 'Rubik 3x3', 'Rubik 3x3 giúp phát triển tư duy của trẻ em', 50000.00, 'images/rubik.jpg', 20, '2025-09-05 09:30:01', 1),
(6, 'Spinner cầm tay', 'Con quay giảm căng thẳng', 100000.00, 'images/spinner.jpg', 32, '2025-09-05 09:30:01', 1),
(7, 'Bộ lego Friends', 'Lắp ráp lego cho trẻ em', 600000.00, 'images/lego_set2.jpg', 10, '2025-09-05 09:30:01', 1);

-- --------------------------------------------------------
-- BẢNG CART_ITEMS
-- --------------------------------------------------------
CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- BẢNG REVENUE
-- --------------------------------------------------------
CREATE TABLE `revenue`(
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `month` VARCHAR(7) NOT NULL,
  `total_revenue` DECIMAL(15,2) NOT NULL
);

INSERT INTO `revenue` (`month`, `total_revenue`) VALUES
('2024-01', 100000000),
('2024-02', 95000000),
('2024-03', 120000000),
('2024-04', 110000000),
('2024-05', 49000000),
('2024-06', 125000000),
('2024-07', 130000000),
('2024-08', 128000000),
('2024-09', 135000000),
('2024-10', 30000000),
('2024-11', 138000000),
('2024-12', 150000000),
('2025-01', 128000000),
('2025-02', 118000000),
('2025-03', 8000000),
('2025-04', 108000000);

CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(12, 2) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recipient_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;