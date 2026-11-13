<?php

declare(strict_types=1);

use Symfony\Component\DomCrawler\Crawler;

expect()->extend('toContainElement', function (string $selector, ?string $text = null): object {
    if (!$this->value instanceof Crawler) {
        throw new InvalidArgumentException('toContainElement() reads a rendered Crawler.');
    }

    $texts = $this->value->filter($selector)->each(static fn (Crawler $element): string => $element->text());

    if ($text === null) {
        expect($texts)->not->toBeEmpty("No element matches [{$selector}].");
    } else {
        expect(in_array($text, $texts, true))->toBeTrue(sprintf('No element matching [%s] reads [%s]; they read %s.', $selector, $text, json_encode($texts, JSON_UNESCAPED_UNICODE)));
    }

    return $this;
});

expect()->extend('toContainButtons', function (string $selector, array $names): object {
    if (!$this->value instanceof Crawler) {
        throw new InvalidArgumentException('toContainButtons() reads a rendered Crawler.');
    }

    $accessibleNames = $this->value->filter($selector)->each(
        static fn (Crawler $button): string => mb_trim($button->attr('aria-label') ?? '') ?: $button->text(),
    );

    expect($accessibleNames)->toBe($names, "The buttons matching [{$selector}] are not named, in order, as expected.");

    return $this;
});
