<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("sizes a simple page's card to its max width ahead of its own content width and the panel's, a token string read as its token", function (): void {
    expect($this->visit('/simple-knobs/token-width-simple-page')->filter(Selectors::MAIN)->attr('class'))->toBe('sy-pa-simple-main sy-width-md');
});
