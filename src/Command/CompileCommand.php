<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Command;

use Sweikenb\ConsoleFramework\Core\Phar\Builder;
use Sweikenb\ConsoleFramework\Exception\CompilerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends Command
{
    const CMD_NAME = 'core:compile';

    const ARG_NAME = 'name';

    const OPT_EXECUTABLE = 'executable';

    protected function configure(): void
    {
        $this->setName(self::CMD_NAME);
        $this->setDescription('Bundles the application as an executable PHAR-file.');

        $this->addArgument(self::ARG_NAME, InputArgument::OPTIONAL, 'Name of the executable.', 'app.phar');

        $this->addOption(self::OPT_EXECUTABLE, 'x', InputOption::VALUE_NONE, 'Make the PHAR file executable.');
        $this->addOption(self::OPT_EXECUTABLE, 'x', InputOption::VALUE_NONE, 'Make the PHAR file executable.');
    }

    /**
     * @throws \Sweikenb\ConsoleFramework\Exception\CompilerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkPhpDependencies();

        $pharName = $input->getArgument(self::ARG_NAME);
        if (empty($pharName)) {
            throw new CompilerException('Application name can not be empty!');
        }
        if (mb_substr($pharName, -5) !== '.phar') {
            throw new CompilerException('The application name must contain the ".phar" file extension!');
        }

        $ignoredPaths = [];
        $ignoredFileNames = ['.idea', '.git'];

        $sourceDir = realpath($_SERVER['PWD']);
        $targetDir = $sourceDir . DIRECTORY_SEPARATOR . 'build';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $builder = new Builder($output, $pharName, $sourceDir, $targetDir, true);
        $builder
            ->removeOldBuild()
            ->build($_SERVER['SCRIPT_FILENAME'], $ignoredPaths, $ignoredFileNames);

        if ($input->getOption(self::OPT_EXECUTABLE)) {
            $builder->makeExecutable();
        }

        return self::SUCCESS;
    }

    /**
     * @return void
     * @throws \Sweikenb\ConsoleFramework\Exception\CompilerException
     */
    private function checkPhpDependencies(): void
    {
        if (ini_get('phar.readonly')) {
            throw new CompilerException('Please enable phar creation in your php.ini file (phar.readonly = 0)!');
        }
        if (extension_loaded('suhosin')) {
            $whitelist = (string)ini_get('suhosin.executor.include.whitelist');
            if (mb_strpos($whitelist, 'phar') === false) {
                throw new CompilerException(
                    'Please add "phar" to the suhosin executor whitelist in your php.ini file (suhosin.executor.include.whitelist = "phar")!'
                );
            }
        }
    }
}
