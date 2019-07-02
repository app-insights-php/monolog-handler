<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Handler;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Monolog\Formatter\ContextFlatterFormatter;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

final class AppInsightsTraceHandler extends AbstractProcessingHandler
{
    /**
     * @var Client
     */
    private $telemetryClient;

    public function __construct(Client $telemetryClient, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->telemetryClient = $telemetryClient;
    }

    protected function write(array $record)
    {
        switch($record['level']) {
            case Logger::DEBUG:
            case Logger::INFO:
                $level = Message_Severity_Level::INFORMATION;
                break;
            case Logger::NOTICE:
                $level = Message_Severity_Level::VERBOSE;
                break;
            case Logger::WARNING:
                $level = Message_Severity_Level::WARNING;
                break;
            case Logger::ERROR:
            case Logger::ALERT:
            case Logger::EMERGENCY:
                $level = Message_Severity_Level::ERROR;
                break;
            case Logger::CRITICAL:
                $level = Message_Severity_Level::CRITICAL;
                break;
        }

        $formattedRecord = $this->formatter->format($record);

        $this->telemetryClient->trackMessage(
            $formattedRecord["message"],
            $level,
            array_merge(
                [
                    'channel' => $record['channel'],
                    'datetime' => ($record['datetime'] instanceof \DateTimeInterface) ? $record['datetime']->format('c') : $record['datetime'],
                    'monolog_level' => $record['level_name'],
                ],
                $formattedRecord['context']
            )
        );
    }

    public function handleBatch(array $records)
    {
        parent::handleBatch($records);
        $this->reset();
    }

    protected function getDefaultFormatter()
    {
        return new ContextFlatterFormatter();
    }

    public function reset()
    {
        $this->telemetryClient->flush();
    }

    public function close()
    {
        $this->reset();
    }
}