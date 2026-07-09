const API_URL = 'src/controllers/api.php';

let currentEditId = null;
let solicitudModal = null;
let confirmModal = null;

document.addEventListener('DOMContentLoaded', function () {
    solicitudModal = new bootstrap.Modal(document.getElementById('solicitudModal'));
    confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    loadSolicitudes();
});

async function loadSolicitudes() {
    showLoading(true);
    try {
        const response = await fetch(API_URL);
        const result = await response.json();
        if (result.success) {
            displaySolicitudes(result.data);
        } else {
            showToast('Error: ' + result.error, 'danger');
        }
    } catch (error) {
        showToast('Error de conexión: ' + error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function applyFilters() {
    loadSolicitudes();
}

function displaySolicitudes(items) {
    const container = document.getElementById('solicitudesContainer');
    if (!items.length) {
        container.innerHTML = '<tr><td colspan="7" class="text-center">No hay solicitudes.</td></tr>';
        return;
    }

    container.innerHTML = items.map(item => `
        <tr>
            <td><strong>${escapeHtml(item.titulo)}</strong></td>
            <td class="description-cell">${escapeHtml(truncate(item.descripcion, 80))}</td>
            <td>${escapeHtml(item.area_solicitante)}</td>
            <td>${escapeHtml(item.area_destino)}</td>
            <td><span class="badge badge-prioridad-${item.prioridad}">${item.prioridad}</span></td>
            <td><span class="badge badge-estado-${item.estado}">${formatEstado(item.estado)}</span></td>
            <td class="actions-cell">${renderActions(item)}</td>
        </tr>
    `).join('');
}

function renderActions(item) {
    let html = '';
    if (item.estado === 'pendiente') {
        html += `<button class="btn btn-sm btn-outline-primary me-1" onclick="editSolicitud(${item.id})" title="Editar"><i class="fas fa-edit"></i></button>`;
        html += `<button class="btn btn-sm btn-outline-danger me-1" onclick="confirmDelete(${item.id}, '${escapeHtml(item.titulo)}')" title="Eliminar"><i class="fas fa-trash"></i></button>`;
        html += `<button class="btn btn-sm btn-outline-info" onclick="cambiarEstado(${item.id}, 'en_proceso')" title="Tomar">Tomar</button>`;
    } else if (item.estado === 'en_proceso') {
        html += `<button class="btn btn-sm btn-success me-1" onclick="cambiarEstado(${item.id}, 'resuelta')">Resolver</button>`;
        html += `<button class="btn btn-sm btn-warning" onclick="cambiarEstado(${item.id}, 'rechazada')">Rechazar</button>`;
    }
    return html;
}

function openModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Nueva Solicitud';
    document.getElementById('solicitudForm').reset();
    document.getElementById('solicitudId').value = '';
    clearMessages();
    solicitudModal.show();
}

async function editSolicitud(id) {
    try {
        const response = await fetch(`${API_URL}?id=${id}`);
        const result = await response.json();
        if (!result.success) {
            showToast(result.error, 'danger');
            return;
        }
        const s = result.data;
        currentEditId = id;
        document.getElementById('modalTitle').textContent = 'Editar Solicitud';
        document.getElementById('solicitudId').value = s.id;
        document.getElementById('titulo').value = s.titulo;
        document.getElementById('descripcion').value = s.descripcion;
        document.getElementById('area_solicitante').value = s.area_solicitante;
        document.getElementById('area_destino').value = s.area_destino;
        document.getElementById('prioridad').value = s.prioridad;
        clearMessages();
        solicitudModal.show();
    } catch (error) {
        showToast('Error de conexión: ' + error.message, 'danger');
    }
}

async function saveSolicitud() {
    const form = document.getElementById('solicitudForm');
    try {
        if (currentEditId) {
            await updateSolicitud(currentEditId, form);
        } else {
            await createSolicitud(form);
        }
    } catch (error) {
        showErrorMessage('Error: ' + error.message);
    }
}

async function createSolicitud(form) {
    const formData = new FormData(form);
    const response = await fetch(API_URL, { method: 'POST', body: formData });
    const result = await response.json();
    handleSaveResponse(result);
}

async function updateSolicitud(id, form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const response = await fetch(`${API_URL}?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const result = await response.json();
    handleSaveResponse(result);
}

async function cambiarEstado(id, estado) {
    try {
        const response = await fetch(`${API_URL}?id=${id}&action=cambiar_estado`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Estado actualizado', 'success');
            loadSolicitudes();
        } else {
            showToast(result.error, 'danger');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'danger');
    }
}

function confirmDelete(id, titulo) {
    document.getElementById('confirmMessage').textContent = `¿Eliminar "${titulo}"?`;
    document.getElementById('confirmButton').onclick = () => deleteSolicitud(id);
    confirmModal.show();
}

async function deleteSolicitud(id) {
    try {
        const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            confirmModal.hide();
            showToast('Eliminada', 'success');
            loadSolicitudes();
        } else {
            showToast(result.error, 'danger');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'danger');
    }
}

function handleSaveResponse(result) {
    if (result.success) {
        showSuccessMessage(result.data.message || 'Guardado');
        setTimeout(() => {
            solicitudModal.hide();
            loadSolicitudes();
        }, 1000);
    } else {
        const msg = Array.isArray(result.error) ? result.error.join('<br>') : result.error;
        showErrorMessage(msg);
    }
}

function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
    document.getElementById('solicitudesTable').style.display = show ? 'none' : 'table';
}

function showErrorMessage(msg) {
    const el = document.getElementById('errorMessages');
    el.innerHTML = msg;
    el.style.display = 'block';
}

function showSuccessMessage(msg) {
    const el = document.getElementById('successMessage');
    el.innerHTML = msg;
    el.style.display = 'block';
}

function clearMessages() {
    document.getElementById('errorMessages').style.display = 'none';
    document.getElementById('successMessage').style.display = 'none';
}

function showToast(message, type) {
    alert(message);
}

function formatEstado(estado) {
    return estado.replace('_', ' ');
}

function truncate(text, len) {
    return text.length > len ? text.substring(0, len) + '...' : text;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
