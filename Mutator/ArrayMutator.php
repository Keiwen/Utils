<?php

namespace Keiwen\Utils\Mutator;


class ArrayMutator
{

    /** array values considered as unique (if not, some data could be lost) */
    public const UNIQUE_SORT = 'unique';
    /** array values could be shared, elements with same values will be returned in same order as received */
    public const NON_UNIQUE_SORT_VALUE = 'value';
    /** count number of element in element field, elements with same number will be returned in same order as received  */
    public const NON_UNIQUE_SORT_COUNT = 'count';

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $fieldName can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @param bool   $reverse
     * @param string $sortType
     * @return array
     */
    public function sortByField(string $fieldName, bool $reverse = false, string $sortType = self::NON_UNIQUE_SORT_VALUE)
    {
        $arraySort = array();
        //use same process on keys to preserve it
        $arrayKeysSort = array();
        foreach($this->data as $key => $element) {
            //get value for each element
            $value = $this->getValueFromElementField($fieldName, $element);
            if($value === null) $value = 0;
            if(is_object($value) || (is_array($value) && $sortType != self::NON_UNIQUE_SORT_COUNT)) {
                //invalid type, should be scalar, or array in count sort type
                $value = 0;
            }
            switch($sortType) {
                case self::UNIQUE_SORT:
                    //all values should be unique, use it as direct key
                    $arraySort[$value] = $element;
                    $arrayKeysSort[$value] = $key;
                    break;
                case self::NON_UNIQUE_SORT_VALUE:
                    //value can be shared, store element as a piece of value
                    $arraySort[$value][] = $element;
                    $arrayKeysSort[$value][] = $key;
                    break;
                case self::NON_UNIQUE_SORT_COUNT:
                    //get size of value, that of course could be shared, store element as a piece of size
                    //be sure to have an array before counting size
                    if(!is_array($value)) $value = array();
                    $arraySort[count($value)][] = $element;
                    $arrayKeysSort[count($value)][] = $key;
                    break;
            }
        }
        //sort by key
        ksort($arraySort);
        ksort($arrayKeysSort);
        if($reverse) {
            $arraySort = array_reverse($arraySort);
            $arrayKeysSort = array_reverse($arrayKeysSort);
        }
        if($sortType != self::UNIQUE_SORT) {
            //if non-unique, $arraySort is compacted, un-pile it
            $temp = array();
            foreach($arraySort as $sort) {
                foreach($sort as $element) {
                    $temp[] = $element;
                }
            }
            $arraySort = $temp;
            $temp = array();
            foreach($arrayKeysSort as $sort) {
                foreach($sort as $key) {
                    $temp[] = $key;
                }
            }
            $arrayKeysSort = $temp;
        }

        //combine back keys with element
        return array_combine($arrayKeysSort, $arraySort);
    }


    /**
     * From several level array, flatten to one level array (iterative)
     * Keys are concatenated and could overwrite existing keys
     * array('a' => array('b' => 1, 'c' => 2));
     * will become
     * array('a_b' => 1, 'a_c' => 2);
     * @param string $prefixKey
     * @param string $keySeparator
     * @return array
     */
    public function flattenArray(string $prefixKey = '', string $keySeparator = '_')
    {
        $flatten = $this->data;
        return $this->flattenArrayIteration($flatten, $prefixKey, $keySeparator);
    }

    /**
     * @param array $data
     * @param string $prefixKey
     * @param string $keySeparator
     * @return array
     */
    protected function flattenArrayIteration(array $data, string $prefixKey = '', string $keySeparator = '_')
    {
        foreach($data as $key => $value) {
            //generate new key and remove old one
            $newKey = $prefixKey . $key;
            unset($data[$key]);
            if(is_array($value)) {
                //if is array, iterate
                $value = $this->flattenArrayIteration($value, $newKey . $keySeparator, $keySeparator);
                //from resulting array, get all keys and put in parent array
                foreach($value as $nk => $v) {
                    $data[$nk] = $v;
                }
            } else {
                //keep value at this level
                $data[$newKey] = $value;
            }
        }
        return $data;
    }

    /**
     * add prefix and/or suffix to array keys
     * @param array  $data
     * @param string $prefix
     * @param string $suffix
     * @return array
     */
    public function amendKeys(string $prefix = '', string $suffix = '')
    {
        $newData = array();
        foreach($this->data as $key => $value) {
            $newData[$prefix.$key.$suffix] = $value;
        }
        return $newData;
    }


    /**
     * Remove value from array
     * @param array $data
     * @param mixed $value
     * @return bool removed or not found
     */
    public static function removeByValue(array &$data, $value)
    {
        $key = array_search($value, $data);
        if($key === false) return false;
        unset($data[$key]);
        return true;
    }


    /**
     * @param string $fieldName
     * @param $element
     * @return null|mixed
     */
    protected function getValueFromElementField(string $fieldName, $element)
    {
        if(strpos($fieldName, '.') === false) {
            //simple attribute
            return $element[$fieldName] ?? null;
        }

        //composed attribute
        $attributeParts = explode('.', $fieldName);
        $value = $element;
        foreach($attributeParts as $attributePart) {
            if(isset($value[$attributePart])) {
                $value = $value[$attributePart];
            } else {
                return null;
            }
        }
        return $value;
    }


    /**
     * given an array of homogeneous elements, extract subfield values of each element
     * @param string $fieldName can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @return array
     */
    public function extractSubFieldList(string $fieldName)
    {
        $subFieldList = array();
        foreach($this->data as $key => $element) {
            //get value for each element
            $subFieldList[$key] = $this->getValueFromElementField($fieldName, $element);
        }
        return $subFieldList;
    }


    /**
     * given an array of homogeneous elements, extract subfields values of each element
     * @param array $fieldNames list of field names to be extracted. Can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @param array $newFieldNames list of corresponding field names to replace original one. Can be empty to preserve names
     * @return array
     */
    public function extractMultipleSubField(array $fieldNames, array $newFieldNames = array())
    {
        if(empty($newFieldNames)) $newFieldNames = $fieldNames;
        $extracted = array();
        foreach($this->data as $key => $element) {
            $newElement = array();
            foreach($fieldNames as $index => $fieldName) {
                $newFieldName = empty($newFieldNames[$index]) ? $fieldName : $newFieldNames[$index];
                $newElement[$newFieldName] = $this->getValueFromElementField($fieldName, $element);
            }
            $extracted[$key] = $newElement;
        }
        return $extracted;
    }


    /**
     * given an array as object, extract specific 'attributes' or subfields
     * If only one attribute specified, return direct value
     * @param string[] $fieldNames can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @param string[] $newFieldNames list of corresponding field names to replace original one. Can be empty to preserve names
     * @return array
     */
    public function extractAttributes(array $fieldNames, array $newFieldNames = array())
    {
        if(empty($newFieldNames)) $newFieldNames = $fieldNames;
        $attributeList = array();
        foreach($fieldNames as $index => $fieldName) {
            $newFieldName = empty($newFieldNames[$index]) ? $fieldName : $newFieldNames[$index];
            $attributeList[$newFieldName] = $this->getValueFromElementField($fieldName, $this->data);
        }
        if(count($fieldNames) === 1) {
            $attributeList = $attributeList[$newFieldName];
        }
        return $attributeList;
    }


    /**
     * Extract elements when field equal a given value
     * @param string $fieldName can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @param $value
     * @param bool $strict true to check with ===, false for == (1 = '1' = true)
     * @return array
     */
    public function searchByField(string $fieldName, $value, bool $strict = true)
    {
        $found = array();
        foreach($this->data as $key => $element) {
            //get value for each element
            $dataValue = $this->getValueFromElementField($fieldName, $element);
            $condition = $strict ? ($dataValue === $value) : ($dataValue == $value);
            if ($condition) {
               $found[$key] = $element;
            }
        }
        return $found;
    }


    /**
     * Extract first element when field equal a given value
     * @param string $fieldName can contains dot char (can be multiple) to check for nested field ("mainField.subField")
     * @param $value
     * @param bool $strict true to check with ===, false for == (1 = '1' = true)
     * @return mixed
     */
    public function findByField(string $fieldName, $value, bool $strict = true)
    {
        $found = $this->searchByField($fieldName, $value, $strict);
        return empty($found) ? null : reset($found);
    }


    /**
     * shuffle array, preserving keys
     * @param array $data
     * @return array shuffled
     */
    public static function shufflePreservingKeys(array $data)
    {
        $keys = array_keys($data);
        shuffle($keys);
        $shuffled = array();
        foreach ($keys as $key) {
            $shuffled[$key] = $data[$key];
        }
        return $shuffled;
    }

    /**
     * @param array $data
     * @param int $part number of part, must be 1 or greater
     * @return array one value per part, each part containing array of resulting element (keys are preserved)
     */
    public static function deal(array $data, int $part): array
    {
        if ($part < 1) return array();
        if ($part == 1) {
            return array(0 => $data);
        }
        $deal = array();
        $currentPart = 0;
        $initialKeys = array_keys($data);
        $elementCount = 0;
        while(!empty($data)) {
            $key = $initialKeys[$elementCount];
            $element = array_shift($data);
            $deal[$currentPart][$key] = $element;
            $currentPart++;
            $elementCount++;
            //if all parts filled, start over
            if($currentPart >= $part) {
                $currentPart = 0;
            }
        }
        return $deal;
    }

}
