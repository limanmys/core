<?php

namespace App\System;

class Command
{

    public static function run($command, $attributes = [], $log = true)
    {
        return trim(server()->run(self::format($command, $attributes), $log));
    }

    public static function runSudo($command, $attributes = [], $log = true)
	{
		return self::run(sudo() . $command, $attributes, $log);
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
		return escapeshellarg($value);
	}
}