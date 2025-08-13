<?php
/**
 * High-Volume IP Lookup Testing - Web Interface
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'High-Volume IP Testing - CTI Tracker';
$testInProgress = false;
$testResults = [];

// Handle test execution
if (isset($_POST['run_high_volume_test'])) {
    $testInProgress = true;
    
    // Capture output
    ob_start();
    
    // Include and execute the high-volume test
    $TEST_PREFIX = 'WEBTEST_';
    $CLEANUP_AFTER = true;
    
    // Simplified version for web display
    executeHighVolumeTest();
    
    $output = ob_get_clean();
    $testResults = [
        'output' => $output,
        'timestamp' => date('Y-m-d H:i:s'),
        'duration' => 0
    ];
}

function executeHighVolumeTest() {
    global $pdo;
    
    $TEST_PREFIX = 'WEBTEST_';
    $totalStart = microtime(true);
    
    echo "<div class='test-section'>";
    echo "<h4>🚀 High-Volume Testing Started</h4>";
    
    // Setup test data
    echo "<div class='step-result'>";
    echo "<h5>📋 Step 1: Setting up test data</h5>";
    $setupStart = microtime(true);
    
    $teams = [
        'Network Ops', 'Security', 'Development', 'QA', 'Infrastructure',
        'DevOps', 'Database', 'Frontend', 'Backend', 'Mobile', 'Analytics'
    ];
    
    $totalAdded = 0;
    
    // Add 30 CIDR blocks
    for ($i = 1; $i <= 30; $i++) {
        $cidr = "10.{$i}.0.0/24";
        $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
        if (addIpRangeFromCidr($pdo, $cidr, $team)) {
            $totalAdded++;
        }
    }
    
    // Add 20 traditional ranges
    for ($i = 1; $i <= 20; $i++) {
        $startIp = "192.168.{$i}.1";
        $endIp = "192.168.{$i}.100";
        $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
        if (addIpRange($pdo, $startIp, $endIp, $team)) {
            $totalAdded++;
        }
    }
    
    // Add server IPs
    $serverIps = [];
    for ($i = 1; $i <= 50; $i++) {
        $serverIps[] = "203.0.113.{$i}";
    }
    $result = addIpListToTeam($pdo, implode(' ', $serverIps), $TEST_PREFIX . 'Server Farm');
    if ($result['success']) {
        $totalAdded += $result['added'];
    }
    
    $setupEnd = microtime(true);
    $setupDuration = round($setupEnd - $setupStart, 3);
    
    echo "<div class='alert alert-success'>";
    echo "✅ Setup Complete: {$totalAdded} IP entries added in {$setupDuration}s";
    echo "</div></div>";
    
    // Individual lookups test
    echo "<div class='step-result'>";
    echo "<h5>🔍 Step 2: Individual IP Lookups (2000 IPs)</h5>";
    $lookupStart = microtime(true);
    
    $testCount = 2000;
    $foundCount = 0;
    
    for ($i = 1; $i <= $testCount; $i++) {
        if ($i <= 500) {
            $subnet = (($i - 1) % 30) + 1;
            $host = (($i - 1) % 254) + 1;
            $testIp = "10.{$subnet}.0.{$host}";
        } elseif ($i <= 1000) {
            $subnet = (($i - 501) % 20) + 1;
            $host = (($i - 501) % 100) + 1;
            $testIp = "192.168.{$subnet}.{$host}";
        } else {
            $host = (($i - 1001) % 50) + 1;
            $testIp = "203.0.113.{$host}";
        }
        
        $team = getTeamByIp($pdo, $testIp);
        if ($team !== null) {
            $foundCount++;
        }
        
        // Progress updates
        if ($i % 500 === 0) {
            $progress = round(($i / $testCount) * 100);
            echo "<div class='progress-update'>Progress: {$progress}% ({$i}/{$testCount})</div>";
            flush();
        }
    }
    
    $lookupEnd = microtime(true);
    $lookupDuration = round($lookupEnd - $lookupStart, 3);
    $lookupsPerSecond = round($testCount / $lookupDuration, 1);
    $successRate = round(($foundCount / $testCount) * 100, 1);
    
    echo "<div class='alert alert-info'>";
    echo "✅ Individual Lookups: {$foundCount}/{$testCount} found ({$successRate}%) in {$lookupDuration}s ({$lookupsPerSecond}/sec)";
    echo "</div></div>";
    
    // Bulk processing test
    echo "<div class='step-result'>";
    echo "<h5>📦 Step 3: Bulk IP Processing (1000 IPs)</h5>";
    $bulkStart = microtime(true);
    
    $bulkBatches = 10;
    $batchSize = 100;
    $totalBulkProcessed = 0;
    $totalBulkFound = 0;
    
    for ($batch = 1; $batch <= $bulkBatches; $batch++) {
        $batchIps = [];
        for ($i = 1; $i <= $batchSize; $i++) {
            $subnet = (($batch - 1) % 30) + 1;
            $batchIps[] = "10.{$subnet}.0.{$i}";
        }
        
        $results = getTeamsByIpInput($pdo, implode(' ', $batchIps));
        $totalBulkProcessed += count($results);
        
        foreach ($results as $result) {
            if (isset($result['team']) && $result['team'] !== null) {
                $totalBulkFound++;
            }
        }
    }
    
    $bulkEnd = microtime(true);
    $bulkDuration = round($bulkEnd - $bulkStart, 3);
    $bulkRate = round($totalBulkProcessed / $bulkDuration, 1);
    $bulkSuccessRate = round(($totalBulkFound / $totalBulkProcessed) * 100, 1);
    
    echo "<div class='alert alert-success'>";
    echo "✅ Bulk Processing: {$totalBulkFound}/{$totalBulkProcessed} found ({$bulkSuccessRate}%) in {$bulkDuration}s ({$bulkRate}/sec)";
    echo "</div></div>";
    
    // Mixed format test
    echo "<div class='step-result'>";
    echo "<h5>🎭 Step 4: Mixed Format Processing</h5>";
    $mixedStart = microtime(true);
    
    $mixedEntries = [];
    
    // 100 individual IPs
    for ($i = 1; $i <= 100; $i++) {
        $subnet = (($i - 1) % 30) + 1;
        $host = (($i - 1) % 254) + 1;
        $mixedEntries[] = "10.{$subnet}.0.{$host}";
    }
    
    // 50 ranges
    for ($i = 1; $i <= 50; $i++) {
        $subnet = (($i - 1) % 20) + 1;
        $mixedEntries[] = "192.168.{$subnet}.1-192.168.{$subnet}.10";
    }
    
    // 50 CIDR blocks
    for ($i = 1; $i <= 50; $i++) {
        $subnet = (($i - 1) % 30) + 1;
        $mixedEntries[] = "10.{$subnet}.0.0/28";
    }
    
    $mixedInput = implode(' ', $mixedEntries);
    $mixedResults = getTeamsByIpInput($pdo, $mixedInput);
    
    $mixedEnd = microtime(true);
    $mixedDuration = round($mixedEnd - $mixedStart, 3);
    
    $typeCounts = ['single' => 0, 'range' => 0, 'cidr' => 0, 'invalid' => 0];
    $teamsFound = 0;
    
    foreach ($mixedResults as $result) {
        if (isset($typeCounts[$result['type']])) {
            $typeCounts[$result['type']]++;
        }
        if (isset($result['team']) && $result['team'] !== null) {
            $teamsFound++;
        }
        if (isset($result['overlapping_teams']) && !empty($result['overlapping_teams'])) {
            $teamsFound++;
        }
    }
    
    $totalMixed = count($mixedResults);
    
    echo "<div class='alert alert-warning'>";
    echo "✅ Mixed Processing: {$totalMixed} entries processed in {$mixedDuration}s<br>";
    echo "Types: Single({$typeCounts['single']}), Range({$typeCounts['range']}), CIDR({$typeCounts['cidr']})<br>";
    echo "Teams Found: {$teamsFound}";
    echo "</div></div>";
    
    // Performance summary
    $totalEnd = microtime(true);
    $totalDuration = round($totalEnd - $totalStart, 3);
    
    echo "<div class='step-result'>";
    echo "<h5>🏆 Performance Summary</h5>";
    echo "<div class='alert alert-primary'>";
    echo "<strong>Total Execution Time:</strong> {$totalDuration}s<br>";
    echo "<strong>Individual Lookups:</strong> {$lookupsPerSecond}/sec ({$successRate}% success)<br>";
    echo "<strong>Bulk Processing:</strong> {$bulkRate}/sec ({$bulkSuccessRate}% success)<br>";
    echo "<strong>Mixed Format:</strong> " . round($totalMixed / $mixedDuration, 1) . "/sec<br>";
    echo "<strong>Memory Peak:</strong> " . formatBytes(memory_get_peak_usage());
    echo "</div></div>";
    
    // Cleanup
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE ?");
    $stmt->execute([$TEST_PREFIX . '%']);
    $cleaned = $stmt->rowCount();
    
    echo "<div class='step-result'>";
    echo "<div class='alert alert-secondary'>";
    echo "🧹 Cleanup: Removed {$cleaned} test entries";
    echo "</div></div>";
    
    echo "</div>"; // Close test-section
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

include 'includes/header.php';
?>

<style>
.test-section {
    font-family: 'Courier New', monospace;
    font-size: 14px;
}
.step-result {
    margin: 15px 0;
    padding: 10px;
    border-left: 4px solid #007bff;
    background: #f8f9fa;
}
.progress-update {
    color: #6c757d;
    margin: 5px 0;
    font-size: 12px;
}
.performance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    margin: 10px 0;
}
.metric-box {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    text-align: center;
}
.live-results {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-rocket"></i> High-Volume IP Testing Suite</h1>
        <p class="text-muted">Comprehensive testing with thousands of IPs and performance benchmarking</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-play"></i> Start High-Volume Test</h5>
            </div>
            <div class="card-body">
                <?php if (!$testInProgress): ?>
                    <form method="POST" id="testForm">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> This test will:</h6>
                            <ul class="mb-0 small">
                                <li>Create 100+ IP ranges/entries</li>
                                <li>Test 2000+ individual IP lookups</li>
                                <li>Test 1000+ bulk IP processing</li>
                                <li>Test mixed format parsing</li>
                                <li>Measure performance metrics</li>
                                <li>Auto-cleanup test data</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small><i class="fas fa-clock"></i> <strong>Estimated Duration:</strong> 30-60 seconds</small>
                        </div>
                        
                        <button type="submit" name="run_high_volume_test" class="btn btn-primary w-100" onclick="startTest()">
                            <i class="fas fa-rocket"></i> Start High-Volume Test
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="performance-card">
                        <h6><i class="fas fa-chart-line"></i> Expected Performance</h6>
                        <div class="metric-box">
                            <strong>Individual Lookups</strong><br>
                            <small>Target: 1000+/sec</small>
                        </div>
                        <div class="metric-box">
                            <strong>Bulk Processing</strong><br>
                            <small>Target: 500+/sec</small>
                        </div>
                        <div class="metric-box">
                            <strong>Memory Usage</strong><br>
                            <small>Target: <50MB</small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Test completed! Check results below.
                    </div>
                    
                    <a href="test_high_volume_web.php" class="btn btn-primary w-100">
                        <i class="fas fa-redo"></i> Run Another Test
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="fas fa-terminal"></i> CLI Version</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2">For even more comprehensive testing:</p>
                <code class="small">php test_high_volume_lookups.php</code>
                <p class="small mt-2 mb-0">
                    The CLI version tests up to 5000 IPs with detailed analysis.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if (!empty($testResults)): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Test Results</h5>
                    <small>Executed at: <?php echo $testResults['timestamp']; ?></small>
                </div>
                <div class="card-body">
                    <div class="live-results">
                        <?php echo $testResults['output']; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> High-Volume Testing Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-database"></i> Test Data Setup</h6>
                            <ul class="small">
                                <li>30 CIDR blocks (/24 networks)</li>
                                <li>20 traditional IP ranges</li>
                                <li>50 individual server IPs</li>
                                <li>11 different team assignments</li>
                                <li>Total: ~20,000 IP addresses</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-search"></i> Lookup Tests</h6>
                            <ul class="small">
                                <li>2000 individual IP lookups</li>
                                <li>1000 bulk IP processing</li>
                                <li>200 mixed format entries</li>
                                <li>Performance timing</li>
                                <li>Success rate calculation</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><i class="fas fa-tachometer-alt"></i> Performance Metrics</h6>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <h4 class="text-primary mb-0">2000+</h4>
                                <small class="text-muted">IP Lookups</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <h4 class="text-success mb-0">1000+</h4>
                                <small class="text-muted">Per Second</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <h4 class="text-info mb-0">200+</h4>
                                <small class="text-muted">Mixed Formats</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <h4 class="text-warning mb-0">90%+</h4>
                                <small class="text-muted">Success Rate</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-light mt-3">
                        <small>
                            <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> 
                            This test simulates real-world usage scenarios with large IP datasets 
                            and validates that your system can handle high-volume operations efficiently.
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function startTest() {
    // Disable form and show loading
    document.getElementById('testForm').style.display = 'none';
    
    // Show loading message
    const cardBody = document.querySelector('.card-body');
    cardBody.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="mt-3">Running High-Volume Test...</h5>
            <p class="text-muted">This may take 30-60 seconds. Please wait.</p>
            <div class="progress mt-3">
                <div class="progress-bar progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
    `;
    
    // Simulate progress
    let progress = 0;
    const progressBar = document.querySelector('.progress-bar');
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 95) progress = 95;
        progressBar.style.width = progress + '%';
        
        if (progress >= 95) {
            clearInterval(interval);
        }
    }, 1000);
}

// Auto-scroll to results when available
<?php if (!empty($testResults)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const results = document.querySelector('.live-results');
    if (results) {
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Auto-scroll to bottom of results
        setTimeout(() => {
            results.scrollTop = results.scrollHeight;
        }, 500);
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>