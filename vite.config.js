import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        // {
        //     name: 'custom-manifest',
        //     generateBundle() {
        //         this.emitFile({
        //             type: 'asset',
        //             fileName: 'manifest.json',
        //             source: JSON.stringify(
        //                 {
        //                     name: 'Keiforum',
        //                     short_name: 'Keiforum',
        //                     start_url: '/',
        //                     display: 'standalone',
        //                     background_color: '#fff9f6',
        //                     theme_color: '#c93020',
        //                     icons: [
        //                         { src: '/apple-touch-icon.png', sizes: '180x180', type: 'image/png' },
        //                         { src: '/google-touch-icon.png', sizes: '512x512', type: 'image/png' },
        //                     ],
        //                 },
        //                 null,
        //                 2
        //             ),
        //         });
        //     },
        // },
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
