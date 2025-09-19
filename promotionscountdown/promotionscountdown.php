<?php
/**
 * Modulo Promozioni con Countdown per PrestaShop 1.7.8+
 * @author ThinkPink Studio info@thinkpinkstudio.it
 * @version 1.1.0
 * Funzionalità: Data di partenza, scadenza, filtri prodotti avanzati
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PromotionsCountdown extends Module
{
    public function __construct()
    {
        $this->name = 'promotionscountdown';
        $this->tab = 'advertising_marketing';
        $this->version = '1.1.0';
        $this->author = 'ThinkPink Studio info@thinkpinkstudio.it';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Promozioni con Countdown');
        $this->description = $this->l('Gestisce promozioni con countdown timer, data di inizio e scadenza, filtri prodotti avanzati.');

        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare questo modulo?');

        if (!Configuration::get('PROMOTIONS_COUNTDOWN_NAME')) {
            $this->warning = $this->l('Modulo non configurato');
        }
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        
        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayHomeBefore') &&
            $this->registerHook('displayProductListReviews') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayProductFlags') &&
            $this->registerHook('displayProductAdditionalInfo') &&
            $this->registerHook('displayShoppingCart') &&
            $this->registerHook('displayCheckoutSummary') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionCartUpdateQuantity') &&
            $this->registerHook('actionCartRuleAdd') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('actionObjectCartAddAfter') &&
            $this->registerHook('actionObjectCartUpdateAfter') &&
            $this->registerHook('actionCronJob') &&
            Configuration::updateValue('PROMOTIONS_COUNTDOWN_NAME', 'Promozioni con Countdown');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        return parent::uninstall() &&
            Configuration::deleteByName('PROMOTIONS_COUNTDOWN_NAME');
    }

    /**
     * Metodo per rimuovere l'hook displayHome se era stato registrato in precedenza
     */
    public function removeDisplayHomeHook()
    {
        return $this->unregisterHook('displayHome') &&
            $this->unregisterHook('displayCheckoutSummary');
    }

    public function hookDisplayHeader()
    {
        if (!Context::getContext()->controller instanceof AdminController) {
            // Frontend - NON inizializzare i prezzi qui per evitare loop infiniti
            // I prezzi vengono gestiti solo negli hook del carrello
            // $this->initializePromotionPrices();
            
            $this->context->controller->addCSS($this->_path.'views/css/promotioncountdown.css');
            $this->context->controller->addJS($this->_path.'views/js/promotionscountdown-front.js');
        } else {
            // Backend/Admin
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
            $this->context->controller->addJS($this->_path.'views/js/promotionscountdown-admin.js');
        }
    }

    public function hookDisplayHomeBefore()
    {
        try {
            $active_promotions = $this->getActivePromotions();
            $upcoming_promotions = $this->getUpcomingPromotions();
            
            $all_promotions = array_merge($active_promotions, $upcoming_promotions);
            
            if (empty($all_promotions)) {
                return;
            }

            $this->context->smarty->assign([
                'promotions' => $all_promotions,
                'module_dir' => $this->_path,
                'link' => Context::getContext()->link,
                'current_time' => time()
            ]);

            return $this->display(__FILE__, 'promotions_banner.tpl');
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore in hookDisplayHomeBefore: ' . $e->getMessage(), 4);
            return '';
        }
    }

    public function hookDisplayProductListReviews($params)
    {
        try {
            $product = $params['product'];
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            $active_promotions = $this->getActivePromotions();
            
            // Mostra solo se la promozione countdown è la migliore
            if ($this->isCountdownPromotionBest($product->id, $active_promotions)) {
                $product_discount = $this->getProductDiscount($product->id, $active_promotions);
                $this->context->smarty->assign([
                    'product_discount' => $product_discount,
                    'product_id' => $product->id,
                    'module_dir' => $this->_path
                ]);
                
                return $this->display(__FILE__, 'product_discount.tpl');
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore in hookDisplayProductListReviews: ' . $e->getMessage(), 4);
            return '';
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        try {
            // Gestisce sia il prezzo principale (before/price) che il blocco aggiuntivo (after)
            if (!in_array($params['type'], ['before', 'price', 'after'])) {
                return;
            }

            $product = $params['product'];
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            // Determina se siamo in una lista prodotti (categoria) o pagina prodotto singolo
            $is_product_list = $this->isProductListContext();

            $active_promotions = $this->getActivePromotions();
            
            // DEBUG: Mostra info promozioni
            $best_discount = $this->getProductDiscount($product->id, $active_promotions);
            $is_countdown_best = $this->isCountdownPromotionBest($product->id, $active_promotions);
            
            // Aggiungi debug info al template
            $specific_prices = SpecificPrice::getByProductId($product->id, 0, $this->context->cart->id);
            $cart_rules = $this->context->cart ? $this->context->cart->getCartRules() : [];
            
            // Debug dettagliato per CartRule
            $cart_rules_debug = [];
            foreach ($cart_rules as $cart_rule) {
                $rule = new CartRule($cart_rule['id_cart_rule']);
                $applies = $this->cartRuleAppliesToProduct($rule, $product->id);
                $cart_rules_debug[] = [
                    'id' => $cart_rule['id_cart_rule'],
                    'name' => $cart_rule['name'],
                    'reduction_percent' => $rule->reduction_percent,
                    'reduction_amount' => $rule->reduction_amount,
                    'applies_to_product' => $applies,
                    'product_restriction' => $rule->product_restriction,
                    'manufacturer_restriction' => $rule->manufacturer_restriction,
                    'category_restriction' => $rule->category_restriction
                ];
            }
            
            // Debug completo del prodotto (semplificato per evitare errori)
            $product_debug = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'wholesale_price' => $product->wholesale_price,
                'id_manufacturer' => $product->id_manufacturer,
                'id_category_default' => $product->id_category_default,
                'on_sale' => $product->on_sale,
                'reduction_price' => $product->reduction_price,
                'reduction_percent' => $product->reduction_percent,
                'reduction_from' => $product->reduction_from,
                'reduction_to' => $product->reduction_to,
                'reduction_type' => $product->reduction_type,
                'price_without_reduction' => $product->price_without_reduction
            ];
            
            // JSON completo del prodotto (rimosso temporaneamente per debug)
            $product_json = "JSON rimosso per debug";
            
            $this->context->smarty->assign([
                'debug_info' => [
                    'product_id' => $product->id,
                    'best_discount' => $best_discount,
                    'is_countdown_best' => $is_countdown_best,
                    'active_promotions_count' => count($active_promotions),
                    'specific_prices_count' => count($specific_prices),
                    'cart_rules_count' => count($cart_rules),
                    'specific_prices' => $specific_prices,
                    'cart_rules' => $cart_rules_debug,
                    'product_debug' => $product_debug,
                    'product_json' => $product_json
                ]
            ]);
            
            // Mostra solo se la promozione countdown è la migliore
            if ($is_countdown_best) {
                $product_discount = $best_discount;
                // Calcola prezzi: pieno (tasse incluse, senza riduzioni/specific price) e scontato
                $id_product_attribute = 0;
                if (isset($params['id_product_attribute'])) {
                    $id_product_attribute = (int)$params['id_product_attribute'];
                } elseif (isset($product->cache_default_attribute) && (int)$product->cache_default_attribute > 0) {
                    $id_product_attribute = (int)$product->cache_default_attribute;
                } elseif ((int)Tools::getValue('id_product_attribute')) {
                    $id_product_attribute = (int)Tools::getValue('id_product_attribute');
                }

                // Prezzo pieno tasse incluse, SENZA riduzioni/specific price
                $specific_price_output = null;
                $original_price_tax_incl = Product::getPriceStatic(
                    (int)$product->id,
                    true, // tasse incluse
                    $id_product_attribute ?: null,
                    6,
                    null,
                    false, // only_reduction
                    false, // usereduc (no riduzioni)
                    1,
                    false,
                    (int)$this->context->customer->id,
                    (int)$this->context->cart->id,
                    null,
                    $specific_price_output,
                    true,  // with ecotax
                    false  // use_specific_price = false (ignora specific price)
                );

                // Calcola il prezzo scontato usando la stessa logica del carrello
                if (isset($this->context->cart) && $this->context->cart->id) {
                    // Se c'è un carrello, applica la SpecificPrice e usa il prezzo calcolato da PrestaShop
                    $this->upsertCartSpecificPrice($this->context->cart->id, $product->id, $id_product_attribute, (float)$product_discount['discount_percent']);
                    
                    $discounted_price_tax_incl = Product::getPriceStatic(
                        (int)$product->id,
                        true, // tasse incluse
                        $id_product_attribute ?: null,
                        6,
                        null,
                        false, // only_reduction
                        true,  // usereduc (con riduzioni)
                        1,
                        false,
                        (int)$this->context->customer->id,
                        (int)$this->context->cart->id,
                        null,
                        $specific_price_output,
                        true,  // with ecotax
                        true   // use_specific_price = true (usa specific price)
                    );
                } else {
                    // Se non c'è un carrello (lista prodotti), calcola manualmente ma usa la stessa logica
                    $discounted_price_tax_incl = (float)$original_price_tax_incl * (1 - ((float)$product_discount['discount_percent'] / 100));
                }

                $this->context->smarty->assign([
                    'product_discount' => $product_discount,
                    'product_id' => $product->id,
                    'module_dir' => $this->_path,
                    'original_price' => $original_price_tax_incl,
                    'discounted_price' => $discounted_price_tax_incl,
                    'original_price_formatted' => Tools::displayPrice($original_price_tax_incl),
                    'discounted_price_formatted' => Tools::displayPrice($discounted_price_tax_incl),
                ]);
                
                // Per il prezzo principale (before/price), sovrascriviamo il prezzo
                if (in_array($params['type'], ['before', 'price'])) {
                    if ($is_product_list) {
                        return $this->display(__FILE__, 'product_list_override.tpl');
                    } else {
                        return $this->display(__FILE__, 'product_single_override.tpl');
                    }
                }
                
                // Per il blocco aggiuntivo (after), mostriamo il template di debug
                return $this->display(__FILE__, 'product_price_discount.tpl');
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore in hookDisplayProductPriceBlock: ' . $e->getMessage(), 4);
            return '';
        }
    }

    /**
     * Hook per sovrascrivere le bandiere di sconto native di PrestaShop
     * Questo è il punto chiave per risolvere il conflitto con le bandiere esistenti
     */
    public function hookDisplayProductFlags($params)
    {
        try {
            $product = $params['product'];
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            // Determina se siamo in una lista prodotti (categoria) o pagina prodotto singolo
            $is_product_list = $this->isProductListContext();

            $active_promotions = $this->getActivePromotions();
            
            // Mostra solo se la promozione countdown è la migliore
            if ($this->isCountdownPromotionBest($product->id, $active_promotions)) {
                $product_discount = $this->getProductDiscount($product->id, $active_promotions);
                // Se c'è una promozione attiva, nascondiamo le bandiere native e mostriamo la nostra
                $this->context->smarty->assign([
                    'product_discount' => $product_discount,
                    'product_id' => $product->id,
                    'module_dir' => $this->_path,
                    'hide_native_flags' => true,
                    'is_product_list' => $is_product_list
                ]);
                
                if ($is_product_list) {
                    return $this->display(__FILE__, 'product_flags_list_override.tpl');
                } else {
                    return $this->display(__FILE__, 'product_flags_override.tpl');
                }
            }
            
            return null;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore in hookDisplayProductFlags: ' . $e->getMessage(), 4);
            return '';
        }
    }

    /**
     * Hook per la pagina del prodotto singolo - nasconde le bandiere native
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        try {
            $product = $params['product'];
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            $active_promotions = $this->getActivePromotions();
            
            // Mostra solo se la promozione countdown è la migliore
            if ($this->isCountdownPromotionBest($product->id, $active_promotions)) {
                $product_discount = $this->getProductDiscount($product->id, $active_promotions);
                $this->context->smarty->assign([
                    'product_discount' => $product_discount,
                    'product_id' => $product->id,
                    'module_dir' => $this->_path
                ]);
                
                return $this->display(__FILE__, 'product_single_override.tpl');
            }
            
            return null;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore in hookDisplayProductAdditionalInfo: ' . $e->getMessage(), 4);
            return '';
        }
    }

    /**
     * Hook per mostrare debug nel carrello
     */
    public function hookDisplayShoppingCart($params)
    {
        // Mostra il debug info se disponibile
        if (isset($this->context->smarty->tpl_vars['cart_debug_info'])) {
            return $this->display(__FILE__, 'cart_debug.tpl');
        }
        return '';
    }

    /**
     * Hook per nascondere le bandiere di sconto native nel checkout
     */
    public function hookDisplayCheckoutSummary($params)
    {
        return $this->display(__FILE__, 'checkout_flags_override.tpl');
    }

    private function getProductDiscount($product_id, $active_promotions)
    {
        // Ottieni il prezzo base del prodotto (senza sconti)
        $id_product_attribute = 0;
        if (isset($this->context->cart->id)) {
            $id_product_attribute = $this->getProductAttributeFromCart($product_id);
        }
        
        $specific_price_output = null;
        $base_price = Product::getPriceStatic(
            (int)$product_id,
            true, // tasse incluse
            $id_product_attribute ?: null,
            6,
            null,
            false, // only_reduction
            false, // usereduc (no riduzioni)
            1,
            false,
            (int)$this->context->customer->id,
            (int)$this->context->cart->id,
            null,
            $specific_price_output,
            true,  // with ecotax
            false  // use_specific_price = false (ignora specific price)
        );

        $best_discount = null;
        $best_final_price = null;

        // 1. Controlla le promozioni countdown
        if (!empty($active_promotions)) {
            foreach ($active_promotions as $promotion) {
                $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'promotion_products` 
                        WHERE id_promotion = '.(int)$promotion['id_promotion'].' 
                        AND id_product = '.(int)$product_id;
                if (Db::getInstance()->getValue($sql) > 0) {
                    $discount_percent = (float)$promotion['discount_percent'];
                    $final_price = $base_price * (1 - $discount_percent / 100);
                    
                    if ($best_final_price === null || $final_price < $best_final_price) {
                        $best_final_price = $final_price;
                        $best_discount = [
                            'id_promotion' => $promotion['id_promotion'],
                            'name' => $promotion['name'],
                            'discount_percent' => $promotion['discount_percent'],
                            'final_price' => $final_price,
                            'start_date' => $promotion['start_date'],
                            'end_date' => $promotion['end_date'],
                            'type' => 'countdown'
                        ];
                    }
                }
            }
        }

        // 2. Controlla le SpecificPrice di PrestaShop (escluse quelle del nostro modulo)
        $specific_prices = SpecificPrice::getByProductId($product_id, $id_product_attribute, $this->context->cart->id);
        
        // DEBUG: Log SpecificPrice trovate
        PrestaShopLogger::addLog("DEBUG SpecificPrice per prodotto $product_id: " . count($specific_prices) . " trovate", 1);
        foreach ($specific_prices as $sp) {
            PrestaShopLogger::addLog("SpecificPrice: ID={$sp['id_specific_price']}, Tipo={$sp['reduction_type']}, Riduzione={$sp['reduction']}, Cart={$sp['id_cart']}", 1);
        }
        
        foreach ($specific_prices as $sp) {
            // Escludi le SpecificPrice create dal nostro modulo (hanno id_cart > 0)
            if ($sp['id_cart'] > 0) {
                continue;
            }
            
            $final_price = $base_price;
            $discount_percent = 0;
            
            if ($sp['reduction_type'] == 'percentage') {
                $discount_percent = (float)$sp['reduction'] * 100;
                $final_price = $base_price * (1 - $discount_percent / 100);
            } elseif ($sp['reduction_type'] == 'amount') {
                $final_price = $base_price - (float)$sp['reduction'];
                $discount_percent = (($base_price - $final_price) / $base_price) * 100;
            }
            
            if ($best_final_price === null || $final_price < $best_final_price) {
                $best_final_price = $final_price;
                $best_discount = [
                    'id_promotion' => 'specific_price_' . $sp['id_specific_price'],
                    'name' => 'Sconto speciale',
                    'discount_percent' => $discount_percent,
                    'final_price' => $final_price,
                    'start_date' => $sp['from'],
                    'end_date' => $sp['to'],
                    'type' => 'specific_price'
                ];
            }
        }

        // 3. Controlla le riduzioni dirette del prodotto
        if ($product->on_sale && $product->reduction_percent > 0) {
            $discount_percent = (float)$product->reduction_percent;
            $final_price = $base_price * (1 - $discount_percent / 100);
            
            if ($best_final_price === null || $final_price < $best_final_price) {
                $best_final_price = $final_price;
                $best_discount = [
                    'id_promotion' => 'product_reduction',
                    'name' => 'Riduzione prodotto',
                    'discount_percent' => $discount_percent,
                    'final_price' => $final_price,
                    'start_date' => $product->reduction_from,
                    'end_date' => $product->reduction_to,
                    'type' => 'product_reduction'
                ];
            }
        }

        // 4. Controlla le CartRule attive nel carrello
        if (isset($this->context->cart) && $this->context->cart->id) {
            $cart_rules = $this->context->cart->getCartRules();
            
            // DEBUG: Log CartRule trovate
            PrestaShopLogger::addLog("DEBUG CartRule per carrello {$this->context->cart->id}: " . count($cart_rules) . " trovate", 1);
            foreach ($cart_rules as $cart_rule) {
                PrestaShopLogger::addLog("CartRule: ID={$cart_rule['id_cart_rule']}, Nome={$cart_rule['name']}", 1);
            }
            
            foreach ($cart_rules as $cart_rule) {
                $rule = new CartRule($cart_rule['id_cart_rule']);
                if (Validate::isLoadedObject($rule) && $rule->active) {
                    // Verifica se la regola si applica a questo prodotto
                    if ($this->cartRuleAppliesToProduct($rule, $product_id)) {
                        $final_price = $base_price;
                        $discount_percent = 0;
                        
                        if ($rule->reduction_percent > 0) {
                            $discount_percent = (float)$rule->reduction_percent;
                            $final_price = $base_price * (1 - $discount_percent / 100);
                        } elseif ($rule->reduction_amount > 0) {
                            $final_price = $base_price - (float)$rule->reduction_amount;
                            $discount_percent = (($base_price - $final_price) / $base_price) * 100;
                        }
                        
                        if ($best_final_price === null || $final_price < $best_final_price) {
                            $best_final_price = $final_price;
                            $best_discount = [
                                'id_promotion' => 'cart_rule_' . $rule->id,
                                'name' => $rule->name,
                                'discount_percent' => $discount_percent,
                                'final_price' => $final_price,
                                'start_date' => $rule->date_from,
                                'end_date' => $rule->date_to,
                                'type' => 'cart_rule'
                            ];
                        }
                    }
                }
            }
        }

        return $best_discount;
    }

    /**
     * Controlla se la promozione countdown è la migliore per il prodotto
     */
    private function isCountdownPromotionBest($product_id, $active_promotions)
    {
        // Ottieni il prezzo base del prodotto (senza sconti)
        $id_product_attribute = 0;
        if (isset($this->context->cart->id)) {
            $id_product_attribute = $this->getProductAttributeFromCart($product_id);
        }
        
        $base_price = Product::getPriceStatic(
            (int)$product_id,
            true, // tasse incluse
            $id_product_attribute ?: null,
            6,
            null,
            false, // only_reduction
            false, // usereduc (no riduzioni)
            1,
            false,
            (int)$this->context->customer->id,
            (int)$this->context->cart->id,
            null,
            $specific_price_output,
            true,  // with ecotax
            false  // use_specific_price = false (ignora specific price)
        );
        
        // Ottieni il prezzo attuale del prodotto (con tutti gli sconti applicati)
        $current_price = Product::getPriceStatic(
            (int)$product_id,
            true, // tasse incluse
            $id_product_attribute ?: null,
            6,
            null,
            false, // only_reduction
            true,  // usereduc (con riduzioni)
            1,
            false,
            (int)$this->context->customer->id,
            (int)$this->context->cart->id,
            null,
            $specific_price_output,
            true,  // with ecotax
            true   // use_specific_price = true (con specific price)
        );
        
        // Calcola lo sconto migliore esistente
        $existing_discount_percent = 0;
        if ($base_price > 0 && $current_price < $base_price) {
            $existing_discount_percent = (($base_price - $current_price) / $base_price) * 100;
        }
        
        // Trova il miglior countdown disponibile
        $best_countdown = null;
        if (!empty($active_promotions)) {
            foreach ($active_promotions as $promotion) {
                $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'promotion_products` 
                        WHERE id_promotion = '.(int)$promotion['id_promotion'].' 
                        AND id_product = '.(int)$product_id;
                if (Db::getInstance()->getValue($sql) > 0) {
                    $discount_percent = (float)$promotion['discount_percent'];
                    $final_price = $base_price * (1 - $discount_percent / 100);
                    
                    if ($best_countdown === null || $final_price < $best_countdown['final_price']) {
                        $best_countdown = [
                            'id_promotion' => $promotion['id_promotion'],
                            'name' => $promotion['name'],
                            'discount_percent' => $promotion['discount_percent'],
                            'final_price' => $final_price,
                            'start_date' => $promotion['start_date'],
                            'end_date' => $promotion['end_date'],
                            'type' => 'countdown'
                        ];
                    }
                }
            }
        }
        
        // Se non c'è un countdown, non è migliore
        if (!$best_countdown) {
            return false;
        }
        
        // Il countdown è migliore solo se offre un prezzo migliore dello sconto esistente
        return $best_countdown['final_price'] < $current_price;
    }

    /**
     * Ottiene l'ID della combinazione prodotto dal carrello
     */
    private function getProductAttributeFromCart($product_id)
    {
        if (!isset($this->context->cart) || !$this->context->cart->id) {
            return 0;
        }

        $cart_products = $this->context->cart->getProducts();
        foreach ($cart_products as $product) {
            if ($product['id_product'] == $product_id) {
                return (int)$product['id_product_attribute'];
            }
        }
        return 0;
    }

    /**
     * Verifica se una CartRule si applica a un prodotto specifico
     */
    private function cartRuleAppliesToProduct($cart_rule, $product_id)
    {
        // Se la regola non ha restrizioni sui prodotti, si applica a tutti
        if (empty($cart_rule->product_restriction)) {
            return true;
        }

        // Ottieni il prodotto per controllare la marca
        $product = new Product($product_id);
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        // Controlla se il prodotto è nella lista dei prodotti applicabili
        $product_rules = $cart_rule->getProductRuleGroups();
        foreach ($product_rules as $rule_group) {
            $products = $rule_group->getProducts();
            if (in_array($product_id, $products)) {
                return true;
            }
        }

        // Controlla se la regola si applica alla marca del prodotto
        $manufacturer_rules = $cart_rule->getManufacturerRuleGroups();
        foreach ($manufacturer_rules as $rule_group) {
            $manufacturers = $rule_group->getManufacturers();
            if (in_array($product->id_manufacturer, $manufacturers)) {
                return true;
            }
        }

        // Controlla se la regola si applica alla categoria del prodotto
        $category_rules = $cart_rule->getCategoryRuleGroups();
        foreach ($category_rules as $rule_group) {
            $categories = $rule_group->getCategories();
            $product_categories = Product::getProductCategories($product_id);
            foreach ($product_categories as $cat_id) {
                if (in_array($cat_id, $categories)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Restituisce l'ID della promozione con sconto più alto che include il prodotto
     */
    private function getBestPromotionIdForProduct($product_id, $active_promotions)
    {
        $best = null;
        foreach ($active_promotions as $promotion) {
            $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'promotion_products` 
                    WHERE id_promotion = '.(int)$promotion['id_promotion'].' 
                    AND id_product = '.(int)$product_id;
            if (Db::getInstance()->getValue($sql) > 0) {
                if ($best === null) {
                    $best = $promotion;
                } else {
                    $current_discount = (float)$promotion['discount_percent'];
                    $best_discount = (float)$best['discount_percent'];
                    
                    if ($current_discount > $best_discount) {
                        $best = $promotion;
                    } elseif ($current_discount == $best_discount) {
                        // Tie-breaker: scegli la promozione più recente (created_date più recente)
                        $current_created = strtotime($promotion['created_date']);
                        $best_created = strtotime($best['created_date']);
                        if ($current_created > $best_created) {
                            $best = $promotion;
                        }
                    }
                }
            }
        }
        return $best ? (int)$best['id_promotion'] : null;
    }

    // public function hookDisplayHome()
    // {
    //     $active_promotions = $this->getActivePromotions();
    //     $upcoming_promotions = $this->getUpcomingPromotions();
        
    //     $all_promotions = array_merge($active_promotions, $upcoming_promotions);
        
    //     if (empty($all_promotions)) {
    //         return;
    //     }

    //     $this->context->smarty->assign([
    //         'promotions' => $all_promotions,
    //         'module_dir' => $this->_path,
    //         'link' => Context::getContext()->link,
    //         'current_time' => time()
    //     ]);

    //     return $this->display(__FILE__, 'promotions_banner.tpl');
    // }

    public function getContent()
    {
        // Carica CSS e JS per l'admin
        $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        $this->context->controller->addJS($this->_path.'views/js/promotionscountdown-admin.js');
        
        // Rimuovi l'hook displayHome se era stato registrato in precedenza
        $this->removeDisplayHomeHook();
        
        $output = null;

        // Gestione aggiornamento regole di sconto esistenti
        if (Tools::isSubmit('update_discount_rules')) {
            $result = $this->updateExistingPromotions();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        // Gestione inizializzazione prezzi promozioni
        if (Tools::isSubmit('initialize_promotion_prices')) {
            // DISABILITATO per evitare loop infiniti nell'admin
            // I prezzi vengono gestiti automaticamente negli hook del carrello
            // $this->initializePromotionPrices();
            $output .= $this->displayConfirmation($this->l('I prezzi delle promozioni vengono gestiti automaticamente nel carrello. Non è necessario inizializzarli manualmente.'));
        }


        // Gestione cancellazione promozione
        if (Tools::isSubmit('delete_promotion')) {
            $promotion_id = (int)Tools::getValue('promotion_id');
            if ($this->deletePromotion($promotion_id)) {
                $output .= $this->displayConfirmation($this->l('Promozione cancellata con successo.'));
                // Cancella il cookie di modifica se presente
                if (isset($this->context->cookie->promotion_edit_id)) {
                    unset($this->context->cookie->promotion_edit_id);
                }
            } else {
                $output .= $this->displayError($this->l('Errore nella cancellazione della promozione.'));
            }
        }

        // Gestione pagina di modifica
        $edit_promotion_id = (int)Tools::getValue('edit_promotion_id');
        if ($edit_promotion_id > 0) {
            return $this->displayEditForm($edit_promotion_id);
        }

        if (Tools::isSubmit('submit'.$this->name)) {
            $promotion_name = strval(Tools::getValue('PROMOTION_NAME'));
            $discount_percent = floatval(Tools::getValue('DISCOUNT_PERCENT'));
            $start_date = Tools::getValue('START_DATE');
            $end_date = Tools::getValue('END_DATE');
            $banner_image = $_FILES['BANNER_IMAGE'];
            $selected_products = Tools::getValue('selected_products');

            if (!$promotion_name || !$discount_percent || !$start_date || !$end_date) {
                $output .= $this->displayError($this->l('Campi obbligatori mancanti.'));
            } else if (strtotime($start_date) >= strtotime($end_date)) {
                $output .= $this->displayError($this->l('La data di inizio deve essere precedente alla data di scadenza.'));
            } else {
                if ($edit_promotion_id > 0) {
                    // Modifica promozione esistente
                    $result = $this->updatePromotion($edit_promotion_id, $promotion_name, $discount_percent, $start_date, $end_date, $banner_image, $selected_products);
                    if ($result === true) {
                        $output .= $this->displayConfirmation($this->l('Promozione modificata con successo.'));
                        // Reindirizza alla pagina principale dopo la modifica
                        Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
                    } else {
                        // Mostra l'errore specifico
                        $error_message = $this->l('Errore nella modifica della promozione.');
                        if (is_string($result)) {
                            $error_message = $result; // Se updatePromotion restituisce un messaggio di errore
                        }
                        $output .= $this->displayError($error_message);
                    }
                } else {
                    // Crea nuova promozione - controlla se esiste già una promozione con lo stesso nome
                    $existing = Db::getInstance()->getRow('SELECT id_promotion FROM `'._DB_PREFIX_.'promotions_countdown` WHERE name = "'.pSQL($promotion_name).'"');
                    if ($existing) {
                        $output .= $this->displayError($this->l('Esiste già una promozione con questo nome'));
                    } else {
                        $result = $this->savePromotion($promotion_name, $discount_percent, $start_date, $end_date, $banner_image, $selected_products);
                    
                        if ($result && is_numeric($result)) {
                            $output .= $this->displayConfirmation($this->l('Promozione salvata con successo.'));
                        } else {
                            // Mostra l'errore specifico
                            $error_message = $this->l('Errore nel salvare la promozione.');
                            if (is_string($result)) {
                                $error_message = $result; // Se savePromotion restituisce un messaggio di errore
                            }
                            $output .= $this->displayError($error_message);
                        }
                    }
                }
            }
        }

        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        $products = $this->getProductsWithDetails();
        $manufacturers = Manufacturer::getManufacturers(false, Context::getContext()->language->id);

        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        $form_title = $this->l('Nuova Promozione');
        $output = '';
        
        // Aggiungi avviso sulla non cumulabilità
        $output .= '<div class="alert alert-warning">
            <strong>' . $this->l('Importante:') . '</strong> ' . 
            $this->l('Le promozioni countdown NON sono cumulabili con altre promozioni o sconti. Quando un prodotto è in promozione countdown, tutti gli altri sconti vengono automaticamente disabilitati.') . '
        </div>';
            
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $form_title,
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nome Promozione'),
                    'name' => 'PROMOTION_NAME',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Percentuale Sconto'),
                    'name' => 'DISCOUNT_PERCENT',
                    'suffix' => '%',
                    'class' => 'fixed-width-xs',
                    'required' => true
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Data e Ora Inizio'),
                    'name' => 'START_DATE',
                    'required' => true,
                    'desc' => $this->l('Quando inizia la promozione')
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Data e Ora Scadenza'),
                    'name' => 'END_DATE',
                    'required' => true,
                    'desc' => $this->l('Quando scade la promozione')
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Immagine Banner'),
                    'name' => 'BANNER_IMAGE',
                    'desc' => $this->l('Carica l\'immagine per il banner della promozione')
                ],
                [
                    'type' => 'html',
                    'label' => $this->l('Seleziona Prodotti (Opzionale)'),
                    'name' => 'products_selector',
                    'html_content' => $this->generateProductSelector($products, $manufacturers, [])
                ]
            ],
            'submit' => [
                'title' => $this->l('Salva Promozione'),
                'name' => 'submit'.$this->name,
            ]
        ];

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->table = 'promotions_countdown';
        $helper->identifier = 'id_promotion';
        
        // Inizializza i campi con valori vuoti per evitare errori Smarty
        $helper->fields_value = [
            'PROMOTION_NAME' => '',
            'DISCOUNT_PERCENT' => '',
            'START_DATE' => '',
            'END_DATE' => '',
        ];

        $existing_promotions = $this->getExistingPromotions();
        $promotions_list = '';
        
        if (!empty($existing_promotions)) {
            $promotions_list = '<div class="panel"><div class="panel-heading">' . $this->l('Promozioni Esistenti') . ' 
                <div style="float: right;">
                    <form method="post" style="display: inline; margin-right: 10px;">
                        <button type="submit" name="update_discount_rules" class="btn btn-warning btn-sm" 
                                onclick="return confirm(\'Questo aggiornerà tutte le regole di sconto esistenti con la priorità corretta. Continuare?\');">
                            <i class="icon-refresh"></i> Aggiorna Regole Sconto
                        </button>
                    </form>
                    <form method="post" style="display: inline;">
                        <button type="submit" name="initialize_promotion_prices" class="btn btn-info btn-sm" 
                                onclick="return confirm(\'Questo inizializzerà i prezzi di tutti i prodotti in promozione. Continuare?\');">
                            <i class="icon-euro"></i> Inizializza Prezzi
                        </button>
                    </form>
                </div>
            </div><div class="panel-body">';
            $promotions_list .= '<table class="table"><thead><tr><th>Nome</th><th>Sconto</th><th>Data Inizio</th><th>Data Scadenza</th><th>Prodotti</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
            
            foreach ($existing_promotions as $promo) {
                $product_count = $this->getPromotionProductsCount($promo['id_promotion']);
                $now = time();
                $start_time = strtotime($promo['start_date']);
                $end_time = strtotime($promo['end_date']);
                
                if ($now < $start_time) {
                    $status = 'Programmata';
                    $status_class = 'label-info';
                } else if ($now >= $start_time && $now < $end_time) {
                    $status = 'Attiva';
                    $status_class = 'label-success';
                } else {
                    $status = 'Scaduta';
                    $status_class = 'label-danger';
                }
                
                $promotions_list .= '<tr>';
                $promotions_list .= '<td>' . $promo['name'] . '</td>';
                $promotions_list .= '<td>' . $promo['discount_percent'] . '%</td>';
                $promotions_list .= '<td>' . date('d/m/Y H:i', $start_time) . '</td>';
                $promotions_list .= '<td>' . date('d/m/Y H:i', $end_time) . '</td>';
                $promotions_list .= '<td>' . $product_count . ' prodotti <button type="button" class="btn btn-info btn-xs" onclick="showProducts(' . $promo['id_promotion'] . ')" title="Visualizza prodotti"><i class="icon-list"></i></button></td>';
                $promotions_list .= '<td><span class="label ' . $status_class . '">' . $status . '</span></td>';
                $promotions_list .= '<td>
                    <a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&edit_promotion_id=' . $promo['id_promotion'] . '&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-primary btn-sm" title="Modifica promozione" style="margin-right: 5px;">
                        <i class="icon-edit"></i> Modifica
                    </a>
                    <form method="post" style="display: inline;" onsubmit="return confirm(\'Sei sicuro di voler cancellare questa promozione?\');">
                        <input type="hidden" name="promotion_id" value="' . $promo['id_promotion'] . '">
                        <button type="submit" name="delete_promotion" class="btn btn-danger btn-sm" title="Cancella promozione">
                            <i class="icon-trash"></i> Cancella
                        </button>
                    </form>
                </td>';
                $promotions_list .= '</tr>';
            }
            
            $promotions_list .= '</tbody></table></div></div>';
            
            // Aggiungi popup per visualizzare i prodotti
            $promotions_list .= '
            <div id="productsModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">Prodotti della Promozione</h4>
                        </div>
                        <div class="modal-body">
                            <div id="productsList"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                        </div>
                    </div>
                </div>
            </div>';
        }

        $form_html = $helper->generateForm($fields_form);
        
        // Aggiungi JavaScript per il popup dei prodotti
        $js = '
        <script>
        function showProducts(promotionId) {
            // Mostra loading
            $("#productsList").html("<div class=\"text-center\"><i class=\"icon-spinner icon-spin\"></i> Caricamento prodotti...</div>");
            $("#productsModal").modal("show");
            
            // Carica i prodotti via AJAX
            $.ajax({
                url: "' . AdminController::$currentIndex . '&configure=' . $this->name . '&ajax=1&action=getPromotionProducts",
                type: "POST",
                data: {
                    promotion_id: promotionId,
                    token: "' . Tools::getAdminTokenLite('AdminModules') . '"
                },
                success: function(response) {
                    $("#productsList").html(response);
                },
                error: function() {
                    $("#productsList").html("<div class=\"alert alert-danger\">Errore nel caricamento dei prodotti.</div>");
                }
            });
        }
        </script>';
        
        return $promotions_list . $form_html . $js;
    }
    
    private function displayEditForm($promotion_id)
    {
        $products = $this->getProductsWithDetails();
        $manufacturers = Manufacturer::getManufacturers(false, Context::getContext()->language->id);
        $edit_promotion = $this->getPromotionById($promotion_id);
        $edit_products = $this->getPromotionProducts($promotion_id);

        if (!$edit_promotion) {
            return $this->displayError($this->l('Promozione non trovata.'));
        }

        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Modifica Promozione: ') . $edit_promotion['name'],
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nome Promozione'),
                    'name' => 'PROMOTION_NAME',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Percentuale Sconto'),
                    'name' => 'DISCOUNT_PERCENT',
                    'suffix' => '%',
                    'class' => 'fixed-width-xs',
                    'required' => true
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Data e Ora Inizio'),
                    'name' => 'START_DATE',
                    'required' => true,
                    'desc' => $this->l('Quando inizia la promozione')
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Data e Ora Scadenza'),
                    'name' => 'END_DATE',
                    'required' => true,
                    'desc' => $this->l('Quando scade la promozione')
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Immagine Banner'),
                    'name' => 'BANNER_IMAGE',
                    'desc' => $this->l('Carica l\'immagine per il banner della promozione')
                ],
                [
                    'type' => 'html',
                    'label' => $this->l('Seleziona Prodotti (Opzionale)'),
                    'name' => 'products_selector',
                    'html_content' => $this->generateProductSelector($products, $manufacturers, $edit_products)
                ]
            ],
            'submit' => [
                'title' => $this->l('Aggiorna Promozione'),
                'name' => 'submit'.$this->name,
            ]
        ];

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->table = 'promotions_countdown';
        $helper->identifier = 'id_promotion';
        
        // Valori precompilati per modifica
        $helper->fields_value = [
            'PROMOTION_NAME' => $edit_promotion['name'],
            'DISCOUNT_PERCENT' => $edit_promotion['discount_percent'],
            'START_DATE' => $edit_promotion['start_date'],
            'END_DATE' => $edit_promotion['end_date'],
        ];

        $form_html = $helper->generateForm($fields_form);
        
        // Aggiungi campo hidden per mantenere l'ID della promozione
        $form_html = str_replace(
            '<form',
            '<input type="hidden" name="edit_promotion_id" value="' . $promotion_id . '"><form',
            $form_html
        );
        
        // Aggiungi pulsante per tornare alla lista
        $back_button = '<div style="text-align: center; margin: 20px 0;">
            <a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-warning">
                <i class="icon-arrow-left"></i> Torna alla Lista Promozioni
            </a>
        </div>';
        
        return $back_button . $form_html;
    }

    public function ajaxProcessGetPromotionProducts()
    {
        $promotion_id = (int)Tools::getValue('promotion_id');
        
        if (!$promotion_id) {
            echo '<div class="alert alert-danger">ID promozione non valido.</div>';
            return;
        }
        
        $products = $this->getPromotionProductsDetails($promotion_id);
        
        if (empty($products)) {
            echo '<div class="alert alert-info">Nessun prodotto trovato per questa promozione.</div>';
            return;
        }
        
        $html = '<div class="row">';
        foreach ($products as $product) {
            $html .= '
            <div class="col-md-6" style="margin-bottom: 15px;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '" class="img-responsive" style="max-height: 80px;">
                            </div>
                            <div class="col-md-9">
                                <h5 style="margin-top: 0;">' . htmlspecialchars($product['name']) . '</h5>
                                <p><strong>Marca:</strong> ' . htmlspecialchars($product['manufacturer_name']) . '</p>
                                <p><strong>Riferimento:</strong> ' . htmlspecialchars($product['reference']) . '</p>
                                <p><strong>Prezzo:</strong> ' . $product['formatted_price'] . '</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';
        
        echo $html;
    }
    
    private function getPromotionProductsDetails($promotion_id)
    {
        $id_lang = Context::getContext()->language->id;
        
        $sql = 'SELECT p.id_product, pl.name, p.reference, p.price, p.id_manufacturer, 
                       m.name as manufacturer_name, p.active,
                       i.id_image, p.id_category_default
                FROM '._DB_PREFIX_.'product p
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN '._DB_PREFIX_.'image i ON (p.id_product = i.id_product AND i.cover = 1)
                INNER JOIN '._DB_PREFIX_.'promotion_products pp ON (p.id_product = pp.id_product)
                WHERE pp.id_promotion = '.(int)$promotion_id.'
                ORDER BY pl.name ASC';
        
        $products = Db::getInstance()->executeS($sql);
        
        foreach ($products as &$product) {
            if ($product['id_image']) {
                $product['image_url'] = Context::getContext()->link->getImageLink(
                    Tools::str2url($product['name']), 
                    $product['id_image'], 
                    'small_default'
                );
            } else {
                $product['image_url'] = Context::getContext()->link->getImageLink(
                    '', 
                    Language::getIsoById($id_lang).'-default', 
                    'small_default'
                );
            }
            
            $product['formatted_price'] = Tools::displayPrice($product['price']);
        }
        
        return $products;
    }

    private function getProductsWithDetails()
    {
        $id_lang = Context::getContext()->language->id;
        
        $sql = 'SELECT p.id_product, pl.name, p.reference, p.price, p.id_manufacturer, 
                       m.name as manufacturer_name, p.active,
                       i.id_image, p.id_category_default
                FROM '._DB_PREFIX_.'product p
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = '.(int)$id_lang.')
                LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN '._DB_PREFIX_.'image i ON (p.id_product = i.id_product AND i.cover = 1)
                WHERE p.active = 1
                ORDER BY pl.name ASC';
        
        $products = Db::getInstance()->executeS($sql);
        
        foreach ($products as &$product) {
            if ($product['id_image']) {
                $product['image_url'] = Context::getContext()->link->getImageLink(
                    Tools::str2url($product['name']), 
                    $product['id_image'], 
                    'small_default'
                );
            } else {
                $product['image_url'] = Context::getContext()->link->getImageLink(
                    '', 
                    Language::getIsoById($id_lang).'-default', 
                    'small_default'
                );
            }
            
            $product['formatted_price'] = Tools::displayPrice($product['price']);
        }
        
        return $products;
    }

    private function generateProductSelector($products, $manufacturers, $edit_products = [])
    {
        $html = '
        <div id="advanced-product-selector">
            <div class="product-filters" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <div class="row">
                    <div class="col-md-4">
                        <label>' . $this->l('Cerca per nome:') . '</label>
                        <input type="text" id="product-name-filter" class="form-control" placeholder="' . $this->l('Digita il nome del prodotto...') . '">
                    </div>
                    <div class="col-md-4">
                        <label>' . $this->l('Filtra per marca:') . '</label>
                        <select id="manufacturer-filter" class="form-control">
                            <option value="">' . $this->l('Tutte le marche') . '</option>';
        
        foreach ($manufacturers as $manufacturer) {
            $html .= '<option value="' . $manufacturer['id_manufacturer'] . '">' . $manufacturer['name'] . '</option>';
        }
        
        $html .= '
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>' . $this->l('Azioni:') . '</label><br>
                        <button type="button" class="btn btn-sm btn-primary" id="apply-filters">' . $this->l('Applica Filtri') . '</button>
                        <button type="button" class="btn btn-sm btn-info" id="clear-filters">' . $this->l('Pulisci Filtri') . '</button>
                        <button type="button" class="btn btn-sm btn-success" id="select-all-visible">' . $this->l('Seleziona tutti visibili') . '</button>
                        <button type="button" class="btn btn-sm btn-warning" id="deselect-all">' . $this->l('Deseleziona tutti') . '</button>
                    </div>
                </div>
            </div>
            
            <div id="selected-products-info" style="margin-bottom: 15px; padding: 10px; background: #e8f5e8; border-radius: 5px; display: none;">
                <strong>' . $this->l('Prodotti selezionati:') . '</strong> <span id="selected-count">0</span>
                <div id="selected-products-list" style="margin-top: 10px;"></div>
            </div>
            
            <div class="product-grid" style="max-height: 750px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;">
                <div class="row" id="products-container">';
        
        foreach ($products as $product) {
            $manufacturer_name = $product['manufacturer_name'] ? $product['manufacturer_name'] : $this->l('Nessuna marca');
            
            $html .= '
                <div class="col-md-6 col-lg-4 product-item" 
                     data-product-id="' . $product['id_product'] . '"
                     data-product-name="' . strtolower($product['name']) . '"
                     data-manufacturer-id="' . $product['id_manufacturer'] . '"
                     style="padding: 10px; border-bottom: 1px solid #eee;">
                    <div class="product-card" style="border: 2px solid transparent; padding: 10px; border-radius: 8px; transition: all 0.3s ease; cursor: pointer;">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="' . $product['image_url'] . '" 
                                     alt="' . htmlspecialchars($product['name']) . '" 
                                     class="img-responsive product-image" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                            </div>
                            <div class="col-md-8">
                                <div class="product-info">
                                    <h6 class="product-name" style="margin: 0 0 5px 0; font-weight: bold; font-size: 12px;">
                                        ' . htmlspecialchars($product['name']) . '
                                    </h6>
                                    <p class="product-brand" style="margin: 0 0 3px 0; font-size: 11px; color: #666;">
                                        <i class="icon-tag"></i> ' . htmlspecialchars($manufacturer_name) . '
                                    </p>
                                    <p class="product-reference" style="margin: 0 0 3px 0; font-size: 10px; color: #999;">
                                        Rif: ' . htmlspecialchars($product['reference']) . '
                                    </p>
                                    <p class="product-price" style="margin: 0; font-size: 11px; font-weight: bold; color: #27ae60;">
                                        ' . $product['formatted_price'] . '
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="product-checkbox" style="text-align: center; margin-top: 8px;">
                            <input type="checkbox" name="selected_products[]" value="' . $product['id_product'] . '" class="product-selector"' . (in_array($product['id_product'], $edit_products) ? ' checked' : '') . '>
                            <label style="font-size: 11px; margin-left: 5px;">' . $this->l('Seleziona') . '</label>
                        </div>
                    </div>
                </div>';
        }
        
        $html .= '
                </div>
            </div>
            
            <div id="no-products-message" style="display: none; text-align: center; padding: 40px; color: #666;">
                <i class="icon-warning-sign" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>' . $this->l('Nessun prodotto trovato con i filtri attuali.') . '</p>
            </div>
        </div>';
        
        return $html;
    }

    private function savePromotion($name, $discount, $start_date, $end_date, $banner_image, $selected_products)
    {
        // Verifica se il nome è valido
        if (empty(trim($name))) {
            return $this->l('Nome promozione obbligatorio');
        }

        // Verifica se il nome è troppo lungo
        if (strlen(trim($name)) > 255) {
            return $this->l('Nome promozione troppo lungo (massimo 255 caratteri)');
        }

        // Verifica se le date sono valide
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        // Verifica se le date sono nel formato corretto
        if ($start_timestamp === false || $end_timestamp === false) {
            return $this->l('Formato date non valido');
        }
        
        if ($end_timestamp <= $start_timestamp) {
            return $this->l('La data di scadenza deve essere successiva alla data di inizio');
        }

        // Verifica se la percentuale di sconto è valida
        if ($discount <= 0 || $discount > 100) {
            return $this->l('La percentuale di sconto deve essere tra 1 e 100');
        }

        // I prodotti non sono più obbligatori - possono essere aggiunti dopo
        // Le promozioni sovrapposte sono ora permesse

        $image_name = null;
        if (isset($banner_image) && $banner_image['error'] == 0) {
            $image_name = $this->uploadBannerImage($banner_image);
        }

        $sql = 'INSERT INTO `'._DB_PREFIX_.'promotions_countdown` 
                (name, discount_percent, start_date, end_date, banner_image, created_date, active) 
                VALUES ("'.pSQL($name).'", '.(float)$discount.', "'.pSQL($start_date).'", "'.pSQL($end_date).'", "'.pSQL($image_name).'", NOW(), 1)';
        
        if (!Db::getInstance()->execute($sql)) {
            return $this->l('Errore nel salvataggio nel database: ') . Db::getInstance()->getMsgError();
        }

        $promotion_id = Db::getInstance()->Insert_ID();
        
        $category_id = $this->createPromotionCategory($name, $promotion_id);
        
        if ($category_id) {
            Db::getInstance()->update('promotions_countdown', ['id_category' => $category_id], 'id_promotion = '.(int)$promotion_id);
        }

        if (!empty($selected_products) && is_array($selected_products)) {
            foreach ($selected_products as $product_id) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'promotion_products` (id_promotion, id_product) 
                        VALUES ('.(int)$promotion_id.', '.(int)$product_id.')';
                Db::getInstance()->execute($sql);
                
                if ($category_id) {
                    $this->addProductToCategory($product_id, $category_id);
                }
            }
        }

        return $promotion_id;
    }

    private function createPromotionCategory($promotion_name, $promotion_id)
    {
        // Verifica se la categoria padre esiste
        $parent_category = new Category(2);
        if (!Validate::isLoadedObject($parent_category)) {
            // Se la categoria padre non esiste, usa la categoria root
            $parent_category = new Category(1);
            if (!Validate::isLoadedObject($parent_category)) {
                return false;
            }
            $parent_id = 1;
        } else {
            $parent_id = 2;
        }
        
        $category = new Category();
        $category->name = [
            Configuration::get('PS_LANG_DEFAULT') => 'Promo: ' . $promotion_name
        ];
        $category->description = [
            Configuration::get('PS_LANG_DEFAULT') => 'Categoria automatica per promozione: ' . $promotion_name
        ];
        $category->link_rewrite = [
            Configuration::get('PS_LANG_DEFAULT') => Tools::str2url('promo-' . $promotion_name . '-' . $promotion_id)
        ];
        $category->id_parent = $parent_id;
        $category->active = 1;
        
        if ($category->add()) {
            return $category->id;
        }
        
        return false;
    }

    private function addProductToCategory($product_id, $category_id)
    {
        $product = new Product($product_id);
        if (Validate::isLoadedObject($product)) {
            $product->addToCategories([$category_id]);
            $product->save();
        }
    }

    private function uploadBannerImage($file)
    {
        $upload_dir = _PS_MODULE_DIR_ . $this->name . '/views/img/';
        
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                return null;
            }
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }

        return null;
    }

    private function getActivePromotions()
    {
        // Cache per evitare query multiple
        static $cached_promotions = null;
        static $cache_time = 0;
        
        // Cache valida per 30 secondi
        if ($cached_promotions !== null && (time() - $cache_time) < 30) {
            return $cached_promotions;
        }
        
        try {
            // Timeout di 5 secondi per evitare blocchi
            $old_timeout = ini_get('max_execution_time');
            set_time_limit(5);
            
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                    WHERE active = 1 AND start_date <= NOW() AND end_date > NOW() 
                    ORDER BY created_date DESC';
            
            $result = Db::getInstance()->executeS($sql);
            
            // Ripristina timeout originale
            set_time_limit($old_timeout);
            
            // Gestisci errori del database
            if ($result === false) {
                PrestaShopLogger::addLog('PromotionsCountdown: Errore nel caricamento promozioni attive: ' . Db::getInstance()->getMsgError(), 3);
                $cached_promotions = [];
                $cache_time = time();
                return [];
            }
            
            // Filtra promozioni con date valide
            $valid_promotions = [];
            foreach ($result as $promotion) {
                $start_timestamp = strtotime($promotion['start_date']);
                $end_timestamp = strtotime($promotion['end_date']);
                
                // Solo promozioni con date valide
                if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp > $start_timestamp) {
                    $valid_promotions[] = $promotion;
                }
            }
            
            // Aggiorna cache
            $cached_promotions = $valid_promotions;
            $cache_time = time();
            
            return $valid_promotions;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore critico in getActivePromotions: ' . $e->getMessage(), 4);
            $cached_promotions = [];
            $cache_time = time();
            return [];
        }
    }

    private function getUpcomingPromotions()
    {
        // Cache per evitare query multiple
        static $cached_promotions = null;
        static $cache_time = 0;
        
        // Cache valida per 30 secondi
        if ($cached_promotions !== null && (time() - $cache_time) < 30) {
            return $cached_promotions;
        }
        
        try {
            // Timeout di 5 secondi per evitare blocchi
            $old_timeout = ini_get('max_execution_time');
            set_time_limit(5);
            
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                    WHERE active = 1 AND start_date > NOW() 
                    ORDER BY start_date ASC LIMIT 3';
            
            $result = Db::getInstance()->executeS($sql);
            
            // Ripristina timeout originale
            set_time_limit($old_timeout);
            
            // Gestisci errori del database
            if ($result === false) {
                PrestaShopLogger::addLog('PromotionsCountdown: Errore nel caricamento promozioni future: ' . Db::getInstance()->getMsgError(), 3);
                $cached_promotions = [];
                $cache_time = time();
                return [];
            }
            
            // Filtra promozioni con date valide
            $valid_promotions = [];
            foreach ($result as $promotion) {
                $start_timestamp = strtotime($promotion['start_date']);
                $end_timestamp = strtotime($promotion['end_date']);
                
                // Solo promozioni con date valide
                if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp > $start_timestamp) {
                    $valid_promotions[] = $promotion;
                }
            }
            
            // Aggiorna cache
            $cached_promotions = $valid_promotions;
            $cache_time = time();
            
            return $valid_promotions;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore critico in getUpcomingPromotions: ' . $e->getMessage(), 4);
            $cached_promotions = [];
            $cache_time = time();
            return [];
        }
    }

    private function getExistingPromotions()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                ORDER BY created_date DESC LIMIT 20';
        
        return Db::getInstance()->executeS($sql);
    }

    private function getPromotionProductsCount($promotion_id)
    {
        $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'promotion_products` 
                WHERE id_promotion = '.(int)$promotion_id;
        
        return Db::getInstance()->getValue($sql);
    }

    private function deletePromotion($promotion_id)
    {
        // Ottieni i dati della promozione per pulire le categorie
        $promotion = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` WHERE id_promotion = '.(int)$promotion_id);
        
        if (!$promotion) {
            return false;
        }

        // Rimuovi i prodotti dalla categoria della promozione
        if ($promotion['id_category']) {
            $products = Db::getInstance()->executeS('SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` WHERE id_promotion = '.(int)$promotion_id);
            foreach ($products as $product) {
                $product_obj = new Product($product['id_product']);
                if (Validate::isLoadedObject($product_obj)) {
                    // Rimuovi il prodotto dalla categoria usando il metodo corretto
                    $product_obj->deleteCategory($promotion['id_category']);
                    $product_obj->save();
                }
            }
        }

        // Cancella i record dei prodotti associati
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'promotion_products` WHERE id_promotion = '.(int)$promotion_id);

        // Cancella la categoria della promozione
        if ($promotion['id_category']) {
            $category = new Category($promotion['id_category']);
            if (Validate::isLoadedObject($category)) {
                $category->delete();
            }
        }

        // Cancella l'immagine del banner se esiste
        if ($promotion['banner_image'] && file_exists(_PS_MODULE_DIR_ . $this->name . '/views/img/' . $promotion['banner_image'])) {
            unlink(_PS_MODULE_DIR_ . $this->name . '/views/img/' . $promotion['banner_image']);
        }

        // Rimuovi la regola di sconto associata
        $this->removePromotionDiscountRule($promotion_id);

        // Cancella la promozione
        $result = Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'promotions_countdown` WHERE id_promotion = '.(int)$promotion_id);
        
        // Pulisci la cache di PrestaShop
        if ($result) {
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
        }
        
        return $result;
    }

    private function getPromotionById($promotion_id)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` WHERE id_promotion = '.(int)$promotion_id;
        return Db::getInstance()->getRow($sql);
    }

    private function getPromotionProducts($promotion_id)
    {
        $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` WHERE id_promotion = '.(int)$promotion_id;
        $products = Db::getInstance()->executeS($sql);
        return array_column($products, 'id_product');
    }

    private function updatePromotion($promotion_id, $name, $discount, $start_date, $end_date, $banner_image, $selected_products)
    {
        // Ottieni la promozione esistente per mantenere la categoria
        $existing_promotion = $this->getPromotionById($promotion_id);
        if (!$existing_promotion) {
            return 'PROMOTION_NOT_FOUND';
        }

        // Verifica se il nome è valido
        if (empty(trim($name))) {
            return 'EMPTY_NAME'; // Nome promozione vuoto
        }

        // Verifica se il nome è troppo lungo
        if (strlen(trim($name)) > 255) {
            return 'NAME_TOO_LONG'; // Nome promozione troppo lungo
        }

        // Verifica se esiste già una promozione con lo stesso nome (escludendo quella corrente)
        $sql = 'SELECT id_promotion FROM `'._DB_PREFIX_.'promotions_countdown` WHERE name = "'.pSQL($name).'" AND id_promotion != '.(int)$promotion_id;
        $duplicate = Db::getInstance()->getRow($sql);
        if ($duplicate) {
            return 'DUPLICATE_NAME'; // Nome già utilizzato da un'altra promozione
        }

        // Verifica se le date sono valide
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        // Verifica se le date sono nel formato corretto
        if ($start_timestamp === false || $end_timestamp === false) {
            return false; // Date non valide
        }
        
        if ($end_timestamp <= $start_timestamp) {
            return false; // Data di scadenza non valida
        }

        // Verifica se la percentuale di sconto è valida
        if ($discount <= 0 || $discount > 100) {
            return false; // Percentuale di sconto non valida
        }

        // I prodotti non sono più obbligatori - possono essere aggiunti dopo

        // Le promozioni sovrapposte sono ora permesse

        // Aggiorna l'immagine se fornita
        $image_name = null;
        if (isset($banner_image) && $banner_image['error'] == 0) {
            $image_name = $this->uploadBannerImage($banner_image);
        }

        // Aggiorna i dati della promozione
        $update_data = [
            'name' => pSQL($name),
            'discount_percent' => (float)$discount,
            'start_date' => pSQL($start_date),
            'end_date' => pSQL($end_date),
        ];

        if ($image_name) {
            $update_data['banner_image'] = pSQL($image_name);
        }

        if (!Db::getInstance()->update('promotions_countdown', $update_data, 'id_promotion = '.(int)$promotion_id)) {
            return false;
        }

        // Aggiorna i prodotti associati
        // Prima rimuovi tutti i prodotti esistenti dalla categoria
        if ($existing_promotion['id_category']) {
            $products = Db::getInstance()->executeS('SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` WHERE id_promotion = '.(int)$promotion_id);
            foreach ($products as $product) {
                $product_obj = new Product($product['id_product']);
                if (Validate::isLoadedObject($product_obj)) {
                    // Rimuovi il prodotto dalla categoria usando il metodo corretto
                    $product_obj->deleteCategory($existing_promotion['id_category']);
                    $product_obj->save();
                }
            }
        }

        // Cancella i record dei prodotti associati
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'promotion_products` WHERE id_promotion = '.(int)$promotion_id);

        // Aggiungi i nuovi prodotti (se forniti)
        if (!empty($selected_products) && is_array($selected_products)) {
            foreach ($selected_products as $product_id) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'promotion_products` (id_promotion, id_product) 
                        VALUES ('.(int)$promotion_id.', '.(int)$product_id.')';
                Db::getInstance()->execute($sql);
                
                // Aggiungi il prodotto alla categoria della promozione
                if ($existing_promotion['id_category']) {
                    $this->addProductToCategory($product_id, $existing_promotion['id_category']);
                }
            }
        }

        // NON creare regole di sconto globali - gli sconti sono applicati direttamente ai prodotti
        // $this->removePromotionDiscountRule($promotion_id);
        // $this->createPromotionDiscountRule($promotion_id);

        return true;
    }

    /**
     * Hook per applicare le regole di sconto nel carrello
     */
    public function hookActionCartSave($params)
    {
        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        $this->applyPromotionDiscounts($cart);
    }

    /**
     * Hook per applicare sconti dopo aggiunta prodotto
     */
    public function hookActionObjectCartAddAfter($params)
    {
        $cart = $params['object'];
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // Evita loop infiniti - controlla se stiamo già processando questo carrello
        static $processing_carts = [];
        $cart_id = $cart->id;
        
        if (isset($processing_carts[$cart_id])) {
            return; // Già in elaborazione, esci per evitare loop
        }
        
        $processing_carts[$cart_id] = true;
        
        try {
            $this->applyPromotionDiscounts($cart);
        } finally {
            // Rimuovi il flag di elaborazione
            unset($processing_carts[$cart_id]);
        }
    }

    /**
     * Hook per applicare sconti dopo aggiornamento carrello
     */
    public function hookActionObjectCartUpdateAfter($params)
    {
        $cart = $params['object'];
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // Evita loop infiniti - controlla se stiamo già processando questo carrello
        static $processing_carts = [];
        $cart_id = $cart->id;
        
        if (isset($processing_carts[$cart_id])) {
            return; // Già in elaborazione, esci per evitare loop
        }
        
        $processing_carts[$cart_id] = true;
        
        try {
            $this->applyPromotionDiscounts($cart);
        } finally {
            // Rimuovi il flag di elaborazione
            unset($processing_carts[$cart_id]);
        }
    }

    /**
     * Applica gli sconti delle promozioni attive al carrello
     * Le promozioni countdown sono NON CUMULABILI con altre promozioni
     */
    private function applyPromotionDiscounts($cart)
    {
        // Evita loop infiniti - controlla se stiamo già processando questo carrello
        static $processing_carts = [];
        $cart_id = $cart->id;
        
        if (isset($processing_carts[$cart_id])) {
            return; // Già in elaborazione, esci per evitare loop
        }
        
        $processing_carts[$cart_id] = true;
        
        try {
            $active_promotions = $this->getActivePromotions();

            if (empty($active_promotions)) {
                return;
            }

            // Applica solo le promozioni countdown quando sono le più vantaggiose
            $cart_products = $cart->getProducts();
            foreach ($cart_products as $cart_product) {
                $product_id = (int)$cart_product['id_product'];
                $id_product_attribute = isset($cart_product['id_product_attribute']) ? (int)$cart_product['id_product_attribute'] : 0;

                $best_discount = $this->getProductDiscount($product_id, $active_promotions);
                $is_countdown_best = $this->isCountdownPromotionBest($product_id, $active_promotions);
                
                // DEBUG: Aggiungi info debug per carrello
                $this->context->smarty->assign([
                    'cart_debug_info' => [
                        'product_id' => $product_id,
                        'best_discount' => $best_discount,
                        'is_countdown_best' => $is_countdown_best,
                        'active_promotions_count' => count($active_promotions),
                        'cart_id' => $cart->id
                    ]
                ]);
                
                if ($is_countdown_best) {
                    // Solo se la promozione countdown è la migliore, applica lo sconto
                    $this->upsertCartSpecificPrice($cart->id, $product_id, $id_product_attribute, (float)$best_discount['discount_percent']);
                } else {
                    // Se c'è una promozione migliore di tipo diverso o nessuna promozione, rimuovi l'eventuale SpecificPrice del modulo
                    $this->removeCartSpecificPrice($cart->id, $product_id, $id_product_attribute);
                }
            }
        } finally {
            // Rimuovi il flag di elaborazione
            unset($processing_carts[$cart_id]);
        }
    }

    /**
     * Rimuove tutte le CartRule non appartenenti al modulo (codice non prefissato PROMO_)
     * per far sì che in carrello sia visibile e conteggiato solo lo sconto del modulo
     */
    private function removeNonModuleCartRules($cart)
    {
        $cart_rules = $cart->getCartRules();
        foreach ($cart_rules as $cart_rule) {
            if (!isset($cart_rule['code']) || strpos($cart_rule['code'], 'PROMO_') !== 0) {
                $cart->removeCartRule($cart_rule['id_cart_rule']);
            }
        }
    }

    /**
     * Crea o aggiorna uno SpecificPrice specifico per carrello e combinazione
     * in modo da forzare il prezzo scontato senza cumulo con altri sconti
     */
    private function upsertCartSpecificPrice($id_cart, $id_product, $id_product_attribute, $discount_percent)
    {
        // Rimuovi eventuali record esistenti del modulo per evitare duplicati
        $this->removeCartSpecificPrice($id_cart, $id_product, $id_product_attribute);

        $sp = new SpecificPrice();
        $sp->id_shop = (int)Context::getContext()->shop->id;
        $sp->id_shop_group = 0;
        $sp->id_currency = 0;
        $sp->id_country = 0;
        $sp->id_group = 0;
        $sp->id_customer = (int)Context::getContext()->customer->id;
        $sp->id_product = (int)$id_product;
        $sp->id_product_attribute = (int)$id_product_attribute;
        $sp->id_cart = (int)$id_cart;
        $sp->from_quantity = 1;
        // Usa riduzione percentuale per consentire la visualizzazione del prezzo pieno (senza riduzioni)
        $sp->reduction_type = 'percentage';
        $sp->reduction = max(0, min(1, ((float)$discount_percent) / 100));
        $sp->price = -1; // non forza un prezzo assoluto
        $sp->from = '0000-00-00 00:00:00';
        $sp->to = '0000-00-00 00:00:00';
        $sp->add();
    }

    /**
     * Rimuove lo SpecificPrice legato al carrello per un prodotto/combinazione
     */
    private function removeCartSpecificPrice($id_cart, $id_product, $id_product_attribute)
    {
        $sql = 'DELETE FROM `'._DB_PREFIX_.'specific_price` WHERE id_cart = '.(int)$id_cart.
               ' AND id_product = '.(int)$id_product.
               ' AND id_product_attribute = '.(int)$id_product_attribute;
        Db::getInstance()->execute($sql);
    }



    /**
     * Rimuove le regole di sconto delle promozioni dal carrello
     */
    private function removePromotionDiscountRules($cart)
    {
        $cart_rules = $cart->getCartRules();
        
        foreach ($cart_rules as $cart_rule) {
            if (strpos($cart_rule['code'], 'PROMO_') === 0) {
                $cart->removeCartRule($cart_rule['id_cart_rule']);
            }
        }
    }

    /**
     * Rimuove TUTTE le regole di sconto dal carrello
     * Per rendere le promozioni countdown NON CUMULABILI
     */
    private function removeAllCartRules($cart)
    {
        $cart_rules = $cart->getCartRules();
        
        foreach ($cart_rules as $cart_rule) {
            $cart->removeCartRule($cart_rule['id_cart_rule']);
        }
    }

    /**
     * Ottiene il prezzo originale del prodotto
     */
    private function getOriginalProductPrice($product_id)
    {
        $sql = 'SELECT original_price FROM `'._DB_PREFIX_.'promotion_original_prices` 
                WHERE id_product = '.(int)$product_id;
        
        return Db::getInstance()->getValue($sql);
    }

    /**
     * Verifica se ci sono prodotti in promozione countdown nel carrello
     */
    private function hasPromotionProductsInCart($cart)
    {
        $active_promotions = $this->getActivePromotions();
        $cart_products = $cart->getProducts();
        
        foreach ($cart_products as $cart_product) {
            $product_id = $cart_product['id_product'];
            $product_discount = $this->getProductDiscount($product_id, $active_promotions);
            
            if ($product_discount) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Rimuove le regole di sconto native che potrebbero confliggere
     */
    private function removeConflictingDiscountRules($cart)
    {
        $cart_rules = $cart->getCartRules();
        $active_promotions = $this->getActivePromotions();
        
        foreach ($cart_rules as $cart_rule) {
            // Rimuovi regole di sconto con priorità inferiore alla nostra
            if (isset($cart_rule['priority']) && $cart_rule['priority'] < 999) {
                // Verifica se il prodotto è in una promozione attiva
                $product_ids = [];
                foreach ($cart->getProducts() as $product) {
                    $product_ids[] = $product['id_product'];
                }
                
                $has_active_promotion = false;
                foreach ($product_ids as $product_id) {
                    if ($this->getProductDiscount($product_id, $active_promotions)) {
                        $has_active_promotion = true;
                        break;
                    }
                }
                
                if ($has_active_promotion && !strpos($cart_rule['code'], 'PROMO_') === 0) {
                    $cart->removeCartRule($cart_rule['id_cart_rule']);
                }
            }
        }
    }

    /**
     * Hook per aggiornare i prezzi dei prodotti quando vengono modificati
     */
    public function hookActionProductUpdate($params)
    {
        $product = $params['product'];
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        // DISABILITATO per evitare loop infiniti
        // I prezzi vengono gestiti solo negli hook del carrello
        // $this->updateProductPromotionPrices($product->id);
    }

    /**
     * Hook per gestire l'aggiornamento della quantità nel carrello
     */
    public function hookActionCartUpdateQuantity($params)
    {
        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // Evita loop infiniti - controlla se stiamo già processando questo carrello
        static $processing_carts = [];
        $cart_id = $cart->id;
        
        if (isset($processing_carts[$cart_id])) {
            return; // Già in elaborazione, esci per evitare loop
        }
        
        $processing_carts[$cart_id] = true;
        
        try {
            // Riapplica gli sconti delle promozioni
            $this->applyPromotionDiscounts($cart);
        } finally {
            // Rimuovi il flag di elaborazione
            unset($processing_carts[$cart_id]);
        }
    }

    /**
     * Hook per bloccare l'aggiunta di regole di sconto se ci sono prodotti in promozione
     */
    public function hookActionCartRuleAdd($params)
    {
        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // Verifica se ci sono prodotti in promozione countdown nel carrello
        if ($this->hasPromotionProductsInCart($cart)) {
            // Se c'è un prodotto in promozione countdown, blocca l'aggiunta di altre regole
            $cart_rule_id = $params['id_cart_rule'];
            $cart->removeCartRule($cart_rule_id);
            
        }
    }

    /**
     * Aggiorna i prezzi dei prodotti per le promozioni attive
     * Applica lo sconto SOLO al prodotto specifico, non a tutto il carrello
     */
    private function updateProductPromotionPrices($product_id)
    {
        $active_promotions = $this->getActivePromotions();
        $product_discount = $this->getProductDiscount($product_id, $active_promotions);
        
        if ($product_discount) {
            // Calcola il nuovo prezzo con lo sconto
            $product = new Product($product_id);
            
            // Ottieni il prezzo originale (se non è già stato salvato)
            $original_price = $this->getOriginalProductPrice($product_id);
            if (!$original_price) {
                $original_price = $product->price;
                $this->saveOriginalPrice($product_id, $original_price);
            }
            
            $discounted_price = $original_price * (1 - $product_discount['discount_percent'] / 100);
            
            // Aggiorna il prezzo del prodotto temporaneamente
            $product->price = $discounted_price;
            $product->save();
        } else {
            // Ripristina il prezzo originale se non c'è promozione
            $this->restoreOriginalPrice($product_id);
        }
    }

    /**
     * Salva il prezzo originale del prodotto
     */
    private function saveOriginalPrice($product_id, $original_price)
    {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'promotion_original_prices` 
                (id_product, original_price, saved_date) 
                VALUES ('.(int)$product_id.', '.(float)$original_price.', NOW())
                ON DUPLICATE KEY UPDATE 
                original_price = '.(float)$original_price.', saved_date = NOW()';
        
        Db::getInstance()->execute($sql);
    }

    /**
     * Ripristina il prezzo originale del prodotto
     */
    private function restoreOriginalPrice($product_id)
    {
        $sql = 'SELECT original_price FROM `'._DB_PREFIX_.'promotion_original_prices` 
                WHERE id_product = '.(int)$product_id;
        
        $original_price = Db::getInstance()->getValue($sql);
        
        if ($original_price) {
            $product = new Product($product_id);
            $product->price = $original_price;
            $product->save();
            
            // Rimuovi il record dal database
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'promotion_original_prices` WHERE id_product = '.(int)$product_id);
        }
    }

    /**
     * Crea una regola di sconto quando viene salvata una promozione
     */
    public function createPromotionDiscountRule($promotion_id)
    {
        $promotion = $this->getPromotionById($promotion_id);
        if (!$promotion) {
            return false;
        }

        try {
            // Crea la regola di sconto
            $rule = new CartRule();
            
            $rule->name = [
                Configuration::get('PS_LANG_DEFAULT') => 'Promozione: ' . $promotion['name']
            ];
            $rule->description = 'Sconto promozione countdown: ' . $promotion['name'];
            $rule->code = 'PROMO_' . $promotion['id_promotion'];
            $rule->quantity = 0;
            $rule->quantity_per_user = 0;
            $rule->priority = 999; // Priorità massima per sovrascrivere altre regole
            $rule->partial_use = false;
            $rule->minimum_amount = 0;
            $rule->minimum_amount_tax = false;
            $rule->minimum_amount_currency = Configuration::get('PS_CURRENCY_DEFAULT');
            $rule->minimum_amount_shipping = false;
            $rule->country_restriction = false;
            $rule->carrier_restriction = false;
            $rule->group_restriction = false;
            // Consenti coesistenza con altre regole del modulo
            // La non cumulabilità tra promozioni è garantita escludendo i prodotti
            // che non sono "migliori" per questa promozione
            $rule->cart_rule_restriction = false;
            $rule->product_restriction = false;
            $rule->shop_restriction = false;
            $rule->free_shipping = false;
            $rule->reduction_percent = $promotion['discount_percent'];
            $rule->reduction_amount = 0;
            $rule->reduction_tax = true;
            $rule->reduction_currency = Configuration::get('PS_CURRENCY_DEFAULT');
            $rule->reduction_product = 0;
            $rule->gift_product = 0;
            $rule->gift_product_attribute = 0;
            $rule->highlight = false;
            $rule->active = true;
            $rule->date_from = $promotion['start_date'];
            $rule->date_to = $promotion['end_date'];
            
            // Configura la regola per applicarsi solo ai prodotti della promozione
            $rule->product_restriction = true;

            if ($rule->add()) {
                // Associa i prodotti della promozione alla regola di sconto
                $this->associateProductsToCartRule($rule->id, $promotion_id);
                
                // Salva l'ID della regola nella promozione
                Db::getInstance()->update('promotions_countdown', 
                    ['id_cart_rule' => $rule->id], 
                    'id_promotion = ' . (int)$promotion_id
                );
                
                return $rule->id;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Associa i prodotti della promozione alla regola di sconto
     */
    private function associateProductsToCartRule($rule_id, $promotion_id)
    {
        // Ottieni i prodotti della promozione
        $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` 
                WHERE id_promotion = '.(int)$promotion_id;
        $products = Db::getInstance()->executeS($sql);

        if (empty($products)) {
            return false;
        }

        // Calcola promozione migliore per ciascun prodotto e associa solo se questa è la migliore
        $active_promotions = $this->getActivePromotions();
        foreach ($products as $product) {
            $best_promo_id = $this->getBestPromotionIdForProduct((int)$product['id_product'], $active_promotions);
            if ($best_promo_id !== (int)$promotion_id) {
                continue; // non associare se esiste una promo con sconto più alto
            }

            $sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_group` 
                    (id_cart_rule, quantity) 
                    VALUES ('.(int)$rule_id.', 1)';
            if (Db::getInstance()->execute($sql)) {
                $rule_group_id = Db::getInstance()->Insert_ID();
                $sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule` 
                        (id_product_rule_group, type, id_item) 
                        VALUES ('.(int)$rule_group_id.', "products", '.(int)$product['id_product'].')';
                Db::getInstance()->execute($sql);
            }
        }
        
        return true;
    }

    /**
     * Rimuove la regola di sconto di una promozione
     */
    private function removePromotionDiscountRule($promotion_id)
    {
        $promotion = $this->getPromotionById($promotion_id);
        if (!$promotion || !isset($promotion['id_cart_rule'])) {
            return false;
        }

        $rule = new CartRule($promotion['id_cart_rule']);
        if (Validate::isLoadedObject($rule)) {
            $rule->delete();
        }

        return true;
    }

    /**
     * Pulisce le regole di sconto delle promozioni scadute
     */
    public function cleanupExpiredPromotions()
    {
        $sql = 'SELECT id_promotion, id_cart_rule FROM `'._DB_PREFIX_.'promotions_countdown` 
                WHERE end_date < NOW() AND id_cart_rule IS NOT NULL';
        
        $expired_promotions = Db::getInstance()->executeS($sql);
        
        foreach ($expired_promotions as $promotion) {
            $rule = new CartRule($promotion['id_cart_rule']);
            if (Validate::isLoadedObject($rule)) {
                $rule->delete();
            }
            
            // Rimuovi il riferimento alla regola
            Db::getInstance()->update('promotions_countdown', 
                ['id_cart_rule' => null], 
                'id_promotion = ' . (int)$promotion['id_promotion']
            );
        }
        
        return true;
    }

    /**
     * Aggiorna le promozioni esistenti con le nuove regole di sconto
     */
    public function updateExistingPromotions()
    {
        try {
            // Ottieni tutte le promozioni esistenti
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                    WHERE active = 1 
                    ORDER BY created_date DESC';

            $promotions = Db::getInstance()->executeS($sql);

            if (empty($promotions)) {
                return [
                    'success' => true,
                    'message' => $this->l('Nessuna promozione trovata da aggiornare.')
                ];
            }

            $updated_count = 0;
            $error_count = 0;

            foreach ($promotions as $promotion) {
                // Rimuovi la regola di sconto esistente se presente
                if ($promotion['id_cart_rule']) {
                    $rule = new CartRule($promotion['id_cart_rule']);
                    if (Validate::isLoadedObject($rule)) {
                        $rule->delete();
                    }
                }
                
                // NON creare regole di sconto globali - gli sconti sono applicati direttamente ai prodotti
                // $rule_id = $this->createPromotionDiscountRule($promotion['id_promotion']);
                
                // DISABILITATO per evitare loop infiniti nell'admin
                // I prezzi vengono gestiti solo negli hook del carrello
                // $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` 
                //         WHERE id_promotion = '.(int)$promotion['id_promotion'];
                // 
                // $products = Db::getInstance()->executeS($sql);
                // 
                // foreach ($products as $product) {
                //     $this->updateProductPromotionPrices($product['id_product']);
                // }
                
                $updated_count++;
            }

            if ($error_count > 0) {
                return [
                    'success' => false,
                    'message' => sprintf($this->l('Aggiornate %d promozioni, %d errori.'), $updated_count, $error_count)
                ];
            } else {
                return [
                    'success' => true,
                    'message' => sprintf($this->l('Aggiornate con successo %d promozioni. I prezzi verranno applicati automaticamente nel carrello.'), $updated_count)
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $this->l('Errore durante l\'aggiornamento: ') . $e->getMessage()
            ];
        }
    }

    /**
     * Inizializza i prezzi delle promozioni attive
     * Applica gli sconti SOLO ai prodotti specifici della promozione
     */
    public function initializePromotionPrices()
    {
        try {
            $active_promotions = $this->getActivePromotions();
            
            if (empty($active_promotions)) {
                return;
            }

            foreach ($active_promotions as $promotion) {
                // Ottieni tutti i prodotti della promozione
                $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'promotion_products` 
                        WHERE id_promotion = '.(int)$promotion['id_promotion'];
                
                $products = Db::getInstance()->executeS($sql);
                
                if ($products === false) {
                    PrestaShopLogger::addLog('PromotionsCountdown: Errore nel caricamento prodotti promozione ' . $promotion['id_promotion'] . ': ' . Db::getInstance()->getMsgError(), 3);
                    continue;
                }
                
                foreach ($products as $product) {
                    $this->updateProductPromotionPrices($product['id_product']);
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('PromotionsCountdown: Errore critico in initializePromotionPrices: ' . $e->getMessage(), 4);
        }
    }

    /**
     * Hook per pulire automaticamente le promozioni scadute
     */
    public function hookActionCronJob()
    {
        $this->cleanupExpiredPromotions();
    }

    /**
     * Determina se siamo in una lista prodotti (categoria) o pagina prodotto singolo
     */
    private function isProductListContext()
    {
        // Controlla il controller corrente
        $controller = $this->context->controller;
        
        // Se siamo in una categoria, siamo in una lista prodotti
        if ($controller instanceof CategoryController) {
            return true;
        }
        
        // Se siamo nella home e c'è una categoria selezionata, siamo in una lista prodotti
        if ($controller instanceof IndexController && Tools::getValue('id_category')) {
            return true;
        }
        
        // Controlla l'URL per pattern di categoria
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\/category\/\d+/', $current_url) || 
            preg_match('/\/categoria\/\d+/', $current_url) ||
            preg_match('/\?id_category=\d+/', $current_url)) {
            return true;
        }
        
        // Se non siamo in ProductController, probabilmente siamo in una lista
        if (!($controller instanceof ProductController)) {
            return true;
        }
        
        return false;
    }
}