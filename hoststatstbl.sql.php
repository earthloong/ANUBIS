<?php
/**
 * Created by PhpStorm.
 * User: Ruben
 * Date: 8-2-14
 * Time: 15:30
 */

$tblstr = "
CREATE TABLE `host_stats` (
	`stamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`host_id` INT(11) NULL DEFAULT NULL,
	`h_5s` FLOAT NULL DEFAULT NULL,
	`gets` INT(11) NULL DEFAULT NULL,
	`accepted` INT(11) NULL DEFAULT NULL,
	`rejected` INT(11) NULL DEFAULT NULL,
	`discarded` INT(11) NULL DEFAULT NULL,
	`stale` INT(11) NULL DEFAULT NULL,
	`getFails` INT(11) NULL DEFAULT NULL,
	`remFails` INT(11) NULL DEFAULT NULL
);";

$crr = $dbh->query($tblstr);

if (!$crr) {
    die('FATAL: create hosts error: ' . db_error());
}