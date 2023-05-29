<?php

namespace App\System;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Command
 * This class' purpose is running commands on remote server in a secure wrapper
 */
class Command
{
    /**
     * Run command as sudo
     *
     * @param $command
     * @param $attributes
     * @param $log
     * @throws GuzzleException
     * @return string
     */
    public static function runSudo($command, $attributes = [], $log = true)
    {
        $command = trim((string) self::format($command, $attributes));

        return self::run(sudo() . $command, $log);
    }

    /**
     * Format's string and cleans it to run securely on remote server
     *
     * @param $command
     * @param $attributes
     * @return array|mixed|string|string[]
     */
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

    /**
     * Clean command
     *
     * @param $value
     * @return string
     */
    private static function clean($value): string
    {
        return escapeshellcmd(escapeshellarg((string) $value));
    }

    /**
     * Clean command without adding quotes
     *
     * @param $value
     * @return array|string|string[]|null
     */
    private static function cleanWithoutQuotes($value)
    {
        return preg_replace(
            '/^(\'(.*)\'|"(.*)")$/',
            '$2$3',
            self::clean($value)
        );
    }

    /**
     * Run command
     *
     * @param $command
     * @param $attributes
     * @param $log
     * @return string
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public static function run($command, $attributes = [], $log = true): string
    {
        return trim((string) server()->run(self::format($command, $attributes), $log));
    }

    /**
     * Run command on Liman server
     *
     * @param $command
     * @param $attributes
     * @return string
     */
    public static function runLiman($command, $attributes = []): string
    {
        return trim((string) shell_exec(self::format($command, $attributes)));
    }

    /**
     * Run command on System
     *
     * @param $command
     * @param $attributes
     * @return string
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public static function runSystem($command, $attributes = []): string
    {
        return trim((string) rootSystem()->runCommand(self::format($command, $attributes)));
    }
}
