# ZyroInn Design Tokens

This document defines the shared design tokens for every surface in ZyroInn. Use these exact names and values for colors, typography, spacing, and border radius so future screens remain consistent.

## 1. Brand color ramp

- `brand-50`: #F5EEFF — lightest purple tint for backgrounds and hover surfaces.
- `brand-100`: #E7D8FF — subtle purple background for cards and badges.
- `brand-200`: #CEB0FF — soft purple for hover backgrounds and elevated surfaces.
- `brand-300`: #B486FF — accent background for active chips and soft buttons.
- `brand-400`: #9759F0 — strong purple for interactive hover states and borders.
- `brand-500`: #6C2BD9 — primary brand color used for buttons, links, and key accents.
- `brand-600`: #5A24B3 — pressed/active state for primary CTA and filled controls.
- `brand-700`: #471C89 — text-on-brand contrast and deeper badge backgrounds.
- `brand-800`: #36145F — dark brand shade for overlays and strong emphasis.
- `brand-900`: #251031 — highest contrast brand shade for minimal text and emphasis.

## 2. Neutral grayscale

- `neutral-50`: #F8F8FB — page background and large containers.
- `neutral-100`: #EFF0F5 — card backgrounds, panels, and form fields.
- `neutral-200`: #D8DAE5 — divider backgrounds, secondary surfaces.
- `neutral-300`: #BFC3D6 — border lines, low-contrast text backgrounds.
- `neutral-400`: #9EA3B8 — disabled text and secondary icon states.
- `neutral-500`: #6E738A — body text on neutral surfaces and subtle captions.

## 3. Semantic colors

### Success
- `success`: #2EBF8F — a calm teal-green for positive states and confirmations.
- `success-bg`: #E6FBF1 — light success background for badges and cards.
- Reasoning: uses a cool teal tone that harmonizes with purple while avoiding default green.

### Warning
- `warning`: #D48B2F — warm amber for caution and status alerts.
- `warning-bg`: #FFF4E1 — soft warning surface for alerts and tag backgrounds.
- Reasoning: amber complements the purple palette with warmth and keeps UI messaging calm.

### Error
- `error`: #D9468F — rich magenta-red for critical errors and destructive actions.
- `error-bg`: #FDE8F4 — muted error surface for inline validation and notifications.
- Reasoning: a jewel-tone error color that is distinct yet harmonious with the brand purple.

### Info
- `info`: #5C7CE6 — indigo-blue for neutral information states.
- `info-bg`: #EBF0FF — pale info background for helper cards and banners.
- Reasoning: a blue-indigo hue that sits naturally next to purple and keeps informational content clear.

## 4. Room status colors

These colors are fixed and must be reused consistently.

- `room-status-clean`: #2EB38B — clean room status.
- `room-status-dirty`: #DF7A00 — dirty room status.
- `room-status-inspect`: #7C5CD8 — inspection required status.
- `room-status-out_of_order`: #D92B5E — out-of-order room status.

## 5. Task priority colors

- `task-priority-urgent`: #C92B5A — urgent task priority.
- `task-priority-normal`: #5C627A — normal task priority.

## 6. Typography

### Fonts
- `font-family-heading`: Poppins Bold — used for all headings and display titles.
- `font-family-body`: Inter Regular — used for all body copy, labels, and paragraph text.

### Headings
- `h1`: 2.5rem / 40px — page and screen titles.
- `h2`: 2rem / 32px — section titles and major headings.
- `h3`: 1.5rem / 24px — subsection headings.
- `h4`: 1.25rem / 20px — card titles and small headings.

### Body text
- `body`: 1rem / 16px — default body copy.
- `body-small`: 0.8125rem / 13px — smallest allowed body text.
- Minimum body size allowed anywhere: 9.5pt / 13px.

## 7. Spacing scale

- `space-1`: 0.25rem / 4px — tiny gaps and icon padding.
- `space-2`: 0.5rem / 8px — small gutters and form control spacing.
- `space-3`: 0.75rem / 12px — compact spacing between elements.
- `space-4`: 1rem / 16px — default padding for cards and panels.
- `space-5`: 1.25rem / 20px — generous spacing inside cards.
- `space-6`: 1.5rem / 24px — section padding and stacked groups.
- `space-8`: 2rem / 32px — larger section margins.
- `space-10`: 2.5rem / 40px — module spacing and page breaks.

## 8. Border radius

- `radius-sm`: 0.5rem / 8px — small rounded corners for buttons and inputs.
- `radius-md`: 1rem / 16px — default card radius.
- `radius-lg`: 1.5rem / 24px — large surface radius for panels and overlay containers.
- `radius-xl`: 2rem / 32px — soft container radius for hero sections.
- `radius-pill`: 9999px — pill shapes for badges and status pills.

## 9. Usage guidance

- Use `brand-500` as the primary brand accent and `brand-600`/`brand-700` for pressed or strong states.
- Use `neutral-100` for card backgrounds and `neutral-300` for borders/dividers.
- Use `success-bg`, `warning-bg`, `error-bg`, and `info-bg` for light background surfaces.
- Use `room-status-*` exactly for room state badges and status labels.
- Use `task-priority-*` exactly for task tags and priority chips.
- Use `font-family-heading` for all headings and `font-family-body` for body text.
- Never use body copy smaller than `body-small` (13px / 9.5pt).
