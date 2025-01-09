<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapApiConnectionInterface;
use RainCity\DataCache;
use RainCity\Logging\Logger;
use Psr\SimpleCache\CacheInterface;
use IU\PHPCap\ErrorHandlerInterface;


/**
 * Extension of RedCapProject
 *
 * Unless provided with an error handler, utilizes RedCapErrorHandler
 * to log errors generated by RedCapProject.
 *
 * Also extends/wraps parent methods to ensure a default/empty value is
 * returned from member methods.
 *
 * @see RedCapErrorHandler
 * @see \IU\PHPCap\RedCapProject
 */
class RedCapProject extends \IU\PHPCap\RedCapProject
{
    private CacheInterface $cache;
    private string $cacheKey;

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
        string $apiUrl,
        string $apiToken,
        bool $sslVerify = true,
        ?string $caCertificateFile = null,
        ?ErrorHandlerInterface $errorHandler = null,
        ?RedCapApiConnectionInterface $connection = null
        )
    {
        parent::__construct(
            $apiUrl,
            $apiToken,
            $sslVerify,
            $caCertificateFile,
            $errorHandler ?? new RedCapErrorHandler(),
            $connection
            );

        $this->cache = DataCache::instance(600);
        $this->cacheKey = hash('sha256', parse_url($this->getConnection()->getUrl(), PHP_URL_HOST) . '-' . $apiToken);
    }

    /**
     * Fetch the hash value representing this project instance.
     *
     * Note that different RedCapProject instances using the same host and
     * API Token will return the same hash value.
     *
     * @return string The hash value for the project object.
     */
    public function getHash(): string
    {
        return $this->cacheKey;
    }

    /**
     * Clear the request cache
     *
     * @return bool True on success, otherwise false.
     */
    public function clearCache(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Fetch the result of the specified function from the cache or make the
     * actual call if necessary.
     *
     * This function should only be used for calls which generally result in
     * the same response each time they are called.
     *
     * The results of calls are stored in the cache using a key based on the
     * function name, the arguments passed to the function, the REDCap host
     * name and the API key.
     *
     * @param string $func The name of the function call.
     * @param array<mixed> $args The array of arguments passed to the original call,
     *      retreived via func_get_args().
     *
     * @return mixed The result of the function call retreived from the cache
     *      or the actual call.
     */
    private function maybeCallParentFunction(string $func, array $args): mixed
    {
        $cacheKey = sprintf('%s-%s-%s', $func, json_encode($args), $this->cacheKey);

        $result = $this->cache->get($cacheKey);
        if (!isset($result)) {
            $scopeTimer = new \RainCity\ScopeTimer(
                Logger::getLogger(get_class($this)),
                'Time to call REDCap function '.$func.'(): %s'
                );

            $result = call_user_func_array(parent::class .'::' .$func, $args);

            if (isset($result)) {
                $this->cache->set($cacheKey, $result);
            }
        }

        return $result;
    }

    /**
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportRedcapVersion()
     */
    public function exportRedcapVersion() {
       $result = null;

       try {
           $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
       }
       catch (PhpCapException $exception) {

       }

       return $result;
    }


    /**
     * @param string $format
     * @return mixed
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportInstruments()
     */
    public function exportInstruments($format = 'php'): mixed
    {
        $result = null;

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            if ('php' == $format) {
                $result = array();
            }
            else {
                $result = '{}';
            }
        }

        return $result;
    }


    /**
     *
     * @param string[] $arms
     *
     * @return mixed
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportInstrumentEventMappings()
     */
    public function exportInstrumentEventMappings($format = 'php', $arms = [])
    {
        $result = null;

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            if ('php' == $format) {
                $result = array();
            }
            else {
                $result = '{}';
            }
        }

        return $result;
    }


    /**
     * @param string[] $fields
     * @param string[] $forms
     *
     * @return mixed
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportMetadata()
     */
    public function exportMetadata($format = 'php', $fields = array(), $forms = array()): mixed
    {
        $result = array();

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            /* Special case:
             * RedCapProject::getRecordIdFieldName() uses exportMetadata to
             * determine the record id field name. If an exception occurs
             * when getRecordIdFieldName has been called we need to forward
             * the exception on to our override of getRecordIdFieldName().
             */
            $dbt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $class = $dbt[1]['class'] ?? null;
            $caller = $dbt[1]['function'] ?? null;
            if ('IU\\PHPCap\\RedCapProject' === $class && 'getRecordIdFieldName' === $caller) {
                throw $exception;
            }
        }

        return $result;
    }

    /**
     * @param bool|null $compactDisplay
     * @param int|null $repeatInstance
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportPdfFileOfInstruments()
     */
    public function exportPdfFileOfInstruments(
        $file = null,
        $recordId = null,
        $event = null,
        $form = null,
        $allRecords = null,
        $compactDisplay = null,
        $repeatInstance = null
        )
    {
        $result = '';

        try {
            $result = parent::exportPdfFileOfInstruments(
                $file,
                $recordId,
                $event,
                $form,
                $allRecords,
                $compactDisplay,
                $repeatInstance
                );
        }
        catch (PhpCapException $exception) {
        }

        return $result;
    }

    /**
     * @return mixed
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportProjectInfo()
     */
    public function exportProjectInfo($format = 'php'): mixed
    {
        $result = array();

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            if ('php' == $format) {
                $result = array();
            }
            else {
                $result = '{}';
            }
        }

        return $result;
    }

    /**
     * @param string[]|null $recordIds
     * @param string[]|null $fields
     * @param string[]|null $forms
     * @param string[]|null $events
     *
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportRecords()
     */
    public function exportRecords(
        $format = 'php',
        $type = 'flat',
        $recordIds = null,
        $fields = null,
        $forms = null,
        $events = null,
        $filterLogic = null,
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false,
        $exportSurveyFields = false,
        $exportDataAccessGroups = false,
        $dateRangeBegin = null,
        $dateRangeEnd = null,
        $csvDelimiter = ',',
        $decimalCharacter = null,
        $exportBlankForGrayFormStatus = false
    ) {
            $result = null;

            try {
                $result = parent::exportRecords(
                    $format,
                    $type,
                    $recordIds,
                    $fields,
                    $forms,
                    $events,
                    $filterLogic,
                    $rawOrLabel,
                    $rawOrLabelHeaders,
                    $exportCheckboxLabel,
                    $exportSurveyFields,
                    $exportDataAccessGroups,
                    $dateRangeBegin,
                    $dateRangeEnd,
                    $csvDelimiter,
                    $decimalCharacter
                    );
            }
            catch (PhpCapException $exception) {
                if ('php' == $format) {
                    $result = array();
                }
                else {
                    $result = '{}';
                }
            }

            return $result;
    }

    /**
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportSurveyLink()
     */
    public function exportSurveyLink($recordId, $form, $event = null, $repeatInstance = null)
    {
        $result = '';

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportSurveyReturnCode()
     */
    public function exportSurveyReturnCode($recordId, $form, $event = null, $repeatInstance = null)
    {
        $result = '';

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @see \IU\PHPCap\RedCapProject::getRecordIdFieldName()
     */
    public function getRecordIdFieldName(): string
    {
        $result = '';

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
        }

        return $result;
    }


    /**
     * {@inheritDoc}
     * @see \IU\PHPCap\RedCapProject::exportFieldNames()
     */
    public function exportFieldNames($format = 'php', $field = null)
    {
        $result = array();

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            if ('php' == $format) {
                $result = array();
            }
            else {
                $result = '{}';
            }
        }

        return $result;
    }

    /**
     * @param string $format
     * @param string[] $arms
     *
     * @return array<string, array<string, mixed>>|string
     *
     * {@inheritdoc}
     * @see \IU\PHPCap\RedCapProject::exportEvents
     */
    public function exportEvents($format = 'php', $arms = []): array|string
    {
        $result = array();

        try {
            $result = $this->maybeCallParentFunction(__FUNCTION__, func_get_args());
        }
        catch (PhpCapException $exception) {
            if ('php' == $format) {
                $result = array();
            }
            else {
                $result = '{}';
            }
        }

        return $result;
    }
}
