-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2019 at 12:00 PM
-- Server version: 10.3.16-MariaDB
-- PHP Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `id8614984_tradedb2`
--

-- --------------------------------------------------------

--
-- Table structure for table `params`
--

CREATE TABLE `params` (
  `id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `params`
--

INSERT INTO `params` (`id`, `value`) VALUES
('last_telegram_send', '1570359927.3245');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `params`
--
ALTER TABLE `params`
  ADD UNIQUE KEY `id_2` (`id`),
  ADD KEY `id` (`id`);
COMMIT;
