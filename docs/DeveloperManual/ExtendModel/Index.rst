.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

.. _extendModel:

Erweiterung des Model
=====================

In diesem Kapitel lernen Sie, wie Sie im Model und der Datenbank neue Felder zu bestehenden Models hinzufügen können.

Die Erweiterung ermöglicht die dynamische Überschreibung der Basismodels Entry, Category und Demand ohne dass diese über
Vererbung erweitert werden. Dafür wird eine Proxy-Klasse unter Verwendung des TYPO3 Caching Frameworks im Ordner ``typo3temp/Cache/Code/bzga_beratungsstellensuche`` abgespeichert.
Es ist wichtig zu wissen, dass dieses Konzept existiert.

.. warning:: Dieses Konzept hat folgende Nachteile:

 	- Benutzen Sie keine use statements am Anfang der Datei. Diese werden ignoriert!
 	- Es ist nicht möglich eine bestehende Methode zu überschreiben.

.. only:: html

	.. contents::
		:local:
		:depth: 1

1) Neue Felder hinzufügen
-------------------------
Der Extension-Key in diesem Beispiel lautet: ``bzga_beratungsstellensuche_extended``.

.. note:: Wir gehen in diesem Beispiel davon aus, dass Sie die grundlegenden Kenntnisse zur Erstellung einer TYPO3-Erweiterung besitzen.

Es werden grundsätzlich 2 Dateien benötigt.


SQL
"""
Erstellen Sie die Datei ``ext_tables.sql`` im Root-Verzeichnis Ihrer Erweiterung mit folgendem Inhalt:

.. code-block:: sql

	#
	# Table structure for table 'tx_bzgaberatungsstellensuche_domain_model_entry'
	#
	CREATE TABLE tx_bzgaberatungsstellensuche_domain_model_entry (
		alternative_email varchar(255) DEFAULT '' NOT NULL,
	);


TCA
"""
Erstellen Sie eine Datei namens ``tx_bzgaberatungsstellensuche_domain_model_entry.php`` im Ordner ``Configuration/TCA/Overrides/``.

.. code-block:: php

	<?php
	defined('TYPO3_MODE') or die();

	$fields = array(
		'alternative_email' => array(
			'exclude' => 1,
			'label' => 'Alternative E-Mail Adresse',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard'
							)
						),
						'JSopenParams' => 'height=600,width=800,status=0,menubar=0,scrollbars=1'
					)
				),
			),
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_bzgaberatungsstellensuche_domain_model_entry', $fields);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_bzgaberatungsstellensuche_domain_model_entry', 'alternative_email');


2) Klasse registrieren
----------------------

Damit die Erweiterung bzga_beratungsstellensuche Kenntnis über Ihre Erweiterung hat, erstellen Sie bitte im Ordner ``Configuration/DomainModelExtension`` eine Datei namens
BzgaBeratungsstellensuche.txt mit folgendem Inhalt:

.. code-block:: text

	Domain/Model/Entry

Eigene Klasse
"""""""""""""
Erstellen Sie nun eine Datei ``packages/bzga_beratungsstellensuche_extended/Classes/Domain/Model/Entry.php``.

.. code-block:: php

	<?php

	namespace Bzga\BzgaBeratungsstellensucheExtended\Domain\Model;

	/**
	 * Entry
	 */
	class Entry extends \Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry {

		/**
		 * @var string
		 */
		protected $alternativeEmail;

		/**
		 * @return string
		 */
		public function getAlternativeEmail()
		{
			return $this->alternativeEmail;
		}

		/**
		 * @param string $alternativeEmail
		 */
		public function setAlternativeEmail($alternativeEmail)
		{
			$this->alternativeEmail = $alternativeEmail;
		}
	}

Clear system cache
""""""""""""""""""
Jetzt müssen Sie nur noch den **system cache** leeren. Danach können Sie überall auf dieses Feld zurückgreifen.

.. hint:: In den meisten Fällen wird die Anforderung doch etwas komplexer sein. Sollten Sie nicht weiterkommen, stellen wir Ihnen gerne eine Erweiterung zu Anschauungszwecken zur Verfügung.


XML-Importer
""""""""""""
Möchten Sie das neue Feld aus dem obigen Beispiel aus dem XML auslesen und in der Datenbank abspeichern, müssen Sie in unserem Beispiel noch einen Slot erstellen.

.. hint:: Zum Thema Slots schauen Sie auch hier: :ref:`hooksSignalsSignals`.

Zunächst registrieren Sie den Slot für das entsprechende Signal in der  ``ext_localconf.php``

.. code-block:: php

	<?php

	// Extend name converter
	$signalSlotDispatcher->connect(
		\Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter::class,
		\Bzga\BzgaBeratungsstellensuche\Events::SIGNAL_MapNames,
		\YOUR_VENDOR\YOUR_EXTKEY\Slots\EntryNameConverter::class,
		'mapNames'
	);

Jetzt müssen Sie noch die dazugehörige Slot-Klasse schreiben:

.. code-block:: php

	<?php
	namespace YOUR_VENDOR\YOUR_EXTKEY\Slots;

	class EntryNameConverter
	{

		/**
		* @param array $mapNames
		* @return array
		*/
		public function mapNames(array $mapNames = array())
		{
			$mapNames = array_merge($mapNames, array(
				'alt_email' => 'alternative_email',
			));

			return array(
				'extendedMapNames' => $mapNames,
			);
		}
	}

.. hint:: In diesem Beispiel enthält das XML im Knoten **entry** einen weiteren Unterknoten mit der Bezeichnung **alt_email** und wird dem neuen Feld **alternative_email** zugeordnet. Der restliche Ablauf ist Magie.
