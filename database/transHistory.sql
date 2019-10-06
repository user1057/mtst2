-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 06, 2019 at 12:01 PM
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
-- Table structure for table `transHistory`
--

CREATE TABLE `transHistory` (
  `price` smallint(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `buy_sell` bit(1) NOT NULL,
  `transid` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp(3) NOT NULL DEFAULT current_timestamp(3) ON UPDATE current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `transHistory`
--
ALTER TABLE `transHistory`
  ADD PRIMARY KEY (`transid`),
  ADD KEY `timestamp` (`timestamp`);
COMMIT;
