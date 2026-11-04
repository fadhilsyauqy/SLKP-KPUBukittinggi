<!-- Progressive Web App (PWA) Install Prompt Banner (TASK-4.1) -->
<div id="pwaInstallBanner" class="pwa-install-banner shadow-lg d-none" style="position: fixed; bottom: 20px; left: 16px; right: 16px; z-index: 9999; max-width: 440px; margin: 0 auto; background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 12px 14px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2.5">
            <img src="{{ asset('assets/img/icons/icon-192.png') }}" alt="Logo SLKP KPU" style="width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;" onerror="this.src='{{ asset('assets/img/logo-kpu.png') }}'">
            <div>
                <div class="fw-bold text-dark lh-sm" style="font-size: 0.88rem;">Install SLKP KPU Bukittinggi</div>
                <small class="text-muted" style="font-size: 0.74rem;">Pasang di layar utama untuk akses instan</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
            <button type="button" id="btnPwaInstallAction" class="btn btn-sm text-white fw-semibold px-2.5 py-1" style="background-color: #7a1c1c; font-size: 0.78rem; border-radius: 8px;">
                <i class="bi bi-phone me-1"></i> Install
            </button>
            <button type="button" id="btnPwaDismissBanner" class="btn btn-sm btn-light text-muted p-1" style="border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;" title="Tutup">
                <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal Panduan Install iOS Safari -->
<div class="modal fade" id="modalPwaIosGuide" tabindex="-1" aria-labelledby="modalPwaIosGuideLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark" id="modalPwaIosGuideLabel">
                    <i class="bi bi-apple me-1 text-secondary"></i> Install di iPhone / iPad
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-3">
                <img src="{{ asset('assets/img/icons/icon-192.png') }}" alt="SLKP KPU" style="width: 56px; height: 56px; border-radius: 14px;" class="mb-3 shadow-sm" onerror="this.src='{{ asset('assets/img/logo-kpu.png') }}'">
                <p class="small text-muted mb-3">Untuk memasang aplikasi <strong>SLKP KPU Bukittinggi</strong> di layar utama iOS Safari:</p>
                <div class="text-start bg-light p-3 rounded-3 small">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-secondary me-2">1</span>
                        <span>Ketuk tombol <strong>Bagikan</strong> (<i class="bi bi-box-arrow-up text-primary"></i> Share) di bar bawah browser.</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2">2</span>
                        <span>Gulir ke bawah lalu pilih <strong>"Tambahkan ke Layar Utama"</strong> (<em>Add to Home Screen</em>).</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal" style="border-radius: 8px;">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // Cek jika aplikasi sudah berjalan dalam mode standalone (layar penuh PWA)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                             window.navigator.standalone === true;

        if (isStandalone) {
            return;
        }

        let deferredPrompt = null;
        const banner = document.getElementById('pwaInstallBanner');
        const installBtn = document.getElementById('btnPwaInstallAction');
        const dismissBtn = document.getElementById('btnPwaDismissBanner');
        const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        const dismissed = sessionStorage.getItem('pwa_banner_dismissed');

        // Android / Desktop Chromium beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;

            if (!dismissed && banner) {
                banner.classList.remove('d-none');
            }
        });

        // Deteksi iOS Safari
        if (isIos && !dismissed && banner) {
            banner.classList.remove('d-none');
        }

        if (installBtn) {
            installBtn.addEventListener('click', function () {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function (choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            if (banner) banner.classList.add('d-none');
                        }
                        deferredPrompt = null;
                    });
                } else if (isIos) {
                    if (banner) banner.classList.add('d-none');
                    const iosModalEl = document.getElementById('modalPwaIosGuide');
                    if (iosModalEl && typeof bootstrap !== 'undefined') {
                        const modal = new bootstrap.Modal(iosModalEl);
                        modal.show();
                    }
                } else {
                    alert('Untuk menginstall aplikasi SLKP KPU, gunakan menu browser Anda lalu pilih "Tambahkan ke Layar Utama" / "Install Aplikasi".');
                }
            });
        }

        if (dismissBtn && banner) {
            dismissBtn.addEventListener('click', function () {
                banner.classList.add('d-none');
                sessionStorage.setItem('pwa_banner_dismissed', 'true');
            });
        }

        window.addEventListener('appinstalled', function () {
            if (banner) banner.classList.add('d-none');
            deferredPrompt = null;
        });
    })();
</script>
