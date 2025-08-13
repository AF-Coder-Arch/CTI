<?php
/**
 * Web-based IP Mapping Test Interface
 * 
 * Access via browser to run tests with a nice UI
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'IP Mapping Test Suite - CTI Tracker';
$testResults = [];

// Handle test execution
if (isset($_POST['run_tests'])) {
    $testTypes = $_POST['test_types'] ?? [];
    
    // Capture output from test script
    ob_start();
    
    // Include and run specific tests based on selection
    if (!empty($testTypes)) {
        foreach ($testTypes as $testType) {
            switch ($testType) {
                case 'basic_operations':
                    runBasicOperationsTests();
                    break;
                case 'lookup_tests':
                    runLookupTests();
                    break;
                case 'parsing_tests':
                    runParsingTests();
                    break;
                case 'error_handling':
                    runErrorHandlingTests();
                    break;
                case 'performance':
                    runPerformanceTests();
                    break;
            }
        }
    }
    
    $output = ob_get_clean();
    $testResults = ['output' => $output, 'timestamp' => date('Y-m-d H:i:s')];
}

function runBasicOperationsTests() {
    global $pdo;
    
    echo "<h4>🔧 Basic Operations Tests</h4>";
    
    // Test adding different types of ranges
    $tests = [
        [
            'name' => 'Add Traditional Range',
            'function' => function() use ($pdo) {
                return addIpRange($pdo, '192.168.50.1', '192.168.50.10', 'WEB_TEST_Traditional');
            }
        ],
        [
            'name' => 'Add CIDR Range',
            'function' => function() use ($pdo) {
                return addIpRangeFromCidr($pdo, '10.100.0.0/24', 'WEB_TEST_CIDR');
            }
        ],
        [
            'name' => 'Add IP List',
            'function' => function() use ($pdo) {
                $result = addIpListToTeam($pdo, '172.30.1.1 172.30.1.5 172.30.1.10', 'WEB_TEST_List');
                return $result['success'] && $result['added'] === 3;
            }
        ]
    ];
    
    runTestBatch($tests);
}

function runLookupTests() {
    global $pdo;
    
    echo "<h4>🔍 Lookup Tests</h4>";
    
    $tests = [
        [
            'name' => 'Single IP Lookup',
            'function' => function() use ($pdo) {
                $team = getTeamByIp($pdo, '192.168.50.5');
                return $team !== null;
            }
        ],
        [
            'name' => 'Multiple IP Lookup',
            'function' => function() use ($pdo) {
                $results = getTeamsByIpInput($pdo, '192.168.50.1 10.100.0.50 172.30.1.5');
                return count($results) === 3;
            }
        ],
        [
            'name' => 'CIDR Overlap Detection',
            'function' => function() use ($pdo) {
                $results = getTeamsByIpInput($pdo, '10.100.0.0/28');
                return !empty($results) && $results[0]['type'] === 'cidr';
            }
        ]
    ];
    
    runTestBatch($tests);
}

function runParsingTests() {
    echo "<h4>📝 Parsing Tests</h4>";
    
    $tests = [
        [
            'name' => 'Parse Single IP',
            'function' => function() {
                $result = parseIpInput('192.168.1.1');
                return count($result) === 1 && $result[0]['type'] === 'single';
            }
        ],
        [
            'name' => 'Parse CIDR',
            'function' => function() {
                $result = parseIpInput('192.168.1.0/24');
                return count($result) === 1 && $result[0]['type'] === 'cidr';
            }
        ],
        [
            'name' => 'Parse IP Range',
            'function' => function() {
                $result = parseIpInput('10.0.0.1-10.0.0.10');
                return count($result) === 1 && $result[0]['type'] === 'range';
            }
        ],
        [
            'name' => 'Parse Mixed Input',
            'function' => function() {
                $result = parseIpInput('192.168.1.1 10.0.0.0/24 172.16.0.1-172.16.0.5');
                return count($result) === 3;
            }
        ]
    ];
    
    runTestBatch($tests);
}

function runErrorHandlingTests() {
    global $pdo;
    
    echo "<h4>⚠️ Error Handling Tests</h4>";
    
    $tests = [
        [
            'name' => 'Invalid CIDR',
            'function' => function() use ($pdo) {
                return !addIpRangeFromCidr($pdo, '192.168.1.0/33', 'WEB_TEST_Invalid');
            }
        ],
        [
            'name' => 'Invalid IP Range Order',
            'function' => function() use ($pdo) {
                return !addIpRange($pdo, '192.168.1.100', '192.168.1.50', 'WEB_TEST_Invalid');
            }
        ],
        [
            'name' => 'Mixed Valid/Invalid IPs',
            'function' => function() use ($pdo) {
                $result = addIpListToTeam($pdo, '192.168.1.1 256.256.256.256 10.0.0.1', 'WEB_TEST_Mixed');
                return $result['success'] && $result['added'] === 2;
            }
        ]
    ];
    
    runTestBatch($tests);
}

function runPerformanceTests() {
    global $pdo;
    
    echo "<h4>⚡ Performance Tests</h4>";
    
    $tests = [
        [
            'name' => 'Large IP List Processing',
            'function' => function() use ($pdo) {
                $startTime = microtime(true);
                
                // Generate 100 IPs: 10.200.1.1 to 10.200.1.100
                $ips = [];
                for ($i = 1; $i <= 100; $i++) {
                    $ips[] = "10.200.1.{$i}";
                }
                
                $result = addIpListToTeam($pdo, implode(' ', $ips), 'WEB_TEST_Performance');
                $endTime = microtime(true);
                
                $duration = $endTime - $startTime;
                echo "<small>⏱️ Processed 100 IPs in " . round($duration, 3) . " seconds</small><br>";
                
                return $result['success'] && $result['added'] === 100 && $duration < 5.0;
            }
        ],
        [
            'name' => 'Bulk Lookup Performance',
            'function' => function() use ($pdo) {
                $startTime = microtime(true);
                
                // Test looking up 50 IPs
                $testIps = [];
                for ($i = 1; $i <= 50; $i++) {
                    $testIps[] = "10.200.1.{$i}";
                }
                
                $results = getTeamsByIpInput($pdo, implode(' ', $testIps));
                $endTime = microtime(true);
                
                $duration = $endTime - $startTime;
                echo "<small>⏱️ Looked up 50 IPs in " . round($duration, 3) . " seconds</small><br>";
                
                return count($results) === 50 && $duration < 2.0;
            }
        ]
    ];
    
    runTestBatch($tests);
}

function runTestBatch($tests) {
    foreach ($tests as $test) {
        echo "<div class='test-item'>";
        try {
            $result = $test['function']();
            if ($result) {
                echo "<span class='badge bg-success me-2'>✅ PASS</span>";
            } else {
                echo "<span class='badge bg-danger me-2'>❌ FAIL</span>";
            }
        } catch (Exception $e) {
            echo "<span class='badge bg-warning me-2'>💥 ERROR</span>";
            echo "<small class='text-muted'>({$e->getMessage()})</small>";
        }
        echo "<strong>{$test['name']}</strong>";
        echo "</div>";
    }
}

include 'includes/header.php';
?>

<style>
.test-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.test-item:last-child {
    border-bottom: none;
}
.test-output {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    white-space: pre-wrap;
    max-height: 500px;
    overflow-y: auto;
}
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-flask"></i> IP Mapping Test Suite</h1>
        <p class="text-muted">Comprehensive testing interface for all IP mapping functionality</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-play"></i> Run Tests</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Test Categories:</label>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="test_types[]" value="basic_operations" id="basic_ops" checked>
                            <label class="form-check-label" for="basic_ops">
                                <i class="fas fa-cogs text-primary"></i> Basic Operations
                            </label>
                            <small class="form-text text-muted">Add ranges, CIDR, IP lists</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="test_types[]" value="lookup_tests" id="lookup" checked>
                            <label class="form-check-label" for="lookup">
                                <i class="fas fa-search text-info"></i> Lookup Tests
                            </label>
                            <small class="form-text text-muted">IP resolution and team mapping</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="test_types[]" value="parsing_tests" id="parsing" checked>
                            <label class="form-check-label" for="parsing">
                                <i class="fas fa-code text-success"></i> Parsing Tests
                            </label>
                            <small class="form-text text-muted">Input format parsing</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="test_types[]" value="error_handling" id="errors" checked>
                            <label class="form-check-label" for="errors">
                                <i class="fas fa-exclamation-triangle text-warning"></i> Error Handling
                            </label>
                            <small class="form-text text-muted">Invalid input handling</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="test_types[]" value="performance" id="performance">
                            <label class="form-check-label" for="performance">
                                <i class="fas fa-tachometer-alt text-danger"></i> Performance Tests
                            </label>
                            <small class="form-text text-muted">Bulk operations timing</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="run_tests" class="btn btn-primary w-100">
                        <i class="fas fa-rocket"></i> Run Selected Tests
                    </button>
                </form>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="?cleanup=1" class="btn btn-warning btn-sm" onclick="return confirm('Remove all WEB_TEST_ entries from database?');">
                        <i class="fas fa-broom"></i> Cleanup Test Data
                    </a>
                    
                    <a href="test_ip_functions.php" class="btn btn-secondary btn-sm" target="_blank">
                        <i class="fas fa-terminal"></i> CLI Test Suite
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if (!empty($testResults)): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Test Results</h5>
                    <small>Executed at: <?php echo $testResults['timestamp']; ?></small>
                </div>
                <div class="card-body">
                    <div class="test-output">
                        <?php echo $testResults['output']; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Getting Started</h5>
                </div>
                <div class="card-body">
                    <h6>🧪 Test Categories:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-cogs text-primary"></i> <strong>Basic Operations:</strong> Tests adding IP ranges using all three methods</li>
                        <li><i class="fas fa-search text-info"></i> <strong>Lookup Tests:</strong> Tests IP-to-team resolution functionality</li>
                        <li><i class="fas fa-code text-success"></i> <strong>Parsing Tests:</strong> Tests input format parsing (CIDR, ranges, etc.)</li>
                        <li><i class="fas fa-exclamation-triangle text-warning"></i> <strong>Error Handling:</strong> Tests invalid input handling</li>
                        <li><i class="fas fa-tachometer-alt text-danger"></i> <strong>Performance Tests:</strong> Tests bulk operations and timing</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>📋 Manual Testing Samples:</h6>
                    <p>You can also test manually using these samples:</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-plus"></i> Admin Panel Tests:</h6>
                            <small>
                                <strong>IP Range:</strong> 192.168.99.1 to 192.168.99.50<br>
                                <strong>CIDR:</strong> 10.99.0.0/24<br>
                                <strong>IP List:</strong> 172.31.1.1 172.31.1.5 172.31.1.10<br>
                                <strong>Mixed List:</strong> 203.0.113.1 203.0.113.5-203.0.113.10
                            </small>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-search"></i> IP Lookup Tests:</h6>
                            <small>
                                <strong>Single:</strong> 192.168.99.25<br>
                                <strong>Multiple:</strong> 192.168.99.1 10.99.0.100 172.31.1.5<br>
                                <strong>CIDR:</strong> 10.99.0.0/28<br>
                                <strong>Mixed:</strong> 192.168.99.1, 10.99.0.0/25, 172.31.1.1-172.31.1.3
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Handle cleanup
if (isset($_GET['cleanup'])) {
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE 'WEB_TEST_%'");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    echo "<script>alert('Cleanup completed. Removed {$deleted} test entries.'); window.location.href = 'test_web_interface.php';</script>";
}
?>

<script>
// Auto-scroll to results when available
<?php if (!empty($testResults)): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.test-output').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>

// Select/deselect all checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="test_types[]"]');
    
    // Add a "Select All" functionality
    const form = document.querySelector('form');
    const selectAllBtn = document.createElement('button');
    selectAllBtn.type = 'button';
    selectAllBtn.className = 'btn btn-sm btn-outline-secondary mb-2';
    selectAllBtn.innerHTML = '<i class="fas fa-check-double"></i> Select All';
    selectAllBtn.onclick = function() {
        checkboxes.forEach(cb => cb.checked = true);
    };
    
    const deselectAllBtn = document.createElement('button');
    deselectAllBtn.type = 'button';
    deselectAllBtn.className = 'btn btn-sm btn-outline-secondary mb-2 ms-2';
    deselectAllBtn.innerHTML = '<i class="fas fa-times"></i> Deselect All';
    deselectAllBtn.onclick = function() {
        checkboxes.forEach(cb => cb.checked = false);
    };
    
    form.querySelector('.mb-3').insertBefore(selectAllBtn, form.querySelector('.form-check'));
    form.querySelector('.mb-3').insertBefore(deselectAllBtn, form.querySelector('.form-check'));
});
</script>

<?php include 'includes/footer.php'; ?>