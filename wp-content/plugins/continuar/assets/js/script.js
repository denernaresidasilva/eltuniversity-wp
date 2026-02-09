jQuery(document).ready(function($) {
    console.log('🚀 SUPERMEMBROS CAROUSEL V2 - AUTOPLAY EDITION');
    
    // Variables
    let autoplayTimer = null;
    let isAutoplayRunning = false;
    
    // Mobile detection
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Wait for elements to be ready
    setTimeout(function() {
        console.log('🔍 Looking for carousel...');
        initializeCarousel();
    }, 1000);
    
    function initializeCarousel() {
        const $carousel = $('.supermembros-carousel');
        
        if ($carousel.length === 0) {
            console.log('❌ No carousel found');
            return;
        }
        
        console.log('✅ Carousel found!');
        
        // Count cards
        const totalCards = $carousel.find('.carousel-item').length;
        console.log(`📊 Total cards: ${totalCards}`);
        
        if (totalCards <= 2) {
            console.log('⏸️ Autoplay disabled: need more than 2 cards');
            setupBasicNavigation($carousel);
            return;
        }
        
        // Check autoplay setting
        const autoplayEnabled = $carousel.attr('data-enable-autoplay') || 'yes';
        console.log(`⚙️ Autoplay setting: ${autoplayEnabled}`);
        
        if (autoplayEnabled === 'no') {
            console.log('⏸️ Autoplay disabled by user');
            setupBasicNavigation($carousel);
            return;
        }
        
        // Get speed
        const speed = parseInt($carousel.attr('data-autoplay-speed')) || 2000;
        console.log(`⏱️ Autoplay speed: ${speed}ms`);
        
        // Setup everything
        setupBasicNavigation($carousel);
        setupAutoplay($carousel, speed);
        setupProgressColors();
        
        console.log('🎉 Carousel initialization complete!');
    }
    
    function setupAutoplay($carousel, speed) {
        console.log('🔄 Setting up autoplay...');
        
        // Start autoplay after delay
        setTimeout(() => {
            startAutoplay($carousel, speed);
        }, speed);
        
        // Pause on hover (desktop)
        if (!isMobile()) {
            $carousel.hover(
                function() {
                    console.log('🖱️ Mouse enter - pause autoplay');
                    stopAutoplay();
                },
                function() {
                    console.log('🖱️ Mouse leave - resume autoplay');
                    setTimeout(() => startAutoplay($carousel, speed), 3000);
                }
            );
        }
        
        // Pause on click
        $carousel.on('click', '.course-link', function() {
            console.log('👆 Course clicked - stop autoplay');
            stopAutoplay();
        });
        
        // Mobile touch handling
        if (isMobile()) {
            let startX = 0;
            let isTouching = false;
            
            $carousel.on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                isTouching = true;
                console.log('📱 Touch start - pause autoplay');
                stopAutoplay();
            });
            
            $carousel.on('touchend', function() {
                if (isTouching) {
                    isTouching = false;
                    console.log('📱 Touch end - resume autoplay');
                    setTimeout(() => startAutoplay($carousel, speed), 3000);
                }
            });
        }
    }
    
    function startAutoplay($carousel, speed) {
        if (isAutoplayRunning) {
            console.log('⚠️ Autoplay already running');
            return;
        }
        
        const carousel = $carousel[0];
        if (!carousel) {
            console.log('❌ No carousel element');
            return;
        }
        
        console.log('▶️ Starting autoplay...');
        isAutoplayRunning = true;
        
        autoplayTimer = setInterval(() => {
            const maxScroll = carousel.scrollWidth - carousel.clientWidth;
            const currentScroll = carousel.scrollLeft;
            
            console.log(`📍 Scroll: ${currentScroll}/${maxScroll}`);
            
            if (currentScroll >= maxScroll - 10) {
                console.log('🔄 End reached - reset to start');
                carousel.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                const cardWidth = $carousel.find('.carousel-item').first().outerWidth(true) || 250;
                console.log(`➡️ Moving forward by ${cardWidth}px`);
                carousel.scrollBy({ left: cardWidth, behavior: 'smooth' });
            }
        }, speed);
        
        console.log(`✅ Autoplay started (${speed}ms interval)`);
    }
    
    function stopAutoplay() {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
        isAutoplayRunning = false;
        console.log('⏹️ Autoplay stopped');
    }
    
    function setupBasicNavigation($carousel) {
        console.log('🧭 Setting up basic navigation...');
        
        // Desktop mouse wheel
        if (!isMobile()) {
            $carousel.on('wheel', function(e) {
                e.preventDefault();
                this.scrollLeft += (e.originalEvent.deltaY);
                stopAutoplay();
            });
        }
        
        // Mobile swipe
        if (isMobile()) {
            let startX = 0;
            let isDragging = false;
            
            $carousel.on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                isDragging = true;
            });
            
            $carousel.on('touchmove', function(e) {
                if (!isDragging) return;
                
                const currentX = e.originalEvent.touches[0].clientX;
                const diffX = startX - currentX;
                
                if (Math.abs(diffX) > 10) {
                    e.preventDefault();
                    this.scrollLeft += diffX * 0.8;
                    startX = currentX;
                }
            });
            
            $carousel.on('touchend', function() {
                isDragging = false;
            });
        }
        
        console.log('✅ Basic navigation setup complete');
    }
    
    function setupProgressColors() {
        console.log('🎨 Setting up progress colors...');
        
        $('.carousel-item').each(function() {
            const progress = $(this).data('progress') || 0;
            const $progressBar = $(this).find('.progress-bar');
            
            if ($progressBar.length === 0) return;
            
            let color = '#4caf50'; // Green
            if (progress < 25) color = '#f44336';      // Red
            else if (progress < 50) color = '#ff9800'; // Orange  
            else if (progress < 75) color = '#2196f3'; // Blue
            
            $progressBar.css('background-color', color);
            
            console.log(`🎯 Card progress: ${progress}% = ${color}`);
        });
    }
    
    // Show swipe hint on mobile
    if (isMobile()) {
        setTimeout(() => {
            const $carousel = $('.supermembros-carousel');
            const showHint = $carousel.attr('data-show-swipe-hint') === 'yes';
            
            if (showHint && $carousel.length > 0) {
                const $wrapper = $carousel.closest('.supermembros-carousel-wrapper');
                
                if (!$wrapper.data('hint-shown')) {
                    $wrapper.data('hint-shown', true);
                    
                    const $hint = $('<div style="position:absolute;bottom:-25px;left:50%;transform:translateX(-50%);font-size:12px;color:#666;z-index:1;">👈 Deslize para navegar 👉</div>');
                    $wrapper.append($hint);
                    
                    setTimeout(() => $hint.fadeOut(), 3000);
                    console.log('💡 Swipe hint shown');
                }
            }
        }, 2000);
    }
    
    // Page visibility handling
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoplay();
            console.log('👁️ Page hidden - autoplay stopped');
        } else {
            console.log('👁️ Page visible');
            // Restart will be handled by existing timers
        }
    });
    
    // Window resize
    $(window).on('resize', function() {
        stopAutoplay();
        console.log('📐 Window resized - autoplay stopped');
        
        setTimeout(() => {
            console.log('🔄 Reinitializing after resize...');
            initializeCarousel();
        }, 1000);
    });
    
    console.log('🏁 Script setup complete - waiting for initialization...');
});