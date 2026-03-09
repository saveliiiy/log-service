<?php

namespace App\Message;

readonly class LogMessage
{
    public function __construct(
        public string             $batchId,
        public array              $logData,
        public \DateTimeImmutable $publishedAt,
        public int                $retryCount = 0,
        public int                $priority = 0
    ) {}
}
