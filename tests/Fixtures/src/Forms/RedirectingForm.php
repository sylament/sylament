<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Saves by redirecting once its form validates, as a record page's create does.
 */
#[AsLiveComponent(name: 'Forms:RedirectingForm', template: '@Fixtures/forms/RedirectingForm.html.twig')]
final class RedirectingForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public const string REDIRECT_URL = '/codes';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('code')->required(),
            ]);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->validateSchema($this->getSchema('form'));

        return new RedirectResponse(self::REDIRECT_URL);
    }
}
