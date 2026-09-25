<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MindAR Card</title>

    <script src="https://aframe.io/releases/1.6.0/aframe.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-aframe.prod.js"></script>

    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        .a-enter-vr {
            display: none !important;
        }
    </style>
</head>
<body>
    <a-scene
        mindar-image="imageTargetSrc: {{ $mindArConfig['target'] }}; autoStart: true;"
        color-space="sRGB"
        renderer="colorManagement: true; physicallyCorrectLights: true;"
        vr-mode-ui="enabled: false"
        xr-mode-ui="enabled: false"
        device-orientation-permission-ui="enabled: false"
    >

        <a-assets timeout="30000">
            <a-asset-item id="avatar-model" src="{{ $mindArConfig['model'] }}"></a-asset-item>
        </a-assets>

        <a-entity light="type: ambient; color: #ffffff; intensity: 1.2;"></a-entity>
        <a-entity light="type: directional; color: #ffffff; intensity: 1.2;" position="-1 2 1"></a-entity>

        <a-camera position="0 0 0" look-controls="enabled: false"></a-camera>

        <a-entity mindar-image-target="targetIndex: 0">
            <!-- Center the model on the logo and rotate its height out of the target. -->
            <a-entity
                position="0 0 0.03"
                rotation="90 0 0"
                scale="0.4 0.4 0.4"
            >

                <a-gltf-model
                    id="avatar"
                    src="#avatar-model"
                    position="-0.3 0 -0.35"
                ></a-gltf-model>

            </a-entity>
        </a-entity>
    </a-scene>
</body>
</html>
