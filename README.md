# PHP Documentation system

Simple but powerful Markdown docs.

![Visitor Badge](https://visitor-badge.laobi.icu/badge?page_id=RobiNN1.Markdown-Docs)

## Features

- Search within Markdown files
- Customizable Twig templates
- Automatically generated ToC and sidebar content
- Cache for faster loading
- Sitemap and robots.txt generator, run to generate `composer sitemap`

## Installation

Simply extract the content to the root directory of the website.
Or put it to sub folder, in this case, need to update `site_path` option in config.php file.

If necessary, set the path to the documentation content (`docs_path` option).

Run `composer install` before use.

## Requirements

- PHP >= 8.1
- mod_rewrite or alternative

## Development

For compiling Tailwind CSS run `npm install` and then
`npm run build` or `npm run watch` for auto-compiling.
