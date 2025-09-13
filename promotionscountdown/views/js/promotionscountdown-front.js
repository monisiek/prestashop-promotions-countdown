// Slideshow globale per frontend
var PromotionsSlideshow = {
    currentSlideIndex: 0,
    totalSlides: 0,
    slideInterval: null,
    
    init: function() {
        this.slides = $('.promotion-slide');
        this.totalSlides = this.slides.length;
        
        if (this.totalSlides > 0) {
            this.showSlide(0);
            this.startSlideshow();
            
            // Pausa al hover
            $('.promotions-slideshow').hover(
                function() { PromotionsSlideshow.stopSlideshow(); },
                function() { PromotionsSlideshow.startSlideshow(); }
            );
        }
    },
    
    showSlide: function(index) {
        this.slides.removeClass('active');
        $('.indicator').removeClass('active');
        
        if (index >= this.totalSlides) {
            this.currentSlideIndex = 0;
        } else if (index < 0) {
            this.currentSlideIndex = this.totalSlides - 1;
        } else {
            this.currentSlideIndex = index;
        }
        
        this.slides.eq(this.currentSlideIndex).addClass('active');
        $('.indicator').eq(this.currentSlideIndex).addClass('active');
    },
    
    nextSlide: function() {
        this.showSlide(this.currentSlideIndex + 1);
    },
    
    prevSlide: function() {
        this.showSlide(this.currentSlideIndex - 1);
    },
    
    goToSlide: function(index) {
        this.showSlide(index);
    },
    
    startSlideshow: function() {
        if (this.totalSlides > 1) {
            this.slideInterval = setInterval(function() {
                PromotionsSlideshow.nextSlide();
            }, 5000);
        }
    },
    
    stopSlideshow: function() {
        clearInterval(this.slideInterval);
    }
};

// Funzioni globali per onclick
function slideshowNext() {
    PromotionsSlideshow.nextSlide();
}

function slideshowPrev() {
    PromotionsSlideshow.prevSlide();
}

function testSlideshow() {
    PromotionsSlideshow.nextSlide();
}

jQuery(document).ready(function($) {
    // Inizializza slideshow
    PromotionsSlideshow.init();
    
    // Eventi per i controlli
    $(document).on('click', '.prev-slide', function(e) {
        e.preventDefault();
        PromotionsSlideshow.stopSlideshow();
        PromotionsSlideshow.prevSlide();
        PromotionsSlideshow.startSlideshow();
    });

    $(document).on('click', '.next-slide', function(e) {
        e.preventDefault();
        PromotionsSlideshow.stopSlideshow();
        PromotionsSlideshow.nextSlide();
        PromotionsSlideshow.startSlideshow();
    });

    $(document).on('click', '.indicator', function(e) {
        e.preventDefault();
        var slideIndex = parseInt($(this).data('slide'));
        PromotionsSlideshow.stopSlideshow();
        PromotionsSlideshow.goToSlide(slideIndex);
        PromotionsSlideshow.startSlideshow();
    });

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
            $(this).delay(delay).addClass('fade-in');
        });
    }
});
