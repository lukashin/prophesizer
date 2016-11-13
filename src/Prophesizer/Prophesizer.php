<?php

namespace Prophesizer;

use Prophesizer\Source\SourceFile;

/**
 * Class Prophesizer
 */
class Prophesizer
{
    /** @var string */
    private $projectDir;

    /** @var string */
    private $sourcePath;

    /** @var SourceFile */
    private $sourceFile;

    /**
     * Prophesizer constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Prophesizer entrypoint
     */
    public function run()
    {
        $this->sourceFile = new SourceFile($this->sourcePath);
        if ($this->sourceFile->processible()) {
            $this->sourceFile->setAutoloaderPath($this->getAutoloaderPath($this->projectDir));
            $this->sourceFile->process();
        }
    }

    /**
     * @param string $projectDir
     * @return string
     */
    private function getAutoloaderPath($projectDir)
    {
        return $projectDir.'/vendor/autoload.php';
    }

    /**
     * Gets initial arguments for prophesizer. If no required args provided writes error to STDERR
     */
    private function init()
    {
        $sourcePath = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
        $projectDir = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null;
        if (!$sourcePath || !$projectDir) {
            fwrite(STDERR,
                'You must setup PHPStorm file watcher for prophesizer!' . PHP_EOL .
                'Setup instructions: https://github.com/prophesizer' . PHP_EOL
            );
            exit(1);
        }
        $this->sourcePath = $sourcePath;
        $this->projectDir = $projectDir;
    }
}
