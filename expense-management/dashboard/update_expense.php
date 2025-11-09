<?php
session_start();

// Define PDO connection directly
$host = 'localhost';
$dbname = 'expense_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Check if user is admin or manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager']) || !isset($_SESSION['company_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $expense_id = $input['expense_id'];
    $status = $input['status'];
    
    // Validate input
    if (empty($expense_id) || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit();
    }
    
    // Check if expense exists and belongs to the same company
    $checkStmt = $pdo->prepare("
        SELECT e.id 
        FROM expenses e 
        JOIN users u ON e.employee_id = u.id 
        WHERE e.id = ? AND u.company_id = ?
    ");
    $checkStmt->execute([$expense_id, $_SESSION['company_id']]);
    $expense = $checkStmt->fetch();
    
    if (!$expense) {
        echo json_encode(['success' => false, 'message' => 'Expense not found']);
        exit();
    }
    
    // Update expense status
    $stmt = $pdo->prepare("UPDATE expenses SET status = ? WHERE id = ?");
    
    if ($stmt->execute([$status, $expense_id])) {
        echo json_encode(['success' => true, 'message' => 'Expense status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating expense: Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
