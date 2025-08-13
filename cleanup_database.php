<?php
/**
 * Database Cleanup Script for CTI Tracker
 * 
 * This script removes all data and tables from the CTI Tracker database
 * to allow for fresch testing of the setup_database.php script.
 * 
 * WARNING: This will permanently delete ALL data!
 * 
 * Instructions:
 * 1. Make sured you want to delete all data (this cannot be undone!)
 * 2. Run this script in your browser: http://localhost/CTI/cleanup_database.php
 * 3. After cleanup, run setup_database.php to recreate the database
 * 4. Delete this file after use for security
 */

require_once 'config/database.php';
// Restrict cleanup to authenticated administrators. If no user is logged in
// or the current user is not an admin, redirect to the dashboard to
// prevent accidental or unauthorized deletion of data.
require_once 'auth.php';
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

echo "<h1>🗑️ CTI Tracker - Database Cleanup</h1>";
echo "<p><strong>WARNING:</strong> This will permanently delete ALL data from the database!</p>";

// Safety check - require confirmation
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>⚠️ Confirmation Required</h3>";
    echo "<p>This action will:</p>";
    echo "<ul>";
    echo "<li>Drop all tables from the database</li>";
    echo "<li>Remove all findings data</li>";
    echo "<li>Remove all indexes</li>";
    echo "<li>Reset the database to empty state</li>";
    echo "</ul>";
    echo "<p><strong>This action cannot be undone!</strong></p>";
    echo "<p><a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Yes, Delete Everything</a></p>";
    echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Cancel</a></p>";
    echo "</div>";
    exit;
}

echo "<p>Starting database cleanup...</p>";

try {
    // Get list of all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>ℹ️ No tables found in database</p>";
    } else {
        echo "<p>Found " . count($tables) . " table(s) to remove:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        // Disable foreign key checks to avoid dependency issues
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        echo "<p>✓ Disabled foreign key checks</p>";
        
        // Drop all tables
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `" . $table . "`");
            echo "<p>✓ Dropped table: " . htmlspecialchars($table) . "</p>";
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "<p>✓ Re-enabled foreign key checks</p>";
    }
    
    // Verify cleanup
    $stmt = $pdo->query("SHOW TABLES");
    $remainingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($remainingTables)) {
        echo "<h2>✅ Database cleanup completed successfully!</h2>";
        echo "<p>The database is now empty and ready for fresh setup.</p>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ul>";
        echo "<li>Run <a href='setup_database.php'>setup_database.php</a> to recreate the database</li>";
        echo "<li>Delete this cleanup file for security</li>";
        echo "<li>Access your CTI Tracker at <a href='index.php'>index.php</a></li>";
        echo "</ul>";
    } else {
        echo "<h2>⚠️ Cleanup incomplete</h2>";
        echo "<p>The following tables were not removed:</p>";
        echo "<ul>";
        foreach ($remainingTables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database cleanup failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Ensure the database exists and user has DROP privileges</li>";
    echo "<li>Try running the script again</li>";
    echo "</ul>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1 {
    color: #333;
    border-bottom: 3px solid #dc3545;
    padding-bottom: 10px;
}
h2 {
    color: #28a745;
}
h3 {
    color: #ffc107;
}
p {
    margin: 10px 0;
}
ul {
    padding-left: 20px;
}
li {
    margin: 5px 0;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>