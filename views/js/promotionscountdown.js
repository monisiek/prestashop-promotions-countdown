$(document).ready(function() {
    // Frontend countdown functionality
    function updateCountdowns() {
        $('.countdown-timer').each(function() {
            var endTime = new Date($(this).data('end-time')).getTime();
            var now = new Date().getTime();
            var timeLeft = endTime - now;
            
            if (timeLeft > 0) {
                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                $(this).find('.days').text(days.toString().padStart(2, '0'));
                $(this).find('.hours').text(hours.toString().padStart(2, '0'));
                $(this).find('.minutes').text(minutes.toString().padStart(2, '0'));
                $(this).find('.seconds').text(seconds.toString().padStart(2, '0'));
            } else {
                $(this).find('.countdown-number').text('00');
                $(this).closest('.promotion-banner').fadeOut(2000);
            }
        });
    }
    
    if ($('.countdown-timer').length > 0) {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
        
        $('.promotion-banner').each(function(index) {
            $(this).delay(index * 200).fadeIn(600);
        });
    }
    
    // Backend product selector functionality
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
        
        $("#product-name-filter").on("keyup", filterProducts);
        $("#manufacturer-filter").on("change", filterProducts);
        
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
});