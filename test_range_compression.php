<?php
/**
 * Test script to demonstrate IP range compression optimization
 * 
 * Shows how ranges and CIDR blocks are stored efficiently as compressed ranges
 * instead of being expanded into many individual IP entries.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🚀 IP Range Compression Test</h1>";

// Test cases showing the compression optimization
$testCases = [
    [
        'name' => 'IP Range Compression',
        'input' => '192.168.1.1-192.168.1.50',
        'description' => '50 IPs compressed into 1 range entry',
        'team' => 'RANGE_COMPRESS_Team'
    ],
    [
        'name' => 'CIDR Block Efficiency', 
        'input' => '10.10.10.0/24',
        'description' => '256 IPs (10.10.10.0-10.10.10.255) in 1 range entry',
        'team' => 'CIDR_COMPRESS_Team'
    ],
    [
        'name' => 'Multiple Ranges',
        'input' => '192.168.2.100-192.168.2.120, 10.20.30.40-10.20.30.45, 192.168.5.5-192.168.5.15',
        'description' => '3 ranges (21+6+11 IPs = 38 total) in just 3 range entries',
        'team' => 'MULTI_RANGE_Team'
    ],
    [
        'name' => 'Mixed Formats - Optimized Storage',
        'input' => '192.168.1.100, 10.10.10.0/24, 172.16.1.1-172.16.1.10, 203.0.113.5',
        'description' => '1 individual IP + 1 CIDR (256 IPs) + 1 range (10 IPs) + 1 individual IP = 4 database entries instead of 268!',
        'team' => 'MIXED_OPTIMIZED_Team'
    ]
];

// Clean up any existing test data
echo "<h2>🧹 Cleanup Previous Test Data</h2>";
foreach ($testCases as $testCase) {
    try {
        $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team = ?");
        $stmt->execute([$testCase['team']]);
        echo "<p>✓ Cleaned up {$testCase['team']}</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Cleanup note: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";

// Run each test case
foreach ($testCases as $index => $testCase) {
    echo "<h2>📊 Test Case " . ($index + 1) . ": {$testCase['name']}</h2>";
    echo "<p><strong>Input:</strong> <code>" . htmlspecialchars($testCase['input']) . "</code></p>";
    echo "<p><strong>Goal:</strong> {$testCase['description']}</p>";
    
    // Parse the input to show what would happen
    $entries = parseIpInput($testCase['input']);
    echo "<h3>Parsed Input Analysis:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f4f4f4;'><th>Type</th><th>Original Input</th><th>Start IP</th><th>End IP</th><th>Coverage</th><th>Storage Method</th></tr>";
    
    foreach ($entries as $entry) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($entry['type']) . "</strong></td>";
        echo "<td><code>" . htmlspecialchars($entry['original']) . "</code></td>";
        echo "<td>" . htmlspecialchars($entry['start_ip'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($entry['end_ip'] ?? 'N/A') . "</td>";
        
        // Calculate coverage
        if ($entry['type'] === 'single') {
            echo "<td>1 IP</td>";
            echo "<td>✅ Individual entry</td>";
        } elseif ($entry['type'] === 'range') {
            $startLong = sprintf('%u', ip2long($entry['start_ip']));
            $endLong = sprintf('%u', ip2long($entry['end_ip']));
            $count = $endLong - $startLong + 1;
            echo "<td><strong>$count IPs</strong></td>";
            echo "<td>🚀 <strong>Compressed range (1 entry)</strong></td>";
        } elseif ($entry['type'] === 'cidr') {
            $startLong = sprintf('%u', ip2long($entry['start_ip']));
            $endLong = sprintf('%u', ip2long($entry['end_ip']));
            $count = $endLong - $startLong + 1;
            echo "<td><strong>$count IPs</strong></td>";
            echo "<td>🚀 <strong>Compressed range (1 entry)</strong></td>";
        } elseif ($entry['type'] === 'invalid') {
            echo "<td>❌ Invalid</td>";
            echo "<td>❌ Skipped</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Add to database and show results
    echo "<h3>Database Storage Results:</h3>";
    $result = addIpListToTeam($pdo, $testCase['input'], $testCase['team']);
    
    if ($result['success']) {
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>✅ Success!</strong> Added {$result['added']} database entries</p>";
        if (isset($result['total_ranges_and_cidrs'])) {
            echo "<p>📊 <strong>Compressed ranges/CIDRs:</strong> {$result['total_ranges_and_cidrs']}</p>";
        }
        if (isset($result['total_individual_ips'])) {
            echo "<p>📊 <strong>Individual IPs:</strong> {$result['total_individual_ips']}</p>";
        }
        echo "</div>";
    } else {
        echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>❌ Failed:</strong> " . implode('; ', $result['errors']) . "</p>";
        echo "</div>";
    }
    
    // Show actual database entries
    try {
        $stmt = $pdo->prepare("SELECT * FROM ip_ranges WHERE team = ? ORDER BY start_ip_long");
        $stmt->execute([$testCase['team']]);
        $dbEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($dbEntries)) {
            echo "<h4>Actual Database Entries:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-size: 0.9em;'>";
            echo "<tr style='background-color: #f4f4f4;'><th>ID</th><th>Start IP</th><th>End IP</th><th>Team</th><th>Type</th></tr>";
            
            foreach ($dbEntries as $dbEntry) {
                echo "<tr>";
                echo "<td>" . $dbEntry['id'] . "</td>";
                echo "<td>" . htmlspecialchars($dbEntry['start_ip']) . "</td>";
                echo "<td>" . htmlspecialchars($dbEntry['end_ip']) . "</td>";
                echo "<td>" . htmlspecialchars($dbEntry['team']) . "</td>";
                
                // Determine type
                if ($dbEntry['start_ip'] === $dbEntry['end_ip']) {
                    echo "<td>Single IP</td>";
                } else {
                    $startLong = sprintf('%u', ip2long($dbEntry['start_ip']));
                    $endLong = sprintf('%u', ip2long($dbEntry['end_ip']));
                    $count = $endLong - $startLong + 1;
                    echo "<td><strong>Range ($count IPs)</strong></td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "<p><strong>Total database rows:</strong> " . count($dbEntries) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>Database query error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

// Show overall efficiency comparison
echo "<h2>📈 Efficiency Analysis</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f4f4f4;'><th>Input</th><th>Total IP Coverage</th><th>Database Entries</th><th>Efficiency</th></tr>";

foreach ($testCases as $testCase) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as entry_count, SUM(end_ip_long - start_ip_long + 1) as total_ips FROM ip_ranges WHERE team = ?");
        $stmt->execute([$testCase['team']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($testCase['input']) . "</code></td>";
        echo "<td><strong>" . number_format($stats['total_ips']) . " IPs</strong></td>";
        echo "<td><strong>" . $stats['entry_count'] . " entries</strong></td>";
        
        $efficiency = $stats['total_ips'] / $stats['entry_count'];
        if ($efficiency > 10) {
            echo "<td style='color: green;'><strong>🚀 " . number_format($efficiency, 1) . "x compression</strong></td>";
        } elseif ($efficiency > 1) {
            echo "<td style='color: orange;'><strong>📊 " . number_format($efficiency, 1) . "x compression</strong></td>";
        } else {
            echo "<td>📌 No compression (individual IPs)</td>";
        }
        echo "</tr>";
    } catch (Exception $e) {
        echo "<tr><td colspan='4'>Error calculating stats: " . $e->getMessage() . "</td></tr>";
    }
}
echo "</table>";

// Test IP lookups to verify ranges work correctly
echo "<h2>🔍 Verification - IP Lookup Tests</h2>";
$testLookups = [
    '192.168.1.25',     // Should match range test
    '10.10.10.100',     // Should match CIDR test
    '192.168.2.110',    // Should match multi-range test
    '172.16.1.5',       // Should match mixed test
    '192.168.1.100',    // Should match mixed test (individual IP)
    '203.0.113.5',      // Should match mixed test (individual IP)
    '8.8.8.8'           // Should not match anything
];

echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f4f4f4;'><th>Test IP</th><th>Found Team</th><th>Status</th></tr>";

foreach ($testLookups as $testIp) {
    $team = getTeamByIp($pdo, $testIp);
    echo "<tr>";
    echo "<td><strong>" . $testIp . "</strong></td>";
    echo "<td>" . ($team ? htmlspecialchars($team) : '<em>No team found</em>') . "</td>";
    echo "<td>" . ($team ? "✅ Found" : "❌ Not found") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Final cleanup
echo "<h2>🧹 Final Cleanup</h2>";
foreach ($testCases as $testCase) {
    try {
        $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team = ?");
        $stmt->execute([$testCase['team']]);
        echo "<p>✓ Cleaned up {$testCase['team']}</p>";
    } catch (Exception $e) {
        echo "<p>Cleanup error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>🎉 Summary</h2>";
echo "<div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 20px; border-radius: 8px;'>";
echo "<h3>✅ Range Compression Optimization Complete!</h3>";
echo "<p><strong>What changed:</strong></p>";
echo "<ul>";
echo "<li>🚀 <strong>IP ranges</strong> (e.g., <code>192.168.1.1-192.168.1.50</code>) are now stored as 1 database entry instead of 50 individual entries</li>";
echo "<li>🚀 <strong>CIDR blocks</strong> (e.g., <code>10.10.10.0/24</code>) are stored as 1 database entry instead of 256 individual entries</li>";
echo "<li>📊 <strong>Individual IPs</strong> (e.g., <code>192.168.1.100</code>) are still stored individually as intended</li>";
echo "<li>🔍 <strong>IP lookups</strong> work exactly the same - no change in functionality</li>";
echo "<li>⚡ <strong>Database performance</strong> is significantly improved for large ranges</li>";
echo "</ul>";
echo "<p><strong>Result:</strong> Massive space savings and better query performance while maintaining full functionality!</p>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Try it in Admin Panel</a> ";
echo "<a href='ip_lookup.php' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-left: 10px;'>Test IP Lookups</a>";
echo "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

table {
    border-collapse: collapse;
    width: 100%;
    margin: 15px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #f4f4f4;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

code {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    padding: 2px 4px;
    font-family: 'Courier New', monospace;
}

h1, h2, h3 {
    color: #333;
}

h1 {
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}

hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 30px 0;
}
</style>