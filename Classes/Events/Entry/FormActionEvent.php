<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Entry;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class FormActionEvent
{
    public function __construct(
        private RequestInterface $request,
        private ?Demand          $demand,
        private array            $assignedViewValues
    )
    {
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getDemand(): ?Demand
    {
        return $this->demand;
    }

    public function setDemand(?Demand $demand): void
    {
        $this->demand = $demand;
    }

    public function getAssignedViewValues(): array
    {
        return $this->assignedViewValues;
    }

    public function setAssignedViewValues(array $assignedViewValues): void
    {
        $this->assignedViewValues = $assignedViewValues;
    }
}
