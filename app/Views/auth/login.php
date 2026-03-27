<div class="login-wrap">
    <div class="login-card premium-login-card">
        <div class="login-side">
            <div class="login-side-inner">
                <span class="eyebrow">Academic Platform</span>
                <h2><?= e(school_meta('system_name', 'Moomba Boarding Secondary School')) ?></h2>
                <p>Premium academic management system built for fast school operations on desktop and phone.</p>
                <div class="login-stat-grid">
                    <div class="mini-stat"><strong>Mobile First</strong><span>Easy navigation for teachers on phones</span></div>
                    <div class="mini-stat"><strong>Fast Results</strong><span>Exam and term report engine</span></div>
                    <div class="mini-stat"><strong>Announcements</strong><span>School-wide updates inside the portal</span></div>
                </div>
                <div class="login-announcement-strip mt-4">
                    <div class="metric-label text-white-50 mb-2">Latest school announcements</div>
                    <?php foreach (($announcements ?? []) as $notice): ?>
                        <div class="mini-announce-item">
                            <strong><?= e($notice['notice_title'] ?? 'Announcement') ?></strong>
                            <div><?= e(announcement_date($notice)) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($announcements)): ?><div class="text-white-50">No announcements posted yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="login-form-pane">
            <div class="login-pane-header">
                <div>
                    <h3 class="mb-1">Sign in</h3>
                    <div class="text-secondary">Access admin, teacher, or student portal.</div>
                </div>
                <a class="quick-result-link" href="<?= e(base_url('/results/quick')) ?>"><i class="bi bi-search-heart"></i> Quick results</a>
            </div>
            <form method="post" action="<?= e(base_url('/login')) ?>" class="row g-3 mt-1">
                <div class="col-12">
                    <label class="form-label">Portal</label>
                    <select name="portal" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Email or Student Code</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person-circle"></i>
                        <input type="text" name="login" class="form-control ps-5" required placeholder="Enter email or student code">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" name="password" class="form-control ps-5" placeholder="Enter password">
                    </div>
                </div>
                <div class="col-12 d-grid mt-2">
                    <button class="btn btn-primary btn-lg">Access System</button>
                </div>
                <div class="col-12">
                    <div class="small text-secondary">Admin/Teacher use email. Student can use student code or email. Results can also be checked without login, now with exam and term summaries from the quick results page.</div>
                </div>
            </form>

            <div class="quick-result-box mt-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <div class="metric-label">No login required</div>
                        <h5 class="mb-1">Quick Results Check</h5>
                        <div class="text-secondary">Let parents or students check results using student code and exam selection.</div>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/results/quick')) ?>">Open</a>
                </div>
                <form method="get" action="<?= e(base_url('/results/quick')) ?>" class="row g-2">
                    <div class="col-12 col-md-5"><input type="text" name="student_code" class="form-control" placeholder="Student code or email"></div>
                    <div class="col-6 col-md-4"><input type="text" name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
                    <div class="col-6 col-md-3 d-grid"><button class="btn btn-primary">Check</button></div>
                </form>
            </div>

            <div class="login-mini-footer">
                <a href="<?= e(base_url('/terms-and-conditions')) ?>">Terms and Conditions</a>
                <span>•</span>
                <a href="<?= e(base_url('/privacy-policy')) ?>">Privacy Policy</a>
                <span>•</span>
                <span>© <?= date('Y') ?> LearnTrack Pro by Nexwares Systems</span>
            </div>
        </div>
    </div>
</div>
