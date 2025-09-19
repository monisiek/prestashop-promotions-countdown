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