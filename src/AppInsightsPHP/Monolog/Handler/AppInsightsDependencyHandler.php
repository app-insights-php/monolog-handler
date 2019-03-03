<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Handler;

use AppInsightsPHP\Client\Client;
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

        $this->telemetryClient->trackDependency(
            $formattedRecord["channel"],
            'Monolog Dependency Handler',
            $record['message'],
            null,
            null,
            \in_array($record['level'], [Logger::CRITICAL, Logger::ERROR, Logger::EMERGENCY]) ? false : true,
            null,
            array_merge(
                [
                    'datetime' => ($record['datetime'] instanceof \DateTimeInterface) ? $record['datetime']->format('c') : $record['datetime'],
                    'monolog_level' => $record['level_name'],
                ],
                $formattedRecord['context']
            )
        );
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
        return $this->reset();
    }
}