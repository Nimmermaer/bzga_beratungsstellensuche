<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\EventListener;

use Bzga\BzgaBeratungsstellensuche\Utility\ExtensionManagementUtility;
use Bzga\BzgaBeratungsstellensuche\Utility\IconUtility;
use Bzga\BzgaBeratungsstellensuche\Utility\TemplateLayout;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @author Sebastian Schreiber
 */
class ElementPreviewEventListener
{
    /**
     * Extension key
     *
     * @var string
     */
    public const KEY = 'bzgaberatungsstellensuche';

    /**
     * Path to the locallang file
     *
     * @var string
     */
    public const LLPATH = 'LLL:EXT:%s/Resources/Private/Language/locallang_be.xlf:';

    /**
     * Table information
     *
     * @var array
     */
    public array $tableData = [];

    /**
     * Flexform information
     *
     * @var array
     */
    public array $flexformData = [];

    /**
     * @var TemplateLayout
     */
    protected TemplateLayout $templateLayoutsUtility;

    /**
     * @var IconUtility
     */
    protected IconUtility $iconUtility;

    /**
     * PageLayoutView constructor.
     */
    public function __construct()
    {
        $this->templateLayoutsUtility = GeneralUtility::makeInstance(TemplateLayout::class);
        $this->iconUtility = GeneralUtility::makeInstance(IconUtility::class);
    }

    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        if (str_contains($event->getRecord()['list_type'], 'bzgaberatungsstellensuche')) {
            $this->tableData = [];
            $result = '<strong>' . $this->sL('pi1_title') . '</strong><br>';
            $result .= $this->generateBackendPreview($event->getRecord());
            $event->setPreviewContent($result);
        }
    }

    private function getDetailPidSetting(): void
    {
        $detailPid = (int)$this->getFieldFromFlexform('settings.singlePid', 'additional');
        if ($detailPid > 0) {
            $content = $this->getPageRecordData($detailPid);

            $this->tableData[] = [
                $this->sL('flexforms_additional.singlePid'),
                $content,
            ];
        }
    }

    private function getFormFieldsSetting(): void
    {
        $formFields = $this->getFieldFromFlexform('settings.formFields', 'additional');
        if ($formFields) {
            $formFieldsArray = GeneralUtility::trimExplode(',', $formFields);
            $formFieldsLabels = [];
            foreach ($formFieldsArray as $formField) {
                $formFieldsLabels[] = $this->sL('flexforms_additional.formFields.' . $formField);
            }
            $this->tableData[] = [
                $this->sL('flexforms_additional.formFields'),
                implode(',', $formFieldsLabels),
            ];
        }
    }

    private function getListPidSetting(): void
    {
        $listPid = (int)$this->getFieldFromFlexform('settings.listPid', 'additional');

        if ($listPid > 0) {
            $content = $this->getPageRecordData($listPid);

            $this->tableData[] = [
                $this->sL('flexforms_additional.listPid'),
                $content,
            ];
        }

    }

    private function getListItemsPerPageSetting(): void
    {
        $itemsPerPage = (int)$this->getFieldFromFlexform('settings.list.itemsPerPage', 'additional');

        if ($itemsPerPage > 0) {
            $this->tableData[] = [
                $this->sL('flexforms_additional.itemsPerPage'),
                $itemsPerPage,
            ];
        }
    }

    private function getBackPidSetting(): void
    {
        $listPid = (int)$this->getFieldFromFlexform('settings.backPid', 'additional');

        if ($listPid > 0) {
            $content = $this->getPageRecordData($listPid);

            $this->tableData[] = [
                $this->sL('flexforms_additional.backPid'),
                $content,
            ];
        }
    }

    private function getPageRecordData(int $detailPid): string
    {
        $pageRecord = BackendUtilityCore::getRecord('pages', $detailPid);

        if (is_array($pageRecord)) {
            return $this->iconUtility->getIconForRecord('pages', $pageRecord);
        }

        $this->addFlashMessage($detailPid);

        return '';
    }

    private function addFlashMessage(int $detailPid): void
    {
        $text = sprintf(
            $this->sL('pagemodule.pageNotAvailable'),
            $detailPid
        );
        /** @var FlashMessage $message */
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $text,
            '',
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($message);
    }

    private function getTemplateLayoutSettings(int $pageUid): void
    {
        $title = '';
        $field = $this->getFieldFromFlexform('settings.templateLayout', 'template');

        // Find correct title by looping over all options
        if (!empty($field)) {
            foreach ($this->templateLayoutsUtility->getAvailableTemplateLayouts($pageUid) as $layout) {
                if ($layout[1] === $field) {
                    $title = $layout[0];
                }
            }
        }

        if (!empty($title)) {
            $this->tableData[] = [
                $this->sL('flexforms_template.templateLayout'),
                $this->sL($title),
            ];
        }
    }

    private function getStartingPoint(): void
    {
        $value = $this->getFieldFromFlexform('settings.startingpoint');

        if (!empty($value)) {
            $pagesOut = [];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $rawPagesRecords = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in('uid', GeneralUtility::intExplode(',', $value, true))
                )
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rawPagesRecords as $page) {
                $pagesOut[] = htmlspecialchars(BackendUtilityCore::getRecordTitle(
                        'pages',
                        $page
                    )) . ' (' . $page['uid'] . ')';
            }

            $recursiveLevel = (int)$this->getFieldFromFlexform('settings.recursive');
            $recursiveLevelText = '';
            if ($recursiveLevel === 250) {
                $recursiveLevelText = $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.5');
            } elseif ($recursiveLevel > 0) {
                $recursiveLevelText = $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.' . $recursiveLevel);
            }

            if (!empty($recursiveLevelText)) {
                $recursiveLevelText = '<br />' .
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.recursive') . ' ' .
                    $recursiveLevelText;
            }

            $this->tableData[] = [
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.startingpoint'),
                implode(', ', $pagesOut) . $recursiveLevelText,
            ];

        }
    }

    private function renderSettingsAsTable(): string
    {
        if (count($this->tableData) === 0) {
            return '';
        }

        $content = '';

        foreach ($this->tableData as $line) {
            $content .= '<strong>' . $line[0] . '</strong>' . ' ' . $line[1] . '<br />';
        }

        return '<pre style="white-space:normal">' . $content . '</pre>';
    }

    private function getFieldFromFlexform(string $key, string $sheet = 'sDEF')
    {
        $flexform = $this->flexformData;

        if (isset($flexform['data'])) {
            $flexform = $flexform['data'];
            if (is_array($flexform) && is_array($flexform[$sheet]) && is_array($flexform[$sheet]['lDEF'])
                && is_array($flexform[$sheet]['lDEF'][$key]) && isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
            ) {
                return $flexform[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }

    private function sL(string $label): string
    {
        $registeredExtensionKeys = ExtensionManagementUtility::getRegisteredExtensionKeys();
        foreach ($registeredExtensionKeys as $extensionKey) {
            $fullPathToLabel = sprintf(self::LLPATH, $extensionKey) . $label;
            $translation = $this->getLanguageService()->sL($fullPathToLabel);
            if ($translation !== '') {
                return $translation;
            }
        }

        return '';
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function generateBackendPreview(array $record): string
    {
        $this->flexformData = GeneralUtility::xml2array($record['pi_flexform']);
        switch ($record['list_type']) {
            case 'bzgaberatungsstellensuche_pi1':
                $this->getStartingPoint();
                $this->getDetailPidSetting();
                $this->getListPidSetting();
                $this->getFormFieldsSetting();
                $this->getListItemsPerPageSetting();
                break;
            case 'bzgaberatungsstellensuche_detail':
                $this->getListPidSetting();
                $this->getBackPidSetting();
                $this->getDetailPidSetting();
                break;
            case 'bzgaberatungsstellensuche_form':
                $this->getListPidSetting();
                $this->getFormFieldsSetting();
                break;
            default:
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche'][self::class]['extensionSummary'] ?? '') {
            $params = [
                'action' => LocalizationUtility::translate('LLL:EXT:bzga_beratungsstellensuche/Resources/Private/Language/locallang_be.xlf:' . $record['list_type']),
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche'][self::class]['extensionSummary'] as $reference) {
                GeneralUtility::callUserFunction($reference, $params, $this);
            }
        }
        $this->getTemplateLayoutSettings($record['pid']);
        return $this->renderSettingsAsTable();
    }

}
