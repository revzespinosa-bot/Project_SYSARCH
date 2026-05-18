<?php
$analyticsMaxDaily = 1;
foreach ($adminAnalytics['daily'] as $cnt) {
    $analyticsMaxDaily = max($analyticsMaxDaily, (int)$cnt);
}
$analyticsMaxLab = 1;
foreach ($adminAnalytics['by_lab'] as $lb) {
    $analyticsMaxLab = max($analyticsMaxLab, (int)$lb['cnt']);
}
if ($labSoftwareResult) {
    $labSoftwareResult->data_seek(0);
}
?>
    <div class="admin-modal" id="analyticsModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:960px; width:95%;">
            <span class="close-modal" onclick="closeFeature('analyticsModal')">&times;</span>
            <h3>📈 Analytics Dashboard</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin:16px 0;">
                <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:16px;border-radius:12px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo (int)$totalSitinsAllTime; ?></div>
                    <div style="font-size:13px;opacity:.9;">Total Sit-ins</div>
                </div>
                <div style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:16px;border-radius:12px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo (int)($adminAnalytics['reservation_stats']['pending'] ?? 0); ?></div>
                    <div style="font-size:13px;opacity:.9;">Pending Reservations</div>
                </div>
                <div style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:16px;border-radius:12px;text-align:center;">
                    <div style="font-size:26px;font-weight:700;"><?php echo count($adminAnalytics['by_lab']); ?></div>
                    <div style="font-size:13px;opacity:.9;">Labs Used</div>
                </div>
            </div>
            <h4 style="margin:18px 0 10px;">Sit-ins — last 7 days</h4>
            <div style="display:flex;align-items:flex-end;gap:8px;height:140px;padding:10px;background:#f8fafc;border-radius:10px;">
                <?php foreach ($adminAnalytics['daily'] as $day => $cnt): ?>
                    <?php $h = max(8, (int)round(((int)$cnt / $analyticsMaxDaily) * 110)); ?>
                    <div style="flex:1;text-align:center;">
                        <div style="height:<?php echo $h; ?>px;background:linear-gradient(180deg,#6366f1,#818cf8);border-radius:6px 6px 0 0;margin:0 auto;max-width:48px;" title="<?php echo (int)$cnt; ?>"></div>
                        <small style="display:block;margin-top:6px;color:#64748b;"><?php echo date('D', strtotime($day)); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <h4 style="margin:18px 0 10px;">Top labs (all time)</h4>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Lab</th><th>Sit-ins</th><th>Share</th></tr></thead>
                    <tbody>
                    <?php if (!empty($adminAnalytics['by_lab'])): ?>
                        <?php foreach ($adminAnalytics['by_lab'] as $lb): ?>
                            <tr>
                                <td>Lab <?php echo htmlspecialchars($lb['lab']); ?></td>
                                <td><?php echo (int)$lb['cnt']; ?></td>
                                <td>
                                    <div style="background:#e0e7ff;border-radius:4px;height:8px;width:<?php echo min(100, round(((int)$lb['cnt'] / $analyticsMaxLab) * 100)); ?>%;"></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#666;">No data yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-modal" id="aiModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:720px; width:95%;">
            <span class="close-modal" onclick="closeFeature('aiModal')">&times;</span>
            <h3>🤖 AI Recommendations</h3>
            <p style="color:#64748b;margin-bottom:16px;">Smart tips based on sit-in history, lab capacity, and pending requests.</p>
            <?php foreach ($adminAiTips as $tip): ?>
                <?php
                $bg = '#eff6ff';
                $border = '#3b82f6';
                if ($tip['type'] === 'warning') { $bg = '#fffbeb'; $border = '#f59e0b'; }
                if ($tip['type'] === 'success') { $bg = '#ecfdf5'; $border = '#10b981'; }
                ?>
                <div style="padding:14px 16px;margin-bottom:12px;border-radius:10px;background:<?php echo $bg; ?>;border-left:4px solid <?php echo $border; ?>;">
                    <strong><?php echo htmlspecialchars($tip['title']); ?></strong>
                    <p style="margin:8px 0 0;color:#374151;font-size:14px;"><?php echo $tip['body']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-modal" id="softwareModal" style="display:none;">
        <div class="admin-modal-content" style="max-width:900px; width:95%;">
            <span class="close-modal" onclick="closeFeature('softwareModal')">&times;</span>
            <h3>💿 Software Apps — Import / Upload</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:16px;">
                <div>
                    <h4>Add software manually</h4>
                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="action" value="add_software">
                        <label>Lab</label>
                        <select name="lab_name" required style="width:100%;padding:10px;margin-bottom:10px;">
                            <option value="">Select lab</option>
                            <?php foreach (['524','526','528','530','542','544'] as $labOpt): ?>
                                <option value="<?php echo $labOpt; ?>">Lab <?php echo $labOpt; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Software name</label>
                        <input type="text" name="software_name" required style="width:100%;padding:10px;margin-bottom:10px;">
                        <label>Version</label>
                        <input type="text" name="version" placeholder="e.g. 2022" style="width:100%;padding:10px;margin-bottom:10px;">
                        <button type="submit" class="save-btn">Add software</button>
                    </form>
                    <hr style="margin:20px 0;">
                    <h4>Import CSV</h4>
                    <p style="font-size:13px;color:#64748b;">Columns: <code>lab, software_name, version</code></p>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                        <input type="hidden" name="action" value="import_software_csv">
                        <input type="file" name="software_csv" accept=".csv" required>
                        <button type="submit" class="save-btn" style="margin-top:10px;">Upload CSV</button>
                    </form>
                </div>
                <div>
                    <h4>Installed software by lab</h4>
                    <div class="table-wrap" style="max-height:360px;overflow:auto;margin-top:10px;">
                        <table>
                            <thead><tr><th>Lab</th><th>Software</th><th>Ver.</th><th></th></tr></thead>
                            <tbody>
                            <?php if ($labSoftwareResult && $labSoftwareResult->num_rows > 0): ?>
                                <?php while ($sw = $labSoftwareResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sw['lab_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sw['software_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sw['version'] ?: '—'); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this software?');">
                                                <input type="hidden" name="action" value="delete_software">
                                                <input type="hidden" name="software_id" value="<?php echo (int)$sw['id']; ?>">
                                                <button type="submit" style="background:#ef4444;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">×</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;color:#666;">No software listed yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
