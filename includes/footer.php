<?php
// includes/footer.php
?>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Col 1: Identity -->
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="w-11 h-11 object-contain shrink-0 drop-shadow">
                        <div>
                            <h4 class="text-white font-extrabold text-base tracking-tight leading-none">SIPAS CIPEUNDEUY</h4>
                            <p class="text-xs text-sky-400 font-semibold mt-1">SMKN 1 Cipeundeuy</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Sistem Informasi Pengaduan & Aspirasi Sekolah untuk mewujudkan lingkungan pendidikan yang aman, transparan, tertib, dan bebas perundungan (bullying).
                    </p>
                    <div class="flex items-center gap-3 text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[11px] text-emerald-400 font-semibold">Layanan Pengaduan Aktif 24/7</span>
                    </div>
                </div>

                <!-- Col 2: Kategori Layanan -->
                <div>
                    <h5 class="text-white font-bold text-sm mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-smk-yellow"></i> Kategori Layanan
                    </h5>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="hover:text-white transition flex items-center gap-2">
                                <i class="fa-solid fa-bullhorn text-sky-400 text-[10px]"></i> Pengaduan Sarana & Prasarana
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="hover:text-white transition flex items-center gap-2">
                                <i class="fa-solid fa-graduation-cap text-sky-400 text-[10px]"></i> Pengaduan Akademik & KBM
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="text-rose-400 hover:text-rose-300 transition flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-shield-cat text-rose-500 text-[10px]"></i> Lapor Perundungan (Bullying)
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="text-rose-400 hover:text-rose-300 transition flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-hand-fist text-rose-500 text-[10px]"></i> Lapor Kekerasan & Pemalakan
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('index.php#alur') ?>" class="hover:text-white transition flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-amber-400 text-[10px]"></i> Kebijakan Perlindungan Pelapor
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 3: Kontak & Lokasi -->
                <div>
                    <h5 class="text-white font-bold text-sm mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-smk-yellow"></i> Kontak Sekolah
                    </h5>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-map-pin text-sky-400 mt-0.5"></i>
                            <span>Jl. Cirata Margalaksana RT/RW: 001/012 Kec. Cipeundeuy, Kab. Bandung Barat Prov. Jawa Barat 40558</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-school text-sky-400"></i>
                            <span>NPSN: 20279928 • Cadisdik Wilayah VI</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-envelope text-sky-400"></i>
                            <span>smkn1cpdykbb@gmail.com</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-globe text-sky-400"></i>
                            <a href="https://www.smkn1cipeundeuy.sch.id" target="_blank" class="hover:text-white transition">www.smkn1cipeundeuy.sch.id</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-sky-400"></i>
                            <span>Telp. 02263200004 (Fax: 0)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-400 text-sm"></i>
                            <span class="text-emerald-300 font-semibold">0812-3456-7890 (TPPK Center)</span>
                        </li>
                    </ul>
                </div>

                <!-- Col 4: Tim TPPK & Prinsip -->
                <div>
                    <h5 class="text-white font-bold text-sm mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-lock text-smk-yellow"></i> Perlindungan Privasi
                    </h5>
                    <div class="bg-slate-800/80 p-3.5 rounded-xl border border-slate-700 space-y-2 text-xs">
                        <p class="text-slate-300">
                            Sistem ini diawasi langsung oleh <strong>Tim Pencegahan dan Penanganan Kekerasan (TPPK)</strong> SMKN 1 Cipeundeuy.
                        </p>
                        <p class="text-amber-300/90 font-medium">
                            <i class="fa-solid fa-user-shield mr-1"></i> Identitas pelapor kekerasan dijamin kerahasiaannya sesuai ketentuan undang-undang dan aturan perlindungan saksi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="border-t border-slate-800 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 gap-4">
                <p>&copy; <?= date('Y') ?> SMKN 1 Cipeundeuy - Kab. Bandung Barat. Seluruh Hak Cipta Dilindungi.</p>
                <div class="flex items-center space-x-4">
                    <span class="text-slate-400">Teknologi Industri & Vokasi Berkualitas</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>

    <!-- SweetAlert2 Flash Notification Handler -->
    <?php if (!empty($flash)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const type = '<?= $flash['type'] === 'danger' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?>';
                const title = type === 'error' ? 'Pemberitahuan' : (type === 'warning' ? 'Perhatian' : 'Berhasil!');
                Swal.fire({
                    icon: type,
                    title: title,
                    text: <?= json_encode($flash['message']) ?>,
                    confirmButtonColor: '#0284c7',
                    timer: 4500,
                    timerProgressBar: true
                });
            });
        </script>
    <?php endif; ?>

</body>
</html>
