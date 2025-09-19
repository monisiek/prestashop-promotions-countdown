{if $product_discount}
    <style>
        /* Nascondi le bandiere di sconto native nella lista prodotti */
        .product-flags .product-flag.discount,
        .product-flags .product-flag.discount-percentage {
            display: none !important;
        }
        
        /* Nascondi i prezzi originali di PrestaShop nella lista prodotti */
        .product-miniature .price,
        .product-miniature .current-price,
        .product-miniature .regular-price,
        .product-miniature .product-price,
        .product-miniature .price-box,
        .product-miniature .price-container,
        .product-miniature .product-price-container,
        .product-miniature .current-price-value,
        .product-miniature .regular-price-value,
        .product-miniature .price-current,
        .product-miniature .price-regular,
        .product-miniature .product-price-current,
        .product-miniature .product-price-regular,
        .product-miniature .product-prices-block,
        .product-miniature .money,
        .product-miniature .sr-only,
        .product-miniature .price-now,
        .product-miniature .price-before,
        .product-miniature .price-after,
        .product-miniature .product-price-now,
        .product-miniature .product-price-before,
        .product-miniature .product-price-after,
        .product-miniature .product-price-wrapper,
        .product-miniature .price-wrapper,
        .product-miniature .product-price-content,
        .product-miniature .price-content,
        .product-miniature .product-price-block,
        .product-miniature .price-block,
        .product-miniature .product-price-display,
        .product-miniature .price-display,
        .product-miniature .product-price-info,
        .product-miniature .price-info,
        .product-miniature .product-price-main,
        .product-miniature .price-main,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-final,
        .product-miniature .price-final,
        .product-miniature .product-price-original,
        .product-miniature .price-original,
        .product-miniature .product-price-sale,
        .product-miniature .price-sale,
        .product-miniature .product-price-discount,
        .product-miniature .price-discount,
        .product-miniature .product-price-reduced,
        .product-miniature .price-reduced,
        .product-miniature .product-price-special,
        .product-miniature .price-special,
        .product-miniature .product-price-offer,
        .product-miniature .price-offer,
        .product-miniature .product-price-promo,
        .product-miniature .price-promo,
        .product-miniature .product-price-deal,
        .product-miniature .price-deal,
        .product-miniature .product-price-bargain,
        .product-miniature .price-bargain,
        .product-miniature .product-price-savings,
        .product-miniature .price-savings,
        .product-miniature .product-price-save,
        .product-miniature .price-save,
        .product-miniature .product-price-cut,
        .product-miniature .price-cut,
        .product-miniature .product-price-drop,
        .product-miniature .price-drop,
        .product-miniature .product-price-lower,
        .product-miniature .price-lower,
        .product-miniature .product-price-cheap,
        .product-miniature .price-cheap,
        .product-miniature .product-price-affordable,
        .product-miniature .price-affordable,
        .product-miniature .product-price-budget,
        .product-miniature .price-budget,
        .product-miniature .product-price-economy,
        .product-miniature .price-economy,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-cost,
        .product-miniature .price-cost,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-sum,
        .product-miniature .price-sum,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-full,
        .product-miniature .price-full,
        .product-miniature .product-price-complete,
        .product-miniature .price-complete,
        .product-miniature .product-price-entire,
        .product-miniature .price-entire,
        .product-miniature .product-price-whole,
        .product-miniature .price-whole,
        .product-miniature .product-price-all,
        .product-miniature .price-all,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-sum,
        .product-miniature .price-sum,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-cost,
        .product-miniature .price-cost,
        .product-miniature .product-price-price,
        .product-miniature .price-price,
        .product-miniature .product-price-money,
        .product-miniature .price-money,
        .product-miniature .product-price-cash,
        .product-miniature .price-cash,
        .product-miniature .product-price-currency,
        .product-miniature .price-currency,
        .product-miniature .product-price-euro,
        .product-miniature .price-euro,
        .product-miniature .product-price-eur,
        .product-miniature .price-eur,
        .product-miniature .product-price-€,
        .product-miniature .price-€,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-cost,
        .product-miniature .price-cost,
        .product-miniature .product-price-price,
        .product-miniature .price-price,
        .product-miniature .product-price-money,
        .product-miniature .price-money,
        .product-miniature .product-price-cash,
        .product-miniature .price-cash,
        .product-miniature .product-price-currency,
        .product-miniature .price-currency,
        .product-miniature .product-price-euro,
        .product-miniature .price-euro,
        .product-miniature .product-price-eur,
        .product-miniature .price-eur,
        .product-miniature .product-price-€,
        .product-miniature .price-€ {
            display: none !important;
        }
        
        /* Nascondi specificamente il blocco prezzi di PrestaShop */
        .product-miniature .product-prices-block {
            display: none !important;
        }
        
        .product-miniature .product-prices-block * {
            display: none !important;
        }
        
        /* Nascondi TUTTI i prezzi di PrestaShop in modo più aggressivo */
        .product-miniature [itemprop="price"],
        .product-miniature [data-currency-eur],
        .product-miniature .money,
        .product-miniature .sr-only {
            display: none !important;
        }
        
        /* Selettore generico per nascondere tutti gli elementi che contengono prezzi */
        .product-miniature [class*="price"]:not(.promotion-price-list-override):not(.promotion-price-list-override *),
        .product-miniature [class*="Price"]:not(.promotion-price-list-override):not(.promotion-price-list-override *),
        .product-miniature [class*="PRICE"]:not(.promotion-price-list-override):not(.promotion-price-list-override *) {
            display: none !important;
        }
        
        /* Nascondi anche elementi che potrebbero contenere prezzi */
        .product-miniature .product-info .price,
        .product-miniature .product-details .price,
        .product-miniature .product-content .price,
        .product-miniature .product-description .price,
        .product-miniature .product-summary .price,
        .product-miniature .product-text .price,
        .product-miniature .product-data .price,
        .product-miniature .product-meta .price,
        .product-miniature .product-attributes .price,
        .product-miniature .product-features .price,
        .product-miniature .product-specs .price,
        .product-miniature .product-specifications .price,
        .product-miniature .product-details .price,
        .product-miniature .product-information .price,
        .product-miniature .product-data .price,
        .product-miniature .product-meta .price,
        .product-miniature .product-attributes .price,
        .product-miniature .product-features .price,
        .product-miniature .product-specs .price,
        .product-miniature .product-specifications .price {
            display: none !important;
        }
        
        /* Stile per il nostro prezzo personalizzato nella lista prodotti */
        .promotion-price-list-override {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 8px 0;
            text-align: center;
        }
        
        /* Nascondi tutti gli elementi che contengono prezzi (approccio più aggressivo) */
        .product-miniature *:not(.promotion-price-list-override):not(.promotion-price-list-override *):not(.promotion-countdown-flag-list):not(.promotion-countdown-flag-list *) {
            /* Nascondi elementi che contengono solo numeri e simboli di valuta */
        }
        
        /* Nascondi elementi specifici che potrebbero contenere prezzi */
        .product-miniature .product-price,
        .product-miniature .price,
        .product-miniature .current-price,
        .product-miniature .regular-price,
        .product-miniature .price-box,
        .product-miniature .price-container,
        .product-miniature .product-price-container,
        .product-miniature .product-price-wrapper,
        .product-miniature .price-wrapper,
        .product-miniature .product-price-content,
        .product-miniature .price-content,
        .product-miniature .product-price-block,
        .product-miniature .price-block,
        .product-miniature .product-price-display,
        .product-miniature .price-display,
        .product-miniature .product-price-info,
        .product-miniature .price-info,
        .product-miniature .product-price-main,
        .product-miniature .price-main,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-final,
        .product-miniature .price-final,
        .product-miniature .product-price-original,
        .product-miniature .price-original,
        .product-miniature .product-price-sale,
        .product-miniature .price-sale,
        .product-miniature .product-price-discount,
        .product-miniature .price-discount,
        .product-miniature .product-price-reduced,
        .product-miniature .price-reduced,
        .product-miniature .product-price-special,
        .product-miniature .price-special,
        .product-miniature .product-price-offer,
        .product-miniature .price-offer,
        .product-miniature .product-price-promo,
        .product-miniature .price-promo,
        .product-miniature .product-price-deal,
        .product-miniature .price-deal,
        .product-miniature .product-price-bargain,
        .product-miniature .price-bargain,
        .product-miniature .product-price-savings,
        .product-miniature .price-savings,
        .product-miniature .product-price-save,
        .product-miniature .price-save,
        .product-miniature .product-price-cut,
        .product-miniature .price-cut,
        .product-miniature .product-price-drop,
        .product-miniature .price-drop,
        .product-miniature .product-price-lower,
        .product-miniature .price-lower,
        .product-miniature .product-price-cheap,
        .product-miniature .price-cheap,
        .product-miniature .product-price-affordable,
        .product-miniature .price-affordable,
        .product-miniature .product-price-budget,
        .product-miniature .price-budget,
        .product-miniature .product-price-economy,
        .product-miniature .price-economy,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-cost,
        .product-miniature .price-cost,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-sum,
        .product-miniature .price-sum,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-full,
        .product-miniature .price-full,
        .product-miniature .product-price-complete,
        .product-miniature .price-complete,
        .product-miniature .product-price-entire,
        .product-miniature .price-entire,
        .product-miniature .product-price-whole,
        .product-miniature .price-whole,
        .product-miniature .product-price-all,
        .product-miniature .price-all,
        .product-miniature .product-price-total,
        .product-miniature .price-total,
        .product-miniature .product-price-sum,
        .product-miniature .price-sum,
        .product-miniature .product-price-amount,
        .product-miniature .price-amount,
        .product-miniature .product-price-value,
        .product-miniature .price-value,
        .product-miniature .product-price-cost,
        .product-miniature .price-cost,
        .product-miniature .product-price-price,
        .product-miniature .price-price,
        .product-miniature .product-price-money,
        .product-miniature .price-money,
        .product-miniature .product-price-cash,
        .product-miniature .price-cash,
        .product-miniature .product-price-currency,
        .product-miniature .price-currency,
        .product-miniature .product-price-euro,
        .product-miniature .price-euro,
        .product-miniature .product-price-eur,
        .product-miniature .price-eur,
        .product-miniature .product-price-€,
        .product-miniature .price-€ {
            display: none !important;
        }
        
        .promotion-price-list-override .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .promotion-price-list-override .discounted-price {
            color: #e74c3c;
            font-size: 18px;
            font-weight: bold;
        }
        
        .promotion-price-list-override .discount-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        /* Stile per la nostra bandiera di promozione nella lista prodotti */
        .promotion-countdown-flag-list {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            font-weight: bold;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
            position: relative;
            overflow: hidden;
            animation: pulse-list 2s infinite;
            margin: 5px 0;
            display: inline-block;
            text-align: center;
        }
        
        .promotion-countdown-flag-list::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine-list 3s infinite;
        }
        
        @keyframes pulse-list {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        
        @keyframes shine-list {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .promotion-countdown-flag-list .discount-percent {
            font-size: 14px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .promotion-countdown-flag-list .discount-text {
            font-size: 9px;
            opacity: 0.9;
            margin-left: 3px;
        }
        
        /* Countdown timer per la lista prodotti */
        .countdown-list {
            font-size: 9px;
            opacity: 0.8;
            margin-top: 2px;
            font-family: monospace;
            font-weight: normal;
        }
    </style>
    
    <!-- Sovrascrivi il prezzo del prodotto nella lista -->
    <div class="promotion-price-list-override">
        <span class="original-price">{if isset($original_price_formatted)}{$original_price_formatted}{else}{$original_price|string_format:"%.2f"} €{/if}</span>
        <span class="discounted-price">{if isset($discounted_price_formatted)}{$discounted_price_formatted}{else}{$discounted_price|string_format:"%.2f"} €{/if}</span>
        <span class="discount-badge">-{$product_discount.discount_percent}%</span>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi tutte le bandiere di sconto native nella lista prodotti
            var nativeFlags = document.querySelectorAll('.product-miniature .product-flags .product-flag.discount, .product-miniature .product-flags .product-flag.discount-percentage');
            nativeFlags.forEach(function(flag) {
                flag.style.display = 'none';
            });
            
            // Nascondi i prezzi originali di PrestaShop nella lista prodotti
            var originalPrices = document.querySelectorAll('.product-miniature .price, .product-miniature .current-price, .product-miniature .regular-price, .product-miniature .product-price, .product-miniature .price-box, .product-miniature .price-container, .product-miniature .product-price-container, .product-miniature .current-price-value, .product-miniature .regular-price-value, .product-miniature .price-current, .product-miniature .price-regular, .product-miniature .product-price-current, .product-miniature .product-price-regular, .product-miniature .product-prices-block, .product-miniature .money, .product-miniature .sr-only, .product-miniature .price-now, .product-miniature .price-before, .product-miniature .price-after, .product-miniature .product-price-now, .product-miniature .product-price-before, .product-miniature .product-price-after, .product-miniature .product-price-wrapper, .product-miniature .price-wrapper, .product-miniature .product-price-content, .product-miniature .price-content, .product-miniature .product-price-block, .product-miniature .price-block, .product-miniature .product-price-display, .product-miniature .price-display, .product-miniature .product-price-info, .product-miniature .price-info, .product-miniature .product-price-main, .product-miniature .price-main, .product-miniature .product-price-value, .product-miniature .price-value, .product-miniature .product-price-amount, .product-miniature .price-amount, .product-miniature .product-price-total, .product-miniature .price-total, .product-miniature .product-price-final, .product-miniature .price-final, .product-miniature .product-price-original, .product-miniature .price-original, .product-miniature .product-price-sale, .product-miniature .price-sale, .product-miniature .product-price-discount, .product-miniature .price-discount, .product-miniature .product-price-reduced, .product-miniature .price-reduced, .product-miniature .product-price-special, .product-miniature .price-special, .product-miniature .product-price-offer, .product-miniature .price-offer, .product-miniature .product-price-promo, .product-miniature .price-promo, .product-miniature .product-price-deal, .product-miniature .price-deal, .product-miniature .product-price-bargain, .product-miniature .price-bargain, .product-miniature .product-price-savings, .product-miniature .price-savings, .product-miniature .product-price-save, .product-miniature .price-save, .product-miniature .product-price-cut, .product-miniature .price-cut, .product-miniature .product-price-drop, .product-miniature .price-drop, .product-miniature .product-price-lower, .product-miniature .price-lower, .product-miniature .product-price-cheap, .product-miniature .price-cheap, .product-miniature .product-price-affordable, .product-miniature .price-affordable, .product-miniature .product-price-budget, .product-miniature .price-budget, .product-miniature .product-price-economy, .product-miniature .price-economy, .product-miniature .product-price-value, .product-miniature .price-value, .product-miniature .product-price-cost, .product-miniature .price-cost, .product-miniature .product-price-amount, .product-miniature .price-amount, .product-miniature .product-price-sum, .product-miniature .price-sum, .product-miniature .product-price-total, .product-miniature .price-total, .product-miniature .product-price-full, .product-miniature .price-full, .product-miniature .product-price-complete, .product-miniature .price-complete, .product-miniature .product-price-entire, .product-miniature .price-entire, .product-miniature .product-price-whole, .product-miniature .price-whole, .product-miniature .product-price-all, .product-miniature .price-all, .product-miniature .product-price-total, .product-miniature .price-total, .product-miniature .product-price-sum, .product-miniature .price-sum, .product-miniature .product-price-amount, .product-miniature .price-amount, .product-miniature .product-price-value, .product-miniature .price-value, .product-miniature .product-price-cost, .product-miniature .price-cost, .product-miniature .product-price-price, .product-miniature .price-price, .product-miniature .product-price-money, .product-miniature .price-money, .product-miniature .product-price-cash, .product-miniature .price-cash, .product-miniature .product-price-currency, .product-miniature .price-currency, .product-miniature .product-price-euro, .product-miniature .price-euro, .product-miniature .product-price-eur, .product-miniature .price-eur, .product-miniature .product-price-€, .product-miniature .price-€');
            originalPrices.forEach(function(price) {
                price.style.display = 'none';
            });
            
            // Nascondi specificamente il blocco prezzi di PrestaShop
            var priceBlocks = document.querySelectorAll('.product-miniature .product-prices-block');
            priceBlocks.forEach(function(block) {
                block.style.display = 'none';
            });
            
            // Nascondi elementi specifici di PrestaShop
            var specificElements = document.querySelectorAll('.product-miniature [itemprop="price"], .product-miniature [data-currency-eur], .product-miniature .money, .product-miniature .sr-only');
            specificElements.forEach(function(element) {
                element.style.display = 'none';
            });
            
            // Nascondi anche tutti gli elementi che contengono prezzi con selettori più generici
            var allPriceElements = document.querySelectorAll('.product-miniature [class*="price"], .product-miniature [class*="Price"], .product-miniature [class*="PRICE"]');
            allPriceElements.forEach(function(element) {
                // Controlla se l'elemento contiene un prezzo (numero seguito da € o simbolo valuta)
                var text = element.textContent || element.innerText || '';
                if (text.match(/\d+[.,]\d+\s*[€$£¥]/) || text.match(/\d+[.,]\d+\s*[€$£¥]/)) {
                    element.style.display = 'none';
                }
            });
            
            // Approccio più aggressivo: nascondi tutti gli elementi che contengono prezzi
            var allElements = document.querySelectorAll('.product-miniature *');
            allElements.forEach(function(element) {
                // Salta i nostri elementi personalizzati
                if (element.classList.contains('promotion-price-list-override') || 
                    element.classList.contains('promotion-countdown-flag-list') ||
                    element.closest('.promotion-price-list-override') ||
                    element.closest('.promotion-countdown-flag-list')) {
                    return;
                }
                
                var text = element.textContent || element.innerText || '';
                // Nascondi se contiene un prezzo (numero seguito da € o simbolo valuta)
                if (text.match(/\d+[.,]\d+\s*[€$£¥]/) || text.match(/\d+[.,]\d+\s*[€$£¥]/)) {
                    element.style.display = 'none';
                }
            });
            
            // DEBUG: Log per capire cosa stiamo nascondendo
            console.log('PromotionsCountdown: Hidden price blocks:', priceBlocks.length);
            console.log('PromotionsCountdown: Hidden specific elements:', specificElements.length);
            console.log('PromotionsCountdown: Hidden all price elements:', allPriceElements.length);
            
            // Cerca un container appropriato per inserire la nostra bandiera nella lista prodotti
            var productMiniature = document.querySelector('.product-miniature[data-id-product="{$product_id}"]');
            if (productMiniature) {
                var productFlags = productMiniature.querySelector('.product-flags');
                var productInfo = productMiniature.querySelector('.product-description');
                var productActions = productMiniature.querySelector('.product-actions');
                
                var targetContainer = productFlags || productInfo || productActions;
                
                if (targetContainer && !targetContainer.querySelector('.promotion-countdown-flag-list')) {
                    var countdownFlag = document.createElement('div');
                    countdownFlag.className = 'promotion-countdown-flag-list';
                    countdownFlag.innerHTML = '<span class="discount-percent">-{$product_discount.discount_percent}%</span><span class="discount-text">PROMO</span><div class="countdown-list" id="countdown-list-{$product_id}"></div>';
                    
                    // Inserisci nel container appropriato
                    if (targetContainer === productFlags) {
                        targetContainer.appendChild(countdownFlag);
                    } else {
                        targetContainer.insertBefore(countdownFlag, targetContainer.firstChild);
                    }
                    
                    // Avvia il countdown
                    var endTime = new Date('{$product_discount.end_date}').getTime();
                    var countdownElement = document.getElementById('countdown-list-{$product_id}');
                    
                    if (countdownElement) {
                        var countdownInterval = setInterval(function() {
                            var now = new Date().getTime();
                            var distance = endTime - now;
                            
                            if (distance < 0) {
                                clearInterval(countdownInterval);
                                countdownElement.innerHTML = 'SCADUTO';
                                countdownElement.style.color = '#ff0000';
                                countdownElement.style.fontWeight = 'bold';
                                return;
                            }
                            
                            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            
                            var timeString = '';
                            if (days > 0) timeString += days + 'g ';
                            if (hours > 0) timeString += hours + 'h ';
                            timeString += minutes + 'm';
                            
                            countdownElement.innerHTML = timeString;
                            
                            // Cambia colore quando mancano meno di 10 minuti
                            if (distance < 600000) { // 10 minuti
                                countdownElement.style.color = '#ffeb3b';
                                countdownElement.style.fontWeight = 'bold';
                            }
                        }, 1000);
                    }
                }
            }
            
            // DEBUG: Log per capire se il template è stato caricato
            console.log('PromotionsCountdown: Template product_list_override.tpl loaded for product {$product_id}');
        });
    </script>
{else}
    <!-- DEBUG: Template non caricato -->
    <script>
        console.log('PromotionsCountdown: Template product_list_override.tpl NOT loaded - no product_discount');
    </script>
{/if}