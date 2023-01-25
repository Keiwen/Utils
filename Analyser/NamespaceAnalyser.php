<?php

namespace Keiwen\Utils\Analyser;


class NamespaceAnalyser
{

    protected $namespace;
    protected $composerPath;
    protected $composerAutoload;
    protected $specificDefinedNamespace = array();

    // relative to this file in vendor folders
    const DEFAULT_APP_ROOT = __DIR__ . '/../../../../';
    const DEFAULT_APP_AUTOLOAD = 'psr-4';

    /**
     * to determine namespace's directory on disk, you can use
     * - automated discovery from composer (require composer)
     * - manually adding directory with addDefinedNamespace method
     * @param string $namespace
     * @param string $composerPath default will get app root folder, from vendor subdir
     * @param string $composerAutoload autoload type, default is PSR-4
     */
    public function __construct(string $namespace, string $composerPath = self::DEFAULT_APP_ROOT, string $composerAutoload = self::DEFAULT_APP_AUTOLOAD)
    {
        $this->namespace = $namespace;
        $this->composerPath = $composerPath;
        $this->composerAutoload = $composerAutoload;
    }


    /**
     * @param string $namespace
     * @param string $directory fully qualified path on disk
     * @return $this
     */
    public function addDefinedNamespace(string $namespace, string $directory)
    {
        // ensure ending backslash
        $namespace = trim($namespace, '\\') . '\\';

        $this->specificDefinedNamespace[$namespace] = realpath($directory) . '/';
        return $this;
    }

    /**
     * @return array
     */
    protected function getAutoDefinedNamespaces(): array
    {
        $composerJsonPath = $this->composerPath . 'composer.json';
        if (file_exists($composerJsonPath)) {
            $composerConfig = json_decode(file_get_contents($composerJsonPath), true);
            if (isset($composerConfig['autoload']) && $composerConfig['autoload'][$this->composerAutoload]) {
                return $composerConfig['autoload'][$this->composerAutoload];
            }
        }

        return array();
    }

    /**
     * @return false|string false if not found
     */
    protected function getNamespaceDirectory()
    {
        $definedNamespaces = $this->getAutoDefinedNamespaces();
        $namespaceParts = explode('\\', $this->namespace);
        $undefinedParts = array();
        while ($namespaceParts) {
            //check for all remaining parts of namespace if defined in autoload
            $possibleDefinition = implode('\\', $namespaceParts) . '\\';

            //check first in specifically defines namespaces
            if (array_key_exists($possibleDefinition, $this->specificDefinedNamespace)) {
                // defined! get the corresponding path and add undefined parts previously stocked
                return $this->specificDefinedNamespace[$possibleDefinition] . implode('/', $undefinedParts);
            }

            //not found, try to find automatically
            if (array_key_exists($possibleDefinition, $definedNamespaces)) {
                // defined! get the corresponding path and add undefined parts previously stocked
                return realpath($this->composerPath . $definedNamespaces[$possibleDefinition] . implode('/', $undefinedParts));
            }

            //remove last part of namespace and retry for 'parent'. Stock this undefined 'child' part
            array_unshift($undefinedParts, array_pop($namespaceParts));
        }
        // not found
        return false;
    }


    /**
     * get classes defined in namespace
     * @return array
     */
    public function getClasses(): array
    {
        $dir = $this->getNamespaceDirectory();
        $files = scandir($dir);
        $classesFound = array();
        foreach ($files as $file) {
            if (is_dir($dir . '/' . $file)) continue;
            $possibleClass = $this->namespace . '\\' . str_replace('.php', '', $file);
            if (class_exists($possibleClass)) {
                $classesFound[] = $possibleClass;
            }
        }
        return $classesFound;
    }

    /**
     * get possible child namespaces
     * @return array
     */
    public function getSubNamespaces(): array
    {
        $dir = $this->getNamespaceDirectory();
        $files = scandir($dir);
        $namespacesFound = array();
        foreach ($files as $file) {
            if (!is_dir($dir . '/' . $file)) continue;
            if ($file == '.' || $file == '..') continue;

            $namespacesFound[] = $this->namespace . '\\' . $file;
        }
        return $namespacesFound;
    }

}
