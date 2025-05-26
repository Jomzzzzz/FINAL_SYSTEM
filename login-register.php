<?php
session_start();
require_once 'config.php';
require_once 'helpers.php'; // Defines redirectUserByRole() and SUPER_ADMIN_EMAIL

$allowed_domains = ['gmail.com', 'gordoncollege.edu.ph'];

// Helper: Check allowed email domain (case-insensitive)
function isAllowedDomain(string $email, array $allowed_domains): bool
{
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    return in_array($domain, array_map('strtolower', $allowed_domains));
}

// Helper: Redirect with error and store which form was active
function redirectWithError(string $formType, string $errorMessage)
{
    $_SESSION[$formType . '_error'] = $errorMessage;
    $_SESSION['active_form'] = $formType;
    header("Location: landing-page.php");
    exit();
}

// ---------- REGISTER ----------
if (isset($_POST['register'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = strtolower(trim($_POST['role'] ?? 'user')); // Default role if missing
    $password   = $_POST['password'] ?? '';

    // Basic validation: required fields
    if (!$first_name || !$last_name || !$email || !$password) {
        redirectWithError('register', 'Please fill in all required fields.');
    }

    // Email domain check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAllowedDomain($email, $allowed_domains)) {
        redirectWithError('register', 'Only Gmail or Gordon College emails are allowed.');
    }

    // Password strength check
    $passwordErrors = [];
    if (strlen($password) < 8) $passwordErrors[] = 'at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $passwordErrors[] = 'an uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $passwordErrors[] = 'a lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $passwordErrors[] = 'a number';

    if (!empty($passwordErrors)) {
        $errorText = 'Password must contain ' . implode(', ', $passwordErrors) . '.';
        redirectWithError('register', $errorText);
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectWithError('register', 'Email is already registered.');
    }
    $stmt->close();

    // Default profile image and hashed password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $profileImage = 'default.png';

    // Insert new user
    $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssss", $first_name, $last_name, $email, $hashedPassword, $role, $profileImage);

    if ($insert->execute()) {
        $_SESSION['register_success'] = 'Registration successful. You can now log in.';
        $insert->close();
        $_SESSION['active_form'] = 'login'; // Switch to login form after successful registration
        header("Location: landing-page.php");
        exit();
    } else {
        $insert->close();
        redirectWithError('register', 'Registration failed: ' . $conn->error);
    }
}

// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        redirectWithError('login', 'Please enter email and password.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAllowedDomain($email, $allowed_domains)) {
        redirectWithError('login', 'Only Gmail or Gordon College emails are allowed.');
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        $stmt->close();
        redirectWithError('login', 'Database error during login.');
    }

    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $stmt->close();

        if (password_verify($password, $user['password'])) {
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
            $_SESSION['role'] = strtolower($user['role']);
            $_SESSION['status'] = strtolower(trim($user['status'] ?? ''));

            // Check admin approval status
            if ($_SESSION['role'] === 'admin' && $_SESSION['status'] !== 'approved') {
                $_SESSION['not_approved'] = 'Your admin account is not approved yet. Please wait for approval.';
                header("Location: landing-page.php");
                exit();
            }

            // Redirect user based on role
            redirectUserByRole($user);
            exit();
        } else {
            redirectWithError('login', 'Incorrect password.');
        }
    } else {
        $stmt->close();
        redirectWithError('login', 'Account not found.');
    }
}
