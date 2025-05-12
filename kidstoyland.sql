CREATE DATABASE kidstoyland;
use kidstoyland;

CREATE TABLE users
(
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    username           VARCHAR(255) NOT NULL,
    email              VARCHAR(255) NOT NULL UNIQUE,
    password           VARCHAR(255) NOT NULL,
    reset_token        VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME     DEFAULT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users (username, email, password)
VALUES ('Huu Duc', 'duc@gmail.com', '$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm');


CREATE TABLE `staff` (
   `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `staff` (`username`, `password`, `email`) VALUES
('staff1','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff1@gmail.com'),('staff2','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff2@gmail.com'),('staff3','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'staff3@gmail.com');

CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `admin` (`username`, `password`, `email`) VALUES
('admin','$2y$10$UA6d8dqFhh5T1WWWNZGeDetmVrMw8rGwndxxQijdKfBdte8z4l9wm', 'admin@gmail.com');

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
('2025-1', 128000000),
('2025-2', 118000000),
('2025-3', 8000000),
('2025-4', 108000000);
