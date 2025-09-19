{* DEBUG INFO *}
{if isset($debug_info)}
    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">
        <strong>DEBUG PromotionsCountdown:</strong><br>
        Prodotto ID: {$debug_info.product_id}<br>
        Promozioni countdown attive: {$debug_info.active_promotions_count}<br>
        SpecificPrice trovate: {$debug_info.specific_prices_count}<br>
        CartRule trovate: {$debug_info.cart_rules_count}<br>
        Migliore: {if $debug_info.best_discount}{$debug_info.best_discount.type} ({$debug_info.best_discount.discount_percent}%) - Prezzo finale: {$debug_info.best_discount.final_price|string_format:"%.2f"}€{else}Nessuna{/if}<br>
        Countdown è migliore: {if $debug_info.is_countdown_best}SÌ{else}NO{/if}<br><br>
        
        {if $debug_info.specific_prices_count > 0}
            <strong>SpecificPrice:</strong><br>
            {foreach from=$debug_info.specific_prices item=sp}
                - ID: {$sp.id_specific_price}, Tipo: {$sp.reduction_type}, Riduzione: {$sp.reduction}, Cart: {$sp.id_cart}<br>
            {/foreach}
        {/if}
        
        {if $debug_info.cart_rules_count > 0}
            <strong>CartRule:</strong><br>
            {foreach from=$debug_info.cart_rules item=cr}
                - ID: {$cr.id}, Nome: {$cr.name}<br>
                &nbsp;&nbsp;Sconto: {if $cr.reduction_percent > 0}{$cr.reduction_percent}%{else}{$cr.reduction_amount}€{/if}<br>
                &nbsp;&nbsp;Si applica: {if $cr.applies_to_product}SÌ{else}NO{/if}<br>
                &nbsp;&nbsp;Restrizioni: Prodotti={if $cr.product_restriction}SÌ{else}NO{/if}, Marche={if $cr.manufacturer_restriction}SÌ{else}NO{/if}, Categorie={if $cr.category_restriction}SÌ{else}NO{/if}<br><br>
            {/foreach}
        {/if}
        
        {if isset($debug_info.product_debug)}
            <strong>Dati Prodotto:</strong><br>
            Nome: {$debug_info.product_debug.name}<br>
            Prezzo: {$debug_info.product_debug.price}€<br>
            Prezzo wholesale: {$debug_info.product_debug.wholesale_price}€<br>
            In vendita: {if $debug_info.product_debug.on_sale}SÌ{else}NO{/if}<br>
            Riduzione prezzo: {$debug_info.product_debug.reduction_price}€<br>
            Riduzione percentuale: {$debug_info.product_debug.reduction_percent}%<br>
            Riduzione da: {$debug_info.product_debug.reduction_from}<br>
            Riduzione a: {$debug_info.product_debug.reduction_to}<br>
            Tipo riduzione: {$debug_info.product_debug.reduction_type}<br>
            Prezzo senza riduzione: {$debug_info.product_debug.price_without_reduction}€<br>
            Marca ID: {$debug_info.product_debug.id_manufacturer}<br>
            Categoria ID: {$debug_info.product_debug.id_category_default}<br>
        {/if}
        
        {if isset($debug_info.product_json)}
            <strong>JSON Completo Prodotto:</strong><br>
            <pre style="background: #f8f8f8; padding: 10px; margin: 10px 0; border: 1px solid #ddd; font-size: 10px; max-height: 400px; overflow-y: auto;">{$debug_info.product_json}</pre>
        {/if}
    </div>
{/if}

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
