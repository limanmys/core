<?php

namespace App\System;

class Command
{
    public static function run($command, $attributes = [], $log = true): string
    {
        return trim((string) server()->run(self::format($command, $attributes), $log));
    }

    public static function runSudo($command, $attributes = [], $log = true)
    {
        $command = trim((string) self::format($command, $attributes));

        return self::run(sudo() . $command, $log);
    }

    public static function runLiman($command, $attributes = []): string
    {
        return trim((string) shell_exec(self::format($command, $attributes)));
    }

    public static function runSystem($command, $attributes = []): string
    {
        return trim((string) rootSystem()->runCommand(self::format($command, $attributes)));
    }

    private static function format($command, $attributes = [])
    {
        if (! is_array($attributes)) {
            return $command;
        }

        foreach ($attributes as $attribute => $value) {
            $command = str_replace(
                "@{:$attribute}",
                self::clean($value),
                (string) $command
            );
            $command = str_replace(
                "{:$attribute}",
                self::cleanWithoutQuotes($value),
                $command
            );
            $command = str_replace(":$attribute:", $value, $command);
        }

        return $command;
    }

    private static function cleanWithoutQuotes($value)
    {
        return preg_replace(
            '/^(\'(.*)\'|"(.*)")$/',
            '$2$3',
            self::clean($value)
        );
    }

    private static function clean($value): string
    {
        return escapeshellcmd(escapeshellarg((string) $value));
    }
}
