<?php

namespace App\Enums;

enum DisplayMediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case YOUTUBE = 'youtube';
    case INSTAGRAM = 'instagram';
    case LINK = 'link';
    case NOTE = 'note';

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Image banner',
            self::VIDEO => 'Video file / embed',
            self::YOUTUBE => 'YouTube link',
            self::INSTAGRAM => 'Instagram link',
            self::LINK => 'Website link',
            self::NOTE => 'Text note',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::IMAGE => 'primary',
            self::VIDEO => 'warning',
            self::YOUTUBE => 'success',
            self::INSTAGRAM => 'info',
            self::LINK => 'gray',
            self::NOTE => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mediaType): array => [
                $mediaType->value => $mediaType->label(),
            ])
            ->all();
    }

    public static function normalize(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(strtolower($value));
    }

    public function isVisual(): bool
    {
        return in_array($this, [self::IMAGE, self::VIDEO, self::YOUTUBE, self::INSTAGRAM], true);
    }

    public function isLinkBased(): bool
    {
        return in_array($this, [self::VIDEO, self::YOUTUBE, self::INSTAGRAM, self::LINK], true);
    }
}
