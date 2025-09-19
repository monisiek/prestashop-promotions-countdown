{* DEBUG INFO PER CARRELLO *}
{if isset($cart_debug_info)}
    <div style="background: #ffffcc; padding: 10px; margin: 10px 0; border: 2px solid #ff6600; font-size: 12px; font-weight: bold;">
        <strong>DEBUG CARRELLO PromotionsCountdown:</strong><br>
        Prodotto ID: {$cart_debug_info.product_id}<br>
        Carrello ID: {$cart_debug_info.cart_id}<br>
        Promozioni attive: {$cart_debug_info.active_promotions_count}<br>
        Migliore: {if $cart_debug_info.best_discount}{$cart_debug_info.best_discount.type} ({$cart_debug_info.best_discount.discount_percent}%){else}Nessuna{/if}<br>
        Countdown è migliore: {if $cart_debug_info.is_countdown_best}SÌ{else}NO{/if}<br>
        <span style="color: {if $cart_debug_info.is_countdown_best}red{else}green{/if};">
            {if $cart_debug_info.is_countdown_best}⚠️ APPLICANDO SCONTO COUNTDOWN{else}✅ NON applicando sconto countdown{/if}
        </span>
    </div>
{/if}
