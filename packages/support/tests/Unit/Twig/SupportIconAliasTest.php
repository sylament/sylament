<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig;

use Sylament\Support\Twig\SupportIconAlias;

it('pins the alias literal the Section template hardcodes', function (): void {
    expect(SupportIconAlias::SECTION_COLLAPSE_BUTTON)->toBe('section.collapse-button');
});
