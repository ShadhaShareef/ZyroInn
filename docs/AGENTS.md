# AGENTS.md

# ZyroInn Frontend Development Guidelines

You are contributing to **ZyroInn**, a production hotel operations and booking platform. This is an active production codebase used by real hotel staff and guests. Every change should improve the existing application without introducing regressions.

These instructions apply to every task in this repository unless explicitly overridden.

---

# PROJECT CONTEXT

ZyroInn consists of four primary frontend surfaces that share a common design system and component library:

* Guest Portal
* Staff Portal
* Owner Portal
* Admin Portal

The application is already well into development. Assume existing implementations should be extended and improved rather than replaced.

---

# SOURCE OF TRUTH

`PRD.md` is the authoritative requirements document.

Before implementing any feature:

* Read the relevant section of `PRD.md`.
* Match the implementation to the documented requirements.
* Never invent business rules.
* Never remove or alter existing requirements.
* If the implementation conflicts with the PRD, clearly explain the conflict before making changes.

---

# MY RESPONSIBILITY

Assume I am responsible only for the frontend unless I explicitly request backend work.

Focus on:

* UI implementation
* UX improvements
* Responsive layouts
* Accessibility
* Frontend architecture
* Component reuse
* Client-side interactions
* Performance optimization
* Design consistency

Do **not** modify:

* Database schema
* SQL migrations
* Backend business logic
* Authentication flow
* API contracts

If frontend changes require backend support, clearly state what backend work is needed instead of implementing it.

---

# TECHNOLOGY STACK

Frontend:

* Server-rendered PHP templates
* Tailwind CSS
* Vanilla JavaScript
* Alpine.js where already used

Do not introduce React, Vue, build tools, or additional frontend frameworks unless explicitly requested.

---

# WORKING WITH THE EXISTING CODEBASE

Before creating anything new:

* Search for existing components.
* Reuse before creating.
* Extend before replacing.
* Maintain backwards compatibility.
* Keep changes limited to the requested feature.
* Avoid unrelated refactoring.

Never duplicate:

* Buttons
* Cards
* Tables
* Forms
* Badges
* Modals
* Dialogs
* Navigation
* Utility functions

If a reusable component should exist, create it inside the shared component structure rather than copying markup.

---

# DESIGN SYSTEM

Follow the existing design system.

Always use:

* Brand color tokens
* Existing spacing scale
* Existing typography
* Existing border radius
* Existing shadows
* Existing status colors
* Existing animation patterns

Never introduce arbitrary colors, spacing values, or font sizes.

If a required design token is missing, mention it instead of inventing one.

---

# DESIGN PHILOSOPHY

The interface should feel:

* Professional
* Modern
* Clean
* Fast
* Consistent
* Easy to understand

Prioritize usability over visual effects.

Animations should support the experience rather than distract from it.

Do not force any specific visual style (Apple, Material, Glassmorphism, etc.) unless explicitly requested.

---

# RESPONSIVE DESIGN

Every page must be mobile-first.

Design for:

* Mobile
* Tablet
* Laptop
* Desktop

Use Tailwind breakpoints appropriately.

Avoid layouts that only work on large screens.

---

# ACCESSIBILITY

Every implementation should aim for WCAG AA.

Always check:

* Keyboard navigation
* Visible focus states
* Semantic HTML
* Form labels
* Accessible dialogs
* Accessible tables
* Color contrast
* Icons paired with text where necessary

Never rely on color alone to communicate status.

---

# PERFORMANCE

Prefer:

* Lazy loading where appropriate
* Pagination instead of rendering huge datasets
* Debounced search
* Optimized images
* Skeleton loading states
* Efficient DOM updates

Avoid unnecessary JavaScript and excessive animations.

---

# USER EXPERIENCE

Every feature should consider:

* Loading state
* Empty state
* Error state
* Success feedback
* Confirmation for destructive actions

Forms should provide clear validation and helpful error messages.

---

# CODE QUALITY

Follow:

* Consistent naming
* Reusable components
* Modular JavaScript
* Clear file organization
* Readable Tailwind classes
* Clean PHP templates

Avoid:

* Dead code
* Duplicate markup
* Large monolithic templates
* Inline styles
* Inline JavaScript event handlers

---

# IMPLEMENTATION STRATEGY

For every task:

1. Read the relevant PRD requirements.
2. Understand the existing implementation.
3. Identify reusable components.
4. Implement the smallest complete solution.
5. Preserve existing functionality.
6. Maintain visual consistency.
7. Review for accessibility.
8. Review for responsiveness.
9. Review for performance.
10. Deliver production-ready frontend code.

---

# BEFORE COMPLETING A TASK

Verify that:

* The implementation matches `PRD.md`.
* Existing functionality is preserved.
* The layout is responsive.
* Accessibility has been considered.
* Loading, empty, success, and error states exist where appropriate.
* Existing components have been reused whenever possible.
* Design tokens are respected.
* There are no unnecessary dependencies.
* There are no console errors.
* The code is clean, maintainable, and production-ready.

When multiple implementation options exist, prefer the one that is simpler, more reusable, easier to maintain, and most consistent with the existing ZyroInn codebase.

# SOURCE OF TRUTH (revise this section)

`PRD.md` is the authoritative requirements document. [Confirm this file exists at this exact
path/name in the repo — if your requirements doc has a different filename, update this line to
match it exactly, or the instruction is unusable.]

Design tokens live at `/database/DESIGN_TOKENS.md` — this is the only source for colors,
spacing, typography, and status colors. Never invent a value not defined there; flag it instead.

Confirmed folder structure (do not restructure without explicit approval):
/app/Controllers, /app/Models, /app/Services, /app/Views (+ /partials)
/config
/database/migrations, /database/seeds, /database/SCHEMA.md
/public/assets, /public/{guest,staff,owner,admin}/index.php

---

# BUILDING AHEAD OF BACKEND

Backend is owned by someone else and may lag behind frontend work. When a PRD-documented
feature has no backend implementation yet:

* Build the UI fully, using realistic mock data.
* Mark every mock data point clearly: `// MOCK DATA — pending backend, see [migration/table name]`
* Base the mock data shape on the actual proposed schema/migration if one exists — do not invent a different shape than what backend is expected to build against.
* Never silently ship mock data as if it were real — every mock state must be visually or functionally distinguishable in dev (e.g. a dev-only banner or console note) so it's never mistaken for working functionality.
* This does not override "never invent business rules" — mocking a documented future feature is fine; inventing a feature or rule not in the PRD is not.
