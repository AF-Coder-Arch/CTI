<?php
/**
 * Additional Database Setup Script for CTI Tracker
 *
 * This script creates the necessary tables to support user
 * authentication and IP range to team mapping. It will not alter
 * existing data in the findings table. If tables already exist it
 * leaves them untouched. A default administrative user is created
 * if no admin account is present.
 *
 * Usage:
 *   - Ensure config/database.php contains correct credentials.
 *   - Run this script in your browser or via CLI: php setup_additional_tables.php
 *   - Remove this file after use for security.
 */

require_once 'config/database.php';

echo "<h1>CTI Tracker - Additional Tables Setup</h1>";
echo "<p>Creating 'users' and 'ip_ranges' tables if they do not exist...</p>";

try {
    // Create users table
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','user') NOT NULL DEFAULT 'user'
    )";
    $pdo->exec($sqlUsers);
    echo "<p>✓ 'users' table ready.</p>";

    // Ensure at least one admin account exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    if ($adminCount == 0) {
        $defaultUser = 'admin';
        $defaultPass = 'admin123';
        $hashed = password_hash($defaultPass, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, 'admin')");
        $insert->execute([':u' => $defaultUser, ':p' => $hashed]);
        echo "<p>✓ Default admin user created. Username: <strong>admin</strong>, Password: <strong>admin123</strong></p>";
    } else {
        echo "<p>ℹ Admin user already exists. No default admin created.</p>";
    }

    // Create ip_ranges table
    $sqlRanges = "CREATE TABLE IF NOT EXISTS ip_ranges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_ip VARCHAR(45) NOT NULL,
        end_ip VARCHAR(45) NOT NULL,
        team VARCHAR(100) NOT NULL,
        start_ip_long BIGINT UNSIGNED NOT NULL,
        end_ip_long BIGINT UNSIGNED NOT NULL
    )";
    $pdo->exec($sqlRanges);
    echo "<p>✓ 'ip_ranges' table ready.</p>";

    // Create index for faster lookup
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ip_ranges_start_end ON ip_ranges(start_ip_long, end_ip_long)");
    echo "<p>✓ Indexes created.</p>";

    echo "<h2>✅ Additional setup completed successfully!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Log in using the default admin credentials (admin/admin123) and change the password immediately.</li>";
    echo "<li>Add other users and configure IP ranges via the Admin panel.</li>";
    echo "<li>Remove this setup file for security.</li>";
    echo "<li>Return to your application: <a href='index.php'>index.php</a></li>";
    echo "</ul>";
} catch (PDOException $e) {
    echo "<h2>❌ Setup failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and ensure the database exists.</p>";
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
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}
h2 {
    color: #28a745;
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