=== Plugin Updater Skeleton ===
Contributors: alejandro-gimeno
Donate link: https://codigoverso.es
Tags: actualizador, github, actualización, esqueleto
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin vacío con lógica de actualización desde GitHub (releases/tags). Copia esta carpeta en tu plugin y ajusta la configuración en plugin-updater-skeleton.php.

== Descripción ==
Este esqueleto integra actualizaciones automáticas en WordPress consultando GitHub (Releases o Tags). Simula el comportamiento del repositorio oficial de WordPress: muestra actualizaciones disponibles, pantalla de detalles con descripción y changelog, y permite actualizar el plugin desde el zip de GitHub.

== Instalación ==
1. Copia la carpeta `plugin-updater-skeleton` en `wp-content/plugins/`.
2. Activa el plugin desde el panel de administración.
3. Edita `plugin-updater-skeleton.php` y configura `github_user`, `github_repo`, `branch` y, opcionalmente, `access_token`.
4. Publica una release en GitHub con el tag `v1.0.1` (por ejemplo) y adjunta el zip o usa `zipball_url`.

== Uso en otros plugins ==
- Copia la carpeta `includes/` y el bootstrap del updater a tu plugin.
- En el archivo principal de tu plugin, instancia `new PUS_GitHub_Plugin_Updater($config)` dentro del hook `init`.
- El updater mostrará actualizaciones, la pantalla de detalles y gestionará la descarga/instalación desde GitHub.

== FAQ ==
= ¿Funciona con repos privados? =
Sí. Añade un token personal como constante `GITHUB_ACCESS_TOKEN` o vía el filtro `pus_updater_config`.

= ¿Cómo se decide la versión? =
Se usa el campo `Version` del encabezado del plugin comparado con `tag_name` o el primer `tag` del repositorio.

== Changelog ==
= 1.0.0 =
- Versión inicial del esqueleto con integración de la API de plugins y comprobación de actualizaciones vía transients.
