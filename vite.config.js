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
  '/workshops/osterkuchen/index.html':                { currentPage: 'workshops',  mobileNavId: 'ws-ok' },
  '/workshops/wareniki-kieztreff/index.html':         { currentPage: 'workshops',  mobileNavId: 'ws-wk' },
  '/workshops/gesundes-essen-benn/index.html':        { currentPage: 'workshops',  mobileNavId: 'ws-ge' },
  '/workshops/wareniki-buergertreff/index.html':      { currentPage: 'workshops',  mobileNavId: 'ws-wb' },
  '/workshops/nicaraguanische-kueche/index.html':     { currentPage: 'workshops',  mobileNavId: 'ws-nk' },
  '/workshops/gymnasium-lichtenberg/index.html':      { currentPage: 'workshops',  mobileNavId: 'ws-gy' },
  '/workshops/wareniki-seefelder/index.html':         { currentPage: 'workshops',  mobileNavId: 'ws-wsf' },
  '/workshops/gesundes-fruehstueck/index.html':       { currentPage: 'workshops',  mobileNavId: 'ws-gf' },
  '/workshops/pizza-couscous/index.html':             { currentPage: 'workshops',  mobileNavId: 'ws-pc' },
  '/workshops/borschtsch-limonade/index.html':        { currentPage: 'workshops',  mobileNavId: 'ws-bl' },
  '/workshops/termin-2026-09-12/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t0912' },
  '/workshops/termin-2026-09-27/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t0927' },
  '/workshops/termin-2026-10-02/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t1002' },
  '/workshops/termin-2026-10-10/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t1010' },
  '/workshops/termin-2026-11-07/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t1107' },
  '/workshops/termin-2026-11-19/index.html':          { currentPage: 'workshops',  mobileNavId: 'ws-t1119' },
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
        workshopOsterkuchen: resolve(__dirname, 'workshops/osterkuchen/index.html'),
        workshopWarenikiKieztreff: resolve(__dirname, 'workshops/wareniki-kieztreff/index.html'),
        workshopGesundesEssenBenn: resolve(__dirname, 'workshops/gesundes-essen-benn/index.html'),
        workshopWarenikiBuergertreff: resolve(__dirname, 'workshops/wareniki-buergertreff/index.html'),
        workshopNicaraguanischeKueche: resolve(__dirname, 'workshops/nicaraguanische-kueche/index.html'),
        workshopGymnasiumLichtenberg: resolve(__dirname, 'workshops/gymnasium-lichtenberg/index.html'),
        workshopWarenikiSeefelder: resolve(__dirname, 'workshops/wareniki-seefelder/index.html'),
        workshopGesundesFruehstueck: resolve(__dirname, 'workshops/gesundes-fruehstueck/index.html'),
        workshopPizzaCouscous: resolve(__dirname, 'workshops/pizza-couscous/index.html'),
        workshopBorschtschLimonade: resolve(__dirname, 'workshops/borschtsch-limonade/index.html'),
        workshopTermin20260912: resolve(__dirname, 'workshops/termin-2026-09-12/index.html'),
        workshopTermin20260927: resolve(__dirname, 'workshops/termin-2026-09-27/index.html'),
        workshopTermin20261002: resolve(__dirname, 'workshops/termin-2026-10-02/index.html'),
        workshopTermin20261010: resolve(__dirname, 'workshops/termin-2026-10-10/index.html'),
        workshopTermin20261107: resolve(__dirname, 'workshops/termin-2026-11-07/index.html'),
        workshopTermin20261119: resolve(__dirname, 'workshops/termin-2026-11-19/index.html'),
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
