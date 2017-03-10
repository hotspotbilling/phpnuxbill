-- pjl SQL Dump
-- Server version:5.6.25
-- Generated: 2015-10-30 08:21:49
-- Current PHP version: 5.6.11
-- Host: localhost
-- Database:biling
-- --------------------------------------------------------
-- Structure for 'tbl_appconfig'
--

CREATE TABLE `tbl_appconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Dump Data for `tbl_appconfig`
--

INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("1","CompanyName","PHPMixBill v5.0");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("2","theme","default");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("3","currency_code","Rp.");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("4","language","english");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("5","show-logo","1");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("6","nstyle","blue");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("7","timezone","Asia/Jakarta");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("8","dec_point",".");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("9","thousands_sep",",");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("10","rtl","0");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("11","address","Jl. Kubangsari VII No. 31 RT.03/RW.06 Bandung");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("12","phone","081322225141");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("13","date_format","d M Y");
INSERT INTO tbl_appconfig (`id`,`setting`,`value`) VALUES ("14","note","Thank you...");

-- --------------------------------------------------------
-- Structure for 'tbl_bandwidth'
--

CREATE TABLE `tbl_bandwidth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_bw` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `rate_down` int(10) unsigned NOT NULL,
  `rate_down_unit` enum('Kbps','Mbps') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `rate_up` int(10) unsigned NOT NULL,
  `rate_up_unit` enum('Kbps','Mbps') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_customers'
--

CREATE TABLE `tbl_customers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) CHARACTER SET latin1 NOT NULL,
  `password` varchar(45) CHARACTER SET latin1 NOT NULL,
  `fullname` varchar(45) CHARACTER SET latin1 NOT NULL,
  `address` text CHARACTER SET latin1,
  `phonenumber` varchar(20) CHARACTER SET latin1 DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_language'
--

CREATE TABLE `tbl_language` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `folder` varchar(32) NOT NULL,
  `author` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Dump Data for `tbl_language`
--

INSERT INTO tbl_language (`id`,`name`,`folder`,`author`) VALUES ("1","Indonesia","indonesia","Ismail Marzuqi");
INSERT INTO tbl_language (`id`,`name`,`folder`,`author`) VALUES ("2","English","english","Ismail Marzuqi");

-- --------------------------------------------------------
-- Structure for 'tbl_logs'
--

CREATE TABLE `tbl_logs` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `userid` int(10) NOT NULL,
  `ip` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_message'
--

CREATE TABLE `tbl_message` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `from_user` varchar(32) CHARACTER SET latin1 NOT NULL,
  `to_user` varchar(32) CHARACTER SET latin1 NOT NULL,
  `title` varchar(60) CHARACTER SET latin1 NOT NULL,
  `message` text CHARACTER SET latin1 NOT NULL,
  `status` enum('0','1') CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_plans'
--

CREATE TABLE `tbl_plans` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name_plan` varchar(40) CHARACTER SET latin1 NOT NULL,
  `id_bw` int(10) NOT NULL,
  `price` varchar(40) CHARACTER SET latin1 NOT NULL,
  `type` enum('Hotspot','PPPOE') CHARACTER SET latin1 NOT NULL,
  `typebp` enum('Unlimited','Limited') CHARACTER SET latin1 DEFAULT NULL,
  `limit_type` enum('Time_Limit','Data_Limit','Both_Limit') CHARACTER SET latin1 DEFAULT NULL,
  `time_limit` int(10) unsigned DEFAULT NULL,
  `time_unit` enum('Mins','Hrs') CHARACTER SET latin1 DEFAULT NULL,
  `data_limit` int(10) unsigned DEFAULT NULL,
  `data_unit` enum('MB','GB') CHARACTER SET latin1 DEFAULT NULL,
  `validity` int(10) NOT NULL,
  `validity_unit` enum('Days','Months') CHARACTER SET latin1 NOT NULL,
  `shared_users` int(10) DEFAULT NULL,
  `routers` varchar(32) CHARACTER SET latin1 NOT NULL,
  `pool` varchar(40) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_pool'
--

CREATE TABLE `tbl_pool` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `pool_name` varchar(40) NOT NULL,
  `range_ip` varchar(40) NOT NULL,
  `routers` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_routers'
--

CREATE TABLE `tbl_routers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET latin1 NOT NULL,
  `ip_address` varchar(128) CHARACTER SET latin1 NOT NULL,
  `username` varchar(50) CHARACTER SET latin1 NOT NULL,
  `password` varchar(60) CHARACTER SET latin1 NOT NULL,
  `description` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_transactions'
--

CREATE TABLE `tbl_transactions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `invoice` varchar(25) NOT NULL,
  `username` varchar(32) NOT NULL,
  `plan_name` varchar(40) NOT NULL,
  `price` varchar(40) NOT NULL,
  `recharged_on` date NOT NULL,
  `expiration` date NOT NULL,
  `time` time NOT NULL,
  `method` enum('voucher','admin') NOT NULL,
  `routers` varchar(32) NOT NULL,
  `type` enum('Hotspot','PPPOE') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_user_recharges'
--

CREATE TABLE `tbl_user_recharges` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `username` varchar(32) CHARACTER SET latin1 NOT NULL,
  `plan_id` int(10) NOT NULL,
  `namebp` varchar(40) CHARACTER SET latin1 NOT NULL,
  `recharged_on` date NOT NULL,
  `expiration` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(20) CHARACTER SET latin1 NOT NULL,
  `method` enum('voucher','admin') CHARACTER SET latin1 NOT NULL,
  `routers` varchar(32) CHARACTER SET latin1 NOT NULL,
  `type` varchar(15) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Structure for 'tbl_users'
--

CREATE TABLE `tbl_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(45) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `fullname` varchar(45) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `password` text CHARACTER SET latin1 NOT NULL,
  `user_type` enum('Admin','Sales') CHARACTER SET latin1 NOT NULL,
  `status` enum('Active','Inactive') CHARACTER SET latin1 NOT NULL DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  `creationdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Dump Data for `tbl_users`
--

INSERT INTO tbl_users (`id`,`username`,`fullname`,`password`,`user_type`,`status`,`last_login`,`creationdate`) VALUES ("1","admin","Ismail Marzuqi","$1$W44.ns/.$MUnR0NeBH9xAcXm0Oku2h1","Admin","Active","2015-10-30 18:27:02","2014-06-23 01:43:07");

-- --------------------------------------------------------
-- Structure for 'tbl_voucher'
--

CREATE TABLE `tbl_voucher` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` enum('Hotspot','PPPOE') CHARACTER SET latin1 NOT NULL,
  `routers` varchar(32) CHARACTER SET latin1 NOT NULL,
  `id_plan` int(10) NOT NULL,
  `code` varchar(55) CHARACTER SET latin1 NOT NULL,
  `user` varchar(45) CHARACTER SET latin1 NOT NULL,
  `status` varchar(25) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
