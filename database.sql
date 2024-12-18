-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2024-12-18 17:57:20
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `database`
--

-- --------------------------------------------------------

--
-- 資料表結構 `admin`
--

CREATE TABLE `admin` (
  `member_id` int(11) NOT NULL,
  `tier` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `admin`
--

INSERT INTO `admin` (`member_id`, `tier`) VALUES
(0, 3),
(1, 3);

-- --------------------------------------------------------

--
-- 資料表結構 `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `creation_time` datetime DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `is_checkout` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `cart`
--

INSERT INTO `cart` (`cart_id`, `creation_time`, `member_id`, `is_checkout`) VALUES
(1, NULL, 1, 1),
(2, NULL, 1, 0),
(3, NULL, 3, 1),
(4, NULL, 3, 1),
(5, NULL, 3, 1),
(6, NULL, 3, 0);

-- --------------------------------------------------------

--
-- 資料表結構 `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `image_path` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `category`
--

INSERT INTO `category` (`category_id`, `name`, `image_path`, `description`) VALUES
(1, '手機殼', 'image/c_1.jpg', '手機殼喔'),
(2, '飾品', 'image/c_2.jpg', '非常漂亮');

-- --------------------------------------------------------

--
-- 資料表結構 `contains`
--

CREATE TABLE `contains` (
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `contains`
--

INSERT INTO `contains` (`cart_id`, `product_id`, `quantity`) VALUES
(1, 3, 1),
(1, 4, 1),
(3, 3, 6),
(3, 1, 5),
(4, 3, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `member`
--

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `member`
--

INSERT INTO `member` (`member_id`, `name`, `phone`, `email`, `password`) VALUES
(1, 'admin', '123456789', 'admin', '$2y$10$Fit.HgZAnHQG9drECFI4rumSLrdNLZepf4bkJ4bcxkj7cdd6m4zHO'),
(2, 'a', '123', 'w@gmail.com', '$2y$10$W0ZKAfURd6C6Y/OqzvtQJOipWzHhcE06UEEQKxCv.D3JrRy5Sa1cS'),
(3, '蔡佩頴', '0965065622', 'tinatina62027@gmail.com', '$2y$10$uTYx8Vah1YUZXZ2anDokzetNk34sSRWzI2JGFghePfmJWKfHBCN3u');

-- --------------------------------------------------------

--
-- 資料表結構 `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_status` varchar(20) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `delivery_address` varchar(255) DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `orders`
--

INSERT INTO `orders` (`order_id`, `order_status`, `payment_method`, `amount`, `delivery_address`, `checkout_time`, `member_id`, `cart_id`) VALUES
(1, '待出貨', '貨到付款', 1700.00, '123', '2024-12-18 22:25:46', 3, 3),
(2, '待出貨', '貨到付款', 200.00, '123', '2024-12-18 22:35:02', 3, 4),
(3, '待出貨', '貨到付款', 0.00, '123', '2024-12-18 22:36:22', 3, 5);

-- --------------------------------------------------------

--
-- 資料表結構 `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `image_path` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `product`
--

INSERT INTO `product` (`product_id`, `category_id`, `name`, `description`, `image_path`, `quantity`, `price`) VALUES
(1, 1, '手機殼1', '超讚', 'image/p_1.jpg', 195, 100),
(2, 1, '手機殼2', '更讚', 'image/p_2.jpg', 300, 100),
(3, 1, '手機殼3', '超讚的', 'image/p_3.jpg', 93, 200),
(4, 1, '手機殼 終極版', '????', 'image/p_4.jpg', 10, 1000),
(5, 2, '精緻約會項鍊', '戴了就會脫單', 'image/p_5.jpg', 20, 300),
(6, 2, '易碎之花戒指', '很容易破碎', 'image/p_6.jpg', 10, 340),
(7, 2, '訴說心語手鍊', '戴上它會開始講心事', 'image/p_7.jpg', 5, 500),
(8, 2, '生命之花耳環', '非常有生命力', 'image/p_8.jpg', 60, 800);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `admin`
--
ALTER TABLE `admin`
  ADD KEY `member_id` (`member_id`);

--
-- 資料表索引 `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `member_id` (`member_id`);

--
-- 資料表索引 `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- 資料表索引 `contains`
--
ALTER TABLE `contains`
  ADD KEY `contains_ibfk_1` (`cart_id`),
  ADD KEY `contains_ibfk_2` (`product_id`);

--
-- 資料表索引 `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`);

--
-- 資料表索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `orders_ibfk_2` (`cart_id`);

--
-- 資料表索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`);

--
-- 資料表的限制式 `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`);

--
-- 資料表的限制式 `contains`
--
ALTER TABLE `contains`
  ADD CONSTRAINT `contains_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  ADD CONSTRAINT `contains_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- 資料表的限制式 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`);

--
-- 資料表的限制式 `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
