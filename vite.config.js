import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                // Hanya dimuat di halaman yang memakai tabel / select2 (@push).
                'resources/sass/tables.scss',
                'resources/js/tables.js',
                'resources/js/peta.js',
                'resources/js/dashboard.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ['import', 'mixed-decls', 'color-functions', 'global-builtin', 'if-function'],
            },
        },
    },
});
