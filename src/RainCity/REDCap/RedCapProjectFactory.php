<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;

/**
 * Factory class for creating a RedCapProject instance on demand.
 *
 * Holds of creating the RedCapProject until someone asks for it.
 */
class RedCapProjectFactory
{
    private RedCapProject $project;

    /**
     * Construct and instance
     *
     * @param string $apiUrl The URL for a REDCap server API
     * @param string $apiToken The API authentication token
     * @param bool $sslVerify Indicator if SSL should be verified or not.
     *      Defaults to true.
     * @param string $caCertificateFile A CA certificate file to use in
     *      connecting to the server. Defaults to null.
     * @param ErrorHandlerInterface $errorHandler An ErrorHandler to use instead of
     *      the default. Defaults to null.
     * @param RedCapApiConnectionInterface $connection A connection to use in
     *      place of the default. Defaults to null.
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

    public function getProject(): RedCapProject
    {
        if (!isset($this->project)) {
            $this->project = new RedCapProject(
                $this->apiUrl,
                $this->apiToken,
                $this->sslVerify,
                $this->caCertificateFile,
                $this->errorHandler,
                $this->connection
                );
        }

        return $this->project;
    }
}
