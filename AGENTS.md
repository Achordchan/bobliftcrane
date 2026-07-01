# Repository Guidelines

## Project Structure & Module Organization

This repository is a WordPress classic theme for BOB-LIFT. Templates live at the repository root, for example `front-page.php`, `page-news.php`, `single-product.php`, and `single-case.php`. Shared helpers, post types, import logic, and seed data live in `inc/`. Front-end assets live in `assets/`: source CSS is `assets/css/input.css`, generated CSS is `assets/css/theme.css`, JavaScript is in `assets/js/`, and media assets are in `assets/images/` and `assets/videos/`. The local runnable WordPress copy is under `.wordpress/`; after editing source theme files, sync matching files into `.wordpress/wp-content/themes/bobliftcrane/`. Use `backups/` only for explicit rollback snapshots.

## Build, Test, and Development Commands

- `npm run build:css` — builds minified Tailwind CSS from `assets/css/input.css` to `assets/css/theme.css`.
- `npm run watch:css` — watches Tailwind classes during active UI work.
- `npm run start:wp` — starts local WordPress at `http://localhost:8080` using `.wordpress` as the document root.
- `php -l path/to/file.php` — checks PHP syntax for edited templates and helpers.
- `node --check assets/js/theme.js` — checks JavaScript syntax after script changes.
- `curl -I http://localhost:8080/page.html` — confirms a page still returns HTTP 200.

## Coding Style & Naming Conventions

Use 4-space indentation in PHP and 2-space indentation in JavaScript/CSS where existing files do. Escape output with WordPress helpers such as `esc_html()`, `esc_attr()`, and `esc_url()`. Prefix project-specific PHP functions and scoped CSS classes with `boblift_` or `boblift-`. Prefer existing Tailwind utilities and scoped component classes over global CSS changes. Do not add production dependencies unless necessary.

## Testing Guidelines

There is no full test suite. For every change, run syntax checks on edited PHP files in both the source theme and `.wordpress` theme copy. Run `npm run build:css` after Tailwind or CSS changes, and run `node --check` after JavaScript changes. Verify affected URLs locally and inspect responsive layouts, especially 360px, 393px, desktop, and wide screens.

## Commit & Pull Request Guidelines

No commit convention is established in history. Use concise imperative commit messages, for example `Fix news category filtering` or `Update public welfare hero layout`. Pull requests should include a short summary, affected URLs/templates, validation commands, screenshots for UI changes, and notes about any files synced into `.wordpress/wp-content/themes/bobliftcrane/`.

## Security & Configuration Tips

Do not commit credentials, WordPress secrets, debug output, or temporary test data. Before delivery, search changed files for `console.log`, `debugger`, `mock`, `fake`, and `demo` leftovers. Preserve existing URLs, WordPress routes, and admin-editable data unless the task explicitly asks to change them.
