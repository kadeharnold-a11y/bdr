# Interaction Reference

> Micro-interactions extracted from live DOM. Recreate these exactly for authentic feel.

## Coverage

| Component Type | Count | States Captured |
|----------------|-------|----------------|
| Button | 3 | default, hover, focus |
| Role Button | 3 | default, hover, focus |
| Link | 2 | default, hover, focus |

## Transition System

These transition declarations were extracted from interactive elements:

```css
transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
transition: opacity 0.15s, transform 0.1s;
transition: border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.1s;
transition: all;
```

Apply these to all interactive elements. Never invent new durations or easings.

## Button Interactions

### Button 1 — `Track Application`

**States:**

- Default: `../screens/states/button-1-default.png`
- Hover: `../screens/states/button-1-hover.png`
- Focus: `../screens/states/button-1-focus.png`

**On hover:**

```css
/* background-color: rgba(0, 0, 0, 0) → */ background-color: rgb(54, 199, 108);
/* color: rgb(54, 199, 108) → */ color: rgb(255, 255, 255);
/* outline: rgb(54, 199, 108) none 3px → */ outline: rgb(255, 255, 255) none 3px;
/* outline-color: rgb(54, 199, 108) → */ outline-color: rgb(255, 255, 255);
```

**On focus:**

```css
/* background-color: rgba(0, 0, 0, 0) → */ background-color: rgb(54, 199, 108);
/* color: rgb(54, 199, 108) → */ color: rgb(255, 255, 255);
/* outline: rgb(54, 199, 108) none 3px → */ outline: rgb(255, 255, 255) none 0px;
/* outline-color: rgb(54, 199, 108) → */ outline-color: rgb(255, 255, 255);
```

**Transition:** `color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out`

### Button 2 — `EN`

**States:**

- Default: `../screens/states/button-2-default.png`
- Hover: `../screens/states/button-2-hover.png`
- Focus: `../screens/states/button-2-focus.png`

**On hover:**

```css
/* color: rgb(90, 106, 133) → */ color: rgb(0, 107, 63);
/* outline: rgb(90, 106, 133) none 3px → */ outline: rgb(0, 107, 63) none 3px;
/* outline-color: rgb(90, 106, 133) → */ outline-color: rgb(0, 107, 63);
```

**On focus:**

```css
/* outline: rgb(90, 106, 133) none 3px → */ outline: rgb(90, 106, 133) none 0px;
```

**Transition:** `color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out`

### Button 3 — `Apply here`

**States:**

- Default: `../screens/states/button-3-default.png`
- Hover: `../screens/states/button-3-hover.png`
- Focus: `../screens/states/button-3-focus.png`

**On hover:**

```css
/* opacity: 1 → */ opacity: 0.92;
/* transform: none → */ transform: matrix(1, 0, 0, 1, 0, -1);
```

**On focus:**

```css
/* outline: rgb(122, 56, 0) none 3px → */ outline: rgb(122, 56, 0) none 0px;
```

**Transition:** `opacity 0.15s, transform 0.1s`

## Role Button Interactions

### Role Button 1 — `Early Birth
Within 12 months`

**States:**

- Default: `../screens/states/role-button-1-default.png`
- Hover: `../screens/states/role-button-1-hover.png`
- Focus: `../screens/states/role-button-1-focus.png`

**On hover:**

```css
/* background-color: rgb(255, 255, 255) → */ background-color: rgb(240, 250, 244);
/* border-color: rgb(224, 230, 235) → */ border-color: rgb(0, 107, 63);
/* box-shadow: rgba(175, 182, 201, 0.2) 0px 2px 4px -1px → */ box-shadow: rgba(0, 0, 0, 0.06) 0px 9px 17.5px 0px;
/* transform: none → */ transform: matrix(1, 0, 0, 1, 0, -2);
```

**Transition:** `border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.1s`

### Role Button 2 — `Late Birth
Over 12 months`

**States:**

- Default: `../screens/states/role-button-2-default.png`
- Hover: `../screens/states/role-button-2-hover.png`
- Focus: `../screens/states/role-button-2-focus.png`

**On hover:**

```css
/* background-color: rgb(255, 255, 255) → */ background-color: rgb(240, 250, 244);
/* border-color: rgb(224, 230, 235) → */ border-color: rgb(0, 107, 63);
/* box-shadow: rgba(175, 182, 201, 0.2) 0px 2px 4px -1px → */ box-shadow: rgba(0, 0, 0, 0.06) 0px 9px 17.5px 0px;
/* transform: none → */ transform: matrix(1, 0, 0, 1, 0, -2);
```

**Transition:** `border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.1s`

### Role Button 3 — `Death Registration
Standard & foetal`

**States:**

- Default: `../screens/states/role-button-3-default.png`
- Hover: `../screens/states/role-button-3-hover.png`
- Focus: `../screens/states/role-button-3-focus.png`

**On hover:**

```css
/* background-color: rgb(255, 255, 255) → */ background-color: rgb(240, 250, 244);
/* border-color: rgb(224, 230, 235) → */ border-color: rgb(0, 107, 63);
/* box-shadow: rgba(175, 182, 201, 0.2) 0px 2px 4px -1px → */ box-shadow: rgba(0, 0, 0, 0.06) 0px 9px 17.5px 0px;
/* transform: none → */ transform: matrix(1, 0, 0, 1, 0, -2);
```

**Transition:** `border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.1s`

## Link Interactions

### Link 1 — `+233 30 266 6651`

**States:**

- Default: `../screens/states/link-1-default.png`
- Hover: `../screens/states/link-1-hover.png`
- Focus: `../screens/states/link-1-focus.png`

**On hover:**

```css
/* color: rgb(82, 107, 122) → */ color: rgb(0, 107, 63);
/* border-color: rgb(82, 107, 122) → */ border-color: rgb(0, 107, 63);
/* outline: rgb(82, 107, 122) none 3px → */ outline: rgb(0, 107, 63) none 3px;
/* outline-color: rgb(82, 107, 122) → */ outline-color: rgb(0, 107, 63);
```

**On focus:**

```css
/* outline: rgb(82, 107, 122) none 3px → */ outline: rgb(16, 16, 16) auto 1px;
/* outline-color: rgb(82, 107, 122) → */ outline-color: rgb(16, 16, 16);
```

**Transition:** `all`

### Link 2 — `hbdrp.bdr.gov.gh`

**States:**

- Default: `../screens/states/link-2-default.png`
- Hover: `../screens/states/link-2-hover.png`
- Focus: `../screens/states/link-2-focus.png`

**On hover:**

```css
/* color: rgb(82, 107, 122) → */ color: rgb(0, 107, 63);
/* border-color: rgb(82, 107, 122) → */ border-color: rgb(0, 107, 63);
/* outline: rgb(82, 107, 122) none 3px → */ outline: rgb(0, 107, 63) none 3px;
/* outline-color: rgb(82, 107, 122) → */ outline-color: rgb(0, 107, 63);
```

**On focus:**

```css
/* outline: rgb(82, 107, 122) none 3px → */ outline: rgb(16, 16, 16) auto 1px;
/* outline-color: rgb(82, 107, 122) → */ outline-color: rgb(16, 16, 16);
```

**Transition:** `all`

## Interaction Rules

- Accent color `#635bff` is used for focus rings, active states, and hover highlights
- Hover effects use **opacity** changes, not color shifts
- Hover effects include **color transitions** — use the extracted values, not approximations
- Focus states use **outline** (not box-shadow) — always match the extracted focus ring
- Transition durations in use: `0.15s`, `0.1s`
- Always respect `prefers-reduced-motion` — set all transitions to `0s` when enabled

