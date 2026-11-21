<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\AffixActionForm;
use Sylament\Tests\Fixtures\Forms\Selectors;

use function Symfony\Component\Translation\t;

it('renders a copy button its controller answers in the browser, with no live click, drawn with its clipboard icon, confirming with the default message for the default time', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('code')->copyable()))
        ->toContainElement('button[aria-label="Copy"][data-sylament--forms--text-input-message-param="Copied"][data-sylament--forms--text-input-duration-param="2000"]' . Selectors::textInputAction('copy') . ' svg', 'clipboard-document-list-20-solid');
});

describe('copy message', function (): void {
    it('confirms with the message and for the time its closures make of the state the field rendered', function (): void {
        expect($this->createLiveComponent(AffixActionForm::class, ['data' => ['copied' => 'ABC']])->render()->crawler())
            ->toContainElement('button[aria-label="Copy"][data-sylament--forms--text-input-message-param="Copied ABC"][data-sylament--forms--text-input-duration-param="3000"]');
    });

    it('makes its message of the state of its own field inside a repeater item', function (): void {
        $render = $this->createLiveComponent(AffixActionForm::class, ['data' => ['tags' => ['a' => ['name' => 'php'], 'b' => ['name' => 'twig']]]])->render()->crawler();

        expect($render)
            ->toContainElement('button[aria-label="Copy"][data-sylament--forms--text-input-message-param="Copied php"]')
            ->toContainElement('button[aria-label="Copy"][data-sylament--forms--text-input-message-param="Copied twig"]');
    });
});

it('confirms with a copy message given as a message, translated', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('code')->copyable(copyMessage: t('text_input.copied', [], 'fixtures-forms'))))
        ->toContainElement('button[aria-label="Copy"][data-sylament--forms--text-input-message-param="Copied it (translated)"]');
});
