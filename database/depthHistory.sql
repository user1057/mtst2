-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2019 at 11:56 AM
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
-- Table structure for table `depthHistory`
--

CREATE TABLE `depthHistory` (
  `price` smallint(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `timestamp` timestamp(3) NOT NULL DEFAULT current_timestamp(3) ON UPDATE current_timestamp(3),
  `id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `subid` varchar(20) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `depthHistory`
--

INSERT INTO `depthHistory` (`price`, `amount`, `timestamp`, `id`, `subid`) VALUES
(700, 93098.88, '2019-10-06 08:20:20.321', '5d99a3c41d4e6', '0');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `depthHistory`
--
ALTER TABLE `depthHistory`
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `timestamp_2` (`timestamp`);
COMMIT;
