<?php

declare(strict_types=1);

namespace Sylament\Support\Concerns;

use LogicException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Markup;

trait HasTranslator
{
    private TranslatorInterface $translator;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @throws LogicException when the object was built with new, so the container never set a translator
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!isset($this->translator)) {
            throw new LogicException(sprintf('The [%s] was built without the container, so it cannot translate a message: build it through make() after the kernel has booted, or register the class as a service.', static::class));
        }

        return $this->translator;
    }

    /**
     * A string, a Markup and null return unchanged without asking for the translator, so a new-built object still renders plain text.
     *
     * @return ($text is null ? null : ($text is TranslatableInterface ? string : ($text is Markup ? Markup : string)))
     */
    public function translateText(string|Markup|TranslatableInterface|null $text): string|Markup|null
    {
        if (!$text instanceof TranslatableInterface) {
            return $text;
        }

        return $text->trans($this->getTranslator());
    }
}
