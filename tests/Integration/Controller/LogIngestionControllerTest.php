<?php

namespace App\Tests\Integration\Controller;

use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class LogIngestionControllerTest extends WebTestCase
{
    /**
     * @throws JsonException
     */
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
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        // Проверяем статус
        self::assertResponseStatusCodeSame(202);

        // Проверяем структуру ответа
        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('batch_id', $response);
        $this->assertArrayHasKey('logs_count', $response);
        $this->assertEquals(1, $response['logs_count']);

        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        $this->assertCount(1, $transport->get());
    }

    /**
     * @throws JsonException
     */
    public function testBatchWithMultipleLogs(): void
    {
        $client = static::createClient();

        $logs = [];
        for ($i = 0; $i < 5; $i++) {
            $logs[] = [
                'timestamp' => '2026-02-26T10:30:45Z',
                'level' => 'error',
                'service' => 'auth-service',
                'message' => "Test message $i"
            ];
        }

        $payload = ['logs' => $logs];

        $client->request(
            'POST',
            '/api/logs/ingest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(202);

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(5, $response['logs_count']);

        // Проверяем количество сообщений в очереди
        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $this->assertCount(5, $transport->get());
    }

    /**
     * @throws JsonException
     */
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
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(400, $response['status']);
        $this->assertEquals('https://symfony.com/errors/validation', $response['type']);
        $this->assertEquals('Validation Failed', $response['title']);
        $this->assertArrayHasKey('detail', $response);
        $this->assertIsArray($response['violations']);

        // Проверим, что есть хотя бы одно нарушение
        $this->assertGreaterThan(0, count($response['violations']));
    }
}
