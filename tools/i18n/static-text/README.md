# Built-in UI string translations (es, ru, fr, ar)

One transport package per language for the `static_text` option section: about 645 menu labels, buttons and fixed page texts that the theme's code dictionaries only translate for ja and zh. Each entry is keyed by the first 12 hex characters of the MD5 of its English original (`boblift_static_text_key()`).

Import each file on the target site through the multilingual plugin's transport, options only:

```
POST /wp-json/boblift-mls/v1/import?only=options&overwrite=true
Body: contents of static-text-<lang>.json
```

A package writes only `static_text` for its language and leaves the other option sections untouched, but it replaces that whole section: entries not in the package are removed and existing texts are overwritten. These packages are meant for the first import into a language that has no `static_text` yet.

If the language already has `static_text` entries (edited in the backend or imported before), export it first with `GET /wp-json/boblift-mls/v1/export/<lang>`. Merge the exported `static_text.strings` into the package, keeping the entries you want, and import the merged file. `dry_run=true` only reports that the section would be updated; it does not list the entries that would change. Later edits are made in the backend or through the same export, merge and import, not in these files.
