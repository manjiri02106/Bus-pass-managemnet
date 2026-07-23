<?php
require_once __DIR__ . '/database.php';

function data_path($file) {
    return __DIR__ . '/../data/' . $file;
}

function ensure_data_dir() {
    $dir = dirname(data_path('users.json'));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function load_json($file, $default = []) {
    ensure_data_dir();
    $path = data_path($file);
    if (!file_exists($path)) {
        save_json($file, $default);
        return $default;
    }

    $content = file_get_contents($path);
    $decoded = json_decode($content, true);
    return $decoded ?: $default;
}

function save_json($file, $data) {
    ensure_data_dir();
    $path = data_path($file);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function page_header($title, $active) {
    $menu = [
        ['Dashboard', 'index.php'],
        ['User Management', 'users.php'],
        ['Student Management', 'students.php'],
        ['System Settings', 'settings.php'],
    ];
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>'; 
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>'; 
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="layout">';
    echo '<aside class="sidebar">';
    echo '<h1>Admin Portal</h1>';
    echo '<p>Bus Pass Management</p>';
    echo '<nav>';
    foreach ($menu as $item) {
        $label = $item[0];
        $link = $item[1];
        $class = ($active === $label) ? 'active' : '';
        echo '<a class="' . $class . '" href="' . $link . '">' . $label . '</a>';
    }
    echo '</nav>';
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        echo '<a href="logout.php">Logout</a>';
    }
    echo '</aside>';
    echo '<main class="content">';
}

function page_footer() {
    echo '</main>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

function dashboard_stats() {
    seed_default_settings();
    $users = get_all_users();
    $students = get_all_students();
    $siteName = get_setting('site_name', 'Bus Pass Admin');
    $maintenance = get_setting('maintenance_mode', 'Off');

    return [
        'users' => count($users),
        'students' => count($students),
        'site_name' => $siteName,
        'maintenance' => $maintenance,
    ];
}
