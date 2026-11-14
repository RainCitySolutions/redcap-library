<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;

interface RedCapConfigIntf
{
    public function getApiUrl(): string;
    public function getApiToken(): string;
    public function getSslVerify(): bool;
    public function getCaCertificateFile(): ?string;
    public function getErrorHandler(): ?ErrorHandlerInterface;
    public function getConnection(): ?RedCapApiConnectionInterface;
}
