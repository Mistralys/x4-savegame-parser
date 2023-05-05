<?php

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\Traits;

trait DebuggableTrait
{
    private bool $logging = false;
    private ?string $logPrefix = null;

    abstract public function getLogIdentifier() : string;

    /**
     * @return $this
     */
    public function enableLogging() : self
    {
        return $this->setLoggingEnabled(true);
    }


    /**
     * @return $this
     */
    public function disableLogging() : self
    {
        return $this->setLoggingEnabled(false);
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setLoggingEnabled(bool $enabled) : self
    {
        $this->logging = $enabled;
        return $this;
    }

    public function isLoggingEnabled() : bool
    {
        return $this->logging;
    }

    protected function log(string $message, ...$params) : void
    {
        if($this->logging === false) {
            return;
        }

        if(!isset($this->logPrefix)) {
            $this->logPrefix = $this->getLogIdentifier();
        }

        if(empty($params)) {
            echo $this->logPrefix.$message.PHP_EOL;
            return;
        }

        echo sprintf(
            $this->logPrefix.$message.PHP_EOL,
            ...$params
        );
    }
}
