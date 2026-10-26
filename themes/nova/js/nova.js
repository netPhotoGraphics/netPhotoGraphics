/* Nova theme – vanilla JS (no framework needed except Fancybox's jQuery) */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var $ = function (s, c) { return (c || document).querySelector(s); };

        /* mobile menu */
        var toggle = $('.nav-toggle'), nav = $('#site-nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                var open = nav.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        /* dark mode toggle (choice is remembered in this browser) */
        var themeBtn = $('.theme-toggle');
        if (themeBtn) {
            var root = document.documentElement;
            var current = function () {
                return root.getAttribute('data-theme') ||
                    (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            };
            themeBtn.setAttribute('aria-pressed', current() === 'dark' ? 'true' : 'false');
            themeBtn.addEventListener('click', function () {
                var next = current() === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                themeBtn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
                try { localStorage.setItem('nova-theme', next); } catch (e) {}
            });
            /* follow the system while the visitor has not chosen */
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (ev) {
                    var stored = null;
                    try { stored = localStorage.getItem('nova-theme'); } catch (e) {}
                    if (!stored) {
                        root.setAttribute('data-theme', ev.matches ? 'dark' : 'light');
                    }
                });
            }
        }

        /* dialogs (login, EXIF) */
        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-dialog-open]');
            if (opener) {
                var d = document.getElementById(opener.getAttribute('data-dialog-open'));
                if (d && typeof d.showModal === 'function') {
                    e.preventDefault();
                    d.showModal();
                }
                return;
            }
            var closer = e.target.closest('[data-dialog-close]');
            if (closer) {
                var dlg = closer.closest('dialog');
                if (dlg) { dlg.close(); }
                return;
            }
            if (e.target.tagName === 'DIALOG') { /* click on backdrop */
                e.target.close();
            }
        });

        /* failed login -> reopen the dialog */
        var login = document.getElementById('login-dialog');
        if (login && login.querySelector('.alert, .errorbox') && typeof login.showModal === 'function') {
            login.showModal();
        }

        /* favorites: label the optional list-name field */
        document.querySelectorAll('.favorites[data-instance-placeholder]').forEach(function (box) {
            var ph = box.getAttribute('data-instance-placeholder');
            box.querySelectorAll('input.favorite_instance').forEach(function (input) {
                input.placeholder = ph;
                input.setAttribute('aria-label', ph);
            });
        });

        /* scroll to top */
        var top = $('.scroll-top');
        if (top) {
            var onScroll = function () { top.hidden = window.scrollY < 600; };
            window.addEventListener('scroll', onScroll, {passive: true});
            top.addEventListener('click', function () { window.scrollTo({top: 0, behavior: 'smooth'}); });
            onScroll();
        }

        /* keyboard navigation: ← → on image and news article pages */
        var prev = $('a[rel="prev"]'), next = $('a[rel="next"]');
        if (prev || next) {
            document.addEventListener('keydown', function (e) {
                if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
                if (document.querySelector('.fancybox-is-open, dialog[open]')) { return; }
                var t = e.target;
                if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) { return; }
                if (e.key === 'ArrowRight' && next) { window.location.href = next.href; }
                if (e.key === 'ArrowLeft' && prev) { window.location.href = prev.href; }
            });
        }
    });
})();
