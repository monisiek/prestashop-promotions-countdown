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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Nascondi tutte le bandiere di sconto native nel checkout
        var nativeFlags = document.querySelectorAll('.checkout-summary .product-flags .product-flag.discount, .checkout-summary .product-flags .product-flag.discount-percentage, .cart-summary .product-flags .product-flag.discount, .cart-summary .product-flags .product-flag.discount-percentage, .order-summary .product-flags .product-flag.discount, .order-summary .product-flags .product-flag.discount-percentage, .checkout-step .product-flags .product-flag.discount, .checkout-step .product-flags .product-flag.discount-percentage, .cart-detailed .product-flags .product-flag.discount, .cart-detailed .product-flags .product-flag.discount-percentage, .cart-item .product-flags .product-flag.discount, .cart-item .product-flags .product-flag.discount-percentage, .product-line .product-flags .product-flag.discount, .product-line .product-flags .product-flag.discount-percentage, .cart-product .product-flags .product-flag.discount, .cart-product .product-flags .product-flag.discount-percentage');
        nativeFlags.forEach(function(flag) {
            flag.style.display = 'none';
        });
    });
</script>