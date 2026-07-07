import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        sourcemap: false,
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-editor': ['@ckeditor/ckeditor5-build-classic', '@ckeditor/ckeditor5-vue'],
                    'vendor-charts': ['chart.js', 'vue-chartjs'],
                    'vendor-ui': ['reka-ui', 'lucide-vue-next'],
                },
            },
        },
    },
    server: {
        allowedHosts: ['localhost', '127.0.0.1', '[::1]', '0.0.0.0'],
    },
});
