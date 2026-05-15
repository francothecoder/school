<div class="ltp-login-shell">
    <div class="ltp-login-bg-orb orb-one"></div>
    <div class="ltp-login-bg-orb orb-two"></div>
    <div class="ltp-login-card">
        <div class="ltp-login-hero">
            <div class="ltp-brand-chip"><i class="bi bi-mortarboard-fill"></i> LearnTrack Pro</div>
            <h1><?= e(school_meta('system_name', config('app.name', 'LearnTrack Pro'))) ?></h1>
            <p>Smart academic management for results, report cards, analytics, attendance, communication, and school performance tracking.</p>
            <div class="ltp-feature-stack">
                <div><i class="bi bi-file-earmark-bar-graph"></i><span>Professional report cards</span></div>
                <div><i class="bi bi-graph-up-arrow"></i><span>Academic analytics</span></div>
                <div><i class="bi bi-phone"></i><span>Mobile-ready teacher portal</span></div>
            </div>
            <div class="ltp-login-notices">
                <div class="metric-label text-white-50 mb-2">Latest announcements</div>
                <?php foreach (($announcements ?? []) as $notice): ?>
                    <div class="ltp-notice-item">
                        <strong><?= e($notice['notice_title'] ?? 'Announcement') ?></strong>
                        <small><?= e(announcement_date($notice)) ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($announcements)): ?><div class="text-white-50 small">No announcements posted yet.</div><?php endif; ?>
            </div>
        </div>
        <div class="ltp-login-form-panel">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="ltp-small-label">Secure Portal</div>
                    <h2 class="mb-1">Welcome back</h2>
                    <div class="text-secondary">Choose your portal and sign in.</div>
                </div>
                <a class="quick-result-link" href="<?= e(base_url('/results/quick')) ?>"><i class="bi bi-search-heart"></i> Quick results</a>
            </div>

            <form method="post" action="<?= e(base_url('/login')) ?>" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Portal</label>
                    <select name="portal" class="form-select form-select-lg">
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Email or Student Code</label>
                    <div class="input-icon-wrap ltp-input-wrap">
                        <i class="bi bi-person-circle"></i>
                        <input type="text" name="login" class="form-control form-control-lg ps-5" required placeholder="Enter email or student code">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Password</label>
                    <div class="input-icon-wrap ltp-input-wrap">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" name="password" class="form-control form-control-lg ps-5" placeholder="Enter password">
                    </div>
                </div>
                <div class="col-12 d-grid mt-2">
                    <button class="btn btn-primary btn-lg ltp-login-button"><i class="bi bi-box-arrow-in-right"></i> Access System</button>
                </div>
            </form>

            <div class="ltp-quick-box mt-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <div class="ltp-small-label">Parents & Students</div>
                        <h5 class="mb-1">Check results without login</h5>
                        <div class="text-secondary small">Use student code or email to access published results.</div>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/results/quick')) ?>">Open</a>
                </div>
                <form method="get" action="<?= e(base_url('/results/quick')) ?>" class="row g-2">
                    <div class="col-12 col-md-5"><input type="text" name="student_code" class="form-control" placeholder="Student code or email"></div>
                    <div class="col-6 col-md-4"><input type="text" name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
                    <div class="col-6 col-md-3 d-grid"><button class="btn btn-primary">Check</button></div>
                </form>
            </div>

            <div class="login-mini-footer mt-4">
                <a href="<?= e(base_url('/terms-and-conditions')) ?>">Terms</a>
                <span>•</span>
                <a href="<?= e(base_url('/privacy-policy')) ?>">Privacy</a>
                <span>•</span>
                <span>© <?= date('Y') ?> LearnTrack Pro by Nexware Systems</span>
            </div>
        </div>
    </div>
</div>