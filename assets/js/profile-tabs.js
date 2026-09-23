const tabLinks = document.querySelectorAll('[data-profile-tabs] a');

tabLinks.forEach((link) => {

    link.addEventListener('click', () => {
        tabLinks.forEach((l) => l.classList.remove('is-active'));
        link.classList.add('is-active');
    });

});

if (tabLinks.length) {

    const sections = Array.from(tabLinks)
        .map((link) => document.querySelector(link.getAttribute('href')))
        .filter(Boolean);

    if (sections.length && 'IntersectionObserver' in window) {

        const observer = new IntersectionObserver((entries) => {

            const visible = entries.find((entry) => entry.isIntersecting);

            if (!visible) {
                return;
            }

            const id = `#${visible.target.id}`;

            tabLinks.forEach((link) => {
                link.classList.toggle('is-active', link.getAttribute('href') === id);
            });

        }, { rootMargin: '-20% 0px -70% 0px' });

        sections.forEach((section) => observer.observe(section));

    }

}

document.querySelectorAll('[data-photo-input]').forEach((input) => {

    input.addEventListener('change', () => {

        if (input.files && input.files[0]) {
            input.closest('form').querySelector('[data-photo-filename]').textContent = input.files[0].name;
        }

    });

});
