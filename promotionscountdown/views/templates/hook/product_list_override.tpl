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
        .product-miniature .product-price-regular {
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
            var originalPrices = document.querySelectorAll('.product-miniature .price, .product-miniature .current-price, .product-miniature .regular-price, .product-miniature .product-price, .product-miniature .price-box, .product-miniature .price-container, .product-miniature .product-price-container, .product-miniature .current-price-value, .product-miniature .regular-price-value, .product-miniature .price-current, .product-miniature .price-regular, .product-miniature .product-price-current, .product-miniature .product-price-regular');
            originalPrices.forEach(function(price) {
                price.style.display = 'none';
            });
            
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
        });
    </script>
{/if}