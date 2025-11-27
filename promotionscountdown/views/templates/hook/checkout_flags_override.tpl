{if isset($promotion_checkout_lines) && $promotion_checkout_lines|count > 0}
<style>
    .promotion-checkout-summary {
        background: #f9f3ff;
        border: 1px solid #ded0ff;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 12px;
        font-size: 0.9rem;
        color: #3c2a4d;
    }

    .promotion-checkout-summary strong {
        color: #7a1fa0;
    }

    .promotion-checkout-list {
        list-style: none;
        margin: 8px 0 0 0;
        padding: 0;
    }

    .promotion-checkout-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }

    .promotion-checkout-prices {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 4px;
        font-size: 0.95rem;
    }

    .promotion-checkout-original {
        text-decoration: line-through;
        color: #9b9b9b;
    }

    .promotion-checkout-discounted {
        color: #c0392b;
        font-weight: 600;
    }

    .promotion-checkout-badge {
        background: #c0392b;
        color: #fff;
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 999px;
        text-transform: uppercase;
    }
</style>

<div class="promotion-checkout-summary">
    <strong>Sconto countdown applicato.</strong> Mostriamo il prezzo originale barrato e
    quello scontato per i prodotti coinvolti.
    <ul class="promotion-checkout-list">
        {foreach from=$promotion_checkout_lines item=line}
            <li>
                <span>-{$line.discount_percent}%</span>
                <span class="promotion-checkout-original">{$line.original_price_formatted}</span>
                <span class="promotion-checkout-discounted">{$line.discounted_price_formatted}</span>
            </li>
        {/foreach}
    </ul>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var promoLines = {$promotion_checkout_lines|json_encode nofilter};
        if (!Array.isArray(promoLines) || !promoLines.length) {
            return;
        }

        promoLines.forEach(function(line) {
            var selector = '.cart-item[data-id-product="' + line.id_product + '"]';
            if (parseInt(line.id_product_attribute, 10)) {
                selector += '[data-id-product-attribute="' + line.id_product_attribute + '"]';
            }

            var cartItem = document.querySelector(selector);
            if (!cartItem) {
                return;
            }

            var priceTarget = cartItem.querySelector('.product-line-info .product-price, .product-line-price, .product-price');
            if (!priceTarget) {
                priceTarget = cartItem;
            }

            if (priceTarget.querySelector('.promotion-checkout-prices')) {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'promotion-checkout-prices';
            wrapper.innerHTML = '<span class="promotion-checkout-original">' + line.original_price_formatted + '</span>' +
                '<span class="promotion-checkout-discounted">' + line.discounted_price_formatted + '</span>' +
                '<span class="promotion-checkout-badge">-' + line.discount_percent + '%</span>';

            priceTarget.appendChild(wrapper);
        });
    });
</script>
{/if}
