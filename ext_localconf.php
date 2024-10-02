<?php

use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use Bzga\BzgaBeratungsstellensuche\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility as GeneralExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Bzga\BzgaBeratungsstellensuche\Controller\EntryController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Bzga\BzgaBeratungsstellensuche\Backend\FormDataProvider\BeratungsstellensucheFlexFormManipulation;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use Bzga\BzgaBeratungsstellensuche\Hooks\DataHandlerProcessor;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use Bzga\BzgaBeratungsstellensuche\Cache\CachedClassLoader;
use Bzga\BzgaBeratungsstellensuche\Factory\CacheFactory;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\ImageLinkConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\StringConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\AbstractEntityConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\ObjectStorageConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\BoolConverter;
use Bzga\BzgaBeratungsstellensuche\Updates\CreateImageUploadFolderUpdate;
use Bzga\BzgaBeratungsstellensuche\Updates\ImportCountryZonesUpdate;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Bzga\BzgaBeratungsstellensuche\Persistence\QueryResult;
if (! defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function ($packageKey) {
    ExtensionManagementUtility::registerExtensionKey($packageKey, 100);

    ExtensionUtility::configurePlugin(
        'BzgaBeratungsstellensuche',
        'Pi1',
        [EntryController::class => 'list,show,autocomplete'],
        [EntryController::class => 'list,autocomplete']
    );

    ExtensionUtility::configurePlugin(
        'BzgaBeratungsstellensuche',
        'Form',
        [EntryController::class => 'form,autocomplete'],
        [EntryController::class => 'form,autocomplete']
    );

    ExtensionUtility::configurePlugin(
        'BzgaBeratungsstellensuche',
        'Detail',
        [EntryController::class => 'show'],
        []
    );
    ExtensionUtility::configurePlugin(
        'BzgaBeratungsstellensuche',
        'MapJavaScript',
        [EntryController::class => 'mapJavaScript'],
        [EntryController::class => 'mapJavaScript']
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][BeratungsstellensucheFlexFormManipulation::class] = [
        'depends' => [
            TcaFlexPrepare::class,
        ],
        'before' => [
            TcaFlexProcess::class,
        ],
    ];

    // Page module hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['bzgaberatungsstellensuche_pi1']['bzga_beratungsstellensuche'] =
        'Bzga\\BzgaBeratungsstellensuche\\Hooks\\PageLayoutView->getExtensionSummary';

    // Command controllers for scheduler
    if (TYPO3 === 'BE') {
        // hooking into TCE Main to monitor record updates that may require deleting documents from the index
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = DataHandlerProcessor::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandlerProcessor::class;
    }

    // Register cache to extend the models of this extension
    if (!array_key_exists($packageKey, $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']) || ! is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]           = [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['groups'] = ['all'];
    }
    if (! isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['frontend'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['frontend'] = PhpFrontend::class;
    }
    if (! isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['backend'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['backend'] = FileBackend::class;
    }
    // Configure clear cache post processing for extended domain models
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$packageKey]
        = 'Bzga\\BzgaBeratungsstellensuche\\Cache\\ClassCacheManager->reBuild';

    // Register cached domain model classes autoloader
    require_once GeneralExtensionManagementUtility::extPath($packageKey) . 'Classes/Cache/CachedClassLoader.php';
    CachedClassLoader::registerAutoloader();

    // Names of entities which can be overriden
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$packageKey]['entities'] = [
        'Entry',
        'Category',
        'Dto/Demand',
    ];

    // Caching of user requests
    if (!array_key_exists('bzgaberatungsstellensuche_cache_coordinates', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']) || ! is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bzgaberatungsstellensuche_cache_coordinates'])
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bzgaberatungsstellensuche_cache_coordinates'] = [
            'frontend' => VariableFrontend::class,
            'backend'  => Typo3DatabaseBackend::class,
            'options'  => [],
        ];
    }

    // Register some type converters so we can prepare everything for the data handler to import the xml
    ExtensionManagementUtility::registerTypeConverter(ImageLinkConverter::class);
    ExtensionManagementUtility::registerTypeConverter(StringConverter::class);
    ExtensionManagementUtility::registerTypeConverter(AbstractEntityConverter::class);
    ExtensionManagementUtility::registerTypeConverter(ObjectStorageConverter::class);
    ExtensionManagementUtility::registerTypeConverter(BoolConverter::class);

    // Linkvalidator
    if (GeneralExtensionManagementUtility::isLoaded('linkvalidator')) {
        GeneralExtensionManagementUtility::addPageTSConfig('@import \'EXT:bzga_beratungsstellensuche/Configuration/TsConfig/Page/LinkValidator/*.tsconfig\'');
    }

    // Upgrade wizards
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][CreateImageUploadFolderUpdate::class] = CreateImageUploadFolderUpdate::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][ImportCountryZonesUpdate::class] = ImportCountryZonesUpdate::class;

}, 'bzga_beratungsstellensuche');

GeneralExtensionManagementUtility::addTypoScriptSetup(trim('
    config.pageTitleProviders {
        beratungsstelle {
            provider = Bzga\BzgaBeratungsstellensuche\PageTitle\PageTitleProvider
            before = record
            after = altPageTitle
        }
    }
'));
