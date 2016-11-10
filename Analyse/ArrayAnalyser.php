<?php

namespace Keiwen\Utils\Analyse;


class ArrayAnalyser
{

    /** array values considered as unique (if not, some data could be lost) */
    const UNIQUE_SORT = 'unique';
    /** array values could be shared, elements with same values will be returned in same order as received */
    const NON_UNIQUE_SORT_VALUE = 'value';
    /** count number of element in element field, elements with same number will be returned in same order as received  */
    const NON_UNIQUE_SORT_COUNT = 'count';


    /**
     * @param array $array
     * @param bool  $ifEmpty
     * @return bool
     */
    public static function hasStringKeys(array $array, bool $ifEmpty = false)
    {
        if(empty($array)) return $ifEmpty;
        return !empty(array_filter(array_keys($array), 'is_string'));
    }


    /**
     * @param array $array
     * @param bool  $ifEmpty
     * @return bool
     */
    public static function isSequential(array $array, bool $ifEmpty = true)
    {
        if(empty($array)) return $ifEmpty;
        //check if keys equal to ordered numbers
        return array_keys($array) === range(0, count($array) - 1);
    }


    /**
     * @param array  $array
     * @param bool   $ifEmpty
     * @param bool   $classFamily false to detect same class strictly, true to allow subclasses
     * @param string $type reference to type detected
     * @return bool
     */
    public static function hasHomogeneousElements(array $array, bool $ifEmpty = true, bool $classFamily = false, string &$type = '')
    {
        if(empty($array)) return $ifEmpty;
        //detect first element type. All other element should be the same
        $first = array_shift($array);
        switch(true) {
            case is_object($first): $type = get_class($first); break;
            case is_array($first): $type = 'array'; break;
            case is_float($first): $type = 'float'; break;
            case is_null($first): $type = 'null'; break;
            case is_string($first): $type = 'string'; break;
            case is_bool($first): $type = 'boolean'; break;
            //if cannot determine type, return false
            default: return false;
        }

        $classesFound = array();
        foreach($array as $element) {
            switch($type) {
                case 'array': if(!is_array($element)) return false; break;
                case 'float': if(!is_float($element)) return false; break;
                case 'null': if(!is_null($element)) return false; break;
                case 'string': if(!is_string($element)) return false; break;
                case 'boolean': if(!is_bool($element)) return false; break;
                default:
                    if(!is_object($element)) return false;
                    //check for strict class
                    if(!$classFamily && get_class($element) != $type) return false;
                    //list all classes found
                    $classesFound[] = get_class($element);
                    break;
            }
        }

        if($classFamily) {
            //with all classes found, check inheritances
            //remove duplicates
            $classesFound = array_unique($classesFound);
            $classesFoundNumber = count($classesFound);
            //if only one class (or empty if type is not a class), return true
            if($classesFoundNumber <= 1) return true;
            $loop = 0;
            //process all classes and remove subclasses
            //add a limit equal to initial number of classes
            while(count($classesFound) > 1 && $loop < $classesFoundNumber) {
                //handle first class in queue
                $handleClass = array_shift($classesFound);
                foreach($classesFound as $index => $class) {
                    //remove classes in queue that are subclasses
                    if(is_subclass_of($class, $handleClass)) {
                        unset($classesFound[$index]);
                    }
                }
                //put handled at the end, check if subclass of remaining classes
                array_push($classesFound, $handleClass);
                $loop++;
            }
            //if more than on class left, then no subclass link found, return false
            if(count($classesFound) > 1) return false;
            $type = reset($classesFound);
        }

        return true;

    }


    /**
     * If object found from a parent class, will return false.
     * @param array  $array
     * @param string $className
     * @param bool   $allowSub
     * @param bool   $ifEmpty
     * @return bool
     */
    public static function isFilledOfObjectsFromClass(array $array, string $className, bool $allowSub = true, bool $ifEmpty = false)
    {
        if(empty($array)) return $ifEmpty;
        $parentClassFound = '';
        $homogeneous = static::hasHomogeneousElements($array, $ifEmpty, $allowSub, $parentClassFound);
        return $homogeneous && $parentClassFound == $className;
    }



    /**
     * @param array  $array array keys are preserved
     * @param string $field can contains a dot char (only one) to check for nested field ("mainField.subField")
     * @param string $sortType
     */
    public static function sortByField(array &$array, string $field, string $sortType = self::NON_UNIQUE_SORT_VALUE)
    {
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
        $array = array_combine($arrayKeysSort, $arraySort);
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
