<?php

namespace Keiwen\Utils\Analyser;


class ArrayAnalyser
{


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


}
