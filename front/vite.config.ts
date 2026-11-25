import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// The Symfony API runs on 127.0.0.1:8000: /api calls and uploaded photos (/uploads) are proxied there,
// so the browser only talks to the Vite server (no CORS needed).
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': 'http://127.0.0.1:8000',
      '/uploads': 'http://127.0.0.1:8000',
    },
  },
});
