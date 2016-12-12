<?php

namespace Keiwen\Utils\Mutator;


use Keiwen\Utils\Analyser\ArrayAnalyser;

class ArrayMutator
{

    /** array values considered as unique (if not, some data could be lost) */
    const UNIQUE_SORT = 'unique';
    /** array values could be shared, elements with same values will be returned in same order as received */
    const NON_UNIQUE_SORT_VALUE = 'value';
    /** count number of element in element field, elements with same number will be returned in same order as received  */
    const NON_UNIQUE_SORT_COUNT = 'count';



    /**
     * @param array  $array array keys are preserved
     * @param string $field can contains a dot char (only one) to check for nested field ("mainField.subField")
     * @param string $sortType
     * @param bool   $forceKeyPreserve
     */
    public static function sortByField(array &$array, string $field, string $sortType = self::NON_UNIQUE_SORT_VALUE, bool $forceKeyPreserve = false)
    {
        $sequential = ArrayAnalyser::isSequential($array);
        $arraySort = array();
        //use same process on keys to preserve it
        $arrayKeysSort = array();
        foreach($array as $key => $element) {
            //get value for each element
            if(strpos($field, '.') === false) {
                //simple attribute
                $value = isset($element[$field]) ? $element[$field] : 0;
            } else {
                //composed attribute
                list($first, $second) = explode('.', $field, 2);
                $value = isset($element[$first][$second]) ? $element[$first][$second] : 0;
            }
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
        $array = ($sequential && !$forceKeyPreserve) ? $arraySort : array_combine($arrayKeysSort, $arraySort);
    }


    /**
     * From several level array, flatten to one level array (iterative)
     * Keys are concatenated and could overwrite existing keys
     * array('one' => array('two' => 2, 'three' => 3));
     * will become
     * array('one_two' => 2, 'one_three' => 3);
     * @param array  $data
     * @param string $prefixKey
     * @param string $keySep
     * @return array
     */
    public static function flattenArray(array $data, string $prefixKey = '', string $keySep = '_')
    {
        foreach($data as $key => $value) {
            //generate new key and remove old one
            $newKey = $prefixKey . $key;
            unset($data[$key]);
            if(is_array($value)) {
                //if is array, iterate
                $value = static::flattenArray($value, $newKey . $keySep, $keySep);
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
    public static function amendKeys(array $data, string $prefix = '', string $suffix = '')
    {
        $newData = array();
        foreach($data as $key => $value) {
            $newData[$prefix.$key.$suffix] = $value;
        }
        return $newData;
    }


    /**
     * Search correspondence between given data element field and reference element.
     * Extract key from corresponding reference found (own key or given field)
     * Set data element key as extracted key
     *
     * If no correspondence, data element is lost
     * Fields value must NOT be empty
     * If correspondence find twice, first element will be erased
     * @param array  $data array of element to convert
     * @param string $dataField field to compare to reference
     * @param array  $ref array of referencing element
     * @param string $refField fill it to use a field to compare to data instead of full element
     * @param string $refKeyField fill it to use a reference field as key instead of element key
     * @return array converted data
     */
    public static function convertKeysWithRefMap(array $data, string $dataField, array $ref, string $refField = '', string $refKeyField = '')
    {
        $newData = array();
        foreach($data as $dataKey => $dataRow) {
            //check that map field is found
            if(empty($dataRow[$dataField])) continue;
            //search in ref for corresponding value
            foreach($ref as $refKey => $refRow) {
                //check that map field is found
                if(!empty($refField) && empty($refRow[$refField])) continue;
                $compareTo = $refField ? $refRow[$refField] : $refRow;
                //check correspondence
                if($compareTo == $dataRow[$dataField]) {
                    //newkey is either fieldKey provided if filled or ref element key
                    $newKey = (empty($refKeyField) || empty($refRow[$refKeyField])) ? $refKey : $refRow[$refKeyField];
                    $newData[$newKey] = $dataRow;
                    break;
                }
            }
            //not found, skip
        }
        return $newData;
    }


}
