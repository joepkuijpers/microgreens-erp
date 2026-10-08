<?php

function runtimeStoragePath(string $relativePath = ''): string
{
    $configuredPath = getenv('MICROGREENS_STORAGE_PATH');

    $basePath = (
        is_string($configuredPath) &&
        trim($configuredPath) !== ''
    )
        ? trim($configuredPath)
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';

    $basePath = rtrim($basePath, '/\\');

    if ($relativePath === '') {
        return $basePath;
    }

    $normalisedRelativePath = str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        trim($relativePath, '/\\')
    );

    return $basePath
        . DIRECTORY_SEPARATOR
        . $normalisedRelativePath;
}

function runtimeStorageFile(string $relativePath): string
{
    $filePath = runtimeStoragePath($relativePath);
    $directory = dirname($filePath);

    if (
        !is_dir($directory) &&
        !mkdir($directory, 0775, true) &&
        !is_dir($directory)
    ) {
        throw new RuntimeException(
            'Unable to create runtime storage directory: ' . $directory
        );
    }

    return $filePath;
}
