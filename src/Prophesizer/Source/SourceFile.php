<?php

namespace Prophesizer\Source;

use Prophesizer\Generator\ProphecyGenerator;

class SourceFile
{
    /** @var string */
    private $autoloaderPath;

    /** @var string */
    private $path;

    /** @var  string[] */
    private $lines;

    /**
     * SourceFile constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /** @return bool */
    public function processible()
    {
        return $this->processingAllowed($this->path);
    }

    /** @param string $autoloaderPath */
    public function setAutoloaderPath($autoloaderPath)
    {
        $this->autoloaderPath = $autoloaderPath;
    }

    /** @return void */
    public function process()
    {
        $this->initAutoloader();
        $sourceFileLine = $this->getLineToProphesize($this->path);
        if ($sourceFileLine) {
            $prophecyGenerator = new ProphecyGenerator($sourceFileLine);

            $phpMethodCode = $prophecyGenerator->getDoubleCode();
            $getDoubleMethodName = $prophecyGenerator->getDoubleMethodName();

            $this->addMethod($phpMethodCode);
            $this->replaceLineWithGetDoubleMethodCall($sourceFileLine, $getDoubleMethodName);
            $this->flush($this->lines, $this->path);

            echo $this->path;
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function initAutoloader()
    {
        $autoloaderPath = $this->autoloaderPath;
        if (file_exists($autoloaderPath) && is_file($autoloaderPath) && is_readable($autoloaderPath)) {
            /** @noinspection PhpIncludeInspection */
            require_once($autoloaderPath);
        } else {
            throw new \RuntimeException(sprintf('Autoloader not found in %s', $autoloaderPath));
        }
    }

    /**
     * @param string $path
     * @return SourceFileLine|null
     */
    private function getLineToProphesize($path)
    {
        $this->lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($this->lines as $idx => $line) {
            if ($sourceFileLine = SourceFileLine::create($line, $this, $idx)) {
                return $sourceFileLine;
            }
        }

        return null;
    }

    /**
     * @param string $sourcePath
     * @return bool
     */
    private function processingAllowed($sourcePath)
    {
        return ($sourcePath
            && file_exists($sourcePath)
            && ('Test.php' === substr($sourcePath, -8))
            && is_file($sourcePath)
            && is_readable($sourcePath)
            && is_writable($sourcePath)
        );
    }

    /**
     * @param string $phpMethodCode
     */
    private function addMethod($phpMethodCode)
    {
        $lastLineIndex = count($this->lines) - 1;
        for ($idx = $lastLineIndex; $lastLineIndex > 1; $idx--) {
            $content = trim($this->lines[$idx]);
            if ($content && '}' === $content) {
                array_splice($this->lines, $idx, 0, PHP_EOL.$phpMethodCode);
                break;
            }
        }
    }

    /**
     * @param SourceFileLine $sourceFileLine
     * @param string $getDoubleMethodName
     */
    private function replaceLineWithGetDoubleMethodCall(SourceFileLine $sourceFileLine, $getDoubleMethodName)
    {
        $padding = str_repeat(' ', 4*2);
        $newLineContent = $sourceFileLine->getProphecyVariable().' = $this->'.$getDoubleMethodName.'();'
            .' // todo: edit predictions!';

        $newLines = [
            $padding.'// '.trim(rtrim($sourceFileLine->getContent(), "/")),
            $padding.$newLineContent,
        ];
        array_splice($this->lines, $sourceFileLine->getLineIndex(), 1, $newLines);
    }

    /**
     * @param string[] $lines
     * @param string $path
     * @return int|bool
     */
    private function flush(array $lines, $path)
    {
        $content = trim(implode(PHP_EOL, $lines)).PHP_EOL;

        return file_put_contents($path, $content, LOCK_EX);
    }
}
