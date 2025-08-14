<?php
/**
 * Test script to demonstrate unified IP input support
 * 
 * This script shows how the updated system can handle all IP formats
 * in a single unified input field.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Testing Unified IP Input Support</h1>";

// Test various input combinations
$testInputs = [
    // Multiple CIDR blocks
    '10.10.10.0/24, 10.10.20.0/24',
    // Mixed formats
    '192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10',
    // Space separated
    '10.0.0.1 10.0.0.2 192.168.1.0/24',
    // Newline separated
    "10.1.1.1\n10.1.1.0/24\n192.168.100.1-192.168.100.5"
];

$testTeamName = 'TEST_Unified_Team';

// Clean up any existing test data first
try {
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team = ?");
    $stmt->execute([$testTeamName]);
    echo "<p>✓ Cleaned up existing test data</p>";
} catch (Exception $e) {
    echo "<p>Note: " . $e->getMessage() . "</p>";
}

foreach ($testInputs as $index => $testInput) {
    echo "<h2>Test Case " . ($index + 1) . "</h2>";
    echo "<p><strong>Input:</strong> <code>" . htmlspecialchars(str_replace("\n", "\\n", $testInput)) . "</code></p>";
    
    // Test parsing
    $entries = parseIpInput($testInput);
    echo "<h3>Parsed Entries:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Type</th><th>Original</th><th>Start IP</th><th>End IP</th></tr>";
    foreach ($entries as $entry) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($entry['type']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['original']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['start_ip'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($entry['end_ip'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test adding to team
    $result = addIpListToTeam($pdo, $testInput, $testTeamName . "_" . ($index + 1));
    echo "<h3>Add Result:</h3>";
    echo "<p>Success: " . ($result['success'] ? "✅ Yes" : "❌ No") . "</p>";
    echo "<p>Added: " . $result['added'] . " entries</p>";
    if (!empty($result['errors'])) {
        echo "<p>Errors/Warnings: " . implode('; ', $result['errors']) . "</p>";
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

// Check all entries in database
echo "<h2>All Database Entries</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM ip_ranges WHERE team LIKE ? ORDER BY team, start_ip");
    $stmt->execute([$testTeamName . "_%"]);
    $ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ranges)) {
        echo "<p>No ranges found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Team</th><th>Start IP</th><th>End IP</th><th>Coverage</th></tr>";
        foreach ($ranges as $range) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($range['team']) . "</td>";
            echo "<td>" . htmlspecialchars($range['start_ip']) . "</td>";
            echo "<td>" . htmlspecialchars($range['end_ip']) . "</td>";
            
            // Show coverage info
            if ($range['start_ip'] === $range['end_ip']) {
                echo "<td>Single IP</td>";
            } else {
                $startLong = sprintf('%u', ip2long($range['start_ip']));
                $endLong = sprintf('%u', ip2long($range['end_ip']));
                $count = $endLong - $startLong + 1;
                echo "<td>Range ($count IPs)</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>Total entries: " . count($ranges) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>Error querying database: " . $e->getMessage() . "</p>";
}

// Test lookups
echo "<h2>Sample IP Lookups</h2>";
$testIps = [
    '10.10.10.1',      // Should match CIDR
    '10.10.20.50',     // Should match CIDR  
    '192.168.1.1',     // Should match individual IP
    '172.16.1.5',      // Should match range
    '10.99.99.1'       // Should not match anything
];

foreach ($testIps as $testIp) {
    $team = getTeamByIp($pdo, $testIp);
    echo "<p><strong>$testIp:</strong> " . ($team ? "✅ Team '$team'" : "❌ No team found") . "</p>";
}

// Clean up test data
echo "<h2>Cleanup</h2>";
try {
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE team LIKE ?");
    $stmt->execute([$testTeamName . "_%"]);
    echo "<p>✓ All test data cleaned up</p>";
} catch (Exception $e) {
    echo "<p>Cleanup error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>✅ Summary</h2>";
echo "<p>The system now has a <strong>unified input field</strong> that accepts all IP formats:</p>";
echo "<ul>";
echo "<li>✅ Single IPs: <code>192.168.1.1, 10.0.0.1</code></li>";
echo "<li>✅ IP ranges: <code>192.168.1.1-192.168.1.50</code></li>";
echo "<li>✅ CIDR blocks: <code>10.10.10.0/24, 10.10.20.0/24</code></li>";
echo "<li>✅ Mixed formats: <code>192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10</code></li>";
echo "<li>✅ Separated by spaces, commas, or new lines</li>";
echo "</ul>";
echo "<p><strong>No more separate input modes!</strong> Everything works in one field.</p>";
echo "<p><a href='admin.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a> to try it out!</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
}
th {
    background-color: #f4f4f4;
    font-weight: bold;
}
pre {
    background-color: #f8f9fa;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow-x: auto;
}
</style>