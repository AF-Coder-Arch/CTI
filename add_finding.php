<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $comment = trim($_POST['comment']);
    $team = trim($_POST['team']);
    $contactPerson = trim($_POST['contact_person']);
    
    if (empty($title)) {
        $message = "Title is required.";
        $messageType = "danger";
    } else {
        if (addFinding($pdo, $title, $description, $comment, $team, $contactPerson)) {
            $message = "Finding added successfully.";
            $messageType = "success";
            // Clear form
            $title = '';
            $description = '';
            $comment = '';
            $team = '';
            $contactPerson = '';
        } else {
            $message = "Error adding finding.";
            $messageType = "danger";
        }
    }
}

$pageTitle = 'Add Finding - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-plus-circle"></i> Add New Finding</h1>
        <p class="text-muted">Create a new CTI finding entry</p>
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
                <h5><i class="fas fa-file-plus"></i> Finding Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                               placeholder="Enter finding title">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" 
                                  placeholder="Enter detailed description of the finding"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" 
                                  placeholder="Additional comments or notes..."><?php echo isset($comment) ? htmlspecialchars($comment) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="team" class="form-label">Team</label>
                        <input type="text" class="form-control" id="team" name="team" 
                               value="<?php echo isset($team) ? htmlspecialchars($team) : ''; ?>"
                               placeholder="Enter team name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo isset($contactPerson) ? htmlspecialchars($contactPerson) : ''; ?>"
                               placeholder="Enter contact person name">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Finding
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Information</h5>
            </div>
            <div class="card-body">
                <h6>Color Coding System:</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-success">Green</span> 0-7 days old</li>
                    <li><span class="badge bg-warning">Yellow</span> 8-30 days old</li>
                    <li><span class="badge bg-danger">Red</span> 30+ days old</li>
                </ul>
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Use clear, descriptive titles</li>
                    <li>Include relevant technical details</li>
                    <li>All findings start as "Open"</li>
                    <li>You can close findings later</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>