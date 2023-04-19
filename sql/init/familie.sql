-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 19, 2023 at 05:52 PM
-- Server version: 10.1.40-MariaDB
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `familie`
--

-- --------------------------------------------------------

--
-- Table structure for table `doop`
--

CREATE TABLE `doop` (
  `person` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `url` text,
  `place` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `geboorte`
--

CREATE TABLE `geboorte` (
  `child` int(11) NOT NULL,
  `father` int(11) DEFAULT NULL,
  `mother` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `url` text,
  `place` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `huwelijk`
--

CREATE TABLE `huwelijk` (
  `id` int(11) NOT NULL,
  `groom` int(11) NOT NULL,
  `bride` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `url` text,
  `place` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `overlijden`
--

CREATE TABLE `overlijden` (
  `person` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `url` text,
  `place` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `personen`
--

CREATE TABLE `personen` (
  `id` int(11) NOT NULL,
  `firstName` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `middleName` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `lastName` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `nickName` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `notes` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doop`
--
ALTER TABLE `doop`
  ADD PRIMARY KEY (`person`),
  ADD UNIQUE KEY `person` (`person`);

--
-- Indexes for table `geboorte`
--
ALTER TABLE `geboorte`
  ADD PRIMARY KEY (`child`),
  ADD UNIQUE KEY `child` (`child`);

--
-- Indexes for table `huwelijk`
--
ALTER TABLE `huwelijk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `groom` (`groom`,`bride`);

--
-- Indexes for table `overlijden`
--
ALTER TABLE `overlijden`
  ADD PRIMARY KEY (`person`),
  ADD UNIQUE KEY `person` (`person`);

--
-- Indexes for table `personen`
--
ALTER TABLE `personen`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `huwelijk`
--
ALTER TABLE `huwelijk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `personen`
--
ALTER TABLE `personen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
