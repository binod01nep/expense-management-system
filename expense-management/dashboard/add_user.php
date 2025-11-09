<?php
session_start();
include('../config/db.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // In production, hash the password
    $role = $_POST['role'];
    $company_id = $_SESSION['company_id'];
    $organization = $_POST['organization'];
    $country = $_POST['country'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = 'All fields are required';
        header("Location: admin.php#users");
        exit();
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Email already exists';
        header("Location: admin.php#users");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, organization, country, company_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $name, $email, $password, $role, $organization, $country, $company_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User added successfully';
    } else {
        $_SESSION['error'] = 'Error adding user: ' . $stmt->error;
    }
    
    $stmt->close();
    $checkStmt->close();
    $conn->close();

    header("Location: admin.php#users");
    exit();
}
?>