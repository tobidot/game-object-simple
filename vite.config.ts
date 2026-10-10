import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.ts',
                'resources/js/desktop.ts',
                'resources/css/desktop.scss',
                'resources/js/tobidot-element-viewer.ts',
                'resources/css/tobidot-element-viewer.scss',
            ],
            refresh: true,
        }),
    ],
});
