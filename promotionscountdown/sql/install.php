<?php
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'promotions_countdown` (
    `id_promotion` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `discount_percent` decimal(5,2) NOT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `banner_image` varchar(255) DEFAULT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `id_cart_rule` int(10) unsigned DEFAULT NULL,
    `created_date` datetime NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_promotion`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_active` (`active`),
    KEY `idx_cart_rule` (`id_cart_rule`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'promotion_products` (
    `id_promotion` int(10) unsigned NOT NULL,
    `id_product` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_promotion`, `id_product`),
    KEY `idx_promotion` (`id_promotion`),
    KEY `idx_product` (`id_product`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

// Controlla se esiste già una tabella senza start_date e aggiorna
$sql[] = 'SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = "'._DB_PREFIX_.'promotions_countdown" 
          AND COLUMN_NAME = "start_date")';

$sql[] = 'SET @sqlstmt := IF(@exist = 0, 
          "ALTER TABLE '._DB_PREFIX_.'promotions_countdown ADD start_date DATETIME NOT NULL AFTER discount_percent", 
          "SELECT 1")';

$sql[] = 'PREPARE stmt FROM @sqlstmt';
$sql[] = 'EXECUTE stmt';
$sql[] = 'DEALLOCATE PREPARE stmt';

// Se la colonna è stata appena aggiunta, imposta la data di creazione come data di inizio per le promozioni esistenti
$sql[] = 'UPDATE `'._DB_PREFIX_.'promotions_countdown` 
          SET start_date = created_date 
          WHERE start_date = "0000-00-00 00:00:00" OR start_date IS NULL';

// Aggiungi colonna id_cart_rule se non esiste
$sql[] = 'SET @exist_cart_rule := (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = "'._DB_PREFIX_.'promotions_countdown" 
          AND COLUMN_NAME = "id_cart_rule")';

$sql[] = 'SET @sqlstmt_cart_rule := IF(@exist_cart_rule = 0, 
          "ALTER TABLE '._DB_PREFIX_.'promotions_countdown ADD id_cart_rule INT(10) UNSIGNED DEFAULT NULL AFTER id_category", 
          "SELECT 1")';

$sql[] = 'PREPARE stmt_cart_rule FROM @sqlstmt_cart_rule';
$sql[] = 'EXECUTE stmt_cart_rule';
$sql[] = 'DEALLOCATE PREPARE stmt_cart_rule';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}