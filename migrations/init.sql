CREATE TABLE IF NOT EXISTS `task` (
  `id_task` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `type` enum('work','shopping') NOT NULL,
  `sort_order` smallint(3) unsigned NOT NULL DEFAULT '1',
  `done` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_created` varchar(45) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id_task`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`),
  UNIQUE KEY `sort_order_UNIQUE` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
