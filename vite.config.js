import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.js', 'resources/js/auth.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@css': path.resolve(__dirname, 'resources/css'),
            '@js': path.resolve(__dirname, 'resources/js'),
            '@npm': path.resolve(__dirname, 'node_modules'),
            '@public': path.resolve(__dirname, 'public'),
            '@vendor': path.resolve(__dirname, 'vendor'),
        },
    },
    server: {
        cors: true,
        hmr: {
            host: process.env.DDEV_HOSTNAME, // process.env.APP_HOST, process.env.DDEV_HOSTNAME
            protocol: 'wss',
        },
        host: true,
        port: 5173,
        strictPort: true,
    },
});
