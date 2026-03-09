/**
 * AI Sales Engine – Front-end Tracker
 *
 * Tracks page views, scroll depth, button clicks, and offer link clicks,
 * and sends events to the REST API endpoint /wp-json/ai-sales/v1/event.
 */

( function () {
    'use strict';

    /** Configuration injected via wp_localize_script */
    const config = window.AISalesTracker || {};
    const endpoint = config.endpoint || '/wp-json/ai-sales/v1/event';
    const nonce    = config.nonce    || '';

    /** Cached lead email – populated from localStorage if previously set */
    let leadEmail = localStorage.getItem( 'aise_lead_email' ) || '';

    /**
     * Send an event to the REST API.
     *
     * @param {string} eventName  Event identifier.
     * @param {string} eventValue Optional scalar value.
     * @param {Object} metadata   Additional data.
     */
    function sendEvent( eventName, eventValue, metadata ) {
        const body = {
            event_name:  eventName,
            event_value: String( eventValue || '' ),
            email:       leadEmail,
            metadata:    Object.assign( {
                url:       window.location.href,
                referrer:  document.referrer,
                title:     document.title,
            }, metadata || {} ),
        };

        // Prefer sendBeacon when available (non-blocking).
        if ( navigator.sendBeacon ) {
            const blob = new Blob( [ JSON.stringify( body ) ], { type: 'application/json' } );
            navigator.sendBeacon( endpoint, blob );
            return;
        }

        // Fallback: fetch
        fetch( endpoint, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce':   nonce,
            },
            body: JSON.stringify( body ),
            keepalive: true,
        } ).catch( () => {} );
    }

    /* ------------------------------------------------------------------
       Page view
    ------------------------------------------------------------------ */
    sendEvent( 'page_visit', window.location.pathname );

    /* ------------------------------------------------------------------
       Scroll depth (25 / 50 / 75 / 100 %)
    ------------------------------------------------------------------ */
    const depthsFired = new Set();

    function checkScrollDepth() {
        const el       = document.documentElement;
        const scrolled = el.scrollTop  + window.innerHeight;
        const total    = el.scrollHeight;
        const pct      = Math.round( ( scrolled / total ) * 100 );

        [ 25, 50, 75, 100 ].forEach( threshold => {
            if ( pct >= threshold && ! depthsFired.has( threshold ) ) {
                depthsFired.add( threshold );
                sendEvent( 'scroll_depth', threshold + '%', { depth: threshold } );
            }
        } );
    }

    let scrollTimer;
    window.addEventListener( 'scroll', () => {
        clearTimeout( scrollTimer );
        scrollTimer = setTimeout( checkScrollDepth, 250 );
    }, { passive: true } );

    /* ------------------------------------------------------------------
       Button clicks
    ------------------------------------------------------------------ */
    document.addEventListener( 'click', function ( e ) {
        const btn = e.target.closest( 'button, [role="button"], .btn, .button' );
        if ( ! btn ) return;

        const label = btn.textContent.trim().substring( 0, 60 )
            || btn.getAttribute( 'aria-label' )
            || btn.getAttribute( 'id' )
            || 'button';

        sendEvent( 'button_click', label, {
            element: btn.tagName.toLowerCase(),
            id:      btn.id || '',
            classes: btn.className || '',
        } );
    } );

    /* ------------------------------------------------------------------
       Offer / CTA link clicks
    ------------------------------------------------------------------ */
    const OFFER_PATTERNS = [
        /checkout/i,
        /comprar/i,
        /buy/i,
        /oferta/i,
        /offer/i,
        /inscri[cç]/i,
        /subscribe/i,
        /enroll/i,
    ];

    document.addEventListener( 'click', function ( e ) {
        const link = e.target.closest( 'a[href]' );
        if ( ! link ) return;

        const href  = link.getAttribute( 'href' ) || '';
        const label = link.textContent.trim().substring( 0, 60 ) || href;

        const isOffer = OFFER_PATTERNS.some(
            p => p.test( href ) || p.test( label )
        );

        if ( isOffer ) {
            sendEvent( 'offer_click', label, {
                href:    href,
                element: 'a',
                id:      link.id || '',
                classes: link.className || '',
            } );
        }
    } );

    /* ------------------------------------------------------------------
       Identity – set email from any visible input[type=email] on submit
    ------------------------------------------------------------------ */
    document.addEventListener( 'submit', function ( e ) {
        const form  = e.target;
        const input = form.querySelector( 'input[type="email"]' );
        if ( input && input.value ) {
            leadEmail = input.value.trim();
            localStorage.setItem( 'aise_lead_email', leadEmail );
        }
    } );

} )();
