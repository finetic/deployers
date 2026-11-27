# FrankenPHP-runtime voor Statamic

De workflow **FrankenPHP runtime** bouwt voor PHP 8.4 en 8.5,
elk voor Linux amd64 en arm64. De runtime bevat Imagick, de ImageMagick-CLI, Redis
met LZF, LZ4 en Zstd, PDO MySQL, GMP en de gedeelde PHP-extensies voor Statamic.

## Bijwerken en publiceren

1. Werk zo nodig de upstreamtag onder `FRANKENPHP_IMAGES` in `docker-bake.hcl` bij.
   De Imagick-, Redis- en PIE-versies staan in `Dockerfile`.
2. Open een PR in `deployers` en controleer de builds en smoke-tests voor
   beide PHP-versies en architecturen.
3. Merge de PR en start **FrankenPHP runtime** handmatig op `main`.
   De workflow gebruikt de secrets `nexus_docker_username` en
   `nexus_docker_password` en publiceert in Nexus.
   De builderaccounts van de projecten hebben leesrechten nodig.
   Het publicatieaccount heeft schrijfrechten nodig en Nexus moet updates
   van dezelfde tags toestaan.
4. Controleer de tags `frankenphp-php8.4` en `frankenphp-php8.5` onder
   `docker.finetic.dev/finetic/runtime/frankenphp`.
5. Bouw eerst één project per PHP-versie op acceptatie. Bouw daarna de
   overige projecten opnieuw met dezelfde verwijzing in `PHP_RUNTIME_IMAGE`.

Elke publicatie werkt dezelfde tags bij. Nieuwe projectbuilds met een verse
pull gebruiken de laatst gepubliceerde runtime. Voor rollback rol je het
laatst werkende applicatie-image opnieuw uit. Voor een tijdelijke rebuild
op een eerdere runtime kun je de eerder vastgelegde runtime-digest meegeven
via `PHP_RUNTIME_IMAGE`, zolang Nexus die digest bewaart.
