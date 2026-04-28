import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        cors: {
            origin: ['http://vblog.local:8000'],
            credentials: true,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
