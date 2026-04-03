import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        impressum: resolve(__dirname, 'impressum/index.html'),
        datenschutz: resolve(__dirname, 'datenschutz/index.html'),
        workshopSoljankaSharlotka: resolve(__dirname, 'workshops/soljanka-sharlotka/index.html'),
        workshopPilzquicheZimtschnecken: resolve(__dirname, 'workshops/pilzquiche-zimtschnecken/index.html'),
        workshopKhachapuriBrownies: resolve(__dirname, 'workshops/khachapuri-brownies/index.html'),
      },
    },
  },
})
