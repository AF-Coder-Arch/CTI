<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
// Require authentication for accessing the dashboard
require_once 'auth.php';

// Get statistics
$openFindings = getAllFindings($pdo, 'open');
$totalOpen = count($openFindings);

// Sort open findings by updated_at DESC for display
usort($openFindings, function($a, $b) {
    return strtotime($b['updated_at']) - strtotime($a['updated_at']);
});

$pageTitle = 'Dashboard - CTI Tracker';
include 'includes/header.php';
?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Finding deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p class="text-muted">Overview of your CTI findings</p>
    </div>
</div>



<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-exclamation-triangle"></i> All Open Findings</h5>
            </div>
            <div class="card-body">
                <?php if (empty($openFindings)): ?>
                    <p class="text-muted">No open findings.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Team</th>
                                    <th>Contact Person</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($openFindings as $finding): ?>
                                    <?php $ageColor = getAgeColor($finding['date_created'], $finding['date_closed']); ?>
                                    <tr class="<?php echo $ageColor == 'danger' ? 'table-danger' : ($ageColor == 'warning' ? 'table-warning' : ''); ?>">
                                        <td>
                                            <a href="edit_finding.php?id=<?php echo $finding['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($finding['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo !empty($finding['team']) ? htmlspecialchars($finding['team']) : '<span class="text-muted">N/A</span>'; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($finding['contact_person']) ? htmlspecialchars($finding['contact_person']) : '<span class="text-muted">N/A</span>'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $finding['status'] == 'open' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($finding['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $ageColor; ?> age-badge">
                                                <?php echo getAgeText($finding['date_created'], $finding['date_closed']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($finding['updated_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_finding.php?id=<?php echo $finding['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_finding.php?id=<?php echo $finding['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this finding?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="add_finding.php" class="btn btn-success shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i> Add New Finding
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>