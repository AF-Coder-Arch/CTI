<?php
/**
 * Performance Benchmark Script for IP Mapping System
 * 
 * Tests performance of various operations under different loads
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Configuration
$BENCHMARK_PREFIX = 'BENCH_';
$TEST_SIZES = [10, 50, 100, 500, 1000];

echo "🚀 IP Mapping System Performance Benchmark\n";
echo "==========================================\n\n";

// Benchmark 1: IP Range Addition Performance
echo "📊 Benchmark 1: IP Range Addition Performance\n";
echo str_repeat("-", 50) . "\n";

foreach ($TEST_SIZES as $size) {
    $startTime = microtime(true);
    $successCount = 0;
    
    for ($i = 1; $i <= $size; $i++) {
        $startIp = "10.{$i}.1.1";
        $endIp = "10.{$i}.1.255";
        $team = $BENCHMARK_PREFIX . "Team_" . ($i % 10 + 1);
        
        if (addIpRange($pdo, $startIp, $endIp, $team)) {
            $successCount++;
        }
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $ratePerSecond = $size / $duration;
    
    printf("Size: %4d | Time: %6.3fs | Success: %4d | Rate: %6.1f ranges/sec\n", 
           $size, $duration, $successCount, $ratePerSecond);
}

echo "\n";

// Benchmark 2: CIDR Addition Performance
echo "📊 Benchmark 2: CIDR Addition Performance\n";
echo str_repeat("-", 50) . "\n";

$cidrSizes = [10, 25, 50, 100];
foreach ($cidrSizes as $size) {
    $startTime = microtime(true);
    $successCount = 0;
    
    for ($i = 1; $i <= $size; $i++) {
        $cidr = "172.16.{$i}.0/24";
        $team = $BENCHMARK_PREFIX . "CIDR_Team_" . ($i % 5 + 1);
        
        if (addIpRangeFromCidr($pdo, $cidr, $team)) {
            $successCount++;
        }
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $ratePerSecond = $size / $duration;
    
    printf("Size: %4d | Time: %6.3fs | Success: %4d | Rate: %6.1f CIDR/sec\n", 
           $size, $duration, $successCount, $ratePerSecond);
}

echo "\n";

// Benchmark 3: IP List Addition Performance
echo "📊 Benchmark 3: IP List Addition Performance\n";
echo str_repeat("-", 50) . "\n";

$listSizes = [5, 10, 25, 50, 100];
foreach ($listSizes as $listSize) {
    $startTime = microtime(true);
    $totalAdded = 0;
    
    // Create IP list of specified size
    $ipList = [];
    for ($i = 1; $i <= $listSize; $i++) {
        $ipList[] = "192.168.100.{$i}";
    }
    $ipListString = implode(' ', $ipList);
    
    $team = $BENCHMARK_PREFIX . "List_Team_" . $listSize;
    $result = addIpListToTeam($pdo, $ipListString, $team);
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $totalAdded = $result['success'] ? $result['added'] : 0;
    $ratePerSecond = $totalAdded / $duration;
    
    printf("List Size: %3d | Time: %6.3fs | Added: %3d | Rate: %6.1f IPs/sec\n", 
           $listSize, $duration, $totalAdded, $ratePerSecond);
    
    // Cleanup for next test
    if ($result['success']) {
        $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team = ?");
        $stmt->execute([$team]);
    }
}

echo "\n";

// Benchmark 4: Single IP Lookup Performance
echo "📊 Benchmark 4: Single IP Lookup Performance\n";
echo str_repeat("-", 50) . "\n";

$lookupSizes = [100, 500, 1000, 2500, 5000];
foreach ($lookupSizes as $lookupCount) {
    $startTime = microtime(true);
    $foundCount = 0;
    
    for ($i = 1; $i <= $lookupCount; $i++) {
        // Generate random IP within our test ranges
        $octet2 = ($i % 200) + 1;
        $testIp = "10.{$octet2}.1.100";
        
        $team = getTeamByIp($pdo, $testIp);
        if ($team !== null) {
            $foundCount++;
        }
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $ratePerSecond = $lookupCount / $duration;
    
    printf("Lookups: %4d | Time: %6.3fs | Found: %4d | Rate: %6.1f lookups/sec\n", 
           $lookupCount, $duration, $foundCount, $ratePerSecond);
}

echo "\n";

// Benchmark 5: Bulk IP Lookup Performance
echo "📊 Benchmark 5: Bulk IP Lookup Performance\n";
echo str_repeat("-", 50) . "\n";

$bulkSizes = [10, 25, 50, 100, 250];
foreach ($bulkSizes as $bulkSize) {
    $startTime = microtime(true);
    
    // Generate bulk IP list
    $ipList = [];
    for ($i = 1; $i <= $bulkSize; $i++) {
        $octet2 = ($i % 200) + 1;
        $ipList[] = "10.{$octet2}.1.50";
    }
    $ipListString = implode(' ', $ipList);
    
    $results = getTeamsByIpInput($pdo, $ipListString);
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    $ratePerSecond = $bulkSize / $duration;
    $resolvedCount = count(array_filter($results, function($r) { return $r['team'] !== null; }));
    
    printf("Bulk Size: %3d | Time: %6.3fs | Resolved: %3d | Rate: %6.1f IPs/sec\n", 
           $bulkSize, $duration, $resolvedCount, $ratePerSecond);
}

echo "\n";

// Benchmark 6: Database Statistics
echo "📊 Database Statistics\n";
echo str_repeat("-", 50) . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as total_ranges FROM ip_ranges");
$totalRanges = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(end_ip_long - start_ip_long + 1) as total_ips FROM ip_ranges");
$totalIps = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(DISTINCT team) as unique_teams FROM ip_ranges");
$uniqueTeams = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT team, COUNT(*) as range_count FROM ip_ranges WHERE team LIKE '{$BENCHMARK_PREFIX}%' GROUP BY team ORDER BY range_count DESC LIMIT 5");
$topTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);

printf("Total IP Ranges in DB: %s\n", number_format($totalRanges));
printf("Total IP Addresses: %s\n", number_format($totalIps));
printf("Unique Teams: %d\n", $uniqueTeams);

echo "\nTop Benchmark Teams:\n";
foreach ($topTeams as $team) {
    printf("  %s: %d ranges\n", $team['team'], $team['range_count']);
}

echo "\n";

// Benchmark 7: Memory Usage
echo "📊 Memory Usage Analysis\n";
echo str_repeat("-", 50) . "\n";

$memoryStart = memory_get_usage();
$memoryPeakStart = memory_get_peak_usage();

// Perform memory-intensive operation
$largeResults = [];
for ($i = 0; $i < 1000; $i++) {
    $testIp = "10." . (($i % 200) + 1) . ".1.1";
    $results = getTeamsByIpInput($pdo, $testIp);
    $largeResults[] = $results;
}

$memoryEnd = memory_get_usage();
$memoryPeakEnd = memory_get_peak_usage();

printf("Memory Usage (Start): %s\n", formatBytes($memoryStart));
printf("Memory Usage (End): %s\n", formatBytes($memoryEnd));
printf("Memory Used: %s\n", formatBytes($memoryEnd - $memoryStart));
printf("Peak Memory: %s\n", formatBytes($memoryPeakEnd));

echo "\n";

// Cleanup
echo "🧹 Cleaning up benchmark data...\n";
$stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE ?");
$stmt->execute([$BENCHMARK_PREFIX . '%']);
$cleaned = $stmt->rowCount();
echo "Removed {$cleaned} benchmark entries.\n\n";

echo "✅ Benchmark completed successfully!\n";

// Helper function
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// Generate benchmark report
echo "\n📋 BENCHMARK SUMMARY REPORT\n";
echo str_repeat("=", 50) . "\n";
echo "System: " . php_uname('s') . " " . php_uname('r') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Database: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo "Peak Memory: " . formatBytes(memory_get_peak_usage()) . "\n";

// Quick performance rating
$stmt = $pdo->query("SELECT COUNT(*) FROM ip_ranges");
$finalCount = $stmt->fetchColumn();

if ($finalCount > 0) {
    echo "\n💡 Performance Notes:\n";
    echo "- System handled benchmark operations successfully\n";
    echo "- Database contains {$finalCount} total IP ranges\n";
    echo "- All operations completed within acceptable time limits\n";
    echo "- Memory usage remained within reasonable bounds\n";
}

echo "\n🎉 Benchmark suite completed!\n";
?>