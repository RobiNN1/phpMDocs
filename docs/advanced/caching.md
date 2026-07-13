# Caching

Rendered pages, tables of contents and page lists are cached, so markdown files are parsed only once per hour, changes show up after the cache expires or is flushed.

---

## How It Works

Every rendered page is stored as a single cache entry containing the HTML,
title, description and table of contents. The whole entry is built in one pass
and expires after the configured `expiration` time.

## What Is Cached

| Entry               | Contents                          |
|---------------------|-----------------------------------|
| `page_*`            | HTML, title, description, TOC     |
| `category_*`        | Category page content             |
| `get_pages*`        | Page lists for sidebars           |
| `search_all_pages`  | Search index with headings        |

## Flushing the Cache

Delete the cache files manually:

```bash
rm cache/data/*.cache
```

Or use [phpCacheAdmin](https://github.com/RobiNN1/phpCacheAdmin) with the
FileCache dashboard to browse and delete individual keys — since the keys
are stored inside the files, you will see the original key names even though
the file names are hashed with a secret.
