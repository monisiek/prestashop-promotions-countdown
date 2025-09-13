{if $promotions}
    <div id="promotions_countdown_container">
        <div class="slideshow-wrapper promotions-slideshow">
            {foreach from=$promotions item=promotion name=promo_loop}
                {assign var="start_time" value=$promotion.start_date|strtotime}
                {assign var="end_time" value=$promotion.end_date|strtotime}
                {assign var="now" value=$current_time}
                
                <div class="promotion-slide {if $smarty.foreach.promo_loop.first}active{/if} {if $now < $start_time}promotion-upcoming{elseif $now >= $start_time && $now < $end_time}promotion-active{else}promotion-expired{/if}">
                    
                    {if $promotion.banner_image}
                        <div class="slide-background" style="background-image: url('{$module_dir}views/img/{$promotion.banner_image}');">
                            <div class="slide-overlay"></div>
                        </div>
                    {else}
                        <div class="slide-background slide-placeholder">
                            <div class="slide-overlay"></div>
                        </div>
                    {/if}
                
                    <div class="slide-content">
                        <div class="promotion-info">
                            <h4 class="promotion-title">{$promotion.name}</h4>
                        </div>
                        
                        <div class="discount-badge">
                            <span class="discount-percent">{$promotion.discount_percent}%</span>
                            <span class="discount-text">SCONTO</span>
                        </div>
                        
                        {* Controlla lo stato della promozione *}
                        {if $now < $start_time}
                            {* Promozione futura - mostra countdown all'inizio *}
                            <div class="promotion-status upcoming">
                                <p class="status-label">Promozione inizia tra:</p>
                                <div class="countdown-timer" data-start-time="{$promotion.start_date}" data-mode="start">
                                    <div class="countdown-item">
                                        <span class="countdown-number days">00</span>
                                        <span class="countdown-label">Giorni</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number hours">00</span>
                                        <span class="countdown-label">Ore</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number minutes">00</span>
                                        <span class="countdown-label">Min</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number seconds">00</span>
                                        <span class="countdown-label">Sec</span>
                                    </div>
                                </div>
                                <div class="start-date-info">
                                    <small>Inizia il {$promotion.start_date|date_format:"%d/%m/%Y alle %H:%M"}</small>
                                </div>
                            </div>
                        {elseif $now >= $start_time && $now < $end_time}
                            {* Promozione attiva - mostra countdown alla fine *}
                            <div class="promotion-status active">
                                <p class="status-label">Promozione scade tra:</p>
                                <div class="countdown-timer" data-end-time="{$promotion.end_date}" data-mode="end">
                                    <div class="countdown-item">
                                        <span class="countdown-number days">00</span>
                                        <span class="countdown-label">Giorni</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number hours">00</span>
                                        <span class="countdown-label">Ore</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number minutes">00</span>
                                        <span class="countdown-label">Min</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number seconds">00</span>
                                        <span class="countdown-label">Sec</span>
                                    </div>
                                </div>
                            </div>
                        {else}
                            {* Promozione scaduta *}
                            <div class="promotion-status expired">
                                <p class="status-label expired-label">Promozione terminata</p>
                                <div class="expired-info">
                                    <small>Scaduta il {$promotion.end_date|date_format:"%d/%m/%Y alle %H:%M"}</small>
                                </div>
                            </div>
                        {/if}
                        
                        {* Pulsante CTA - solo per promozioni attive *}
                        {if $now >= $start_time && $now < $end_time && $promotion.id_category}
                            <a href="{$link->getCategoryLink($promotion.id_category)}" 
                               class="btn btn-primary promotion-cta active-cta">
                                Scopri l'Offerta
                            </a>
                        {elseif $now < $start_time}
                            <div class="promotion-cta upcoming-cta">
                                <span>Disponibile dal {$promotion.start_date|date_format:"%d/%m/%Y"}</span>
                            </div>
                        {else}
                            <div class="promotion-cta expired-cta">
                                <span>Offerta terminata</span>
                            </div>
                        {/if}
                        
                        {* Indicatore di durata promozione *}
                        <div class="promotion-duration">
                            <small class="duration-info">
                                {if $now < $start_time}
                                    Dal {$promotion.start_date|date_format:"%d/%m"} al {$promotion.end_date|date_format:"%d/%m/%Y"}
                                {elseif $now >= $start_time && $now < $end_time}
                                    Iniziata il {$promotion.start_date|date_format:"%d/%m"} - Scade il {$promotion.end_date|date_format:"%d/%m/%Y"}
                                {else}
                                    Durata: dal {$promotion.start_date|date_format:"%d/%m"} al {$promotion.end_date|date_format:"%d/%m/%Y"}
                                {/if}
                            </small>
                        </div>
                    </div>
                </div>
        {/foreach}
        </div>
        
        <!-- Controlli slideshow -->
        <div class="slideshow-controls">
            <button class="prev-slide" data-direction="-1">❮</button>
            <button class="next-slide" data-direction="1">❯</button>
        </div>
        
        <!-- Indicatori -->
        <div class="slideshow-indicators">
            {foreach from=$promotions item=promotion name=indicator_loop}
                <span class="indicator {if $smarty.foreach.indicator_loop.first}active{/if}" data-slide="{$smarty.foreach.indicator_loop.index}"></span>
            {/foreach}
        </div>
    </div>
{/if}