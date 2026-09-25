/**
 * BEIT Frontpage — filtrage du catalogue côté client.
 *
 * Filtre/trie les cartes de cours selon : recherche, catégories,
 * disponibilité (inscription), et option de tri. Aucune dépendance externe.
 */
(function () {
    'use strict';

    function init() {
        var grid = document.getElementById('beit-grid');
        if (!grid) {
            return;
        }

        var searchInput = document.getElementById('beit-search');
        var sortSelect = document.getElementById('beit-sort');
        var enrolOnly = document.getElementById('beit-enrol-only');
        var resetBtn = document.getElementById('beit-reset');
        var countEl = document.getElementById('beit-count');
        var noResults = document.getElementById('beit-no-results');
        var toggleBtn = document.getElementById('beit-filters-toggle');
        var filters = document.getElementById('beit-filters');
        var catChecks = Array.prototype.slice.call(
            document.querySelectorAll('.beit-cat-check')
        );
        var levelChecks = Array.prototype.slice.call(
            document.querySelectorAll('.beit-level-check')
        );

        var cards = Array.prototype.slice.call(
            grid.querySelectorAll('.beit-course-card')
        );

        // Mémoriser l'ordre d'origine pour le tri "par défaut".
        cards.forEach(function (card, i) {
            card.dataset.order = i;
        });

        var countSingular = countEl ? countEl.getAttribute('data-singular') : '';

        function getCountText(n) {
            var word = (n === 1)
                ? (countEl.getAttribute('data-singular') || 'cours trouvé')
                : (countEl.getAttribute('data-plural') || 'cours trouvés');
            return n + ' ' + word;
        }

        function apply() {
            var term = (searchInput && searchInput.value || '')
                .toLowerCase().trim();
            var selectedCats = catChecks
                .filter(function (c) { return c.checked; })
                .map(function (c) { return c.value; });
            var selectedLevels = levelChecks
                .filter(function (c) { return c.checked; })
                .map(function (c) { return c.value; });
            var enrolFilter = enrolOnly && enrolOnly.checked;

            var visible = 0;
            cards.forEach(function (card) {
                var matchSearch = !term ||
                    (card.dataset.search || '').indexOf(term) !== -1;
                var matchCat = selectedCats.length === 0 ||
                    selectedCats.indexOf(card.dataset.category) !== -1;
                var matchLevel = selectedLevels.length === 0 ||
                    selectedLevels.indexOf(card.dataset.level) !== -1;
                var matchEnrol = !enrolFilter || card.dataset.canenrol === '1';

                var show = matchSearch && matchCat && matchLevel && matchEnrol;
                card.style.display = show ? '' : 'none';
                if (show) {
                    visible++;
                }
            });

            if (countEl) {
                countEl.textContent = getCountText(visible);
            }
            if (noResults) {
                noResults.hidden = visible !== 0;
            }

            sortCards();
        }

        function sortCards() {
            if (!sortSelect) {
                return;
            }
            var mode = sortSelect.value;
            var sorted = cards.slice();

            sorted.sort(function (a, b) {
                switch (mode) {
                    case 'az':
                        return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                    case 'za':
                        return (b.dataset.name || '').localeCompare(a.dataset.name || '');
                    case 'recent':
                        return (parseInt(b.dataset.timecreated, 10) || 0) -
                               (parseInt(a.dataset.timecreated, 10) || 0);
                    case 'oldest':
                        return (parseInt(a.dataset.timecreated, 10) || 0) -
                               (parseInt(b.dataset.timecreated, 10) || 0);
                    default:
                        return (parseInt(a.dataset.order, 10) || 0) -
                               (parseInt(b.dataset.order, 10) || 0);
                }
            });

            sorted.forEach(function (card) {
                grid.appendChild(card);
            });
        }

        // Écouteurs.
        if (searchInput) {
            searchInput.addEventListener('input', apply);
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', apply);
        }
        if (enrolOnly) {
            enrolOnly.addEventListener('change', apply);
        }
        catChecks.forEach(function (c) {
            c.addEventListener('change', apply);
        });
        levelChecks.forEach(function (c) {
            c.addEventListener('change', apply);
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (searchInput) { searchInput.value = ''; }
                if (sortSelect) { sortSelect.value = 'default'; }
                if (enrolOnly) { enrolOnly.checked = false; }
                catChecks.forEach(function (c) { c.checked = false; });
                levelChecks.forEach(function (c) { c.checked = false; });
                apply();
            });
        }

        // Bouton d'affichage des filtres sur mobile.
        if (toggleBtn && filters) {
            toggleBtn.addEventListener('click', function () {
                filters.classList.toggle('beit-filters--open');
            });
        }

        var slider = document.querySelector('.beit-slideshow');
        if (slider) {
            var slides = Array.prototype.slice.call(slider.querySelectorAll('.beit-slide'));
            var position = slider.querySelector('.beit-slide-position');
            var index = 0;
            var timer;
            var showSlide = function (next) {
                index = (next + slides.length) % slides.length;
                slides.forEach(function (slide, i) {
                    slide.hidden = i !== index;
                    slide.classList.toggle('is-active', i === index);
                });
                if (position) { position.textContent = (index + 1) + ' / ' + slides.length; }
            };
            var restart = function () {
                window.clearInterval(timer);
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    timer = window.setInterval(function () { showSlide(index + 1); }, 6500);
                }
            };
            slider.querySelectorAll('[data-slide]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showSlide(index + (button.dataset.slide === 'next' ? 1 : -1));
                    restart();
                });
            });
            slider.addEventListener('mouseenter', function () { window.clearInterval(timer); });
            slider.addEventListener('mouseleave', restart);
            slider.addEventListener('focusin', function () { window.clearInterval(timer); });
            slider.addEventListener('focusout', restart);
            restart();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
