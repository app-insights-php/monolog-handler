<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Monolog\Handler\AppInsightsDependencyHandler;
use ApplicationInsights\Telemetry_Client;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class AppInsightsDependencyHandlerTest extends TestCase
{
    public function test_log_record()
    {
        $logDate = new \DateTimeImmutable('2019-01-01 10:00:00');

        $telemetry = $this->createMock(Telemetry_Client::class);
        $telemetry->expects($this->once())
            ->method('trackDependency')
            ->with(
                'channel_name',
                'Monolog Dependency Handler',
                'test message',
                null,
                null,
                true,
                null,
                [
                    'datetime' => $logDate->format('c'),
                    'monolog_level' => 'DEBUG',
                    'foo' => 'bar'
                ]
            );


        $handler = new AppInsightsDependencyHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handle($this->getRecord($logDate, Logger::DEBUG, 'test message', 'channel_name', ['foo' => 'bar']));
    }

    protected function getRecord(\DateTimeInterface $dateTime, $level = Logger::WARNING, $message = 'test', string $channel, $context = array())
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
}