<?php
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandler;
use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;

/**
 * PhpCap Error Handler to log errors
 *
 */
class RedCapErrorHandler extends ErrorHandler
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getLogger(get_class($this));
    }

    /**
     * {@inheritdoc}
     *
     */
    public function throwException(
				        $message,
				        $code,
				        $connectionErrorNumber = null,
				        $httpStatusCode = null,
				        $previousException = null
				        )
    {
        $this->logger->error(
            "PhpCap Error: {msg}\nError code: {errCode}\nConnection error number: {errNum}\nHttp Status code: {httpCode}\n",
            array(
                'msg' => $message,
                'errCode' => $code,
                'errNum' => $connectionErrorNumber,
                'httpCode' => $httpStatusCode
            )
        );
        ob_start();
        $stackTrace = debug_backtrace(2, 11);
        array_shift($stackTrace);	// remove ourselves from the list
        array_walk($stackTrace, function ($a, $ndx) {
        	echo sprintf(' #%d %s(%d): %s%s%s()', $ndx, $a['file'] ?? 'unknown', $a['line'] ?? 'unknwon', $a['class'] ?? '', $a['type'] ?? '', $a['function'] );
        });
        $stack = ob_get_contents();
        ob_end_clean();

        $this->logger->error("Stack trace:\n{trace}", array('trace' => $stack));

        parent::throwException($message, $code, $connectionErrorNumber, $httpStatusCode, $previousException);
    }
}
