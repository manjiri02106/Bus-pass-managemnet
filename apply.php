<?php
/**
 * Bus Pass Application Page (Multi-Step Stepper Form)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/database/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$routes = [];
$categories = [];
$error = '';

try {
    $db = Database::connect();
    
    // Fetch routes
    $stmt = $db->query("SELECT * FROM routes ORDER BY route_code ASC");
    $routes = $stmt->fetchAll();

    // Fetch categories
    $stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error querying system data.';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id = intval($_POST['route_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $duration = intval($_POST['duration'] ?? 1);
    $card_number = trim($_POST['card_number'] ?? '');
    
    // Validate inputs
    if ($route_id === 0 || $category_id === 0 || empty($card_number)) {
        $error = 'Please complete all steps and payment fields.';
    } else {
        // Securely handle profile photo upload
        $profile_pic_filename = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_name = $_FILES['profile_pic']['name'];
            $file_size = $_FILES['profile_pic']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/jpg'];
            
            // Perform security checks
            if (!in_array($file_ext, $allowed_exts)) {
                $error = 'Only JPG, JPEG, and PNG images are allowed.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file_tmp);
                finfo_close($finfo);

                if (!in_array($mime_type, $allowed_mimes)) {
                    $error = 'Invalid image file format.';
                } elseif ($file_size > 2 * 1024 * 1024) { // Max 2MB
                    $error = 'Profile image must be less than 2MB.';
                } else {
                    // Create directory if not exists
                    $upload_dir = __DIR__ . '/assets/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Secure unique name
                    $profile_pic_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                    if (!move_uploaded_file($file_tmp, $upload_dir . $profile_pic_filename)) {
                        $error = 'Failed to upload profile picture.';
                    }
                }
            }
        }

        if (empty($error)) {
            try {
                // Calculate price serverside to prevent user tempering
                $stmt = $db->prepare("SELECT standard_price FROM routes WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $route_id]);
                $route = $stmt->fetch();

                $stmt = $db->prepare("SELECT discount_percentage FROM categories WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $category_id]);
                $category = $stmt->fetch();

                if ($route && $category) {
                    $base_price = floatval($route['standard_price']) * $duration;
                    $discount = $base_price * (floatval($category['discount_percentage']) / 100);
                    $final_price = $base_price - $discount;

                    // Expiry calculations
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime("+$duration month"));

                    $pass_num = generate_pass_number();
                    $qr_code_data = 'VAL-' . $pass_num;

                    $db->beginTransaction();

                    // 1. Create Pass record
                    $stmt = $db->prepare("
                        INSERT INTO passes (pass_number, user_id, category_id, route_id, start_date, end_date, price, status, qr_code_data) 
                        VALUES (:pass_number, :user_id, :category_id, :route_id, :start_date, :end_date, :price, 'pending', :qr_code_data)
                    ");
                    $stmt->execute([
                        'pass_number' => $pass_num,
                        'user_id' => $user_id,
                        'category_id' => $category_id,
                        'route_id' => $route_id,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'price' => $final_price,
                        'qr_code_data' => $qr_code_data
                    ]);
                    $pass_id = $db->lastInsertId();

                    // 2. Update user profile photo if uploaded
                    if ($profile_pic_filename) {
                        $stmt = $db->prepare("UPDATE users SET profile_pic = :pic WHERE id = :id");
                        $stmt->execute([
                            'pic' => $profile_pic_filename,
                            'id' => $user_id
                        ]);
                    }

                    // 3. Create simulated Payment transaction
                    $txn_id = 'TXN' . strtoupper(bin2hex(random_bytes(6)));
                    $stmt = $db->prepare("
                        INSERT INTO payments (pass_id, amount, transaction_id, status)
                        VALUES (:pass_id, :amount, :transaction_id, 'completed')
                    ");
                    $stmt->execute([
                        'pass_id' => $pass_id,
                        'amount' => $final_price,
                        'transaction_id' => $txn_id
                    ]);

                    $db->commit();

                    set_flash_message('success', 'Pass application submitted! Admin approval is pending.');
                    redirect('/Bus-pass-managemnet/dashboard.php');
                } else {
                    $error = 'Invalid route or category selected.';
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'System Database Error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 650px; margin: 0 auto;">
    <h1 class="text-gradient" style="text-align: center; margin-bottom: 30px;">Digital Bus Pass Application</h1>

    <!-- Stepper Navigation Indicators -->
    <div class="stepper">
        <div class="step-indicator active">
            <div class="step-num">1</div>
            <div class="step-label">Identify Photo</div>
        </div>
        <div class="step-indicator">
            <div class="step-num">2</div>
            <div class="step-label">Pass Specs</div>
        </div>
        <div class="step-indicator">
            <div class="step-num">3</div>
            <div class="step-label">Checkout</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--border-radius-md); padding: 12px; color: var(--color-danger); font-size: 14px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form class="glass-card multi-step-form" action="apply.php" method="POST" enctype="multipart/form-data">
        
        <!-- STEP 1: Identification & Photo -->
        <div class="step-content active">
            <h3 class="text-gradient" style="margin-bottom: 15px;"><i class="fas fa-camera"></i> Step 1: Upload Passenger Photo</h3>
            <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 25px;">Please upload a passport-sized profile photo. This photo will be printed directly onto your digital ticket for inspector validation.</p>
            
            <div class="form-group" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <div id="image-preview" style="width: 140px; height: 140px; border-radius: var(--border-radius-md); border: 2px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; background-size: cover; background-position: center; font-size: 36px; color: var(--color-text-muted);">
                    <i class="fas fa-user-circle"></i>
                </div>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/png, image/jpeg, image/jpg" class="form-control" style="display: none;" onchange="previewImage(this, 'image-preview')">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('profile_pic').click()"><i class="fas fa-upload"></i> Choose Photo</button>
                <span style="font-size: 11px; color: var(--color-text-muted);">Max file size: 2MB. Format: JPG, PNG</span>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 30px;">
                <button type="button" class="btn btn-primary btn-next">Next Step <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- STEP 2: Pass Specs Selection -->
        <div class="step-content">
            <h3 class="text-gradient" style="margin-bottom: 15px;"><i class="fas fa-sliders-h"></i> Step 2: Choose Route & Category</h3>
            <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 25px;">Select your source, destination, pass discount type, and validity period.</p>
            
            <div class="form-group">
                <label for="route_id" class="form-label">Municipal Transit Route</label>
                <select id="route_id" name="route_id" class="form-control" required>
                    <option value="" disabled selected>-- Select Route --</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>" data-price="<?= $route['standard_price'] ?>">
                            <?= e($route['route_code']) ?>: <?= e($route['source']) ?> to <?= e($route['destination']) ?> ($<?= e(number_format($route['standard_price'], 2)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="category_id" class="form-label">Pass Discount Category</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="" disabled selected>-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-discount="<?= $cat['discount_percentage'] ?>">
                            <?= e($cat['name']) ?> (<?= e(number_format($cat['discount_percentage'], 0)) ?>% Discount)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="duration" class="form-label">Pass Duration (Months)</label>
                <select id="duration" name="duration" class="form-control" required>
                    <option value="1" selected>1 Month Pass</option>
                    <option value="3">3 Months Pass (Quarterly)</option>
                    <option value="6">6 Months Pass (Semi-Annual)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <button type="button" class="btn btn-secondary btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-next">Next Step <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- STEP 3: Simulated Checkout Payment Gateway -->
        <div class="step-content">
            <h3 class="text-gradient" style="margin-bottom: 15px;"><i class="fas fa-credit-card"></i> Step 3: Fare Payment Simulator</h3>
            <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 25px;">Confirm your pass pricing specifications and complete checkout simulation.</p>
            
            <!-- Fare Summary Card -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--panel-border); border-radius: var(--border-radius-md); padding: 20px; margin-bottom: 25px;">
                <h4 style="margin-bottom: 15px; font-size: 15px; color: var(--color-text-muted); text-transform: uppercase;">Payment Calculation</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Base Fare Subtotal</span>
                    <strong id="calc_base_price">$0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--color-success);">
                    <span>Discount Applied</span>
                    <strong id="calc_discount">-$0.00 (0%)</strong>
                </div>
                <hr style="border-color: rgba(255,255,255,0.08); margin: 15px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 18px;">
                    <span>Total Fare Due</span>
                    <strong id="calc_final_price" class="text-gradient">$0.00</strong>
                </div>
                <input type="hidden" id="price_input" name="price" value="0.00">
            </div>

            <!-- Simulated Credit Card Inputs -->
            <div class="form-group">
                <label for="card_holder" class="form-label">Cardholder Name</label>
                <input type="text" id="card_holder" class="form-control" placeholder="Jane Doe" required>
            </div>

            <div class="form-group">
                <label for="card_number" class="form-label">Credit Card Number</label>
                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="4111 2222 3333 4444" required maxlength="19">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="card_exp" class="form-label">Expiry Date</label>
                    <input type="text" id="card_exp" class="form-control" placeholder="MM/YY" required maxlength="5">
                </div>
                <div class="form-group">
                    <label for="card_cvv" class="form-label">CVV Code</label>
                    <input type="password" id="card_cvv" class="form-control" placeholder="•••" required maxlength="3">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <button type="button" class="btn btn-secondary btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-wallet"></i> Pay & Submit Application</button>
            </div>
        </div>

    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
