-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2019 at 11:59 AM
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
-- Table structure for table `misc`
--

CREATE TABLE `misc` (
  `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp(3) NOT NULL DEFAULT '0000-00-00 00:00:00.000' ON UPDATE current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `misc`
--

INSERT INTO `misc` (`id`, `timestamp`) VALUES
('START', '2019-02-02 00:00:27.309');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `misc`
--
ALTER TABLE `misc`
  ADD KEY `timestamp` (`timestamp`);
COMMIT;
