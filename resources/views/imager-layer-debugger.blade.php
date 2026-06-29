@php
    $debugParams = [
        'gender' => $gender ?? 'M',
        'figure' => $figure ?? '',
        'direction' => (int) ($direction ?? 2),
        'head_direction' => (int) ($headDirection ?? 2),
        'gesture' => $gesture ?? 'nrm',
        'action' => $action ?? 'std',
        'frame' => (int) ($frame ?? 0),
        'head_only' => !empty($headOnly) ? 1 : 0,
    ];

    $layersDataUrl = route('imager.debug.layers-data');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imager Layer Debugger</title>
    <style>
        * { box-sizing: border-box; }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #111;
            color: #f5f5f5;
        }
        .stage-wrap {
            overscroll-behavior: none;
                cursor: grab;
        }


        .debugger {
            display: grid;
            grid-template-columns: 340px 1fr;
            height: 100vh;
            min-height: 100vh;
        }

        .sidebar {
            border-right: 1px solid rgba(255,255,255,.12);
            background: #171717;
            padding: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .stage-wrap {
            position: relative;
            overflow: auto;
            background:
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            flex: 0 0 auto;
        }

        .toolbar button {
            background: #262626;
            border: 1px solid rgba(255,255,255,.12);
            color: #fff;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        .zoom-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            flex: 0 0 auto;
            font-size: 12px;
            color: #cfcfcf;
        }

        .zoom-row input[type="range"] {
            width: 100%;
            accent-color: #4db2ff;
        }

        .zoom-readout {
            min-width: 56px;
            text-align: right;
            font-weight: 700;
            color: #fff;
        }

        .meta {
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 12px;
            color: #cfcfcf;
            word-break: break-word;
            flex: 0 0 auto;
        }

        .layer-list-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            border-top: 1px solid rgba(255,255,255,.08);
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: 12px;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .layer-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .layer-row {
            border: 1px solid rgba(255,255,255,.08);
            background: #202020;
            border-radius: 10px;
            padding: 10px;
            cursor: grab;
            user-select: none;
        }

        .layer-row.active {
            border-color: #6db2ff;
            background: #1f2d3d;
        }

        .layer-row.hidden {
            opacity: .45;
        }

        .layer-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .layer-sub {
            font-size: 11px;
            color: #b8b8b8;
            line-height: 1.45;
        }

        .row-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .row-actions button {
            flex: 1;
            border: 1px solid rgba(255,255,255,.1);
            background: #2c2c2c;
            color: #fff;
            border-radius: 8px;
            padding: 6px 8px;
            cursor: pointer;
        }

        .canvas-host {
            position: relative;
            min-width: 2200px;
            min-height: 1800px;
            overflow: visible;
        }

        .zoom-stage {
            position: absolute;
            left: 0;
            top: 0;
            transform-origin: top left;
        }

        .origin-cross {
            position: absolute;
            left: 800px;
            top: 600px;
            width: 1px;
            height: 1px;
            overflow: visible;
            pointer-events: none;
        }

        .origin-cross::before,
        .origin-cross::after {
            content: "";
            position: absolute;
            background: rgba(255, 80, 80, .7);
        }

        .origin-cross::before {
            width: 2600px;
            height: 1px;
            left: -1300px;
            top: 0;
        }

        .origin-cross::after {
            width: 1px;
            height: 2600px;
            left: 0;
            top: -1300px;
        }

        .bounds-box {
            position: absolute;
            border: 2px dashed rgba(255, 214, 10, .9);
            pointer-events: none;
            display: none;
        }

        .bounds-box.visible {
            display: block;
        }

        .layer-el {
            position: absolute;
            cursor: move;
            user-select: none;
            overflow: visible;
            touch-action: none;
        }

        .layer-inner {
            position: absolute;
            left: 0;
            top: 0;
            transform-origin: top left;
        }

        .layer-el img {
            display: block;
            width: 100%;
            height: 100%;
            image-rendering: pixelated;
            pointer-events: none;
            user-select: none;
            -webkit-user-drag: none;
        }

        .layer-el.show-boxes::after {
            content: "";
            position: absolute;
            inset: -1px;
            border: 1px solid rgba(255, 255, 255, .28);
            pointer-events: none;
        }

        .layer-el.active::after {
            content: "";
            position: absolute;
            inset: -1px;
            border: 1px solid #4db2ff;
            pointer-events: none;
        }

        .layer-el.out-of-bounds::before {
            content: "";
            position: absolute;
            inset: -1px;
            border: 1px dashed rgba(255, 72, 72, .95);
            pointer-events: none;
        }

        .layer-el.active.out-of-bounds::before {
            border: 1px dashed rgba(255, 72, 72, .95);
        }

        .floating-info {
            flex: 0 0 auto;
            background: rgba(0,0,0,.45);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px;
            padding: 12px;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .error {
            padding: 20px;
            color: #ff9e9e;
        }

        .badge {
            display: inline-block;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #313131;
            margin-left: 6px;
        }
    </style>
</head>
<body>
<div class="debugger">
    <aside class="sidebar">
        <div class="toolbar">
            <button type="button" id="toggle-bounds">Toggle Bounds</button>
            <button type="button" id="toggle-layer-boxes">Toggle Layer Boxes</button>
            <button type="button" id="reset-positions">Reset Drag</button>
            <button type="button" id="reset-order">Reset Order</button>
        </div>

        <div class="zoom-row">
            <label for="zoom-slider">Zoom</label>
            <input id="zoom-slider" type="range" min="25" max="400" step="5" value="100">
            <div class="zoom-readout" id="zoom-readout">100%</div>
        </div>

        <div class="meta" id="meta-block">Loading...</div>

        <div class="layer-list-wrap">
            <div class="layer-list" id="layer-list"></div>
        </div>

        <div class="floating-info" id="selected-info">Nothing selected.</div>
    </aside>

    <main class="stage-wrap" id="stage-wrap">
        <div class="canvas-host" id="canvas-host">
            <div class="zoom-stage" id="zoom-stage">
                <div class="origin-cross"></div>
                <div class="bounds-box" id="bounds-box"></div>
            </div>
        </div>
    </main>
</div>

<script>
    const debugParams = {!! json_encode($debugParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    const layersDataUrl = {!! json_encode($layersDataUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};

    const dataUrl = new URL(layersDataUrl, window.location.origin);
    Object.entries(debugParams).forEach(function(entry) {
        dataUrl.searchParams.set(entry[0], entry[1]);
    });

    const state = {
        boundsVisible: true,
        layerBoxesVisible: true,
        selectedId: null,
        layers: [],
        originalLayers: [],
        bounds: {},
        zoom: 1,
    };

    const canvasHost = document.getElementById('canvas-host');
    const zoomStage = document.getElementById('zoom-stage');
    const stageWrap = document.getElementById('stage-wrap');
    const layerList = document.getElementById('layer-list');
    const selectedInfo = document.getElementById('selected-info');
    const metaBlock = document.getElementById('meta-block');
    const boundsBox = document.getElementById('bounds-box');
    const zoomSlider = document.getElementById('zoom-slider');
    const zoomReadout = document.getElementById('zoom-readout');
    

let isPanning = false;
let panStartX = 0;
let panStartY = 0;
let startScrollLeft = 0;
let startScrollTop = 0;

stageWrap.addEventListener('pointerdown', (e) => {
    if (e.target.closest('.layer-el')) return;

    isPanning = true;
    panStartX = e.clientX;
    panStartY = e.clientY;

    startScrollLeft = stageWrap.scrollLeft;
    startScrollTop = stageWrap.scrollTop;

    stageWrap.style.cursor = 'grabbing';

    stageWrap.setPointerCapture?.(e.pointerId);
});

stageWrap.addEventListener('pointermove', (e) => {
    if (!isPanning) return;

    const dx = e.clientX - panStartX;
    const dy = e.clientY - panStartY;

    stageWrap.scrollLeft = startScrollLeft - dx;
    stageWrap.scrollTop = startScrollTop - dy;
});

function stopPan(e) {
    if (!isPanning) return;

    isPanning = false;
    stageWrap.style.cursor = 'default';

    try {
        stageWrap.releasePointerCapture?.(e.pointerId);
    } catch {}
}

stageWrap.addEventListener('pointerup', stopPan);
stageWrap.addEventListener('pointercancel', stopPan);
stageWrap.addEventListener('mouseleave', stopPan);

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function deepClone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function getStageOrigin() {
        return { x: 800, y: 600 };
    }

    function setMeta(meta) {
        metaBlock.innerHTML =

            '<strong>Gender:</strong> ' + escapeHtml(meta.gender) + '<br>' +
            '<strong>Figure:</strong> ' + escapeHtml(meta.figure) + '<br>' +
            '<strong>Action:</strong> ' + escapeHtml(meta.action) + '<br>' +
            '<strong>Gesture:</strong> ' + escapeHtml(meta.gesture) + '<br>' +
            '<strong>Direction:</strong> ' + escapeHtml(meta.direction) + '<br>' +
            '<strong>Head Direction:</strong> ' + escapeHtml(meta.head_direction) + '<br>' +
            '<strong>Frame:</strong> ' + escapeHtml(meta.frame) + '<br>' +
            '<strong>Head Only:</strong> ' + (meta.head_only ? 'yes' : 'no');
    }

    function updateZoom(originX = 0, originY = 0) {
    const prevZoom = state.prevZoom || 1;
    const newZoom = state.zoom;

    const rect = stageWrap.getBoundingClientRect();

    const offsetX = originX - rect.left;
    const offsetY = originY - rect.top;

    const scrollLeft = stageWrap.scrollLeft;
    const scrollTop = stageWrap.scrollTop;

    const zoomFactor = newZoom / prevZoom;

    stageWrap.scrollLeft = (scrollLeft + offsetX) * zoomFactor - offsetX;
    stageWrap.scrollTop = (scrollTop + offsetY) * zoomFactor - offsetY;

    zoomStage.style.transform = `scale(${newZoom})`;
    zoomReadout.textContent = Math.round(newZoom * 100) + '%';

    state.prevZoom = newZoom;
}

stageWrap.addEventListener('wheel', function(e) {
    if (!e.ctrlKey) return;

    e.preventDefault();

    const zoomIntensity = 0.0015;
    const delta = -e.deltaY * zoomIntensity;

    let newZoom = state.zoom * (1 + delta);

    newZoom = Math.max(0.25, Math.min(4, newZoom));

    state.zoom = newZoom;

    updateZoom(e.clientX, e.clientY);
}, { passive: false });

    function updateBounds(bounds) {
    const origin = getStageOrigin();
    
    if (state.layers && state.layers.length > 0) {
        let minX = Infinity;
        let minY = Infinity;
        let maxX = -Infinity;
        let maxY = -Infinity;
        
        state.layers.forEach(layer => {
            const left = layer.ui_x;
            const top = layer.ui_y;
            const right = left + layer.width;
            const bottom = top + layer.height;
            
            minX = Math.min(minX, left);
            minY = Math.min(minY, top);
            maxX = Math.max(maxX, right);
            maxY = Math.max(maxY, bottom);
        });
        
        if (minX !== Infinity) {
            boundsBox.style.left = (origin.x + minX) + 'px';
            boundsBox.style.top = (origin.y + minY) + 'px';
            boundsBox.style.width = (maxX - minX) + 'px';
            boundsBox.style.height = (maxY - minY) + 'px';
            boundsBox.classList.toggle('visible', state.boundsVisible);
            return;
        }
    }
    
    let canvasWidth = Number(bounds.canvas_width || 0);
    let canvasHeight = Number(bounds.canvas_height || 0);
    let minX = Number(bounds.min_x || 0);
    let minY = Number(bounds.min_y || 0);
    
    if (canvasWidth === 0 && bounds.max_x !== undefined) {
        canvasWidth = (bounds.max_x - minX) + 1;
        canvasHeight = (bounds.max_y - minY) + 1;
    }
    
    boundsBox.style.left = (origin.x + minX) + 'px';
    boundsBox.style.top = (origin.y + minY) + 'px';
    boundsBox.style.width = Math.max(1, canvasWidth) + 'px';
    boundsBox.style.height = Math.max(1, canvasHeight) + 'px';
    boundsBox.classList.toggle('visible', state.boundsVisible);
}

    function isOutOfBounds(layer) {
        const bounds = state.bounds || {};
        const canvasWidth = Number(bounds.canvas_width || 0);
        const canvasHeight = Number(bounds.canvas_height || 0);

        const left = Number(layer.ui_x || 0);
        const top = Number(layer.ui_y || 0);
        const right = left + Number(layer.width || 0);
        const bottom = top + Number(layer.height || 0);

        return left < 0 || top < 0 || right > canvasWidth || bottom > canvasHeight;
    }

    function getOriginalLayer(layerId) {
        return state.originalLayers.find(function(item) {
            return item.id === layerId;
        }) || null;
    }

    function renderSelectedInfo() {
        const layer = state.layers.find(function(item) {
            return item.id === state.selectedId;
        });

        if (!layer) {
            selectedInfo.textContent = 'Nothing selected.';
            return;
        }

        const original = getOriginalLayer(layer.id);
        const originalX = Number(original ? original.ui_x : layer.ui_x || 0);
        const originalY = Number(original ? original.ui_y : layer.ui_y || 0);
        const currentX = Number(layer.ui_x || 0);
        const currentY = Number(layer.ui_y || 0);
        const deltaX = currentX - originalX;
        const deltaY = currentY - originalY;

        selectedInfo.textContent =
            '#' + layer.order + '\n' +
            'set_type: ' + (layer.set_type || '') + '\n' +
            'part_type: ' + (layer.part_type || '') + '\n' +
            'render_part_type: ' + (layer.render_part_type || '') + '\n' +
            'source_part_type: ' + (layer.source_part_type || '') + '\n' +
            'part_id: ' + (layer.part_id || '') + '\n' +
            'symbol_name: ' + (layer.symbol_name || '') + '\n' +
            'action: ' + (layer.action || '') + '\n' +
            'requested_direction: ' + (layer.requested_direction || '') + '\n' +
            'source_direction: ' + (layer.source_direction || '') + '\n' +
            'mirrored: ' + (layer.mirrored ? 'yes' : 'no') + '\n' +
            'original_x: ' + originalX + '\n' +
            'original_y: ' + originalY + '\n' +
            'current_x: ' + currentX + '\n' +
            'current_y: ' + currentY + '\n' +
            'offset_dx: ' + deltaX + '\n' +
            'offset_dy: ' + deltaY + '\n' +
            'w: ' + (layer.width || 0) + '\n' +
            'h: ' + (layer.height || 0) + '\n' +
            'relative_path: ' + (layer.relative_path || '');
    }

    function syncZIndexes() {
        state.layers.forEach(function(layer, index) {
            layer.order = index;
            const el = document.querySelector('[data-layer-id="' + layer.id + '"]');
            if (el) {
                el.style.zIndex = String(index + 1);
            }
        });
    }

    function renderLayerList() {
        layerList.innerHTML = '';

        state.layers.forEach(function(layer, index) {
            const original = getOriginalLayer(layer.id);
            const originalX = Number(original ? original.ui_x : layer.ui_x || 0);
            const originalY = Number(original ? original.ui_y : layer.ui_y || 0);
            const currentX = Number(layer.ui_x || 0);
            const currentY = Number(layer.ui_y || 0);
            const deltaX = currentX - originalX;
            const deltaY = currentY - originalY;

            const row = document.createElement('div');
            row.className = 'layer-row' +
                (state.selectedId === layer.id ? ' active' : '') +
                (layer.hidden ? ' hidden' : '');
            row.draggable = true;
            row.dataset.layerId = layer.id;

            row.innerHTML =
                '<div class="layer-title">' +
                    '#' + index + ' ' + escapeHtml(layer.set_type) + '/' + escapeHtml(layer.part_type) +
                    (layer.mirrored ? '<span class="badge">mirrored</span>' : '') +
                    (isOutOfBounds(layer) ? '<span class="badge">out</span>' : '') +
                '</div>' +
                '<div class="layer-sub">' +
                    'id: ' + escapeHtml(layer.part_id) + '<br>' +
                    'symbol: ' + escapeHtml(layer.symbol_name) + '<br>' +
                    'og x/y: ' + escapeHtml(originalX) + ' / ' + escapeHtml(originalY) + '<br>' +
                    'cur x/y: ' + escapeHtml(currentX) + ' / ' + escapeHtml(currentY) + '<br>' +
                    'delta: ' + escapeHtml(deltaX) + ' / ' + escapeHtml(deltaY) + '<br>' +
                    'req dir: ' + escapeHtml(layer.requested_direction) + ' / src dir: ' + escapeHtml(layer.source_direction) +
                '</div>' +
                '<div class="row-actions">' +
                    '<button type="button" data-action="select">Select</button>' +
                    '<button type="button" data-action="toggle">' + (layer.hidden ? 'Show' : 'Hide') + '</button>' +
                '</div>';

            row.querySelector('[data-action="select"]').addEventListener('click', function() {
                state.selectedId = layer.id;
                renderAll();
            });

            row.querySelector('[data-action="toggle"]').addEventListener('click', function() {
                layer.hidden = !layer.hidden;
                renderCanvasOnly();
                renderLayerList();
                renderSelectedInfo();
            });

            row.addEventListener('dragstart', function(event) {
                event.dataTransfer.setData('text/plain', layer.id);
            });

            row.addEventListener('dragover', function(event) {
                event.preventDefault();
            });

            row.addEventListener('drop', function(event) {
                event.preventDefault();

                const draggedId = event.dataTransfer.getData('text/plain');
                const fromIndex = state.layers.findIndex(function(item) {
                    return item.id === draggedId;
                });
                const toIndex = state.layers.findIndex(function(item) {
                    return item.id === layer.id;
                });

                if (fromIndex === -1 || toIndex === -1 || fromIndex === toIndex) {
                    return;
                }

                const moved = state.layers.splice(fromIndex, 1)[0];
                state.layers.splice(toIndex, 0, moved);

                syncZIndexes();
                renderAll();
            });

            row.addEventListener('click', function(event) {
                if (event.target.closest('button')) {
                    return;
                }

                state.selectedId = layer.id;
                renderAll();
            });

            layerList.appendChild(row);
        });
    }

    function makeLayerDraggable(el, layer) {
        let dragging = false;
        let startX = 0;
        let startY = 0;
        let baseX = 0;
        let baseY = 0;

        el.addEventListener('pointerdown', function(event) {
            event.preventDefault();
            dragging = true;
            startX = event.clientX;
            startY = event.clientY;
            baseX = Number(layer.ui_x || 0);
            baseY = Number(layer.ui_y || 0);
            state.selectedId = layer.id;

            el.classList.add('active');

            if (el.setPointerCapture) {
                el.setPointerCapture(event.pointerId);
            }

            renderLayerList();
            renderSelectedInfo();
        });

        el.addEventListener('pointermove', function(event) {
            if (!dragging) {
                return;
            }

            const dx = (event.clientX - startX) / state.zoom;
            const dy = (event.clientY - startY) / state.zoom;

            layer.ui_x = baseX + dx;
            layer.ui_y = baseY + dy;

            applyLayerPosition(el, layer);
            renderLayerList();
            renderSelectedInfo();
        });

function endDrag(event) {
    if (!dragging) {
        return;
    }

    dragging = false;

    if (event && el.releasePointerCapture) {
        try {
            el.releasePointerCapture(event.pointerId);
        } catch (e) {}
    }

    renderLayerList();
    renderSelectedInfo();
}

        el.addEventListener('pointerup', endDrag);
        el.addEventListener('pointercancel', endDrag);
    }

    function applyLayerPosition(el, layer) {
        const origin = getStageOrigin();
        const left = origin.x + Number(layer.ui_x || 0);
        const top = origin.y + Number(layer.ui_y || 0);
        const width = Number(layer.width || 0);
        const height = Number(layer.height || 0);

        el.style.left = left + 'px';
        el.style.top = top + 'px';
        el.style.width = width + 'px';
        el.style.height = height + 'px';
        el.style.display = layer.hidden ? 'none' : 'block';
        el.classList.toggle('active', state.selectedId === layer.id);
        el.classList.toggle('out-of-bounds', isOutOfBounds(layer));
        el.classList.toggle('show-boxes', state.layerBoxesVisible);

        const inner = el.querySelector('.layer-inner');
        if (inner) {
            inner.style.width = width + 'px';
            inner.style.height = height + 'px';
            inner.style.transformOrigin = 'top left';
            inner.style.transform = layer.mirrored
                ? 'translateX(' + width + 'px) scaleX(-1)'
                : 'translateX(0px) scaleX(1)';
        }
    }

    function renderCanvasOnly() {
        Array.prototype.slice.call(zoomStage.querySelectorAll('.layer-el')).forEach(function(el) {
            el.remove();
        });

        state.layers.forEach(function(layer, index) {
            const el = document.createElement('div');
            el.className = 'layer-el';
            el.dataset.layerId = layer.id;
            el.style.zIndex = String(index + 1);

            const inner = document.createElement('div');
            inner.className = 'layer-inner';

            const img = document.createElement('img');
            img.src = layer.asset_url || '';
            img.alt = '';
            img.width = Number(layer.width || 0);
            img.height = Number(layer.height || 0);

            inner.appendChild(img);
            el.appendChild(inner);

            applyLayerPosition(el, layer);

            el.addEventListener('click', function(event) {
                event.stopPropagation();
                state.selectedId = layer.id;
                renderLayerList();
                renderSelectedInfo();
                zoomStage.querySelectorAll('.layer-el').forEach(function(node) {
                    node.classList.toggle('active', node.dataset.layerId === layer.id);
                });
            });

            makeLayerDraggable(el, layer);
            zoomStage.appendChild(el);
        });
    }

    function renderAll() {
        syncZIndexes();
        renderCanvasOnly();
        renderLayerList();
        renderSelectedInfo();
        updateBounds(state.bounds || {});
        updateZoom();
    }

    async function boot() {
        try {
            const response = await fetch(dataUrl.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            const payload = await response.json();

            if (!payload.ok) {
                throw new Error(payload.message || 'Unknown debugger error.');
            }

            state.bounds = payload.bounds || {};
            state.layers = (payload.layers || []).map(function(layer, index) {
                return Object.assign({}, layer, {
                    id: 'layer-' + index,
                    order: index,
                    hidden: false,
                    ui_x: Number(layer.ui_x || 0),
                    ui_y: Number(layer.ui_y || 0),
                    width: Number(layer.width || 0),
                    height: Number(layer.height || 0),
                });
            });

            state.originalLayers = deepClone(state.layers);

            setMeta(payload.meta || {});
            renderAll();
        } catch (error) {
            layerList.innerHTML = '<div class="error">' + escapeHtml(error.message || 'Failed to load debugger data.') + '</div>';
            metaBlock.textContent = 'Failed to load debugger.';
        }
    }

    document.getElementById('toggle-bounds').addEventListener('click', function() {
        state.boundsVisible = !state.boundsVisible;
        updateBounds(state.bounds || {});
    });

    document.getElementById('toggle-layer-boxes').addEventListener('click', function() {
        state.layerBoxesVisible = !state.layerBoxesVisible;
        zoomStage.querySelectorAll('.layer-el').forEach(function(el) {
            el.classList.toggle('show-boxes', state.layerBoxesVisible);
        });
    });

    document.getElementById('reset-positions').addEventListener('click', function() {
        state.layers.forEach(function(layer, index) {
            layer.ui_x = Number((state.originalLayers[index] && state.originalLayers[index].ui_x) || layer.ui_x);
            layer.ui_y = Number((state.originalLayers[index] && state.originalLayers[index].ui_y) || layer.ui_y);
        });
        renderAll();
    });

    document.getElementById('reset-order').addEventListener('click', function() {
        state.layers = deepClone(state.originalLayers).map(function(layer, index) {
            return Object.assign({}, layer, {
                id: 'layer-' + index,
                order: index,
                hidden: state.layers[index] ? state.layers[index].hidden : false
            });
        });
        renderAll();
    });

    zoomSlider.addEventListener('input', function() {
        state.zoom = Number(zoomSlider.value || 100) / 100;
        updateZoom();
    });

    zoomStage.addEventListener('click', function(event) {
        if (event.target !== zoomStage) {
            return;
        }

        state.selectedId = null;
        zoomStage.querySelectorAll('.layer-el').forEach(function(node) {
            node.classList.remove('active');
        });
        renderLayerList();
        renderSelectedInfo();
    });

    boot();
</script>
</body>
</html>
