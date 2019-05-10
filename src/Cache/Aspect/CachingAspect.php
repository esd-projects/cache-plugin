<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/5/8
 * Time: 11:08
 */

namespace GoSwoole\Plugins\Cache\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use GoSwoole\BaseServer\Plugins\Logger\GetLogger;
use GoSwoole\Plugins\Cache\Annotation\Cacheable;
use GoSwoole\Plugins\Cache\Annotation\CacheEvict;
use GoSwoole\Plugins\Cache\Annotation\CachePut;
use GoSwoole\Plugins\Cache\CacheStorage;

/**
 * Caching aspect
 */
class CachingAspect implements Aspect
{
    use GetLogger;
    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    public function __construct(CacheStorage $cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * This advice intercepts an execution of cacheable methods
     *
     * Logic is pretty simple: we look for the value in the cache and if it's not present here
     * then invoke original method and store it's result in the cache.
     *
     * Real-life examples will use APC or Memcache to store value in the cache
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Around("@execution(GoSwoole\Plugins\Cache\Annotation\Cacheable)")
     * @return mixed
     */
    public function aroundCacheable(MethodInvocation $invocation)
    {
        $obj = $invocation->getThis();
        $class = is_object($obj) ? get_class($obj) : $obj;
        $cacheable = $invocation->getMethod()->getAnnotation(Cacheable::class);
        //初始化计算环境
        $p = $invocation->getArguments();
        //计算key
        $key = eval("return (" . $cacheable->key . ");");
        $this->debug("cache get namespace:{$cacheable->namespace} key:{$key}");
        //计算condition
        $condition = true;
        if (!empty($cacheable->condition)) {
            $condition = eval("return (" . $cacheable->condition . ");");
        }
        $data = null;
        if (empty($cacheable->namespace)) {
            $data = $this->cacheStorage->get($key);
        } else {
            $data = $this->cacheStorage->getFromNameSpace($cacheable->namespace, $key);
        }
        //获取到缓存就返回
        if ($data != null) {
            $this->debug("cache Hit!");
            return serverUnSerialize($data);
        }
        //执行
        $result = $invocation->proceed();
        //可以缓存就缓存
        if ($condition) {
            $data = serverSerialize($result);
            if (empty($cacheable->namespace)) {
                $this->cacheStorage->set($key, $data, $cacheable->time);
            } else {
                $this->cacheStorage->setFromNameSpace($cacheable->namespace, $key, $data);
            }
        }
        return $result;
    }

    /**
     * This advice intercepts an execution of cachePut methods
     *
     * Logic is pretty simple: we look for the value in the cache and if it's not present here
     * then invoke original method and store it's result in the cache.
     *
     * Real-life examples will use APC or Memcache to store value in the cache
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Around("@execution(GoSwoole\Plugins\Cache\Annotation\CachePut)")
     * @return mixed
     */
    public function aroundCachePut(MethodInvocation $invocation)
    {
        $obj = $invocation->getThis();
        $class = is_object($obj) ? get_class($obj) : $obj;
        $cachePut = $invocation->getMethod()->getAnnotation(CachePut::class);
        //初始化计算环境
        $p = $invocation->getArguments();
        //计算key
        $key = eval("return (" . $cachePut->key . ");");
        $this->debug("cache put namespace:{$cachePut->namespace} key:{$key}");
        //计算condition
        $condition = true;
        if (!empty($cachePut->condition)) {
            $condition = eval("return (" . $cachePut->condition . ");");
        }
        //执行
        $result = $invocation->proceed();
        //可以缓存就缓存
        if ($condition) {
            $data = serverSerialize($result);
            if (empty($cachePut->namespace)) {
                $this->cacheStorage->set($key, $data, $cachePut->time);
            } else {
                $this->cacheStorage->setFromNameSpace($cachePut->namespace, $key, $data);
            }
        }
        return $result;
    }

    /**
     * This advice intercepts an execution of cacheEvict methods
     *
     * Logic is pretty simple: we look for the value in the cache and if it's not present here
     * then invoke original method and store it's result in the cache.
     *
     * Real-life examples will use APC or Memcache to store value in the cache
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Around("@execution(GoSwoole\Plugins\Cache\Annotation\CacheEvict)")
     * @return mixed
     */
    public function aroundCacheEvict(MethodInvocation $invocation)
    {
        $obj = $invocation->getThis();
        $class = is_object($obj) ? get_class($obj) : $obj;
        $cacheEvict = $invocation->getMethod()->getAnnotation(CacheEvict::class);
        //初始化计算环境
        $p = $invocation->getArguments();
        //计算key
        $key = eval("return (" . $cacheEvict->key . ");");
        $this->debug("cache evict namespace:{$cacheEvict->namespace} key:{$key}");
        $result = null;
        if ($cacheEvict->beforeInvocation) {
            //执行
            $result = $invocation->proceed();
        }
        if (empty($cacheEvict->namespace)) {
            $this->cacheStorage->remove($key);
        } else {
            if ($cacheEvict->allEntries) {
                $this->cacheStorage->removeNameSpace($cacheEvict->namespace);
            } else {
                $this->cacheStorage->removeFromNameSpace($cacheEvict->namespace, $key);
            }
        }
        if (!$cacheEvict->beforeInvocation) {
            //执行
            $result = $invocation->proceed();
        }
        return $result;
    }
}
