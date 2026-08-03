# Contao Wrapper Tags

Content elements for building semantic HTML structures inside Contao articles without custom PHP templates.

Version 5.7 targets Contao 5.7 and provides three Twig-based content elements:

- `wrapper_tag_start` for one or more opening tags
- `wrapper_tag_stop` for one or more closing tags
- `wrapper_tag_complete` for complete or void tags

## Requirements

- PHP 8.3 or newer
- Contao 5.7
- MultiColumnWizard 3.6 or newer

## Installation

```bash
composer require dvc/contao-wrapper_tags:^5.7
php vendor/bin/contao-console contao:migrate --no-interaction
php vendor/bin/contao-console cache:clear
php vendor/bin/contao-console assets:install
```

Review the migration output first on production systems. Database deletes are not required by this bundle; do not add `--with-deletes` for this installation.

## Upgrade from 1.0.x

The included migration maps the legacy content element types automatically:

| Legacy type | Contao 5.7 type |
| --- | --- |
| `wt_opening_tags` | `wrapper_tag_start` |
| `wt_closing_tags` | `wrapper_tag_stop` |
| `wt_complete_tags` | `wrapper_tag_complete` |

The existing fields `wt_opening_tags`, `wt_closing_tags`, and `wt_complete_tags` remain unchanged. Existing content is therefore retained.

## Features

- Configurable list of allowed HTML tags
- Multiple HTML attributes and CSS classes per tag
- Insert tags in attribute names and values
- Backend validation for mismatched opening and closing tags
- Colored nesting and indentation in the backend article view
- Twig frontend and editor-view templates
- Migration of legacy content element type names and CSS classes

## Tests

After installing the package in a Contao project, run:

```bash
php bundles/contao-wrapper_tags/tests/run.php
php bundles/contao-wrapper_tags/tests/render.php
php vendor/bin/contao-console lint:container
php vendor/bin/contao-console lint:twig
```

## License

LGPL-3.0-or-later
