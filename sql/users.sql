-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 30, 2013 at 03:30 PM
-- Server version: 5.5.24-log
-- PHP Version: 5.4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dave_films_development`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `uid` int(7) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT '0',
  `firstname` varchar(20) NOT NULL,
  `lastname` varchar(30) NOT NULL,
  `username` varchar(20) NOT NULL,
  `email` varchar(256) NOT NULL DEFAULT '@fitzroy.nl',
  `password` varchar(32) NOT NULL,
  `bc_token` varchar(40) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `activity_id` int(11) NOT NULL,
  `hours_contract` int(2) NOT NULL DEFAULT '40',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=26 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `time_stamp`, `uid`, `role_id`, `firstname`, `lastname`, `username`, `email`, `password`, `bc_token`, `active`, `activity_id`, `hours_contract`) VALUES
(11, '2012-12-17 11:18:31', 5738721, 1, 'Dave', 'Timmerman', 'dave.timmerman', 'dave@fitzroy.nl', '8a671ef0c6fbcefcfb8c682369a66523', '1cf25d743aaba775cff4692b0c30bbcbc7e8623b', 1, 2, 36);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
