<?php

namespace App\Tests\Unit\Service;

use App\DTO\IngestLogsRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;

class LogValidatorTest extends KernelTestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidLogPassesValidation(): void
    {
        $data = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45Z',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'Test message'
                ]
            ]
        ];

        $request = new IngestLogsRequest($data['logs']);
        $errors = $this->validator->validate($request);

        $this->assertCount(0, $errors);
    }

    public function testInvalidLogFailsValidation(): void
    {
        $data = [
            'logs' => [
                [
                    'timestamp' => 'invalid-date',
                    'level' => 'invalid-level',
                    'service' => '',
                    'message' => ''
                ]
            ]
        ];

        $request = new IngestLogsRequest($data['logs']);
        $errors = $this->validator->validate($request);

        $this->assertGreaterThan(0, count($errors));
    }
}
