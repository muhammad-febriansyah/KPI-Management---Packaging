import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = new URL(env.APP_URL ?? 'http://localhost:8000');
    const vitePort = Number(env.VITE_PORT ?? 5173);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port: vitePort,
            strictPort: true,
            cors: {
                origin: appUrl.origin,
            },
            origin: `${appUrl.protocol}//${appUrl.hostname}:${vitePort}`,
            hmr: {
                host: appUrl.hostname,
                port: vitePort,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
