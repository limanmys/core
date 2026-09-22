<?php

namespace App\Support;

final class ExtensionPackagePath
{
    /**
     * @return array{directory: string, database: string}|null
     */
    public static function resolve(string $extractionDirectory): ?array
    {
        if (is_link($extractionDirectory)) {
            return null;
        }

        $extractionRoot = realpath($extractionDirectory);
        if ($extractionRoot === false || ! is_dir($extractionRoot)) {
            return null;
        }

        $entries = scandir($extractionRoot, SCANDIR_SORT_NONE);
        if ($entries === false) {
            return null;
        }

        $entries = array_values(array_filter(
            $entries,
            static fn (string $entry): bool => $entry !== '.' && $entry !== '..',
        ));

        $packageDirectory = $extractionRoot;
        if (count($entries) === 1) {
            $candidate = $extractionRoot.DIRECTORY_SEPARATOR.$entries[0];
            if (is_link($candidate) || ! is_dir($candidate)) {
                return null;
            }

            $resolvedCandidate = realpath($candidate);
            if (
                $resolvedCandidate === false ||
                ! self::isDescendant($extractionRoot, $resolvedCandidate)
            ) {
                return null;
            }

            $packageDirectory = $resolvedCandidate;
        }

        $database = $packageDirectory.DIRECTORY_SEPARATOR.'db.json';
        if (is_link($database) || ! is_file($database)) {
            return null;
        }

        $resolvedDatabase = realpath($database);
        if (
            $resolvedDatabase === false ||
            ! self::isDescendant($packageDirectory, $resolvedDatabase)
        ) {
            return null;
        }

        return [
            'directory' => $packageDirectory,
            'database' => $resolvedDatabase,
        ];
    }

    private static function isDescendant(string $directory, string $candidate): bool
    {
        return str_starts_with(
            $candidate,
            $directory.DIRECTORY_SEPARATOR,
        );
    }
}
