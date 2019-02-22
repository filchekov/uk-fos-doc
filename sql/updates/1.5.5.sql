CREATE TABLE IF NOT EXISTS `#__mod_uk_fos_doc_log` (

	`id` int(5) AUTO_INCREMENT,
	`mod_id` INT(2),
	`date` timestamp,
	`status` varchar(25),
	`ip` varchar(15),
	`content` text,
	`error` varchar(255),

  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;