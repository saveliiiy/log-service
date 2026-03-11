<?php

namespace App\Tests\Unit\Service;

use App\DTO\IngestLogsRequestDTO;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogValidatorTest extends KernelTestCase
{
    private ValidatorInterface $validator;

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

        $request = new IngestLogsRequestDTO($data['logs']);
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

        $request = new IngestLogsRequestDTO($data['logs']);
        $errors = $this->validator->validate($request);

        $this->assertGreaterThan(0, count($errors));
    }
}
