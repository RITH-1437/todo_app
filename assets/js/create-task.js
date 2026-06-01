document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('create-page-body');
    const form = document.getElementById('create-task-form');
    const submitButton = document.getElementById('create-task-submit');

    applyCreateTaskTheme(body);

    if (!form || !submitButton) {
        return;
    }

    form.addEventListener('submit', event => {
        const titleInput = form.querySelector('input[name="title"]');
        const prioritySelect = form.querySelector('select[name="priority"]');

        const title = titleInput ? titleInput.value.trim() : '';
        const priority = prioritySelect ? prioritySelect.value : '';

        if (!title) {
            event.preventDefault();
            showCreateTaskToast('Task title is required.', 'warning');
            titleInput?.focus();
            return;
        }

        if (!['low', 'medium', 'high'].includes(priority)) {
            event.preventDefault();
            showCreateTaskToast('Please choose a valid priority.', 'warning');
            prioritySelect?.focus();
            return;
        }

        // Delay disabling so the browser captures the full POST payload first.
        // The hidden input name="create_task" is the reliable trigger on the server.
        setTimeout(() => setCreateTaskLoading(submitButton, true), 0);
    });
});

function applyCreateTaskTheme(body) {
    if (!body) {
        return;
    }

    const savedTheme = localStorage.getItem('theme');
    const initialTheme = document.documentElement.dataset.theme || body.dataset.theme || 'dark';
    const theme = ['light', 'dark'].includes(savedTheme) ? savedTheme : initialTheme;

    body.dataset.theme = theme;
    document.documentElement.dataset.theme = theme;
    body.classList.toggle('dark', theme === 'dark');
    document.documentElement.classList.toggle('dark', theme === 'dark');
}

function setCreateTaskLoading(button, isLoading) {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
    }

    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);
    button.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    button.textContent = isLoading ? 'Adding task...' : button.dataset.defaultText;
}

function showCreateTaskToast(message, type = 'info') {
    if (window.showToast) {
        window.showToast(message, type);
        return;
    }

    console[type === 'error' ? 'error' : 'log'](message);
}
