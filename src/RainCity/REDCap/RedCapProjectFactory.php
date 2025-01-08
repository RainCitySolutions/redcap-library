<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;
use RainCity\Exception\InvalidStateException;

/**
 * Factory class for creating a RedCapProject instance on demand.
 *
 * Holds off creating the RedCapProject until someone asks for it. Once the
 * project is created, the same instance will be used for subsequent
 * requests for the project from the same instance of this class.
 * <p>
 * It is also possible to create a singleton from an instance of this class
 * by creating an instance and then calling initSingleton(). Subsequently,
 * RedCapProjectFactory::instance()->getProject() can be called to fetch the
 * RedCapProject associated with the singleton. As with an instance of the
 * class, the project is not actually created until getProject() is called.
 */
class RedCapProjectFactory implements RedCapProjectFactoryIntf
{
    // Allow for null to support unit testing
    protected static ?RedCapProjectFactory $singletonFactory;

    private RedCapProject $project;

    /**
     * Construct and instance
     *
     * @param string $apiUrl The URL for a REDCap server API
     * @param string $apiToken The API authentication token
     * @param bool $sslVerify Indicator if SSL should be verified or not.
     *      Defaults to true.
     *      [optional]
     * @param string $caCertificateFile A CA certificate file to use in
     *      connecting to the server. Defaults to null.
     *      [optional]
     * @param ErrorHandlerInterface $errorHandler An ErrorHandler to use instead of
     *      the default. Defaults to null.
     *      [optional]
     * @param RedCapApiConnectionInterface $connection A connection to use in
     *      place of the default. Defaults to null.
     *      [optional]
     */
    public function __construct(
        private string $apiUrl,
        private string $apiToken,
        private bool $sslVerify = true,
        private ?string $caCertificateFile = null,
        private ?ErrorHandlerInterface $errorHandler = null,
        private ?RedCapApiConnectionInterface $connection = null
        )
    {
    }

    public function initSingleton(): RedCapProjectFactoryIntf
    {
        if (isset(self::$singletonFactory)) {
            throw new InvalidStateException('Singleton has already been initialized.');
        }

        self::$singletonFactory = new RedCapProjectFactory(
            $this->apiUrl,
            $this->apiToken,
            $this->sslVerify,
            $this->caCertificateFile,
            $this->errorHandler,
            $this->connection
            );

        return self::$singletonFactory;
    }

    public static function instance(): RedCapProjectFactoryIntf
    {
        if (!isset(self::$singletonFactory)) {
            throw new InvalidStateException('Singleton has not been initialized.');
        }

        return self::$singletonFactory;
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\RedCapProjectFactoryIntf::getProject()
     */
    public function getProject(): RedCapProject
    {
        if (!isset($this->project)) {
            $this->project = $this->createProject();
        }

        return $this->project;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function createProject(): RedCapProject
    {
        return new RedCapProject(
            $this->apiUrl,
            $this->apiToken,
            $this->sslVerify,
            $this->caCertificateFile,
            $this->errorHandler,
            $this->connection
            );
    }
}
