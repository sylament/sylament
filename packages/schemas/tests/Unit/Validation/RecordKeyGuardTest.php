<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Validation;

use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Validation\RecordKeyGuard;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/*
 * The constraint a record-key rule's refusals become: one violation per
 * refusal, at the refusal's path and in its order, its message translated by
 * the validator that runs it, in that validator's domain. No statement runs.
 */

it('raises one violation per refusal, at its path and in its order', function (): void {
    $violations = Validation::createValidator()->validate(new RecordFixture(), RecordKeyGuard::constraint([
        'code' => 'This value cannot be changed.',
        'spine.code' => 'This value is already used.',
    ]));

    expect($violations)->toHaveCount(2)
        ->and($violations->get(0)->getPropertyPath())->toBe('code')
        ->and((string) $violations->get(0)->getMessage())->toBe('This value cannot be changed.')
        ->and($violations->get(1)->getPropertyPath())->toBe('spine.code')
        ->and((string) $violations->get(1)->getMessage())->toBe('This value is already used.');
});

it('raises nothing for no refusal', function (): void {
    expect(Validation::createValidator()->validate(new RecordFixture(), RecordKeyGuard::constraint([])))->toHaveCount(0);
});

it('translates each message through the translator of the validator running it, in its domain', function (): void {
    $translator = new class implements TranslatorInterface {
        /** @var list<string> */
        public array $domains = [];

        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            $this->domains[] = $domain ?? '';

            return match ($id) {
                'This value cannot be changed.' => 'Táto hodnota sa nedá zmeniť.',
                default => strtr($id, $parameters),
            };
        }

        public function getLocale(): string
        {
            return 'sk';
        }
    };

    $validator = Validation::createValidatorBuilder()
        ->setTranslator($translator)
        ->setTranslationDomain('validators')
        ->getValidator();

    $violations = $validator->validate(new RecordFixture(), RecordKeyGuard::constraint(['code' => 'This value cannot be changed.']));

    expect((string) $violations->get(0)->getMessage())->toBe('Táto hodnota sa nedá zmeniť.')
        ->and($violations->get(0)->getMessageTemplate())->toBe('This value cannot be changed.')
        ->and($translator->domains)->toBe(['validators']);
});
