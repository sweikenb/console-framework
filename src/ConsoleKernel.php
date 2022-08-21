<?php /** @noinspection PhpNeverTypedFunctionReturnViolationInspection */
declare(strict_types=1);

namespace Sweikenb\ConsoleFramework;

use Exception;
use Pimple\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleKernel
{
    protected readonly Container $container;
    protected readonly Application $application;

    public function __construct(
        private readonly string $appName,
        private readonly string $appVersion,
        private readonly string $configDir,
        private readonly ?string $settingsFile = null
    ) {
        $this->container = new Container(['version' => $this->appVersion]);
        $this->application = new Application($this->appName, $this->appVersion);
    }

    public function configure(SymfonyStyle $io, InputInterface $input, OutputInterface $output): void
    {
        $this->application->setCatchExceptions(true);
        $this->application->setAutoExit(false);
    }

    public function handle(): never
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        $io = new SymfonyStyle($input, $output);

        $this->configure($io, $input, $output);

        try {
            $bootstrap = new BootstrapProcessor($this->container, $this->application, $this->configDir);
            $bootstrap->execute($this->settingsFile);
            $exitCode = $this->application->run($input, $output);
        } catch (Exception $e) {
            $exitCode = 1;
            $output->getErrorOutput()->writeln(
                sprintf(
                    "Bootstrap Error: %s (%s:%s)",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
        }

        exit($exitCode);
    }
}
