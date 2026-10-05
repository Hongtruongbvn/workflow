/* ============================================================
   WorkFlow Pro — Kanban drag & drop (vanilla JS, no dependencies)
   ============================================================ */
(function () {
    'use strict';

    const kanban = document.getElementById('kanban');
    if (!kanban) return;

    const projectId = kanban.dataset.project;
    const moveUrl = kanban.dataset.moveUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let draggedCard = null;

    /* ---------- Drag start / end ---------- */
    kanban.addEventListener('dragstart', (e) => {
        const card = e.target.closest('.kanban-card');
        if (!card) return;
        draggedCard = card;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    kanban.addEventListener('dragend', () => {
        if (draggedCard) draggedCard.classList.remove('dragging');
        draggedCard = null;
    });

    /* ---------- Drop targets ---------- */
    kanban.querySelectorAll('.kanban-cards').forEach((column) => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            column.classList.add('drop-target');
        });

        column.addEventListener('dragleave', () => column.classList.remove('drop-target'));

        column.addEventListener('drop', (e) => {
            e.preventDefault();
            column.classList.remove('drop-target');
            if (!draggedCard) return;

            const empty = column.querySelector('.kanban-empty');
            if (empty) empty.remove();

            column.appendChild(draggedCard);
            updateColumnCounts();
            persistMove(draggedCard, column);
            draggedCard = null;
        });
    });

    /* ---------- Persist the new column order ---------- */
    function persistMove(card, column) {
        const ids = [...column.querySelectorAll('.kanban-card')]
            .map((c) => c.dataset.taskId);

        fetch(moveUrl.replace(':taskId', card.dataset.taskId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                status: column.dataset.column,
                order: ids,
            }),
        })
            .then((res) => {
                if (!res.ok) {
                    return res.json().then((data) => {
                        throw new Error(data.message || 'Không thể di chuyển task.');
                    });
                }
                return res.json();
            })
            .then(() => flash('Đã cập nhật trạng thái task ✅', 'success'))
            .catch((err) => {
                flash(err.message, 'error');
                window.location.reload();
            });
    }

    function updateColumnCounts() {
        kanban.querySelectorAll('.kanban-col').forEach((col) => {
            const count = col.querySelectorAll('.kanban-card').length;
            col.querySelector('.kanban-count').textContent = count;
        });
    }

    /* ---------- Quick-create task form ---------- */
    const createForm = document.getElementById('task-create-form');
    const createStatus = document.getElementById('create-status');
    const createTitle = document.getElementById('task-create-title');

    document.querySelectorAll('[data-toggle-column]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const column = btn.dataset.toggleColumn;
            createStatus.value = column;
            createTitle.textContent = '➕ Thêm Task — ' + column.replace('_', ' ');
            createForm.style.display = 'block';
            createForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            createForm.querySelector('input[name="title"]').focus();
        });
    });

    const cancelBtn = document.getElementById('task-create-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', () => (createForm.style.display = 'none'));

    /* ---------- Toast ---------- */
    function flash(message, type) {
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.className = 'toast show toast-' + type;
        setTimeout(() => toast.classList.remove('show'), 2500);
    }
})();
