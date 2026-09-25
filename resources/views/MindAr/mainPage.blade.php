<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $playground?->name ?? 'MindAR Card' }}</title>

    <script src="https://aframe.io/releases/1.6.0/aframe.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-aframe.prod.js"></script>
    @if (! $playground)
        <script src="/vendor/jsqr/jsQR-1.4.0.js"></script>
    @endif
    <script>
        AFRAME.registerComponent('normalize-playground-model', {
            init: function () {
                this.el.addEventListener('model-loaded', event => {
                    const model = event.detail.model;
                    const localModel = model.clone(true);

                    localModel.position.set(0, 0, 0);
                    localModel.quaternion.identity();
                    localModel.scale.set(1, 1, 1);
                    localModel.updateMatrixWorld(true);

                    const box = new THREE.Box3().setFromObject(localModel);
                    const size = box.getSize(new THREE.Vector3());
                    const center = box.getCenter(new THREE.Vector3());
                    const unit = 1.8 / Math.max(size.x, size.y, size.z, 0.001);

                    model.scale.setScalar(unit);
                    model.position.set(-center.x * unit, -box.min.y * unit, -center.z * unit);
                    model.traverse(child => {
                        if (! child.isMesh) return;

                        child.frustumCulled = false;

                        const materials = Array.isArray(child.material)
                            ? child.material
                            : [child.material];

                        materials.filter(Boolean).forEach(material => {
                            material.side = THREE.DoubleSide;
                            material.needsUpdate = true;
                        });
                    });
                });
            }
        });
    </script>

    <style>
        html,
        body {
            width:100%;
            height:100%;
            margin:0;
            overflow:hidden;
            background:transparent;
            font-family:Arial,sans-serif
        }
        a-scene {
            position:fixed;
            inset:0;
            width:100%;
            height:100%
        }
        .a-enter-vr {
            display:none!important
        }
        .viewer-header {
            position:fixed;
            z-index:10;
            top:14px;
            left:50%;
            width:max-content;
            max-width:calc(100% - 28px);
            padding:10px 14px;
            border:1px solid rgba(255,255,255,.22);
            border-radius:12px;
            background:rgba(15,23,42,.76);
            box-shadow:0 8px 28px rgba(0,0,0,.25);
            color:#fff;
            text-align:center;
            transform:translateX(-50%);
            backdrop-filter:blur(8px)
        }
        .viewer-title {
            display:block;
            overflow:hidden;
            max-width:72vw;
            font-size:14px;
            font-weight:800;
            text-overflow:ellipsis;
            white-space:nowrap
        }
        .viewer-status {
            display:block;
            margin-top:3px;
            color:#bfdbfe;
            font-size:11px
        }
        .viewer-empty {
            position:fixed;
            z-index:11;
            left:50%;
            bottom:24px;
            width:min(420px,calc(100% - 32px));
            padding:14px 16px;
            border-radius:12px;
            background:rgba(15,23,42,.82);
            color:#fff;
            text-align:center;
            font-size:13px;
            line-height:1.45;
            transform:translateX(-50%);
            backdrop-filter:blur(8px)
        }
    </style>
</head>
<body>
    <div class="viewer-header">
        <span class="viewer-title">{{ $playground?->name ?? 'MindAR Card' }}</span>
        <span id="viewer-status" class="viewer-status">
            {{ $playground ? 'Point the camera at this playground QR card' : 'Point the camera at a playground QR code' }}
        </span>
    </div>

    @if ($playground && ! $mindArConfig['target_ready'])
        <div class="viewer-empty">This QR target is not ready yet. Open this playground in the builder once, then reload this page.</div>
    @endif

    @if ($playground && $sceneObjects->isEmpty())
        <div class="viewer-empty">This playground does not have a saved visible scene yet.</div>
    @endif

    <a-scene
        mindar-image="imageTargetSrc: {{ $mindArConfig['target'] }}; autoStart: {{ $mindArConfig['target_ready'] ? 'true' : 'false' }};"
        color-space="sRGB"
        renderer="colorManagement: true; physicallyCorrectLights: true;"
        vr-mode-ui="enabled: false"
        xr-mode-ui="enabled: false"
        device-orientation-permission-ui="enabled: false"
    >
        <a-assets timeout="30000">
            @foreach ($modelAssets as $asset)
                <a-asset-item id="model-asset-{{ $asset['id'] }}" src="{{ $asset['url'] }}"></a-asset-item>
            @endforeach
        </a-assets>

        <a-entity light="type: ambient; color: #ffffff; intensity: 1.2;"></a-entity>
        <a-entity light="type: directional; color: #ffffff; intensity: 1.2;" position="-1 2 1"></a-entity>
        <a-camera position="0 0 0" look-controls="enabled: false"></a-camera>

        <a-entity id="image-target" mindar-image-target="targetIndex: 0">
            <a-entity
                id="saved-playground-scene"
                position="0 0 0.03"
                rotation="0 0 0"
                scale="{{ $mindArConfig['scene_scale'] }} {{ $mindArConfig['scene_scale'] }} {{ $mindArConfig['scene_scale'] }}"
            >
                @foreach ($sceneObjects as $object)
                    <a-gltf-model
                        id="scene-object-{{ $object['id'] }}"
                        src="#model-asset-{{ $object['asset_id'] }}"
                        position="{{ implode(' ', $object['position']) }}"
                        rotation="{{ implode(' ', $object['rotation']) }}"
                        scale="{{ implode(' ', $object['scale']) }}"
                        normalize-playground-model
                    ></a-gltf-model>
                @endforeach
            </a-entity>
        </a-entity>
    </a-scene>

    <script>
        const sceneElement = document.querySelector('a-scene');
        const imageTarget = document.getElementById('image-target');
        const viewerStatus = document.getElementById('viewer-status');
        const usesPlaygroundQr = @json((bool) $playground);
        const sceneModels = [...document.querySelectorAll('#saved-playground-scene a-gltf-model')];
        let loadedModelCount = 0;
        let targetIsVisible = false;
        let qrScanTimer = null;
        let qrScanBusy = false;
        let qrRedirecting = false;

        function startPlaygroundQrScanner() {
            if (usesPlaygroundQr || qrScanTimer || typeof window.jsQR !== 'function') return;

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d', { willReadFrequently: true });

            qrScanTimer = window.setInterval(() => {
                if (qrScanBusy || qrRedirecting) return;

                const video = document.querySelector('video');

                if (!video || video.readyState < 2 || !video.videoWidth || !video.videoHeight) return;

                qrScanBusy = true;

                try {
                    const scale = Math.min(1, 720 / video.videoWidth);
                    canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
                    canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
                    const result = window.jsQR(pixels.data, pixels.width, pixels.height, {
                        inversionAttempts: 'dontInvert'
                    });

                    if (!result?.data) return;

                    const scannedUrl = new URL(result.data, window.location.origin);
                    const playgroundPath = /^\/mindar\/[0-9A-HJKMNP-TV-Z]{26}$/i;

                    if (scannedUrl.host !== window.location.host || !playgroundPath.test(scannedUrl.pathname)) return;

                    qrRedirecting = true;
                    viewerStatus.textContent = 'Playground QR found · Opening AR scene…';
                    window.location.assign(scannedUrl.pathname);
                } catch (error) {
                    console.error('Could not scan the playground QR:', error);
                } finally {
                    qrScanBusy = false;
                }
            }, 350);
        }

        sceneElement.addEventListener('arReady', () => {
            viewerStatus.textContent = usesPlaygroundQr
                ? 'Camera ready · Point at the playground QR card'
                : 'Camera ready · Scanning for a playground QR code';
            startPlaygroundQrScanner();
        });

        sceneElement.addEventListener('arError', () => {
            viewerStatus.textContent = 'Camera could not start · Check browser permission';
        });

        sceneModels.forEach(model => {
            model.addEventListener('model-loaded', () => {
                loadedModelCount++;

                if (targetIsVisible) {
                    viewerStatus.textContent = `Scene found · ${loadedModelCount}/${sceneModels.length} models loaded`;
                }
            });

            model.addEventListener('model-error', () => {
                viewerStatus.textContent = 'Scene found · A 3D model could not be loaded';
            });
        });

        imageTarget.addEventListener('targetFound', () => {
            targetIsVisible = true;
            viewerStatus.textContent = `Scene found · ${loadedModelCount}/${sceneModels.length} models loaded`;
        });

        imageTarget.addEventListener('targetLost', () => {
            targetIsVisible = false;
            viewerStatus.textContent = usesPlaygroundQr
                ? 'Point the camera at the playground QR card'
                : 'Scanning for a playground QR code';
        });
    </script>
</body>
</html>
