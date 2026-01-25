<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;

interface RedCapProjectFactoryIntf
{
    /**
     * Initialize a RedCapProject.
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
     *
     * @return string An identifier for the project.
     */
    public function initializeProject(
        string $apiUrl,
        string $apiToken,
        bool $sslVerify = true,
        ?string $caCertificateFile = null,
        ?ErrorHandlerInterface $errorHandler = null,
        ?RedCapApiConnectionInterface $connection = null
        ): string;

    /**
     * Fetch a RedCapProject instance from the factory.
     *
     * @param string $projectId The identifier for a project previously
     *      initialized by a call to initializeProject().
     *
     * @return RedCapProject|NULL A RedCapProject instance, or null if there
     *      is no project intialized with the id provided.
     */
    public function getProject(string $projectId): ?RedCapProject;
}
