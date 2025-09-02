<?php
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'promotions_countdown` (
    `id_promotion` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `discount_percent` decimal(5,2) NOT NULL,
    `end_date` datetime NOT NULL,
    `banner_image` varchar(255) DEFAULT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `created_date` datetime NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_promotion`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'promotion_products` (
    `id_promotion` int(10) unsigned NOT NULL,
    `id_product` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_promotion`, `id_product`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}