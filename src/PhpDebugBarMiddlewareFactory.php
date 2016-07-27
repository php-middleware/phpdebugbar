<?php

namespace PhpMiddleware\PhpDebugBar;

use Interop\Container\ContainerInterface;
use ArrayObject;
use DebugBar\StandardDebugBar;
use DebugBar\JavascriptRenderer;
use DebugBar\DataCollector\ConfigCollector;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;

/**
 * Create and return a DebugBar instance
 * 
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 * 
 * <code>
 * 'phpdebugbar' => [
 *     // set default options, 
 *     // @see JavascriptRenderer::setOptions()
 *     'options' => []
 *     // additional collectors
 *     'collectors' => [],
 *     // storage
 *     'storage' => null,
 * ],
 * </code>
 */
class PhpDebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        
        if (! is_array($config) && ! $config instanceof ArrayObject) {
            throw new Exception\InvalidConfigException(sprintf(
                '"config" service must be an array or ArrayObject for the %s to be able to consume it; received %s',
                __CLASS__,
                (is_object($config) ? get_class($config) : gettype($config))
            ));
        }
        
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;
        
        $debugBarConfig = (isset($config['phpdebugbar']) && is_array($config['phpdebugbar']))
            ? $config['phpdebugbar']
            : [];
        
        $debugBarOptions = (isset($debugBarConfig['options']) && is_array($debugBarConfig['options']))
            ? $debugBarConfig['options']
            : [];
        
        $debugBar = new StandardDebugBar();
        
        // Config Collectors
        $debugBar->addCollector(new ConfigCollector($config));
        
        // Collectors
        $collectors = (isset($debugBarConfig['collectors']) && is_array($debugBarConfig['collectors']))
            ? $debugBarConfig['collectors']
            : [];
        
        foreach ($collectors as $collectorName) {
            $collector = $container->get($collectorName);
            $debugBar->addCollector($collector);
        }
        
        // Storage
        if (isset($debugBarConfig['storage']) && $container->has($debugBarConfig['storage'])) {
            $storage = $container->get($debugBarConfig['storage']);
            $debugBar->setStorage($storage);
        }
        
        $javascriptRenderer = new JavascriptRenderer($debugBar);
        $javascriptRenderer->setOptions($debugBarOptions);

        return new PhpDebugBarMiddleware($javascriptRenderer);
    }
}
