<?php
require_once 'config.php';

// Define user info
$email = '202310944@gordoncollege.edu.ph';
// Split name into first and last (adjust this as needed)
$first_name = 'Super';
$last_name = 'Admin';
$password = password_hash('1', PASSWORD_DEFAULT); // Securely hash the password
$role = 'super_admin';
$status = 'approved';
$profile_image = 'default.png';
$course = '';
$year = '';
$block = '';
$created_at = date('Y-m-d H:i:s');

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Super admin already exists.";
} else {
    $stmt->close();

    // Insert super admin
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, profile_image, created_at, status, course, year, block) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssssssssss",
        $first_name,
        $last_name,
        $email,
        $password,
        $role,
        $profile_image,
        $created_at,
        $status,
        $course,
        $year,
        $block
    );

    if ($stmt->execute()) {
        echo "Super admin inserted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
