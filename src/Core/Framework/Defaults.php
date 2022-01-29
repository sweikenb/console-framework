<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Core\Framework;

use Phar;
use Sweikenb\ConsoleFramework\Command\CompileCommand;
use Sweikenb\Library\Pcntl\Api\ProcessManagerInterface;
use Sweikenb\Library\Pcntl\ProcessManager;

final class Defaults
{
    const SERVICE_PROCESS_MANAGER = 'process.manager';
    const SERVICE_EVENT_DISPATCHER = 'event.dispatcher';

    public static function getCoreContracts(): array
    {
        $contracts = [];

        // PCNTL process manager available?
        if (class_exists(ProcessManager::class)) {
            $contracts[ProcessManagerInterface::class] = ['class' => ProcessManager::class];
        }

        return $contracts;
    }

    public static function getCoreServices(): array
    {
        $services = [];

        // PCNTL process manager available?
        if (class_exists(ProcessManager::class)) {
            $services[self::SERVICE_PROCESS_MANAGER] = ['class' => ProcessManagerInterface::class];
        }

        return $services;
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