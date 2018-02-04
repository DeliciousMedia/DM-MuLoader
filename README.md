# DM MuLoader

Automatically loads WordPress Must-Use plugins from within subdirectories of the mu-plugins folder.

## Installation

Install via Composer (`composer require deliciousmedia/dm-muloader`), or just clone/copy in to your mu-plugins folder.

You'll need to copy `dm-muloader.php` to the main mu-plugins folder or otherwise include `dm-muloader-plugin.php`.

## Usage

The plugin caches the list of plugin files it has found in the `dmmuloader_muplugins` site transient.

If you add a new mu-plugin you'll want to delete the transient to force the plugin to find new plugins.

The most reliable way to do this is to add `wp transient delete dmmuloader_muplugins --network` to your post-deployment script.

You can also do this by visiting the plugins page and viewing the Must-Use 'tab'.

---
Built by the team at [Delicious Media](https://www.deliciousmedia.co.uk/), a specialist WordPress development agency based in Sheffield, UK.