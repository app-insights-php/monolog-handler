<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Handler;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\TelemetryData;
use AppInsightsPHP\Monolog\Formatter\ContextFlatterFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

final class AppInsightsDependencyHandler extends AbstractProcessingHandler
{
    /**
     * @var Client
     */
    private $telemetryClient;

    public function __construct(Client $telemetryClient)
    {
        parent::__construct(Logger::DEBUG, false);
        $this->telemetryClient = $telemetryClient;
    }

    protected function write(array $record)
    {
        $formattedRecord = $this->formatter->format($record);
        $name = $formattedRecord["channel"];
        $type = 'Monolog Dependency Handler';
        $command = $record['message'];
        $properties = \array_merge(
            [
                'datetime' => ($record['datetime'] instanceof \DateTimeInterface) ? $record['datetime']->format('c') : $record['datetime'],
                'monolog_level' => $record['level_name'],
            ],
            $formattedRecord['context']
        );

        if (TelemetryData::dependency($name, $type, $command, $properties)->exceededMaximumSize()) {
            return;
        };

        $this->telemetryClient->trackDependency(
            $name,
            $type,
            $command,
            null,
            0,
            $record['level'] >= Logger::ERROR ? false : true,
            null,
            $properties
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