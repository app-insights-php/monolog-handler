<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Monolog\Handler\AppInsightsTraceHandler;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;
use ApplicationInsights\Telemetry_Client;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppInsightsTraceHandlerTest extends TestCase
{
    public function test_log_record(): void
    {
        $logDate = new \DateTimeImmutable('2019-01-01 10:00:00');
        $message = 'test message';
        $context = ['foo' => 'bar'];

        $telemetry = $this->createMock(Telemetry_Client::class);
        $this->expectsMessageToBeTracked($telemetry, $logDate, $message, $context);

        $handler = new AppInsightsTraceHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handle($this->getRecord($logDate, Logger::DEBUG, $message, $context));
    }

    public function test_sent_message_to_app_insights_after_batch_processing(): void
    {
        $telemetry = $this->createMock(Telemetry_Client::class);
        $this->expectsMessageToBeTracked($telemetry, $logDate = new \DateTimeImmutable('2019-01-01 10:00:00'));

        // two times, because the first time is after batc processing and the second time in the object destructor
        $telemetry->expects($this->exactly(2))->method('flush');

        $handler = new AppInsightsTraceHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handleBatch([$this->getRecord($logDate, Logger::DEBUG)]);
    }

    protected function getRecord(\DateTimeInterface $dateTime, $level = Logger::WARNING, string $message = 'test', array $context = []): array
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => $dateTime,
            'extra' => [],
        ];
    }

    private function expectsMessageToBeTracked(MockObject $telemetry, \DateTimeImmutable $logDate, string $message = 'test', array $context = []): void
    {
        $telemetry->expects($this->once())
            ->method('trackMessage')
            ->with(
                $message,
                Message_Severity_Level::INFORMATION,
                \array_merge(
                    [
                        'channel' => 'test',
                        'datetime' => $logDate->format('c'),
                        'monolog_level' => 'DEBUG',
                    ],
                    $context
                )
            )
        ;
    }
}