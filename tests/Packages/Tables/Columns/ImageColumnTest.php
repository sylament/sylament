<?php

declare(strict_types=1);

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use Sylament\Support\Enums\TextSize;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Tables\Columns\ImageColumn;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ExistenceBlindAdapter;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

describe('its sources', function (): void {
    it('draws a url or data state as it is and a stored path by the url its storage mints, falling to the default image for a path it cannot find or a url it refuses', function (string $state, string $source, string $sourceWithDefault): void {
        $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage')->write('lamp.png', 'Lamp');
        $src = fn (ImageColumn $column): ?string => $this->renderViewComponent($column->record(['photo' => $state]))->filter('img')->attr('src');

        expect($src(ImageColumn::make('photo')))->toBe($source)
            ->and($src(ImageColumn::make('photo')->defaultImageUrl('https://img.example.com/default.png')))->toBe($sourceWithDefault);
    })->with([
        'an absolute url' => ['https://img.example.com/lamp.png', 'https://img.example.com/lamp.png', 'https://img.example.com/lamp.png'],
        'a data uri' => ['data:image/png;base64,iVBORw0KGgo=', 'data:image/png;base64,iVBORw0KGgo=', 'data:image/png;base64,iVBORw0KGgo='],
        'a stored path' => ['lamp.png', '/files/lamp.png', '/files/lamp.png'],
        'a path the storage lacks' => ['missing.png', '', 'https://img.example.com/default.png'],
        'a javascript url' => ['javascript://img.example.com/lamp.png', '', 'https://img.example.com/default.png'],
    ]);

    it('reads a stored path from the storage disk() names, the default storage otherwise, and refuses a storage nobody registered', function (): void {
        $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage')->write('lamp.png', 'Lamp');
        $source = fn (ImageColumn $column): ?string => $this->renderViewComponent($column->record(['photo' => 'lamp.png']))->filter('img')->attr('src');

        expect($source(ImageColumn::make('photo')))->toBe('/files/lamp.png')
            ->and($source(ImageColumn::make('photo')->disk('secondary.storage')))->toBe('')
            ->and(fn (): ?string => ImageColumn::make('photo')->disk('missing.storage')->getImageUrl('lamp.png'))
            ->toThrow(LogicException::class, 'No flysystem storage is registered as "missing.storage"');
    });

    it('falls to the default image for a stored file whose existence its storage cannot check, and an empty source without one, until told not to check', function (): void {
        $storage = new Filesystem(new ExistenceBlindAdapter(), ['public_url' => '/files']);
        $storage->write('lamp.png', 'Lamp');

        $registry = new FilesystemRegistry(new ServiceLocator(['blind.storage' => static fn (): FilesystemOperator => $storage]), defaultDiskName: 'blind.storage');
        $source = function (ImageColumn $column) use ($registry): ?string {
            $column->setFilesystemRegistry($registry);

            return $this->renderViewComponent($column->record(['photo' => 'lamp.png']))->filter('img')->attr('src');
        };

        expect($source(ImageColumn::make('photo')))->toBe('')
            ->and($source(ImageColumn::make('photo')->defaultImageUrl('https://img.example.com/default.png')))->toBe('https://img.example.com/default.png')
            ->and($source(ImageColumn::make('photo')->checkFileExistence(false)))->toBe('/files/lamp.png');
    });

    it('draws a url state without a storage when built with new, and refuses a path state naming the column', function (): void {
        $column = new ImageColumn()->name('photo');

        expect($column->getImageUrl('https://img.example.com/lamp.png'))->toBe('https://img.example.com/lamp.png')
            ->and(fn (): ?string => $column->getImageUrl('lamp.png'))
            ->toThrow(LogicException::class, 'The [photo] column was built with `new` and cannot resolve a storage disk; register it as a service so the filesystem registry reaches it.');
    });

    it("mints a plain url for a private file once told it is public, and none while it reads the file's own visibility", function (): void {
        $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage')->write('private.png', 'Private', [Config::OPTION_VISIBILITY => Visibility::PRIVATE]);
        $source = fn (ImageColumn $column): ?string => $this->renderViewComponent($column->record(['photo' => 'private.png']))->filter('img')->attr('src');

        expect($source(ImageColumn::make('photo')->visibility(Visibility::PUBLIC)))->toBe('/files/private.png')
            ->and($source(ImageColumn::make('photo')))->toBe('');
    });

    it('skips only the existence probe when told not to check, so a missing file gets a url once it is public, and a private one still none while its visibility is read', function (): void {
        $this::getContainer()->get(FilesystemRegistry::class)->get('primary.storage')->write('private.png', 'Private', [Config::OPTION_VISIBILITY => Visibility::PRIVATE]);
        $source = fn (string $path, ImageColumn $column): ?string => $this->renderViewComponent($column->record(['photo' => $path]))->filter('img')->attr('src');

        expect($source('missing.png', ImageColumn::make('photo')->checkFileExistence(false)->visibility(Visibility::PUBLIC)))->toBe('/files/missing.png')
            ->and($source('missing.png', ImageColumn::make('photo')->visibility(Visibility::PUBLIC)))->toBe('')
            ->and($source('private.png', ImageColumn::make('photo')->checkFileExistence(false)))->toBe('');
    });

    it('draws the default image for a blank state or a value that is no string, in place of its placeholder, and drops a javascript default', function (): void {
        $images = fn (mixed $state, ImageColumn $column): Crawler => $this->renderViewComponent($column->record(['photo' => $state])->placeholder('No photo'));
        $defaulted = static fn (): ImageColumn => ImageColumn::make('photo')->defaultImageUrl('https://img.example.com/default.png');
        $unsafe = $images(null, ImageColumn::make('photo')->defaultImageUrl('javascript:alert(1)'));

        expect($images(null, $defaulted())->filter('img')->each(static fn (Crawler $image): ?string => $image->attr('src')))->toBe(['https://img.example.com/default.png'])
            ->and($images(null, $defaulted())->filter(Selectors::PLACEHOLDER))->toHaveCount(0)
            ->and($images([4, new Memo('lamp.png'), 'https://img.example.com/lamp.png'], $defaulted())->filter('img')->each(static fn (Crawler $image): ?string => $image->attr('src')))
            ->toBe(['https://img.example.com/default.png', 'https://img.example.com/default.png', 'https://img.example.com/lamp.png'])
            ->and($unsafe)->toContainElement(Selectors::PLACEHOLDER, 'No photo')
            ->and($unsafe->filter('img'))->toHaveCount(0);
    });
});

describe('its images', function (): void {
    it('sizes each image by the height and width it is given, 2.5rem high by default, 2rem stacked, and as wide as high when circular or square', function (): void {
        $style = fn (ImageColumn $column): ?string => $this->renderViewComponent($column->record(['photo' => 'https://img.example.com/lamp.png']))->filter('img')->attr('style');
        $circular = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => 'https://img.example.com/lamp.png'])->circular());
        $square = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => 'https://img.example.com/lamp.png'])->square());

        expect($style(ImageColumn::make('photo')))->toBe('height: 2.5rem')
            ->and($style(ImageColumn::make('photo')->stacked()))->toBe('height: 2rem')
            ->and($style(ImageColumn::make('photo')->imageHeight(40)))->toBe('height: 40px')
            ->and($style(ImageColumn::make('photo')->imageWidth('3rem')))->toBe('height: 2.5rem; width: 3rem')
            ->and($style(ImageColumn::make('photo')->imageSize(24)))->toBe('height: 24px; width: 24px')
            ->and($circular->filter(Selectors::IMAGE_COLUMN . '.sy-circular img')->attr('style'))->toBe('height: 2.5rem; width: 2.5rem')
            ->and($square->filter(Selectors::IMAGE_COLUMN . ':not(.sy-circular) img')->attr('style'))->toBe('height: 2.5rem; width: 2.5rem');
    });

    it('overlaps a stacked cell by two unless told otherwise and rings it when asked, and neither unless stacked', function (): void {
        $root = fn (ImageColumn $column): Crawler => $this->renderViewComponent($column->record(['photo' => ['https://img.example.com/oak.png', 'https://img.example.com/ash.png']]))->filter(Selectors::IMAGE_COLUMN);

        expect($root(ImageColumn::make('photo')->stacked())->matches('.sy-stacked.sy-ta-image-overlap-2:not(.sy-ta-image-ring)'))->toBeTrue()
            ->and($root(ImageColumn::make('photo')->stacked()->overlap(4)->ring(2))->matches('.sy-stacked.sy-ta-image-overlap-4.sy-ta-image-ring.sy-ta-image-ring-2'))->toBeTrue()
            ->and($root(ImageColumn::make('photo')->overlap(4)->ring(2))->matches(':not(.sy-stacked):not([class*="sy-ta-image-overlap-"]):not(.sy-ta-image-ring)'))->toBeTrue();
    });

    it("gives each image its own alt text, and an empty one without", function (): void {
        $alts = fn (ImageColumn $column): array => $this->renderViewComponent($column->record(['photo' => ['https://img.example.com/oak.png', 'https://img.example.com/ash.png']]))
            ->filter('img')->each(static fn (Crawler $image): ?string => $image->attr('alt'));

        expect($alts(ImageColumn::make('photo')->alt(fn (string $state): string => 'Photo of ' . basename($state, '.png'))))->toBe(['Photo of oak', 'Photo of ash'])
            ->and($alts(ImageColumn::make('photo')))->toBe(['', '']);
    });

    it('draws the first images up to its limit, counting the rest on a chip in the size it is given when asked, and every image under a limit below one', function (): void {
        $five = array_map(static fn (string $tree): string => "https://img.example.com/{$tree}.png", ['oak', 'ash', 'elm', 'fir', 'yew']);
        $cell = fn (ImageColumn $column, array $state = []): Crawler => $this->renderViewComponent($column->record(['photo' => $state ?: $five]));
        $chipped = $cell(ImageColumn::make('photo')->limit(3)->limitedRemainingText(size: TextSize::Large));
        $chip = '.sy-ta-image-limited-remaining-text';

        expect($chipped->filter('img')->each(static fn (Crawler $image): ?string => $image->attr('src')))->toBe(array_slice($five, 0, 3))
            ->and($chipped->filter($chip . SupportSelectors::size('lg'))->text())->toBe('+2')
            ->and($cell(ImageColumn::make('photo')->limit(3)->limitedRemainingText(size: 'tiny'))->filter($chip . '.tiny')->text())->toBe('+2')
            ->and($cell(ImageColumn::make('photo')->limit(3))->filter('img'))->toHaveCount(3)
            ->and($cell(ImageColumn::make('photo')->limit(3))->filter($chip))->toHaveCount(0)
            ->and($cell(ImageColumn::make('photo')->limit(5)->limitedRemainingText())->filter($chip))->toHaveCount(0)
            ->and($cell(ImageColumn::make('photo')->limit(-1)->limitedRemainingText())->filter('img'))->toHaveCount(5)
            ->and($cell(ImageColumn::make('photo')->limit(-1)->limitedRemainingText())->filter($chip))->toHaveCount(0);
    });

    it('spreads its extra img attributes on each image, a later call replacing them unless it merges, an earlier one winning a key both set', function (): void {
        $image = fn (ImageColumn $column): Crawler => $this->renderViewComponent($column->record(['photo' => 'https://img.example.com/lamp.png']))->filter('img');

        expect($image(ImageColumn::make('photo')->extraImgAttributes(['loading' => 'lazy'])->extraImgAttributes(['decoding' => 'async']))->matches('[decoding="async"]:not([loading])'))->toBeTrue()
            ->and($image(ImageColumn::make('photo')->extraImgAttributes(['loading' => 'lazy', 'data-shelf' => 'top'])->extraImgAttributes(['decoding' => 'async', 'data-shelf' => 'low'], merge: true))
                ->matches('[loading="lazy"][decoding="async"][data-shelf="top"]'))->toBeTrue();
    });

    it('titles each image with its own tooltip and links each to its own url, in a new tab when asked, and leaves a url that ignores the state to the cell', function (): void {
        $photos = ['https://img.example.com/oak.png', 'https://img.example.com/ash.png'];
        $linked = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => $photos])
            ->tooltip(fn (string $state): Markup => new Markup('<b>Photo</b> ' . basename($state, '.png'), 'UTF-8'))
            ->url(fn (string $state): string => '/photos/' . basename($state, '.png'), shouldOpenInNewTab: true));
        $unlinked = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => $photos])->url('/photos'));

        expect($linked->filter('a[target="_blank"]')->each(static fn (Crawler $link): array => [$link->attr('href'), $link->filter('img')->attr('title')]))
            ->toBe([['/photos/oak', 'Photo oak'], ['/photos/ash', 'Photo ash']])
            ->and($unlinked->filter('img'))->toHaveCount(2)
            ->and($unlinked->filter('a'))->toHaveCount(0);
    });
});

describe('its cell', function (): void {
    it('roots its cell with its alignment, its inline and wrapped looks and its extra attributes', function (): void {
        $root = fn (ImageColumn $column): Crawler => $this->renderViewComponent($column->record(['photo' => 'https://img.example.com/lamp.png']))->filter(Selectors::IMAGE_COLUMN);

        expect($root(ImageColumn::make('photo')->alignEnd()->inline()->wrap()->extraAttributes(['data-shelf' => 'top']))
            ->matches(SupportSelectors::align('end') . SchemasSelectors::INLINE . SupportSelectors::WRAPPED . '[data-shelf="top"]'))->toBeTrue()
            ->and($root(ImageColumn::make('photo'))->matches(':not(' . SupportSelectors::WRAPPED . ')'))->toBeTrue();
    });

    it('shows its placeholder for a blank state, titled by its empty tooltip as plain text, and no placeholder without one', function (): void {
        $blank = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => []])
            ->placeholder('No photo')->emptyTooltip(new Markup('<b>Nothing</b> shot', 'UTF-8')));
        $bare = $this->renderViewComponent(ImageColumn::make('photo')->record(['photo' => null]));

        expect($blank)->toContainElement(Selectors::IMAGE_COLUMN . '[title="Nothing shot"] ' . Selectors::PLACEHOLDER, 'No photo')
            ->and($blank->filter('img'))->toHaveCount(0)
            ->and($bare->filter(Selectors::IMAGE_COLUMN . ':not([title])'))->toHaveCount(1)
            ->and($bare->filter(Selectors::PLACEHOLDER))->toHaveCount(0);
    });
});
