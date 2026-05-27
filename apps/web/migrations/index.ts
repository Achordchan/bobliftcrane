import * as migration_20260526_080417_initial_payload_schema from './20260526_080417_initial_payload_schema';
import * as migration_20260526_092449_article_editor_html_css_fields from './20260526_092449_article_editor_html_css_fields';
import * as migration_20260526_093027_remove_article_code_css_field from './20260526_093027_remove_article_code_css_field';

export const migrations = [
  {
    up: migration_20260526_080417_initial_payload_schema.up,
    down: migration_20260526_080417_initial_payload_schema.down,
    name: '20260526_080417_initial_payload_schema',
  },
  {
    up: migration_20260526_092449_article_editor_html_css_fields.up,
    down: migration_20260526_092449_article_editor_html_css_fields.down,
    name: '20260526_092449_article_editor_html_css_fields',
  },
  {
    up: migration_20260526_093027_remove_article_code_css_field.up,
    down: migration_20260526_093027_remove_article_code_css_field.down,
    name: '20260526_093027_remove_article_code_css_field'
  },
];
