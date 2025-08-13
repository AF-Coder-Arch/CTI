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

// Get finding details
$stmt = $pdo->prepare("SELECT * FROM findings WHERE id = :id");
$stmt->execute([':id' => $id]);
$finding = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$finding) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_finding'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $comment = trim($_POST['comment']);
        $team = trim($_POST['team']);
        $contactPerson = trim($_POST['contact_person']);
        $status = $_POST['status'];
        
        if (!empty($title) && !empty($description)) {
            $updateStmt = $pdo->prepare("UPDATE findings SET title = :title, description = :description, comment = :comment, team = :team, contact_person = :contact_person, status = :status, date_closed = :date_closed WHERE id = :id");
            $dateClosed = ($status == 'closed' && $finding['status'] == 'open') ? date('Y-m-d H:i:s') : ($status == 'open' ? null : $finding['date_closed']);
            
            if ($updateStmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':comment' => $comment,
                ':team' => $team,
                ':contact_person' => $contactPerson,
                ':status' => $status,
                ':date_closed' => $dateClosed,
                ':id' => $id
            ])) {
                $message = "Finding updated successfully.";
                $messageType = "success";
                // Refresh finding data
                $stmt->execute([':id' => $id]);
                $finding = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Error updating finding.";
                $messageType = "danger";
            }
        } else {
            $message = "Please fill in all required fields.";
            $messageType = "danger";
        }
    }
}

$pageTitle = 'Edit Finding - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-edit"></i> Edit Finding</h1>
        <p class="text-muted">Modify finding information</p>
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
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-edit"></i> Edit Finding Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($finding['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($finding['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" 
                                  placeholder="Additional comments or notes..."><?php echo htmlspecialchars($finding['comment'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="team" class="form-label">Team</label>
                        <input type="text" class="form-control" id="team" name="team" 
                               value="<?php echo htmlspecialchars($finding['team'] ?? ''); ?>"
                               placeholder="Enter team name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo htmlspecialchars($finding['contact_person'] ?? ''); ?>"
                               placeholder="Enter contact person name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="open" <?php echo $finding['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="closed" <?php echo $finding['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                        <a href="delete_finding.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this finding?')">
                            <i class="fas fa-trash"></i> Delete Finding
                        </a>
                        <div>
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" name="update_finding" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Finding
                            </button>
                        </div>
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
                <p><strong>Current Status:</strong> 
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