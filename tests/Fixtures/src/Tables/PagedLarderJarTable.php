<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

/**
 * Larder jars by label a user pages through, reorderable by position, its page bound to the
 * URL as `page`. Its page options, its default page option and its pagination mode (a
 * `PaginationMode` case's name) are mount props, and so is a poll interval; each variant
 * configures one more setting: `unpaginated`, `shortcut` (`paginated([5, 10])` after
 * `paginated(false)`), `extremeLinks`, `modeByClosure` (the mode handed over in a closure),
 * `whileReordering` (`paginatedWhileReordering()`), `deferred` (`deferLoading()`),
 * `pollByDefault` (`poll()` with no interval) and `actions` (an `open` record action and
 * an `archive` bulk action, so its rows carry actions and can be selected).
 */
#[AsLiveComponent(name: 'Tables:PagedLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class PagedLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var list<int|string>|null
     */
    #[LiveProp]
    public ?array $options = null;

    #[LiveProp]
    public ?int $defaultOption = null;

    #[LiveProp]
    public ?string $mode = null;

    #[LiveProp]
    public ?string $poll = null;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->defaultSort('label')
            ->reorderable('position')
            ->paginationPageOptions($this->options)
            ->defaultPaginationPageOption($this->defaultOption)
            ->extremePaginationLinks($this->has('extremeLinks'))
            ->paginatedWhileReordering($this->has('whileReordering'))
            ->deferLoading($this->has('deferred'))
            ->recordActions($this->has('actions') ? [Action::make('open')] : [])
            ->toolbarActions($this->has('actions') ? [BulkAction::make('archive')] : []);

        if ($this->mode !== null) {
            $mode = match ($this->mode) {
                'Simple' => PaginationMode::Simple,
                'Cursor' => PaginationMode::Cursor,
                default => PaginationMode::Default,
            };

            $table->paginationMode($this->has('modeByClosure') ? static fn (): PaginationMode => $mode : $mode);
        }

        if ($this->has('unpaginated')) {
            $table->paginated(false);
        }

        if ($this->has('shortcut')) {
            $table->paginated(false)->paginated([5, 10]);
        }

        if ($this->poll !== null) {
            $table->poll($this->poll);
        }

        if ($this->has('pollByDefault')) {
            $table->poll();
        }

        return $table;
    }

    protected function getTableUrlMapping(string $prop): ?UrlMapping
    {
        return $prop === 'page' ? new UrlMapping(as: 'page') : null;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
