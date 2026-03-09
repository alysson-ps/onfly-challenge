<?php

namespace Tests\Feature;

use App\Factories\ExceptionHandlerFactory;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExceptionHandlerFactoryTest extends TestCase
{
    public function test_returns_default_response_for_unknown_exception(): void
    {
        $exception = new Exception('Unknown error');
        $response = ExceptionHandlerFactory::make($exception);

        $data = $response->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertEquals('Internal Server Error', $data['error']['message']);
        $this->assertArrayHasKey('code', $data['error']);
        $this->assertEquals(500, $data['error']['code']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_returns_detailed_message_in_non_production(): void
    {
        config(['app.env' => 'local']);

        $exception = new Exception('Detailed error message');
        $response = ExceptionHandlerFactory::make($exception);

        $data = $response->getData(true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('reason', $data['error']);
        $this->assertEquals('Detailed error message', $data['error']['reason']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_returns_generic_message_in_production(): void
    {
        config(['app.env' => 'production']);

        $exception = new Exception('Detailed error message');
        $response = ExceptionHandlerFactory::make($exception);

        $data = $response->getData(true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('reason', $data['error']);
        $this->assertEquals('No message available', $data['error']['reason']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_validation_exception_strategy_is_used(): void
    {
        $validator = Validator::make([], ['field' => 'required']);
        $exception = new ValidationException($validator);

        $response = ExceptionHandlerFactory::make($exception);

        $data = $response->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertEquals('Validation Error', $data['error']['message']);
        $this->assertArrayHasKey('fields', $data['error']);
        $this->assertArrayHasKey('field', $data['error']['fields']);
        $this->assertEquals(['The field field is required.'], $data['error']['fields']['field']);
        $this->assertEquals(422, $response->getStatusCode());
    }
}
