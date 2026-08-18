<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\ViewField;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

const VIEW_FIELD_TEMPLATE = '@FormsFixtures/partials/ViewFieldFixture/index.html.twig';

const VIEW_FIELD_UNWRAPPED_TEMPLATE = '@FormsFixtures/partials/ViewFieldFixture/Unwrapped.html.twig';

it('renders the user template with the component in scope', function (): void {
    $crawler = new Crawler(renderField(
        ViewField::make('bio')->template(VIEW_FIELD_TEMPLATE)->label('Bio'),
    ));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->count())->toBe(1)
        ->and($input->attr('id'))->toBe('bio')
        ->and($input->attr('data-model'))->toBe('norender|data.bio')
        ->and($input->attr('value'))->toBe('');
});

it('wraps the field only because the user template opens the wrapper', function (): void {
    $wrapped = new Crawler(renderField(
        ViewField::make('bio')->template(VIEW_FIELD_TEMPLATE)->label('Bio'),
    ));
    $unwrapped = new Crawler(renderField(
        ViewField::make('bio')->template(VIEW_FIELD_UNWRAPPED_TEMPLATE)->label('Bio'),
    ));

    expect($wrapped->filter('div.sy-fo-field')->count())->toBe(1)
        ->and($wrapped->filter('.sy-fo-field-label-content')->text())->toBe('Bio')
        ->and($unwrapped->filter('div.sy-fo-field')->count())->toBe(0)
        ->and($unwrapped->filter('input.sy-fo-input')->count())->toBe(1);
});

it('merges template data into the template scope', function (): void {
    $input = new Crawler(renderField(
        ViewField::make('bio')
            ->template(VIEW_FIELD_TEMPLATE, ['note' => 'from template data'])
            ->templateData(['hint' => 'from templateData()']),
    ))->filter('input.sy-fo-input');

    expect($input->attr('data-note'))->toBe('from template data')
        ->and($input->attr('data-hint'))->toBe('from templateData()');
});

it('fails fast when no template is set', function (): void {
    expect(fn (): string => ViewField::make('bio')->getTemplate())
        ->toThrow(
            LogicException::class,
            'The [Sylament\Forms\Components\ViewField] field has no template. Call template() on it, or declare a $template property on a subclass.',
        );
});

it('fails fast when rendered without a template', function (): void {
    expect(fn (): string => renderToHtml(ViewField::make('bio')))->toThrow(LogicException::class);
});

it('resolves a default template when no explicit one is set', function (): void {
    expect(ViewField::make('bio')->defaultTemplate(VIEW_FIELD_TEMPLATE)->getTemplate())
        ->toBe(VIEW_FIELD_TEMPLATE);
});
