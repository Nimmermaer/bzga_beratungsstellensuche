<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Property\TypeConverter;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\ExternalIdInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\Exception\DownloadException;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverterBeforeInterface;

use Bzga\BzgaBeratungsstellensuche\Property\TypeConverterInterface;
use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File as FalFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;

/**
 * @author Sebastian Schreiber
 */
class ImageLinkConverter implements TypeConverterBeforeInterface
{
    /**
     * Folder where the file upload should go to (including storage).
     */
    public const CONFIGURATION_UPLOAD_FOLDER = 1;

    /**
     * How to handle a upload when the name of the uploaded file conflicts.
     */
    public const CONFIGURATION_UPLOAD_CONFLICT_MODE = 2;

    /**
     * Whether to replace an already present resource.
     * Useful for "maxitems = 1" fields and properties
     * with no ObjectStorage annotation.
     */
    public const CONFIGURATION_ALLOWED_FILE_EXTENSIONS = 4;

    private string $defaultUploadFolder = '1:/user_upload/tx_bzgaberatungsstellensuche';

    private string $tempFolder = 'typo3temp/tx_bzgaberatungsstellensuche/';

    /**
     * One of 'cancel', 'replace', 'changeName'
     */
    private string $defaultConflictMode = 'replace';

    private ?ResourceFactory $resourceFactory = null;

    private static array $imageMimeTypes = [
        'bmp' => 'image/bmp',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
    ];

    private DataHandler $dataHandler;

    public function __construct(?DataHandler $dataHandler = null, ResourceFactory $resourceFactory = null)
    {
        if ($dataHandler === null) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        }
        if ($resourceFactory === null) {
            $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        }
        $this->dataHandler = $dataHandler;
        $this->dataHandler->bypassAccessCheckForRecords = true;
        $this->dataHandler->admin = true;
    }

    /**
     * @inheritDoc
     */
    public function supports($source, string $type = TypeConverterInterface::CONVERT_BEFORE)
    {
        if (! $source instanceof ImageLink) {
            return false;
        }

        return true;
    }

    public function convert($source, array $configuration = null)
    {
        // Check if we have no image url, return 0 if not
        if ($source->getExternalUrl() === '') {
            return 0;
        }

        // First of all we delete the old references
        /** @var AbstractEntity|ExternalIdInterface $entity */
        $entity = $configuration['entity'];

        $fileReferenceData = [
            'table_local' => 'sys_file',
            'tablenames' => $configuration['tableName'],
            'uid_foreign' => $configuration['tableUid'],
            'fieldname' => $configuration['tableField'],
            'pid' => $entity->getPid(),
        ];

        if (! $entity->_isNew()) {
            $this->deleteOldFileReferences($fileReferenceData);
        }

        try {
            $fileReferenceData['uid_local'] = $this->getFileUid($source, $entity);

            $fileReferenceUid = uniqid('NEW_', false);
            $dataMap = [];
            $dataMap['sys_file_reference'][$fileReferenceUid] = $fileReferenceData;
            $this->dataHandler->start($dataMap, []);
            $this->dataHandler->process_datamap();

            return $this->dataHandler->substNEWwithIDs[$fileReferenceUid];
        } catch (\Exception $e) {
            // TODO: Add logging here
        }

        // We fail gracefully here by intention
        return 0;
    }

    private function downloadFile(ImageLink $source, ExternalIdInterface $entity): string
    {
        $imageContent = GeneralUtility::getUrl($source->getExternalUrl());

        if ($imageContent === false) {
            throw new DownloadException(sprintf('The file %s could not be downloaded', $source->getExternalUrl()));
        }

        $imageInfo = getimagesizefromstring($imageContent);
        if (is_array($imageInfo)) {
            $extension = $this->getExtensionFromMimeType($imageInfo['mime']);
            if ($extension) {
                $pathToUploadFile = Environment::getPublicPath() . '/' . $this->tempFolder . GeneralUtility::hmac($entity->getExternalId()) . '.' . $extension;
                $error = GeneralUtility::writeFileToTypo3tempDir($pathToUploadFile, $imageContent);
//                // due to Bug https://forge.typo3.org/issues/90063#change-431917 error always conatains something.
//                // therefore just testing if file got uploaded
//                $hasError = !@is_file($pathToUploadFile);
                if ($error) {
                    throw new TypeConverterException($error, 1_399_312_443);
                }
            } else {
                throw new TypeConverterException('Mime type ' . $imageInfo['mime'] . ' is not allowed as image.', 1_399_312_443);
            }
        } else {
            throw new TypeConverterException('File is not an Image as expected', 1_399_312_443);
        }
        return $pathToUploadFile;
    }

    private function importResource(string $tempFilePath): FalFile
    {
        if (class_exists(FileNameValidator::class)) {
            if (! GeneralUtility::makeInstance(FileNameValidator::class)->isValid($tempFilePath)) {
                throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1_399_312_430);
            }
        } else {
            if (! GeneralUtility::makeInstance(FileNameValidator::class)->isValid($tempFilePath)) {
                throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1_399_312_430);
            }
        }

        $uploadFolder = $this->resourceFactory->retrieveFileOrFolderObject($this->defaultUploadFolder);

        return $uploadFolder->addFile($tempFilePath, null, $this->defaultConflictMode);
    }

    /**
     * @return mixed
     */
    private function getExtensionFromMimeType(string $mimeType)
    {
        return array_search($mimeType, self::$imageMimeTypes, false);
    }

    private function deleteOldFileReferences(array $fileReferenceData): void
    {

        if (isset($fileReferenceData['uid_local'])) {
            unset($fileReferenceData['uid_local']);
        }

        $databaseConnection = $this->getDatabaseConnectionForTable('sys_file_reference');

        $where = [];
        foreach ($fileReferenceData as $key => $value) {
            $where[$key] = $value;
        }

        $databaseConnection->delete('sys_file_reference', $where);
    }

    private function getDatabaseConnectionForTable(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }

    /**
     * @param AbstractEntity|ExternalIdInterface $entity
     */
    private function getFileUid(ImageLink $source, $entity): ?int
    {
        // First we check if we already have a file with the identifier in the database (if source identifier is set)
        if ($source->getIdentifier() !== '') {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $row = $queryBuilder->select('*')
                ->from('sys_file')
                ->where($queryBuilder->expr()->eq('external_identifier', $queryBuilder->createNamedParameter($source->getIdentifier())))
                ->executeQuery()
                ->fetchAssociative();
            if ($row) {
                return $row['uid'];
            }
            $pathToUploadFile = $this->downloadFile($source, $entity);

            try {
                $falFile = $this->importResource($pathToUploadFile);
                $this->getDatabaseConnectionForTable('sys_file')->update(
                    'sys_file',
                    ['external_identifier' => $source->getIdentifier()],
                    ['uid' => $falFile->getUid()]
                );

                return $falFile->getUid();
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }
}
