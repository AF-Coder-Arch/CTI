<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reopen_finding'])) {
        $id = $_POST['finding_id'];
        if (reopenFinding($pdo, $id)) {
            $message = "Finding reopened successfully.";
            $messageType = "success";
        } else {
            $message = "Error reopening finding.";
            $messageType = "danger";
        }
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$findings = empty($search) ? getAllFindings($pdo, 'closed') : searchFindings($pdo, $search, 'closed');

$pageTitle = 'Closed Findings - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-check-circle text-success"></i> Closed Findings</h1>
        <p class="text-muted">Resolved CTI findings archive</p>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="search-container">
    <form method="GET" class="row g-3">
        <div class="col-md-10">
            <input type="text" class="form-control" name="search" placeholder="Search closed findings..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
    </form>
    <?php if (!empty($search)): ?>
        <div class="mt-2">
            <a href="closed_findings.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i> Clear Search
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-12">
        <?php if (empty($findings)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo empty($search) ? 'No closed findings found.' : 'No findings match your search criteria.'; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($findings as $finding): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card finding-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <a href="edit_finding.php?id=<?php echo $finding['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($finding['title']); ?>
                                    </a>
                                </h6>
                                <span class="badge bg-<?php echo getAgeColor($finding['date_created'], $finding['date_closed']); ?> age-badge">
                                    <?php echo getAgeText($finding['date_created'], $finding['date_closed']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($finding['description']); ?></p>
                                <?php if (!empty($finding['comment'])): ?>
                                    <p class="card-text"><strong>Comment:</strong> <em><?php echo htmlspecialchars($finding['comment']); ?></em></p>
                                <?php endif; ?>
                                <?php if (!empty($finding['team'])): ?>
                                    <p class="card-text"><strong>Team:</strong> <?php echo htmlspecialchars($finding['team']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($finding['contact_person'])): ?>
                                    <p class="card-text"><strong>Contact:</strong> <?php echo htmlspecialchars($finding['contact_person']); ?></p>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Created: <?php echo date('M j, Y', strtotime($finding['date_created'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <i class="fas fa-check"></i> Closed: <?php echo date('M j, Y', strtotime($finding['date_closed'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="finding_id" value="<?php echo $finding['id']; ?>">
                                        <button type="submit" name="reopen_finding" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to reopen this finding?')">
                                            <i class="fas fa-undo"></i> Reopen Finding
                                        </button>
                                    </form>
                                    <div>
                                        <a href="edit_finding.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-primary btn-sm me-2" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_finding.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this finding?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>