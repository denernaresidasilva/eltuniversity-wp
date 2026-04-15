/**
 * Plataforma de Webinars – Admin Dashboard
 * React app usando wp.element (React built-in do WordPress)
 */
(function () {
  'use strict';

  var el          = wp.element.createElement;
  var useState    = wp.element.useState;
  var useEffect   = wp.element.useEffect;
  var useCallback = wp.element.useCallback;
  var useRef      = wp.element.useRef;
  var Fragment    = wp.element.Fragment;

  var API_URL  = window.WPWebinarConfig.apiUrl;
  var NONCE    = window.WPWebinarConfig.nonce;
  var SITE_URL = window.WPWebinarConfig.siteUrl;

  /* ─────────────────────────────────────────
     API helpers
  ───────────────────────────────────────── */
  async function apiFetch(path, options) {
    options = options || {};
    var method = options.method || 'GET';
    var body   = options.body !== undefined ? JSON.stringify(options.body) : undefined;
    var res    = await fetch(API_URL + path, {
      method : method,
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
      body   : body,
    });
    var data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Erro na API');
    return data;
  }

  /* ─────────────────────────────────────────
     Utility components
  ───────────────────────────────────────── */

  /* Convert 6-digit hex color to rgba() string */
  function hexToRgba(hex, alpha) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function Spinner() {
    return el('div', { className: 'ww-spinner' });
  }
  function LoadingCenter() {
    return el('div', { className: 'ww-loading-center' }, el(Spinner));
  }
  function Alert(_ref) {
    var type = _ref.type, children = _ref.children, onClose = _ref.onClose;
    return el('div', { className: 'ww-alert ww-alert-' + type, role: 'alert', 'aria-live': 'polite' },
      el('span', null, children),
      onClose ? el('button', { className: 'ww-alert-close', onClick: onClose, 'aria-label': 'Fechar notificação', type: 'button' }, '×') : null
    );
  }
  function Badge(_ref) {
    var value = _ref.value, color = _ref.color;
    var c = color || '#FF6A00';
    return el('span', { className: 'ww-badge', style: { color: c, background: hexToRgba(c, 0.14) } }, value);
  }
  function StatusBadge(_ref) {
    var status = _ref.status;
    /* Each entry: [solidColor, bgColor, label] — colored text on semi-transparent bg */
    var map = {
      publicado: ['#22C55E', 'rgba(34,197,94,0.14)',   'Publicado'],
      rascunho:  ['#F59E0B', 'rgba(245,158,11,0.14)',  'Rascunho'],
      encerrado: ['#B6B6BD', 'rgba(182,182,189,0.14)', 'Encerrado'],
    };
    var info = map[status] || ['#B6B6BD', 'rgba(182,182,189,0.14)', status];
    return el('span', { className: 'ww-status-badge', style: { color: info[0], background: info[1] } }, info[2]);
  }

  /* ─────────────────────────────────────────
     MetricCard component
  ───────────────────────────────────────── */
  function MetricCard(_ref) {
    var title = _ref.title, value = _ref.value, icon = _ref.icon, color = _ref.color, sub = _ref.sub;
    var iconColor = color || '#FF6A00';
    return el('div', { className: 'ww-metric-card' },
      el('div', { className: 'ww-metric-icon', style: { background: hexToRgba(iconColor, 0.14), color: iconColor } }, icon),
      el('div', { className: 'ww-metric-body' },
        el('div', { className: 'ww-metric-title' }, title),
        el('div', { className: 'ww-metric-value' }, value),
        sub ? el('div', { className: 'ww-metric-sub' }, sub) : null
      )
    );
  }

  /* ─────────────────────────────────────────
     Mini bar chart
  ───────────────────────────────────────── */
  function BarChart(_ref) {
    var data = _ref.data, label = _ref.label;
    if (!data || !data.length) return el('div', { className: 'ww-no-data' }, 'Sem dados disponíveis');
    var max = data.reduce(function(m, d) { return d.total > m ? d.total : m; }, 1);
    return el('div', { className: 'ww-chart' },
      el('div', { className: 'ww-chart-title' }, label),
      el('div', { className: 'ww-chart-bars' },
        data.slice(-14).map(function(d, i) {
          var pct = Math.round((d.total / max) * 100);
          return el('div', { key: i, className: 'ww-chart-bar-wrap', title: d.dia + ': ' + d.total },
            el('div', { className: 'ww-chart-bar', style: { height: pct + '%' } }),
            el('div', { className: 'ww-chart-bar-label' }, d.dia ? d.dia.slice(5) : '')
          );
        })
      )
    );
  }

  /* ─────────────────────────────────────────
     Dashboard page
  ───────────────────────────────────────── */
  function DashboardPage() {
    var _s = useState(null), data = _s[0], setData = _s[1];
    var _l = useState(true), loading = _l[0], setLoading = _l[1];
    var _e = useState(''), error = _e[0], setError = _e[1];

    useEffect(function() {
      apiFetch('/dashboard')
        .then(function(d) { setData(d); setLoading(false); })
        .catch(function(e) { setError(e.message); setLoading(false); });
    }, []);

    if (loading) return el(LoadingCenter);
    if (error) return el(Alert, { type: 'error' }, error);
    if (!data) return null;

    function fmtTempo(s) {
      if (!s) return '0min';
      var m = Math.floor(s / 60), sec = s % 60;
      return m + 'min ' + sec + 's';
    }

    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '📊 Dashboard'),

      el('div', { className: 'ww-metrics-grid' },
        el(MetricCard, { title: 'Total de Webinars',     value: data.total_webinars,      icon: '🎥', color: '#FF6A00' }),
        el(MetricCard, { title: 'Total de Participantes',value: data.total_participantes,  icon: '👥', color: '#0ea5e9' }),
        el(MetricCard, { title: 'Participantes Hoje',    value: data.participantes_hoje,   icon: '📅', color: '#22C55E' }),
        el(MetricCard, { title: 'Tempo Médio Assistido', value: fmtTempo(data.tempo_medio_segundos), icon: '⏱', color: '#F59E0B' }),
        el(MetricCard, { title: 'Taxa de Conversão',     value: data.taxa_conversao + '%', icon: '🎯', color: '#EF4444' })
      ),

      el('div', { className: 'ww-dashboard-cols' },
        el('div', { className: 'ww-dashboard-col' },
          el(BarChart, { data: data.inscricoes_por_dia, label: 'Inscrições dos últimos 14 dias' })
        ),
        el('div', { className: 'ww-dashboard-col' },
          el('div', { className: 'ww-card' },
            el('h3', { className: 'ww-card-title' }, '🏆 Webinars Ativos'),
            data.webinars_ativos.length === 0
              ? el('p', { className: 'ww-no-data' }, 'Nenhum webinar publicado ainda.')
              : el('table', { className: 'ww-table' },
                  el('thead', null, el('tr', null,
                    el('th', null, 'Nome'), el('th', null, 'Tipo'), el('th', null, 'Participantes')
                  )),
                  el('tbody', null, data.webinars_ativos.map(function(w) {
                    return el('tr', { key: w.id },
                      el('td', null, w.nome),
                      el('td', null, w.tipo === 'ao_vivo' ? '🔴 Ao Vivo' : '🟢 Evergreen'),
                      el('td', null, w.total_participantes)
                    );
                  }))
                )
          )
        )
      ),

      el('div', { className: 'ww-card ww-mt' },
        el('h3', { className: 'ww-card-title' }, '👤 Últimos Inscritos'),
        data.ultimos_inscritos.length === 0
          ? el('p', { className: 'ww-no-data' }, 'Nenhuma inscrição ainda.')
          : el('table', { className: 'ww-table' },
              el('thead', null, el('tr', null,
                el('th', null, 'Nome'), el('th', null, 'E-mail'), el('th', null, 'Webinar'), el('th', null, 'Data')
              )),
              el('tbody', null, data.ultimos_inscritos.map(function(p) {
                return el('tr', { key: p.id },
                  el('td', null, p.nome),
                  el('td', null, p.email),
                  el('td', null, p.webinar_nome),
                  el('td', null, new Date(p.data_registro).toLocaleDateString('pt-BR'))
                );
              }))
            )
      )
    );
  }

  /* ─────────────────────────────────────────
     Webinar Form Modal
  ───────────────────────────────────────── */
  function WebinarFormModal(_ref) {
    var webinar = _ref.webinar, onSave = _ref.onSave, onClose = _ref.onClose;
    var editing = !!webinar;

    var _f = useState({
      nome           : (webinar && webinar.nome) || '',
      descricao      : (webinar && webinar.descricao) || '',
      youtube_video_id: (webinar && webinar.youtube_video_id) || '',
      tipo           : (webinar && webinar.tipo) || 'evergreen',
      data_inicio    : (webinar && webinar.data_inicio) || '',
      bloquear_avanco: (webinar && +webinar.bloquear_avanco) || 0,
      simulacao_ativa: (webinar && +webinar.simulacao_ativa) || 0,
      simulacao_contagem: (webinar && +webinar.simulacao_contagem) || 0,
    });
    var form = _f[0], setForm = _f[1];
    var _s = useState(false), saving = _s[0], setSaving = _s[1];
    var _e = useState(''), err = _e[0], setErr = _e[1];

    function handle(field, value) {
      setForm(function(prev) { return Object.assign({}, prev, { [field]: value }); });
    }

    async function submit(e) {
      e.preventDefault();
      if (!form.nome.trim()) { setErr('Nome do webinar é obrigatório.'); return; }
      setSaving(true); setErr('');
      try {
        var result = editing
          ? await apiFetch('/webinars/' + webinar.id, { method: 'PUT', body: form })
          : await apiFetch('/webinars', { method: 'POST', body: form });
        onSave(result);
      } catch(ex) {
        setErr(ex.message);
        setSaving(false);
      }
    }

    return el('div', { className: 'ww-modal-overlay', onClick: function(e) { if (e.target === e.currentTarget) onClose(); } },
      el('div', { className: 'ww-modal' },
        el('div', { className: 'ww-modal-header' },
          el('h2', null, editing ? '✏️ Editar Webinar' : '➕ Novo Webinar'),
          el('button', { className: 'ww-modal-close', onClick: onClose }, '×')
        ),
        el('form', { onSubmit: submit, className: 'ww-form' },
          err ? el('div', {
            id          : 'ww-modal-err',
            role        : 'alert',
            'aria-live' : 'polite',
            className   : 'ww-alert ww-alert-error',
          },
            el('span', null, err),
            el('button', { className: 'ww-alert-close', onClick: function() { setErr(''); },
              'aria-label': 'Fechar erro', type: 'button' }, '×')
          ) : null,

          /* ── Seção 1: Dados do Webinar ── */
          el('div', { className: 'ww-form-section' },
            el('div', { className: 'ww-form-section-title' }, 'Dados do Webinar'),
            el('div', { className: 'ww-form-section-body' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label', htmlFor: 'ww-f-nome' }, 'Nome *'),
                el('input', {
                  id              : 'ww-f-nome',
                  className       : 'ww-input' + (!form.nome.trim() && err ? ' is-error' : ''),
                  value           : form.nome,
                  onChange        : function(e) { handle('nome', e.target.value); },
                  required        : true,
                  placeholder     : 'Ex: Aula Secreta de Marketing',
                  'aria-describedby': 'ww-f-nome-help',
                  'aria-invalid'  : !form.nome.trim() && err ? 'true' : 'false',
                }),
                el('span', { id: 'ww-f-nome-help', className: 'ww-field-help' },
                  'Título exibido nas páginas de inscrição e webinar.')
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label', htmlFor: 'ww-f-desc' }, 'Descrição'),
                el('textarea', {
                  id         : 'ww-f-desc',
                  className  : 'ww-input ww-textarea',
                  value      : form.descricao,
                  onChange   : function(e) { handle('descricao', e.target.value); },
                  placeholder: 'Descreva brevemente o conteúdo do webinar...',
                })
              )
            )
          ),

          /* ── Seção 2: Vídeo e Reprodução ── */
          el('div', { className: 'ww-form-section' },
            el('div', { className: 'ww-form-section-title' }, 'Vídeo e Reprodução'),
            el('div', { className: 'ww-form-section-body' },
              el('div', { className: 'ww-form-row' },
                el('div', { className: 'ww-form-group' },
                  el('label', { className: 'ww-label', htmlFor: 'ww-f-vid' }, 'ID do Vídeo YouTube'),
                  el('input', {
                    id                : 'ww-f-vid',
                    className         : 'ww-input',
                    value             : form.youtube_video_id,
                    onChange          : function(e) { handle('youtube_video_id', e.target.value); },
                    placeholder       : 'Ex: dQw4w9WgXcQ',
                    'aria-describedby': 'ww-f-vid-help',
                  }),
                  el('span', { id: 'ww-f-vid-help', className: 'ww-field-help' },
                    'Somente o ID — parte final da URL do YouTube.')
                ),
                el('div', { className: 'ww-form-group' },
                  el('label', { className: 'ww-label', htmlFor: 'ww-f-tipo' }, 'Tipo'),
                  el('select', {
                    id      : 'ww-f-tipo',
                    className: 'ww-select',
                    value   : form.tipo,
                    onChange: function(e) { handle('tipo', e.target.value); },
                  },
                    el('option', { value: 'evergreen' }, '🟢 Evergreen (gravado)'),
                    el('option', { value: 'ao_vivo' }, '🔴 Ao Vivo')
                  )
                )
              ),
              el('div', { className: 'ww-form-row' },
                el('div', { className: 'ww-form-group ww-form-check' },
                  el('label', { className: 'ww-label-check', htmlFor: 'ww-f-bloquear' },
                    el('input', {
                      id      : 'ww-f-bloquear',
                      type    : 'checkbox',
                      checked : !!form.bloquear_avanco,
                      onChange: function(e) { handle('bloquear_avanco', e.target.checked ? 1 : 0); },
                    }),
                    ' Bloquear avanço do vídeo'
                  )
                ),
                el('div', { className: 'ww-form-group ww-form-check' },
                  el('label', { className: 'ww-label-check', htmlFor: 'ww-f-simulacao' },
                    el('input', {
                      id      : 'ww-f-simulacao',
                      type    : 'checkbox',
                      checked : !!form.simulacao_ativa,
                      onChange: function(e) { handle('simulacao_ativa', e.target.checked ? 1 : 0); },
                    }),
                    ' Simulação de audiência ativa'
                  )
                )
              ),
              form.simulacao_ativa ? el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label', htmlFor: 'ww-f-contagem' }, 'Contagem simulada'),
                el('input', {
                  id                : 'ww-f-contagem',
                  type              : 'number',
                  className         : 'ww-input',
                  value             : form.simulacao_contagem,
                  min               : 0,
                  onChange          : function(e) { handle('simulacao_contagem', parseInt(e.target.value, 10) || 0); },
                  'aria-describedby': 'ww-f-contagem-help',
                }),
                el('span', { id: 'ww-f-contagem-help', className: 'ww-field-help' },
                  'Número de espectadores exibidos durante o webinar.')
              ) : null
            )
          ),

          /* ── Seção 3: Agendamento ── */
          el('div', { className: 'ww-form-section' },
            el('div', { className: 'ww-form-section-title' }, 'Agendamento'),
            el('div', { className: 'ww-form-section-body' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label', htmlFor: 'ww-f-data' }, 'Data de Início'),
                el('input', {
                  id      : 'ww-f-data',
                  type    : 'datetime-local',
                  className: 'ww-input',
                  value   : form.data_inicio,
                  onChange: function(e) { handle('data_inicio', e.target.value); },
                })
              )
            )
          ),

          el('div', { className: 'ww-modal-footer' },
            el('button', { type: 'button', className: 'ww-btn ww-btn-secondary', onClick: onClose }, 'Cancelar'),
            el('button', { type: 'submit', className: 'ww-btn ww-btn-primary', disabled: saving,
              'aria-busy': saving ? 'true' : 'false' },
              saving ? '⏳ Salvando...' : (editing ? '💾 Salvar Alterações' : '✅ Criar Webinar')
            )
          )
        )
      )
    );
  }

  /* ─────────────────────────────────────────
     Webinars page
  ───────────────────────────────────────── */
  function WebinarsPage() {
    var _w = useState([]), webinars = _w[0], setWebinars = _w[1];
    var _l = useState(true), loading = _l[0], setLoading = _l[1];
    var _e = useState(''), err = _e[0], setErr = _e[1];
    var _m = useState(false), modal = _m[0], setModal = _m[1];
    var _ed = useState(null), editing = _ed[0], setEditing = _ed[1];
    var _si = useState(''), searchInput = _si[0], setSearchInput = _si[1];
    var _s = useState(''), search = _s[0], setSearch = _s[1];
    var _t = useState(0), total = _t[0], setTotal = _t[1];
    var _ok = useState(''), ok = _ok[0], setOk = _ok[1];
    var _sf = useState(''), statusFilter = _sf[0], setStatusFilter = _sf[1];
    var _pg = useState(1), page = _pg[0], setPage = _pg[1];
    var _tp = useState(1), totalPages = _tp[0], setTotalPages = _tp[1];
    var debounceRef = useRef(null);

    var STATUS_FILTERS = [
      { value: '',          label: 'Todos' },
      { value: 'rascunho',  label: 'Rascunho' },
      { value: 'publicado', label: 'Publicado' },
      { value: 'encerrado', label: 'Encerrado' },
    ];

    var load = useCallback(function() {
      setLoading(true);
      var qs = '?page=' + page + '&per_page=12';
      if (search) qs += '&search=' + encodeURIComponent(search);
      if (statusFilter) qs += '&status=' + encodeURIComponent(statusFilter);
      apiFetch('/webinars' + qs)
        .then(function(d) {
          setWebinars(d.items || d.data || []);
          setTotal(d.total || 0);
          setTotalPages(d.total_pages || d.pages || 1);
          setLoading(false);
        })
        .catch(function(e) { setErr(e.message); setLoading(false); });
    }, [search, statusFilter, page]);

    useEffect(load, [load]);

    function openCreate()  { setEditing(null); setModal(true); }
    function openEdit(w)   { setEditing(w); setModal(true); }
    function closeModal()  { setModal(false); setEditing(null); }

    function onSearchChange(e) {
      var val = e.target.value;
      setSearchInput(val);
      if (debounceRef.current) clearTimeout(debounceRef.current);
      debounceRef.current = setTimeout(function() {
        setSearch(val);
        setPage(1);
      }, 300);
    }

    function onSave(w) {
      closeModal();
      setOk(editing ? 'Webinar "' + w.nome + '" atualizado com sucesso!' : 'Webinar "' + w.nome + '" criado com sucesso!');
      load();
      setTimeout(function() { setOk(''); }, 3000);
    }

    async function publicar(id) {
      try {
        await apiFetch('/webinars/' + id + '/publicar', { method: 'POST' });
        setOk('Webinar publicado com sucesso! Páginas criadas automaticamente.');
        load();
        setTimeout(function() { setOk(''); }, 4000);
      } catch(e) { setErr(e.message); }
    }

    async function despublicar(w) {
      if (!window.confirm('Despublicar "' + w.nome + '"? O webinar voltará para Rascunho.')) return;
      try {
        await apiFetch('/webinars/' + w.id, { method: 'PUT', body: { status: 'rascunho' } });
        setOk('"' + w.nome + '" despublicado e definido como Rascunho.');
        load();
        setTimeout(function() { setOk(''); }, 3000);
      } catch(e) { setErr(e.message); }
    }

    async function duplicar(w) {
      if (!window.confirm('Duplicar o webinar "' + w.nome + '"?')) return;
      try {
        await apiFetch('/webinars', { method: 'POST', body: {
          nome            : w.nome + ' (Cópia)',
          descricao       : w.descricao || '',
          youtube_video_id: w.youtube_video_id || '',
          tipo            : w.tipo,
        }});
        setOk('Webinar duplicado com sucesso!');
        load();
        setTimeout(function() { setOk(''); }, 3000);
      } catch(e) { setErr(e.message); }
    }

    async function excluir(w) {
      if (!window.confirm('Excluir o webinar "' + w.nome + '"? Esta ação não pode ser desfeita.')) return;
      try {
        await apiFetch('/webinars/' + w.id, { method: 'DELETE' });
        setOk('Webinar excluído com sucesso.');
        load();
        setTimeout(function() { setOk(''); }, 3000);
      } catch(e) { setErr(e.message); }
    }

    return el('div', { className: 'ww-page' },
      modal ? el(WebinarFormModal, { webinar: editing, onSave: onSave, onClose: closeModal }) : null,

      el('div', { className: 'ww-page-header' },
        el('h1', { className: 'ww-page-title' }, '🎥 Webinars'),
        el('button', { className: 'ww-btn ww-btn-primary', onClick: openCreate }, '+ Novo Webinar')
      ),

      ok  ? el(Alert, { type: 'success', onClose: function() { setOk(''); } }, ok)  : null,
      err ? el(Alert, { type: 'error',   onClose: function() { setErr(''); } }, err) : null,

      el('div', { className: 'ww-toolbar' },
        el('input', {
          className   : 'ww-search',
          placeholder : 'Buscar webinar...',
          value       : searchInput,
          onChange    : onSearchChange,
          'aria-label': 'Buscar webinars',
        }),
        el('div', { className: 'ww-filter-tabs', role: 'group', 'aria-label': 'Filtrar por status' },
          STATUS_FILTERS.map(function(sf) {
            return el('button', {
              key      : sf.value,
              type     : 'button',
              className: 'ww-filter-tab' + (statusFilter === sf.value ? ' is-active' : ''),
              onClick  : function() { setStatusFilter(sf.value); setPage(1); },
            }, sf.label);
          })
        ),
        el('span', { className: 'ww-total' }, total + ' webinar(s)')
      ),

      loading ? el(LoadingCenter) :
      webinars.length === 0
        ? el('div', { className: 'ww-empty' },
            el('p', null, statusFilter
              ? 'Nenhum webinar com status "' + statusFilter + '".'
              : 'Nenhum webinar encontrado.'),
            !statusFilter ? el('button', { className: 'ww-btn ww-btn-primary', onClick: openCreate }, '+ Criar Primeiro Webinar') : null
          )
        : el(Fragment, null,
            el('div', { className: 'ww-webinar-grid' },
              webinars.map(function(w) {
                return el('div', { key: w.id, className: 'ww-webinar-card' },
                  el('div', { className: 'ww-webinar-card-header' },
                    el('h3', null, w.nome),
                    el(StatusBadge, { status: w.status })
                  ),
                  el('p', { className: 'ww-webinar-meta' },
                    w.tipo === 'ao_vivo' ? '🔴 Ao Vivo' : '🟢 Evergreen',
                    ' · ID: ', w.id
                  ),
                  w.descricao ? el('p', { className: 'ww-webinar-desc' }, w.descricao.substring(0, 100) + (w.descricao.length > 100 ? '...' : '')) : null,
                  el('div', { className: 'ww-webinar-card-footer' },
                    el('button', { className: 'ww-btn ww-btn-sm ww-btn-secondary', onClick: function() { openEdit(w); }, 'aria-label': 'Editar ' + w.nome }, '✏️ Editar'),
                    w.status === 'rascunho' ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-success', onClick: function() { publicar(w.id); }, 'aria-label': 'Publicar ' + w.nome }, '🚀 Publicar') : null,
                    w.status === 'publicado' ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-secondary', onClick: function() { despublicar(w); }, 'aria-label': 'Despublicar ' + w.nome }, '⏸ Despublicar') : null,
                    w.pagina_webinar_id ? el('a', { className: 'ww-btn ww-btn-sm ww-btn-outline', href: SITE_URL + '/?page_id=' + w.pagina_webinar_id, target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'Visualizar ' + w.nome }, '👁 Ver') : null,
                    el('button', { className: 'ww-btn ww-btn-sm ww-btn-outline', onClick: function() { duplicar(w); }, 'aria-label': 'Duplicar ' + w.nome }, '⧉ Duplicar'),
                    el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { excluir(w); }, 'aria-label': 'Excluir ' + w.nome }, '🗑')
                  )
                );
              })
            ),
            totalPages > 1 ? el('div', { className: 'ww-pagination' },
              el('button', {
                className: 'ww-btn ww-btn-sm ww-btn-secondary',
                disabled : page <= 1,
                onClick  : function() { setPage(page - 1); },
              }, '← Anterior'),
              el('span', null, 'Página ' + page + ' de ' + totalPages),
              el('button', {
                className: 'ww-btn ww-btn-sm ww-btn-secondary',
                disabled : page >= totalPages,
                onClick  : function() { setPage(page + 1); },
              }, 'Próxima →')
            ) : null
          )
    );
  }

  /* ─────────────────────────────────────────
     Participantes page
  ───────────────────────────────────────── */
  function ParticipantesPage() {
    var _p = useState([]), participantes = _p[0], setParticipantes = _p[1];
    var _l = useState(true), loading = _l[0], setLoading = _l[1];
    var _e = useState(''), err = _e[0], setErr = _e[1];
    var _s = useState(''), search = _s[0], setSearch = _s[1];
    var _t = useState(0), total = _t[0], setTotal = _t[1];
    var _pg = useState(1), page = _pg[0], setPage = _pg[1];
    var _pages = useState(1), pages = _pages[0], setPages = _pages[1];

    var load = useCallback(function() {
      setLoading(true);
      apiFetch('/participantes?search=' + encodeURIComponent(search) + '&page=' + page + '&per_page=20')
        .then(function(d) {
          setParticipantes(d.data || []);
          setTotal(d.total || 0);
          setPages(d.total_pages || d.pages || 1);
          setLoading(false);
        })
        .catch(function(e) { setErr(e.message); setLoading(false); });
    }, [search, page]);

    useEffect(load, [load]);

    function fmtTempo(s) {
      if (!s) return '-';
      var m = Math.floor(s / 60), sec = s % 60;
      return m + 'min ' + sec + 's';
    }

    return el('div', { className: 'ww-page' },
      el('div', { className: 'ww-page-header' },
        el('h1', { className: 'ww-page-title' }, '👥 Participantes'),
        el('span', { className: 'ww-total' }, total + ' participante(s)')
      ),

      err ? el(Alert, { type: 'error', onClose: function() { setErr(''); } }, err) : null,

      el('div', { className: 'ww-toolbar' },
        el('input', { className: 'ww-search', placeholder: 'Buscar por nome ou e-mail...', value: search,
          onChange: function(e) { setSearch(e.target.value); setPage(1); } })
      ),

      loading ? el(LoadingCenter) :
      participantes.length === 0
        ? el('p', { className: 'ww-no-data' }, 'Nenhum participante encontrado.')
        : el(Fragment, null,
            el('table', { className: 'ww-table' },
              el('thead', null, el('tr', null,
                el('th', null, 'Nome'), el('th', null, 'E-mail'), el('th', null, 'Telefone'),
                el('th', null, 'Webinar ID'), el('th', null, 'Data'), el('th', null, 'Tempo Assistido')
              )),
              el('tbody', null, participantes.map(function(p) {
                return el('tr', { key: p.id },
                  el('td', null, p.nome),
                  el('td', null, p.email),
                  el('td', null, p.telefone || '-'),
                  el('td', null, p.webinar_id),
                  el('td', null, new Date(p.data_registro).toLocaleDateString('pt-BR')),
                  el('td', null, fmtTempo(p.tempo_assistido))
                );
              }))
            ),
            pages > 1 ? el('div', { className: 'ww-pagination' },
              el('button', { className: 'ww-btn ww-btn-sm', disabled: page <= 1, onClick: function() { setPage(page - 1); } }, '← Anterior'),
              el('span', null, 'Página ' + page + ' de ' + pages),
              el('button', { className: 'ww-btn ww-btn-sm', disabled: page >= pages, onClick: function() { setPage(page + 1); } }, 'Próxima →')
            ) : null
          )
    );
  }

  /* ─────────────────────────────────────────
     Chat page
  ───────────────────────────────────────── */
  function ChatPage() {
    var _w = useState([]), webinars = _w[0], setWebinars = _w[1];
    var _sel = useState(''), selectedId = _sel[0], setSelectedId = _sel[1];
    var _msgs = useState([]), msgs = _msgs[0], setMsgs = _msgs[1];
    var _l = useState(false), loading = _l[0], setLoading = _l[1];
    var _ok = useState(''), ok = _ok[0], setOk = _ok[1];
    var _err = useState(''), err = _err[0], setErr = _err[1];
    var _form = useState({ autor: '', mensagem: '', tempo: 0, tipo: 'programada' });
    var form = _form[0], setForm = _form[1];

    useEffect(function() {
      apiFetch('/webinars?per_page=100')
        .then(function(d) { setWebinars(d.data || []); })
        .catch(function() {});
    }, []);

    useEffect(function() {
      if (!selectedId) return;
      setLoading(true);
      apiFetch('/webinars/' + selectedId + '/chat')
        .then(function(d) { setMsgs(d || []); setLoading(false); })
        .catch(function(e) { setErr(e.message); setLoading(false); });
    }, [selectedId]);

    function handleForm(field, value) {
      setForm(function(prev) { return Object.assign({}, prev, { [field]: value }); });
    }

    async function addMsg(e) {
      e.preventDefault();
      if (!selectedId) { setErr('Selecione um webinar primeiro.'); return; }
      if (!form.autor.trim()) { setErr('O campo Autor é obrigatório.'); return; }
      if (!form.mensagem.trim()) { setErr('O campo Mensagem é obrigatório.'); return; }
      if (form.mensagem.trim().length > 500) { setErr('A mensagem não pode ter mais de 500 caracteres.'); return; }
      try {
        await apiFetch('/webinars/' + selectedId + '/chat', {
          method: 'POST',
          body: { autor: form.autor, mensagem: form.mensagem, tempo: form.tempo, tipo: form.tipo }
        });
        setOk('Mensagem adicionada!');
        setForm({ autor: '', mensagem: '', tempo: 0, tipo: 'programada' });
        apiFetch('/webinars/' + selectedId + '/chat').then(function(d) { setMsgs(d || []); });
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
    }

    async function deleteMsg(id) {
      if (!window.confirm('Remover esta mensagem?')) return;
      await apiFetch('/webinars/' + selectedId + '/chat/' + id, { method: 'DELETE' });
      setMsgs(msgs.filter(function(m) { return m.id !== id; }));
    }

    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '💬 Chat Programado'),

      ok  ? el(Alert, { type: 'success', onClose: function() { setOk(''); } }, ok) : null,
      err ? el(Alert, { type: 'error',   onClose: function() { setErr(''); } }, err) : null,

      el('div', { className: 'ww-form-group' },
        el('label', { className: 'ww-label', htmlFor: 'ww-chat-webinar' }, 'Selecione o Webinar'),
        el('select', { id: 'ww-chat-webinar', className: 'ww-select', value: selectedId, onChange: function(e) { setSelectedId(e.target.value); } },
          el('option', { value: '' }, '-- Selecione --'),
          webinars.map(function(w) { return el('option', { key: w.id, value: w.id }, w.nome); })
        )
      ),
          el('form', { onSubmit: addMsg, className: 'ww-form' },
            el('div', { className: 'ww-form-row' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Autor'),
                el('input', { className: 'ww-input', value: form.autor, onChange: function(e) { handleForm('autor', e.target.value); }, placeholder: 'João Silva' })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tempo (segundos)'),
                el('input', { type: 'number', className: 'ww-input', value: form.tempo, min: 0,
                  onChange: function(e) { handleForm('tempo', parseInt(e.target.value, 10) || 0); } })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tipo'),
                el('select', { className: 'ww-select', value: form.tipo, onChange: function(e) { handleForm('tipo', e.target.value); } },
                  el('option', { value: 'programada' }, 'Programada'),
                  el('option', { value: 'ao_vivo' }, 'Ao Vivo')
                )
              )
            ),
            el('div', { className: 'ww-form-group' },
              el('label', { className: 'ww-label' }, 'Mensagem'),
              el('textarea', { className: 'ww-input ww-textarea', value: form.mensagem,
                onChange: function(e) { handleForm('mensagem', e.target.value); },
                placeholder: 'Conteúdo da mensagem...', rows: 2 })
            ),
            el('button', { type: 'submit', className: 'ww-btn ww-btn-primary' }, 'Adicionar Mensagem')
          )
        ),

        loading ? el(LoadingCenter) :
        el('div', { className: 'ww-card ww-mt' },
          el('h3', { className: 'ww-card-title' }, 'Mensagens (' + msgs.length + ')'),
          msgs.length === 0 ? el('p', { className: 'ww-no-data' }, 'Nenhuma mensagem cadastrada.') :
          el('table', { className: 'ww-table' },
            el('thead', null, el('tr', null,
              el('th', null, 'Tempo'), el('th', null, 'Autor'), el('th', null, 'Mensagem'), el('th', null, 'Tipo'), el('th', null, '')
            )),
            el('tbody', null, msgs.map(function(m) {
              return el('tr', { key: m.id },
                el('td', null, Math.floor(m.tempo / 60) + ':' + String(m.tempo % 60).padStart(2, '0')),
                el('td', null, m.autor),
                el('td', null, m.mensagem),
                el('td', null, m.tipo === 'ao_vivo' ? '🔴 Ao Vivo' : '⏰ Programada'),
                el('td', null, el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { deleteMsg(m.id); } }, '🗑'))
              );
            }))
          )
        )
      ) : null
    );
  }

  /* ─────────────────────────────────────────
     Automações page
  ───────────────────────────────────────── */
  function AutomacoesPage() {
    var _w = useState([]), webinars = _w[0], setWebinars = _w[1];
    var _sel = useState(''), selectedId = _sel[0], setSelectedId = _sel[1];
    var _aut = useState([]), automacoes = _aut[0], setAutomacoes = _aut[1];
    var _l = useState(false), loading = _l[0], setLoading = _l[1];
    var _ok = useState(''), ok = _ok[0], setOk = _ok[1];
    var _err = useState(''), err = _err[0], setErr = _err[1];
    var _form = useState({ nome: '', gatilho: 'tempo_especifico', acao: 'mostrar_botao', config: { tempo: 0, texto: '', url: '', cor: '#ef4444' } });
    var form = _form[0], setForm = _form[1];

    var GATILHOS = [
      { value: 'inscricao',         label: 'Inscrição no Webinar' },
      { value: 'inicio_video',      label: 'Início do Vídeo' },
      { value: 'tempo_especifico',  label: 'Tempo Específico do Vídeo' },
      { value: 'tag_adicionada',    label: 'Tag Adicionada' },
    ];
    var ACOES = [
      { value: 'mostrar_botao',       label: 'Mostrar Botão de Compra' },
      { value: 'mostrar_popup',       label: 'Mostrar Popup' },
      { value: 'enviar_webhook',      label: 'Enviar Webhook' },
      { value: 'redirecionar',        label: 'Redirecionar Usuário' },
      { value: 'mostrar_notificacao', label: 'Mostrar Notificação' },
    ];

    useEffect(function() {
      apiFetch('/webinars?per_page=100').then(function(d) { setWebinars(d.data || []); }).catch(function() {});
    }, []);

    useEffect(function() {
      if (!selectedId) return;
      setLoading(true);
      apiFetch('/webinars/' + selectedId + '/automacoes')
        .then(function(d) { setAutomacoes(d || []); setLoading(false); })
        .catch(function(e) { setErr(e.message); setLoading(false); });
    }, [selectedId]);

    function handleForm(field, value) {
      setForm(function(prev) { return Object.assign({}, prev, { [field]: value }); });
    }
    function handleConfig(field, value) {
      setForm(function(prev) { return Object.assign({}, prev, { config: Object.assign({}, prev.config, { [field]: value }) }); });
    }

    async function addAut(e) {
      e.preventDefault();
      if (!selectedId) { setErr('Selecione um webinar.'); return; }
      try {
        await apiFetch('/webinars/' + selectedId + '/automacoes', {
          method: 'POST',
          body: { nome: form.nome, gatilho: form.gatilho, acao: form.acao, config: form.config, ordem: automacoes.length }
        });
        setOk('Automação criada!');
        apiFetch('/webinars/' + selectedId + '/automacoes').then(function(d) { setAutomacoes(d || []); });
        setForm({ nome: '', gatilho: 'tempo_especifico', acao: 'mostrar_botao', config: { tempo: 0, texto: '', url: '', cor: '#ef4444' } });
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
    }

    async function toggleAtivo(a) {
      await apiFetch('/webinars/' + selectedId + '/automacoes/' + a.id, { method: 'PUT', body: { ativo: a.ativo ? 0 : 1 } });
      apiFetch('/webinars/' + selectedId + '/automacoes').then(function(d) { setAutomacoes(d || []); });
    }

    async function delAut(id) {
      if (!confirm('Excluir esta automação?')) return;
      await apiFetch('/webinars/' + selectedId + '/automacoes/' + id, { method: 'DELETE' });
      setAutomacoes(automacoes.filter(function(a) { return a.id !== id; }));
    }

    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '⚡ Automações'),

      ok  ? el(Alert, { type: 'success', onClose: function() { setOk(''); } }, ok) : null,
      err ? el(Alert, { type: 'error',   onClose: function() { setErr(''); } }, err) : null,

      el('div', { className: 'ww-form-group' },
        el('label', { className: 'ww-label' }, 'Selecione o Webinar'),
        el('select', { className: 'ww-select', value: selectedId, onChange: function(e) { setSelectedId(e.target.value); } },
          el('option', { value: '' }, '-- Selecione --'),
          webinars.map(function(w) { return el('option', { key: w.id, value: w.id }, w.nome); })
        )
      ),

      selectedId ? el(Fragment, null,
        el('div', { className: 'ww-card' },
          el('h3', { className: 'ww-card-title' }, 'Nova Automação'),
          el('form', { onSubmit: addAut, className: 'ww-form' },
            el('div', { className: 'ww-form-row' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Nome da Automação'),
                el('input', { className: 'ww-input', value: form.nome, onChange: function(e) { handleForm('nome', e.target.value); }, placeholder: 'Ex: Botão de Oferta' })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Gatilho'),
                el('select', { className: 'ww-select', value: form.gatilho, onChange: function(e) { handleForm('gatilho', e.target.value); } },
                  GATILHOS.map(function(g) { return el('option', { key: g.value, value: g.value }, g.label); })
                )
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Ação'),
                el('select', { className: 'ww-select', value: form.acao, onChange: function(e) { handleForm('acao', e.target.value); } },
                  ACOES.map(function(a) { return el('option', { key: a.value, value: a.value }, a.label); })
                )
              )
            ),
            el('div', { className: 'ww-form-row' },
              form.gatilho === 'tempo_especifico' ? el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tempo de Disparo (segundos)'),
                el('input', { type: 'number', className: 'ww-input', value: form.config.tempo || 0, min: 0,
                  onChange: function(e) { handleConfig('tempo', parseInt(e.target.value, 10) || 0); } })
              ) : null,
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Texto / Mensagem'),
                el('input', { className: 'ww-input', value: form.config.texto || '', onChange: function(e) { handleConfig('texto', e.target.value); }, placeholder: 'Ex: Garanta sua vaga!' })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'URL (link do botão/redirect)'),
                el('input', { className: 'ww-input', value: form.config.url || '', onChange: function(e) { handleConfig('url', e.target.value); }, placeholder: 'https://...' })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Cor'),
                el('input', { type: 'color', className: 'ww-input ww-input-color', value: form.config.cor || '#ef4444', onChange: function(e) { handleConfig('cor', e.target.value); } })
              )
            ),
            el('button', { type: 'submit', className: 'ww-btn ww-btn-primary' }, 'Adicionar Automação')
          )
        ),

        loading ? el(LoadingCenter) :
        el('div', { className: 'ww-card ww-mt' },
          el('h3', { className: 'ww-card-title' }, 'Automações (' + automacoes.length + ')'),
          automacoes.length === 0 ? el('p', { className: 'ww-no-data' }, 'Nenhuma automação cadastrada.') :
          el('table', { className: 'ww-table' },
            el('thead', null, el('tr', null,
              el('th', null, 'Nome'), el('th', null, 'Gatilho'), el('th', null, 'Ação'), el('th', null, 'Status'), el('th', null, '')
            )),
            el('tbody', null, automacoes.map(function(a) {
              var gatilhoLabel = GATILHOS.find(function(g) { return g.value === a.gatilho; });
              var acaoLabel    = ACOES.find(function(ac) { return ac.value === a.acao; });
              return el('tr', { key: a.id },
                el('td', null, a.nome || '—'),
                el('td', null, gatilhoLabel ? gatilhoLabel.label : a.gatilho),
                el('td', null, acaoLabel ? acaoLabel.label : a.acao),
                el('td', null,
                  el('button', { className: 'ww-btn ww-btn-sm ' + (a.ativo ? 'ww-btn-success' : 'ww-btn-secondary'), onClick: function() { toggleAtivo(a); } },
                    a.ativo ? '✅ Ativo' : '⏸ Pausado'
                  )
                ),
                el('td', null, el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { delAut(a.id); } }, '🗑'))
              );
            }))
          )
        )
      ) : null
    );
  }

  /* ─────────────────────────────────────────
     Analytics page
  ───────────────────────────────────────── */
  function AnalyticsPage() {
    var _w = useState([]), webinars = _w[0], setWebinars = _w[1];
    var _sel = useState(''), selectedId = _sel[0], setSelectedId = _sel[1];
    var _d = useState(null), data = _d[0], setData = _d[1];
    var _l = useState(false), loading = _l[0], setLoading = _l[1];

    useEffect(function() {
      apiFetch('/webinars?per_page=100').then(function(d) { setWebinars(d.data || []); }).catch(function() {});
    }, []);

    useEffect(function() {
      if (!selectedId) return;
      setLoading(true);
      apiFetch('/webinars/' + selectedId + '/analytics')
        .then(function(d) { setData(d); setLoading(false); })
        .catch(function() { setLoading(false); });
    }, [selectedId]);

    function fmtTempo(s) {
      if (!s) return '0min';
      return Math.floor(s / 60) + 'min ' + (s % 60) + 's';
    }

    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '📈 Analytics'),

      el('div', { className: 'ww-form-group' },
        el('label', { className: 'ww-label' }, 'Selecione o Webinar'),
        el('select', { className: 'ww-select', value: selectedId, onChange: function(e) { setSelectedId(e.target.value); } },
          el('option', { value: '' }, '-- Selecione --'),
          webinars.map(function(w) { return el('option', { key: w.id, value: w.id }, w.nome); })
        )
      ),

      loading ? el(LoadingCenter) :
      data ? el(Fragment, null,
        el('div', { className: 'ww-metrics-grid' },
          el(MetricCard, { title: 'Participantes',     value: data.total_participantes,  icon: '👥', color: '#0ea5e9' }),
          el(MetricCard, { title: 'Participantes Hoje',value: data.participantes_hoje,    icon: '📅', color: '#22C55E' }),
          el(MetricCard, { title: 'Tempo Médio',       value: fmtTempo(data.tempo_medio_segundos), icon: '⏱', color: '#F59E0B' }),
          el(MetricCard, { title: 'Convertidos',       value: data.convertidos,           icon: '🎯', color: '#EF4444' }),
          el(MetricCard, { title: 'Taxa de Conversão', value: data.taxa_conversao + '%',  icon: '📊', color: '#FF6A00' }),
          el(MetricCard, { title: 'Cliques no Botão',  value: data.cliques_botao,         icon: '🖱', color: '#0ea5e9' })
        ),
        el('div', { className: 'ww-dashboard-cols' },
          el('div', { className: 'ww-dashboard-col' },
            el(BarChart, { data: data.inscricoes_por_dia, label: 'Inscrições por dia (últimos 30 dias)' })
          ),
          el('div', { className: 'ww-dashboard-col' },
            el('div', { className: 'ww-card' },
              el('h3', { className: 'ww-card-title' }, '📉 Retenção por Segmento'),
              data.retencao && data.retencao.length > 0
                ? el('table', { className: 'ww-table' },
                    el('thead', null, el('tr', null, el('th', null, 'Minuto'), el('th', null, 'Assistindo'))),
                    el('tbody', null, data.retencao.map(function(r, i) {
                      return el('tr', { key: i },
                        el('td', null, r.minuto + 'min'),
                        el('td', null, r.count)
                      );
                    }))
                  )
                : el('p', { className: 'ww-no-data' }, 'Sem dados de retenção.')
            )
          )
        )
      ) : null
    );
  }

  /* ─────────────────────────────────────────
     Configurações page
  ───────────────────────────────────────── */
  function ConfiguracoesPage() {
    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '⚙️ Configurações'),
      el('div', { className: 'ww-card' },
        el('h3', { className: 'ww-card-title' }, 'Informações do Plugin'),
        el('p', null, el('strong', null, 'Versão: '), '1.0.0'),
        el('p', null, el('strong', null, 'API REST: '), API_URL),
        el('p', null,
          el('strong', null, 'Documentação dos shortcodes:'),
          el('br', null),
          el('code', null, '[webinar_player id="ID"]'), ' — exibe o player do webinar',
          el('br', null),
          el('code', null, '[webinar_inscricao id="ID"]'), ' — exibe o formulário de inscrição'
        )
      )
    );
  }

  /* ─────────────────────────────────────────
     Sessões Page — session management, offer config,
     tag automations, message sequences, participants
  ───────────────────────────────────────── */
  function SessoesPage() {
    var _w   = useState([]), webinars = _w[0], setWebinars = _w[1];
    var _sel = useState(''), selectedId = _sel[0], setSelectedId = _sel[1];
    var _s   = useState([]), sessoes = _s[0], setSessoes = _s[1];
    var _l   = useState(false), loading = _l[0], setLoading = _l[1];
    var _ok  = useState(''), ok = _ok[0], setOk = _ok[1];
    var _err = useState(''), err = _err[0], setErr = _err[1];

    // Offer / Tag automation / Sequence config (stored in webinar.configuracoes_json)
    var _cfg = useState(null), cfg = _cfg[0], setCfg = _cfg[1];
    var _cfgSaving = useState(false), cfgSaving = _cfgSaving[0], setCfgSaving = _cfgSaving[1];

    // Lead SaaS lists and tags
    var _listas = useState([]), listas = _listas[0], setListas = _listas[1];
    var _tags   = useState([]), leadsaaTags = _tags[0], setLeadsaaTags = _tags[1];

    // Session form
    var _sf = useState({ inicio_em: '', tipo: 'evergreen' }), sfForm = _sf[0], setSfForm = _sf[1];

    // Session participants panel
    var _selSessao = useState(null), selSessao = _selSessao[0], setSelSessao = _selSessao[1];
    var _parts = useState([]), parts = _parts[0], setParts = _parts[1];
    var _pLoading = useState(false), pLoading = _pLoading[0], setPLoading = _pLoading[1];

    // Finalize modal
    var _finModal = useState(null), finModal = _finModal[0], setFinModal = _finModal[1];
    var _finLista = useState(''), finLista = _finLista[0], setFinLista = _finLista[1];

    // Tag automation form
    var _taForm = useState({ tag_nome: '', lista_id: '' }), taForm = _taForm[0], setTaForm = _taForm[1];

    // Sequence form
    var _seqForm = useState({ tipo: 'email', assunto: '', corpo: '', offset_segundos: 0 });
    var seqForm = _seqForm[0], setSeqForm = _seqForm[1];

    useEffect(function() {
      apiFetch('/webinars?per_page=100').then(function(d) { setWebinars(d.data || []); }).catch(function() {});
      apiFetch('/leadsaas/listas').then(setListas).catch(function() {});
      apiFetch('/leadsaas/tags').then(setLeadsaaTags).catch(function() {});
    }, []);

    useEffect(function() {
      if (!selectedId) { setSessoes([]); setCfg(null); return; }
      setLoading(true);
      Promise.all([
        apiFetch('/webinars/' + selectedId + '/sessoes'),
        apiFetch('/webinars/' + selectedId),
      ]).then(function(res) {
        setSessoes(res[0] || []);
        var w = res[1];
        var c = {};
        try { c = JSON.parse(w.configuracoes_json || '{}'); } catch(e) {}
        setCfg({
          oferta_aparece_em_segundos: c.oferta_aparece_em_segundos || 0,
          tag_automacoes: c.tag_automacoes || [],
          sequencias: c.sequencias || [],
        });
        setLoading(false);
      }).catch(function(e) { setErr(e.message); setLoading(false); });
    }, [selectedId]);

    // Load participants when a session is selected
    useEffect(function() {
      if (!selSessao) { setParts([]); return; }
      setPLoading(true);
      apiFetch('/sessoes/' + selSessao.id + '/participantes?per_page=100')
        .then(function(d) { setParts(d.data || []); setPLoading(false); })
        .catch(function() { setPLoading(false); });
    }, [selSessao]);

    async function saveConfig() {
      if (!selectedId || !cfg) return;
      setCfgSaving(true);
      try {
        // Fetch current webinar to merge other fields
        var w = await apiFetch('/webinars/' + selectedId);
        var existing = {};
        try { existing = JSON.parse(w.configuracoes_json || '{}'); } catch(e) {}
        var merged = Object.assign({}, existing, cfg);
        await apiFetch('/webinars/' + selectedId, { method: 'PUT', body: { configuracoes_json: JSON.stringify(merged) } });
        setOk('Configurações salvas!');
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
      setCfgSaving(false);
    }

    async function createSessao(e) {
      e.preventDefault();
      if (!selectedId || !sfForm.inicio_em) { setErr('Selecione um webinar e preencha a data/hora.'); return; }
      try {
        var s = await apiFetch('/webinars/' + selectedId + '/sessoes', { method: 'POST', body: sfForm });
        setSessoes(function(prev) { return prev.concat([s]); });
        setSfForm({ inicio_em: '', tipo: 'evergreen' });
        setOk('Sessão criada!');
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
    }

    async function deleteSessao(s) {
      if (!confirm('Excluir a sessão de ' + s.inicio_em + '? Só é possível se não houver participantes.')) return;
      try {
        await apiFetch('/sessoes/' + s.id, { method: 'DELETE' });
        setSessoes(function(prev) { return prev.filter(function(x) { return x.id !== s.id; }); });
        if (selSessao && selSessao.id === s.id) setSelSessao(null);
        setOk('Sessão excluída.');
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
    }

    async function marcarReplay(s) {
      if (s.replay_enviado_em) { alert('Replay já foi enviado em ' + s.replay_enviado_em); return; }
      if (!confirm('Marcar replay como enviado para esta sessão? Não poderá ser desfeito.')) return;
      try {
        await apiFetch('/sessoes/' + s.id + '/replay', { method: 'POST' });
        setSessoes(function(prev) { return prev.map(function(x) { return x.id === s.id ? Object.assign({}, x, { replay_enviado_em: new Date().toISOString() }) : x; }); });
        setOk('Replay marcado como enviado!');
        setTimeout(function() { setOk(''); }, 3000);
      } catch(ex) { setErr(ex.message); }
    }

    async function dispararSequencia(s) {
      if (!confirm('Disparar sequência de mensagens para todos os participantes desta sessão?')) return;
      try {
        var res = await apiFetch('/sessoes/' + s.id + '/sequencia', { method: 'POST' });
        setOk(res.message + ' (' + res.agendados + ' mensagens agendadas)');
        setTimeout(function() { setOk(''); }, 4000);
      } catch(ex) { setErr(ex.message); }
    }

    async function confirmarFinalizar() {
      if (!finModal) return;
      try {
        var res = await apiFetch('/sessoes/' + finModal.id + '/finalizar', {
          method: 'POST',
          body: { lista_destino_id: parseInt(finLista, 10) || 0 }
        });
        setOk(res.message + ' · Leads movidos: ' + res.leads_movidos + (res.lista_deletada ? ' · Lista deletada ✓' : ''));
        setSessoes(function(prev) {
          return prev.map(function(x) { return x.id === finModal.id ? Object.assign({}, x, { status: 'finalizada' }) : x; });
        });
        setFinModal(null);
        setTimeout(function() { setOk(''); }, 5000);
      } catch(ex) { setErr(ex.message); setFinModal(null); }
    }

    function addTagAutomacao() {
      if (!taForm.tag_nome || !taForm.lista_id) return;
      setCfg(function(prev) {
        return Object.assign({}, prev, { tag_automacoes: (prev.tag_automacoes || []).concat([{
          tag_nome: taForm.tag_nome,
          lista_id: parseInt(taForm.lista_id, 10),
        }]) });
      });
      setTaForm({ tag_nome: '', lista_id: '' });
    }

    function removeTagAutomacao(idx) {
      setCfg(function(prev) {
        return Object.assign({}, prev, { tag_automacoes: prev.tag_automacoes.filter(function(_, i) { return i !== idx; }) });
      });
    }

    function addSequencia() {
      if (!seqForm.corpo) return;
      setCfg(function(prev) {
        return Object.assign({}, prev, { sequencias: (prev.sequencias || []).concat([Object.assign({}, seqForm)]) });
      });
      setSeqForm({ tipo: 'email', assunto: '', corpo: '', offset_segundos: 0 });
    }

    function removeSequencia(idx) {
      setCfg(function(prev) {
        return Object.assign({}, prev, { sequencias: prev.sequencias.filter(function(_, i) { return i !== idx; }) });
      });
    }

    function fmtDt(dt) {
      if (!dt) return '-';
      return new Date(dt).toLocaleString('pt-BR');
    }

    function fmtOffset(s) {
      var abs = Math.abs(s);
      var sign = s < 0 ? '-' : '+';
      if (abs < 3600) return sign + Math.round(abs / 60) + 'min';
      return sign + Math.round(abs / 3600) + 'h';
    }

    var webinarAtivo = selectedId ? webinars.find(function(w) { return String(w.id) === String(selectedId); }) : null;

    return el('div', { className: 'ww-page' },
      // Finalize modal
      finModal ? el('div', { className: 'ww-modal-overlay', onClick: function(e) { if (e.target === e.currentTarget) setFinModal(null); } },
        el('div', { className: 'ww-modal' },
          el('div', { className: 'ww-modal-header' },
            el('h2', null, '🏁 Finalizar Sessão'),
            el('button', { className: 'ww-modal-close', onClick: function() { setFinModal(null); } }, '×')
          ),
          el('div', { style: { padding: '1.5rem' } },
            el('p', null, 'Todos os leads desta sessão serão movidos para a lista selecionada. A lista da sessão só será deletada se ficar vazia após a movimentação.'),
            el('div', { className: 'ww-form-group', style: { marginTop: 12 } },
              el('label', { className: 'ww-label' }, 'Lista de destino (Lead SaaS)'),
              el('select', { className: 'ww-select', value: finLista, onChange: function(e) { setFinLista(e.target.value); } },
                el('option', { value: '' }, '-- Selecione (opcional) --'),
                listas.map(function(l) { return el('option', { key: l.id, value: l.id }, l.nome); })
              )
            ),
            el('div', { className: 'ww-modal-footer' },
              el('button', { className: 'ww-btn ww-btn-secondary', onClick: function() { setFinModal(null); } }, 'Cancelar'),
              el('button', { className: 'ww-btn ww-btn-primary', onClick: confirmarFinalizar }, '✅ Confirmar Finalização')
            )
          )
        )
      ) : null,

      el('h1', { className: 'ww-page-title' }, '📅 Sessões & Leads'),

      ok  ? el(Alert, { type: 'success', onClose: function() { setOk(''); } }, ok)  : null,
      err ? el(Alert, { type: 'error',   onClose: function() { setErr(''); } }, err) : null,

      el('div', { className: 'ww-form-group' },
        el('label', { className: 'ww-label' }, 'Selecione o Webinar'),
        el('select', { className: 'ww-select', value: selectedId, onChange: function(e) { setSelectedId(e.target.value); setSelSessao(null); } },
          el('option', { value: '' }, '-- Selecione --'),
          webinars.map(function(w) { return el('option', { key: w.id, value: w.id }, w.nome); })
        )
      ),

      selectedId && cfg ? el(Fragment, null,

        /* ── Config: offer time ── */
        el('div', { className: 'ww-card' },
          el('h3', { className: 'ww-card-title' }, '⚙️ Configuração do Webinar (válida para todas as sessões)'),

          el('div', { className: 'ww-form-group' },
            el('label', { className: 'ww-label' }, '⏱ Oferta aparece em (segundos)'),
            el('input', { type: 'number', className: 'ww-input', min: 0, value: cfg.oferta_aparece_em_segundos,
              onChange: function(e) { setCfg(function(p) { return Object.assign({}, p, { oferta_aparece_em_segundos: parseInt(e.target.value, 10) || 0 }); }); } })
          ),

          /* Tag automations */
          el('div', { style: { marginTop: 16 } },
            el('strong', null, '🏷 Automações: Tag recebida → Mover para lista Lead SaaS'),
            el('div', { className: 'ww-form-row', style: { marginTop: 8 } },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tag (nome)'),
                el('input', { className: 'ww-input', placeholder: 'ex: viu_oferta', value: taForm.tag_nome,
                  onChange: function(e) { setTaForm(function(p) { return Object.assign({}, p, { tag_nome: e.target.value }); }); } })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Lista destino (Lead SaaS)'),
                el('select', { className: 'ww-select', value: taForm.lista_id,
                  onChange: function(e) { setTaForm(function(p) { return Object.assign({}, p, { lista_id: e.target.value }); }); } },
                  el('option', { value: '' }, '-- Selecione --'),
                  listas.map(function(l) { return el('option', { key: l.id, value: l.id }, l.nome); })
                )
              ),
              el('div', { className: 'ww-form-group', style: { display: 'flex', alignItems: 'flex-end' } },
                el('button', { className: 'ww-btn ww-btn-primary', onClick: addTagAutomacao }, '+ Adicionar')
              )
            ),
            cfg.tag_automacoes && cfg.tag_automacoes.length > 0
              ? el('table', { className: 'ww-table', style: { marginTop: 8 } },
                  el('thead', null, el('tr', null, el('th', null, 'Tag'), el('th', null, 'Mover para Lista'), el('th', null, ''))),
                  el('tbody', null, cfg.tag_automacoes.map(function(ta, idx) {
                    var lista = listas.find(function(l) { return l.id == ta.lista_id; });
                    return el('tr', { key: idx },
                      el('td', null, ta.tag_nome),
                      el('td', null, lista ? lista.nome : 'Lista #' + ta.lista_id),
                      el('td', null, el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { removeTagAutomacao(idx); } }, '🗑'))
                    );
                  }))
                )
              : el('p', { className: 'ww-no-data', style: { marginTop: 8 } }, 'Nenhuma automação configurada.')
          ),

          /* Message sequences */
          el('div', { style: { marginTop: 20 } },
            el('strong', null, '✉️ Sequências de Mensagens (baseadas na hora da sessão)'),
            el('div', { className: 'ww-form-row', style: { marginTop: 8 } },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tipo'),
                el('select', { className: 'ww-select', value: seqForm.tipo,
                  onChange: function(e) { setSeqForm(function(p) { return Object.assign({}, p, { tipo: e.target.value }); }); } },
                  el('option', { value: 'email' }, '📧 E-mail'),
                  el('option', { value: 'whatsapp' }, '💬 WhatsApp')
                )
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Offset em segundos (ex: -3600 = 1h antes, 3600 = 1h depois)'),
                el('input', { type: 'number', className: 'ww-input', value: seqForm.offset_segundos,
                  onChange: function(e) { setSeqForm(function(p) { return Object.assign({}, p, { offset_segundos: parseInt(e.target.value, 10) || 0 }); }); } })
              )
            ),
            el('div', { className: 'ww-form-row' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Assunto (e-mail)'),
                el('input', { className: 'ww-input', value: seqForm.assunto, placeholder: 'Assunto do e-mail',
                  onChange: function(e) { setSeqForm(function(p) { return Object.assign({}, p, { assunto: e.target.value }); }); } })
              ),
              el('div', { className: 'ww-form-group', style: { display: 'flex', alignItems: 'flex-end' } },
                el('button', { className: 'ww-btn ww-btn-primary', onClick: addSequencia }, '+ Adicionar')
              )
            ),
            el('div', { className: 'ww-form-group' },
              el('label', { className: 'ww-label' }, 'Corpo da Mensagem'),
              el('textarea', { className: 'ww-input ww-textarea', rows: 3, value: seqForm.corpo, placeholder: 'Conteúdo da mensagem...',
                onChange: function(e) { setSeqForm(function(p) { return Object.assign({}, p, { corpo: e.target.value }); }); } })
            ),
            cfg.sequencias && cfg.sequencias.length > 0
              ? el('table', { className: 'ww-table', style: { marginTop: 8 } },
                  el('thead', null, el('tr', null, el('th', null, 'Tipo'), el('th', null, 'Quando'), el('th', null, 'Assunto'), el('th', null, ''))),
                  el('tbody', null, cfg.sequencias.map(function(seq, idx) {
                    return el('tr', { key: idx },
                      el('td', null, seq.tipo === 'whatsapp' ? '💬 WhatsApp' : '📧 E-mail'),
                      el('td', null, fmtOffset(seq.offset_segundos || 0)),
                      el('td', null, seq.assunto || '—'),
                      el('td', null, el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { removeSequencia(idx); } }, '🗑'))
                    );
                  }))
                )
              : el('p', { className: 'ww-no-data', style: { marginTop: 8 } }, 'Nenhuma sequência configurada.')
          ),

          el('div', { style: { marginTop: 16 } },
            el('button', { className: 'ww-btn ww-btn-primary', onClick: saveConfig, disabled: cfgSaving },
              cfgSaving ? 'Salvando...' : '💾 Salvar Configuração'
            )
          )
        ),

        /* ── Sessions list ── */
        el('div', { className: 'ww-card ww-mt' },
          el('h3', { className: 'ww-card-title' }, '📅 Sessões (máx. 10 ativas)'),

          /* Create session form */
          el('form', { onSubmit: createSessao, className: 'ww-form', style: { marginBottom: 16 } },
            el('div', { className: 'ww-form-row' },
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Data e Hora da Sessão *'),
                el('input', { type: 'datetime-local', className: 'ww-input', value: sfForm.inicio_em,
                  onChange: function(e) { setSfForm(function(p) { return Object.assign({}, p, { inicio_em: e.target.value }); }); } })
              ),
              el('div', { className: 'ww-form-group' },
                el('label', { className: 'ww-label' }, 'Tipo'),
                el('select', { className: 'ww-select', value: sfForm.tipo,
                  onChange: function(e) { setSfForm(function(p) { return Object.assign({}, p, { tipo: e.target.value }); }); } },
                  el('option', { value: 'evergreen' }, '🟢 Evergreen'),
                  el('option', { value: 'ao_vivo' }, '🔴 Ao Vivo')
                )
              ),
              el('div', { className: 'ww-form-group', style: { display: 'flex', alignItems: 'flex-end' } },
                el('button', { type: 'submit', className: 'ww-btn ww-btn-success' }, '+ Nova Sessão')
              )
            )
          ),

          loading ? el(LoadingCenter) :
          sessoes.length === 0
            ? el('p', { className: 'ww-no-data' }, 'Nenhuma sessão criada para este webinar.')
            : el('table', { className: 'ww-table' },
                el('thead', null, el('tr', null,
                  el('th', null, 'Data/Hora'),
                  el('th', null, 'Tipo'),
                  el('th', null, 'Status'),
                  el('th', null, 'Participantes'),
                  el('th', null, 'Leads SaaS'),
                  el('th', null, 'Replay'),
                  el('th', null, 'Ações')
                )),
                el('tbody', null, sessoes.map(function(s) {
                  var isAtiva = s.status === 'ativa';
                  var isSel   = selSessao && selSessao.id === s.id;
                  return el('tr', { key: s.id, style: { background: isSel ? 'rgba(255,106,0,0.06)' : undefined } },
                    el('td', null, fmtDt(s.inicio_em)),
                    el('td', null, s.tipo === 'ao_vivo' ? '🔴 Ao Vivo' : '🟢 Evergreen'),
                    el('td', null, isAtiva
                      ? el('span', { style: { color: '#22C55E' } }, '● Ativa')
                      : el('span', { style: { color: '#B6B6BD' } }, '◼ Finalizada')
                    ),
                    el('td', null, s.total_participantes || 0),
                    el('td', null, s.leadsaas_total !== null ? s.leadsaas_total : '—'),
                    el('td', null, s.replay_enviado_em ? '✅ ' + fmtDt(s.replay_enviado_em) : '—'),
                    el('td', null,
                      el('div', { style: { display: 'flex', gap: 4, flexWrap: 'wrap' } },
                        el('button', { className: 'ww-btn ww-btn-sm ww-btn-secondary',
                          onClick: function() { setSelSessao(isSel ? null : s); } },
                          isSel ? '▲ Fechar' : '👥 Participantes'
                        ),
                        isAtiva ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-outline',
                          onClick: function() { dispararSequencia(s); } }, '✉️ Sequência') : null,
                        isAtiva && !s.replay_enviado_em ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-outline',
                          onClick: function() { marcarReplay(s); } }, '🔁 Marcar Replay') : null,
                        isAtiva ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger',
                          onClick: function() { setFinModal(s); setFinLista(''); } }, '🏁 Finalizar') : null,
                        isAtiva && (s.total_participantes === 0 || s.total_participantes === '0')
                          ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger',
                              onClick: function() { deleteSessao(s); } }, '🗑') : null
                      )
                    )
                  );
                }))
              )
        ),

        /* ── Session participants panel ── */
        selSessao ? el('div', { className: 'ww-card ww-mt' },
          el('h3', { className: 'ww-card-title' }, '👥 Participantes da Sessão — ' + fmtDt(selSessao.inicio_em)),
          pLoading ? el(LoadingCenter) :
          parts.length === 0
            ? el('p', { className: 'ww-no-data' }, 'Nenhum participante nesta sessão.')
            : el('table', { className: 'ww-table' },
                el('thead', null, el('tr', null,
                  el('th', null, 'Nome'), el('th', null, 'E-mail'), el('th', null, 'Telefone'),
                  el('th', null, 'Data'), el('th', null, 'Tempo Assistido'), el('th', null, 'Lead SaaS ID')
                )),
                el('tbody', null, parts.map(function(p) {
                  function fmtTempo(s) { if (!s) return '-'; return Math.floor(s/60)+'min '+String(s%60)+'s'; }
                  return el('tr', { key: p.id },
                    el('td', null, p.nome),
                    el('td', null, p.email),
                    el('td', null, p.telefone || '-'),
                    el('td', null, new Date(p.data_registro).toLocaleDateString('pt-BR')),
                    el('td', null, fmtTempo(p.tempo_assistido)),
                    el('td', null, p.leadsaas_lead_id ? '#' + p.leadsaas_lead_id : '—')
                  );
                }))
              )
        ) : null

      ) : null
    );
  }

  /* ─────────────────────────────────────────
     Root App
  ───────────────────────────────────────── */
  function App() {
    var rawPage = (window.WPWebinarConfig.page || '').replace('wp-webinar-', '') || 'dashboard';
    var pageMap = {
      'plataforma'   : 'dashboard',
      'webinars'     : 'webinars',
      'participantes': 'participantes',
      'chat'         : 'chat',
      'automacoes'   : 'automacoes',
      'analytics'    : 'analytics',
      'configuracoes': 'configuracoes',
      'sessoes'      : 'sessoes',
    };
    var page = pageMap[rawPage] || 'dashboard';

    var pages = {
      dashboard    : el(DashboardPage),
      webinars     : el(WebinarsPage),
      participantes: el(ParticipantesPage),
      chat         : el(ChatPage),
      automacoes   : el(AutomacoesPage),
      analytics    : el(AnalyticsPage),
      configuracoes: el(ConfiguracoesPage),
      sessoes      : el(SessoesPage),
    };

    return el('main', { className: 'ww-main' },
      pages[page] || el(DashboardPage)
    );
  }

  /* ─────────────────────────────────────────
     Boot
  ───────────────────────────────────────── */
  var container = document.getElementById('wp-webinar-app');
  if (container) {
    wp.element.render(el(App), container);
  }

})();
