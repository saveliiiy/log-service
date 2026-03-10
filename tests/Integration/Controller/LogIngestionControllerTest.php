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

        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        $this->assertCount(1, $transport->get());
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
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('type', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('detail', $response);
        $this->assertArrayHasKey('violations', $response);
        $this->assertIsArray($response['violations']);
        $this->assertNotEmpty($response['violations']);

        // Проверим, что есть хотя бы одно нарушение
        $this->assertGreaterThan(0, count($response['violations']));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
