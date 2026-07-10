# Plantilla de Análisis — Instancia 1

**Candidato:** Lucyk Daniel Sebastián

---

## 1. Resumen del problema

El módulo gestiona las **solicitudes internas entre áreas** de la institución. Antes, esos pedidos se manejaban de forma informal y dispersa (mail, WhatsApp, planillas compartidas), sin trazabilidad ni un estado claro de cada pedido. Para resolver eso ya se construyó una **pantalla web que hoy está en uso**, donde se cargan las solicitudes, se les asigna prioridad y se sigue su ciclo de vida (pendiente → en proceso → resuelta/rechazada) desde un listado consultable y filtrable.

El problema actual es que ese módulo existente **tiene fallas y comportamientos mal resueltos**: permite acciones que no deberían estar habilitadas (como editar una solicitud ya resuelta), borra sin confirmación, y los filtros no son claros. El objetivo de este trabajo es **analizar el proceso real, dejar las reglas de negocio bien definidas y corregir el módulo** para que respete esas reglas, dándole trazabilidad y orden confiable a los pedidos entre áreas.

---

## 2. Actores

Los actores del sistema son **roles funcionales**, no personas ni áreas fijas. Cualquier área de la institución puede asumir cualquiera de los dos roles según el caso: una misma área es solicitante en una solicitud y receptora en otra.

| Rol funcional | Qué hace en el proceso |
|---|---|
| Área solicitante | Crea la solicitud (título, descripción, área destinataria, prioridad). Puede editarla o borrarla mientras siga *pendiente*. |
| Área receptora | Es el área a la que va dirigida la solicitud. La toma (la pasa a *en proceso*) y la cierra como *resuelta* o *rechazada*. |

 **Sobre las personas de la entrevista:** María, Lucas y Carlos son **fuentes de información**, no roles del sistema. Sus descripciones reflejan cómo usan la herramienta en su día a día, pero el módulo **no distingue quién es quién**: como no hay login, no existe un rol técnico atado a una persona o área. Sistemas es quien desarrolla y mantiene el módulo, y define el alcance técnico (mantener PHP, login diferido).

 **Nota sobre control de acceso:** El escenario no define asignación por área ni control de acceso. No hay usuarios, roles técnicos ni login: el área se escribe a mano como texto libre. Se asume que, hasta implementar login, cualquiera puede operar sobre cualquier solicitud (limitación conocida, ver §6).

---

## 3. Flujo del proceso

Ciclo de vida de una solicitud:

```
   [Creación] (por el área solicitante)
       │
       ▼
   PENDIENTE ──── editar permitido
       │  │       borrar permitido (la confirmación es una mejora a implementar)
       │  │
       │  └──── único estado donde se puede editar o borrar
       │
       │  (acción "Tomar", por el área receptora)
       ▼
   EN PROCESO ──── texto congelado (ya no se edita)
       │  │
       │  └──────────────┐
       ▼                 ▼
   RESUELTA          RECHAZADA
   (final,           (final en esta versión;
    ineditable)       reapertura no definida → §6)
```

**Estados y transiciones:**

- **Pendiente** (estado inicial): la solicitud nace aquí al crearse. Es el único estado donde se puede editar el texto o borrar la solicitud.
- **En proceso**: se alcanza cuando el área receptora ejecuta la acción "Tomar". A partir de acá el texto de la solicitud queda congelado.
- **Resuelta** (estado final): el área receptora la marca al completar el pedido. Es **ineditable** — regla muy enfatizada repetidamente en la entrevista.
- **Rechazada** (estado final en esta versión): el área receptora la marca cuando el pedido no corresponde. La reapertura fue mencionada por Compras pero no está definida (ver §6).

**Quién puede cambiar el estado:** solo el área receptora puede tomar, resolver o rechazar. El área solicitante solo puede editar o borrar mientras la solicitud siga *pendiente* (es decir, mientras nadie la haya tomado).


---

## 4. Reglas de negocio

La última columna indica el estado de cada regla en el módulo actual: **(ya funciona)** si el módulo ya la cumple, **(a implementar/corregir)** si es una de las mejoras que este trabajo debe resolver en la Instancia 2. Esta clasificación es un supuesto de trabajo a confirmar al levantar el `codigo-base/`.

| Regla | ¿Explícita en la entrevista? | Estado en el módulo actual |
|---|---|---|
| Título, descripción, área solicitante, área destinataria y prioridad son obligatorios al crear | Sí | A confirmar al revisar el código |
| La prioridad tiene tres valores: alta, media, baja | Sí | Ya funciona (existe el campo) |
| Una solicitud nace en estado pendiente | Sí | Ya funciona |
| Mientras está pendiente se puede editar o borrar | Sí | Ya funciona |
| Una vez tomada (en proceso), el texto no se modifica | Sí | **A corregir** (el módulo hoy permite editar de más) |
| Una solicitud resuelta es ineditable | Sí  | **A corregir** (falla principal reportada) |
| Borrar debe pedir confirmación | Sí | **A implementar** (todavía no existe la confirmación) |
| Solo el área receptora cambia el estado | Sí | A confirmar al revisar el código |
| El área solicitante puede borrar mientras nadie la tomó | Sí | Ya funciona (borrado disponible en pendiente) |
| La prioridad ordena el listado (alta → media → baja) | **Parcial → supuesto** | **A implementar/corregir** el orden (ver §6) |
| Los filtros combinan estado + prioridad | **No cerrado → supuesto** | **A revisar/corregir** los filtros (ver §6) |
| Reabrir una solicitud rechazada | **No definido → supuesto** | Fuera de alcance esta versión (ver §6) |

---

## 5. Modelo de datos propuesto

Tabla principal: **`solicitudes`**

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT, PK, AUTO_INCREMENT | Identificador único. |
| `titulo` | VARCHAR(150), NOT NULL | Título del pedido. |
| `descripcion` | TEXT, NOT NULL | Detalle del pedido. |
| `area_solicitante` | VARCHAR(100), NOT NULL | Área que crea el pedido (texto libre por ahora, sin login). |
| `area_destino` | VARCHAR(100), NOT NULL | Área a la que va dirigido. |
| `prioridad` | ENUM('alta','media','baja'), NOT NULL | Prioridad del pedido. |
| `estado` | ENUM('pendiente','en_proceso','resuelta','rechazada'), NOT NULL, DEFAULT 'pendiente' | Estado actual. |
| `created_at` | DATETIME, DEFAULT CURRENT_TIMESTAMP | Fecha de alta. |
| `updated_at` | DATETIME | Última modificación de estado. |

**Notas de diseño:**
- Uso ENUM para estado y prioridad porque son conjuntos cerrados y conocidos; garantiza integridad a nivel base.
- No modelo tabla de usuarios/áreas todavía: la entrevista deja login y áreas fuera de alcance. Cuando se implemente login, area_solicitante y area_destino pasarían a ser claves foráneas a una tabla areas, y se agregaría usuarios.
- El orden por prioridad se resuelve en la consulta (ORDER BY FIELD(prioridad,'alta','media','baja')), no requiere campo extra.

---

## 6. Preguntas abiertas y supuestos

| Tema | Qué se dijo (resumen) | Mi supuesto | Justificación |
|---|---|---|---|
| **Prioridad: ¿ordena o es referencia?** | Carlos: las altas primero. María: es solo referencia. Lucas: indiferente. | La prioridad **ordena** el listado (alta → media → baja). | Es el único uso que le da valor al campo, fue pedido explícitamente por Compras. Ordenar satisface a quien lo necesita sin perjudicar al resto. |
| **Reabrir rechazadas** | Carlos lo necesita a veces; Lucas cree que es final "salvo que un jefe diga". Ambos reconocen que no está definido. | En esta versión **no se reabre**: rechazada es final. | Falta una regla y un rol (jefe) que la entrevista no define. Implementar reapertura sin control de acceso sería riesgoso. Se deja como requerimiento a definir en próxima versión, ligado al login. |
| **Filtros: ¿combinables? ¿sobre qué operan?** | María no sabe si filtran lo visible o van a la base. Lucas asume que estado + prioridad se combinan. | Los filtros **se combinan** (estado Y prioridad) y operan **contra la base de datos**, no solo sobre lo visible en pantalla. | La interpretación de Lucas y la más útil: filtrar contra la base garantiza resultados completos y consistentes, no limitados a lo ya cargado en pantalla. |
| **Login / control de acceso** | Sin login; el área se escribe a mano. "Cualquiera aprieta cualquier botón". Sistemas lo dejó "para después". | Fuera de alcance en esta versión; se asume acceso sin restricción. | La entrevista lo excluye explícitamente. Se documenta como limitación conocida, no como falla. |
| **"Cancelar" como estado** | María querría cancelar mientras nadie la tomó, pero cancelar no existe como estado; hoy se borra. | No se agrega estado "cancelada": cancelar = borrar (con confirmación) mientras esté pendiente. | Evita ampliar el modelo de estados sin pedido firme. La necesidad ("deshacer un pedido no tomado") ya queda cubierta por el borrado con confirmación. |

---

## 7. Requerimientos funcionales

### Listado
- Muestra todas las solicitudes con: título, área solicitante, área destinataria, prioridad y estado.
- Ordenado por prioridad (alta → media → baja).
- Sobre el listado se aplican los filtros (ver más abajo).

### Alta, edición y baja
- **Alta:** formulario con título, descripción, área solicitante, área destinataria y prioridad. Todos obligatorios. La solicitud nace en estado *pendiente*.
- **Edición:** permitida **solo** mientras la solicitud está *pendiente*. Una vez en proceso, resuelta o rechazada, el texto no se puede modificar.
- **Baja:** permitida solo mientras está *pendiente*, y **requiere confirmación** antes de ejecutarse.

### Cambio de estado
- **Tomar** (pendiente → en proceso): lo hace el área receptora.
- **Resolver** (en proceso → resuelta): lo hace el área receptora. Estado final, ineditable.
- **Rechazar** (en proceso → rechazada): lo hace el área receptora. Estado final en esta versión.
- No se permite editar el texto de una solicitud una vez que salió de *pendiente*.
- No se permite modificar una solicitud *resuelta* bajo ninguna circunstancia.

### Filtros y orden
- Filtro por **estado** y por **prioridad**, combinables entre sí.
- Los filtros operan contra la base de datos.
- Orden por defecto: prioridad descendente (alta → media → baja).

---

## 8. Fuera de alcance

Según la entrevista, queda afuera de esta versión:
- **Login / autenticación de usuarios** y control de acceso por rol o área.
- **Notificaciones automáticas por mail.**
- **Adjuntar archivos** a las solicitudes.
- **Cambio de PHP a otro framework** — Sistemas fue claro en mantener PHP.
- **Reapertura de solicitudes rechazadas** (no definida; ver §6).
- **Estado "cancelada"** como estado propio (se resuelve con borrado; ver §6).

---

## 9. Plan para la Instancia 2

Sin haber visto aún el código, mi enfoque para la corrección sería:

1. **Levantar el entorno** (MySQL + servidor PHP local) y ejecutar el módulo tal como viene, para observar el comportamiento real antes de tocar nada.
2. **Diagnosticar contra este análisis:** verificar si el código respeta las reglas duras que documenté acá — sobre todo (a) que no se pueda editar una resuelta, (b) que borrar pida confirmación, (c) que solo se edite/borre en estado pendiente, (d) que los filtros combinen estado + prioridad, (e) que el orden respete la prioridad.
3. **Registrar cada falla en Plantilla-Entrega §1 antes de corregir**, como pide la consigna.
4. **Corregir respetando las restricciones:** sin cambiar estructura de carpetas, sin tocar el contrato JSON `{ success, data | error }`, sin renombrar `api.php`.
5. **Validar manualmente** al menos 3 comportamientos (alta válida, edición no permitida sobre estado cerrado, filtro combinado) y registrarlos en el log.

---

*Fin de Instancia 1.*
