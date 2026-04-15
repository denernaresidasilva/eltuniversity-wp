/**
 * Gerenciador de Leads SaaS – Admin Dashboard
 * React app using WordPress's built-in wp.element (React)
 */
(function () {
  'use strict';

  var el = wp.element.createElement;
  var useState = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var useCallback = wp.element.useCallback;
  var useRef = wp.element.useRef;
  var Fragment = wp.element.Fragment;

  var API_URL = window.LeadsSaaSConfig.apiUrl;
  var NONCE = window.LeadsSaaSConfig.nonce;
  var SITE_URL = window.LeadsSaaSConfig.siteUrl;

  /* ============================================================
     API helpers
  ============================================================ */
  async function apiFetch(path, options) {
    options = options || {};
    var method = options.method || 'GET';
    var body = options.body ? JSON.stringify(options.body) : undefined;
    var res = await fetch(API_URL + path, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': NONCE,
      },
      body: body,
    });
    var data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Erro na API');
    return data;
  }

  /* ============================================================
     Utility components
  ============================================================ */
  function Spinner() {
    return el('div', { className: 'ls-spinner' });
  }

  function LoadingCenter() {
    return el('div', { className: 'ls-loading-center' }, el(Spinner));
  }

  function Alert({ type, children, onClose }) {
    return el('div', { className: 'ls-alert ls-alert-' + type },
      el('span', null, type === 'success' ? '✓' : '✕'),
      el('span', { style: { flex: 1 } }, children),
      onClose && el('button', {
        onClick: onClose,
        style: { background: 'none', border: 'none', cursor: 'pointer', fontWeight: 700, fontSize: 16, color: 'inherit', padding: '0 4px' }
      }, '×')
    );
  }

  function Modal({ title, onClose, children, footer }) {
    return el('div', { className: 'ls-modal-overlay', onClick: function(e) { if (e.target === e.currentTarget) onClose(); } },
      el('div', { className: 'ls-modal' },
        el('div', { className: 'ls-modal-header' },
          el('h2', { className: 'ls-modal-title' }, title),
          el('button', { className: 'ls-modal-close', onClick: onClose }, '×')
        ),
        el('div', { className: 'ls-modal-body' }, children),
        footer && el('div', { className: 'ls-modal-footer' }, footer)
      )
    );
  }

  function FormGroup({ label, required, children }) {
    return el('div', { className: 'ls-form-group' },
      label && el('label', null, label, required && el('span', { className: 'req' }, ' *')),
      children
    );
  }

  function Input({ value, onChange, placeholder, type, required }) {
    return el('input', {
      className: 'ls-input',
      type: type || 'text',
      value: value,
      onChange: function(e) { onChange(e.target.value); },
      placeholder: placeholder || '',
      required: required || false,
    });
  }

  function Select({ value, onChange, options }) {
    return el('select', {
      className: 'ls-select',
      value: value,
      onChange: function(e) { onChange(e.target.value); }
    },
      options.map(function(opt) {
        return el('option', { key: opt.value, value: opt.value }, opt.label);
      })
    );
  }

  function Textarea({ value, onChange, placeholder, rows }) {
    return el('textarea', {
      className: 'ls-textarea',
      value: value,
      onChange: function(e) { onChange(e.target.value); },
      placeholder: placeholder || '',
      rows: rows || 3,
    });
  }

  function TagBadge({ tag }) {
    return el('span', {
      className: 'ls-tag',
      style: { background: tag.cor + '22', color: tag.cor, border: '1px solid ' + tag.cor + '44' }
    },
      el('span', { className: 'ls-tag-dot', style: { background: tag.cor } }),
      tag.nome
    );
  }

  function EmptyState({ icon, title, desc, action }) {
    return el('div', { className: 'ls-empty-state' },
      el('div', { className: 'ls-empty-state__icon' }, icon || '📋'),
      el('div', { className: 'ls-empty-state__title' }, title),
      el('div', { className: 'ls-empty-state__desc' }, desc || ''),
      action
    );
  }

  function Pagination({ page, total, perPage, onChange }) {
    var totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return null;
    return el('div', { className: 'ls-pagination' },
      el('span', { className: 'ls-pagination-info' },
        'Mostrando ' + ((page - 1) * perPage + 1) + '–' + Math.min(page * perPage, total) + ' de ' + total
      ),
      el('button', {
        className: 'ls-btn ls-btn-outline ls-btn-sm',
        disabled: page <= 1,
        onClick: function() { onChange(page - 1); }
      }, '← Anterior'),
      el('span', { style: { fontSize: 13, color: '#6b7280', margin: '0 6px' } }, page + ' / ' + totalPages),
      el('button', {
        className: 'ls-btn ls-btn-outline ls-btn-sm',
        disabled: page >= totalPages,
        onClick: function() { onChange(page + 1); }
      }, 'Próxima →')
    );
  }

  /* ============================================================
     Dashboard Page
  ============================================================ */
  function DashboardPage() {
    var [data, setData] = useState(null);
    var [loading, setLoading] = useState(true);

    useEffect(function() {
      apiFetch('/dashboard').then(function(d) {
        setData(d);
        setLoading(false);
      }).catch(function() { setLoading(false); });
    }, []);

    if (loading) return el(LoadingCenter);
    if (!data) return el(Alert, { type: 'error' }, 'Erro ao carregar métricas.');

    var taxa = data.taxa_crescimento;
    var taxaDir = taxa >= 0 ? 'up' : 'down';
    var taxaPrefix = taxa >= 0 ? '↑' : '↓';

    return el(Fragment, null,
      el('div', { className: 'ls-metrics-grid' },
        MetricCard('Total de Leads', data.total_leads, null, null),
        MetricCard('Total de Listas', data.total_listas, null, null),
        MetricCard('Leads Hoje', data.leads_hoje, null, null),
        MetricCard('Leads este Mês', data.leads_mes,
          taxaPrefix + ' ' + Math.abs(taxa) + '% vs mês ant.',
          taxaDir
        ),
        MetricCard('Total de Etiquetas', data.total_tags, null, null)
      ),

      el('div', { className: 'ls-grid-2' },
        // Leads por lista
        el('div', { className: 'ls-card' },
          el('div', { className: 'ls-card-header' },
            el('h3', { className: 'ls-card-title' }, 'Leads por Lista')
          ),
          el('div', { className: 'ls-card-body' },
            data.leads_por_lista.length === 0
              ? el('p', { className: 'ls-text-muted' }, 'Nenhuma lista criada.')
              : el('div', null,
                  data.leads_por_lista.map(function(item) {
                    var pct = data.total_leads > 0 ? Math.round((item.total / data.total_leads) * 100) : 0;
                    return el('div', { key: item.id, style: { marginBottom: 12 } },
                      el('div', { className: 'ls-flex ls-gap-2', style: { justifyContent: 'space-between', marginBottom: 4 } },
                        el('span', { style: { fontSize: 13, fontWeight: 500 } }, item.nome),
                        el('span', { className: 'ls-text-muted' }, item.total + ' leads')
                      ),
                      el('div', { style: { background: '#e5e7eb', borderRadius: 20, height: 6, overflow: 'hidden' } },
                        el('div', { style: { background: '#6366f1', height: '100%', width: pct + '%', borderRadius: 20, transition: 'width .5s ease' } })
                      )
                    );
                  })
                )
          )
        ),

        // Leads recentes
        el('div', { className: 'ls-card' },
          el('div', { className: 'ls-card-header' },
            el('h3', { className: 'ls-card-title' }, 'Leads Recentes')
          ),
          data.leads_recentes.length === 0
            ? el('div', { className: 'ls-card-body' }, el('p', { className: 'ls-text-muted' }, 'Nenhum lead ainda.'))
            : el('table', { className: 'ls-table' },
                el('thead', null,
                  el('tr', null,
                    el('th', null, 'Nome'),
                    el('th', null, 'E-mail'),
                    el('th', null, 'Lista'),
                    el('th', null, 'Data')
                  )
                ),
                el('tbody', null,
                  data.leads_recentes.map(function(lead) {
                    return el('tr', { key: lead.id },
                      el('td', null, lead.nome || '—'),
                      el('td', null, lead.email),
                      el('td', null, lead.lista_nome || '—'),
                      el('td', null, formatDate(lead.created_at))
                    );
                  })
                )
              )
        )
      )
    );
  }

  function MetricCard(label, value, badgeText, badgeType) {
    return el('div', { className: 'ls-metric-card', key: label },
      el('div', { className: 'ls-metric-card__label' }, label),
      el('div', { className: 'ls-metric-card__value' }, value),
      badgeText && el('div', { className: 'ls-metric-card__badge ' + (badgeType || '') }, badgeText)
    );
  }

  /* ============================================================
     Listas Page
  ============================================================ */
  function ListasPage() {
    var [listas, setListas] = useState([]);
    var [total, setTotal] = useState(0);
    var [loading, setLoading] = useState(true);
    var [modal, setModal] = useState(null); // null | 'create' | 'edit' | 'builder' | 'delete'
    var [current, setCurrent] = useState(null);
    var [alert, setAlert] = useState(null);
    var [saving, setSaving] = useState(false);
    var [form, setForm] = useState({ nome: '', descricao: '' });
    var [subPage, setSubPage] = useState(null); // null | 'leads' | 'seq'

    function load() {
      setLoading(true);
      apiFetch('/listas?per_page=100').then(function(d) {
        setListas(d.items);
        setTotal(d.total);
        setLoading(false);
      });
    }

    useEffect(load, []);

    function openCreate() {
      setForm({ nome: '', descricao: '' });
      setCurrent(null);
      setModal('create');
    }

    function openEdit(lista) {
      setForm({ nome: lista.nome, descricao: lista.descricao || '' });
      setCurrent(lista);
      setModal('edit');
    }

    function openBuilder(lista) {
      setCurrent(lista);
      setModal('builder');
    }

    function openDelete(lista) {
      setCurrent(lista);
      setModal('delete');
    }

    function openLeadsPage(lista) {
      setCurrent(lista);
      setSubPage('leads');
    }

    function openTagsModal(lista) {
      setCurrent(lista);
      setModal('tags');
    }

    function openSeqPage(lista) {
      setCurrent(lista);
      setSubPage('seq');
    }

    function closeSubPage() {
      setSubPage(null);
    }

    async function handleSave() {
      if (!form.nome.trim()) return;
      setSaving(true);
      try {
        if (modal === 'create') {
          await apiFetch('/listas', { method: 'POST', body: form });
          showAlert('success', 'Lista criada com sucesso!');
        } else {
          await apiFetch('/listas/' + current.id, { method: 'PUT', body: form });
          showAlert('success', 'Lista atualizada!');
        }
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    async function handleDelete() {
      setSaving(true);
      try {
        await apiFetch('/listas/' + current.id, { method: 'DELETE' });
        showAlert('success', 'Lista excluída.');
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    function showAlert(type, msg) {
      setAlert({ type, msg });
      setTimeout(function() { setAlert(null); }, 4000);
    }

    // Sub-page navigation: return full-page components instead of modal
    if (subPage === 'leads' && current) {
      return el(ListLeadsPage, { lista: current, onBack: closeSubPage });
    }
    if (subPage === 'seq' && current) {
      return el(ListSeqPage, { lista: current, onBack: closeSubPage });
    }

    return el(Fragment, null,
      alert && el(Alert, { type: alert.type, onClose: function(){ setAlert(null); } }, alert.msg),

      el('div', { className: 'ls-card' },
        el('div', { className: 'ls-card-header' },
          el('h3', { className: 'ls-card-title' }, 'Listas (' + total + ')'),
          el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Nova Lista')
        ),

        loading ? el(LoadingCenter) :
        listas.length === 0 ? el(EmptyState, {
          icon: '📋',
          title: 'Nenhuma lista criada',
          desc: 'Crie sua primeira lista para começar a capturar leads.',
          action: el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Criar Lista')
        }) :
        el('div', { className: 'ls-table-wrap' },
          el('table', { className: 'ls-table' },
            el('thead', null,
              el('tr', null,
                el('th', null, 'Nome'),
                el('th', null, 'Descrição'),
                el('th', null, 'Webhook URL'),
                el('th', null, 'Shortcode'),
                el('th', null, 'Ações')
              )
            ),
            el('tbody', null,
              listas.map(function(lista) {
                var webhookUrl = SITE_URL + '/wp-json/leads/v1/webhook/' + lista.webhook_key;
                var shortcode = '[wplm_form id="' + lista.id + '"]';
                return el('tr', { key: lista.id },
                  el('td', null,
                    el('button', {
                      style: { fontWeight: 600, cursor: 'pointer', background: 'none', border: 'none', padding: 0, color: '#6366f1', textDecoration: 'underline', fontSize: 'inherit' },
                      onClick: function() { openLeadsPage(lista); }
                    }, lista.nome)
                  ),
                  el('td', null, el('span', { className: 'ls-text-muted' }, lista.descricao || '—')),
                  el('td', null,
                    el('code', {
                      style: { fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4, wordBreak: 'break-all', cursor: 'pointer' },
                      title: 'Clique para copiar',
                      onClick: function() {
                        navigator.clipboard.writeText(webhookUrl).then(function() { showAlert('success', 'Webhook URL copiada!'); }).catch(function() { showAlert('error', 'Não foi possível copiar. Copie manualmente: ' + webhookUrl); });
                      }
                    }, webhookUrl)
                  ),
                  el('td', null,
                    el('code', {
                      style: { fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4, cursor: 'pointer' },
                      title: 'Clique para copiar',
                      onClick: function() {
                        navigator.clipboard.writeText(shortcode).then(function() { showAlert('success', 'Shortcode copiado!'); }).catch(function() { showAlert('error', 'Não foi possível copiar. Copie manualmente: ' + shortcode); });
                      }
                    }, shortcode)
                  ),
                  el('td', null,
                    el('div', { className: 'ls-flex ls-gap-2', style: { flexWrap: 'wrap' } },
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openBuilder(lista); } }, '🔧 Form'),
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openSeqPage(lista); } }, 'Sequência'),
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openTagsModal(lista); } }, 'Tags'),
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openEdit(lista); } }, 'Editar'),
                      el('button', { className: 'ls-btn ls-btn-danger ls-btn-sm', onClick: function() { openDelete(lista); } }, 'Excluir')
                    )
                  )
                );
              })
            )
          )
        )
      ),

      // Create/Edit Modal
      (modal === 'create' || modal === 'edit') && el(Modal, {
        title: modal === 'create' ? 'Nova Lista' : 'Editar Lista',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', {
            className: 'ls-btn ls-btn-primary',
            onClick: handleSave,
            disabled: saving || !form.nome.trim()
          }, saving ? 'Salvando…' : 'Salvar')
        )
      },
        el(FormGroup, { label: 'Nome', required: true },
          el(Input, { value: form.nome, onChange: function(v) { setForm(Object.assign({}, form, { nome: v })); }, placeholder: 'Nome da lista' })
        ),
        el(FormGroup, { label: 'Descrição' },
          el(Textarea, { value: form.descricao, onChange: function(v) { setForm(Object.assign({}, form, { descricao: v })); }, placeholder: 'Descrição opcional' })
        )
      ),

      // Delete Confirm
      modal === 'delete' && el(Modal, {
        title: 'Excluir Lista',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-danger', onClick: handleDelete, disabled: saving }, saving ? 'Excluindo…' : 'Excluir')
        )
      },
        el('p', null, 'Tem certeza que deseja excluir a lista "', el('strong', null, current && current.nome), '"? Esta ação não pode ser desfeita.')
      ),

      // Form Builder
      modal === 'builder' && el(FormBuilderModal, {
        lista: current,
        onClose: function() { setModal(null); },
        onSaved: function() { setModal(null); showAlert('success', 'Formulário salvo!'); }
      }),

      // Tags Automation Modal
      modal === 'tags' && current && el(ListTagsModal, {
        lista: current,
        onClose: function() { setModal(null); },
        onSaved: function() { setModal(null); showAlert('success', 'Tags automáticas salvas!'); }
      })
    );
  }

  /* ============================================================
     List Leads Modal – shows paginated leads for a specific list
  ============================================================ */
  function ListLeadsModal({ lista, onClose }) {
    var [leads, setLeads] = useState([]);
    var [total, setTotal] = useState(0);
    var [page, setPage] = useState(1);
    var [search, setSearch] = useState('');
    var [loading, setLoading] = useState(true);

    useEffect(function() {
      setLoading(true);
      var params = '?lista_id=' + lista.id + '&page=' + page + '&per_page=20';
      if (search) params += '&search=' + encodeURIComponent(search);
      apiFetch('/leads' + params).then(function(d) {
        setLeads(d.items);
        setTotal(d.total);
        setLoading(false);
      }).catch(function() { setLoading(false); });
    }, [lista.id, page, search]);

    return el(Modal, {
      title: 'Leads da lista: ' + lista.nome + ' (' + total + ')',
      onClose: onClose,
    },
      el('div', { style: { marginBottom: 12 } },
        el('div', { className: 'ls-search-bar' },
          el('span', { className: 'ls-search-icon' }, '🔍'),
          el('input', {
            className: 'ls-input',
            style: { paddingLeft: 30, width: '100%' },
            placeholder: 'Pesquisar leads...',
            value: search,
            onChange: function(e) { setSearch(e.target.value); setPage(1); }
          })
        )
      ),
      loading ? el(LoadingCenter) :
      leads.length === 0
        ? el(EmptyState, { icon: '👤', title: 'Nenhum lead', desc: 'Esta lista não possui leads ainda.' })
        : el('div', null,
            el('table', { className: 'ls-table' },
              el('thead', null,
                el('tr', null,
                  el('th', null, 'Nome'),
                  el('th', null, 'E-mail'),
                  el('th', null, 'Telefone'),
                  el('th', null, 'Data')
                )
              ),
              el('tbody', null,
                leads.map(function(lead) {
                  return el('tr', { key: lead.id },
                    el('td', null, lead.nome || el('span', { className: 'ls-text-muted' }, '—')),
                    el('td', null, lead.email),
                    el('td', null, lead.telefone || el('span', { className: 'ls-text-muted' }, '—')),
                    el('td', null, el('span', { className: 'ls-text-muted' }, formatDate(lead.created_at)))
                  );
                })
              )
            ),
            el(Pagination, { page: page, total: total, perPage: 20, onChange: setPage })
          )
    );
  }

  /* ============================================================
     List Tags Modal – select tags to auto-add when lead enters list
  ============================================================ */
  function ListTagsModal({ lista, onClose, onSaved }) {
    var [tags, setTags] = useState([]);
    var [selectedTags, setSelectedTags] = useState([]);
    var [loading, setLoading] = useState(true);
    var [saving, setSaving] = useState(false);
    var [existingAutoId, setExistingAutoId] = useState(null);

    useEffect(function() {
      Promise.all([
        apiFetch('/tags'),
        apiFetch('/automacoes')
      ]).then(function(results) {
        var allTags = results[0];
        var autos   = results[1];
        setTags(allTags);
        var existing = autos.find(function(a) {
          return a.trigger_key === 'lead_entered_list' &&
                 a.conditions_json && a.conditions_json.lista_id == lista.id;
        });
        if (existing) {
          setExistingAutoId(existing.id);
          setSelectedTags(
            (existing.acoes_json || [])
              .filter(function(ac) { return ac.tipo === 'add_tag'; })
              .map(function(ac) { return ac.tag_id; })
          );
        }
        setLoading(false);
      }).catch(function() { setLoading(false); });
    }, [lista.id]);

    function toggleTag(tagId) {
      setSelectedTags(function(prev) {
        var idx = prev.indexOf(tagId);
        return idx >= 0 ? prev.filter(function(t) { return t !== tagId; }) : prev.concat([tagId]);
      });
    }

    async function handleSave() {
      setSaving(true);
      try {
        var payload = {
          nome:       'Tags automáticas – ' + lista.nome,
          trigger:    'lead_entered_list',
          acoes:      selectedTags.map(function(tagId) { return { tipo: 'add_tag', tag_id: tagId }; }),
          conditions: { lista_id: lista.id },
          ativo:      1,
        };
        if (existingAutoId) {
          await apiFetch('/automacoes/' + existingAutoId, { method: 'PUT', body: payload });
        } else {
          await apiFetch('/automacoes', { method: 'POST', body: payload });
        }
        onSaved();
      } catch(e) {
        alert(e.message);
      } finally {
        setSaving(false);
      }
    }

    return el(Modal, {
      title: 'Tags automáticas – ' + lista.nome,
      onClose: onClose,
      footer: el(Fragment, null,
        el('button', { className: 'ls-btn ls-btn-outline', onClick: onClose }, 'Cancelar'),
        el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving }, saving ? 'Salvando…' : 'Salvar')
      )
    },
      loading ? el(LoadingCenter) :
      tags.length === 0
        ? el('p', { className: 'ls-text-muted' }, 'Nenhuma etiqueta criada ainda. Crie etiquetas primeiro.')
        : el('div', null,
            el('p', { style: { marginBottom: 12, fontSize: 13, color: '#374151' } },
              'Selecione as etiquetas adicionadas automaticamente quando um lead entrar nesta lista:'
            ),
            el('div', { style: { display: 'flex', flexWrap: 'wrap', gap: 10 } },
              tags.map(function(tag) {
                var selected = selectedTags.indexOf(tag.id) >= 0;
                return el('div', {
                  key: tag.id,
                  onClick: function() { toggleTag(tag.id); },
                  style: {
                    display: 'flex', alignItems: 'center', gap: 6,
                    background: selected ? tag.cor + '33' : '#f9fafb',
                    border: '2px solid ' + (selected ? tag.cor : '#e5e7eb'),
                    borderRadius: 20, padding: '6px 14px', cursor: 'pointer',
                    transition: 'all .15s'
                  }
                },
                  el('span', { style: { width: 10, height: 10, borderRadius: '50%', background: tag.cor, display: 'inline-block', flexShrink: 0 } }),
                  el('span', { style: { fontWeight: 600, color: tag.cor, fontSize: 13 } }, tag.nome),
                  selected && el('span', { style: { color: tag.cor, fontSize: 12, marginLeft: 4 } }, '✓')
                );
              })
            )
          )
    );
  }

  /* ============================================================
     List Sequence Modal – manage per-list email sequence steps
  ============================================================ */
  function ListSeqModal({ lista, onClose, onSaved }) {
    var [steps, setSteps] = useState([]);
    var [loading, setLoading] = useState(true);
    var [saving, setSaving] = useState(false);

    useEffect(function() {
      apiFetch('/email-sequences/' + lista.id).then(function(d) {
        setSteps(d.steps || []);
        setLoading(false);
      }).catch(function() {
        setSteps([]);
        setLoading(false);
      });
    }, [lista.id]);

    function addStep() {
      var isFirst = steps.length === 0;
      setSteps(function(prev) {
        return prev.concat([{
          assunto:        '',
          corpo_html:     '',
          delay_value:    isFirst ? 0 : 1,
          delay_unit:     'hours',
          wait_for_open:  false,
          max_wait_value: 48,
          max_wait_unit:  'hours',
        }]);
      });
    }

    function removeStep(idx) {
      setSteps(function(prev) { return prev.filter(function(_, i) { return i !== idx; }); });
    }

    function updateStep(idx, patch) {
      setSteps(function(prev) {
        return prev.map(function(s, i) { return i === idx ? Object.assign({}, s, patch) : s; });
      });
    }

    async function handleSave() {
      setSaving(true);
      try {
        await apiFetch('/email-sequences/' + lista.id, {
          method: 'PUT',
          body: { steps: steps },
        });
        onSaved();
      } catch(e) {
        alert(e.message);
      } finally {
        setSaving(false);
      }
    }

    var delayUnitOptions = [{ value: 'hours', label: 'hora(s)' }, { value: 'days', label: 'dia(s)' }];

    return el(Modal, {
      title: 'Sequência de E-mails – ' + lista.nome,
      onClose: onClose,
      footer: el(Fragment, null,
        el('button', { className: 'ls-btn ls-btn-outline', onClick: onClose }, 'Cancelar'),
        el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving }, saving ? 'Salvando…' : 'Salvar Sequência')
      )
    },
      loading ? el(LoadingCenter) :
      el('div', null,
        el('div', { style: { marginBottom: 12 } },
          el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: addStep }, '+ Adicionar Passo')
        ),
        steps.length === 0
          ? el('p', { className: 'ls-text-muted' }, 'Nenhum passo configurado. Clique em "+ Adicionar Passo" para começar.')
          : steps.map(function(step, idx) {
              var isFirst = idx === 0;
              return el('div', {
                key: idx,
                style: { border: '1px solid #e5e7eb', borderRadius: 8, padding: 16, marginBottom: 12, background: '#fafafa' }
              },
                el('div', { className: 'ls-flex', style: { justifyContent: 'space-between', marginBottom: 10 } },
                  el('div', { style: { fontWeight: 600, fontSize: 14 } },
                    isFirst ? '📧 Passo 1 — Envio imediato ao entrar na lista' : '📧 Passo ' + (idx + 1)
                  ),
                  el('button', { className: 'ls-btn ls-btn-danger ls-btn-sm', onClick: function() { removeStep(idx); } }, '×')
                ),
                el(FormGroup, { label: 'Assunto' },
                  el(Input, {
                    value: step.assunto,
                    onChange: function(v) { updateStep(idx, { assunto: v }); },
                    placeholder: 'Assunto do e-mail'
                  })
                ),
                el(FormGroup, { label: 'Corpo do e-mail (HTML)' },
                  el(Textarea, {
                    value: step.corpo_html,
                    onChange: function(v) { updateStep(idx, { corpo_html: v }); },
                    placeholder: '<p>Olá, seja bem-vindo!</p>',
                    rows: 5
                  })
                ),
                !isFirst && el('div', { className: 'ls-grid-2' },
                  el(FormGroup, { label: 'Enviar após' },
                    el('div', { className: 'ls-flex ls-gap-2' },
                      el('input', {
                        className: 'ls-input',
                        type: 'number',
                        min: 0,
                        value: step.delay_value,
                        onChange: function(e) { updateStep(idx, { delay_value: Math.max(0, parseInt(e.target.value) || 0) }); },
                        style: { width: 70 }
                      }),
                      el(Select, {
                        value: step.delay_unit,
                        onChange: function(v) { updateStep(idx, { delay_unit: v }); },
                        options: delayUnitOptions
                      })
                    )
                  ),
                  el(FormGroup, { label: 'Aguardar abertura do e-mail anterior?' },
                    el(Select, {
                      value: step.wait_for_open ? '1' : '0',
                      onChange: function(v) { updateStep(idx, { wait_for_open: v === '1' }); },
                      options: [{ value: '0', label: 'Não' }, { value: '1', label: 'Sim (com fallback)' }]
                    })
                  )
                ),
                !isFirst && step.wait_for_open && el(FormGroup, { label: 'Se não abrir, enviar mesmo assim após' },
                  el('div', { className: 'ls-flex ls-gap-2' },
                    el('input', {
                      className: 'ls-input',
                      type: 'number',
                      min: 1,
                      value: step.max_wait_value,
                      onChange: function(e) { updateStep(idx, { max_wait_value: Math.max(1, parseInt(e.target.value) || 1) }); },
                      style: { width: 70 }
                    }),
                    el(Select, {
                      value: step.max_wait_unit,
                      onChange: function(v) { updateStep(idx, { max_wait_unit: v }); },
                      options: delayUnitOptions
                    })
                  )
                )
              );
            })
      )
    );
  }

  /* ============================================================
     Variables sidebar – clickable placeholders for email bodies
  ============================================================ */
  var EMAIL_VARIABLES = [
    { label: 'Nome',             value: '{{nome}}',            desc: 'Nome completo do lead' },
    { label: 'E-mail',           value: '{{email}}',           desc: 'E-mail do lead' },
    { label: 'Telefone',         value: '{{telefone}}',        desc: 'Telefone do lead' },
    { label: 'Nome da Lista',    value: '{{lista_nome}}',      desc: 'Nome da lista do lead' },
    { label: 'URL do Site',      value: '{{site_url}}',        desc: 'URL do seu site WordPress' },
    { label: 'Link Descadastro', value: '{{unsubscribe_url}}', desc: 'Link de descadastro automático' },
    { label: 'Data Atual',       value: '{{data}}',            desc: 'Data no momento do envio' },
    { label: 'Primeiro Nome',    value: '{{primeiro_nome}}',   desc: 'Primeiro nome do lead' },
  ];

  function VariablesSidebar({ onInsert, copyFeedback }) {
    return el('div', { className: 'ls-seq-sidebar' },
      el('div', { className: 'ls-seq-sidebar__header' }, '{ } Variáveis'),
      el('p', { className: 'ls-seq-sidebar__desc' },
        'Clique para inserir no campo ativo ou copiar para o clipboard.'
      ),
      el('div', { className: 'ls-var-list' },
        EMAIL_VARIABLES.map(function(v) {
          var isCopied = copyFeedback === v.value;
          return el('div', {
            key: v.value,
            className: 'ls-var-item' + (isCopied ? ' ls-var-item--copied' : ''),
            onClick: function() { onInsert(v.value); },
            title: v.desc,
          },
            el('code', { className: 'ls-var-item__code' }, v.value),
            el('span', { className: 'ls-var-item__label' }, isCopied ? '✓ Copiado!' : v.label)
          );
        })
      )
    );
  }

  /* ============================================================
     List Leads Page – full-page view of leads for a specific list
  ============================================================ */
  function ListLeadsPage({ lista, onBack }) {
    var [leads, setLeads] = useState([]);
    var [total, setTotal] = useState(0);
    var [page, setPage] = useState(1);
    var [search, setSearch] = useState('');
    var [loading, setLoading] = useState(true);

    useEffect(function() {
      setLoading(true);
      var params = '?lista_id=' + lista.id + '&page=' + page + '&per_page=20';
      if (search) params += '&search=' + encodeURIComponent(search);
      apiFetch('/leads' + params).then(function(d) {
        setLeads(d.items);
        setTotal(d.total);
        setLoading(false);
      }).catch(function() { setLoading(false); });
    }, [lista.id, page, search]);

    return el(Fragment, null,
      el('div', { className: 'ls-page-header' },
        el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: onBack }, '← Voltar'),
        el('h2', { className: 'ls-page-header__title' }, '👤 Leads – ' + lista.nome)
      ),

      el('div', { className: 'ls-card' },
        el('div', { className: 'ls-card-header' },
          el('h3', { className: 'ls-card-title' }, 'Leads (' + total + ')'),
          el('div', { className: 'ls-search-bar' },
            el('span', { className: 'ls-search-icon' }, '🔍'),
            el('input', {
              className: 'ls-input',
              style: { paddingLeft: 30, width: 240 },
              placeholder: 'Pesquisar leads...',
              value: search,
              onChange: function(e) { setSearch(e.target.value); setPage(1); }
            })
          )
        ),
        loading ? el(LoadingCenter) :
        leads.length === 0
          ? el(EmptyState, { icon: '👤', title: 'Nenhum lead', desc: 'Esta lista não possui leads ainda.' })
          : el('div', null,
              el('div', { className: 'ls-table-wrap' },
                el('table', { className: 'ls-table' },
                  el('thead', null,
                    el('tr', null,
                      el('th', null, 'Nome'),
                      el('th', null, 'E-mail'),
                      el('th', null, 'Telefone'),
                      el('th', null, 'Data')
                    )
                  ),
                  el('tbody', null,
                    leads.map(function(lead) {
                      return el('tr', { key: lead.id },
                        el('td', null, lead.nome || el('span', { className: 'ls-text-muted' }, '—')),
                        el('td', null, lead.email),
                        el('td', null, lead.telefone || el('span', { className: 'ls-text-muted' }, '—')),
                        el('td', null, el('span', { className: 'ls-text-muted' }, formatDate(lead.created_at)))
                      );
                    })
                  )
                )
              ),
              el(Pagination, { page: page, total: total, perPage: 20, onChange: setPage })
            )
      )
    );
  }

  /* ============================================================
     List Seq Page – full-page email sequence editor with sidebar
  ============================================================ */
  function ListSeqPage({ lista, onBack }) {
    var [steps, setSteps] = useState([]);
    var [savedSteps, setSavedSteps] = useState([]);
    var [loading, setLoading] = useState(true);
    var [saving, setSaving] = useState(false);
    var [alert, setAlert] = useState(null);
    var [copyFeedback, setCopyFeedback] = useState(null);
    var [openPreviews, setOpenPreviews] = useState({});
    var activeBodyRef = useRef(null); // { idx, el }

    useEffect(function() {
      apiFetch('/email-sequences/' + lista.id).then(function(d) {
        var loaded = d.steps || [];
        setSteps(loaded);
        setSavedSteps(loaded);
        setLoading(false);
      }).catch(function() {
        setSteps([]);
        setSavedSteps([]);
        setLoading(false);
      });
    }, [lista.id]);

    function showAlert(type, msg) {
      setAlert({ type, msg });
      setTimeout(function() { setAlert(null); }, 4000);
    }

    function addStep() {
      var isFirst = steps.length === 0;
      setSteps(function(prev) {
        return prev.concat([{
          assunto:        '',
          corpo_html:     '',
          delay_value:    isFirst ? 0 : 1,
          delay_unit:     'hours',
          wait_for_open:  false,
          max_wait_value: 48,
          max_wait_unit:  'hours',
        }]);
      });
    }

    function removeStep(idx) {
      setSteps(function(prev) { return prev.filter(function(_, i) { return i !== idx; }); });
      setOpenPreviews(function(prev) {
        var next = Object.assign({}, prev);
        delete next[idx];
        return next;
      });
      if (activeBodyRef.current && activeBodyRef.current.idx === idx) {
        activeBodyRef.current = null;
      }
    }

    function updateStep(idx, patch) {
      setSteps(function(prev) {
        return prev.map(function(s, i) { return i === idx ? Object.assign({}, s, patch) : s; });
      });
    }

    async function handleSave() {
      setSaving(true);
      try {
        await apiFetch('/email-sequences/' + lista.id, {
          method: 'PUT',
          body: { steps: steps },
        });
        setSavedSteps(steps.slice());
        showAlert('success', 'Sequência salva com sucesso!');
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    function handleVariableClick(varValue) {
      if (activeBodyRef.current) {
        var info = activeBodyRef.current;
        var domEl = info.el;
        var start = domEl.selectionStart;
        var end = domEl.selectionEnd;
        var current = steps[info.idx] ? steps[info.idx].corpo_html : '';
        var newVal = current.slice(0, start) + varValue + current.slice(end);
        var newCursor = start + varValue.length;
        updateStep(info.idx, { corpo_html: newVal });
        setTimeout(function() {
          domEl.focus();
          domEl.setSelectionRange(newCursor, newCursor);
        }, 0);
      }
      navigator.clipboard.writeText(varValue).then(function() {
        setCopyFeedback(varValue);
        setTimeout(function() { setCopyFeedback(null); }, 2000);
      }).catch(function() {
        showAlert('error', 'Não foi possível copiar. Insira a variável manualmente no campo de texto.');
      });
    }

    function togglePreview(idx) {
      setOpenPreviews(function(prev) {
        return Object.assign({}, prev, { [idx]: !prev[idx] });
      });
    }

    function scrollToStep(idx) {
      var el = document.getElementById('ls-seq-step-' + idx);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    var delayUnitOptions = [{ value: 'hours', label: 'hora(s)' }, { value: 'days', label: 'dia(s)' }];

    return el(Fragment, null,
      el('div', { className: 'ls-page-header' },
        el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: onBack }, '← Voltar'),
        el('h2', { className: 'ls-page-header__title' }, '📧 Sequência de E-mails – ' + lista.nome)
      ),

      alert && el(Alert, { type: alert.type, onClose: function() { setAlert(null); } }, alert.msg),

      loading ? el(LoadingCenter) :
      el('div', { className: 'ls-seq-layout' },

        // ── Main editor column ──────────────────────────────────
        el('div', { className: 'ls-seq-main' },
          el('div', { className: 'ls-card' },
            el('div', { className: 'ls-card-header' },
              el('h3', { className: 'ls-card-title' }, 'Passos da Sequência'),
              el('div', { className: 'ls-flex ls-gap-2' },
                el('button', { className: 'ls-btn ls-btn-outline', onClick: addStep }, '+ Adicionar Passo'),
                el('button', {
                  className: 'ls-btn ls-btn-primary',
                  onClick: handleSave,
                  disabled: saving,
                }, saving ? 'Salvando…' : '💾 Salvar Sequência')
              )
            ),
            el('div', { className: 'ls-card-body' },
              steps.length === 0
                ? el(EmptyState, {
                    icon: '📧',
                    title: 'Nenhum passo configurado',
                    desc: 'Clique em "+ Adicionar Passo" para começar.',
                  })
                : steps.map(function(step, idx) {
                    var isFirst = idx === 0;
                    var showPreview = !!openPreviews[idx];
                    return el('div', {
                      key: idx,
                      id: 'ls-seq-step-' + idx,
                      style: { border: '1px solid #e5e7eb', borderRadius: 8, padding: 16, marginBottom: 12, background: '#fafafa' }
                    },
                      // Step header
                      el('div', { className: 'ls-flex', style: { justifyContent: 'space-between', marginBottom: 10 } },
                        el('div', { style: { fontWeight: 600, fontSize: 14 } },
                          isFirst ? '📧 Passo 1 — Envio imediato ao entrar na lista' : '📧 Passo ' + (idx + 1)
                        ),
                        el('button', {
                          className: 'ls-btn ls-btn-danger ls-btn-sm',
                          onClick: function() { removeStep(idx); }
                        }, '×')
                      ),
                      // Assunto
                      el(FormGroup, { label: 'Assunto' },
                        el(Input, {
                          value: step.assunto,
                          onChange: function(v) { updateStep(idx, { assunto: v }); },
                          placeholder: 'Assunto do e-mail'
                        })
                      ),
                      // Corpo HTML – direct element to track focus for variable insertion
                      el(FormGroup, { label: 'Corpo do e-mail (HTML)' },
                        el('textarea', {
                          className: 'ls-textarea',
                          value: step.corpo_html,
                          rows: 6,
                          placeholder: '<p>Olá {{nome}}, seja bem-vindo!</p>',
                          style: { fontFamily: 'monospace', fontSize: 12 },
                          onChange: function(e) { updateStep(idx, { corpo_html: e.target.value }); },
                          onFocus: function(e) { activeBodyRef.current = { idx: idx, el: e.target }; },
                        }),
                        el('button', {
                          className: 'ls-btn ls-btn-outline ls-btn-sm',
                          style: { marginTop: 6 },
                          onClick: function() { togglePreview(idx); },
                        }, showPreview ? '🙈 Ocultar Prévia' : '👁 Ver Prévia do E-mail')
                      ),
                      // Email HTML preview
                      showPreview && el('div', { className: 'ls-email-preview' },
                        el('div', { className: 'ls-email-preview__header' },
                          'Prévia: ' + (step.assunto || '(sem assunto)')
                        ),
                        el('div', {
                          className: 'ls-email-preview__body',
                          dangerouslySetInnerHTML: {
                            __html: step.corpo_html || '<p style="color:#9ca3af;font-style:italic">Corpo do e-mail vazio</p>'
                          }
                        })
                      ),
                      // Delay settings (not first step)
                      !isFirst && el('div', { className: 'ls-grid-2' },
                        el(FormGroup, { label: 'Enviar após' },
                          el('div', { className: 'ls-flex ls-gap-2' },
                            el('input', {
                              className: 'ls-input',
                              type: 'number',
                              min: 0,
                              value: step.delay_value,
                              onChange: function(e) { updateStep(idx, { delay_value: Math.max(0, parseInt(e.target.value) || 0) }); },
                              style: { width: 70 }
                            }),
                            el(Select, {
                              value: step.delay_unit,
                              onChange: function(v) { updateStep(idx, { delay_unit: v }); },
                              options: delayUnitOptions
                            })
                          )
                        ),
                        el(FormGroup, { label: 'Aguardar abertura do e-mail anterior?' },
                          el(Select, {
                            value: step.wait_for_open ? '1' : '0',
                            onChange: function(v) { updateStep(idx, { wait_for_open: v === '1' }); },
                            options: [{ value: '0', label: 'Não' }, { value: '1', label: 'Sim (com fallback)' }]
                          })
                        )
                      ),
                      !isFirst && step.wait_for_open && el(FormGroup, { label: 'Se não abrir, enviar mesmo assim após' },
                        el('div', { className: 'ls-flex ls-gap-2' },
                          el('input', {
                            className: 'ls-input',
                            type: 'number',
                            min: 1,
                            value: step.max_wait_value,
                            onChange: function(e) { updateStep(idx, { max_wait_value: Math.max(1, parseInt(e.target.value) || 1) }); },
                            style: { width: 70 }
                          }),
                          el(Select, {
                            value: step.max_wait_unit,
                            onChange: function(v) { updateStep(idx, { max_wait_unit: v }); },
                            options: delayUnitOptions
                          })
                        )
                      )
                    );
                  })
            )
          ),

          // ── Saved Steps section ─────────────────────────────
          savedSteps.length > 0 && el('div', { className: 'ls-card ls-mt-4' },
            el('div', { className: 'ls-card-header' },
              el('h3', { className: 'ls-card-title' }, '✅ Sequência Salva (' + savedSteps.length + ' passo(s))')
            ),
            el('div', { className: 'ls-card-body' },
              el('p', { className: 'ls-text-muted', style: { marginBottom: 12 } },
                'Clique em um passo para ir até o editor.'
              ),
              el('div', { className: 'ls-saved-steps' },
                savedSteps.map(function(step, idx) {
                  return el('div', {
                    key: idx,
                    className: 'ls-saved-step-card',
                    onClick: function() { scrollToStep(idx); },
                  },
                    el('div', { className: 'ls-saved-step-card__num' }, 'Passo ' + (idx + 1)),
                    el('div', { className: 'ls-saved-step-card__subject' },
                      step.assunto || el('span', { className: 'ls-text-muted' }, '(sem assunto)')
                    ),
                    idx > 0 && el('div', { className: 'ls-saved-step-card__delay' },
                      '⏱ Enviar após ' + step.delay_value + ' ' + (step.delay_unit === 'hours' ? 'hora(s)' : 'dia(s)')
                    )
                  );
                })
              )
            )
          )
        ),

        // ── Variables sidebar ───────────────────────────────────
        el(VariablesSidebar, { onInsert: handleVariableClick, copyFeedback: copyFeedback })
      )
    );
  }

  /* ============================================================
     Form Builder Modal
  ============================================================ */
  var FIELD_TYPES = [
    { type: 'text',     label: 'Texto',    icon: '𝐓' },
    { type: 'email',    label: 'E-mail',   icon: '✉' },
    { type: 'tel',      label: 'Telefone', icon: '☎' },
    { type: 'textarea', label: 'Textarea', icon: '≡' },
    { type: 'select',   label: 'Select',   icon: '▾' },
    { type: 'checkbox', label: 'Checkbox', icon: '☑' },
    { type: 'hidden',   label: 'Oculto',   icon: '⊝' },
    { type: 'date',     label: 'Data',     icon: '📅' },
    { type: 'number',   label: 'Número',   icon: '#' },
  ];

  function FormBuilderModal({ lista, onClose, onSaved }) {
    var [fields, setFields] = useState([]);
    var [loading, setLoading] = useState(true);
    var [saving, setSaving] = useState(false);
    var [editingIdx, setEditingIdx] = useState(null);
    var [dragOverIdx, setDragOverIdx] = useState(null);
    var [dragSrcIdx, setDragSrcIdx] = useState(null);

    useEffect(function() {
      apiFetch('/listas/' + lista.id).then(function(d) {
        var schema = d.form_schema_json;
        var existingFields = (schema && schema.fields) ? schema.fields : [
          { type: 'text',  label: 'Nome',     name: 'nome',     required: true,  placeholder: '' },
          { type: 'email', label: 'E-mail',   name: 'email',    required: true,  placeholder: '' },
          { type: 'tel',   label: 'Telefone', name: 'telefone', required: false, placeholder: '' },
        ];
        setFields(existingFields);
        setLoading(false);
      });
    }, [lista.id]);

    function addField(type) {
      var label = FIELD_TYPES.find(function(f) { return f.type === type; }).label;
      var newField = {
        type: type,
        label: label,
        name: type + '_' + Date.now(),
        required: false,
        placeholder: '',
        options: type === 'select' ? ['Opção 1', 'Opção 2'] : undefined,
      };
      setFields(function(prev) { return prev.concat([newField]); });
    }

    function removeField(idx) {
      setFields(function(prev) { return prev.filter(function(_, i) { return i !== idx; }); });
      if (editingIdx === idx) setEditingIdx(null);
    }

    function updateField(idx, patch) {
      setFields(function(prev) {
        return prev.map(function(f, i) { return i === idx ? Object.assign({}, f, patch) : f; });
      });
    }

    function handleDragStart(idx) { setDragSrcIdx(idx); }
    function handleDragOver(e, idx) { e.preventDefault(); setDragOverIdx(idx); }
    function handleDrop(idx) {
      if (dragSrcIdx === null || dragSrcIdx === idx) { setDragSrcIdx(null); setDragOverIdx(null); return; }
      var newFields = fields.slice();
      var moved = newFields.splice(dragSrcIdx, 1)[0];
      newFields.splice(idx, 0, moved);
      setFields(newFields);
      setDragSrcIdx(null);
      setDragOverIdx(null);
    }

    function handlePaletteDrop(e) {
      e.preventDefault();
      var type = e.dataTransfer.getData('fieldType');
      if (type) addField(type);
      setDragOverIdx(null);
    }

    async function handleSave() {
      setSaving(true);
      try {
        await apiFetch('/listas/' + lista.id, {
          method: 'PUT',
          body: { form_schema_json: { fields: fields } }
        });
        onSaved();
      } catch(e) {
        alert(e.message);
      } finally {
        setSaving(false);
      }
    }

    var modalContent = loading ? el(LoadingCenter) :
      el('div', { className: 'ls-builder-layout' },
        // Palette
        el('div', { className: 'ls-builder-palette' },
          el('h3', null, 'Componentes'),
          FIELD_TYPES.map(function(ft) {
            return el('div', {
              key: ft.type,
              className: 'ls-palette-item',
              draggable: true,
              onDragStart: function(e) { e.dataTransfer.setData('fieldType', ft.type); }
            }, el('span', null, ft.icon), ft.label);
          })
        ),

        // Canvas
        el('div', {
          className: 'ls-builder-canvas' + (dragOverIdx === 'canvas' ? ' drag-over' : ''),
          onDragOver: function(e) { e.preventDefault(); setDragOverIdx('canvas'); },
          onDrop: handlePaletteDrop,
          onDragLeave: function() { setDragOverIdx(null); }
        },
          fields.length === 0
            ? el('div', { style: { textAlign: 'center', color: '#9ca3af', padding: '40px 20px' } },
                el('div', { style: { fontSize: 32, marginBottom: 8 } }, '↙'),
                el('div', null, 'Arraste componentes aqui para montar o formulário')
              )
            : fields.map(function(field, idx) {
                var isEditing = editingIdx === idx;
                var isDragOver = dragOverIdx === idx && dragSrcIdx !== idx;
                return el('div', {
                  key: idx,
                  className: 'ls-builder-field-item' + (dragSrcIdx === idx ? ' drag-source' : '') + (isDragOver ? ' drag-over' : ''),
                  draggable: true,
                  onDragStart: function() { handleDragStart(idx); },
                  onDragOver: function(e) { handleDragOver(e, idx); },
                  onDrop: function() { handleDrop(idx); },
                  style: { flexDirection: 'column', alignItems: 'stretch' }
                },
                  el('div', { className: 'ls-flex ls-gap-2' },
                    el('span', { className: 'ls-builder-field-drag', style: { cursor: 'grab' } }, '⠿'),
                    el('div', { className: 'ls-builder-field-info' },
                      el('div', { className: 'ls-builder-field-label' }, field.label || field.type),
                      el('div', { className: 'ls-builder-field-type' }, field.type + (field.required ? ' • obrigatório' : ''))
                    ),
                    el('div', { className: 'ls-builder-field-actions' },
                      el('button', {
                        className: 'ls-btn ls-btn-outline ls-btn-sm',
                        onClick: function() { setEditingIdx(isEditing ? null : idx); }
                      }, isEditing ? '✓' : '✏'),
                      el('button', {
                        className: 'ls-btn ls-btn-danger ls-btn-sm',
                        onClick: function() { removeField(idx); }
                      }, '×')
                    )
                  ),
                  isEditing && el('div', { style: { marginTop: 10, padding: '12px', background: '#f9fafb', borderRadius: 6, border: '1px solid #e5e7eb' } },
                    el('div', { className: 'ls-grid-2', style: { marginBottom: 8 } },
                      el(FormGroup, { label: 'Label' },
                        el(Input, { value: field.label, onChange: function(v) { updateField(idx, { label: v }); } })
                      ),
                      el(FormGroup, { label: 'Name (campo)' },
                        el(Input, { value: field.name, onChange: function(v) { updateField(idx, { name: v }); } })
                      )
                    ),
                    el('div', { className: 'ls-grid-2' },
                      el(FormGroup, { label: 'Placeholder' },
                        el(Input, { value: field.placeholder || '', onChange: function(v) { updateField(idx, { placeholder: v }); } })
                      ),
                      el(FormGroup, { label: 'Obrigatório' },
                        el(Select, {
                          value: field.required ? '1' : '0',
                          onChange: function(v) { updateField(idx, { required: v === '1' }); },
                          options: [{ value: '0', label: 'Não' }, { value: '1', label: 'Sim' }]
                        })
                      )
                    ),
                    field.type === 'select' && el(FormGroup, { label: 'Opções (uma por linha)' },
                      el(Textarea, {
                        value: (field.options || []).join('\n'),
                        onChange: function(v) { updateField(idx, { options: v.split('\n').map(function(s) { return s.trim(); }).filter(Boolean) }); },
                        rows: 3
                      })
                    )
                  )
                );
              })
        )
      );

    return el(Modal, {
      title: 'Form Builder – ' + lista.nome,
      onClose: onClose,
      footer: el(Fragment, null,
        el('button', { className: 'ls-btn ls-btn-outline', onClick: onClose }, 'Cancelar'),
        el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving }, saving ? 'Salvando…' : 'Salvar Formulário')
      )
    }, modalContent);
  }

  /* ============================================================
     Leads Page
  ============================================================ */
  function LeadsPage() {
    var [leads, setLeads] = useState([]);
    var [total, setTotal] = useState(0);
    var [loading, setLoading] = useState(true);
    var [page, setPage] = useState(1);
    var [search, setSearch] = useState('');
    var [listaId, setListaId] = useState('');
    var [listas, setListas] = useState([]);
    var [modal, setModal] = useState(null);
    var [current, setCurrent] = useState(null);
    var [alert, setAlert] = useState(null);
    var [saving, setSaving] = useState(false);

    useEffect(function() {
      apiFetch('/listas?per_page=100').then(function(d) { setListas(d.items); });
    }, []);

    function load() {
      setLoading(true);
      var params = '?page=' + page + '&per_page=20';
      if (search) params += '&search=' + encodeURIComponent(search);
      if (listaId) params += '&lista_id=' + listaId;
      apiFetch('/leads' + params).then(function(d) {
        setLeads(d.items);
        setTotal(d.total);
        setLoading(false);
      });
    }

    useEffect(load, [page, search, listaId]);

    function openCreate() {
      setCurrent(null);
      setModal('create');
    }

    function openView(lead) {
      setCurrent(lead);
      setModal('view');
    }

    function openEdit(lead) {
      setCurrent(lead);
      setModal('edit');
    }

    function openDelete(lead) {
      setCurrent(lead);
      setModal('delete');
    }

    function showAlert(type, msg) {
      setAlert({ type, msg });
      setTimeout(function() { setAlert(null); }, 4000);
    }

    async function handleDelete() {
      setSaving(true);
      try {
        await apiFetch('/leads/' + current.id, { method: 'DELETE' });
        showAlert('success', 'Lead excluído.');
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    var listaOptions = [{ value: '', label: 'Todas as listas' }].concat(
      listas.map(function(l) { return { value: String(l.id), label: l.nome }; })
    );

    return el(Fragment, null,
      alert && el(Alert, { type: alert.type, onClose: function(){ setAlert(null); } }, alert.msg),

      el('div', { className: 'ls-card' },
        el('div', { className: 'ls-card-header' },
          el('h3', { className: 'ls-card-title' }, 'Leads (' + total + ')'),
          el('div', { className: 'ls-flex ls-gap-2' },
            el('div', { className: 'ls-search-bar' },
              el('span', { className: 'ls-search-icon' }, '🔍'),
              el('input', {
                className: 'ls-input',
                style: { width: 200, paddingLeft: 30 },
                placeholder: 'Pesquisar...',
                value: search,
                onChange: function(e) { setSearch(e.target.value); setPage(1); }
              })
            ),
            el(Select, {
              value: listaId,
              onChange: function(v) { setListaId(v); setPage(1); },
              options: listaOptions
            }),
            el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Novo Lead')
          )
        ),

        loading ? el(LoadingCenter) :
        leads.length === 0 ? el(EmptyState, {
          icon: '👤',
          title: 'Nenhum lead encontrado',
          desc: search ? 'Tente outros termos de busca.' : 'Capture seu primeiro lead via formulário, webhook ou manualmente.',
          action: el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Adicionar Lead')
        }) :
        el('div', { className: 'ls-table-wrap' },
          el('table', { className: 'ls-table' },
            el('thead', null,
              el('tr', null,
                el('th', null, 'Nome'),
                el('th', null, 'E-mail'),
                el('th', null, 'Telefone'),
                el('th', null, 'Lista'),
                el('th', null, 'Etiquetas'),
                el('th', null, 'Origem'),
                el('th', null, 'Data'),
                el('th', null, 'Ações')
              )
            ),
            el('tbody', null,
              leads.map(function(lead) {
                return el('tr', { key: lead.id },
                  el('td', null, lead.nome || el('span', { className: 'ls-text-muted' }, '—')),
                  el('td', null, lead.email),
                  el('td', null, lead.telefone || el('span', { className: 'ls-text-muted' }, '—')),
                  el('td', null, lead.lista_nome || el('span', { className: 'ls-text-muted' }, '—')),
                  el('td', null,
                    el('div', { className: 'ls-flex', style: { flexWrap: 'wrap', gap: 4 } },
                      (lead.tags || []).map(function(tag) { return el(TagBadge, { key: tag.id, tag: tag }); })
                    )
                  ),
                  el('td', null, el('span', { className: 'ls-badge' }, lead.origem || 'manual')),
                  el('td', null, el('span', { className: 'ls-text-muted' }, formatDate(lead.created_at))),
                  el('td', null,
                    el('div', { className: 'ls-flex ls-gap-2' },
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openView(lead); } }, 'Ver'),
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openEdit(lead); } }, 'Editar'),
                      el('button', { className: 'ls-btn ls-btn-danger ls-btn-sm', onClick: function() { openDelete(lead); } }, 'Excluir')
                    )
                  )
                );
              })
            )
          ),
          el(Pagination, { page: page, total: total, perPage: 20, onChange: setPage })
        )
      ),

      modal === 'view' && current && el(Modal, {
        title: 'Lead: ' + (current.nome || current.email),
        onClose: function() { setModal(null); }
      },
        el('div', { className: 'ls-grid-2' },
          el(FormGroup, { label: 'Nome' }, el('div', { className: 'ls-input', style: { background: '#f9fafb' } }, current.nome || '—')),
          el(FormGroup, { label: 'E-mail' }, el('div', { className: 'ls-input', style: { background: '#f9fafb' } }, current.email))
        ),
        el('div', { className: 'ls-grid-2' },
          el(FormGroup, { label: 'Telefone' }, el('div', { className: 'ls-input', style: { background: '#f9fafb' } }, current.telefone || '—')),
          el(FormGroup, { label: 'Origem' }, el('div', { className: 'ls-input', style: { background: '#f9fafb' } }, current.origem || '—'))
        ),
        el(FormGroup, { label: 'Etiquetas' },
          el('div', { className: 'ls-flex', style: { flexWrap: 'wrap', gap: 6 } },
            (current.tags || []).length === 0
              ? el('span', { className: 'ls-text-muted' }, 'Nenhuma etiqueta')
              : (current.tags || []).map(function(tag) { return el(TagBadge, { key: tag.id, tag: tag }); })
          )
        ),
        el(FormGroup, { label: 'Data de Entrada' },
          el('div', { className: 'ls-input', style: { background: '#f9fafb' } }, formatDate(current.created_at))
        )
      ),

      modal === 'create' && el(CreateLeadModal, {
        listas: listas,
        onClose: function() { setModal(null); },
        onSaved: function() { setModal(null); showAlert('success', 'Lead criado!'); load(); }
      }),

      modal === 'edit' && current && el(EditLeadModal, {
        lead: current,
        listas: listas,
        onClose: function() { setModal(null); },
        onSaved: function() { setModal(null); showAlert('success', 'Lead atualizado!'); load(); }
      }),

      modal === 'delete' && el(Modal, {
        title: 'Excluir Lead',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-danger', onClick: handleDelete, disabled: saving }, saving ? 'Excluindo…' : 'Excluir')
        )
      },
        el('p', null, 'Tem certeza que deseja excluir este lead? Esta ação não pode ser desfeita.')
      )
    );
  }

  function CreateLeadModal({ listas, onClose, onSaved }) {
    var [form, setForm] = useState({ nome: '', email: '', telefone: '', lista_id: listas[0] ? String(listas[0].id) : '' });
    var [saving, setSaving] = useState(false);

    async function handleSave() {
      if (!form.email.trim()) return;
      setSaving(true);
      try {
        await apiFetch('/leads', { method: 'POST', body: Object.assign({}, form, { lista_id: parseInt(form.lista_id) || 0 }) });
        onSaved();
      } catch(e) {
        alert(e.message);
      } finally {
        setSaving(false);
      }
    }

    var listaOptions = listas.map(function(l) { return { value: String(l.id), label: l.nome }; });

    return el(Modal, {
      title: 'Novo Lead',
      onClose: onClose,
      footer: el(Fragment, null,
        el('button', { className: 'ls-btn ls-btn-outline', onClick: onClose }, 'Cancelar'),
        el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving || !form.email }, saving ? 'Salvando…' : 'Salvar')
      )
    },
      el('div', { className: 'ls-grid-2' },
        el(FormGroup, { label: 'Nome' },
          el(Input, { value: form.nome, onChange: function(v) { setForm(Object.assign({}, form, { nome: v })); } })
        ),
        el(FormGroup, { label: 'E-mail', required: true },
          el(Input, { value: form.email, type: 'email', onChange: function(v) { setForm(Object.assign({}, form, { email: v })); } })
        )
      ),
      el('div', { className: 'ls-grid-2' },
        el(FormGroup, { label: 'Telefone' },
          el(Input, { value: form.telefone, type: 'tel', onChange: function(v) { setForm(Object.assign({}, form, { telefone: v })); } })
        ),
        el(FormGroup, { label: 'Lista' },
          el(Select, {
            value: form.lista_id,
            onChange: function(v) { setForm(Object.assign({}, form, { lista_id: v })); },
            options: listaOptions.length ? listaOptions : [{ value: '', label: 'Sem lista' }]
          })
        )
      )
    );
  }

  /* ============================================================
     Edit Lead Modal
  ============================================================ */
  function EditLeadModal({ lead, listas, onClose, onSaved }) {
    var [form, setForm]           = useState({
      nome:     lead.nome     || '',
      email:    lead.email    || '',
      telefone: lead.telefone || '',
      lista_id: lead.lista_id ? String(lead.lista_id) : '',
    });
    var [allTags, setAllTags]     = useState([]);
    var [selectedTags, setSelectedTags] = useState((lead.tags || []).map(function(t) { return t.id; }));
    var [saving, setSaving]       = useState(false);

    useEffect(function() {
      apiFetch('/tags').then(function(d) { setAllTags(d); }).catch(function() {});
    }, []);

    function toggleTag(tagId) {
      setSelectedTags(function(prev) {
        var idx = prev.indexOf(tagId);
        return idx >= 0 ? prev.filter(function(t) { return t !== tagId; }) : prev.concat([tagId]);
      });
    }

    async function handleSave() {
      setSaving(true);
      try {
        // Update core fields.
        await apiFetch('/leads/' + lead.id, {
          method: 'PUT',
          body: Object.assign({}, form, { lista_id: parseInt(form.lista_id) || 0 }),
        });

        // Sync tags: add new, remove removed.
        var currentTagIds = (lead.tags || []).map(function(t) { return t.id; });
        var toAdd    = selectedTags.filter(function(id) { return currentTagIds.indexOf(id) < 0; });
        var toRemove = currentTagIds.filter(function(id) { return selectedTags.indexOf(id) < 0; });

        await Promise.all(
          toAdd.map(function(tag_id) {
            return apiFetch('/leads/' + lead.id + '/tags', { method: 'POST', body: { tag_id: tag_id } });
          }).concat(
            toRemove.map(function(tag_id) {
              return apiFetch('/leads/' + lead.id + '/tags', { method: 'DELETE', body: { tag_id: tag_id } });
            })
          )
        );

        onSaved();
      } catch(e) {
        alert(e.message);
      } finally {
        setSaving(false);
      }
    }

    var listaOptions = listas.map(function(l) { return { value: String(l.id), label: l.nome }; });

    return el(Modal, {
      title: 'Editar Lead',
      onClose: onClose,
      footer: el(Fragment, null,
        el('button', { className: 'ls-btn ls-btn-outline', onClick: onClose }, 'Cancelar'),
        el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving || !form.email }, saving ? 'Salvando…' : 'Salvar')
      )
    },
      el('div', { className: 'ls-grid-2' },
        el(FormGroup, { label: 'Nome' },
          el(Input, { value: form.nome, onChange: function(v) { setForm(Object.assign({}, form, { nome: v })); } })
        ),
        el(FormGroup, { label: 'E-mail', required: true },
          el(Input, { value: form.email, type: 'email', onChange: function(v) { setForm(Object.assign({}, form, { email: v })); } })
        )
      ),
      el('div', { className: 'ls-grid-2' },
        el(FormGroup, { label: 'Telefone' },
          el(Input, { value: form.telefone, type: 'tel', onChange: function(v) { setForm(Object.assign({}, form, { telefone: v })); } })
        ),
        el(FormGroup, { label: 'Lista' },
          el(Select, {
            value: form.lista_id,
            onChange: function(v) { setForm(Object.assign({}, form, { lista_id: v })); },
            options: listaOptions.length ? listaOptions : [{ value: '', label: 'Sem lista' }]
          })
        )
      ),
      el(FormGroup, { label: 'Etiquetas' },
        el('div', { style: { display: 'flex', flexWrap: 'wrap', gap: 8, marginTop: 4 } },
          allTags.length === 0
            ? el('span', { className: 'ls-text-muted' }, 'Nenhuma etiqueta cadastrada.')
            : allTags.map(function(tag) {
                var selected = selectedTags.indexOf(tag.id) >= 0;
                return el('button', {
                  key: tag.id,
                  type: 'button',
                  onClick: function() { toggleTag(tag.id); },
                  style: {
                    padding: '3px 10px',
                    borderRadius: 20,
                    border: '2px solid ' + tag.cor,
                    background: selected ? tag.cor : 'transparent',
                    color: selected ? '#fff' : tag.cor,
                    cursor: 'pointer',
                    fontWeight: 600,
                    fontSize: 12,
                    transition: 'all .15s',
                  }
                }, (selected ? '✓ ' : '') + tag.nome);
              })
        )
      )
    );
  }

  /* ============================================================
     Tags Page
  ============================================================ */
  function TagsPage() {
    var [tags, setTags] = useState([]);
    var [loading, setLoading] = useState(true);
    var [modal, setModal] = useState(null);
    var [current, setCurrent] = useState(null);
    var [form, setForm] = useState({ nome: '', cor: '#6366f1' });
    var [saving, setSaving] = useState(false);
    var [alert, setAlert] = useState(null);

    function load() {
      setLoading(true);
      apiFetch('/tags').then(function(d) { setTags(d); setLoading(false); });
    }

    useEffect(load, []);

    function showAlert(type, msg) {
      setAlert({ type, msg });
      setTimeout(function() { setAlert(null); }, 4000);
    }

    function openCreate() {
      setForm({ nome: '', cor: '#6366f1' });
      setCurrent(null);
      setModal('create');
    }

    function openEdit(tag) {
      setForm({ nome: tag.nome, cor: tag.cor });
      setCurrent(tag);
      setModal('edit');
    }

    function openDelete(tag) {
      setCurrent(tag);
      setModal('delete');
    }

    async function handleSave() {
      if (!form.nome.trim()) return;
      setSaving(true);
      try {
        if (modal === 'create') {
          await apiFetch('/tags', { method: 'POST', body: form });
          showAlert('success', 'Etiqueta criada!');
        } else {
          await apiFetch('/tags/' + current.id, { method: 'PUT', body: form });
          showAlert('success', 'Etiqueta atualizada!');
        }
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    async function handleDelete() {
      setSaving(true);
      try {
        await apiFetch('/tags/' + current.id, { method: 'DELETE' });
        showAlert('success', 'Etiqueta excluída.');
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    return el(Fragment, null,
      alert && el(Alert, { type: alert.type, onClose: function(){ setAlert(null); } }, alert.msg),

      el('div', { className: 'ls-card' },
        el('div', { className: 'ls-card-header' },
          el('h3', { className: 'ls-card-title' }, 'Etiquetas (' + tags.length + ')'),
          el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Nova Etiqueta')
        ),

        loading ? el(LoadingCenter) :
        tags.length === 0 ? el(EmptyState, {
          icon: '🏷',
          title: 'Nenhuma etiqueta criada',
          desc: 'Organize seus leads com etiquetas coloridas.',
          action: el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Criar Etiqueta')
        }) :
        el('div', { className: 'ls-card-body' },
          el('div', { style: { display: 'flex', flexWrap: 'wrap', gap: 12 } },
            tags.map(function(tag) {
              return el('div', {
                key: tag.id,
                style: {
                  display: 'flex', alignItems: 'center', gap: 10,
                  background: tag.cor + '18', border: '1px solid ' + tag.cor + '44',
                  borderRadius: 20, padding: '6px 14px'
                }
              },
                el('span', { style: { width: 10, height: 10, borderRadius: '50%', background: tag.cor, display: 'inline-block', flexShrink: 0 } }),
                el('span', { style: { fontWeight: 600, color: tag.cor, fontSize: 13 } }, tag.nome),
                el('button', {
                  onClick: function() { openEdit(tag); },
                  style: { background: 'none', border: 'none', cursor: 'pointer', color: tag.cor, fontSize: 12, padding: '0 2px' }
                }, '✏'),
                el('button', {
                  onClick: function() { openDelete(tag); },
                  style: { background: 'none', border: 'none', cursor: 'pointer', color: '#ef4444', fontSize: 12, padding: '0 2px' }
                }, '×')
              );
            })
          )
        )
      ),

      (modal === 'create' || modal === 'edit') && el(Modal, {
        title: modal === 'create' ? 'Nova Etiqueta' : 'Editar Etiqueta',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving || !form.nome }, saving ? 'Salvando…' : 'Salvar')
        )
      },
        el('div', { className: 'ls-grid-2' },
          el(FormGroup, { label: 'Nome', required: true },
            el(Input, { value: form.nome, onChange: function(v) { setForm(Object.assign({}, form, { nome: v })); } })
          ),
          el(FormGroup, { label: 'Cor' },
            el('input', {
              type: 'color',
              value: form.cor,
              onChange: function(e) { setForm(Object.assign({}, form, { cor: e.target.value })); },
              style: { width: '100%', height: 40, border: '1px solid #e5e7eb', borderRadius: 7, cursor: 'pointer', padding: 2 }
            })
          )
        ),
        el('div', { style: { marginTop: 8 } },
          el('span', { className: 'ls-text-muted' }, 'Prévia: '),
          el(TagBadge, { tag: { nome: form.nome || 'Etiqueta', cor: form.cor } })
        )
      ),

      modal === 'delete' && el(Modal, {
        title: 'Excluir Etiqueta',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-danger', onClick: handleDelete, disabled: saving }, saving ? 'Excluindo…' : 'Excluir')
        )
      },
        el('p', null, 'Excluir etiqueta "', el('strong', null, current && current.nome), '"?')
      )
    );
  }

  /* ============================================================
     Automações Page
  ============================================================ */
  var TRIGGERS = [
    { value: 'lead_created',     label: 'Lead criado' },
    { value: 'lead_entered_list',label: 'Lead entrou em lista' },
    { value: 'tag_added',        label: 'Tag adicionada' },
  ];

  var ACOES_TIPOS = [
    { value: 'add_tag',      label: 'Adicionar etiqueta' },
    { value: 'move_list',    label: 'Mover para lista' },
    { value: 'send_webhook', label: 'Enviar webhook' },
  ];

  var NODE_TYPES = [
    { value: 'trigger',  label: '⚡ Gatilho',   color: '#3b82f6' },
    { value: 'action',   label: '▶ Ação',       color: '#f59e0b' },
    { value: 'wait',     label: '⏳ Espera',    color: '#8b5cf6' },
    { value: 'if_else',  label: '🔀 Se/Senão',  color: '#10b981' },
    { value: 'split',    label: '⑂ Divisão',   color: '#6366f1' },
    { value: 'goal',     label: '🎯 Meta',      color: '#f97316' },
    { value: 'loop',     label: '🔁 Loop',      color: '#ec4899' },
    { value: 'end',      label: '■ Fim',        color: '#6b7280' },
  ];

  var CONDITION_OPS = [
    { value: 'has_tag',      label: 'Possui etiqueta' },
    { value: 'not_has_tag',  label: 'Não possui etiqueta' },
    { value: 'lista_is',     label: 'Lista é' },
    { value: 'field_equals', label: 'Campo igual a' },
  ];

  /**
   * WorkflowNodeBuilder – list-based node editor for building visual workflows.
   * Nodes are stored as a JSON array; each node has: id, type, config, next (array of node IDs).
   * next_true / next_false are used for if_else nodes.
   * next_exit is used for loop nodes (when max iterations reached).
   */
  function WorkflowNodeBuilder({ nodes, tags, listas, triggers, onChange }) {
    var [editingIdx, setEditingIdx] = useState(null);

    function genId() {
      return 'node_' + Math.random().toString(36).slice(2, 9);
    }

    function addNode(type) {
      var newNode = { id: genId(), type: type, config: {}, next: [] };
      if (type === 'if_else') { newNode.next_true = []; newNode.next_false = []; }
      if (type === 'loop')    { newNode.next_exit = []; }
      onChange(nodes.concat([newNode]));
    }

    function removeNode(idx) {
      var removed = nodes[idx];
      var updated = nodes.filter(function(_, i) { return i !== idx; });
      // Clean up references to the removed node's ID from other nodes' next arrays.
      updated = updated.map(function(n) {
        return Object.assign({}, n, {
          next:       (n.next        || []).filter(function(id) { return id !== removed.id; }),
          next_true:  (n.next_true   || []).filter(function(id) { return id !== removed.id; }),
          next_false: (n.next_false  || []).filter(function(id) { return id !== removed.id; }),
          next_exit:  (n.next_exit   || []).filter(function(id) { return id !== removed.id; }),
        });
      });
      onChange(updated);
      if (editingIdx === idx) setEditingIdx(null);
    }

    function updateNode(idx, patch) {
      onChange(nodes.map(function(n, i) { return i === idx ? Object.assign({}, n, patch) : n; }));
    }

    function updateConfig(idx, patch) {
      var node = nodes[idx];
      updateNode(idx, { config: Object.assign({}, node.config, patch) });
    }

    function getNodeColor(type) {
      var nt = NODE_TYPES.find(function(t) { return t.value === type; });
      return nt ? nt.color : '#6b7280';
    }

    function getNodeLabel(type) {
      var nt = NODE_TYPES.find(function(t) { return t.value === type; });
      return nt ? nt.label : type;
    }

    function renderEdgeSelect(idx, edgeKey, label) {
      var node = nodes[idx];
      var ids  = node[edgeKey] || [];
      return el(FormGroup, { label: label },
        el('select', {
          multiple: true,
          className: 'ls-select',
          style: { height: 80 },
          value: ids,
          onChange: function(e) {
            var selected = Array.from(e.target.selectedOptions).map(function(o) { return o.value; });
            var patch = {};
            patch[edgeKey] = selected;
            updateNode(idx, patch);
          }
        },
          nodes.filter(function(_, i) { return i !== idx; }).map(function(n) {
            return el('option', { key: n.id, value: n.id }, '[' + n.type + '] ' + (n.config && n.config.tipo ? n.config.tipo : n.id));
          })
        )
      );
    }

    function renderNodeConfig(idx) {
      var node = nodes[idx];
      var cfg  = node.config || {};
      var type = node.type;

      return el('div', { style: { padding: '10px 0', borderTop: '1px solid var(--ls-border)', marginTop: 8 } },
        el('div', { style: { fontWeight: 600, marginBottom: 8, fontSize: 12, color: getNodeColor(type) } }, 'Configuração do nó'),

        type === 'trigger' && el(FormGroup, { label: 'Gatilho' },
          el(Select, {
            value: cfg.trigger || triggers[0].value,
            onChange: function(v) { updateConfig(idx, { trigger: v }); },
            options: triggers
          })
        ),

        type === 'action' && el(Fragment, null,
          el(FormGroup, { label: 'Tipo de ação' },
            el(Select, {
              value: cfg.tipo || 'add_tag',
              onChange: function(v) { updateConfig(idx, { tipo: v }); },
              options: ACOES_TIPOS
            })
          ),
          cfg.tipo === 'add_tag' && el(FormGroup, { label: 'Etiqueta' },
            el(Select, {
              value: String(cfg.tag_id || ''),
              onChange: function(v) { updateConfig(idx, { tag_id: parseInt(v) }); },
              options: [{ value: '', label: '— Selecione —' }].concat(tags.map(function(t) { return { value: String(t.id), label: t.nome }; }))
            })
          ),
          cfg.tipo === 'move_list' && el(FormGroup, { label: 'Lista destino' },
            el(Select, {
              value: String(cfg.lista_id || ''),
              onChange: function(v) { updateConfig(idx, { lista_id: parseInt(v) }); },
              options: [{ value: '', label: '— Selecione —' }].concat(listas.map(function(l) { return { value: String(l.id), label: l.nome }; }))
            })
          ),
          cfg.tipo === 'send_webhook' && el(FormGroup, { label: 'URL do webhook' },
            el(Input, {
              value: cfg.url || '',
              onChange: function(v) { updateConfig(idx, { url: v }); },
              placeholder: 'https://exemplo.com/webhook'
            })
          )
        ),

        type === 'wait' && el(Fragment, null,
          el('div', { className: 'ls-grid-2' },
            el(FormGroup, { label: 'Aguardar' },
              el('input', {
                className: 'ls-input',
                type: 'number',
                min: 0,
                value: cfg.delay_value || 0,
                onChange: function(e) { updateConfig(idx, { delay_value: Math.max(0, parseInt(e.target.value) || 0) }); }
              })
            ),
            el(FormGroup, { label: 'Unidade' },
              el(Select, {
                value: cfg.delay_unit || 'hours',
                onChange: function(v) { updateConfig(idx, { delay_unit: v }); },
                options: [{ value: 'hours', label: 'Horas' }, { value: 'days', label: 'Dias' }]
              })
            )
          )
        ),

        (type === 'if_else' || type === 'goal') && el(Fragment, null,
          el(FormGroup, { label: 'Condição' },
            el(Select, {
              value: cfg.op || 'has_tag',
              onChange: function(v) { updateConfig(idx, { op: v }); },
              options: CONDITION_OPS
            })
          ),
          (cfg.op === 'has_tag' || cfg.op === 'not_has_tag') && el(FormGroup, { label: 'Etiqueta' },
            el(Select, {
              value: String(cfg.value || ''),
              onChange: function(v) { updateConfig(idx, { value: parseInt(v) }); },
              options: [{ value: '', label: '— Selecione —' }].concat(tags.map(function(t) { return { value: String(t.id), label: t.nome }; }))
            })
          ),
          cfg.op === 'lista_is' && el(FormGroup, { label: 'Lista' },
            el(Select, {
              value: String(cfg.value || ''),
              onChange: function(v) { updateConfig(idx, { value: parseInt(v) }); },
              options: [{ value: '', label: '— Selecione —' }].concat(listas.map(function(l) { return { value: String(l.id), label: l.nome }; }))
            })
          ),
          cfg.op === 'field_equals' && el(Fragment, null,
            el(FormGroup, { label: 'Campo (ex: lista_id)' },
              el(Input, { value: cfg.field || '', onChange: function(v) { updateConfig(idx, { field: v }); } })
            ),
            el(FormGroup, { label: 'Valor' },
              el(Input, { value: String(cfg.value || ''), onChange: function(v) { updateConfig(idx, { value: v }); } })
            )
          )
        ),

        type === 'loop' && el(FormGroup, { label: 'Máximo de iterações' },
          el('input', {
            className: 'ls-input',
            type: 'number',
            min: 1,
            value: cfg.max_iterations || 1,
            onChange: function(e) { updateConfig(idx, { max_iterations: Math.max(1, parseInt(e.target.value) || 1) }); }
          })
        ),

        // Edge connections
        el('div', { style: { marginTop: 8 } },
          (type === 'if_else')
            ? el(Fragment, null,
                renderEdgeSelect(idx, 'next_true',  'Se verdadeiro → próximos nós'),
                renderEdgeSelect(idx, 'next_false', 'Se falso → próximos nós')
              )
            : (type === 'loop')
              ? el(Fragment, null,
                  renderEdgeSelect(idx, 'next',      'Corpo do loop (próximos nós)'),
                  renderEdgeSelect(idx, 'next_exit', 'Saída do loop (após max. iterações)')
                )
              : (type !== 'end')
                ? renderEdgeSelect(idx, 'next', 'Próximos nós')
                : null
        )
      );
    }

    var nodeTypeOptions = NODE_TYPES.map(function(t) { return { value: t.value, label: t.label }; });
    var [addType, setAddType] = useState('action');

    return el('div', { style: { marginTop: 8 } },
      // Toolbar
      el('div', { className: 'ls-flex ls-gap-2', style: { marginBottom: 12, flexWrap: 'wrap' } },
        el('div', { style: { fontWeight: 600, fontSize: 13, alignSelf: 'center' } }, 'Nós do fluxo'),
        el(Select, { value: addType, onChange: setAddType, options: nodeTypeOptions }),
        el('button', {
          type: 'button',
          className: 'ls-btn ls-btn-outline ls-btn-sm',
          onClick: function() { addNode(addType); }
        }, '+ Adicionar nó')
      ),

      // Node list
      nodes.length === 0
        ? el('div', { className: 'ls-text-muted', style: { fontSize: 13, padding: '12px 0' } },
            'Nenhum nó adicionado. Comece adicionando um nó "Gatilho".'
          )
        : nodes.map(function(node, idx) {
            var isEditing = editingIdx === idx;
            var color     = getNodeColor(node.type);
            return el('div', {
              key: node.id,
              style: {
                border: '2px solid ' + color,
                borderRadius: 8,
                padding: '10px 12px',
                marginBottom: 8,
                background: 'var(--ls-gray-200)',
              }
            },
              el('div', { className: 'ls-flex ls-gap-2', style: { alignItems: 'center' } },
                el('div', { style: { width: 10, height: 10, borderRadius: '50%', background: color, flexShrink: 0 } }),
                el('div', { style: { fontWeight: 700, fontSize: 13, color: color, flex: 1 } }, getNodeLabel(node.type)),
                el('div', { className: 'ls-text-muted', style: { fontSize: 11 } }, node.id),
                el('button', {
                  type: 'button',
                  className: 'ls-btn ls-btn-outline ls-btn-sm',
                  onClick: function() { setEditingIdx(isEditing ? null : idx); }
                }, isEditing ? 'Fechar' : 'Configurar'),
                el('button', {
                  type: 'button',
                  className: 'ls-btn ls-btn-danger ls-btn-sm',
                  onClick: function() { removeNode(idx); }
                }, '×')
              ),
              isEditing && renderNodeConfig(idx)
            );
          })
    );
  }

  function AutomacoesPage() {
    var [automacoes, setAutomacoes] = useState([]);
    var [loading, setLoading] = useState(true);
    var [modal, setModal] = useState(null);
    var [current, setCurrent] = useState(null);
    var [alert, setAlert] = useState(null);
    var [saving, setSaving] = useState(false);
    var [tags, setTags] = useState([]);
    var [listas, setListas] = useState([]);
    var [form, setForm] = useState({ nome: '', trigger: 'lead_created', acoes: [], ativo: 1 });

    useEffect(function() {
      apiFetch('/tags').then(function(d) { setTags(d); });
      apiFetch('/listas?per_page=100').then(function(d) { setListas(d.items); });
    }, []);

    function load() {
      setLoading(true);
      apiFetch('/automacoes').then(function(d) { setAutomacoes(d); setLoading(false); });
    }

    useEffect(load, []);

    function showAlert(type, msg) {
      setAlert({ type, msg });
      setTimeout(function() { setAlert(null); }, 4000);
    }

    function openCreate() {
      setForm({ nome: '', trigger: 'lead_created', acoes: [], nodes: null, ativo: 1, editorMode: 'simple' });
      setCurrent(null);
      setModal('create');
    }

    function openEdit(a) {
      setForm({
        nome:       a.nome,
        trigger:    a.trigger_key,
        acoes:      a.acoes_json  || [],
        nodes:      a.nodes_json  || null,
        ativo:      a.ativo,
        editorMode: (a.nodes_json && a.nodes_json.length) ? 'advanced' : 'simple',
      });
      setCurrent(a);
      setModal('edit');
    }

    function openDelete(a) {
      setCurrent(a);
      setModal('delete');
    }

    function addAcao() {
      setForm(function(prev) {
        return Object.assign({}, prev, { acoes: prev.acoes.concat([{ tipo: 'add_tag', tag_id: '', lista_id: '', url: '' }]) });
      });
    }

    function removeAcao(idx) {
      setForm(function(prev) {
        return Object.assign({}, prev, { acoes: prev.acoes.filter(function(_, i) { return i !== idx; }) });
      });
    }

    function updateAcao(idx, patch) {
      setForm(function(prev) {
        return Object.assign({}, prev, {
          acoes: prev.acoes.map(function(a, i) { return i === idx ? Object.assign({}, a, patch) : a; })
        });
      });
    }

    async function handleSave() {
      if (!form.nome.trim()) return;
      setSaving(true);
      try {
        var payload = {
          nome:    form.nome,
          trigger: form.trigger,
          acoes:   form.editorMode === 'simple' ? form.acoes : [],
          nodes:   form.editorMode === 'advanced' ? (form.nodes || []) : null,
          ativo:   form.ativo,
        };
        if (modal === 'create') {
          await apiFetch('/automacoes', { method: 'POST', body: payload });
          showAlert('success', 'Automação criada!');
        } else {
          await apiFetch('/automacoes/' + current.id, { method: 'PUT', body: payload });
          showAlert('success', 'Automação atualizada!');
        }
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    async function handleDelete() {
      setSaving(true);
      try {
        await apiFetch('/automacoes/' + current.id, { method: 'DELETE' });
        showAlert('success', 'Automação excluída.');
        setModal(null);
        load();
      } catch(e) {
        showAlert('error', e.message);
      } finally {
        setSaving(false);
      }
    }

    function getTriggerLabel(key) {
      var t = TRIGGERS.find(function(x) { return x.value === key; });
      return t ? t.label : key;
    }

    return el(Fragment, null,
      alert && el(Alert, { type: alert.type, onClose: function(){ setAlert(null); } }, alert.msg),

      el('div', { className: 'ls-card' },
        el('div', { className: 'ls-card-header' },
          el('h3', { className: 'ls-card-title' }, 'Automações (' + automacoes.length + ')'),
          el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Nova Automação')
        ),

        loading ? el(LoadingCenter) :
        automacoes.length === 0 ? el(EmptyState, {
          icon: '⚡',
          title: 'Nenhuma automação criada',
          desc: 'Crie fluxos automáticos para seus leads.',
          action: el('button', { className: 'ls-btn ls-btn-primary', onClick: openCreate }, '+ Criar Automação')
        }) :
        el('div', { className: 'ls-table-wrap' },
          el('table', { className: 'ls-table' },
            el('thead', null,
              el('tr', null,
                el('th', null, 'Nome'),
                el('th', null, 'Gatilho'),
                el('th', null, 'Ações'),
                el('th', null, 'Status'),
                el('th', null, '')
              )
            ),
            el('tbody', null,
              automacoes.map(function(a) {
                return el('tr', { key: a.id },
                  el('td', null, el('span', { style: { fontWeight: 600 } }, a.nome)),
                  el('td', null,
                    el('span', { className: 'ls-badge ls-badge-primary' }, getTriggerLabel(a.trigger_key))
                  ),
                  el('td', null, (a.nodes_json && a.nodes_json.length)
                    ? el('span', { className: 'ls-badge ls-badge-primary' }, a.nodes_json.length + ' nó(s)')
                    : (a.acoes_json || []).length + ' ação(ões)'
                  ),
                  el('td', null,
                    el('span', { className: 'ls-badge ' + (a.ativo ? 'ls-badge-success' : '') },
                      a.ativo ? 'Ativo' : 'Inativo'
                    )
                  ),
                  el('td', null,
                    el('div', { className: 'ls-flex ls-gap-2' },
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openEdit(a); } }, 'Editar'),
                      el('button', { className: 'ls-btn ls-btn-danger ls-btn-sm', onClick: function() { openDelete(a); } }, 'Excluir')
                    )
                  )
                );
              })
            )
          )
        )
      ),

      (modal === 'create' || modal === 'edit') && el(Modal, {
        title: modal === 'create' ? 'Nova Automação' : 'Editar Automação',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-primary', onClick: handleSave, disabled: saving }, saving ? 'Salvando…' : 'Salvar')
        )
      },
        el(FormGroup, { label: 'Nome', required: true },
          el(Input, { value: form.nome, onChange: function(v) { setForm(Object.assign({}, form, { nome: v })); } })
        ),
        el('div', { className: 'ls-grid-2' },
          el(FormGroup, { label: 'Gatilho' },
            el(Select, {
              value: form.trigger,
              onChange: function(v) { setForm(Object.assign({}, form, { trigger: v })); },
              options: TRIGGERS
            })
          ),
          el(FormGroup, { label: 'Status' },
            el(Select, {
              value: String(form.ativo),
              onChange: function(v) { setForm(Object.assign({}, form, { ativo: parseInt(v) })); },
              options: [{ value: '1', label: 'Ativo' }, { value: '0', label: 'Inativo' }]
            })
          )
        ),

        // Editor mode tabs
        el('div', { style: { display: 'flex', gap: 8, marginBottom: 12, marginTop: 4 } },
          el('button', {
            type: 'button',
            className: 'ls-btn ls-btn-sm ' + (form.editorMode === 'simple' ? 'ls-btn-primary' : 'ls-btn-outline'),
            onClick: function() { setForm(Object.assign({}, form, { editorMode: 'simple' })); }
          }, '⚡ Ações rápidas'),
          el('button', {
            type: 'button',
            className: 'ls-btn ls-btn-sm ' + (form.editorMode === 'advanced' ? 'ls-btn-primary' : 'ls-btn-outline'),
            onClick: function() { setForm(Object.assign({}, form, { editorMode: 'advanced', nodes: form.nodes || [] })); }
          }, '🔀 Fluxo de nós')
        ),

        form.editorMode === 'simple' && el(Fragment, null,
          // Simple flat-actions editor (existing)
          el('div', { style: { marginTop: 4 } },
            el('div', { className: 'ls-flex ls-gap-2', style: { marginBottom: 8 } },
              el('div', { style: { fontWeight: 600, fontSize: 13 } }, 'Ações'),
              el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: addAcao }, '+ Ação')
            ),

            el('div', { className: 'ls-flow-step', style: { background: '#eff6ff' } },
              el('div', { className: 'ls-flow-step-icon trigger' }, '⚡'),
              el('div', null,
                el('div', { style: { fontWeight: 600, fontSize: 13 } }, 'Gatilho'),
                el('div', { className: 'ls-text-muted' }, getTriggerLabel(form.trigger))
              )
            ),

            form.acoes.map(function(acao, idx) {
              return el(Fragment, { key: idx },
                el('div', { className: 'ls-flow-connector' }),
                el('div', { className: 'ls-flow-step' },
                  el('div', { className: 'ls-flow-step-icon action' }, '▶'),
                  el('div', { style: { flex: 1 } },
                    el('div', { className: 'ls-grid-2', style: { gap: 8 } },
                      el(Select, {
                        value: acao.tipo,
                        onChange: function(v) { updateAcao(idx, { tipo: v }); },
                        options: ACOES_TIPOS
                      }),
                      acao.tipo === 'add_tag' && el(Select, {
                        value: String(acao.tag_id || ''),
                        onChange: function(v) { updateAcao(idx, { tag_id: parseInt(v) }); },
                        options: [{ value: '', label: '— Selecione tag —' }].concat(tags.map(function(t) { return { value: String(t.id), label: t.nome }; }))
                      }),
                      acao.tipo === 'move_list' && el(Select, {
                        value: String(acao.lista_id || ''),
                        onChange: function(v) { updateAcao(idx, { lista_id: parseInt(v) }); },
                        options: [{ value: '', label: '— Selecione lista —' }].concat(listas.map(function(l) { return { value: String(l.id), label: l.nome }; }))
                      }),
                      acao.tipo === 'send_webhook' && el(Input, {
                        value: acao.url || '',
                        onChange: function(v) { updateAcao(idx, { url: v }); },
                        placeholder: 'https://exemplo.com/webhook'
                      })
                    )
                  ),
                  el('button', {
                    className: 'ls-btn ls-btn-danger ls-btn-sm',
                    onClick: function() { removeAcao(idx); },
                    style: { alignSelf: 'flex-start', marginLeft: 8 }
                  }, '×')
                )
              );
            })
          )
        ),

        form.editorMode === 'advanced' && el(WorkflowNodeBuilder, {
          nodes:    form.nodes || [],
          tags:     tags,
          listas:   listas,
          triggers: TRIGGERS,
          onChange: function(nodes) { setForm(Object.assign({}, form, { nodes: nodes })); }
        })
      ),

      modal === 'delete' && el(Modal, {
        title: 'Excluir Automação',
        onClose: function() { setModal(null); },
        footer: el(Fragment, null,
          el('button', { className: 'ls-btn ls-btn-outline', onClick: function() { setModal(null); } }, 'Cancelar'),
          el('button', { className: 'ls-btn ls-btn-danger', onClick: handleDelete, disabled: saving }, saving ? 'Excluindo…' : 'Excluir')
        )
      },
        el('p', null, 'Excluir automação "', el('strong', null, current && current.nome), '"?')
      )
    );
  }

  var LEADS_SAAS_VERSION = '1.2.0';

  /* ============================================================
     Main App
  ============================================================ */
  function getInitialPage() {
    var wpPage = window.LeadsSaaSConfig.page;
    if (wpPage === 'leads-saas-listas')    return 'listas';
    if (wpPage === 'leads-saas-leads')     return 'leads';
    if (wpPage === 'leads-saas-tags')      return 'tags';
    if (wpPage === 'leads-saas-automacoes') return 'automacoes';
    return 'dashboard';
  }

  function App() {
    var page = getInitialPage();

    function renderPage() {
      switch (page) {
        case 'dashboard':  return el(DashboardPage);
        case 'listas':     return el(ListasPage);
        case 'leads':      return el(LeadsPage);
        case 'tags':       return el(TagsPage);
        case 'automacoes': return el(AutomacoesPage);
        default:           return el(DashboardPage);
      }
    }

    return el('div', { className: 'ls-page-content' },
      renderPage()
    );
  }

  /* ============================================================
     Helpers
  ============================================================ */
  function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
      var d = new Date(dateStr);
      return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
        ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch(e) {
      return dateStr;
    }
  }

  /* ============================================================
     Mount
  ============================================================ */
  var container = document.getElementById('leads-saas-app');
  if (container && wp.element.render) {
    wp.element.render(el(App), container);
  }

})();
