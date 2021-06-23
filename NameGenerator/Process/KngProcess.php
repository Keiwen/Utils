<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

abstract class KngProcess
{

    public const PARAMETER_CAPITALIZE = 'capitalize';
    public const PARAMETER_MINIMIZE = 'minimize';
    public const PARAMETER_UCFIRST = 'ucfirst';

    public const PREDEFINED_CHARGROUP_VOWEL = 'vowel';
    public const PREDEFINED_CHARGROUP_CONSONANT = 'consonant';


    /** @var array $rawParameters */
    protected $rawParameters;

    protected $dictionary = array();
    protected $dictionarySize = 0;
    protected $capitalize = false;
    protected $minimize = false;
    protected $ucFirst = false;

    /** @var string[] $conceivableSeparators */
    protected static $conceivableSeparators = array(
        ', ', ',', '; ', ';', ' / ', '/', '|',
    );

    /**
     * KngProcess constructor.
     * Dictionary will contains input values
     * Generation will generate a random term based on inputs
     * Use constants for parameters
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        $this->rawParameters = $parameters;
        if(!empty($parameters[static::PARAMETER_CAPITALIZE])) $this->capitalize = true;
        if(!empty($parameters[static::PARAMETER_MINIMIZE])) $this->minimize = true;
        if(!empty($parameters[static::PARAMETER_UCFIRST])) $this->ucFirst = true;
    }


    /**
     * Adds a single term to the dictionary
     * @param string $term
     */
    public abstract function addToDictionary($term);

    /**
     * Adds multiple terms to the dictionary
     * @param array $terms
     */
    public function addListToDictionary(array $terms)
    {
        foreach($terms as $term) {
            $this->addToDictionary($term);
        }
    }


    /**
     * Generates a random term according to process
     * @return string
     */
    public abstract function generate(): string;

    /**
     * Format term before returning
     * @param string $term
     * @return string
     */
    protected function formatTerm(string $term): string
    {
        if($this->capitalize) return strtoupper($term);
        if($this->minimize) $term = strtolower($term);
        if($this->ucFirst) return ucfirst($term);
        return $term;
    }

    /**
     * Count number of possibilities for term generation
     * @return int|null null if cannot be determined
     */
    public abstract function countPossibilities();

    /**
     * @return bool
     */
    public function isDictionaryEmpty(): bool
    {
        return $this->dictionarySize == 0;
    }

    /**
     * Check if process is valid / ready to generate
     * @return bool
     * @throws KngException empty dictionnary
     */
    public function checkReadyForGeneration(): bool
    {
        if ($this->isDictionaryEmpty()) {
            throw new KngException('Cannot generate: empty dictionary');
        }
        return true;
    }

    /**
     * @param string $key identifying a predefined group, use constants
     * @return array|string group of chars
     */
    public static function getPredefinedCharGroup(string $key)
    {
        switch($key) {
            case static::PREDEFINED_CHARGROUP_VOWEL:
                return 'aeiouy';
            case static::PREDEFINED_CHARGROUP_CONSONANT:
                return 'bcdfghjklmnpqrstvwxz';
            default:
                return array();
        }
    }


    /**
     * Loop among list of conceivable separators and detect first one found.
     * Consider that it is the separator
     * @param string $term
     * @return string
     */
    protected static function detectSeparator(string $term)
    {
        $detected = '';
        foreach(static::$conceivableSeparators as $separator) {
            if(strpos($term, $separator) !== false) {
                $detected = $separator;
                break;
            }
        }
        return $detected;
    }

    /**
     * @param string $term
     * @return string[]
     */
    public static function splitTerm(string $term)
    {
        $separator = static::detectSeparator($term);
        if(empty($separator)) {
            return str_split($term);
        }
        return explode($separator, $term);
    }


    /**
     * @param bool $capitalize
     */
    public function setCapitalize(bool $capitalize)
    {
        $this->capitalize = $capitalize;
    }

    /**
     * @param bool $minimize
     */
    public function setMinimize(bool $minimize)
    {
        $this->minimize = $minimize;
    }

    /**
     * @param bool $ucFirst
     */
    public function setUcFirst(bool $ucFirst)
    {
        $this->ucFirst = $ucFirst;
    }

    /**
     * @param array $terms
     * @return static
     */
    public static function createFromDictionary(array $terms)
    {
        $process = new static();
        $process->addListToDictionary($terms);
        return $process;
    }


}
