<?php

declare(strict_types=1);

namespace Sylament\Notifications;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Notifications\Concerns\HasBody;
use Sylament\Notifications\Concerns\HasDuration;
use Sylament\Notifications\Concerns\HasId;
use Sylament\Notifications\Concerns\HasStatus;
use Sylament\Notifications\Concerns\HasTitle;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconColor;
use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Contracts\ScalableIcon;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

class Notification extends ViewComponent
{
    use HasBody;
    use HasColor;
    use HasDuration;
    use HasIcon;
    use HasIconColor;
    use HasIconSize;
    use HasId;
    use HasStatus;
    use HasTitle;

    protected string|Closure|null $defaultTemplate = '@SylamentNotifications/partials/Notification/index.html.twig';

    protected ?SessionNotifications $sessionNotifications = null;

    #[Required]
    public function setSessionNotifications(SessionNotifications $sessionNotifications): void
    {
        $this->sessionNotifications = $sessionNotifications;
    }

    public function send(): static
    {
        $sessionNotifications = $this->sessionNotifications ?? throw new LogicException(
            'Notification::send() needs the container-built instance — boot the bundle and create notifications through Notification::make().',
        );

        $sessionNotifications->push($this->toArray());

        return $this;
    }

    public static function make(?string $id = null): static
    {
        $notification = static::newInstance();

        $notification->id($id ?? Uuid::v7()->toRfc4122());

        $notification->configure();

        return $notification;
    }

    /**
     * @return array{id: string, title: ?string, body: ?string, status: ?string, color: string|array<string>|null, icon: string|BackedEnum|Markup|null, iconColor: string|array<string>|null, duration: int|string}
     */
    public function toArray(): array
    {
        $icon = $this->getIcon();

        if ($icon instanceof BackedEnum && !$icon instanceof ScalableIcon) {
            $icon = (string) $icon->value;
        }

        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'status' => $this->getStatus(),
            'color' => $this->getColor(),
            'icon' => $icon,
            'iconColor' => $this->getIconColor(),
            'duration' => $this->getDuration(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $id = $data['id'] ?? null;
        $static = static::make(is_string($id) ? $id : null);

        if (($static::class !== self::class) && (static::class === self::class)) {
            return $static::fromArray($data);
        }

        $title = $data['title'] ?? null;
        $static->title(is_string($title) ? $title : null);

        $body = $data['body'] ?? null;
        $static->body(is_string($body) ? $body : null);

        $status = $data['status'] ?? null;
        $static->status(is_string($status) ? $status : null);

        $static->color(static::toColor($data['color'] ?? null));

        $icon = $data['icon'] ?? null;
        $static->icon(is_string($icon) || $icon instanceof BackedEnum || $icon instanceof Markup ? $icon : null);

        $static->iconColor(static::toColor($data['iconColor'] ?? null));

        $duration = $data['duration'] ?? null;
        $static->duration(is_int($duration) || is_string($duration) ? $duration : $static->getDuration());

        return $static;
    }

    /**
     * @return string|array<string>|null
     */
    protected static function toColor(mixed $color): string|array|null
    {
        if (is_string($color)) {
            return $color;
        }

        if (! is_array($color)) {
            return null;
        }

        $shades = array_filter($color, is_string(...));

        return count($shades) === count($color) ? $shades : null;
    }
}
