<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Monolog\Handler\AppInsightsDependencyHandler;
use ApplicationInsights\Telemetry_Client;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppInsightsDependencyHandlerTest extends TestCase
{
    public function test_log_record(): void
    {
        $logDate = new \DateTimeImmutable('2019-01-01 10:00:00');
        $message = 'test message';
        $channel = 'channel_name';
        $context = ['foo' => 'bar'];

        $telemetry = $this->createMock(Telemetry_Client::class);
        $this->expectsMessageToBeTracked($telemetry, $logDate, $message, $channel, $context);

        $handler = new AppInsightsDependencyHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handle($this->getRecord($logDate, Logger::DEBUG, $message, $channel, $context));
    }

    public function test_sent_message_to_app_insights_after_batch_processing(): void
    {
        $telemetry = $this->createMock(Telemetry_Client::class);
        $this->expectsMessageToBeTracked($telemetry, $logDate = new \DateTimeImmutable('2019-01-01 10:00:00'));

        // two times, because the first time is after batc processing and the second time in the object destructor
        $telemetry->expects($this->exactly(2))->method('flush');

        $handler = new AppInsightsDependencyHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handleBatch([$this->getRecord($logDate, Logger::DEBUG)]);
    }

    protected function getRecord(\DateTimeInterface $dateTime, $level = Logger::WARNING, string $message = 'test', string $channel = 'channel', array $context = []): array
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => $channel,
            'datetime' => $dateTime,
            'extra' => [],
        ];
    }

    private function expectsMessageToBeTracked(MockObject $telemetry, \DateTimeImmutable $logDate, string $message = 'test', string $channel = 'channel', array $context = []): void
    {
        $telemetry->expects($this->once())
            ->method('trackDependency')
            ->with(
                $channel,
                'Monolog Dependency Handler',
                $message,
                null,
                null,
                true,
                null,
                \array_merge(
                    [
                        'datetime' => $logDate->format('c'),
                        'monolog_level' => 'DEBUG',
                    ],
                    $context
                )
            )
        ;
    }
}