/**
 * AI Sales Engine – Admin JavaScript
 *
 * Handles dashboard stats, lead table, and flow-builder placeholder.
 */

/* global AISalesAdmin, jQuery */

( function ( $ ) {
    'use strict';

    const api  = ( window.AISalesAdmin && AISalesAdmin.rest_url ) || '/wp-json/ai-sales/v1/';
    const nonce = ( window.AISalesAdmin && AISalesAdmin.nonce )   || '';

    function apiFetch( path, options = {} ) {
        return fetch( api + path, Object.assign( {
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            },
        }, options ) ).then( r => r.json() );
    }

    /* ------------------------------------------------------------------
       Dashboard
    ------------------------------------------------------------------ */
    function initDashboard() {
        const statsGrid = document.getElementById( 'aise-stats-grid' );
        if ( ! statsGrid ) return;

        apiFetch( 'analytics/summary' ).then( data => {
            const map = {
                'stat-total-leads':  data.total_leads,
                'stat-new-today':    data.new_leads_today,
                'stat-total-events': data.total_events,
                'stat-avg-score':    data.avg_score,
                'stat-automations':  data.active_automations,
                'stat-pending-jobs': data.pending_jobs,
            };

            Object.entries( map ).forEach( ( [ id, value ] ) => {
                const el = document.getElementById( id );
                if ( el ) el.textContent = value;
            } );

            document.querySelectorAll( '.aise-stat-card' ).forEach( c => c.classList.remove( 'aise-loading' ) );

            // Events chart (requires Chart.js loaded via CDN or WP scripts)
            if ( typeof Chart !== 'undefined' && data.events_last_7_days ) {
                const ctx = document.getElementById( 'aise-events-chart' );
                if ( ctx ) {
                    new Chart( ctx, {
                        type: 'line',
                        data: {
                            labels:   data.events_last_7_days.map( r => r.day ),
                            datasets: [ {
                                label:           'Events',
                                data:            data.events_last_7_days.map( r => r.count ),
                                borderColor:     '#6366f1',
                                backgroundColor: 'rgba(99,102,241,.1)',
                                fill:            true,
                                tension:         0.4,
                            } ],
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } },
                    } );
                }
            }

            // Sources table
            const tbody = document.querySelector( '#aise-sources-table tbody' );
            if ( tbody && data.top_sources ) {
                tbody.innerHTML = data.top_sources.map(
                    r => `<tr><td>${ escHtml( r.source ) }</td><td>${ r.count }</td></tr>`
                ).join( '' ) || '<tr><td colspan="2">No data.</td></tr>';
            }
        } ).catch( () => {} );
    }

    /* ------------------------------------------------------------------
       Leads page
    ------------------------------------------------------------------ */
    function initLeads() {
        const tbody = document.getElementById( 'aise-leads-tbody' );
        if ( ! tbody ) return;

        let page   = 1;
        let search = '';
        let status = '';

        function loadLeads() {
            const params = new URLSearchParams( { per_page: 20, page, search, status } );
            apiFetch( `leads?${ params }` ).then( res => {
                if ( ! res.data ) return;

                tbody.innerHTML = res.data.map( lead => `
                    <tr>
                        <td>${ escHtml( lead.name ) }</td>
                        <td>${ escHtml( lead.email ) }</td>
                        <td>${ escHtml( lead.phone || '–' ) }</td>
                        <td>${ lead.lead_score }</td>
                        <td><span class="aise-badge aise-badge-${ escHtml( lead.status ) }">${ escHtml( lead.status ) }</span></td>
                        <td>${ escHtml( lead.created_at.substring( 0, 10 ) ) }</td>
                        <td>
                            <a href="#" class="aise-delete-lead" data-id="${ lead.id }">
                                ${ aiseTrans( 'Delete' ) }
                            </a>
                        </td>
                    </tr>
                ` ).join( '' ) || '<tr><td colspan="7">No leads found.</td></tr>';

                renderPagination( res.pages, page );
            } ).catch( () => {} );
        }

        function renderPagination( pages, current ) {
            const el = document.getElementById( 'aise-leads-pagination' );
            if ( ! el ) return;
            el.innerHTML = '';
            for ( let i = 1; i <= pages; i++ ) {
                const btn = document.createElement( 'button' );
                btn.textContent = i;
                if ( i === current ) btn.classList.add( 'active' );
                btn.addEventListener( 'click', () => { page = i; loadLeads(); } );
                el.appendChild( btn );
            }
        }

        // Delegated delete
        tbody.addEventListener( 'click', e => {
            const btn = e.target.closest( '.aise-delete-lead' );
            if ( ! btn ) return;
            e.preventDefault();
            if ( ! confirm( 'Delete this lead?' ) ) return;
            const id = btn.dataset.id;
            apiFetch( `leads/${ id }`, { method: 'DELETE' } )
                .then( () => loadLeads() )
                .catch( () => {} );
        } );

        // Search
        const searchInput = document.getElementById( 'aise-lead-search' );
        if ( searchInput ) {
            let timer;
            searchInput.addEventListener( 'input', () => {
                clearTimeout( timer );
                timer = setTimeout( () => { search = searchInput.value; page = 1; loadLeads(); }, 400 );
            } );
        }

        // Status filter
        const statusSelect = document.getElementById( 'aise-lead-status-filter' );
        if ( statusSelect ) {
            statusSelect.addEventListener( 'change', () => { status = statusSelect.value; page = 1; loadLeads(); } );
        }

        loadLeads();
    }

    /* ------------------------------------------------------------------
       Lists page
    ------------------------------------------------------------------ */
    function initLists() {
        const tbody = document.getElementById( 'aise-lists-tbody' );
        if ( ! tbody ) return;

        apiFetch( 'lists' ).then( lists => {
            tbody.innerHTML = lists.map( l => `
                <tr>
                    <td>${ escHtml( l.name ) }</td>
                    <td>${ escHtml( l.description || '–' ) }</td>
                    <td>${ l.webhook_url ? `<a href="${ escHtml( l.webhook_url ) }" target="_blank">link</a>` : '–' }</td>
                    <td>${ escHtml( l.created_at.substring( 0, 10 ) ) }</td>
                    <td>–</td>
                </tr>
            ` ).join( '' ) || '<tr><td colspan="5">No lists found.</td></tr>';
        } ).catch( () => {} );
    }

    /* ------------------------------------------------------------------
       Automations page
    ------------------------------------------------------------------ */
    function initAutomations() {
        const tbody = document.getElementById( 'aise-automations-tbody' );
        if ( ! tbody ) return;

        apiFetch( 'automations' ).then( rows => {
            tbody.innerHTML = rows.map( a => `
                <tr>
                    <td>${ escHtml( a.name ) }</td>
                    <td><code>${ escHtml( a.trigger_type ) }</code></td>
                    <td><span class="aise-badge aise-badge-${ escHtml( a.status ) }">${ escHtml( a.status ) }</span></td>
                    <td>${ escHtml( a.created_at.substring( 0, 10 ) ) }</td>
                    <td>–</td>
                </tr>
            ` ).join( '' ) || '<tr><td colspan="5">No automations found.</td></tr>';
        } ).catch( () => {} );
    }

    /* ------------------------------------------------------------------
       Agents page
    ------------------------------------------------------------------ */
    function initAgents() {
        const grid = document.getElementById( 'aise-agents-grid' );
        if ( ! grid ) return;

        function loadAgents() {
            apiFetch( 'agents' ).then( agents => {
                grid.innerHTML = agents.map( a => `
                    <div class="aise-agent-card">
                        <h3>${ escHtml( a.name ) }</h3>
                        <p class="aise-role">${ escHtml( a.role || 'No role set' ) }</p>
                        <p>${ escHtml( ( a.goal || '' ).substring( 0, 80 ) ) }${ ( a.goal || '' ).length > 80 ? '…' : '' }</p>
                    </div>
                ` ).join( '' ) || '<p>No agents yet.</p>';
            } ).catch( () => {} );
        }

        const addBtn    = document.getElementById( 'aise-add-agent-btn' );
        const formCard  = document.getElementById( 'aise-agent-form-card' );
        const cancelBtn = document.getElementById( 'aise-agent-cancel-btn' );
        const form      = document.getElementById( 'aise-agent-form' );

        if ( addBtn && formCard ) {
            addBtn.addEventListener( 'click', () => { formCard.style.display = ''; } );
        }

        if ( cancelBtn && formCard ) {
            cancelBtn.addEventListener( 'click', () => { formCard.style.display = 'none'; } );
        }

        if ( form ) {
            form.addEventListener( 'submit', e => {
                e.preventDefault();
                const fd  = new FormData( form );
                const body = {
                    name:             fd.get( 'name' ),
                    role:             fd.get( 'role' ),
                    goal:             fd.get( 'goal' ),
                    personality:      fd.get( 'personality' ),
                    training_prompt:  fd.get( 'training_prompt' ),
                    voice_enabled:    fd.get( 'voice_enabled' ) ? 1 : 0,
                    image_enabled:    fd.get( 'image_enabled' ) ? 1 : 0,
                };
                apiFetch( 'agents', { method: 'POST', body: JSON.stringify( body ) } )
                    .then( () => { form.reset(); formCard.style.display = 'none'; loadAgents(); } )
                    .catch( () => {} );
            } );
        }

        loadAgents();
    }

    /* ------------------------------------------------------------------
       Flow builder placeholder (React hook)
    ------------------------------------------------------------------ */
    function initFlowBuilder() {
        const root = document.getElementById( 'aise-flow-builder-root' );
        if ( ! root ) return;
        // Reserved for React/block-editor based flow builder.
        // wp.element.render( <FlowBuilder />, root );
    }

    /* ------------------------------------------------------------------
       Helpers
    ------------------------------------------------------------------ */
    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' );
    }

    function aiseTrans( str ) {
        return str; // Placeholder for i18n.
    }

    /* ------------------------------------------------------------------
       Boot
    ------------------------------------------------------------------ */
    document.addEventListener( 'DOMContentLoaded', () => {
        initDashboard();
        initLeads();
        initLists();
        initAutomations();
        initAgents();
        initFlowBuilder();
    } );

} )( window.jQuery || { fn: {} } );
