<?php
/**
 * IP Lookup page
 *
 * Allows an authenticated user to enter an IP address and determine
 * which team, if any, is responsible for it based on configured
 * ranges. Requires authentication. Displays the team name if a
 * mapping is found, otherwise informs the user that no mapping exists.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

$ipInput = '';
$results = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipInput = isset($_POST['ip_address']) ? trim($_POST['ip_address']) : '';
    if ($ipInput === '') {
        $error = 'Please enter one or more IP addresses.';
    } else {
        $results = getTeamsByIpInput($pdo, $ipInput);
        if (empty($results)) {
            $error = 'No valid IP addresses found in input.';
        }
    }
}

$pageTitle = 'IP Lookup - CTI Tracker';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-network-wired"></i> IP to Team Mapping</h1>
        <p class="text-muted">Enter IP addresses, ranges, or CIDR notation (space, comma, or line separated) to find associated teams</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> IP Lookup</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Addresses</label>
                        <textarea name="ip_address" id="ip_address" class="form-control" rows="4" 
                                  placeholder="Enter IPs (space, comma, or line separated):&#10;192.168.1.1 10.0.0.0/8 172.16.0.1-172.16.0.100&#10;192.168.1.1, 10.23.211.10&#10;192.168.1.0/24"><?php echo htmlspecialchars($ipInput); ?></textarea>
                        <div class="form-text">
                            <strong>Supported formats:</strong><br>
                            • Single IPs: <code>192.168.1.1</code><br>
                            • CIDR notation: <code>192.168.1.0/24</code><br>
                            • IP ranges: <code>192.168.1.1-192.168.1.50</code><br>
                            • Multiple entries separated by spaces, commas, or new lines
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Lookup Teams
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> How it works</h5>
            </div>
            <div class="card-body">
                <h6>Input Examples:</h6>
                <ul class="small">
                    <li><code>192.168.1.1</code> - Single IP</li>
                    <li><code>192.168.1.0/24</code> - CIDR block</li>
                    <li><code>10.0.0.1-10.0.0.50</code> - IP range</li>
                    <li><code>10.12.2.2 10.90.10.100 10.23.211.10</code> - Space-separated list</li>
                    <li>Mix multiple formats with spaces, commas, or new lines</li>
                </ul>
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Use CIDR notation for network blocks</li>
                    <li>Ranges show all overlapping team mappings</li>
                    <li>Only IPv4 addresses are supported</li>
                    <li>Invalid entries will be shown with errors</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($results !== null && !empty($results)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Lookup Results</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($results as $result): ?>
                        <div class="mb-3 p-3 border rounded">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-1">
                                        <?php 
                                        $typeIcon = '';
                                        switch ($result['type']) {
                                            case 'single': $typeIcon = 'fas fa-dot-circle'; break;
                                            case 'cidr': $typeIcon = 'fas fa-network-wired'; break;
                                            case 'range': $typeIcon = 'fas fa-arrows-alt-h'; break;
                                            case 'invalid': $typeIcon = 'fas fa-exclamation-triangle text-danger'; break;
                                        }
                                        ?>
                                        <i class="<?php echo $typeIcon; ?>"></i>
                                        <?php echo htmlspecialchars($result['original']); ?>
                                    </h6>
                                    
                                    <?php if ($result['type'] !== 'invalid' && $result['type'] !== 'single'): ?>
                                        <small class="text-muted">
                                            Range: <?php echo htmlspecialchars($result['start_ip']); ?> - <?php echo htmlspecialchars($result['end_ip']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <?php if ($result['type'] === 'invalid'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times"></i> <?php echo htmlspecialchars($result['error']); ?>
                                        </span>
                                    <?php elseif ($result['type'] === 'single'): ?>
                                        <?php if ($result['found']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> <?php echo htmlspecialchars($result['team']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-question-circle"></i> No team mapping found
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($result['found']): ?>
                                            <?php foreach ($result['teams'] as $team): ?>
                                                <span class="badge bg-success me-1">
                                                    <i class="fas fa-check"></i> <?php echo htmlspecialchars($team); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <br><small class="text-muted">
                                                Found <?php echo count($result['teams']); ?> overlapping team(s)
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-question-circle"></i> No team mappings found
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?php 
                            $validCount = count(array_filter($results, function($r) { return $r['type'] !== 'invalid'; }));
                            $foundCount = count(array_filter($results, function($r) { return isset($r['found']) && $r['found']; }));
                            $invalidCount = count(array_filter($results, function($r) { return $r['type'] === 'invalid'; }));
                            ?>
                            <small class="text-muted">
                                <strong>Summary:</strong> 
                                <?php echo $validCount; ?> valid entries, 
                                <?php echo $foundCount; ?> with team mappings
                                <?php if ($invalidCount > 0): ?>, <?php echo $invalidCount; ?> invalid<?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                CIDR and ranges may show multiple overlapping teams
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>