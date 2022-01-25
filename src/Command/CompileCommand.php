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
    const DEFAULT_EXCLUDE_PATHS = ['vendor/bin'];
    const DEFAULT_SKIP_NAMES = ['.idea', '.git'];

    const CMD_NAME = 'core:compile';

    const ARG_NAME = 'name';

    const OPT_EXECUTABLE = 'executable';
    const OPT_EXCLUDE = 'exclude';
    const OPT_EXCLUDE_FILE = 'exclude-file';
    const OPT_SKIP = 'skip';
    const OPT_SKIP_FILE = 'skip-file';

    protected function configure(): void
    {
        $this->setName(self::CMD_NAME);
        $this->setDescription('Bundles the application as an executable PHAR-file.');

        $this->addArgument(self::ARG_NAME, InputArgument::OPTIONAL, 'Name of the executable.', 'app.phar');

        $this->addOption(self::OPT_EXECUTABLE, 'x', InputOption::VALUE_NONE, 'Make the PHAR file executable.');

        $this->addOption(self::OPT_EXCLUDE, null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filepath to exclude (absolute or relative to project root)');
        $this->addOption(self::OPT_EXCLUDE_FILE, null, InputOption::VALUE_REQUIRED, 'File of paths to exclude, one exclude-path per line');

        $this->addOption(self::OPT_SKIP, null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Folder- or filename to skipp (absolute or relative to project root)');
        $this->addOption(self::OPT_SKIP_FILE, null, InputOption::VALUE_REQUIRED, 'File of folder- or filename to skipp, one skip-path per line');
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

        $excludedPaths = $this->processExcludePaths($input);
        $skippedNames = $this->processSkipNames($input);

        $sourceDir = realpath($_SERVER['PWD']);
        $targetDir = $sourceDir . DIRECTORY_SEPARATOR . 'build';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $builder = new Builder($output, $pharName, $sourceDir, $targetDir, true);
        $builder
            ->removeOldBuild()
            ->build($_SERVER['SCRIPT_FILENAME'], $excludedPaths, $skippedNames);

        if ($input->getOption(self::OPT_EXECUTABLE)) {
            $builder->makeExecutable();
        }

        return self::SUCCESS;
    }

    /**
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

    /**
     * @throws \Sweikenb\ConsoleFramework\Exception\CompilerException
     */
    private function processExcludePaths(InputInterface $input): array
    {
        $excludePaths = $input->getOption(self::OPT_EXCLUDE);
        $excludeFile = $input->getOption(self::OPT_EXCLUDE_FILE);

        // fallback to defaults?
        if (empty($excludePaths) && empty($excludeFile)) {
            return self::DEFAULT_EXCLUDE_PATHS;
        }

        // process exclude file
        if ($excludeFile) {
            if (!file_exists($excludeFile) || is_dir($excludeFile)) {
                throw new CompilerException('Invalid exclude file provided.');
            }
            $excludePaths = array_merge(file($excludeFile), $excludePaths);
        }

        // process single paths provided
        $processed = [];
        foreach ($excludePaths as $excludePath) {
            $excludePath = trim($excludePath);
            if (!empty($excludePath)) {
                $excludePath = realpath($excludePath);
                if ($excludePath) {
                    $processed[] = $excludePath;
                }
            }
        }

        return $processed;
    }

    /**
     * @throws \Sweikenb\ConsoleFramework\Exception\CompilerException
     */
    private function processSkipNames(InputInterface $input): array
    {
        $skipNames = $input->getOption(self::OPT_SKIP);
        $skipFile = $input->getOption(self::OPT_SKIP_FILE);

        // fallback to defaults?
        if (empty($skipNames) && empty($skipFile)) {
            return self::DEFAULT_SKIP_NAMES;
        }

        // process skip file
        if ($skipFile) {
            if (!file_exists($skipFile) || is_dir($skipFile)) {
                throw new CompilerException('Invalid skip file provided.');
            }
            $skipNames = array_merge(file($skipFile), $skipNames);
        }

        // process single names provided
        $processed = [];
        foreach ($skipNames as $skippName) {
            $skippName = trim($skippName);
            if (!empty($skippName)) {
                $processed[] = $skippName;
            }
        }

        return $processed;
    }
}
