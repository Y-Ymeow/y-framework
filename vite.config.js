import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/build/' : '/',
    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            input: [
                'resources/js/ui.js',
                'resources/js/ux.js',
            ],
        },
    },
    server: {
        strictPort: true,
        port: 5173,
        host: '0.0.0.0',
        origin: 'http://localhost:5173', // 强制指定 origin 以支持 PHP 跨域引用
        cors: true, // 开启跨域
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources'),
        },
    },
}));
