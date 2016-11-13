<?php

namespace Keiwen\Utils\Object;


trait CacheHandlerTrait
{

    protected $cache = null;
    protected $defaultCacheLifetime = 0;
    protected $cacheKeyPrefix = '';

    protected static $staticCache = array();
    protected static $cacheGetters = array('get', 'fetch');
    protected static $cacheSetters = array('set', 'save');



    /**
     * @return bool
     */
    protected function hasCacheEnabled()
    {
        return !empty($this->cache);
    }


    /**
     */
    public function disableCache()
    {
        $this->cache = null;
    }


    /**
     * @param string $partKey
     * @return string
     */
    public function getCacheFullKey(string $partKey)
    {
        return $this->cacheKeyPrefix . $partKey;
    }


    /**
     * @param string $key
     * @param mixed  $data
     * @param int    $cacheLifetime
     * @return bool
     */
    protected function storeInCache(string $key, $data, int $cacheLifetime = 0)
    {
        if(empty($cacheLifetime)) $cacheLifetime = $this->defaultCacheLifetime;
        static::$staticCache[$key] = $data;
        if(!$this->hasCacheEnabled()) return true;
        $cacheKey = $this->getCacheFullKey($key);
        $setterFound = false;
        foreach(static::$cacheSetters as $setter) {
            if(method_exists($this->cache, $setter)) {
                try {
                    $this->cache->$setter($cacheKey, $data, $cacheLifetime);
                    $setterFound = true;
                    break;
                } catch (\Exception $e) {

                }
            }
        }
        return $setterFound;
    }

    /**
     * @param string $url
     * @return mixed|null null means not found in cache
     */
    protected function readInCache(string $key)
    {
        $static = isset(static::$staticCache[$key]) ? static::$staticCache[$key] : null;
        if($static !== null) return $static;
        if(!$this->hasCacheEnabled()) return null;
        $cacheKey = $this->getCacheFullKey($key);
        foreach(static::$cacheGetters as $getter) {
            if(method_exists($this->cache, $getter)) {
                try {
                    $data = $this->cache->$getter($cacheKey);
                    return $data;
                } catch (\Exception $e) {

                }
            }
        }
        return null;
    }


}
