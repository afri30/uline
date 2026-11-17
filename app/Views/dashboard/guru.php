<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-success text-white py-3 px-4 rounded-top-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-workspace me-2"></i>Area Khusus Guru Penguji</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="alert alert-success bg-success-subtle border border-success-subtle text-success-emphasis rounded-3 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Otorisasi Berhasil:</strong> Anda berhasil melewati <code>RoleMiddleware</code> untuk panel <strong>GURU</strong>.
                </div>
                
                <h6 class="fw-bold mb-3">Placeholder Fitur Guru:</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-journal-plus fs-3 text-success"></i>
                            <h6 class="fw-bold mt-2 mb-1">Manajemen Bank Soal</h6>
                            <p class="text-muted extra-small mb-0">Input soal pilihan ganda, essay, dan kunci jawaban.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-camera-video fs-3 text-success"></i>
                            <h6 class="fw-bold mt-2 mb-1">Live Proctoring Room</h6>
                            <p class="text-muted extra-small mb-0">Pantau webcam dan peringatan AI peserta secara realtime.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-file-earmark-bar-graph fs-3 text-success"></i>
                            <h6 class="fw-bold mt-2 mb-1">Laporan & Rekap Nilai</h6>
                            <p class="text-muted extra-small mb-0">Export nilai otomatis beserta log pelanggaran AI.</p>
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
