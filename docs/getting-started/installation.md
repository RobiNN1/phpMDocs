# Installation

Learn how to install phpMDocs and prepare the environment for your documentation site.

---

## Requirements

Before installing, make sure your server meets these requirements:

- PHP 8.4 or higher
- Composer
- Apache with `mod_rewrite` (or an equivalent Nginx config)

## Install via Composer

Clone the repository and install dependencies:

```bash
git clone https://github.com/RobiNN1/phpMDocs.git
cd phpMDocs
composer install
```

## Directory Structure

After installation you should see the following structure:

```
phpMDocs/
├── assets/     # CSS and JS files
├── cache/      # Twig and data cache
├── docs/       # Your markdown documentation
├── src/        # Application source code
└── templates/  # Twig templates
```

## First Run

Point your web server to the project root and open the site in a browser.
You should see the homepage with a list of categories from the `docs/` directory.

### Troubleshooting

If you see a blank page, check that the `cache/` directory is writable:

```bash
chmod -R 775 cache
```

### Next Steps

Continue with the [configuration](configuration.md) page to customize your site.
