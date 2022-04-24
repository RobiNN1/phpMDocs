# PHP Documentation system

Simple but powerful Markdown docs.

## Features

- Search within Markdown files
- Customizable Twig templates (Note: default design is just plain Bootstrap)
- Automatically generated ToC and list of pages under category
- Cache for faster loading
- Sitemap and robots.txt generato, run to generate `composer sitemap`

## Installation

Simply extract the content to the root directory of the website and set a path to documentation content.

Or put it to do folder, in this case, need to update `site_path` option in config.php file.

Run `composer install` before use.

## Requirements

- PHP >= 8.1
- mod_rewrite or alternative
