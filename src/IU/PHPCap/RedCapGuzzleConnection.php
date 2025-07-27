<?php
declare(strict_types = 1);
namespace IU\PHPCap;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;

class RedCapGuzzleConnection implements RedCapApiConnectionInterface
{
    const DEFAULT_TIMEOUT_IN_SECONDS = 1200; // 1,200 seconds = 20 minutes
    const DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS = 20;

    private const BASE_URI = 'base_uri';
    private const HANDLER = 'handler';

    /** @var array<string, mixed> */
    private array $clientOptions;
    private ?Client $client;

    /** the error handler for the connection. */
    protected ErrorHandlerInterface $errorHandler;

    /**
     * Constructor that creates a REDCap API connection for the specified URL, with the
     * specified settings.
     *
     * @param string $url
     *            the URL for the API of the REDCap site that you want to connect to.
     * @param boolean $sslVerify indicates if verification should be done for the SSL
     *            connection to REDCap. Setting this to false is not secure.
     * @param string $caCertificateFile
     *            the CA (Certificate Authority) certificate file used for veriying the REDCap site's
     *            SSL certificate (i.e., for verifying that the REDCap site that is
     *            connected to is the one specified).
     * @param ErrorHandlerInterface $errorHandler the error handler for the connection.
     * @param callable[] $middlewareHandlers array of middleware handlers to add to the connection
     */
    public function __construct(
        $url,
        $sslVerify = false,
        $caCertificateFile = '',
        $errorHandler = null,
        $middlewareHandlers = []
    ) {
        # If an error handler was specified, use it,
        # otherwise, use the default PHPCap error handler
        if (isset($errorHandler)) {
            $this->errorHandler = $errorHandler;
        } else {
            $this->errorHandler = new ErrorHandler();
        }

        $stack = HandlerStack::create();

        foreach ($middlewareHandlers as $handler) {
            $stack->push($handler);
        }

        $this->clientOptions = [
            self::BASE_URI => $url,
            self::HANDLER => $stack,
            RequestOptions::VERIFY => $sslVerify,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::CONNECT_TIMEOUT => self::DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS,
            RequestOptions::TIMEOUT => self::DEFAULT_TIMEOUT_IN_SECONDS,
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HEADERS => [
                'Accept' => 'text/xml'
                ]
            ];

        if ($sslVerify && $caCertificateFile != null && trim($caCertificateFile) != '') {
            if (! file_exists($caCertificateFile)) {
                $message = sprintf(
                    'The cert file "%s" does not exist.',
                    $caCertificateFile
                    );
                $code    = ErrorHandlerInterface::CA_CERTIFICATE_FILE_NOT_FOUND;
                $this->errorHandler->throwException($message, $code, null, null, null);
            } elseif (! is_readable($caCertificateFile)) {
                $message = sprintf(
                    'The cert file "%s" exists, but cannot be read.',
                    $caCertificateFile
                    );
                $code    = ErrorHandlerInterface::CA_CERTIFICATE_FILE_UNREADABLE;
                $this->errorHandler->throwException($message, $code, null, null, null);
            } // @codeCoverageIgnore

            $this->clientOptions[RequestOptions::CERT] = $caCertificateFile;
        }

        $this->createClient();
    }

    /**
     *
     */
    private function createClient(): void
    {
        $this->client = new Client($this->clientOptions);
    }

    /**
     * Destructor for this class.
     */
    public function __destruct()
    {
        if (isset($this->client)) {
            unset($this->client);
        }
    }


    /**
     * Makes a call to REDCap's API and returns the results.
     *
     * @param mixed $data
     *         data for the call.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function call($data)
    {
        if (!is_string($data) && !is_array($data)) {
            $message = "Data passed to ".__METHOD__." has type ".gettype($data)
            .", but should be a string or an array.";
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code, null, null, null);
        } // @codeCoverageIgnore

        $responseStr = '';

        try {
            $requestBody = [];
            if (is_string($data)) {
                $requestBody[RequestOptions::HEADERS] = [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ];
                $requestBody[RequestOptions::BODY] = $data;
            } else {
                $requestBody[RequestOptions::HEADERS] = [
                    'Accept' => 'application/json'
                ];
                $requestBody[RequestOptions::FORM_PARAMS] = $data;
            }

            $response = $this->client->post('', $requestBody);

            // Check for HTTP errors
            $httpCode = $response->getStatusCode();

            if ($httpCode == 301 || $httpCode == 302) {
                $locationHdrs = $response->getHeader('Location');

                $message = sprintf(
                    'The page for the specified URL (%s) has moved to %s. Please update your URL.',
                    $this->getOption(self::BASE_URI),
                    !empty($locationHdrs) ? array_shift($locationHdrs) : ''
                    );
                $code = ErrorHandlerInterface::INVALID_URL;
                $this->errorHandler->throwException($message, $code, null, $httpCode, null);
            } elseif ($httpCode == 404) {
                $message = sprintf(
                    'The specified URL (%s) appears to be incorrect. Nothing was found at this URL.',
                    $this->getOption(self::BASE_URI)
                    );
                $code = ErrorHandlerInterface::INVALID_URL;
                $this->errorHandler->throwException($message, $code, null, $httpCode, null);
            } // @codeCoverageIgnore

            $responseStr = (string)$response->getBody();
        } catch (GuzzleException $ge) {
            $message = $ge->getMessage();
            $code    = ErrorHandlerInterface::CONNECTION_ERROR;

            $this->errorHandler->throwException($message, $code, null, null, null);
        }

        return $responseStr;
    }


    /**
     * Calls REDCap's API using a with a correctly formatted string version
     * of the specified array and returns the results.
     *
     * @param mixed[] $dataArray the array of data that is converted to a
     *         string and then passed to the REDCap API.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function callWithArray($dataArray)
    {
        $data = http_build_query($dataArray, '', '&');

        return $this->call($data);
    }


    /**
     * Returns call information for the most recent call.
     *
     * @return mixed[] an associative array of values of call information for the most recent call made.
     *
     * See {@link href="http://php.net/manual/en/function.curl-getinfo.php
     *     http://php.net/manual/en/function.curl-getinfo.php}
     *     for information on what values are returned.
     */
    public function getCallInfo()
    {
        // Not supported

        return [];
    }


    /**
     * Gets the error handler for the connection.
     *
     * @return ErrorHandlerInterface the error handler for the connection.
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * Sets the error handler;
     *
     * @param ErrorHandlerInterface $errorHandler the error handler to use.
     * @return void
     */
    public function setErrorHandler($errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Gets the URL of the connection.
     *
     * @return string the URL of the connection.
     */
    public function getUrl()
    {
        return $this->getOption(self::BASE_URI);
    }

    /**
     * @param string $url
     * @return void
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapApiConnectionInterface::setUrl()
     */
    public function setUrl($url)
    {
        $this->setOption(self::BASE_URI, $url);
    }

    public function getSslVerify()
    {
        return $this->getOption(RequestOptions::VERIFY);
    }

    /**
     * @param bool $sslVerify
     * @return void
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapApiConnectionInterface::setSslVerify()
     */
    public function setSslVerify($sslVerify)
    {
        $this->setOption(RequestOptions::VERIFY, $sslVerify);
    }

    /**
     *
     * @return mixed|NULL
     */
    public function getCaCertificateFile()
    {
        return $this->getOption(RequestOptions::CERT);
    }

    /**
     *
     * @param string $caCertificateFile
     * @return void
     */
    public function setCaCertificateFile($caCertificateFile)
    {
        $this->setOption(RequestOptions::CERT, $caCertificateFile);
    }

    public function getTimeoutInSeconds()
    {
        return $this->getOption(RequestOptions::TIMEOUT);
    }

    /**
     * @param int $timeoutInSeconds
     * @return void
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapApiConnectionInterface::setTimeoutInSeconds()
     */
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
        $this->setOption(RequestOptions::TIMEOUT, $timeoutInSeconds);
    }

    public function getConnectionTimeoutInSeconds()
    {
        return $this->getOption(RequestOptions::CONNECT_TIMEOUT);
    }

    /**
     * @param int $connectionTimeoutInSeconds
     * @return integer connection timeout in seconds.
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapApiConnectionInterface::setConnectionTimeoutInSeconds()
     */
    public function setConnectionTimeoutInSeconds($connectionTimeoutInSeconds)
    {
        $this->setOption(RequestOptions::CONNECT_TIMEOUT, $connectionTimeoutInSeconds);

        return $connectionTimeoutInSeconds;
    }

    /**
     * Sets the specified option to the specified value.
     *
     * @param string $option the option that is being set.
     * @param mixed $value the value that the option is being set to.
     *
     * @return boolean Returns true on success and false on failure.
     */
    private function setOption(string $option, mixed $value): bool
    {
        $result = false;

        $orgClient = $this->client;
        $orgValue = $this->getOption($option);

        $this->clientOptions[$option] = $value;

        try {
            $this->createClient();
            $result = true;
        } catch (GuzzleException $ge) {
            // put things back the way they were
            $this->client = $orgClient;
            $this->clientOptions[$option] = $orgValue;
        }

        return $result;
    }

    /**
     * Gets the value for the specified cURL option number.
     *
     * @param string $option option to retrieve.
     *
     * @return mixed if the specified option has a value that has been set in the code,
     *     then the value is returned. If no value was set, then null is returned.
     */
    private function getOption(string $option): mixed
    {
        $optionValue = null;

        if (array_key_exists($option, $this->clientOptions)) {
            $optionValue = $this->clientOptions[$option];
        }

        return $optionValue;
    }
}
