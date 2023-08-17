<?php

namespace Keiwen\Utils\ID3;

abstract class ID3AbstractParser
{

    protected $data = array();

    public function __construct(string $tagRawData)
    {
        $this->data = $this->parse($tagRawData);
    }

    public static abstract function getPossibleDataNames(): array;

    protected abstract function parse(string $tagRawData): array;

    protected function parseIntValue($rawValue): int
    {
        return intval(trim($rawValue));
    }

    protected function parseStringValue($rawValue, $encoding = null): string
    {
        if (!$encoding) {
            // Detecting UTF-8 before ISO-8859-1 will cause ASCII strings being tagged as UTF-8, which is fine.
            // However, it will prevent UTF-8 encoded strings from being wrongly decoded twice.
            $encoding = mb_detect_encoding($rawValue, ['Windows-1251', 'Windows-1252', 'KOI8-R', 'UTF-8', 'ISO-8859-1']);
        }
        if($encoding !== 'UTF-8'){
            $rawValue = mb_convert_encoding($rawValue, 'UTF-8', $encoding);
        }
        return trim($rawValue);
    }

    /**
     * @param string $dataName
     * @return bool
     */
    public function isDataDefined(string $dataName): bool
    {
        if (!in_array($dataName, static::getPossibleDataNames())) return false;
        return isset($this->data[$dataName]);
    }

    /**
     * @param string $dataName
     * @param mixed|null $notSetValue value returned if data is not set, null by default
     * @return mixed|null
     */
    public function getData(string $dataName, $notSetValue = null)
    {
        if (!$this->isDataDefined($dataName)) return $notSetValue;
        return $this->data[$dataName];
    }

    public function getAllDatas(): array
    {
        return $this->data;
    }

}
