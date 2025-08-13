<?php
/**
 * Comprehensive IP Mapping Test Suite
 * 
 * This script tests all IP mapping and adding functions.
 * Run via web browser or command line.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Test configuration
$TEST_PREFIX = 'TEST_';
$CLEANUP_AFTER = true; // Set to false to keep test data for manual inspection

// Test results tracking
$tests = [];
$passed = 0;
$failed = 0;

// Helper function to run tests
function runTest($testName, $testFunction, &$tests, &$passed, &$failed) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🧪 TESTING: {$testName}\n";
    echo str_repeat("=", 60) . "\n";
    
    try {
        $result = $testFunction();
        if ($result['success']) {
            echo "✅ PASSED: {$testName}\n";
            if (isset($result['details'])) {
                echo "   Details: {$result['details']}\n";
            }
            $tests[] = ['name' => $testName, 'status' => 'PASSED', 'details' => $result['details'] ?? ''];
            $passed++;
        } else {
            echo "❌ FAILED: {$testName}\n";
            echo "   Error: {$result['error']}\n";
            $tests[] = ['name' => $testName, 'status' => 'FAILED', 'error' => $result['error']];
            $failed++;
        }
    } catch (Exception $e) {
        echo "💥 EXCEPTION: {$testName}\n";
        echo "   Exception: {$e->getMessage()}\n";
        $tests[] = ['name' => $testName, 'status' => 'EXCEPTION', 'error' => $e->getMessage()];
        $failed++;
    }
}

// Test 1: Add IP Range (Traditional)
function testAddIpRange() {
    global $pdo, $TEST_PREFIX;
    
    $startIp = '192.168.100.1';
    $endIp = '192.168.100.50';
    $team = $TEST_PREFIX . 'Traditional Range Team';
    
    $result = addIpRange($pdo, $startIp, $endIp, $team);
    
    if ($result) {
        // Verify it was added
        $ranges = getAllIpRanges($pdo);
        $found = false;
        foreach ($ranges as $range) {
            if ($range['start_ip'] === $startIp && $range['end_ip'] === $endIp && $range['team'] === $team) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            return ['success' => true, 'details' => "Added range {$startIp}-{$endIp} for team {$team}"];
        } else {
            return ['success' => false, 'error' => 'Range was not found in database after adding'];
        }
    } else {
        return ['success' => false, 'error' => 'addIpRange() returned false'];
    }
}

// Test 2: Add CIDR Range
function testAddCidrRange() {
    global $pdo, $TEST_PREFIX;
    
    $cidr = '10.50.0.0/24';
    $team = $TEST_PREFIX . 'CIDR Team';
    
    $result = addIpRangeFromCidr($pdo, $cidr, $team);
    
    if ($result) {
        // Verify the CIDR was correctly converted
        $ranges = getAllIpRanges($pdo);
        $found = false;
        foreach ($ranges as $range) {
            if ($range['start_ip'] === '10.50.0.0' && $range['end_ip'] === '10.50.0.255' && $range['team'] === $team) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            return ['success' => true, 'details' => "Added CIDR {$cidr} (10.50.0.0-10.50.0.255) for team {$team}"];
        } else {
            return ['success' => false, 'error' => 'CIDR range was not correctly converted and stored'];
        }
    } else {
        return ['success' => false, 'error' => 'addIpRangeFromCidr() returned false'];
    }
}

// Test 3: Add IP List (Individual IPs)
function testAddIpList() {
    global $pdo, $TEST_PREFIX;
    
    $ipList = '172.16.1.10 172.16.1.20 172.16.1.30';
    $team = $TEST_PREFIX . 'IP List Team';
    
    $result = addIpListToTeam($pdo, $ipList, $team);
    
    if ($result['success']) {
        if ($result['added'] === 3) {
            return ['success' => true, 'details' => "Added {$result['added']} individual IPs from list for team {$team}"];
        } else {
            return ['success' => false, 'error' => "Expected 3 IPs, but added {$result['added']}"];
        }
    } else {
        return ['success' => false, 'error' => 'addIpListToTeam() failed: ' . implode(', ', $result['errors'])];
    }
}

// Test 4: Add IP List with Ranges
function testAddIpListWithRanges() {
    global $pdo, $TEST_PREFIX;
    
    $ipList = '10.20.1.1 10.20.2.1-10.20.2.5 10.20.3.1';
    $team = $TEST_PREFIX . 'Mixed List Team';
    
    $result = addIpListToTeam($pdo, $ipList, $team);
    
    if ($result['success']) {
        // Should add: 1 + 5 + 1 = 7 IPs total
        if ($result['added'] === 7) {
            return ['success' => true, 'details' => "Added {$result['added']} IPs from mixed list (individual + range) for team {$team}"];
        } else {
            return ['success' => false, 'error' => "Expected 7 IPs, but added {$result['added']}"];
        }
    } else {
        return ['success' => false, 'error' => 'addIpListToTeam() failed: ' . implode(', ', $result['errors'])];
    }
}

// Test 5: IP Lookup - Single IP
function testSingleIpLookup() {
    global $pdo;
    
    $testIp = '192.168.100.25'; // Should match the traditional range
    $team = getTeamByIp($pdo, $testIp);
    
    if ($team && strpos($team, 'Traditional Range Team') !== false) {
        return ['success' => true, 'details' => "IP {$testIp} correctly resolved to team: {$team}"];
    } else {
        return ['success' => false, 'error' => "IP {$testIp} resolved to: " . ($team ?: 'null') . " (expected Traditional Range Team)"];
    }
}

// Test 6: IP Lookup - Multiple IPs
function testMultipleIpLookup() {
    global $pdo;
    
    $testIps = ['192.168.100.1', '10.50.0.100', '172.16.1.10'];
    $results = getTeamsByIpInput($pdo, implode(' ', $testIps));
    
    $successCount = 0;
    foreach ($results as $result) {
        if ($result['type'] === 'single' && $result['team'] !== null) {
            $successCount++;
        }
    }
    
    if ($successCount === 3) {
        return ['success' => true, 'details' => "All 3 test IPs correctly resolved to their respective teams"];
    } else {
        return ['success' => false, 'error' => "Only {$successCount}/3 IPs were correctly resolved"];
    }
}

// Test 7: IP Lookup - CIDR Notation
function testCidrLookup() {
    global $pdo;
    
    $cidr = '10.50.0.0/28'; // Subset of our /24 range
    $results = getTeamsByIpInput($pdo, $cidr);
    
    if (!empty($results) && $results[0]['type'] === 'cidr') {
        $overlaps = $results[0]['overlapping_teams'] ?? [];
        if (count($overlaps) > 0) {
            return ['success' => true, 'details' => "CIDR {$cidr} found overlapping teams: " . implode(', ', array_unique($overlaps))];
        } else {
            return ['success' => false, 'error' => "CIDR {$cidr} should have found overlapping teams"];
        }
    } else {
        return ['success' => false, 'error' => "CIDR lookup failed or returned unexpected format"];
    }
}

// Test 8: Parse Input Function
function testParseInput() {
    $testInputs = [
        '192.168.1.1' => 'single',
        '192.168.1.0/24' => 'cidr',
        '10.0.0.1-10.0.0.10' => 'range',
        '192.168.1.1 10.0.0.0/8 172.16.0.1-172.16.0.5' => 'mixed'
    ];
    
    $allPassed = true;
    $details = [];
    
    foreach ($testInputs as $input => $expectedType) {
        $parsed = parseIpInput($input);
        
        if ($expectedType === 'mixed') {
            if (count($parsed) === 3) {
                $details[] = "Mixed input correctly parsed into 3 entries";
            } else {
                $allPassed = false;
                $details[] = "Mixed input parsing failed";
            }
        } else {
            if (!empty($parsed) && $parsed[0]['type'] === $expectedType) {
                $details[] = "{$expectedType} input correctly parsed";
            } else {
                $allPassed = false;
                $details[] = "{$expectedType} input parsing failed";
            }
        }
    }
    
    if ($allPassed) {
        return ['success' => true, 'details' => implode('; ', $details)];
    } else {
        return ['success' => false, 'error' => implode('; ', $details)];
    }
}

// Test 9: Update IP Range
function testUpdateIpRange() {
    global $pdo, $TEST_PREFIX;
    
    // Find a test range to update
    $ranges = getAllIpRanges($pdo);
    $testRange = null;
    foreach ($ranges as $range) {
        if (strpos($range['team'], $TEST_PREFIX) !== false) {
            $testRange = $range;
            break;
        }
    }
    
    if (!$testRange) {
        return ['success' => false, 'error' => 'No test range found to update'];
    }
    
    $newStartIp = '192.168.200.1';
    $newEndIp = '192.168.200.100';
    $newTeam = $TEST_PREFIX . 'Updated Team';
    
    $result = updateIpRange($pdo, $testRange['id'], $newStartIp, $newEndIp, $newTeam);
    
    if ($result) {
        // Verify the update
        $updatedRange = getIpRangeById($pdo, $testRange['id']);
        if ($updatedRange && 
            $updatedRange['start_ip'] === $newStartIp && 
            $updatedRange['end_ip'] === $newEndIp && 
            $updatedRange['team'] === $newTeam) {
            return ['success' => true, 'details' => "Successfully updated range ID {$testRange['id']} to {$newStartIp}-{$newEndIp}, team: {$newTeam}"];
        } else {
            return ['success' => false, 'error' => 'Range was not properly updated in database'];
        }
    } else {
        return ['success' => false, 'error' => 'updateIpRange() returned false'];
    }
}

// Test 5: Setup High-Volume Test Data
function testSetupHighVolumeData() {
    global $pdo, $TEST_PREFIX;
    
    $teams = [
        'Network Operations', 'Security Team', 'Development Team', 'QA Engineering',
        'Infrastructure', 'DevOps', 'Database Team', 'Frontend Team', 'Backend Team',
        'Mobile Team', 'Analytics Team', 'Customer Support', 'Sales Engineering',
        'Marketing Tech', 'Cloud Operations', 'Monitoring Team', 'Release Team'
    ];
    
    $totalAdded = 0;
    $startTime = microtime(true);
    
    try {
        // Add 50 /24 CIDR blocks across different teams
        for ($i = 1; $i <= 50; $i++) {
            $cidr = "10.{$i}.0.0/24";
            $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
            if (addIpRangeFromCidr($pdo, $cidr, $team)) {
                $totalAdded++;
            }
        }
        
        // Add 30 traditional ranges in 192.168.x.x
        for ($i = 1; $i <= 30; $i++) {
            $startIp = "192.168.{$i}.1";
            $endIp = "192.168.{$i}.100";
            $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
            if (addIpRange($pdo, $startIp, $endIp, $team)) {
                $totalAdded++;
            }
        }
        
        // Add 20 smaller ranges in 172.16.x.x
        for ($i = 1; $i <= 20; $i++) {
            $startIp = "172.16.{$i}.10";
            $endIp = "172.16.{$i}.50";
            $team = $TEST_PREFIX . $teams[($i - 1) % count($teams)];
            if (addIpRange($pdo, $startIp, $endIp, $team)) {
                $totalAdded++;
            }
        }
        
        // Add individual server IPs
        $serverIps = [];
        for ($i = 1; $i <= 100; $i++) {
            $serverIps[] = "203.0.113.{$i}";
        }
        $result = addIpListToTeam($pdo, implode(' ', $serverIps), $TEST_PREFIX . 'Server Farm');
        if ($result['success']) {
            $totalAdded += $result['added'];
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 3);
        
        if ($totalAdded >= 100) {
            return [
                'success' => true, 
                'details' => "Setup {$totalAdded} IP ranges across " . count($teams) . " teams in {$duration}s"
            ];
        } else {
            return ['success' => false, 'error' => "Only added {$totalAdded} entries, expected at least 100"];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception during setup: ' . $e->getMessage()];
    }
}

// Test 6: High-Volume IP Lookup (1000 IPs)
function testHighVolumeIpLookup() {
    global $pdo;
    
    $startTime = microtime(true);
    $testIps = [];
    $foundCount = 0;
    $totalTests = 1000;
    
    // Generate 1000 test IPs across our known ranges
    for ($i = 1; $i <= $totalTests; $i++) {
        if ($i <= 250) {
            // Test IPs in 10.x.0.x range (CIDR blocks)
            $subnet = (($i - 1) % 50) + 1;
            $host = (($i - 1) % 254) + 1;
            $testIps[] = "10.{$subnet}.0.{$host}";
        } elseif ($i <= 500) {
            // Test IPs in 192.168.x.x range (traditional ranges)
            $subnet = (($i - 251) % 30) + 1;
            $host = (($i - 251) % 100) + 1;
            $testIps[] = "192.168.{$subnet}.{$host}";
        } elseif ($i <= 750) {
            // Test IPs in 172.16.x.x range
            $subnet = (($i - 501) % 20) + 1;
            $host = (($i - 501) % 41) + 10; // 10-50 range
            $testIps[] = "172.16.{$subnet}.{$host}";
        } else {
            // Test server IPs in 203.0.113.x range
            $host = (($i - 751) % 100) + 1;
            $testIps[] = "203.0.113.{$host}";
        }
    }
    
    // Perform individual lookups
    foreach ($testIps as $ip) {
        $team = getTeamByIp($pdo, $ip);
        if ($team !== null) {
            $foundCount++;
        }
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 3);
    $lookupsPerSecond = round($totalTests / $duration, 1);
    
    // We expect a high success rate since we're testing IPs in our known ranges
    $successRate = round(($foundCount / $totalTests) * 100, 1);
    
    if ($foundCount >= 800 && $duration < 10) { // 80%+ success rate, under 10 seconds
        return [
            'success' => true,
            'details' => "Looked up {$totalTests} IPs in {$duration}s ({$lookupsPerSecond}/sec). Found teams for {$foundCount} IPs ({$successRate}%)"
        ];
    } else {
        return [
            'success' => false,
            'error' => "Performance/accuracy issue: {$foundCount}/{$totalTests} found in {$duration}s ({$successRate}% success)"
        ];
    }
}

// Test 7: Bulk Team Resolution (500 IPs)
function testBulkTeamResolution() {
    global $pdo;
    
    $startTime = microtime(true);
    $testIpsList = [];
    
    // Create bulk IP lists for testing
    for ($batch = 1; $batch <= 10; $batch++) {
        $batchIps = [];
        for ($i = 1; $i <= 50; $i++) {
            $subnet = (($batch - 1) % 50) + 1;
            $host = $i;
            $batchIps[] = "10.{$subnet}.0.{$host}";
        }
        $testIpsList[] = implode(' ', $batchIps);
    }
    
    $totalProcessed = 0;
    $totalFound = 0;
    
    foreach ($testIpsList as $ipList) {
        $results = getTeamsByIpInput($pdo, $ipList);
        $totalProcessed += count($results);
        
        foreach ($results as $result) {
            if ($result['team'] !== null) {
                $totalFound++;
            }
        }
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 3);
    $successRate = round(($totalFound / $totalProcessed) * 100, 1);
    
    if ($totalProcessed === 500 && $totalFound >= 400 && $duration < 5) {
        return [
            'success' => true,
            'details' => "Bulk resolved {$totalProcessed} IPs in {$duration}s. Found teams for {$totalFound} IPs ({$successRate}%)"
        ];
    } else {
        return [
            'success' => false,
            'error' => "Bulk resolution issue: {$totalFound}/{$totalProcessed} resolved in {$duration}s"
        ];
    }
}

// Test 8: Mixed Format Lookup (250 entries)
function testMixedFormatLookup() {
    global $pdo;
    
    $startTime = microtime(true);
    $testEntries = [];
    
    // Build mixed format test string with 250 total items
    $mixedInput = [];
    
    // 100 individual IPs
    for ($i = 1; $i <= 100; $i++) {
        $subnet = (($i - 1) % 50) + 1;
        $host = (($i - 1) % 254) + 1;
        $mixedInput[] = "10.{$subnet}.0.{$host}";
    }
    
    // 50 IP ranges
    for ($i = 1; $i <= 50; $i++) {
        $subnet = (($i - 1) % 30) + 1;
        $start = ($i % 10) + 1;
        $end = $start + 5;
        $mixedInput[] = "192.168.{$subnet}.{$start}-192.168.{$subnet}.{$end}";
    }
    
    // 50 CIDR blocks
    for ($i = 1; $i <= 50; $i++) {
        $subnet = (($i - 1) % 20) + 1;
        $mixedInput[] = "172.16.{$subnet}.0/28";
    }
    
    // 50 more individual IPs
    for ($i = 1; $i <= 50; $i++) {
        $host = $i;
        $mixedInput[] = "203.0.113.{$host}";
    }
    
    // Combine all entries into one string
    $testInput = implode(' ', $mixedInput);
    
    $results = getTeamsByIpInput($pdo, $testInput);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 3);
    
    // Count results by type
    $typeCounts = ['single' => 0, 'range' => 0, 'cidr' => 0, 'invalid' => 0];
    $teamsFound = 0;
    
    foreach ($results as $result) {
        $typeCounts[$result['type']]++;
        if (isset($result['team']) && $result['team'] !== null) {
            $teamsFound++;
        }
        if (isset($result['overlapping_teams']) && !empty($result['overlapping_teams'])) {
            $teamsFound++;
        }
    }
    
    $totalEntries = count($results);
    
    if ($totalEntries >= 200 && $teamsFound >= 100 && $duration < 3) {
        return [
            'success' => true,
            'details' => "Processed {$totalEntries} mixed entries in {$duration}s. Types: Single({$typeCounts['single']}), Range({$typeCounts['range']}), CIDR({$typeCounts['cidr']}), Invalid({$typeCounts['invalid']}). Teams found: {$teamsFound}"
        ];
    } else {
        return [
            'success' => false,
            'error' => "Mixed format processing issue: {$totalEntries} entries, {$teamsFound} teams found in {$duration}s"
        ];
    }
}

// Test 10: Error Handling
function testErrorHandling() {
    global $pdo, $TEST_PREFIX;
    
    $errorTests = [
        'Invalid CIDR' => function() use ($pdo, $TEST_PREFIX) {
            return !addIpRangeFromCidr($pdo, '192.168.1.0/33', $TEST_PREFIX . 'Invalid');
        },
        'Invalid IP Range' => function() use ($pdo, $TEST_PREFIX) {
            return !addIpRange($pdo, '192.168.1.100', '192.168.1.50', $TEST_PREFIX . 'Invalid');
        },
        'Empty IP List' => function() use ($pdo, $TEST_PREFIX) {
            $result = addIpListToTeam($pdo, '', $TEST_PREFIX . 'Empty');
            return !$result['success'];
        },
        'Invalid IP in List' => function() use ($pdo, $TEST_PREFIX) {
            $result = addIpListToTeam($pdo, '256.256.256.256 192.168.1.1', $TEST_PREFIX . 'Mixed');
            return $result['success'] && $result['added'] === 1; // Should add only the valid IP
        }
    ];
    
    $passedTests = [];
    $failedTests = [];
    
    foreach ($errorTests as $testName => $testFunc) {
        if ($testFunc()) {
            $passedTests[] = $testName;
        } else {
            $failedTests[] = $testName;
        }
    }
    
    if (empty($failedTests)) {
        return ['success' => true, 'details' => 'All error handling tests passed: ' . implode(', ', $passedTests)];
    } else {
        return ['success' => false, 'error' => 'Failed error tests: ' . implode(', ', $failedTests)];
    }
}

// Cleanup function
function cleanupTestData() {
    global $pdo, $TEST_PREFIX;
    
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE :prefix");
    $stmt->execute([':prefix' => $TEST_PREFIX . '%']);
    $deleted = $stmt->rowCount();
    
    echo "\n🧹 Cleanup: Removed {$deleted} test entries\n";
}

// Main test execution
echo "🚀 STARTING IP MAPPING COMPREHENSIVE TEST SUITE\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";

// Run all tests
runTest("Add IP Range (Traditional)", 'testAddIpRange', $tests, $passed, $failed);
runTest("Add CIDR Range", 'testAddCidrRange', $tests, $passed, $failed);
runTest("Add IP List (Individual IPs)", 'testAddIpList', $tests, $passed, $failed);
runTest("Add IP List with Ranges", 'testAddIpListWithRanges', $tests, $passed, $failed);
runTest("Setup High-Volume Test Data", 'testSetupHighVolumeData', $tests, $passed, $failed);
runTest("High-Volume IP Lookup (1000 IPs)", 'testHighVolumeIpLookup', $tests, $passed, $failed);
runTest("Bulk Team Resolution (500 IPs)", 'testBulkTeamResolution', $tests, $passed, $failed);
runTest("Mixed Format Lookup (250 entries)", 'testMixedFormatLookup', $tests, $passed, $failed);
runTest("Single IP Lookup", 'testSingleIpLookup', $tests, $passed, $failed);
runTest("Multiple IP Lookup", 'testMultipleIpLookup', $tests, $passed, $failed);
runTest("CIDR Lookup", 'testCidrLookup', $tests, $passed, $failed);
runTest("Parse Input Function", 'testParseInput', $tests, $passed, $failed);
runTest("Update IP Range", 'testUpdateIpRange', $tests, $passed, $failed);
runTest("Error Handling", 'testErrorHandling', $tests, $passed, $failed);

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "📈 Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";

if ($failed > 0) {
    echo "\n💥 FAILED TESTS:\n";
    foreach ($tests as $test) {
        if ($test['status'] !== 'PASSED') {
            echo "   - {$test['name']}: {$test['error']}\n";
        }
    }
}

// Cleanup
if ($CLEANUP_AFTER) {
    cleanupTestData();
} else {
    echo "\n⚠️  Test data kept in database (CLEANUP_AFTER = false)\n";
    echo "   Use prefix '{$TEST_PREFIX}' to identify test entries\n";
}

echo "\n🎉 TEST SUITE COMPLETED!\n";
?>