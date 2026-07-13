<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use RobiNN\Pmd\ParsedownExt;
use RobiNN\Pmd\ParseMarkdown;

final class ParseMarkdownTest extends PmdTestCase {
    public function testParseFile(): void {
        $md = new ParseMarkdown('category_a/page-one');
        $content = $md->parse();

        $this->assertStringContainsString('<h1>Page One</h1>', $content);
        $this->assertSame('Page One', $md->getTitle());
        $this->assertSame('First page description.', $md->getDescription());
    }

    public function testParseRawText(): void {
        $md = new ParseMarkdown("# Raw Title\n\nJust **text**.");

        $this->assertStringContainsString('<strong>text</strong>', $md->parse());
        $this->assertSame('Raw Title', $md->getTitle());
    }

    public function testTitleFallbackWithoutParse(): void {
        // Without parse() the title is read from the first line.
        $md = new ParseMarkdown('category_a/page-one');

        $this->assertSame('Page One', $md->getTitle());
    }

    public function testCategoryReadmeFallback(): void {
        // A category path falls back to its README.md file.
        $md = new ParseMarkdown('category_a');

        $this->assertSame('Category A', $md->getTitle());
        $this->assertSame('Pages about testing.', $md->getDescription());
    }

    public function testDefaultDescription(): void {
        $md = new ParseMarkdown('# Only Title');

        $this->assertSame('Default site description.', $md->getDescription());
    }

    public function testDescriptionIsTruncatedAtWordBoundary(): void {
        // Raw text instead of a fixture file, so that an editor cannot re-wrap the long line.
        $long_line = trim(str_repeat('slovíčko ', 40)); // 359 chars with multibyte characters

        $md = new ParseMarkdown("# Title\n\n".$long_line);
        $description = $md->getDescription();

        $this->assertLessThanOrEqual(158, mb_strlen($description));
        // Truncated at a word boundary, multibyte characters are never cut in half.
        $this->assertStringEndsWith('slovíčko...', $description);
        $this->assertSame($description, (string) mb_convert_encoding($description, 'UTF-8', 'UTF-8'));
    }

    public function testHeadingsAndAnchors(): void {
        $md = new ParseMarkdown('category_a/page-one');
        $content = $md->parse();

        $this->assertSame([
            ['title' => 'First Heading', 'id' => 'first-heading'],
            ['title' => 'Second Heading', 'id' => 'second-heading'],
        ], $md->getHeadings());

        $this->assertStringContainsString('<h2 id="first-heading"><a href="#first-heading">First Heading</a></h2>', $content);
    }

    public function testLinks(): void {
        $content = new ParseMarkdown('category_a/page-one')->parse();

        $this->assertStringContainsString('href="page-two"', $content); // .md removed
        $this->assertStringContainsString('href="https://example.com" target="_blank"', $content);
    }

    public function testFencedCodeWithCustomClass(): void {
        $content = new ParseMarkdown('category_a/page-one')->parse();

        $this->assertStringContainsString('<pre class="special">', $content);
        $this->assertStringContainsString('class="language-php"', $content);
    }

    public function testTableClass(): void {
        $content = new ParseMarkdown('category_a/page-one')->parse();

        $this->assertStringContainsString('<table class="table-responsive">', $content);
    }

    public function testImages(): void {
        $content = new ParseMarkdown('category_a/page-two')->parse();

        // A local image is embedded as base64, a missing one keeps its original src.
        $this->assertStringContainsString('src="data:image/png;base64,', $content);
        $this->assertStringContainsString('src="img/none.png"', $content);
        $this->assertStringContainsString('class="img-fluid"', $content);
    }

    public function testCreateIdFromTitle(): void {
        $parsedown = new ParsedownExt();

        $this->assertSame('simple-title', $parsedown->createIdFromTitle('Simple Title'));
        $this->assertSame('diacritics-test', $parsedown->createIdFromTitle('Diacritics — test'));
        $this->assertSame('a-b-c', $parsedown->createIdFromTitle('a   b?!c'));
    }
}
