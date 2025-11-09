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
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'];
    
    // Validate input
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit();
    }
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit();
    }
    
    // Check if user exists and belongs to the same company
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND company_id = ?");
    $checkStmt->bind_param("ii", $user_id, $_SESSION['company_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Delete user (in a real application, you might want to soft delete)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND company_id = ?");
    $stmt->bind_param("ii", $user_id, $_SESSION['company_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $stmt->error]);
    }
    
    $stmt->close();
    $checkStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
