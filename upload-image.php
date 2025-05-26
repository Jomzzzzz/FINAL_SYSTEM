<?php
session_start();
include('config.php'); // Make sure you include the database connection

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = basename($_FILES['profile_image']['name']);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array(strtolower($fileExtension), $allowed)) {
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadPath = 'uploads/' . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            // Update the database with the new image path
            $email = $_SESSION['email']; // Assuming the user's email is stored in session
            $query = "UPDATE users SET profile_image = ? WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $newFileName, $email);
            $stmt->execute();
            
            // Update session variable with new profile image
            $_SESSION['profile_image'] = $newFileName;

            header("Location: admin/admin_dashboard.php");
            exit();
        } else {
            echo "Failed to upload file.";
        }
    } else {
        echo "Invalid file type.";
    }
} else {
    echo "No file selected or upload error.";
}
?>
