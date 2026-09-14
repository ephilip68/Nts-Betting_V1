document.addEventListener('DOMContentLoaded', () => {

    const track = document.querySelector('.hero-sports-track');

    if (!track) {
        return;
    }

    const originalItems = Array.from(track.children);

    if (!originalItems.length) {
        return;
    }

    /*
     * On crée plusieurs séries afin que la barre
     * soit toujours remplie, quelle que soit
     * la largeur de l'écran.
     */

    const containerWidth = track.parentElement.offsetWidth;

    let currentWidth = track.scrollWidth;

    while (currentWidth < containerWidth * 2) {

        originalItems.forEach((item) => {

            const clone = item.cloneNode(true);

            clone.setAttribute('aria-hidden', 'true');

            track.appendChild(clone);

        });

        currentWidth = track.scrollWidth;
    }

});