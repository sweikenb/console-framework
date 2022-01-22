<?php /** @noinspection PhpNeverTypedFunctionReturnViolationInspection */
declare(strict_types=1);

namespace Sweikenb\ConsoleFramework;

use Exception;
use Pimple\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleKernel
{
    private Container $container;
    private Application $application;

    public function __construct(
        private string $appName,
        private string $appVersion,
        private string $configDir,
        private ?string $settingsFile = null
    ) {
        $this->container = new Container(['version' => $this->appVersion]);
        $this->application = new Application($this->appName, $this->appVersion);
    }

    public function handle(): never
    {
        $this->application->setCatchExceptions(true);
        $this->application->setAutoExit(false);

        $input = new ArgvInput();
        $output = new ConsoleOutput();

        try {
            $bootstrap = new BootstrapProcessor($this->container, $this->application, $this->configDir);
            $bootstrap->execute($this->settingsFile);
            $exitCode = $this->application->run($input, $output);
        }
        catch (Exception $e) {
            $exitCode = 1;
            $output->getErrorOutput()->writeln(
                sprintf(
                    "Bootstrap Error: %s (%s:%s)", $e->getMessage(),
                    $e->getFile(), $e->getLine()
                )
            );
        }

        exit($exitCode);
    }
}