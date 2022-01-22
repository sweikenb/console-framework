<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Resolver;

abstract class AbstractSettingsResolver
{
    protected function getEnvSettingsVar(): string
    {
        return 'APP_SETTINGS';
    }

    protected function getPathsToCheck(string $projectRoot): array
    {
        return [
            $projectRoot . '/settings.yaml',
            $projectRoot . '/settings.yaml.dist',
        ];
    }

    public function getFilepath(string $projectRoot): ?string
    {
        $filepath = getenv($this->getEnvSettingsVar()) ?: null;
        if ($filepath) {
            return $filepath;
        }

        foreach ($this->getPathsToCheck($projectRoot) as $filepath) {
            if (file_exists($filepath)) {
                return $filepath;
            }
        }

        return null;
    }
}
