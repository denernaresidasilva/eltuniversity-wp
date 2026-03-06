/* globals zapwaFlowBuilderData */
/**
 * ZapWA Flow Builder — Vanilla JS visual node editor.
 *
 * Supports:
 *  - Drag-and-drop nodes from palette onto canvas
 *  - Drag nodes around the canvas
 *  - Connect nodes by dragging from output port to input port
 *  - Click on edges to delete them
 *  - Delete nodes via ✕ button
 *  - Settings panel per node type
 *  - Zoom in/out/reset
 *  - Pan the canvas (middle-button or scroll drag)
 *  - Save/load JSON via WP REST API
 */
(function () {
    'use strict';

    if (typeof zapwaFlowBuilderData === 'undefined') {
        return;
    }

    var data = zapwaFlowBuilderData;

    // -----------------------------------------------------------------------
    // State
    // -----------------------------------------------------------------------
    var state = {
        nodes:        [],     // { id, type, data, position: {x,y} }
        edges:        [],     // { id, source, target, label }
        scale:        1,
        offsetX:      0,
        offsetY:      0,
        selectedNode: null,
        nextId:       1,

        // Connection dragging
        draggingEdge:   false,
        edgeSrc:        null,   // node id
        edgeSrcLabel:   '',     // 'true' | 'false' | ''
        edgeMouse:      {x: 0, y: 0},
        edgeTmpLine:    null,

        // Canvas panning
        panActive:      false,
        panStartX:      0,
        panStartY:      0,
        panStartOX:     0,
        panStartOY:     0,

        // Node dragging
        draggingNode:   false,
        dragNodeId:     null,
        dragNodeStartX: 0,
        dragNodeStartY: 0,
        dragMouseStartX: 0,
        dragMouseStartY: 0,
    };

    // -----------------------------------------------------------------------
    // DOM refs
    // -----------------------------------------------------------------------
    var canvas     = document.getElementById('zapwa-canvas');
    var nodesLayer = document.getElementById('zapwa-nodes-layer');
    var edgesSvg   = document.getElementById('zapwa-edges-svg');
    var canvasWrap = document.getElementById('zapwa-canvas-wrap');
    var settingsPanel   = document.getElementById('zapwa-node-settings');
    var settingsTitle   = document.getElementById('zapwa-settings-title');
    var settingsBody    = document.getElementById('zapwa-settings-body');
    var settingsClose   = document.getElementById('zapwa-settings-close');
    var saveBtn         = document.getElementById('zapwa-save-flow');
    var saveNotice      = document.getElementById('zapwa-save-notice');
    var flowTitleInput  = document.getElementById('zapwa-flow-title');
    var flowTriggerSel  = document.getElementById('zapwa-flow-trigger');
    var flowStatusChk   = document.getElementById('zapwa-flow-status');
    var zoomInBtn       = document.getElementById('zapwa-zoom-in');
    var zoomOutBtn      = document.getElementById('zapwa-zoom-out');
    var zoomResetBtn    = document.getElementById('zapwa-zoom-reset');

    // -----------------------------------------------------------------------
    // Initialise
    // -----------------------------------------------------------------------
    function init() {
        injectArrowDef();
        loadStructure(data.structure);
        bindPaletteEvents();
        bindCanvasEvents();
        bindToolbarEvents();
        bindSettingsPanelEvents();
        render();
    }

    /** Inject SVG arrow-head marker definition */
    function injectArrowDef() {
        var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        defs.innerHTML =
            '<marker id="zapwa-arrow" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">' +
            '<path d="M0,0 L0,6 L8,3 z" fill="#128c7e"/></marker>';
        edgesSvg.insertBefore(defs, edgesSvg.firstChild);
    }

    // -----------------------------------------------------------------------
    // Structure load / save
    // -----------------------------------------------------------------------
    function loadStructure(structure) {
        if (!structure) return;
        var nodes = structure.nodes || [];
        var edges = structure.edges || [];

        state.nodes = nodes.map(function (n) {
            return {
                id:       String(n.id),
                type:     n.type || 'send_whatsapp',
                data:     n.data || {},
                position: { x: n.position.x || 0, y: n.position.y || 0 },
            };
        }).filter(function (n) { return n.type !== 'trigger'; });

        state.edges = edges.map(function (e) {
            return { id: e.id || uid(), source: String(e.source), target: String(e.target), label: e.label || '' };
        }).filter(function (e) {
            return getNodeById(e.source) && getNodeById(e.target);
        });

        if (state.nodes.length === 0) {
            addNode('send_whatsapp', 220, 140);
        }

        // Compute next ID from existing nodes
        state.nodes.forEach(function (n) {
            var num = parseInt(n.id, 10);
            if (!isNaN(num) && num >= state.nextId) {
                state.nextId = num + 1;
            }
        });
    }

    function buildStructure() {
        return {
            nodes: state.nodes.map(function (n) {
                return {
                    id:       n.id,
                    type:     n.type,
                    data:     n.data,
                    position: { x: Math.round(n.position.x), y: Math.round(n.position.y) },
                };
            }),
            edges: state.edges.map(function (e) {
                return { id: e.id, source: e.source, target: e.target, label: e.label };
            }),
        };
    }

    // -----------------------------------------------------------------------
    // Palette drag → canvas drop
    // -----------------------------------------------------------------------
    function bindPaletteEvents() {
        var paletteNodes = document.querySelectorAll('.zapwa-palette-node');
        paletteNodes.forEach(function (el) {
            el.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('zapwa-node-type', el.getAttribute('data-node-type'));
            });
        });

        canvasWrap.addEventListener('dragover', function (e) {
            e.preventDefault();
            canvasWrap.classList.add('drag-over');
        });
        canvasWrap.addEventListener('dragleave', function () {
            canvasWrap.classList.remove('drag-over');
        });
        canvasWrap.addEventListener('drop', function (e) {
            e.preventDefault();
            canvasWrap.classList.remove('drag-over');
            var nodeType = e.dataTransfer.getData('zapwa-node-type');
            if (!nodeType || nodeType === 'trigger') return;

            var rect = canvasWrap.getBoundingClientRect();
            var x = (e.clientX - rect.left - state.offsetX) / state.scale;
            var y = (e.clientY - rect.top  - state.offsetY) / state.scale;

            addNode(nodeType, x, y);
            render();
        });
    }

    // -----------------------------------------------------------------------
    // Canvas events (pan, wheel zoom)
    // -----------------------------------------------------------------------
    function bindCanvasEvents() {
        // Wheel zoom
        canvasWrap.addEventListener('wheel', function (e) {
            e.preventDefault();
            var delta = e.deltaY > 0 ? 0.9 : 1.1;
            setScale(state.scale * delta);
        }, { passive: false });

        // Pan with middle mouse
        canvasWrap.addEventListener('mousedown', function (e) {
            if (e.button === 1 || (e.button === 0 && e.altKey)) {
                state.panActive   = true;
                state.panStartX   = e.clientX;
                state.panStartY   = e.clientY;
                state.panStartOX  = state.offsetX;
                state.panStartOY  = state.offsetY;
                canvasWrap.style.cursor = 'grabbing';
                e.preventDefault();
            }
        });

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    }

    function onMouseMove(e) {
        if (state.panActive) {
            state.offsetX = state.panStartOX + (e.clientX - state.panStartX);
            state.offsetY = state.panStartOY + (e.clientY - state.panStartY);
            applyTransform();
        }
        if (state.draggingNode) {
            var dx = (e.clientX - state.dragMouseStartX) / state.scale;
            var dy = (e.clientY - state.dragMouseStartY) / state.scale;
            var node = getNodeById(state.dragNodeId);
            if (node) {
                node.position.x = state.dragNodeStartX + dx;
                node.position.y = state.dragNodeStartY + dy;
                renderNodePosition(node);
                renderEdges();
            }
        }
        if (state.draggingEdge) {
            var rect = canvasWrap.getBoundingClientRect();
            state.edgeMouse.x = (e.clientX - rect.left - state.offsetX) / state.scale;
            state.edgeMouse.y = (e.clientY - rect.top  - state.offsetY) / state.scale;
            renderEdges();
        }
    }

    function onMouseUp(e) {
        if (state.panActive) {
            state.panActive = false;
            canvasWrap.style.cursor = '';
        }
        if (state.draggingNode) {
            state.draggingNode = false;
            state.dragNodeId   = null;
        }
        if (state.draggingEdge) {
            // If we released on an input port it would have been handled by the port's mouseup.
            // Otherwise cancel.
            state.draggingEdge = false;
            state.edgeSrc      = null;
            state.edgeTmpLine  = null;
            renderEdges();
        }
    }

    // -----------------------------------------------------------------------
    // Toolbar events
    // -----------------------------------------------------------------------
    function bindToolbarEvents() {
        zoomInBtn.addEventListener('click', function () { setScale(state.scale * 1.15); });
        zoomOutBtn.addEventListener('click', function () { setScale(state.scale * 0.87); });
        zoomResetBtn.addEventListener('click', function () { setScale(1); state.offsetX = 0; state.offsetY = 0; applyTransform(); });

        saveBtn.addEventListener('click', saveFlow);
    }

    function setScale(s) {
        state.scale = Math.max(0.2, Math.min(2.5, s));
        applyTransform();
    }

    function applyTransform() {
        canvas.style.transform = 'translate(' + state.offsetX + 'px,' + state.offsetY + 'px) scale(' + state.scale + ')';
    }

    // -----------------------------------------------------------------------
    // Settings panel
    // -----------------------------------------------------------------------
    function bindSettingsPanelEvents() {
        settingsClose.addEventListener('click', function () {
            closeSettings();
        });
    }

    function openSettings(node) {
        state.selectedNode = node;
        settingsTitle.textContent = nodeLabel(node.type);
        settingsBody.innerHTML    = buildSettingsForm(node);
        settingsPanel.style.display = 'flex';
        settingsPanel.style.flexDirection = 'column';

        // Live-update node data on input changes.
        settingsBody.querySelectorAll('[data-field]').forEach(function (el) {
            el.addEventListener('input', function () {
                node.data[el.getAttribute('data-field')] = el.value;
                renderNodeBody(node);
            });
            el.addEventListener('change', function () {
                node.data[el.getAttribute('data-field')] = el.value;
                renderNodeBody(node);
            });
        });
    }

    function closeSettings() {
        settingsPanel.style.display = 'none';
        state.selectedNode = null;
        document.querySelectorAll('.zapwa-node').forEach(function (el) {
            el.classList.remove('selected');
        });
    }

    function buildSettingsForm(node) {
        var type = node.type;
        var d    = node.data;
        var html = '';

        switch (type) {
            case 'send_whatsapp':
                html += field('Mensagem', 'textarea', 'message', d.message || '');
                break;
            case 'send_email':
                html += field('Assunto', 'text', 'subject', d.subject || '');
                html += field('Corpo do E-mail', 'textarea', 'body', d.body || '');
                break;
            case 'delay':
                html += field('Quantidade', 'number', 'delay_amount', d.delay_amount || 1);
                html += field('Unidade', 'select', 'delay_unit', d.delay_unit || 'minutes', [
                    ['seconds', 'Segundos'], ['minutes', 'Minutos'], ['hours', 'Horas'], ['days', 'Dias'],
                ]);
                break;
            case 'condition':
                html += field('Condição', 'select', 'condition_type', d.condition_type || 'has_tag', [
                    ['has_tag', 'Possui Tag'], ['has_purchased_course', 'Inscrito no Curso'],
                ]);
                html += field('Valor', 'text', 'value', d.value || '');
                break;
            case 'end':
                html += '<p style="color:#888;font-size:.82rem;">Fim do fluxo. Sem configurações adicionais.</p>';
                break;
        }
        return html;
    }

    function field(label, inputType, fieldName, value, options) {
        var id = 'zapwa-field-' + fieldName;
        var html = '<div class="zapwa-field"><label for="' + esc(id) + '">' + esc(label) + '</label>';

        if (inputType === 'textarea') {
            html += '<textarea id="' + esc(id) + '" data-field="' + esc(fieldName) + '" rows="4">' + esc(value) + '</textarea>';
        } else if (inputType === 'select' && Array.isArray(options)) {
            html += '<select id="' + esc(id) + '" data-field="' + esc(fieldName) + '">';
            options.forEach(function (o) {
                var v   = Array.isArray(o) ? o[0] : o;
                var lbl = Array.isArray(o) ? o[1] : o;
                html += '<option value="' + esc(v) + '"' + (v === String(value) ? ' selected' : '') + '>' + esc(lbl) + '</option>';
            });
            html += '</select>';
        } else {
            html += '<input type="' + esc(inputType) + '" id="' + esc(id) + '" data-field="' + esc(fieldName) + '" value="' + esc(value) + '" />';
        }

        html += '</div>';
        return html;
    }

    function triggerTypeOptions(current) {
        var opts = [];
        var types = data.triggerTypes || {};
        Object.keys(types).forEach(function (k) {
            opts.push([k, types[k]]);
        });
        return opts;
    }

    // -----------------------------------------------------------------------
    // Node management
    // -----------------------------------------------------------------------
    function addNode(type, x, y) {
        var id = String(state.nextId++);
        var defaults = defaultNodeData(type);
        var node = { id: id, type: type, data: defaults, position: { x: x - 80, y: y - 30 } };
        state.nodes.push(node);
        return node;
    }

    function getNodeById(id) {
        for (var i = 0; i < state.nodes.length; i++) {
            if (state.nodes[i].id === id) return state.nodes[i];
        }
        return null;
    }

    function removeNode(id) {
        state.nodes  = state.nodes.filter(function (n) { return n.id !== id; });
        state.edges  = state.edges.filter(function (e) { return e.source !== id && e.target !== id; });
        if (state.selectedNode && state.selectedNode.id === id) closeSettings();
    }

    function defaultNodeData(type) {
        switch (type) {
            case 'send_whatsapp': return { message: 'Olá {user_name}, ...' };
            case 'send_email':    return { subject: 'Assunto do email', body: 'Corpo do email' };
            case 'delay':         return { delay_amount: 1, delay_unit: 'minutes' };
            case 'condition':     return { condition_type: 'has_tag', value: '' };
            case 'end':           return {};
            default:              return {};
        }
    }

    // -----------------------------------------------------------------------
    // Full render
    // -----------------------------------------------------------------------
    function render() {
        renderNodes();
        renderEdges();
    }

    // -----------------------------------------------------------------------
    // Render all nodes
    // -----------------------------------------------------------------------
    function renderNodes() {
        nodesLayer.innerHTML = '';
        state.nodes.forEach(function (node) {
            nodesLayer.appendChild(createNodeEl(node));
        });
    }

    function createNodeEl(node) {
        var el = document.createElement('div');
        el.className = 'zapwa-node';
        el.setAttribute('data-node-id', node.id);
        el.setAttribute('data-type', node.type);
        el.style.left = node.position.x + 'px';
        el.style.top  = node.position.y + 'px';

        // Header
        var header = document.createElement('div');
        header.className = 'zapwa-node__header';
        header.innerHTML = nodeIcon(node.type) + ' ' + esc(nodeLabel(node.type));

        var deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'zapwa-node__delete';
        deleteBtn.innerHTML = '✕';
        deleteBtn.title = 'Remover';
        deleteBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (confirm(data.i18n.deleteConfirm || 'Remover este bloco?')) {
                removeNode(node.id);
                render();
            }
        });
        header.appendChild(deleteBtn);
        el.appendChild(header);

        // Body
        var body = document.createElement('div');
        body.className = 'zapwa-node__body';
        body.setAttribute('data-node-body', node.id);
        body.innerHTML = buildNodeBodyText(node);
        el.appendChild(body);

        // Footer with ports
        var footer = document.createElement('div');
        footer.className = 'zapwa-node__footer';

        // Input port (all nodes except end)
        if (node.type !== 'end') {
            var inPort = createPort(node.id, 'input', '');
            footer.appendChild(inPort);
        } else {
            footer.appendChild(document.createElement('span'));
        }

        // Output port(s)
        if (node.type === 'condition') {
            var truePort  = createPort(node.id, 'output', 'true');
            var falsePort = createPort(node.id, 'output', 'false');
            var portsWrap = document.createElement('span');
            portsWrap.style.display = 'flex'; portsWrap.style.gap = '8px'; portsWrap.style.alignItems = 'center';
            var trueLabel = document.createElement('span'); trueLabel.style.fontSize = '.68rem'; trueLabel.style.color = '#2e7d32'; trueLabel.textContent = 'S';
            var falseLabel = document.createElement('span'); falseLabel.style.fontSize = '.68rem'; falseLabel.style.color = '#c62828'; falseLabel.textContent = 'N';
            portsWrap.appendChild(trueLabel);
            portsWrap.appendChild(truePort);
            portsWrap.appendChild(falseLabel);
            portsWrap.appendChild(falsePort);
            footer.appendChild(portsWrap);
        } else if (node.type !== 'end') {
            var outPort = createPort(node.id, 'output', '');
            footer.appendChild(outPort);
        } else {
            footer.appendChild(document.createElement('span'));
        }

        el.appendChild(footer);

        // Drag-to-move node
        bindNodeDrag(el, node);

        // Click to open settings
        el.addEventListener('click', function (e) {
            if (e.target.classList.contains('zapwa-port') ||
                e.target.classList.contains('zapwa-node__delete')) return;
            document.querySelectorAll('.zapwa-node').forEach(function (n) { n.classList.remove('selected'); });
            el.classList.add('selected');
            openSettings(node);
        });

        return el;
    }

    function createPort(nodeId, direction, label) {
        var port = document.createElement('div');
        port.className = 'zapwa-port is-' + direction;
        port.setAttribute('data-node', nodeId);
        port.setAttribute('data-direction', direction);
        port.setAttribute('data-label', label);
        port.title = direction === 'output' ? 'Arraste para conectar' : 'Entrada';

        if (direction === 'output') {
            port.addEventListener('mousedown', function (e) {
                e.stopPropagation();
                e.preventDefault();
                state.draggingEdge  = true;
                state.edgeSrc       = nodeId;
                state.edgeSrcLabel  = label;
                var rect = canvasWrap.getBoundingClientRect();
                state.edgeMouse.x = (e.clientX - rect.left - state.offsetX) / state.scale;
                state.edgeMouse.y = (e.clientY - rect.top  - state.offsetY) / state.scale;
            });
        }

        if (direction === 'input') {
            port.addEventListener('mouseup', function (e) {
                if (!state.draggingEdge) return;
                e.stopPropagation();
                var srcId = state.edgeSrc;
                var tgtId = nodeId;
                if (srcId !== tgtId) {
                    // Avoid duplicate edges from same source with same label.
                    var duplicate = state.edges.some(function (ed) {
                        return ed.source === srcId && ed.target === tgtId && ed.label === state.edgeSrcLabel;
                    });
                    if (!duplicate) {
                        state.edges.push({ id: uid(), source: srcId, target: tgtId, label: state.edgeSrcLabel });
                    }
                }
                state.draggingEdge = false;
                state.edgeSrc      = null;
                state.edgeTmpLine  = null;
                renderEdges();
            });
        }

        return port;
    }

    function bindNodeDrag(el, node) {
        el.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            if (e.target.classList.contains('zapwa-port') ||
                e.target.classList.contains('zapwa-node__delete')) return;
            e.stopPropagation();
            state.draggingNode    = true;
            state.dragNodeId      = node.id;
            state.dragNodeStartX  = node.position.x;
            state.dragNodeStartY  = node.position.y;
            state.dragMouseStartX = e.clientX;
            state.dragMouseStartY = e.clientY;
        });
    }

    function renderNodePosition(node) {
        var el = nodesLayer.querySelector('[data-node-id="' + node.id + '"]');
        if (el) {
            el.style.left = node.position.x + 'px';
            el.style.top  = node.position.y + 'px';
        }
    }

    function renderNodeBody(node) {
        var el = nodesLayer.querySelector('[data-node-body="' + node.id + '"]');
        if (el) el.innerHTML = buildNodeBodyText(node);
    }

    function buildNodeBodyText(node) {
        var d = node.data;
        switch (node.type) {
            case 'send_whatsapp':
                return truncate(d.message || '', 60);
            case 'send_email':
                return '📨 ' + esc(truncate(d.subject || '', 50));
            case 'delay':
                return '⏳ ' + esc(d.delay_amount || 1) + ' ' + esc(d.delay_unit || 'minutos');
            case 'condition':
                return '🔀 ' + esc(d.condition_type || '') + ': ' + esc(d.value || '');
            case 'end':
                return '🏁 Fim do fluxo';
            default:
                return '';
        }
    }

    // -----------------------------------------------------------------------
    // Render edges (SVG)
    // -----------------------------------------------------------------------
    function renderEdges() {
        // Keep the <defs> element
        var defs = edgesSvg.querySelector('defs');
        edgesSvg.innerHTML = '';
        if (defs) edgesSvg.appendChild(defs);

        state.edges.forEach(function (edge) {
            var srcNode = getNodeById(edge.source);
            var tgtNode = getNodeById(edge.target);
            if (!srcNode || !tgtNode) return;

            var src = getPortCoords(srcNode, 'output', edge.label);
            var tgt = getPortCoords(tgtNode, 'input',  '');

            drawEdge(src.x, src.y, tgt.x, tgt.y, edge.id, edge.label);
        });

        // Draw temporary edge while connecting
        if (state.draggingEdge && state.edgeSrc) {
            var srcNode = getNodeById(state.edgeSrc);
            if (srcNode) {
                var src = getPortCoords(srcNode, 'output', state.edgeSrcLabel);
                drawTempEdge(src.x, src.y, state.edgeMouse.x, state.edgeMouse.y);
            }
        }
    }

    function drawEdge(x1, y1, x2, y2, edgeId, label) {
        var g    = svgEl('g');
        var path = svgEl('path');
        path.setAttribute('class', 'zapwa-edge-path');
        path.setAttribute('d', bezier(x1, y1, x2, y2));
        path.setAttribute('marker-end', 'url(#zapwa-arrow)');

        // Invisible wider path for easier clicking to delete
        var hitPath = svgEl('path');
        hitPath.setAttribute('class', 'zapwa-edge-delete-zone');
        hitPath.setAttribute('d', bezier(x1, y1, x2, y2));
        hitPath.setAttribute('data-edge-id', edgeId);
        hitPath.style.pointerEvents = 'stroke';
        hitPath.title = 'Clique para remover conexão';
        hitPath.addEventListener('click', function () {
            state.edges = state.edges.filter(function (e) { return e.id !== edgeId; });
            renderEdges();
        });

        if (label === 'true') {
            path.style.stroke = '#43a047';
        } else if (label === 'false') {
            path.style.stroke = '#e53935';
        }

        g.appendChild(hitPath);
        g.appendChild(path);
        edgesSvg.appendChild(g);
    }

    function drawTempEdge(x1, y1, x2, y2) {
        var path = svgEl('path');
        path.setAttribute('class', 'zapwa-edge-path is-drawing');
        path.setAttribute('d', bezier(x1, y1, x2, y2));
        edgesSvg.appendChild(path);
    }

    /** Get canvas coordinates of a port for a given node */
    function getPortCoords(node, direction, label) {
        var el = nodesLayer.querySelector('[data-node-id="' + node.id + '"]');
        if (!el) {
            return { x: node.position.x, y: node.position.y };
        }

        var selector = '.zapwa-port[data-direction="' + direction + '"]';
        if (label !== undefined && label !== '') {
            selector += '[data-label="' + label + '"]';
        }

        var port = el.querySelector(selector);
        if (!port) {
            port = el.querySelector('.zapwa-port[data-direction="' + direction + '"]');
        }
        if (!port) {
            return { x: node.position.x + 80, y: node.position.y + 30 };
        }

        var nodeRect = el.getBoundingClientRect();
        var portRect = port.getBoundingClientRect();
        var wrap     = canvasWrap.getBoundingClientRect();

        var px = portRect.left + portRect.width / 2  - wrap.left;
        var py = portRect.top  + portRect.height / 2 - wrap.top;

        return {
            x: (px - state.offsetX) / state.scale,
            y: (py - state.offsetY) / state.scale,
        };
    }

    function bezier(x1, y1, x2, y2) {
        var cx = (x2 - x1) * 0.5;
        return 'M' + x1 + ',' + y1 +
               ' C' + (x1 + cx) + ',' + y1 +
               ' ' + (x2 - cx) + ',' + y2 +
               ' ' + x2 + ',' + y2;
    }

    // -----------------------------------------------------------------------
    // Save
    // -----------------------------------------------------------------------
    function saveFlow() {
        var flowId    = data.flowId;
        var title     = flowTitleInput ? flowTitleInput.value.trim() : '';
        var trigger   = flowTriggerSel ? flowTriggerSel.value : '';
        var isActive  = flowStatusChk  ? flowStatusChk.checked  : false;
        var structure = buildStructure();

        if (!title) {
            showNotice(data.i18n.saveError || 'Erro: título obrigatório.', true);
            return;
        }

        var payload = {
            title:     title,
            status:    isActive ? 'active' : 'inactive',
            trigger:   trigger,
            structure: structure,
        };

        var url    = flowId > 0
            ? data.restUrl + '/' + flowId
            : data.restUrl;
        var method = flowId > 0 ? 'PUT' : 'POST';

        saveBtn.disabled = true;
        saveBtn.textContent = '⏳ Salvando...';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type':  'application/json',
                'X-WP-Nonce':    data.restNonce,
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) {
            var ok = res.ok;
            return res.json()
                .catch(function () { return { message: res.statusText }; })
                .then(function (d) { return { ok: ok, body: d }; });
        })
        .then(function (res) {
            if (!res.ok) throw new Error(res.body.message || 'Error');

            if (!data.flowId || data.flowId === 0) {
                data.flowId = res.body.id;
                // Update URL without reload using URLSearchParams.
                try {
                    var newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('flow_id', res.body.id);
                    history.replaceState({}, '', newUrl.toString());
                } catch (e) { /* ignore URL update failure */ }
            }

            showNotice(data.i18n.saved || 'Salvo!', false);
        })
        .catch(function (err) {
            showNotice((data.i18n.saveError || 'Erro ao salvar.') + ' ' + err.message, true);
        })
        .finally(function () {
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Salvar';
        });
    }

    function showNotice(msg, isError) {
        if (!saveNotice) return;
        saveNotice.textContent = msg;
        saveNotice.className   = 'zapwa-save-notice' + (isError ? ' error' : '');
        saveNotice.style.display = 'block';
        saveNotice.style.opacity = '1';
        setTimeout(function () {
            saveNotice.style.opacity = '0';
            setTimeout(function () { saveNotice.style.display = 'none'; }, 400);
        }, 3000);
    }

    // -----------------------------------------------------------------------
    // Utility
    // -----------------------------------------------------------------------
    function uid() {
        return 'e' + Math.random().toString(36).slice(2, 9);
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function truncate(str, maxLen) {
        str = String(str);
        return esc(str.length > maxLen ? str.slice(0, maxLen) + '…' : str);
    }

    function svgEl(tag) {
        return document.createElementNS('http://www.w3.org/2000/svg', tag);
    }

    function nodeLabel(type) {
        var labels = {
            send_whatsapp: 'Enviar WhatsApp',
            send_email:    'Enviar Email',
            delay:         'Delay',
            condition:     'Condição',
            end:           'Fim',
        };
        return labels[type] || type;
    }

    function nodeIcon(type) {
        var icons = {
            send_whatsapp: '💬',
            send_email:    '✉️',
            delay:         '⏳',
            condition:     '🔀',
            end:           '🏁',
        };
        return icons[type] || '📦';
    }

    // -----------------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------------
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());
