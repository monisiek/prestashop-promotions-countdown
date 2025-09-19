<?php
// Bootstrap per i test PHPUnit

// Definisci le costanti PrestaShop necessarie
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'InnoDB');
}

// Carica la classe del modulo
require_once __DIR__ . '/../promotionscountdown/promotionscountdown.php';

// Mock di PrestaShop per i test
class MockPrestaShop {
    public static function getContext() {
        return new MockContext();
    }
}

class MockContext {
    public $customer;
    public $cart;
    
    public function __construct() {
        $this->customer = new MockCustomer();
        $this->cart = new MockCart();
    }
}

class MockCustomer {
    public $id = 1;
}

class MockCart {
    public $id = 1;
    
    public function getProducts() {
        return [
            ['id_product' => 1, 'id_product_attribute' => 0],
            ['id_product' => 2, 'id_product_attribute' => 0]
        ];
    }
}

// Mock delle classi PrestaShop
if (!class_exists('Module')) {
    class Module {
        public static function getInstanceByName($name) {
            return new PromotionsCountdown();
        }
    }
}

if (!class_exists('Context')) {
    class Context {
        public static function getContext() {
            return new MockContext();
        }
    }
}

if (!class_exists('Validate')) {
    class Validate {
        public static function isLoadedObject($object) {
            return $object !== null;
        }
    }
}

if (!class_exists('Tools')) {
    class Tools {
        public static function getValue($key) {
            return null;
        }
    }
}

if (!class_exists('Configuration')) {
    class Configuration {
        public static function get($key) {
            return false;
        }
    }
}

if (!class_exists('Db')) {
    class Db {
        public static function getInstance() {
            return new MockDb();
        }
    }
}

class MockDb {
    public function getValue($sql) {
        // Mock per le query di test
        if (strpos($sql, 'promotion_products') !== false) {
            return 1; // Simula che il prodotto è in promozione
        }
        return 0;
    }
    
    public function execute($sql) {
        return true;
    }
}

if (!class_exists('PrestaShopLogger')) {
    class PrestaShopLogger {
        public static function addLog($message, $level) {
            echo "[LOG $level] $message\n";
        }
    }
}

if (!class_exists('SpecificPrice')) {
    class SpecificPrice {
        public static function getByProductId($product_id, $id_product_attribute, $cart_id) {
            // Mock: restituisce una SpecificPrice del 40% per il test
            return [
                [
                    'id_specific_price' => 1,
                    'reduction_type' => 'percentage',
                    'reduction' => 0.40, // 40%
                    'from' => '2024-01-01 00:00:00',
                    'to' => '2024-12-31 23:59:59',
                    'id_cart' => 0
                ]
            ];
        }
    }
}

if (!class_exists('Product')) {
    class Product {
        public static function getPriceStatic($id_product, $usetax = true, $id_product_attribute = null, $decimals = 6, $divisor = null, $only_reduction = false, $usereduc = true, $quantity = 1, $force_associated_tax = false, $id_customer = null, $id_cart = null, $id_address = null, &$specific_price_output = null, $with_ecotax = true, $use_group_reduction = true, $context = null, $use_customer_price = true) {
            // Mock: restituisce sempre 100 come prezzo base
            return 100.0;
        }
    }
}
