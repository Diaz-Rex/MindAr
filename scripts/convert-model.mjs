import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { DOMParser } from '@xmldom/xmldom';
import * as THREE from 'three';
import { ColladaLoader } from 'three/examples/jsm/loaders/ColladaLoader.js';
import { GLTFExporter } from 'three/examples/jsm/exporters/GLTFExporter.js';
import { buildScene, toGLB } from 'openskp';
import { DXFLoader } from 'three-dxf-loader';

const require = createRequire(import.meta.url);
const { convertDwgToDxf } = require('dwg2dxf-converter');

const reportFailure = error => {
    const message = error instanceof Error ? error.message : String(error);
    process.stderr.write(`MODEL_CONVERSION_ERROR: ${message}\n`);
    process.exit(1);
};

process.on('uncaughtException', reportFailure);
process.on('unhandledRejection', reportFailure);

class NodeFileReader {
    readAsArrayBuffer(blob) {
        blob.arrayBuffer()
            .then(result => this.finish(result))
            .catch(error => this.fail(error));
    }

    readAsDataURL(blob) {
        blob.arrayBuffer()
            .then(result => {
                const mimeType = blob.type || 'application/octet-stream';
                const base64 = Buffer.from(result).toString('base64');
                this.finish(`data:${mimeType};base64,${base64}`);
            })
            .catch(error => this.fail(error));
    }

    finish(result) {
        this.result = result;
        this.onload?.({ target: this });
        this.onloadend?.({ target: this });
    }

    fail(error) {
        this.error = error;
        this.onerror?.(error);
        this.onloadend?.({ target: this });
    }
}

globalThis.FileReader ??= NodeFileReader;
globalThis.DOMParser ??= DOMParser;

async function exportThreeObjectToGlb(model, outputPath) {
    model.updateMatrixWorld(true);

    const bounds = new THREE.Box3().setFromObject(model);

    if (bounds.isEmpty()) {
        throw new Error('The source file does not contain visible geometry.');
    }

    const exporter = new GLTFExporter();
    const glb = await exporter.parseAsync(model, {
        binary: true,
        onlyVisible: true,
        trs: false,
    });

    await fs.writeFile(outputPath, new Uint8Array(glb));
}

const [, , inputArgument, outputArgument] = process.argv;

if (!inputArgument || !outputArgument) {
    throw new Error('Usage: node scripts/convert-model.mjs input.skp|input.dwg|input.dae output.glb');
}

const inputPath = path.resolve(inputArgument);
const outputPath = path.resolve(outputArgument);
const extension = path.extname(inputPath).toLowerCase();

await fs.mkdir(path.dirname(outputPath), { recursive: true });

if (extension === '.skp') {
    const input = await fs.readFile(inputPath);
    const buffer = input.buffer.slice(input.byteOffset, input.byteOffset + input.byteLength);
    const scene = buildScene(buffer, { respectEdgeVisibility: true });

    if (!scene.glbPrimitives.length) {
        throw new Error('The SketchUp file does not contain renderable face geometry.');
    }

    await fs.writeFile(outputPath, toGLB(scene, { textures: true }));
} else if (extension === '.dwg') {
    const dxfPath = outputPath.replace(/\.glb$/i, '.conversion.dxf');

    try {
        const result = await convertDwgToDxf(inputPath, dxfPath, { timeout: 120000 });

        if (!result.success) {
            throw new Error(result.error || 'LibreDWG could not convert this drawing.');
        }

        const dxf = await fs.readFile(dxfPath, 'utf8');
        const loader = new DXFLoader();
        loader.setEnableLayer(true);
        loader.setConsumeUnits(true);
        loader.setDefaultColor(0x334155);
        const parsed = loader.parse(dxf);
        const model = parsed?.entity;

        if (!model || !model.children.length) {
            throw new Error('The DWG does not contain CAD geometry supported by the converter.');
        }

        // AutoCAD is Z-up. Rotate once so the resulting glTF uses the Y-up convention.
        model.rotation.x = -Math.PI / 2;
        await exportThreeObjectToGlb(model, outputPath);
    } finally {
        await fs.rm(dxfPath, { force: true });
    }
} else if (extension === '.dae') {
    let colladaSource = await fs.readFile(inputPath, 'utf8');

    // Node has no browser image element. Keep DAE geometry and material colors,
    // while replacing external texture slots with a neutral material color.
    colladaSource = colladaSource.replace(
        /<(diffuse|ambient|emission|specular)([^>]*)>\s*<texture\b[^>]*(?:\/>|>[\s\S]*?<\/texture>)\s*<\/\1>/gi,
        '<$1$2><color>0.8 0.8 0.8 1</color></$1>',
    );

    const collada = new ColladaLoader().parse(colladaSource, `${path.dirname(inputPath)}/`);
    const model = collada?.scene;

    if (!model || !model.children.length) {
        throw new Error('The DAE file does not contain renderable Collada geometry.');
    }

    await exportThreeObjectToGlb(model, outputPath);
} else {
    throw new Error(`Unsupported source format: ${extension || 'unknown'}`);
}

const output = await fs.stat(outputPath);

if (output.size < 20) {
    throw new Error('The converted GLB is empty.');
}

process.stdout.write(JSON.stringify({ outputPath, fileSize: output.size }));
