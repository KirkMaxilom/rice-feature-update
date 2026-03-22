-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 01:20 PM
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
-- Database: `rice_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_payable`
--

CREATE TABLE `account_payable` (
  `ap_id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('paid','unpaid','partial','overdue') DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_payable`
--

INSERT INTO `account_payable` (`ap_id`, `purchase_id`, `supplier_id`, `total_amount`, `amount_paid`, `balance`, `due_date`, `status`, `approved`, `approved_by`, `approved_at`, `created_at`) VALUES
(1, 2, 1, 4000.00, 4000.00, 0.00, '2026-02-06', 'paid', 1, 5, '2026-01-28 07:14:37', '2026-01-28 06:48:30'),
(2, 3, 1, 10000.00, 10000.00, 0.00, '2026-02-06', 'paid', 1, 5, '2026-01-28 07:55:29', '2026-01-28 07:54:07'),
(3, 4, 1, 0.00, 0.00, 0.00, '2026-02-08', 'unpaid', 1, 5, '2026-01-28 08:40:35', '2026-01-28 08:38:18'),
(4, 6, 1, 11200.00, 11200.00, 0.00, '2026-02-15', 'paid', 1, 5, '2026-01-28 08:55:26', '2026-01-28 08:53:54'),
(5, 9, 3, 3360.00, 3360.00, 0.00, '2026-01-28', 'paid', 1, 5, '2026-01-29 02:23:39', '2026-01-29 02:22:32'),
(6, 10, 3, 4300.00, 4300.00, 0.00, '2026-02-06', 'paid', 1, 5, '2026-01-29 11:53:22', '2026-01-29 11:52:47'),
(7, 16, 3, 400.00, 400.00, 0.00, '2026-02-21', 'paid', 1, 5, '2026-02-05 09:12:19', '2026-02-05 09:11:51'),
(8, 17, 2, 440.00, 0.00, 440.00, '2026-03-06', 'unpaid', 0, NULL, NULL, '2026-02-06 06:03:06'),
(9, 18, 1, 6100.00, 0.00, 6100.00, '2026-03-02', 'unpaid', 0, NULL, NULL, '2026-02-22 03:21:16'),
(10, 19, 2, 20200.00, 0.00, 20200.00, '2026-03-01', 'unpaid', 0, NULL, NULL, '2026-02-22 03:34:51'),
(11, 20, 1, 11000.00, 0.00, 11000.00, '2026-03-02', 'unpaid', 0, NULL, NULL, '2026-02-22 04:29:41'),
(12, 21, 2, 20100.00, 0.00, 20100.00, '2026-03-01', 'unpaid', 0, NULL, NULL, '2026-02-22 12:51:15'),
(13, 22, 3, 10150.00, 10150.00, 0.00, '2026-02-28', 'paid', 1, 5, '2026-02-23 04:27:00', '2026-02-23 04:24:54'),
(14, 23, 2, 245.00, 0.00, 245.00, '2026-02-27', 'unpaid', 1, 5, '2026-03-20 12:09:59', '2026-02-26 17:14:08');

-- --------------------------------------------------------

--
-- Table structure for table `account_receivable`
--

CREATE TABLE `account_receivable` (
  `ar_id` int(11) NOT NULL,
  `sales_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('paid','unpaid','partial','overdue') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_receivable`
--

INSERT INTO `account_receivable` (`ar_id`, `sales_id`, `customer_id`, `total_amount`, `amount_paid`, `balance`, `due_date`, `status`, `created_at`) VALUES
(1, 16, 2, 1815.00, 1815.00, 0.00, '2026-01-31', 'paid', '2026-01-26 09:26:19'),
(2, 17, 1, 680.00, 680.00, 0.00, '2026-02-05', 'paid', '2026-01-26 10:42:27'),
(3, 19, 2, 550.00, 550.00, 0.00, '2026-02-12', 'paid', '2026-01-26 11:02:37'),
(4, 20, 3, 1375.00, 1375.00, 0.00, '2026-02-12', 'paid', '2026-01-27 03:02:10'),
(5, 21, 4, 1375.00, 1375.00, 0.00, '2026-01-30', 'paid', '2026-01-27 06:10:04'),
(6, 22, 5, 1375.00, 1375.00, 0.00, '2026-02-04', 'paid', '2026-01-27 07:24:29'),
(7, 26, 5, 680.00, 680.00, 0.00, '2026-02-14', 'paid', '2026-01-27 07:34:22'),
(8, 31, 3, 55.00, 55.00, 0.00, '2026-01-30', 'paid', '2026-01-28 08:15:42'),
(9, 34, 2, 265.00, 265.00, 0.00, '2026-01-30', 'paid', '2026-01-29 11:09:44'),
(10, 35, 1, 60.00, 60.00, 0.00, '2026-01-30', 'paid', '2026-01-29 11:14:46'),
(11, 38, 2, 265.00, 265.00, 0.00, '2026-01-30', 'paid', '2026-01-29 11:27:25'),
(12, 41, 2, 300.00, 300.00, 0.00, '2026-01-30', 'paid', '2026-01-29 11:46:45'),
(13, 42, 2, 110.00, 110.00, 0.00, '2026-01-30', 'paid', '2026-01-29 11:47:41'),
(14, 47, 7, 550.00, 550.00, 0.00, '2026-01-30', 'paid', '2026-01-30 12:21:52'),
(15, 50, 4, 720.00, 720.00, 0.00, '2026-02-02', 'paid', '2026-01-30 13:28:47'),
(16, 53, 2, 75.00, 75.00, 0.00, '2026-02-02', 'paid', '2026-01-31 05:39:38'),
(17, 55, 3, 550.00, 550.00, 0.00, '2026-02-15', 'paid', '2026-02-03 04:22:36'),
(18, 57, 6, 470.00, 470.00, 0.00, '2026-02-15', 'paid', '2026-02-03 09:09:18'),
(19, 60, 3, 800.00, 800.00, 0.00, '2026-02-15', 'paid', '2026-02-05 09:07:13'),
(20, 61, 2, 300.00, 300.00, 0.00, '2026-02-15', 'paid', '2026-02-06 06:01:36'),
(21, 65, 5, 550.00, 550.00, 0.00, '2026-02-15', 'paid', '2026-02-06 06:15:59'),
(22, 70, 4, 242.00, 242.00, 0.00, '2026-02-25', 'paid', '2026-02-22 06:26:19'),
(23, 76, 7, 2550.00, 2550.00, 0.00, '2026-02-25', 'paid', '2026-02-23 04:18:12'),
(24, 79, 3, 36.00, 36.00, 0.00, '2026-02-28', 'paid', '2026-02-26 17:18:24'),
(25, 81, 3, 100.00, 0.00, 100.00, '2026-03-25', 'unpaid', '2026-03-20 09:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`activity_id`, `user_id`, `activity_type`, `description`, `created_at`) VALUES
(1, 1, 'USER_CREATE', 'Created new user (owner): sydney', '2026-01-25 06:32:10'),
(2, 1, 'USER_STATUS', 'Changed user_id 4 status to inactive', '2026-01-25 06:58:27'),
(3, 1, 'USER_STATUS', 'Changed user_id 3 status to inactive', '2026-01-25 06:58:32'),
(4, 1, 'USER_CREATE', 'Created new user (owner): kriz', '2026-01-25 07:26:32'),
(5, 2, 'SALE_CREATED', 'Created sale #3 total ₱10,000,000.00', '2026-01-25 09:12:56'),
(6, 2, 'DELIVERY_UPDATE', 'Updated delivery receipt for sale #3 (status: pending)', '2026-01-25 09:13:47'),
(7, 2, 'PAYMENT_REQUEST', 'Sent payment request for sale #3 to 09859958194', '2026-01-25 09:14:09'),
(8, 2, 'SALE_CREATED', 'Created sale #4 total ₱18,000.00', '2026-01-25 09:37:37'),
(9, 2, 'SALE_CREATED', 'Created sale #0 total ₱20,000.00', '2026-01-25 10:45:38'),
(10, 2, 'SALE_CREATED', 'Created sale #0 total ₱18,000.00', '2026-01-25 10:45:55'),
(11, 2, 'SALE_CREATE', 'Created sale #8 (CASH) total: 340', '2026-01-25 11:17:36'),
(12, 2, 'SALE_CREATE', 'Created sale #18 (paid) total ₱1,700.00', '2026-01-26 11:01:43'),
(13, 2, 'SALE_CREATE', 'Created sale #19 (unpaid) total ₱550.00', '2026-01-26 11:02:37'),
(14, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #19 amount ₱250.00', '2026-01-26 11:03:08'),
(15, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #17 amount ₱680.00', '2026-01-26 11:03:49'),
(16, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #2', '2026-01-26 11:09:40'),
(17, 1, 'USER_CREATE', 'Created new user (owner): johndoe', '2026-01-26 11:22:01'),
(18, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #19 amount ₱300.00', '2026-01-27 03:01:22'),
(19, 2, 'SALE_CREATE', 'Created sale #20 (unpaid) total ₱1,375.00', '2026-01-27 03:02:10'),
(20, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #20 amount ₱500.00', '2026-01-27 03:03:29'),
(21, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #20 amount ₱375.00', '2026-01-27 03:03:50'),
(22, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #20 amount ₱500.00', '2026-01-27 03:04:03'),
(23, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #3', '2026-01-27 03:11:14'),
(24, 2, 'SALE_CREATE', 'Created sale #21 (unpaid) total ₱1,375.00', '2026-01-27 06:10:04'),
(25, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #21 amount ₱375.00', '2026-01-27 06:59:34'),
(26, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #21 amount ₱1,000.00', '2026-01-27 06:59:53'),
(27, 2, 'SALE_CREATE', 'Created sale #22 (unpaid) total ₱1,375.00', '2026-01-27 07:24:29'),
(28, 2, 'SALE_CREATE', 'Created sale #24 (paid) total ₱40,715.00', '2026-01-27 07:28:03'),
(29, 2, 'SALE_CREATE', 'Created sale #26 (unpaid) total ₱680.00', '2026-01-27 07:34:22'),
(30, 1, 'PRODUCT', 'Added product: Princess Sydney - B (SKU: PS123)', '2026-01-27 07:41:54'),
(31, 1, 'PRODUCT', 'Edited product #9: Princess Sydney - B (SKU: PS123)', '2026-01-27 07:42:23'),
(32, 1, 'PRODUCT', 'Added product: Super Sydney - A (SKU: SS11)', '2026-01-27 07:47:48'),
(33, 1, 'USER_STATUS', 'Changed user_id 6 status to inactive', '2026-01-27 07:53:27'),
(34, 1, 'USER_STATUS', 'Changed user_id 6 status to active', '2026-01-27 07:53:37'),
(35, 5, 'RETURN_REJECTED', 'RETURN_REJECTED for Return #4', '2026-01-27 07:57:59'),
(36, 5, 'PROFILE_UPDATE', 'Updated profile username to \'kriezyl\'', '2026-01-27 10:31:23'),
(37, 6, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #5', '2026-01-27 11:55:40'),
(38, 2, 'SALE_CREATE', 'Created sale #28 (paid) total ₱550.00', '2026-01-27 13:00:24'),
(39, 1, 'USER_STATUS', 'Changed user_id 4 status to active', '2026-01-28 05:22:17'),
(40, 1, 'USER_STATUS', 'Changed user_id 4 status to inactive', '2026-01-28 05:22:20'),
(41, 1, 'PRODUCT', 'Added product: Premium Rice - A (SKU: PR345)', '2026-01-28 05:48:37'),
(42, 2, 'SALE_CREATE', 'Created sale #30 (paid) total ₱1,500.00', '2026-01-28 07:45:35'),
(43, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #26 amount ₱680.00', '2026-01-28 07:45:48'),
(44, 2, 'SALE_CREATE', 'Created sale #31 (unpaid) total ₱55.00', '2026-01-28 08:15:42'),
(45, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #22 amount ₱1,375.00', '2026-01-28 08:17:30'),
(46, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #31 amount ₱55.00', '2026-01-28 08:18:01'),
(47, 1, 'PRODUCT', 'Added product: Sakura - B (SKU: SAK342)', '2026-01-28 08:37:44'),
(48, 1, 'PRODUCT', 'Added product: Malagkit Rice - B (SKU: MAL903)', '2026-01-28 08:56:35'),
(49, 2, 'SALE_CREATE', 'Created sale #32 (paid) total ₱385.00', '2026-01-29 11:01:46'),
(50, 2, 'SALE_CREATE', 'Created sale #33 (paid) total ₱275.00', '2026-01-29 11:03:19'),
(51, 2, 'SALE_CREATE', 'Created sale #34 (unpaid) total ₱265.00', '2026-01-29 11:09:44'),
(52, 2, 'SALE_CREATE', 'Created sale #35 (unpaid) total ₱60.00', '2026-01-29 11:14:46'),
(53, 2, 'SALE_CREATE', 'Created sale #36 (paid) total ₱85.00', '2026-01-29 11:15:22'),
(54, 2, 'SALE_CREATE', 'Created sale #37 (paid) total ₱250.00', '2026-01-29 11:26:51'),
(55, 2, 'SALE_CREATE', 'Created sale #38 (unpaid) total ₱265.00', '2026-01-29 11:27:25'),
(56, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #38 amount ₱265.00', '2026-01-29 11:28:10'),
(57, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #35 amount ₱60.00', '2026-01-29 11:36:17'),
(58, 2, 'SALE_CREATE', 'Created sale #39 (paid) total ₱240.00', '2026-01-29 11:36:58'),
(59, 2, 'SALE_CREATE', 'Created sale #40 (paid) total ₱125.00', '2026-01-29 11:46:15'),
(60, 2, 'SALE_CREATE', 'Created sale #41 (unpaid) total ₱300.00', '2026-01-29 11:46:45'),
(61, 2, 'SALE_CREATE', 'Created sale #42 (unpaid) total ₱110.00', '2026-01-29 11:47:41'),
(62, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #34 amount ₱265.00', '2026-01-29 11:48:04'),
(63, 1, 'PRODUCT', 'Added product: Black Rice - A (SKU: BLA972)', '2026-01-30 11:02:37'),
(64, 2, 'SALE_CREATE', 'Created sale #43 (paid) total ₱550.00', '2026-01-30 11:09:46'),
(65, 2, 'SALE_CREATE', 'Created sale #44 (paid) total ₱120.00', '2026-01-30 11:21:24'),
(66, 2, 'SALE_CREATE', 'Created sale #45 (paid) total ₱50.00', '2026-01-30 12:17:36'),
(67, 2, 'SALE_CREATE', 'Created sale #46 (paid) total ₱180.00', '2026-01-30 12:21:06'),
(68, 2, 'SALE_CREATE', 'Created sale #47 (unpaid) total ₱550.00', '2026-01-30 12:21:52'),
(69, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #41 amount ₱300.00', '2026-01-30 12:25:41'),
(70, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #42 amount ₱110.00', '2026-01-30 12:29:12'),
(71, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #47 amount ₱250.00', '2026-01-30 12:31:01'),
(72, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #47 amount ₱150.00', '2026-01-30 12:31:41'),
(73, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #47 amount ₱150.00', '2026-01-30 12:32:29'),
(74, 2, 'SALE_CREATE', 'Created sale #50 (unpaid) total ₱720.00', '2026-01-30 13:28:47'),
(75, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #50 amount ₱720.00', '2026-01-30 13:29:08'),
(76, 2, 'SALE_CREATE', 'Created sale #51 (paid) total ₱1,200.00', '2026-01-30 13:29:24'),
(77, 2, 'SALE_CREATE', 'Created sale #52 (paid) total ₱530.00', '2026-01-31 05:38:59'),
(78, 2, 'SALE_CREATE', 'Created sale #53 (unpaid) total ₱75.00', '2026-01-31 05:39:38'),
(79, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #53 amount ₱75.00', '2026-01-31 05:43:47'),
(80, 1, 'USER_STATUS', 'Changed user_id 6 status to inactive', '2026-02-03 01:44:30'),
(81, 2, 'SALE_CREATE', 'Created sale #54 (paid) total ₱250.00', '2026-02-03 04:22:06'),
(82, 2, 'SALE_CREATE', 'Created sale #55 (unpaid) total ₱550.00', '2026-02-03 04:22:36'),
(83, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #55 amount ₱550.00', '2026-02-03 04:24:18'),
(84, 2, 'SALE_CREATE', 'Created sale #57 (unpaid) total ₱470.00', '2026-02-03 09:09:18'),
(85, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #57 amount ₱470.00', '2026-02-03 09:09:50'),
(86, 2, 'SALE_CREATE', 'Created sale #58 (paid) total ₱300.00', '2026-02-05 03:37:54'),
(87, 2, 'SALE_CREATE', 'Created sale #59 (paid) total ₱530.00', '2026-02-05 09:06:29'),
(88, 2, 'SALE_CREATE', 'Created sale #60 (unpaid) total ₱800.00', '2026-02-05 09:07:13'),
(89, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #60 amount ₱800.00', '2026-02-05 09:07:28'),
(90, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #6', '2026-02-05 09:14:42'),
(91, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #7', '2026-02-06 04:32:49'),
(92, 2, 'SALE_CREATE', 'Created sale #61 (unpaid) total ₱300.00', '2026-02-06 06:01:36'),
(93, 2, 'SALE_CREATE', 'Created sale #63 (paid) total ₱500.00', '2026-02-06 06:10:56'),
(94, 2, 'SALE_CREATE', 'Created sale #64 (paid) total ₱600.00', '2026-02-06 06:14:01'),
(95, 2, 'SALE_CREATE', 'Created sale #65 (unpaid) total ₱550.00', '2026-02-06 06:15:59'),
(96, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #65 amount ₱100.00', '2026-02-06 06:18:32'),
(97, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #65 amount ₱450.00', '2026-02-06 06:19:05'),
(98, 2, 'SALE_CREATE', 'Created sale #66 (paid) gross ₱750.00 disc ₱150.00 net ₱600.00', '2026-02-06 06:27:53'),
(99, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #61 amount ₱100.00', '2026-02-06 06:35:25'),
(100, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #61 amount ₱200.00', '2026-02-06 06:35:57'),
(101, 2, 'SALE_CREATE', 'Created sale #67 (paid) gross ₱540.00 disc ₱0.00 net ₱540.00', '2026-02-08 04:08:53'),
(102, 1, 'PRODUCT', 'Added product: Sakura - A (15kg sack)', '2026-02-22 03:19:47'),
(103, 1, 'PRODUCT', 'Added product: Japonesa - A (50kg sack)', '2026-02-22 03:25:35'),
(104, 1, 'PRODUCT', 'Edited product #14: Black Rice - A (10kg sack)', '2026-02-22 04:37:53'),
(105, 1, 'PRODUCT', 'Edited product #13: Young Chow - B (50kg sack)', '2026-02-22 04:39:46'),
(106, 1, 'PRODUCT', 'Edited product #12: Jasmine Rice (Local) - B (50kg sack)', '2026-02-22 04:39:53'),
(107, 1, 'PRODUCT', 'Edited product #10: Mang Bigas - A (10kg sack)', '2026-02-22 04:40:09'),
(108, 1, 'PRODUCT', 'Edited product #2: Valencia Rice (Special) - B (50kg sack)', '2026-02-22 04:40:56'),
(109, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=1000) after password confirmation', '2026-02-22 05:25:25'),
(110, 1, 'PRODUCT', 'Edited product #13: Young Chow - B (50kg/sack)', '2026-02-22 06:03:58'),
(111, 1, 'PRODUCT', 'Edited product #12: Jasmine Rice (Local) - B (50kg/sack)', '2026-02-22 06:04:05'),
(112, 1, 'PRODUCT', 'Edited product #11: Japonika - A (10kg/sack)', '2026-02-22 06:04:13'),
(113, 1, 'PRODUCT', 'Edited product #10: Mang Bigas - A (10kg/sack)', '2026-02-22 06:04:24'),
(114, 1, 'PRODUCT', 'Edited product #9: Square Line - B (10kg/sack)', '2026-02-22 06:04:30'),
(115, 1, 'PRODUCT', 'Edited product #5: Valencia Red Rice - B (50kg/sack)', '2026-02-22 06:04:36'),
(116, 1, 'PRODUCT', 'Edited product #4: Red Rice - A (50kg/sack)', '2026-02-22 06:04:42'),
(117, 1, 'PRODUCT', 'Edited product #7: Masipag Valencia - A (25kg/sack)', '2026-02-22 06:04:48'),
(118, 1, 'PRODUCT', 'Edited product #6: Angelica Rice - A (25kg/sack)', '2026-02-22 06:04:56'),
(119, 1, 'PRODUCT', 'Edited product #2: Valencia Rice (Special) - B (50kg/sack)', '2026-02-22 06:05:05'),
(120, 1, 'PRODUCT', 'Edited product #1: Valencia Rice - B (50kg/sack)', '2026-02-22 06:05:12'),
(121, 2, 'SALE_CREATE', 'Created sale #68 (paid) gross ₱89.34 disc ₱0.00 net ₱89.34', '2026-02-22 06:09:53'),
(122, 2, 'SALE_CREATE', 'Created sale #69 (paid) gross ₱500.00 disc ₱0.00 net ₱500.00', '2026-02-22 06:18:13'),
(123, 2, 'SALE_CREATE', 'Created sale #70 (unpaid) gross ₱242.00 disc ₱0.00 net ₱242.00', '2026-02-22 06:26:19'),
(124, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #70 amount ₱200.00', '2026-02-22 06:27:08'),
(125, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #70 amount ₱42.00', '2026-02-22 06:30:12'),
(126, 2, 'SALE_CREATE', 'Created sale #71 (paid) gross ₱400.00 disc ₱80.00 net ₱320.00', '2026-02-22 09:01:30'),
(127, 2, 'SALE_CREATE', 'Created sale #73 (paid) gross ₱4,800.00 disc ₱960.00 net ₱3,840.00', '2026-02-22 09:02:31'),
(128, 2, 'SALE_CREATE', 'Created sale #74 (paid) gross ₱720.00 disc ₱0.00 net ₱720.00', '2026-02-22 09:05:58'),
(129, 1, 'PRODUCT', 'Added product: Princess Sydney - B (15kg/sack)', '2026-02-22 12:50:43'),
(130, 1, 'PRODUCT', 'Edited product #17: Princess Sydney - B (15kg/sack)', '2026-02-22 12:55:21'),
(131, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=2000) after password confirmation', '2026-02-23 02:07:14'),
(132, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=1000) after password confirmation', '2026-02-23 02:08:54'),
(133, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=1000) after password confirmation', '2026-02-23 02:17:26'),
(134, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=1000)', '2026-02-23 02:19:39'),
(135, 1, 'SECURITY', 'Updated stock thresholds (LOW=70, OVER=1000)', '2026-02-23 03:21:11'),
(136, 5, 'PASSWORD_CHANGE', 'Changed account password', '2026-02-23 04:10:58'),
(137, 2, 'SALE_CREATE', 'Created sale #75 (paid) gross ₱240.00 disc ₱0.00 net ₱240.00', '2026-02-23 04:17:03'),
(138, 2, 'SALE_CREATE', 'Created sale #76 (unpaid) gross ₱2,550.00 disc ₱0.00 net ₱2,550.00', '2026-02-23 04:18:12'),
(139, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #76 amount ₱2,000.00', '2026-02-23 04:19:14'),
(140, 2, 'PAYMENT_RECEIVED', 'Payment received for Sale #76 amount ₱550.00', '2026-02-23 04:19:24'),
(141, 2, 'SALE_CREATE', 'Created sale #77 (paid) gross ₱1,000.00 disc ₱200.00 net ₱800.00', '2026-02-23 04:20:00'),
(142, 2, 'SALE_CREATE', 'Created sale #78 (paid) gross ₱54.00 disc ₱10.80 net ₱43.20', '2026-02-23 04:20:33'),
(143, 1, 'PRODUCT', 'Edited product #12: Jasmine Rice (Local) - B (50kg/sack)', '2026-02-23 04:25:24'),
(144, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #8', '2026-02-23 04:29:49'),
(145, 1, 'SECURITY', 'Updated stock thresholds (LOW=50, OVER=500)', '2026-02-26 17:02:56'),
(146, 1, 'SECURITY', 'Updated stock thresholds (LOW=70, OVER=1000)', '2026-02-26 17:03:46'),
(147, 1, 'PRODUCT', 'Added product: Java Rice - Premium (20kg/sack)', '2026-02-26 17:05:49'),
(148, 1, 'PRODUCT', 'Archived product #18', '2026-02-26 17:06:43'),
(149, 1, 'PRODUCT', 'Added product: Java Rice - Premium (25kg/sack)', '2026-02-26 17:09:47'),
(150, 1, 'USER_CREATE', 'Created new user (cashier): TTrib', '2026-02-26 17:16:43'),
(151, 7, 'SALE_CREATE', 'Created sale #79 (unpaid) gross ₱45.00 disc ₱9.00 net ₱36.00', '2026-02-26 17:18:24'),
(152, 7, 'PAYMENT_RECEIVED', 'Payment received for Sale #79 amount ₱36.00', '2026-02-26 17:19:52'),
(153, 7, 'SALE_CREATE', 'Created sale #80 (paid) gross ₱1,295.00 disc ₱259.00 net ₱1,036.00', '2026-02-26 17:24:29'),
(154, 2, 'SALE_CREATE', 'Created sale #81 (unpaid) gross ₱100.00 disc ₱0.00 net ₱100.00', '2026-03-20 09:56:30'),
(155, 5, 'RETURN_APPROVED', 'RETURN_APPROVED for Return #9', '2026-03-20 12:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `phone`, `address`, `created_at`) VALUES
(1, 'Kriezyl', 'Villlalobos', '09129823923', 'Zone 7 Block 6 Kabina Kauswagan Cagayan de Oro City', '2026-01-24 10:59:49'),
(2, 'Sydney', 'Magsalay', '0972838634', 'Carmen', '2026-01-25 11:54:27'),
(3, 'Kirk', 'Maxilom', '09138326434', 'COC', '2026-01-25 12:59:46'),
(4, 'Kristina Cass', 'Merida', '091234567897', 'Carmen', '2026-01-26 10:59:43'),
(5, 'Jayson', 'Belmes', '091234567895', 'carmen', '2026-01-27 07:22:51'),
(6, 'Britt', 'Jaramillo', '09526347981', 'cdo', '2026-01-29 02:17:19'),
(7, 'Jecel', 'Cabasag', '093745637456', 'coc carmen', '2026-01-30 11:09:23'),
(8, 'Bill', 'Gates', '09086219285', 'USA', '2026-02-26 17:20:42');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_receipts`
--

CREATE TABLE `delivery_receipts` (
  `receipts_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivered_by` varchar(100) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `status` enum('pending','delivery','delivered') DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_receipts`
--

INSERT INTO `delivery_receipts` (`receipts_id`, `sale_id`, `delivery_date`, `delivered_by`, `received_by`, `status`, `remarks`) VALUES
(1, 3, '2026-01-25', '', '', 'pending', '');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `discount_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `inventTrans_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty_kg` decimal(10,2) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` enum('sale','purchase','return','delivery','adjust') DEFAULT NULL,
  `type` enum('in','out','adjust') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`inventTrans_id`, `product_id`, `qty_kg`, `reference_id`, `reference_type`, `type`, `note`, `created_at`) VALUES
(1, NULL, NULL, NULL, 'purchase', 'in', NULL, '2026-01-24 04:53:57'),
(2, NULL, NULL, NULL, 'purchase', 'in', NULL, '2026-01-24 04:53:59'),
(3, NULL, NULL, NULL, 'purchase', 'in', NULL, '2026-01-24 04:54:00'),
(4, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 04:54:04'),
(5, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 04:55:00'),
(6, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 04:55:00'),
(7, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 04:55:02'),
(8, NULL, NULL, NULL, 'purchase', 'in', NULL, '2026-01-24 04:55:10'),
(9, NULL, NULL, NULL, 'purchase', 'in', NULL, '2026-01-24 04:56:19'),
(10, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 06:12:35'),
(11, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 06:14:25'),
(12, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 06:20:33'),
(13, NULL, NULL, NULL, 'sale', 'out', NULL, '2026-01-24 06:34:01'),
(16, 1, 200.00, 101, 'purchase', 'in', 'Initial stock', '2026-01-24 07:38:43'),
(17, 2, 150.00, 102, 'purchase', 'in', 'Initial stock', '2026-01-24 07:38:43'),
(18, 1, 1.00, 2147483647, '', 'out', 'wala lang', '2026-01-24 07:48:28'),
(19, 2, 2.00, NULL, '', 'out', 'padala sa imong mama', '2026-01-24 08:34:18'),
(20, 2, 1000.00, 2147483647, 'purchase', 'in', 'stock pa more', '2026-01-24 10:43:16'),
(21, 2, -100.00, NULL, '', 'adjust', 'change lang', '2026-01-24 11:51:56'),
(22, 1, -1.00, 2, 'sale', 'out', 'Sold via cashier', '2026-01-24 12:30:18'),
(23, 2, 100.00, NULL, '', 'adjust', 'stock add', '2026-01-25 05:00:49'),
(24, 1, 5.00, 8, 'sale', 'out', 'Sale deduction', '2026-01-25 11:17:36'),
(25, 2, 10.00, 9, 'sale', 'out', NULL, '2026-01-25 11:39:07'),
(26, 1, 1000.00, 2147483647, 'purchase', 'in', 'delivered by supplier', '2026-01-25 12:22:36'),
(27, 2, 5.00, 11, 'sale', 'out', 'Sale #11 - deducted stock', '2026-01-25 12:59:08'),
(31, 1, 5.00, 15, 'sale', 'out', 'Sale #15 - deducted stock', '2026-01-26 09:25:38'),
(32, 2, 33.00, 16, 'sale', 'out', 'Sale #16 - deducted stock', '2026-01-26 09:26:19'),
(33, 1, 10.00, 17, 'sale', 'out', 'Sale #17 - deducted stock', '2026-01-26 10:42:27'),
(34, 6, 500.00, 0, 'purchase', 'in', 'Dliverd by supplier', '2026-01-26 10:46:37'),
(35, 2, 100.00, 0, 'purchase', 'in', 'Delivered by supplier', '2026-01-26 10:47:11'),
(36, 6, 20.00, 18, 'sale', 'out', 'Sale #18 - deducted stock', '2026-01-26 11:01:43'),
(37, 2, 10.00, 19, 'sale', 'out', 'Sale #19 - deducted stock', '2026-01-26 11:02:37'),
(38, 2, 10.00, 2, 'return', 'in', 'Approved return #2', '2026-01-26 11:09:40'),
(39, 2, 25.00, 20, 'sale', 'out', 'Sale #20 - deducted stock', '2026-01-27 03:02:10'),
(40, 2, 25.00, 3, 'return', 'in', 'Approved return #3', '2026-01-27 03:11:14'),
(41, 2, 25.00, 21, 'sale', 'out', 'Sale #21 - deducted stock', '2026-01-27 06:10:04'),
(42, 2, 25.00, 22, 'sale', 'out', 'Sale #22 - deducted stock', '2026-01-27 07:24:29'),
(43, 6, 479.00, 24, 'sale', 'out', 'Sale #24 - deducted stock', '2026-01-27 07:28:03'),
(44, 1, 10.00, 26, 'sale', 'out', 'Sale #26 - deducted stock', '2026-01-27 07:34:22'),
(45, 9, 500.00, NULL, '', 'in', 'Manual stock adjustment', '2026-01-27 07:43:22'),
(46, 9, 50.00, NULL, '', 'in', 'Manual stock adjustment', '2026-01-27 07:46:07'),
(47, 10, 500.00, 134352345, 'purchase', 'in', 'Stock received (Add Stock)', '2026-01-27 07:50:32'),
(48, 2, 25.00, 5, 'return', 'in', 'Approved return #5', '2026-01-27 11:55:40'),
(49, 2, 10.00, 28, 'sale', 'out', 'Sale #28 - deducted stock', '2026-01-27 13:00:24'),
(50, 11, 10.00, 1, 'purchase', 'in', 'Delivered by the supplier', '2026-01-28 05:49:42'),
(51, 11, 100.00, 2, 'purchase', 'in', 'received by staff', '2026-01-28 06:48:30'),
(52, 10, 50.00, 30, 'sale', 'out', 'Sale #30 - deducted stock', '2026-01-28 07:45:35'),
(53, 6, 200.00, 3, 'purchase', 'in', 'delivered', '2026-01-28 07:54:07'),
(54, 2, 1.00, 31, 'sale', 'out', 'Sale #31 - deducted stock', '2026-01-28 08:15:42'),
(55, 12, 100.00, 4, 'purchase', 'in', 'received by staff', '2026-01-28 08:38:18'),
(56, 10, 20.00, 5, 'purchase', 'in', 'delivered by supplier', '2026-01-28 08:47:21'),
(57, 8, 200.00, 6, 'purchase', 'in', 'None', '2026-01-28 08:53:54'),
(58, 13, 50.00, 7, 'purchase', 'in', 'done', '2026-01-28 08:57:23'),
(59, 9, 60.00, 8, 'purchase', 'in', 'delivered to warehouse', '2026-01-29 02:13:55'),
(60, 4, 60.00, 9, 'purchase', 'in', 'received by staff', '2026-01-29 02:22:32'),
(61, 2, 7.00, 32, 'sale', 'out', 'Sale #32 - deducted stock', '2026-01-29 11:01:46'),
(62, 2, 5.00, 33, 'sale', 'out', 'Sale #33 - deducted stock', '2026-01-29 11:03:19'),
(63, 12, 5.00, 34, 'sale', 'out', 'Sale #34 - deducted stock', '2026-01-29 11:09:44'),
(64, 10, 2.00, 35, 'sale', 'out', 'Sale #35 - deducted stock', '2026-01-29 11:14:46'),
(65, 6, 1.00, 36, 'sale', 'out', 'Sale #36 - deducted stock', '2026-01-29 11:15:22'),
(66, 9, 10.00, 37, 'sale', 'out', 'Sale #37 - deducted stock', '2026-01-29 11:26:51'),
(67, 12, 5.00, 38, 'sale', 'out', 'Sale #38 - deducted stock', '2026-01-29 11:27:25'),
(68, 10, 8.00, 39, 'sale', 'out', 'Sale #39 - deducted stock', '2026-01-29 11:36:58'),
(69, 9, 5.00, 40, 'sale', 'out', 'Sale #40 - deducted stock', '2026-01-29 11:46:15'),
(70, 10, 10.00, 41, 'sale', 'out', 'Sale #41 - deducted stock', '2026-01-29 11:46:45'),
(71, 2, 2.00, 42, 'sale', 'out', 'Sale #42 - deducted stock', '2026-01-29 11:47:41'),
(72, 5, 100.00, 10, 'purchase', 'in', 'delivered', '2026-01-29 11:52:47'),
(73, 14, 20.00, 11, 'purchase', 'in', 'DELIVERED', '2026-01-30 11:03:12'),
(74, 2, 10.00, 43, 'sale', 'out', 'Sale #43 - deducted stock', '2026-01-30 11:09:46'),
(75, 10, 4.00, 44, 'sale', 'out', 'Sale #44 - deducted stock', '2026-01-30 11:21:24'),
(76, 11, 99.00, 12, 'purchase', 'in', 'test', '2026-01-30 12:00:12'),
(77, 14, 100.00, 13, 'purchase', 'in', 'test', '2026-01-30 12:01:01'),
(78, 7, 1.00, 14, 'purchase', 'in', 'test', '2026-01-30 12:02:22'),
(79, 9, 2.00, 45, 'sale', 'out', 'Sale #45 - deducted stock', '2026-01-30 12:17:36'),
(80, 10, 6.00, 46, 'sale', 'out', 'Sale #46 - deducted stock', '2026-01-30 12:21:06'),
(81, 2, 10.00, 47, 'sale', 'out', 'Sale #47 - deducted stock', '2026-01-30 12:21:52'),
(82, 11, 9.00, 50, 'sale', 'out', 'Sale #50 - deducted stock', '2026-01-30 13:28:47'),
(83, 10, 40.00, 51, 'sale', 'out', 'Sale #51 - deducted stock', '2026-01-30 13:29:24'),
(84, 12, 10.00, 52, 'sale', 'out', 'Sale #52 - deducted stock', '2026-01-31 05:38:59'),
(85, 9, 3.00, 53, 'sale', 'out', 'Sale #53 - deducted stock', '2026-01-31 05:39:38'),
(86, 9, 10.00, 54, 'sale', 'out', 'Sale #54 - deducted stock', '2026-02-03 04:22:06'),
(87, 2, 10.00, 55, 'sale', 'out', 'Sale #55 - deducted stock', '2026-02-03 04:22:36'),
(88, 13, 10.00, 57, 'sale', 'out', 'Sale #57 - deducted stock', '2026-02-03 09:09:18'),
(89, 10, 10.00, 58, 'sale', 'out', 'Sale #58 - deducted stock', '2026-02-05 03:37:54'),
(90, 12, 10.00, 59, 'sale', 'out', 'Sale #59 - deducted stock', '2026-02-05 09:06:29'),
(91, 11, 10.00, 60, 'sale', 'out', 'Sale #60 - deducted stock', '2026-02-05 09:07:13'),
(92, 8, 30.00, 15, 'purchase', 'in', 'delivered', '2026-02-05 09:09:34'),
(93, 14, 10.00, 16, 'purchase', 'in', 'test', '2026-02-05 09:11:51'),
(94, 11, 10.00, 6, 'return', 'in', 'Approved return #6', '2026-02-05 09:14:42'),
(95, 10, 40.00, 7, 'return', 'in', 'Approved return #7', '2026-02-06 04:32:49'),
(96, 10, 10.00, 61, 'sale', 'out', 'Sale #61 - deducted stock', '2026-02-06 06:01:36'),
(97, 4, 10.00, 17, 'purchase', 'in', 'test', '2026-02-06 06:03:06'),
(98, 9, 20.00, 63, 'sale', 'out', 'Sale #63 - deducted stock', '2026-02-06 06:10:56'),
(99, 10, 20.00, 64, 'sale', 'out', 'Sale #64 - deducted stock', '2026-02-06 06:14:01'),
(100, 2, 10.00, 65, 'sale', 'out', 'Sale #65 - deducted stock', '2026-02-06 06:15:59'),
(101, 4, 10.00, 66, 'sale', 'out', 'Sale #66 - deducted stock', '2026-02-06 06:27:53'),
(102, 11, 10.00, 67, 'sale', 'out', 'Sale #67 - deducted stock', '2026-02-08 04:08:53'),
(103, 15, 150.00, 18, 'purchase', 'in', 'delivered', '2026-02-22 03:21:16'),
(104, 16, 1000.00, 19, 'purchase', 'in', 'test', '2026-02-22 03:34:51'),
(105, 8, 500.00, 20, 'purchase', 'in', 'test | sacks=10 | sack_size=50kg | supplier/sack=₱1,000.00 | transport=₱1,000.00 | cost/kg=₱22.00 | markup/kg=₱20.00 | sell/kg=₱42.00', '2026-02-22 04:29:41'),
(106, 15, 2.00, 68, 'sale', 'out', 'Sale #68 - 2.00 kg deducted', '2026-02-22 06:09:53'),
(107, 5, 10.00, 69, 'sale', 'out', 'Sale #69 - 10.00 kg deducted', '2026-02-22 06:18:13'),
(108, 16, 10.00, 70, 'sale', 'out', 'Sale #70 - 10.00 kg deducted', '2026-02-22 06:26:19'),
(109, 1, 10.00, 71, 'sale', 'out', 'Sale #71 - 10.00 kg deducted', '2026-02-22 09:01:30'),
(110, 2, 100.00, 73, 'sale', 'out', 'Sale #73 - 2 sack(s) deducted', '2026-02-22 09:02:31'),
(111, 6, 15.00, 74, 'sale', 'out', 'Sale #74 - 15.00 kg deducted', '2026-02-22 09:05:58'),
(112, 17, 150.00, 21, 'purchase', 'in', 'test | sacks=10 | sack_size=15kg | supplier/sack=₱2,000.00 | transport=₱100.00 | cost/kg=₱134.00 | markup/kg=₱5.00 | sell/kg=₱139.00', '2026-02-22 12:51:15'),
(113, 17, 50.00, NULL, '', 'out', 'test', '2026-02-22 13:15:13'),
(114, 2, 10.00, NULL, '', 'out', 'TEST LUNGS', '2026-02-23 02:56:45'),
(115, 1, 60.00, NULL, 'adjust', 'out', 'test', '2026-02-23 03:13:46'),
(116, 6, 5.00, 75, 'sale', 'out', 'Sale #75 - 5.00 kg deducted', '2026-02-23 04:17:03'),
(117, 5, 50.00, 76, 'sale', 'out', 'Sale #76 - 1 sack(s) deducted', '2026-02-23 04:18:12'),
(118, 10, 20.00, 77, 'sale', 'out', 'Sale #77 - 2 sack(s) deducted', '2026-02-23 04:20:00'),
(119, 12, 1.00, 78, 'sale', 'out', 'Sale #78 - 1.00 kg deducted', '2026-02-23 04:20:33'),
(120, 12, 500.00, 22, 'purchase', 'in', 'test | sacks=10 | sack_size=50kg | supplier/sack=₱1,000.00 | transport=₱150.00 | cost/kg=₱20.30 | markup/kg=₱4.00 | sell/kg=₱24.30', '2026-02-23 04:24:54'),
(121, 5, 25.00, 8, 'return', 'in', 'Approved return #8', '2026-02-23 04:29:49'),
(122, 19, 125.00, 23, 'purchase', 'in', 'Stock received (Receiving) | sacks=5 | sack_size=25kg | supplier/sack=₱45.00 | transport=₱20.00 | cost/kg=₱1.96 | markup/kg=₱10.00 | sell/kg=₱11.96', '2026-02-26 17:14:08'),
(123, 19, 25.00, NULL, 'adjust', 'out', 'Manual adjustment', '2026-02-26 17:14:55'),
(124, 19, 25.00, 79, 'sale', 'out', 'Sale #79 - 1 sack(s) deducted', '2026-02-26 17:18:24'),
(125, 19, 25.00, 80, 'sale', 'out', 'Sale #80 - 1 sack(s) deducted', '2026-02-26 17:24:29'),
(126, 13, 5.00, 80, 'sale', 'out', 'Sale #80 - 5.00 kg deducted', '2026-02-26 17:24:29'),
(127, 12, 50.00, 80, 'sale', 'out', 'Sale #80 - 1 sack(s) deducted', '2026-02-26 17:24:29'),
(128, 13, 2.00, 81, 'sale', 'out', 'Sale #81 - 2.00 kg deducted', '2026-03-20 09:56:30'),
(129, 12, 50.00, 9, 'return', 'in', 'Approved return #9', '2026-03-20 12:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `device_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`log_id`, `user_id`, `login_time`, `device_info`, `ip_address`) VALUES
(1, 2, '2026-01-25 10:45:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(2, 5, '2026-01-25 11:41:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(3, 2, '2026-01-25 11:42:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(4, 1, '2026-01-25 11:53:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(5, 2, '2026-01-25 11:53:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(6, 2, '2026-01-25 12:14:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(7, 2, '2026-01-25 12:14:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(8, 2, '2026-01-25 12:16:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(9, 1, '2026-01-25 12:21:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(10, 2, '2026-01-25 12:25:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(11, 1, '2026-01-25 13:02:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(12, 5, '2026-01-25 13:04:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(13, 1, '2026-01-26 04:14:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(14, 5, '2026-01-26 04:15:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(15, 5, '2026-01-26 05:09:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(16, 1, '2026-01-26 05:10:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(17, 5, '2026-01-26 05:11:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(18, 1, '2026-01-26 05:39:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(19, 2, '2026-01-26 08:43:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(20, 2, '2026-01-26 08:58:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(21, 5, '2026-01-26 09:29:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(22, 2, '2026-01-26 09:29:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(23, 5, '2026-01-26 09:55:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(24, 2, '2026-01-26 10:30:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(25, 5, '2026-01-26 10:43:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(26, 1, '2026-01-26 10:45:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(27, 2, '2026-01-26 10:59:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(28, 5, '2026-01-26 11:06:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(29, 1, '2026-01-26 11:13:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(30, 1, '2026-01-26 11:16:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(31, 1, '2026-01-26 11:21:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(32, 6, '2026-01-26 11:22:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(33, 2, '2026-01-27 02:35:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(34, 5, '2026-01-27 03:09:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(35, 2, '2026-01-27 03:11:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(36, 1, '2026-01-27 03:12:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(37, 2, '2026-01-27 06:07:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(38, 1, '2026-01-27 06:10:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(39, 5, '2026-01-27 06:12:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(40, 2, '2026-01-27 06:58:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(41, 1, '2026-01-27 07:01:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(42, 5, '2026-01-27 07:02:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(43, 1, '2026-01-27 07:06:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(44, 2, '2026-01-27 07:11:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(45, 5, '2026-01-27 07:12:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(46, 2, '2026-01-27 07:21:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(47, 1, '2026-01-27 07:38:13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(48, 5, '2026-01-27 07:57:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(49, 1, '2026-01-27 08:02:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(50, 2, '2026-01-27 08:06:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(51, 5, '2026-01-27 08:06:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(52, 2, '2026-01-27 08:07:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(53, 1, '2026-01-27 08:08:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(54, 1, '2026-01-27 09:02:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(55, 1, '2026-01-27 09:05:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(56, 2, '2026-01-27 09:20:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(57, 2, '2026-01-27 09:20:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(58, 5, '2026-01-27 09:25:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(59, 5, '2026-01-27 10:22:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(60, 1, '2026-01-27 10:35:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(61, 2, '2026-01-27 11:06:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(62, 6, '2026-01-27 11:48:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(63, 2, '2026-01-27 11:56:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(64, 1, '2026-01-27 12:26:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(65, 2, '2026-01-27 12:29:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(66, 2, '2026-01-28 01:09:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(67, 1, '2026-01-28 01:10:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(68, 2, '2026-01-28 05:10:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(69, 1, '2026-01-28 05:19:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(70, 5, '2026-01-28 06:49:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(71, 5, '2026-01-28 07:38:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(72, 1, '2026-01-28 07:38:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(73, 2, '2026-01-28 07:45:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(74, 1, '2026-01-28 07:46:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(75, 5, '2026-01-28 07:54:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(76, 1, '2026-01-28 07:56:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(77, 2, '2026-01-28 08:00:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(78, 1, '2026-01-28 08:18:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(79, 5, '2026-01-28 08:19:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(80, 5, '2026-01-28 08:26:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(81, 1, '2026-01-28 08:26:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(82, 2, '2026-01-28 08:28:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(83, 1, '2026-01-28 08:28:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(84, 5, '2026-01-28 08:40:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(85, 1, '2026-01-28 08:40:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(86, 5, '2026-01-28 08:54:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(87, 1, '2026-01-28 08:55:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(88, 5, '2026-01-28 08:57:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(89, 1, '2026-01-29 02:12:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(90, 2, '2026-01-29 02:15:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(91, 5, '2026-01-29 02:17:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(92, 1, '2026-01-29 02:19:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(93, 5, '2026-01-29 02:23:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(94, 1, '2026-01-29 07:34:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(95, 2, '2026-01-29 10:13:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(96, 5, '2026-01-29 10:42:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(97, 1, '2026-01-29 10:49:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(98, 2, '2026-01-29 10:57:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(99, 5, '2026-01-29 11:04:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(100, 2, '2026-01-29 11:08:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(101, 5, '2026-01-29 11:12:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(102, 1, '2026-01-29 11:12:54', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(103, 2, '2026-01-29 11:13:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(104, 5, '2026-01-29 11:37:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(105, 2, '2026-01-29 11:39:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(106, 1, '2026-01-29 11:48:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(107, 5, '2026-01-29 11:50:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(108, 1, '2026-01-29 11:52:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(109, 5, '2026-01-29 11:53:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(110, 5, '2026-01-30 10:05:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(111, 1, '2026-01-30 10:12:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(112, 5, '2026-01-30 10:24:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(113, 2, '2026-01-30 10:57:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(114, 1, '2026-01-30 10:57:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(115, 5, '2026-01-30 11:04:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(116, 1, '2026-01-30 11:05:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(117, 2, '2026-01-30 11:08:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(118, 1, '2026-01-30 11:10:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(119, 5, '2026-01-30 11:11:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(120, 2, '2026-01-30 11:21:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(121, 5, '2026-01-30 11:42:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(122, 1, '2026-01-30 11:47:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(123, 5, '2026-01-30 11:52:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(124, 1, '2026-01-30 11:53:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(125, 5, '2026-01-30 12:02:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(126, 1, '2026-01-30 12:04:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(127, 2, '2026-01-30 12:16:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(128, 5, '2026-01-30 12:29:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(129, 2, '2026-01-30 12:30:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(130, 5, '2026-01-30 12:33:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(131, 2, '2026-01-30 12:41:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(132, 1, '2026-01-30 13:29:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(133, 5, '2026-01-30 13:31:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(134, 2, '2026-01-31 05:38:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(135, 1, '2026-01-31 08:08:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(136, 5, '2026-01-31 08:14:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(137, 1, '2026-01-31 08:28:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(138, 5, '2026-02-03 01:33:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(139, 2, '2026-02-03 01:37:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(140, 1, '2026-02-03 01:39:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(141, 1, '2026-02-03 01:43:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(142, 5, '2026-02-03 03:27:03', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(143, 2, '2026-02-03 04:21:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(144, 1, '2026-02-03 04:25:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(145, 1, '2026-02-03 05:29:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(146, 1, '2026-02-03 09:00:42', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '192.168.1.72'),
(147, 2, '2026-02-03 09:07:27', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '192.168.1.72'),
(148, 2, '2026-02-03 09:11:34', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '192.168.1.77'),
(149, 2, '2026-02-05 03:37:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(150, 1, '2026-02-05 05:57:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(151, 1, '2026-02-05 06:13:20', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/144.0.7559.95 Mobile/15E148 Safari/604.1', '192.168.1.72'),
(152, 5, '2026-02-05 06:36:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(153, 1, '2026-02-05 06:58:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(154, 2, '2026-02-05 07:34:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(155, 2, '2026-02-05 07:48:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(156, 5, '2026-02-05 08:03:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(157, 1, '2026-02-05 08:28:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(158, 5, '2026-02-05 08:44:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(159, 1, '2026-02-05 08:57:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(160, 2, '2026-02-05 09:02:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(161, 5, '2026-02-05 09:02:18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(162, 1, '2026-02-05 09:03:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(163, 1, '2026-02-05 09:05:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(164, 2, '2026-02-05 09:05:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(165, 5, '2026-02-05 09:05:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(166, 2, '2026-02-05 09:06:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(167, 5, '2026-02-05 09:08:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(168, 1, '2026-02-05 09:08:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(169, 5, '2026-02-05 09:10:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(170, 1, '2026-02-05 09:10:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(171, 5, '2026-02-05 09:12:09', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(172, 2, '2026-02-05 09:14:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(173, 5, '2026-02-05 09:14:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(174, 5, '2026-02-05 09:15:53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(175, 1, '2026-02-05 09:29:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(176, 1, '2026-02-05 09:58:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(177, 1, '2026-02-05 10:17:07', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(178, 5, '2026-02-05 10:17:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(179, 2, '2026-02-05 10:17:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(180, 1, '2026-02-06 04:26:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(181, 2, '2026-02-06 04:31:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(182, 5, '2026-02-06 04:32:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(183, 1, '2026-02-06 04:33:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(184, 5, '2026-02-06 04:55:46', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(185, 2, '2026-02-06 06:01:17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(186, 5, '2026-02-06 06:01:50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(187, 1, '2026-02-06 06:02:32', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(188, 5, '2026-02-06 06:03:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(189, 2, '2026-02-06 06:04:20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(190, 1, '2026-02-06 06:37:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(191, 5, '2026-02-06 06:37:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(192, 2, '2026-02-06 06:39:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(193, 5, '2026-02-06 07:06:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(194, 1, '2026-02-06 07:15:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(195, 2, '2026-02-06 13:19:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(196, 1, '2026-02-08 03:47:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(197, 2, '2026-02-08 04:06:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(198, 5, '2026-02-08 04:09:26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '::1'),
(199, 1, '2026-02-22 02:39:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(200, 2, '2026-02-22 03:14:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(201, 1, '2026-02-22 03:18:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(202, 1, '2026-02-22 04:51:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(203, 2, '2026-02-22 05:40:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(204, 2, '2026-02-22 06:05:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(205, 1, '2026-02-22 06:05:55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(206, 2, '2026-02-22 06:09:33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(207, 1, '2026-02-22 06:25:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(208, 2, '2026-02-22 06:25:34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(209, 1, '2026-02-22 06:26:35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(210, 2, '2026-02-22 06:26:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(211, 1, '2026-02-22 06:50:52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(212, 5, '2026-02-22 07:04:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(213, 5, '2026-02-22 07:33:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(214, 1, '2026-02-22 08:19:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(215, 1, '2026-02-22 08:32:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(216, 2, '2026-02-22 09:01:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(217, 1, '2026-02-22 09:03:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(218, 2, '2026-02-22 09:05:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(219, 1, '2026-02-22 09:06:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(220, 1, '2026-02-22 09:08:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(221, 1, '2026-02-22 09:25:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(222, 5, '2026-02-22 09:45:37', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(223, 1, '2026-02-22 10:16:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(224, 2, '2026-02-22 10:16:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(225, 1, '2026-02-22 10:17:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(226, 5, '2026-02-22 10:19:04', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(227, 1, '2026-02-22 10:39:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(228, 5, '2026-02-22 10:40:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(229, 5, '2026-02-22 11:49:16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(230, 1, '2026-02-22 11:51:11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(231, 1, '2026-02-22 12:38:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(232, 5, '2026-02-22 13:15:57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(233, 2, '2026-02-22 13:18:21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(234, 1, '2026-02-23 01:58:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(235, 5, '2026-02-23 02:02:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(236, 1, '2026-02-23 02:06:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(237, 2, '2026-02-23 02:07:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(238, 5, '2026-02-23 02:08:08', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(239, 1, '2026-02-23 02:08:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(240, 5, '2026-02-23 02:09:22', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(241, 1, '2026-02-23 02:09:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(242, 1, '2026-02-23 02:17:51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(243, 2, '2026-02-23 02:20:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(244, 1, '2026-02-23 02:24:49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(245, 2, '2026-02-23 02:26:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(246, 5, '2026-02-23 02:31:14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(247, 1, '2026-02-23 02:31:41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(248, 1, '2026-02-23 02:52:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(249, 2, '2026-02-23 03:14:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(250, 1, '2026-02-23 03:20:48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(251, 2, '2026-02-23 03:21:28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(252, 5, '2026-02-23 03:21:40', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(253, 1, '2026-02-23 03:22:27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(254, 5, '2026-02-23 03:29:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(255, 1, '2026-02-23 03:44:30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(256, 1, '2026-02-23 04:02:15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(257, 5, '2026-02-23 04:02:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(258, 5, '2026-02-23 04:11:36', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(259, 2, '2026-02-23 04:11:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(260, 1, '2026-02-23 04:15:44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(261, 2, '2026-02-23 04:16:47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(262, 1, '2026-02-23 04:20:58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(263, 5, '2026-02-23 04:22:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(264, 1, '2026-02-23 04:24:02', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(265, 5, '2026-02-23 04:26:00', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(266, 2, '2026-02-23 04:28:43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(267, 5, '2026-02-23 04:29:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(268, 1, '2026-02-23 04:30:38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '::1'),
(269, 1, '2026-02-26 16:59:12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(270, 7, '2026-02-26 17:17:10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(271, 1, '2026-02-26 17:26:39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(272, 5, '2026-02-26 17:27:29', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(273, 1, '2026-02-26 17:36:45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '::1'),
(274, 1, '2026-03-20 09:13:59', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '::1'),
(275, 2, '2026-03-20 09:50:31', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '::1'),
(276, 5, '2026-03-20 12:07:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '::1'),
(277, 1, '2026-03-20 12:14:25', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '::1'),
(278, 5, '2026-03-20 12:17:42', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` enum('cash','gcash','bank') DEFAULT NULL,
  `status` enum('pending','paid','failed') DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `external_ref` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `sale_id`, `amount`, `method`, `status`, `paid_at`, `external_ref`) VALUES
(1, 8, 340.00, 'cash', 'paid', '2026-01-25 11:17:36', NULL),
(2, 9, 550.00, 'cash', 'paid', '2026-01-25 11:39:07', NULL),
(3, 16, 815.00, 'cash', 'paid', '2026-01-26 09:26:59', ''),
(4, 16, 1000.00, 'cash', 'paid', '2026-01-26 10:42:57', ''),
(5, 19, 250.00, 'cash', 'paid', '2026-01-26 11:03:08', ''),
(6, 17, 680.00, 'cash', 'paid', '2026-01-26 11:03:49', ''),
(7, 19, 300.00, 'cash', 'paid', '2026-01-27 03:01:22', ''),
(8, 20, 500.00, 'cash', 'paid', '2026-01-27 03:03:29', ''),
(9, 20, 375.00, 'cash', 'paid', '2026-01-27 03:03:50', ''),
(10, 20, 500.00, 'cash', 'paid', '2026-01-27 03:04:03', ''),
(11, 21, 375.00, 'cash', 'paid', '2026-01-27 06:59:34', ''),
(12, 21, 1000.00, 'cash', 'paid', '2026-01-27 06:59:53', ''),
(13, 26, 680.00, 'cash', 'paid', '2026-01-28 07:45:48', ''),
(14, 22, 1375.00, 'cash', 'paid', '2026-01-28 08:17:30', ''),
(15, 31, 55.00, 'cash', 'paid', '2026-01-28 08:18:01', ''),
(16, 38, 265.00, 'cash', 'paid', '2026-01-29 11:28:10', ''),
(17, 35, 60.00, 'cash', 'paid', '2026-01-29 11:36:17', ''),
(18, 34, 265.00, 'cash', 'paid', '2026-01-29 11:48:04', ''),
(19, 41, 300.00, 'cash', 'paid', '2026-01-30 12:25:41', ''),
(20, 42, 110.00, 'cash', 'paid', '2026-01-30 12:29:12', ''),
(21, 47, 250.00, 'cash', 'paid', '2026-01-30 12:31:01', ''),
(22, 47, 150.00, 'cash', 'paid', '2026-01-30 12:31:41', ''),
(23, 47, 150.00, 'cash', 'paid', '2026-01-30 12:32:29', ''),
(24, 50, 720.00, 'cash', 'paid', '2026-01-30 13:29:08', ''),
(25, 53, 75.00, 'cash', 'paid', '2026-01-31 05:43:47', ''),
(26, 55, 550.00, 'cash', 'paid', '2026-02-03 04:24:18', ''),
(27, 57, 470.00, 'cash', 'paid', '2026-02-03 09:09:50', ''),
(28, 60, 800.00, 'cash', 'paid', '2026-02-05 09:07:28', ''),
(29, 65, 100.00, 'cash', 'paid', '2026-02-06 06:18:32', ''),
(30, 65, 450.00, 'cash', 'paid', '2026-02-06 06:19:05', ''),
(31, 61, 100.00, 'cash', 'paid', '2026-02-06 06:35:25', ''),
(32, 61, 200.00, 'cash', 'paid', '2026-02-06 06:35:57', ''),
(33, 70, 200.00, 'cash', 'paid', '2026-02-22 06:27:08', ''),
(34, 70, 42.00, 'cash', 'paid', '2026-02-22 06:30:12', ''),
(35, 76, 2000.00, 'cash', 'paid', '2026-02-23 04:19:14', ''),
(36, 76, 550.00, 'cash', 'paid', '2026-02-23 04:19:24', ''),
(37, 79, 36.00, 'gcash', 'paid', '2026-02-26 17:19:52', '');

-- --------------------------------------------------------

--
-- Table structure for table `payment_request`
--

CREATE TABLE `payment_request` (
  `pay_req_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','paid','expired') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_request`
--

INSERT INTO `payment_request` (`pay_req_id`, `sale_id`, `phone`, `requested_at`, `status`) VALUES
(1, 3, '09859958194', '2026-01-25 09:14:09', '');

-- --------------------------------------------------------

--
-- Table structure for table `penalties`
--

CREATE TABLE `penalties` (
  `penalty_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `reference_type` enum('payable','receivable') NOT NULL,
  `days_late` int(11) DEFAULT 0,
  `penalty_amount` decimal(10,2) DEFAULT 0.00,
  `penalty_rate` decimal(10,2) DEFAULT 10.00,
  `last_calculated` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalties`
--

INSERT INTO `penalties` (`penalty_id`, `reference_id`, `reference_type`, `days_late`, `penalty_amount`, `penalty_rate`, `last_calculated`, `created_at`) VALUES
(1, 8, 'payable', 14, 140.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(2, 9, 'payable', 18, 180.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(3, 10, 'payable', 19, 190.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(4, 11, 'payable', 18, 180.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(5, 12, 'payable', 19, 190.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(6, 14, 'payable', 21, 210.00, 10.00, '2026-03-20', '2026-03-20 09:49:33'),
(7, 25, 'receivable', 21, 210.00, 10.00, '2026-04-15', '2026-04-15 10:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `penalty_waivers`
--

CREATE TABLE `penalty_waivers` (
  `waiver_id` int(11) NOT NULL,
  `reference_type` enum('payable','receivable') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `waived_by` int(11) DEFAULT NULL,
  `waived_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `variety` varchar(100) DEFAULT NULL,
  `grade` enum('A','B') NOT NULL,
  `unit_weight_kg` decimal(10,2) DEFAULT NULL,
  `price_per_sack` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `stock_kg` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `variety`, `grade`, `unit_weight_kg`, `price_per_sack`, `price_per_kg`, `created_at`, `archived`, `stock_kg`) VALUES
(1, 'Valencia Rice', 'B', 50.00, 2300.00, 40.00, '2026-01-24 07:38:02', 0, 1000.00),
(2, 'Valencia Rice (Special)', 'B', 50.00, 2400.00, 49.00, '2026-01-24 07:38:02', 0, 100.00),
(3, 'HR Jasmine', 'A', 50.00, 54.00, 0.00, '2026-01-24 09:26:13', 1, 0.00),
(4, 'Red Rice', 'A', 50.00, 2600.00, 60.00, '2026-01-25 11:15:32', 0, 500.00),
(5, 'Valencia Red Rice', 'B', 50.00, 2550.00, 50.00, '2026-01-25 11:15:32', 0, 800.00),
(6, 'Angelica Rice', 'A', 25.00, 1100.00, 48.00, '2026-01-25 11:15:32', 0, 900.00),
(7, 'Masipag Valencia', 'A', 25.00, 1000.00, 59.00, '2026-01-25 11:15:32', 0, 150.00),
(8, 'NFA Rice', 'A', 50.00, 1000.00, 42.00, '2026-01-25 11:15:32', 0, 300.00),
(9, 'Square Line', 'B', 10.00, 2350.00, 45.00, '2026-01-27 07:41:54', 0, 550.00),
(10, 'Mang Bigas', 'A', 10.00, 500.00, 52.00, '2026-01-27 07:47:48', 0, 500.00),
(11, 'Japonika', 'A', 10.00, 5500.00, 57.00, '2026-01-28 05:48:37', 0, 110.00),
(12, 'Jasmine Rice (Local)', 'B', 50.00, 1000.00, 40.30, '2026-01-28 08:37:44', 0, 100.00),
(13, 'Young Chow', 'B', 50.00, 2450.00, 50.00, '2026-01-28 08:56:35', 0, 0.00),
(14, 'Black Rice', 'A', 10.00, 800.00, 40.00, '2026-01-30 11:02:37', 0, 0.00),
(15, 'Sakura', 'A', 15.00, 600.00, 44.67, '2026-02-22 03:19:47', 0, 0.00),
(16, 'Japonesa', 'A', 50.00, 1000.00, 24.20, '2026-02-22 03:25:35', 0, 0.00),
(17, 'Princess Sydney', 'B', 15.00, 600.00, 139.00, '2026-02-22 12:50:43', 0, -50.00),
(18, 'Java Rice', '', 20.00, 750.00, 55.00, '2026-02-26 17:05:49', 1, 0.00),
(19, 'Java Rice', '', 25.00, 45.00, 11.96, '2026-02-26 17:09:47', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchases_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','received','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchases_id`, `supplier_id`, `purchase_date`, `total_amount`, `status`, `created_by`, `created_at`) VALUES
(1, 1, '2026-01-28', 0.00, 'received', 1, '2026-01-28 05:49:42'),
(2, 1, '2026-01-29', 4000.00, 'received', 1, '2026-01-28 06:48:30'),
(3, 1, '2026-01-30', 10000.00, 'received', 1, '2026-01-28 07:54:07'),
(4, 1, '2026-02-01', 0.00, 'received', 1, '2026-01-28 08:38:18'),
(5, 1, '2026-02-05', 0.00, 'received', 1, '2026-01-28 08:47:21'),
(6, 1, '2026-02-05', 11200.00, 'received', 1, '2026-01-28 08:53:54'),
(7, 2, '2026-01-30', 0.00, 'received', 1, '2026-01-28 08:57:23'),
(8, 2, '2026-01-31', 0.00, 'received', 1, '2026-01-29 02:13:55'),
(9, 3, '2026-01-29', 3360.00, 'received', 1, '2026-01-29 02:22:32'),
(10, 3, '2026-01-29', 4300.00, 'received', 1, '2026-01-29 11:52:47'),
(11, 2, '2026-02-28', 0.00, 'received', 1, '2026-01-30 11:03:12'),
(12, 1, '2026-01-30', 0.00, 'received', 1, '2026-01-30 12:00:12'),
(13, 1, '2026-01-30', 0.00, 'received', 1, '2026-01-30 12:01:01'),
(14, 3, '2026-01-30', 0.00, 'received', 1, '2026-01-30 12:02:22'),
(15, 3, '2026-02-28', 0.00, 'received', 1, '2026-02-05 09:09:34'),
(16, 3, '2026-02-05', 400.00, 'received', 1, '2026-02-05 09:11:51'),
(17, 2, '2026-02-06', 440.00, 'received', 1, '2026-02-06 06:03:06'),
(18, 1, '2026-02-23', 6100.00, 'received', 1, '2026-02-22 03:21:16'),
(19, 2, '2026-02-22', 20200.00, 'received', 1, '2026-02-22 03:34:51'),
(20, 1, '2026-02-23', 11000.00, 'received', 1, '2026-02-22 04:29:41'),
(21, 2, '2026-02-22', 20100.00, 'received', 1, '2026-02-22 12:51:15'),
(22, 3, '2026-02-24', 10150.00, 'received', 1, '2026-02-23 04:24:54'),
(23, 2, '2026-02-26', 245.00, 'received', 1, '2026-02-26 17:14:08');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('pending','ordered','received','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `po_item_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_notif_logs`
--

CREATE TABLE `push_notif_logs` (
  `push_notif_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed') DEFAULT NULL,
  `device_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `push_notif_logs`
--

INSERT INTO `push_notif_logs` (`push_notif_id`, `payment_id`, `customer_id`, `message`, `sent_at`, `status`, `device_token`) VALUES
(1, NULL, 1, 'Hi! You have an unpaid balance of ₱680.00 for Sale #17. Please settle it by 2026-02-05. Thank you!', '2026-01-26 10:42:27', 'sent', NULL),
(2, NULL, 2, 'Payment received for Sale #16: ₱1,000.00. Thank you!', '2026-01-26 10:42:57', 'sent', NULL),
(3, NULL, 2, 'Hi! You have an unpaid balance of ₱550.00 for Sale #19. Please settle it by 2026-02-12. Thank you!', '2026-01-26 11:02:37', 'sent', NULL),
(4, NULL, 2, 'Payment received for Sale #19: ₱250.00. Thank you!', '2026-01-26 11:03:08', 'sent', NULL),
(5, NULL, 1, 'Payment received for Sale #17: ₱680.00. Thank you!', '2026-01-26 11:03:49', 'sent', NULL),
(6, NULL, 2, 'Payment received for Sale #19: ₱300.00. Thank you!', '2026-01-27 03:01:22', 'sent', NULL),
(7, NULL, 3, 'Hi! You have an unpaid balance of ₱1,375.00 for Sale #20. Please settle it by 2026-02-12. Thank you!', '2026-01-27 03:02:10', 'sent', NULL),
(8, NULL, 3, 'Payment received for Sale #20: ₱500.00. Thank you!', '2026-01-27 03:03:29', 'sent', NULL),
(9, NULL, 3, 'Payment received for Sale #20: ₱375.00. Thank you!', '2026-01-27 03:03:50', 'sent', NULL),
(10, NULL, 3, 'Payment received for Sale #20: ₱500.00. Thank you!', '2026-01-27 03:04:03', 'sent', NULL),
(11, NULL, 4, 'Hi! You have an unpaid balance of ₱1,375.00 for Sale #21. Please settle it by 2026-01-30. Thank you!', '2026-01-27 06:10:04', 'sent', NULL),
(12, NULL, 4, 'Payment received for Sale #21: ₱375.00. Thank you!', '2026-01-27 06:59:34', 'sent', NULL),
(13, NULL, 4, 'Payment received for Sale #21: ₱1,000.00. Thank you!', '2026-01-27 06:59:53', 'sent', NULL),
(14, NULL, 5, 'Hi! You have an unpaid balance of ₱1,375.00 for Sale #22. Please settle it by 2026-02-04. Thank you!', '2026-01-27 07:24:29', 'sent', NULL),
(15, NULL, 5, 'Hi! You have an unpaid balance of ₱680.00 for Sale #26. Please settle it by 2026-02-14. Thank you!', '2026-01-27 07:34:22', 'sent', NULL),
(16, NULL, 5, 'Payment received for Sale #26: ₱680.00. Thank you!', '2026-01-28 07:45:48', 'sent', NULL),
(17, NULL, 3, 'Hi! You have an unpaid balance of ₱55.00 for Sale #31. Please settle it by 2026-01-30. Thank you!', '2026-01-28 08:15:42', 'sent', NULL),
(18, NULL, 5, 'Payment received for Sale #22: ₱1,375.00. Thank you!', '2026-01-28 08:17:30', 'sent', NULL),
(19, NULL, 3, 'Payment received for Sale #31: ₱55.00. Thank you!', '2026-01-28 08:18:01', 'sent', NULL),
(20, NULL, 2, 'Hi! You have an unpaid balance of ₱265.00 for Sale #34. Please settle it by 2026-01-30. Thank you!', '2026-01-29 11:09:44', 'sent', NULL),
(21, NULL, 1, 'Hi! You have an unpaid balance of ₱60.00 for Sale #35. Please settle it by 2026-01-30. Thank you!', '2026-01-29 11:14:46', 'sent', NULL),
(22, NULL, 2, 'Hi! You have an unpaid balance of ₱265.00 for Sale #38. Please settle it by 2026-01-30. Thank you!', '2026-01-29 11:27:25', 'sent', NULL),
(23, NULL, 2, 'Payment received for Sale #38: ₱265.00. Thank you!', '2026-01-29 11:28:10', 'sent', NULL),
(24, NULL, 1, 'Payment received for Sale #35: ₱60.00. Thank you!', '2026-01-29 11:36:17', 'sent', NULL),
(25, NULL, 2, 'Hi! You have an unpaid balance of ₱300.00 for Sale #41. Please settle it by 2026-01-30. Thank you!', '2026-01-29 11:46:45', 'sent', NULL),
(26, NULL, 2, 'Hi! You have an unpaid balance of ₱110.00 for Sale #42. Please settle it by 2026-01-30. Thank you!', '2026-01-29 11:47:41', 'sent', NULL),
(27, NULL, 2, 'Payment received for Sale #34: ₱265.00. Thank you!', '2026-01-29 11:48:04', 'sent', NULL),
(28, NULL, 7, 'Hi! You have an unpaid balance of ₱550.00 for Sale #47. Please settle it by 2026-01-30. Thank you!', '2026-01-30 12:21:52', 'sent', NULL),
(29, NULL, 2, 'Payment received for Sale #41: ₱300.00. Thank you!', '2026-01-30 12:25:41', 'sent', NULL),
(30, NULL, 2, 'Payment received for Sale #42: ₱110.00. Thank you!', '2026-01-30 12:29:12', 'sent', NULL),
(31, NULL, 7, 'Payment received for Sale #47: ₱250.00. Thank you!', '2026-01-30 12:31:01', 'sent', NULL),
(32, NULL, 7, 'Payment received for Sale #47: ₱150.00. Thank you!', '2026-01-30 12:31:41', 'sent', NULL),
(33, NULL, 7, 'Payment received for Sale #47: ₱150.00. Thank you!', '2026-01-30 12:32:29', 'sent', NULL),
(34, NULL, 4, 'Hi! You have an unpaid balance of ₱720.00 for Sale #50. Please settle it by 2026-02-02. Thank you!', '2026-01-30 13:28:47', 'sent', NULL),
(35, NULL, 4, 'Payment received for Sale #50: ₱720.00. Thank you!', '2026-01-30 13:29:08', 'sent', NULL),
(36, NULL, 2, 'Hi! You have an unpaid balance of ₱75.00 for Sale #53. Please settle it by 2026-02-02. Thank you!', '2026-01-31 05:39:38', 'sent', NULL),
(37, NULL, 2, 'Payment received for Sale #53: ₱75.00. Thank you!', '2026-01-31 05:43:47', 'sent', NULL),
(38, NULL, 3, 'Hi! You have an unpaid balance of ₱550.00 for Sale #55. Please settle it by 2026-02-15. Thank you!', '2026-02-03 04:22:36', 'sent', NULL),
(39, NULL, 3, 'Payment received for Sale #55: ₱550.00. Thank you!', '2026-02-03 04:24:18', 'sent', NULL),
(40, NULL, 6, 'Hi! You have an unpaid balance of ₱470.00 for Sale #57. Please settle it by 2026-02-15. Thank you!', '2026-02-03 09:09:18', 'sent', NULL),
(41, NULL, 6, 'Payment received for Sale #57: ₱470.00. Thank you!', '2026-02-03 09:09:50', 'sent', NULL),
(42, NULL, 3, 'Hi! You have an unpaid balance of ₱800.00 for Sale #60. Please settle it by 2026-02-15. Thank you!', '2026-02-05 09:07:13', 'sent', NULL),
(43, NULL, 3, 'Payment received for Sale #60: ₱800.00. Thank you!', '2026-02-05 09:07:28', 'sent', NULL),
(44, NULL, 2, 'Hi! You have an unpaid balance of ₱300.00 for Sale #61. Please settle it by 2026-02-15. Thank you!', '2026-02-06 06:01:36', 'sent', NULL),
(45, NULL, 5, 'Hi! You have an unpaid balance of ₱550.00 for Sale #65. Please settle it by 2026-02-15. Thank you!', '2026-02-06 06:15:59', 'sent', NULL),
(46, NULL, 5, 'Payment received for Sale #65: ₱100.00. Thank you!', '2026-02-06 06:18:32', 'sent', NULL),
(47, NULL, 5, 'Payment received for Sale #65: ₱450.00. Thank you!', '2026-02-06 06:19:05', 'sent', NULL),
(48, NULL, 2, 'Payment received for Sale #61: ₱100.00. Thank you!', '2026-02-06 06:35:25', 'sent', NULL),
(49, NULL, 2, 'Payment received for Sale #61: ₱200.00. Thank you!', '2026-02-06 06:35:57', 'sent', NULL),
(50, NULL, 4, 'Hi! You have an unpaid balance of ₱242.00 for Sale #70. Please settle it by 2026-02-25. Thank you!', '2026-02-22 06:26:19', 'sent', NULL),
(51, NULL, 4, 'Payment received for Sale #70: ₱200.00. Thank you!', '2026-02-22 06:27:08', 'sent', NULL),
(52, NULL, 4, 'Payment received for Sale #70: ₱42.00. Thank you!', '2026-02-22 06:30:12', 'sent', NULL),
(53, NULL, 7, 'Hi! You have an unpaid balance of ₱2,550.00 for Sale #76. Please settle it by 2026-02-25. Thank you!', '2026-02-23 04:18:12', 'sent', NULL),
(54, NULL, 7, 'Payment received for Sale #76: ₱2,000.00. Thank you!', '2026-02-23 04:19:14', 'sent', NULL),
(55, NULL, 7, 'Payment received for Sale #76: ₱550.00. Thank you!', '2026-02-23 04:19:24', 'sent', NULL),
(56, NULL, 3, 'Hi! You have an unpaid balance of ₱36.00 for Sale #79. Please settle it by 2026-02-28. Thank you!', '2026-02-26 17:18:24', 'sent', NULL),
(57, NULL, 3, 'Payment received for Sale #79: ₱36.00. Thank you!', '2026-02-26 17:19:52', 'sent', NULL),
(58, NULL, 3, 'Hi! You have an unpaid balance of ₱100.00 for Sale #81. Please settle it by 2026-03-25. Thank you!', '2026-03-20 09:56:30', 'sent', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `return_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty_returned` decimal(10,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`return_id`, `sale_id`, `product_id`, `qty_returned`, `reason`, `return_date`, `status`) VALUES
(1, 16, 2, 12.00, 'sera naman yanxxx', '2026-01-26', 'rejected'),
(2, 16, 2, 10.00, 'sira', '2026-01-26', 'approved'),
(3, 20, 2, 25.00, 'damaged', '2026-01-27', 'approved'),
(4, 24, 6, 300.00, 'basa', '2026-01-27', 'rejected'),
(5, 21, 2, 25.00, 'damaged', '2026-01-27', 'approved'),
(6, 60, 11, 10.00, 'daot', '2026-02-05', 'approved'),
(7, 51, 10, 40.00, 'test', '2026-02-06', 'approved'),
(8, 76, 5, 25.00, 'daot test', '2026-02-23', 'approved'),
(9, 80, 12, 50.00, 'Basa inyong bugas', '2026-02-27', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sale_date` datetime NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `user_id`, `customer_id`, `sale_date`, `total_amount`, `status`, `created_at`) VALUES
(1, 2, 1, '0000-00-00 00:00:00', 20000.00, '', '2026-01-24 12:24:23'),
(2, 2, 1, '0000-00-00 00:00:00', 20000.00, '', '2026-01-24 12:30:18'),
(3, 2, 1, '2026-01-25 00:00:00', 10000000.00, 'pending', '2026-01-25 09:12:56'),
(4, 2, 1, '2026-01-25 00:00:00', 18000.00, 'pending', '2026-01-25 09:37:37'),
(7, 2, 1, '2026-01-25 00:00:00', 340.00, 'paid', '2026-01-25 11:17:36'),
(8, 2, 1, '2026-01-25 00:00:00', 340.00, 'paid', '2026-01-25 11:17:36'),
(9, 2, 1, '2026-01-25 19:39:07', 550.00, 'paid', '2026-01-25 11:39:07'),
(11, 2, 2, '2026-01-25 20:59:08', 275.00, 'paid', '2026-01-25 12:59:08'),
(15, 2, 2, '2026-01-26 17:25:38', 340.00, 'paid', '2026-01-26 09:25:38'),
(16, 2, 2, '2026-01-26 17:26:19', 1815.00, 'paid', '2026-01-26 09:26:19'),
(17, 2, 1, '2026-01-26 18:42:27', 680.00, 'paid', '2026-01-26 10:42:27'),
(18, 2, 4, '2026-01-26 19:01:43', 1700.00, 'paid', '2026-01-26 11:01:43'),
(19, 2, 2, '2026-01-26 19:02:37', 550.00, 'paid', '2026-01-26 11:02:37'),
(20, 2, 3, '2026-01-27 11:02:10', 1375.00, 'paid', '2026-01-27 03:02:10'),
(21, 2, 4, '2026-01-27 14:10:04', 1375.00, 'paid', '2026-01-27 06:10:04'),
(22, 2, 5, '2026-01-27 15:24:29', 1375.00, 'paid', '2026-01-27 07:24:29'),
(24, 2, 5, '2026-01-27 15:28:03', 40715.00, 'paid', '2026-01-27 07:28:03'),
(26, 2, 5, '2026-01-27 15:34:22', 680.00, 'paid', '2026-01-27 07:34:22'),
(28, 2, 5, '2026-01-27 21:00:24', 550.00, 'paid', '2026-01-27 13:00:24'),
(30, 2, 2, '2026-01-28 15:45:35', 1500.00, 'paid', '2026-01-28 07:45:35'),
(31, 2, 3, '2026-01-28 16:15:42', 55.00, 'paid', '2026-01-28 08:15:42'),
(32, 2, 6, '2026-01-29 19:01:46', 385.00, 'paid', '2026-01-29 11:01:46'),
(33, 2, 5, '2026-01-29 19:03:19', 275.00, 'paid', '2026-01-29 11:03:19'),
(34, 2, 2, '2026-01-29 19:09:44', 265.00, 'paid', '2026-01-29 11:09:44'),
(35, 2, 1, '2026-01-29 19:14:46', 60.00, 'paid', '2026-01-29 11:14:46'),
(36, 2, 6, '2026-01-29 19:15:22', 85.00, 'paid', '2026-01-29 11:15:22'),
(37, 2, 1, '2026-01-29 19:26:51', 250.00, 'paid', '2026-01-29 11:26:51'),
(38, 2, 2, '2026-01-29 19:27:25', 265.00, 'paid', '2026-01-29 11:27:25'),
(39, 2, 6, '2026-01-29 19:36:58', 240.00, 'paid', '2026-01-29 11:36:58'),
(40, 2, 5, '2026-01-29 19:46:15', 125.00, 'paid', '2026-01-29 11:46:15'),
(41, 2, 2, '2026-01-29 19:46:45', 300.00, 'paid', '2026-01-29 11:46:45'),
(42, 2, 2, '2026-01-29 19:47:41', 110.00, 'paid', '2026-01-29 11:47:41'),
(43, 2, 7, '2026-01-30 19:09:46', 550.00, 'paid', '2026-01-30 11:09:46'),
(44, 2, 5, '2026-01-30 19:21:24', 120.00, 'paid', '2026-01-30 11:21:24'),
(45, 2, 6, '2026-01-30 20:17:36', 50.00, 'paid', '2026-01-30 12:17:36'),
(46, 2, 3, '2026-01-30 20:21:06', 180.00, 'paid', '2026-01-30 12:21:06'),
(47, 2, 7, '2026-01-30 20:21:52', 550.00, 'paid', '2026-01-30 12:21:52'),
(50, 2, 4, '2026-01-30 21:28:47', 720.00, 'paid', '2026-01-30 13:28:47'),
(51, 2, 7, '2026-01-30 21:29:24', 1200.00, 'paid', '2026-01-30 13:29:24'),
(52, 2, 1, '2026-01-31 13:38:59', 530.00, 'paid', '2026-01-31 05:38:59'),
(53, 2, 2, '2026-01-31 13:39:38', 75.00, 'paid', '2026-01-31 05:39:38'),
(54, 2, 7, '2026-02-03 12:22:06', 250.00, 'paid', '2026-02-03 04:22:06'),
(55, 2, 3, '2026-02-03 12:22:36', 550.00, 'paid', '2026-02-03 04:22:36'),
(57, 2, 6, '2026-02-03 17:09:18', 470.00, 'paid', '2026-02-03 09:09:18'),
(58, 2, 6, '2026-02-05 11:37:54', 300.00, 'paid', '2026-02-05 03:37:54'),
(59, 2, 4, '2026-02-05 17:06:29', 530.00, 'paid', '2026-02-05 09:06:29'),
(60, 2, 3, '2026-02-05 17:07:13', 800.00, 'paid', '2026-02-05 09:07:13'),
(61, 2, 2, '2026-02-06 14:01:36', 300.00, 'paid', '2026-02-06 06:01:36'),
(63, 2, 6, '2026-02-06 14:10:56', 500.00, 'paid', '2026-02-06 06:10:56'),
(64, 2, 3, '2026-02-06 14:14:01', 600.00, 'paid', '2026-02-06 06:14:01'),
(65, 2, 5, '2026-02-06 14:15:59', 550.00, 'paid', '2026-02-06 06:15:59'),
(66, 2, 6, '2026-02-06 14:27:53', 600.00, 'paid', '2026-02-06 06:27:53'),
(67, 2, 2, '2026-02-08 12:08:53', 540.00, 'paid', '2026-02-08 04:08:53'),
(68, 2, 7, '2026-02-22 14:09:53', 89.34, 'paid', '2026-02-22 06:09:53'),
(69, 2, 6, '2026-02-22 14:18:13', 500.00, 'paid', '2026-02-22 06:18:13'),
(70, 2, 4, '2026-02-22 14:26:19', 242.00, 'paid', '2026-02-22 06:26:19'),
(71, 2, 7, '2026-02-22 17:01:30', 320.00, 'paid', '2026-02-22 09:01:30'),
(73, 2, 7, '2026-02-22 17:02:31', 3840.00, 'paid', '2026-02-22 09:02:31'),
(74, 2, 1, '2026-02-22 17:05:58', 720.00, 'paid', '2026-02-22 09:05:58'),
(75, 2, 7, '2026-02-23 12:17:03', 240.00, 'paid', '2026-02-23 04:17:03'),
(76, 2, 7, '2026-02-23 12:18:12', 2550.00, 'paid', '2026-02-23 04:18:12'),
(77, 2, 2, '2026-02-23 12:20:00', 800.00, 'paid', '2026-02-23 04:20:00'),
(78, 2, 6, '2026-02-23 12:20:33', 43.20, 'paid', '2026-02-23 04:20:33'),
(79, 7, 3, '2026-02-27 01:18:24', 36.00, 'paid', '2026-02-26 17:18:24'),
(80, 7, 3, '2026-02-27 01:24:29', 1036.00, 'paid', '2026-02-26 17:24:29'),
(81, 2, 3, '2026-03-20 17:56:30', 100.00, 'unpaid', '2026-03-20 09:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `sales_forecast`
--

CREATE TABLE `sales_forecast` (
  `forecast_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `forecasting_period` enum('daily','weekly','monthly') DEFAULT NULL,
  `forecasting_start_date` date DEFAULT NULL,
  `forecasting_end_date` date DEFAULT NULL,
  `predict_qty_kg` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `generated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `sales_item_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty_kg` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `line_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`sales_item_id`, `sale_id`, `product_id`, `qty_kg`, `unit_price`, `line_total`) VALUES
(1, 2, 1, 1.00, 20000.00, 20000.00),
(2, 3, 1, 500.00, 20000.00, 10000000.00),
(3, 4, 2, 1.00, 18000.00, 18000.00),
(6, 8, 1, 5.00, 68.00, 340.00),
(7, 9, 2, 10.00, 55.00, 550.00),
(8, 11, 2, 5.00, 55.00, 275.00),
(14, 15, 1, 5.00, 68.00, 340.00),
(15, 15, 1, 5.00, 68.00, 340.00),
(16, 16, 2, 33.00, 55.00, 1815.00),
(17, 16, 2, 33.00, 55.00, 1815.00),
(18, 17, 1, 10.00, 68.00, 680.00),
(19, 17, 1, 10.00, 68.00, 680.00),
(20, 18, 6, 20.00, 85.00, 1700.00),
(21, 18, 6, 20.00, 85.00, 1700.00),
(22, 19, 2, 10.00, 55.00, 550.00),
(23, 19, 2, 10.00, 55.00, 550.00),
(24, 20, 2, 25.00, 55.00, 1375.00),
(25, 20, 2, 25.00, 55.00, 1375.00),
(26, 21, 2, 25.00, 55.00, 1375.00),
(27, 21, 2, 25.00, 55.00, 1375.00),
(28, 22, 2, 25.00, 55.00, 1375.00),
(29, 22, 2, 25.00, 55.00, 1375.00),
(30, 24, 6, 479.00, 85.00, 40715.00),
(31, 24, 6, 479.00, 85.00, 40715.00),
(32, 26, 1, 10.00, 68.00, 680.00),
(33, 26, 1, 10.00, 68.00, 680.00),
(34, 28, 2, 10.00, 55.00, 550.00),
(35, 30, 10, 50.00, 30.00, 1500.00),
(36, 31, 2, 1.00, 55.00, 55.00),
(37, 32, 2, 7.00, 55.00, 385.00),
(38, 33, 2, 5.00, 55.00, 275.00),
(39, 34, 12, 5.00, 53.00, 265.00),
(40, 35, 10, 2.00, 30.00, 60.00),
(41, 36, 6, 1.00, 85.00, 85.00),
(42, 37, 9, 10.00, 25.00, 250.00),
(43, 38, 12, 5.00, 53.00, 265.00),
(44, 39, 10, 8.00, 30.00, 240.00),
(45, 40, 9, 5.00, 25.00, 125.00),
(46, 41, 10, 10.00, 30.00, 300.00),
(47, 42, 2, 2.00, 55.00, 110.00),
(48, 43, 2, 10.00, 55.00, 550.00),
(49, 44, 10, 4.00, 30.00, 120.00),
(50, 45, 9, 2.00, 25.00, 50.00),
(51, 46, 10, 6.00, 30.00, 180.00),
(52, 47, 2, 10.00, 55.00, 550.00),
(53, 50, 11, 9.00, 80.00, 720.00),
(54, 51, 10, 40.00, 30.00, 1200.00),
(55, 52, 12, 10.00, 53.00, 530.00),
(56, 53, 9, 3.00, 25.00, 75.00),
(57, 54, 9, 10.00, 25.00, 250.00),
(58, 55, 2, 10.00, 55.00, 550.00),
(59, 57, 13, 10.00, 47.00, 470.00),
(60, 58, 10, 10.00, 30.00, 300.00),
(61, 59, 12, 10.00, 53.00, 530.00),
(62, 60, 11, 10.00, 80.00, 800.00),
(63, 61, 10, 10.00, 30.00, 300.00),
(64, 63, 9, 20.00, 25.00, 500.00),
(65, 64, 10, 20.00, 30.00, 600.00),
(66, 65, 2, 10.00, 55.00, 550.00),
(67, 66, 4, 10.00, 75.00, 750.00),
(68, 67, 11, 10.00, 54.00, 540.00),
(69, 68, 15, 2.00, 44.67, 89.34),
(70, 69, 5, 10.00, 50.00, 500.00),
(71, 70, 16, 10.00, 24.20, 242.00),
(72, 71, 1, 10.00, 40.00, 400.00),
(73, 73, 2, 100.00, 2400.00, 4800.00),
(74, 74, 6, 15.00, 48.00, 720.00),
(75, 75, 6, 5.00, 48.00, 240.00),
(76, 76, 5, 50.00, 2550.00, 2550.00),
(77, 77, 10, 20.00, 500.00, 1000.00),
(78, 78, 12, 1.00, 54.00, 54.00),
(79, 79, 19, 25.00, 45.00, 45.00),
(80, 80, 19, 25.00, 45.00, 45.00),
(81, 80, 13, 5.00, 50.00, 250.00),
(82, 80, 12, 50.00, 1000.00, 1000.00),
(83, 81, 13, 2.00, 50.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_settings`
--

CREATE TABLE `stock_settings` (
  `id` tinyint(4) NOT NULL,
  `low_threshold_kg` decimal(10,2) NOT NULL DEFAULT 10.00,
  `over_threshold_kg` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_settings`
--

INSERT INTO `stock_settings` (`id`, `low_threshold_kg`, `over_threshold_kg`, `updated_at`) VALUES
(1, 70.00, 1000.00, '2026-02-27 01:03:46');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `contact` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `phone`, `address`, `created_at`, `status`, `contact`) VALUES
(1, 'ABC Ride Trading', '', '', '2026-01-28 05:49:02', 'active', NULL),
(2, 'Agri Oro', '0923457982', 'cdo', '2026-01-28 08:57:03', 'active', NULL),
(3, 'Grace Rice Trading', '09563287432', 'Cogon', '2026-01-29 02:21:50', 'active', NULL),
(4, 'ABC Supplier', NULL, 'Manila', '2026-03-20 11:56:09', 'active', '09123456789');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `sp_id` int(11) NOT NULL,
  `ap_id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('cash','gcash','bank') DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `paid_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`sp_id`, `ap_id`, `purchase_id`, `supplier_id`, `amount`, `method`, `reference_no`, `paid_at`, `paid_by`, `note`, `created_at`) VALUES
(1, 1, 2, 1, 4000.00, 'cash', '', '2026-01-28 15:15:06', 5, 'paid', '2026-01-28 07:15:06'),
(2, 2, 3, 1, 10000.00, 'cash', '', '2026-01-28 15:55:56', 5, '', '2026-01-28 07:55:56'),
(3, 4, 6, 1, 11200.00, 'cash', '', '2026-01-28 16:55:36', 5, '', '2026-01-28 08:55:36'),
(4, 5, 9, 3, 3360.00, 'cash', '', '2026-01-29 10:24:14', 5, 'done paid', '2026-01-29 02:24:14'),
(5, 6, 10, 3, 300.00, 'cash', '', '2026-01-29 19:53:29', 5, '', '2026-01-29 11:53:29'),
(6, 6, 10, 3, 4000.00, 'cash', '', '2026-01-29 19:53:37', 5, '', '2026-01-29 11:53:37'),
(7, 7, 16, 3, 400.00, 'cash', '', '2026-02-05 17:12:49', 5, 'paid na yan baks', '2026-02-05 09:12:49'),
(8, 13, 22, 3, 10000.00, 'cash', '', '2026-02-23 12:27:11', 5, 'test', '2026-02-23 04:27:11'),
(9, 13, 22, 3, 150.00, 'cash', '', '2026-02-23 12:27:26', 5, 'test', '2026-02-23 04:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_product_ratings`
--

CREATE TABLE `supplier_product_ratings` (
  `rating_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$VppWtCcA0hAvVzNFEdlaQetJo1i0tpvfQWbReMMZbwf5GzXCVcXIa', 'System', 'Administrator', '09123456789', 'admin', '2026-01-23 11:33:23', 'active'),
(2, 'cashier', '$2y$10$UGDooW1kbUmS0PaB/bTN2e733ocFZqW5UWdMnobci7sDvctS6qa26', 'Cashier', 'System', '09234567812', 'cashier', '2026-01-24 05:14:29', 'active'),
(3, 'owner', '$2y$10$t1DRLyLzgHAVACoL6/PY2ONF5ggtZk4jkDO.ejSacrDER9vBA59v.', 'System', 'Owner', '09123456789', '', '2026-01-24 11:58:06', 'inactive'),
(4, 'sydney', '$2y$10$4vVA7RsnWb2hlU.xIA1o9OtdbMHZdyKkQWr2Gh6coz2sPgVAHah92', 'Sydney', 'Magsalay', '09127837823', '', '2026-01-25 06:32:10', 'inactive'),
(5, 'kriezyl', '$2y$10$TugB2Vrjwi4Xbt/x5cxc8ukWJPkaTEtKAQxDSSv5LACr9acs47tFO', 'Kriz', 'Villlalobos', '09127837823', 'owner', '2026-01-25 07:26:32', 'active'),
(6, 'johndoe', '$2y$10$WZgt8/fc/2em7iVP9qFXk.18jgZFyMNturX2Fs7fp2Dv1H5ljWQhy', 'John', 'Doe', '097326432498', 'owner', '2026-01-26 11:22:01', 'inactive'),
(7, 'TTrib', '$2y$10$KOUebVU/ez6KWBQIXUFlVeYsDgLMF/eAW0FQP4upbz1boGQl3VHae', 'Britt', 'Jaramillo', '09086219285', 'cashier', '2026-02-26 17:16:43', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_payable`
--
ALTER TABLE `account_payable`
  ADD PRIMARY KEY (`ap_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `account_payable_approved_by_fk` (`approved_by`);

--
-- Indexes for table `account_receivable`
--
ALTER TABLE `account_receivable`
  ADD PRIMARY KEY (`ar_id`),
  ADD KEY `sales_id` (`sales_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD PRIMARY KEY (`receipts_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`discount_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`inventTrans_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `payment_request`
--
ALTER TABLE `payment_request`
  ADD PRIMARY KEY (`pay_req_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `penalties`
--
ALTER TABLE `penalties`
  ADD PRIMARY KEY (`penalty_id`);

--
-- Indexes for table `penalty_waivers`
--
ALTER TABLE `penalty_waivers`
  ADD PRIMARY KEY (`waiver_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_waived_by` (`waived_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchases_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `push_notif_logs`
--
ALTER TABLE `push_notif_logs`
  ADD PRIMARY KEY (`push_notif_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_forecast`
--
ALTER TABLE `sales_forecast`
  ADD PRIMARY KEY (`forecast_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`sales_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_settings`
--
ALTER TABLE `stock_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`sp_id`),
  ADD KEY `sp_ap_fk` (`ap_id`),
  ADD KEY `sp_supplier_fk` (`supplier_id`),
  ADD KEY `sp_paid_by_fk` (`paid_by`);

--
-- Indexes for table `supplier_product_ratings`
--
ALTER TABLE `supplier_product_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_payable`
--
ALTER TABLE `account_payable`
  MODIFY `ap_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `account_receivable`
--
ALTER TABLE `account_receivable`
  MODIFY `ar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  MODIFY `receipts_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `inventTrans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `payment_request`
--
ALTER TABLE `payment_request`
  MODIFY `pay_req_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penalties`
--
ALTER TABLE `penalties`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `penalty_waivers`
--
ALTER TABLE `penalty_waivers`
  MODIFY `waiver_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchases_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_notif_logs`
--
ALTER TABLE `push_notif_logs`
  MODIFY `push_notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `sales_forecast`
--
ALTER TABLE `sales_forecast`
  MODIFY `forecast_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `sales_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `sp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `supplier_product_ratings`
--
ALTER TABLE `supplier_product_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_payable`
--
ALTER TABLE `account_payable`
  ADD CONSTRAINT `account_payable_approved_by_fk` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `account_payable_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchases_id`),
  ADD CONSTRAINT `account_payable_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `account_receivable`
--
ALTER TABLE `account_receivable`
  ADD CONSTRAINT `account_receivable_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`sale_id`),
  ADD CONSTRAINT `account_receivable_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD CONSTRAINT `delivery_receipts_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`);

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`);

--
-- Constraints for table `payment_request`
--
ALTER TABLE `payment_request`
  ADD CONSTRAINT `payment_request_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`);

--
-- Constraints for table `push_notif_logs`
--
ALTER TABLE `push_notif_logs`
  ADD CONSTRAINT `push_notif_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`),
  ADD CONSTRAINT `push_notif_logs_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `sales_forecast`
--
ALTER TABLE `sales_forecast`
  ADD CONSTRAINT `sales_forecast_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `sales_forecast_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `sp_ap_fk` FOREIGN KEY (`ap_id`) REFERENCES `account_payable` (`ap_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sp_paid_by_fk` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `supplier_product_ratings`
--
ALTER TABLE `supplier_product_ratings`
  ADD CONSTRAINT `supplier_product_ratings_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
