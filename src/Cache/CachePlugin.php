<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/5/8
 * Time: 10:55
 */

namespace GoSwoole\Plugins\Cache;

use GoSwoole\BaseServer\Server\Context;
use GoSwoole\BaseServer\Server\PlugIn\AbstractPlugin;
use GoSwoole\BaseServer\Server\PlugIn\PluginInterfaceManager;
use GoSwoole\BaseServer\Server\Server;
use GoSwoole\Plugins\Aop\AopConfig;
use GoSwoole\Plugins\Aop\AopPlugin;
use GoSwoole\Plugins\Cache\Aspect\CachingAspect;
use GoSwoole\Plugins\Redis\RedisPlugin;

class CachePlugin extends AbstractPlugin
{

    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    /**
     * @var CacheStorage
     */
    protected $cacheStorage;


    /**
     * 获取插件名字
     * @return string
     */
    public function getName(): string
    {
        return "Cache";
    }

    /**
     * CachePlugin constructor.
     * @param CacheConfig|null $cacheConfig
     * @throws \DI\DependencyException
     * @throws \ReflectionException
     */
    public function __construct(?CacheConfig $cacheConfig = null)
    {
        parent::__construct();
        $this->atAfter(RedisPlugin::class);
        $this->atAfter(AopConfig::class);
        if ($cacheConfig == null) {
            $cacheConfig = new CacheConfig();
        }
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \DI\DependencyException
     * @throws \GoSwoole\BaseServer\Exception
     * @throws \ReflectionException
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $pluginInterfaceManager->addPlug(new RedisPlugin());
        $pluginInterfaceManager->addPlug(new AopPlugin());
    }


    /**
     * 在服务启动前
     * @param Context $context
     * @return mixed
     * @throws \GoSwoole\BaseServer\Server\Exception\ConfigException
     */
    public function beforeServerStart(Context $context)
    {
        $this->cacheConfig->merge();
        $class = $this->cacheConfig->getCacheStorageClass();
        $this->cacheStorage = new $class($this->cacheConfig);
        $aopPlugin = Server::$instance->getPlugManager()->getPlug(AopPlugin::class);
        if ($aopPlugin instanceof AopPlugin) {
            $aopPlugin->getAopConfig()->addAspect(new CachingAspect($this->cacheStorage));
        }
    }

    /**
     * 在进程启动前
     * @param Context $context
     * @return mixed
     */
    public function beforeProcessStart(Context $context)
    {
        $this->ready();
    }

    /**
     * @return CacheStorage
     */
    public function getCacheStorage(): CacheStorage
    {
        return $this->cacheStorage;
    }
}