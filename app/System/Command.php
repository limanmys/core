<?php

namespace App\System;

class Command
{
    public static function run($command, $attributes = [], $log = true)
    {
        return trim((string) server()->run(self::format($command, $attributes), $log));
    }

    public static function runSudo($command, $attributes = [], $log = true)
    {
        $command = trim((string) self::format($command, $attributes));

        return self::run(sudo().'echo {:command} | base64 -d | sudo bash', ['command' => base64_encode($command)], $attributes, $log);
    }

    public static function runLiman($command, $attributes = [])
    {
        return trim((string) shell_exec(self::format($command, $attributes)));
    }

    public static function runSystem($command, $attributes = [])
    {
        return trim((string) rootSystem()->runCommand(self::format($command, $attributes)));
    }

    private static function format($command, $attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            $command = str_replace(
                "@{:$attribute}",
                self::clean($value),
                $command
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

    private static function clean($value)
    {
        return escapeshellcmd(escapeshellarg($value));
    }
}
