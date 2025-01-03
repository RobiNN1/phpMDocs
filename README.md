# phpMDocs

Simple but powerful Markdown documentation.

![Visitor Badge](https://visitor-badge.laobi.icu/badge?page_id=RobiNN1.phpMDocs)

## Features

- Search within Markdown files
- Customizable Twig templates
- Automatically generated ToC and sidebar content
- Cache for faster loading
- Sitemap and robots.txt generator, run to generate `composer sitemap`

## Installation

Extract the content to the root directory of the website.
Or put it to subfolder, in this case, need to update `site_path` option in config.php file.

If necessary, set the path to the documentation content (`docs_path` option).

Run `composer install` before use.

## Requirements

- PHP >= 8.2
- mod_rewrite or alternative
