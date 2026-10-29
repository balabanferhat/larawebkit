/*
 * larawebkit theme script, shared by every theme through the parent chain. Small on purpose
 * (no framework): everything works without it, this only adds toggles and keyboard support.
 * Mobile menu, dropdowns (disclosure pattern), dark-mode switch, announcement dismissal,
 * gallery lightbox and click-to-load video embeds.
 */
(function () {
    'use strict';
    var d = document;
    var root = d.documentElement;

    function closeSubmenus(except) {
        d.querySelectorAll('.lwk-sub-toggle[aria-expanded="true"]').forEach(function (b) {
            if (b !== except) b.setAttribute('aria-expanded', 'false');
        });
    }

    function setNav(open, focusToggle) {
        var toggle = d.querySelector('[data-nav-toggle]');
        if (!toggle) return;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        root.classList.toggle('lwk-nav-open', open);
        if (open) {
            var first = d.querySelector('#lwk-header-panel a, #lwk-header-panel button, #lwk-header-panel input');
            if (first) first.focus();
        } else if (focusToggle) {
            toggle.focus();
        }
    }

    d.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target : e.target.parentElement;
        if (!t) return;

        var sub = t.closest('.lwk-sub-toggle');
        if (sub) {
            var open = sub.getAttribute('aria-expanded') !== 'true';
            closeSubmenus(sub);
            sub.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }
        if (!t.closest('.lwk-menu-item.has-children')) closeSubmenus();

        if (t.closest('[data-nav-toggle]')) {
            setNav(!root.classList.contains('lwk-nav-open'));
            return;
        }

        if (t.closest('[data-theme-toggle]')) {
            var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            try { localStorage.setItem('lwk-theme', next); } catch (err) {}
            return;
        }

        var dismiss = t.closest('[data-dismiss-announcement]');
        if (dismiss) {
            var bar = dismiss.closest('[data-announcement]');
            bar.hidden = true;
            try { localStorage.setItem('lwk-ann', bar.dataset.announcement); } catch (err) {}
            return;
        }

        var video = t.closest('[data-video-src]');
        if (video) {
            e.preventDefault();
            var frame = d.createElement('iframe');
            frame.src = video.dataset.videoSrc;
            frame.title = video.dataset.videoTitle || '';
            frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            frame.allowFullscreen = true;
            frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            video.replaceWith(frame);
            frame.focus();
            return;
        }

        var zoom = t.closest('[data-lightbox]');
        if (zoom && window.HTMLDialogElement) {
            e.preventDefault();
            openLightbox(zoom);
        }
    });

    d.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var openSub = d.querySelector('.lwk-sub-toggle[aria-expanded="true"]');
        if (openSub) { closeSubmenus(); openSub.focus(); return; }
        if (root.classList.contains('lwk-nav-open')) setNav(false, true);
    });

    // Close a dropdown when keyboard focus leaves it.
    d.addEventListener('focusout', function (e) {
        var item = e.target.closest && e.target.closest('.lwk-menu-item.has-children');
        if (item && !item.contains(e.relatedTarget)) {
            var b = item.querySelector('.lwk-sub-toggle');
            if (b) b.setAttribute('aria-expanded', 'false');
        }
    });

    // Leaving the mobile layout closes the panel.
    if (window.matchMedia) {
        var wide = matchMedia('(min-width: 60em)');
        var onChange = function () { if (wide.matches) setNav(false); };
        wide.addEventListener ? wide.addEventListener('change', onChange) : wide.addListener(onChange);
    }

    /* Gallery lightbox: a native <dialog>, so focus trapping and Escape come for free. */
    var dialog;
    function openLightbox(link) {
        var gallery = link.closest('[data-gallery]');
        var links = gallery ? Array.prototype.slice.call(gallery.querySelectorAll('[data-lightbox]')) : [link];
        var index = links.indexOf(link);
        if (!dialog) {
            dialog = d.createElement('dialog');
            dialog.className = 'lwk-lightbox';
            dialog.innerHTML = '<figure><img alt=""><figcaption></figcaption></figure>'
                + '<button type="button" class="lwk-lightbox-prev" aria-label="' + (root.dataset.lPrev || 'Previous') + '">‹</button>'
                + '<button type="button" class="lwk-lightbox-next" aria-label="' + (root.dataset.lNext || 'Next') + '">›</button>'
                + '<button type="button" class="lwk-lightbox-close" aria-label="' + (root.dataset.lClose || 'Close') + '">×</button>';
            d.body.appendChild(dialog);
            dialog.addEventListener('click', function (ev) {
                if (ev.target === dialog || ev.target.closest('.lwk-lightbox-close')) dialog.close();
                if (ev.target.closest('.lwk-lightbox-prev')) show(dialog._index - 1);
                if (ev.target.closest('.lwk-lightbox-next')) show(dialog._index + 1);
            });
            dialog.addEventListener('keydown', function (ev) {
                if (ev.key === 'ArrowLeft') show(dialog._index - 1);
                if (ev.key === 'ArrowRight') show(dialog._index + 1);
            });
        }
        function show(i) {
            i = (i + links.length) % links.length;
            dialog._index = i;
            var img = dialog.querySelector('img');
            img.src = links[i].href;
            img.alt = links[i].dataset.alt || '';
            dialog.querySelector('figcaption').textContent = links[i].dataset.caption || '';
            dialog.querySelectorAll('.lwk-lightbox-prev, .lwk-lightbox-next').forEach(function (b) { b.hidden = links.length < 2; });
        }
        show(index);
        dialog.showModal();
    }
})();
