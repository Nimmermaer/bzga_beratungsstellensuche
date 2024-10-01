<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Service\Importer;

use Bzga\BzgaBeratungsstellensuche\Domain\Manager\CategoryManager;
use Bzga\BzgaBeratungsstellensuche\Domain\Manager\EntryManager;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Serializer;
use Bzga\BzgaBeratungsstellensuche\Domain\ValueObject\ImportAuthorization;
use Bzga\BzgaBeratungsstellensuche\Service\Importer\Exception\ContentCouldNotBeFetchedException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use UnexpectedValueException;

/**
 * @author Sebastian Schreiber
 */
abstract class AbstractImporter implements ImporterInterface
{
    protected Serializer $serializer;

    protected EntryManager $entryManager;

    protected CategoryManager $categoryManager;

//    protected Dispatcher $signalSlotDispatcher;

    public function importFromFile(string $file, int $pid = 0): void
    {
        $file = GeneralUtility::getFileAbsFileName($file);

        if (! file_exists($file)) {
            throw new FileDoesNotExistException(sprintf('The file %s does not exists', $file));
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new ContentCouldNotBeFetchedException('The content could not be fetched');
        }

        $this->import($content, $pid);
    }

    public function importFromUrl(string $url, ImportAuthorization $importAuthorization, int $pid = 0): void
    {
        if (! GeneralUtility::isValidUrl($url)) {
            throw new UnexpectedValueException(sprintf('This is not a valid url: "%s"', $url));
        }

        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        $response = $requestFactory->request($importAuthorization->getUrl(), 'POST', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $importAuthorization->getClientId(),
                'client_secret' => $importAuthorization->getClientSecret(),
            ],
        ]);

        $json = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);

        if ($json === false) {
            throw new UnexpectedValueException(sprintf('Could not retrieve token from url "%s"', $url));
        }

        $headers = [
            'Authorization' => 'Bearer ' . $json['access_token'],
            'Accept'        => 'application/xml',
        ];

        $response = $requestFactory->request($url, 'GET', [
            'headers' => $headers,
            'query' => [
                '_format' => 'xml',
            ],
        ]);

        $content = $response->getBody()->__toString();

        if ($content === false) {
            throw new ContentCouldNotBeFetchedException('The content could not be fetched');
        }

        $this->import($content, $pid);
    }

    public function injectCategoryManager(CategoryManager $categoryManager): void
    {
        $this->categoryManager = $categoryManager;
    }

    public function injectEntryManager(EntryManager $entryManager): void
    {
        $this->entryManager = $entryManager;
    }

    public function injectSerializer(Serializer $serializer): void
    {
        $this->serializer = $serializer;
    }

//    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher): void
//    {
//        $this->signalSlotDispatcher = $signalSlotDispatcher;
//    }
}
