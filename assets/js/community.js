document.addEventListener('DOMContentLoaded', () => {

    const composer = document.querySelector('.community-composer');
    const openButton = document.querySelector('[data-community-open-composer]');
    const closeButton = document.querySelector('.community-composer__close');
    const publishButton = composer.querySelector('.community-composer__publish');

    const communityFeed = document.querySelector('.community-feed');

    const typeButtons = document.querySelectorAll('.community-composer__type');

    if (!composer || !openButton || !closeButton) {
        return;
    }


    /* =====================================================
       ÉLÉMENTS COMPOSER
       ===================================================== */

    const uploadZone = composer.querySelector(
        '.community-composer__upload'
    );

    const fileInput = composer.querySelector(
        '.community-composer__file'
    );

    let selectedFile = null;

    const uploadButton = composer.querySelector(
        '.community-composer__upload-button'
    );

    const preview = composer.querySelector(
        '.community-composer__preview'
    );

    const previewImage = composer.querySelector(
        '.community-composer__preview-image img'
    );

    const previewName = composer.querySelector(
        '.community-composer__preview-name'
    );

    const previewRemove = composer.querySelector(
        '.community-composer__preview-remove'
    );


    /* =====================================================
       OUVRIR
       ===================================================== */

    openButton.addEventListener('click', () => {

        composer.classList.add('is-open');

    });


    /* =====================================================
       FERMER
       ===================================================== */

    closeButton.addEventListener('click', () => {

        composer.classList.remove('is-open');

    });


    /* =====================================================
       TYPE DE PUBLICATION
       ===================================================== */

    typeButtons.forEach((button) => {

        button.addEventListener('click', () => {

            typeButtons.forEach((item) => {
                item.classList.remove('is-active');
            });

            button.classList.add('is-active');


            const type = button
                .querySelector('span')
                ?.textContent
                .trim()
                .toLowerCase();


            composer.classList.remove(
                'is-ticket',
                'is-discussion',
                'is-analysis',
                'is-vip'
            );


            if (type === 'ticket') {

                composer.classList.add('is-ticket');

            } else if (type === 'discussion') {

                composer.classList.add('is-discussion');

            } else if (type === 'analyse') {

                composer.classList.add('is-analysis');

            } else if (type === 'vip') {

                composer.classList.add('is-vip');

            }


            if (type === 'discussion') {

                uploadZone?.classList.add('is-hidden');

            } else {

                uploadZone?.classList.remove('is-hidden');

            }

        });

    });


    /* =====================================================
       UPLOAD
       ===================================================== */

    if (
        !uploadZone ||
        !fileInput ||
        !uploadButton ||
        !preview ||
        !previewImage ||
        !previewName ||
        !previewRemove
    ) {
        return;
    }


    uploadButton.addEventListener('click', (event) => {

        event.stopPropagation();

        fileInput.click();

    });


    uploadZone.addEventListener('click', () => {

        fileInput.click();

    });


    fileInput.addEventListener('change', () => {

        if (!fileInput.files.length) {
            return;
        }

        handleFile(fileInput.files[0]);

    });


    /* =====================================================
       DRAG & DROP
       ===================================================== */

    uploadZone.addEventListener('dragover', (event) => {

        event.preventDefault();

        uploadZone.classList.add('is-dragging');

    });


    uploadZone.addEventListener('dragleave', () => {

        uploadZone.classList.remove('is-dragging');

    });


    uploadZone.addEventListener('drop', (event) => {

        event.preventDefault();

        uploadZone.classList.remove('is-dragging');

        const file = event.dataTransfer.files[0];

        if (!file) {
            return;
        }

        handleFile(file);

    });


    /* =====================================================
       TRAITEMENT IMAGE
       ===================================================== */

    function handleFile(file) {

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        selectedFile = file;

        if (!allowedTypes.includes(file.type)) {
            return;
        }


        const reader = new FileReader();

        reader.addEventListener('load', () => {

            previewImage.src = reader.result;

            previewName.textContent = file.name;

            preview.classList.add('is-visible');

        });

        reader.readAsDataURL(file);

    }


    /* =====================================================
       SUPPRIMER IMAGE
       ===================================================== */

    previewRemove.addEventListener('click', () => {

        fileInput.value = '';

        previewImage.src = '';

        previewName.textContent = '';

        preview.classList.remove('is-visible');

    });

    function getPostTypeClass(type) {

        switch (type) {
            case 'Ticket':
                return 'ticket';

            case 'Discussion':
                return 'discussion';

            case 'Analyse':
                return 'analysis';

            case 'VIP':
                return 'vip';

            default:
                return 'discussion';
        }
    }

publishButton?.addEventListener('click', () => {

    const activeType = composer.querySelector(
        '.community-composer__type.is-active span'
    )?.textContent.trim();

    const textarea = composer.querySelector(
        '.community-composer__textarea'
    );

    const content = textarea?.value.trim();

    const activeTypeButton = composer.querySelector(
        '.community-composer__type.is-active'
    );

    const type = activeTypeButton?.dataset.type;

    if (!type || !content) {
        textarea?.focus();
        return;
    }

   const formData = new FormData();

    formData.append('type', type);
    formData.append('content', content);

    const analysisTitle = composer.querySelector(
        '#community-analysis-title'
    )?.value.trim();

    const analysisSport = composer.querySelector(
        '#community-analysis-sport'
    )?.value;

    const vipMatch = composer.querySelector(
        '#community-vip-match'
    )?.value.trim();

    const vipPrediction = composer.querySelector(
        '#community-vip-prediction'
    )?.value.trim();

    const vipOdds = composer.querySelector(
        '#community-vip-odds'
    )?.value.trim();

    const vipStake = composer.querySelector(
        '#community-vip-stake'
    )?.value.trim();

    const vipConfidence = composer.querySelector(
        '#community-vip-confidence'
    )?.value;

    formData.append('sport', analysisSport || 'football');
    formData.append('analysisTitle', analysisTitle || '');
    formData.append('vipMatch', vipMatch || '');
    formData.append('vipPrediction', vipPrediction || '');
    formData.append('vipOdds', vipOdds || '');
    formData.append('vipStake', vipStake || '');
    formData.append('vipConfidence', vipConfidence || '');

    if (selectedFile) {
        formData.append('photo', selectedFile);
    }

    fetch('/community/post/create', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(result => {
    console.log('REPONSE SYMFONY :', result);
    alert(result);
})
        .catch(error => {
            console.error('Erreur backend :', error);
        });

    const imageSrc = previewImage?.src || '';

    const hasImage =
        imageSrc &&
        preview?.classList.contains('is-visible');

    const post = document.createElement('article');

    post.className = 'community-post community-post--new';

    post.innerHTML = `
        <header class="community-post__header">

            <div class="community-post__author">

                <div class="community-post__avatar">
                    T
                </div>

                <div class="community-post__author-info">

                    <strong>
                        Thomas11
                    </strong>

                    <span>
                        À l'instant · ${activeType || 'Publication'}
                    </span>

                </div>

            </div>

            <button
                type="button"
                class="community-post__menu"
                aria-label="Options"
            >
                <i class="fa-solid fa-ellipsis"></i>
            </button>

        </header>

        <div class="community-post__body">

            ${activeType === 'Analyse' ? `
                <div class="community-post__analysis-info">

                    <strong>
                        ${analysisTitle || 'Analyse'}
                    </strong>

                    ${analysisSport ? `
                        <span>
                            ${analysisSport}
                        </span>
                    ` : ''}

                </div>
            ` : ''}

            ${activeType === 'VIP' ? `
                <div class="community-post__vip-info">

                    <div class="community-post__vip-header">

                        <i class="fa-solid fa-crown"></i>

                        <strong>
                            Pronostic VIP
                        </strong>

                        <span>
                            VIP
                        </span>

                    </div>

                    <div class="community-post__vip-match">
                        ${vipMatch || 'Match / Événement'}
                    </div>

                    <div class="community-post__vip-grid">

                        <div>
                            <span>Pronostic</span>

                            <strong>
                                ${vipPrediction || '—'}
                            </strong>
                        </div>

                        <div>
                            <span>Cote</span>

                            <strong>
                                ${vipOdds || '—'}
                            </strong>
                        </div>

                        <div>
                            <span>Mise conseillée</span>

                            <strong>
                                ${vipStake || '—'}
                            </strong>
                        </div>

                        <div>
                            <span>Confiance</span>

                            <strong>
                                ${vipConfidence
                                    ? '★'.repeat(Number(vipConfidence))
                                    : '—'}
                            </strong>
                        </div>

                    </div>

                    <div class="community-post__vip-lock">

                        <i class="fa-solid fa-lock"></i>

                        <span>
                            Contenu réservé aux membres VIP
                        </span>

                    </div>

                </div>
            ` : ''}

            <div class="community-post__tags">

                <span class="community-post__tag community-post__tag--${getPostTypeClass(activeType)}">
                    ${activeType || 'Publication'}
                </span>

            </div>

            <p class="community-post__text">
                ${content}
            </p>

            ${hasImage ? `
                <div class="community-post__media">

                    <img
                        src="${imageSrc}"
                        alt="Image partagée par Thomas11"
                    >

                </div>
            ` : ''}

            <footer class="community-post__actions">

                <button type="button">

                    <i class="fa-regular fa-heart"></i>

                    <span>0</span>

                </button>

                <button type="button">

                    <i class="fa-regular fa-comment"></i>

                    <span>0</span>

                </button>

                <button
                    type="button"
                    class="community-post__share-button"
                >
                    <i class="fa-solid fa-share-nodes"></i>
                    <span>Partager</span>
                </button>

            </footer>

            <div class="community-post__comments">

                <div class="community-post__comment-form">

                    <div class="community-post__comment-avatar">
                        T
                    </div>

                    <input
                        type="text"
                        class="community-post__comment-input"
                        placeholder="Écrire un commentaire..."
                        autocomplete="off"
                    >

                    <button
                        type="button"
                        class="community-post__comment-submit"
                        aria-label="Publier le commentaire"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>

                </div>

                <div class="community-post__comments-list"></div>

            </div>

        </div>
        <div class="community-post__share">

            <div class="community-post__share-header">

                <strong>
                    Partager cette publication
                </strong>

                <button
                    type="button"
                    class="community-post__share-close"
                    aria-label="Fermer"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <div class="community-post__share-actions">

                <button
                    type="button"
                    class="community-post__share-action"
                    data-share="copy"
                >
                    <i class="fa-solid fa-link"></i>
                    <span>Copier le lien</span>
                </button>

                <button
                    type="button"
                    class="community-post__share-action"
                    data-share="telegram"
                >
                    <i class="fa-brands fa-telegram"></i>
                    <span>Telegram</span>
                </button>

                <button
                    type="button"
                    class="community-post__share-action"
                    data-share="whatsapp"
                >
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </button>

            </div>

        </div>
    `;

    const firstPost = communityFeed.querySelector(
        '.community-post'
    );

    if (firstPost) {
        communityFeed.insertBefore(post, firstPost);
    } else {
        communityFeed.appendChild(post);
    }

    /*
     * Réinitialisation du formulaire
     */

    textarea.value = '';

    const analysisTitleInput = composer.querySelector(
        '#community-analysis-title'
    );

    const analysisSportInput = composer.querySelector(
        '#community-analysis-sport'
    );

    if (analysisTitleInput) {
        analysisTitleInput.value = '';
    }

    if (analysisSportInput) {
        analysisSportInput.value = '';
    }

    const vipInputs = [
        '#community-vip-match',
        '#community-vip-prediction',
        '#community-vip-odds',
        '#community-vip-stake'
    ];

    vipInputs.forEach((selector) => {

        const input = composer.querySelector(selector);

        if (input) {
            input.value = '';
        }

    });

    const vipConfidenceInput = composer.querySelector(
        '#community-vip-confidence'
    );

    if (vipConfidenceInput) {
        vipConfidenceInput.value = '';
    }

    fileInput.value = '';
    selectedFile = null;
    previewImage.src = '';
    previewName.textContent = '';
    preview.classList.remove('is-visible');

    composer.classList.remove('is-open');

});

    // =========================================
    // COMMUNITY — J'AIME
    // =========================================

    communityFeed?.addEventListener('click', async (event) => {
        const button = event.target.closest('.community-post__actions button');

        if (!button) return;

        const icon = button.querySelector('.fa-heart');

        if (!icon) return;

        const counter = button.querySelector('span');

        if (!counter) return;

        const post = button.closest('.community-post');

        if (!post) return;

        const postId = post.dataset.postId;

        if (!postId) return;

        try {
            const response = await fetch(`/community/post/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Erreur lors du like.');
            }

            const result = await response.json();

            if (!result.success) {
                return;
            }

            if (result.liked) {
                button.classList.add('is-liked');
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                button.classList.remove('is-liked');
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }

            counter.textContent = result.count;

        } catch (error) {
            console.error('Erreur like :', error);
        }
    });

    // =========================================
    // COMMUNITY — COMMENTAIRES
    // =========================================

    communityFeed?.addEventListener('click', (event) => {

        const button = event.target.closest(
            '.community-post__actions button'
        );

        if (!button) {
            return;
        }

        const icon = button.querySelector(
            '.fa-comment'
        );

        if (!icon) {
            return;
        }

        const post = button.closest(
            '.community-post'
        );

        if (!post) {
            return;
        }

        const comments = post.querySelector(
            '.community-post__comments'
        );

        if (!comments) {
            return;
        }

        comments.classList.toggle('is-open');

        if (comments.classList.contains('is-open')) {

            comments.querySelector(
                '.community-post__comment-input'
            )?.focus();

        }

    });

    // =========================================
    // COMMUNITY — PUBLIER UN COMMENTAIRE
    // =========================================

    communityFeed?.addEventListener('click', async (event) => {

        const submit = event.target.closest(
            '.community-post__comment-submit'
        );

        if (!submit) {
            return;
        }

        const comments = submit.closest(
            '.community-post__comments'
        );

        if (!comments) {
            return;
        }

        const post = comments.closest(
            '.community-post'
        );

        if (!post) {
            return;
        }

        const input = comments.querySelector(
            '.community-post__comment-input'
        );

        const list = comments.querySelector(
            '.community-post__comments-list'
        );

        const text = input?.value.trim();

        if (!text || !list) {
            return;
        }

        const postId = post.dataset.postId;

        if (!postId) {
            console.error('ID du post introuvable.');
            return;
        }


        // Éviter plusieurs clics pendant l'envoi
        if (submit.disabled) {
            return;
        }

        submit.disabled = true;
        input.disabled = true;


        try {

            const formData = new FormData();

            formData.append('content', text);


            const response = await fetch(
                `/community/post/${postId}/comment`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }
            );


            const data = await response.json();


            if (!response.ok || !data.success) {

                console.error(
                    'Erreur commentaire :',
                    data.message || 'Erreur inconnue'
                );

                return;
            }


            // =========================================
            // COMMENTAIRE ENREGISTRÉ EN BDD
            // =========================================

            const comment = document.createElement('div');

            comment.className =
                'community-post__comment';

            comment.dataset.commentId =
                data.comment.id;


            const avatar = document.createElement('div');

            avatar.className =
                'community-post__comment-avatar';


            if (data.comment.photo) {

                const image = document.createElement('img');

                image.src =
                    `/uploads/profile/${data.comment.photo}`;

                image.alt =
                    data.comment.nickname;

                avatar.appendChild(image);

            } else {

                avatar.textContent =
                    data.comment.nickname.charAt(0).toUpperCase();

            }


            const content = document.createElement('div');

            content.className =
                'community-post__comment-content';


            const header = document.createElement('div');

            header.className =
                'community-post__comment-header';


            const nickname = document.createElement('strong');

            nickname.textContent =
                data.comment.nickname;


            const date = document.createElement('span');

            date.textContent =
                data.comment.createdAt;


            header.appendChild(nickname);
            header.appendChild(date);


            const paragraph = document.createElement('p');

            paragraph.textContent =
                data.comment.content;


            content.appendChild(header);
            content.appendChild(paragraph);


            comment.appendChild(avatar);
            comment.appendChild(content);


            list.appendChild(comment);


            // =========================================
            // RESET INPUT
            // =========================================

            input.value = '';

            input.focus();


            // =========================================
            // INCRÉMENTER LE COMPTEUR
            // =========================================

            const commentButton = post.querySelector(
                '.community-post__actions .fa-comment'
            )?.closest('button');


            if (commentButton) {

                const counter = commentButton.querySelector(
                    'span'
                );


                if (counter) {

                    const currentCount =
                        parseInt(counter.textContent, 10) || 0;

                    counter.textContent =
                        currentCount + 1;
                }
            }


        } catch (error) {

            console.error(
                'Erreur lors de l\'enregistrement du commentaire :',
                error
            );

        } finally {

            submit.disabled = false;
            input.disabled = false;

        }

    });

    // =========================================
    // COMMUNITY — ENTRÉE POUR PUBLIER
    // =========================================

    communityFeed?.addEventListener('keydown', (event) => {

        const input = event.target.closest(
            '.community-post__comment-input'
        );

        if (!input) {
            return;
        }

        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const comments =
            input.closest('.community-post__comments');

        if (!comments) {
            return;
        }

        const submit =
            comments.querySelector(
                '.community-post__comment-submit'
            );

        if (!submit) {
            return;
        }

        submit.click();

    });

    // =========================================
    // COMMUNITY — PARTAGE
    // =========================================

    communityFeed?.addEventListener('click', async (event) => {

        // -----------------------------------------
        // OUVRIR LE PARTAGE
        // -----------------------------------------

        const shareButton = event.target.closest(
            '.community-post__actions button'
        );

        if (
            shareButton &&
            shareButton.querySelector('.fa-share-nodes')
        ) {

            const post = shareButton.closest(
                '.community-post'
            );

            if (!post) {
                return;
            }

            let share = post.querySelector(
                '.community-post__share'
            );

            // Si la zone n'existe pas encore
            // (anciennes publications)
            if (!share) {

                share = document.createElement('div');

                share.className =
                    'community-post__share';

                share.innerHTML = `
                    <div class="community-post__share-header">

                        <strong>
                            Partager cette publication
                        </strong>

                        <button
                            type="button"
                            class="community-post__share-close"
                            aria-label="Fermer"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>

                    <div class="community-post__share-actions">

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="copy"
                        >
                            <i class="fa-solid fa-link"></i>
                            <span>Copier le lien</span>
                        </button>

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="facebook"
                        >
                            <i class="fa-brands fa-facebook"></i>
                            <span>Facebook</span>
                        </button>

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="instagram"
                        >
                            <i class="fa-brands fa-instagram"></i>
                            <span>Instagram</span>
                        </button>

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="tiktok"
                        >
                            <i class="fa-brands fa-tiktok"></i>
                            <span>TikTok</span>
                        </button>

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="telegram"
                        >
                            <i class="fa-brands fa-telegram"></i>
                            <span>Telegram</span>
                        </button>

                        <button
                            type="button"
                            class="community-post__share-action"
                            data-share="whatsapp"
                        >
                            <i class="fa-brands fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </button>

                    </div>
                `;

                const actions = post.querySelector(
                    '.community-post__actions'
                );

                actions?.after(share);
            }

            share.classList.toggle('is-open');

            return;
        }


        // -----------------------------------------
        // FERMER LE PARTAGE
        // -----------------------------------------

        const closeButton = event.target.closest(
            '.community-post__share-close'
        );

        if (closeButton) {

            const share = closeButton.closest(
                '.community-post__share'
            );

            share?.classList.remove('is-open');

            return;
        }


        // -----------------------------------------
        // ACTION DE PARTAGE
        // -----------------------------------------

        const shareAction = event.target.closest(
            '.community-post__share-action'
        );

        if (!shareAction) {
            return;
        }

        const post = shareAction.closest(
            '.community-post'
        );

        if (!post) {
            return;
        }

        const shareType = shareAction.dataset.share;

        const shareUrl = window.location.href;

        const shareText =
            'Découvrez cette publication sur NTS Betting';

        // -----------------------------------------
        // COPIER LE LIEN
        // -----------------------------------------

        if (shareType === 'copy') {

            try {

                await navigator.clipboard.writeText(
                    shareUrl
                );

                const text =
                    shareAction.querySelector('span');

                if (text) {
                    text.textContent = 'Lien copié ✓';
                }

            } catch (error) {

                console.error(
                    'Impossible de copier le lien.',
                    error
                );
            }

            return;
        }


        // -----------------------------------------
        // TELEGRAM
        // -----------------------------------------

        if (shareType === 'telegram') {

            window.open(
                `https://t.me/share/url?url=${encodeURIComponent(shareUrl)}`,
                '_blank'
            );

            return;
        }

        // -----------------------------------------
        // FACEBOOK
        // -----------------------------------------

        if (shareType === 'facebook') {

            window.open(
                `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`,
                '_blank',
                'width=700,height=600'
            );

            return;
        }


        // -----------------------------------------
        // INSTAGRAM
        // -----------------------------------------

        if (shareType === 'instagram') {

            navigator.clipboard.writeText(
                shareUrl
            );

            window.open(
                'https://www.instagram.com/',
                '_blank'
            );

            return;
        }


        // -----------------------------------------
        // TIKTOK
        // -----------------------------------------

        if (shareType === 'tiktok') {

            navigator.clipboard.writeText(
                shareUrl
            );

            window.open(
                'https://www.tiktok.com/',
                '_blank'
            );

            return;
        }


        // -----------------------------------------
        // WHATSAPP
        // -----------------------------------------

        if (shareType === 'whatsapp') {

            window.open(
                `https://wa.me/?text=${encodeURIComponent(shareUrl)}`,
                '_blank'
            );

        }

    });

    // =========================================
    // SECURITY — ÉCHAPPER LE HTML
    // =========================================

    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value;

        return div.innerHTML;
    }

});