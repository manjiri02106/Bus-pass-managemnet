<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Create a new user
     */
    public function createUser($data) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (first_name, last_name, email, phone, address, city, state, postal_code, user_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->bind_param(
                "sssssssss",
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['user_type']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $stmt->error);
            }
            
            $userId = $this->db->lastInsertId();
            $stmt->close();
            
            return array(
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully'
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get user details
     */
    public function getUserDetails($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Update user details
     */
    public function updateUser($userId, $data) {
        try {
            $fields = array();
            $params = array();
            $types = '';
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'user_type'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                    $types .= 's';
                }
            }
            
            if (empty($fields)) {
                return array('success' => false, 'error' => 'No valid fields to update');
            }
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $userId;
            $types .= 'i';
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user: " . $stmt->error);
            }
            
            $stmt->close();
            
            return array(
                'success' => true,
                'message' => 'User updated successfully'
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get all users
     */
    public function getAllUsers($limit = 10, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
    
    /**
     * Get total users count
     */
    public function getTotalUsersCount() {
        $result = $this->db->query("SELECT COUNT(*) as total FROM users");
        return $result->fetch_assoc()['total'];
    }
    
    /**
     * Get users by type
     */
    public function getUsersByType($userType, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE user_type = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        
        $stmt->bind_param("sii", $userType, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
}

$user = new User();
?>
