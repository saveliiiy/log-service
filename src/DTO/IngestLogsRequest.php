<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class IngestLogsRequest
{
    public function __construct(

        #[Assert\NotBlank(message: 'Массив логов обязателен')]
        #[Assert\Count(
            min: 1,
            max: 1000,
            minMessage: 'Необходим минимум один лог',
            maxMessage: 'Максимально допустимое количество логов в одном пакете: {{ limit }}'
        )]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'timestamp' => [
                        new Assert\NotBlank(message: 'Временная метка (timestamp) обязательна'),
                        new Assert\DateTime(
                            format: 'Y-m-d\TH:i:s\Z',
                            message: 'Неверный формат временной метки. Ожидается формат: Y-m-d\TH:i:sZ (например, 2026-03-06T22:30:45Z)'
                        )
                    ],
                    'level' => [
                        new Assert\NotBlank(message: 'Уровень лога (level) обязателен')
                    ],
                    'service' => [
                        new Assert\NotBlank(message: 'Название сервиса (service) обязательно'),
                        new Assert\Length(
                            min: 2,
                            max: 100,
                            minMessage: 'Название сервиса должно содержать минимум {{ limit }} символа',
                            maxMessage: 'Название сервиса не может превышать {{ limit }} символов'
                        ),
                        new Assert\Regex(
                            pattern: '/^[a-zA-Z0-9\-_]+$/',
                            message: 'Название сервиса может содержать только буквы латинского алфавита, цифры, дефисы и знаки подчеркивания'
                        )
                    ],
                    'message' => [
                        new Assert\NotBlank(message: 'Сообщение (message) обязательно'),
                        new Assert\Length(
                            max: 1000,
                            maxMessage: 'Сообщение не может превышать {{ limit }} символов'
                        )
                    ],
                    'context' => [
                        new Assert\Optional([
                            new Assert\Type(
                                type: 'array',
                                message: 'Контекст (context) должен быть массивом'
                            )
                        ])
                    ],
                    'trace_id' => [
                        new Assert\Optional([
                            new Assert\Length(
                                exactly: 12,
                                exactMessage: 'Trace ID должен содержать ровно {{ limit }} символов'
                            ),
                            new Assert\Regex(
                                pattern: '/^[a-f0-9]+$/i',
                                message: 'Trace ID должен быть шестнадцатеричным числом (только символы a-f, A-F и 0-9)'
                            )
                        ])
                    ]
                ],
                allowExtraFields: true,
                allowMissingFields: false
            )
        ])]
        public array $logs
    ) {}
}
