(function ($) {
    'use strict';

    function hideLoaderNow() {
        const loader = document.getElementById('appLoader');
        if (loader) {
            loader.classList.add('hidden');
        }
    }

    function animateCounters() {
        $('.counter').each(function () {
            const $el = $(this);
            const target = Number($el.data('target') || 0);
            const duration = 1200;
            const stepTime = 20;
            const steps = Math.max(1, Math.floor(duration / stepTime));
            const increment = target / steps;
            let value = 0;

            const timer = setInterval(function () {
                value += increment;
                if (value >= target) {
                    value = target;
                    clearInterval(timer);
                }
                $el.text(Math.floor(value).toLocaleString());
            }, stepTime);
        });
    }

    function animateProgressBars() {
        $('.progress-bar').each(function () {
            const width = $(this).data('width') || 0;
            setTimeout(() => {
                $(this).css('width', width + '%');
            }, 120);
        });
    }

    function setupRevealAnimation() {
        const elements = document.querySelectorAll('.reveal');
        if (!elements.length) {
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.14 });

        elements.forEach((el) => observer.observe(el));
    }

    function setupSidebarToggle() {
        $('#sidebarToggle').on('click', function () {
            $('body').toggleClass('sidebar-open');
        });

        $(document).on('click', function (event) {
            if ($(window).width() > 991) {
                return;
            }

            const isInside = $(event.target).closest('#sidebar, #sidebarToggle').length > 0;
            if (!isInside) {
                $('body').removeClass('sidebar-open');
            }
        });
    }

    function setupModalHelpers() {
        window.openModal = function (modalId) {
            $('#' + modalId).addClass('open');
            $('body').addClass('modal-open');
        };

        window.closeModal = function (modalId) {
            $('#' + modalId).removeClass('open');
            if (!$('.modal-overlay.open').length) {
                $('body').removeClass('modal-open');
            }
        };

        $(document).on('click', '[data-modal-open]', function () {
            const id = $(this).data('modal-open');
            openModal(id);
        });

        $(document).on('click', '[data-modal-close]', function () {
            const id = $(this).data('modal-close');
            closeModal(id);
        });

        $(document).on('click', '.modal-overlay', function (event) {
            if (event.target === this) {
                $(this).removeClass('open');
                if (!$('.modal-overlay.open').length) {
                    $('body').removeClass('modal-open');
                }
            }
        });
    }

    window.showToast = function (icon, title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-start',
                icon: icon,
                title: title,
                showConfirmButton: false,
                timer: 2600,
                timerProgressBar: true,
            });
            return;
        }

        alert(title);
    };

    window.confirmAction = function (title, text) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(confirm(text || title));
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0f7ea5',
        }).then((result) => result.isConfirmed);
    };

    function setupAjaxDefaults() {
        const token = $('meta[name="csrf-token"]').attr('content') || '';
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
    }

    function setupLoader() {
        const hideWithDelay = () => {
            setTimeout(hideLoaderNow, 120);
        };

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            hideWithDelay();
        } else {
            document.addEventListener('DOMContentLoaded', hideWithDelay, { once: true });
        }

        window.addEventListener('load', hideWithDelay, { once: true });

        // Absolute fallback in case load events are delayed/missed.
        setTimeout(hideLoaderNow, 2500);
    }

    setupLoader();

    $(function () {
        setupAjaxDefaults();
        setupSidebarToggle();
        setupModalHelpers();
        animateCounters();
        animateProgressBars();
        setupRevealAnimation();
    });
})(jQuery);
