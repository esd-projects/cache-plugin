<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/5/8
 * Time: 11:08
 */

namespace GoSwoole\Plugins\Cache;


use GoSwoole\BaseServer\Plugins\Config\BaseConfig;

class CacheConfig extends BaseConfig
{
    const key = "cache";
    /**
     *
     * 销毁时间s
     * @var int
     */
    protected $timeout = 30 * 60;

    /**
     * @var string
     */
    protected $db = "default";
    /**
     * @var string
     */
    protected $cacheStorageClass = RedisCacheStorage::class;

    public function __construct()
    {
        parent::__construct(self::key);
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getDb(): string
    {
        return $this->db;
    }

    /**
     * @param string $db
     */
    public function setDb(string $db): void
    {
        $this->db = $db;
    }

    /**
     * @return string
     */
    public function getCacheStorageClass(): string
    {
        return $this->cacheStorageClass;
    }

    /**
     * @param string $cacheStorageClass
     */
    public function setCacheStorageClass(string $cacheStorageClass): void
    {
        $this->cacheStorageClass = $cacheStorageClass;
    }
}