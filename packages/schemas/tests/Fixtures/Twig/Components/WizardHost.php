<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use RuntimeException;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Support\Exceptions\Halt;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host carrying a three-step Wizard over a `data` state path: two constrained
 * steps and an unconstrained last one, plus props steering the two branches that
 * cannot be driven from the client — a `Halt` out of `beforeValidation()` and a
 * skippable wizard.
 */
#[AsLiveComponent(name: 'WizardHost', template: '@SchemasFixtures/components/WizardHost.html.twig')]
class WizardHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public const string WIZARD_KEY = 'schema.data::wizard';

    public const string FIRST_STEP_KEY = 'schema.basics::data::wizard-step';

    public const string SECOND_STEP_KEY = 'schema.details::data::wizard-step';

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public bool $halt = false;

    #[LiveProp]
    public bool $skippable = false;

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Basics')
                        ->beforeValidation(function (): void {
                            if ($this->halt) {
                                throw new Halt();
                            }
                        })
                        ->components([ConstrainedField::make('title')->required()]),
                    Step::make('Details')
                        ->components([ConstrainedField::make('body')->required()]),
                    Step::make('Done'),
                ])->skippable(fn (): bool => $this->skippable),
            ]);
    }

    public function getWizard(): Wizard
    {
        $wizard = $this->getSchemaComponent(static::WIZARD_KEY);

        if (!$wizard instanceof Wizard) {
            throw new RuntimeException('The wizard is missing from the fixture schema.');
        }

        return $wizard;
    }

    /**
     * The seam call the browser makes, with the `ValidationException` swallowed so the
     * request still renders — the browser events are only readable off a response.
     */
    #[LiveAction]
    public function advance(#[LiveArg] int $currentStepIndex = 0): void
    {
        try {
            $this->getWizard()->nextStep($currentStepIndex);
        } catch (ValidationException) {
        }
    }
}
