<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case STARTED = 'started';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public static function activeValues(): array
    {
        return [
            self::SCHEDULED->value,
            self::STARTED->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::SCHEDULED => 'Запланировано',
            self::STARTED => 'Начато',
            self::COMPLETED => 'Завершено',
            self::CANCELED => 'Отменено',
        };
    }

    public static function caseSql(string $column = 'activities.status', string $alias = 'status'): string
    {
        $parts = array_map(
            static fn (self $status): string => "WHEN {$column} = '".$status->value."' THEN '".$status->label()."'",
            self::cases()
        );

        return 'CASE '.implode(' ', $parts)." ELSE 'Отменено' END as {$alias}";
    }
}
