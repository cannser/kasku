/*
 * app.js - JavaScript tanpa inline script (sesuai Content-Security-Policy).
 * 1) Dialog konfirmasi:      <form data-confirm="Pesan">
 * 2) Pesan (toast):          hilang otomatis, bisa ditutup
 * 3) Tampil/sembunyi password dan daftar syarat password (halaman daftar / ganti password)
 * 4) Form transaksi:         pratinjau nominal dan tombol tanggal cepat
 * 5) Laporan:                pilih bulan langsung memuat ulang (form[data-autosubmit])
 * 6) Menu "Lainnya" (HP):    tutup saat klik di luar atau tekan Esc
 * 7) Grafik pengeluaran:     <canvas id="expenseChart" data-labels data-values>
 * Validasi sebenarnya tetap dilakukan di server; script ini hanya membantu kenyamanan.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    /* ---------- 1) Dialog konfirmasi ---------- */
    function initConfirm() {
        var dialog = document.getElementById('confirmDialog');
        var text = document.getElementById('confirmText');
        var pending = null;

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                var message = form.getAttribute('data-confirm');
                if (!dialog || typeof dialog.showModal !== 'function') {
                    if (!window.confirm(message)) { ev.preventDefault(); }
                    return;
                }
                ev.preventDefault();
                pending = form;
                text.textContent = message;
                dialog.returnValue = '';
                dialog.showModal();
            });
        });

        if (dialog) {
            dialog.addEventListener('close', function () {
                if (dialog.returnValue === 'ok' && pending) {
                    // Pakai prototype agar tidak tertimpa field bernama "submit"
                    HTMLFormElement.prototype.submit.call(pending);
                }
                pending = null;
            });
        }
    }

    /* ---------- 2) Pesan (toast) ---------- */
    function initToasts() {
        document.querySelectorAll('.toast-kk').forEach(function (toast) {
            function close() {
                toast.classList.add('is-leaving');
                window.setTimeout(function () { toast.remove(); }, 300);
            }
            var btn = toast.querySelector('[data-toast-close]');
            if (btn) { btn.addEventListener('click', close); }
            // Pesan galat dibiarkan lebih lama agar sempat dibaca
            window.setTimeout(close, toast.classList.contains('danger') ? 9000 : 5000);
        });
    }

    /* ---------- 3) Password ---------- */
    function initPasswords() {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle-password'));
                var wrap = btn.closest('.pw');
                if (!input || !wrap) { return; }
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                wrap.classList.toggle('is-shown', show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
        });

        document.querySelectorAll('[data-pw-rules]').forEach(function (list) {
            var input = document.getElementById(list.getAttribute('data-pw-rules'));
            if (!input) { return; }
            function check() {
                var v = input.value;
                var state = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), digit: /[0-9]/.test(v) };
                list.querySelectorAll('li').forEach(function (li) {
                    li.classList.toggle('ok', !!state[li.getAttribute('data-rule')]);
                });
            }
            input.addEventListener('input', check);
            check();
        });
    }

    /* ---------- 4) Form transaksi ---------- */
    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function initTransactionForm() {
        var amount = document.getElementById('amount');
        var preview = document.getElementById('amountPreview');
        if (amount && preview) {
            var hint = preview.textContent;
            function update() {
                var v = amount.value.trim();
                preview.classList.remove('is-bad');
                if (v === '') { preview.textContent = hint; return; }
                if (/^\d+(\.\d{1,2})?$/.test(v)) {
                    preview.textContent = 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                } else {
                    preview.textContent = 'Gunakan angka saja, tanpa titik ribuan. Desimal pakai titik.';
                    preview.classList.add('is-bad');
                }
            }
            amount.addEventListener('input', update);
            update();
        }

        document.querySelectorAll('[data-date-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-date-target'));
                if (!input) { return; }
                var d = new Date();
                d.setDate(d.getDate() + parseInt(btn.getAttribute('data-date-offset') || '0', 10));
                input.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            });
        });
    }

    /* ---------- 5) Pilih bulan di laporan ---------- */
    function initAutosubmit() {
        document.querySelectorAll('form [data-autosubmit]').forEach(function (field) {
            field.addEventListener('change', function () {
                if (field.value) { field.form.submit(); }
            });
        });
    }

    /* ---------- 6) Menu "Lainnya" ---------- */
    function initMore() {
        var more = document.querySelector('details.more');
        if (!more) { return; }
        document.addEventListener('click', function (ev) {
            if (more.open && !more.contains(ev.target)) { more.open = false; }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && more.open) { more.open = false; }
        });
    }

    /* ---------- 7) Grafik pengeluaran ---------- */
    function initChart() {
        var canvas = document.getElementById('expenseChart');
        if (!canvas || !window.Chart) { return; }
        var labels = [];
        var values = [];
        try {
            labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
            values = JSON.parse(canvas.getAttribute('data-values') || '[]');
        } catch (e) {
            labels = [];
            values = [];
        }
        // Urutan warna sama dengan kelas .dot-0 sampai .dot-7 di style.css
        var palette = ['#2b4fd8', '#f5a524', '#12a47a', '#d8404e', '#7c5ce0', '#1fa6c9', '#d9528f', '#7a849c'];
        new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map(function (_, i) { return palette[i % palette.length]; }),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.label + ': Rp ' + Number(ctx.parsed).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    ready(function () {
        initConfirm();
        initToasts();
        initPasswords();
        initTransactionForm();
        initAutosubmit();
        initMore();
        initChart();
    });
})();
