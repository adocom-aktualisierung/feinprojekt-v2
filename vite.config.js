import { defineConfig } from 'vite'
import { resolve } from 'path'
import handlebars from 'vite-plugin-handlebars'

// Per-page context for Handlebars partials (currentPage drives aria-current, mobileNavId differentiates mobile-nav IDs)
const pageContext = {
  '/index.html':                                      { currentPage: 'start',      mobileNavId: 'main' },
  '/projekt/index.html':                              { currentPage: 'projekt',    mobileNavId: 'prj' },
  '/workshops/index.html':                            { currentPage: 'workshops',  mobileNavId: 'ws' },
  '/workshops/soljanka-sharlotka/index.html':         { currentPage: 'workshops',  mobileNavId: 'ws-ss' },
  '/workshops/pilzquiche-zimtschnecken/index.html':   { currentPage: 'workshops',  mobileNavId: 'ws-pz' },
  '/workshops/khachapuri-brownies/index.html':        { currentPage: 'workshops',  mobileNavId: 'ws-kb' },
  '/workshops/pilzkuchen-honigkuchen/index.html':     { currentPage: 'workshops',  mobileNavId: 'ws-ph' },
  '/workshops/pfannkuchen-fuellungen/index.html':     { currentPage: 'workshops',  mobileNavId: 'ws-pf' },
  '/workshops/sekerbura/index.html':                  { currentPage: 'workshops',  mobileNavId: 'ws-se' },
  '/aktuelles/index.html':                            { currentPage: 'aktuelles',  mobileNavId: 'akt' },
  '/partner/index.html':                              { currentPage: 'partner',    mobileNavId: 'prt' },
  '/transparenz/index.html':                          { currentPage: '',           mobileNavId: 'trp' },
  '/impressum/index.html':                            { currentPage: 'impressum',  mobileNavId: 'imp' },
  '/datenschutz/index.html':                          { currentPage: 'datenschutz', mobileNavId: 'dsg' },
  '/teilnehmen/index.html':                           { currentPage: 'teilnehmen', mobileNavId: 'tln' },
}

export default defineConfig({
  plugins: [
    handlebars({
      partialDirectory: resolve(__dirname, 'partials'),
      context(pagePath) {
        return pageContext[pagePath] || {}
      },
      helpers: {
        eq(a, b, options) {
          return a === b ? options.fn(this) : options.inverse(this)
        },
      },
    }),
  ],
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        notFound: resolve(__dirname, '404.html'),
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
