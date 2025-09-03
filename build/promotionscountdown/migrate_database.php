<?php
/**
 * Script di migrazione database per aggiungere start_date
 * Esegui questo script UNA SOLA VOLTA se hai già il modulo installato
 * Attenzione: fai backup del database prima di eseguire
 */

// Verifica di essere nell'ambiente PrestaShop
if (!defined('_PS_VERSION_')) {
    define('_PS_ROOT_DIR_', dirname(__FILE__) . '/../../..');
    require_once(_PS_ROOT_DIR_ . '/config/config.inc.php');
}

function migratePromotionsDatabase() {
    $errors = [];
    $success = [];
    
    try {
        // Controlla se la colonna start_date esiste già
        $checkColumn = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                       WHERE TABLE_SCHEMA = '" . _DB_NAME_ . "' 
                       AND TABLE_NAME = '" . _DB_PREFIX_ . "promotions_countdown' 
                       AND COLUMN_NAME = 'start_date'";
        
        $result = Db::getInstance()->executeS($checkColumn);
        
        if ($result[0]['count'] == 0) {
            // Aggiunge la colonna start_date
            $addColumn = "ALTER TABLE `" . _DB_PREFIX_ . "promotions_countdown` 
                         ADD `start_date` DATETIME NOT NULL AFTER `discount_percent`";
            
            if (Db::getInstance()->execute($addColumn)) {
                $success[] = "Colonna start_date aggiunta con successo";
                
                // Imposta start_date = created_date per le promozioni esistenti
                $updateExisting = "UPDATE `" . _DB_PREFIX_ . "promotions_countdown` 
                                 SET start_date = created_date 
                                 WHERE start_date = '0000-00-00 00:00:00' OR start_date IS NULL";
                
                if (Db::getInstance()->execute($updateExisting)) {
                    $success[] = "Date di inizio aggiornate per promozioni esistenti";
                } else {
                    $errors[] = "Errore nell'aggiornamento delle date esistenti";
                }
                
            } else {
                $errors[] = "Errore nell'aggiungere la colonna start_date";
            }
        } else {
            $success[] = "Colonna start_date già esistente";
        }
        
        // Aggiunge indici per migliorare le performance
        $addIndexes = [
            "ALTER TABLE `" . _DB_PREFIX_ . "promotions_countdown` 
             ADD INDEX `idx_dates` (`start_date`, `end_date`)",
            
            "ALTER TABLE `" . _DB_PREFIX_ . "promotions_countdown` 
             ADD INDEX `idx_active` (`active`)",
             
            "ALTER TABLE `" . _DB_PREFIX_ . "promotion_products` 
             ADD INDEX `idx_promotion` (`id_promotion`)",
             
            "ALTER TABLE `" . _DB_PREFIX_ . "promotion_products` 
             ADD INDEX `idx_product` (`id_product`)"
        ];
        
        foreach ($addIndexes as $indexQuery) {
            try {
                if (Db::getInstance()->execute($indexQuery)) {
                    $success[] = "Indice aggiunto";
                }
            } catch (Exception $e) {
                // Gli indici potrebbero già esistere, non è un errore critico
                continue;
            }
        }
        
        // Verifica integrità dati
        $checkIntegrity = "SELECT COUNT(*) as invalid_promotions 
                          FROM `" . _DB_PREFIX_ . "promotions_countdown` 
                          WHERE start_date >= end_date";
        
        $result = Db::getInstance()->executeS($checkIntegrity);
        
        if ($result[0]['invalid_promotions'] > 0) {
            $errors[] = "Attenzione: " . $result[0]['invalid_promotions'] . " promozioni hanno data inizio >= data scadenza";
        }
        
    } catch (Exception $e) {
        $errors[] = "Errore durante la migrazione: " . $e->getMessage();
    }
    
    return [
        'success' => $success,
        'errors' => $errors
    ];
}

// Esegue la migrazione se chiamato direttamente
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    echo "<h2>Migrazione Database Promozioni Countdown</h2>";
    echo "<p><strong>Attenzione:</strong> Assicurati di aver fatto un backup del database!</p>";
    
    $result = migratePromotionsDatabase();
    
    if (!empty($result['success'])) {
        echo "<h3 style='color: green;'>Operazioni completate:</h3>";
        echo "<ul>";
        foreach ($result['success'] as $msg) {
            echo "<li>" . htmlspecialchars($msg) . "</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($result['errors'])) {
        echo "<h3 style='color: red;'>Errori:</h3>";
        echo "<ul>";
        foreach ($result['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h3>Passi successivi:</h3>";
    echo "<ol>";
    echo "<li>Sostituisci i file del modulo con le versioni aggiornate</li>";
    echo "<li>Vai nell'admin PrestaShop → Moduli → Trova 'Promozioni con Countdown'</li>";
    echo "<li>Clicca 'Reinstalla' per applicare le nuove funzionalità</li>";
    echo "<li>Testa la creazione di una nuova promozione</li>";
    echo "</ol>";
    
    echo "<h3>Note:</h3>";
    echo "<ul>";
    echo "<li>Le promozioni esistenti avranno come data di inizio la data di creazione</li>";
    echo "<li>Dovrai modificare manualmente le promozioni esistenti per impostare date corrette</li>";
    echo "<li>Il modulo ora supporta promozioni programmate per il futuro</li>";
    echo "</ul>";
}

/**
 * Funzione per verificare lo stato delle promozioni
 */
function checkPromotionsStatus() {
    $sql = "SELECT 
                name,
                start_date,
                end_date,
                CASE 
                    WHEN start_date > NOW() THEN 'Programmata'
                    WHEN start_date <= NOW() AND end_date > NOW() THEN 'Attiva'
                    ELSE 'Scaduta'
                END as status,
                discount_percent
            FROM `" . _DB_PREFIX_ . "promotions_countdown` 
            ORDER BY start_date DESC";
    
    return Db::getInstance()->executeS($sql);
}
?>