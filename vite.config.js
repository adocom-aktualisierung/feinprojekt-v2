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
        workshopPilzkuchenHonigkuchen: resolve(__dirname, 'workshops/pilzkuchen-honigkuchen/index.html'),
        workshopPfannkuchenFuellungen: resolve(__dirname, 'workshops/pfannkuchen-fuellungen/index.html'),
        workshopSekerbura: resolve(__dirname, 'workshops/sekerbura/index.html'),
        projekt: resolve(__dirname, 'projekt/index.html'),
        workshopsOverview: resolve(__dirname, 'workshops/index.html'),
        aktuelles: resolve(__dirname, 'aktuelles/index.html'),
        partner: resolve(__dirname, 'partner/index.html'),
        transparenz: resolve(__dirname, 'transparenz/index.html'),
        teilnehmen: resolve(__dirname, 'teilnehmen/index.html'),
      },
    },
  },
})
