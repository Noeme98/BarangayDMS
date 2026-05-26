-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 24, 2026 at 02:29 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `file_archiving_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
CREATE TABLE IF NOT EXISTS `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) NOT NULL,
  `description` text,
  `file_category` enum('ordinance','permit','report') NOT NULL,
  `date_uploaded` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'pending',
  `visible_to` varchar(255) DEFAULT 'all',
  `target_role` varchar(50) DEFAULT 'all',
  `target_roles` text NOT NULL,
  `target_users` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `file_name`, `file_type`, `file_size`, `uploaded_by`, `upload_date`, `file_path`, `description`, `file_category`, `date_uploaded`, `status`, `visible_to`, `target_role`, `target_roles`, `target_users`) VALUES
(62, 'business_permit.pdf', NULL, NULL, 'test', '2026-05-18 14:10:53', 'uploads/ordinance/business_permit.pdf', NULL, 'ordinance', '2026-05-18 22:10:53', 'pending', 'all', 'all', 'all', NULL),
(100, 'all_test.docx', NULL, NULL, 'bipsu', '2026-05-22 09:41:37', 'uploads/ordinance/all_test.docx', NULL, 'ordinance', '2026-05-22 17:41:37', 'pending', 'all', 'all', 'all', NULL),
(101, 'sec_only.docx', NULL, NULL, 'bipsu', '2026-05-22 09:42:10', 'uploads/ordinance/sec_only.docx', NULL, 'ordinance', '2026-05-22 17:42:10', 'pending', 'all', 'all', 'secretary', NULL),
(102, 'member_only.docx', NULL, NULL, 'bipsu', '2026-05-22 09:42:48', 'uploads/ordinance/member_only.docx', NULL, 'ordinance', '2026-05-22 17:42:48', 'pending', 'all', 'all', 'member', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
