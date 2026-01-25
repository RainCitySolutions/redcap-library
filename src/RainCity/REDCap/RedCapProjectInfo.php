<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;

/**
 * Represents the configuration for a RedCapProject instance, and the project
 * itself it if has been created.
 *
 */
class RedCapProjectInfo
{
    // Allow for null to support unit testing
    private ?RedCapProject $project = null;

    public function __construct(
        private string $id,
        private string $apiUrl,
        private string $apiToken,
        private bool $sslVerify = true,
        private ?string $caCertificateFile = null,
        private ?ErrorHandlerInterface $errorHandler = null,
        private ?RedCapApiConnectionInterface $connection = null
        )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getSslVerify(): bool
    {
        return $this->sslVerify;
    }

    public function getCaCertificateFile(): ?string
    {
        return $this->caCertificateFile;
    }

    public function getErrorHandler(): ?ErrorHandlerInterface
    {
        return $this->errorHandler;
    }

    public function getConnection(): ?RedCapApiConnectionInterface
    {
        return $this->connection;
    }

    public function setProject(RedCapProject $project): void
    {
        $this->project = $project;
    }

    public function getProject(): ?RedCapProject
    {
        return $this->project;
    }
}
