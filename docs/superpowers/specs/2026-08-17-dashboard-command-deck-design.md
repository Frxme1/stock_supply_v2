# Stock Supply Dashboard Command Deck

## Context

Stock Supply is a WordPress/Astra inventory tool used mainly on mobile while staff are moving around the workplace. The current `/home/` dashboard is data-heavy: category cards, status cards, live stock monitoring, charts, and recent devices. Navigation already exists in the sidebar and mobile bottom bar, but important actions are spread across those surfaces.

## Goal

Make the first dashboard viewport a practical launchpad for important work. A user should reach the most common menu in one tap, understand the current stock state quickly, and never feel trapped in a decorative analytics screen.

## Recommended direction: Command deck

Keep the existing inventory metrics and live monitor, but put a compact quick-menu layer first.

### Mobile order

1. Page title and short context line.
2. `Quick actions` grid with large, thumb-friendly menu cards:
   - Scan QR
   - Add Device
   - Maintenance
   - Employees
   - History
   - All Devices
3. `Inventory` category links:
   - Monitor
   - Laptop
   - Accessories
4. Existing status summary, converted to compact cards that remain useful below the fold.
5. Existing live monitor, charts, and recent-device content.

The quick-menu cards use existing routes only. Scan QR continues to use the existing `/?scan=1` entry point. Maintenance can show the existing maintenance badge count. No new database query or API is required for this visual pass.

### Desktop order

Use a four-column quick-menu row followed by the existing category and status summaries. Preserve the desktop sidebar as the persistent navigation; dashboard cards act as task shortcuts, not a second full sidebar.

## Visual system

- Product character: calm operations tool, closer to a well-made field instrument than a generic SaaS landing page.
- Surface: cool near-white `#f6f8fb`; cards use white with thin slate borders.
- Text: deep slate `#0f172a`, secondary slate `#64748b`.
- Primary action: indigo `#4f46e5`, used sparingly for focus and the Scan QR action.
- Semantic accents: teal for available/healthy, amber for maintenance, red only for urgent states.
- Typography: existing Inter stack, 16px minimum body text, clear numeric hierarchy.
- Shape: 16–18px card radius, 12px icon tile radius, restrained shadows.
- Icons: existing Font Awesome line-style icons; no emoji or decorative icon mixing.
- Motion: 180–240ms transitions, small lift/press feedback only, disabled or reduced under `prefers-reduced-motion`.

## Interaction and accessibility

- Render menu cards as real links, not clickable `div` elements.
- Minimum touch target: 44px; keep at least 8px between targets.
- Add visible `:focus-visible` treatment and useful accessible names.
- Show badge counts as text, not color alone.
- Avoid horizontal overflow at 320px–430px widths.
- Keep the mobile bottom navigation usable and prevent card content from hiding behind it.

## Implementation scope

- Primary template: `wp-content/themes/astra-child/model/dashboard/device_dashboard.php`.
- Shared visual rules: `wp-content/themes/astra-child/css/dashboard_cards.css`.
- Add page-scoped classes so existing Monitor, Laptop, Accessories, Employee, and Maintenance dashboards do not inherit unintended layout changes.
- Do not change inventory queries, routes, status semantics, or sidebar behavior in this pass.

## Validation

- PHP lint changed PHP files.
- Browser check at 320px, 390px, 430px, and desktop width.
- Verify no horizontal scroll, clipped cards, hidden bottom-nav content, or broken links.
- Verify keyboard focus, reduced-motion behavior, and contrast for all menu states.
- Click-test every quick-menu card and confirm route targets.

## Success criteria

- Important menu visible in first mobile viewport.
- Common action reachable in one tap.
- Dashboard feels operational and intentional, not like a generic AI-generated card wall.
- Existing inventory information remains available without backend changes.
