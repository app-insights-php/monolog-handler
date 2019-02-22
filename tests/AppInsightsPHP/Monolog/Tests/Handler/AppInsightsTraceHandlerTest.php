<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Tests\Formatter;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Monolog\Handler\AppInsightsTraceHandler;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;
use ApplicationInsights\Telemetry_Client;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class AppInsightsTraceHandlerTest extends TestCase
{
    public function test_log_record()
    {
        $logDate = new \DateTimeImmutable('2019-01-01 10:00:00');

        $telemetry = $this->createMock(Telemetry_Client::class);
        $telemetry->expects($this->once())
            ->method('trackMessage')
            ->with(
                'test message',
                Message_Severity_Level::INFORMATION,
                [
                    'channel' => 'test',
                    'datetime' => $logDate->format('c'),
                    'monolog_level' => 'DEBUG',
                    'foo' => 'bar'
                ]
            );


        $handler = new AppInsightsTraceHandler(new Client($telemetry, Configuration::createDefault()));

        $handler->handle($this->getRecord($logDate, Logger::DEBUG, 'test message', ['foo' => 'bar']));
    }

    protected function getRecord(\DateTimeInterface $dateTime, $level = Logger::WARNING, $message = 'test', $context = array())
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
}