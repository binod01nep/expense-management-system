<?php
session_start();
include('../config/db.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['name'];
    
    // Validate input
    if (empty($company_name)) {
        echo json_encode(['success' => false, 'message' => 'Company name is required']);
        exit();
    }
    
    // Update company
    $stmt = $conn->prepare("UPDATE companies SET company_name = ? WHERE company_id = ?");
    $stmt->bind_param("si", $company_name, $_SESSION['company_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Company updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating company: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
