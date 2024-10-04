<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Normalizer;

final class CallbackEvent
{
    public function __construct(
        private array $callbacks
    )
    {
    }

    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function setCallbacks(array $callbacks): void
    {
        $this->callbacks = array_merge($this->callbacks, $callbacks);
    }

}
