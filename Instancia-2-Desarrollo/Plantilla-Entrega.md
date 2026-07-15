# Plantilla de Entrega — Instancia 2

**Nombre del candidato:** Lucyk Daniel Sebastián
**Fecha de entrega:** 15/07/2026
**URL del repositorio público:** https://github.com/daniellucyk80/prueba-tecnica-solicitudes-internas 

**Análisis de negocio:** entregado en `Instancia-1-Analisis/Plantilla-Analisis.md`.

---

## 1. Diagnóstico de bugs (antes de corregir)

| # | Bug / síntoma | Archivo | Evidencia | Hipótesis de causa |
|---|---|---|---|---|
| 1 | la API no responde al listar solicitudes | `src/models/Database.php`, L15 | `php -l` devuelve: `syntax error, unexpected variable "$this" on line 16` | Falta el `;` al final del primer `setAttribute(...)`. Sin el punto y coma. |
| 2 | la API devuelve error 500 en cualquier petición | `src/controllers/api.php` | `php -l` devuelve: `syntax error, unexpected token "private" on line 66` | Falta la llave `}` que cierra el método `handleRequest()`. El `try/catch` cierra correctamente, pero el método en sí queda abierto, y PHP interpreta que `getAll()` se declara dentro de otra función. |
| 3 | No se pueden crear solicitudes nuevas: el alta falla | `assets/js/app.js` | Consola del navegador: `SyntaxError: await is only valid in async functions` | La función `createSolicitud()` usa `await` pero no está declarada como `async`.|
| 4 | El listado no se puede filtrar y sale ordenado por fecha en vez de por prioridad | `src/models/Solicitud.php`, método `getAll()` | El parámetro `$filters` se recibe pero nunca se usa en la query. El `ORDER BY created_at DESC` ordena por fecha, no por prioridad | El método fue implementado de forma incompleta: no construye las condiciones `WHERE` a partir de los filtros, y el criterio de orden no coincide con la regla definida en Instancia 1 §7 (orden por prioridad, filtros combinables por estado y prioridad) |
| 5 | Se puede editar una solicitud en cualquier estado, incluso resuelta (mucho enfasis en la entrevista) | `src/models/Solicitud.php`, método `update()` | El `UPDATE` se ejecuta directo sin verificar el estado. El botón editar se oculta en el front, pero una petición PUT directa modifica igual una resuelta | Falta validación de estado en el backend antes de actualizar. La única protección actual está en el frontend. Rompe la regla de Instancia 1: "una solicitud resuelta es ineditable" y "solo se edita en estado pendiente" |
| 6 | Se puede cambiar una solicitud a cualquier estado sin respetar el ciclo de vida (ej: resuelta -> pendiente) | `src/models/Solicitud.php`, método `cambiarEstado()` | El `UPDATE` de estado se ejecuta con cualquier valor recibido, sin validar la transición. Un PATCH directo permite saltos inválidos o estados inexistentes | Falta validar en el backend que la transición sea coherente con el ciclo de vida de Instancia 1 (pendiente -> en proceso->resuelta/rechazada; los finales no admiten cambios) |
| 7 | El título viaja vacío aunque el usuario lo complete | `index.html`, campo título | El input tiene `id="titulo"` pero le falta `name="titulo"`. `FormData` recoge los campos por `name`, así que el título nunca se envía. La validación "título obligatorio" del backend se dispara siempre | Falta el atributo `name` en el input del título. |
| 8 | Los acentos y la ñ se ven corruptos en los datos de la base (ej: "Administraci??n") | `sql/database.sql`, `CREATE DATABASE` y `CREATE TABLE` | Los textos fijos del HTML se ven bien (charset correcto), pero los datos que vienen de la base salen mal. El DSN ya declara `charset=utf8mb4`, así que la falla no está en la conexión | El `CREATE DATABASE` y el `CREATE TABLE` no declaran `utf8mb4`. La tabla se crea con la codificación por defecto del servidor , y los INSERT con acentos se almacenan corruptos.|
| 9 | Los filtros de la pantalla no filtran nada: siempre se muestran todas las solicitudes | `assets/js/app.js`, `loadSolicitudes()` y `applyFilters()` | La API filtra bien si se la llama con `?prioridad=alta` en la URL, pero desde la pantalla no. `loadSolicitudes()` hace `fetch(API_URL)` sin leer los selects ni agregar los filtros a la URL | El frontend nunca lee los valores de los `<select>` de filtro ni los envía a la API. `applyFilters()` solo recarga sin pasar los filtros seleccionados |
| 10 | Se puede eliminar una solicitud en cualquier estado (debería permitirse solo si está pendiente) | `src/models/Solicitud.php`, método `delete()` | El `DELETE` se ejecuta sin verificar el estado. Un DELETE directo (curl/consola) borra una resuelta o en proceso, saltándose la regla | Falta validación de estado en el backend antes de borrar. El modal de confirmación del frontend cubre la UX, pero no la regla de negocio de Instancia 1 ("baja solo mientras está pendiente") |

---

## 2. Resumen de cambios realizados

| # | Archivo modificado | Qué se cambió | Por qué (vínculo con Instancia 1) |
|---|---|---|---|
| 1 | `src/models/Database.php` | Se agregó el `;` faltante al final del primer `setAttribute()` | Error de sintaxis que impedía cargar la clase de conexión. Sin la base andando, ninguna regla se puede cumplir |
| 2 | `src/controllers/api.php` | Se agregó la llave `}` que cerraba el método `handleRequest()` | Error de sintaxis fatal: PHP interpretaba `getAll()` como función anidada. El proyecto no cargaba |
| 3 | `assets/js/app.js` | Se declaró `createSolicitud()` como `async` | Sin `async`, el `await` interno rompía el alta de solicitudes (JS no ejecutaba la función) |
| 4 | `src/models/Solicitud.php` | `getAll()` ahora aplica los filtros recibidos y ordena por prioridad con `FIELD()` | Implementa el requerimiento §7 de Instancia 1: filtros combinables (estado + prioridad) y orden alta → media → baja |
| 5 | `src/models/Solicitud.php` y `src/controllers/api.php` | `update()` valida que la solicitud esté *pendiente* antes de editar; devuelve resultado con mensaje | Regla clave de Instancia 1: "una solicitud resuelta es ineditable" y "solo se edita en estado pendiente". Validación en backend, no solo en UI |
| 6 | `src/models/Solicitud.php` y `src/controllers/api.php` | `cambiarEstado()` valida la transición contra el ciclo de vida | Respeta el ciclo de vida de Instancia 1: pendiente→en_proceso→resuelta/rechazada. Bloquea saltos inválidos y estados inexistentes |
| 7 | `index.html` | Se agregó `name="titulo"` al input del título | Sin `name`, `FormData` no enviaba el título y el alta fallaba siempre por "título obligatorio" |
| 8 | `sql/database.sql` | Se declaró `utf8mb4` en `CREATE DATABASE` y `CREATE TABLE` | Los acentos y la ñ se guardaban corruptos. Sin esto, los datos de las áreas se mostraban mal |
| 9 | `assets/js/app.js` | `loadSolicitudes()` ahora lee los selects de filtro y los envía a la API | El backend filtraba bien, pero el frontend nunca enviaba los filtros seleccionados. Completa el requerimiento de filtros |
| 10 | `src/models/Solicitud.php` y `src/controllers/api.php` | `delete()` valida que la solicitud esté *pendiente* antes de eliminar | Regla de Instancia 1: "baja permitida solo mientras está pendiente". El modal de confirmación (UI) ya existía; faltaba la validación de negocio en backend |

**Nota sobre restricciones respetadas:** no se modificó la estructura de carpetas de `codigo-base/`, no se cambió el contrato JSON `{ success, data | error }`, y no se renombró `src/controllers/api.php`.

---

## 3. Funcionalidades implementadas

- [x] ABM básico funcional (crear, listar, editar, eliminar)
- [x] Reglas de edición/eliminación según estado
- [x] Cambio de estado con reglas de negocio
- [x] Filtros / orden según Instancia 1
- [x] Otro: validaciones en backend, corrección de encoding UTF-8

---

## 4. Instrucciones de ejecución

**Requisitos:** PHP 7+, MySQL, servidor web local. (Probado con PHP 8.2 y MariaDB/MySQL vía XAMPP.)

**1. Base de datos:**
```
mysql -u root -p --default-character-set=utf8mb4 -e "source Instancia-2-Desarrollo/codigo-base/sql/database.sql"
```
> Nota: se usa `source` en lugar de la redirección `<` para asegurar que los acentos se carguen en UTF-8 correctamente (en algunos entornos, como PowerShell en Windows, la redirección corrompe la codificación).

**2. Configuración:**
```
cp Instancia-2-Desarrollo/codigo-base/config/config_example.php Instancia-2-Desarrollo/codigo-base/config/config.php
```
Las credenciales por defecto (`root` sin contraseña, base `sistema_solicitudes`) coinciden con una instalación estándar de XAMPP. Ajustar si es necesario.

**3. Servidor:**
```
php -S localhost:8080 -t Instancia-2-Desarrollo/codigo-base
```

**4. URL:** http://localhost:8080/index.html

**5. Problemas conocidos / pendientes:**
- La reapertura de solicitudes rechazadas no está implementada (fuera de alcance según Instancia 1 §6, pendiente de definición junto con el login).
- No hay control de acceso / login: cualquiera puede operar sobre cualquier solicitud (limitación conocida documentada en Instancia 1).

---

## 5. Declaración de IA

**¿Usaste herramientas de IA?** Sí

### Herramientas
- Claude (Anthropic) — asistencia para estructurar el análisis, diagnosticar los bugs y explicar las correcciones antes de aplicarlas.

### Prompts relevantes
1. Pedí ayuda para estructurar el análisis de la entrevista (Instancia 1), pero revisé y corregí varios puntos: reencuadré el resumen ("antes los pedidos eran informales; hoy ya existe el módulo, con fallas a corregir"), redefiní los actores como roles funcionales (área solicitante / área receptora) en lugar de personas fijas, y aclaré que la confirmación de borrado era una mejora a implementar.
2. Al resolver el listado (`getAll`), la primera versión propuesta me pareció por encima de mi nivel y difícil de defender. Pedí una versión más simple: descarté el enfoque con `implode` y arrays de condiciones dinámicas, y me quedé con `WHERE 1=1` + condiciones con `?` + `FIELD()` para el orden, que puedo explicar línea por línea.
3. Definí el criterio de la prioridad: decidí que **ordena** el listado (alta → media → baja), justificándolo con que es el único uso que le da valor al campo y nadie en la entrevista se opone.
4. Usé la asistencia para entender el patrón de validación en el backend (devolver un array con `ok`/`error`/`status` en lugar de un booleano), y lo apliqué de forma coherente en `update()`, `cambiarEstado()` y `delete()`.

### Validaciones manuales
1. **Alta con datos válidos:** creé una solicitud nueva desde el formulario. Al principio fallaba (el título no se enviaba por falta del atributo `name`, y `createSolicitud` no era `async`); tras corregir ambos, la solicitud se crea y aparece en el listado.
2. **Edición de una solicitud resuelta (validación en backend):** mandé un PUT directo desde la consola del navegador a una solicitud en estado *resuelta*, salteando el botón oculto de la interfaz. El backend la rechazó con "Solo se puede editar una solicitud pendiente. Estado actual: resuelta". Confirma que la regla se valida en el servidor, no solo en pantalla.
3. **Transición de estado inválida:** intenté pasar una solicitud *resuelta* de vuelta a *pendiente* vía PATCH. Fue rechazada con "No se puede pasar de 'resuelta' a 'pendiente'". También probé un estado inexistente ("blablabla") y fue rechazado igual.
4. **Transición válida:** pasé una solicitud de *en_proceso* a *resuelta* y se actualizó correctamente.
5. **Eliminación según estado:** mandé un DELETE directo a una solicitud no pendiente y el backend la rechazó ("Solo se puede eliminar una solicitud pendiente"); sobre una pendiente, se eliminó correctamente.
6. **Filtros del listado:** apliqué el filtro de prioridad "alta" en la pantalla y el listado mostró solo las de esa prioridad; combiné estado + prioridad y el resultado fue coherente.
7. **Encoding:** tras declarar `utf8mb4` y cargar la base sin el pipe de PowerShell, los acentos y la ñ se muestran correctamente.

_(Ver Reglas-Uso-IA.md)_

---

## 6. Autoevaluación (opcional)

| Criterio | 0 | 1 | 2 | 3 | Comentario |
|---|---|---|---|---|---|
| Diagnóstico de bugs | | | x |  | 10 bugs documentados antes de corregir, con archivo, evidencia e hipótesis de causa. |
| Coherencia Inst. 1 ↔ código | | | | X | Cada corrección de negocio se vincula con una regla del análisis; modelo de datos alineado con la base. |
| Calidad del código | | | X | | Validaciones en backend, consultas preparadas, patrón consistente. |
