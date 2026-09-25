@extends('layouts.app')

@section('title', '3D Playground')

@section('content')
    <style>
.playground-heading {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:18px
}
.playground-heading h1 {
    margin-bottom:3px
}
.playground-heading p {
    margin:0;
    font-size:14px
}
.playground-button {
    min-height:36px;
    padding:0 13px;
    border:1px solid #c7d9f4;
    border-radius:9px;
    background:#fff;
    color:#17417e;
    cursor:pointer;
    font:inherit;
    font-size:13px;
    font-weight:700
}
.playground-button:hover,.playground-button.active {
    border-color:#2563eb;
    background:#eaf2ff;
    color:#174cb1
}
.playground-button:focus-visible,.model-choice:focus-visible,.library-card:focus-visible {
    outline:3px solid #93c5fd;
    outline-offset:2px
}
.playground-button.primary {
    border-color:#2563eb;
    background:#2563eb;
    color:#fff
}
.playground-actions {
    display:flex;
    flex-wrap:wrap;
    gap:8px
}
.playground-workspace {
    display:grid;
    grid-template-columns:175px minmax(320px,1fr) 240px;
    min-height:620px;
    height:calc(100vh - 205px);
    max-height:900px;
    overflow:hidden;
    border:1px solid #d5e4f7;
    border-radius:16px;
    background:#fff;
    box-shadow:0 14px 36px rgba(30,88,171,.1)
}
.playground-panel {
    padding:16px 12px;
    background:#f8fbff
}
.playground-panel.left {
    display:flex;
    flex-direction:column;
    border-right:1px solid #dce8f8
}
.panel-tabs {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:4px;
    margin:0 2px 14px;
    padding:3px;
    border-radius:10px;
    background:#eaf1fa
}
.panel-tab {
    min-width:0;
    padding:8px 5px;
    border:0;
    border-radius:7px;
    background:transparent;
    color:#64748b;
    cursor:pointer;
    font:inherit;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase
}
.panel-tab.active {
    background:#fff;
    color:#174cb1;
    box-shadow:0 1px 4px rgba(30,64,110,.12)
}
.panel-tab-content {
    display:flex;
    min-height:0;
    flex:1;
    flex-direction:column
}
.panel-tab-content[hidden] {
    display:none
}
.playground-panel.right {
    border-left:1px solid #dce8f8;
    overflow:auto
}
.panel-title {
    margin:0 0 12px;
    padding:0 6px;
    color:#54709e;
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase
}
.model-choice {
    display:flex;
    align-items:center;
    gap:9px;
    width:100%;
    margin:3px 0;
    padding:11px 10px;
    border:1px solid transparent;
    border-radius:9px;
    background:transparent;
    color:#29466b;
    cursor:pointer;
    text-align:left;
    font:inherit;
    font-size:13px;
    font-weight:700
}
.model-choice:hover {
    background:#eaf2ff
}
.model-choice.active {
    border-color:#bfd6ff;
    background:#e3efff;
    color:#174cb1
}
.scene-object-list {
    min-height:0;
    flex:1;
    overflow:auto
}
.scene-empty {
    margin:8px 7px;
    color:#7b8da8;
    font-size:12px;
    line-height:1.45
}
.add-object-button {
    width:100%;
    margin-top:12px
}
.history-actions {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:6px;
    margin-bottom:12px
}
.history-actions .playground-button {
    min-width:0;
    padding:0 5px
}
.history-actions .playground-button:disabled {
    opacity:.45;
    cursor:not-allowed
}
.history-list {
    min-height:0;
    overflow:auto
}
.history-entry {
    display:grid;
    grid-template-columns:9px minmax(0,1fr);
    gap:8px;
    width:100%;
    margin:2px 0;
    padding:9px 7px;
    border:1px solid transparent;
    border-radius:8px;
    background:transparent;
    color:#29466b;
    cursor:pointer;
    text-align:left;
    font:inherit
}
.history-entry:hover {
    background:#edf4ff
}
.history-entry.active {
    border-color:#bfd6ff;
    background:#e3efff
}
.history-entry.undone {
    opacity:.48
}
.history-marker {
    width:8px;
    height:8px;
    margin-top:4px;
    border:2px solid #3b82f6;
    border-radius:50%
}
.history-entry.active .history-marker {
    background:#2563eb
}
.history-label {
    display:block;
    overflow:hidden;
    font-size:12px;
    font-weight:700;
    text-overflow:ellipsis;
    white-space:nowrap
}
.history-time {
    display:block;
    margin-top:2px;
    color:#7b8da8;
    font-size:10px
}
.model-dot {
    width:10px;
    height:10px;
    flex:none;
    border-radius:50%;
    background:#3b82f6
}
.model-choice:nth-of-type(2) .model-dot {
    background:#14b8a6
}
.model-choice:nth-of-type(3) .model-dot {
    background:#f59e0b
}
.playground-viewport {
    position:relative;
    min-width:0;
    overflow:hidden;
    background:radial-gradient(circle at 50% 40%,#f4f9ff,#e5effc 75%)
}
#playground-canvas {
    position:absolute;
    inset:0
}
#playground-canvas canvas {
    display:block;
    width:100%;
    height:100%;
    outline:none;
    cursor:default
}
.selection-marquee {
    position:absolute;
    z-index:4;
    display:none;
    border:1px solid #2563eb;
    background:rgba(37,99,235,.13);
    pointer-events:none
}
.viewport-toolbar {
    position:absolute;
    z-index:2;
    top:14px;
    left:14px;
    display:flex;
    gap:6px
}
.viewport-hint {
    position:absolute;
    z-index:2;
    bottom:14px;
    left:14px;
    right:14px;
    width:max-content;
    max-width:calc(100% - 28px);
    padding:8px 11px;
    border:1px solid #d5e4f7;
    border-radius:8px;
    background:rgba(255,255,255,.88);
    color:#526b8c;
    font-size:12px;
    pointer-events:none
}
.viewport-status {
    position:absolute;
    z-index:2;
    top:14px;
    right:14px;
    max-width:45%;
    padding:8px 11px;
    border-radius:8px;
    background:rgba(255,255,255,.9);
    color:#36577b;
    font-size:12px
}
.transform-section {
    margin-bottom:20px
}
.transform-section h3 {
    margin:0 0 9px;
    color:#29466b;
    font-size:13px
}
.axis-row {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:6px
}
.axis-field span {
    display:block;
    margin-bottom:4px;
    color:#64748b;
    font-size:11px;
    font-weight:800
}
.axis-field input {
    width:100%;
    min-width:0;
    height:36px;
    padding:0 6px;
    text-align:center;
    font-size:12px
}
.playground-panel input:disabled {
    opacity:.5
}
.panel-note {
    margin:0 5px;
    color:#64748b;
    font-size:12px;
    line-height:1.5
}
.selected-name {
    margin:0 5px 20px;
    color:#17376e;
    font-size:17px;
    font-weight:800
}
.object-library {
    width:min(560px,calc(100vw - 32px));
    max-height:min(680px,calc(100vh - 32px));
    padding:0;
    overflow:hidden;
    border:1px solid #c9dcf5;
    border-radius:16px;
    box-shadow:0 24px 70px rgba(22,54,101,.28)
}
.object-library::backdrop {
    background:rgba(20,39,67,.45);
    backdrop-filter:blur(2px)
}
.library-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:18px 20px;
    border-bottom:1px solid #dce8f8
}
.library-header h2 {
    margin:0;
    color:#17376e;
    font-size:18px
}
.library-close {
    width:34px;
    height:34px;
    padding:0;
    border:0;
    border-radius:8px;
    background:#eef5ff;
    color:#29466b;
    cursor:pointer;
    font-size:22px
}
.library-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
    gap:12px;
    max-height:520px;
    padding:18px;
    overflow:auto
}
.library-card {
    min-height:130px;
    padding:16px 12px;
    border:1px solid #d5e4f7;
    border-radius:12px;
    background:#f8fbff;
    color:#29466b;
    cursor:pointer;
    font:inherit;
    font-size:13px;
    font-weight:700
}
.library-card:hover {
    border-color:#75a7f5;
    background:#eaf2ff
}
.library-card-icon {
    display:block;
    margin-bottom:10px;
    color:#3b82f6;
    font-size:38px;
    line-height:1
}
@media(max-width:1100px) {
    .playground-workspace {
        grid-template-columns:145px minmax(280px,1fr) 205px
    }
    .playground-panel {
        padding:12px 8px
    }
}
@media(max-width:900px) {
    .playground-workspace {
        min-width:780px
    }
    .playground-heading {
        align-items:flex-start;
        flex-direction:column
    }
}
    </style>

    <div class="playground-heading">
        <div>
            <h1>3D Playground</h1>
            <p>Arrange, select, and manage models in a desktop workspace.</p>
        </div>
        <div class="playground-actions">
            <button id="reset-layout" class="playground-button" type="button">Reset layout</button>
            <button id="download-layout" class="playground-button primary" type="button">Download positions</button>
        </div>
    </div>

    <div class="playground-workspace">
        <aside class="playground-panel left" aria-label="Models">
            <div class="panel-tabs" role="tablist" aria-label="Scene panel">
                <button class="panel-tab active" type="button" role="tab" aria-selected="true" aria-controls="objects-panel" data-panel-tab="objects">Objects</button>
                <button class="panel-tab" type="button" role="tab" aria-selected="false" aria-controls="history-panel" data-panel-tab="history">History</button>
            </div>
            <section id="objects-panel" class="panel-tab-content" role="tabpanel" data-panel-content="objects">
                <h2 class="panel-title">Scene objects</h2>
                <div id="scene-object-list" class="scene-object-list"></div>
                <button id="add-object" class="playground-button primary add-object-button" type="button">+ Add object</button>
            </section>
            <section id="history-panel" class="panel-tab-content" role="tabpanel" data-panel-content="history" hidden>
                <h2 class="panel-title">Session history</h2>
                <div class="history-actions">
                    <button id="undo-change" class="playground-button" type="button" disabled>Undo</button>
                    <button id="redo-change" class="playground-button" type="button" disabled>Redo</button>
                </div>
                <div id="history-list" class="history-list"></div>
            </section>
        </aside>

        <div class="playground-viewport">
            <div id="playground-canvas" aria-label="3D model editor"></div>
            <div id="selection-marquee" class="selection-marquee" aria-hidden="true"></div>
            <div class="viewport-toolbar" aria-label="Transform tools">
                <button class="playground-button active" type="button" data-mode="translate" title="Move (G)">Move</button>
                <button class="playground-button" type="button" data-mode="rotate" title="Rotate (R)">Rotate</button>
                <button class="playground-button" type="button" data-mode="scale" title="Scale (S)">Scale</button>
            </div>
            <div id="playground-status" class="viewport-status" role="status">Loading models&hellip;</div>
            <div class="viewport-hint">Click: select &middot; Shift+click: multi-select &middot; Drag empty space: box select &middot; MMB: orbit &middot; Shift+MMB: pan &middot; Delete: remove</div>
        </div>

        <aside class="playground-panel right" aria-label="Model properties">
            <h2 class="panel-title">Properties</h2>
            <div id="selected-name" class="selected-name">No selection</div>
            @foreach (['position' => 'Position', 'rotation' => 'Rotation (degrees)', 'scale' => 'Scale'] as $property => $label)
                <div class="transform-section">
                    <h3>{{ $label }}</h3>
                    <div class="axis-row">
                        @foreach (['x', 'y', 'z'] as $axis)
                            <label class="axis-field">
                                <span>{{ strtoupper($axis) }}</span>
                                <input type="number" step="0.01" data-property="{{ $property }}" data-axis="{{ $axis }}" aria-label="{{ $label }} {{ strtoupper($axis) }}" disabled>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p id="property-note" class="panel-note">Select an object to edit it. Changes are saved in this browser.</p>
        </aside>
    </div>

    <dialog id="object-library" class="object-library" aria-labelledby="object-library-title">
        <div class="library-header">
            <h2 id="object-library-title">Add an object</h2>
            <button id="close-object-library" class="library-close" type="button" aria-label="Close">&times;</button>
        </div>
        <div class="library-grid">
            @forelse ($modelLibrary as $asset)
                <button class="library-card" type="button" data-add-asset="{{ $asset['key'] }}">
                    <span class="library-card-icon" aria-hidden="true">&#11041;</span>
                    {{ $asset['label'] }}
                </button>
            @empty
                <p class="panel-note">No GLTF or GLB models were found.</p>
            @endforelse
        </div>
    </dialog>

    <script type="importmap">
        {
            "imports": {
                "three": "https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js",
                "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.1/examples/jsm/"
            }
        }
    </script>
    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { TransformControls } from 'three/addons/controls/TransformControls.js';
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

        const modelLibrary = @json($modelLibrary->values());
        const assetsByKey = new Map(modelLibrary.map(asset => [asset.key, asset]));
        const defaultAssetKey = modelLibrary[0]?.key ?? null;
        const storageKey = 'mindar-playground-layout-v2';
        const legacyStorageKey = 'mindar-playground-layout-v1';
        const defaults = [-2.3, 0, 2.3].map((x, index) => ({
            id: `object-${index + 1}`,
            name: `Model ${index + 1}`,
            asset: defaultAssetKey,
            position: [x, 0, 0],
            rotation: [0, 0, 0],
            scale: [1, 1, 1]
        }));
        const viewport = document.getElementById('playground-canvas');
        const status = document.getElementById('playground-status');
        const sceneObjectList = document.getElementById('scene-object-list');
        const historyList = document.getElementById('history-list');
        const undoButton = document.getElementById('undo-change');
        const redoButton = document.getElementById('redo-change');
        const selectionMarquee = document.getElementById('selection-marquee');
        const objectLibraryDialog = document.getElementById('object-library');
        const propertyNote = document.getElementById('property-note');
        const inputs = [...document.querySelectorAll('[data-property][data-axis]')];
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 1000);
        camera.position.set(6, 5, 8);
        camera.lookAt(0, 0.7, 0);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        viewport.appendChild(renderer.domElement);

        const orbit = new OrbitControls(camera, renderer.domElement);
        orbit.target.set(0, 0.7, 0);
        orbit.enableDamping = true;
        // Blender-style navigation: MMB orbits, Shift+MMB pans, and the wheel zooms.
        orbit.mouseButtons.LEFT = null;
        orbit.mouseButtons.MIDDLE = THREE.MOUSE.ROTATE;
        orbit.update();

        scene.add(new THREE.AmbientLight(0xffffff, 2));
        const sun = new THREE.DirectionalLight(0xffffff, 2.5);
        sun.position.set(4, 8, 5);
        scene.add(sun);
        const grid = new THREE.GridHelper(20, 20, 0x9db9dd, 0xcbdcf0);
        scene.add(grid);

        const selectionPivot = new THREE.Object3D();
        scene.add(selectionPivot);
        const transform = new TransformControls(camera, renderer.domElement);
        transform.addEventListener('dragging-changed', event => {
            orbit.enabled = !event.value && !marqueeDrag;
        });
        transform.addEventListener('mouseDown', beginMultiTransform);
        transform.addEventListener('mouseUp', finishMultiTransform);
        transform.addEventListener('objectChange', () => {
            applyMultiTransform();
            updateInputs();
            updateSelectionHelpers();
            saveLayout();
        });
        scene.add(transform);

        const models = [];
        const selectedModels = new Set();
        const selectionHelpers = new Map();
        const sourceCache = new Map();
        const loader = new GLTFLoader();
        let primarySelection = null;
        let marqueeDrag = null;
        let multiTransformSnapshot = null;
        let transformHistoryBefore = null;
        let nextObjectId = 4;
        let isHydrating = false;
        let historyBaseState = null;
        let historyIndex = 0;
        let historyBusy = false;
        const historyEntries = [];

        function hasVector(value, positive = false) {
            return Array.isArray(value) && value.length === 3 && value.every(number =>
                typeof number === 'number' && Number.isFinite(number) && (!positive || number > 0)
            );
        }

        function validRecord(record) {
            return record && assetsByKey.has(record.asset) && hasVector(record.position) &&
                hasVector(record.rotation) && hasVector(record.scale, true);
        }

        function readLayout() {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey));
                if (saved && Array.isArray(saved.objects) && saved.objects.every(validRecord)) {
                    nextObjectId = Math.max(1, Number(saved.nextObjectId) || 1);
                    return saved.objects;
                }

                const legacy = JSON.parse(localStorage.getItem(legacyStorageKey));
                if (defaultAssetKey && Array.isArray(legacy) && legacy.every(item =>
                    hasVector(item?.position) && hasVector(item?.rotation) && hasVector(item?.scale, true)
                )) {
                    return legacy.map((item, index) => ({
                        ...item,
                        id: `object-${index + 1}`,
                        name: `Model ${index + 1}`,
                        asset: defaultAssetKey
                    }));
                }
            } catch { /* Ignore invalid or unavailable browser storage. */ }

            return defaults.filter(validRecord);
        }

        function getLayout() {
            return {
                version: 2,
                nextObjectId,
                objects: models.map(model => ({
                    ...model.userData.editor,
                    position: model.position.toArray(),
                    rotation: [model.rotation.x, model.rotation.y, model.rotation.z].map(THREE.MathUtils.radToDeg),
                    scale: model.scale.toArray()
                }))
            };
        }

        function captureSceneState() {
            return JSON.parse(JSON.stringify(getLayout()));
        }

        function renderHistory() {
            historyList.replaceChildren();
            const entries = [{ label: 'Session started', timestamp: null, target: 0 },
                ...historyEntries.map((entry, index) => ({ ...entry, target: index + 1 }))];
            [...entries].reverse().forEach(entry => {
                const button = document.createElement('button');
                const marker = document.createElement('span');
                const text = document.createElement('span');
                const label = document.createElement('span');
                button.type = 'button';
                button.className = `history-entry${entry.target === historyIndex ? ' active' : ''}${entry.target > historyIndex ? ' undone' : ''}`;
                button.dataset.historyTarget = entry.target;
                button.disabled = historyBusy;
                marker.className = 'history-marker';
                label.className = 'history-label';
                label.textContent = entry.label;
                text.appendChild(label);
                if (entry.timestamp) {
                    const time = document.createElement('span');
                    time.className = 'history-time';
                    time.textContent = new Date(entry.timestamp).toLocaleTimeString([], {
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    });
                    text.appendChild(time);
                }
                button.append(marker, text);
                historyList.appendChild(button);
            });
            undoButton.disabled = historyBusy || historyIndex === 0;
            redoButton.disabled = historyBusy || historyIndex === historyEntries.length;
        }

        function recordHistory(label, before) {
            const after = captureSceneState();
            if (JSON.stringify(before) === JSON.stringify(after)) return;
            if (!historyBaseState) historyBaseState = before;
            historyEntries.splice(historyIndex);
            historyEntries.push({ label, before, after, timestamp: Date.now() });
            if (historyEntries.length > 100) {
                historyBaseState = historyEntries.shift().after;
            }
            historyIndex = historyEntries.length;
            renderHistory();
        }

        async function goToHistory(target) {
            if (historyBusy || target < 0 || target > historyEntries.length || target === historyIndex) return;
            const state = target === 0 ? historyBaseState : historyEntries[target - 1].after;
            if (!state) return;
            historyBusy = true;
            renderHistory();
            updateStatus(target < historyIndex ? 'Undoing change…' : 'Redoing change…');
            try {
                nextObjectId = state.nextObjectId;
                await replaceScene(state.objects);
                historyIndex = target;
            } catch (error) {
                console.error('Could not restore playground history:', error);
                updateStatus('Could not restore that history state.');
            } finally {
                historyBusy = false;
                renderHistory();
            }
        }

        function saveLayout() {
            if (isHydrating) return;
            try { localStorage.setItem(storageKey, JSON.stringify(getLayout())); } catch { /* Storage may be disabled. */ }
        }

        function loadSource(assetKey) {
            if (sourceCache.has(assetKey)) return sourceCache.get(assetKey);
            const asset = assetsByKey.get(assetKey);
            const promise = new Promise((resolve, reject) => {
                loader.load(asset.url, gltf => resolve(gltf.scene), undefined, reject);
            }).catch(error => {
                sourceCache.delete(assetKey);
                throw error;
            });
            sourceCache.set(assetKey, promise);
            return promise;
        }

        async function createModel(record) {
            const source = await loadSource(record.asset);
            const contents = source.clone(true);
            const box = new THREE.Box3().setFromObject(contents);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());
            const unit = 1.8 / Math.max(size.x, size.y, size.z, 0.001);
            contents.scale.setScalar(unit);
            contents.position.set(-center.x * unit, -box.min.y * unit, -center.z * unit);

            const root = new THREE.Group();
            root.userData.editor = { id: record.id, name: record.name, asset: record.asset };
            root.position.fromArray(record.position);
            root.rotation.set(...record.rotation.map(THREE.MathUtils.degToRad));
            root.scale.fromArray(record.scale);
            root.add(contents);
            scene.add(root);
            models.push(root);
            return root;
        }

        function removeAllModels() {
            transform.detach();
            selectionHelpers.forEach(helper => scene.remove(helper));
            selectionHelpers.clear();
            selectedModels.clear();
            primarySelection = null;
            models.forEach(model => scene.remove(model));
            models.splice(0);
        }

        async function replaceScene(records) {
            isHydrating = true;
            removeAllModels();
            try {
                for (const record of records) await createModel(record);
            } finally {
                isHydrating = false;
            }
            renderSceneList();
            setSelection(models.length ? [models[0]] : []);
            saveLayout();
        }

        function updateStatus(message = null) {
            if (message) {
                status.textContent = message;
                return;
            }
            const selected = selectedModels.size ? ` · ${selectedModels.size} selected` : '';
            status.textContent = `${models.length} ${models.length === 1 ? 'object' : 'objects'}${selected}`;
        }

        function renderSceneList() {
            sceneObjectList.replaceChildren();
            if (!models.length) {
                const empty = document.createElement('p');
                empty.className = 'scene-empty';
                empty.textContent = 'The scene is empty. Add an object to begin.';
                sceneObjectList.appendChild(empty);
                return;
            }
            models.forEach((model, index) => {
                const button = document.createElement('button');
                const dot = document.createElement('span');
                button.type = 'button';
                button.className = `model-choice${selectedModels.has(model) ? ' active' : ''}`;
                button.dataset.objectId = model.userData.editor.id;
                dot.className = 'model-dot';
                dot.style.background = `hsl(${(index * 137 + 214) % 360} 76% 52%)`;
                button.append(dot, document.createTextNode(model.userData.editor.name));
                sceneObjectList.appendChild(button);
            });
        }

        function updateSelectionHelpers() {
            selectionHelpers.forEach((helper, model) => {
                if (!selectedModels.has(model)) {
                    scene.remove(helper);
                    selectionHelpers.delete(model);
                }
            });
            selectedModels.forEach(model => {
                let helper = selectionHelpers.get(model);
                if (!helper) {
                    helper = new THREE.BoxHelper(model, 0x2563eb);
                    helper.material.depthTest = false;
                    helper.material.transparent = true;
                    helper.material.opacity = 0.9;
                    helper.renderOrder = 999;
                    scene.add(helper);
                    selectionHelpers.set(model, helper);
                }
                helper.material.color.set(model === primarySelection ? 0xf59e0b : 0x2563eb);
                helper.update();
            });
        }

        function selectionCenter() {
            const center = new THREE.Vector3();
            if (!selectedModels.size) return center;
            selectedModels.forEach(model => center.add(model.position));
            return center.divideScalar(selectedModels.size);
        }

        function configureTransformTarget() {
            multiTransformSnapshot = null;
            transform.detach();
            selectionPivot.position.copy(selectionCenter());
            selectionPivot.quaternion.identity();
            selectionPivot.scale.set(1, 1, 1);
            selectionPivot.updateMatrixWorld(true);

            if (selectedModels.size > 1) transform.attach(selectionPivot);
            else if (primarySelection) transform.attach(primarySelection);
        }

        function beginMultiTransform() {
            transformHistoryBefore = captureSceneState();
            if (selectedModels.size < 2 || transform.object !== selectionPivot) {
                multiTransformSnapshot = null;
                return;
            }
            multiTransformSnapshot = {
                pivotPosition: selectionPivot.position.clone(),
                pivotQuaternion: selectionPivot.quaternion.clone(),
                pivotScale: selectionPivot.scale.clone(),
                models: new Map([...selectedModels].map(model => [model, {
                    position: model.position.clone(),
                    quaternion: model.quaternion.clone(),
                    scale: model.scale.clone()
                }]))
            };
        }

        function applyMultiTransform() {
            if (!multiTransformSnapshot || transform.object !== selectionPivot) return;
            const snapshot = multiTransformSnapshot;
            const inverseStartRotation = snapshot.pivotQuaternion.clone().invert();
            const rotationDelta = selectionPivot.quaternion.clone().multiply(inverseStartRotation);
            const scaleFactor = new THREE.Vector3(
                selectionPivot.scale.x / snapshot.pivotScale.x,
                selectionPivot.scale.y / snapshot.pivotScale.y,
                selectionPivot.scale.z / snapshot.pivotScale.z
            );

            snapshot.models.forEach((start, model) => {
                const offset = start.position.clone()
                    .sub(snapshot.pivotPosition)
                    .applyQuaternion(inverseStartRotation)
                    .multiply(scaleFactor)
                    .applyQuaternion(selectionPivot.quaternion);
                model.position.copy(selectionPivot.position).add(offset);
                model.quaternion.copy(rotationDelta).multiply(start.quaternion);
                model.scale.copy(start.scale).multiply(scaleFactor);
            });
        }

        function finishMultiTransform(event) {
            const before = transformHistoryBefore;
            const transformedMultiple = Boolean(multiTransformSnapshot);
            transformHistoryBefore = null;
            multiTransformSnapshot = null;
            if (transformedMultiple) configureTransformTarget();
            updateInputs();
            updateSelectionHelpers();
            saveLayout();
            if (before) {
                const action = { translate: 'Moved', rotate: 'Rotated', scale: 'Scaled' }[event.mode] ?? 'Changed';
                const target = selectedModels.size === 1
                    ? primarySelection.userData.editor.name
                    : `${selectedModels.size} objects`;
                recordHistory(`${action} ${target}`, before);
            }
        }

        function setSelection(items, primary = null) {
            const unique = [...new Set(items)].filter(model => models.includes(model));
            selectedModels.clear();
            unique.forEach(model => selectedModels.add(model));
            primarySelection = primary && selectedModels.has(primary) ? primary : unique.at(-1) ?? null;
            configureTransformTarget();
            updateSelectionHelpers();
            renderSceneList();
            updateInputs();
            updateStatus();
        }

        function toggleSelection(model) {
            const next = new Set(selectedModels);
            if (next.has(model)) next.delete(model); else next.add(model);
            setSelection([...next], next.has(model) ? model : [...next].at(-1));
        }

        function updateInputs() {
            const selected = [...selectedModels];
            const title = document.getElementById('selected-name');
            title.textContent = selected.length === 0 ? 'No selection' :
                selected.length === 1 ? selected[0].userData.editor.name : `${selected.length} objects selected`;
            propertyNote.textContent = selected.length > 1
                ? 'The gizmo and property changes apply to every selected object.'
                : selected.length === 1
                    ? 'Changes are saved in this browser. Download positions to keep a JSON copy.'
                    : 'Select an object to edit it. Changes are saved in this browser.';
            inputs.forEach(input => {
                const { property, axis } = input.dataset;
                input.disabled = selected.length === 0;
                input.placeholder = '';
                if (!selected.length) {
                    input.value = '';
                    return;
                }
                const values = selected.map(model => property === 'rotation'
                    ? THREE.MathUtils.radToDeg(model.rotation[axis])
                    : model[property][axis]);
                if (values.every(value => Math.abs(value - values[0]) < 0.0001)) {
                    input.value = Number(values[0].toFixed(2));
                } else {
                    input.value = '';
                    input.placeholder = 'Mixed';
                }
            });
        }

        function setMode(mode) {
            transform.setMode(mode);
            document.querySelectorAll('[data-mode]').forEach(button => {
                button.classList.toggle('active', button.dataset.mode === mode);
            });
        }

        document.querySelectorAll('[data-panel-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                const selectedTab = tab.dataset.panelTab;
                document.querySelectorAll('[data-panel-tab]').forEach(button => {
                    const active = button.dataset.panelTab === selectedTab;
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-selected', String(active));
                });
                document.querySelectorAll('[data-panel-content]').forEach(panel => {
                    panel.hidden = panel.dataset.panelContent !== selectedTab;
                });
            });
        });
        undoButton.addEventListener('click', () => goToHistory(historyIndex - 1));
        redoButton.addEventListener('click', () => goToHistory(historyIndex + 1));
        historyList.addEventListener('click', event => {
            const entry = event.target.closest('[data-history-target]');
            if (entry) goToHistory(Number(entry.dataset.historyTarget));
        });
        sceneObjectList.addEventListener('click', event => {
            const button = event.target.closest('[data-object-id]');
            if (!button) return;
            const model = models.find(item => item.userData.editor.id === button.dataset.objectId);
            if (!model) return;
            if (event.shiftKey || event.ctrlKey || event.metaKey) toggleSelection(model);
            else setSelection([model], model);
        });
        document.querySelectorAll('[data-mode]').forEach(button => {
            button.addEventListener('click', () => setMode(button.dataset.mode));
        });
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                const value = Number(input.value);
                if (!selectedModels.size || input.value.trim() === '' || !Number.isFinite(value) ||
                    (input.dataset.property === 'scale' && value <= 0)) {
                    updateInputs();
                    return;
                }
                const before = captureSceneState();
                selectedModels.forEach(model => {
                    model[input.dataset.property][input.dataset.axis] = input.dataset.property === 'rotation'
                        ? THREE.MathUtils.degToRad(value) : value;
                });
                configureTransformTarget();
                updateInputs();
                updateSelectionHelpers();
                saveLayout();
                const property = input.dataset.property[0].toUpperCase() + input.dataset.property.slice(1);
                recordHistory(`${property} ${input.dataset.axis.toUpperCase()} changed`, before);
            });
        });

        document.getElementById('reset-layout').addEventListener('click', async () => {
            if (historyBusy) return;
            const before = captureSceneState();
            updateStatus('Resetting scene…');
            nextObjectId = 4;
            try {
                await replaceScene(defaults.filter(validRecord));
                recordHistory('Reset scene', before);
            } catch (error) {
                console.error('Could not reset the playground scene:', error);
                updateStatus('Could not reset the scene. Check the model assets and reload.');
            }
        });
        document.getElementById('download-layout').addEventListener('click', () => {
            const file = new Blob([JSON.stringify(getLayout(), null, 2)], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(file);
            link.download = 'playground-scene.json';
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        });

        window.addEventListener('keydown', event => {
            const editingText = event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement;
            const modifier = event.ctrlKey || event.metaKey;
            if (!editingText && !objectLibraryDialog.open && modifier && !event.altKey && event.key.toLowerCase() === 'z') {
                event.preventDefault();
                goToHistory(event.shiftKey ? historyIndex + 1 : historyIndex - 1);
                return;
            }
            if (!editingText && !objectLibraryDialog.open && modifier && !event.altKey && event.key.toLowerCase() === 'y') {
                event.preventDefault();
                goToHistory(historyIndex + 1);
                return;
            }
            if (editingText || modifier || event.altKey || objectLibraryDialog.open) return;
            if ((event.key === 'Delete' || event.key === 'Backspace') && selectedModels.size && !transform.dragging) {
                event.preventDefault();
                deleteSelection();
                return;
            }
            const mode = { g: 'translate', r: 'rotate', s: 'scale' }[event.key.toLowerCase()];
            if (mode) setMode(mode);
        });

        const raycaster = new THREE.Raycaster();
        const pointer = new THREE.Vector2();
        function modelAtPoint(clientX, clientY) {
            const bounds = renderer.domElement.getBoundingClientRect();
            pointer.set(((clientX - bounds.left) / bounds.width) * 2 - 1,
                -((clientY - bounds.top) / bounds.height) * 2 + 1);
            raycaster.setFromCamera(pointer, camera);
            const hit = raycaster.intersectObjects(models, true)[0];
            if (!hit) return null;
            let object = hit.object;
            while (object && !models.includes(object)) object = object.parent;
            return object ?? null;
        }

        function marqueePoint(event) {
            const bounds = renderer.domElement.getBoundingClientRect();
            return {
                x: Math.max(0, Math.min(bounds.width, event.clientX - bounds.left)),
                y: Math.max(0, Math.min(bounds.height, event.clientY - bounds.top))
            };
        }

        function drawMarquee(start, end) {
            const left = Math.min(start.x, end.x);
            const top = Math.min(start.y, end.y);
            selectionMarquee.style.left = `${left}px`;
            selectionMarquee.style.top = `${top}px`;
            selectionMarquee.style.width = `${Math.abs(end.x - start.x)}px`;
            selectionMarquee.style.height = `${Math.abs(end.y - start.y)}px`;
        }

        function modelsInsideMarquee(start, end) {
            const bounds = renderer.domElement.getBoundingClientRect();
            const left = Math.min(start.x, end.x);
            const right = Math.max(start.x, end.x);
            const top = Math.min(start.y, end.y);
            const bottom = Math.max(start.y, end.y);
            const center = new THREE.Vector3();
            const box = new THREE.Box3();
            return models.filter(model => {
                box.setFromObject(model).getCenter(center);
                center.project(camera);
                if (center.z < -1 || center.z > 1) return false;
                const x = (center.x + 1) * bounds.width / 2;
                const y = (1 - center.y) * bounds.height / 2;
                return x >= left && x <= right && y >= top && y <= bottom;
            });
        }

        renderer.domElement.addEventListener('pointerdown', event => {
            if (event.button !== 0 || transform.axis || transform.dragging) return;
            const model = modelAtPoint(event.clientX, event.clientY);
            if (model) {
                if (event.shiftKey || event.ctrlKey || event.metaKey) toggleSelection(model);
                else if (!selectedModels.has(model) || selectedModels.size !== 1) setSelection([model], model);
                return;
            }
            const start = marqueePoint(event);
            marqueeDrag = {
                pointerId: event.pointerId,
                start,
                end: start,
                moved: false,
                additive: event.shiftKey || event.ctrlKey || event.metaKey
            };
            orbit.enabled = false;
            renderer.domElement.setPointerCapture(event.pointerId);
        });

        renderer.domElement.addEventListener('pointermove', event => {
            if (!marqueeDrag || event.pointerId !== marqueeDrag.pointerId) return;
            marqueeDrag.end = marqueePoint(event);
            marqueeDrag.moved = Math.hypot(
                marqueeDrag.end.x - marqueeDrag.start.x,
                marqueeDrag.end.y - marqueeDrag.start.y
            ) > 4;
            if (marqueeDrag.moved) {
                selectionMarquee.style.display = 'block';
                drawMarquee(marqueeDrag.start, marqueeDrag.end);
            }
        });

        function finishMarquee(event) {
            if (!marqueeDrag || event.pointerId !== marqueeDrag.pointerId) return;
            const drag = marqueeDrag;
            marqueeDrag = null;
            selectionMarquee.style.display = 'none';
            orbit.enabled = !transform.dragging;
            if (renderer.domElement.hasPointerCapture(event.pointerId)) {
                renderer.domElement.releasePointerCapture(event.pointerId);
            }
            if (!drag.moved) {
                setSelection([]);
                return;
            }
            const hits = modelsInsideMarquee(drag.start, drag.end);
            setSelection(drag.additive ? [...selectedModels, ...hits] : hits, hits.at(-1));
        }
        renderer.domElement.addEventListener('pointerup', finishMarquee);
        renderer.domElement.addEventListener('pointercancel', finishMarquee);
        renderer.domElement.addEventListener('contextmenu', event => event.preventDefault());

        function deleteSelection() {
            if (historyBusy) return;
            const before = captureSceneState();
            const count = selectedModels.size;
            const removing = new Set(selectedModels);
            transform.detach();
            removing.forEach(model => {
                const helper = selectionHelpers.get(model);
                if (helper) scene.remove(helper);
                selectionHelpers.delete(model);
                scene.remove(model);
            });
            for (let index = models.length - 1; index >= 0; index--) {
                if (removing.has(models[index])) models.splice(index, 1);
            }
            setSelection([]);
            saveLayout();
            recordHistory(`Deleted ${count} ${count === 1 ? 'object' : 'objects'}`, before);
        }

        async function addObject(assetKey) {
            const asset = assetsByKey.get(assetKey);
            if (!asset || historyBusy) return;
            const before = captureSceneState();
            const serial = nextObjectId++;
            const column = models.length % 5;
            const row = Math.floor(models.length / 5);
            updateStatus(`Adding ${asset.label}…`);
            try {
                const model = await createModel({
                    id: `object-${serial}`,
                    name: `${asset.label} ${serial}`,
                    asset: assetKey,
                    position: [(column - 2) * 1.6, 0, row * -1.6],
                    rotation: [0, 0, 0],
                    scale: [1, 1, 1]
                });
                setSelection([model], model);
                saveLayout();
                recordHistory(`Added ${model.userData.editor.name}`, before);
            } catch (error) {
                nextObjectId = before.nextObjectId;
                console.error('Could not add the playground model:', error);
                updateStatus(`Could not add ${asset.label}.`);
            }
        }

        document.getElementById('add-object').addEventListener('click', () => objectLibraryDialog.showModal());
        document.getElementById('close-object-library').addEventListener('click', () => objectLibraryDialog.close());
        objectLibraryDialog.addEventListener('click', event => {
            if (event.target === objectLibraryDialog) objectLibraryDialog.close();
        });
        document.querySelectorAll('[data-add-asset]').forEach(button => {
            button.addEventListener('click', () => {
                objectLibraryDialog.close();
                addObject(button.dataset.addAsset);
            });
        });

        new ResizeObserver(() => {
            const width = viewport.clientWidth;
            const height = viewport.clientHeight;
            if (!width || !height) return;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        }).observe(viewport);

        function render() {
            requestAnimationFrame(render);
            orbit.update();
            selectionHelpers.forEach(helper => helper.update());
            renderer.render(scene, camera);
        }
        render();

        async function initialize() {
            if (!modelLibrary.length) {
                renderSceneList();
                historyBaseState = captureSceneState();
                renderHistory();
                updateStatus('No GLTF or GLB models found.');
                return;
            }
            try {
                await replaceScene(readLayout());
                historyBaseState = captureSceneState();
                historyIndex = 0;
                renderHistory();
                } catch (error) {
                console.error('Could not load the playground scene:', error);
                updateStatus('Could not load the scene. Check the model assets and reload.');
            }
        }
        initialize();
    </script>
@endsection
