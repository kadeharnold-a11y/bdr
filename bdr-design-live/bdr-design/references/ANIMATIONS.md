# Animation Reference

> Cinematic motion design extracted from live DOM. Follow these specs exactly to recreate the experience.

## Motion Technology Stack

| Library | Type | Notes |
|---------|------|-------|
| **Web Animations API (8 active)** | animation |  |

## Scroll Journey

The page is **2,501px** tall. Each frame below shows what the user sees at that scroll depth.

> **Use these screenshots to understand WHAT animates, WHEN it animates, and HOW it moves.**

### 0% — Top / Hero
Scroll position: 0px

![Scroll 0%](../screens/scroll/scroll-000.png)

### 17% — Opening Section
Scroll position: 272px

![Scroll 17%](../screens/scroll/scroll-017.png)

### 33% — First Feature Section
Scroll position: 528px

![Scroll 33%](../screens/scroll/scroll-033.png)

### 50% — Mid-Page
Scroll position: 801px

![Scroll 50%](../screens/scroll/scroll-050.png)

### 67% — Lower Content
Scroll position: 1,073px

![Scroll 67%](../screens/scroll/scroll-067.png)

### 83% — Near Footer
Scroll position: 1,329px

![Scroll 83%](../screens/scroll/scroll-083.png)

### 100% — Bottom / Footer
Scroll position: 1,601px

![Scroll 100%](../screens/scroll/scroll-100.png)

## Scroll Animation Patterns

| Pattern | Library | Element Count | Duration | Delay | Easing |
|---------|---------|---------------|----------|-------|--------|
| parallax / sticky scroll | CSS | 1 | — | — | — |

### CSS Implementation

## CSS Keyframes (61 extracted)

### `@keyframes crvsFadeUp-2d9721cd`

Duration: `0.4s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `forwards`

Used by: `.crvs-anim[data-v-2d9721cd]`, `.crvs-anim-kpi[data-v-2d9721cd]`, `.crvs-table-row[data-v-2d9721cd]`

```css
@keyframes crvsFadeUp-2d9721cd {
  0% {
    opacity: 0;
    transform: translateY(12px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes progress-bar-stripes`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.progress-bar-animated`, `body .ui-state-highlight`

```css
@keyframes progress-bar-stripes {
  0% {
    background-position-x: 5px;
  }
}
```

> Background color/gradient shift · Background position (shimmer/scroll)

### `@keyframes progress-bar-stripes`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.progress-bar-animated`, `body .ui-state-highlight`

```css
@keyframes progress-bar-stripes {
  0% {
    background-position-x: 1rem;
    background-position-y: 0px;
  }
  100% {
    background-position-x: 0px;
    background-position-y: 0px;
  }
}
```

> Background color/gradient shift · Background position (shimmer/scroll)

### `@keyframes placeholder-glow`

Duration: `2s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.placeholder-glow .placeholder`

```css
@keyframes placeholder-glow {
  50% {
    opacity: 0.2;
  }
}
```

> Opacity fade

### `@keyframes placeholder-wave`

Duration: `2s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.placeholder-wave`

```css
@keyframes placeholder-wave {
  100% {
    -webkit-mask-position-x: -200%;
    -webkit-mask-position-y: 0px;
  }
}
```

### `@keyframes animation-dropdown-menu-fade-in`

Duration: `0.5s, 0.5s` · Easing: `ease, ease-out` · Delay: `0s, 0s` · Iteration: `1, 1` · Fill: `none, none`

Used by: `.dropdown-menu-animate-up`

```css
@keyframes animation-dropdown-menu-fade-in {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}
```

> Opacity fade

### `@keyframes animation-dropdown-menu-fade-in`

Duration: `0.5s, 0.5s` · Easing: `ease, ease-out` · Delay: `0s, 0s` · Iteration: `1, 1` · Fill: `none, none`

Used by: `.dropdown-menu-animate-up`

```css
@keyframes animation-dropdown-menu-fade-in {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}
```

> Opacity fade

### `@keyframes marquee-rtl`

Duration: `45s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `html[dir="rtl"] .slide-animation1`

```css
@keyframes marquee-rtl {
  0% {
    transform: translateZ(0px);
  }
  100% {
    transform: translate3d(2086px, 0px, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes marquee-rtl2`

Duration: `45s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `html[dir="rtl"] .slide-animation2`

```css
@keyframes marquee-rtl2 {
  0% {
    transform: translate3d(2086px, 0px, 0px);
  }
  100% {
    transform: translateZ(0px);
  }
}
```

> Transform/motion animation

### `@keyframes slideup`

Duration: `35s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.hero-section .hero-img-slide .banner-img-1`

```css
@keyframes slideup {
  0% {
    transform: translate3d(0px, 0px, 0px);
  }
  100% {
    transform: translate3d(0px, -100%, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes slideDown`

Duration: `35s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.hero-section .hero-img-slide .banner-img-2`

```css
@keyframes slideDown {
  0% {
    transform: translate3d(0px, -100%, 0px);
  }
  100% {
    transform: translate3d(0px, 0px, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes slide`

Duration: `45s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.sliding-wrapper .slide-background .slide`

```css
@keyframes slide {
  0% {
    transform: translate3d(0px, 0px, 0px);
  }
  100% {
    transform: translate3d(-100%, 0px, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes marquee`

Duration: `25s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.slide-animation1`

```css
@keyframes marquee {
  0% {
    transform: translate3d(0px, 0px, 0px);
  }
  100% {
    transform: translate3d(-2086px, 0px, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes marquee2`

Duration: `25s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.slide-animation2`

```css
@keyframes marquee2 {
  0% {
    transform: translate3d(-2086px, 0px, 0px);
  }
  100% {
    transform: translate3d(0px, 0px, 0px);
  }
}
```

> Transform/motion animation

### `@keyframes badge-pulse-b1f0b7b5`

Duration: `1.8s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-notif-badge[data-v-b1f0b7b5]`

```css
@keyframes badge-pulse-b1f0b7b5 {
  0%, 100% {
    box-shadow: rgba(255, 102, 146, 0.7) 0px 0px;
  }
  50% {
    box-shadow: rgba(255, 102, 146, 0) 0px 0px 0px 6px;
  }
}
```

> Shadow pulse/glow effect

### `@keyframes bell-shake-b1f0b7b5`

Duration: `1.2s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-bell-shake[data-v-b1f0b7b5]`

```css
@keyframes bell-shake-b1f0b7b5 {
  0%, 50%, 100% {
    transform: rotate(0deg);
  }
  5%, 15% {
    transform: rotate(18deg);
  }
  10%, 20% {
    transform: rotate(-16deg);
  }
  25% {
    transform: rotate(10deg);
  }
  30% {
    transform: rotate(-8deg);
  }
  35% {
    transform: rotate(4deg);
  }
  40% {
    transform: rotate(-2deg);
  }
  45% {
    transform: rotate(1deg);
  }
}
```

> Transform/motion animation

### `@keyframes ringRotate-afbd99ba`

Duration: `40s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.ring-spin[data-v-afbd99ba]`

```css
@keyframes ringRotate-afbd99ba {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes chevronBounce-afbd99ba`

Duration: `1.8s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-scroll-chevron[data-v-afbd99ba]`

```css
@keyframes chevronBounce-afbd99ba {
  0%, 100% {
    transform: rotate(45deg) translateY(0px);
  }
  50% {
    transform: rotate(45deg) translateY(5px);
  }
}
```

> Transform/motion animation

### `@keyframes spin-a080ff23`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-a080ff23]`

```css
@keyframes spin-a080ff23 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes shimmer-cb7a0567`

Duration: `1.4s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-skel-icon[data-v-cb7a0567], .bdr-skel-line[data-v-cb7a0567]`

```css
@keyframes shimmer-cb7a0567 {
  0% {
    background-position-x: -400px;
    background-position-y: 0px;
  }
  100% {
    background-position-x: 400px;
    background-position-y: 0px;
  }
}
```

> Background color/gradient shift · Background position (shimmer/scroll)

### `@keyframes pulse-amber-cb7a0567`

Duration: `1.4s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-pulse-dot[data-v-cb7a0567]`

```css
@keyframes pulse-amber-cb7a0567 {
  0%, 100% {
    box-shadow: rgba(255, 193, 7, 0.6) 0px 0px;
  }
  50% {
    box-shadow: rgba(255, 193, 7, 0) 0px 0px 0px 7px;
  }
}
```

> Shadow pulse/glow effect

### `@keyframes spin-d23f169e`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-d23f169e]`

```css
@keyframes spin-d23f169e {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes modal-pop-d23f169e`

Duration: `0.2s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `none`

Used by: `.bdr-switch-modal[data-v-d23f169e]`

```css
@keyframes modal-pop-d23f169e {
  0% {
    transform: scale(0.92);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
```

> Fade + motion enter animation

### `@keyframes spin-3766e850`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-3766e850]`

```css
@keyframes spin-3766e850 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes modal-pop-3766e850`

Duration: `0.2s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `none`

Used by: `.bdr-switch-modal[data-v-3766e850]`

```css
@keyframes modal-pop-3766e850 {
  0% {
    transform: scale(0.92);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
```

> Fade + motion enter animation

### `@keyframes spin-a974d4e6`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-a974d4e6]`

```css
@keyframes spin-a974d4e6 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes modal-pop-a974d4e6`

Duration: `0.2s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `none`

Used by: `.bdr-switch-modal[data-v-a974d4e6]`

```css
@keyframes modal-pop-a974d4e6 {
  0% {
    transform: scale(0.92);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
```

> Fade + motion enter animation

### `@keyframes spin-38ba801e`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-38ba801e]`

```css
@keyframes spin-38ba801e {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes spin-e57d8823`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-e57d8823]`

```css
@keyframes spin-e57d8823 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes spin-40c544d1`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-40c544d1]`

```css
@keyframes spin-40c544d1 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes spin-fc9a0015`

Duration: `0.9s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spinner-icon[data-v-fc9a0015]`

```css
@keyframes spin-fc9a0015 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes rotateDash-fc9a0015`

Duration: `40s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.ring-rotate[data-v-fc9a0015]`

```css
@keyframes rotateDash-fc9a0015 {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes fadeUp-fc9a0015`

Duration: `0.45s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `both`

Used by: `.card[data-v-fc9a0015]`

```css
@keyframes fadeUp-fc9a0015 {
  0% {
    opacity: 0;
    transform: translateY(16px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes draw-circle-fc9a0015`

Duration: `0.6s` · Easing: `ease` · Delay: `0.2s` · Iteration: `1` · Fill: `forwards`

Used by: `.check-circle[data-v-fc9a0015]`

```css
@keyframes draw-circle-fc9a0015 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes draw-tick-fc9a0015`

Duration: `0.4s` · Easing: `ease` · Delay: `0.8s` · Iteration: `1` · Fill: `forwards`

Used by: `.check-tick[data-v-fc9a0015]`

```css
@keyframes draw-tick-fc9a0015 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes bdr-rotate-0debc658`

Duration: `40s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-ring-spin[data-v-0debc658]`

```css
@keyframes bdr-rotate-0debc658 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes bdr-spin-0debc658`

Duration: `0.9s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-spinner[data-v-0debc658]`

```css
@keyframes bdr-spin-0debc658 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes shake-70c267ab`

Duration: `0.4s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `none`

Used by: `.track-input-shake[data-v-70c267ab]`

```css
@keyframes shake-70c267ab {
  0%, 100% {
    transform: translate(0px);
  }
  20% {
    transform: translate(-8px);
  }
  40% {
    transform: translate(8px);
  }
  60% {
    transform: translate(-6px);
  }
  80% {
    transform: translate(6px);
  }
}
```

> Transform/motion animation

### `@keyframes spin-70c267ab`

Duration: `1s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.spin[data-v-70c267ab]`

```css
@keyframes spin-70c267ab {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes draw-circle-ec7c88a5`

Duration: `0.7s` · Easing: `ease` · Delay: `0.1s` · Iteration: `1` · Fill: `forwards`

Used by: `.draw-circle[data-v-ec7c88a5]`

```css
@keyframes draw-circle-ec7c88a5 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes draw-tick-ec7c88a5`

Duration: `0.45s` · Easing: `ease` · Delay: `0.75s` · Iteration: `1` · Fill: `forwards`

Used by: `.draw-tick[data-v-ec7c88a5]`

```css
@keyframes draw-tick-ec7c88a5 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes pulse-amber-89b80290`

Duration: `1.4s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.corr-pulse-dot[data-v-89b80290]`

```css
@keyframes pulse-amber-89b80290 {
  0%, 100% {
    box-shadow: rgba(252, 209, 22, 0.7) 0px 0px;
  }
  50% {
    box-shadow: rgba(252, 209, 22, 0) 0px 0px 0px 8px;
  }
}
```

> Shadow pulse/glow effect

### `@keyframes corr-circle-89b80290`

Duration: `0.6s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `forwards`

Used by: `.corr-check-circle[data-v-89b80290]`

```css
@keyframes corr-circle-89b80290 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes corr-tick-89b80290`

Duration: `0.4s` · Easing: `ease` · Delay: `0.6s` · Iteration: `1` · Fill: `forwards`

Used by: `.corr-check-tick[data-v-89b80290]`

```css
@keyframes corr-tick-89b80290 {
  100% {
    stroke-dashoffset: 0;
  }
}
```

> SVG stroke animation

### `@keyframes pulse-amber-9f6669ed`

Duration: `1.4s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-pulse-dot[data-v-9f6669ed]`

```css
@keyframes pulse-amber-9f6669ed {
  0%, 100% {
    box-shadow: rgba(255, 193, 7, 0.6) 0px 0px;
  }
  50% {
    box-shadow: rgba(255, 193, 7, 0) 0px 0px 0px 6px;
  }
}
```

> Shadow pulse/glow effect

### `@keyframes bdr-rotate-08bb4432`

Duration: `40s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-ring-spin[data-v-08bb4432]`

```css
@keyframes bdr-rotate-08bb4432 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes bdr-spin-08bb4432`

Duration: `0.9s` · Easing: `linear` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.bdr-spinner[data-v-08bb4432]`

```css
@keyframes bdr-spin-08bb4432 {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes oq-pulse-yellow-72ffb4a3`

Duration: `1.6s` · Easing: `ease-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.dot-live-yellow[data-v-72ffb4a3]`

```css
@keyframes oq-pulse-yellow-72ffb4a3 {
  0% {
    box-shadow: rgba(250, 204, 21, 0.55) 0px 0px;
  }
  70% {
    box-shadow: rgba(250, 204, 21, 0) 0px 0px 0px 12px;
  }
  100% {
    box-shadow: rgba(250, 204, 21, 0) 0px 0px;
  }
}
```

> Shadow pulse/glow effect

### `@keyframes aw-fade-df995795`

Duration: `0.2s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `none`

Used by: `.aw-tab-panel[data-v-df995795]`

```css
@keyframes aw-fade-df995795 {
  0% {
    opacity: 0;
    transform: translateY(6px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes pulse-f235343f`

Duration: `1.5s` · Easing: `ease` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.pulse-ring[data-v-f235343f]`

```css
@keyframes pulse-f235343f {
  0% {
    transform: scale(1);
    opacity: 0.8;
  }
  50% {
    transform: scale(2.2);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 0;
  }
}
```

> Fade + motion enter animation

### `@keyframes sla-bar-shimmer-3219270b`

Duration: `1.1s` · Easing: `ease-in-out` · Delay: `0s` · Iteration: `infinite` · Fill: `none`

Used by: `.sla-bar-track--shimmer[data-v-3219270b]::after`

```css
@keyframes sla-bar-shimmer-3219270b {
  0% {
    background-position-x: 100%;
    background-position-y: 0px;
  }
  100% {
    background-position-x: -100%;
    background-position-y: 0px;
  }
}
```

> Background color/gradient shift · Background position (shimmer/scroll)

### `@keyframes op-cell-reveal-f285a65b`

Duration: `0.35s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `forwards`

Used by: `.heatmap-cell[data-v-f285a65b]`

```css
@keyframes op-cell-reveal-f285a65b {
  0% {
    opacity: 0;
    transform: scale(0.6);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}
```

> Fade + motion enter animation

### `@keyframes wbFadeInUp-ea550497`

Duration: `0.35s` · Easing: `ease` · Delay: `0s` · Iteration: `1` · Fill: `forwards`

Used by: `.wb-sdg-row[data-v-ea550497]`

```css
@keyframes wbFadeInUp-ea550497 {
  0% {
    opacity: 0;
    transform: translateY(8px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes spinner-border`

```css
@keyframes spinner-border {
  100% {
    transform: rotate(360deg);
  }
}
```

> Transform/motion animation

### `@keyframes spinner-grow`

```css
@keyframes spinner-grow {
  0% {
    transform: scale(0);
  }
  50% {
    opacity: 1;
    transform: none;
  }
}
```

> Fade + motion enter animation

### `@keyframes animation-dropdown-menu-move-up-scroll`

```css
@keyframes animation-dropdown-menu-move-up-scroll {
  0% {
    top: 71px;
  }
  100% {
    top: 70px;
  }
}
```

### `@keyframes menuDropdownShow`

```css
@keyframes menuDropdownShow {
  0% {
    opacity: 0;
    transform: translateY(-0.5rem);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes fadeUp-afbd99ba`

```css
@keyframes fadeUp-afbd99ba {
  0% {
    opacity: 0;
    transform: translateY(18px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes bdr-fadeUp-0debc658`

```css
@keyframes bdr-fadeUp-0debc658 {
  0% {
    opacity: 0;
    transform: translateY(14px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes bdr-fadeUp-08bb4432`

```css
@keyframes bdr-fadeUp-08bb4432 {
  0% {
    opacity: 0;
    transform: translateY(14px);
  }
  100% {
    opacity: 1;
    transform: translateY(0px);
  }
}
```

> Fade + motion enter animation

### `@keyframes livepulse`

```css
@keyframes livepulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.3);
  }
}
```

> Fade + motion enter animation

## Global Transition Declarations

These `transition` values were extracted from CSS rules across the site:

```css
transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
transition: background-position 0.15s ease-in-out;
transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
transition: opacity 0.15s linear;
transition: height 0.35s;
transition: width 0.35s;
transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
transition: var(--bs-navbar-toggler-transition);
transition: var(--bs-accordion-transition);
transition: var(--bs-accordion-btn-icon-transition);
```

## How to Recreate This Motion Design

### Step 1 — Install Dependencies

```bash
```

### Step 2 — Scroll-Reveal Pattern

Elements that animate into view follow this pattern:

```css
/* Initial hidden state */
.reveal {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1),
              transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}
.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}
```

### Step 3 — Key Motion Principles

- **Duration scale:** `0.15s` — use these values, never invent new durations
- **Always add** `@media (prefers-reduced-motion: reduce) { * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; } }`

### Step 4 — Scroll Journey Reference

Match what happens at each scroll position:

- **0%** (`0px`) → `screens/scroll/scroll-000.png`
- **17%** (`272px`) → `screens/scroll/scroll-017.png`
- **33%** (`528px`) → `screens/scroll/scroll-033.png`
- **50%** (`801px`) → `screens/scroll/scroll-050.png`
- **67%** (`1073px`) → `screens/scroll/scroll-067.png`
- **83%** (`1329px`) → `screens/scroll/scroll-083.png`
- **100%** (`1601px`) → `screens/scroll/scroll-100.png`

