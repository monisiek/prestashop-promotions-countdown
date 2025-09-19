{if $product_discount}
    <div class="promotion-price-discount">
        <div class="original-price">
            <span class="price-label">Prezzo originale (tasse incl.):</span>
            <span class="price-value">{if isset($original_price_formatted)}{$original_price_formatted}{else}{$original_price|string_format:"%.2f"} €{/if}</span>
        </div>
        <div class="discounted-price">
            <span class="price-label">Prezzo scontato:</span>
            <span class="price-value">{if isset($discounted_price_formatted)}{$discounted_price_formatted}{else}{$discounted_price|string_format:"%.2f"} €{/if}</span>
        </div>
        <div class="discount-info">
            <span class="discount-percent">-{$product_discount.discount_percent}%</span>
            <span class="discount-text">SCONTO</span>
        </div>
    </div>
{/if}
