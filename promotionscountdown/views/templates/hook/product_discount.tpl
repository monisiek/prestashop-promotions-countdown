{if $product_discount}
    <style>
        /* Nascondi le bandiere di sconto native nella lista prodotti */
        .product-miniature .product-flags .product-flag.discount,
        .product-miniature .product-flags .product-flag.discount-percentage {
            display: none !important;
        }
        
        /* Stile per il pannellino rosso ingrandito con prezzi */
        .promotion-discount-badge-enhanced {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 15px 20px;
            border-radius: 25px;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            position: relative;
            overflow: hidden;
            animation: pulse-enhanced 2s infinite;
            margin: 10px 0;
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        
        .promotion-discount-badge-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine-enhanced 3s infinite;
        }
        
        @keyframes pulse-enhanced {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes shine-enhanced {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .promotion-discount-badge-enhanced .discount-percent {
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            display: block;
            margin-bottom: 5px;
        }
        
        .promotion-discount-badge-enhanced .discount-text {
            font-size: 14px;
            opacity: 0.9;
            display: block;
            margin-bottom: 8px;
        }
        
        .promotion-discount-badge-enhanced .price-info {
            font-size: 12px;
            opacity: 0.8;
            font-style: italic;
            margin-top: 5px;
        }
        
        .promotion-discount-badge-enhanced .countdown-mini {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 5px;
            font-family: monospace;
        }
    </style>
    
    <div class="promotion-discount-badge-enhanced">
        <span class="discount-percent">-{$product_discount.discount_percent}%</span>
        <span class="discount-text">SCONTO REALE</span>
        <div class="price-info">Prezzo scontato visibile nel carrello e nella pagina prodotto</div>
        <div class="countdown-mini" id="countdown-enhanced-{$product_id}"></div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi tutte le bandiere di sconto native nella lista prodotti
            var nativeFlags = document.querySelectorAll('.product-miniature .product-flags .product-flag.discount, .product-miniature .product-flags .product-flag.discount-percentage');
            nativeFlags.forEach(function(flag) {
                flag.style.display = 'none';
            });
            
            // Avvia il countdown per il pannellino ingrandito
            var endTime = new Date('{$product_discount.end_date}').getTime();
            var countdownElement = document.getElementById('countdown-enhanced-{$product_id}');
            
            if (countdownElement) {
                var countdownInterval = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = endTime - now;
                    
                    if (distance < 0) {
                        clearInterval(countdownInterval);
                        countdownElement.innerHTML = 'SCADUTO';
                        countdownElement.style.color = '#ffeb3b';
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
        });
    </script>
{/if}
