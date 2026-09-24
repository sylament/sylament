<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class EditPortfolio extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    /**
     * The sheet item keys the form held when `afterSave` ran — a `LiveProp`, so
     * the save's own response carries them back to the test.
     *
     * @var list<int|string>
     */
    #[LiveProp]
    public array $sheetKeysAfterSave = [];

    protected function afterSave(): void
    {
        $sheets = $this->getSchema('form')->getRawState()['sheets'] ?? [];

        $this->sheetKeysAfterSave = is_array($sheets) ? array_keys($sheets) : [];
    }
}
