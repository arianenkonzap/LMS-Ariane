# LMS-ARIANE — Plan de design

## Concept
"Ariane" = fil d'Ariane (parcours, progression, guidage à travers un labyrinthe d'apprentissage).
Signature visuelle : un FIL (ligne de progression) qui traverse l'interface — utilisé comme
indicateur de progression de module/leçon, comme séparateur de section, et comme motif de fond.

## Palette (4-6 couleurs nommées)
- --ariane-ink:      #1A1B2E   (fond profond, bleu-nuit encre)
- --ariane-paper:    #F7F5F0   (fond clair, papier crème)
- --ariane-thread:   #E8A33D   (fil d'or — accent principal, progression, CTA)
- --ariane-violet:   #6C5CE7   (violet labyrinthe — accents secondaires, liens)
- --ariane-sage:     #4FA37D   (vert validation/succès)
- --ariane-coral:    #E85D5D   (alerte/erreur)

## Typographie
- Display : "Fraunces" (serif à fort caractère, empattements marqués) — titres, hero
- Corps : "Manrope" (sans-serif géométrique, lisible) — texte courant, UI
- Utilitaire/data : "JetBrains Mono" — codes certificats, scores, métadonnées

## Layout
- Sidebar fixe à gauche (navigation par rôle) + zone de contenu principale
- Le "fil" (thread) = barre de progression filiforme, courbée en SVG, qui relie les leçons
  d'un cours sous forme de chemin (comme une carte de progression)
- Cartes (cards) à coins légèrement arrondis (8px), bordures fines, ombres très subtiles

## Signature
Le "Chemin du fil" : sur la page cours/dashboard étudiant, les leçons sont reliées par un
tracé SVG en forme de fil sinueux (comme un fil d'Ariane), avec des points lumineux pour
les leçons complétées (or) et des points creux pour celles à venir. C'est l'élément
mémorable de l'app — la métaphore visuelle du nom.

## ASCII wireframe (dashboard étudiant)
```
┌──────────┬─────────────────────────────────────┐
│  LOGO    │  Bonjour, Paul                       │
│  ARIANE  │  ───────────────────────────────────│
│          │  Mes modules                          │
│  Accueil │  ┌─────────────┐ ┌─────────────┐    │
│  Modules │  │ Module 1    │ │ Module 2    │    │
│  Certifs │  │ ~~~o──o──○  │ │ ~~~●──●──●  │    │
│  Profil  │  │ 45%         │ │ 100% ✓      │    │
│          │  └─────────────┘ └─────────────┘    │
└──────────┴─────────────────────────────────────┘
```
