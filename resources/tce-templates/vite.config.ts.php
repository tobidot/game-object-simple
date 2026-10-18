<?php
/** @var \App\DTO\TceGeneratorConfig $config */
?>
import { defineConfig } from 'vite';
import path from 'path';

/**
 * One source, two outputs — see "Assets & Build: Dual Build Output": the ES module is what the
 * package loader imports, the IIFE is what the zero-script embed route serves. Files in
 * `public/` are copied into `dist/` unchanged.
 */
export default defineConfig({
    base: './',
    build: {
        lib: {
            entry: path.resolve(__dirname, 'src/index.ts'),
            name: '<?= $config->className ?>',
            formats: ['es', 'iife'],
            fileName: (format) => (format === 'iife' ? 'index.global.js' : 'index.js'),
        },
        outDir: 'dist',
        emptyOutDir: true,
    },
});
