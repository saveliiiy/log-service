<?php

namespace App\Controller;

use App\DTO\IngestLogsRequest;
use App\Service\LogProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestController extends AbstractController
{
    public function __construct(
        private readonly LogProcessor $logProcessor,
        private readonly ValidatorInterface $validator
    ) {}
    #[Route('/test/logs/ingest', name: 'test_logs_ingest', methods: ['GET'])]
    public function testIngest(Request $request): JsonResponse
    {
        $data = [
            'logs' => [
                'timestamp' => '2026-02-26T10:30:45Z',
                'level' => 'error',
                'service' => 'auth-service',
                'message' => 'User authentication failed',
                'context' => [
                    'user_id' => 123,
                    'ip' => '192.168.1.1',
                    'error_code' => 'INVALID_TOKEN'
                ],
                'trace_id' => 'abc123def456'
            ]
        ];
        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON format',
                'received' => $request->getContent()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Создаем DTO
            $ingestRequest = new IngestLogsRequest($data['logs'] ?? []);

            // Валидируем
            $errors = $this->validator->validate($ingestRequest);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = [
                        'property' => $error->getPropertyPath(),
                        'message' => $error->getMessage(),
                        'value' => $error->getInvalidValue()
                    ];
                }

                return $this->json([
                    'status' => 'error',
                    'errors' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Обрабатываем логи
            $result = $this->logProcessor->process($ingestRequest);

            return $this->json([
                'status' => 'accepted',
                'batch_id' => $result['batch_id'],
                'logs_count' => $result['logs_count'],
                'message' => 'Logs successfully queued'
            ], Response::HTTP_ACCEPTED);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/test', name: 'test_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'service' => 'Log Service API',
            'version' => '1.0',
            'endpoints' => [
                'GET /test' => 'This info',
                'GET /test/logs/ingest' => 'Test ingest endpoint (GET)',
                'POST /api/logs/ingest' => 'Main API endpoint for sending logs'
            ],
            'test_with_curl' => [
                'curl -X POST http://localhost:8080/api/logs/ingest -H "Content-Type: application/json" -d \'{"logs":[{"timestamp":"2026-02-26T10:30:45Z","level":"error","service":"test","message":"hello"}]}\''
            ]
        ]);
    }
}
