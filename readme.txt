# Plugin Updater Skeleton

- Contributors: alejandro-gimeno
- Donate link: https://codigoverso.es
- Tags: actualizador, github, actualización, esqueleto
- Requires at least: 5.8
- Tested up to: 6.6
- Requires PHP: 7.4
- Stable tag: 1.0.0
- License: GPLv2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin vacío con lógica de actualización desde GitHub (releases/tags). Copia esta carpeta en tu plugin y ajusta la configuración en `plugin-updater-skeleton.php`.

## Descripción
Este esqueleto integra actualizaciones automáticas en WordPress consultando GitHub (Releases o Tags). Simula el comportamiento del repositorio oficial de WordPress:
- Muestra actualizaciones disponibles en la lista de plugins.
- Provee la pantalla de detalles con descripción y changelog.
- Permite actualizar el plugin desde el zip de GitHub.

## Instalación
1. Copia la carpeta `plugin-updater-skeleton` en `wp-content/plugins/`.
2. Activa el plugin desde el panel de administración.
3. Edita `plugin-updater-skeleton.php` y configura `github_user`, `github_repo`, `branch` y, opcionalmente, `access_token`.
4. Publica una release en GitHub con el tag `v1.0.1` (por ejemplo) y adjunta el zip o usa `zipball_url`.

## Uso en otros plugins
- Copia la carpeta `includes/` y el bootstrap del updater a tu plugin.
- En el archivo principal de tu plugin, instancia `new PUS_GitHub_Plugin_Updater($config)` dentro del hook `init`.
- El updater mostrará actualizaciones, la pantalla de detalles y gestionará la descarga/instalación desde GitHub.

## Compatibilidad con actualizaciones de GitHub
Esta sección explica cómo garantizar que tu plugin sea compatible con actualizaciones alojadas en GitHub.

### 1) Estructura del repositorio
- El archivo principal del plugin debe tener el encabezado estándar de WordPress con el campo `Version`.
- Mantén un tag en GitHub por cada versión publicada (`v1.0.1`, `v1.2.0`, etc.).
- Si usas Releases:
  - Crea una Release y asigna el tag correspondiente (por ejemplo `v1.0.1`).
  - El updater usará `releases/latest` y su `zipball_url`.
- Si usas solo Tags:
  - El updater construye el zip desde `archive/refs/tags/<TAG>.zip`.

### 2) Configuración del updater
En tu plugin principal:
- Incluye la clase `class-github-plugin-updater.php`.
- En el hook `init`, pasa una configuración como:

```php
$config = [
  'github_user'  => 'tu-usuario',
  'github_repo'  => 'tu-repo',
  'plugin_file'  => __FILE__, // ruta al archivo principal del plugin
  'use_releases' => true,     // o false si solo usas tags
  'branch'       => 'main',
  'access_token' => '',       // opcional; para repos privados
  'cache_ttl'    => 30 * MINUTE_IN_SECONDS,
  'homepage'     => 'https://tu-sitio.example',
];
new PUS_GitHub_Plugin_Updater($config);
```

### 3) Repositorios privados
- Crea un token personal en GitHub con permiso `repo`.
- Define la constante `GITHUB_ACCESS_TOKEN` en `wp-config.php` o añade el token vía filtro:

```php
add_filter('pus_updater_config', function($config) {
  $config['access_token'] = 'xxxxxxxxxxxxxxxx';
  return $config;
});
```

### 4) Pantalla de detalles y metadatos
- La clase rellena `plugins_api` con:
  - Versión (`tag_name` de la release/tag)
  - Descripción y `changelog` (cuerpo de la Release)
  - Enlace de descarga al zip
- Puedes personalizar metadatos usando el parámetro `homepage` o extendiendo la clase.

### 5) Manejo del nombre del directorio en el zip
- Los zips de GitHub suelen incluir un directorio con nombre dinámico.
- La clase intenta reubicar el contenido al directorio del plugin correcto tras la instalación, para evitar que el plugin se desactive por cambiar de ruta.

### 6) Buenas prácticas
- Actualiza el campo `Version` del plugin cuando publiques una nueva Release.
- Usa el prefijo `v` en los tags si deseas; la clase ya normaliza quitando `v` para comparar versiones.
- Mantén un `readme` con `Stable tag` consistente.

## FAQ
**¿Funciona con repos privados?**
Sí. Añade un token como constante `GITHUB_ACCESS_TOKEN` o vía el filtro `pus_updater_config`.

**¿Cómo se decide la versión?**
Se compara el `Version` del encabezado del plugin con `tag_name` (releases) o el primer `tag` (tags).

## Changelog
### 1.0.0
- Versión inicial del esqueleto con integración de la API de plugins y comprobación de actualizaciones vía transients.
