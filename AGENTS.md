# Agent Instructions

## Frontend Design System

Use **shadcn-vue as the primary UI component system** and **Tailwind CSS for styling** across the entire project. Build consistent, reusable, professional interfaces instead of creating new visual styles for every page.

### Stack facts (do not ignore)

- Laravel + Vue 3 + Inertia.js + Vite
- **Tailwind CSS v3** via PostCSS (`tailwind.config.js`, `resources/css/app.css`). Do **not** migrate to Tailwind 4 / `@tailwindcss/vite` unless explicitly asked.
- shadcn-vue CLI for this repo: local `shadcn-vue@0.11.4` (radix / Tailwind v3 tag). Prefer `./node_modules/.bin/shadcn-vue add <name> -y` or `npx shadcn-vue@0.11.4`. Do **not** use `shadcn-vue@latest` (targets Tailwind 4).
- Project `.npmrc` sets `legacy-peer-deps=true` so CLI dependency installs succeed.
- Primary primitives: `resources/js/Components/ui/*`
- Utils: `resources/js/Lib/utils.js` (`cn()`); import as `@/Lib/utils`
- Config: `components.json`
- App composition / domain chrome (keep): `PageHeader`, `SectionCard`, `DataTable`, `FilterBar`, `EmptyState`, `StatusBadge`, `FlashAlerts`, `Breadcrumbs`, `Pagination`, `AppSidebar`, `TaskKanban`, `ScheduleGantt`, `ImprovementWorkspace`
- Layouts: `AuthenticatedLayout`, `GuestLayout` — do not redesign unless explicitly asked
- Path alias: `@/` → `resources/js/`

### 1. Inspect Before Making Changes

Before implementing any UI:

- Inspect the existing Laravel, Vue 3, Inertia.js, Vite, and Tailwind CSS setup.
- Check installed versions and existing shadcn-vue configuration (`components.json`, `Components/ui/`).
- Inspect existing shared layouts, components, design tokens, and representative pages.
- Identify which shadcn-vue components are already installed.
- Preserve existing application behavior, business logic, routing, permissions, and API contracts.

Do not reinstall dependencies, upgrade packages, or modify configuration unnecessarily. Prefer composing existing components over inventing new styles.

### 2. Component Reuse Rules

- Prefer existing shadcn-vue components (`Components/ui/*`) before writing custom UI.
- Reuse existing project components (`PageHeader`, `DataTable`, etc.) before creating new ones.
- Use shadcn-vue for buttons, inputs, selects, checkboxes, dialogs, dropdowns, tabs, tooltips, badges, cards, and other supported UI elements.
- Use Tailwind utility classes for layout, spacing, sizing, alignment, responsive behavior, typography, borders, and colors.
- If a required component is missing, check the official shadcn-vue docs (radix / Tailwind v3), then add it with `./node_modules/.bin/shadcn-vue add <name> -y`.
- Extract repeated UI patterns into shared Vue components when that improves consistency.
- Do not create duplicate components that provide the same functionality.
- Do not mass-refactor existing pages to shadcn unless explicitly asked. New/changed UI should use `ui/` first.

### Compatibility mapping

| Existing (legacy) | Prefer going forward |
|-------------------|----------------------|
| `PrimaryButton` / `SecondaryButton` / `DangerButton` | `ui/button` variants |
| `TextInput` / `TextareaInput` / `SelectInput` / `Checkbox` | `ui/input`, `ui/textarea`, `ui/select`, `ui/checkbox` |
| `Modal` / `ConfirmModal` | `ui/dialog` |
| `Dropdown` / `DropdownLink` | `ui/dropdown-menu` |
| `AppTabs` | `ui/tabs` (or AppTabs wrapping tabs) |
| `StatusBadge` | keep for domain status; use `ui/badge` for generic chips |
| `SectionCard` | keep API; may wrap `ui/card` later |
| `DataTable` | keep; may compose `ui/table` internally later |
| `FlashAlerts` | keep; align with `ui/alert` when touched |
| `NavLink` / `ResponsiveNavLink` | Breeze leftovers; do not extend |

Do not delete legacy wrappers until a dedicated migration pass. When unclear, follow the nearest existing page’s patterns rather than introducing a new visual language.

### 3. Styling Rules

- Use Tailwind CSS utilities instead of custom CSS for ordinary layout and styling.
- Follow existing design tokens and theme variables in `resources/css/app.css` and `tailwind.config.js`.
- Use semantic theme colors (`primary`, `destructive`, `brand`, `ink`, `surface`, etc.) rather than hardcoding arbitrary hex values.
- Maintain consistent spacing, typography, border radius, borders, shadows, and component sizes.
- Prefer clean, minimalist, professional business interfaces.
- Use whitespace and visual hierarchy instead of excessive decoration.
- Avoid unnecessary gradients, glassmorphism, animations, shadows, and decorative colors.
- Do not use inline styles or arbitrary CSS values unless there is a clear technical or design requirement.
- Avoid `!important` to override component styles.
- Use component variants and Tailwind state/responsive utilities where appropriate.
- Preserve existing `app-*` / `hub-*` component classes while pages still depend on them.

### 4. Consistent UX Patterns

Apply consistently:

- Page headers: title, description, breadcrumbs, primary actions (`PageHeader`)
- Forms: labels, inputs, validation messages, help text, error states
- Tables: headers, row spacing, loading/empty states, filters, pagination
- Status indicators: consistent badge colors and meanings (`StatusBadge` / `ui/badge`)
- Dialogs: sizing, titles, descriptions, actions, destructive confirmation
- Buttons: primary, secondary, outline, ghost, destructive variants
- Feedback: loading, success, error, notifications
- Responsive layouts: usable on desktop, tablet, and mobile
- Accessibility: semantic HTML, keyboard navigation, visible focus, associated labels

Do not redesign an existing shared layout when modifying an individual page unless explicitly requested.

### 5. Vue and Laravel Implementation

- Use Vue 3 and existing Composition API conventions.
- Preserve Inertia.js navigation and form-handling patterns.
- Keep presentation separate from business logic where practical.
- Preserve existing props, events, validation behavior, permissions, and backend integration.
- Do not introduce React components or incompatible UI libraries.
- Do not replace working application functionality merely to match a visual reference.

### 6. Before Creating Custom Styles

1. Search for an existing project component.
2. Check whether an appropriate shadcn-vue component exists under `Components/ui/`.
3. Compose with Tailwind utilities and existing design tokens.
4. Extract a shared Vue component if the pattern is reused.
5. Write custom CSS only when the preceding options cannot reasonably satisfy the requirement.

### 7. Verification

After implementing a page:

- Check that the page uses the established component system.
- Check for duplicated styles and unnecessary custom CSS.
- Check responsive behavior and accessibility.
- Check browser console errors and build or test results when available.
- Confirm existing business functionality remains intact.
- Report modified files, newly added components, and any checks that could not be completed.

### 8. Expected Result

Every page should look and behave as part of one coherent application. Prefer composing existing components over inventing new styles.
