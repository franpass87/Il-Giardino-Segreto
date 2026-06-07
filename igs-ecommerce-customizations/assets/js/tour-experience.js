/**
 * IGS Tour Experience — interazioni pagina tour (vanilla JS, zero dipendenze).
 * Scroll-reveal, nav interna sticky con scroll-spy, lightbox galleria,
 * accordion info, barra prenotazione sticky.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function throttle(fn, wait) {
        var last = 0, scheduled = null;
        return function () {
            var now = Date.now();
            if (now - last >= wait) { last = now; fn(); }
            else if (!scheduled) {
                scheduled = setTimeout(function () { scheduled = null; last = Date.now(); fn(); }, wait - (now - last));
            }
        };
    }

    ready(function () {
        if (!document.querySelector('.igs-tour-content') &&
            !document.querySelector('.custom-hero') &&
            !document.querySelector('.igs-editorial')) {
            return;
        }

        /* 1) Scroll-reveal -------------------------------------------------- */
        var revealEls = Array.prototype.slice.call(document.querySelectorAll('.igs-reveal'));
        if (revealEls.length) {
            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { e.target.classList.add('igs-in'); io.unobserve(e.target); }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
                revealEls.forEach(function (el) { io.observe(el); });
            } else {
                revealEls.forEach(function (el) { el.classList.add('igs-in'); });
            }
        }

        /* 1b) Rail sticky "scroll-then-stick" ------------------------------- */
        /* Se il rail è più alto del viewport (es. Azzorre: titolo lungo + foto +
           fatti + prezzo + CTA + nav), agganciarlo con top fisso taglierebbe il
           fondo (CTA/nav). Calcoliamo un top che, quando il rail eccede l'altezza
           disponibile, lo aggancia in BASSO: così in cima alla pagina si vede il
           titolo e scorrendo si raggiunge il fondo (CTA/nav). Se ci sta, top sotto
           l'header fisso. Solo desktop (su ≤1024 il rail è statico via CSS). */
        var railInner = document.querySelector('.igs-ed-rail-inner');
        if (railInner) {
            var RAIL_HEADER = 96, RAIL_GAP = 24;
            var adjustRail = function () {
                if (window.innerWidth <= 1024) { railInner.style.top = ''; return; }
                var railH = railInner.offsetHeight;
                var vh = window.innerHeight;
                railInner.style.top = (railH > vh - RAIL_HEADER)
                    ? (vh - railH - RAIL_GAP) + 'px'
                    : RAIL_HEADER + 'px';
            };
            adjustRail();
            window.addEventListener('resize', throttle(adjustRail, 150));
            window.addEventListener('load', adjustRail);
            if (document.fonts && document.fonts.ready) { document.fonts.ready.then(adjustRail); }
        }

        /* 2) Nav interna: smooth scroll + scroll-spy ------------------------ */
        var nav = document.querySelector('[data-igs-spy]') || document.querySelector('.igs-tour-nav');
        if (nav) {
            var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]'));
            var sections = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); }).filter(Boolean);
            links.forEach(function (a) {
                a.addEventListener('click', function (ev) {
                    var target = document.getElementById(a.getAttribute('href').slice(1));
                    if (target) {
                        ev.preventDefault();
                        var y = target.getBoundingClientRect().top + window.pageYOffset - 72;
                        window.scrollTo({ top: y, behavior: 'smooth' });
                    }
                });
            });
            var spy = function () {
                var pos = window.pageYOffset + 130;
                var cur = null;
                sections.forEach(function (s) { if (s.getBoundingClientRect().top + window.pageYOffset <= pos) { cur = s; } });
                links.forEach(function (a) {
                    a.classList.toggle('is-active', !!cur && a.getAttribute('href') === '#' + cur.id);
                });
            };
            window.addEventListener('scroll', throttle(spy, 120), { passive: true });
            window.addEventListener('resize', throttle(spy, 200));
            spy();
        }

        /* 3) Lightbox galleria --------------------------------------------- */
        var galleryItems = Array.prototype.slice.call(document.querySelectorAll('.igs-gallery-item'));
        if (galleryItems.length) {
            var urls = galleryItems.map(function (a) { return a.getAttribute('href'); });
            var current = 0;
            var lb = document.createElement('div');
            lb.className = 'igs-lb';
            lb.setAttribute('role', 'dialog');
            lb.setAttribute('aria-modal', 'true');
            lb.innerHTML =
                '<button class="igs-lb-btn igs-lb-close" aria-label="Chiudi">×</button>' +
                '<button class="igs-lb-btn igs-lb-prev" aria-label="Precedente">‹</button>' +
                '<figure class="igs-lb-stage"><img class="igs-lb-img" alt=""><figcaption class="igs-lb-count"></figcaption></figure>' +
                '<button class="igs-lb-btn igs-lb-next" aria-label="Successiva">›</button>';
            document.body.appendChild(lb);
            var lbImg = lb.querySelector('.igs-lb-img');
            var lbCount = lb.querySelector('.igs-lb-count');

            function show() {
                lbImg.src = urls[current];
                lbCount.textContent = (current + 1) + ' / ' + urls.length;
            }
            function open(i) { current = i; show(); lb.classList.add('is-open'); document.body.classList.add('igs-lb-lock'); }
            function close() { lb.classList.remove('is-open'); document.body.classList.remove('igs-lb-lock'); }
            function step(d) { current = (current + d + urls.length) % urls.length; show(); }

            galleryItems.forEach(function (a, i) {
                a.addEventListener('click', function (ev) { ev.preventDefault(); open(i); });
            });
            lb.querySelector('.igs-lb-close').addEventListener('click', close);
            lb.querySelector('.igs-lb-prev').addEventListener('click', function () { step(-1); });
            lb.querySelector('.igs-lb-next').addEventListener('click', function () { step(1); });
            lb.addEventListener('click', function (e) { if (e.target === lb) { close(); } });
            document.addEventListener('keydown', function (e) {
                if (!lb.classList.contains('is-open')) { return; }
                if (e.key === 'Escape') { close(); }
                else if (e.key === 'ArrowRight') { step(1); }
                else if (e.key === 'ArrowLeft') { step(-1); }
            });
        }

        /* 4) Accordion info ------------------------------------------------- */
        Array.prototype.slice.call(document.querySelectorAll('.igs-acc-item')).forEach(function (item, idx) {
            var head = item.querySelector('.igs-acc-head');
            var panel = item.querySelector('.igs-acc-panel');
            if (!head || !panel) { return; }
            if (idx === 0) { item.classList.add('is-open'); head.setAttribute('aria-expanded', 'true'); }
            head.addEventListener('click', function () {
                var open = item.classList.toggle('is-open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        /* 5) Prenotazione: i pulsanti [data-igs-open-modal] aprono il modal di
              BookingModal (riusa #gs-open-modal — un solo sistema di prenotazione). */
        Array.prototype.slice.call(document.querySelectorAll('[data-igs-open-modal]')).forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                var trigger = document.getElementById('gs-open-modal');
                if (trigger) { trigger.click(); }
            });
        });
    });
})();
