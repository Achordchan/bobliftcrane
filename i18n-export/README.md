# English news export for translation

`news-en.json`: all 80 published English posts (id, slug, date, title, excerpt, HTML content, categories, tags, SEO fields)
with `translations.ja` / `translations.zh` set to the existing translated post ID or null.
74 posts have no ja or zh version yet (the 4 older company-news posts and the two guide articles already have both).
Exported from the local site on 2026-10-16.

`translations/<ja|zh>/<id>.json`: Japanese and Simplified Chinese versions of those 74 posts (written by Codex from `glossary.md`, structure and links checked against the English HTML). Import with `wp eval-file tools/i18n/import-news-translations.php` (translations keep the English slug; Polylang directory mode, `inc/polylang-slugs.php`). Links to old category addresses that the English site only redirects stay unprefixed.
