import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: 'src/index.js',
            name: 'DavingmWelcome',
            fileName: 'welcomepage',
            formats: ['es'],
        },
        cssCodeSplit: false,
        rollupOptions: {
            output: {
                // Single JS file + single CSS file — no chunking
                assetFileNames: 'welcomepage.[ext]',
            },
        },
        // Keep the bundle readable; it is not a hot path
        minify: true,
        sourcemap: false,
    },
});
