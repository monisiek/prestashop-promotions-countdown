<style>
    /* Nascondi le bandiere di sconto native nel checkout */
    .checkout-summary .product-flags .product-flag.discount,
    .checkout-summary .product-flags .product-flag.discount-percentage,
    .cart-summary .product-flags .product-flag.discount,
    .cart-summary .product-flags .product-flag.discount-percentage,
    .order-summary .product-flags .product-flag.discount,
    .order-summary .product-flags .product-flag.discount-percentage,
    .checkout-step .product-flags .product-flag.discount,
    .checkout-step .product-flags .product-flag.discount-percentage,
    .cart-detailed .product-flags .product-flag.discount,
    .cart-detailed .product-flags .product-flag.discount-percentage,
    .cart-item .product-flags .product-flag.discount,
    .cart-item .product-flags .product-flag.discount-percentage,
    .product-line .product-flags .product-flag.discount,
    .product-line .product-flags .product-flag.discount-percentage,
    .cart-product .product-flags .product-flag.discount,
    .cart-product .product-flags .product-flag.discount-percentage {
        display: none !important;
    }
    
    /* Nascondi il quadratino del 40% nel checkout */
    .discount.discount-percentage {
        display: none !important;
    }
    
    /* CORREZIONE: Rimuovi display none dai prezzi corretti nel cart-bottom (regole più aggressive) */
    .cart-bottom .cart-subtotals .total-line .value.price,
    .cart-bottom .cart-total .value.price,
    .cart-bottom .total-line .price,
    .cart-bottom .price-total,
    .cart-bottom span.price,
    .cart-bottom .value[style*="display"],
    .cart-bottom .price[style*="display"] {
        display: block !important;
        visibility: visible !important;
    }
    
    /* CORREZIONE: Nascondi la percentuale di sconto sbagliata nel product-line-info (regole più specifiche) */
    .product-line-info .product-discount .discount.discount-percentage,
    .product-line-info .discount-percentage,
    .product-line-info .discount,
    .has-discount .discount.discount-percentage,
    .product-price .discount.discount-percentage,
    .cart-item .discount.discount-percentage,
    .product-line .discount.discount-percentage {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Stile per la nostra bandiera di sconto corretta nel checkout */
    .promotion-checkout-discount {
        background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        color: white;
        font-weight: bold;
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        position: relative;
        overflow: hidden;
        animation: pulse-checkout 2s infinite;
        margin: 5px 0;
        display: inline-block;
        text-align: center;
    }
    
    .promotion-checkout-discount::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: shine-checkout 3s infinite;
    }
    
    @keyframes pulse-checkout {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }
    
    @keyframes shine-checkout {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .promotion-checkout-discount .discount-percent {
        font-size: 16px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    
    .promotion-checkout-discount .discount-text {
        font-size: 10px;
        opacity: 0.9;
        margin-left: 4px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Nascondi tutte le bandiere di sconto native nel checkout
        var nativeFlags = document.querySelectorAll('.checkout-summary .product-flags .product-flag.discount, .checkout-summary .product-flags .product-flag.discount-percentage, .cart-summary .product-flags .product-flag.discount, .cart-summary .product-flags .product-flag.discount-percentage, .order-summary .product-flags .product-flag.discount, .order-summary .product-flags .product-flag.discount-percentage, .checkout-step .product-flags .product-flag.discount, .checkout-step .product-flags .product-flag.discount-percentage, .cart-detailed .product-flags .product-flag.discount, .cart-detailed .product-flags .product-flag.discount-percentage, .cart-item .product-flags .product-flag.discount, .cart-item .product-flags .product-flag.discount-percentage, .product-line .product-flags .product-flag.discount, .product-line .product-flags .product-flag.discount-percentage, .cart-product .product-flags .product-flag.discount, .cart-product .product-flags .product-flag.discount-percentage');
        nativeFlags.forEach(function(flag) {
            flag.style.display = 'none';
        });
        
        // Nascondi il quadratino del 40% nel checkout
        var wrongDiscounts = document.querySelectorAll('.discount.discount-percentage');
        wrongDiscounts.forEach(function(discount) {
            discount.style.display = 'none';
        });
        
        // CORREZIONE 1: Rimuovi display: none dai prezzi corretti nel cart-bottom (AGGRESSIVO)
        function fixCartPrices() {
            // Selettori più ampi per catturare tutti i prezzi del cart-bottom
            var cartPriceSelectors = [
                '.cart-bottom .cart-subtotals .total-line .value.price',
                '.cart-bottom .cart-total .value.price',
                '.cart-bottom .total-line .price',
                '.cart-bottom .price-total',
                '.cart-bottom span.price',
                '.cart-bottom .value',
                '.cart-bottom .price'
            ];
            
            cartPriceSelectors.forEach(function(selector) {
                var elements = document.querySelectorAll(selector);
                elements.forEach(function(element) {
                    var style = element.getAttribute('style');
                    if (style && (style.includes('display: none') || style.includes('display:none') || style.includes('/* display: none'))) {
                        // Rimuovi completamente l'attributo style o solo la parte di display
                        var newStyle = style.replace(/display\s*:\s*none\s*;?/g, '').replace(/\/\*\s*display\s*:\s*none\s*;\?\s*\*\//g, '');
                        if (newStyle.trim() === '') {
                            element.removeAttribute('style');
                        } else {
                            element.setAttribute('style', newStyle);
                        }
                        // Forza la visualizzazione
                        element.style.display = 'block';
                        element.style.visibility = 'visible';
                    }
                });
            });
        }
        
        // CORREZIONE 2: Nascondi la percentuale di sconto sbagliata nel product-line-info (AGGRESSIVO)
        function hideWrongDiscounts() {
            var wrongDiscountSelectors = [
                '.product-line-info .product-discount .discount.discount-percentage',
                '.product-line-info .discount-percentage',
                '.product-line-info .discount',
                '.has-discount .discount.discount-percentage',
                '.product-price .discount.discount-percentage',
                '.cart-item .discount.discount-percentage',
                '.product-line .discount.discount-percentage',
                // Selettori specifici per il contenuto che hai mostrato
                '.product-line-info .product-discount span.discount',
                '.product-discount .discount-percentage'
            ];
            
            wrongDiscountSelectors.forEach(function(selector) {
                var elements = document.querySelectorAll(selector);
                elements.forEach(function(element) {
                    // Nascondi solo se contiene percentuali sbagliate (come -40%)
                    var text = element.textContent || element.innerText;
                    if (text.includes('-40%') || text.includes('40%') || element.classList.contains('discount-percentage')) {
                        element.style.display = 'none';
                        element.style.visibility = 'hidden';
                    }
                });
            });
        }
        
        // Esegui le correzioni
        fixCartPrices();
        hideWrongDiscounts();
        
        // Ri-esegui le correzioni dopo un piccolo delay per gestire contenuto caricato dinamicamente
        setTimeout(function() {
            fixCartPrices();
            hideWrongDiscounts();
        }, 500);
        
        // Ri-esegui le correzioni quando la pagina cambia (per SPA)
        if (window.MutationObserver) {
            var observer = new MutationObserver(function(mutations) {
                var shouldRerun = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        shouldRerun = true;
                    }
                });
                if (shouldRerun) {
                    setTimeout(function() {
                        fixCartPrices();
                        hideWrongDiscounts();
                    }, 100);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
        
        // Sostituisci con la percentuale corretta (99% nel tuo caso)
        // Questo è un placeholder - dovresti ottenere la percentuale reale dal backend
        var correctDiscount = 99; // Sostituisci con la percentuale reale della promozione
        
        // Cerca tutti i container che potrebbero contenere sconti nel checkout
        var checkoutContainers = document.querySelectorAll('.checkout-summary, .cart-summary, .order-summary, .checkout-step, .cart-detailed, .cart-item, .product-line, .cart-product');
        
        checkoutContainers.forEach(function(container) {
            // Se c'è un elemento nascosto con sconto, aggiungi il nostro
            var hiddenDiscount = container.querySelector('.discount.discount-percentage[style*="display: none"]');
            if (hiddenDiscount && !container.querySelector('.promotion-checkout-discount')) {
                var newDiscount = document.createElement('span');
                newDiscount.className = 'promotion-checkout-discount';
                newDiscount.innerHTML = '<span class="discount-percent">-' + correctDiscount + '%</span><span class="discount-text">SCONTO REALE</span>';
                
                // Inserisci dopo l'elemento nascosto
                hiddenDiscount.parentNode.insertBefore(newDiscount, hiddenDiscount.nextSibling);
            }
        });
    });
</script>