# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

"Gemeinsam Kochen – Gemeinsam Wachsen" — FEIN-Pilotprojekt Lichtenberg 2026 by Mavka.Berlin.Volunteers. Community cooking workshops for seniors, families, and newcomers in Berlin-Lichtenberg.

## Commands

- `npm run dev` — Vite dev server (port 5173)
- `npm run build` — Production build to `dist/`
- `npm run preview` — Preview production build (port 4173)

No test framework, linter, or formatter configured.

## Architecture

Static multi-page site: Vite 8 + vanilla JS (ES modules) + custom CSS. No framework.

**Pages** (14 entry points in `vite.config.js`):

- `index.html` — Main landing page (single-page with anchor sections)
- `projekt/index.html` — Über das Projekt (standalone page)
- `workshops/index.html` — Workshops & Termine overview (with location filter)
- `workshops/soljanka-sharlotka/index.html` — Workshop detail page
- `workshops/pilzquiche-zimtschnecken/index.html` — Workshop detail page
- `workshops/khachapuri-brownies/index.html` — Workshop detail page
- `workshops/pilzkuchen-honigkuchen/index.html` — Workshop detail page
- `workshops/pfannkuchen-fuellungen/index.html` — Workshop detail page
- `workshops/sekerbura/index.html` — Workshop detail page
- `aktuelles/index.html` — Was bisher passiert ist (with category filter)
- `partner/index.html` — Träger & Partner
- `transparenz/index.html` — Für Förderer & Partner
- `impressum/index.html` — Legal notice
- `datenschutz/index.html` — Privacy policy

**CSS** (loaded in order): `tokens.css` → `base.css` → `layout.css` → `components.css` → `animations.css`. Standalone pages additionally load `pages.css`.

**JS**: `js/main.js` — mobile nav, font-size switcher (localStorage), registration dialog, form validation, toast notifications, IntersectionObserver animations. Uses `textContent`/`createElement` (no innerHTML) for XSS safety. `js/filter.js` — filter chips on workshops overview and aktuelles pages.

## Document Authority Hierarchy

When documents conflict, this order applies:

1. **`docs/Projektbeschreibung.docx`** — Project requirements, tech stack, scope (highest authority)
2. **`docs/brand-identity-mavka.html`** — Colors, typography, logo system (authoritative for visual tokens)
3. **`docs/design-system.html`** — CSS custom properties, spacing, radii, shadows, components
4. **`docs/Stategisches Website Konzept-Gemeinsam-Kochen-Aktualisiert.docx`** — Strategy, UX rules, personas, sitemap (references brand-identity for tokens, does NOT define its own)

The strategic concept deliberately contains **no color/font/spacing values** — it references the brand identity and design system instead.

## Design Tokens (from brand-identity-mavka.html)

Authoritative values — `css/tokens.css` must match these:

**Colors**: Primary `#1B5E20` (Deep Forest), Secondary `#2E6F40`, Accent `#FFBF00` (Amber), Soft Gold `#D4AF37`, Background `#FFFDF5` (Brand White / Cards), Page Background `#FAFAF5` (Off-white), Text `#4A4A4A`, Error `#C43333`, FAB `#1F4D3A`
**Fonts**: Nunito (display/headings), Atkinson Hyperlegible (body — chosen for accessibility)
**Body**: 18px base, line-height 1.65

## Target Audience & UX Constraints

Primary audience: **seniors (72+) and families**. All code changes must respect:

- Minimum touch target: 48×48px (WCAG 2.5.8), primary CTAs: 56px height
- Mobile buttons: full width
- No complex dropdown menus — mobile uses full-screen overlay nav
- No auto-playing carousels or sliders
- No animations that cannot be disabled (respect `prefers-reduced-motion`)
- Phone number always visible (floating action button on mobile: 56×56px, bottom-right)
- Minimum body font: 18px, high contrast
- Max 2 scroll screens on Startseite
- WCAG 2.1 AA as minimum standard

## Navigation (finalized, v1.0)

6 items: Start | Über das Projekt | Workshops & Termine | Was bisher passiert ist | Teilnehmen | Träger & Kontakt

Secondary entry: "Für Förderer & Partner" text link in header.

## Language / i18n

Client-side i18n with DE (source in HTML) and EN (fetched from `/locales/en.json`). Language preference persisted in localStorage.

**Architecture**: `js/i18n.js` module — `initI18n()`, `t(key, fallback)`, `setLang(lang)`. Translations applied via `data-i18n` attributes on HTML elements. German is the HTML source; English is overlaid at runtime. Switching to German reloads the page.

**Files**: `js/i18n.js` (module), `public/locales/en.json` (English translations), `css/base.css` (flash-prevention rules). Every HTML page has an inline `<script>` in `<head>` for early lang detection and `data-i18n-page` on `<html>`.

**Attributes**: `data-i18n` (textContent), `data-i18n-placeholder`, `data-i18n-aria-label`, `data-i18n-title`, `data-i18n-alt`.

URL structure `/de/`, `/en/` planned but not yet implemented (client-side switching only).

## Signature Design Element

Hand-drawn food illustrations (bread, vegetables, herbs) derived from the Mavka logo's organic character. Used as: section dividers, card decorations, empty states.

## Tech Stack Context

Hosting: Hostinger Cloud Enterprise. Newsletter: Hostinger Reach. If complexity grows, WordPress + Astra Pro + Spectra Pro is the fallback CMS option.
