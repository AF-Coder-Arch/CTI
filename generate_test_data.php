<?php
/**
 * Test Data Generator for IP Mapping System
 * 
 * This script generates various test datasets for thorough testing
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Test Data Generator - CTI Tracker';

// Configuration
$datasets = [
    'small' => [
        'name' => 'Small Dataset',
        'description' => '20 ranges, mixed types, good for basic testing',
        'ranges' => 20
    ],
    'medium' => [
        'name' => 'Medium Dataset', 
        'description' => '100 ranges, comprehensive coverage, realistic scenario',
        'ranges' => 100
    ],
    'large' => [
        'name' => 'Large Dataset',
        'description' => '500 ranges, stress testing, performance evaluation',
        'ranges' => 500
    ],
    'edge_cases' => [
        'name' => 'Edge Cases',
        'description' => 'Special cases: single IPs, large ranges, overlaps',
        'ranges' => 'varied'
    ]
];

$teams = [
    'Security Operations', 'Network Engineering', 'Development Team', 
    'QA Engineering', 'Infrastructure', 'DevOps', 'Database Team',
    'Frontend Team', 'Backend Team', 'Mobile Team', 'Analytics',
    'Customer Support', 'Sales Engineering', 'Marketing Tech'
];

// Handle data generation
if (isset($_POST['generate'])) {
    $datasetType = $_POST['dataset_type'];
    $prefix = isset($_POST['use_prefix']) ? 'TESTDATA_' : '';
    
    $results = generateDataset($datasetType, $prefix);
    $message = $results['message'];
    $messageType = $results['success'] ? 'success' : 'danger';
}

function generateDataset($type, $prefix = '') {
    global $pdo, $teams;
    
    $generated = 0;
    $errors = [];
    
    try {
        switch ($type) {
            case 'small':
                $generated = generateSmallDataset($pdo, $teams, $prefix);
                break;
            case 'medium':
                $generated = generateMediumDataset($pdo, $teams, $prefix);
                break;
            case 'large':
                $generated = generateLargeDataset($pdo, $teams, $prefix);
                break;
            case 'edge_cases':
                $generated = generateEdgeCasesDataset($pdo, $teams, $prefix);
                break;
            default:
                throw new Exception("Unknown dataset type: {$type}");
        }
        
        return [
            'success' => true,
            'message' => "Successfully generated {$generated} IP range entries for dataset '{$type}'"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Error generating dataset: " . $e->getMessage()
        ];
    }
}

function generateSmallDataset($pdo, $teams, $prefix) {
    $generated = 0;
    
    // 10 traditional ranges
    $ranges = [
        ['192.168.1.0', '192.168.1.255'],
        ['192.168.2.0', '192.168.2.255'],
        ['10.10.1.0', '10.10.1.255'],
        ['10.10.2.0', '10.10.2.255'],
        ['172.16.1.0', '172.16.1.255'],
        ['172.16.2.0', '172.16.2.255'],
        ['192.168.100.1', '192.168.100.50'],
        ['192.168.101.1', '192.168.101.50'],
        ['10.50.1.1', '10.50.1.100'],
        ['10.50.2.1', '10.50.2.100']
    ];
    
    foreach ($ranges as $range) {
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRange($pdo, $range[0], $range[1], $team)) {
            $generated++;
        }
    }
    
    // 5 CIDR ranges
    $cidrs = [
        '172.20.0.0/24',
        '172.21.0.0/24', 
        '10.100.0.0/24',
        '10.101.0.0/24',
        '192.168.200.0/28'
    ];
    
    foreach ($cidrs as $cidr) {
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRangeFromCidr($pdo, $cidr, $team)) {
            $generated++;
        }
    }
    
    // 5 individual IP lists
    $ipLists = [
        '203.0.113.1 203.0.113.5 203.0.113.10',
        '198.51.100.1 198.51.100.10 198.51.100.20',
        '10.200.1.1 10.200.1.5 10.200.1.10',
        '172.30.1.1 172.30.1.2 172.30.1.3',
        '192.0.2.1 192.0.2.10 192.0.2.20'
    ];
    
    foreach ($ipLists as $ipList) {
        $team = $prefix . $teams[array_rand($teams)];
        $result = addIpListToTeam($pdo, $ipList, $team);
        if ($result['success']) {
            $generated += $result['added'];
        }
    }
    
    return $generated;
}

function generateMediumDataset($pdo, $teams, $prefix) {
    $generated = 0;
    
    // 50 /24 networks across different ranges
    for ($i = 1; $i <= 50; $i++) {
        $network = "10.{$i}.0.0/24";
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRangeFromCidr($pdo, $network, $team)) {
            $generated++;
        }
    }
    
    // 30 smaller ranges in 192.168.x.x
    for ($i = 1; $i <= 30; $i++) {
        $startIp = "192.168.{$i}.1";
        $endIp = "192.168.{$i}.100";
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRange($pdo, $startIp, $endIp, $team)) {
            $generated++;
        }
    }
    
    // 20 individual server IPs
    for ($i = 1; $i <= 20; $i++) {
        $ip = "172.16.100.{$i}";
        $team = $prefix . $teams[array_rand($teams)] . ' Servers';
        if (addIpRange($pdo, $ip, $ip, $team)) {
            $generated++;
        }
    }
    
    return $generated;
}

function generateLargeDataset($pdo, $teams, $prefix) {
    $generated = 0;
    
    // 200 /24 networks
    for ($subnet = 1; $subnet <= 200; $subnet++) {
        $octet2 = ($subnet - 1) % 255 + 1;
        $octet1 = intval(($subnet - 1) / 255) + 1;
        $network = "10.{$octet1}.{$octet2}.0/24";
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRangeFromCidr($pdo, $network, $team)) {
            $generated++;
        }
    }
    
    // 150 /16 subnets in 172.x range
    for ($i = 16; $i <= 31; $i++) {
        for ($j = 0; $j < 10 && $generated < 350; $j++) {
            $network = "172.{$i}.{$j}.0/24";
            $team = $prefix . $teams[array_rand($teams)];
            if (addIpRangeFromCidr($pdo, $network, $team)) {
                $generated++;
            }
        }
    }
    
    // 150 random ranges
    for ($i = 0; $i < 150; $i++) {
        $baseOctet = rand(1, 254);
        $startIp = "192.168.{$baseOctet}.1";
        $endIp = "192.168.{$baseOctet}." . rand(50, 254);
        $team = $prefix . $teams[array_rand($teams)];
        if (addIpRange($pdo, $startIp, $endIp, $team)) {
            $generated++;
        }
    }
    
    return $generated;
}

function generateEdgeCasesDataset($pdo, $teams, $prefix) {
    $generated = 0;
    
    // Single IP addresses
    $singleIps = [
        '8.8.8.8', '8.8.4.4', '1.1.1.1', '1.0.0.1', 
        '208.67.222.222', '208.67.220.220'
    ];
    
    foreach ($singleIps as $ip) {
        $team = $prefix . 'Public DNS Servers';
        if (addIpRange($pdo, $ip, $ip, $team)) {
            $generated++;
        }
    }
    
    // Very large ranges (whole /16 networks)
    $largeCidrs = [
        '10.0.0.0/16',    // 65,536 IPs
        '172.16.0.0/16',  // 65,536 IPs
        '192.168.0.0/16'  // 65,536 IPs
    ];
    
    foreach ($largeCidrs as $cidr) {
        $team = $prefix . 'Large Network ' . explode('.', explode('/', $cidr)[0])[0];
        if (addIpRangeFromCidr($pdo, $cidr, $team)) {
            $generated++;
        }
    }
    
    // Overlapping ranges (for testing conflict detection)
    $overlappingRanges = [
        ['192.168.50.1', '192.168.50.100', 'Team A'],
        ['192.168.50.50', '192.168.50.150', 'Team B'],
        ['10.20.0.1', '10.20.0.255', 'Team C'],
        ['10.20.0.100', '10.20.1.50', 'Team D']
    ];
    
    foreach ($overlappingRanges as $range) {
        $team = $prefix . $range[2];
        if (addIpRange($pdo, $range[0], $range[1], $team)) {
            $generated++;
        }
    }
    
    // Very small ranges (2-5 IPs)
    $smallRanges = [
        ['203.0.113.10', '203.0.113.12'],
        ['198.51.100.20', '198.51.100.23'],
        ['192.0.2.100', '192.0.2.104']
    ];
    
    foreach ($smallRanges as $range) {
        $team = $prefix . 'Small Range Team';
        if (addIpRange($pdo, $range[0], $range[1], $team)) {
            $generated++;
        }
    }
    
    // Sequential IP lists (testing expansion)
    $sequentialLists = [
        '10.99.1.1-10.99.1.20',
        '172.99.1.1-172.99.1.15', 
        '192.168.99.1-192.168.99.25'
    ];
    
    foreach ($sequentialLists as $list) {
        $team = $prefix . 'Sequential Team';
        $result = addIpListToTeam($pdo, $list, $team);
        if ($result['success']) {
            $generated += $result['added'];
        }
    }
    
    return $generated;
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-database"></i> Test Data Generator</h1>
        <p class="text-muted">Generate comprehensive test datasets for IP mapping system validation</p>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Generate Test Data</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Dataset Type:</label>
                        
                        <?php foreach ($datasets as $key => $dataset): ?>
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="dataset_type" value="<?php echo $key; ?>" id="<?php echo $key; ?>">
                                    <label class="form-check-label" for="<?php echo $key; ?>">
                                        <strong><?php echo $dataset['name']; ?></strong>
                                        <br><small class="text-muted"><?php echo $dataset['description']; ?></small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="use_prefix" value="1" id="use_prefix" checked>
                            <label class="form-check-label" for="use_prefix">
                                Use "TESTDATA_" prefix for easy cleanup
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="generate" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Generate Test Data
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-broom"></i> Cleanup Tools</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?cleanup_testdata=1" class="btn btn-warning btn-sm" onclick="return confirm('Remove all TESTDATA_ entries?');">
                        <i class="fas fa-trash"></i> Remove Test Data
                    </a>
                    <a href="?cleanup_all=1" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ DANGER: Remove ALL IP ranges from database?');">
                        <i class="fas fa-exclamation-triangle"></i> Remove ALL Data
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Current Database Stats</h5>
            </div>
            <div class="card-body">
                <?php
                $stats = getDatabaseStats($pdo);
                ?>
                <div class="row text-center">
                    <div class="col-4">
                        <h3 class="text-primary"><?php echo number_format($stats['total_ranges']); ?></h3>
                        <small class="text-muted">Total Ranges</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-success"><?php echo number_format($stats['total_ips']); ?></h3>
                        <small class="text-muted">Total IPs</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-info"><?php echo $stats['unique_teams']; ?></h3>
                        <small class="text-muted">Unique Teams</small>
                    </div>
                </div>
                
                <hr>
                
                <h6>Team Distribution:</h6>
                <?php if (!empty($stats['team_counts'])): ?>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($stats['team_counts'] as $team): ?>
                            <div class="d-flex justify-content-between">
                                <span class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($team['team']); ?>">
                                    <?php echo htmlspecialchars($team['team']); ?>
                                </span>
                                <span class="badge bg-secondary"><?php echo $team['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No data in database</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Testing Tips</h6>
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li><strong>Small Dataset:</strong> Perfect for manual testing and validation</li>
                    <li><strong>Medium Dataset:</strong> Good for realistic usage scenarios</li>
                    <li><strong>Large Dataset:</strong> Stress testing and performance evaluation</li>
                    <li><strong>Edge Cases:</strong> Test unusual scenarios and error handling</li>
                    <li>Always use the prefix option for easy cleanup</li>
                    <li>Run tests after generating data to validate functionality</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Handle cleanup actions
if (isset($_GET['cleanup_testdata'])) {
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE 'TESTDATA_%'");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "<script>alert('Removed {$deleted} test data entries.'); window.location.href = 'generate_test_data.php';</script>";
}

if (isset($_GET['cleanup_all'])) {
    $stmt = $pdo->query("DELETE FROM ip_ranges");
    $deleted = $stmt->rowCount();
    echo "<script>alert('Removed ALL {$deleted} entries from database.'); window.location.href = 'generate_test_data.php';</script>";
}

function getDatabaseStats($pdo) {
    // Total ranges
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ip_ranges");
    $totalRanges = $stmt->fetchColumn();
    
    // Total IPs (approximate)
    $stmt = $pdo->query("SELECT SUM(end_ip_long - start_ip_long + 1) as total_ips FROM ip_ranges");
    $totalIps = $stmt->fetchColumn() ?: 0;
    
    // Unique teams
    $stmt = $pdo->query("SELECT COUNT(DISTINCT team) as unique_teams FROM ip_ranges");
    $uniqueTeams = $stmt->fetchColumn();
    
    // Team counts
    $stmt = $pdo->query("SELECT team, COUNT(*) as count FROM ip_ranges GROUP BY team ORDER BY count DESC LIMIT 10");
    $teamCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_ranges' => $totalRanges,
        'total_ips' => $totalIps,
        'unique_teams' => $uniqueTeams,
        'team_counts' => $teamCounts
    ];
}

include 'includes/footer.php';
?>