<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("sizes each shell's content to its own knob, the simple shell's from a closure's answer", function (): void {
    expect($this->visit('/simple-knobs/notes-page')->filter(Selectors::contentWidth('6xl')))->toHaveCount(1)
        ->and($this->visit('/simple-knobs/card-simple-page')->filter(Selectors::contentWidth('2xl')))->toHaveCount(1);
});

it('sizes the simple shell narrower than the ordinary one when the panel sets neither knob', function (): void {
    expect($this->visit('/simple-shapes/card-simple-page')->filter(Selectors::contentWidth('lg')))->toHaveCount(1)
        ->and($this->visit('/simple-shapes/notes-page')->filter(Selectors::contentWidth('7xl')))->toHaveCount(1);
});
