<?php
/**
 * Sample Input Tester
 * 
 * Tests all the sample inputs provided in the documentation
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Sample Input Tester - CTI Tracker';

echo "🧪 Testing All Sample Inputs\n";
echo "============================\n\n";

$allTests = [];
$passedTests = 0;
$failedTests = 0;

function testSample($name, $testFunction, &$allTests, &$passedTests, &$failedTests) {
    echo "Testing: {$name}... ";
    
    try {
        $result = $testFunction();
        if ($result['success']) {
            echo "✅ PASS";
            if (isset($result['details'])) {
                echo " - {$result['details']}";
            }
            echo "\n";
            $passedTests++;
            $allTests[] = ['name' => $name, 'status' => 'PASS'];
        } else {
            echo "❌ FAIL - {$result['error']}\n";
            $failedTests++;
            $allTests[] = ['name' => $name, 'status' => 'FAIL', 'error' => $result['error']];
        }
    } catch (Exception $e) {
        echo "💥 EXCEPTION - {$e->getMessage()}\n";
        $failedTests++;
        $allTests[] = ['name' => $name, 'status' => 'EXCEPTION', 'error' => $e->getMessage()];
    }
}

// ADMIN PANEL SAMPLES
echo "📋 ADMIN PANEL SAMPLE TESTS\n";
echo str_repeat("-", 40) . "\n";

// 1. IP Range Mode Samples
testSample("IP Range: 192.168.100.1 to 192.168.100.50", function() {
    global $pdo;
    return ['success' => addIpRange($pdo, '192.168.100.1', '192.168.100.50', 'SAMPLE_Network Operations Team')];
}, $allTests, $passedTests, $failedTests);

testSample("IP Range: 10.50.0.1 to 10.50.0.255", function() {
    global $pdo;
    return ['success' => addIpRange($pdo, '10.50.0.1', '10.50.0.255', 'SAMPLE_Development Team')];
}, $allTests, $passedTests, $failedTests);

// 2. CIDR Mode Samples
testSample("CIDR: 172.16.0.0/24", function() {
    global $pdo;
    return ['success' => addIpRangeFromCidr($pdo, '172.16.0.0/24', 'SAMPLE_Security Team')];
}, $allTests, $passedTests, $failedTests);

testSample("CIDR: 10.0.0.0/16", function() {
    global $pdo;
    return ['success' => addIpRangeFromCidr($pdo, '10.0.0.0/16', 'SAMPLE_Infrastructure Team')];
}, $allTests, $passedTests, $failedTests);

testSample("CIDR: 192.168.1.0/28", function() {
    global $pdo;
    return ['success' => addIpRangeFromCidr($pdo, '192.168.1.0/28', 'SAMPLE_QA Team')];
}, $allTests, $passedTests, $failedTests);

// 3. IP List Mode Samples
testSample("IP List: 10.12.2.2 10.90.10.100 10.23.211.10", function() {
    global $pdo;
    $result = addIpListToTeam($pdo, '10.12.2.2 10.90.10.100 10.23.211.10', 'SAMPLE_DevOps Team');
    return [
        'success' => $result['success'] && $result['added'] === 3,
        'details' => "Added {$result['added']} IPs"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("IP List: 203.0.113.1 203.0.113.5 203.0.113.10 203.0.113.15", function() {
    global $pdo;
    $result = addIpListToTeam($pdo, '203.0.113.1 203.0.113.5 203.0.113.10 203.0.113.15', 'SAMPLE_External Services Team');
    return [
        'success' => $result['success'] && $result['added'] === 4,
        'details' => "Added {$result['added']} IPs"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("IP List: 172.20.1.5, 172.20.1.10, 172.20.1.15", function() {
    global $pdo;
    $result = addIpListToTeam($pdo, '172.20.1.5, 172.20.1.10, 172.20.1.15', 'SAMPLE_Database Team');
    return [
        'success' => $result['success'] && $result['added'] === 3,
        'details' => "Added {$result['added']} IPs"
    ];
}, $allTests, $passedTests, $failedTests);

// 4. Range Expansion Sample
testSample("IP List with Range: 10.10.10.10-10.10.10.20", function() {
    global $pdo;
    $result = addIpListToTeam($pdo, '10.10.10.10-10.10.10.20', 'SAMPLE_Range Expansion Team');
    return [
        'success' => $result['success'] && $result['added'] === 11,
        'details' => "Expanded range to {$result['added']} individual IPs"
    ];
}, $allTests, $passedTests, $failedTests);

echo "\n";

// IP LOOKUP SAMPLES
echo "🔍 IP LOOKUP SAMPLE TESTS\n";
echo str_repeat("-", 40) . "\n";

// Wait a moment to ensure data is committed
sleep(1);

// Single IP Tests
testSample("Single IP: 192.168.100.25", function() {
    global $pdo;
    $team = getTeamByIp($pdo, '192.168.100.25');
    return [
        'success' => $team !== null,
        'details' => $team ? "Resolved to: {$team}" : "No team found"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Single IP: 10.50.0.100", function() {
    global $pdo;
    $team = getTeamByIp($pdo, '10.50.0.100');
    return [
        'success' => $team !== null,
        'details' => $team ? "Resolved to: {$team}" : "No team found"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Single IP: 172.16.0.50", function() {
    global $pdo;
    $team = getTeamByIp($pdo, '172.16.0.50');
    return [
        'success' => $team !== null,
        'details' => $team ? "Resolved to: {$team}" : "No team found"
    ];
}, $allTests, $passedTests, $failedTests);

// Space-separated Lists
testSample("Space List: 192.168.100.1 10.50.0.1 172.16.0.1", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.1 10.50.0.1 172.16.0.1');
    $foundTeams = array_filter($results, function($r) { return $r['team'] !== null; });
    return [
        'success' => count($foundTeams) >= 2,
        'details' => "Found teams for " . count($foundTeams) . "/3 IPs"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Space List: 10.12.2.2 10.90.10.100 10.23.211.10 203.0.113.1", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '10.12.2.2 10.90.10.100 10.23.211.10 203.0.113.1');
    $foundTeams = array_filter($results, function($r) { return $r['team'] !== null; });
    return [
        'success' => count($foundTeams) === 4,
        'details' => "Found teams for " . count($foundTeams) . "/4 IPs"
    ];
}, $allTests, $passedTests, $failedTests);

// Comma-separated Lists
testSample("Comma List: 192.168.100.10, 10.50.0.50, 172.16.0.100", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.10, 10.50.0.50, 172.16.0.100');
    $foundTeams = array_filter($results, function($r) { return $r['team'] !== null; });
    return [
        'success' => count($foundTeams) >= 2,
        'details' => "Found teams for " . count($foundTeams) . "/3 IPs"
    ];
}, $allTests, $passedTests, $failedTests);

// CIDR Notation Tests
testSample("CIDR Lookup: 192.168.100.0/24", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.0/24');
    return [
        'success' => !empty($results) && $results[0]['type'] === 'cidr',
        'details' => !empty($results) ? "CIDR parsed successfully" : "CIDR parsing failed"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Multiple CIDR: 10.50.0.0/24 172.16.0.0/24", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '10.50.0.0/24 172.16.0.0/24');
    $cidrResults = array_filter($results, function($r) { return $r['type'] === 'cidr'; });
    return [
        'success' => count($cidrResults) === 2,
        'details' => "Parsed " . count($cidrResults) . "/2 CIDR blocks"
    ];
}, $allTests, $passedTests, $failedTests);

// IP Range Tests
testSample("Range Lookup: 192.168.100.1-192.168.100.50", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.1-192.168.100.50');
    return [
        'success' => !empty($results) && $results[0]['type'] === 'range',
        'details' => !empty($results) ? "Range parsed successfully" : "Range parsing failed"
    ];
}, $allTests, $passedTests, $failedTests);

// Complex Mixed Input
testSample("Complex Mixed: 192.168.100.25 10.50.0.0/24 172.16.0.1-172.16.0.50 10.12.2.2", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.25 10.50.0.0/24 172.16.0.1-172.16.0.50 10.12.2.2');
    return [
        'success' => count($results) === 4,
        'details' => "Parsed " . count($results) . "/4 entries of mixed types"
    ];
}, $allTests, $passedTests, $failedTests);

echo "\n";

// ERROR HANDLING TESTS
echo "⚠️ ERROR HANDLING TESTS\n";
echo str_repeat("-", 40) . "\n";

testSample("Invalid IP: 256.256.256.256", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '256.256.256.256');
    $invalidResults = array_filter($results, function($r) { return $r['type'] === 'invalid'; });
    return [
        'success' => count($invalidResults) === 1,
        'details' => "Correctly identified as invalid"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Invalid CIDR: 192.168.1.0/33", function() {
    global $pdo;
    $result = addIpRangeFromCidr($pdo, '192.168.1.0/33', 'SAMPLE_Invalid Team');
    return [
        'success' => !$result,
        'details' => "Correctly rejected invalid CIDR"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Mixed Valid/Invalid: 192.168.100.1 invalid-ip 10.50.0.1", function() {
    global $pdo;
    $results = getTeamsByIpInput($pdo, '192.168.100.1 invalid-ip 10.50.0.1');
    $validResults = array_filter($results, function($r) { return $r['type'] !== 'invalid'; });
    $invalidResults = array_filter($results, function($r) { return $r['type'] === 'invalid'; });
    return [
        'success' => count($validResults) === 2 && count($invalidResults) === 1,
        'details' => "Processed " . count($validResults) . " valid, " . count($invalidResults) . " invalid"
    ];
}, $allTests, $passedTests, $failedTests);

echo "\n";

// PERFORMANCE SPOT CHECKS
echo "⚡ PERFORMANCE SPOT CHECKS\n";
echo str_repeat("-", 40) . "\n";

testSample("Large IP List Processing (50 IPs)", function() {
    global $pdo;
    
    $startTime = microtime(true);
    
    $ipList = [];
    for ($i = 1; $i <= 50; $i++) {
        $ipList[] = "10.250.1.{$i}";
    }
    
    $result = addIpListToTeam($pdo, implode(' ', $ipList), 'SAMPLE_Performance Test');
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    // Cleanup
    if ($result['success']) {
        $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team = ?");
        $stmt->execute(['SAMPLE_Performance Test']);
    }
    
    return [
        'success' => $result['success'] && $result['added'] === 50 && $duration < 2.0,
        'details' => "Processed 50 IPs in " . round($duration, 3) . "s"
    ];
}, $allTests, $passedTests, $failedTests);

testSample("Bulk Lookup Performance (25 IPs)", function() {
    global $pdo;
    
    $startTime = microtime(true);
    
    $testIps = [];
    for ($i = 1; $i <= 25; $i++) {
        $testIps[] = "192.168.100." . ($i * 2);
    }
    
    $results = getTeamsByIpInput($pdo, implode(' ', $testIps));
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    return [
        'success' => count($results) === 25 && $duration < 1.0,
        'details' => "Looked up 25 IPs in " . round($duration, 3) . "s"
    ];
}, $allTests, $passedTests, $failedTests);

echo "\n";

// SUMMARY
echo str_repeat("=", 50) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed Tests: {$passedTests}\n";
echo "❌ Failed Tests: {$failedTests}\n";
echo "📈 Success Rate: " . round(($passedTests / ($passedTests + $failedTests)) * 100, 1) . "%\n";

if ($failedTests > 0) {
    echo "\n💥 FAILED TESTS:\n";
    foreach ($allTests as $test) {
        if ($test['status'] !== 'PASS') {
            echo "   - {$test['name']}: {$test['status']}";
            if (isset($test['error'])) {
                echo " ({$test['error']})";
            }
            echo "\n";
        }
    }
}

// Database statistics
echo "\n📋 DATABASE STATISTICS:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM ip_ranges WHERE team LIKE 'SAMPLE_%'");
$sampleCount = $stmt->fetchColumn();
echo "Sample entries created: {$sampleCount}\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM ip_ranges");
$totalCount = $stmt->fetchColumn();
echo "Total entries in database: {$totalCount}\n";

echo "\n🧹 CLEANUP\n";
$stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE 'SAMPLE_%'");
$stmt->execute();
$cleaned = $stmt->rowCount();
echo "Removed {$cleaned} sample test entries.\n";

echo "\n🎉 Sample testing completed!\n";
echo "All sample inputs from documentation have been validated.\n";
?>