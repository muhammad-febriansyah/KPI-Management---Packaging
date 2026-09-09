# KPI Management Co-Packing UI

Static HTML concept for the **Login** and **Admin Dashboard** pages.

## Pages

- `index.html` / `login.html` — responsive login page with password show/hide.
- `dashboard.html` — responsive admin dashboard with collapsible desktop sidebar and off-canvas mobile sidebar.

## Quick start

Open `index.html` directly in a browser. The UI is fully styled with the local `assets/css/app.css`, so it works without a build step.

For local development with a small HTTP server:

```bash
python3 -m http.server 8080
```

Then open `http://localhost:8080`.

## Tailwind CSS v4

The project includes a Tailwind v4 source entry point in `src/input.css` and pinned CLI packages in `package.json`.

```bash
npm install
npm run build
# or
npm run watch
```

The generated Tailwind output is written to `assets/css/tailwind.css`.

## Responsive behavior

- Desktop (`>= 1024px`): sidebar can collapse to icon-only mode.
- Tablet: sidebar becomes an off-canvas drawer; dashboard cards reduce columns.
- Mobile: single-column KPI layout, scrollable charts/tables, full-width actions, and compact topbar.
- Password field supports show/hide with an eye / eye-slash control.
- Sidebar state is remembered in `localStorage` on desktop.

## Laravel integration

This package is static HTML. To integrate into Laravel 13:

1. Move markup into Blade layouts/views.
2. Move `assets/` into `resources/` / Vite pipeline or `public/` as appropriate.
3. Replace demo form submission in `assets/js/app.js` with Laravel authentication routes.
4. Do **not** implement authentication in frontend JavaScript; backend validation, CSRF, rate limiting, session handling, policies, and tenant isolation remain server responsibilities.
5. Replace `#` links with named routes.

## Icons

The project follows the **Heroicons 24px outline** visual language requested for navigation and controls. A local reusable SVG sprite is included in `assets/icons/heroicons.svg`, and the same icon paths are rendered by `assets/js/app.js` so the icons also work when the HTML is opened directly via `file://`.

Heroicons: https://heroicons.com/

## Design tokens

- Primary: `#2547F9`
- Primary hover: `#1D37D8`
- Background: `#F7F9FC`
- Border: `#E5EAF2`
- Cards: white, thin border, very light shadow

The design intentionally avoids heavy gradients, nested cards, thick shadows, and excessive decoration.
