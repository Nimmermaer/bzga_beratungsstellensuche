<?php

namespace Bzga\BzgaBeratungsstellensuche\Events;

final class InitializeActionEvent
{
  public function __construct(private array $propertyMappingConfiguration)
  {
  }

    public function getPropertyMappingConfiguration(): array
    {
        return $this->propertyMappingConfiguration;
    }

    public function setPropertyMappingConfiguration(array $propertyMappingConfiguration): void
    {
        $this->propertyMappingConfiguration = $propertyMappingConfiguration;
    }

}
