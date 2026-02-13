(function () {
    'use strict';

    function initCarousels() {
        var viewports = document.querySelectorAll('.carousel-viewport');
        viewports.forEach(function (viewport) {
            var track = viewport.querySelector('.carousel-track');
            var slides = viewport ? viewport.querySelectorAll('.carousel-slide') : [];
            var controls = viewport.closest('.favorites-carousel');
            if (!controls) return;

            var prevBtn = controls.querySelector('.carousel-prev');
            var nextBtn = controls.querySelector('.carousel-next');
            var indexEl = controls.querySelector('.carousel-index');

            if (slides.length === 0) return;

            var total = slides.length;

            function getCurrentIndex() {
                var w = viewport.offsetWidth;
                var scroll = viewport.scrollLeft;
                return Math.round(scroll / w) || 0;
            }

            function updateIndex() {
                var idx = Math.min(getCurrentIndex(), total - 1);
                if (indexEl) indexEl.textContent = (idx + 1) + ' / ' + total;
                if (prevBtn) prevBtn.disabled = idx <= 0;
                if (nextBtn) nextBtn.disabled = idx >= total - 1;
            }

            function scrollTo(idx) {
                var w = viewport.offsetWidth;
                viewport.scrollTo({ left: idx * w, behavior: 'smooth' });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    scrollTo(Math.max(0, getCurrentIndex() - 1));
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    scrollTo(Math.min(total - 1, getCurrentIndex() + 1));
                });
            }

            viewport.addEventListener('scroll', updateIndex);
            updateIndex();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarousels);
    } else {
        initCarousels();
    }
})();
