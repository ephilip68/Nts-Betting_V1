document.addEventListener('DOMContentLoaded', () => {

    const pendingState = document.getElementById('verification-pending');
    const successState = document.getElementById('verification-success');
    const benefits = document.getElementById('verification-benefits');

    if (!pendingState || !successState) {
        return;
    }

    let checking = false;

    const checkVerification = async () => {

        if (checking) {
            return;
        }

        checking = true;

        try {

            const response = await fetch('/verification-email/status', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.verified === true) {

                pendingState.classList.add('verification-state-hide');

                setTimeout(() => {

                    pendingState.hidden = true;

                    successState.hidden = false;
                    successState.classList.add('verification-state-show');

                    if (benefits) {
                        benefits.hidden = false;
                        benefits.classList.add('verification-state-show');
                    }

                }, 450);
            }

        } catch (error) {

            console.error(
                'Erreur lors de la vérification de l’e-mail :',
                error
            );

        } finally {

            checking = false;

        }
    };

    // Première vérification immédiate
    checkVerification();

    // Vérification automatique toutes les 2,5 secondes
    const verificationInterval = setInterval(() => {

        if (successState.hidden === false) {
            clearInterval(verificationInterval);
            return;
        }

        checkVerification();

    }, 2500);

});