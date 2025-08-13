<?php
// Simple setup script for CTI Tracker
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTI Tracker Setup</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="scripts/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> CTI Tracker Setup</h3>
                    </div>
                    <div class="card-body">
                        <h5>Setup Instructions</h5>
                        <ol>
                            <li><strong>Database Setup:</strong>
                                <ul>
                                    <li>Create a MySQL database named <code>cti_tracker</code></li>
                                    <li>Import the schema: <code>mysql -u root -p cti_tracker < sql/setup.sql</code></li>
                                    <li>Or run the SQL commands in <code>sql/setup.sql</code> manually</li>
                                </ul>
                            </li>
                            <li><strong>Configuration:</strong>
                                <ul>
                                    <li>Edit <code>config/database.php</code> with your MySQL credentials</li>
                                    <li>Update DB_HOST, DB_NAME, DB_USER, and DB_PASS constants</li>
                                </ul>
                            </li>
                            <li><strong>Test Connection:</strong>
                                <ul>
                                    <li>Click the "Test Database Connection" button below</li>
                                </ul>
                            </li>
                        </ol>

                        <div class="mt-4">
                            <a href="test_connection.php" class="btn btn-primary">Test Database Connection</a>
                            <a href="index.php" class="btn btn-success">Go to Application</a>
                        </div>

                        <div class="mt-4">
                            <h6>Default Sample Data Includes:</h6>
                            <ul>
                                <li>6 sample findings (3 open, 3 closed)</li>
                                <li>Various creation dates for color coding demonstration</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>