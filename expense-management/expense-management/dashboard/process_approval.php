<?php
session_start();
include('../config/db.php');

// Check if user is manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $approval_id = $input['approval_id'];
    $expense_id = $input['expense_id'];
    $action = $input['action'];
    $comments = $input['comments'] ?? '';
    
    // Validate input
    if (empty($approval_id) || empty($expense_id) || !in_array($action, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit();
    }
    
    // Check if approval exists and belongs to this manager
    $checkStmt = $conn->prepare("
        SELECT ea.*, e.status as expense_status
        FROM expense_approvals ea
        JOIN expenses e ON ea.expense_id = e.id
        WHERE ea.id = ? AND ea.approver_id = ?
    ");
    $checkStmt->bind_param("ii", $approval_id, $_SESSION['user_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Approval not found']);
        exit();
    }
    
    $approval = $result->fetch_assoc();
    
    // Check if expense is still pending
    if ($approval['expense_status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Expense is no longer pending']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update approval status
        $stmt = $conn->prepare("
            UPDATE expense_approvals 
            SET status = ?, comments = ?, approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $action, $comments, $approval_id);
        $stmt->execute();
        
        if ($action === 'rejected') {
            // If rejected, mark expense as rejected
            $expenseStmt = $conn->prepare("UPDATE expenses SET status = 'rejected' WHERE id = ?");
            $expenseStmt->bind_param("i", $expense_id);
            $expenseStmt->execute();
            $expenseStmt->close();
        } else {
            // If approved, check if this was the last step
            $stepStmt = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM expense_approvals WHERE expense_id = ? AND status = 'approved') as approved_count,
                    (SELECT COUNT(*) FROM expense_approvals WHERE expense_id = ?) as total_count
            ");
            $stepStmt->bind_param("ii", $expense_id, $expense_id);
            $stepStmt->execute();
            $stepResult = $stepStmt->get_result();
            $stepData = $stepResult->fetch_assoc();
            
            // If all approvals are completed, mark expense as approved
            if ($stepData['approved_count'] >= $stepData['total_count']) {
                $expenseStmt = $conn->prepare("UPDATE expenses SET status = 'approved' WHERE id = ?");
                $expenseStmt->bind_param("i", $expense_id);
                $expenseStmt->execute();
                $expenseStmt->close();
            }
            
            $stepStmt->close();
        }
        
        $stmt->close();
        $checkStmt->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Expense {$action} successfully"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error processing approval: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
