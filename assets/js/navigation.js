/**
 * NTS BETTING
 * Navigation
 */

document.addEventListener('DOMContentLoaded', () => {

    const header = document.querySelector('.site-header');

    if (!header) {
        return;
    }

    const toggle = header.querySelector('.navbar-toggle');
    const navigation = header.querySelector('.navbar-navigation');
    const links = header.querySelectorAll('.navbar-link');
    const searchButton = header.querySelector('.navbar-search');


    /*
     * =========================================================
     * NAVBAR — SCROLL
     * =========================================================
     *
     * Au chargement :
     * → navbar transparente intégrée au Hero
     *
     * Après quelques pixels de scroll :
     * → navbar fixe
     * → fond sombre
     * → effet blur
     *
     */

    const scrollThreshold = 40;

    const updateNavbarOnScroll = () => {

        if (window.scrollY > scrollThreshold) {

            header.classList.add('is-scrolled');

        } else {

            header.classList.remove('is-scrolled');

        }

    };


    /*
     * Vérification initiale
     * utile notamment si la page est rechargée
     * alors qu'elle est déjà scrollée.
     */

    updateNavbarOnScroll();


    /*
     * Écoute du scroll
     */

    window.addEventListener(
        'scroll',
        updateNavbarOnScroll,
        { passive: true }
    );


    /*
     * =========================================================
     * MOBILE MENU
     * =========================================================
     */

    const openMobileMenu = () => {

        if (!toggle || !navigation) {
            return;
        }

        navigation.classList.add('is-open');
        toggle.classList.add('is-active');

        toggle.setAttribute('aria-expanded', 'true');

        document.body.classList.add('menu-open');
    };


    const closeMobileMenu = () => {

        if (!toggle || !navigation) {
            return;
        }

        navigation.classList.remove('is-open');
        toggle.classList.remove('is-active');

        toggle.setAttribute('aria-expanded', 'false');

        document.body.classList.remove('menu-open');
    };


    const toggleMobileMenu = () => {

        if (!navigation) {
            return;
        }

        if (navigation.classList.contains('is-open')) {

            closeMobileMenu();

        } else {

            openMobileMenu();

        }

    };


    /*
     * Ouverture / fermeture du menu mobile
     */

    if (toggle) {

        toggle.addEventListener(
            'click',
            toggleMobileMenu
        );

    }


    /*
     * =========================================================
     * FERMETURE APRÈS CLIC SUR UN LIEN
     * =========================================================
     */

    links.forEach((link) => {

        link.addEventListener('click', () => {

            closeMobileMenu();

        });

    });


    /*
     * =========================================================
     * FERMETURE AVEC ESCAPE
     * =========================================================
     */

    document.addEventListener('keydown', (event) => {

        if (event.key === 'Escape') {

            closeMobileMenu();

        }

    });


    /*
     * =========================================================
     * FERMETURE EN CLIQUANT À L'EXTÉRIEUR
     * =========================================================
     */

    document.addEventListener('click', (event) => {

        if (
            !navigation ||
            !navigation.classList.contains('is-open')
        ) {
            return;
        }

        if (!header.contains(event.target)) {

            closeMobileMenu();

        }

    });


    /*
     * =========================================================
     * RETOUR AU MODE DESKTOP
     * =========================================================
     */

    window.addEventListener('resize', () => {

        if (window.innerWidth > 900) {

            closeMobileMenu();

        }

    });


    /*
     * =========================================================
     * RECHERCHE
     * =========================================================
     *
     * Pour l'instant, le bouton est préparé.
     * La vraie recherche sera connectée lorsque nous créerons
     * la page / le système de recherche.
     *
     */

    if (searchButton) {

        searchButton.addEventListener('click', () => {

            console.log('NTS Betting — recherche');

        });

    }

});