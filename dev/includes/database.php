<?php
function storage_path($file) {
    return __DIR__ . '/../data/' . $file;
}

function ensure_storage_dir() {
    $dir = dirname(storage_path('users.json'));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function load_storage($file, $default = []) {
    ensure_storage_dir();
    $path = storage_path($file);
    if (!file_exists($path)) {
        save_storage($file, $default);
        return $default;
    }

    $content = file_get_contents($path);
    $decoded = json_decode($content, true);
    return $decoded ?: $default;
}

function save_storage($file, $data) {
    ensure_storage_dir();
    $path = storage_path($file);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function seed_default_settings() {
    $defaults = [
        'site_name' => 'Bus Pass Admin',
        'maintenance_mode' => 'Off',
        'academic_year' => '2026-2027',
        'portal_theme' => 'Light',
        'email_notifications' => 'On',
    ];

    $settings = load_storage('settings.json', []);
    $changed = false;
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
            $changed = true;
        }
    }

    if ($changed) {
        save_storage('settings.json', $settings);
    }
}

function get_setting($key, $fallback = '') {
    $settings = load_storage('settings.json', []);
    return $settings[$key] ?? $fallback;
}

function save_setting($key, $value) {
    $settings = load_storage('settings.json', []);
    $settings[$key] = $value;
    save_storage('settings.json', $settings);
}

function get_all_users() {
    return load_storage('users.json', []);
}

function save_user($id, $name, $email, $role, $status) {
    $users = get_all_users();
    $updated = false;

    foreach ($users as $index => $user) {
        if ((int)$user['id'] === (int)$id) {
            $users[$index] = [
                'id' => (int)$id,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'status' => $status,
            ];
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $users[] = [
            'id' => $id ? (int)$id : time(),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status,
        ];
    }

    save_storage('users.json', $users);
}

function delete_user($id) {
    $users = array_values(array_filter(get_all_users(), function ($user) use ($id) {
        return (int)$user['id'] !== (int)$id;
    }));
    save_storage('users.json', $users);
}

function get_all_students() {
    return load_storage('students.json', []);
}

function save_student($id, $studentId, $name, $department, $course, $phone, $status) {
    $students = get_all_students();
    $updated = false;

    foreach ($students as $index => $student) {
        if ((int)$student['id'] === (int)$id) {
            $students[$index] = [
                'id' => (int)$id,
                'student_id' => $studentId,
                'name' => $name,
                'department' => $department,
                'course' => $course,
                'phone' => $phone,
                'status' => $status,
            ];
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $students[] = [
            'id' => $id ? (int)$id : time(),
            'student_id' => $studentId,
            'name' => $name,
            'department' => $department,
            'course' => $course,
            'phone' => $phone,
            'status' => $status,
        ];
    }

    save_storage('students.json', $students);
}

function delete_student($id) {
    $students = array_values(array_filter(get_all_students(), function ($student) use ($id) {
        return (int)$student['id'] !== (int)$id;
    }));
    save_storage('students.json', $students);
}
