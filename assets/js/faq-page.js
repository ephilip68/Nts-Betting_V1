const searchInput = document.getElementById('faq-search-input');
const categoryButtons = document.querySelectorAll('[data-faq-category]');
const groups = document.querySelectorAll('[data-faq-group]');
const emptyState = document.getElementById('faq-empty');

if (searchInput && groups.length) {

    let activeCategory = '';

    const applyFilters = () => {

        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        groups.forEach((group) => {

            const groupCategory = group.getAttribute('data-faq-group');
            const categoryMatches = !activeCategory || groupCategory === activeCategory;
            let groupHasVisible = false;

            group.querySelectorAll('.faq-item').forEach((item) => {

                const question = item.getAttribute('data-faq-question') || '';
                const answer = item.getAttribute('data-faq-answer') || '';
                const textMatches = !query || question.includes(query) || answer.includes(query);
                const visible = categoryMatches && textMatches;

                item.hidden = !visible;

                if (visible) {
                    groupHasVisible = true;
                    visibleCount++;
                }

            });

            group.hidden = !groupHasVisible;

        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }

    };

    searchInput.addEventListener('input', applyFilters);

    categoryButtons.forEach((button) => {

        button.addEventListener('click', () => {

            activeCategory = button.getAttribute('data-faq-category') || '';

            categoryButtons.forEach((b) => b.classList.remove('is-active'));
            button.classList.add('is-active');

            applyFilters();

        });

    });

}
