import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.ts', 'resources/js/desktop.ts', 'resources/css/desktop.scss'],
            refresh: true,
        }),
    ],
});
