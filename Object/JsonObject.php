<?php

namespace Keiwen\Utils\Object;


use Keiwen\Utils\Analyser\ArrayAnalyser;
use Keiwen\Utils\Mutator\ArrayMutator;


/**
 * Class JsonObject
 * Generate object from JSON. Can nest similar object.
 * Advised to extends this class and implements specific attributes getter and setter
 * Child class:
 * - should implements specific getter and setter (could use generic get and set defined here)
 * - could redefined nested object (static method includedJsonObjects())
 * - could redefined nested map object (static method includedJsonObjectMapList())
 * - could redefined field to attribute map (static method retrieveFieldToAttributeMap())
 * - could overwrite getDefaultDataAll()
 * Child object could be created from a parent object using the generateFromParent() static method
 */
class JsonObject
{


    /** @var array */
    protected $data = array();


    /**
     * JsonObject constructor.
     *
     * @param string|array $jsonData
     */
    public function __construct($jsonData = '')
    {
        $jsonData = static::sanitizeJsonData($jsonData);
        $data = static::extractData($jsonData, static::getDefaultDataAll(), true);
        foreach($data as $attr => $value) {
            //use setter
            $this->selfSet($attr, $value);
        }
    }

    /**
     * A JsonObject can include another JsonObject.
     * List here data field name corresponding to a JsonObject.
     * When processing this field from data, it will create the nested JsonObject instead of providing an array.
     * This method should return:
     * - sequential array with fields name to deal with will generate generic JsonObject
     * - associative with fields name as key, fully qualified targeted class as value
     * @return array
     */
    protected static function includedJsonObjects()
    {
        /*
        return array(
            'fieldToNest' => JsonObject::class
        );
        */
        return array();
    }


    /**
     * A JsonObject can include of list of other JsonObject.
     * Although SEQUENTIAL list are supported, a map or ASSOCIATIVE list
     * cannot be detected automatically, so you need to declare them here.
     * List corresponding data field names (as sequential array).
     * When processing this field from data, it will create an array of nested objects
     * instead of trying to use this associative list as object data directly.
     * Field names listed must also be declared as a JsonObject inclusion
     * @see includedJsonObjects()
     * @return array
     */
    protected static function includedJsonObjectMapList()
    {
        /*
        return array('fieldToNest');
        */
        return array();
    }


    /**
     * Associative array to convert json field name (key) to JsonObjectAttribute (value)
     * @return array
     */
    protected static function retrieveFieldToAttributeMap()
    {
        /*
        return array(
            'dataField' => 'objectAttribute'
        );
        */
        return array();
    }


    /**
     * Ensure to get jsonData as array. If string provided, try to decode
     * @param array|string $jsonData
     * @return array
     * @throws \RuntimeException
     */
    public static function sanitizeJsonData($jsonData)
    {
        if(empty($jsonData)) $jsonData = array();
        if(is_string($jsonData)) {
            $jsonData = json_decode($jsonData, true);
            if(empty($jsonData)) {
                throw new \RuntimeException('JsonObject, json error when decoding: ' . json_last_error_msg());
            }
        }
        if(!is_array($jsonData)) {
            throw new \RuntimeException('JsonObject: invalid data type provided (must be valid JSON or array)');
        }
        if((new ArrayAnalyser($jsonData))->isSequential()) {
            throw new \RuntimeException('JsonObject: no associative data detected');
        }
        return $jsonData;
    }


    /**
     * extract data from json to push in object data
     * @param array $jsonData
     * @param array $objectData initial object data to merge
     * @param bool  $overwriteObjectData
     * @return array
     */
    protected static function extractData(array $jsonData, array $objectData = array(), bool $overwriteObjectData = true)
    {
        //check field flagged as nested JsonObject
        $toNest = static::includedJsonObjects();
        if((new ArrayAnalyser($toNest))->isSequential()) {
            //flip it to keep field name as keys
            $toNest = array_flip($toNest);
        }
        $toNestInList = array_flip(static::includedJsonObjectMapList());
        $fieldToAttributeMap = static::retrieveFieldToAttributeMap();

        foreach($jsonData as $field => $data) {
            $attribute = empty($fieldToAttributeMap[$field]) ? $field : $fieldToAttributeMap[$field];
            //check if we gonna overwrite existing object attribute
            if(!$overwriteObjectData && isset($objectData[$attribute])) continue;
            if(!empty($toNest[$field])) {
                if(empty($data)) {
                    //do not overwrite default value for this expected object
                    continue;
                }
                $nestedClass = $toNest[$field];
                //check if nested is valid: class exist and subclass of this
                //if invalid, instantiate this class instead
                if(is_string($nestedClass) && class_exists($nestedClass)) {
                    if(!is_subclass_of($nestedClass, self::class)) {
                        $nestedClass = self::class;
                    }
                } else {
                    $nestedClass = self::class;
                }
                //check if data is array
                // - associative:
                //      - declared as mapList: consider as array of nested object with keys
                //      - not declared: consider as single object data
                // - sequential, consider as array of nested object
                $isObjectList = is_array($data) && ((new ArrayAnalyser($data))->isSequential() || isset($toNestInList[$field]));

                if($isObjectList) {
                    $nested = array();
                    foreach($data as $key => $elmt) {
                        $nested[$key] = new $nestedClass($elmt);
                    }
                    $objectData[$attribute] = $nested;
                } else {
                    $objectData[$attribute] = new $nestedClass($data);
                }
                continue;
            }
            if(is_object($data)) {
                throw new \RuntimeException('JsonObject: objects are not supported (found class "' . get_class($data) . '")');
            }
            $objectData[$attribute] = $data;
        }

        return $objectData;
    }


    /**
     * From parent object, create a child object with additionnal json
     * @param JsonObject $parent
     * @param mixed      $additionalJson
     * @param bool       $overwriteParentValue
     * @return static
     */
    public static function generateFromParent(JsonObject $parent, $additionalJson = '', bool $overwriteParentValue = true)
    {
        $additionalJson = static::sanitizeJsonData($additionalJson);
        $parentData = $parent->exportData();
        //merge parent and child data
        $childData = $overwriteParentValue ? array_merge($parentData, $additionalJson) : array_merge($additionalJson, $parentData);
        return new static($childData);
    }


    /**
     * @return array
     */
    protected static function getDefaultDataAll()
    {
        $default = array();
        //initialized nested object with null
        $nestedObjects = static::includedJsonObjects();
        if((new ArrayAnalyser($nestedObjects))->isSequential()) {
            //flip it to keep field name as keys
            $nestedObjects = array_flip($nestedObjects);
        }
        $nestedObjectsList = static::includedJsonObjectMapList();
        foreach($nestedObjects as $attribute => $class) {
            //create empty array if nested list
            if(in_array($attribute, $nestedObjectsList)) {
                $default[$attribute] = array();
            } else {
                //if class provided and valid, create new object
                if(is_subclass_of($class, self::class)) {
                    $default[$attribute] = new $class();
                } else {
                    //create json object
                    $default[$attribute] = new self();
                }
            }
        }
        return $default;
    }


    /**
     * @param string $attribute will return all data if empty
     * @return mixed
     */
    public static function getDefaultData(string $attribute = '')
    {
        $allData = static::getDefaultDataAll();
        if(empty($attribute)) return $allData;
        return isset($allData[$attribute]) ? $allData[$attribute] : '';
    }


    /**
     * @param string $attribute
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $attribute, $default = '')
    {
        return isset($this->data[$attribute]) ? $this->data[$attribute] : $default;
    }


    /**
     * @param string $attribute
     * @param mixed  $value
     * @return static
     */
    public function set(string $attribute, $value)
    {
        $this->data[$attribute] = $value;
        return $this;
    }


    /**
     * @param string $attribute
     * @return bool
     */
    public function has(string $attribute)
    {
        return array_key_exists($attribute, $this->data);
    }


    /**
     * Process an array of JsonObjects and return ones with attribute matching searched value
     * @param self[] $objectList
     * @param string $attribute
     * @param string $value
     * @return array
     */
    public static function extractFromList(array $objectList, string $attribute, string $value)
    {
        $found = array();
        foreach($objectList as $object) {
            if(!$object instanceof self) continue;
            if($object->has($attribute) && $object->get($attribute) == $value) {
                $found[] = $object;
            }
        }
        return $found;
    }


    /**
     * Try to call specific getter, or use generic one
     * @param string $attribute
     * @param mixed  $defaultValue
     * @return mixed
     */
    protected function selfGet(string $attribute, $defaultValue = '')
    {
        $getter = 'get' . ucfirst($attribute);
        if(method_exists($this, $getter)) {
            return $this->$getter();
        }
        return $this->get($attribute, $defaultValue);
    }

    /**
     * Try to call specific setter, or use generic one
     * @param string $attribute
     * @param mixed  $value
     * @return $this
     */
    protected function selfSet(string $attribute, $value)
    {
        $setter = 'set' . ucfirst($attribute);
        if(method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->set($attribute, $value);
        }
        return $this;
    }

    /**
     * Get array data from object
     * @param bool $jsonEncoded
     * @return array|string
     */
    public function exportData(bool $jsonEncoded = false)
    {
        $dataExport = $this->data;
        //export nested object as well
        foreach($dataExport as $attr => &$data) {
            if($data instanceof self) {
               $data = $data->exportData(false);
            } elseif(is_array($data) && !empty($data)) {
                //do not forget array of nested object
                foreach($data as &$elmt) {
                    //if array, all element should be same type
                    if($elmt instanceof self) {
                        $elmt = $elmt->exportData(false);
                    } else {
                        //if one element is not object, stop processing this array
                        break;
                    }
                }
                unset($elmt);
            } else {
                //user getter
                $data = $this->selfGet($attr, $data);
            }
        }
        return $jsonEncoded ? json_encode($dataExport) : $dataExport;
    }

}
