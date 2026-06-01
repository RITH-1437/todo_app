// Force dark mode on all inputs
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"]');
    inputs.forEach(input => {
        input.style.backgroundColor = '#111827';
        input.style.color = 'white';
    });
});