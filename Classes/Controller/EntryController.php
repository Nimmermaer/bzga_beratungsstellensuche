<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Controller;

use Bzga\BzgaBeratungsstellensuche\Domain\Map\MapBuilderInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\MapWindowInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\KilometerRepository;
use Bzga\BzgaBeratungsstellensuche\Events;
use Bzga\BzgaBeratungsstellensuche\Service\SessionService;
use Bzga\BzgaBeratungsstellensuche\Utility\Utility;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use SJBR\StaticInfoTables\Domain\Model\Country;
use SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * @author Sebastian Schreiber
 */
class EntryController extends ActionController
{
    /**
     * @var int
     */
    public const GERMANY_ISOCODENUMBER = 276;

    protected EntryRepository $entryRepository;

    protected KilometerRepository $kilometerRepository;

    protected SessionService $sessionService;

    protected CategoryRepository $categoryRepository;

    protected CountryZoneRepository $countryZoneRepository;
    private MapBuilderInterface $mapBuilder;

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function injectCountryZoneRepository(CountryZoneRepository $countryZoneRepository): void
    {
        $this->countryZoneRepository = $countryZoneRepository;
    }

    public function injectEntryRepository(EntryRepository $entryRepository): void
    {
        $this->entryRepository = $entryRepository;
    }

    public function injectKilometerRepository(KilometerRepository $kilometerRepository): void
    {
        $this->kilometerRepository = $kilometerRepository;
    }

    public function injectSessionService(SessionService $sessionService): void
    {
        $this->sessionService = $sessionService;
    }

    public function injectMapBuilder(MapBuilderInterface $mapBuilder): void
    {
        $this->mapBuilder = $mapBuilder;
    }

    public function initializeAction(): void
    {
        if ($this->arguments->hasArgument('demand')) {
            $propertyMappingConfiguration = $this->arguments->getArgument('demand')->getPropertyMappingConfiguration();
            $propertyMappingConfiguration->allowAllProperties();
            $propertyMappingConfiguration->setTypeConverterOption(
                PersistentObjectConverter::class,
                (string)PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true
            );
            $propertyMappingConfiguration->setTypeConverterOption(
                PersistentObjectConverter::class,
                (string)PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
                true
            );
            $propertyMappingConfiguration->forProperty('categories')->allowAllProperties();
            $propertyMappingConfiguration->allowCreationForSubProperty('categories');
            $propertyMappingConfiguration->allowModificationForSubProperty('categories');
            $this->emitInitializeActionSignal(['propertyMappingConfiguration' => $propertyMappingConfiguration]);
        }
    }

    public function initializeFormAction(): void
    {
        $this->resetDemand();
        $this->addDemandRequestArgumentFromSession();
    }

    public function formAction(Demand $demand = null): ResponseInterface
    {
        if (!$demand instanceof Demand) {
            $demand = GeneralUtility::makeInstance(Demand::class);
        }
        $countryZonesGermany = $this->findCountryZonesForGermany();
        $kilometers = $this->kilometerRepository->findKilometersBySettings($this->settings);
        $categories = $this->categoryRepository->findAll();
        $random = random_int(0, 1000);
        $assignedViewValues = compact('demand', 'kilometers', 'categories', 'countryZonesGermany', 'random');
        $assignedViewValues = $this->emitActionSignal(Events::FORM_ACTION_SIGNAL, $assignedViewValues);
        $this->view->assignMultiple($assignedViewValues);
        return $this->htmlResponse();
    }

    public function initializeListAction(): void
    {
        $this->resetDemand();
        if (!$this->request->hasArgument('demand')) {
            $this->addDemandRequestArgumentFromSession();
        } else {
            $this->sessionService->writeToSession($this->request->getArgument('demand'));
        }
    }

    public function listAction(Demand $demand = null): ResponseInterface
    {
        if (!$demand instanceof Demand) {
            $demand = GeneralUtility::makeInstance(Demand::class);
        }

        if (!$demand->hasValidCoordinates()) {
            $this->redirect('form', 'Entry', 'bzga_beratungsstellensuche', ['demand' => $demand], $this->settings['backPid']);
        }

        $entries = $this->entryRepository->findDemanded($demand);
        $countryZonesGermany = $this->findCountryZonesForGermany();
        $kilometers = $this->kilometerRepository->findKilometersBySettings($this->settings);
        $categories = $this->categoryRepository->findAll();

        $itemsPerPage = (int)($this->settings['list']['itemsPerPage'] ?? 10);
        $maximumLinks = (int)($this->settings['list']['maximumLinks'] ?? 10);
        $currentPage = $this->request->hasArgument('currentPage') ? (int)$this->request->getArgument('currentPage') : 1;

        // For some reason the QueryResultPaginator does not work
        $paginator = new ArrayPaginator($entries->toArray(), $currentPage, $itemsPerPage);
        $pagination = new NumberedPagination($paginator, $maximumLinks);
        $this->view->assign('pagination', [
            'paginator' => $paginator,
            'pagination' => $pagination,
        ]);
        $assignedViewValues = compact('entries', 'demand', 'kilometers', 'categories', 'countryZonesGermany');
        $assignedViewValues = $this->emitActionSignal(Events::LIST_ACTION_SIGNAL, $assignedViewValues);
        $this->view->assignMultiple($assignedViewValues);
        return $this->htmlResponse();
    }

    public function initializeShowAction(): void
    {
        $this->addDemandRequestArgumentFromSession();
    }

    public function showAction(Entry $entry = null, Demand $demand = null): void
    {
        if (!$entry instanceof Entry) {
            // @TODO: Add possibility to hook into here.
            $this->redirect('list', null, null, [], $this->settings['listPid'], null, 404);
        }

        $mapId = sprintf('map_%s', StringUtility::getUniqueId());
        $assignedViewValues = compact('entry', 'demand', 'mapId');
        $assignedViewValues = $this->emitActionSignal(Events::SHOW_ACTION_SIGNAL, $assignedViewValues);
        $this->view->assignMultiple($assignedViewValues);
    }

    public function mapJavaScriptAction(string $mapId, ?Entry $mainEntry = null, ?Demand $demand = null): ResponseInterface
    {
        $this->view->assign('mapId', $mapId);

        // These are only some defaults and can be overridden via a hook method
        $map = $this->mapBuilder->createMap($mapId);

        // Set map options configurable via TypoScript, option:value => maxZoom:17
        $mapOptions = isset($this->settings['map']['options']) ? GeneralUtility::trimExplode(',', $this->settings['map']['options']) : [];

        if (is_array($mapOptions) && ! empty($mapOptions)) {
            foreach ($mapOptions as $mapOption) {
                [$mapOptionKey, $mapOptionValue] = GeneralUtility::trimExplode(':', $mapOption, true, 2);
                $map->setOption($mapOptionKey, $mapOptionValue);
            }
        }

        $entries = new ObjectStorage();
        if ($demand !== null) {
            try {
                $queryResult = $this->entryRepository->findDemanded($demand);
                $entries = Utility::transformQueryResultToObjectStorage($queryResult);
            } catch (InvalidQueryException $e) {
            }
        }

        if ($mainEntry !== null) {
            $entries->attach($mainEntry);
        }

        $markerCluster = $this->mapBuilder->createMarkerCluster('markercluster', $map);

        foreach ($entries as $entry) {
            /* @var $entry GeopositionInterface|MapWindowInterface */
            $coordinate = $this->mapBuilder->createCoordinate($entry->getLatitude(), $entry->getLongitude());
            $marker = $this->mapBuilder->createMarker(sprintf('marker_%d', $entry->getUid()), $coordinate);

            $iconFile = $this->settings['map']['pathToDefaultMarker'] ?? '';
            $isCurrentMarker = false;
            if ($entry === $mainEntry) {
                $isCurrentMarker = true;
                $iconFile = $this->settings['map']['pathToActiveMarker'] ?? '';
                $map->setCenter($coordinate);
            }

            if (! empty($iconFile)) {
                $marker->addIconFromPath(Utility::stripPathSite(GeneralUtility::getFileAbsFileName($iconFile)));
            }

            $infoWindowParameters = [];

            // Current marker does not need detail link
            if (!$isCurrentMarker) {
                $detailsPid = (int)($this->settings['singlePid'] ?? $this->getTyposcriptFrontendController()->id);
                $uriBuilder = $this->controllerContext->getUriBuilder();
                $infoWindowParameters['detailLink'] = $uriBuilder->reset()->setTargetPageUid($detailsPid)->uriFor(
                    'show',
                    ['entry' => $entry],
                    'Entry'
                );
            }

            // Create Info Window
            $popUp = $this->mapBuilder->createPopUp('popUp');

            // Call hook functions for modify the info window
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyInfoWindow'])) {
                $params = [
                    'popUp' => &$popUp,
                    'isCurrentMarker' => $isCurrentMarker,
                    'demand' => $demand,
                ];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyInfoWindow'] as $reference) {
                    GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }

            $marker->addPopUp($popUp, $entry->getInfoWindow($infoWindowParameters), $isCurrentMarker);

            // Call hook functions for modify the marker
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMarker'])) {
                $params = [
                    'marker' => &$marker,
                    'isCurrentMarker' => $isCurrentMarker,
                ];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMarker'] as $reference) {
                    GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }
            $markerCluster->addMarker($marker);
        }

        // Call hook functions for modify the map
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMap'])) {
            $params = [
                'map' => &$map,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMap'] as $reference) {
                GeneralUtility::callUserFunction($reference, $params, $this);
            }
        }

        $this->view->assign('map', $this->mapBuilder->build($map));
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/javascript; charset=utf-8')
            ->withBody($this->streamFactory->createStream($this->view->render()));
    }

    private function getTyposcriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    public function autocompleteAction(string $q): ResponseInterface
    {
        $this->view->assign('entries', $this->entryRepository->findByQuery($q));
        $this->view->assign('q', $q);
        return $this->htmlResponse();
    }

    private function findCountryZonesForGermany(): array
    {
        if (GeneralUtility::inList($this->settings['formFields'], 'countryZonesGermany') === false) {
            return [];
        }
        $country = GeneralUtility::makeInstance(Country::class);
        $country->setIsoCodeNumber(self::GERMANY_ISOCODENUMBER);

        return $this->countryZoneRepository->findByCountryOrderedByLocalizedName($country);
    }

    private function emitInitializeActionSignal(array $signalArguments): void
    {
//        $this->signalSlotDispatcher->dispatch(static::class, Events::INITIALIZE_ACTION_SIGNAL, $signalArguments);
    }

    private function emitActionSignal(string $signalName, array $assignedViewValues): array
    {
        $signalArguments = [];
        $signalArguments[] = [];

        $additionalViewValues = $this->signalSlotDispatcher->dispatch(static::class, $signalName, $signalArguments);

        if(
            is_array($additionalViewValues) &&
            array_key_exists('extendedVariables', $additionalViewValues) &&
            is_array($additionalViewValues['extendedVariables'])
        ) {
            return array_merge($assignedViewValues, $additionalViewValues['extendedVariables']);
        }
        return $assignedViewValues;
    }

    private function addDemandRequestArgumentFromSession(): void
    {
        $demand = $this->sessionService->restoreFromSession();
        if ($demand) {
            $this->request->setArgument('demand', $demand);
        }
    }

    private function resetDemand(): void
    {
        if ($this->request->hasArgument('reset')) {
            $this->sessionService->cleanUpSession();
            $this->request->setArgument('demand', null);
        }
    }
}
