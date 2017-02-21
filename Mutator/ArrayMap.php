<?php

namespace Keiwen\Utils\Mutator;


/**
 * Class ArrayMap
 * Convert associative array from a source structure to a destination structure
 * Designed to be extended. Change attribute to configure your map, then create methods calling protected map methods
 * @package Keiwen\Utils\Mutator
 */
class ArrayMap
{

    /** @var array Define fields to keep between source and destination */
    protected static $whiteListFields = array();
    /** @var bool true to keep all fields between source and destination without filling white list */
    protected static $whiteAllFields = true;
    /** @var array Define fields to remove between source and destination, only if whiteallfield true */
    protected static $blackListFields = array();
    /** @var array associative array with source as keys and destination as value */
    protected static $mapFields = array();
    /** @var bool default value if no correspondance found between source and destination */
    protected static $defaultFieldValue = false;


    /**
     * Convert data from source to destination or destination to source
     * @param array $fromData
     * @param bool  $reverse
     * @return array
     */
    protected static function convert(array $fromData, $reverse = false)
    {
        $toData = array();
        if(static::$whiteAllFields) {
            $toData = $fromData;
            foreach(static::$blackListFields as $blackField) {
                unset($toData[$blackField]);
            }
        } else {
            foreach(static::$whiteListFields as $whiteField) {
                $toData[$whiteField] = isset($fromData[$whiteField]) ? $fromData[$whiteField] : static::$defaultFieldValue;
            }
        }

        $map = static::$mapFields;
        if($reverse) $map = array_flip($map);
        foreach($map as $fromField => $toField) {
            $toData[$toField] = isset($fromData[$fromField]) ? $fromData[$fromField] : static::$defaultFieldValue;
        }
        return $toData;
    }

    /**
     * Convert source data to destination data
     * @param array  $sourceData
     * @return array destination data
     */
    protected static function mapForward(array $sourceData)
    {
        return static::convert($sourceData);
    }

    /**
     * Convert destination data to source data
     * @param array  $destinationData
     * @return array source data
     */
    protected static function mapBackward(array $destinationData)
    {
        return static::convert($destinationData, true);
    }



}
