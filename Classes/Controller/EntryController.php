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
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
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

    public function __construct(\Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository $categoryRepository, \SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository $countryZoneRepository, \Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository $entryRepository, \Bzga\BzgaBeratungsstellensuche\Domain\Repository\KilometerRepository $kilometerRepository, \Bzga\BzgaBeratungsstellensuche\Service\SessionService $sessionService, \Bzga\BzgaBeratungsstellensuche\Domain\Map\MapBuilderInterface $mapBuilder)
    {
        $this->categoryRepository = $categoryRepository;
        $this->countryZoneRepository = $countryZoneRepository;
        $this->entryRepository = $entryRepository;
        $this->kilometerRepository = $kilometerRepository;
        $this->sessionService = $sessionService;
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
            $event = new Events\Entry\InitializeActionEvent(['propertyMappingConfiguration' => $propertyMappingConfiguration]);
            $this->eventDispatcher->dispatch($event);
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
        $event = new Events\Entry\FormActionEvent($this->request, $demand, $assignedViewValues);
        $event = $this->eventDispatcher->dispatch($event);
        $this->view->assignMultiple($event->getAssignedViewValues());
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
        $event = new Events\Entry\ListActionEvent($this->request, $demand, $assignedViewValues);
        $this->view->assignMultiple($event->getAssignedViewValues());
        return $this->htmlResponse();
    }

    public function initializeShowAction(): void
    {
        $this->addDemandRequestArgumentFromSession();
    }

    public function showAction(Entry $entry = null, Demand $demand = null): ResponseInterface
    {
        if (!$entry instanceof Entry) {
            // @TODO: Add possibility to hook into here.
            $this->redirect('list', null, null, [], $this->settings['listPid'], null, 404);
        }
        $mapId = sprintf('map_%s', StringUtility::getUniqueId());
        $assignedViewValues = compact('entry', 'demand', 'mapId');
        $event = new Events\Entry\ShowActionEvent($this->request, $demand, $assignedViewValues);
        $this->view->assignMultiple($event->getAssignedViewValues());
        return $this->htmlResponse();
    }

    public function mapJavaScriptAction(string $mapId, ?Entry $mainEntry = null, ?Demand $demand = null): ResponseInterface
    {
        $this->view->assign('mapId', $mapId);
        // These are only some defaults and can be overridden via a hook method
        $map = $this->mapBuilder->createMap($mapId);

        // Set map options configurable via TypoScript, option:value => maxZoom:17
        $mapOptions = isset($this->settings['map']['options']) ? GeneralUtility::trimExplode(',', $this->settings['map']['options']) : [];

        if (is_array($mapOptions) && !empty($mapOptions)) {
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

            if (!empty($iconFile)) {
                $marker->addIconFromPath(Utility::stripPathSite(GeneralUtility::getFileAbsFileName($iconFile)));
            }

            $infoWindowParameters = [];

            // Current marker does not need detail link
           if (!$isCurrentMarker) {
                // @extensionScannerIgnoreLine
                $detailsPid = (int)($this->settings['singlePid'] ?? $this->getTyposcriptFrontendController()->id);
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $infoWindowParameters['detailLink'] =
                    $uriBuilder
                        ->reset()
                        ->setRequest($this->request)
                        ->setTargetPageUid($detailsPid)->uriFor(
                            'show',
                            ['entry' => $entry],
                            'Entry',
                            'BzgaBeratungsstellensuche',
                            'MapJavaScript'
                        );
            }
            // Create Info Window
            $popUp = $this->mapBuilder->createPopUp('popUp');

            // Call hook functions for modify the info window
            if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyInfoWindow'] ?? false) {
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
            if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMarker'] ?? false) {
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
        if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['ViewHelpers/Widget/Controller/MapController.php']['modifyMap'] ?? false) {
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
        return $this->request->getAttribute('frontend.controller');
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

    private function addDemandRequestArgumentFromSession(): void
    {
        $demand = $this->sessionService->restoreFromSession();
        if ($demand) {
            $this->request = $this->request->withArguments(['demand' => $demand]);
        }
    }

    private function resetDemand(): void
    {
        if ($this->request->hasArgument('reset')) {
            $this->sessionService->cleanUpSession();
            $this->request = $this->request->withArguments(['demand' => null]);
        }
    }
}
