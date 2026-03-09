<?php

namespace App\MessageHandler;

use App\Message\LogMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class LogMessageHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function __invoke(LogMessage $message): void
    {
        $this->logger->info('Processing log message', [
            'batch_id' => $message->batchId,
            'log_data' => $message->logData,
            'published_at' => $message->publishedAt->format('Y-m-d H:i:s'),
            'retry_count' => $message->retryCount
        ]);
    }
}
