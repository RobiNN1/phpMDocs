# Links and Images

Relative links to other markdown pages lose the .md extension automatically, external links open in a new tab and local images are embedded as base64.

---

## Internal Links

Links to other pages can use the `.md` extension, it is removed automatically:

- [Text Formatting](text-formatting.md)
- [Code Blocks](code-blocks.md)
- [Installation guide](../getting-started/installation.md)

## External Links

External links open in a new tab automatically:

- [phpMDocs on GitHub](https://github.com/RobiNN1/phpMDocs)
- [Parsedown](https://parsedown.org)

## Local Image

A relative image is embedded directly into the page as base64:

![Sample image](img/sample.png)

## Missing Image

A missing local image keeps its original `src` untouched:

![Missing image](img/does-not-exist.png)

## External Image

![External badge](https://visitor-badge.laobi.icu/badge?page_id=RobiNN1.phpMDocs)
