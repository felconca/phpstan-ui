# phpstan-ui

A local browser UI for PHPStan analysis results. No PHPStan Pro needed.

## Installation

```bash
composer require --dev felconca/phpstan-ui
```

Or for local development, add to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../phpstan-ui"
    }
  ],
  "require-dev": {
    "felconca/phpstan-ui": "*"
  }
}
```

## Usage

Run PHPStan with the `ui` error formatter:

```bash
vendor/bin/phpstan analyse src/ --error-format=ui
vendor/bin/phpstan analyse src/ --error-format=ui --level=5
vendor/bin/phpstan analyse src/MyFile.php --error-format=ui
```

PHPStan runs as normal, then a local PHP server starts at `http://localhost:8742`
and your browser opens automatically.

Press `Ctrl+C` in the terminal to stop the server when you're done.

## Features

- File tree sidebar with error counts
- Search across files and error messages
- Filter: All / Errors only / Ignorable only
- Sort: by error count or filename
- Expandable file blocks with line numbers
- Identifier tags, tip blocks, ignorable badges
- Zero-dependency — uses PHP built-in server
- Works on Windows, macOS, and Linux

## Custom Port

To change the default port (8742), override the service in your `phpstan.neon`:

```neon
services:
    -
        class: PhpstanUi\UiErrorFormatter
        arguments:
            port: 9000
            host: localhost
        tags:
            - phpstan.errorFormatter
```

## Requirements

- PHP >= 5.6
- PHPStan ^1.0
- Composer
