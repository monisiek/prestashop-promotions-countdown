{if $promotions}
    <div id="promotions_countdown_container">
        {foreach from=$promotions item=promotion}
            <div class="promotion-banner" style="position: relative; margin-bottom: 20px;">
                {if $promotion.banner_image}
                    <img src="{$module_dir}views/img/{$promotion.banner_image}" alt="{$promotion.name}" class="img-responsive promotion-banner-img">
                {else}
                    <div class="promotion-banner-placeholder">
                        <h3>{$promotion.name}</h3>
                    </div>
                {/if}
                
                <div class="promotion-overlay">
                    <div class="promotion-info">
                        <h4 class="promotion-title">{$promotion.name}</h4>
                        <div class="discount-badge">
                            <span class="discount-percent">{$promotion.discount_percent}%</span>
                            <span class="discount-text">SCONTO</span>
                        </div>
                        <div class="countdown-timer" data-end-time="{$promotion.end_date}">
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
                        {if $promotion.id_category}
                            <a href="{$link->getCategoryLink($promotion.id_category)}" class="btn btn-primary promotion-cta">
                                Scopri l'Offerta
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
{/if}