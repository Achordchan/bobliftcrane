# French, Spanish, Russian and Arabic: translation plan

Target: every English page, product, product category and news post on the new site gets fr, es, ru and ar versions, under
/fr/, /es/, /ru/ and /ar/ with the English slug. Run on the Mac (local site + Codex CLI).

## Who does what

- **Codex CLI** writes the translations (bulk). Command:
  `codex exec --skip-git-repo-check -m group/auto-gpt-6-1-sol -s workspace-write -c model_reasoning_effort=low -C <dir> "<task>"`
- **DeepL** (free tier, about 500,000 characters a month, 5,000 per call) drafts what its quota covers, starting with the
  short, high-traffic items: page titles, SEO titles and descriptions, product names and specs, category names. Codex then
  reviews those drafts against the glossary instead of starting from scratch.
- **Claude** spot-checks the result at the end.

Size: the news export alone is about 400,000 English characters, so four languages are about 1.6 million. DeepL cannot cover
that; Codex carries the news bodies.

## Steps

1. **Glossary.** Add fr, es, ru and ar columns to `glossary.md` (Codex, one call). Keep product model codes (SQ10ZA5, CC165 …),
   units (t, kg, mm, m) and BOB-LIFT unchanged.
2. **Polylang.** Add French (fr), Spanish (es), Russian (ru) and Arabic (ar, right to left) on the local site, in directory
   mode, and translate the categories (news categories, product categories, tags used in the files).
3. **News.** Split the 80 posts in `news-en.json` into batches (about 10 posts per Codex call, one language per call, run
   in parallel). Output `translations/<lang>/<id>.json` with the same fields as the ja/zh files: title, excerpt, content,
   seo_title, seo_description, seo_keywords, tags. Import with
   `wp eval-file tools/i18n/import-news-translations.php i18n-export/translations langs=fr,es,ru,ar`.
4. **Pages, products, categories.** Export them the same way (one JSON per item, English source plus every SEO field and
   product spec field), translate with the same batching, and import the same way as the ja/zh pages were created.
5. **Checks before import** (script, every file): JSON parses, no empty field, the same number of links, images, headings,
   tables and list items as the English HTML, link targets unchanged, numbers and model codes unchanged, no English
   sentences left in the body. The importer's preflight repeats the field, link-count and category checks.
6. **After import.** Every fr/es/ru/ar address answers 200 locally (same check as the 347 ja/zh pages), Arabic pages render
   right to left, and `migrate-translation-slugs.php` reports nothing to rename.

## Rules for the translators

- Translate only what the English says; no new claims, numbers or certifications (never add classification-society
  certification for marine cranes).
- Keep HTML, attributes, URLs, image paths and shortcodes exactly as they are; translate alt text and visible text only.
- Natural, professional B2B tone for buyers of lifting equipment; local number formats are fine in prose, but specs keep
  the English figures and units.
