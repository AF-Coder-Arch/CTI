<?php
/**
 * High-Volume IP Lookup Testing Script
 * 
 * Tests IP-to-team mapping with large datasets and high lookup volumes
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Configuration
$TEST_PREFIX = 'HVTEST_';
$CLEANUP_AFTER = true;

// Performance tracking
$totalStartTime = microtime(true);
$memoryStart = memory_get_usage();

echo "🚀 HIGH-VOLUME IP LOOKUP TESTING SUITE\n";
echo "=====================================\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Memory Start: " . formatBytes($memoryStart) . "\n\n";

// Step 1: Setup comprehensive test data
echo "🔧 STEP 1: Setting up comprehensive test data...\n";
$setupStart = microtime(true);

$teams = [
    'Network Operations', 'Security Team', 'Development Team', 'QA Engineering',
    'Infrastructure Team', 'DevOps Team', 'Database Team', 'Frontend Team', 
    'Backend Team', 'Mobile Team', 'Analytics Team', 'Customer Support',
    'Sales Engineering', 'Marketing Tech', 'Cloud Operations', 'Monitoring Team',
    'Release Team', 'Platform Team', 'Data Science', 'Machine Learning',
    'Cyber Security', 'Network Security', 'Application Security', 'Compliance',
    'Site Reliability', 'Performance Team', 'Integration Team', 'API Team'
];

$totalRangesAdded = 0;

// Add 100 /24 CIDR blocks (25,600 IPs each)
echo "   Adding CIDR blocks...\n";
for ($i = 1; $i <= 100; $i++) {
    $cidr = "10.{$i}.0.0/24";
    $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
    if (addIpRangeFromCidr($pdo, $cidr, $team)) {
        $totalRangesAdded++;
    }
    if ($i % 25 === 0) echo "   ... {$i}/100 CIDR blocks added\n";
}

// Add 50 larger traditional ranges
echo "   Adding traditional IP ranges...\n";
for ($i = 1; $i <= 50; $i++) {
    $startIp = "192.168.{$i}.1";
    $endIp = "192.168.{$i}.250";
    $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
    if (addIpRange($pdo, $startIp, $endIp, $team)) {
        $totalRangesAdded++;
    }
}

// Add 30 ranges in 172.16.x.x space
echo "   Adding 172.16.x.x ranges...\n";
for ($i = 1; $i <= 30; $i++) {
    $startIp = "172.16.{$i}.10";
    $endIp = "172.16.{$i}.200";
    $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
    if (addIpRange($pdo, $startIp, $endIp, $team)) {
        $totalRangesAdded++;
    }
}

// Add individual server IPs across multiple ranges
echo "   Adding individual server IPs...\n";
$serverRanges = ['203.0.113', '198.51.100', '192.0.2', '198.18.0', '198.19.0'];
foreach ($serverRanges as $idx => $baseNetwork) {
    $serverIps = [];
    for ($i = 1; $i <= 100; $i++) {
        $serverIps[] = "{$baseNetwork}.{$i}";
    }
    $team = $TEST_PREFIX . 'Server Farm ' . ($idx + 1);
    $result = addIpListToTeam($pdo, implode(' ', $serverIps), $team);
    if ($result['success']) {
        $totalRangesAdded += $result['added'];
    }
}

$setupEnd = microtime(true);
$setupDuration = round($setupEnd - $setupStart, 3);

echo "✅ Setup Complete: {$totalRangesAdded} IP entries added in {$setupDuration}s\n\n";

// Step 2: High-Volume Individual IP Lookups
echo "🔍 STEP 2: Testing Individual IP Lookups (5000 IPs)...\n";
$lookupStart = microtime(true);

$testCount = 5000;
$foundCount = 0;
$testIps = [];

// Generate test IPs across all our ranges
for ($i = 1; $i <= $testCount; $i++) {
    if ($i <= 1000) {
        // Test IPs in 10.x.0.x ranges (CIDR blocks)
        $subnet = (($i - 1) % 100) + 1;
        $host = (($i - 1) % 254) + 1;
        $testIps[] = "10.{$subnet}.0.{$host}";
    } elseif ($i <= 2000) {
        // Test IPs in 192.168.x.x ranges
        $subnet = (($i - 1001) % 50) + 1;
        $host = (($i - 1001) % 250) + 1;
        $testIps[] = "192.168.{$subnet}.{$host}";
    } elseif ($i <= 3000) {
        // Test IPs in 172.16.x.x ranges
        $subnet = (($i - 2001) % 30) + 1;
        $host = (($i - 2001) % 191) + 10; // 10-200 range
        $testIps[] = "172.16.{$subnet}.{$host}";
    } elseif ($i <= 4000) {
        // Test server IPs
        $ranges = ['203.0.113', '198.51.100', '192.0.2', '198.18.0', '198.19.0'];
        $rangeIdx = ($i - 3001) % count($ranges);
        $host = (($i - 3001) % 100) + 1;
        $testIps[] = $ranges[$rangeIdx] . ".{$host}";
    } else {
        // Random mix from all ranges
        $allRanges = [
            '10.' . ((($i - 4001) % 100) + 1) . '.0.' . ((($i - 4001) % 254) + 1),
            '192.168.' . ((($i - 4001) % 50) + 1) . '.' . ((($i - 4001) % 250) + 1),
            '172.16.' . ((($i - 4001) % 30) + 1) . '.' . ((($i - 4001) % 191) + 10)
        ];
        $testIps[] = $allRanges[($i - 4001) % count($allRanges)];
    }
}

// Perform lookups with progress tracking
foreach ($testIps as $idx => $ip) {
    $team = getTeamByIp($pdo, $ip);
    if ($team !== null) {
        $foundCount++;
    }
    
    // Progress indicator
    if (($idx + 1) % 1000 === 0) {
        $progress = round((($idx + 1) / $testCount) * 100, 1);
        $currentCount = $idx + 1;
        echo "   ... {$progress}% complete ({$currentCount}/{$testCount})\n";
    }
}

$lookupEnd = microtime(true);
$lookupDuration = round($lookupEnd - $lookupStart, 3);
$lookupsPerSecond = round($testCount / $lookupDuration, 1);
$successRate = round(($foundCount / $testCount) * 100, 1);

echo "✅ Individual Lookups Complete:\n";
echo "   Time: {$lookupDuration}s\n";
echo "   Rate: {$lookupsPerSecond} lookups/sec\n";
echo "   Found: {$foundCount}/{$testCount} ({$successRate}%)\n\n";

// Step 3: Bulk IP Resolution Testing
echo "📦 STEP 3: Testing Bulk IP Resolution (2000 IPs in batches)...\n";
$bulkStart = microtime(true);

$batchSize = 100;
$totalBatches = 20;
$totalBulkProcessed = 0;
$totalBulkFound = 0;

for ($batch = 1; $batch <= $totalBatches; $batch++) {
    // Generate batch of IPs
    $batchIps = [];
    for ($i = 1; $i <= $batchSize; $i++) {
        $subnet = (($batch - 1) % 50) + 1;
        $host = $i;
        $batchIps[] = "10.{$subnet}.0.{$host}";
    }
    
    $ipString = implode(' ', $batchIps);
    $results = getTeamsByIpInput($pdo, $ipString);
    
    $totalBulkProcessed += count($results);
    foreach ($results as $result) {
        if (isset($result['team']) && $result['team'] !== null) {
            $totalBulkFound++;
        }
    }
    
    if ($batch % 5 === 0) {
        echo "   ... {$batch}/{$totalBatches} batches processed\n";
    }
}

$bulkEnd = microtime(true);
$bulkDuration = round($bulkEnd - $bulkStart, 3);
$bulkRate = round($totalBulkProcessed / $bulkDuration, 1);
$bulkSuccessRate = round(($totalBulkFound / $totalBulkProcessed) * 100, 1);

echo "✅ Bulk Resolution Complete:\n";
echo "   Time: {$bulkDuration}s\n";
echo "   Rate: {$bulkRate} IPs/sec\n";
echo "   Found: {$totalBulkFound}/{$totalBulkProcessed} ({$bulkSuccessRate}%)\n\n";

// Step 4: Mixed Format Processing
echo "🎭 STEP 4: Testing Mixed Format Processing (1000 entries)...\n";
$mixedStart = microtime(true);

$mixedEntries = [];

// 400 individual IPs
for ($i = 1; $i <= 400; $i++) {
    $subnet = (($i - 1) % 100) + 1;
    $host = (($i - 1) % 254) + 1;
    $mixedEntries[] = "10.{$subnet}.0.{$host}";
}

// 200 IP ranges
for ($i = 1; $i <= 200; $i++) {
    $subnet = (($i - 1) % 50) + 1;
    $start = ($i % 20) + 1;
    $end = $start + 5;
    $mixedEntries[] = "192.168.{$subnet}.{$start}-192.168.{$subnet}.{$end}";
}

// 200 CIDR blocks
for ($i = 1; $i <= 200; $i++) {
    $subnet = (($i - 1) % 30) + 1;
    $prefix = 28; // /28 = 16 IPs
    $mixedEntries[] = "172.16.{$subnet}.0/{$prefix}";
}

// 200 more individual IPs
for ($i = 1; $i <= 200; $i++) {
    $ranges = ['203.0.113', '198.51.100', '192.0.2'];
    $rangeIdx = ($i - 1) % count($ranges);
    $host = (($i - 1) % 100) + 1;
    $mixedEntries[] = $ranges[$rangeIdx] . ".{$host}";
}

// Process mixed input
$mixedInput = implode(' ', $mixedEntries);
$mixedResults = getTeamsByIpInput($pdo, $mixedInput);

$mixedEnd = microtime(true);
$mixedDuration = round($mixedEnd - $mixedStart, 3);

// Analyze results
$typeCounts = ['single' => 0, 'range' => 0, 'cidr' => 0, 'invalid' => 0];
$mixedTeamsFound = 0;

foreach ($mixedResults as $result) {
    if (isset($typeCounts[$result['type']])) {
        $typeCounts[$result['type']]++;
    }
    
    if (isset($result['team']) && $result['team'] !== null) {
        $mixedTeamsFound++;
    }
    if (isset($result['overlapping_teams']) && !empty($result['overlapping_teams'])) {
        $mixedTeamsFound++;
    }
}

$totalMixedEntries = count($mixedResults);
$mixedRate = round($totalMixedEntries / $mixedDuration, 1);

echo "✅ Mixed Format Processing Complete:\n";
echo "   Time: {$mixedDuration}s\n";
echo "   Rate: {$mixedRate} entries/sec\n";
echo "   Processed: {$totalMixedEntries} entries\n";
echo "   Types: Single({$typeCounts['single']}), Range({$typeCounts['range']}), CIDR({$typeCounts['cidr']}), Invalid({$typeCounts['invalid']})\n";
echo "   Teams Found: {$mixedTeamsFound}\n\n";

// Step 5: Database Statistics
echo "📊 STEP 5: Database Statistics...\n";
$statsStart = microtime(true);

$stmt = $pdo->query("SELECT COUNT(*) as total_ranges FROM ip_ranges WHERE team LIKE '{$TEST_PREFIX}%'");
$testRanges = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_ranges FROM ip_ranges");
$totalRanges = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(end_ip_long - start_ip_long + 1) as total_ips FROM ip_ranges WHERE team LIKE '{$TEST_PREFIX}%'");
$testIpCount = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(DISTINCT team) as unique_teams FROM ip_ranges WHERE team LIKE '{$TEST_PREFIX}%'");
$testTeams = $stmt->fetchColumn();

// Top teams by range count
$stmt = $pdo->query("SELECT team, COUNT(*) as range_count FROM ip_ranges WHERE team LIKE '{$TEST_PREFIX}%' GROUP BY team ORDER BY range_count DESC LIMIT 10");
$topTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statsEnd = microtime(true);
$statsDuration = round($statsEnd - $statsStart, 3);

echo "✅ Database Statistics:\n";
echo "   Test Ranges Added: " . number_format($testRanges) . "\n";
echo "   Total DB Ranges: " . number_format($totalRanges) . "\n";
echo "   Test IP Count: " . number_format($testIpCount) . "\n";
echo "   Test Teams: {$testTeams}\n";
echo "   Stats Query Time: {$statsDuration}s\n";

echo "\n   Top 10 Teams by Range Count:\n";
foreach ($topTeams as $team) {
    $shortName = str_replace($TEST_PREFIX, '', $team['team']);
    echo "      {$shortName}: {$team['range_count']} ranges\n";
}
echo "\n";

// Final Summary
$totalEndTime = microtime(true);
$totalDuration = round($totalEndTime - $totalStartTime, 3);
$memoryEnd = memory_get_usage();
$memoryPeak = memory_get_peak_usage();

echo "🎯 FINAL SUMMARY\n";
echo "================\n";
echo "Total Execution Time: {$totalDuration}s\n";
echo "Memory Usage: " . formatBytes($memoryEnd - $memoryStart) . "\n";
echo "Peak Memory: " . formatBytes($memoryPeak) . "\n";
echo "\nTest Results:\n";
echo "✅ Setup: {$totalRangesAdded} entries in {$setupDuration}s\n";
echo "✅ Individual Lookups: {$foundCount}/{$testCount} found ({$successRate}%) in {$lookupDuration}s\n";
echo "✅ Bulk Resolution: {$totalBulkFound}/{$totalBulkProcessed} found ({$bulkSuccessRate}%) in {$bulkDuration}s\n";
echo "✅ Mixed Processing: {$mixedTeamsFound} teams found from {$totalMixedEntries} entries in {$mixedDuration}s\n";

// Performance Assessment
echo "\n📈 PERFORMANCE ASSESSMENT\n";
echo "========================\n";

$overallScore = 0;
$maxScore = 0;

// Individual lookup performance
$maxScore += 25;
if ($lookupsPerSecond > 1000 && $successRate > 80) {
    $overallScore += 25;
    echo "✅ Individual Lookups: EXCELLENT ({$lookupsPerSecond}/sec, {$successRate}%)\n";
} elseif ($lookupsPerSecond > 500 && $successRate > 70) {
    $overallScore += 20;
    echo "✅ Individual Lookups: GOOD ({$lookupsPerSecond}/sec, {$successRate}%)\n";
} elseif ($lookupsPerSecond > 100 && $successRate > 50) {
    $overallScore += 15;
    echo "⚠️  Individual Lookups: FAIR ({$lookupsPerSecond}/sec, {$successRate}%)\n";
} else {
    $overallScore += 5;
    echo "❌ Individual Lookups: NEEDS IMPROVEMENT ({$lookupsPerSecond}/sec, {$successRate}%)\n";
}

// Bulk processing performance
$maxScore += 25;
if ($bulkRate > 500 && $bulkSuccessRate > 80) {
    $overallScore += 25;
    echo "✅ Bulk Processing: EXCELLENT ({$bulkRate}/sec, {$bulkSuccessRate}%)\n";
} elseif ($bulkRate > 200 && $bulkSuccessRate > 70) {
    $overallScore += 20;
    echo "✅ Bulk Processing: GOOD ({$bulkRate}/sec, {$bulkSuccessRate}%)\n";
} else {
    $overallScore += 15;
    echo "⚠️  Bulk Processing: FAIR ({$bulkRate}/sec, {$bulkSuccessRate}%)\n";
}

// Mixed format performance
$maxScore += 25;
if ($mixedRate > 100 && $mixedTeamsFound > 800) {
    $overallScore += 25;
    echo "✅ Mixed Format: EXCELLENT ({$mixedRate}/sec, {$mixedTeamsFound} teams found)\n";
} elseif ($mixedRate > 50 && $mixedTeamsFound > 500) {
    $overallScore += 20;
    echo "✅ Mixed Format: GOOD ({$mixedRate}/sec, {$mixedTeamsFound} teams found)\n";
} else {
    $overallScore += 15;
    echo "⚠️  Mixed Format: FAIR ({$mixedRate}/sec, {$mixedTeamsFound} teams found)\n";
}

// Memory efficiency
$maxScore += 25;
$memoryUsedMB = ($memoryPeak - $memoryStart) / 1024 / 1024;
if ($memoryUsedMB < 50) {
    $overallScore += 25;
    echo "✅ Memory Usage: EXCELLENT (" . round($memoryUsedMB, 1) . "MB)\n";
} elseif ($memoryUsedMB < 100) {
    $overallScore += 20;
    echo "✅ Memory Usage: GOOD (" . round($memoryUsedMB, 1) . "MB)\n";
} else {
    $overallScore += 15;
    echo "⚠️  Memory Usage: FAIR (" . round($memoryUsedMB, 1) . "MB)\n";
}

$overallPercentage = round(($overallScore / $maxScore) * 100, 1);
echo "\n🏆 OVERALL SCORE: {$overallScore}/{$maxScore} ({$overallPercentage}%)\n";

if ($overallPercentage >= 90) {
    echo "🎉 OUTSTANDING! Your IP mapping system is performing excellently.\n";
} elseif ($overallPercentage >= 75) {
    echo "✅ GREAT! Your IP mapping system is performing well.\n";
} elseif ($overallPercentage >= 60) {
    echo "⚠️  GOOD! Your IP mapping system is performing adequately.\n";
} else {
    echo "❌ NEEDS IMPROVEMENT! Consider optimizing your IP mapping system.\n";
}

// Cleanup
if ($CLEANUP_AFTER) {
    echo "\n🧹 CLEANUP\n";
    echo "==========\n";
    echo "Removing test data...\n";
    
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE ?");
    $stmt->execute([$TEST_PREFIX . '%']);
    $cleaned = $stmt->rowCount();
    
    echo "✅ Cleaned up: Removed {$cleaned} test entries\n";
} else {
    echo "\n⚠️  Test data kept in database (prefix: {$TEST_PREFIX})\n";
}

echo "\n🎉 HIGH-VOLUME TESTING COMPLETED!\n";

// Helper function
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>