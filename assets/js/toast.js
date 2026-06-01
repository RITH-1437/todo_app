function getToastContainer() {
    let container = document.getElementById('toast-container');

    if (container) {
        return container;
    }

    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-5 right-5 z-50 space-y-3';
    document.body.appendChild(container);

    return container;
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');

    const styles = {
        success: 'bg-green-500 shadow-green-500/20',
        error: 'bg-red-500 shadow-red-500/20',
        warning: 'bg-yellow-500 text-slate-950 shadow-yellow-500/20',
        info: 'bg-blue-500 shadow-blue-500/20'
    };

    toast.className = `
        ${styles[type] || styles.info}
        ${type === 'warning' ? '' : 'text-white'}
        px-6
        py-4
        rounded-2xl
        shadow-2xl
        font-medium
        transform
        transition-all
        duration-300
        translate-x-full
        opacity-0
        max-w-sm
    `;

    toast.textContent = message;
    getToastContainer().appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 100);

    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');

        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

window.showToast = showToast;
