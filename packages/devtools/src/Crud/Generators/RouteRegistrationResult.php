<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

final class RouteRegistrationResult
{
    public const STATUS_ADDED = 'added';
    public const STATUS_UPDATED = 'updated';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        public readonly string $status,
        public readonly string $routeName,
        public readonly string $blockContent,
        public readonly ?string $skipReason = null,
    ) {
    }

    public function wasAdded(): bool
    {
        return $this->status === self::STATUS_ADDED;
    }

    public function wasUpdated(): bool
    {
        return $this->status === self::STATUS_UPDATED;
    }

    public function wasSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }
}
