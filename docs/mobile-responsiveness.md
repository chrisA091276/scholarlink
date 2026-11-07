# Mobile layouts

The shared `public/css/responsive.css` stylesheet loads after page styles in the application, authentication, guest, and landing layouts. These are directly served assets; this change does not require a Vite build.

- Below 992px, the workspace sidebar becomes a navigation drawer. It supports Escape, backdrop and close-button dismissal, keyboard focus containment, background scroll locking, and reset when switching back to desktop.
- Desktop uses a 252px sidebar and a flexible content column. Phones use smaller page gutters and wrapping headers.
- Filters stack on phones, controls have larger touch targets, and text inputs use 16px text to avoid focus zoom on iOS.
- Wide staff and administrator tables scroll within their containers. The existing student tables retain their card presentation on phones.
- Dashboard application tables for Student, Staff, and Administrator become labelled cards below 768px. Dates, status, document checklists, and full-width application actions remain visible; table roles and column headers remain available to assistive technology.
- Dashboard summary cards use two columns on phones, with wrapping labels and larger action targets. From 992px to 1279px, dashboard content uses one main column to allow room for the desktop sidebar.
- The landing page has a collapsible section/account menu below 1400px. Escape restores focus to its toggle; section links close the menu and move to the selected heading. The hero, buttons, poster, and informational cards adapt to phone widths.
- The seven-step application, form summaries, landing navigation, and authentication footers adapt to narrow screens.

Navigation behavior lives in `public/js/navigation.js`. Its media query must stay aligned with the 992px CSS breakpoint. PDF templates use their own styles.

Landing navigation behavior lives in `public/js/landing-navigation.js`; its 1400px breakpoint matches Bootstrap's `navbar-expand-xxl` and the shared stylesheet.

## Verification

The browser script uses fictional accounts in the existing isolated preview database. It does not use the configured runtime database or submit scholarship changes.

```powershell
# Prepare the isolated fixtures if they are not already present.
php tools/prepare-professional-preview.php

# Keep this local server running in a separate terminal.
php -S 127.0.0.1:8017 -t public tools/professional-preview-router.php

# Full page coverage at 320, 390, 768, 1024, and 1440px.
node --experimental-websocket tools/preview-responsive.cjs

# Navigation keyboard/resize checks and all seven application steps,
# including the 991/992px drawer boundary.
node --experimental-websocket tools/preview-responsive.cjs --interactions

# Landing page and all three dashboards, including mobile menus, record cards,
# expanded checklists, long names, breakpoint boundaries, and 844 x 390 landscape.
node tools/preview-responsive.cjs --dashboards

php artisan test --no-coverage
npm.cmd test
```

The browser script uses the installed Chrome at `C:/Program Files/Google/Chrome/Application/chrome.exe`. JSON reports and screenshots are written to `storage/app/responsive-preview/`, which is ignored by Git. These checks emulate viewports in Chrome; they do not replace physical Safari/Android device testing.

The earlier November 6, 2026 verification covered 112 PHP tests (802 assertions), three frontend tests, and 344 distinct successful page/viewport checks across 320–1440px. All seven application steps and drawer keyboard, dismissal, and resize behavior passed. The initial browser sweep exposed two outdated URLs in the test script; their corrected routes passed in the follow-up run. `verified-summary.json` records that earlier sweep.

For the landing/dashboard update on November 6, 2026, the focused browser sweep passed 52 page/viewport checks: all four pages at 320, 375, 390, 576, 768, 844, 991, 992, 1024, 1280, 1399, 1400, and 1440px, including 844 × 390 landscape. `dashboard-report.json` records zero page overflow, JavaScript errors, or failed HTTP responses. Checks include landing menu dismissal/anchors/resize, all three sidebar focus traps, card actions and dates, expanded evidence, and long record names. Seven relevant PHP tests (37 assertions), the three frontend tests, and JavaScript syntax checks also passed. Phone and desktop screenshots are saved alongside the report.

The final student heading adjustment also passed all 13 viewport checks using `--dashboards --role=student`; results are in `dashboard-student-report.json`.
