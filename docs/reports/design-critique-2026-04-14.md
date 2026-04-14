# Design Critique: „Gemeinsam Kochen – Gemeinsam Wachsen"

**Reviewer:** Apple Design Director (Perspektive)
**Datum:** 14. April 2026
**Scope:** Gesamte Website — 14 Seiten, Design-System, CSS-Architektur, Interaktionen
**Methodik:** Nielsens 10 Heuristiken, WCAG 2.1 AA, visuelle Analyse, strategische Bewertung

---

## Executive Summary

Die Website zeigt eine solide handwerkliche Basis: durchdachtes Token-System, vorbildliche Accessibility-Grundlagen (Skip-Links, Focus-Styles, `prefers-reduced-motion`), semantisches HTML und eine klare Informationsarchitektur. Für ein gemeinnütziges Pilotprojekt mit Senioren-Zielgruppe ist das Fundament bemerkenswert stark.

**Aber:** Die Seite leidet an dem, was ich „wohlmeinendem Template-Syndrom" nenne — sie trifft viele richtige Entscheidungen, ohne dass diese Entscheidungen eine eigenständige visuelle Persönlichkeit ergeben. Die Hero-Section könnte zu jeder NGO gehören. Die Workshop-Cards folgen einem Standard-Pattern. Der emotionale Kern des Projekts — Menschen verschiedener Generationen und Kulturen kochen zusammen — wird vom Design beschrieben, aber nicht fühlbar gemacht.

**Gesamtnote: 3,6 / 5** — Professionell, funktional, aber noch nicht unvergesslich.

---

## Teil 1: Nielsens 10 Heuristiken

### H1 — Sichtbarkeit des Systemstatus | Score: 4/5

**Stark:** Focus-Styles mit `outline: 3px solid var(--color-amber)` — konsistent, gut sichtbar. Toast-Notifications für Formular-Feedback. `aria-live="polite"` auf Filter-Status. Button-State „Wird gesendet…" während API-Calls.

**Schwäche:** Alle 6 Workshop-Cards zeigen „Ausgebucht" — es gibt keinen visuellen Unterschied zwischen „ausgebucht, vorbei" und „ausgebucht, Warteliste möglich". Der User sieht eine Wand aus identischen Sold-out-States und hat keine Orientierung, wo noch Handlungsmöglichkeit besteht. Der Opacity-Drop auf 0.75 für `is-sold-out` ist zu subtil — das Auge findet keinen Anker.

**Beispiel:** Ein 72-jähriger Senior scrollt durch 6 identisch wirkende, halbtransparente Cards und versteht nicht, warum manche einen „Warteliste"-Button haben und andere nicht.

### H2 — Übereinstimmung zwischen System und Realität | Score: 4,5/5

**Stark:** Hervorragende Sprachwahl — „Nachbar*innen", „Begegnung beginnt am Kochtopf", Siez-Form. Die Leaf-Divider mit Hand-drawn SVGs (Brot, Kochtopf, Kräuter) sprechen die Food-Sprache des Projekts. Meta-Informationen (Datum, Uhrzeit, Ort) verwenden vertraute Konventionen.

**Schwäche:** Die `hero-bg-pattern` mit `morphBlob`-Animation spricht eine Tech-Startup-Sprache, keine Community-Küchen-Sprache. Organische CSS-Blobs sind ein 2022–2024-SaaS-Trend und wirken hier deplatziert.

### H3 — Nutzerkontrolle und Freiheit | Score: 4/5

**Stark:** Dialog-Close über ×-Button, Backdrop-Click und Escape-Key. Mobile-Nav schließt bei Link-Klick. Formular-Reset bei Dialog-Close. Font-Size-Switcher mit localStorage-Persistenz.

**Schwäche:** Kein „Zurück zu allen Workshops"-Link auf Detail-Seiten ohne Breadcrumb-Kontext. Die `pages.css` enthält zwar Breadcrumb-Styles, aber der Workshop-Detail-Template nutzt Handlebars-Partials (`{{> header}}`), was darauf hindeutet, dass die HTML-Seite möglicherweise noch nicht vollständig gerendert vorliegt.

### H4 — Konsistenz und Standards | Score: 3,5/5

**Stark:** Token-System ist sauber durchgezogen. Spacing-Skala (xs→2xl) wird konsistent verwendet. Buttons folgen einem einheitlichen Schema.

**Schwächen:**
- **Inkonsistente Sektionierung:** Workshops-Seite nutzt `role="group"` für Filter, Aktuelles-Seite nicht.
- **`!important`-Overrides** in `.workshop-card-cta` (3× `!important`) — ein Zeichen für Spezifizitäts-Konflikte statt sauberer Kaskade.
- **Color-Token-Alias fehlt:** `var(--color-primary-green, #1B5E20)` taucht in `.mobile-utility-switches` als Fallback auf, existiert aber nicht als deklarierter Token. Das ist eine Inkonsistenz im Token-System.
- **Hardcoded Farben:** `#245C1A` im Hero-Gradient und Footer ohne Token.
- **Grid-Breakpoints uneinheitlich:** `640px`, `768px`, `900px`, `960px`, `1024px` — fünf verschiedene Breakpoints ohne erkennbare Systematik.

### H5 — Fehlervermeidung | Score: 4,5/5

**Stark:** Formular-Validierung mit Error-Summary (WCAG-Pattern), inline Validierung auf `blur`, Error-Messages mit `aria-live="polite"`, Consent-Checkbox vor Submit. Robuste XSS-Prevention durch `textContent`/`createElement` statt `innerHTML` (mit einer Ausnahme in `injectMobileUtilitySwitches`).

**Schwäche:** Die `innerHTML`-Nutzung in `injectMobileUtilitySwitches()` widerspricht dem eigenen Sicherheitsstandard aus CLAUDE.md.

### H6 — Wiedererkennung statt Erinnerung | Score: 3,5/5

**Stark:** Navigation mit 6 Items ist gut dimensioniert. Contact-Bar direkt unter dem Hero wiederholt Telefon/E-Mail/Adresse.

**Schwäche:** Die Startseite hat **keine persistente CTA** nach dem Hero. Scrollt man über die Contact-Bar hinaus, verschwindet die primäre Handlungsaufforderung bis man „Teilnehmen & Mitmachen" erreicht — das ist weit unten. Auf einem Senioren-Gerät sind das leicht 4–5 Scroll-Screens.

### H7 — Flexibilität und Effizienz | Score: 3,5/5

**Stark:** Font-Size-Switcher für Senioren. Language-Switcher DE/EN. Keyboard-Navigation mit Focus-Trap in Mobile-Nav und Dialog.

**Schwäche:** Kein Dark-Mode-Support (nicht zwingend für diese Zielgruppe, aber als System-Präferenz wäre `prefers-color-scheme` ein Quick Win). Filter auf der Workshop-Seite nur nach Standort — kein Filter nach Datum oder Verfügbarkeit, obwohl das die relevanteste Dimension ist.

### H8 — Ästhetik und minimalistisches Design | Score: 3/5

**Das zentrale Problem.** Die Seite hat zu viele gleichwertige Elemente pro Viewport:

- **Hero:** Label-Pill + H1 + Description + 2 CTAs — das sind 5 Elemente, die um Aufmerksamkeit konkurrieren.
- **Workshop-Cards:** Jede Card hat Bild + Date-Badge + 2 Tags + Title + Description + 2 Meta-Items + CTA = 9 Informationseinheiten. Bei 6 Cards auf der Startseite sind das 54 Datenpunkte in einer Section.
- **Value-Cards:** 4 identische Karten mit Icon + Title + Text — der Section-Green-Hintergrund hebt nichts hervor, weil alles hervorgehoben ist.

**Faustregel verletzt:** Wenn auf einem 375px-Screen eine Workshop-Card 1,5 Screens einnimmt, ist die Informationsdichte zu hoch.

### H9 — Hilfe bei Fehlern | Score: 4,5/5

**Stark:** Error-Summary mit Links zu fehlerhaften Feldern. Individuelle Error-Messages per Feld. Toast für Netzwerkfehler mit Telefonnummer als Fallback. Klare Sprache: „Bitte geben Sie eine gültige E-Mail-Adresse ein."

**Schwäche:** Kein expliziter 404-Handler erkennbar.

### H10 — Hilfe und Dokumentation | Score: 3/5

**Schwäche:** Keine FAQ-Seite. Keine Erklärung, was bei einem Workshop passiert (Ablauf, was mitbringen, für wen geeignet). Die Workshop-Detail-Seiten haben Content-Sections, aber die Card-Descriptions auf der Startseite sind zu knapp für jemanden, der zum ersten Mal von dem Projekt hört.

---

## Teil 2: Visuelle Hierarchie, Typografie, Farbe

### Typografie — Score: 4/5

**Stark:** Die Kombination Nunito (Display) + Atkinson Hyperlegible (Body) ist eine kluge, bewusste Wahl. Atkinson Hyperlegible wurde für Sehbehinderungen entwickelt — das zeigt echte Zielgruppen-Empathie. Variable Font für Nunito reduziert HTTP-Requests. Self-Hosting ist DSGVO-konform.

Die `clamp()`-basierte Fluid-Typography ist sauber implementiert:
```css
--font-size-body: clamp(1.125rem, 1rem + 0.5vw, 1.25rem);  /* 18px → 20px */
--font-size-h1:   clamp(2rem, 1.6rem + 2vw, 3.25rem);       /* 32px → 52px */
```

**Schwächen:**
- **Zu viele Größenstufen:** body, small, h1, h2, h3, nav, utility = 7 Stufen. Dazu 6 weitere in `html[data-font-size="large"]`. Das ist typografisch üppig — 4–5 Stufen mit klarem Verhältnis wären schärfer.
- **Line-Height-Inkonsistenz:** `--line-height-body: 1.65` global, aber `.hero-description` hat `1.7`, `.footer-brand p` hat `1.6`, `.value-card p` hat `1.55`. Diese Variationen sind so gering, dass sie kein gestalterisches Ziel haben, aber die Konsistenz brechen.

### Farbe — Score: 3,5/5

**Stark:** Die Green-Palette (#1B5E20 → #2E6F40 → #577842 → #8EB69B) hat gute Abstufung. AA-Kontrast wurde bewusst justiert (`#577842` auf `#FFFDF5` = 4.6:1). Die Amber-Akzentfarbe (#FFBF00) ist ein starker Differenziator.

**Schwächen:**
- **Green-Dominanz ohne Differenzierung:** 4 Grüntöne + Mint + FAB-Green + Hero-Gradient-Green = 7 Grün-Varianten. Das ist zu viel — die Abstufungen verschwimmen optisch.
- **Amber wird verschenkt:** `#FFBF00` taucht nur im Hero-H1 (`em`), Focus-Ring, Testimonial-Border und Footer-Hover auf. Die kraftvollste Farbe im System wird als Akzent versteckt statt strategisch als primäre Handlungsfarbe eingesetzt.
- **Cream vs. Off-White vs. White:** `#FDFBD4`, `#FAFAF5`, `#FFFDF5` — drei nahezu identische Hintergrundtöne, deren Unterschied auf den meisten Consumer-Displays nicht wahrnehmbar ist (ΔE < 5).

### Visuelle Hierarchie — Score: 3/5

**Hauptproblem: Alles schwebt auf der gleichen Ebene.**

Die Shadow-Tokens (`--shadow-soft`, `--shadow-card`, `--shadow-elevated`) sind definiert, aber die Elevations-Hierarchie wird nicht konsequent genutzt. Workshop-Cards haben `--shadow-card` im Ruhezustand — das ist die mittlere Elevation. Wohin sollen sie bei Hover elevieren, wenn sie bereits erhöht starten?

Der Hero hat keinen klaren Z-Layer-Übergang zum Content darunter. Die Contact-Bar sitzt zwischen Hero und Workshop-Section ohne visuelle Trennung — sie wirkt wie ein Fremdkörper.

Die Leaf-Divider (Brot, Kochtopf, Kräuter) sind eine charmante Idee, aber bei nur 28×28px und `opacity: 0.7` zu klein und zu blass, um als Sektions-Trenner zu funktionieren. Sie fallen unter das Wahrnehmungs-Minimum.

---

## Teil 3: Kognitive Belastung, Accessibility, Interaktion

### Kognitive Belastung — Score: 3,5/5

**Startseite-Scroll-Tiefe:** Hero → Contact-Bar → Leaf-Divider → Workshops (6 Cards) → Leaf-Divider → Über das Projekt → Values (4 Cards) → Mitmachen (3 Cards) → Leaf-Divider → Aktuelles → Partners → Newsletter → Kontaktformular → Footer. Das sind **13 Sektionen** auf einer Seite, die laut Spec „max 2 Scroll-Screens" haben soll.

**Die Spec-Verletzung ist gravierend.** `CLAUDE.md` definiert: „Max 2 scroll screens on Startseite". Die aktuelle Seite hat auf Mobile mindestens 12–15 Scroll-Screens. Entweder die Spec ist veraltet oder die Implementation ist außer Kontrolle geraten.

**Millers Gesetz:** 6 Workshop-Cards, alle im gleichen visuellen Gewicht, alle ausgebucht — das überlastet das Arbeitsgedächtnis. 3 würden genügen, mit einem „Alle anzeigen"-Link.

### WCAG 2.1 AA — Score: 4/5

**Stark (und bemerkenswert für ein Pilotprojekt):**
- Skip-Link vorhanden und funktional
- `aria-expanded`, `aria-controls`, `aria-pressed` korrekt eingesetzt
- Focus-Trap in Mobile-Nav und Dialog
- Error-Summary mit WCAG-Pattern
- `prefers-reduced-motion` respektiert Animationen global
- Touch-Targets ≥ 48px (Mobile-Nav-Links `min-height: 52px`, Buttons `min-height: 56px`)
- `.sr-only` Utility korrekt implementiert
- Schema.org Microdata in Breadcrumbs

**Lücken:**
- **Filter-Hidden-Items bleiben im Tab-Order:** `[data-filter-target].is-hidden { display: none; }` entfernt zwar visuell, aber `filter.js` setzt kein `aria-hidden` oder `inert`. Bei `display: none` ist das technisch OK (Element ist nicht fokussierbar), aber die fehlende explizite ARIA-Kommunikation ist ein Muster-Bruch.
- **Inkonsistente `role`-Attribute:** Workshop-Filter hat `role="group"`, Aktuelles-Filter nicht.
- **Newsletter-Input fehlt sichtbares Label:** Das `<label>` existiert als `.newsletter-label`, aber die visuelle Zuordnung zum Input ist bei der Flex-Row-Anordnung (ab 480px) unklar.
- **Color-Contrast Edge-Case:** `.hero-description` mit `rgba(255, 255, 255, 0.88)` auf dem dunkelgrünen Gradient — bei bestimmten Gradient-Positionen könnte das unter 4.5:1 fallen.

### Interaktionsklarheit — Score: 3,5/5

**Problem 1: Ist die Card klickbar oder der Button?**
Workshop-Cards haben `cursor: pointer` auf der gesamten `.workshop-card`, aber der eigentliche Link ist `.workshop-card-link` (ein `<a>`-Tag). Die visuellen Affordances stimmen, aber der CTA-Button innerhalb der Card hat `aria-hidden="true"` — er sieht klickbar aus, ist aber dekorativ. Das verwirrt: Warum zeige ich einen Button, der kein Button ist?

**Problem 2: Sold-out-State ist unklar.**
Die Opacity-Reduktion auf 0.75 sagt visuell: „etwas anders". Aber was? Grau-Ausfaden, Durchstreichen oder ein eindeutiges Badge wären klarer. Der Text „Ausgebucht" in einem roten Tag ist korrekt, aber die Gesamtcard-Behandlung signalisiert „disabled", nicht „vergangen + Warteliste möglich".

---

## Teil 4: Strategische Passung und Differenzierung

### Zielgruppen-Fit — Score: 3,5/5

**Das Design bedient Senioren auf der technischen Ebene** (große Buttons, Telefon-FAB, Font-Size-Switcher, hohe Kontraste) **aber nicht auf der emotionalen Ebene.** Die visuelle Sprache — Hero-Gradients, glassmorphism-inspirierte Backdrop-Filters, Blob-Animationen — ist die Sprache einer Tech-Firma, nicht einer Nachbarschaftsküche.

Ein 72-jähriger Senior erwartet: Fotos von echten Menschen (vorhanden auf Cards, aber nicht im Hero), warme Handschrift-Elemente, weniger „Interface" und mehr „Einladung".

### Differenzierung — Score: 2,5/5

**Die Website sieht aus wie 80% aller NGO-Websites 2024–2026.** Green-Palette + Card-Grid + Hero-Gradient + Pill-Buttons = das Standard-Toolkit. Die Leaf-Divider-SVGs und die Atkinson-Hyperlegible-Wahl sind die einzigen echten Differenzierungsmerkmale, aber sie werden nicht stark genug ausgespielt.

**Was fehlt:** Die Hand-drawn-Illustrationen, die in CLAUDE.md als „Signature Design Element" definiert sind — „bread, vegetables, herbs derived from the Mavka logo's organic character" — sind nur als 28px-Divider vorhanden. Sie müssten die gesamte visuelle Identität durchziehen: Card-Corners, Section-Backgrounds, Loading-States, Empty-States.

---

## Teil 5: Priorisierte Fixes

### CRITICAL (Woche 1)

1. **Startseite auf 2 Scroll-Screens kürzen.** Die Spec sagt es, das Design ignoriert es. Lösung: Workshops auf 3 Cards begrenzen + „Alle anzeigen"-Link. Values, Mitmachen und Aktuelles auf Subpages auslagern. Die Startseite braucht nur: Hero → Nächste Workshops → Über uns (Kurztext) → Kontakt.

2. **Workshop-Card-States differenzieren.** Drei Zustände visuell trennen: (a) Buchbar — volle Farbe, grüner CTA „Platz reservieren"; (b) Ausgebucht/Warteliste — gelber Warteliste-Badge + funktionaler Button; (c) Vergangen — Graustufen, kein CTA, nur „Fotos ansehen"-Link.

3. **`innerHTML` in `injectMobileUtilitySwitches` durch `createElement`-Pattern ersetzen.** Das eigene Sicherheitsmodell wird verletzt.

### IMPORTANT (Woche 2–3)

4. **Breakpoint-System bereinigen.** 5 Breakpoints auf 3 reduzieren: `640px` (mobile→tablet), `768px` (löschen, in 640px mergen), `960px` (tablet→desktop), `1024px` (desktop-nav). `900px` eliminieren.

5. **Amber als Handlungsfarbe etablieren.** Primäre CTAs (Workshop buchen, Warteliste, Newsletter) in Amber statt Green. Green für Navigation und informative Elemente. Das gibt der Seite sofort mehr visuelle Klarheit.

6. **Hero-Hintergrund überarbeiten.** Das Gradient+Blob-Pattern durch ein echtes Foto ersetzen (Community-Kochen mit Overlay) oder durch eine illustrative Collage aus den Hand-drawn-Elementen. Das würde die emotionale Verbindung sofort stärken.

7. **Token-Inkonsistenzen bereinigen.** `--color-primary-green` deklarieren oder Fallbacks entfernen. Hardcoded `#245C1A` in Token umwandeln. Drei Background-Töne auf zwei reduzieren.

8. **Line-Height auf 2 Werte beschränken.** `1.65` für Body, `1.25` für Headings. Alle anderen Variationen (1.55, 1.6, 1.7) eliminieren.

### POLISH (Woche 4+)

9. **Leaf-Divider auf 48–64px vergrößern.** Bei 28px sind sie visuell irrelevant. Alternativ: ganzseitige illustrative Section-Trenner als SVG-Wellen mit Food-Motiven.

10. **Card-Elevation-System schärfen.** Ruhezustand: `--shadow-soft` (statt `-card`). Hover: `--shadow-card`. Fokus/Aktiv: `--shadow-elevated`. Aktuell starten Cards zu hoch.

11. **Newsletter-Label visuell an Input binden.** Bei Flex-Row (≥480px) steht das Label optisch zu weit vom Input entfernt. Einen Fieldset-Wrapper oder eine Stacked-Variante nutzen.

12. **404-Seite erstellen.** Mit Illustration, Telefonnummer und „Zurück zur Startseite"-Button.

13. **Workshop-Filter um Datum/Verfügbarkeit erweitern.** „Kommende" vs. „Vergangene" als primärer Filter, Standort als sekundärer.

---

## Teil 6: Zwei alternative Redesign-Richtungen

### Richtung A: „Die Küche als Interface" — Warme Editorial-Sprache

**Kernidee:** Die Website fühlt sich an wie ein aufgeschlagenes Community-Kochbuch. Statt Tech-Gradients und Card-Grids dominieren großformatige Fotos, Handschrift-Akzente und warme Texturen.

**Konkret:**
- **Hero:** Vollflächiges Foto (Community beim Kochen) mit leichtem Warm-Overlay. Titel in einer Handschrift-inspirierten Variante von Nunito (Extra Bold, leicht geneigt). Kein Gradient, kein Blob.
- **Workshop-Cards:** Horizontales Layout (Bild links, Text rechts) statt vertikalem Card-Grid. Wirkt wie ein Rezept-Index. Datums-Badge als handgezeichneter Kreis.
- **Section-Trenner:** Ganzseitige Illustrationen (Brot, Gemüse, Kräuter) aus dem Mavka-Logo-Stil, als Aquarell-Textur über volle Breite.
- **Farbe:** Cream-dominanter Hintergrund. Green nur für Text und Akzente. Amber für alle CTAs. Ergebnis: wärmer, einladender, weniger „Interface".
- **Typografie:** Nunito nur für H1 und H2. Atkinson Hyperlegible für alles andere — inklusive Navigation. Weniger Schriftschnitte = klarere Hierarchie.

**Risiko:** Kann bei schlechten Fotos amateurhaft wirken. Braucht hochwertige Fotografie als Voraussetzung.

**Geeignet für:** Senioren, Familien, Menschen, die Wärme und Vertrauen suchen. Ideal für die Kernzielgruppe.

### Richtung B: „Klare Struktur, leise Eleganz" — Reduzierter Utility-Ansatz

**Kernidee:** Radikal weniger. Die Website wird zum ruhigen Informationssystem — wie ein gut gestalteter öffentlicher Aushang. Jede Seite hat genau einen Job.

**Konkret:**
- **Hero:** Kein Bild, kein Gradient. Nur Typografie auf Off-White: großer Titel (56px+), Subtitle, ein einziger Amber-CTA-Button. Daneben: die nächsten 2 Workshop-Termine als kompakte Liste (Datum + Name + Ort). Alles above the fold.
- **Workshop-Cards:** Tabellen-artiges Layout. Jede Zeile: Datum | Name | Ort | Status-Badge | CTA. Keine Bilder, keine Tags, keine Meta-Icons. Maximale Scan-Geschwindigkeit.
- **Farbe:** Monochromes Schema. Off-White + Dark Green + ein einziger Amber-Akzent. Kein Cream, kein Mint, kein Lime. 3 Farben total.
- **Navigation:** Horizontal auf Desktop, vertikal auf Mobile — ohne Overlay, einfach eine Liste.
- **Illustration:** Die Hand-drawn-Elemente werden GROSS — ein einzelnes Brot-SVG als 400px-Wasserzeichen auf der Startseite. Wenig, aber unvergesslich.

**Risiko:** Kann kalt wirken, wenn die Copy nicht warm genug ist. Braucht exzellentes Copywriting.

**Geeignet für:** Förderer, Partner, institutionelle Stakeholder. Kommuniziert Professionalität und Fokus.

---

## Zusammenfassung der Scores

| Heuristik | Score |
|-----------|-------|
| H1 Systemstatus | 4/5 |
| H2 Realitätsnähe | 4,5/5 |
| H3 Nutzerkontrolle | 4/5 |
| H4 Konsistenz | 3,5/5 |
| H5 Fehlervermeidung | 4,5/5 |
| H6 Wiedererkennung | 3,5/5 |
| H7 Flexibilität | 3,5/5 |
| H8 Ästhetik | 3/5 |
| H9 Fehlerhilfe | 4,5/5 |
| H10 Dokumentation | 3/5 |
| **Durchschnitt** | **3,8/5** |

| Weitere Dimensionen | Score |
|---------------------|-------|
| Typografie | 4/5 |
| Farbe | 3,5/5 |
| Visuelle Hierarchie | 3/5 |
| Kognitive Belastung | 3,5/5 |
| WCAG 2.1 AA | 4/5 |
| Interaktionsklarheit | 3,5/5 |
| Zielgruppen-Fit | 3,5/5 |
| Differenzierung | 2,5/5 |
| **Durchschnitt** | **3,4/5** |

| **Gesamtbewertung** | **3,6/5** |
|---|---|

---

*Die Website hat ein starkes technisches Fundament. Der nächste Schritt ist nicht „mehr bauen", sondern „mutiger gestalten". Die Critical-Fixes (Startseite kürzen, Workshop-States differenzieren) sind in einer Woche umsetzbar und hätten den größten Impact auf die Nutzererfahrung der Kernzielgruppe.*
