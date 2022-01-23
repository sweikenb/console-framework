<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Core\Framework;

use Phar;
use Sweikenb\ConsoleFramework\Command\CompileCommand;

final class Defaults
{
    public static function getCoreContracts(): array
    {
        return [];
    }

    public static function getCoreServices(): array
    {
        return [];
    }

    public static function getCoreCommands(): array
    {
        $commands = [];

        // Only add the compile command if the PHAR module is enabled,
        //  and we are not already running inside a PHAR file.
        if (extension_loaded('phar') && empty(Phar::running())) {
            $commands[CompileCommand::class] = [];
        }

        return $commands;
    }

    public static function getCoreEventListeners(): array
    {
        return [];
    }
}