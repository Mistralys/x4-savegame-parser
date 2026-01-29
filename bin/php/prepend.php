<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Bin;

use AppUtils\BaseException;
use Mistralys\X4\SaveViewer\Monitor\BaseMonitor;
use Throwable;

require_once __DIR__.'/../../prepend.php';

function runMonitor(BaseMonitor $monitor) : void
{
    try
    {
        $monitor->start();
    }
    catch (Throwable $e)
    {
        global $argv;

        if (in_array('--json', $argv ?? [])) {
            $dt = new \DateTime('now', new \DateTimeZone('UTC'));

            $errors = [];
            $current = $e;

            // Build the exception chain
            while ($current !== null) {
                $errorData = [
                    'message' => $current->getMessage(),
                    'code' => $current->getCode(),
                    'class' => get_class($current),
                    'trace' => $current->getTraceAsString()
                ];

                if ($current instanceof BaseException) {
                    $errorData['details'] = $current->getDetails();
                }

                $errors[] = $errorData;
                $current = $current->getPrevious();
            }

            echo json_encode([
                'type' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'errors' => $errors,
                'timestamp' => $dt->format('c')
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            exit(1);
        }

        die(
            'An exception occurred. '.PHP_EOL.
            'Message: ['.$e->getMessage().'] '.PHP_EOL.
            'Code: ['.$e->getCode().'] '.PHP_EOL
        );
    }
}
