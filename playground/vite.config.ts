import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { laravelPages } from './vendor/davingm/laravel-runtime/src/Compiler/Plugin/vite.mjs';

export default defineConfig({
    plugins: [
        laravelPages(),
        laravel({
            input: [
                'src/assets/css/app.css',
                'node_modules/@davingm/welcome/dist/welcomepage.css',
                '.laravel/generated/pages.css',
                '.laravel/frontend/framework.js',
                'src/assets/ts/app.ts',
                '.laravel/generated/pages.ts',
            ],
            refresh: ['src/**/*.blade.php'],
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
