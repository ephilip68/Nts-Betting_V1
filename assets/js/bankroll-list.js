document.querySelector('[data-bankroll-new-toggle]')?.addEventListener('click', () => {
    const form = document.querySelector('[data-bankroll-new-form]');
    if (form) form.hidden = !form.hidden;
});

document.querySelectorAll('[data-bankroll-rename-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-bankroll-rename-toggle');
        const form = document.getElementById(targetId);
        if (form) form.hidden = !form.hidden;
    });
});
