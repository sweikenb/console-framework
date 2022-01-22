<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Resolver;

abstract class AbstractSettingsResolver
{
    const ENV_SETTINGS_FILEPATH = 'APP_SETTINGS';

    const CHECK_PATHS = [
        '/settings.yaml',
        '/settings.yaml.dist',
    ];

    public static function getFilepath(string $projectRoot): ?string
    {
        $filepath = getenv(self::ENV_SETTINGS_FILEPATH) ?: null;
        if ($filepath) {
            return $filepath;
        }

        foreach (self::CHECK_PATHS as $filepath) {
            $filepath = $projectRoot . $filepath;
            if (file_exists($filepath)) {
                return $filepath;
            }
        }

        return null;
    }
}
