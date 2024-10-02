<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Cache;

use Exception;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Sebastian Schreiber
 */
class ClassCacheManager implements SingletonInterface
{
    /**
     * @var string
     */
    protected $extensionKey = 'bzga_beratungsstellensuche';

    /**
     * @var array[]
     */
    protected $cacheConfiguration = [
        'bzga_beratungsstellensuche' => [
            'frontend' => PhpFrontend::class,
            'backend' => FileBackend::class,
            'options' => [],
            'groups' => ['all'],
        ],
    ];

    /**
     * @var FrontendInterface
     */
    protected $cacheInstance;

    public function __construct()
    {
        $this->initializeCache();
    }

    protected function initializeCache(): void
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        /* @var $cacheManager CacheManager */
        if (!$cacheManager->hasCache($this->extensionKey)) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$this->extensionKey])) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $this->cacheConfiguration[$this->extensionKey],
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$this->extensionKey]
                );
            }
            $cacheManager->setCacheConfigurations($this->cacheConfiguration);
        }
        $this->cacheInstance = $cacheManager->getCache($this->extensionKey);
    }

    public function build(): void
    {
        $extensibleExtensions = $this->getExtensibleExtensions();
        $entities = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['entities'];

        foreach ($entities as $entity) {
            $key = 'Domain/Model/' . $entity;

            $path = ExtensionManagementUtility::extPath($this->extensionKey) . 'Classes/' . $key . '.php';
            if (!is_file($path)) {
                throw new \Exception('given file "' . $path . '" does not exist');
            }
            $code = $this->parseSingleFile($path, false);
            // Get the files from all other extensions that are extending this domain model class
            if (isset($extensibleExtensions[$key]) && is_array($extensibleExtensions[$key]) && count($extensibleExtensions[$key]) > 0) {
                $extensionsWithThisClass = array_keys($extensibleExtensions[$key]);
                foreach ($extensionsWithThisClass as $extension) {
                    $path = ExtensionManagementUtility::extPath($extension) . 'Classes/' . $key . '.php';
                    if (is_file($path)) {
                        $code .= $this->parseSingleFile($path);
                    }
                }
            }

            // Close the class definition and the php tag
            $code = $this->closeClassDefinition($code);

            // The file is added to the class cache
            $entryIdentifier = str_replace('/', '', $key);
            try {
                $this->cacheInstance->set($entryIdentifier, $code);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }
    }

    protected function getExtensibleExtensions(): array
    {
        $loadedExtensions = array_unique(ExtensionManagementUtility::getLoadedExtensionListArray());

        // Get the extensions which want to extend static_info_tables
        $extensibleExtensions = [];
        foreach ($loadedExtensions as $extensionKey) {
            $extensionInfoFile = ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/DomainModelExtension/BzgaBeratungsstellensuche.txt';
            if (file_exists($extensionInfoFile)) {
                $info = GeneralUtility::getUrl($extensionInfoFile);
                $classes = GeneralUtility::trimExplode(LF, $info, true);
                foreach ($classes as $class) {
                    $extensibleExtensions[$class][$extensionKey] = 1;
                }
            }
        }

        return $extensibleExtensions;
    }

    public function parseSingleFile(string $filePath, bool $removeClassDefinition = true): string
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" could not be found', $filePath));
        }
        $code = GeneralUtility::getUrl($filePath);

        return $this->changeCode($code, $filePath, $removeClassDefinition);
    }

    protected function changeCode(string $code, string $filePath, bool $removeClassDefinition = true, bool $renderPartialInfo = true): string
    {
        if (empty($code)) {
            throw new \InvalidArgumentException(sprintf('File "%s" could not be fetched or is empty', $filePath));
        }
        $code = trim($code);
        $code = str_replace(['<?php', '?>'], '', $code);
        $code = trim($code);

        // Remove everything before 'class ', including namespaces,
        // comments and require-statements.
        if ($removeClassDefinition) {
            $pos = strpos($code, 'class ');
            $pos2 = strpos($code, '{', $pos);

            $code = substr($code, $pos2 + 1);
        }

        $code = trim($code);

        // Add some information for each partial
        if ($renderPartialInfo) {
            $code = $this->getPartialInfo($filePath) . $code;
        }

        // Remove last }
        $pos = strrpos($code, '}');
        $code = substr($code, 0, $pos);
        $code = trim($code);

        return $code . LF . LF;
    }

    protected function getPartialInfo(string $filePath): string
    {
        return '/*' . str_repeat('*', 70) . LF .
        ' * this is partial from: ' . $filePath . LF . str_repeat('*', 70) . '*/' . LF . "\t";
    }

    protected function closeClassDefinition(string $code): string
    {
        return $code . LF . '}';
    }

    public function clear(): void
    {
        $this->cacheInstance->flush();
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->user) {
            $GLOBALS['BE_USER']->writelog(
                3,
                1,
                0,
                0,
                '[BZgA Beratungsstellensuche]: User %s has cleared the class cache',
                [$GLOBALS['BE_USER']->user['username']]
            );
        }
    }

    public function reBuild(array $parameters = []): void
    {
        $isValidCall = (
            empty($parameters)
            || (
                !empty($parameters['cacheCmd'])
                && GeneralUtility::inList('all,temp_cached', $parameters['cacheCmd'])
                && isset($GLOBALS['BE_USER'])
            )
        );
        if ($isValidCall) {
            $this->clear();
            $this->build();
        }
    }
}
