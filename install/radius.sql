
DROP TABLE IF EXISTS `nas`;
CREATE TABLE `nas` (
  `id` int(10) NOT NULL,
  `nasname` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `shortname` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'other',
  `ports` int(5) DEFAULT NULL,
  `secret` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'secret',
  `server` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `community` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'RADIUS Client',
  `routers` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radacct`;
CREATE TABLE `radacct` (
  `radacctid` bigint(21) NOT NULL,
  `acctsessionid` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `acctuniqueid` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `username` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `realm` varchar(64) COLLATE utf8mb4_general_ci DEFAULT '',
  `nasipaddress` varchar(15) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `nasportid` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nasporttype` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acctstarttime` datetime DEFAULT NULL,
  `acctupdatetime` datetime DEFAULT NULL,
  `acctstoptime` datetime DEFAULT NULL,
  `acctinterval` int(12) DEFAULT NULL,
  `acctsessiontime` int(12) UNSIGNED DEFAULT NULL,
  `acctauthentic` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `connectinfo_start` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `connectinfo_stop` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `acctinputoctets` bigint(20) DEFAULT NULL,
  `acctoutputoctets` bigint(20) DEFAULT NULL,
  `calledstationid` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `callingstationid` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `acctterminatecause` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `servicetype` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `framedprotocol` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `framedipaddress` varchar(15) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `framedipv6address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `framedipv6prefix` varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `framedinterfaceid` varchar(44) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `delegatedipv6prefix` varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `class` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radcheck`;
CREATE TABLE `radcheck` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '==',
  `value` varchar(253) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radgroupcheck`;
CREATE TABLE `radgroupcheck` (
  `id` int(11) UNSIGNED NOT NULL,
  `groupname` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '==',
  `value` varchar(253) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radgroupreply`;
CREATE TABLE `radgroupreply` (
  `id` int(11) UNSIGNED NOT NULL,
  `groupname` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '=',
  `value` varchar(253) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `plan_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radpostauth`;
CREATE TABLE `radpostauth` (
  `id` int(11) NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pass` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `reply` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `authdate` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `class` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radreply`;
CREATE TABLE `radreply` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '=',
  `value` varchar(253) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `radusergroup`;
CREATE TABLE `radusergroup` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `groupname` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `nasreload`;
CREATE TABLE `nasreload` (
  nasipaddress varchar(15) NOT NULL,
  reloadtime datetime NOT NULL,
  PRIMARY KEY (nasipaddress)
) ENGINE = INNODB;

ALTER TABLE `nas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasname` (`nasname`);

ALTER TABLE `radacct`
  ADD PRIMARY KEY (`radacctid`),
  ADD UNIQUE KEY `acctuniqueid` (`acctuniqueid`),
  ADD KEY `username` (`username`),
  ADD KEY `framedipaddress` (`framedipaddress`),
  ADD KEY `framedipv6address` (`framedipv6address`),
  ADD KEY `framedipv6prefix` (`framedipv6prefix`),
  ADD KEY `framedinterfaceid` (`framedinterfaceid`),
  ADD KEY `delegatedipv6prefix` (`delegatedipv6prefix`),
  ADD KEY `acctsessionid` (`acctsessionid`),
  ADD KEY `acctsessiontime` (`acctsessiontime`),
  ADD KEY `acctstarttime` (`acctstarttime`),
  ADD KEY `acctinterval` (`acctinterval`),
  ADD KEY `acctstoptime` (`acctstoptime`),
  ADD KEY `nasipaddress` (`nasipaddress`),
  ADD KEY `class` (`class`);

ALTER TABLE `radcheck`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));

ALTER TABLE `radgroupcheck`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupname` (`groupname`(32));

ALTER TABLE `radgroupreply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupname` (`groupname`(32));

ALTER TABLE `radpostauth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `class` (`class`);

ALTER TABLE `radreply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));

ALTER TABLE `radusergroup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));


ALTER TABLE `nas`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

ALTER TABLE `radacct`
  MODIFY `radacctid` bigint(21) NOT NULL AUTO_INCREMENT;

ALTER TABLE `radcheck`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `radgroupcheck`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `radgroupreply`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `radpostauth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `radreply`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `radusergroup`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS=1;
