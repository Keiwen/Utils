<?php

namespace Keiwen\Utils\Mutator;


/**
 * Class ArrayMap
 * Convert associative array from a source structure to a destination structure
 * Designed to be extended. Set white/black list to configure your map.
 * You can create conversion methods (naming defined in getConvertFieldMethod())
 * to specifically handle values from source field.
 * For example, when converting 'myField', it will automatically call convertFieldMyField($fromData, $toData) if exists ;
 * otherwise, it will just map data from source to destination.
 *
 * Mapping is designed to have unique correspondences between source and destination.
 * You may have some collapse for common fieldnames. In that case, you may need to create 2 subclass
 * (one for each direction) and throw exceptions on mapBackward()
 * @package Keiwen\Utils\Mutator
 */
abstract class ArrayMap
{


    public function __construct()
    {
    }

    /**
     * Define fields to keep between source and destination. Leave empty to keep all fields
     * @return array
     */
    public abstract function getWhiteListFields(): array;

    /**
     * Define fields to remove between source and destination
     * @return array
     */
    public abstract function getBlackListFields(): array;

    /**
     * associative array with source fieldname as keys and destination fieldname as value
     * @return array
     */

    public abstract function getMapFields(): array;

    /**
     * default value if no correspondence found between source and destination
     * associative array with key = fieldname and value = default value
     * @return mixed
     */
    public abstract function getDefaultFieldValues(): array;

    /**
     * default value if no correspondence found between source and destination
     * and no default field value
     * @return mixed
     */
    public abstract function getGlobalDefaultValue();


    /**
     * Return default value for a specified field, whether defined specifically or global value
     * @param string $fieldname
     * @return mixed
     */
    protected function getDefaultValueForField(string $fieldname)
    {
        $defaultFieldValues = $this->getDefaultFieldValues();
        return $defaultFieldValues[$fieldname] ?? $this->getGlobalDefaultValue();
    }


    /**
     * Define naming of specific convert method
     * @param string $fromField
     * @return string
     */
    protected function getConvertFieldMethod(string $fromField)
    {
        return 'convertField' . ucfirst($fromField);
    }

    /**
     * Convert data from source to destination or destination to source
     * Field order: whitelist, then blacklist, then map list
     * Try to call specific convert method, map fromValue otherwise
     * @param array $fromData
     * @param bool  $reverse
     * @return array
     */
    protected function convert(array $fromData, bool $reverse = false)
    {
        $toData = array();
        // handle white list field
        if(empty($this->getWhiteListFields())) {
            $toData = $fromData;
        } else {
            foreach($this->getWhiteListFields() as $whiteField) {
                $convertFieldMethod = $this->getConvertFieldMethod($whiteField);
                if(method_exists($this, $convertFieldMethod) && isset($fromData[$whiteField])) {
                    $toData = $this->$convertFieldMethod($fromData, $toData);
                } else {
                    $toData[$whiteField] = $fromData[$whiteField] ?? $this->getDefaultValueForField($whiteField);
                }
            }
        }

        //handle blacklist to remove some fields
        foreach($this->getBlackListFields() as $blackField) {
            unset($toData[$blackField]);
        }

        //handle mapped field
        $map = $this->getMapFields();
        if($reverse) $map = array_flip($map);
        foreach($map as $fromField => $toField) {
            $convertFieldMethod = $this->getConvertFieldMethod($fromField);
            if(method_exists($this, $convertFieldMethod) && isset($fromData[$fromField])) {
                $toData = $this->$convertFieldMethod($fromData, $toData);
            } else {
                $toData[$toField] = $fromData[$fromField] ?? $this->getDefaultValueForField($toField);
            }
        }
        return $toData;
    }

    /**
     * Convert source data to destination data
     * @param array  $sourceData
     * @return array destination data
     */
    public function mapForward(array $sourceData)
    {
        return $this->convert($sourceData);
    }

    /**
     * Convert destination data to source data
     * @param array  $destinationData
     * @return array source data
     */
    public function mapBackward(array $destinationData)
    {
        return $this->convert($destinationData, true);
    }



    /**
     * Convert list of source data to destination data
     * @param array  $sourceDataList
     * @return array destination data list (keys preserved)
     */
    public function mapListForward(array $sourceDataList)
    {
        $list = array();
        foreach($sourceDataList as $key => $sourceData) {
            $list[$key] = $this->mapForward($sourceData);
        }
        return $list;
    }

    /**
     * Convert list of destination data to source data
     * @param array  $destinationDataList
     * @return array source data list (keys preserved)
     */
    public function mapListBackward(array $destinationDataList)
    {
        $list = array();
        foreach($destinationDataList as $key => $destinationData) {
            $list[$key] = $this->mapBackward($destinationData);
        }
        return $list;
    }




}
