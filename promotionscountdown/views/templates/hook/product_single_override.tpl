{if $product_discount}
    <style>
        /* Nascondi le bandiere di sconto native nella pagina del prodotto singolo */
        .product-flags .product-flag.discount,
        .product-flags .product-flag.discount-percentage {
            display: none !important;
        }
        
        /* Stile per la nostra bandiera di promozione nella pagina del prodotto */
        .promotion-countdown-flag-single {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            font-weight: bold;
            font-size: 18px;
            padding: 12px 20px;
            border-radius: 25px;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            position: relative;
            overflow: hidden;
            animation: pulse-single 2s infinite;
            margin: 10px 0;
            display: inline-block;
        }
        
        .promotion-countdown-flag-single::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine-single 3s infinite;
        }
        
        @keyframes pulse-single {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        
        @keyframes shine-single {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .promotion-countdown-flag-single .discount-percent {
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .promotion-countdown-flag-single .discount-text {
            font-size: 12px;
            opacity: 0.9;
            margin-left: 6px;
        }
        
        /* Countdown timer più grande per la pagina del prodotto */
        .countdown-large {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
            font-family: monospace;
            font-weight: normal;
        }
        
        /* Messaggio di urgenza */
        .urgency-message {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 15px;
            margin-top: 8px;
            font-size: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi tutte le bandiere di sconto native nella pagina del prodotto
            var nativeFlags = document.querySelectorAll('.product-flags .product-flag.discount, .product-flags .product-flag.discount-percentage');
            nativeFlags.forEach(function(flag) {
                flag.style.display = 'none';
            });
            
            // Cerca un container appropriato per inserire la nostra bandiera
            var productFlags = document.querySelector('.product-flags');
            var productInfo = document.querySelector('.product-information');
            var productActions = document.querySelector('.product-actions');
            
            var targetContainer = productFlags || productInfo || productActions;
            
            if (targetContainer && !targetContainer.querySelector('.promotion-countdown-flag-single')) {
                var countdownFlag = document.createElement('div');
                countdownFlag.className = 'promotion-countdown-flag-single';
                countdownFlag.innerHTML = '<span class="discount-percent">-{$product_discount.discount_percent}%</span><span class="discount-text">PROMO</span><div class="countdown-large" id="countdown-large-{$product_id}"></div><div class="urgency-message">Offerta limitata!</div>';
                
                // Inserisci nel container appropriato
                if (targetContainer === productFlags) {
                    targetContainer.appendChild(countdownFlag);
                } else {
                    targetContainer.insertBefore(countdownFlag, targetContainer.firstChild);
                }
                
                // Avvia il countdown
                var endTime = new Date('{$product_discount.end_date}').getTime();
                var countdownElement = document.getElementById('countdown-large-{$product_id}');
                
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
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        var timeString = '';
                        if (days > 0) timeString += days + 'g ';
                        if (hours > 0) timeString += hours + 'h ';
                        if (minutes > 0) timeString += minutes + 'm ';
                        timeString += seconds + 's';
                        
                        countdownElement.innerHTML = timeString;
                        
                        // Cambia colore quando mancano meno di 10 minuti
                        if (distance < 600000) { // 10 minuti
                            countdownElement.style.color = '#ffeb3b';
                            countdownElement.style.fontWeight = 'bold';
                        }
                    }, 1000);
                }
            }
        });
    </script>
{/if}
