<?php
// Helper functions

function getAgeColor($dateCreated, $dateClosed = null) {
    $startDate = new DateTime($dateCreated);
    $endDate = $dateClosed ? new DateTime($dateClosed) : new DateTime();
    
    $diff = $startDate->diff($endDate);
    $daysDiff = $diff->days;
    
    if ($daysDiff <= 7) {
        return 'success'; // Green
    } elseif ($daysDiff <= 30) {
        return 'warning'; // Yellow
    } else {
        return 'danger'; // Red
    }
}

function getAgeText($dateCreated, $dateClosed = null) {
    $startDate = new DateTime($dateCreated);
    $endDate = $dateClosed ? new DateTime($dateClosed) : new DateTime();
    
    $diff = $startDate->diff($endDate);
    $daysDiff = $diff->days;
    
    if ($daysDiff == 0) {
        return 'Today';
    } elseif ($daysDiff == 1) {
        return '1 day';
    } else {
        return $daysDiff . ' days';
    }
}

function searchFindings($pdo, $search, $status) {
    $sql = "SELECT * FROM findings WHERE status = :status";
    $params = [':status' => $status];
    
    if (!empty($search)) {
        $sql .= " AND (title LIKE :search OR description LIKE :search OR comment LIKE :search OR team LIKE :search OR contact_person LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY date_created DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllFindings($pdo, $status) {
    $stmt = $pdo->prepare("SELECT * FROM findings WHERE status = :status ORDER BY date_created DESC");
    $stmt->execute([':status' => $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addFinding($pdo, $title, $description, $comment = null, $team = null, $contactPerson = null) {
    $stmt = $pdo->prepare("INSERT INTO findings (title, description, comment, team, contact_person, status) VALUES (:title, :description, :comment, :team, :contact_person, 'open')");
    return $stmt->execute([
        ':title' => $title, 
        ':description' => $description, 
        ':comment' => $comment,
        ':team' => $team,
        ':contact_person' => $contactPerson
    ]);
}

function closeFinding($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE findings SET status = 'closed', date_closed = NOW() WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function reopenFinding($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE findings SET status = 'open', date_closed = NULL WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function deleteFinding($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM findings WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function getFindingById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM findings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* --------------------------------------------------------------------------
 * Authentication and IP range helper functions
 *
 * The following functions support the new authentication system and IP
 * mapping feature. They are intentionally separated from the core
 * findings logic above. Each function accepts a PDO instance and
 * performs safe database operations using prepared statements.
 */

/**
 * Add a new user to the system.
 *
 * @param PDO    $pdo     Database connection
 * @param string $username Username of the new user
 * @param string $password Plain text password; will be hashed
 * @param string $role     User role ('admin' or 'user')
 * @return bool            True on success, false on failure
 */
function addUser($pdo, $username, $password, $role = 'user') {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
    return $stmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword,
        ':role' => $role
    ]);
}

/**
 * Retrieve all users from the database.
 *
 * @param PDO $pdo Database connection
 * @return array  List of users with id, username and role
 */
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY username");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Delete a user by their ID.
 *
 * @param PDO $pdo Database connection
 * @param int $id  User ID
 * @return bool    True on success, false on failure
 */
function deleteUser($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Add an IP range mapping to a team.
 *
 * @param PDO    $pdo      Database connection
 * @param string $startIp  Starting IP address (string format)
 * @param string $endIp    Ending IP address (string format)
 * @param string $teamName Name of the team associated with this range
 * @return bool            True on success, false on failure
 */
function addIpRange($pdo, $startIp, $endIp, $teamName) {
    // Validate IP addresses
    if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || 
        !filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false; // Invalid IP format
    }
    
    // Convert dotted IPs to unsigned integer representation for easy comparisons
    $startIpLong = sprintf('%u', ip2long($startIp));
    $endIpLong = sprintf('%u', ip2long($endIp));
    
    // Validate that start IP is not greater than end IP
    if ($startIpLong > $endIpLong) {
        return false; // Invalid range order
    }
    
    $stmt = $pdo->prepare("INSERT INTO ip_ranges (start_ip, end_ip, team, start_ip_long, end_ip_long) VALUES (:start_ip, :end_ip, :team, :start_ip_long, :end_ip_long)");
    return $stmt->execute([
        ':start_ip' => $startIp,
        ':end_ip' => $endIp,
        ':team' => $teamName,
        ':start_ip_long' => $startIpLong,
        ':end_ip_long' => $endIpLong
    ]);
}

/**
 * Retrieve all IP ranges.
 *
 * @param PDO $pdo Database connection
 * @return array  List of IP range entries
 */
function getAllIpRanges($pdo) {
    $stmt = $pdo->query("SELECT id, start_ip, end_ip, team FROM ip_ranges ORDER BY start_ip_long");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Delete an IP range by ID.
 *
 * @param PDO $pdo Database connection
 * @param int $id  Range ID
 * @return bool    True on success, false on failure
 */
function deleteIpRange($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM ip_ranges WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Update an existing IP range.
 *
 * @param PDO $pdo Database connection
 * @param int $id IP range ID to update
 * @param string $startIp New start IP address
 * @param string $endIp New end IP address
 * @param string $teamName New team name
 * @return bool True on success, false on failure
 */
function updateIpRange($pdo, $id, $startIp, $endIp, $teamName) {
    $startLong = sprintf('%u', ip2long($startIp));
    $endLong = sprintf('%u', ip2long($endIp));
    
    $stmt = $pdo->prepare("UPDATE ip_ranges SET start_ip = :start_ip, end_ip = :end_ip, team = :team, start_ip_long = :start_ip_long, end_ip_long = :end_ip_long WHERE id = :id");
    
    return $stmt->execute([
        ':id' => $id,
        ':start_ip' => $startIp,
        ':end_ip' => $endIp,
        ':team' => $teamName,
        ':start_ip_long' => $startLong,
        ':end_ip_long' => $endLong
    ]);
}

/**
 * Get a single IP range by ID.
 *
 * @param PDO $pdo Database connection
 * @param int $id IP range ID
 * @return array|false IP range data or false if not found
 */
function getIpRangeById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM ip_ranges WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Resolve an IP address to a team name if a mapping exists.
 *
 * Converts the dotted IP string into an unsigned integer and performs a
 * range lookup against the ip_ranges table. If a matching range is
 * found the associated team name is returned; otherwise null.
 *
 * @param PDO    $pdo       Database connection
 * @param string $ipAddress IPv4 or IPv6 address
 * @return string|null      Team name or null if not found
 */
function getTeamByIp($pdo, $ipAddress) {
    // Only IPv4 addresses are supported for integer comparison; other
    // addresses will not match any entry.
    $ipLong = sprintf('%u', ip2long($ipAddress));
    $stmt = $pdo->prepare("SELECT team FROM ip_ranges WHERE start_ip_long <= :ip AND end_ip_long >= :ip LIMIT 1");
    $stmt->execute([':ip' => $ipLong]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['team'] : null;
}

/**
 * Get ALL teams by IP address (for IPs that belong to multiple teams).
 *
 * @param PDO    $pdo       Database connection
 * @param string $ipAddress IPv4 or IPv6 address
 * @return array            Array of team names
 */
function getAllTeamsByIp($pdo, $ipAddress) {
    // Only IPv4 addresses are supported for integer comparison; other
    // addresses will not match any entry.
    $ipLong = sprintf('%u', ip2long($ipAddress));
    $stmt = $pdo->prepare("SELECT DISTINCT team FROM ip_ranges WHERE start_ip_long <= :ip AND end_ip_long >= :ip ORDER BY team");
    $stmt->execute([':ip' => $ipLong]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Parse CIDR notation into start and end IP addresses.
 *
 * @param string $cidr CIDR notation (e.g., "192.168.1.0/24")
 * @return array|null Array with 'start' and 'end' IP addresses, or null if invalid
 */
function parseCidr($cidr) {
    if (strpos($cidr, '/') === false) {
        return null;
    }
    
    list($ip, $prefix) = explode('/', $cidr);
    $prefix = (int)$prefix;
    
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $prefix < 0 || $prefix > 32) {
        return null;
    }
    
    $ipLong = ip2long($ip);
    $mask = -1 << (32 - $prefix);
    $networkLong = $ipLong & $mask;
    $broadcastLong = $networkLong | ~$mask;
    
    return [
        'start' => long2ip($networkLong),
        'end' => long2ip($broadcastLong)
    ];
}

/**
 * Parse input string containing IPs, IP ranges, or CIDR notation.
 * Supports comma-separated, space-separated, or newline-separated entries.
 *
 * @param string $input Input string with IPs/ranges/CIDR
 * @return array Array of parsed IP entries with their types
 */
function parseIpInput($input) {
    $entries = [];
    $input = trim($input);
    
    if (empty($input)) {
        return $entries;
    }
    
    // First, normalize spaces around dashes to preserve ranges like "10.0.0.1 - 10.0.0.255"
    $input = preg_replace('/\s*-\s*/', '-', $input);
    
    // Split by comma, newline, or spaces (but preserve ranges that we just normalized)
    $lines = preg_split('/[,\n\r\s]+/', $input);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        // Check if it's CIDR notation
        if (strpos($line, '/') !== false) {
            $cidr = parseCidr($line);
            if ($cidr) {
                $entries[] = [
                    'type' => 'cidr',
                    'original' => $line,
                    'start_ip' => $cidr['start'],
                    'end_ip' => $cidr['end']
                ];
            } else {
                $entries[] = [
                    'type' => 'invalid',
                    'original' => $line,
                    'error' => 'Invalid CIDR notation'
                ];
            }
        }
        // Check if it's a range (IP-IP)
        elseif (strpos($line, '-') !== false) {
            $parts = explode('-', $line, 2);
            $startIp = trim($parts[0]);
            $endIp = trim($parts[1]);
            
            if (filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
                filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $entries[] = [
                    'type' => 'range',
                    'original' => $line,
                    'start_ip' => $startIp,
                    'end_ip' => $endIp
                ];
            } else {
                $entries[] = [
                    'type' => 'invalid',
                    'original' => $line,
                    'error' => 'Invalid IP range format'
                ];
            }
        }
        // Single IP address
        elseif (filter_var($line, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $entries[] = [
                'type' => 'single',
                'original' => $line,
                'start_ip' => $line,
                'end_ip' => $line
            ];
        } else {
            $entries[] = [
                'type' => 'invalid',
                'original' => $line,
                'error' => 'Invalid IP address format'
            ];
        }
    }
    
    return $entries;
}

/**
 * Get team mappings for multiple IP addresses, ranges, or CIDR blocks.
 *
 * @param PDO $pdo Database connection
 * @param string $input Input string with IPs/ranges/CIDR
 * @return array Array with parsed entries and their team mappings
 */
function getTeamsByIpInput($pdo, $input) {
    $entries = parseIpInput($input);
    $results = [];
    
    foreach ($entries as $entry) {
        if ($entry['type'] === 'invalid') {
            $results[] = $entry;
            continue;
        }
        
        // For single IPs, get ALL teams that contain this IP
        if ($entry['type'] === 'single') {
            $teams = getAllTeamsByIp($pdo, $entry['start_ip']);
            $results[] = array_merge($entry, [
                'teams' => $teams,
                'found' => !empty($teams)
            ]);
        } else {
            // For ranges and CIDR, we need to check if any part of the range matches existing mappings
            $startLong = sprintf('%u', ip2long($entry['start_ip']));
            $endLong = sprintf('%u', ip2long($entry['end_ip']));
            
            // Find all ranges that overlap with this input range
            $stmt = $pdo->prepare("
                SELECT DISTINCT team 
                FROM ip_ranges 
                WHERE (start_ip_long <= :end_long AND end_ip_long >= :start_long)
            ");
            $stmt->execute([':start_long' => $startLong, ':end_long' => $endLong]);
            $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $results[] = array_merge($entry, [
                'teams' => $teams,
                'found' => !empty($teams)
            ]);
        }
    }
    
    return $results;
}

/**
 * Add IP range from CIDR notation.
 *
 * @param PDO $pdo Database connection
 * @param string $cidr CIDR notation (e.g., "192.168.1.0/24")
 * @param string $teamName Name of the team
 * @return bool True on success, false on failure
 */
function addIpRangeFromCidr($pdo, $cidr, $teamName) {
    $parsed = parseCidr($cidr);
    if (!$parsed) {
        return false;
    }
    
    return addIpRange($pdo, $parsed['start'], $parsed['end'], $teamName);
}

/**
 * Expand an IP range into individual IP addresses.
 *
 * @param string $startIp Starting IP address
 * @param string $endIp Ending IP address
 * @param int $maxIps Maximum number of IPs to expand (safety limit)
 * @return array Array of individual IP addresses, or empty array if range is too large
 */
function expandIpRange($startIp, $endIp, $maxIps = 1000) {
    $startLong = sprintf('%u', ip2long($startIp));
    $endLong = sprintf('%u', ip2long($endIp));
    
    if ($startLong > $endLong) {
        return []; // Invalid range
    }
    
    $count = $endLong - $startLong + 1;
    if ($count > $maxIps) {
        return []; // Range too large for individual IP expansion
    }
    
    $ips = [];
    for ($i = $startLong; $i <= $endLong; $i++) {
        $ips[] = long2ip($i);
    }
    
    return $ips;
}

/**
 * Add multiple individual IPs from a list and map them all to the same team.
 * Supports individual IPs and IP ranges (which get expanded to individual IPs).
 * Each IP becomes its own range entry (start_ip = end_ip).
 *
 * @param PDO $pdo Database connection
 * @param string $ipList Space/comma/newline separated list of IPs and ranges
 * @param string $teamName Name of the team
 * @return array Result with success status, count of added IPs, and any errors
 */
function addIpListToTeam($pdo, $ipList, $teamName) {
    $entries = parseIpInput($ipList);
    $added = 0;
    $errors = [];
    $validIps = [];
    $rangeCount = 0;
    
    // Process each entry 
    foreach ($entries as $entry) {
        if ($entry['type'] === 'invalid') {
            $errors[] = "Invalid: " . $entry['original'] . " - " . $entry['error'];
        } elseif ($entry['type'] === 'single') {
            // Individual IPs go to validIps array for individual storage
            $validIps[] = $entry['start_ip'];
        } elseif ($entry['type'] === 'range') {
            // Store IP ranges as compressed ranges (not expanded individual IPs)
            if (addIpRange($pdo, $entry['start_ip'], $entry['end_ip'], $teamName)) {
                $added++;
                $rangeCount++;
            } else {
                $errors[] = "Failed to add IP range: " . $entry['original'];
            }
        } elseif ($entry['type'] === 'cidr') {
            // Add CIDR block as a single range entry
            if (addIpRange($pdo, $entry['start_ip'], $entry['end_ip'], $teamName)) {
                $added++;
                $rangeCount++;
            } else {
                $errors[] = "Failed to add CIDR range: " . $entry['original'];
            }
        } else {
            $errors[] = "Skipped: " . $entry['original'] . " - Unsupported entry type";
        }
    }
    
    // Remove duplicates from individual IPs
    $validIps = array_unique($validIps);
    
    // Add each individual IP as its own single-IP range
    foreach ($validIps as $ip) {
        if (addIpRange($pdo, $ip, $ip, $teamName)) {
            $added++;
        } else {
            $errors[] = "Failed to add individual IP: " . $ip;
        }
    }
    
    // Check if we have any results
    if ($added === 0) {
        return [
            'success' => false,
            'added' => 0,
            'errors' => empty($errors) ? ['No valid IPs found in input'] : $errors
        ];
    }
    
    return [
        'success' => $added > 0,
        'added' => $added,
        'errors' => $errors,
        'total_ranges_and_cidrs' => $rangeCount,
        'total_individual_ips' => count($validIps)
    ];
}
?>