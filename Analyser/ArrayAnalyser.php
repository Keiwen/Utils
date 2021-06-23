<?php

namespace Keiwen\Utils\Analyser;


class ArrayAnalyser
{

    protected $analyseIfEmpty;
    protected $dataArray;

    public function __construct(array $dataArray, bool $analyseIfEmpty = false)
    {
        $this->dataArray = $dataArray;
        $this->analyseIfEmpty = $analyseIfEmpty;
    }


    /**
     * Check if all array keys are strings
     * @return bool
     */
    public function hasStringKeys()
    {
        if(empty($this->dataArray)) return $this->analyseIfEmpty;
        foreach(array_keys($this->dataArray) as $key) {
            if (!is_string($key)) return false;
        }
        return true;
    }


    /**
     * Check if array keys are sequential (0,1,2,... to x, x=size-1)
     * @return bool
     */
    public function isSequential()
    {
        if(empty($this->dataArray)) return $this->analyseIfEmpty;
        //check if keys equal to ordered numbers
        return array_keys($this->dataArray) === range(0, count($this->dataArray) - 1);
    }


    /**
     * Check if array elements share same type
     * @param bool   $classFamily false to detect same class strictly, true to allow subclasses
     * @param string|null $type reference to type detected
     * @return bool
     */
    public function hasHomogeneousElements(bool $classFamily = false, &$type = '')
    {
        if(!is_string($type)) $type = '';
        if(empty($this->dataArray)) return $this->analyseIfEmpty;
        //detect first element type. All other element should be the same
        $first = array_shift($this->dataArray);
        switch(true) {
            case is_object($first): $type = get_class($first); break;
            case is_array($first): $type = 'array'; break;
            case is_float($first): $type = 'float'; break;
            case $first === null: $type = 'null'; break;
            case is_string($first): $type = 'string'; break;
            case is_bool($first): $type = 'boolean'; break;
            case is_int($first): $type = 'int'; break;
            //if cannot determine type, return false
            default: return false;
        }

        $classesFound = array();
        foreach($this->dataArray as $element) {
            switch($type) {
                case 'array': if(!is_array($element)) return false; break;
                case 'float': if(!is_float($element)) return false; break;
                case 'null': if(!($element === null)) return false; break;
                case 'string': if(!is_string($element)) return false; break;
                case 'boolean': if(!is_bool($element)) return false; break;
                case 'int': if(!is_int($element)) return false; break;
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
                foreach($classesFound as $index => $classFound) {
                    //remove classes in queue that are subclasses
                    if(is_subclass_of($classFound, $handleClass)) {
                        unset($classesFound[$index]);
                    }
                }
                //put handled at the end, check if subclass of remaining classes
                $classesFound[] = $handleClass;
                $loop++;
            }
            //if more than on class left, then no subclass link found, maybe parent class?
            $classesCount = count($classesFound);
            if($classesCount > 1) {
                $parentClassesCounters = array();
                foreach($classesFound as $classFound) {
                    $parentClasses = array_unique(class_parents($classFound, false));
                    foreach($parentClasses as $parentClass) {
                        if(!isset($parentClassesCounters[$parentClass])) $parentClassesCounters[$parentClass] = 0;
                        $parentClassesCounters[$parentClass]++;
                    }
                }
                foreach($parentClassesCounters as $parentClass => $parentCount) {
                    /** @var string $parentClass */
                    if($classesCount == $parentCount) {
                        //all classes left are a common parent
                        $type = $parentClass;
                        return true;
                    }
                }
                //no common parent found, return false
                return false;
            }
            $type = reset($classesFound);
        }

        return true;

    }


    /**
     * Check if array elements belongs to specified class
     * If object found from a parent class, will return false.
     * @param string $className
     * @param bool   $allowSub
     * @return bool
     */
    public function isFilledOfObjectsFromClass(string $className, bool $allowSub = true)
    {
        if(empty($this->dataArray)) return $this->analyseIfEmpty;
        $parentClassFound = '';
        $homogeneous = static::hasHomogeneousElements($allowSub, $parentClassFound);
        $directClassFound = ($parentClassFound == $className);
        $subClassFound = is_subclass_of($parentClassFound, $className);

        return $homogeneous && ($directClassFound || $subClassFound);
    }


}
