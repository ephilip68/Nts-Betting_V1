const helpToggle = document.querySelector('[data-pronostics-help-toggle]');
const helpPanel = document.querySelector('[data-pronostics-help-panel]');

if (helpToggle && helpPanel) {
    helpToggle.addEventListener('click', () => {
        helpPanel.hidden = !helpPanel.hidden;
    });
}

document.querySelectorAll('[data-pronostic-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.getElementById(button.getAttribute('data-pronostic-toggle'));
        if (target) {
            target.hidden = !target.hidden;
        }
    });
});

const sortSelect = document.querySelector('[data-pronostics-sort]');

if (sortSelect) {
    sortSelect.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortSelect.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
}
