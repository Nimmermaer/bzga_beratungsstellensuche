<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Category;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\BaseMappingNameConverter;
use Bzga\BzgaBeratungsstellensuche\Events;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer as BaseGetSetMethodNormalizer;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @author Sebastian Schreiber
 */
class GetSetMethodNormalizer extends AbstractObjectNormalizer
{

    /**
     * @var array
     */
    protected array $denormalizeCallbacks = [];
    protected ?EventDispatcher $eventDispatcher = null;

    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null ,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->classMetadataFactory = $classMetadataFactory;
        if ($nameConverter === null) {
            $nameConverter = new BaseMappingNameConverter();
        }
        $this->nameConverter = $nameConverter;
        parent::__construct($classMetadataFactory, $nameConverter);
    }

    public function setDenormalizeCallbacks(array $callbacks): self
    {
        $event = new Events\Normalizer\CallbackEvent($callbacks) ;
        $event = $this->eventDispatcher->dispatch($event);
        foreach ($event->getCallbacks() as $attribute => $callback) {
            if (!is_callable($callback)) {

                throw new \InvalidArgumentException(sprintf(
                    'The given callback for attribute "%s" is not callable.',
                    $attribute
                ));
            }
        }
        $this->denormalizeCallbacks = $event->getCallbacks();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $type, $format = null, array $context = []): mixed
    {

        $allowedAttributes = $this->getAllowedAttributes($type, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($type);
        $object = $this->instantiateObject($normalizedData, $type, $context, $reflectionClass, $allowedAttributes);

        $classMethods = get_class_methods($object);
        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $context[self::IGNORED_ATTRIBUTES] ?? []);

            if ($allowed && !$ignored) {
                $setter = 'set' . ucfirst($attribute);
                if (in_array($setter, $classMethods, false) && !$reflectionClass->getMethod($setter)->isStatic()) {
                    if (isset($this->denormalizeCallbacks[$attribute])) {
                        $value = call_user_func($this->denormalizeCallbacks[$attribute], $value);
                    }
                    if ($value !== null) {
                        $object->$setter($value);
                    }
                }
            }
        }

        return $object;
    }


    public function getSupportedTypes(?string $format): array
    {
        if ($format === 'xml') {
            return [
                Category::class => true,
                Entry::class => true,
                ];
        }
        return [];
    }

    protected function extractAttributes(object $object, ?string $format = null, array $context = []): array
    {
        $attributes = [];
        $reflectionClass = new \ReflectionClass($object);
        foreach ($reflectionClass->getProperties() as $property) {
            $attributes[] = $property->getName();
        }
        return $attributes;
    }

    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = []): mixed
    {
        $reflectionClass = new \ReflectionClass($object);
        if ($reflectionClass->hasProperty($attribute)) {
            $property = $reflectionClass->getProperty($attribute);
            return $property->getValue($object);
        }

        // Alternativ könnten Sie Getter verwenden
        $getter = 'get' . ucfirst($attribute);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        throw new \LogicException(sprintf('Attribute "%s" not found in class "%s".', $attribute, get_class($object)));
    }

    protected function setAttributeValue(object $object, string $attribute, mixed $value, string $format = null, array $context = []): void
    {
        $reflectionClass = new \ReflectionClass($object);
        if ($reflectionClass->hasProperty($attribute)) {
            $property = $reflectionClass->getProperty($attribute);
            $property->setValue($object, $value);
            return;
        }

        // Alternativ über Setter-Methoden
        $setter = 'set' . ucfirst($attribute);
        if (method_exists($object, $setter)) {
            $object->$setter($value);
            return;
        }

        throw new \LogicException(sprintf('Attribute "%s" not found in class "%s".', $attribute, get_class($object)));
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher):void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
