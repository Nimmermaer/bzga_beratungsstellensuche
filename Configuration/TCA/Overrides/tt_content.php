<?php

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

defined('TYPO3') or die();

$extKey = 'bzga_beratungsstellensuche';

$plugins = [
    'Pi1',
    'Form',
    'Detail',
];

foreach ($plugins as $plugin) {
    $pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        $extKey,
        $plugin,
        'LLL:EXT:bzga_beratungsstellensuche/Resources/Private/Language/locallang_be.xlf:' . strtolower($plugin) . '_title'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'recursive,select_key,pages';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:' . $extKey . '/Configuration/FlexForms/' . $pluginSignature . '.xml'
    );
}

unset($extKey);
