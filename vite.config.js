import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

/**
 * There is no PWA plugin here on purpose.
 *
 * This application is a progressive web app through a hand-written service
 * worker at public/sw.js and a hand-written public/manifest.json, which is
 * what app.blade.php links and registers. vite-plugin-pwa used to sit in this
 * config generating a second service worker and a second manifest that
 * nothing registered or linked: 1.8 MB of precache built on every run and
 * served to nobody. Two service workers competing for the same '/' scope is
 * undefined behaviour, so only one of them can be the real one.
 */
export default defineConfig({
  plugins: [
    tailwindcss(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
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
  server: {
    host: '0.0.0.0',
    port: 5173,
    cors: true,
    ...(process.env.VITE_HMR_HOST
      ? {
          origin: `http://${process.env.VITE_HMR_HOST}:5173`,
          hmr: { host: process.env.VITE_HMR_HOST },
        }
      : {}),
  },
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
  css: {
    postcss: false,
  },
})
