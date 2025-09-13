{if $product_discount}
    <div class="promotion-price-discount">
        <div class="original-price">
            <span class="price-label">Prezzo originale:</span>
            <span class="price-value">{$product.price|string_format:"%.2f"} €</span>
        </div>
        <div class="discounted-price">
            <span class="price-label">Prezzo scontato:</span>
            <span class="price-value">{$product.price * (1 - $product_discount.discount_percent / 100)|string_format:"%.2f"} €</span>
        </div>
        <div class="discount-info">
            <span class="discount-percent">-{$product_discount.discount_percent}%</span>
            <span class="discount-text">SCONTO</span>
        </div>
    </div>
{/if}
