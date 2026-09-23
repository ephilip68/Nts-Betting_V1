const toggleButton = document.querySelector('[data-member-sidebar-toggle]');
const sidebar = document.querySelector('.member-sidebar');

if (toggleButton && sidebar) {

    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {

        if (!sidebar.classList.contains('is-open')) {
            return;
        }

        if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
            sidebar.classList.remove('is-open');
        }

    });

}
