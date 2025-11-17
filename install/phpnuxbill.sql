
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `tbl_appconfig`;
CREATE TABLE `tbl_appconfig` (
  `id` int NOT NULL,
  `setting` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_bandwidth`;
CREATE TABLE `tbl_bandwidth` (
  `id` int UNSIGNED NOT NULL,
  `name_bw` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rate_down` int UNSIGNED NOT NULL,
  `rate_down_unit` enum('Kbps','Mbps') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rate_up` int UNSIGNED NOT NULL,
  `rate_up_unit` enum('Kbps','Mbps') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `burst` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_customers`;
CREATE TABLE `tbl_customers` (
  `id` int NOT NULL,
  `username` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo` VARCHAR(128) NOT NULL DEFAULT '/user.default.jpg',
  `pppoe_username` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'For PPPOE Login',
  `pppoe_password` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'For PPPOE Login',
  `pppoe_ip` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'For PPPOE Login',
  `fullname` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phonenumber` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `coordinates` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Latitude and Longitude coordinates',
  `account_type` enum('Business','Personal') COLLATE utf8mb4_general_ci DEFAULT 'Personal' COMMENT 'For selecting account type',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'For Money Deposit',
  `service_type` enum('Hotspot','PPPoE','Others') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Others' COMMENT 'For selecting user type',
  `auto_renewal` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Auto renewall using balance',
  `status` enum('Active','Banned','Disabled','Inactive','Limited','Suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_customers_fields`;
CREATE TABLE `tbl_customers_fields` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `field_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `field_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_logs`;
CREATE TABLE `tbl_logs` (
  `id` int NOT NULL,
  `date` datetime DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `userid` int NOT NULL,
  `ip` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_payment_gateway`;
CREATE TABLE `tbl_payment_gateway` (
  `id` int NOT NULL,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `gateway` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'xendit | midtrans',
  `gateway_trx_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `plan_id` int NOT NULL,
  `plan_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `routers_id` int NOT NULL,
  `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pg_url_payment` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `payment_method` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `payment_channel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pg_request` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pg_paid_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `expired_date` datetime DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `paid_date` datetime DEFAULT NULL,
  `trx_invoice` varchar(25) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'from tbl_transactions',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 unpaid 2 paid 3 failed 4 canceled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_plans`;
CREATE TABLE `tbl_plans` (
  `id` int NOT NULL,
  `name_plan` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_bw` int NOT NULL,
  `price` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price_old` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `type` enum('Hotspot','PPPOE','Balance') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `typebp` enum('Unlimited','Limited') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `limit_type` enum('Time_Limit','Data_Limit','Both_Limit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time_limit` int UNSIGNED DEFAULT NULL,
  `time_unit` enum('Mins','Hrs') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_limit` int UNSIGNED DEFAULT NULL,
  `data_unit` enum('MB','GB') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `validity` int NOT NULL,
  `validity_unit` enum('Mins','Hrs','Days','Months','Period') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `shared_users` int DEFAULT NULL,
  `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_radius` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 is radius',
  `pool` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_expired` int NOT NULL DEFAULT '0',
  `expired_date` TINYINT(1) NOT NULL DEFAULT '20',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled\r\n',
  `prepaid` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'yes' COMMENT 'is prepaid',
  `plan_type` enum('Business','Personal') COLLATE utf8mb4_general_ci DEFAULT 'Personal' COMMENT 'For selecting account type',
  `device` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `on_login` TEXT NULL DEFAULT NULL,
  `on_logout` TEXT NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_pool`;
CREATE TABLE `tbl_pool` (
  `id` int NOT NULL,
  `pool_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `local_ip` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `range_ip` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `routers` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_routers`;
CREATE TABLE `tbl_routers` (
  `id` int NOT NULL,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coordinates` VARCHAR(50) NOT NULL DEFAULT '',
  `status` ENUM('Online', 'Offline') DEFAULT 'Online',
  `last_seen` DATETIME,
  `coverage` VARCHAR(8) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 disabled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_transactions`;
CREATE TABLE `tbl_transactions` (
  `id` int NOT NULL,
  `invoice` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `plan_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recharged_on` date NOT NULL,
  `recharged_time` time NOT NULL DEFAULT '00:00:00',
  `expiration` date NOT NULL,
  `time` time NOT NULL,
  `method` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('Hotspot','PPPOE','Balance') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'for note',
  `admin_id` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE `tbl_users` (
  `id` int UNSIGNED NOT NULL,
  `root` int NOT NULL DEFAULT '0' COMMENT 'for sub account',
  `photo` VARCHAR(128) NOT NULL DEFAULT '/admin.default.png',
  `username` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `fullname` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `password` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `city` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'kota',
  `subdistrict` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'kecamatan',
  `ward` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'kelurahan',
  `user_type` enum('SuperAdmin','Admin','Report','Agent','Sales') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `data` TEXT NULL DEFAULT NULL COMMENT 'to put additional data',
  `last_login` datetime DEFAULT NULL,
  `login_token` VARCHAR(40),
  `creationdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_user_recharges`;
CREATE TABLE `tbl_user_recharges` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `plan_id` int NOT NULL,
  `namebp` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recharged_on` date NOT NULL,
  `recharged_time` time NOT NULL DEFAULT '00:00:00',
  `expiration` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `method` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `admin_id` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_voucher`;
CREATE TABLE `tbl_voucher` (
  `id` int NOT NULL,
  `type` enum('Hotspot','PPPOE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_plan` int NOT NULL,
  `code` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_date` DATETIME NULL DEFAULT NULL,
  `generated_by` int NOT NULL DEFAULT '0' COMMENT 'id admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `rad_acct`;
CREATE TABLE `rad_acct` (
  `id` bigint NOT NULL,
  `acctsessionid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `realm` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `nasid` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `nasipaddress` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `nasportid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nasporttype` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `framedipaddress` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `acctsessiontime` BIGINT NOT NULL DEFAULT '0',
  `acctinputoctets` BIGINT NOT NULL DEFAULT '0',
  `acctoutputoctets` BIGINT NOT NULL DEFAULT '0',
  `acctstatustype` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `macaddr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dateAdded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_customers_inbox`;
CREATE TABLE `tbl_customers_inbox` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `date_created` datetime NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `subject` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `body` TEXT  NULL DEFAULT NULL,
  `from` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'System' COMMENT 'System or Admin or Else',
  `admin_id` int NOT NULL DEFAULT '0' COMMENT 'other than admin is 0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tbl_port_pool`;
CREATE TABLE IF NOT EXISTS `tbl_port_pool` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `public_ip` varchar(40) NOT NULL,
  `port_name` varchar(40) NOT NULL,
  `range_port` varchar(40) NOT NULL,
  `routers` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_meta` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tbl` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Table name',
  `tbl_id` int NOT NULL COMMENT 'table value id',
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This Table to add additional data for any table';

CREATE TABLE `tbl_odps` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(32) NOT NULL COLLATE 'latin1_swedish_ci',
	`port_amount` INT NOT NULL,
	`attenuation` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
	`address` MEDIUMTEXT NOT NULL COLLATE 'latin1_swedish_ci',
	`coordinates` VARCHAR(50) NOT NULL COLLATE 'latin1_swedish_ci',
	`coverage` INT NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
;

CREATE TABLE IF NOT EXISTS  `tbl_coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `type` ENUM('fixed', 'percent') NOT NULL,
    `value` DECIMAL(10,2) NOT NULL,
    `description` TEXT NOT NULL,
    `max_usage` INT NOT NULL DEFAULT 1,
    `usage_count` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL,
    `min_order_amount` DECIMAL(10,2) NOT NULL,
    `max_discount_amount` DECIMAL(10,2) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_widgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orders` int NOT NULL DEFAULT '99',
  `position` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1. top 2. left 3. right 4. bottom',
  `user` ENUM('Admin','Agent','Sales','Customer') NOT NULL DEFAULT 'Admin',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `widget` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tbl_message_logs (
    `id` SERIAL PRIMARY KEY,
    `message_type` VARCHAR(50),
    `recipient` VARCHAR(255),
    `message_content` TEXT,
    `status` VARCHAR(50),
    `error_message` TEXT,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `rad_acct`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `framedipaddress` (`framedipaddress`),
  ADD KEY `acctsessionid` (`acctsessionid`),
  ADD KEY `nasipaddress` (`nasipaddress`);


ALTER TABLE `rad_acct`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_appconfig`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_bandwidth`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_customers`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_customers_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

ALTER TABLE `tbl_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_payment_gateway`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_plans`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_pool`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_routers`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_transactions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_user_recharges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tbl_voucher`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `tbl_appconfig`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_bandwidth`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_customers_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_payment_gateway`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_pool`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_routers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_user_recharges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbl_voucher`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

INSERT INTO
    `tbl_appconfig` (`id`, `setting`, `value`)
VALUES (1, 'CompanyName', 'PHPNuxBill'), (2, 'currency_code', 'Rp.'), (3, 'language', 'english'), (4, 'show-logo', '1'), (5, 'nstyle', 'blue'), (6, 'timezone', 'Asia/Jakarta'), (7, 'dec_point', ','), (8, 'thousands_sep', '.'), (9, 'rtl', '0'), (10, 'address', ''), (11, 'phone', ''), (12, 'date_format', 'd M Y'), (13, 'note', 'Thank you...');


INSERT INTO
    `tbl_users` (
        `id`,
        `username`,
        `fullname`,
        `password`,
        `user_type`,
        `status`,
        `last_login`,
        `creationdate`
    )
VALUES (
        1,
        'admin',
        'Administrator',
        'd033e22ae348aeb5660fc2140aec35850c4da997',
        'SuperAdmin',
        'Active',
        '2022-09-06 16:09:50',
        '2014-06-23 01:43:07'
    );

INSERT INTO `tbl_widgets` (`id`, `orders`, `position`, `user`, `enabled`, `title`, `widget`, `content`) VALUES
(1, 1, 1, 'Admin', 1, 'Top Widget', 'top_widget', ''),
(2, 2, 1, 'Admin', 1, 'Default Info', 'default_info_row', ''),
(3, 1, 2, 'Admin', 1, 'Graph Monthly Registered Customers', 'graph_monthly_registered_customers', ''),
(4, 2, 2, 'Admin', 1, 'Graph Monthly Sales', 'graph_monthly_sales', ''),
(5, 3, 2, 'Admin', 1, 'Voucher Stocks', 'voucher_stocks', ''),
(6, 4, 2, 'Admin', 1, 'Customer Expired', 'customer_expired', ''),
(7, 1, 3, 'Admin', 1, 'Cron Monitor', 'cron_monitor', ''),
(8, 2, 3, 'Admin', 1, 'Mikrotik Cron Monitor', 'mikrotik_cron_monitor', ''),
(9, 3, 3, 'Admin', 1, 'Info Payment Gateway', 'info_payment_gateway', ''),
(10, 4, 3, 'Admin', 1, 'Graph Customers Insight', 'graph_customers_insight', ''),
(11, 5, 3, 'Admin', 1, 'Activity Log', 'activity_log', ''),

(30, 1, 1, 'Agent', 1, 'Top Widget', 'top_widget', ''),
(31, 2, 1, 'Agent', 1, 'Default Info', 'default_info_row', ''),
(32, 1, 2, 'Agent', 1, 'Graph Monthly Registered Customers', 'graph_monthly_registered_customers', ''),
(33, 2, 2, 'Agent', 1, 'Graph Monthly Sales', 'graph_monthly_sales', ''),
(34, 3, 2, 'Agent', 1, 'Voucher Stocks', 'voucher_stocks', ''),
(35, 4, 2, 'Agent', 1, 'Customer Expired', 'customer_expired', ''),
(36, 1, 3, 'Agent', 1, 'Cron Monitor', 'cron_monitor', ''),
(37, 2, 3, 'Agent', 1, 'Mikrotik Cron Monitor', 'mikrotik_cron_monitor', ''),
(38, 3, 3, 'Agent', 1, 'Info Payment Gateway', 'info_payment_gateway', ''),
(39, 4, 3, 'Agent', 1, 'Graph Customers Insight', 'graph_customers_insight', ''),
(40, 5, 3, 'Agent', 1, 'Activity Log', 'activity_log', ''),

(41, 1, 1, 'Sales', 1, 'Top Widget', 'top_widget', ''),
(42, 2, 1, 'Sales', 1, 'Default Info', 'default_info_row', ''),
(43, 1, 2, 'Sales', 1, 'Graph Monthly Registered Customers', 'graph_monthly_registered_customers', ''),
(44, 2, 2, 'Sales', 1, 'Graph Monthly Sales', 'graph_monthly_sales', ''),
(45, 3, 2, 'Sales', 1, 'Voucher Stocks', 'voucher_stocks', ''),
(46, 4, 2, 'Sales', 1, 'Customer Expired', 'customer_expired', ''),
(47, 1, 3, 'Sales', 1, 'Cron Monitor', 'cron_monitor', ''),
(48, 2, 3, 'Sales', 1, 'Mikrotik Cron Monitor', 'mikrotik_cron_monitor', ''),
(49, 3, 3, 'Sales', 1, 'Info Payment Gateway', 'info_payment_gateway', ''),
(50, 4, 3, 'Sales', 1, 'Graph Customers Insight', 'graph_customers_insight', ''),
(51, 5, 3, 'Sales', 1, 'Activity Log', 'activity_log', ''),

(60, 1, 2, 'Customer', 1, 'Account Info', 'account_info', ''),
(61, 3, 1, 'Customer', 1, 'Active Internet Plan', 'active_internet_plan', ''),
(62, 4, 1, 'Customer', 1, 'Balance Transfer', 'balance_transfer', ''),
(63, 1, 1, 'Customer', 1, 'Unpaid Order', 'unpaid_order', ''),
(64, 2, 1, 'Customer', 1, 'Announcement', 'announcement', ''),
(65, 5, 1, 'Customer', 1, 'Recharge A Friend', 'recharge_a_friend', ''),
(66, 2, 2, 'Customer', 1, 'Voucher Activation', 'voucher_activation', '');