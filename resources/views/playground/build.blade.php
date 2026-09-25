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
.playground-button:focus-visible,.model-choice:focus-visible {
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
    border-right:1px solid #dce8f8
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
    outline:none
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
            <p>Position three copies of your model in a desktop workspace.</p>
        </div>
        <div class="playground-actions">
            <button id="reset-layout" class="playground-button" type="button">Reset layout</button>
            <button id="download-layout" class="playground-button primary" type="button">Download positions</button>
        </div>
    </div>

    <div class="playground-workspace">
        <aside class="playground-panel left" aria-label="Models">
            <h2 class="panel-title">Scene objects</h2>
            <button class="model-choice active" type="button" data-model="0"><span class="model-dot"></span>Model 1</button>
            <button class="model-choice" type="button" data-model="1"><span class="model-dot"></span>Model 2</button>
            <button class="model-choice" type="button" data-model="2"><span class="model-dot"></span>Model 3</button>
        </aside>

        <div class="playground-viewport">
            <div id="playground-canvas" aria-label="3D model editor"></div>
            <div class="viewport-toolbar" aria-label="Transform tools">
                <button class="playground-button active" type="button" data-mode="translate" title="Move (G)">Move</button>
                <button class="playground-button" type="button" data-mode="rotate" title="Rotate (R)">Rotate</button>
                <button class="playground-button" type="button" data-mode="scale" title="Scale (S)">Scale</button>
            </div>
            <div id="playground-status" class="viewport-status" role="status">Loading model…</div>
            <div class="viewport-hint">Left drag: edit selected model · Right drag: orbit · Wheel: zoom · G / R / S: tools</div>
        </div>

        <aside class="playground-panel right" aria-label="Model properties">
            <h2 class="panel-title">Properties</h2>
            <div id="selected-name" class="selected-name">Model 1</div>
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
            <p class="panel-note">Changes are saved in this browser. Download positions to keep a JSON copy.</p>
        </aside>
    </div>

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

        const modelUrl = @json($modelUrl);
        const storageKey = 'mindar-playground-layout-v1';
        const defaults = [-2.3, 0, 2.3].map(x => ({
            position: [x, 0, 0], rotation: [0, 0, 0], scale: [1, 1, 1]
        }));
        const viewport = document.getElementById('playground-canvas');
        const status = document.getElementById('playground-status');
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
        orbit.update();

        scene.add(new THREE.AmbientLight(0xffffff, 2));
        const sun = new THREE.DirectionalLight(0xffffff, 2.5);
        sun.position.set(4, 8, 5);
        scene.add(sun);
        const grid = new THREE.GridHelper(20, 20, 0x9db9dd, 0xcbdcf0);
        scene.add(grid);

        const transform = new TransformControls(camera, renderer.domElement);
        transform.addEventListener('dragging-changed', event => { orbit.enabled = !event.value; });
        transform.addEventListener('objectChange', () => {
            updateInputs();
            saveLayout();
        });
        scene.add(transform);

        const models = [];
        let selectedIndex = 0;

        function validLayout(value) {
            return Array.isArray(value) && value.length === 3 && value.every(item =>
                ['position', 'rotation', 'scale'].every(key =>
                    Array.isArray(item?.[key]) && item[key].length === 3 &&
                    item[key].every(number => typeof number === 'number' && Number.isFinite(number))
                ) && item.scale.every(number => number > 0)
            );
        }

        function readLayout() {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey));
                return validLayout(saved) ? saved : defaults;
            } catch {
                return defaults;
            }
        }

        function getLayout() {
            return models.map(model => ({
                position: model.position.toArray(),
                rotation: [model.rotation.x, model.rotation.y, model.rotation.z].map(THREE.MathUtils.radToDeg),
                scale: model.scale.toArray()
            }));
        }

        function saveLayout() {
            if (models.length !== 3) return;
            try { localStorage.setItem(storageKey, JSON.stringify(getLayout())); } catch { /* Storage may be disabled. */ }
        }

        function applyLayout(layout) {
            models.forEach((model, index) => {
                const values = layout[index];
                model.position.fromArray(values.position);
                model.rotation.set(...values.rotation.map(THREE.MathUtils.degToRad));
                model.scale.fromArray(values.scale);
            });
            updateInputs();
            saveLayout();
        }

        function updateInputs() {
            const model = models[selectedIndex];
            if (!model) return;
            inputs.forEach(input => {
                const { property, axis } = input.dataset;
                const value = property === 'rotation'
                    ? THREE.MathUtils.radToDeg(model.rotation[axis])
                    : model[property][axis];
                input.value = Number(value.toFixed(2));
            });
        }

        function selectModel(index) {
            selectedIndex = index;
            transform.attach(models[index]);
            document.getElementById('selected-name').textContent = `Model ${index + 1}`;
            document.querySelectorAll('.model-choice').forEach(button => {
                button.classList.toggle('active', Number(button.dataset.model) === index);
            });
            updateInputs();
        }

        function setMode(mode) {
            transform.setMode(mode);
            document.querySelectorAll('[data-mode]').forEach(button => {
                button.classList.toggle('active', button.dataset.mode === mode);
            });
        }

        document.querySelectorAll('.model-choice').forEach(button => {
            button.addEventListener('click', () => {
                if (models.length === 3) selectModel(Number(button.dataset.model));
            });
        });
        document.querySelectorAll('[data-mode]').forEach(button => {
            button.addEventListener('click', () => setMode(button.dataset.mode));
        });
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                const model = models[selectedIndex];
                const value = Number(input.value);
                if (!model || !Number.isFinite(value) || (input.dataset.property === 'scale' && value <= 0)) {
                    updateInputs();
                    return;
                }
                model[input.dataset.property][input.dataset.axis] = input.dataset.property === 'rotation'
                    ? THREE.MathUtils.degToRad(value) : value;
                updateInputs();
                saveLayout();
            });
        });

        document.getElementById('reset-layout').addEventListener('click', () => {
            if (models.length === 3) applyLayout(defaults);
        });
        document.getElementById('download-layout').addEventListener('click', () => {
            if (models.length !== 3) return;
            const file = new Blob([JSON.stringify(getLayout(), null, 2)], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(file);
            link.download = 'model-positions.json';
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        });

        window.addEventListener('keydown', event => {
            if (event.target instanceof HTMLInputElement || event.ctrlKey || event.metaKey || event.altKey) return;
            const mode = { g: 'translate', r: 'rotate', s: 'scale' }[event.key.toLowerCase()];
            if (mode) setMode(mode);
        });

        const raycaster = new THREE.Raycaster();
        const pointer = new THREE.Vector2();
        renderer.domElement.addEventListener('pointerdown', event => {
            if (event.button !== 0 || transform.axis || models.length !== 3) return;
            const bounds = renderer.domElement.getBoundingClientRect();
            pointer.set(((event.clientX - bounds.left) / bounds.width) * 2 - 1,
                -((event.clientY - bounds.top) / bounds.height) * 2 + 1);
            raycaster.setFromCamera(pointer, camera);
            const hit = raycaster.intersectObjects(models, true)[0];
            if (!hit) return;
            let object = hit.object;
            while (object && !models.includes(object)) object = object.parent;
            if (object) selectModel(models.indexOf(object));
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
            renderer.render(scene, camera);
        }
        render();

        new GLTFLoader().load(modelUrl, gltf => {
            const box = new THREE.Box3().setFromObject(gltf.scene);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());
            const unit = 1.8 / Math.max(size.x, size.y, size.z, 0.001);
            for (let index = 0; index < 3; index++) {
                const root = new THREE.Group();
                const contents = gltf.scene.clone(true);
                contents.scale.setScalar(unit);
                contents.position.set(-center.x * unit, -box.min.y * unit, -center.z * unit);
                root.add(contents);
                scene.add(root);
                models.push(root);
            }
            applyLayout(readLayout());
            selectModel(0);
            inputs.forEach(input => { input.disabled = false; });
            status.textContent = '3 models ready';
        }, undefined, error => {
            console.error('Could not load the playground model:', error);
            status.textContent = 'Could not load the model. Check the asset and reload.';
        });
    </script>
@endsection
