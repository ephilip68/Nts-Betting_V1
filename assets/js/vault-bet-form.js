const config = window.vaultBetFormConfig;

if (config) {

    const betTypeSelect = document.getElementById(config.betTypeId);
    const simpleFields = document.getElementById('simple-fields');
    const selectionsWrapper = document.getElementById('selections-wrapper');
    const container = document.getElementById('selections-container');
    const oddsField = document.getElementById(config.oddsId);
    const stakeField = document.getElementById(config.stakeId);
    const statusField = document.getElementById(config.statusId);
    const cashoutField = document.getElementById('cashout-field');
    const systemFields = document.getElementById('system-fields');
    const systemSelect = document.getElementById(config.systemOptionId);
    const profitField = document.getElementById('profit-field');
    const gainField = document.getElementById('gain-field');
    const boostCheckbox = document.getElementById('boost-checkbox');
    const boostedField = document.getElementById('isBoosted-field');
    const freebetCheckbox = document.getElementById('freebet-checkbox');
    const freebetField = document.getElementById('isFreebet-field');
    const insuredCheckbox = document.getElementById('insured-checkbox');
    const insuredField = document.getElementById('isInsured-field');
    const boostLabel = document.getElementById('boost-label');
    const insuredLabel = document.getElementById('insured-label');

    let index = container ? container.children.length : 0;
    const winnerOptionsCache = {};

    const roundCote = (value) => Math.round(value * 100) / 100;

    function displayResult(stake, profit) {
        const gain = roundCote(stake + profit);

        if (gainField) gainField.textContent = gain.toFixed(2);

        if (profitField) {
            profitField.textContent = profit.toFixed(2);
            profitField.classList.toggle('is-positive', profit > 0);
            profitField.classList.toggle('is-negative', profit < 0);
        }
    }

    const isCombine = () => ['combiné', 'live combiné'].includes(betTypeSelect.value);
    const isSysteme = () => betTypeSelect.value === 'système';

    // ====== Résultat (winner) dynamique selon le sport, avec noms d'équipes injectés ======
    function capitalize(value) {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
    }

    function withTeamNames(options, homeName, awayName) {
        const mapped = [];

        Object.keys(options).forEach((label) => {
            const value = options[label];
            let displayLabel = label;

            if (label.includes('(1/2)')) {
                displayLabel = `${label} (${homeName}/${awayName})`;
            } else if (label.includes('(2/1)')) {
                displayLabel = `${label} (${awayName}/${homeName})`;
            } else if (label.includes('(1/N)')) {
                displayLabel = `${label} (${homeName})`;
            } else if (label.includes('(N/2)')) {
                displayLabel = `${label} (${awayName})`;
            } else if (label.includes('-')) {
                if (homeName) mapped.push({ value, label: `${label} (${homeName})` });
                if (awayName) mapped.push({ value, label: `${label} (${awayName})` });
                if (homeName || awayName) return;
            }

            mapped.push({ value, label: displayLabel });
        });

        return mapped;
    }

    function loadWinnerOptions(sport, winnerSelect, homeInput, awayInput) {
        if (!sport || !winnerSelect) {
            return;
        }

        const apply = (options) => {
            const homeName = capitalize(homeInput?.value.trim() || '');
            const awayName = capitalize(awayInput?.value.trim() || '');
            const current = winnerSelect.value;

            winnerSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Sélectionnez un résultat';
            winnerSelect.appendChild(placeholder);

            withTeamNames(options, homeName, awayName).forEach(({ value, label }) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                winnerSelect.appendChild(opt);
            });

            if ([...winnerSelect.options].some((o) => o.value === current)) {
                winnerSelect.value = current;
            }
        };

        if (winnerOptionsCache[sport]) {
            apply(winnerOptionsCache[sport]);
            return;
        }

        fetch(`${config.winnerOptionsUrl}?sport=${encodeURIComponent(sport)}`)
            .then((res) => res.json())
            .then((data) => {
                winnerOptionsCache[sport] = data;
                apply(data);
            })
            .catch(() => {});
    }

    function bindCategoryWinner(scope) {
        const categorySelect = scope.querySelector('select[name*="[category]"]');
        const winnerSelect = scope.querySelector('select[name*="[winner]"]');
        const homeInput = scope.querySelector('input[name*="[homeTeam]"]');
        const awayInput = scope.querySelector('input[name*="[awayTeam]"]');

        if (!categorySelect || !winnerSelect) {
            return;
        }

        const refresh = () => loadWinnerOptions(categorySelect.value, winnerSelect, homeInput, awayInput);

        if (categorySelect.value) {
            refresh();
        }

        categorySelect.addEventListener('change', refresh);
        homeInput?.addEventListener('input', refresh);
        awayInput?.addEventListener('input', refresh);

        winnerSelect.addEventListener('change', () => {
            const winnerLabelInput = scope.querySelector('input[name*="[winnerLabel]"]');
            if (winnerLabelInput) {
                winnerLabelInput.value = winnerSelect.selectedOptions[0]?.text || '';
            }
        });
    }

    // ====== Options système selon le nombre de sélections ======
    function updateSystemOptions() {
        if (!systemSelect) {
            return;
        }

        const matchCount = container ? container.querySelectorAll('.selection-block').length : 0;
        systemSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';

        if (matchCount < 3) {
            placeholder.textContent = 'Ajoute au moins 3 sélections…';
            systemSelect.appendChild(placeholder);
            return;
        }

        if (matchCount > 8) {
            placeholder.textContent = 'Limité à 8 sélections.';
            systemSelect.appendChild(placeholder);
            return;
        }

        placeholder.textContent = 'Sélectionnez un système';
        systemSelect.appendChild(placeholder);

        fetch(`${config.systemOptionsUrl}?matches=${matchCount}`)
            .then((res) => res.json())
            .then((data) => {
                [...(data.standard || []), ...(data.special || [])].forEach((item) => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = `${item.name} — ${item.description}`;
                    systemSelect.appendChild(opt);
                });
                calculateSystemPreview();
            })
            .catch(() => {});
    }

    // ====== Aperçu cote/profit d'un système — le serveur recalcule le vrai résultat à l'enregistrement ======
    const NAMED_SYSTEM_LEGS = {
        'Trixie': [2, 3],
        'Patent': [1, 2, 3],
        'Yankee': [2, 3, 4],
        'Lucky 15': [1, 2, 3, 4],
        'Canadian (Super Yankee)': [2, 3, 4, 5],
        'Lucky 31': [1, 2, 3, 4, 5],
        'Heinz': [2, 3, 4, 5, 6],
        'Lucky 63': [1, 2, 3, 4, 5, 6],
        'Super Heinz': [2, 3, 4, 5, 6, 7],
        'Goliath': [2, 3, 4, 5, 6, 7, 8],
    };

    function systemLegSizes(label) {
        const m = label.match(/^(\d+)\/(\d+)$/);
        if (m) return [parseInt(m[1], 10)];
        return NAMED_SYSTEM_LEGS[label] || [];
    }

    function combinationsOfIndexes(n, k) {
        const result = [];
        const combo = [];
        const helper = (start) => {
            if (combo.length === k) {
                result.push([...combo]);
                return;
            }
            for (let i = start; i < n; i++) {
                combo.push(i);
                helper(i + 1);
                combo.pop();
            }
        };
        helper(0);
        return result;
    }

    function calculateSystemPreview() {
        if (!systemSelect || !isSysteme() || !container) {
            return;
        }

        const selectedOption = systemSelect.selectedOptions[0];
        const stake = parseFloat(stakeField?.value) || 0;

        if (!selectedOption || !selectedOption.value) {
            if (oddsField) oddsField.value = '1.00';
            displayResult(stake, 0);
            return;
        }

        const systemLabel = selectedOption.textContent.split('—')[0].trim();
        const legSizes = systemLegSizes(systemLabel);

        const odds = Array.from(container.querySelectorAll('input[name*="[odds]"]'))
            .map((input) => parseFloat(input.value))
            .filter((v) => !isNaN(v) && v > 0);

        if (odds.length === 0 || legSizes.length === 0) {
            if (oddsField) oddsField.value = '1.00';
            displayResult(stake, 0);
            return;
        }

        let totalCombos = 0;
        let totalCote = 0;

        legSizes.forEach((k) => {
            combinationsOfIndexes(odds.length, k).forEach((combo) => {
                totalCombos++;
                const product = combo.reduce((acc, i) => acc * odds[i], 1);
                totalCote += roundCote(product);
            });
        });

        if (totalCombos === 0) {
            return;
        }

        const avgOdds = roundCote(totalCote / totalCombos);
        if (oddsField) oddsField.value = avgOdds.toFixed(2);

        if (stake > 0) {
            const unitStake = stake / totalCombos;
            const potentialGain = roundCote(unitStake * totalCote);
            displayResult(stake, roundCote(potentialGain - stake));
        } else {
            displayResult(0, 0);
        }
    }

    // ====== Cote combinée (boost + assurance) — aperçu, le serveur recalcule à l'enregistrement ======
    function boostOdd(odd, count) {
        let boostPercent;
        if (odd < 1.5) boostPercent = 0.003;
        else if (odd < 2) boostPercent = 0.0062;
        else if (odd < 3) boostPercent = 0.008;
        else if (odd < 5) boostPercent = 0.018;
        else if (odd < 7) boostPercent = 0.020;
        else if (odd < 10) boostPercent = 0.025;
        else boostPercent = 0.045;

        const matchBonus = count > 2 ? Math.min((count - 2) * 0.0015, 0.006) : 0;

        return odd * (1 + boostPercent + matchBonus);
    }

    function insuranceReduction(count) {
        const table = { 2: 0.15, 3: 0.25, 4: 0.363, 5: 0.2405, 6: 0.1705, 7: 0.11, 8: 0.07305 };
        return table[count] ?? 0.0465;
    }

    function calculateCombinedPreview() {
        if (!container || !isCombine()) {
            return;
        }

        const selections = Array.from(container.querySelectorAll('.selection-block'));
        const odds = selections
            .map((block) => parseFloat(block.querySelector('input[name*="[odds]"]')?.value))
            .filter((v) => !isNaN(v) && v > 0);

        if (odds.length === 0) {
            if (oddsField) oddsField.value = '1.00';
            updateProfitPreview(1);
            return;
        }

        const boosted = odds.map((o) => roundCote(boostCheckbox?.checked && odds.length >= 2 ? boostOdd(o, odds.length) : o));
        let total = roundCote(boosted.reduce((acc, v) => acc * v, 1));

        if (insuredCheckbox?.checked) {
            total = roundCote(total * (1 - insuranceReduction(odds.length)));
        }

        if (oddsField) oddsField.value = total.toFixed(2);
        updateProfitPreview(total);
    }

    function updateProfitPreview(odds) {
        const stake = parseFloat(stakeField?.value) || 0;
        const o = odds ?? (parseFloat(oddsField?.value) || 1);
        displayResult(stake, roundCote(stake * o - stake));
    }

    // ====== Sections visibles selon le type de pari ======
    function toggleFields() {
        const combine = isCombine();
        const systeme = isSysteme();

        if (simpleFields) simpleFields.style.display = (combine || systeme) ? 'none' : 'block';
        if (selectionsWrapper) selectionsWrapper.style.display = (combine || systeme) ? 'block' : 'none';
        if (systemFields) systemFields.style.display = systeme ? 'block' : 'none';
        if (oddsField) oddsField.readOnly = combine || systeme;

        if (boostLabel) boostLabel.style.display = combine ? 'flex' : 'none';
        if (insuredLabel) insuredLabel.style.display = combine ? 'flex' : 'none';

        if (!combine) {
            if (boostCheckbox) boostCheckbox.checked = false;
            if (insuredCheckbox) insuredCheckbox.checked = false;
            if (boostedField) boostedField.value = '0';
            if (insuredField) insuredField.value = '0';
        }

        updateSwitchAvailability();

        if (systeme) {
            updateSystemOptions();
        } else if (combine) {
            calculateCombinedPreview();
        } else {
            updateProfitPreview();
        }
    }

    function updateSwitchAvailability() {
        if (!boostCheckbox || !insuredCheckbox) {
            return;
        }

        const combine = isCombine();
        const count = container ? container.querySelectorAll('.selection-block').length : 0;

        const boostAllowed = combine && count >= 2;
        const insuredAllowed = combine && count >= 3;

        boostCheckbox.disabled = !boostAllowed;
        boostCheckbox.closest('.bet-switch')?.classList.toggle('is-disabled', !boostAllowed);
        if (!boostAllowed) boostCheckbox.checked = false;

        insuredCheckbox.disabled = !insuredAllowed;
        insuredCheckbox.closest('.bet-switch')?.classList.toggle('is-disabled', !insuredAllowed);
        if (!insuredAllowed) insuredCheckbox.checked = false;

        if (insuredCheckbox.checked && boostCheckbox) {
            boostCheckbox.disabled = true;
            boostCheckbox.checked = false;
            boostCheckbox.closest('.bet-switch')?.classList.add('is-disabled');
        }
    }

    // ====== Blocs de sélection (combiné/système) ======
    function updateMatchTitles() {
        if (!container) return;

        container.querySelectorAll('.selection-block').forEach((block, i) => {
            const label = block.querySelector('.match-label');
            if (label) label.textContent = `Match ${i + 1}`;
        });
    }

    function bindSelectionBlock(block) {
        block.querySelectorAll('input[name*="[odds]"]').forEach((input) => {
            input.addEventListener('input', () => {
                calculateCombinedPreview();
                if (isSysteme()) calculateSystemPreview();
            });
        });

        bindCategoryWinner(block);

        const title = block.querySelector('.match-title');
        const trash = block.querySelector('.closed-trash');

        if (title) {
            title.addEventListener('click', () => {
                const open = block.dataset.open === 'true';
                block.dataset.open = open ? 'false' : 'true';
                const chevron = block.querySelector('.chevron');
                if (chevron) {
                    chevron.classList.toggle('fa-chevron-up', !open);
                    chevron.classList.toggle('fa-chevron-down', open);
                }
            });
        }

        if (trash) {
            trash.addEventListener('click', (e) => {
                e.stopPropagation();
                block.remove();
                updateMatchTitles();
                updateSwitchAvailability();
                calculateCombinedPreview();
                if (isSysteme()) updateSystemOptions();
            });
        }
    }

    function addSelection() {
        if (!container || !container.dataset.prototype) return;

        const html = container.dataset.prototype.replace(/__name__/g, index);
        index++;

        const div = document.createElement('div');
        div.className = 'selection-block';
        div.dataset.open = 'true';
        div.innerHTML = `
            <div class="match-title">
                <div class="match-actions">
                    <i class="fa-solid fa-chevron-up chevron"></i>
                    <span class="match-label">Match</span>
                </div>
                <span class="closed-trash"><i class="fa-solid fa-trash-alt"></i></span>
            </div>
            <div class="match-content">${html}</div>
        `;

        container.appendChild(div);
        updateMatchTitles();
        bindSelectionBlock(div);
        updateSwitchAvailability();
        calculateCombinedPreview();
        if (isSysteme()) updateSystemOptions();
    }

    if (container) {
        container.querySelectorAll('.selection-block').forEach(bindSelectionBlock);
    }

    document.querySelector('.add-selection')?.addEventListener('click', addSelection);

    // ====== Champ principal catégorie/résultat (paris simple/live) ======
    bindCategoryWinner(document);

    // ====== Écouteurs ======
    if (betTypeSelect) {
        betTypeSelect.addEventListener('change', toggleFields);
        toggleFields();
    }

    if (statusField && cashoutField) {
        const toggleCashout = () => {
            cashoutField.style.display = statusField.value === 'cashout' ? 'flex' : 'none';
        };
        statusField.addEventListener('change', toggleCashout);
        toggleCashout();
    }

    if (stakeField) {
        stakeField.addEventListener('input', () => {
            if (isCombine()) calculateCombinedPreview();
            else if (isSysteme()) calculateSystemPreview();
            else updateProfitPreview();
        });
    }

    if (oddsField) {
        oddsField.addEventListener('input', () => {
            if (!isCombine()) updateProfitPreview();
        });
    }

    if (systemSelect) {
        systemSelect.addEventListener('change', calculateSystemPreview);
    }

    if (boostCheckbox) {
        boostCheckbox.addEventListener('change', () => {
            if (boostedField) boostedField.value = boostCheckbox.checked ? '1' : '0';
            updateSwitchAvailability();
            calculateCombinedPreview();
        });
    }

    if (insuredCheckbox) {
        insuredCheckbox.addEventListener('change', () => {
            if (insuredField) insuredField.value = insuredCheckbox.checked ? '1' : '0';
            updateSwitchAvailability();
            calculateCombinedPreview();
        });
    }

    if (freebetCheckbox) {
        freebetCheckbox.addEventListener('change', () => {
            if (freebetField) freebetField.value = freebetCheckbox.checked ? '1' : '0';
        });
    }
}
