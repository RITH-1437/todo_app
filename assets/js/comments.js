/* ============================================================
   COMMENT SYSTEM — AJAX submit, inline edit, delete
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Wire up submit handlers for all comment forms on the page.
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', handleCommentSubmit);
    });

    // Delegate edit / delete clicks so dynamically inserted cards work too.
    document.querySelectorAll('.comments-list').forEach(list => {
        list.addEventListener('click', handleCommentAction);
    });
});

// ── Submit new comment ────────────────────────────────────────────────────────

async function handleCommentSubmit(event) {
    event.preventDefault();

    const form     = event.currentTarget;
    const input    = form.querySelector('.comment-input');
    const button   = form.querySelector('.comment-submit');
    const taskCard = form.closest('.task-card');
    const list     = taskCard?.querySelector('.comments-list');
    const isOverdue = taskCard?.classList.contains('task-card--overdue') ?? false;
    const message  = input?.value.trim() ?? '';

    if (!message) {
        showCommentToast('Comment cannot be empty.', 'warning');
        input?.focus();
        return;
    }

    setCommentLoading(button, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' },
        });

        let data;
        try { data = await response.json(); } catch { throw new Error('Server error. Please try again.'); }

        if (!response.ok || !data.success) throw new Error(data.message || 'Failed to add comment.');

        if (list) {
            const card = createCommentCard(data.comment, isOverdue);
            card.classList.add('comment-card--entering');
            list.prepend(card);
            requestAnimationFrame(() => requestAnimationFrame(() => {
                card.classList.remove('comment-card--entering');
            }));
        }

        input.value = '';
        showCommentToast(data.message || 'Comment added!', 'success');
    } catch (err) {
        showCommentToast(err.message || 'Failed to add comment.', 'error');
    } finally {
        setCommentLoading(button, false);
    }
}

// ── Edit / Delete delegation ──────────────────────────────────────────────────

function handleCommentAction(event) {
    const editBtn   = event.target.closest('.comment-action-edit');
    const deleteBtn = event.target.closest('.comment-action-delete');

    if (editBtn)   enterEditMode(editBtn.closest('.comment-card'));
    else if (deleteBtn) deleteComment(deleteBtn.closest('.comment-card'));
}

// ── Inline edit ───────────────────────────────────────────────────────────────

function enterEditMode(card) {
    if (!card || card.classList.contains('comment-card--editing')) return;
    card.classList.add('comment-card--editing');

    const messageEl    = card.querySelector('.comment-message');
    const originalText = messageEl.textContent.trim();
    const commentId    = card.dataset.commentId;

    const textarea = document.createElement('textarea');
    textarea.className = 'comment-edit-textarea';
    textarea.value = originalText;

    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'comment-edit-save';
    saveBtn.textContent = 'Save';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'comment-edit-cancel';
    cancelBtn.textContent = 'Cancel';

    const controls = document.createElement('div');
    controls.className = 'comment-edit-controls';
    controls.append(saveBtn, cancelBtn);

    messageEl.replaceWith(textarea);
    card.querySelector('.comment-footer')?.before(controls);

    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = textarea.value.length;

    cancelBtn.addEventListener('click', () => exitEditMode(card, textarea, originalText, controls));

    saveBtn.addEventListener('click', async () => {
        const newText = textarea.value.trim();
        if (!newText) { showCommentToast('Comment cannot be empty.', 'warning'); return; }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const fd = new FormData();
            fd.append('comment_id', commentId);
            fd.append('message', newText);

            const response = await fetch('comments/update.php', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' },
            });

            let data;
            try { data = await response.json(); } catch { throw new Error('Server error.'); }
            if (!response.ok || !data.success) throw new Error(data.message || 'Update failed.');

            exitEditMode(card, textarea, newText, controls);
            showCommentToast('Comment updated.', 'success');
        } catch (err) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
            showCommentToast(err.message, 'error');
        }
    });
}

function exitEditMode(card, textarea, text, controls) {
    const p = document.createElement('p');
    p.className = 'comment-message';
    p.textContent = text;
    textarea.replaceWith(p);
    controls.remove();
    card.classList.remove('comment-card--editing');
}

// ── Delete ────────────────────────────────────────────────────────────────────

async function deleteComment(card) {
    if (!card) return;
    if (!confirm('Delete this comment?')) return;

    try {
        const fd = new FormData();
        fd.append('comment_id', card.dataset.commentId);

        const response = await fetch('comments/delete.php', {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json' },
        });

        let data;
        try { data = await response.json(); } catch { throw new Error('Server error.'); }
        if (!response.ok || !data.success) throw new Error(data.message || 'Delete failed.');

        card.classList.add('comment-card--removing');
        card.addEventListener('transitionend', () => card.remove(), { once: true });
        showCommentToast('Comment deleted.', 'success');
    } catch (err) {
        showCommentToast(err.message, 'error');
    }
}

// ── DOM builder ───────────────────────────────────────────────────────────────

function createCommentCard(comment, isOverdue = false) {
    const card = document.createElement('div');
    card.className = ['comment-card', 'comment-box', isOverdue ? 'overdue-comment' : '']
        .filter(Boolean).join(' ');
    card.dataset.commentId = comment.id;

    const messageEl = document.createElement('p');
    messageEl.className = 'comment-message';
    messageEl.textContent = comment.message;

    const footer = document.createElement('div');
    footer.className = 'comment-footer';

    const timestamp = document.createElement('small');
    timestamp.className = ['comment-time', isOverdue ? 'overdue-time' : ''].filter(Boolean).join(' ');
    timestamp.textContent = comment.created_at;

    const actions = document.createElement('div');
    actions.className = 'comment-actions';
    actions.innerHTML = `
        <button type="button" class="comment-action-btn comment-action-edit" title="Edit comment">${iconPencil()}</button>
        <button type="button" class="comment-action-btn comment-action-delete" title="Delete comment">${iconTrash()}</button>
    `;

    footer.append(timestamp, actions);
    card.append(messageEl, footer);

    return card;
}

// ── Icons (Heroicons solid 20, 13 x 13) ──────────────────────────────────────

function iconPencil() {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"/></svg>`;
}

function iconTrash() {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 3.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg>`;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function setCommentLoading(button, isLoading) {
    if (!button) return;
    if (!button.dataset.defaultText) button.dataset.defaultText = button.textContent.trim();
    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);
    button.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    button.textContent = isLoading ? 'Adding...' : button.dataset.defaultText;
    button.classList.toggle('opacity-70', isLoading);
    button.classList.toggle('cursor-not-allowed', isLoading);
}

function showCommentToast(message, type = 'info') {
    if (window.showToast) { window.showToast(message, type); return; }
    console[type === 'error' ? 'error' : 'log'](message);
}
