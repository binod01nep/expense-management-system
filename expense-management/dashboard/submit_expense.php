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

// Check if user is employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee' || !isset($_SESSION['company_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employee_id = $_SESSION['user_id'];
        $description = $_POST['description'];
        $expense_date = $_POST['expense_date'];
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        
        // Validate input
        if (empty($description) || empty($expense_date) || empty($category) || empty($amount) || empty($currency)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit();
        }
        
        // Validate amount is numeric
        if (!is_numeric($amount) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Amount must be a positive number']);
            exit();
        }
    
    // Get company currency for conversion
    $stmt = $pdo->prepare("SELECT currency FROM companies WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch();
    $company_currency = $company['currency'];
    
    // Get currency conversion rate
    $conversion_rates = [
        'USD' => 1.0,
        'EUR' => 0.85,
        'GBP' => 0.73,
        'INR' => 83.0,
        'CAD' => 1.35,
        'AUD' => 1.55,
        'JPY' => 110.0,
        'CHF' => 0.92
    ];
    
    if (isset($conversion_rates[$currency]) && isset($conversion_rates[$company_currency])) {
        $converted_amount = $amount * ($conversion_rates[$company_currency] / $conversion_rates[$currency]);
    } else {
        $converted_amount = $amount; // Fallback to same amount if currency not supported
    }
    
    // Insert expense
    $stmt = $pdo->prepare("
        INSERT INTO expenses (employee_id, amount, currency, converted_amount, category, description, expense_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if ($stmt->execute([$employee_id, $amount, $currency, $converted_amount, $category, $description, $expense_date])) {
        $expense_id = $pdo->lastInsertId();
        
        // Create approval record (if workflow exists)
        $workflow_stmt = $pdo->prepare("
            SELECT wf.id, ws.approver_id, ws.step_order 
            FROM approval_workflows wf
            JOIN workflow_steps ws ON wf.id = ws.workflow_id
            WHERE wf.company_id = ? AND wf.type = 'sequential'
            ORDER BY ws.step_order
        ");
        $workflow_stmt->execute([$_SESSION['company_id']]);
        $workflow_steps = $workflow_stmt->fetchAll();
        
        if (count($workflow_steps) > 0) {
            // Create approval records for each step
            $approval_stmt = $pdo->prepare("
                INSERT INTO expense_approvals (expense_id, approver_id, step_order, status) 
                VALUES (?, ?, ?, 'pending')
            ");
            
            foreach ($workflow_steps as $step) {
                $approval_stmt->execute([$expense_id, $step['approver_id'], $step['step_order']]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Expense submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error submitting expense: Database error']);
    }
    
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error submitting expense: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
