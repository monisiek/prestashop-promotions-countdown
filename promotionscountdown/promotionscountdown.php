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
        return $this->unregisterHook('displayHome');
    }

    public function hookDisplayHeader()
    {
        if (!Context::getContext()->controller instanceof AdminController) {
            // Frontend
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
    }

    public function hookDisplayProductListReviews($params)
    {
        $product = $params['product'];
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $active_promotions = $this->getActivePromotions();
        $product_discount = $this->getProductDiscount($product->id, $active_promotions);
        
        if ($product_discount) {
            $this->context->smarty->assign([
                'product_discount' => $product_discount,
                'product_id' => $product->id,
                'module_dir' => $this->_path
            ]);
            
            return $this->display(__FILE__, 'product_discount.tpl');
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] !== 'price') {
            return;
        }

        $product = $params['product'];
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $active_promotions = $this->getActivePromotions();
        $product_discount = $this->getProductDiscount($product->id, $active_promotions);
        
        if ($product_discount) {
            $this->context->smarty->assign([
                'product_discount' => $product_discount,
                'product_id' => $product->id,
                'module_dir' => $this->_path
            ]);
            
            return $this->display(__FILE__, 'product_price_discount.tpl');
        }
    }

    private function getProductDiscount($product_id, $active_promotions)
    {
        if (empty($active_promotions)) {
            return null;
        }

        foreach ($active_promotions as $promotion) {
            $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'promotion_products` 
                    WHERE id_promotion = '.(int)$promotion['id_promotion'].' 
                    AND id_product = '.(int)$product_id;
            
            if (Db::getInstance()->getValue($sql) > 0) {
                return [
                    'id_promotion' => $promotion['id_promotion'],
                    'name' => $promotion['name'],
                    'discount_percent' => $promotion['discount_percent'],
                    'start_date' => $promotion['start_date'],
                    'end_date' => $promotion['end_date']
                ];
            }
        }

        return null;
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

        // Gestione modifica promozione
        if (Tools::isSubmit('edit_promotion')) {
            $promotion_id = (int)Tools::getValue('edit_promotion_id');
            $this->context->cookie->promotion_edit_id = $promotion_id;
            $output .= $this->displayConfirmation($this->l('Modifica promozione. Compila il form sottostante.'));
        }

        // Gestione annulla modifica
        if (Tools::isSubmit('cancel_edit')) {
            unset($this->context->cookie->promotion_edit_id);
            $output .= $this->displayConfirmation($this->l('Modifica annullata.'));
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

        // Gestione aggiornamento promozioni esistenti
        if (Tools::isSubmit('update_existing_promotions')) {
            $result = $this->updateExistingPromotions();
            if ($result['success']) {
                $output .= $this->displayConfirmation($this->l('Promozioni esistenti aggiornate con successo! ') . $result['message']);
            } else {
                $output .= $this->displayError($this->l('Errore nell\'aggiornamento delle promozioni: ') . $result['message']);
            }
        }

        // Cancella il cookie di modifica se non stiamo modificando o editando
        if (!Tools::isSubmit('edit_promotion') && !Tools::isSubmit('submit'.$this->name) && isset($this->context->cookie->promotion_edit_id)) {
            unset($this->context->cookie->promotion_edit_id);
        }

        if (Tools::isSubmit('submit'.$this->name)) {
            $promotion_name = strval(Tools::getValue('PROMOTION_NAME'));
            $discount_percent = floatval(Tools::getValue('DISCOUNT_PERCENT'));
            $start_date = Tools::getValue('START_DATE');
            $end_date = Tools::getValue('END_DATE');
            $banner_image = $_FILES['BANNER_IMAGE'];
            $selected_products = Tools::getValue('selected_products');
            $edit_promotion_id = isset($this->context->cookie->promotion_edit_id) ? (int)$this->context->cookie->promotion_edit_id : 0;


            if (!$promotion_name || !$discount_percent || !$start_date || !$end_date) {
                $output .= $this->displayError($this->l('Campi obbligatori mancanti.'));
            } else if (strtotime($start_date) >= strtotime($end_date)) {
                $output .= $this->displayError($this->l('La data di inizio deve essere precedente alla data di scadenza.'));
            } else {
                
                if (empty($selected_products) || !is_array($selected_products)) {
                    $output .= $this->displayError($this->l('Devi selezionare almeno un prodotto per la promozione.'));
                } else {
                    if ($edit_promotion_id > 0) {
                        // Modifica promozione esistente
                        $result = $this->updatePromotion($edit_promotion_id, $promotion_name, $discount_percent, $start_date, $end_date, $banner_image, $selected_products);
                        if ($result) {
                            $output .= $this->displayConfirmation($this->l('Promozione modificata con successo. Prodotti selezionati: ') . count($selected_products));
                            unset($this->context->cookie->promotion_edit_id);
                        } else {
                            $output .= $this->displayError($this->l('Errore nella modifica della promozione.'));
                        }
                    } else {
                        // Crea nuova promozione
                        $result = $this->savePromotion($promotion_name, $discount_percent, $start_date, $end_date, $banner_image, $selected_products);
                        
                        if ($result && is_numeric($result)) {
                            $output .= $this->displayConfirmation($this->l('Promozione salvata con successo. Prodotti selezionati: ') . count($selected_products));
                            // Assicurati che il cookie di modifica sia cancellato
                            if (isset($this->context->cookie->promotion_edit_id)) {
                                unset($this->context->cookie->promotion_edit_id);
                            }
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
        
        // Dati per modifica promozione
        $edit_promotion_id = isset($this->context->cookie->promotion_edit_id) ? (int)$this->context->cookie->promotion_edit_id : 0;
        $edit_promotion = null;
        $edit_products = [];
        
        if ($edit_promotion_id > 0) {
            $edit_promotion = $this->getPromotionById($edit_promotion_id);
            $edit_products = $this->getPromotionProducts($edit_promotion_id);
        }

        $form_title = $edit_promotion ? 
            $this->l('Modifica Promozione: ') . $edit_promotion['name'] : 
            $this->l('Nuova Promozione');
            
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
                    'label' => $this->l('Seleziona Prodotti'),
                    'name' => 'products_selector',
                    'html_content' => $this->generateProductSelector($products, $manufacturers, $edit_products)
                ]
            ],
            'submit' => [
                'title' => $edit_promotion ? $this->l('Aggiorna Promozione') : $this->l('Salva Promozione'),
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
        if ($edit_promotion) {
            $helper->fields_value = [
                'PROMOTION_NAME' => $edit_promotion['name'],
                'DISCOUNT_PERCENT' => $edit_promotion['discount_percent'],
                'START_DATE' => $edit_promotion['start_date'],
                'END_DATE' => $edit_promotion['end_date'],
            ];
        } else {
            // Inizializza i campi con valori vuoti per evitare errori Smarty
            $helper->fields_value = [
                'PROMOTION_NAME' => '',
                'DISCOUNT_PERCENT' => '',
                'START_DATE' => '',
                'END_DATE' => '',
            ];
        }

        $existing_promotions = $this->getExistingPromotions();
        $promotions_list = '';
        
        // Bottone per aggiornare le promozioni esistenti
        $update_button = '<div class="panel" style="margin-bottom: 20px;">
            <div class="panel-heading">
                <i class="icon-refresh"></i> ' . $this->l('Aggiorna Promozioni Esistenti') . '
            </div>
            <div class="panel-body">
                <p>' . $this->l('Se hai promozioni esistenti create prima di questo aggiornamento, clicca il pulsante qui sotto per aggiornarle con il nuovo sistema di sconti.') . '</p>
                <form method="post" style="display: inline;" onsubmit="return confirm(\'Sei sicuro di voler aggiornare tutte le promozioni esistenti? Questa operazione non può essere annullata.\');">
                    <button type="submit" name="update_existing_promotions" class="btn btn-warning">
                        <i class="icon-refresh"></i> ' . $this->l('Aggiorna Promozioni Esistenti') . '
                    </button>
                </form>
            </div>
        </div>';
        
        if (!empty($existing_promotions)) {
            $promotions_list = '<div class="panel"><div class="panel-heading">' . $this->l('Promozioni Esistenti') . '</div><div class="panel-body">';
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
                $promotions_list .= '<td>' . $product_count . ' prodotti</td>';
                $promotions_list .= '<td><span class="label ' . $status_class . '">' . $status . '</span></td>';
                $promotions_list .= '<td>
                    <form method="post" style="display: inline;" onsubmit="return confirm(\'Sei sicuro di voler modificare questa promozione?\');">
                        <input type="hidden" name="edit_promotion_id" value="' . $promo['id_promotion'] . '">
                        <button type="submit" name="edit_promotion" class="btn btn-primary btn-sm" title="Modifica promozione" style="margin-right: 5px;">
                            <i class="icon-edit"></i> Modifica
                        </button>
                    </form>
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
        }

        $form_html = $helper->generateForm($fields_form);
        
        // Aggiungi pulsante per annullare modifica
        if ($edit_promotion) {
            $cancel_button = '<div style="text-align: center; margin: 20px 0;">
                <form method="post" style="display: inline;">
                    <button type="submit" name="cancel_edit" class="btn btn-warning">
                        <i class="icon-remove"></i> Annulla Modifica
                    </button>
                </form>
            </div>';
            $form_html = $cancel_button . $form_html;
        }
        
        return $update_button . $promotions_list . $form_html;
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
        error_log("PromotionsCountdown: Inizio salvataggio promozione - Nome: " . $name);
        
        // Verifica se il nome è valido
        if (empty(trim($name))) {
            error_log("PromotionsCountdown: ERRORE - Nome promozione vuoto");
            return $this->l('Nome promozione obbligatorio');
        }

        // Verifica se il nome è troppo lungo
        if (strlen(trim($name)) > 255) {
            error_log("PromotionsCountdown: ERRORE - Nome promozione troppo lungo");
            return $this->l('Nome promozione troppo lungo (massimo 255 caratteri)');
        }

        // Verifica se esiste già una promozione con lo stesso nome
        $existing = Db::getInstance()->getRow('SELECT id_promotion FROM `'._DB_PREFIX_.'promotions_countdown` WHERE name = "'.pSQL($name).'"');
        if ($existing) {
            error_log("PromotionsCountdown: ERRORE - Promozione già esistente con nome: " . $name);
            return $this->l('Esiste già una promozione con questo nome');
        }

        // Verifica se le date sono valide
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        error_log("PromotionsCountdown: Date - Start: " . $start_date . " (" . $start_timestamp . "), End: " . $end_date . " (" . $end_timestamp . ")");
        
        if ($end_timestamp <= $start_timestamp) {
            error_log("PromotionsCountdown: ERRORE - Data di scadenza non valida");
            return $this->l('La data di scadenza deve essere successiva alla data di inizio');
        }

        // Verifica se le date sono nel formato corretto
        if ($start_timestamp === false || $end_timestamp === false) {
            error_log("PromotionsCountdown: ERRORE - Date non valide");
            return $this->l('Formato date non valido');
        }

        // Verifica se la percentuale di sconto è valida
        if ($discount <= 0 || $discount > 100) {
            error_log("PromotionsCountdown: ERRORE - Percentuale di sconto non valida: " . $discount);
            return $this->l('La percentuale di sconto deve essere tra 1 e 100');
        }

        // Verifica se ci sono prodotti selezionati
        if (empty($selected_products) || !is_array($selected_products)) {
            error_log("PromotionsCountdown: ERRORE - Nessun prodotto selezionato");
            return $this->l('Devi selezionare almeno un prodotto');
        }

        error_log("PromotionsCountdown: Validazioni superate, procedo con il salvataggio");

        // Le promozioni sovrapposte sono ora permesse

        $image_name = null;
        if (isset($banner_image) && $banner_image['error'] == 0) {
            error_log("PromotionsCountdown: Upload immagine banner");
            $image_name = $this->uploadBannerImage($banner_image);
            error_log("PromotionsCountdown: Immagine caricata: " . ($image_name ? $image_name : 'FALLITO'));
        }

        error_log("PromotionsCountdown: Inserimento promozione nel database");
        $sql = 'INSERT INTO `'._DB_PREFIX_.'promotions_countdown` 
                (name, discount_percent, start_date, end_date, banner_image, created_date, active) 
                VALUES ("'.pSQL($name).'", '.(float)$discount.', "'.pSQL($start_date).'", "'.pSQL($end_date).'", "'.pSQL($image_name).'", NOW(), 1)';
        
        if (!Db::getInstance()->execute($sql)) {
            error_log("PromotionsCountdown: ERRORE - Inserimento promozione fallito. SQL: " . $sql);
            error_log("PromotionsCountdown: ERRORE - Errore DB: " . Db::getInstance()->getMsgError());
            return $this->l('Errore nel salvataggio nel database: ') . Db::getInstance()->getMsgError();
        }

        $promotion_id = Db::getInstance()->Insert_ID();
        error_log("PromotionsCountdown: Promozione creata con ID: " . $promotion_id);
        
        error_log("PromotionsCountdown: Creazione categoria");
        $category_id = $this->createPromotionCategory($name, $promotion_id);
        error_log("PromotionsCountdown: Categoria creata con ID: " . ($category_id ? $category_id : 'FALLITO'));
        
        if ($category_id) {
            error_log("PromotionsCountdown: Aggiornamento promozione con categoria");
            Db::getInstance()->update('promotions_countdown', ['id_category' => $category_id], 'id_promotion = '.(int)$promotion_id);
        }

        error_log("PromotionsCountdown: Aggiunta prodotti alla promozione");
        if (!empty($selected_products)) {
            foreach ($selected_products as $product_id) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'promotion_products` (id_promotion, id_product) 
                        VALUES ('.(int)$promotion_id.', '.(int)$product_id.')';
                if (!Db::getInstance()->execute($sql)) {
                    error_log("PromotionsCountdown: ERRORE - Inserimento prodotto " . $product_id . " fallito");
                }
                
                if ($category_id) {
                    error_log("PromotionsCountdown: Aggiunta prodotto " . $product_id . " alla categoria");
                    $this->addProductToCategory($product_id, $category_id);
                }
            }
        }

        error_log("PromotionsCountdown: Creazione regola di sconto");
        // Crea la regola di sconto per la promozione
        $rule_id = $this->createPromotionDiscountRule($promotion_id);
        error_log("PromotionsCountdown: Regola di sconto creata con ID: " . ($rule_id ? $rule_id : 'FALLITO'));

        if (!$rule_id) {
            error_log("PromotionsCountdown: ATTENZIONE - Regola di sconto non creata, ma promozione salvata");
            // Non restituiamo errore qui, la promozione è stata salvata correttamente
        }

        error_log("PromotionsCountdown: Salvataggio completato con successo");
        return $promotion_id;
    }

    private function createPromotionCategory($promotion_name, $promotion_id)
    {
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
        $category->id_parent = 2;
        $category->active = 1;
        
        if ($category->add()) {
            return $category->id;
        }
        
        return false;
    }

    private function addProductToCategory($product_id, $category_id)
    {
        $product = new Product($product_id);
        $product->addToCategories([$category_id]);
        $product->save();
    }

    private function uploadBannerImage($file)
    {
        $upload_dir = _PS_MODULE_DIR_ . $this->name . '/views/img/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Debug: verifica che il file sia stato creato
            if (file_exists($filepath)) {
                error_log("PromotionsCountdown: Immagine salvata correttamente: " . $filepath);
            } else {
                error_log("PromotionsCountdown: ERRORE - File non trovato dopo upload: " . $filepath);
            }
            return $filename;
        } else {
            error_log("PromotionsCountdown: ERRORE - Upload fallito per: " . $file['name']);
        }

        return null;
    }

    private function getActivePromotions()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                WHERE active = 1 AND start_date <= NOW() AND end_date > NOW() 
                ORDER BY created_date DESC';
        
        return Db::getInstance()->executeS($sql);
    }

    private function getUpcomingPromotions()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'promotions_countdown` 
                WHERE active = 1 AND start_date > NOW() 
                ORDER BY start_date ASC LIMIT 3';
        
        return Db::getInstance()->executeS($sql);
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
            return false;
        }

        // Verifica se il nome è valido
        if (empty(trim($name))) {
            return false; // Nome promozione vuoto
        }

        // Verifica se il nome è troppo lungo
        if (strlen(trim($name)) > 255) {
            return false; // Nome promozione troppo lungo
        }

        // Verifica se esiste già una promozione con lo stesso nome (escludendo quella corrente)
        $duplicate = Db::getInstance()->getRow('SELECT id_promotion FROM `'._DB_PREFIX_.'promotions_countdown` WHERE name = "'.pSQL($name).'" AND id_promotion != '.(int)$promotion_id);
        if ($duplicate) {
            return false; // Nome già utilizzato da un'altra promozione
        }

        // Verifica se le date sono valide
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        if ($end_timestamp <= $start_timestamp) {
            return false; // Data di scadenza non valida
        }

        // Verifica se le date sono nel formato corretto
        if ($start_timestamp === false || $end_timestamp === false) {
            return false; // Date non valide
        }

        // Verifica se la percentuale di sconto è valida
        if ($discount <= 0 || $discount > 100) {
            return false; // Percentuale di sconto non valida
        }

        // Verifica se ci sono prodotti selezionati
        if (empty($selected_products) || !is_array($selected_products)) {
            return false; // Nessun prodotto selezionato
        }

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

        // Aggiungi i nuovi prodotti
        if (!empty($selected_products)) {
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

        // Aggiorna la regola di sconto
        $this->removePromotionDiscountRule($promotion_id);
        $this->createPromotionDiscountRule($promotion_id);

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

        $this->applyPromotionDiscounts($cart);
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

        $this->applyPromotionDiscounts($cart);
    }

    /**
     * Applica gli sconti delle promozioni attive al carrello
     */
    private function applyPromotionDiscounts($cart)
    {
        $active_promotions = $this->getActivePromotions();
        
        if (empty($active_promotions)) {
            return;
        }

        // Rimuovi tutte le regole di sconto precedenti del modulo
        $this->removePromotionDiscountRules($cart);

        foreach ($active_promotions as $promotion) {
            // Verifica se la promozione ha una regola di sconto
            if (isset($promotion['id_cart_rule']) && $promotion['id_cart_rule']) {
                $rule = new CartRule($promotion['id_cart_rule']);
                if (Validate::isLoadedObject($rule) && $rule->active) {
                    // Applica la regola al carrello se non è già applicata
                    $cart_rules = $cart->getCartRules();
                    $already_applied = false;
                    foreach ($cart_rules as $cart_rule) {
                        if ($cart_rule['id_cart_rule'] == $promotion['id_cart_rule']) {
                            $already_applied = true;
                            break;
                        }
                    }
                    
                    if (!$already_applied) {
                        $cart->addCartRule($promotion['id_cart_rule']);
                    }
                }
            }
        }
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
     * Crea una regola di sconto quando viene salvata una promozione
     */
    public function createPromotionDiscountRule($promotion_id)
    {
        error_log("PromotionsCountdown: Inizio creazione regola di sconto per promozione ID: " . $promotion_id);
        
        $promotion = $this->getPromotionById($promotion_id);
        if (!$promotion) {
            error_log("PromotionsCountdown: ERRORE - Promozione non trovata per ID: " . $promotion_id);
            return false;
        }

        error_log("PromotionsCountdown: Promozione trovata: " . print_r($promotion, true));

        try {
            error_log("PromotionsCountdown: Creazione oggetto CartRule");
            // Crea la regola di sconto
            $rule = new CartRule();
            
            error_log("PromotionsCountdown: Configurazione proprietà della regola");
            $rule->name = [
                Configuration::get('PS_LANG_DEFAULT') => 'Promozione: ' . $promotion['name']
            ];
            $rule->description = 'Sconto promozione countdown: ' . $promotion['name'];
            $rule->code = 'PROMO_' . $promotion['id_promotion'];
            $rule->quantity = 0;
            $rule->quantity_per_user = 0;
            $rule->priority = 1;
            $rule->partial_use = false;
            $rule->minimum_amount = 0;
            $rule->minimum_amount_tax = false;
            $rule->minimum_amount_currency = Configuration::get('PS_CURRENCY_DEFAULT');
            $rule->minimum_amount_shipping = false;
            $rule->country_restriction = false;
            $rule->carrier_restriction = false;
            $rule->group_restriction = false;
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

            error_log("PromotionsCountdown: Tentativo di salvataggio regola");
            if ($rule->add()) {
                error_log("PromotionsCountdown: Regola salvata con ID: " . $rule->id);
                
                // Associa i prodotti della promozione alla regola di sconto
                $this->associateProductsToCartRule($rule->id, $promotion_id);
                
                // Salva l'ID della regola nella promozione
                error_log("PromotionsCountdown: Aggiornamento promozione con ID regola");
                $update_result = Db::getInstance()->update('promotions_countdown', 
                    ['id_cart_rule' => $rule->id], 
                    'id_promotion = ' . (int)$promotion_id
                );
                
                if ($update_result) {
                    error_log("PromotionsCountdown: Promozione aggiornata con successo");
                } else {
                    error_log("PromotionsCountdown: ERRORE - Aggiornamento promozione fallito");
                }
                
                error_log("PromotionsCountdown: Regola di sconto creata con successo. ID: " . $rule->id);
                return $rule->id;
            } else {
                error_log("PromotionsCountdown: ERRORE - Salvataggio regola fallito");
                $errors = $rule->getErrors();
                error_log("PromotionsCountdown: Errori regola: " . print_r($errors, true));
                return false;
            }
        } catch (Exception $e) {
            error_log("PromotionsCountdown: ERRORE - Eccezione nella creazione della regola: " . $e->getMessage());
            error_log("PromotionsCountdown: Stack trace: " . $e->getTraceAsString());
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
        
        // Associa ogni prodotto alla regola di sconto
        foreach ($products as $product) {
            $sql = 'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_group` 
                    (id_cart_rule, quantity) 
                    VALUES ('.(int)$rule_id.', 1)';
            
            if (Db::getInstance()->execute($sql)) {
                $rule_group_id = Db::getInstance()->Insert_ID();
                
                // Crea la regola per il prodotto specifico
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
                
                // Crea una nuova regola di sconto
                $rule_id = $this->createPromotionDiscountRule($promotion['id_promotion']);
                
                if ($rule_id) {
                    // Aggiorna la promozione con l'ID della nuova regola
                    $update_result = Db::getInstance()->update('promotions_countdown', 
                        ['id_cart_rule' => $rule_id], 
                        'id_promotion = ' . (int)$promotion['id_promotion']
                    );
                    
                    if ($update_result) {
                        $updated_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++;
                }
            }

            if ($error_count > 0) {
                return [
                    'success' => false,
                    'message' => sprintf($this->l('Aggiornate %d promozioni, %d errori.'), $updated_count, $error_count)
                ];
            } else {
                return [
                    'success' => true,
                    'message' => sprintf($this->l('Aggiornate con successo %d promozioni.'), $updated_count)
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
     * Hook per pulire automaticamente le promozioni scadute
     */
    public function hookActionCronJob()
    {
        $this->cleanupExpiredPromotions();
    }
}