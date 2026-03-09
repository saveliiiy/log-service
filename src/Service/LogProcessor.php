<?php

namespace App\Service;

use App\DTO\IngestLogsRequest;
use App\Message\LogMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class LogProcessor
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

    public function process(IngestLogsRequest $request): array
    {
        $batchId = Uuid::v4()->toRfc4122();
        $publishedAt = new \DateTimeImmutable();

        foreach ($request->logs as $logData) {
            $priority = match($logData['level']) {
                'emergency', 'alert', 'critical' => 10,
                'error' => 8,
                'warning' => 5,
                default => 1,
            };

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
