import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    base: process.env.NODE_ENV === 'development' ? '/' : '/_framework/',
    build: {
        outDir: './public/build',
        manifest: true,
        rollupOptions: {
            input: {
                ui: './resources/js/ui.js',
                ux: './resources/js/ux.js',
            },
            output: {
                entryFileNames: 'js/[name].min.js',
                chunkFileNames: 'js/[name].min.js',
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/\.css$/i.test(assetInfo.name)) {
                        return 'css/[name].min[extname]';
                    }
                    return 'assets/[name].min[extname]';
                },
            },
        },
    },
    server: {
        strictPort: true,
        port: 5173,
        host: '0.0.0.0',
        origin: 'http://localhost:5173',
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname),
        },
    },
});
