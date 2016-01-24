SET NAMES utf8;

DROP TABLE IF EXISTS ##prefix_##xpandbuddy;
CREATE TABLE ##prefix_##xpandbuddy (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`flg_type` TEXT NULL,
	`settings` TEXT NULL,
	`flg_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`start` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;