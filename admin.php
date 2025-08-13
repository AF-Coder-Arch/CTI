<?php
/**
 * Admin panel
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
        $inputMode = isset($_POST['input_mode']) ? $_POST['input_mode'] : 'range';
        $teamName = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';

        if ($teamName === '') {
            $message = 'Please enter a team name.';
            $messageType = 'danger';
        } elseif ($inputMode === 'cidr') {
            // CIDR mode
            $cidr = isset($_POST['cidr']) ? trim($_POST['cidr']) : '';
            if ($cidr === '') {
                $message = 'Please enter a CIDR notation.';
                $messageType = 'danger';
            } else {
                if (addIpRangeFromCidr($pdo, $cidr, $teamName)) {
                    $message = 'IP range from CIDR added successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Invalid CIDR notation or error adding IP range.';
                    $messageType = 'danger';
                }
            }
        } elseif ($inputMode === 'list') {
            // IP List mode
            $ipList = isset($_POST['ip_list']) ? trim($_POST['ip_list']) : '';
            if ($ipList === '') {
                $message = 'Please enter a list of IP addresses.';
                $messageType = 'danger';
            } else {
                $result = addIpListToTeam($pdo, $ipList, $teamName);
                if ($result['success']) {
                    $message = "Successfully added {$result['added']} IP(s) to team '{$teamName}'.";
                    if (!empty($result['errors'])) {
                        $message .= " Warnings: " . implode('; ', $result['errors']);
                    }
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add IPs. Errors: ' . implode('; ', $result['errors']);
                    $messageType = 'danger';
                }
            }
        } else {
            // Range mode (existing functionality)
            $startIp = isset($_POST['start_ip']) ? trim($_POST['start_ip']) : '';
            $endIp = isset($_POST['end_ip']) ? trim($_POST['end_ip']) : '';

            // Validate inputs
            if ($startIp === '' || $endIp === '') {
                $message = 'Please fill in start IP and end IP for the IP range.';
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
                    if (addIpRange($pdo, $startIp, $endIp, $teamName)) {
                        $message = 'IP range added successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Error adding IP range.';
                        $messageType = 'danger';
                    }
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
                <form method="POST" class="mb-3" id="ipRangeForm">
                    <input type="hidden" name="add_ip_range" value="1">
                    
                    <!-- Input Mode Toggle -->
                    <div class="mb-3">
                        <label class="form-label">Input Method</label>
                        <div class="btn-group w-100" role="group" aria-label="Input mode">
                            <input type="radio" class="btn-check" name="input_mode" id="range_mode" value="range" checked>
                            <label class="btn btn-outline-secondary" for="range_mode">
                                <i class="fas fa-arrows-alt-h"></i> Range
                            </label>
                            
                            <input type="radio" class="btn-check" name="input_mode" id="cidr_mode" value="cidr">
                            <label class="btn btn-outline-secondary" for="cidr_mode">
                                <i class="fas fa-network-wired"></i> CIDR
                            </label>
                            
                            <input type="radio" class="btn-check" name="input_mode" id="list_mode" value="list">
                            <label class="btn btn-outline-secondary" for="list_mode">
                                <i class="fas fa-list"></i> IP List
                            </label>
                        </div>
                    </div>
                    
                    <!-- Range Mode Fields -->
                    <div id="range_fields">
                        <div class="mb-3">
                            <label for="start_ip" class="form-label">Start IP</label>
                            <input type="text" class="form-control" id="start_ip" name="start_ip" placeholder="e.g., 10.0.0.1">
                        </div>
                        <div class="mb-3">
                            <label for="end_ip" class="form-label">End IP</label>
                            <input type="text" class="form-control" id="end_ip" name="end_ip" placeholder="e.g., 10.0.0.255">
                        </div>
                    </div>
                    
                    <!-- CIDR Mode Fields -->
                    <div id="cidr_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="cidr" class="form-label">CIDR Notation</label>
                            <input type="text" class="form-control" id="cidr" name="cidr" placeholder="e.g., 192.168.1.0/24">
                            <div class="form-text">
                                Examples: <code>10.0.0.0/8</code>, <code>192.168.1.0/24</code>, <code>172.16.0.0/16</code>
                            </div>
                        </div>
                    </div>
                    
                    <!-- IP List Mode Fields -->
                    <div id="list_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="ip_list" class="form-label">IP Address List</label>
                            <textarea class="form-control" id="ip_list" name="ip_list" rows="4" 
                                      placeholder="Enter individual IPs (space, comma, or line separated):&#10;10.12.2.2 10.90.10.100 10.23.211.10&#10;192.168.1.1, 172.16.0.50&#10;10.0.0.1"></textarea>
                            <div class="form-text">
                                <strong>Examples:</strong> <code>10.12.2.2 10.90.10.100 10.23.211.10</code><br>
                                <small class="text-warning"><i class="fas fa-info-circle"></i> Only individual IPs allowed - no ranges or CIDR blocks in list mode</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="team_name" class="form-label">Team Name</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" placeholder="e.g., Network Security" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> <span id="submit_button_text">Add IP Range</span></button>
                </form>
                
                <script>
                    // Toggle between range, CIDR, and IP list input modes
                    function showInputMode(mode) {
                        // Hide all field groups
                        document.getElementById('range_fields').style.display = 'none';
                        document.getElementById('cidr_fields').style.display = 'none';
                        document.getElementById('list_fields').style.display = 'none';
                        
                        // Remove all required attributes
                        document.getElementById('start_ip').removeAttribute('required');
                        document.getElementById('end_ip').removeAttribute('required');
                        document.getElementById('cidr').removeAttribute('required');
                        document.getElementById('ip_list').removeAttribute('required');
                        
                        // Show appropriate fields and set required attributes
                        if (mode === 'range') {
                            document.getElementById('range_fields').style.display = 'block';
                            document.getElementById('start_ip').setAttribute('required', '');
                            document.getElementById('end_ip').setAttribute('required', '');
                            document.getElementById('submit_button_text').textContent = 'Add IP Range';
                        } else if (mode === 'cidr') {
                            document.getElementById('cidr_fields').style.display = 'block';
                            document.getElementById('cidr').setAttribute('required', '');
                            document.getElementById('submit_button_text').textContent = 'Add CIDR Range';
                        } else if (mode === 'list') {
                            document.getElementById('list_fields').style.display = 'block';
                            document.getElementById('ip_list').setAttribute('required', '');
                            document.getElementById('submit_button_text').textContent = 'Add IP List';
                        }
                    }
                    
                    document.getElementById('range_mode').addEventListener('change', function() {
                        if (this.checked) showInputMode('range');
                    });
                    
                    document.getElementById('cidr_mode').addEventListener('change', function() {
                        if (this.checked) showInputMode('cidr');
                    });
                    
                    document.getElementById('list_mode').addEventListener('change', function() {
                        if (this.checked) showInputMode('list');
                    });
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
                                    <tr>
                                        <td>
                                            <div>
                                                <?php if ($range['start_ip'] === $range['end_ip']): ?>
                                                    <i class="fas fa-dot-circle text-primary"></i>
                                                    <?php echo htmlspecialchars($range['start_ip']); ?>
                                                <?php else: ?>
                                                    <i class="fas fa-arrows-alt-h text-info"></i>
                                                    <?php echo htmlspecialchars($range['start_ip']); ?> - <?php echo htmlspecialchars($range['end_ip']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($cidrEquivalent): ?>
                                                <small class="text-success">
                                                    <i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($cidrEquivalent); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($range['team']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo number_format($ipCount); ?> IP<?php echo $ipCount !== 1 ? 's' : ''; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="admin.php?delete_ip_id=<?php echo $range['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Delete IP range for <?php echo htmlspecialchars($range['team']); ?>?\n<?php echo htmlspecialchars($range['start_ip']); ?> - <?php echo htmlspecialchars($range['end_ip']); ?>');"
                                               title="Delete this IP range">
                                                <i class="fas fa-trash"></i>
                                            </a>
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