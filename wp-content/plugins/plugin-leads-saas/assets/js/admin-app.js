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
                  el('td', null, el('span', { style: { fontWeight: 600 } }, lista.nome)),
                  el('td', null, el('span', { className: 'ls-text-muted' }, lista.descricao || '—')),
                  el('td', null,
                    el('code', { style: { fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4, wordBreak: 'break-all' } }, webhookUrl)
                  ),
                  el('td', null,
                    el('code', { style: { fontSize: 11, background: '#f3f4f6', padding: '2px 6px', borderRadius: 4 } }, shortcode)
                  ),
                  el('td', null,
                    el('div', { className: 'ls-flex ls-gap-2' },
                      el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: function() { openBuilder(lista); } }, '🔧 Form'),
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
      })
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
      setForm({ nome: '', trigger: 'lead_created', acoes: [], ativo: 1 });
      setCurrent(null);
      setModal('create');
    }

    function openEdit(a) {
      setForm({ nome: a.nome, trigger: a.trigger_key, acoes: a.acoes_json || [], ativo: a.ativo });
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
        var payload = { nome: form.nome, trigger: form.trigger, acoes: form.acoes, ativo: form.ativo };
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
                  el('td', null, (a.acoes_json || []).length + ' ação(ões)'),
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

        // Fluxo visual
        el('div', { style: { marginTop: 4 } },
          el('div', { className: 'ls-flex ls-gap-2', style: { marginBottom: 8 } },
            el('div', { style: { fontWeight: 600, fontSize: 13 } }, 'Ações'),
            el('button', { className: 'ls-btn ls-btn-outline ls-btn-sm', onClick: addAcao }, '+ Ação')
          ),

          // Flow display
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

  var LEADS_SAAS_VERSION = '1.0.0';

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
