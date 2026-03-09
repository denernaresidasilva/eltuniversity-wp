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
  function Spinner() {
    return el('div', { className: 'ww-spinner' });
  }
  function LoadingCenter() {
    return el('div', { className: 'ww-loading-center' }, el(Spinner));
  }
  function Alert(_ref) {
    var type = _ref.type, children = _ref.children, onClose = _ref.onClose;
    return el('div', { className: 'ww-alert ww-alert-' + type },
      el('span', null, children),
      onClose ? el('button', { className: 'ww-alert-close', onClick: onClose }, '×') : null
    );
  }
  function Badge(_ref) {
    var value = _ref.value, color = _ref.color;
    return el('span', { className: 'ww-badge', style: { background: color || '#6366f1' } }, value);
  }
  function StatusBadge(_ref) {
    var status = _ref.status;
    var map = { publicado: ['#10b981', 'Publicado'], rascunho: ['#f59e0b', 'Rascunho'], encerrado: ['#6b7280', 'Encerrado'] };
    var info = map[status] || ['#6b7280', status];
    return el('span', { className: 'ww-status-badge', style: { background: info[0] } }, info[1]);
  }

  /* ─────────────────────────────────────────
     MetricCard component
  ───────────────────────────────────────── */
  function MetricCard(_ref) {
    var title = _ref.title, value = _ref.value, icon = _ref.icon, color = _ref.color, sub = _ref.sub;
    return el('div', { className: 'ww-metric-card' },
      el('div', { className: 'ww-metric-icon', style: { background: color || '#6366f1' } }, icon),
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
        el(MetricCard, { title: 'Total de Webinars',     value: data.total_webinars,      icon: '🎥', color: '#6366f1' }),
        el(MetricCard, { title: 'Total de Participantes',value: data.total_participantes,  icon: '👥', color: '#0ea5e9' }),
        el(MetricCard, { title: 'Participantes Hoje',    value: data.participantes_hoje,   icon: '📅', color: '#10b981' }),
        el(MetricCard, { title: 'Tempo Médio Assistido', value: fmtTempo(data.tempo_medio_segundos), icon: '⏱', color: '#f59e0b' }),
        el(MetricCard, { title: 'Taxa de Conversão',     value: data.taxa_conversao + '%', icon: '🎯', color: '#ef4444' })
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
          err ? el(Alert, { type: 'error', onClose: function() { setErr(''); } }, err) : null,

          el('div', { className: 'ww-form-group' },
            el('label', { className: 'ww-label' }, 'Nome do Webinar *'),
            el('input', { className: 'ww-input', value: form.nome,
              onChange: function(e) { handle('nome', e.target.value); }, required: true,
              placeholder: 'Ex: Aula Secreta de Marketing' })
          ),

          el('div', { className: 'ww-form-group' },
            el('label', { className: 'ww-label' }, 'Descrição'),
            el('textarea', { className: 'ww-input ww-textarea', value: form.descricao,
              onChange: function(e) { handle('descricao', e.target.value); },
              placeholder: 'Descreva o webinar...' })
          ),

          el('div', { className: 'ww-form-row' },
            el('div', { className: 'ww-form-group' },
              el('label', { className: 'ww-label' }, 'ID do Vídeo YouTube'),
              el('input', { className: 'ww-input', value: form.youtube_video_id,
                onChange: function(e) { handle('youtube_video_id', e.target.value); },
                placeholder: 'Ex: dQw4w9WgXcQ' })
            ),
            el('div', { className: 'ww-form-group' },
              el('label', { className: 'ww-label' }, 'Tipo'),
              el('select', { className: 'ww-select', value: form.tipo,
                onChange: function(e) { handle('tipo', e.target.value); } },
                el('option', { value: 'evergreen' }, '🟢 Evergreen (gravado)'),
                el('option', { value: 'ao_vivo' }, '🔴 Ao Vivo')
              )
            )
          ),

          el('div', { className: 'ww-form-row' },
            el('div', { className: 'ww-form-group' },
              el('label', { className: 'ww-label' }, 'Data de Início'),
              el('input', { type: 'datetime-local', className: 'ww-input', value: form.data_inicio,
                onChange: function(e) { handle('data_inicio', e.target.value); } })
            )
          ),

          el('div', { className: 'ww-form-row' },
            el('div', { className: 'ww-form-group ww-form-check' },
              el('label', { className: 'ww-label-check' },
                el('input', { type: 'checkbox', checked: !!form.bloquear_avanco,
                  onChange: function(e) { handle('bloquear_avanco', e.target.checked ? 1 : 0); } }),
                ' Bloquear avanço do vídeo'
              )
            ),
            el('div', { className: 'ww-form-group ww-form-check' },
              el('label', { className: 'ww-label-check' },
                el('input', { type: 'checkbox', checked: !!form.simulacao_ativa,
                  onChange: function(e) { handle('simulacao_ativa', e.target.checked ? 1 : 0); } }),
                ' Simulação de audiência ativa'
              )
            )
          ),

          form.simulacao_ativa ? el('div', { className: 'ww-form-group' },
            el('label', { className: 'ww-label' }, 'Contagem simulada (pessoas assistindo)'),
            el('input', { type: 'number', className: 'ww-input', value: form.simulacao_contagem, min: 0,
              onChange: function(e) { handle('simulacao_contagem', parseInt(e.target.value, 10) || 0); } })
          ) : null,

          el('div', { className: 'ww-modal-footer' },
            el('button', { type: 'button', className: 'ww-btn ww-btn-secondary', onClick: onClose }, 'Cancelar'),
            el('button', { type: 'submit', className: 'ww-btn ww-btn-primary', disabled: saving },
              saving ? 'Salvando...' : (editing ? 'Salvar Alterações' : 'Criar Webinar')
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
    var _s = useState(''), search = _s[0], setSearch = _s[1];
    var _t = useState(0), total = _t[0], setTotal = _t[1];
    var _ok = useState(''), ok = _ok[0], setOk = _ok[1];

    var load = useCallback(function() {
      setLoading(true);
      apiFetch('/webinars?search=' + encodeURIComponent(search) + '&per_page=50')
        .then(function(d) { setWebinars(d.data || []); setTotal(d.total || 0); setLoading(false); })
        .catch(function(e) { setErr(e.message); setLoading(false); });
    }, [search]);

    useEffect(load, [load]);

    function openCreate()  { setEditing(null); setModal(true); }
    function openEdit(w)   { setEditing(w); setModal(true); }
    function closeModal()  { setModal(false); setEditing(null); }

    function onSave(w) {
      closeModal();
      setOk(editing ? 'Webinar atualizado com sucesso!' : 'Webinar criado com sucesso!');
      load();
      setTimeout(function() { setOk(''); }, 3000);
    }

    async function publicar(id) {
      try {
        await apiFetch('/webinars/' + id + '/publicar', { method: 'POST' });
        setOk('Webinar publicado! Páginas criadas automaticamente.');
        load();
        setTimeout(function() { setOk(''); }, 4000);
      } catch(e) { setErr(e.message); }
    }

    async function excluir(w) {
      if (!confirm('Excluir o webinar "' + w.nome + '"? Esta ação não pode ser desfeita.')) return;
      try {
        await apiFetch('/webinars/' + w.id, { method: 'DELETE' });
        setOk('Webinar excluído.');
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
        el('input', { className: 'ww-search', placeholder: 'Buscar webinar...', value: search,
          onChange: function(e) { setSearch(e.target.value); } }),
        el('span', { className: 'ww-total' }, total + ' webinar(s)')
      ),

      loading ? el(LoadingCenter) :
      webinars.length === 0
        ? el('div', { className: 'ww-empty' },
            el('p', null, 'Nenhum webinar encontrado.'),
            el('button', { className: 'ww-btn ww-btn-primary', onClick: openCreate }, '+ Criar Primeiro Webinar')
          )
        : el('div', { className: 'ww-webinar-grid' },
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
                  el('button', { className: 'ww-btn ww-btn-sm ww-btn-secondary', onClick: function() { openEdit(w); } }, '✏️ Editar'),
                  w.status === 'rascunho' ? el('button', { className: 'ww-btn ww-btn-sm ww-btn-success', onClick: function() { publicar(w.id); } }, '🚀 Publicar') : null,
                  w.pagina_webinar_id ? el('a', { className: 'ww-btn ww-btn-sm ww-btn-outline', href: SITE_URL + '/?page_id=' + w.pagina_webinar_id, target: '_blank' }, '👁 Ver') : null,
                  el('button', { className: 'ww-btn ww-btn-sm ww-btn-danger', onClick: function() { excluir(w); } }, '🗑')
                )
              );
            })
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
          setPages(d.pages || 1);
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
      if (!selectedId || !form.autor.trim() || !form.mensagem.trim()) {
        setErr('Selecione um webinar e preencha autor e mensagem.'); return;
      }
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
      if (!confirm('Remover esta mensagem?')) return;
      await apiFetch('/webinars/' + selectedId + '/chat/' + id, { method: 'DELETE' });
      setMsgs(msgs.filter(function(m) { return m.id !== id; }));
    }

    return el('div', { className: 'ww-page' },
      el('h1', { className: 'ww-page-title' }, '💬 Chat Programado'),

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
          el('h3', { className: 'ww-card-title' }, 'Adicionar Mensagem Programada'),
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
          el(MetricCard, { title: 'Participantes Hoje',value: data.participantes_hoje,    icon: '📅', color: '#10b981' }),
          el(MetricCard, { title: 'Tempo Médio',       value: fmtTempo(data.tempo_medio_segundos), icon: '⏱', color: '#f59e0b' }),
          el(MetricCard, { title: 'Convertidos',       value: data.convertidos,           icon: '🎯', color: '#ef4444' }),
          el(MetricCard, { title: 'Taxa de Conversão', value: data.taxa_conversao + '%',  icon: '📊', color: '#6366f1' }),
          el(MetricCard, { title: 'Cliques no Botão',  value: data.cliques_botao,         icon: '🖱', color: '#8b5cf6' })
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
     Root App
  ───────────────────────────────────────── */
  function App() {
    var rawPage = (window.WPWebinarConfig.page || '').replace('wp-webinar-', '') || 'dashboard';
    var pageMap = {
      'plataforma' : 'dashboard',
      'webinars'   : 'webinars',
      'participantes': 'participantes',
      'chat'       : 'chat',
      'automacoes' : 'automacoes',
      'analytics'  : 'analytics',
      'configuracoes': 'configuracoes',
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
