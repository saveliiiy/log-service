<?php

namespace App\Controller;

use App\DTO\IngestLogsRequest;
use App\Service\LogProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs')]
readonly class LogIngestionController
{
    public function __construct(private LogProcessor $logProcessor) {}

    #[Route('/ingest', name: 'logs_ingest', methods: ['POST'])]
    public function ingest(#[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] IngestLogsRequest $request): JsonResponse
    {
        $result = $this->logProcessor->process($request);

        return new JsonResponse(
            data: [
                'status' => 'accepted',
                'batch_id' => $result['batch_id'],
                'logs_count' => $result['logs_count']
            ],
            status: Response::HTTP_ACCEPTED
        );
    }
}
