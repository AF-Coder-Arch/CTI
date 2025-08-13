<?php
$pageTitle = 'Database Connection Test';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="scripts/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Database Connection Test</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            require_once 'config/database.php';
                            
                            // Test basic connection
                            $stmt = $pdo->query("SELECT 1");
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check-circle"></i> Database connection successful!';
                            echo '</div>';
                            
                            // Test if findings table exists
                            $stmt = $pdo->query("SHOW TABLES LIKE 'findings'");
                            if ($stmt->rowCount() > 0) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle"></i> Findings table exists!';
                                echo '</div>';
                                
                                // Check for sample data
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM findings");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $count = $result['count'];
                                
                                echo '<div class="alert alert-info">';
                                echo '<i class="fas fa-info-circle"></i> Found ' . $count . ' findings in database.';
                                echo '</div>';
                                
                                if ($count > 0) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle"></i> Setup complete! You can now use the application.';
                                    echo '</div>';
                                    
                                    echo '<div class="mt-3">';
                                    echo '<a href="index.php" class="btn btn-primary">Go to Application</a>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<i class="fas fa-exclamation-triangle"></i> No sample data found. You may want to import the sample data from sql/setup.sql';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="fas fa-exclamation-circle"></i> Findings table not found! Please run the SQL setup script.';
                                echo '</div>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="fas fa-exclamation-circle"></i> Database connection failed: ' . $e->getMessage();
                            echo '</div>';
                            
                            echo '<div class="mt-3">';
                            echo '<h6>Troubleshooting:</h6>';
                            echo '<ul>';
                            echo '<li>Check database credentials in config/database.php</li>';
                            echo '<li>Ensure MySQL service is running</li>';
                            echo '<li>Verify database "cti_tracker" exists</li>';
                            echo '<li>Check user permissions</li>';
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
                        
                        <div class="mt-4">
                            <a href="setup.php" class="btn btn-secondary">Back to Setup</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>