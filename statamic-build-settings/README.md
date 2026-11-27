# Centrale Statamic-build

De buildactie gebruikt de centrale Dockerfile zodra het project
`docker/statamic-build.json` bevat. Een leeg object kiest de standaardbuild
met PHP 8.5 en Node 26. Zonder instellingen kiest de actie de lokale
`docker/prod/Dockerfile`. De projectmap is in beide gevallen de buildcontext
en levert onder meer de Caddy-configuratie en de applicatiecode.

De gedeelde PHP-instellingen staan in `frankenphp-runtime/statamic.ini` en
worden bij het bouwen van de Statamic-image geactiveerd. Gebruik
`docker/php.ini` in een project alleen voor afwijkingen, bijvoorbeeld
`memory_limit=1536M`. PHP leest dit bestand na de gedeelde instellingen.

## Projectinstellingen

- `PHP_RUNTIME_IMAGE` en `NODE_IMAGE` kiezen de PHP-runtime en Node-versie.
- `COMPOSER_GIT=1` installeert Git in de Composer-buildstage.
- `LEGACY_PUBLIC_STORAGE=1` maakt de extra opslagmappen onder `public/storage` aan.
- `PERSIST_ADDON_SETTINGS=1` brengt de add-oninstellingen onder op het bestaande
  opslagpad dat het entrypoint initialiseert.
- `prod-default-entrypoint` gebruikt het entrypoint van de gedeelde runtime.
- `prod-themed` ondersteunt LVO Weert. De Bake-matrix levert `THEME` per site.
  Bij `APP_SOURCE=app_code` wordt het resultaat van die frontendbuild pas in
  de eindlaag gekopieerd, zodat de backendlaag wordt gedeeld.
- `prod-waimo` behoudt het revisielabel en de publieke opslagmappen.
  Met `APP_SOURCE=app_waimo` wordt alleen de geselecteerde applicatiecode
  meegenomen. De Waimo Compose-template in Puppet levert de webhealthcheck.

## Uitrol

Merge Deployers eerst en publiceer vervolgens de PHP 8.4- en 8.5-runtimes
met GMP en de ImageMagick-CLI. Rol voor Waimo CMS eerst de Puppet-template
met de webhealthcheck uit. Valideer een standaardproject, LVO Weert en Waimo CMS op acceptatie
voordat je de overige projectwijzigingen uitrolt. De applicatiebuild ontvangt
Composer-auth als BuildKit-secret.

Zolang de lokale Dockerfile beschikbaar is, kun je de centrale build voor een
project terugdraaien door `docker/statamic-build.json` weg te halen en opnieuw te
bouwen. Afzonderlijk daarvan kun je bij een deployprobleem het vorige
applicatie-image opnieuw uitrollen.
