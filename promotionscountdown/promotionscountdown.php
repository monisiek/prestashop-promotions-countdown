<?php
/**
 * Modulo Promozioni con Countdown per PrestaShop 1.7.8+
 * @author Il tuo nome
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
        $this->author = 'Il tuo nome';
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
            $this->registerHook('displayHome') &&
            $this->registerHook('displayProductListReviews') &&
            Configuration::updateValue('PROMOTIONS_COUNTDOWN_NAME', 'Promozioni con Countdown');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        return parent::uninstall() &&
            Configuration::deleteByName('PROMOTIONS_COUNTDOWN_NAME');
    }

    public function hookDisplayHeader()
    {
        if (!Context::getContext()->controller instanceof AdminController) {
            $this->context->controller->addCSS($this->_path.'views/css/promotionscountdown.css');
            $this->context->controller->addJS($this->_path.'views/js/promotionscountdown.js');
        } else {
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }

    public function hookDisplayHome()
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

    public function getContent()
    {
        $output = null;

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
            } else if (strtotime($end_date) <= time()) {
                $output .= $this->displayError($this->l('La data di scadenza deve essere nel futuro.'));
            } else {
                if (empty($selected_products) || !is_array($selected_products)) {
                    $output .= $this->displayError($this->l('Devi selezionare almeno un prodotto per la promozione.'));
                } else {
                    $promotion_id = $this->savePromotion($promotion_name, $discount_percent, $start_date, $end_date, $banner_image, $selected_products);
                    
                    if ($promotion_id) {
                        $output .= $this->displayConfirmation($this->l('Promozione salvata con successo. Prodotti selezionati: ') . count($selected_products));
                    } else {
                        $output .= $this->displayError($this->l('Errore nel salvare la promozione.'));
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

        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Impostazioni Promozione'),
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
                    'html_content' => $this->generateProductSelector($products, $manufacturers)
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

        $existing_promotions = $this->getExistingPromotions();
        $promotions_list = '';
        
        if (!empty($existing_promotions)) {
            $promotions_list = '<div class="panel"><div class="panel-heading">' . $this->l('Promozioni Esistenti') . '</div><div class="panel-body">';
            $promotions_list .= '<table class="table"><thead><tr><th>Nome</th><th>Sconto</th><th>Data Inizio</th><th>Data Scadenza</th><th>Prodotti</th><th>Stato</th></tr></thead><tbody>';
            
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
                $promotions_list .= '</tr>';
            }
            
            $promotions_list .= '</tbody></table></div></div>';
        }

        return $promotions_list . $helper->generateForm($fields_form);
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

    private function generateProductSelector($products, $manufacturers)
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
                        <button type="button" class="btn btn-sm btn-success" id="select-all-visible">' . $this->l('Seleziona tutti visibili') . '</button>
                        <button type="button" class="btn btn-sm btn-warning" id="deselect-all">' . $this->l('Deseleziona tutti') . '</button>
                    </div>
                </div>
            </div>
            
            <div id="selected-products-info" style="margin-bottom: 15px; padding: 10px; background: #e8f5e8; border-radius: 5px; display: none;">
                <strong>' . $this->l('Prodotti selezionati:') . '</strong> <span id="selected-count">0</span>
                <div id="selected-products-list" style="margin-top: 10px;"></div>
            </div>
            
            <div class="product-grid" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;">
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
                            <input type="checkbox" name="selected_products[]" value="' . $product['id_product'] . '" class="product-selector">
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
        $image_name = null;
        if (isset($banner_image) && $banner_image['error'] == 0) {
            $image_name = $this->uploadBannerImage($banner_image);
        }

        $sql = 'INSERT INTO `'._DB_PREFIX_.'promotions_countdown` 
                (name, discount_percent, start_date, end_date, banner_image, created_date, active) 
                VALUES ("'.pSQL($name).'", '.(float)$discount.', "'.pSQL($start_date).'", "'.pSQL($end_date).'", "'.pSQL($image_name).'", NOW(), 1)';
        
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $promotion_id = Db::getInstance()->Insert_ID();
        $category_id = $this->createPromotionCategory($name, $promotion_id);
        
        Db::getInstance()->update('promotions_countdown', ['id_category' => $category_id], 'id_promotion = '.(int)$promotion_id);

        if (!empty($selected_products)) {
            foreach ($selected_products as $product_id) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'promotion_products` (id_promotion, id_product) 
                        VALUES ('.(int)$promotion_id.', '.(int)$product_id.')';
                Db::getInstance()->execute($sql);
                $this->addProductToCategory($product_id, $category_id);
            }
        }

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
            return $filename;
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
}