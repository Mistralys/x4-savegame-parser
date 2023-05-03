<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer;

use Mistralys\X4\X4Exception;
use Throwable;

class SaveViewerException extends X4Exception
{
    public function __construct(string $message, ?string $details = null, ?int $code = null, ?Throwable $previous = null)
    {
        if(defined('X4_TESTSUITE') && X4_TESTSUITE && !empty($details)) {
            $message .= PHP_EOL.$details;
        }

        parent::__construct($message, $details, $code, $previous);
    }
}
