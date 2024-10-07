<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Utility;

use Bzga\BzgaBeratungsstellensuche\EventListener\ElementPreviewEventListener;
use TYPO3\CMS\Backend\Routing\UriBuilder;

use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IconUtility
{
    private IconFactory $iconFactory;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function getIconForRecord(string $table, array $record): string
    {
        $data = '<span data-toggle="tooltip" data-placement="top" data-title="id=' . $record['uid'] . '">'
                . $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render()
                . '</span> ';
        $content = BackendUtilityCore::wrapClickMenuOnIcon(
            $data,
            $table,
            $record['uid']
        );

        $linkTitle = htmlspecialchars(BackendUtilityCore::getRecordTitle($table, $record));

        if ($table === 'pages') {
            $id = $record['uid'];
            $currentPageId = (int)$GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'];
            $link = htmlspecialchars($this->getEditLink($record, $currentPageId));
            $switchLabel = $this->getLanguageService()->sL(ElementPreviewEventListener::LLPATH . 'pagemodule.switchToPage');
            $content .= ' <a href="#" data-toggle="tooltip" data-placement="top" data-title="' . $switchLabel . '" onclick=\'top.jump("' . $link . '", "web_layout", "web", ' . $id . ');return false\'>' . $linkTitle . '</a>';
        } else {
            $content .= $linkTitle;
        }

        return $content;
    }

    protected function getEditLink(array $row, int $currentPageUid): string
    {
        $editLink = '';
        $localCalcPerms = $GLOBALS['BE_USER']->calcPerms(BackendUtilityCore::getRecord('pages', $row['uid']));
        $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
        if ($permsEdit) {
            $returnUrl = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('web_layout', ['id' => $currentPageUid]);
            $editLink = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('web_layout', [
                'id' => $row['uid'],
                'returnUrl' => $returnUrl,
            ]);
        }

        return (string)$editLink;
    }

    public function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
