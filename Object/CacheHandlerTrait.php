<?php

namespace Keiwen\Utils\Object;


use Psr\Cache\CacheItemPoolInterface;

trait CacheHandlerTrait
{

    protected $cache = null;
    protected $defaultCacheLifetime = 0;
    protected $cacheKeyPrefix = '';
    protected $cacheDisabled = false;
    protected $cacheReadBypass = false;
    protected $cacheLoaded = false;

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
     * @return bool
     */
    protected function hasCacheLoaded()
    {
        return $this->cacheLoaded;
    }


    /**
     * @param $cache
     */
    protected function loadCache($cache)
    {
        if(!$this->cacheDisabled) {
            $this->cache = $cache;
            $this->cacheLoaded = true;
        }
    }


    /**
     */
    public function disableCache()
    {
        $this->cacheDisabled = true;
        $this->cache = null;
    }

    /**
     *
     */
    public function bypassCacheRead()
    {
        $this->cacheReadBypass = true;
    }

    /**
     * @return bool
     */
    public function hasCacheReadBypass()
    {
        return $this->cacheReadBypass;
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
        if(!$this->prepareCache()) return true;
        if(!$this->hasCacheEnabled()) return true;
        if(empty($cacheLifetime)) $cacheLifetime = $this->defaultCacheLifetime;
        static::$staticCache[$key] = $data;
        $cacheKey = $this->getCacheFullKey($key);
        if($this->cache instanceof CacheItemPoolInterface) {
            $item = $this->cache->getItem($cacheKey);
            $item->set($data)->expiresAfter($cacheLifetime);
            return $this->cache->save($item);
        } else {
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
        }
        return $setterFound;
    }


    /**
     * @param string $url
     * @return mixed|null null means not found in cache
     */
    protected function readInCache(string $key)
    {
        if(!$this->prepareCache()) return null;
        if(!$this->hasCacheEnabled()) return null;
        if($this->hasCacheReadBypass()) return null;
        $static = isset(static::$staticCache[$key]) ? static::$staticCache[$key] : null;
        if($static !== null) return $static;
        $cacheKey = $this->getCacheFullKey($key);
        if($this->cache instanceof CacheItemPoolInterface) {
            $item = $this->cache->getItem($cacheKey);
            return $item->get();
        } else {
            foreach(static::$cacheGetters as $getter) {
                if(method_exists($this->cache, $getter)) {
                    try {
                        $data = $this->cache->$getter($cacheKey);

                        return $data;
                    } catch(\Exception $e) {

                    }
                }
            }
        }
        return null;
    }


    /**
     * @return bool
     */
    protected function prepareCache()
    {
        return true;
    }

}
