<?php
/**
 * Created by PhpStorm.
 * User: Ruben
 * Date: 8-2-14
 * Time: 15:30
 */

$tblstr = "
CREATE TABLE `dev_stats` (
	`host` INT(11) NULL DEFAULT NULL,
	`dev` CHAR(32) NULL DEFAULT NULL,
	`stamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`enabled` BIT(1) NULL DEFAULT NULL,
	`status` CHAR(32) NULL DEFAULT NULL,
	`temp` FLOAT NULL DEFAULT NULL,
	`fan` TINYINT(4) NULL DEFAULT NULL,
	`gpu` INT(11) NULL DEFAULT NULL,
	`mem` INT(11) NULL DEFAULT NULL,
	`v` FLOAT NULL DEFAULT NULL,
	`h_5s` FLOAT NULL DEFAULT NULL,
	`accepted` INT(11) NULL DEFAULT NULL,
	`rejected` INT(11) NULL DEFAULT NULL,
	`hw_error` INT(11) NULL DEFAULT NULL,
	`intensity` INT(11) NULL DEFAULT NULL,
	INDEX `Index 1` (`stamp`)
);";

$crr = $dbh->query($tblstr);

if (!$crr) {
    die('FATAL: create hosts error: ' . db_error());
}