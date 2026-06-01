const PROFILE_MAX_AVATAR_SIZE = 50 * 1024 * 1024;
const PROFILE_MAX_AVATAR_SIZE_LABEL = '50MB';
const PROFILE_VALID_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

document.addEventListener('DOMContentLoaded', () => {
    syncInitialTheme();
    setupAvatarPreview();
    setupAvatarUploadState();
    setupPasswordValidation();
    setupThemePreference();
});

function syncInitialTheme() {
    const serverTheme = window.todoInitialTheme === 'light' ? 'light' : 'dark';

    try {
        localStorage.setItem('theme', serverTheme);
    } catch (error) {
        // localStorage can be unavailable in private or restricted browser modes.
    }

    applyProfileTheme(serverTheme);
}

function setupAvatarPreview() {
    const input = document.getElementById('avatar-input');
    const preview = document.getElementById('avatar-preview');

    if (!input || !preview) {
        return;
    }

    let previewUrl = null;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        if (!file) {
            return;
        }

        if (!PROFILE_VALID_IMAGE_TYPES.includes(file.type)) {
            showProfileToast('Avatar must be a JPG, PNG, JPEG, or WEBP image.', 'error');
            input.value = '';
            return;
        }

        if (file.size > PROFILE_MAX_AVATAR_SIZE) {
            showProfileToast(`Avatar must be ${PROFILE_MAX_AVATAR_SIZE_LABEL} or smaller.`, 'error');
            input.value = '';
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
    });
}

function setupAvatarUploadState() {
    const form = document.getElementById('avatar-upload-form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        const input = form.querySelector('input[type="file"]');
        const button = form.querySelector('button[type="submit"]');
        const file = input?.files?.[0];

        if (!file) {
            event.preventDefault();
            showProfileToast('Please choose a valid image.', 'error');
            return;
        }

        setButtonLoading(button, true, 'Uploading...');
    });
}

function setupPasswordValidation() {
    const form = document.querySelector('form input[name="action"][value="update_password"]')?.closest('form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', event => {
        const newPassword = form.querySelector('input[name="new_password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;

        if (newPassword.length < 6) {
            event.preventDefault();
            showProfileToast('New password must be at least 6 characters.', 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            event.preventDefault();
            showProfileToast('Password confirmation does not match.', 'error');
        }
    });
}

function setupThemePreference() {
    const form = document.getElementById('theme-preference-form');

    if (!form) {
        return;
    }

    const select = form.querySelector('select[name="theme_preference"]');

    if (!select) {
        return;
    }

    if (select) {
        select.addEventListener('change', () => {
            const theme = select.value === 'light' ? 'light' : 'dark';
            applyProfileTheme(theme);
            setStoredTheme(theme);
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const theme = select.value === 'light' ? 'light' : 'dark';

        applyProfileTheme(theme);
        setStoredTheme(theme);
        setButtonLoading(button, true, 'Saving...');

        try {
            const response = await fetch('profile.php', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Theme preference could not be saved.');
            }

            const savedTheme = data.theme === 'light' ? 'light' : 'dark';
            select.value = savedTheme;
            applyProfileTheme(savedTheme);
            setStoredTheme(savedTheme);
            showProfileToast(data.message || 'Theme changed successfully.', 'success');
        } catch (error) {
            showProfileToast(error.message || 'Theme preference could not be saved.', 'error');
        } finally {
            setButtonLoading(button, false);
        }
    });
}

function applyProfileTheme(theme) {
    const body = document.getElementById('body');
    const isDark = theme === 'dark';

    if (!body) {
        return;
    }

    body.dataset.theme = theme;
    body.classList.remove('bg-slate-950', 'bg-gray-100', 'text-white', 'text-slate-900');
    body.classList.add(isDark ? 'bg-slate-950' : 'bg-gray-100', isDark ? 'text-white' : 'text-slate-900');

    document.querySelectorAll('.profile-surface').forEach(surface => {
        swapClasses(
            surface,
            isDark
                ? ['bg-white', 'border-slate-200', 'shadow-slate-200/80']
                : ['bg-white/[0.07]', 'border-white/10', 'shadow-slate-950/30'],
            isDark
                ? ['bg-white/[0.07]', 'border-white/10', 'shadow-slate-950/30']
                : ['bg-white', 'border-slate-200', 'shadow-slate-200/80']
        );
    });

    document.querySelectorAll('.profile-muted').forEach(element => {
        element.classList.remove('text-slate-300', 'text-slate-400', 'text-slate-500', 'text-slate-600');
        element.classList.add(isDark ? 'text-slate-400' : 'text-slate-500');
    });

    document.querySelectorAll('.profile-input').forEach(input => {
        swapClasses(
            input,
            isDark
                ? ['border-slate-200', 'bg-white', 'text-slate-900', 'placeholder-slate-400', 'file:bg-slate-100', 'file:text-slate-700']
                : ['border-white/10', 'bg-slate-900/80', 'text-white', 'placeholder-slate-400', 'file:bg-slate-800', 'file:text-white'],
            isDark
                ? ['border-white/10', 'bg-slate-900/80', 'text-white', 'placeholder-slate-400', 'file:bg-slate-800', 'file:text-white']
                : ['border-slate-200', 'bg-white', 'text-slate-900', 'placeholder-slate-400', 'file:bg-slate-100', 'file:text-slate-700']
        );
    });

    const backLink = document.getElementById('profile-back-link');

    if (backLink) {
        backLink.classList.remove('text-slate-400', 'hover:text-white', 'text-slate-500', 'hover:text-slate-900');
        backLink.classList.add(isDark ? 'text-slate-400' : 'text-slate-500', isDark ? 'hover:text-white' : 'hover:text-slate-900');
    }
}

function swapClasses(element, removeClasses, addClasses) {
    element.classList.remove(...removeClasses);
    element.classList.add(...addClasses);
}

function setStoredTheme(theme) {
    try {
        localStorage.setItem('theme', theme);
    } catch (error) {
        // Theme still works for the current page even when localStorage is blocked.
    }
}

function setButtonLoading(button, isLoading, loadingText = 'Saving...') {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
    }

    button.disabled = isLoading;
    button.textContent = isLoading ? loadingText : button.dataset.defaultText;
    button.classList.toggle('opacity-70', isLoading);
    button.classList.toggle('cursor-not-allowed', isLoading);
}

function showProfileToast(message, type) {
    if (window.showToast) {
        window.showToast(message, type);
        return;
    }

    console[type === 'error' ? 'error' : 'log'](message);
}
