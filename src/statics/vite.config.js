import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    base: '/_framework/',
    build: {
        outDir: './dist',
        manifest: true,
        rollupOptions: {
            input: {
                ui: resolve(__dirname, 'ui.js'),
                ux: resolve(__dirname, 'ux.js'),
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
