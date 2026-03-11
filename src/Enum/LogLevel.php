<?php

namespace App\Enum;

enum LogLevel: string
{
    case EMERGENCY = 'emergency';
    case ALERT = 'alert';
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEBUG = 'debug';

    private const array PRIORITY_MAP = [
        'emergency' => 10,
        'alert' => 10,
        'critical' => 10,
        'error' => 8,
        'warning' => 5,
        'info' => 1,
        'debug' => 1,
    ];

    public function getPriority(): int
    {
        return self::PRIORITY_MAP[$this->value];
    }

    public static function getPriorityForLevel(string $level): int
    {
        return self::PRIORITY_MAP[$level] ?? 1;
    }
}
