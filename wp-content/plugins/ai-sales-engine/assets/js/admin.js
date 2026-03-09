/**
 * IA Vendas – Admin JavaScript
 *
 * Gerencia painel, leads, listas, automações, agentes e pipelines.
 */

/* global AISalesAdmin, jQuery, Chart */

( function () {
    'use strict';

    const api   = ( window.AISalesAdmin && AISalesAdmin.rest_url ) || '/wp-json/ai-sales/v1/';
    const nonce = ( window.AISalesAdmin && AISalesAdmin.nonce )    || '';
    const t     = ( window.AISalesAdmin && AISalesAdmin.i18n )     || {};

    /* ------------------------------------------------------------------
       Utilidades
    ------------------------------------------------------------------ */

    function tr( key, fallback ) {
        return t[ key ] || fallback || key;
    }

    function apiFetch( path, options ) {
        options = options || {};
        return fetch( api + path, Object.assign( {
            headers: Object.assign( {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            }, options.headers || {} ),
        }, options ) ).then( function ( r ) { return r.json(); } );
    }

    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#39;' );
    }

    function fmtDate( str ) {
        if ( ! str ) return '–';
        return str.substring( 0, 10 ).split( '-' ).reverse().join( '/' );
    }

    function statusLabel( status ) {
        var map = {
            active:   tr( 'statusAtivo',    'Ativo' ),
            inactive: tr( 'statusInativo',  'Inativo' ),
            pending:  tr( 'statusPendente', 'Pendente' ),
            failed:   tr( 'statusFalhou',   'Falhou' ),
        };
        return map[ status ] || status;
    }

    function toast( msg, type ) {
        type = type || 'success';
        var el = document.createElement( 'div' );
        el.className = 'aise-toast aise-toast-' + type;
        el.textContent = msg;
        document.body.appendChild( el );
        setTimeout( function () { el.classList.add( 'aise-toast-visible' ); }, 10 );
        setTimeout( function () {
            el.classList.remove( 'aise-toast-visible' );
            setTimeout( function () { el.remove(); }, 300 );
        }, 3500 );
    }

    /* ------------------------------------------------------------------
       Modal helpers
    ------------------------------------------------------------------ */

    function openModal( id ) {
        var modal = document.getElementById( id );
        if ( modal ) {
            modal.style.display = 'flex';
            document.body.classList.add( 'aise-modal-open' );
        }
    }

    function closeModal( id ) {
        var modal = id ? document.getElementById( id ) : null;
        if ( modal ) {
            modal.style.display = 'none';
        } else {
            document.querySelectorAll( '.aise-modal' ).forEach( function ( m ) {
                m.style.display = 'none';
            } );
        }
        document.body.classList.remove( 'aise-modal-open' );
    }

    // Close modals on backdrop click or × button
    document.addEventListener( 'click', function ( e ) {
        if ( e.target.classList.contains( 'aise-modal-backdrop' ) ||
             e.target.classList.contains( 'aise-modal-close' ) ||
             e.target.closest( '.aise-modal-close' ) ) {
            closeModal();
        }
    } );

    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' ) closeModal();
    } );

    /* ------------------------------------------------------------------
       Painel
    ------------------------------------------------------------------ */

    function initDashboard() {
        var statsGrid = document.getElementById( 'aise-stats-grid' );
        if ( ! statsGrid ) return;

        apiFetch( 'analytics/summary' ).then( function ( data ) {
            var map = {
                'stat-total-leads':  data.total_leads,
                'stat-new-today':    data.new_leads_today,
                'stat-total-events': data.total_events,
                'stat-avg-score':    data.avg_score,
                'stat-automations':  data.active_automations,
                'stat-pending-jobs': data.pending_jobs,
            };

            Object.keys( map ).forEach( function ( id ) {
                var el = document.getElementById( id );
                if ( el ) el.textContent = map[ id ] !== undefined ? map[ id ] : '0';
            } );

            document.querySelectorAll( '.aise-stat-card' ).forEach( function ( c ) {
                c.classList.remove( 'aise-loading' );
            } );

            if ( typeof Chart !== 'undefined' && data.events_last_7_days ) {
                var ctx = document.getElementById( 'aise-events-chart' );
                if ( ctx ) {
                    new Chart( ctx, {
                        type: 'line',
                        data: {
                            labels:   data.events_last_7_days.map( function ( r ) { return fmtDate( r.day ); } ),
                            datasets: [ {
                                label:           tr( 'eventos', 'Eventos' ),
                                data:            data.events_last_7_days.map( function ( r ) { return r.count; } ),
                                borderColor:     '#6366f1',
                                backgroundColor: 'rgba(99,102,241,.12)',
                                fill:            true,
                                tension:         0.4,
                                pointRadius:     4,
                                pointHoverRadius: 6,
                            } ],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        },
                    } );
                }
            }

            var tbody = document.querySelector( '#aise-sources-table tbody' );
            if ( tbody && data.top_sources ) {
                tbody.innerHTML = data.top_sources.map( function ( r ) {
                    return '<tr><td>' + escHtml( r.source || '–' ) + '</td><td>' + escHtml( r.count ) + '</td></tr>';
                } ).join( '' ) || '<tr><td colspan="2">' + tr( 'semDados', 'Sem dados.' ) + '</td></tr>';
            }
        } ).catch( function () {} );
    }

    /* ------------------------------------------------------------------
       Leads
    ------------------------------------------------------------------ */

    function initLeads() {
        var tbody = document.getElementById( 'aise-leads-tbody' );
        if ( ! tbody ) return;

        var page   = 1;
        var search = '';
        var status = '';

        function loadLeads() {
            tbody.innerHTML = '<tr><td colspan="7" class="aise-loading-row">' + tr( 'carregando', 'Carregando…' ) + '</td></tr>';
            var params = new URLSearchParams( { per_page: 20, page: page, search: search, status: status } );
            apiFetch( 'leads?' + params ).then( function ( res ) {
                if ( ! res.data ) {
                    tbody.innerHTML = '<tr><td colspan="7" class="aise-empty-row">' + tr( 'nenhumLead', 'Nenhum lead encontrado.' ) + '</td></tr>';
                    return;
                }
                tbody.innerHTML = res.data.map( function ( lead ) {
                    return '<tr>' +
                        '<td data-label="Nome"><strong>' + escHtml( lead.name || '–' ) + '</strong></td>' +
                        '<td data-label="Email">' + escHtml( lead.email || '–' ) + '</td>' +
                        '<td data-label="Telefone">' + escHtml( lead.phone || '–' ) + '</td>' +
                        '<td data-label="Score"><span class="aise-score-pill">' + escHtml( lead.lead_score ) + '</span></td>' +
                        '<td data-label="Status"><span class="aise-badge aise-badge-' + escHtml( lead.status ) + '">' + escHtml( statusLabel( lead.status ) ) + '</span></td>' +
                        '<td data-label="Criado em">' + fmtDate( lead.created_at ) + '</td>' +
                        '<td data-label="Ações" class="aise-actions-cell">' +
                            '<button class="aise-btn-icon aise-edit-lead" data-id="' + lead.id + '" title="Editar">✏️</button>' +
                            '<button class="aise-btn-icon aise-delete-lead" data-id="' + lead.id + '" title="Excluir">🗑️</button>' +
                        '</td>' +
                    '</tr>';
                } ).join( '' );

                if ( ! res.data.length ) {
                    tbody.innerHTML = '<tr><td colspan="7" class="aise-empty-row">' + tr( 'nenhumLead', 'Nenhum lead encontrado.' ) + '</td></tr>';
                }

                renderPagination( res.pages || 1, page );
            } ).catch( function () {} );
        }

        function renderPagination( pages, current ) {
            var el = document.getElementById( 'aise-leads-pagination' );
            if ( ! el ) return;
            el.innerHTML = '';
            for ( var i = 1; i <= pages; i++ ) {
                var btn = document.createElement( 'button' );
                btn.textContent = i;
                btn.className = 'aise-page-btn' + ( i === current ? ' active' : '' );
                ( function ( pageNum ) {
                    btn.addEventListener( 'click', function () { page = pageNum; loadLeads(); } );
                } )( i );
                el.appendChild( btn );
            }
        }

        // Delegated actions
        tbody.addEventListener( 'click', function ( e ) {
            var delBtn  = e.target.closest( '.aise-delete-lead' );
            var editBtn = e.target.closest( '.aise-edit-lead' );

            if ( delBtn ) {
                e.preventDefault();
                if ( ! confirm( tr( 'confirmarExcluir', 'Excluir este lead?' ) ) ) return;
                var id = delBtn.dataset.id;
                apiFetch( 'leads/' + id, { method: 'DELETE' } )
                    .then( function () { toast( 'Lead excluído!' ); loadLeads(); } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao excluir.' ), 'error' ); } );
            }

            if ( editBtn ) {
                var lid = editBtn.dataset.id;
                apiFetch( 'leads/' + lid ).then( function ( lead ) {
                    var form = document.getElementById( 'aise-lead-form' );
                    if ( ! form ) return;
                    form.querySelector( '#lead-id' ).value      = lead.id;
                    form.querySelector( '#lead-name' ).value    = lead.name || '';
                    form.querySelector( '#lead-email' ).value   = lead.email || '';
                    form.querySelector( '#lead-phone' ).value   = lead.phone || '';
                    form.querySelector( '#lead-whatsapp' ).value = lead.whatsapp || '';
                    form.querySelector( '#lead-source' ).value  = lead.source || '';
                    form.querySelector( '#lead-status' ).value  = lead.status || 'active';
                    document.getElementById( 'aise-lead-modal-title' ).textContent = 'Editar Lead';
                    openModal( 'aise-lead-modal' );
                } );
            }
        } );

        // Search
        var searchInput = document.getElementById( 'aise-lead-search' );
        if ( searchInput ) {
            var timer;
            searchInput.addEventListener( 'input', function () {
                clearTimeout( timer );
                timer = setTimeout( function () { search = searchInput.value; page = 1; loadLeads(); }, 400 );
            } );
        }

        // Status filter
        var statusSelect = document.getElementById( 'aise-lead-status-filter' );
        if ( statusSelect ) {
            statusSelect.addEventListener( 'change', function () { status = statusSelect.value; page = 1; loadLeads(); } );
        }

        // Open modal for new lead
        var addBtn = document.getElementById( 'aise-add-lead-btn' );
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-lead-form' );
                if ( form ) form.reset();
                var mid = document.getElementById( 'aise-lead-modal-title' );
                if ( mid ) mid.textContent = 'Novo Lead';
                openModal( 'aise-lead-modal' );
            } );
        }

        // Save lead
        var saveBtn = document.getElementById( 'aise-lead-save-btn' );
        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-lead-form' );
                if ( ! form || ! form.reportValidity() ) return;
                var fd = new FormData( form );
                var id = fd.get( 'id' );
                var body = {
                    name:      fd.get( 'name' ),
                    email:     fd.get( 'email' ),
                    phone:     fd.get( 'phone' ),
                    whatsapp:  fd.get( 'whatsapp' ),
                    source:    fd.get( 'source' ),
                    status:    fd.get( 'status' ),
                };
                var method = id ? 'PUT' : 'POST';
                var path   = id ? 'leads/' + id : 'leads';
                saveBtn.disabled = true;
                saveBtn.textContent = tr( 'salvando', 'Salvando…' );
                apiFetch( path, { method: method, body: JSON.stringify( body ) } )
                    .then( function () {
                        closeModal();
                        toast( tr( 'salvoSucesso', 'Salvo com sucesso!' ) );
                        loadLeads();
                    } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao salvar.' ), 'error' ); } )
                    .finally( function () {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Salvar Lead';
                    } );
            } );
        }

        loadLeads();
    }

    /* ------------------------------------------------------------------
       Listas
    ------------------------------------------------------------------ */

    function initLists() {
        var tbody = document.getElementById( 'aise-lists-tbody' );
        if ( ! tbody ) return;

        function loadLists() {
            tbody.innerHTML = '<tr><td colspan="5" class="aise-loading-row">' + tr( 'carregando', 'Carregando…' ) + '</td></tr>';
            apiFetch( 'lists' ).then( function ( lists ) {
                if ( ! lists || ! lists.length ) {
                    tbody.innerHTML = '<tr><td colspan="5" class="aise-empty-row">' + tr( 'nenhumaLista', 'Nenhuma lista encontrada.' ) + '</td></tr>';
                    return;
                }
                tbody.innerHTML = lists.map( function ( l ) {
                    return '<tr>' +
                        '<td data-label="Nome"><strong>' + escHtml( l.name ) + '</strong></td>' +
                        '<td data-label="Descrição">' + escHtml( l.description || '–' ) + '</td>' +
                        '<td data-label="Webhook">' + ( l.webhook_url ? '<a href="' + escHtml( l.webhook_url ) + '" target="_blank" rel="noopener">link</a>' : '–' ) + '</td>' +
                        '<td data-label="Criado em">' + fmtDate( l.created_at ) + '</td>' +
                        '<td data-label="Ações" class="aise-actions-cell">' +
                            '<button class="aise-btn-icon aise-delete-list" data-id="' + l.id + '" title="Excluir">🗑️</button>' +
                        '</td>' +
                    '</tr>';
                } ).join( '' );
            } ).catch( function () {} );
        }

        tbody.addEventListener( 'click', function ( e ) {
            var btn = e.target.closest( '.aise-delete-list' );
            if ( ! btn ) return;
            if ( ! confirm( tr( 'confirmarExcluirLista', 'Excluir esta lista?' ) ) ) return;
            apiFetch( 'lists/' + btn.dataset.id, { method: 'DELETE' } )
                .then( function () { toast( 'Lista excluída!' ); loadLists(); } )
                .catch( function () { toast( tr( 'erroSalvar', 'Erro ao excluir.' ), 'error' ); } );
        } );

        var addBtn = document.getElementById( 'aise-add-list-btn' );
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-list-form' );
                if ( form ) form.reset();
                openModal( 'aise-list-modal' );
            } );
        }

        var saveBtn = document.getElementById( 'aise-list-save-btn' );
        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-list-form' );
                if ( ! form || ! form.reportValidity() ) return;
                var fd = new FormData( form );
                var body = {
                    name:        fd.get( 'name' ),
                    description: fd.get( 'description' ),
                    webhook_url: fd.get( 'webhook_url' ),
                };
                saveBtn.disabled = true;
                apiFetch( 'lists', { method: 'POST', body: JSON.stringify( body ) } )
                    .then( function () { closeModal(); toast( tr( 'salvoSucesso', 'Salvo!' ) ); loadLists(); } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao salvar.' ), 'error' ); } )
                    .finally( function () { saveBtn.disabled = false; } );
            } );
        }

        loadLists();
    }

    /* ------------------------------------------------------------------
       Automações
    ------------------------------------------------------------------ */

    function initAutomations() {
        var tbody = document.getElementById( 'aise-automations-tbody' );
        if ( ! tbody ) return;

        var triggerLabels = {
            'page_visit':         tr( 'trigPageVisit',         'Visita à Página' ),
            'message_reply':      tr( 'trigMessageReply',      'Resposta de Mensagem' ),
            'webinar_completed':  tr( 'trigWebinarCompleted',  'Webinar Concluído' ),
            'purchase_completed': tr( 'trigPurchaseCompleted', 'Compra Concluída' ),
        };

        function loadAutomations() {
            tbody.innerHTML = '<tr><td colspan="5" class="aise-loading-row">' + tr( 'carregando', 'Carregando…' ) + '</td></tr>';
            apiFetch( 'automations' ).then( function ( rows ) {
                if ( ! rows || ! rows.length ) {
                    tbody.innerHTML = '<tr><td colspan="5" class="aise-empty-row">' + tr( 'nenhumaAutomacao', 'Nenhuma automação encontrada.' ) + '</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map( function ( a ) {
                    var trigLabel = triggerLabels[ a.trigger_type ] || a.trigger_type;
                    var isActive  = a.status === 'active';
                    return '<tr>' +
                        '<td data-label="Nome"><strong>' + escHtml( a.name ) + '</strong></td>' +
                        '<td data-label="Gatilho"><span class="aise-trigger-tag">' + escHtml( trigLabel ) + '</span></td>' +
                        '<td data-label="Status"><span class="aise-badge aise-badge-' + escHtml( a.status ) + '">' + ( isActive ? 'Ativo' : 'Inativo' ) + '</span></td>' +
                        '<td data-label="Criado em">' + fmtDate( a.created_at ) + '</td>' +
                        '<td data-label="Ações" class="aise-actions-cell">' +
                            '<button class="aise-btn-icon aise-toggle-automation" data-id="' + a.id + '" data-status="' + escHtml( a.status ) + '" title="' + ( isActive ? 'Desativar' : 'Ativar' ) + '">' + ( isActive ? '⏸️' : '▶️' ) + '</button>' +
                            '<button class="aise-btn-icon aise-delete-automation" data-id="' + a.id + '" title="Excluir">🗑️</button>' +
                        '</td>' +
                    '</tr>';
                } ).join( '' );
            } ).catch( function () {} );
        }

        tbody.addEventListener( 'click', function ( e ) {
            var delBtn    = e.target.closest( '.aise-delete-automation' );
            var toggleBtn = e.target.closest( '.aise-toggle-automation' );

            if ( delBtn ) {
                if ( ! confirm( tr( 'confirmarExcluirAutomacao', 'Excluir esta automação?' ) ) ) return;
                apiFetch( 'automations/' + delBtn.dataset.id, { method: 'DELETE' } )
                    .then( function () { toast( 'Automação excluída!' ); loadAutomations(); } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao excluir.' ), 'error' ); } );
            }

            if ( toggleBtn ) {
                var newStatus = toggleBtn.dataset.status === 'active' ? 'inactive' : 'active';
                apiFetch( 'automations/' + toggleBtn.dataset.id, {
                    method: 'PUT',
                    body: JSON.stringify( { status: newStatus } ),
                } ).then( function () {
                    toast( newStatus === 'active' ? 'Automação ativada!' : 'Automação desativada!' );
                    loadAutomations();
                } ).catch( function () { toast( tr( 'erroSalvar', 'Erro.' ), 'error' ); } );
            }
        } );

        var addBtn = document.getElementById( 'aise-add-automation-btn' );
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-automation-form' );
                if ( form ) form.reset();
                openModal( 'aise-automation-modal' );
            } );
        }

        var saveBtn = document.getElementById( 'aise-automation-save-btn' );
        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-automation-form' );
                if ( ! form || ! form.reportValidity() ) return;
                var fd = new FormData( form );
                var body = { name: fd.get( 'name' ), trigger_type: fd.get( 'trigger_type' ) };
                saveBtn.disabled = true;
                apiFetch( 'automations', { method: 'POST', body: JSON.stringify( body ) } )
                    .then( function () { closeModal(); toast( tr( 'salvoSucesso', 'Automação criada!' ) ); loadAutomations(); } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao criar.' ), 'error' ); } )
                    .finally( function () { saveBtn.disabled = false; } );
            } );
        }

        loadAutomations();
    }

    /* ------------------------------------------------------------------
       Agentes
    ------------------------------------------------------------------ */

    function initAgents() {
        var grid = document.getElementById( 'aise-agents-grid' );
        if ( ! grid ) return;

        function loadAgents() {
            grid.innerHTML = '<p class="aise-loading-row">' + tr( 'carregando', 'Carregando…' ) + '</p>';
            apiFetch( 'agents' ).then( function ( agents ) {
                if ( ! agents || ! agents.length ) {
                    grid.innerHTML = '<p class="aise-empty-hint"><span class="dashicons dashicons-info-outline"></span>' + tr( 'nenhumAgente', 'Nenhum agente cadastrado.' ) + '</p>';
                    return;
                }
                grid.innerHTML = agents.map( function ( a ) {
                    return '<div class="aise-agent-card">' +
                        '<div class="aise-agent-card-header">' +
                            '<div class="aise-agent-avatar">' + escHtml( a.name.charAt( 0 ).toUpperCase() ) + '</div>' +
                            '<div>' +
                                '<h3>' + escHtml( a.name ) + '</h3>' +
                                '<p class="aise-role">' + escHtml( a.role || tr( 'semFuncao', 'Sem função definida' ) ) + '</p>' +
                            '</div>' +
                        '</div>' +
                        '<p class="aise-agent-goal">' + escHtml( ( a.goal || '' ).substring( 0, 100 ) ) + ( ( a.goal || '' ).length > 100 ? '…' : '' ) + '</p>' +
                        '<div class="aise-agent-features">' +
                            ( a.voice_enabled   ? '<span class="aise-feature-tag">🎙️ Voz</span>'   : '' ) +
                            ( a.image_enabled   ? '<span class="aise-feature-tag">🖼️ Imagem</span>' : '' ) +
                        '</div>' +
                        '<div class="aise-agent-actions">' +
                            '<button class="aise-btn aise-btn-ghost aise-btn-sm aise-delete-agent" data-id="' + a.id + '">Excluir</button>' +
                        '</div>' +
                    '</div>';
                } ).join( '' );
            } ).catch( function () {} );
        }

        grid.addEventListener( 'click', function ( e ) {
            var btn = e.target.closest( '.aise-delete-agent' );
            if ( ! btn ) return;
            if ( ! confirm( tr( 'confirmarExcluirAgente', 'Excluir este agente?' ) ) ) return;
            apiFetch( 'agents/' + btn.dataset.id, { method: 'DELETE' } )
                .then( function () { toast( 'Agente excluído!' ); loadAgents(); } )
                .catch( function () { toast( tr( 'erroSalvar', 'Erro ao excluir.' ), 'error' ); } );
        } );

        var addBtn = document.getElementById( 'aise-add-agent-btn' );
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-agent-form' );
                if ( form ) form.reset();
                openModal( 'aise-agent-modal' );
            } );
        }

        var saveBtn = document.getElementById( 'aise-agent-save-btn' );
        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-agent-form' );
                if ( ! form || ! form.reportValidity() ) return;
                var fd = new FormData( form );
                var body = {
                    name:            fd.get( 'name' ),
                    role:            fd.get( 'role' ),
                    goal:            fd.get( 'goal' ),
                    personality:     fd.get( 'personality' ),
                    training_prompt: fd.get( 'training_prompt' ),
                    voice_enabled:   fd.get( 'voice_enabled' ) ? 1 : 0,
                    image_enabled:   fd.get( 'image_enabled' ) ? 1 : 0,
                };
                saveBtn.disabled = true;
                apiFetch( 'agents', { method: 'POST', body: JSON.stringify( body ) } )
                    .then( function () { closeModal(); toast( tr( 'salvoSucesso', 'Agente criado!' ) ); loadAgents(); } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao salvar.' ), 'error' ); } )
                    .finally( function () { saveBtn.disabled = false; } );
            } );
        }

        loadAgents();
    }

    /* ------------------------------------------------------------------
       Pipelines (Kanban)
    ------------------------------------------------------------------ */

    function initPipelines() {
        var board  = document.getElementById( 'aise-kanban-board' );
        var select = document.getElementById( 'aise-pipeline-select' );
        if ( ! board || ! select ) return;

        var currentPipelineId = null;

        function loadPipelines() {
            apiFetch( 'pipelines' ).then( function ( pipelines ) {
                select.innerHTML = '<option value="">' + tr( 'selecionePipeline', '— Escolher —' ) + '</option>';
                if ( ! pipelines || ! pipelines.length ) return;
                pipelines.forEach( function ( p ) {
                    var opt = document.createElement( 'option' );
                    opt.value       = p.id;
                    opt.textContent = p.name;
                    select.appendChild( opt );
                } );
            } );
        }

        function loadBoard( pipelineId ) {
            board.innerHTML = '<p class="aise-loading-row">' + tr( 'carregando', 'Carregando…' ) + '</p>';
            apiFetch( 'pipelines/' + pipelineId + '/board' ).then( function ( stages ) {
                if ( ! stages || ! stages.length ) {
                    board.innerHTML = '<p class="aise-empty-hint"><span class="dashicons dashicons-info-outline"></span>Nenhuma etapa neste pipeline.</p>';
                    return;
                }
                board.innerHTML = stages.map( function ( stage ) {
                    var leads = stage.leads || [];
                    return '<div class="aise-kanban-column" data-stage-id="' + stage.id + '">' +
                        '<div class="aise-kanban-col-header">' +
                            '<h3>' + escHtml( stage.name ) + '</h3>' +
                            '<span class="aise-kanban-count">' + leads.length + '</span>' +
                        '</div>' +
                        '<div class="aise-kanban-cards" data-stage-id="' + stage.id + '">' +
                            ( leads.length ? leads.map( function ( l ) {
                                return '<div class="aise-kanban-card" data-lead-id="' + l.id + '" draggable="true">' +
                                    '<strong>' + escHtml( l.name ) + '</strong>' +
                                    '<small>' + escHtml( l.email || '' ) + '</small>' +
                                    '<div class="aise-kanban-card-score">' +
                                        '<span class="aise-score-pill">' + l.lead_score + '</span>' +
                                    '</div>' +
                                '</div>';
                            } ).join( '' ) : '<p class="aise-kanban-empty">Vazio</p>' ) +
                        '</div>' +
                    '</div>';
                } ).join( '' );

                initKanbanDragDrop( pipelineId );
            } ).catch( function () {
                board.innerHTML = '<p class="aise-empty-hint">Erro ao carregar o quadro.</p>';
            } );
        }

        function initKanbanDragDrop( pipelineId ) {
            var dragged = null;

            board.querySelectorAll( '.aise-kanban-card' ).forEach( function ( card ) {
                card.addEventListener( 'dragstart', function () {
                    dragged = card;
                    card.classList.add( 'aise-dragging' );
                } );
                card.addEventListener( 'dragend', function () {
                    card.classList.remove( 'aise-dragging' );
                } );
            } );

            board.querySelectorAll( '.aise-kanban-cards' ).forEach( function ( col ) {
                col.addEventListener( 'dragover', function ( e ) {
                    e.preventDefault();
                    col.classList.add( 'aise-drop-target' );
                } );
                col.addEventListener( 'dragleave', function () {
                    col.classList.remove( 'aise-drop-target' );
                } );
                col.addEventListener( 'drop', function ( e ) {
                    e.preventDefault();
                    col.classList.remove( 'aise-drop-target' );
                    if ( ! dragged ) return;
                    var leadId  = dragged.dataset.leadId;
                    var stageId = col.dataset.stageId;
                    col.appendChild( dragged );
                    apiFetch( 'pipelines/' + pipelineId + '/move', {
                        method: 'POST',
                        body: JSON.stringify( { lead_id: parseInt( leadId, 10 ), stage_id: parseInt( stageId, 10 ) } ),
                    } ).then( function () {
                        toast( 'Lead movido!' );
                    } ).catch( function () {
                        toast( 'Erro ao mover lead.', 'error' );
                        loadBoard( pipelineId );
                    } );
                } );
            } );
        }

        select.addEventListener( 'change', function () {
            currentPipelineId = select.value;
            if ( currentPipelineId ) {
                loadBoard( currentPipelineId );
            } else {
                board.innerHTML = '<p class="aise-empty-hint"><span class="dashicons dashicons-info-outline"></span>' + tr( 'selecionePipeline', 'Selecione um pipeline para visualizar o quadro.' ) + '</p>';
            }
        } );

        var addBtn = document.getElementById( 'aise-add-pipeline-btn' );
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-pipeline-form' );
                if ( form ) form.reset();
                openModal( 'aise-pipeline-modal' );
            } );
        }

        var saveBtn = document.getElementById( 'aise-pipeline-save-btn' );
        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                var form = document.getElementById( 'aise-pipeline-form' );
                if ( ! form || ! form.reportValidity() ) return;
                var fd = new FormData( form );
                var body = {
                    name:        fd.get( 'name' ),
                    description: fd.get( 'description' ),
                    stages:      fd.get( 'stages' ),
                };
                saveBtn.disabled = true;
                apiFetch( 'pipelines', { method: 'POST', body: JSON.stringify( body ) } )
                    .then( function ( res ) {
                        closeModal();
                        toast( tr( 'salvoSucesso', 'Pipeline criado!' ) );
                        loadPipelines();
                        setTimeout( function () {
                            select.value = res.id;
                            loadBoard( res.id );
                        }, 300 );
                    } )
                    .catch( function () { toast( tr( 'erroSalvar', 'Erro ao criar.' ), 'error' ); } )
                    .finally( function () { saveBtn.disabled = false; } );
            } );
        }

        loadPipelines();
    }

    /* ------------------------------------------------------------------
       Relatórios (Analytics)
    ------------------------------------------------------------------ */

    function initAnalytics() {
        var statsGrid = document.getElementById( 'aise-analytics-stats' );
        if ( ! statsGrid ) return;

        apiFetch( 'analytics/summary' ).then( function ( data ) {
            statsGrid.innerHTML = [
                { label: 'Total de Leads', value: data.total_leads, icon: 'dashicons-groups' },
                { label: 'Novos Hoje', value: data.new_leads_today, icon: 'dashicons-plus-alt' },
                { label: 'Total de Eventos', value: data.total_events, icon: 'dashicons-bell' },
                { label: 'Score Médio', value: data.avg_score, icon: 'dashicons-star-filled' },
            ].map( function ( s ) {
                return '<div class="aise-stat-card">' +
                    '<div class="aise-stat-icon"><span class="dashicons ' + s.icon + '"></span></div>' +
                    '<div class="aise-stat-info">' +
                        '<span class="aise-stat-label">' + escHtml( s.label ) + '</span>' +
                        '<span class="aise-stat-value">' + ( s.value !== undefined ? s.value : '0' ) + '</span>' +
                    '</div>' +
                '</div>';
            } ).join( '' );

            if ( typeof Chart === 'undefined' ) return;

            var chartDefaults = {
                responsive: true,
                plugins: { legend: { display: false } },
                scales:  { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            };

            if ( data.events_last_7_days ) {
                var ctx = document.getElementById( 'aise-analytics-events-chart' );
                if ( ctx ) {
                    new Chart( ctx, {
                        type: 'bar',
                        data: {
                            labels:   data.events_last_7_days.map( function ( r ) { return fmtDate( r.day ); } ),
                            datasets: [ {
                                label:           'Eventos',
                                data:            data.events_last_7_days.map( function ( r ) { return r.count; } ),
                                backgroundColor: 'rgba(99,102,241,.7)',
                                borderRadius:    4,
                            } ],
                        },
                        options: chartDefaults,
                    } );
                }
            }

            if ( data.top_sources && data.top_sources.length ) {
                var sCtx = document.getElementById( 'aise-sources-chart' );
                if ( sCtx ) {
                    new Chart( sCtx, {
                        type: 'doughnut',
                        data: {
                            labels:   data.top_sources.map( function ( r ) { return r.source || 'Direto'; } ),
                            datasets: [ {
                                data: data.top_sources.map( function ( r ) { return r.count; } ),
                                backgroundColor: [ '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#3b82f6' ],
                                borderWidth: 2,
                            } ],
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
                    } );
                }
            }
        } ).catch( function () {} );
    }

    /* ------------------------------------------------------------------
       Boot
    ------------------------------------------------------------------ */

    document.addEventListener( 'DOMContentLoaded', function () {
        initDashboard();
        initLeads();
        initLists();
        initAutomations();
        initAgents();
        initPipelines();
        initAnalytics();
    } );

} )();

