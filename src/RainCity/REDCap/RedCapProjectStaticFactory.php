<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;
use RainCity\SingletonTrait;

class RedCapProjectStaticFactory
{
    use SingletonTrait;

    private RedCapProjectFactory $factory;

    protected function __construct(mixed ...$args)
    {
        list($apiUrl, $apiToken, $sslVerify, $caCertificateFile, $errorHandler, $connection) = $args;

        if (!isset($apiUrl) || !is_string($apiUrl)) {
            throw new \InvalidArgumentException('Invalid URL provided for REDCap server API');
        }

        if (!isset($apiToken) || !is_string($apiToken)) {
            throw new \InvalidArgumentException('Invalid API Token provided');
        }

        $sslVerify ??= true;
        $caCertificateFile ??= null;
        $errorHandler ??= null;
        $connection ??= null;

        if (!is_bool($sslVerify)) {
            throw new \InvalidArgumentException('$sslVerify parameter (3) must be a boolean');
        }

        if (isset($caCertificateFile) && (!is_string($caCertificateFile) || !file_exists($caCertificateFile) )) {
            throw new \InvalidArgumentException('$caCertificateFile parameter (4) must be a string and refer to a valid file');
        }

        if (isset($errorHandler) && !($errorHandler instanceof ErrorHandlerInterface)) {
            throw new \InvalidArgumentException('$errorHandler parameter (5) must implement ErrorHandlerInterface');
        }

        if (isset($connection) && !($connection instanceof RedCapApiConnectionInterface)) {
            throw new \InvalidArgumentException('$connection parameter (5) must implement RedCapApiConnectionInterface');
        }

        $this->factory = new RedCapProjectFactory(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler,
            $connection
            );
    }

    public static function getProject(): RedCapProject
    {
        if (!isset(self::$instance)) {
            throw new \Exception('Invalid state exception, attempt to get project before instantiated.');
        }

        return self::$instance->factory->getProject();
    }
}
