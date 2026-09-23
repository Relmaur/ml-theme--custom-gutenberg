<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Integration\Blocks;

use WP_UnitTestCase;

/**
 * Frontend output of the Hero block (src/blocks/hero/render.php), rendered by
 * real WordPress. Attributes are editable by anyone who can edit a post, so a
 * big part of this is making sure hostile values come out harmless.
 *
 * @coversNothing render.php is a template, not a class.
 */
final class HeroRenderTest extends WP_UnitTestCase
{
    /**
     * @param array<string, mixed> $attributes
     */
    private function renderHero(array $attributes): string
    {
        return render_block([
            'blockName'    => 'my-theme/hero',
            'attrs'        => $attributes,
            'innerBlocks'  => [],
            'innerHTML'    => '',
            'innerContent' => [],
        ]);
    }

    public function testRendersTitleSubtitleAndImage(): void
    {
        $html = $this->renderHero([
            'title'    => 'Hello',
            'subtitle' => 'World',
            'imageUrl' => 'https://example.org/hero.jpg',
        ]);

        self::assertStringContainsString('<h1>Hello</h1>', $html);
        self::assertStringContainsString('<p class="subtitle">World</p>', $html);
        self::assertStringContainsString('<img src="https://example.org/hero.jpg" alt="Hello">', $html);
    }

    public function testWrapperGetsTheBlockClassAndColorSupportClasses(): void
    {
        $html = $this->renderHero(['title' => 'Hi', 'backgroundColor' => 'accent']);

        self::assertMatchesRegularExpression('/<section class="[^"]*\bwp-block-my-theme-hero\b[^"]*"/', $html);
        self::assertMatchesRegularExpression('/<section class="[^"]*\bhero-section\b[^"]*"/', $html);
        self::assertMatchesRegularExpression('/<section class="[^"]*\bhas-accent-background-color\b[^"]*"/', $html);
    }

    public function testUsesTheBlockJsonDefaultTitle(): void
    {
        self::assertStringContainsString('<h1>Welcome</h1>', $this->renderHero([]));
    }

    public function testOmitsEmptyParts(): void
    {
        $html = $this->renderHero(['title' => '', 'subtitle' => '', 'imageUrl' => '']);

        self::assertStringNotContainsString('<h1', $html);
        self::assertStringNotContainsString('class="subtitle"', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    public function testKeepsTheThemeRichTextFormats(): void
    {
        // The editor's font-weight and accent formats (formats.tsx) must survive
        // escaping, or they'd work in the editor but vanish on the frontend.
        $title = 'A <span class="text-weight" style="font-weight: 700" data-weight="700">bold</span>'
            . ' and <span class="text-accent">accent</span> title';

        $html = $this->renderHero(['title' => $title]);

        self::assertStringContainsString(
            '<span class="text-weight" style="font-weight: 700" data-weight="700">bold</span>',
            $html
        );
        self::assertStringContainsString('<span class="text-accent">accent</span>', $html);
    }

    public function testStripsScriptsAndEventHandlersFromText(): void
    {
        $html = $this->renderHero([
            'title'    => 'Hi<script>alert(1)</script><img src=x onerror=alert(2)>',
            'subtitle' => '<a href="javascript:alert(3)" onclick="alert(4)">link</a>',
        ]);

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('onerror', $html);
        self::assertStringNotContainsString('onclick', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testTheImageAltTextIsPlainText(): void
    {
        $html = $this->renderHero([
            'title'    => '<strong>Big</strong> "sale"',
            'imageUrl' => 'https://example.org/hero.jpg',
        ]);

        self::assertStringContainsString('alt="Big &quot;sale&quot;"', $html);
    }

    /**
     * @dataProvider unsafeImageUrls
     */
    public function testDoesNotRenderAnImageForAnUnsafeUrl(string $url): void
    {
        $html = $this->renderHero(['title' => 'Hi', 'imageUrl' => $url]);

        self::assertStringNotContainsString('<img', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public function unsafeImageUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'data'       => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
        ];
    }

    public function testIgnoresAttributesOfTheWrongType(): void
    {
        // e.g. saved through the REST API with arrays instead of strings.
        // WordPress validates types against block.json first (falling back to
        // the default title); render.php's is_string() checks are the backup.
        $html = $this->renderHero([
            'title'    => ['<script>'],
            'subtitle' => 42,
            'imageUrl' => ['https://example.org/x.jpg'],
        ]);

        self::assertStringContainsString('<h1>Welcome</h1>', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('class="subtitle"', $html);
        self::assertStringNotContainsString('<img', $html);
    }
}
