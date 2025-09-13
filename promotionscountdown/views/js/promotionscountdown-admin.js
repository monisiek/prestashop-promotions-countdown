jQuery(document).ready(function($) {
    console.log('PromotionsCountdown Admin JS: Caricato');
    
    // Backend product selector functionality
    if ($('#advanced-product-selector').length > 0) {
        console.log('PromotionsCountdown Admin JS: Selettore prodotti trovato');
        function filterProducts() {
            console.log('PromotionsCountdown Admin JS: Filtro applicato');
            var nameFilter = $("#product-name-filter").val().toLowerCase();
            var manufacturerFilter = $("#manufacturer-filter").val();
            var visibleCount = 0;
            
            console.log('Filtri:', { name: nameFilter, manufacturer: manufacturerFilter });
            
            
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
            
            console.log('Prodotti visibili:', visibleCount);
            
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
        $("#product-name-filter").on("keyup", function() {
            // Filtra automaticamente mentre digiti (con debounce)
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(filterProducts, 300);
        });
        
        $("#manufacturer-filter").on("change", filterProducts);
        
        // Pulsanti per i filtri
        $("#apply-filters").on("click", function() {
            filterProducts();
        });
        
        $("#clear-filters").on("click", function() {
            $("#product-name-filter").val("");
            $("#manufacturer-filter").val("");
            filterProducts();
        });
        
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
        
        // Validazione prodotti rimossa - i prodotti sono ora opzionali
        // Non serve più validare i prodotti prima dell'invio del form
        
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
                    '<div class="alert alert-info date-validation-error" style="margin-top: 5px;">La promozione scadrà immediatamente se la data di scadenza è nel passato.</div>'
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
