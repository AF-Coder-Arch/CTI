<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Get finding details for confirmation
$finding = getFindingById($pdo, $id);

if (!$finding) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        if (deleteFinding($pdo, $id)) {
            // Redirect with success message
            header('Location: index.php?deleted=1');
            exit;
        } else {
            $message = "Error deleting finding.";
            $messageType = "danger";
        }
    } elseif (isset($_POST['cancel_delete'])) {
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Delete Finding - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-trash"></i> Delete Finding</h1>
        <p class="text-muted">Confirm deletion of finding</p>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Warning!</strong> This action cannot be undone. The finding will be permanently deleted from the database.
                </div>
                
                <p><strong>Are you sure you want to delete this finding?</strong></p>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($finding['title']); ?></h6>
                        <p class="card-text"><?php echo htmlspecialchars($finding['description']); ?></p>
                        <small class="text-muted">
                            Created: <?php echo date('M j, Y H:i', strtotime($finding['date_created'])); ?>
                            <?php if ($finding['team']): ?>
                                | Team: <?php echo htmlspecialchars($finding['team']); ?>
                            <?php endif; ?>
                            <?php if ($finding['contact_person']): ?>
                                | Contact: <?php echo htmlspecialchars($finding['contact_person']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                
                <form method="POST" class="mt-4">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="cancel_delete" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Finding
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Finding Information</h5>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> #<?php echo $finding['id']; ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $finding['status'] == 'open' ? 'danger' : 'success'; ?>">
                        <?php echo ucfirst($finding['status']); ?>
                    </span>
                </p>
                <p><strong>Age:</strong> 
                    <span class="badge bg-<?php echo getAgeColor($finding['date_created'], $finding['date_closed']); ?>">
                        <?php echo getAgeText($finding['date_created'], $finding['date_closed']); ?>
                    </span>
                </p>
                <?php if (!empty($finding['team'])): ?>
                    <p><strong>Team:</strong><br>
                        <?php echo htmlspecialchars($finding['team']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($finding['contact_person'])): ?>
                    <p><strong>Contact Person:</strong><br>
                        <?php echo htmlspecialchars($finding['contact_person']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($finding['comment'])): ?>
                    <p><strong>Comment:</strong><br>
                        <em><?php echo htmlspecialchars($finding['comment']); ?></em>
                    </p>
                <?php endif; ?>
                <p><strong>Created:</strong><br>
                    <?php echo date('M j, Y H:i', strtotime($finding['date_created'])); ?>
                </p>
                <?php if ($finding['date_closed']): ?>
                    <p><strong>Closed:</strong><br>
                        <?php echo date('M j, Y H:i', strtotime($finding['date_closed'])); ?>
                    </p>
                <?php endif; ?>
                <p><strong>Last Updated:</strong><br>
                    <?php echo date('M j, Y H:i', strtotime($finding['updated_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>