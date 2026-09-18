/**
 * Task Manager - front-end behavior.
 * Uses the Fetch API to talk to api/*.php without full page reloads
 * for delete, and polls get_tasks.php periodically to pick up changes
 * made elsewhere (the "real-time" behavior described in Stage 16).
 */

document.addEventListener('DOMContentLoaded', () => {
    wireDeleteButtons();
    startLivePolling();
});

/* ---------------- Toast messages ---------------- */

function showMessage(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

/* ---------------- Delete via Fetch (no page reload) ---------------- */

function wireDeleteButtons() {
    document.querySelectorAll('.js-delete-task').forEach((button) => {
        button.addEventListener('click', async () => {
            const taskId = button.getAttribute('data-task-id');
            if (!confirm('Delete this task? This cannot be undone.')) return;

            button.disabled = true;
            try {
                const response = await fetch('api/delete_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId }),
                });

                if (!response.ok && response.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    const card = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
                    if (card) card.remove();
                    showMessage(data.message, 'success');
                    maybeShowEmptyState();
                } else {
                    showMessage(data.message || 'Could not delete task.', 'error');
                    button.disabled = false;
                }
            } catch (err) {
                showMessage('Network error. Please check your connection and try again.', 'error');
                button.disabled = false;
            }
        });
    });
}

function maybeShowEmptyState() {
    const list = document.getElementById('task-list');
    if (list && list.children.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <p>You don't have any tasks yet.</p>
                <a href="add_task.php" class="btn btn-primary">Create your first task</a>
            </div>`;
    }
}

/* ---------------- Lightweight live refresh ----------------
 * Polls the API every few seconds and re-renders the task list if the
 * server's data changed. This is the practical stand-in for WebSockets:
 * it demonstrates "another session's changes show up here" without
 * needing a persistent socket connection. See README for how to swap
 * this for a real WebSocket push if you want to extend the project.
 */

let lastTasksSignature = null;

function startLivePolling() {
    if (!document.getElementById('task-list')) return; // only on dashboard
    setInterval(refreshTasksFromServer, 5000);
}

async function refreshTasksFromServer() {
    try {
        const response = await fetch('api/get_tasks.php', { headers: { 'Accept': 'application/json' } });
        if (!response.ok) return;
        const data = await response.json();
        if (!data.success) return;

        const signature = JSON.stringify(data.tasks);
        if (signature === lastTasksSignature) return; // nothing changed
        lastTasksSignature = signature;

        renderTaskList(data.tasks);
    } catch (err) {
        // Silently ignore - the page still works from the last full load.
    }
}

function renderTaskList(tasks) {
    const list = document.getElementById('task-list');
    if (!list) return;

    if (tasks.length === 0) {
        maybeShowEmptyState();
        return;
    }

    list.innerHTML = tasks.map(taskToCardHtml).join('');
    wireDeleteButtons();
}

function statusClass(status) {
    switch (status) {
        case 'Pending': return 'status-pending';
        case 'In Progress': return 'status-progress';
        case 'Completed': return 'status-completed';
        default: return '';
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

function taskToCardHtml(task) {
    const due = formatDate(task.due_date);
    const created = formatDate(task.created_at);
    return `
    <div class="task-card" data-task-id="${task.id}">
        <div class="task-card-top">
            <h3>${escapeHtml(task.title)}</h3>
            <span class="badge ${statusClass(task.status)}">${escapeHtml(task.status)}</span>
        </div>
        ${task.description ? `<p class="task-desc">${escapeHtml(task.description)}</p>` : ''}
        <div class="task-meta">
            ${due ? `<span>Due: ${due}</span>` : ''}
            ${created ? `<span>Created: ${created}</span>` : ''}
        </div>
        <div class="task-actions">
            <a href="edit_task.php?id=${task.id}" class="btn btn-outline btn-sm">Edit</a>
            <button class="btn btn-danger btn-sm js-delete-task" data-task-id="${task.id}">Delete</button>
        </div>
    </div>`;
}
