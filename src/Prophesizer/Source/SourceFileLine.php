<?php

namespace Prophesizer\Source;

/**
 * Class SourceFileLine
 */
class SourceFileLine
{
    /** @var SourceFile */
    private $sourceFile;

    /** @var string */
    private $content;

    /** @var string */
    private $className;

    /** @var string */
    private $prophecyVariable;

    /** @var int */
    private $lineIndex;

    /**
     * SourceFileLine constructor.
     * @param SourceFile $sourceFile
     * @param int        $lineIndex
     * @param string     $content
     * @param string     $className
     * @param string     $prophecyVariable
     */
    public function __construct(SourceFile $sourceFile, $lineIndex, $content, $className, $prophecyVariable)
    {
        $this->sourceFile = $sourceFile;
        $this->content = $content;
        $this->className = $className;
        $this->prophecyVariable = $prophecyVariable;
        $this->lineIndex = $lineIndex;
    }

    /**
     * @param string     $lineContent
     * @param SourceFile $sourceFile
     * @param int        $lineIndex
     * @return SourceFileLine|null
     */
    public static function create($lineContent, SourceFile $sourceFile, $lineIndex)
    {
        $trimmedLine = trim($lineContent);
        if ($trimmedLine && '///' === substr($trimmedLine, -3)) {
            $pattern = '#\s*(\$[a-z0-9_]+)\s*=.+prophesize\([\'"](.+)[\'"]\);#i';
            if (preg_match($pattern, $trimmedLine, $matches)) {
                $type = '\\' . ltrim($matches[2], '\\');
                $prophecyVariable = $matches[1];
                if (interface_exists($type, true) || class_exists($type, true)) {
                    return new SourceFileLine($sourceFile, $lineIndex, $lineContent, $type, $prophecyVariable);
                }
                echo "class|interface fail";
            }
        }

        return null;
    }

    /**
     * @return SourceFile
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getProphecyVariable()
    {
        return $this->prophecyVariable;
    }

    /**
     * @return int
     */
    public function getLineIndex()
    {
        return $this->lineIndex;
    }
}
