<div class="panel-card mb-4">
    <div class="panel-head"><h5>Analytics Filters</h5></div>
    <form method="get" class="row g-3">
        <div class="col-md-3"><label class="form-label">Academic Year</label><input id="analyticsYear" name="year" class="form-control" value="<?= e($year) ?>"></div>
        <div class="col-md-3"><label class="form-label">Class</label>
            <select name="class_id" class="form-select" data-role="class-subject-driver" data-subject-target="#analyticsSubject" data-year-target="#analyticsYear" data-endpoint="<?= e(base_url('/api/class-subjects')) ?>" data-selected-subject="<?= e((string)$subjectId) ?>">
                <option value="">School-wide</option>
                <?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= ((string)$classId===(string)$row['class_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Exam</label><select name="exam_id" class="form-select"><option value="">All exams in year</option><?php foreach ($exams as $row): ?><option value="<?= e($row['exam_id']) ?>" <?= ((string)$examId===(string)$row['exam_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Subject Breakdown</label><select id="analyticsSubject" name="subject_id" class="form-select" data-selected-value="<?= e((string)$subjectId) ?>"><option value="">All subjects</option><?php foreach ($subjects as $row): ?><option value="<?= e($row['subject_id']) ?>" <?= ((string)$subjectId===(string)$row['subject_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12 d-flex gap-2 flex-wrap align-items-center"><button class="btn btn-outline-primary">Run Analytics</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/analytics')) ?>">Reset</a><span class="badge-soft-primary">Pass mark in use: <?= e(number_format((float)$passMark,0)) ?>%</span></div>
    </form>
</div>

<?php
$totalCount = (int)($passFail['total_count'] ?? 0);
$passCount = (int)($passFail['pass_count'] ?? 0);
$failCount = (int)($passFail['fail_count'] ?? 0);
$passRate = $totalCount > 0 ? ($passCount / $totalCount) * 100 : 0;
$failRate = $totalCount > 0 ? ($failCount / $totalCount) * 100 : 0;
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="metric-card h-100"><span class="metric-label">Students Covered</span><strong class="metric-value"><?= e((string)($overview['students_covered'] ?? 0)) ?></strong><small>Unique students in current scope</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="metric-card h-100"><span class="metric-label">Overall Average</span><strong class="metric-value"><?= e(number_format((float)($overview['school_average'] ?? 0), 2)) ?>%</strong><small>Highest mark <?= e((string)($overview['highest_mark'] ?? 0)) ?>%</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="metric-card h-100"><span class="metric-label">Pass Rate</span><strong class="metric-value"><?= e(number_format($passRate, 1)) ?>%</strong><small><?= e((string)$passCount) ?> pass entries at <?= e(number_format((float)$passMark,0)) ?>%+</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="metric-card h-100"><span class="metric-label">Fail Rate</span><strong class="metric-value"><?= e(number_format($failRate, 1)) ?>%</strong><small><?= e((string)$failCount) ?> below pass mark</small></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel-card h-100">
            <div class="panel-head d-flex justify-content-between align-items-center"><h5><?= $classId ? 'Class Rankings' : 'Top Students School-wide' ?></h5><?php if ($rankings || $bestStudents): ?><span class="badge-soft-success"><?= count($rankings ?: $bestStudents) ?> listed</span><?php endif; ?></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>#</th><th>Student</th><th>Code</th><th>Sex</th><th>Total</th><th>Average</th><th>Subjects</th></tr></thead>
                    <tbody>
                    <?php foreach (($rankings ?: $bestStudents) as $i => $row): ?>
                    <tr><td><?= e($i + 1) ?></td><td><?= e($row['name']) ?></td><td><?= e($row['student_code'] ?? '-') ?></td><td><?= e($row['sex'] ?? '-') ?></td><td><?= e(number_format((float)($row['total_marks'] ?? 0), 2)) ?></td><td><?= e(number_format((float)($row['average_marks'] ?? 0), 2)) ?></td><td><?= e((string)($row['subjects_written'] ?? 0)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!($rankings ?: $bestStudents)): ?><tr><td colspan="7" class="empty-state">Load filters to see student rankings.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel-card h-100">
            <div class="panel-head"><h5>Best Performing Teachers</h5></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>#</th><th>Teacher</th><th>Avg</th><th>Pass</th><th>Fail</th></tr></thead>
                    <tbody>
                    <?php foreach ($bestTeachers as $i => $row): ?>
                    <tr><td><?= e($i + 1) ?></td><td><?= e($row['name'] ?: 'Unassigned') ?></td><td><?= e(number_format((float)$row['average_marks'], 2)) ?></td><td><?= e((string)$row['pass_count']) ?></td><td><?= e((string)$row['fail_count']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$bestTeachers): ?><tr><td colspan="5" class="empty-state">No teacher analytics available in this scope.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Subject Performance Breakdown</h5></div><div style="height:320px"><canvas id="subjectAverageChart"></canvas></div></div></div>
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Class-wise Average Comparison</h5></div><div style="height:320px"><canvas id="classAverageChart"></canvas></div></div></div>
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Grade Distribution</h5></div><div style="height:320px"><canvas id="gradeDistributionChart"></canvas></div></div></div>
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Pass vs Fail</h5></div><div style="height:320px"><canvas id="passFailChart"></canvas></div></div></div>
</div>


<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5>Teacher Performance Scorecards</h5>
        <span class="badge-soft-primary"><?= count($teacherScorecards) ?> scorecards</span>
    </div>
    <div class="teacher-score-grid">
        <?php foreach ($teacherScorecards as $row): ?>
            <div class="teacher-scorecard">
                <h6><?= e($row['name']) ?></h6>
                <div class="score-meta">
                    <div class="score-pill"><span class="text-secondary small">Average</span><strong><?= e(number_format((float)$row['average_marks'], 2)) ?>%</strong></div>
                    <div class="score-pill"><span class="text-secondary small">Pass Rate</span><strong><?= e(number_format((float)$row['pass_rate'], 1)) ?>%</strong></div>
                    <div class="score-pill"><span class="text-secondary small">Scripts</span><strong><?= e((string)$row['scripts_marked']) ?></strong></div>
                    <div class="score-pill"><span class="text-secondary small">Pass / Fail</span><strong><?= e((string)$row['pass_count']) ?> / <?= e((string)$row['fail_count']) ?></strong></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$teacherScorecards): ?>
            <div class="empty-state">No teacher scorecards available for the selected scope.</div>
        <?php endif; ?>
    </div>
</div>

<div class="chart-grid-extended mb-4">
    <div class="panel-card"><div class="panel-head"><h5>Top vs Weak Subjects</h5><div class="form-helper">Best and weakest subjects by average mark in the current scope.</div></div><div style="height:320px"><canvas id="topWeakSubjectsChart"></canvas></div></div>
    <div class="panel-card"><div class="panel-head"><h5>Teacher Performance Overview</h5><div class="form-helper">Average marks compared with pass rate for top teachers.</div></div><div style="height:320px"><canvas id="teacherPerformanceChart"></canvas></div></div>
</div>

<div class="panel-card mb-4">
    <div class="panel-head"><h5>Class Comparison Graph</h5><div class="form-helper">Average marks and pass rate compared class by class.</div></div>
    <div style="height:340px"><canvas id="classComparisonChart"></canvas></div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Top Students Trend</h5></div><div style="height:320px"><canvas id="topStudentChart"></canvas></div></div></div>
    <div class="col-xl-6"><div class="panel-card"><div class="panel-head"><h5>Performance by Sex</h5></div><div style="height:320px"><canvas id="sexAverageChart"></canvas></div></div></div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-5">
        <div class="panel-card">
            <div class="panel-head"><h5>Class-wise Leaders</h5></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Class</th><th>Average</th><th>Top Student</th><th>Top Avg</th><th>Pass</th><th>Fail</th></tr></thead>
                    <tbody>
                    <?php foreach ($classTopSummary as $row): ?>
                    <tr><td><?= e($row['class_name']) ?></td><td><?= e(number_format((float)$row['average_marks'], 2)) ?></td><td><?= e($row['top_student']) ?></td><td><?= e($row['top_average'] !== null ? number_format((float)$row['top_average'], 2) : '-') ?></td><td><?= e((string)$row['pass_count']) ?></td><td><?= e((string)$row['fail_count']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$classTopSummary): ?><tr><td colspan="6" class="empty-state">No class leadership summary available.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="panel-card">
            <div class="panel-head"><h5>Performance by Sex</h5></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Sex</th><th>Entries</th><th>Average</th><th>Pass</th><th>Fail</th></tr></thead>
                    <tbody>
                    <?php foreach ($sexPerformance as $row): ?>
                    <tr><td><?= e($row['sex']) ?></td><td><?= e((string)$row['entries']) ?></td><td><?= e(number_format((float)$row['average_marks'], 2)) ?></td><td><?= e((string)$row['pass_count']) ?></td><td><?= e((string)$row['fail_count']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$sexPerformance): ?><tr><td colspan="5" class="empty-state">No male/female analytics available in this scope.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="panel-card">
            <div class="panel-head"><h5>Subject-Specific Student Performance</h5></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Student</th><th>Code</th><th>Sex</th><th>Mark</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($subjectPerformance as $row): ?>
                    <tr><td><?= e($row['name']) ?></td><td><?= e($row['student_code']) ?></td><td><?= e($row['sex']) ?></td><td><?= e(format_mark($row['mark_obtained'] ?? null)) ?></td><td><?= e(format_mark($row['mark_total'] ?? null)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$subjectPerformance): ?><tr><td colspan="5" class="empty-state">Choose a specific subject and exam to see the student-by-student marks table.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.metric-card{background:var(--panel-bg,#fff);border:1px solid rgba(148,163,184,.18);border-radius:1.25rem;padding:1rem 1.1rem;box-shadow:0 12px 35px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:.35rem}
.metric-label{font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted,#64748b)}
.metric-value{font-size:1.8rem;line-height:1;color:var(--text-strong,#0f172a)}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const subjectLabels = <?= json_encode(array_map(fn($row) => $row['name'], $subjectAverages)) ?>;
    const subjectData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $subjectAverages)) ?>;
    const classLabels = <?= json_encode(array_map(fn($row) => $row['name'], $classAverages)) ?>;
    const classData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $classAverages)) ?>;
    const gradeLabels = <?= json_encode(array_map(fn($row) => $row['label'] . ' (' . $row['point'] . ')', $gradeDistribution)) ?>;
    const gradeData = <?= json_encode(array_map(fn($row) => (int)$row['count'], $gradeDistribution)) ?>;
    const topStudentLabels = <?= json_encode(array_map(fn($row) => $row['name'], ($rankings ?: $bestStudents))) ?>;
    const topStudentData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], ($rankings ?: $bestStudents))) ?>;
    const sexLabels = <?= json_encode(array_map(fn($row) => $row['sex'], $sexPerformance)) ?>;
    const sexAverages = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $sexPerformance)) ?>;
    const topSubjectLabels = <?= json_encode(array_map(fn($row) => $row['name'], $topSubjects)) ?>;
    const topSubjectData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $topSubjects)) ?>;
    const weakSubjectLabels = <?= json_encode(array_map(fn($row) => $row['name'], $weakSubjects)) ?>;
    const weakSubjectData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $weakSubjects)) ?>;
    const teacherLabels = <?= json_encode(array_map(fn($row) => $row['name'], $teacherScorecards)) ?>;
    const teacherAverageData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $teacherScorecards)) ?>;
    const teacherPassRateData = <?= json_encode(array_map(fn($row) => (float)$row['pass_rate'], $teacherScorecards)) ?>;
    const classCompareLabels = <?= json_encode(array_map(fn($row) => $row['name'], $classComparisonSeries)) ?>;
    const classCompareAverageData = <?= json_encode(array_map(fn($row) => (float)$row['average_marks'], $classComparisonSeries)) ?>;
    const classComparePassRateData = <?= json_encode(array_map(fn($row) => (float)$row['pass_rate'], $classComparisonSeries)) ?>;
    const passFailLabels = ['Pass', 'Fail'];
    const passFailData = [<?= (int)$passCount ?>, <?= (int)$failCount ?>];
    const mkBar = (id, labels, data, max=100) => { const el = document.getElementById(id); if (!el) return; new Chart(el, {type:'bar',data:{labels,datasets:[{data,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max}},plugins:{legend:{display:false}}}}); };
    mkBar('subjectAverageChart', subjectLabels, subjectData);
    mkBar('classAverageChart', classLabels, classData);
    const g = document.getElementById('gradeDistributionChart'); if (g) new Chart(g,{type:'doughnut',data:{labels:gradeLabels,datasets:[{data:gradeData,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false}});
    const pf = document.getElementById('passFailChart'); if (pf) new Chart(pf,{type:'pie',data:{labels:passFailLabels,datasets:[{data:passFailData,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false}});
    const t = document.getElementById('topStudentChart'); if (t) new Chart(t,{type:'line',data:{labels:topStudentLabels,datasets:[{data:topStudentData,tension:.35,fill:false,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100}},plugins:{legend:{display:false}}}});
    mkBar('sexAverageChart', sexLabels, sexAverages);
    const tw = document.getElementById('topWeakSubjectsChart'); if (tw) new Chart(tw,{type:'bar',data:{labels:[...topSubjectLabels,...weakSubjectLabels],datasets:[{label:'Average',data:[...topSubjectData,...weakSubjectData],borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}}}});
    const tp = document.getElementById('teacherPerformanceChart'); if (tp) new Chart(tp,{data:{labels:teacherLabels,datasets:[{type:'bar',label:'Average Mark',data:teacherAverageData,borderWidth:1,yAxisID:'y'},{type:'line',label:'Pass Rate',data:teacherPassRateData,tension:.35,borderWidth:2,yAxisID:'y1'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,position:'left'},y1:{beginAtZero:true,max:100,position:'right',grid:{drawOnChartArea:false}}}}});
    const cc = document.getElementById('classComparisonChart'); if (cc) new Chart(cc,{data:{labels:classCompareLabels,datasets:[{type:'bar',label:'Average Mark',data:classCompareAverageData,borderWidth:1,yAxisID:'y'},{type:'line',label:'Pass Rate',data:classComparePassRateData,tension:.35,borderWidth:2,yAxisID:'y1'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,position:'left'},y1:{beginAtZero:true,max:100,position:'right',grid:{drawOnChartArea:false}}}}});
})();
</script>
