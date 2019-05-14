<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/5/8
 * Time: 10:55
 */

namespace ESD\Plugins\Cache;

use ESD\BaseServer\Server\Context;
use ESD\BaseServer\Server\PlugIn\AbstractPlugin;
use ESD\BaseServer\Server\PlugIn\PluginInterfaceManager;
use ESD\BaseServer\Server\Server;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\Aop\AopPlugin;
use ESD\Plugins\Cache\Aspect\CachingAspect;
use ESD\Plugins\Redis\RedisPlugin;

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
        $this->atAfter(AopPlugin::class);
        if ($cacheConfig == null) {
            $cacheConfig = new CacheConfig();
        }
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \DI\DependencyException
     * @throws \ESD\BaseServer\Exception
     * @throws \ReflectionException
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $pluginInterfaceManager->addPlug(new RedisPlugin());
        $pluginInterfaceManager->addPlug(new AopPlugin());
    }

    /**
     * @param Context $context
     * @return mixed|void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ESD\BaseServer\Server\Exception\ConfigException
     */
    public function init(Context $context)
    {
        parent::init($context);
        $this->cacheConfig->merge();
        $class = $this->cacheConfig->getCacheStorageClass();
        $this->cacheStorage = new $class($this->cacheConfig);
        $this->setToDIContainer(CacheStorage::class, $this->cacheStorage);
        $aopConfig = Server::$instance->getContainer()->get(AopConfig::class);
        $aopConfig->addAspect(new CachingAspect($this->cacheStorage));
    }

    /**
     * 在服务启动前
     * @param Context $context
     * @return mixed
     */
    public function beforeServerStart(Context $context)
    {

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