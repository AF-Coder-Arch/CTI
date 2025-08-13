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