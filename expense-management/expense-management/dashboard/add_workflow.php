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
    $type = $_POST['type'];
    $approval_rule = $_POST['approval_rule'] ?? null;
    $approval_value = $_POST['approval_value'] ?? null;
    $approvers = $_POST['approvers'] ?? [];
    $company_id = $_SESSION['company_id'];
    
    // Validate input
    if (empty($name) || empty($type) || empty($approvers)) {
        $_SESSION['error'] = 'All required fields must be filled';
        header("Location: admin.php#workflows");
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert workflow
        $stmt = $conn->prepare("INSERT INTO approval_workflows (company_id, name, type, approval_rule, approval_value) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $company_id, $name, $type, $approval_rule, $approval_value);
        $stmt->execute();
        $workflow_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert workflow steps
        $stepStmt = $conn->prepare("INSERT INTO workflow_steps (workflow_id, approver_id, step_order) VALUES (?, ?, ?)");
        foreach ($approvers as $index => $approver_id) {
            if (!empty($approver_id)) {
                $step_order = $index + 1;
                $stepStmt->bind_param("iii", $workflow_id, $approver_id, $step_order);
                $stepStmt->execute();
            }
        }
        $stepStmt->close();
        
        $conn->commit();
        $_SESSION['success'] = 'Workflow created successfully';
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Error creating workflow: ' . $e->getMessage();
    }
    
    header("Location: admin.php#workflows");
    exit();
}

$conn->close();
?>