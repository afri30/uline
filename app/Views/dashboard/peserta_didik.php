<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-primary text-white py-3 px-4 rounded-top-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-mortarboard-fill me-2"></i>Area Khusus Peserta Didik</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="alert alert-primary bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-3 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Otorisasi Berhasil:</strong> Anda berhasil melewati <code>RoleMiddleware</code> untuk panel <strong>PESERTA DIDIK</strong>.
                </div>
                
                <h6 class="fw-bold mb-3">Placeholder Fitur Peserta Didik:</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-card-checklist fs-3 text-primary"></i>
                            <h6 class="fw-bold mt-2 mb-1">Daftar Jadwal Ujian</h6>
                            <p class="text-muted extra-small mb-0">Lihat ujian yang sedang aktif dan siap dikerjakan.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-webcam fs-3 text-primary"></i>
                            <h6 class="fw-bold mt-2 mb-1">Cek Kamera & WebRTC</h6>
                            <p class="text-muted extra-small mb-0">Verifikasi perangkat webcam sebelum memasuki ruang ujian.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-trophy fs-3 text-primary"></i>
                            <h6 class="fw-bold mt-2 mb-1">Hasil & Nilai</h6>
                            <p class="text-muted extra-small mb-0">Lihat riwayat pengerjaan dan skor ujian yang telah dirilis.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
