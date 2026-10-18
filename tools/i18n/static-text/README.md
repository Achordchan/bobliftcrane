# Built-in UI string translations (es, ru, fr, ar)

One transport package per language for the `static_text` option section: about 645 menu labels, buttons and fixed page texts that the theme's code dictionaries only translate for ja and zh. Each entry is keyed by the first 12 hex characters of the MD5 of its English original (`boblift_static_text_key()`).

Import each file on the target site through the multilingual plugin's transport, options only:

```
POST /wp-json/boblift-mls/v1/import?only=options&overwrite=true
Body: contents of static-text-<lang>.json
```

Run it with `dry_run=true` first to see the report. A package writes only `static_text` for its language and leaves the other option sections untouched. Later edits are made in the backend or through the same import, not in these files.
