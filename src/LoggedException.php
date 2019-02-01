<?php

namespace parseword\MassFetcher;

use parseword\logger\Logger;

/**
 * This Exception will attempt to write the error message and call stack to
 * the log file, then raise itself again.
 */
class LoggedException extends \Exception
{

    /**
     * @param string $message The Exception message to throw
     * @param int $code The Exception code
     * @param \Throwable $previous The previous exception, used for chaining
     */
    public function __construct($message = null, $code = 0,
            \Throwable $previous = null) {
        Logger::error($message . ' : ' . $this->getTraceAsString());
        parent::__construct($message, $code, $previous);
    }

}
