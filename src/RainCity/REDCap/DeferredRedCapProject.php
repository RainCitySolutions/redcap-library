<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * Allows for deferring the creation of a RedCapProject object until it is
 * actually needed.
 *
 * @see RedCapProject
 */
class DeferredRedCapProject
{
    private RedCapProject $project;

    /**
     * Construct and instance
     *
     * @param RedCapConfigIntf $config An object with the REDCap configuration information
     * @param CacheInterface $cache The cache for caching responses
     * @param LockFactory $lockFactory A reference to a Symfony/LockFactory
     * @param LoggerInterface $logger The object to use for logging
     */
    public function __construct(
        private RedCapConfigIntf $config,
        private CacheInterface $cache,
        private LockFactory $lockFactory,
        private LoggerInterface $logger
        )
    {
    }

    /**
     * Get the instance of the RedCapProject.
     * 
     * If called multiple times, the same instance of a RedCapProject object
     * is returned.
     * 
     * @return RedCapProject A RedCapProject instance
     */
    public function getProject(): RedCapProject
    {
        if (!isset($this->project)) {
            $this->project = new RedCapProject(
                $this->config,
                $this->cache,
                $this->lockFactory,
                $this->logger
            );
        }

        return $this->project;
    }
}
