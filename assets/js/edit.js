const body = document.getElementById('body');

const editTitle = document.getElementById('edit-title');

const inputs = document.querySelectorAll('.edit-input');

if (localStorage.getItem('theme') === 'dark') {

    enableDarkMode();
}

function enableDarkMode() {

    body.classList.remove('bg-gray-100');

    body.classList.add('bg-gray-900');

    editTitle.classList.remove('text-gray-800');

    editTitle.classList.add('text-white');

    inputs.forEach(input => {

        input.classList.add(
            'bg-gray-800',
            'text-white',
            'border-gray-700'
        );
    });
}