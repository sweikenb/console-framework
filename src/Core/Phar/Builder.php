<?php declare(strict_types=1);

namespace Sweikenb\ConsoleFramework\Core\Phar;

use Phar;
use Symfony\Component\Console\Output\OutputInterface;

class Builder
{
    private string $targetName;
    private ?Phar $phar = null;

    public function __construct(
        private OutputInterface $output,
        private string $filename,
        private string $sourceDir,
        private string $targetDir,
        private bool $debugging = false
    ) {
        $this->targetName = $this->targetDir . DIRECTORY_SEPARATOR . $this->filename;
    }

    private function debug(string $msg): void
    {
        if ($this->debugging) {
            $this->output->writeln($msg);
        }
    }

    public function removeOldBuild(): self
    {
        if (file_exists($this->targetName) && !is_dir($this->targetName)) {
            unlink($this->targetName);
        }
        return $this;
    }

    public function build(string $cli, array $ignoredPaths = [], array $ignoredFileNames = []): self
    {
        // debug
        $this->debug("Start building executable. This might take some time, please wait ...");
        $this->debug(str_repeat('-', 76));

        // create phar
        $pharAlias = basename($this->filename);
        $this->phar = new Phar($this->targetName, 0, $pharAlias);
        $this->phar->setSignatureAlgorithm(Phar::SHA256);
        $this->phar->compress(Phar::GZ);

        // start buffering
        $this->phar->startBuffering();

        // normalize ignored-array
        foreach ($ignoredPaths as &$path) {
            $path = str_replace($this->sourceDir, '', $path);
            if (DIRECTORY_SEPARATOR !== mb_substr($path, 0, 1)) {
                $path = DIRECTORY_SEPARATOR . $path;
            }
        }

        // indexing source
        $this->indexSourceDir($this->sourceDir, $ignoredPaths, $ignoredFileNames);
        $this->debug("-> <info>Indexing done</info>");
        $this->debug(str_repeat('-', 76));

        // normalize entry files
        $cli = str_replace($this->sourceDir, '', $cli);
        $this->debug(sprintf("-> Setting entry-point for CLI-execution to <info>'%s'</info>", $cli));

        // set entry-points
        $this->phar->setStub(
            implode("\n", [
                "#!/usr/bin/env php",
                "<?php",
                "extension_loaded('phar') || die('This executable requires the PHAR module of PHP enabled.');",
                "Phar::mapPhar();",
                sprintf("require 'phar://%s/%s';", $this->filename, $cli),
                "__HALT_COMPILER();",
            ])
        );
        $this->phar->stopBuffering();

        return $this;
    }

    public function makeExecutable(): self
    {
        if ($this->phar) {
            passthru(sprintf('chmod +x %s', escapeshellarg($this->targetName)), $status);
            if ($status !== 0) {
                $this->debug("-> <error>Making target executable failed</error>");
            }
            else {
                $this->debug("-> Making target executable: <info>OK</info>");
            }
        }
        else {
            $this->debug("-> <error>Can't make file as executable. Target has not been built yet!</error>");
        }

        return $this;
    }

    protected function indexSourceDir(string $dir, array $ignored, array $ignoredFileNames)
    {
        $h = opendir($dir);
        while ($row = readdir($h)) {

            // skipp relative dirs
            if (in_array($row, ['.', '..'])) {
                continue;
            }

            // define current path
            $path = $dir . DIRECTORY_SEPARATOR . $row;
            $pathRel = str_replace($this->sourceDir, '', $path);

            // skipp explicit filenames (e.g. ".git", ".idea", ...)
            if (in_array($row, $ignoredFileNames)) {
                $this->debug(sprintf("-> <comment>SKIPPING %s</comment>", $pathRel));
                continue;
            }

            // process path
            if (is_dir($path)) {
                if (in_array($pathRel, $ignored)) {
                    $this->debug(sprintf("-> <comment>IGNORING %s</comment>", $pathRel));
                    continue;
                }

                $this->indexSourceDir($path, $ignored, $ignoredFileNames);
            }
            else {
                if (in_array($pathRel, $ignored)) {
                    $this->debug(sprintf("-> <comment>IGNORING %s</comment>", $pathRel));
                }
                else {
                    $this->phar[$pathRel] = file_get_contents($path);
                }
            }
        }
        closedir($h);
    }
}
