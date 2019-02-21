<?php

declare (strict_types=1);

namespace AppInsightsPHP\Monolog\Formatter;

use Monolog\Formatter\NormalizerFormatter;

final class ContextFlatterFormatter extends NormalizerFormatter
{
    public function format(array $record)
    {
        if (!\array_key_exists('context', $record) || !\is_array($record['context'])) {
            return $record;
        }

        $formatted = $record;
        $formatted['context'] = $this->flatterArray($record['context']);

        return $formatted;
    }

    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    private function flatterArray(array $array, $prefix = '')
    {
        $result = [];

        foreach($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatterArray($value, $prefix . $key . '.');
            } else {
                $normalized = $this->normalize($value);

                if (\is_array($normalized)) {
                    $result = $result + $this->flatterArray($normalized, $prefix . $key . '.');
                } else {
                    $result[$prefix.$key] = $normalized;
                }
            }
        }

        return $result;
    }
}