<?php

declare(strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Client\FailureCache;
use AppInsightsPHP\Monolog\Handler\AppInsightsTraceHandler;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class AppInsightsTraceHandlerTest extends TestCase
{
    public function test_log_record() : void
    {
        $logDate = new \DateTimeImmutable('2019-01-01 10:00:00');
        $message = 'test message';
        $context = ['foo' => 'bar'];

        $telemetry = $this->createTelemetryClientMock();
        $this->expectsMessageToBeTracked($telemetry, $logDate, $message, $context);

        $handler = new AppInsightsTraceHandler(
            new Client(
                $telemetry,
                Configuration::createDefault(),
                new FailureCache($this->createMock(CacheInterface::class)),
                new NullLogger()
            )
        );

        $handler->handle($this->getRecord($logDate, Logger::DEBUG, $message, $context));
    }

    public function test_sent_message_to_app_insights_after_batch_processing() : void
    {
        $telemetry = $this->createTelemetryClientMock();
        $this->expectsMessageToBeTracked($telemetry, $logDate = new \DateTimeImmutable('2019-01-01 10:00:00'));

        // two times, because the first time is after batc processing and the second time in the object destructor
        $telemetry->expects($this->exactly(2))->method('flush');

        $handler = new AppInsightsTraceHandler(
            new Client(
                $telemetry,
                Configuration::createDefault(),
                new FailureCache($this->createMock(CacheInterface::class)),
                new NullLogger()
            )
        );

        $handler->handleBatch([$this->getRecord($logDate, Logger::DEBUG)]);
    }

    protected function getRecord(\DateTimeInterface $dateTime, $level = Logger::WARNING, string $message = 'test', array $context = []) : array
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

    private function expectsMessageToBeTracked(MockObject $telemetry, \DateTimeImmutable $logDate, string $message = 'test', array $context = []) : void
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
            );
    }

    private function createTelemetryClientMock() : MockObject
    {
        $telemetryClientMock = $this->createMock(Telemetry_Client::class);
        $telemetryClientMock->method('getChannel')->willReturn(
            $telemetryChannelMock = $this->createMock(Telemetry_Channel::class)
        );
        $telemetryChannelMock->method('getQueue')->willReturn([]);
        $telemetryChannelMock->method('getSerializedQueue')->willReturn(\json_encode([]));

        return $telemetryClientMock;
    }
}
