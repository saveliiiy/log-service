<?php

namespace App\Service;

use App\DTO\IngestLogsRequestDTO;
use App\Enum\LogLevel;
use App\Message\LogMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class LogProcessor
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

    public function process(IngestLogsRequestDTO $request): array
    {
        $batchId = Uuid::v4()->toRfc4122();
        $publishedAt = new \DateTimeImmutable();

        foreach ($request->logs as $logData) {
            $priority = LogLevel::getPriorityForLevel($logData['level']);

            $message = new LogMessage(
                batchId: $batchId,
                logData: [
                    'timestamp' => $logData['timestamp'],
                    'level' => $logData['level'],
                    'service' => $logData['service'],
                    'message' => $logData['message'],
                    'context' => $logData['context'] ?? null,
                    'trace_id' => $logData['trace_id'] ?? null,
                ],
                publishedAt: $publishedAt,
                priority: $priority,
            );

            $this->bus->dispatch($message);
        }

        return [
            'batch_id' => $batchId,
            'logs_count' => count($request->logs)
        ];
    }
}
