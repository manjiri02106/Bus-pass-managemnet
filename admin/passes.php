<?php
/**
 * Admin Pass Listing, Search, Filtering and Pagination Portal
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/db.php';

require_admin();

$db = Database::connect();

// Handle Approve/Reject POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['pass_id'])) {
    $pass_id = intval($_POST['pass_id']);
    $action = $_POST['action'];
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    if (in_array($new_status, ['approved', 'rejected'])) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE passes SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $pass_id]);

            if ($new_status === 'approved') {
                $stmt = $db->prepare("UPDATE payments SET status = 'completed' WHERE pass_id = :pass_id");
                $stmt->execute(['pass_id' => $pass_id]);
            }
            $db->commit();
            set_flash_message('success', "Pass status updated successfully.");
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash_message('error', "Database error: " . $e->getMessage());
        }
        redirect('/Bus-pass-managemnet/admin/passes.php');
    }
}

// Check if this is an AJAX request
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $category_id = intval($_GET['category'] ?? 0);
    $route_id = intval($_GET['route'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 5;
    $offset = ($page - 1) * $limit;

    // Build query conditions
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $conditions[] = "(u.name LIKE :search OR u.email LIKE :search OR p.pass_number LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if (!empty($status)) {
        $conditions[] = "p.status = :status";
        $params['status'] = $status;
    }

    if ($category_id > 0) {
        $conditions[] = "p.category_id = :category_id";
        $params['category_id'] = $category_id;
    }

    if ($route_id > 0) {
        $conditions[] = "p.route_id = :route_id";
        $params['route_id'] = $route_id;
    }

    $where_sql = '';
    if (!empty($conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $conditions);
    }

    try {
        // Get total count
        $count_query = "
            SELECT COUNT(*) 
            FROM passes p
            INNER JOIN users u ON p.user_id = u.id
            $where_sql
        ";
        $stmt = $db->prepare($count_query);
        $stmt->execute($params);
        $total_rows = $stmt->fetchColumn();
        $total_pages = ceil($total_rows / $limit);

        // Get matching passes
        $data_query = "
            SELECT p.*, u.name AS user_name, u.email AS user_email,
                   r.source, r.destination, r.route_code,
                   c.name AS category_name,
                   pay.transaction_id, pay.status AS payment_status
            FROM passes p
            INNER JOIN users u ON p.user_id = u.id
            INNER JOIN routes r ON p.route_id = r.id
            INNER JOIN categories c ON p.category_id = c.id
            LEFT JOIN payments pay ON pay.pass_id = p.id
            $where_sql
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $db->prepare($data_query);
        // Bind parameters manually for correct integer evaluation
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $passes = $stmt->fetchAll();

        // Render HTML rows
        $html = '';
        if (empty($passes)) {
            $html .= '<tr><td colspan="8" style="text-align: center; color: var(--color-text-muted);">No records match criteria.</td></tr>';
        } else {
            foreach ($passes as $p) {
                $status_badge = get_status_badge($p['status']);
                $pay_badge = '';
                if ($p['payment_status'] === 'completed') {
                    $pay_badge = '<span class="badge badge-success"><i class="fas fa-check"></i> Paid</span>';
                } elseif ($p['payment_status'] === 'pending') {
                    $pay_badge = '<span class="badge badge-warning"><i class="fas fa-spinner"></i> Pending</span>';
                } else {
                    $pay_badge = '<span class="badge badge-danger"><i class="fas fa-times"></i> Unpaid</span>';
                }

                $actions = '';
                if ($p['status'] === 'pending') {
                    $actions = '
                        <form action="passes.php" method="POST" style="margin: 0; display: inline-block;">
                            <input type="hidden" name="pass_id" value="'.$p['id'].'">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;"><i class="fas fa-check"></i> Approve</button>
                        </form>
                        <form action="passes.php" method="POST" style="margin: 0; display: inline-block;">
                            <input type="hidden" name="pass_id" value="'.$p['id'].'">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 11px;"><i class="fas fa-ban"></i> Reject</button>
                        </form>
                    ';
                } else {
                    $actions = '<span style="color: var(--color-text-muted); font-size: 12px;"><i class="fas fa-lock"></i> Finalized</span>';
                }

                $avatar = '';
                if ($p['profile_pic']) {
                    $avatar = '<img src="/Bus-pass-managemnet/assets/uploads/'.e($p['profile_pic']).'" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 8px;">';
                } else {
                    $avatar = '<div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.05); display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; margin-right: 8px;"><i class="fas fa-user" style="font-size: 12px; color: var(--color-text-muted);"></i></div>';
                }

                $html .= '
                    <tr>
                        <td style="font-family: monospace; font-weight: 600;">'.e($p['pass_number']).'</td>
                        <td>
                            '.$avatar.'
                            <div style="display: inline-block; vertical-align: middle;">
                                <strong>'.e($p['user_name']).'</strong><br>
                                <span style="font-size: 12px; color: var(--color-text-muted);">'.e($p['user_email']).'</span>
                            </div>
                        </td>
                        <td>
                            <strong>'.e($p['category_name']).'</strong><br>
                            <span style="font-size: 12px; color: var(--color-text-muted);">'.e($p['route_code']).' ('.e($p['source']).' to '.e($p['destination']).')</span>
                        </td>
                        <td style="font-size: 13px;">'.e($p['start_date']).' to '.e($p['end_date']).'</td>
                        <td><strong>$'.number_format(e($p['price']), 2).'</strong></td>
                        <td>'.$status_badge.'</td>
                        <td>'.$pay_badge.'</td>
                        <td>'.$actions.'</td>
                    </tr>
                ';
            }
        }

        // Render Pagination HTML
        $pagination = '';
        if ($total_pages > 1) {
            $prev_disabled = ($page === 1) ? 'disabled' : '';
            $pagination .= '<button class="page-btn" data-page="'.($page - 1).'" '.$prev_disabled.'><i class="fas fa-chevron-left"></i></button>';

            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i === $page) ? 'active' : '';
                $pagination .= '<button class="page-btn '.$active_class.'" data-page="'.$i.'">'.$i.'</button>';
            }

            $next_disabled = ($page === $total_pages) ? 'disabled' : '';
            $pagination .= '<button class="page-btn" data-page="'.($page + 1).'" '.$next_disabled.'><i class="fas fa-chevron-right"></i></button>';
        }

        echo json_encode([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Fetch categories and routes for filter dropdowns on initial static page load
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();

    $stmt = $db->query("SELECT * FROM routes ORDER BY route_code ASC");
    $routes = $stmt->fetchAll();
} catch (Exception $e) {
    set_flash_message('error', 'Error initializing search components.');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 30px;">
    <h1 class="text-gradient">Manage Passenger Passes</h1>
    <p style="color: var(--color-text-muted);">Real-time search, status filtering, and verification approval queue.</p>
</div>

<!-- Dynamic Search & Filters Board -->
<div class="glass-card" style="margin-bottom: 30px;">
    <form id="admin-search-form" onsubmit="return false;">
        <div class="filter-bar">
            <!-- Search Query Input -->
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="search_query" class="form-control" placeholder="Search by name, email, or pass number...">
            </div>

            <!-- Status Filter -->
            <div class="filter-select">
                <select id="filter_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Active</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="filter-select">
                <select id="filter_category" class="form-control">
                    <option value="">All Tiers</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Route Filter -->
            <div class="filter-select">
                <select id="filter_route" class="form-control">
                    <option value="">All Routes</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>"><?= e($route['route_code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Database Table of Results -->
<div class="glass-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pass Number</th>
                    <th>Passenger</th>
                    <th>Specifications</th>
                    <th>Validity Range</th>
                    <th>Price Paid</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="passes-table-body">
                <!-- Injected via AJAX (search.js) -->
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--color-text-muted);">
                        <i class="fas fa-spinner fa-spin"></i> Initializing pass ledger...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination links -->
    <div id="pagination-container" class="pagination">
        <!-- Injected via AJAX -->
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
