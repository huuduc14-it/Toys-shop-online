<?php
    require_once('db_connect.php');
    function login($email, $password) {
        $conn = create_connection();
        if (!$conn) {
            return false;
        }
    
        $tables = [
            'users' => 'users',
            'admin' => 'admin',
            'staff' => 'staff'
        ];
    
        foreach ($tables as $table => $role) {
            $sql = "SELECT * FROM $table WHERE email = ?";
            $stm = $conn->prepare($sql);
            if (!$stm) continue;
    
            $stm->bind_param('s', $email);
            if ($stm->execute()) {
                $result = $stm->get_result();
                if ($result && $result->num_rows === 1) {
                    $data = $result->fetch_assoc();
                    if (password_verify($password, $data['password'])) {
                        $_SESSION['user'] = $data['email'];
                        $_SESSION['role'] = $role;
                        $_SESSION['user_id'] = $data['id'];
                        $_SESSION['user_name'] = $data['full_name'] ?? $data['name'] ?? '';
                        $_SESSION['user_email'] = $data['email'];
                        $stm->close();
                        $conn->close();
                        return true;
                    }
                }
            }
    
            $stm->close();
        }
    
        $conn->close();
        return false;
    }
?>