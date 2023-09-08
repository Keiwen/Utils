<?php

namespace Keiwen\Utils\Parsing;


class DirectoryParser
{

    protected $baseDirectory;
    protected $pathSeparator;
    protected $filesInDirectory = array();
    protected $filesCount = 0;

    public function __construct(string $baseDirectory, string $pathSeparator = '/')
    {
        $this->pathSeparator = $pathSeparator;
        $baseDirectory = trim($baseDirectory, $this->pathSeparator) . $this->pathSeparator;
        if(!is_dir($baseDirectory)) {
            throw new \LogicException(sprintf('Directory %s not found, or is not a directory', $baseDirectory));
        }
        $this->baseDirectory = $baseDirectory;
        $this->parsedirectory('');
    }

    /**
     * ITERATIVE METHOD
     * @param string $directory
     */
    protected function parseDirectory(string $directory)
    {
        $currentDir = $this->baseDirectory . $directory;
        $handle = opendir($currentDir);
        while (($fileOrFolder = readdir($handle)) !== false) {
            $fullPath = $currentDir . $fileOrFolder;
            $relPath = $directory . $fileOrFolder;
            if (!is_dir($fullPath)) {
                //it's a file
                if ($this->doesConsiderFile($directory, $fileOrFolder)) {
                    $this->registerFile($directory, $fullPath);
                }
            } else if (!in_array($fileOrFolder, array('.', '..'))) {
                //be sure to ignore . and .., current and parent directories!
                //now we are processing a sub-directory
                if ($this->doesConsiderFolder($directory, $fileOrFolder)) {
                    $this->parseDirectory($relPath . $this->pathSeparator);
                }
            }
        }
        closedir($handle);
    }


    /**
     * override this method to filter on files
     * @param string $directory
     * @param string $filename
     * @return bool
     */
    protected function doesConsiderFile(string $directory, string $filename): bool
    {
        return true;
    }

    /**
     * override this method to filter on folders
     * @param string $directory
     * @param string $foldername
     * @return bool
     */
    protected function doesConsiderFolder(string $directory, string $foldername): bool
    {
        return true;
    }


    /**
     * @param string $directory
     * @param string $file
     */
    protected function registerFile(string $directory, string $file)
    {
        if (!isset($this->filesInDirectory[$directory])) {
            $this->filesInDirectory[$directory] = array();
        }
        $this->filesInDirectory[$directory][] = $file;
        $this->filesCount++;
    }

    /**
     * @param string $directory
     * @return string[] files in directory
     */
    public function getFileFromDirectory(string $directory): array
    {
        return $this->filesInDirectory[$directory] ?? array();
    }

    /**
     * @return array directory => list of files in that directory
     */
    public function getAllFiles(): array
    {
        return $this->filesInDirectory;
    }

    /**
     * @return int
     */
    public function getFilesCount(): int
    {
        return $this->filesCount;
    }


    /**
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    /**
     * @return string
     */
    public function getPathSeparator(): string
    {
        return $this->pathSeparator;
    }


    /**
     * @param string $filename
     * @return string|null
     */
    public static function getFileExtension(string $filename): ?string
    {
        $dotIndex = strrpos($filename, ".");
        if ($dotIndex !== false) {
            return substr($filename, $dotIndex + 1);
        }
        return null;
    }



}
