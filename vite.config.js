import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        impressum: resolve(__dirname, 'impressum/index.html'),
        datenschutz: resolve(__dirname, 'datenschutz/index.html'),
      },
    },
  },
})
