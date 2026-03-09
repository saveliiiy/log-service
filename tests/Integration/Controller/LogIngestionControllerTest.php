<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class LogIngestionControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessfulIngestion(): void
    {
        $client = static::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45Z',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'Test message',
                    'context' => ['user_id' => 123],
                    'trace_id' => 'abc123def456'
                ]
            ]
        ];

        $client->request(
            'POST',
            '/api/logs/ingest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Проверяем статус
        $this->assertResponseStatusCodeSame(202);

        // Проверяем структуру ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('batch_id', $response);
        $this->assertArrayHasKey('logs_count', $response);
        $this->assertEquals(1, $response['logs_count']);

        // Получаем транспорт из контейнера ПОСЛЕ createClient()
        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
    }

    public function testBatchWithMultipleLogs(): void
    {
        $client = static::createClient();

        $logs = [];
        for ($i = 0; $i < 5; $i++) {
            $logs[] = [
                'timestamp' => '2026-02-26T10:30:45Z',
                'level' => 'error',
                'service' => 'auth-service',
                'message' => "Test message {$i}"
            ];
        }

        $payload = ['logs' => $logs];

        $client->request(
            'POST',
            '/api/logs/ingest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(202);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(5, $response['logs_count']);

        // Проверяем количество сообщений в очереди
        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $this->assertCount(5, $transport->get());
    }

    public function testValidationError(): void
    {
        $client = static::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => 'invalid-date',
                    'level' => 'invalid-level',
                    'service' => '',
                    'message' => ''
                ]
            ]
        ];

        $client->request(
            'POST',
            '/api/logs/ingest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $response);
        $this->assertIsArray($response['errors']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Очищаем транспорт после каждого теста
        if (self::$container) {
            $transport = self::getContainer()->get('messenger.transport.logs_ingest');
            if ($transport instanceof InMemoryTransport) {
                // Очищаем очередь
                $transport->reset();
            }
        }
    }
}
