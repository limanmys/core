<?php

namespace Tests\Unit;

use App\Http\Middleware\XssSanitization;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class XssSanitizationTest extends TestCase
{
    public function test_it_sanitizes_strings_without_coercing_json_scalar_types(): void
    {
        $payload = [
            'approve_host_key' => true,
            'replace_host_key' => false,
            'retry_count' => 0,
            'description' => '<strong>trusted</strong>',
            'nested' => [
                'enabled' => false,
                'label' => '<script>alert(1)</script>server',
            ],
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertSame($payload, $request->all());

        $result = (new XssSanitization)->handle($request, fn (Request $request) => $request);

        $this->assertTrue($result->input('approve_host_key'));
        $this->assertFalse($result->input('replace_host_key'));
        $this->assertSame(0, $result->input('retry_count'));
        $this->assertSame('trusted', $result->input('description'));
        $this->assertFalse($result->input('nested.enabled'));
        $this->assertSame('alert(1)server', $result->input('nested.label'));
    }
}
