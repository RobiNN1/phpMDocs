# Configuration

All configuration options live in a single file, this page describes every available option and its default value so you can tune the site exactly to your needs.

---

## Basic Options

Open `config.php` in the project root. The most important options:

| Option             | Type   | Description                              |
|--------------------|--------|------------------------------------------|
| `site_title`       | string | Displayed on the homepage and meta tags  |
| `site_description` | string | Default meta description                 |
| `site_path`        | string | Subdirectory path, e.g. `/docs/`         |
| `docs_path`        | string | Path to the markdown files               |
| `category_page`    | bool   | Show category page instead of redirect   |

## Cache

Caching is enabled by default and uses the [robinn/cache](https://github.com/RobiNN1/Cache) package:

```php
'cache' => [
    'enabled'    => true,
    'expiration' => 3600, // 1h default
    'storage'    => 'file',
    'file'       => ['path' => __DIR__.'/cache/data', 'secret' => 'phpmdocs_cache'],
],
```

> **Tip:** During local development you can set `enabled` to `false`
> so that content changes show up immediately.

## Navigation Links

Add custom links to the top navigation:

```php
'nav_links' => [
    ['link' => '{site_url}', 'title' => 'Home'],
    ['link' => '/getting-started', 'title' => 'Docs'],
],
```

## Reordering Items

Categories on the homepage can be reordered with `reorder_items`.
Items that are not listed will not be displayed:

```php
'reorder_items' => [
    'home' => ['getting-started', 'markdown-syntax'],
],
```
