// ===== SPM Track =====

const akar = document.documentElement;

// 1. Ganti tema gelap/terang, pilihan disimpan di browser
document.querySelector('[data-ganti-tema]')?.addEventListener('click', () => {
    const baru = akar.dataset.tema === 'terang' ? 'gelap' : 'terang';
    akar.dataset.tema = baru;
    try { localStorage.setItem('tema', baru); } catch (e) { /* abaikan kalau penyimpanan diblokir */ }
});

// 2. Jam hidup (zona waktu WIB), format: "Rab, 28 Okt · 08.24.47"
const elemenJam = document.querySelector('[data-jam]');
if (elemenJam) {
    const format = new Intl.DateTimeFormat('id-ID', {
        weekday: 'short', day: 'numeric', month: 'short',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false, timeZone: 'Asia/Jakarta',
    });

    const tampilkanJam = () => {
        const b = Object.fromEntries(format.formatToParts(new Date()).map((p) => [p.type, p.value]));
        elemenJam.textContent = `${b.weekday}, ${b.day} ${b.month} · ${b.hour}.${b.minute}.${b.second}`;
    };

    tampilkanJam();
    setInterval(tampilkanJam, 1000);
}

// 3. Tombol Esc menutup sidebar yang dibuka lewat tombol menu (di halaman login tidak ada sidebar, jadi aman)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const kotak = document.getElementById('sidebar-toggle');
        if (kotak) kotak.checked = false;
    }
});

// 4. Tombol mata di kolom password: tampilkan/sembunyikan isinya
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-lihat-sandi]');
    if (!tombol) return;

    const kolom = tombol.parentElement.querySelector('input');
    const tampil = kolom.type === 'password';
    kolom.type = tampil ? 'text' : 'password';
    tombol.toggleAttribute('data-tampil', tampil);
});

// 5. Laporan: lipat/buka satu kelompok pelayanan, atau semuanya sekaligus
const aturKelompok = (kelompok, buka) => {
    kelompok.classList.toggle('tutup', !buka);
    kelompok.querySelector('[data-lipat-grup]')?.setAttribute('aria-expanded', String(buka));
};

document.addEventListener('click', (e) => {
    const kepala = e.target.closest('[data-lipat-grup]');
    if (kepala) {
        const kelompok = kepala.closest('.grup-laporan');
        aturKelompok(kelompok, kelompok.classList.contains('tutup'));
        return;
    }

    const semua = e.target.closest('[data-lipat-semua]');
    if (semua) {
        document.querySelectorAll('.grup-laporan').forEach((k) => aturKelompok(k, semua.dataset.lipatSemua === 'buka'));
    }
});

// 6. Dashboard: tab Detail (SPM | IKU)
const wadahTab = document.querySelector('[data-tab-wadah]');
if (wadahTab) {
    const pilihTab = (nama) => {
        wadahTab.querySelectorAll('[data-tab-tombol]').forEach((t) => {
            const aktif = t.dataset.tabTombol === nama;
            t.classList.toggle('aktif', aktif);
            t.setAttribute('aria-selected', String(aktif));
        });
        wadahTab.querySelectorAll('[data-tab-isi]').forEach((p) => {
            p.classList.toggle('aktif', p.dataset.tabIsi === nama);
        });
        try { sessionStorage.setItem('tabDetail', nama); } catch (e) { /* abaikan kalau penyimpanan diblokir */ }
    };

    // Tab terakhir diingat selama sesi supaya tidak balik ke SPM setiap ganti tahun atau bulan.
    // Tautan #tabel (klik nama pelayanan di bar kelengkapan) selalu membuka tab SPM.
    let awal = 'spm';
    try { awal = sessionStorage.getItem('tabDetail') || 'spm'; } catch (e) { /* pakai tab SPM */ }
    if (location.hash === '#tabel' || !wadahTab.querySelector(`[data-tab-isi="${awal}"]`)) awal = 'spm';
    pilihTab(awal);

    wadahTab.addEventListener('click', (e) => {
        const tombol = e.target.closest('[data-tab-tombol]');
        if (tombol) pilihTab(tombol.dataset.tabTombol);
    });
}

// 7. Dashboard: animasi (angka dihitung naik, bagian muncul saat digulir, sorot mengikuti kursor).
// Gayanya ada di public/css/animasi.css. Dilewati kalau pengguna mematikan animasi di perangkatnya.
const dasbor = document.getElementById('ringkasan');
if (dasbor && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    // 7a. Nomor baris (--r) dan kolom (--c) untuk jeda bertahap pada bar, segmen, dan heatmap
    document.querySelectorAll('.matriks').forEach((matriks) => {
        matriks.querySelectorAll('.matriks-baris').forEach((baris, r) => {
            baris.style.setProperty('--r', r);
            baris.querySelectorAll('.heat-sel').forEach((sel, c) => sel.style.setProperty('--c', c));
        });
    });
    document.querySelectorAll('.segmen-bar').forEach((bar) => {
        bar.querySelectorAll('i').forEach((i, c) => i.style.setProperty('--c', c));
    });

    // 7b. Angka besar dan angka pendukung dihitung naik dari 0. Teks akhir dikembalikan persis seperti aslinya.
    // Hanya angka polos atau persen (contoh "85,3%", "26"); yang lain seperti "5/20" dibiarkan.
    const hitungNaik = (el, jeda) => {
        const asli = el.textContent.trim();
        const cocok = asli.match(/^(\d+(?:[.,]\d+)?)(\s*%)?$/);
        if (!cocok) return;

        const desimal = cocok[1].includes(',') ? cocok[1].split(',')[1].length : 0;
        const nilai = parseFloat(cocok[1].replace(/\./g, '').replace(',', '.'));
        const akhiran = cocok[2] || '';
        const format = new Intl.NumberFormat('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });
        const durasi = 1100;
        const mulai = performance.now() + jeda;

        el.textContent = format.format(0) + akhiran;

        const langkah = (sekarang) => {
            const p = Math.min(Math.max((sekarang - mulai) / durasi, 0), 1);
            const halus = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);   // melambat di akhir
            el.textContent = p === 1 ? asli : format.format(nilai * halus) + akhiran;
            if (p < 1) requestAnimationFrame(langkah);
        };
        requestAnimationFrame(langkah);
    };

    dasbor.querySelectorAll('.ringkas-angka strong, .kartu-angka').forEach((el, i) => hitungNaik(el, 250 + i * 70));

    // 7c. Bagian yang masih di bawah layar muncul saat digulir sampai. Dilewati kalau alamat memuat #tabel dan sejenisnya,
    // supaya lompatan ke bagian itu tidak bertemu bagian yang masih tersembunyi.
    if ('IntersectionObserver' in window && !location.hash) {
        const dibawah = [...document.querySelectorAll('.halaman > .bagian')]
            .filter((b) => b.getBoundingClientRect().top > innerHeight * 0.9);

        if (dibawah.length) {
            const pengamat = new IntersectionObserver((daftar) => {
                daftar.forEach((d) => {
                    if (!d.isIntersecting) return;
                    d.target.classList.add('tampil');
                    d.target.querySelectorAll('.anim-tunggu').forEach((x) => x.classList.remove('anim-tunggu'));
                    pengamat.unobserve(d.target);
                });
            }, { threshold: 0.08 });

            dibawah.forEach((b) => {
                b.classList.add('sembunyi');
                b.querySelectorAll('.panel-matriks').forEach((p) => p.classList.add('anim-tunggu'));
                pengamat.observe(b);
            });
        }
    }

    // 7d. Sorot cahaya mengikuti kursor di panel (hanya perangkat dengan mouse)
    if (matchMedia('(hover: hover)').matches) {
        document.querySelectorAll('.ringkas, .panel-matriks').forEach((kartu) => {
            kartu.classList.add('sorot');
            let menunggu = false;

            kartu.addEventListener('pointermove', (e) => {
                if (menunggu) return;
                menunggu = true;
                requestAnimationFrame(() => {
                    const k = kartu.getBoundingClientRect();
                    kartu.style.setProperty('--mx', `${e.clientX - k.left}px`);
                    kartu.style.setProperty('--my', `${e.clientY - k.top}px`);
                    menunggu = false;
                });
            });
        });
    }
}