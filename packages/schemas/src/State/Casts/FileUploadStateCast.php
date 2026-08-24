<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\Uid\Uuid;

use function Sylament\Support\blank;
use function Sylament\Support\wrap;

class FileUploadStateCast implements StateCast
{
    public function __construct(
        protected bool $isMultiple = false,
    ) {
    }

    public function get(mixed $state): mixed
    {
        if ($this->isMultiple) {
            return array_values(wrap($state));
        }

        if (!is_array($state)) {
            return $state;
        }

        return array_first($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function set(mixed $state): array
    {
        $newState = [];

        foreach (wrap($state) as $key => $file) {
            if (blank($file)) {
                continue;
            }

            if (is_numeric($key)) {
                $key = Uuid::v4()->toRfc4122();
            }

            $newState[$key] = $file;
        }

        return $newState;
    }
}
