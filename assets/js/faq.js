document.querySelectorAll('.faq-item__question').forEach((button) => {

    button.addEventListener('click', () => {

        const item = button.closest('.faq-item');

        document.querySelectorAll('.faq-item.is-open').forEach((openItem) => {

            if (openItem !== item) {
                openItem.classList.remove('is-open');
            }

        });

        item.classList.toggle('is-open');

    });

});