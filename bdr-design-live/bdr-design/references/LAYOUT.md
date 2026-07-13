# Layout Reference

> Auto-extracted from live DOM. Use this to understand how the site is structured spatially.

## Spacing System

**Base grid:** 4px

**Scale:** `2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30` px

| Spacing | Semantic Use |
|---------|-------------|
| 4px | Tight — within a component |
| 8px | Medium — between sibling items |
| 16px | Wide — between sections |
| 32px | Vast — major section breaks |

## Flex Layouts

| Element | Direction | Justify | Align | Gap | Children |
|---------|-----------|---------|-------|-----|----------|
| `nav.navbar.navbar-expand-lg` | row | start | center | — | 3 |
| `section.bdr-hero.d-flex` | column | center | center | — | 7 |
| `a.navbar-brand.d-flex` | row | — | center | 8px | 2 |
| `div.d-flex.gap-3` | row | center | — | 16px | 2 |
| `div#landingNav.collapse.navbar-collapse` | row | end | center | — | 1 |
| `div.bdr-hero-badge.mb-4` | row | — | center | 8px | 1 |
| `div.d-flex.gap-3` | row | center | — | 16px | 2 |
| `div.border-top.pt-3` | row | space-between | center | 8px | 2 |
| `div.row.g-3` | row | center | — | — | 4 |
| `div.row.g-4` | row | — | — | — | 4 |
| `div.row.g-4` | row | — | — | — | 4 |
| `div.row.g-3` | row | — | — | — | 6 |
| `div.d-flex.align-items-center` | row | — | center | 8px | 2 |

## Structural Containers

### `<nav>` (`nav.navbar.navbar-expand-lg`)

```
display:          flex
flex-direction:   row
justify-content:  start
align-items:      center
padding:          8px 48px
children:         3
```

### `<footer>` (`footer.bdr-footer.pt-5`)

```
display:          block
padding:          48px 16px 16px
children:         1
```

### `<section>` (`section.bdr-hero.d-flex`)

```
display:          flex
flex-direction:   column
justify-content:  center
align-items:      center
padding:          48px 16px
children:         7
```

### `<section>` (`section#how-it-works.bg-white.py-5`)

```
display:          block
padding:          48px 16px
children:         1
```

### `<section>` (`section.bdr-info-strip.py-4`)

```
display:          block
padding:          24px 0px
children:         1
```

### `<section>` (`section.bdr-cta-banner.py-5`)

```
display:          block
padding:          48px 16px
children:         1
```

## Layout Rules

- **Container max-width:** `1320px` — always center with `margin: auto`
- Primary layout system: **Flexbox**
- Every spacing value must be a multiple of **4px**
- Never use arbitrary margin/padding values outside the spacing scale

