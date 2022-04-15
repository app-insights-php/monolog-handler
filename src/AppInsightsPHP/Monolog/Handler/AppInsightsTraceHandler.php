<?php

declare(strict_types=1);

namespace AppInsightsPHP\Monolog\Handler;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\TelemetryData;
use AppInsightsPHP\Monolog\Formatter\ContextFlatterFormatter;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;
use Monolog\Formatter\FormatterInterface;
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

    public function handleBatch(array $records) : void
    {
        parent::handleBatch($records);
        $this->reset();
    }

    public function reset() : void
    {
        $this->telemetryClient->flush();
    }

    public function close() : void
    {
        $this->reset();
    }

    protected function write(array $record) : void
    {
        switch ($record['level']) {
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
        $message = $formattedRecord['message'];
        $properties = \array_merge(
            [
                'channel' => $record['channel'],
                'datetime' => ($record['datetime'] instanceof \DateTimeInterface) ? $record['datetime']->format('c') : $record['datetime'],
                'monolog_level' => $record['level_name'],
            ],
            $formattedRecord['context']
        );

        if (TelemetryData::message($message, $properties)->exceededMaximumSize()) {
            return;
        }

        $this->telemetryClient->trackMessage($message, $level, $properties);
    }

    protected function getDefaultFormatter() : FormatterInterface
    {
        return new ContextFlatterFormatter();
    }
}
