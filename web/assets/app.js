(() => {
    document.querySelectorAll('.needs-validation').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    const search = document.querySelector('[data-station-search]');
    const stationItems = document.querySelectorAll('[data-station-list] .station-item');
    if (search) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            stationItems.forEach((item) => {
                item.hidden = query !== '' && !item.dataset.search.includes(query);
            });
        });
    }

    document.querySelectorAll('[data-return-form]').forEach((form) => {
        const stationSelect = form.querySelector('[data-station-select]');
        const borneSelect = form.querySelector('[data-borne-select]');
        if (!stationSelect || !borneSelect) return;

        const updateBores = () => {
            const stationId = stationSelect.value;
            let firstVisible = null;
            [...borneSelect.options].forEach((option) => {
                if (!option.value) return;
                const visible = !stationId || option.dataset.station === stationId;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && firstVisible === null) firstVisible = option;
            });
            const selected = borneSelect.options[borneSelect.selectedIndex];
            if (selected && selected.value && selected.disabled) {
                borneSelect.value = firstVisible ? firstVisible.value : '';
            }
        };

        stationSelect.addEventListener('change', updateBores);
        borneSelect.addEventListener('change', () => {
            const option = borneSelect.options[borneSelect.selectedIndex];
            if (option && option.dataset.station) stationSelect.value = option.dataset.station;
        });
        updateBores();
    });
})();
