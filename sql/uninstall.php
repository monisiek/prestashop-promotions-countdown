<?php
$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'promotion_products`';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'promotions_countdown`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
return true;
