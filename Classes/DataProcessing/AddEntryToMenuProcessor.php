<?php

namespace Bzga\BzgaBeratungsstellensuche\DataProcessing;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Add the current entry to any menu, e.g. breadcrumb
 *
 * 20 = Bzga\BzgaBeratungsstellensuche\DataProcessing\AddEntryToMenuProcessor
 * 20.menus = breadcrumbMenu,specialMenu
 *
 */
final class AddEntryToMenuProcessor implements DataProcessorInterface
{

    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        if (!$processorConfiguration['menus']) {
            return $processedData;
        }
        $record = $this->getRecord();
        if ($record) {
            $menus = GeneralUtility::trimExplode(',', $processorConfiguration['menus'], true);
            foreach ($menus as $menu) {
                if (isset($processedData[$menu])) {
                    $this->addRecordToMenu($record, $processedData[$menu]);
                }
            }
        }
        return $processedData;
    }

    private function addRecordToMenu(array $record, array &$menu): void
    {
        foreach ($menu as &$menuItem) {
            $menuItem['current'] = 0;
        }

        $menu[] = [
            'data' => $record,
            'title' => $record['title'],
            'active' => 1,
            'current' => 1,
            'link' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'isEntry' => true
        ];
    }

    protected function getRecord(): array
    {
        $entryId = 0;
        $vars = GeneralUtility::_GET('tx_bzgaberatungsstellensuche_pi1');
        if (isset($vars['entry'])) {
            $entryId = (int)$vars['entry'];
        }

        if ($entryId) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_bzgaberatungsstellensuche_domain_model_entry');
            $row = $queryBuilder
                ->select('*')
                ->from('tx_bzgaberatungsstellensuche_domain_model_entry')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($entryId, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetch();

            if ($row) {
                $row = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay('tx_bzgaberatungsstellensuche_domain_model_entry', $row, $this->getCurrentLanguage());
            }

            return empty($row) ? [] : $row;
        }
        return [];
    }

    private function getCurrentLanguage(): int
    {
        $languageId = 0;
        $context = GeneralUtility::makeInstance(Context::class);
        try {
            $languageId = $context->getPropertyFromAspect('language', 'contentId');
        } catch (AspectNotFoundException $e) {
            // do nothing
        }

        return (int)$languageId;
    }

    private function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
