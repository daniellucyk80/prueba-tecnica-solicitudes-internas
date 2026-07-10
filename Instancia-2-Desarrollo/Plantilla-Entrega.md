# Plantilla de Entrega — Instancia 2

**Nombre del candidato:** Lucyk Daniel Sebastián
**Fecha de entrega:** _(completar al entregar)_
**URL del repositorio público:** _(completar con el link de GitHub)_

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

| Archivo modificado | Qué se cambió | Por qué (vincular con Instancia 1 si aplica) |
|---|---|---|
| _(completar)_ | | |

---

## 3. Funcionalidades implementadas

- [ ] ABM básico funcional
- [ ] Reglas de edición/eliminación según estado
- [ ] Cambio de estado con reglas de negocio
- [ ] Filtros / orden según Instancia 1
- [ ] Otro: ___________

---

## 4. Instrucciones de ejecución

**Requisitos:** PHP 7+, MySQL, servidor web local.

**1. Base de datos:**
```
mysql -u root -p < Instancia-2-Desarrollo/codigo-base/sql/database.sql
```

**2. Configuración:**
```
cp Instancia-2-Desarrollo/codigo-base/config/config_example.php \
   Instancia-2-Desarrollo/codigo-base/config/config.php
```
_(ajustar credenciales en config.php si es necesario)_

**3. Servidor:**
```
cd Instancia-2-Desarrollo/codigo-base && php -S localhost:8080
```

**4. URL:** http://localhost:8080/index.html

**5. Problemas conocidos / pendientes:**
_(completar)_

---

## 5. Declaración de IA

¿Usaste herramientas de IA? Sí

Herramientas

_(Ver Reglas-Uso-IA.md)_

---

## 6. Autoevaluación (opcional)

| Criterio | 0 | 1 | 2 | 3 | Comentario |
|---|---|---|---|---|---|
| Diagnóstico de bugs | | | | | |
| Coherencia Inst. 1 ↔ código | | | | | |
| Calidad del código | | | | | |
