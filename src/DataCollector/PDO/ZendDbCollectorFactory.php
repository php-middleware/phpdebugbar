<?php

namespace PhpMiddleware\PhpDebugBar\DataCollector\PDO;

use Interop\Container\ContainerInterface;
use DebugBar\DataCollector\PDO\TraceablePDO;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Driver\Pdo\Pdo;
use PhpMiddleware\PhpDebugBar\Exception;

class ZendDbCollectorFactory
{
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(AdapterInterface::class)) {
            throw new Exception\MissingServiceException(sprintf(
                '%s requires a %s service at instantiation; none found', 
                ZendDbCollectorFactory::class, 
                AdapterInterface::class
            ));
        }
        $dbAdapter = $container->get(AdapterInterface::class);
        $driver = $dbAdapter->getDriver();
        
        if (! $driver instanceof Pdo) {
            throw new Exception\RuntimeException(sprintf(
                'Driver must be instance of %s', 
                Pdo::class
            ));
        }
        
        $pdo = $driver->getConnection()->getResource();
        $traceablePdo = new TraceablePDO($pdo);
        $pdoCollector = new ZendDbCollector($traceablePdo);

        return $pdoCollector;
    }
}
