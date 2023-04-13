Beratungsstellensuche
=====================
[![CI Status](https://github.com/sabbelasichon/bzga_beratungsstellensuche/workflows/CI/badge.svg)](https://github.com/sabbelasichon/bzga_beratungsstellensuche/actions)

Die Erweiterung ermöglicht die Pflege und Darstellung von Beratungsstellen (Locations) auf Basis des CMS TYPO3.

Durch die Verwendung von zahlreichen Hooks, Signals und TypoScript-Settings kann die Erweiterung flexibel an die eigenen Anforderungen und Wünsche angepasst werden.

Über einen bereitgestellten Importer-Task können Beratungsstellen aus dem System von http://www.bzga-rat.de/adm über die vorhandene XML-Schnitstelle importiert werden.

Route enhancer für sprechende URLS, füge folgendes zu sites/<sitename>/config.yaml hinzu:

```
imports:
  - { resource: 'EXT:bzga_beratungsstellensuche/Configuration/Routes/RouteEnhancers.yaml'}
```
