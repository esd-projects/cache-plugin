<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/5/7
 * Time: 17:09
 */

namespace GoSwoole\Plugins\Cache;


use GoSwoole\Plugins\Redis\GetRedis;

class RedisCacheStorage implements CacheStorage
{
    use GetRedis;
    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    const prefix = "CACHE_";

    public function __construct(CacheConfig $cacheConfig)
    {
        $this->cacheConfig = $cacheConfig;
    }

    public function getFromNameSpace(string $nameSpace, string $id)
    {
        return $this->redis($this->cacheConfig->getDb())->hGet(self::prefix . $nameSpace, $id);
    }

    public function setFromNameSpace(string $nameSpace, string $id, string $data)
    {
        return $this->redis($this->cacheConfig->getDb())->hSet(self::prefix . $nameSpace, $id, $data);
    }

    public function removeFromNameSpace(string $nameSpace, string $id)
    {
        return $this->redis($this->cacheConfig->getDb())->hDel(self::prefix . $nameSpace, $id);
    }

    public function removeNameSpace(string $nameSpace)
    {
        return $this->redis($this->cacheConfig->getDb())->del(self::prefix . $nameSpace);
    }

    public function get(string $id)
    {
        return $this->redis($this->cacheConfig->getDb())->get(self::prefix . $id);
    }

    public function set(string $id, string $data, int $time)
    {
        if ($time == 0) {
            $time = $this->cacheConfig->getTimeout();
        }
        if ($time > 0) {
            $this->redis($this->cacheConfig->getDb())->setex(self::prefix . $id, $time, $data);
        } else {
            $this->redis($this->cacheConfig->getDb())->set(self::prefix . $id, $data);
        }
    }

    public function remove(string $id)
    {
        $this->redis($this->cacheConfig->getDb())->del(self::prefix . $id);
    }
}