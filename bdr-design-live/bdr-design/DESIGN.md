# BDR DESIGN.md

> Auto-generated design system — reverse-engineered via static analysis by skillui.
> Frameworks: None detected
> Colors: 20 · Fonts: 3 · Components: 0
> Icon library: not detected · State: not detected
> Primary theme: dark · Dark mode toggle: no · Motion: expressive

## Visual Reference

**Match this design exactly** — study colors, fonts, spacing, and component shapes before writing any UI code.

![BDR Homepage](../screenshots/homepage.png)

---

## 1. Visual Theme & Atmosphere

This is a **dark-themed** interface with a cool tone. Depth is expressed through layered shadows and subtle surface color variation. Typography pairs **Manrope** for display/headings with **Public Sans** for body text, creating clear visual hierarchy through type contrast. Spacing follows a **4px base grid** (compact density), with scale: 2, 4, 6, 8, 10, 12, 14, 16px. The accent color **#635bff** anchors interactive elements (buttons, links, focus rings). Motion is expressive — spring physics, layout animations, and staggered reveals are part of the visual language.

---

## 2. Color Palette & Roles

| Token | Hex | Role | Use |
|---|---|---|---|
| bs-primary | `#006b3f` | background | Page background, darkest surface |
| bs-emphasis-color | `#000000` | surface | Card and panel backgrounds |
| bs-card-bg | `#1a2537` | surface | Card and panel backgrounds |
| bs-heading-color | `#ffffff` | text-primary | Headings and body text |
| bs-body-color | `#98a4ae` | text-muted | Captions, placeholders, secondary info |
| bs-dark-text-emphasis | `#29343d` | border | Dividers, card borders, outlines |
| bs-primary | `#635bff` | accent | CTAs, links, focus rings, active states |
| danger | `#cc0001` | danger | Error states, destructive actions |
| success | `#005233` | success | Success states, positive indicators |
| warning | `#fcd116` | warning | Warning states, caution indicators |
| bs-accordion-btn-color | `#eaeff4` | info | Informational highlights |
| bs-muted | `#526b7a` | unknown | Palette color |
| unknown | `#111c2d` | unknown | Palette color |
| bs-table-border-color | `#3b5166` | unknown | Palette color |
| unknown | `#64748b` | unknown | Palette color |
| unknown | `#7a3800` | unknown | Palette color |
| bs-warning | `#f8c20a` | unknown | Palette color |
| bs-btn-border-color | `#0a2540` | unknown | Palette color |
| bs-border-color | `#e0e6eb` | unknown | Palette color |
| bdr-muted | `#7c8fac` | unknown | Palette color |

### CSS Variable Tokens

```css
--bs-card-subtitle-color: rgba(255,255,255,0.6);
--bs-secondary-color: rgba(255,255,255,0.6);
--bs-card-title-color: rgba(255,255,255,0.85);
--bs-card-subtitle-color: rgba(255,255,255,0.6);
--bs-card-bg: #1a2537;
--bs-card-box-shadow: none;
--bs-table-border-color: #313e54;
--bs-accordion-border-color: #333f55;
--bs-primary: #635BFF;
--bs-primary-rgb: 99,91,255;
--bs-secondary: #16CDC7;
--bs-secondary-rgb: 22,205,199;
--bs-btn-border-color: #635BFF;
--bs-btn-hover-border-color: #5249fe;
--bs-btn-border-color: #16CDC7;
--bs-btn-hover-border-color: #1cc3bd;
--bs-primary: #0074ba;
--bs-primary-rgb: 0,116,186;
--bs-light-primary: rgba(0,116,186,0.1);
--bs-primary-bg-subtle: rgba(0,116,186,0.1);
```


---

## 3. Typography Rules

**Font Stack:**
- **Public Sans** — Heading 1, Heading 2, Heading 3
- **Manrope** — Body, Caption
- **SFMono-Regular** — Code

**Font Sources:**

```css
@font-face {
  font-family: "Manrope";
  src: url("https://fonts.gstatic.com/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk79FO_F.ttf") format("truetype");
  font-weight: 400;
}
@font-face {
  font-family: "Manrope";
  src: url("https://fonts.gstatic.com/s/manrope/v20/xn7_YHE41ni1AdIRqAuZuw1Bx9mbZk4aE-_F.ttf") format("truetype");
  font-weight: 700;
}
```

| Role | Font | Size | Weight |
|---|---|---|---|
| Heading 1 | Public Sans | 5rem | 700 |
| Heading 2 | Public Sans | 4.5rem | 700 |
| Heading 3 | Public Sans | 4rem | 700 |
| Body | Manrope | 13px | 400 |
| Caption | Manrope | 12px | 400 |
| Code | SFMono-Regular | 14px | 400 |

**Typographic Rules:**
- Limit to 3 font families max per screen
- Use **Public Sans** for body/UI text, **Manrope** for display/headings
- Maintain consistent hierarchy: no more than 3-4 font sizes per screen
- Headings use bold (600-700), body uses regular (400)
- Line height: 1.5 for body text, 1.2 for headings
- Use color and opacity for secondary hierarchy, not additional font sizes


---

## 4. Component Stylings

No components detected. Scan `src/components/` or `components/` to populate this section.

---

## 5. Layout Principles

- **Base spacing unit:** 4px
- **Spacing scale:** 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24
- **Border radius:** .25em, .25rem, .375rem, 1rem, 2em, 8px, inherit, .2rem, 2px, 3px, 4px, 5px, 6px, 7px, 10px, 12px, 14px, 15%, 15px, 16px, 20px, 23px, 24px, 50px, 99px, 100%, 999px
- **Max content width:** 1024px

**Spacing as Meaning:**
| Spacing | Use |
|---|---|
| 4-8px | Tight: related items within a group |
| 12-16px | Medium: between groups |
| 24-32px | Wide: between sections |
| 48px+ | Vast: major section breaks |


---

## 6. Depth & Elevation

### Flat — subtle depth hints

- `0 0 0 1px #fff,0 0 0 .25rem rgba(99,91,255,.25)`
- `0 0 0 2px #facc1566`
- `0 0 0 1px #ffffff59`

### Raised — cards, buttons, interactive elements

- `var(--bs-box-shadow-sm)`
- `var(--bs-box-shadow-inset)`
- `var(--bs-box-shadow-inset),0 0 0 .25rem rgba(99,91,255,.25)`

### Floating — dropdowns, popovers, modals

- `0 17px 20px -8px rgba(77,91,236,.231372549)`
- `rgba(0,0,0,.05)0 9px 17.5px`
- `0 5px 10px rgba(0,0,0,.03)`

### Overlay — full-screen overlays, top-level dialogs

- `inset 0 0 0 9999px rgba(239,244,250,.2)`
- `inset 0 0 0 9999px var(--bs-table-bg-state,var(--bs-table-bg-type,var(--bs-table-accent-bg)))`
- `0 15px 30px rgba(0,0,0,.12)`

### Z-Index Scale

`0, 1, 2, 3, 4, 5, 9, 10, 40, 45, 50, 98, 99, 100, 200, 300, 999, 1000, 1020, 1030, 1040, 1050, 1055, 1060, 9999, 99999, 999999`



---

## 7. Animation & Motion

This project uses **expressive motion**. Animations are an integral part of the experience.

### CSS Animations

- `@keyframes progress-bar-stripes`
- `@keyframes spinner-border`
- `@keyframes spinner-grow`
- `@keyframes placeholder-glow`
- `@keyframes placeholder-wave`
- `@keyframes animation-dropdown-menu-move-up-scroll`
- `@keyframes animation-dropdown-menu-fade-in`
- `@keyframes menuDropdownShow`

### Motion Guidelines

- Duration: 150-300ms for micro-interactions, 300-500ms for page transitions
- Easing: `ease-out` for enters, `ease-in` for exits
- Always respect `prefers-reduced-motion`


---

## 8. Do's and Don'ts

### Do's

- Use `#635bff` for interactive elements (buttons, links, focus rings)
- Use `#006b3f` as the primary page background
- Pair **Public Sans** (body) with **Manrope** (display) — these are the only allowed fonts
- Follow the **4px** spacing grid for all margins, padding, and gaps
- Use the defined shadow tokens for elevation — see Section 6
- Use border-radius from the scale: .25em, .25rem, .375rem, 1rem, 2em

### Don'ts

- Don't introduce colors outside this palette — extend the design tokens first
- Don't introduce additional font families beyond Public Sans and Manrope and SFMono-Regular
- Don't use arbitrary spacing values — stick to multiples of 4px
- Don't create custom box-shadow values outside the system tokens
- Don't use arbitrary border-radius values — pick from the defined scale
- Don't use backdrop-blur or blur effects

### Anti-Patterns (detected from codebase)

- No blur or backdrop-blur effects
- No zebra striping on tables/lists


---

## 9. Responsive Behavior

| Name | Value | Source |
|---|---|---|
| xs | 241px | css |
| xs | 360px | css |
| xs | 400px | css |
| xs | 480px | css |
| sm | 481px | css |
| sm | 575px | css |
| sm | 575.98px | css |
| sm | 576px | css |
| sm | 600px | css |
| sm | 640px | css |
| md | 767.98px | css |
| md | 768px | css |
| lg | 991px | css |
| lg | 991.98px | css |
| lg | 992px | css |
| lg | 1024px | css |
| xl | 1100px | css |
| xl | 1199px | css |
| xl | 1200px | css |
| 2xl | 1299.98px | css |
| 2xl | 1300px | css |
| 2xl | 1399.98px | css |
| 2xl | 1400px | css |
| 2xl | 1500px | css |

**Approach:** Use `@media (min-width: ...)` queries matching the breakpoints above.


---

## 10. Agent Prompt Guide

Use these as starting points when building new UI:

### Build a Card

```
Background: #000000
Border: 1px solid #29343d
Radius: 7px
Padding: 16px
Font: Public Sans
Use shadow tokens from Section 6.
```

### Build a Button

```
Primary: bg #635bff, text white
Ghost: bg transparent, border #29343d
Padding: 8px 16px
Radius: 7px
Hover: opacity 0.9 or lighter shade
Focus: ring with #635bff
```

### Build a Page Layout

```
Background: #006b3f
Max-width: 1024px, centered
Grid: 4px base
Responsive: mobile-first, breakpoints from Section 9
```

### Build a Stats Card

```
Surface: #000000
Label: #98a4ae (muted, 12px, uppercase)
Value: #ffffff (primary, 24-32px, bold)
Status: use success/warning/danger from Section 2
```

### Build a Form

```
Input bg: #006b3f
Input border: 1px solid #29343d
Focus: border-color #635bff
Label: #98a4ae 12px
Spacing: 16px between fields
Radius: 7px
```

### General Component

```
1. Read DESIGN.md Sections 2-6 for tokens
2. Colors: only from palette
3. Font: Public Sans, type scale from Section 3
4. Spacing: 4px grid
5. Components: match patterns from Section 4
6. Elevation: shadow tokens
```
