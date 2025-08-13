<?php
/**
 * Database Setup Script for CTI Tracker
 * 
 * This script creates the necessary database tables and structure
 * for the CTI (Cyber Threat Intelligence) Tracker application.
 * 
 * Instructions:
 * 1. Make sure your MySQL server is running
 * 2. Create a database named 'cti_tracker' (or update the config/database.php)
 * 3. Run this script in your browser or via command line: php setup_database.php
 * 4. Delete this file after setup for security
 */

require_once 'config/database.php';

echo "<h1>CTI Tracker - Database Setup</h1>";
echo "<p>Setting up database tables...</p>";

try {
    // Create findings table
    $sql = "CREATE TABLE IF NOT EXISTS findings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        comment TEXT,
        team VARCHAR(100),
        contact_person VARCHAR(100),
        status ENUM('open', 'closed') DEFAULT 'open',
        date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_closed TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p>✓ 'findings' table created successfully</p>";
    
    // Check if table is empty and add sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM findings");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "<p>Adding sample data...</p>";
        
        // Insert sample findings
        $sampleFindings = [
            [
                'title' => 'Suspicious Network Activity Detected',
                'description' => 'Unusual outbound traffic patterns detected on network segment 192.168.1.0/24. Multiple connections to suspicious IP addresses.',
                'comment' => 'Initial investigation shows potential data exfiltration attempt.',
                'team' => 'Network Security',
                'contact_person' => 'John Smith'
            ],
            [
                'title' => 'Malware Detected on Endpoint',
                'description' => 'Antivirus software detected Trojan.Win32.GenKryptik on workstation WS-001.',
                'comment' => 'Machine has been isolated from network. Awaiting forensic analysis.',
                'team' => 'Endpoint Security',
                'contact_person' => 'Jane Doe'
            ],
            [
                'title' => 'Phishing Email Campaign',
                'description' => 'Multiple users reported receiving phishing emails with malicious attachments claiming to be from IT department.',
                'comment' => 'Email addresses added to blocklist. User awareness training scheduled.',
                'team' => 'Email Security',
                'contact_person' => 'Mike Johnson'
            ],
            [
                'title' => 'Unauthorized Access Attempt',
                'description' => 'Failed login attempts detected on administrative accounts from external IP addresses.',
                'comment' => 'Account lockout policies triggered. Reviewing access logs.',
                'team' => 'Identity Security',
                'contact_person' => 'Sarah Wilson'
            ],
            [
                'title' => 'Vulnerability Assessment Finding',
                'description' => 'Critical vulnerability (CVE-2023-12345) discovered in web application server.',
                'comment' => 'Patch available. Scheduling maintenance window for deployment.',
                'team' => 'Application Security',
                'contact_person' => 'David Brown'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO findings (title, description, comment, team, contact_person, status) VALUES (:title, :description, :comment, :team, :contact_person, 'open')");
        
        foreach ($sampleFindings as $finding) {
            $stmt->execute([
                ':title' => $finding['title'],
                ':description' => $finding['description'],
                ':comment' => $finding['comment'],
                ':team' => $finding['team'],
                ':contact_person' => $finding['contact_person']
            ]);
        }
        
        echo "<p>✓ Sample data inserted successfully</p>";
        
        // Create one closed finding for demonstration
        $stmt = $pdo->prepare("INSERT INTO findings (title, description, comment, team, contact_person, status, date_closed) VALUES (:title, :description, :comment, :team, :contact_person, 'closed', NOW())");
        $stmt->execute([
            ':title' => 'DDoS Attack Mitigated',
            ':description' => 'Distributed Denial of Service attack detected and successfully mitigated using rate limiting and traffic filtering.',
            ':comment' => 'Attack lasted 2 hours. All services restored to normal operation.',
            ':team' => 'Network Security',
            ':contact_person' => 'John Smith'
        ]);
        
        echo "<p>✓ Sample closed finding added</p>";
    } else {
        echo "<p>ℹ Table already contains data, skipping sample data insertion</p>";
    }
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_findings_status ON findings(status)",
        "CREATE INDEX IF NOT EXISTS idx_findings_date_created ON findings(date_created)",
        "CREATE INDEX IF NOT EXISTS idx_findings_updated_at ON findings(updated_at)",
        "CREATE INDEX IF NOT EXISTS idx_findings_team ON findings(team)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    echo "<p>✓ Database indexes created</p>";
    
    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Delete this setup file (setup_database.php) for security</li>";
    echo "<li>Access your CTI Tracker at <a href='index.php'>index.php</a></li>";
    echo "<li>Start adding your CTI findings</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database setup failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Ensure the database 'cti_tracker' exists</li>";
    echo "<li>Verify user has necessary permissions</li>";
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