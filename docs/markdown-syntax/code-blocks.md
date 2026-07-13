# Code Blocks

Fenced code blocks with syntax highlighting via highlight.js, including the custom class extension for the pre tag.

---

## PHP

```php
<?php
declare(strict_types=1);

$cache = new RobiNN\Cache\Cache([
    'storage' => 'file',
    'file'    => ['path' => __DIR__.'/cache'],
]);

$data = $cache->remember('expensive', static fn (): array => heavyComputation(), 3600);
```

## JavaScript

```js
document.querySelectorAll('h2[id]').forEach((heading) => {
    heading.addEventListener('click', () => {
        navigator.clipboard.writeText(location.origin + location.pathname + '#' + heading.id);
    });
});
```

## Custom Class

A code block can add a custom class to the `<pre>` tag:

```php {.custom-class}
echo 'This <pre> tag has the .custom-class CSS class.';
```

## Plain Text

```
No language specified, rendered without highlighting.
```
