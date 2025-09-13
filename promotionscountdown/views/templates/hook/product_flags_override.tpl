{if $product_discount}
    <style>
        /* Nascondi le bandiere di sconto native di PrestaShop */
        .product-flags .product-flag.discount,
        .product-flags .product-flag.discount-percentage {
            display: none !important;
        }
        
        /* Stile per la nostra bandiera di promozione */
        .promotion-countdown-flag {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            font-weight: bold;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
        }
        
        .promotion-countdown-flag::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 3s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .promotion-countdown-flag .discount-percent {
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .promotion-countdown-flag .discount-text {
            font-size: 10px;
            opacity: 0.9;
            margin-left: 4px;
        }
        
        /* Effetto countdown timer */
        .countdown-mini {
            font-size: 10px;
            opacity: 0.8;
            margin-top: 2px;
            font-family: monospace;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi tutte le bandiere di sconto native
            var nativeFlags = document.querySelectorAll('.product-flags .product-flag.discount, .product-flags .product-flag.discount-percentage');
            nativeFlags.forEach(function(flag) {
                flag.style.display = 'none';
            });
            
            // Se non esiste già, aggiungi la nostra bandiera
            var productFlags = document.querySelector('.product-flags');
            if (productFlags && !productFlags.querySelector('.promotion-countdown-flag')) {
                var countdownFlag = document.createElement('li');
                countdownFlag.className = 'product-flag promotion-countdown-flag';
                countdownFlag.innerHTML = '<span class="discount-percent">-{$product_discount.discount_percent}%</span><span class="discount-text">PROMO</span><div class="countdown-mini" id="countdown-mini-{$product_id}"></div>';
                
                // Aggiungi all'inizio delle bandiere
                productFlags.insertBefore(countdownFlag, productFlags.firstChild);
                
                // Avvia il countdown
                var endTime = new Date('{$product_discount.end_date}').getTime();
                var countdownElement = document.getElementById('countdown-mini-{$product_id}');
                
                if (countdownElement) {
                    var countdownInterval = setInterval(function() {
                        var now = new Date().getTime();
                        var distance = endTime - now;
                        
                        if (distance < 0) {
                            clearInterval(countdownInterval);
                            countdownElement.innerHTML = 'SCADUTO';
                            countdownElement.style.color = '#ff0000';
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
                    }, 1000);
                }
            }
        });
    </script>
{/if}
