{if $product_discount}
    <style>
        /* Nascondi le bandiere di sconto native nella lista prodotti */
        .product-miniature .product-flags .product-flag.discount,
        .product-miniature .product-flags .product-flag.discount-percentage {
            display: none !important;
        }
    </style>
    
    <div class="promotion-discount-badge">
        <span class="discount-percent">-{$product_discount.discount_percent}%</span>
        <span class="discount-text">SCONTO</span>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi tutte le bandiere di sconto native nella lista prodotti
            var nativeFlags = document.querySelectorAll('.product-miniature .product-flags .product-flag.discount, .product-miniature .product-flags .product-flag.discount-percentage');
            nativeFlags.forEach(function(flag) {
                flag.style.display = 'none';
            });
        });
    </script>
{/if}
