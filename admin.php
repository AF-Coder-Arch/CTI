<?php
/**
 * Admin panellll
 *
 * This page allows administrators to manage application users and
 * configure IP range to team mappings. It is only accessible to users
 * with the 'admin' role. Non‑admin users are redirected to the
 * dashboard.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

// Ensure only administrators can access this page
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';

// Handle deletion of a user
if (isset($_GET['delete_user_id']) && ctype_digit($_GET['delete_user_id'])) {
    $deleteId = (int)$_GET['delete_user_id'];
    // Prevent deletion of oneself
    if ($deleteId == $_SESSION['user_id']) {
        $message = 'You cannot delete your own account.';
        $messageType = 'warning';
    } else {
        if (deleteUser($pdo, $deleteId)) {
            $message = 'User deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Error deleting user.';
            $messageType = 'danger';
        }
    }
}

// Handle deletion of an IP range
if (isset($_GET['delete_ip_id']) && ctype_digit($_GET['delete_ip_id'])) {
    $deleteIpId = (int)$_GET['delete_ip_id'];
    if (deleteIpRange($pdo, $deleteIpId)) {
        $message = 'IP range deleted successfully.';
        $messageType = 'success';
    } else {
        $message = 'Error deleting IP range.';
        $messageType = 'danger';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user form
    if (isset($_POST['add_user'])) {
        $newUsername = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $newRole = isset($_POST['new_role']) && $_POST['new_role'] === 'admin' ? 'admin' : 'user';

        if ($newUsername === '' || $newPassword === '') {
            $message = 'Please enter both username and password for the new user.';
            $messageType = 'danger';
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $stmt->execute([':username' => $newUsername]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $message = 'A user with that username already exists.';
                $messageType = 'warning';
            } else {
                if (addUser($pdo, $newUsername, $newPassword, $newRole)) {
                    $message = 'User added successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding user.';
                    $messageType = 'danger';
                }
            }
        }
    }
    // Add new IP range form
    elseif (isset($_POST['add_ip_range'])) {
        $teamName = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
        $ipInput = isset($_POST['ip_input']) ? trim($_POST['ip_input']) : '';

        if ($teamName === '') {
            $message = 'Please enter a team name.';
            $messageType = 'danger';
        } elseif ($ipInput === '') {
            $message = 'Please enter IP addresses, ranges, or CIDR blocks.';
            $messageType = 'danger';
        } else {
            $result = addIpListToTeam($pdo, $ipInput, $teamName);
            if ($result['success']) {
                $message = "Successfully added {$result['added']} IP entry/entries to team '{$teamName}'.";
                if (!empty($result['errors'])) {
                    $message .= " Warnings: " . implode('; ', $result['errors']);
                }
                $messageType = 'success';
            } else {
                $message = 'Failed to add IPs. Errors: ' . implode('; ', $result['errors']);
                $messageType = 'danger';
            }
        }
    }
    // Update IP range form
    elseif (isset($_POST['update_ip_range'])) {
        $rangeId = isset($_POST['range_id']) ? (int)$_POST['range_id'] : 0;
        $startIp = isset($_POST['edit_start_ip']) ? trim($_POST['edit_start_ip']) : '';
        $endIp = isset($_POST['edit_end_ip']) ? trim($_POST['edit_end_ip']) : '';
        $teamName = isset($_POST['edit_team_name']) ? trim($_POST['edit_team_name']) : '';

        if ($rangeId <= 0) {
            $message = 'Invalid IP range ID.';
            $messageType = 'danger';
        } elseif ($startIp === '' || $endIp === '' || $teamName === '') {
            $message = 'Please fill in all fields for the IP range update.';
            $messageType = 'danger';
        } elseif (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || 
                 !filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $message = 'Please enter valid IPv4 addresses.';
            $messageType = 'danger';
        } else {
            // Convert to long to compare start and end order
            $startLong = sprintf('%u', ip2long($startIp));
            $endLong = sprintf('%u', ip2long($endIp));
            if ($startLong > $endLong) {
                $message = 'Start IP must be less than or equal to End IP.';
                $messageType = 'danger';
            } else {
                if (updateIpRange($pdo, $rangeId, $startIp, $endIp, $teamName)) {
                    $message = 'IP range updated successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating IP range.';
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Fetch current users and IP ranges
$users = getAllUsers($pdo);
$ipRanges = getAllIpRanges($pdo);

$pageTitle = 'Admin Panel - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-user-shield"></i> Admin Panel</h1>
        <p class="text-muted">Manage users and IP-to-team mappings</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> User Management</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="add_user" value="1">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_role" class="form-label">Role</label>
                        <select class="form-select" id="new_role" name="new_role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Add User</button>
                </form>

                <h6 class="mt-4">Existing Users</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="admin.php?delete_user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-network-wired"></i> IP Range Management</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="add_ip_range" value="1">
                    
                    <div class="mb-3">
                        <label for="ip_input" class="form-label">IP Addresses, Ranges & CIDR Blocks</label>
                        <textarea class="form-control" id="ip_input" name="ip_input" rows="6" required
                                  placeholder="Enter any combination (space, comma, or line separated):&#10;&#10;Single IPs: 192.168.1.1, 10.0.0.1&#10;IP ranges: 192.168.1.1-192.168.1.50&#10;CIDR blocks: 10.10.10.0/24, 10.10.20.0/24&#10;Mixed: 192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10"></textarea>
                        <div class="form-text">
                            <strong>All formats supported:</strong><br>
                            • <strong>Single IPs:</strong> <code>192.168.1.1, 10.0.0.1</code> (stored individually)<br>
                            • <strong>IP ranges:</strong> <code>192.168.1.1-192.168.1.50</code> (stored as efficient ranges)<br>
                            • <strong>CIDR blocks:</strong> <code>10.10.10.0/24, 10.10.20.0/24</code> (stored as efficient ranges)<br>
                            • <strong>Mixed formats:</strong> <code>192.168.1.1, 10.10.10.0/24, 172.16.1.1-172.16.1.10</code><br>
                            <small class="text-info"><i class="fas fa-info-circle"></i> Ranges and CIDR blocks are stored efficiently as compressed ranges. Individual IPs are stored separately.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="team_name" class="form-label">Team Name</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" placeholder="e.g., Network Security Team" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add IP Mappings</button>
                </form>
                
                <script>
                    // Inline editing functionality for IP ranges
                    document.addEventListener('DOMContentLoaded', function() {
                        // Edit button click handler
                        document.querySelectorAll('.edit-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const rangeId = this.dataset.rangeId;
                                toggleEditMode(rangeId, true);
                            });
                        });
                        
                        // Cancel button click handler
                        document.querySelectorAll('.cancel-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const rangeId = this.dataset.rangeId;
                                toggleEditMode(rangeId, false);
                            });
                        });
                        
                        // Form submit handler with validation
                        document.querySelectorAll('.edit-form').forEach(form => {
                            form.addEventListener('submit', function(e) {
                                const startIp = form.querySelector('[name="edit_start_ip"]').value.trim();
                                const endIp = form.querySelector('[name="edit_end_ip"]').value.trim();
                                const teamName = form.querySelector('[name="edit_team_name"]').value.trim();
                                
                                if (!startIp || !endIp || !teamName) {
                                    e.preventDefault();
                                    alert('Please fill in all fields.');
                                    return false;
                                }
                                
                                // Basic IP validation
                                const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
                                if (!ipRegex.test(startIp) || !ipRegex.test(endIp)) {
                                    e.preventDefault();
                                    alert('Please enter valid IP addresses.');
                                    return false;
                                }
                                
                                return true;
                            });
                        });
                    });
                    
                    function toggleEditMode(rangeId, editMode) {
                        const row = document.getElementById('row_' + rangeId);
                        const displayElements = row.querySelectorAll('.display-mode');
                        const editElements = row.querySelectorAll('.edit-mode');
                        
                        if (editMode) {
                            // Switch to edit mode
                            displayElements.forEach(el => el.style.display = 'none');
                            editElements.forEach(el => el.style.display = 'block');
                            
                            // Focus on the first input field
                            const firstInput = row.querySelector('[name="edit_start_ip"]');
                            if (firstInput) {
                                setTimeout(() => firstInput.focus(), 100);
                            }
                        } else {
                            // Switch back to display mode
                            editElements.forEach(el => el.style.display = 'none');
                            displayElements.forEach(el => el.style.display = 'block');
                            
                            // Reset form values to original
                            const form = row.querySelector('.edit-form');
                            if (form) {
                                form.reset();
                            }
                        }
                    }
                </script>

                <h6 class="mt-4">Existing IP Ranges</h6>
                <?php if (empty($ipRanges)): ?>
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-info-circle"></i> No IP ranges configured yet. Add one above to get started.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Range</th>
                                    <th>Team</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipRanges as $range): ?>
                                    <?php
                                    // Calculate IP count in range
                                    $startLong = sprintf('%u', ip2long($range['start_ip']));
                                    $endLong = sprintf('%u', ip2long($range['end_ip']));
                                    $ipCount = $endLong - $startLong + 1;
                                    
                                    // Try to determine if it's a clean CIDR block
                                    $cidrEquivalent = '';
                                    if ($range['start_ip'] === $range['end_ip']) {
                                        $cidrEquivalent = $range['start_ip'] . '/32';
                                    } else {
                                        // Check if it's a power of 2 range that aligns to CIDR boundaries
                                        $size = $ipCount;
                                        if (($size & ($size - 1)) === 0) { // Power of 2
                                            $prefix = 32 - log($size, 2);
                                            $networkLong = $startLong;
                                            if (($networkLong & ($size - 1)) === 0) { // Aligned to boundary
                                                $cidrEquivalent = long2ip($networkLong) . '/' . $prefix;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr id="row_<?php echo $range['id']; ?>">
                                        <!-- Display Mode -->
                                        <td class="display-mode">
                                            <div>
                                                <?php if ($range['start_ip'] === $range['end_ip']): ?>
                                                    <i class="fas fa-dot-circle text-primary"></i>
                                                    <span class="ip-display"><?php echo htmlspecialchars($range['start_ip']); ?></span>
                                                <?php else: ?>
                                                    <i class="fas fa-arrows-alt-h text-info"></i>
                                                    <span class="ip-display"><?php echo htmlspecialchars($range['start_ip']); ?> - <?php echo htmlspecialchars($range['end_ip']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($cidrEquivalent): ?>
                                                <small class="text-success">
                                                    <i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($cidrEquivalent); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Edit Mode (hidden by default) -->
                                        <td class="edit-mode" style="display: none;">
                                            <form method="POST" class="edit-form" id="edit_form_<?php echo $range['id']; ?>" data-range-id="<?php echo $range['id']; ?>">
                                                <input type="hidden" name="update_ip_range" value="1">
                                                <input type="hidden" name="range_id" value="<?php echo $range['id']; ?>">
                                                <div class="row g-1">
                                                    <div class="col-5">
                                                        <input type="text" name="edit_start_ip" class="form-control form-control-sm" 
                                                               value="<?php echo htmlspecialchars($range['start_ip']); ?>" 
                                                               placeholder="Start IP" required>
                                                    </div>
                                                    <div class="col-2 text-center align-self-center">
                                                        <small class="text-muted">to</small>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" name="edit_end_ip" class="form-control form-control-sm" 
                                                               value="<?php echo htmlspecialchars($range['end_ip']); ?>" 
                                                               placeholder="End IP" required>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                        
                                        <td>
                                            <div class="display-mode">
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($range['team']); ?>
                                                </span>
                                            </div>
                                            <div class="edit-mode" style="display: none;">
                                                <input type="text" name="edit_team_name" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($range['team']); ?>" 
                                                       placeholder="Team Name" required form="edit_form_<?php echo $range['id']; ?>">
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="display-mode">
                                                <small class="text-muted">
                                                    <?php echo number_format($ipCount); ?> IP<?php echo $ipCount !== 1 ? 's' : ''; ?>
                                                </small>
                                            </div>
                                            <div class="edit-mode" style="display: none;">
                                                <small class="text-muted">Editing...</small>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="display-mode">
                                                <button class="btn btn-sm btn-outline-primary me-1 edit-btn" 
                                                        data-range-id="<?php echo $range['id']; ?>" 
                                                        title="Edit this IP range">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="admin.php?delete_ip_id=<?php echo $range['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Delete IP range for <?php echo htmlspecialchars($range['team']); ?>?\n<?php echo htmlspecialchars($range['start_ip']); ?> - <?php echo htmlspecialchars($range['end_ip']); ?>');"
                                                   title="Delete this IP range">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            <div class="edit-mode" style="display: none;">
                                                <button type="submit" class="btn btn-sm btn-success me-1 save-btn" 
                                                        title="Save changes" form="edit_form_<?php echo $range['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary cancel-btn" 
                                                        data-range-id="<?php echo $range['id']; ?>" 
                                                        title="Cancel editing">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Total: <?php echo count($ipRanges); ?> range<?php echo count($ipRanges) !== 1 ? 's' : ''; ?> configured
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>