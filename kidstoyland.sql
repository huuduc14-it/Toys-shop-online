-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th5 12, 2025 lúc 02:48 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `kidstoyland`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(1, 1, 4, 1, '2025-05-09 11:29:25'),
(2, 1, 5, 1, '2025-05-09 11:29:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 1, 6, '2025-05-09 11:29:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `excerpt` text NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT 'KidsToyLand Team',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `news`
--

INSERT INTO `news` (`id`, `title`, `excerpt`, `content`, `image`, `author`, `created_at`, `updated_at`) VALUES
(1, 'Top 5 Món Đồ Chơi Giáo Dục Cho Bé 2025', 'Khám phá những món đồ chơi giúp bé vừa học vừa chơi, phát triển tư duy và sáng tạo.', 'Nội dung chi tiết về top 5 món đồ chơi giáo dục, bao gồm bộ xếp hình LEGO, bảng vẽ thông minh, và robot lập trình. Những món đồ chơi này không chỉ giúp bé giải trí mà còn kích thích tư duy logic và sáng tạo...', 'images/news_edu_toys.jpg', 'Nguyễn Minh Anh', '2025-05-01 03:00:00', '2025-05-11 08:54:32'),
(2, 'Khuyến Mãi Tháng 5: Giảm Giá 30% Đồ Chơi', 'Cơ hội mua sắm đồ chơi yêu thích với giá ưu đãi trong tháng này!', 'KidsToyLand hân hạnh mang đến chương trình khuyến mãi đặc biệt tháng 5 với giảm giá lên đến 30% cho các dòng đồ chơi xe, búp bê, và đồ chơi STEM. Đừng bỏ lỡ cơ hội này để làm phong phú thêm bộ sưu tập đồ chơi cho bé...', 'images/news_sale.jpg', 'Trần Văn Hùng', '2025-05-05 02:00:00', '2025-05-11 08:54:32'),
(3, 'Mẹo Chọn Đồ Chơi An Toàn Cho Trẻ', 'Hướng dẫn các bậc phụ huynh cách chọn đồ chơi an toàn và phù hợp với độ tuổi của bé.', 'Bài viết cung cấp các mẹo quan trọng để chọn đồ chơi an toàn, bao gồm kiểm tra chất liệu, tránh các chi tiết nhỏ dễ nuốt, và chọn thương hiệu uy tín. Ngoài ra, chúng tôi cũng chia sẻ danh sách các sản phẩm an toàn tại KidsToyLand...', 'images/news_safety.jpg', 'Lê Thị Thanh', '2025-04-28 01:30:00', '2025-05-11 08:54:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `created_at`, `is_featured`) VALUES
(1, 'Gấu bông Teddy', 'Gấu bông mềm mại, kích thước 50cm, phù hợp cho trẻ em.', 250000.00, 'images/teddy_bear.jpg', 100, '2025-05-08 16:53:56', 0),
(2, 'Xe đồ chơi điều khiển', 'Xe điều khiển từ xa, pin sạc, tốc độ cao.', 450000.00, 'images/remote_car.jpg', 50, '2025-05-08 16:53:56', 0),
(3, 'Bộ xếp hình LEGO', 'Bộ xếp hình 500 mảnh, phát triển tư duy sáng tạo.', 350000.00, 'images/lego_set.jpg', 75, '2025-05-08 16:53:56', 1),
(4, 'Gấu bông Disney', 'Gấu bông nhỏ, dành cho trẻ em', 125000.00, 'images/disney_bear.jpg', 10, '2025-09-05 09:30:01', 1),
(5, 'Rubik 3x3', 'Rubik 3x3 giúp phát triển tư duy của trẻ em', 50000.00, 'images/rubik.jpg', 20, '2025-09-05 09:30:01', 1),
(6, 'Spinner cầm tay', 'Con quay giảm căng thẳng', 100000.00, 'images/spinner.jpg', 32, '2025-09-05 09:30:01', 1),
(7, 'Bộ lego Friends', 'Lắp ráp lego cho trẻ em', 600000.00, 'images/lego_set2.jpg', 10, '2025-09-05 09:30:01', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `reset_token`, `reset_token_expiry`, `created_at`, `remember_token`, `remember_token_expiry`) VALUES
(1, 'Lâm Khang', 'lamkhang.lv.9d2@gmail.com', '$2y$10$Cwy/ZJHxPb1EmzJzEixy3uQfW5m7Pj/L.35/pcFXKNpy8cP6UMOhi', NULL, NULL, '2025-05-07 15:45:44', NULL, NULL),
(2, 'Lâm Vỹ', 'lamvy060205@gmail.com', '$2y$10$xfVY1l3kS2Y8lT/Z8HmfVO4Mf5qr/AYkE0RIZOoGjNCb3K84LJTmK', NULL, NULL, '2025-05-11 08:45:58', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
