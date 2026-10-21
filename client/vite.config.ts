import { defineConfig } from 'vite';

// Прокси dev-сервера на живой бэкенд — чтобы браузерный fetch() шёл на
// тот же origin (localhost), а не напрямую на destinysphere.ru (там нет
// Access-Control-Allow-Origin, реальный кросс-доменный fetch из браузера
// будет заблокирован CORS — в отличие от Node/curl-тестов Фазы 1, где
// CORS вообще не действует). См. CLIENT_PORTING_PLAN.md, Фаза 2.
export default defineConfig({
  base: process.env.VITE_BASE_PATH || '/',
  server: {
    proxy: {
      '/ds': {
        target: 'https://destinysphere.ru',
        changeOrigin: true,
        secure: true,
      },
    },
  },
});
