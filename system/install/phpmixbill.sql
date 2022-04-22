--
-- Database: `phpmixbill`
--
-- --------------------------------------------------------
--
-- Struktur dari tabel `tbl_appconfig`
--
DROP TABLE IF EXISTS `tbl_appconfig`;
CREATE TABLE `tbl_appconfig` (
  `id` int(11) NOT NULL,
  `setting` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data untuk tabel `tbl_appconfig`
--
INSERT INTO
  `tbl_appconfig` (`id`, `setting`, `value`)
VALUES
  (1, 'CompanyName', 'PHPMixBill'),
  (2, 'theme', 'default'),
  (3, 'currency_code', 'Rp.'),
  (4, 'language', 'english'),
  (5, 'show-logo', '1'),
  (6, 'nstyle', 'blue'),
  (7, 'timezone', 'Asia/Jakarta'),
  (8, 'dec_point', ','),
  (9, 'thousands_sep', '.'),
  (10, 'rtl', '0'),
  (11, 'address', ''),
  (12, 'phone', ''),
  (13, 'date_format', 'd M Y'),
  (14, 'note', 'Thank you...');
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_bandwidth`
  --
  DROP TABLE IF EXISTS `tbl_bandwidth`;
CREATE TABLE `tbl_bandwidth` (
    `id` int(10) UNSIGNED NOT NULL,
    `name_bw` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `rate_down` int(10) UNSIGNED NOT NULL,
    `rate_down_unit` enum('Kbps', 'Mbps') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `rate_up` int(10) UNSIGNED NOT NULL,
    `rate_up_unit` enum('Kbps', 'Mbps') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_customers`
  --
  DROP TABLE IF EXISTS `tbl_customers`;
CREATE TABLE `tbl_customers` (
    `id` int(10) NOT NULL,
    `username` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `password` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `fullname` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `address` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `phonenumber` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` datetime DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_language`
  --
  DROP TABLE IF EXISTS `tbl_language`;
CREATE TABLE `tbl_language` (
    `id` int(10) NOT NULL,
    `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `folder` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `author` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
  -- Dumping data untuk tabel `tbl_language`
  --
INSERT INTO
  `tbl_language` (`id`, `name`, `folder`, `author`)
VALUES
  (1, 'Indonesia', 'indonesia', 'Ismail Marzuqi'),
  (2, 'English', 'english', 'Ismail Marzuqi'),
  (4, 'Türkçe', 'turkish', 'Goktug Bogac Ogel');
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_logs`
  --
  DROP TABLE IF EXISTS `tbl_logs`;
CREATE TABLE `tbl_logs` (
    `id` int(10) NOT NULL,
    `date` datetime DEFAULT NULL,
    `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `description` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
    `userid` int(10) NOT NULL,
    `ip` mediumtext COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_message`
  --
  DROP TABLE IF EXISTS `tbl_message`;
CREATE TABLE `tbl_message` (
    `id` int(10) NOT NULL,
    `from_user` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `to_user` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `title` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `status` enum('0', '1') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
    `date` datetime NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_plans`
  --
  DROP TABLE IF EXISTS `tbl_plans`;
CREATE TABLE `tbl_plans` (
    `id` int(10) NOT NULL,
    `name_plan` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `id_bw` int(10) NOT NULL,
    `price` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `type` enum('Hotspot', 'PPPOE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `typebp` enum('Unlimited', 'Limited') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `limit_type` enum('Time_Limit', 'Data_Limit', 'Both_Limit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `time_limit` int(10) UNSIGNED DEFAULT NULL,
    `time_unit` enum('Mins', 'Hrs') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `data_limit` int(10) UNSIGNED DEFAULT NULL,
    `data_unit` enum('MB', 'GB') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `validity` int(10) NOT NULL,
    `validity_unit` enum('Days', 'Months') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `shared_users` int(10) DEFAULT NULL,
    `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `pool` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_pool`
  --
  DROP TABLE IF EXISTS `tbl_pool`;
CREATE TABLE `tbl_pool` (
    `id` int(10) NOT NULL,
    `pool_name` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `range_ip` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `routers` varchar(40) COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_routers`
  --
  DROP TABLE IF EXISTS `tbl_routers`;
CREATE TABLE `tbl_routers` (
    `id` int(10) NOT NULL,
    `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `ip_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `password` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_transactions`
  --
  DROP TABLE IF EXISTS `tbl_transactions`;
CREATE TABLE `tbl_transactions` (
    `id` int(10) NOT NULL,
    `invoice` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
    `username` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `plan_name` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `price` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
    `recharged_on` date NOT NULL,
    `expiration` date NOT NULL,
    `time` time NOT NULL,
    `method` enum('voucher', 'admin') COLLATE utf8mb4_general_ci NOT NULL,
    `routers` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    `type` enum('Hotspot', 'PPPOE') COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_users`
  --
  DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE `tbl_users` (
    `id` int(10) UNSIGNED NOT NULL,
    `username` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `fullname` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `password` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `user_type` enum('Admin', 'Sales') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `status` enum('Active', 'Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
    `last_login` datetime DEFAULT NULL,
    `creationdate` datetime NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
  -- Dumping data untuk tabel `tbl_users`
  --
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
VALUES
  (
    1,
    'admin',
    'Administrator',
    '7c4a8d09ca3762af61e59520943dc26494f8941b',
    'Admin',
    'Active',
    '2015-10-30 18:27:02',
    '2014-06-23 01:43:07'
  );
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_user_recharges`
  --
  DROP TABLE IF EXISTS `tbl_user_recharges`;
CREATE TABLE `tbl_user_recharges` (
    `id` int(10) NOT NULL,
    `customer_id` int(10) NOT NULL,
    `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `plan_id` int(10) NOT NULL,
    `namebp` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `recharged_on` date NOT NULL,
    `expiration` date NOT NULL,
    `time` time NOT NULL,
    `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `method` enum('voucher', 'admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `type` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
  --
  -- Struktur dari tabel `tbl_voucher`
  --
  DROP TABLE IF EXISTS `tbl_voucher`;
CREATE TABLE `tbl_voucher` (
    `id` int(10) NOT NULL,
    `type` enum('Hotspot', 'PPPOE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `routers` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `id_plan` int(10) NOT NULL,
    `code` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `user` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `status` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
  -- Indexes for dumped tables
  --
  --
  -- Indeks untuk tabel `tbl_appconfig`
  --
ALTER TABLE
  `tbl_appconfig`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_bandwidth`
  --
ALTER TABLE
  `tbl_bandwidth`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_customers`
  --
ALTER TABLE
  `tbl_customers`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_language`
  --
ALTER TABLE
  `tbl_language`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_logs`
  --
ALTER TABLE
  `tbl_logs`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_message`
  --
ALTER TABLE
  `tbl_message`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_plans`
  --
ALTER TABLE
  `tbl_plans`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_pool`
  --
ALTER TABLE
  `tbl_pool`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_routers`
  --
ALTER TABLE
  `tbl_routers`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_transactions`
  --
ALTER TABLE
  `tbl_transactions`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_users`
  --
ALTER TABLE
  `tbl_users`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_user_recharges`
  --
ALTER TABLE
  `tbl_user_recharges`
ADD
  PRIMARY KEY (`id`);
--
  -- Indeks untuk tabel `tbl_voucher`
  --
ALTER TABLE
  `tbl_voucher`
ADD
  PRIMARY KEY (`id`);
--
  -- AUTO_INCREMENT untuk tabel yang dibuang
  --
  --
  -- AUTO_INCREMENT untuk tabel `tbl_appconfig`
  --
ALTER TABLE
  `tbl_appconfig`
MODIFY
  `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 15;
--
  -- AUTO_INCREMENT untuk tabel `tbl_bandwidth`
  --
ALTER TABLE
  `tbl_bandwidth`
MODIFY
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_customers`
  --
ALTER TABLE
  `tbl_customers`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_language`
  --
ALTER TABLE
  `tbl_language`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 5;
--
  -- AUTO_INCREMENT untuk tabel `tbl_logs`
  --
ALTER TABLE
  `tbl_logs`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_message`
  --
ALTER TABLE
  `tbl_message`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_plans`
  --
ALTER TABLE
  `tbl_plans`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_pool`
  --
ALTER TABLE
  `tbl_pool`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_routers`
  --
ALTER TABLE
  `tbl_routers`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_transactions`
  --
ALTER TABLE
  `tbl_transactions`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_users`
  --
ALTER TABLE
  `tbl_users`
MODIFY
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
  -- AUTO_INCREMENT untuk tabel `tbl_user_recharges`
  --
ALTER TABLE
  `tbl_user_recharges`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
--
  -- AUTO_INCREMENT untuk tabel `tbl_voucher`
  --
ALTER TABLE
  `tbl_voucher`
MODIFY
  `id` int(10) NOT NULL AUTO_INCREMENT;
COMMIT;