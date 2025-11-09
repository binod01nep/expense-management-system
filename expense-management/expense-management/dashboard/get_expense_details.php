<?php
session_start();
include('../config/db.php');

// Check if user is logged in (admin, employee, or manager)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'employee', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $expense_id = $_GET['id'];
    
    // Validate input
    if (empty($expense_id) || !is_numeric($expense_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        exit();
    }
    
    // Get expense details with employee and company information
    // Add role-based access control
    if ($_SESSION['user_role'] === 'employee') {
        // Employee can only see their own expenses
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                u.name as employee_name,
                u.email as employee_email,
                c.currency as company_currency
            FROM expenses e 
            JOIN users u ON e.employee_id = u.id 
            JOIN companies c ON u.company_id = c.company_id
            WHERE e.id = ? AND e.employee_id = ? AND u.company_id = ?
        ");
        $stmt->bind_param("iii", $expense_id, $_SESSION['user_id'], $_SESSION['company_id']);
    } else {
        // Admin and manager can see all company expenses
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                u.name as employee_name,
                u.email as employee_email,
                c.currency as company_currency
            FROM expenses e 
            JOIN users u ON e.employee_id = u.id 
            JOIN companies c ON u.company_id = c.company_id
            WHERE e.id = ? AND u.company_id = ?
        ");
        $stmt->bind_param("ii", $expense_id, $_SESSION['company_id']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $expense = $result->fetch_assoc();
        
        // Get approval history
        $historyStmt = $conn->prepare("
            SELECT 
                ea.status,
                ea.comments,
                ea.approved_at,
                u.name as approver_name
            FROM expense_approvals ea
            JOIN users u ON ea.approver_id = u.id
            WHERE ea.expense_id = ?
            ORDER BY ea.approved_at ASC
        ");
        $historyStmt->bind_param("i", $expense_id);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        
        $status_history = '';
        while ($history = $historyResult->fetch_assoc()) {
            $status_class = $history['status'] === 'approved' ? 'success' : 'danger';
            $status_history .= "
                <div class='d-flex justify-content-between align-items-center mb-2'>
                    <div>
                        <strong>{$history['approver_name']}</strong> {$history['status']} this expense
                        " . ($history['comments'] ? "<br><small class='text-muted'>{$history['comments']}</small>" : "") . "
                    </div>
                    <small class='text-muted'>{$history['approved_at']}</small>
                </div>
            ";
        }
        
        $expense['status_history'] = $status_history;
        echo json_encode(['success' => true, 'expense' => $expense]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Expense not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
