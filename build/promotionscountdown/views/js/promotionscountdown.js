$(document).ready(function() {
    // Frontend countdown functionality per promozioni con data di partenza e scadenza
    function updateCountdowns() {
        $('.countdown-timer').each(function() {
            var mode = $(this).data('mode');
            var targetTime;
            var labelPrefix;
            
            if (mode === 'start') {
                // Countdown per l'inizio della promozione
                targetTime = new Date($(this).data('start-time')).getTime();
                labelPrefix = 'Inizia tra: ';
            } else {
                // Countdown per la scadenza della promozione
                targetTime = new Date($(this).data('end-time')).getTime();
                labelPrefix = 'Scade tra: ';
            }
            
            var now = new Date().getTime();
            var timeLeft = targetTime - now;
            
            if (timeLeft > 0) {
                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                $(this).find('.days').text(days.toString().padStart(2, '0'));
                $(this).find('.hours').text(hours.toString().padStart(2, '0'));
                $(this).find('.minutes').text(minutes.toString().padStart(2, '0'));
                $(this).find('.seconds').text(seconds.toString().padStart(2, '0'));
                
                // Aggiunge effetti visivi in base al tempo rimanente
                var banner = $(this).closest('.promotion-banner');
                if (timeLeft < 3600000) { // Meno di 1 ora
                    banner.addClass('urgent');
                } else if (timeLeft < 86400000) { // Meno di 24 ore
                    banner.addClass('soon');
                }
                
            } else {
                // Timer scaduto
                $(this).find('.countdown-number').text('00');
                var banner = $(this).closest('.promotion-banner');
                
                if (mode === 'start') {
                    // La promozione è iniziata, ricarica la pagina per mostrare il countdown di scadenza
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // La promozione è scaduta
                    banner.addClass('expired');
                    setTimeout(function() {
                        banner.fadeOut(2000);
                    }, 5000);
                }
            }
        });
    }
    
    // Countdown aggiornato ogni secondo
    if ($('.countdown-timer').length > 0) {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
        
        // Animazione di ingresso con delay basato sullo stato
        $('.promotion-banner').each(function(index) {
            var delay = index * 200;
            if ($(this).hasClass('promotion-upcoming')) {
                delay += 100; // Ritardo maggiore per promozioni future
            }
            $(this).delay(delay).fadeIn(600);
        });
    }
    
    // Backend product selector functionality (stesso del precedente)
    if ($('#advanced-product-selector').length > 0) {
        function filterProducts() {
            var nameFilter = $("#product-name-filter").val().toLowerCase();
            var manufacturerFilter = $("#manufacturer-filter").val();
            var visibleCount = 0;
            
            $(".product-item").each(function() {
                var productName = $(this).data("product-name");
                var manufacturerId = $(this).data("manufacturer-id");
                var showProduct = true;
                
                if (nameFilter && productName.indexOf(nameFilter) === -1) {
                    showProduct = false;
                }
                
                if (manufacturerFilter && manufacturerId != manufacturerFilter) {
                    showProduct = false;
                }
                
                if (showProduct) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            if (visibleCount === 0) {
                $("#no-products-message").show();
                $("#products-container").hide();
            } else {
                $("#no-products-message").hide();
                $("#products-container").show();
            }
        }
        
        function updateSelectedProducts() {
            var selectedCount = $(".product-selector:checked").length;
            $("#selected-count").text(selectedCount);
            
            if (selectedCount > 0) {
                $("#selected-products-info").show();
                
                var selectedList = "";
                $(".product-selector:checked").each(function() {
                    var productCard = $(this).closest(".product-item");
                    var productName = productCard.find(".product-name").text();
                    var productBrand = productCard.find(".product-brand").text();
                    selectedList += "<span class=\"label label-info\" style=\"margin: 2px;\">" + productName + " (" + productBrand + ")</span>";
                });
                
                $("#selected-products-list").html(selectedList);
            } else {
                $("#selected-products-info").hide();
            }
        }
        
        // Eventi per i filtri
        $("#product-name-filter").on("keyup", filterProducts);
        $("#manufacturer-filter").on("change", filterProducts);
        
        // Gestione selezione prodotti
        $(".product-selector").on("change", function() {
            var productCard = $(this).closest(".product-card");
            if ($(this).is(":checked")) {
                productCard.addClass("selected");
                productCard.css("border-color", "#28a745");
                productCard.css("background-color", "#f8fff8");
            } else {
                productCard.removeClass("selected");
                productCard.css("border-color", "transparent");
                productCard.css("background-color", "transparent");
            }
            updateSelectedProducts();
        });
        
        $(".product-card").on("click", function(e) {
            if (e.target.type !== "checkbox") {
                var checkbox = $(this).find(".product-selector");
                checkbox.prop("checked", !checkbox.prop("checked")).trigger("change");
            }
        });
        
        $("#select-all-visible").on("click", function() {
            $(".product-item:visible .product-selector").prop("checked", true).trigger("change");
        });
        
        $("#deselect-all").on("click", function() {
            $(".product-selector").prop("checked", false).trigger("change");
        });
        
        updateSelectedProducts();
    }
    
    // Validazione date nel form admin
    $('input[name="START_DATE"], input[name="END_DATE"]').on('change', function() {
        var startDate = $('input[name="START_DATE"]').val();
        var endDate = $('input[name="END_DATE"]').val();
        
        if (startDate && endDate) {
            var start = new Date(startDate);
            var end = new Date(endDate);
            var now = new Date();
            
            // Rimuovi messaggi precedenti
            $('.date-validation-error').remove();
            
            if (start >= end) {
                $('input[name="END_DATE"]').after(
                    '<div class="alert alert-danger date-validation-error" style="margin-top: 5px;">La data di scadenza deve essere successiva alla data di inizio.</div>'
                );
            }
            
            if (end <= now) {
                $('input[name="END_DATE"]').after(
                    '<div class="alert alert-warning date-validation-error" style="margin-top: 5px;">La data di scadenza dovrebbe essere nel futuro.</div>'
                );
            }
            
            if (start < now) {
                $('input[name="START_DATE"]').after(
                    '<div class="alert alert-info date-validation-error" style="margin-top: 5px;">La promozione inizierà immediatamente se la data di inizio è nel passato.</div>'
                );
            }
        }
    });
});