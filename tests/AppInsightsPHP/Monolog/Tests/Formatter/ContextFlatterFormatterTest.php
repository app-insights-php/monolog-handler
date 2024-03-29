<?php

declare(strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Monolog\Formatter\ContextFlatterFormatter;
use PHPUnit\Framework\TestCase;

final class ContextFlatterFormatterTest extends TestCase
{
    public function test_formatting_record_without_context() : void
    {
        $formatter = new ContextFlatterFormatter();

        $this->assertEquals(['test' => 'test'], $formatter->format(['test' => 'test']));
    }

    public function test_formatting_multidimensional_array_context_with_scalar_values() : void
    {
        $formatter = new ContextFlatterFormatter();

        $this->assertEquals(
            [
                'context' => [
                    'string' => 'string',
                    'assoc_array.key.value' => 'value',
                    'scalar_array.0.value' => 'value',
                ],
            ],
            $formatter->format([
                'context' => [
                    'string' => 'string',
                    'assoc_array' => [
                        'key' => [
                            'value' => 'value',
                        ],
                    ],
                    'scalar_array' => [
                        ['value' => 'value'],
                    ],
                ],
            ])
        );
    }

    public function test_formatting_multidimensional_array_context_with_mixed_values() : void
    {
        $formatter = new ContextFlatterFormatter();

        $formatted = $formatter->format([
            'context' => [
                'string' => 'string',
                'datetime' => new \DateTimeImmutable('2022-04-15T13:31:28+00:00'),
            ],
        ]);

        $this->assertEquals('string', $formatted['context']['string']);
        $this->assertStringContainsString('2022-04-15', $formatted['context']['datetime']);
    }

    public function test_formatting_multidimensional_array_context_with_mixed_values_with_prefix() : void
    {
        $formatter = new ContextFlatterFormatter('param_');

        $formatted = $formatter->format([
            'context' => [
                'string' => 'string',
                'datetime' => new \DateTimeImmutable('2022-04-15T13:31:28+00:00'),
            ],
        ]);

        $this->assertEquals('string', $formatted['context']['param_string']);
        $this->assertStringContainsString('2022-04-15', $formatted['context']['param_datetime']);
    }
}
