import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',  // Main CSS file
                'resources/css/rtl.css',  // RTL CSS file
                'resources/js/app.js'     // Main JS file
            ],
            refresh: true,
        }),
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
            '@': path.resolve(__dirname, 'resources/js'),
            '~': path.resolve(__dirname, 'resources/css'),  // New alias for CSS
        },
    },
    build: {
        assetsDir: 'assets',  // Organized output directory
        manifest: true,
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    // Organize output files by type
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                }
            }
        }
    }
});