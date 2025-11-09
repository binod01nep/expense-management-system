<?php
session_start();

// Define PDO connection directly in admin.php
$host = 'localhost';
$dbname = 'expense_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin' || !isset($_SESSION['company_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get company info
$stmt = $pdo->prepare("SELECT * FROM companies WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$company = $stmt->fetch();

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users WHERE company_id = ? ORDER BY role, name");
$stmt->execute([$_SESSION['company_id']]);
$users = $stmt->fetchAll();

// Get approval workflows
$stmt = $pdo->prepare("SELECT * FROM approval_workflows WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$workflows = $stmt->fetchAll();

// Get all expenses
$stmt = $pdo->prepare("
    SELECT e.*, u.name as employee_name 
    FROM expenses e 
    JOIN users u ON e.employee_id = u.id 
    WHERE u.company_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$_SESSION['company_id']]);
$expenses = $stmt->fetchAll();

// Get expense statistics
$totalExpenses = count($expenses);
$pendingExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'pending'));
$approvedExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'approved'));
$rejectedExpenses = count(array_filter($expenses, fn($e) => $e['status'] === 'rejected'));
$totalAmount = array_sum(array_column($expenses, 'converted_amount'));

// Get approvers for the workflow modal
$approvers = array_filter($users, function($u) {
    return in_array($u['role'], ['manager', 'admin']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ExpenseFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{
            --gradient-from: #7F00FF;
            --gradient-to: #00C6FF;
        }
        .gradient-bg{
            background-image: linear-gradient(110deg, var(--gradient-from), var(--gradient-to));
        }
        .card-shadow { 
            box-shadow: 0 8px 30px rgba(34, 24, 64, 0.12); 
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .tab-pane {
            display: none !important;
        }
        .tab-pane.active {
            display: block !important;
        }
        .tab-pane.show {
            display: block !important;
        }
    </style>
</head>
<body class="antialiased text-slate-800 bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="#" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="white" stroke-width="1.6">
                                <path d="M3 12h7l3 7 6-14"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-lg tracking-wide">ExpenseFlow</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-3 px-4 py-2 rounded-lg bg-white/10">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-user-shield text-sm"></i>
                        </div>
                        <div class="text-sm">
                            <div class="font-medium"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                            <div class="text-white/80">Admin</div>
                        </div>
                    </div>
                    <a href="logout.php" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 lg:px-10 py-8">
        <!-- Alert Container -->
        <div id="alertContainer">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4" role="alert">
                    <div class="flex justify-between items-center">
                        <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                        <button type="button" class="text-green-700 hover:text-green-900" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" role="alert">
                    <div class="flex justify-between items-center">
                        <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                        <button type="button" class="text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-2xl p-6 card-shadow">
                    <nav class="space-y-2">
                        <a href="#dashboard" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200 active" data-tab="dashboard">
                            <div class="w-8 h-8 rounded-lg gradient-bg flex items-center justify-center text-white">
                                <i class="fas fa-tachometer-alt text-sm"></i>
                            </div>
                            <span class="font-medium">Dashboard</span>
                        </a>
                        <a href="#users" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200" data-tab="users">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <span class="font-medium">User Management</span>
                        </a>
                        <a href="#workflows" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200" data-tab="workflows">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                                <i class="fas fa-sitemap text-sm"></i>
                            </div>
                            <span class="font-medium">Approval Workflows</span>
                        </a>
                        <a href="#expenses" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200" data-tab="expenses">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                                <i class="fas fa-receipt text-sm"></i>
                            </div>
                            <span class="font-medium">All Expenses</span>
                        </a>
                        <a href="#company" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200" data-tab="company">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                                <i class="fas fa-building text-sm"></i>
                            </div>
                            <span class="font-medium">Company Settings</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard" style="display: block;">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-slate-800 mb-2">Dashboard Overview</h2>
                            <p class="text-slate-600">Monitor your expense management system at a glance</p>
                                    </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-2xl font-bold text-slate-800"><?php echo count($users); ?></h3>
                                        <p class="text-slate-600 font-medium">Total Users</p>
                                        <p class="text-sm text-slate-500 mt-1">Employees: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'employee')); ?> | Managers: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'manager')); ?></p>
                                </div>
                                    <div class="w-12 h-12 rounded-xl gradient-bg flex items-center justify-center">
                                        <i class="fas fa-users text-white text-lg"></i>
                            </div>
                                    </div>
                                </div>
                            
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $pendingExpenses; ?></h3>
                                        <p class="text-slate-600 font-medium">Pending Expenses</p>
                                        <p class="text-sm text-slate-500 mt-1">Awaiting approval</p>
                            </div>
                                    <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center">
                                        <i class="fas fa-clock text-white text-lg"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $totalExpenses; ?></h3>
                                        <p class="text-slate-600 font-medium">Total Expenses</p>
                                        <p class="text-sm text-slate-500 mt-1">Approved: <?php echo $approvedExpenses; ?> | Rejected: <?php echo $rejectedExpenses; ?></p>
                                    </div>
                                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center">
                                        <i class="fas fa-receipt text-white text-lg"></i>
                                </div>
                                </div>
                            </div>
                            
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($totalAmount, 2); ?></h3>
                                        <p class="text-slate-600 font-medium">Total Amount</p>
                                        <p class="text-sm text-slate-500 mt-1"><?php echo $company['currency']; ?></p>
                                    </div>
                                    <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
                                        <i class="fas fa-dollar-sign text-white text-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                        <i class="fas fa-receipt text-emerald-600 text-sm"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-slate-800">Recent Expenses</h3>
                                </div>
                                <?php if (count($expenses) > 0): ?>
                                    <div class="space-y-3">
                                        <?php foreach (array_slice($expenses, 0, 5) as $expense): ?>
                                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                                            <div>
                                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($expense['employee_name']); ?></p>
                                                <p class="text-sm text-slate-600"><?php echo htmlspecialchars($expense['category']); ?> - <?php echo $expense['amount']; ?> <?php echo $expense['currency']; ?></p>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-<?php echo $expense['status'] === 'approved' ? 'emerald' : ($expense['status'] === 'rejected' ? 'red' : 'amber'); ?>-100 text-<?php echo $expense['status'] === 'approved' ? 'emerald' : ($expense['status'] === 'rejected' ? 'red' : 'amber'); ?>-800">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-receipt text-4xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-500">No expenses found.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-sitemap text-blue-600 text-sm"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-slate-800">Approval Workflows</h3>
                                </div>
                                <?php if (count($workflows) > 0): ?>
                                    <div class="space-y-3">
                                        <?php foreach ($workflows as $workflow): ?>
                                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                                            <div>
                                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($workflow['name']); ?></p>
                                                <p class="text-sm text-slate-600"><?php echo ucfirst($workflow['type']); ?> workflow</p>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Active
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-sitemap text-4xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-500">No workflows created yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Tab -->
                    <div class="tab-pane fade" id="users" style="display: none;">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800">User Management</h2>
                                <p class="text-slate-600">Manage your team members and their roles</p>
                            </div>
                            <button class="px-4 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity" onclick="openAddUserModal()">
                                <i class="fas fa-plus me-2"></i>Add User
                            </button>
                        </div>
                        
                        <!-- Search and Filter -->
                        <div class="glass-card rounded-2xl p-6 card-shadow mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-slate-400"></i>
                                    </div>
                                    <input type="text" class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" id="userSearch" placeholder="Search users...">
                                </div>
                                <select class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" id="roleFilter">
                                    <option value="">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="employee">Employee</option>
                                </select>
                                <button class="px-4 py-3 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors" onclick="clearFilters()">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                        
                        <div class="glass-card rounded-2xl p-6 card-shadow">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                <thead>
                                        <tr class="border-b border-slate-200">
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">ID</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Name</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Email</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Role</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Organization</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Country</th>
                                            <th class="text-center py-3 px-4 font-semibold text-slate-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr id="user-row-<?php echo $user['id']; ?>" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td class="py-4 px-4 text-sm font-medium text-slate-800"><?php echo $user['id']; ?></td>
                                            <td class="py-4 px-4">
                                                <div class="font-medium text-slate-800"><?php echo htmlspecialchars($user['name']); ?></div>
                                            </td>
                                            <td class="py-4 px-4 text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="py-4 px-4">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-<?php 
                                                    echo $user['role'] === 'admin' ? 'red' : 
                                                         ($user['role'] === 'manager' ? 'amber' : 'blue'); 
                                                ?>-100 text-<?php 
                                                    echo $user['role'] === 'admin' ? 'red' : 
                                                         ($user['role'] === 'manager' ? 'amber' : 'blue'); 
                                                ?>-800">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                            <td class="py-4 px-4 text-slate-600"><?php echo htmlspecialchars($user['organization']); ?></td>
                                            <td class="py-4 px-4 text-slate-600"><?php echo htmlspecialchars($user['country']); ?></td>
                                            <td class="py-4 px-4">
                                                <div class="flex gap-2 justify-center">
                                                    <button class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors edit-user" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-user-role="<?php echo $user['role']; ?>"
                                                            data-user-organization="<?php echo htmlspecialchars($user['organization']); ?>"
                                                            data-user-country="<?php echo htmlspecialchars($user['country']); ?>">
                                                        <i class="fas fa-edit text-sm"></i>
                                            </button>
                                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors delete-user" 
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                        <i class="fas fa-trash text-sm"></i>
                                            </button>
                                                    <?php endif; ?>
                                                </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Workflows Tab -->
                    <div class="tab-pane fade" id="workflows" style="display: none;">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800">Approval Workflows</h2>
                                <p class="text-slate-600">Configure approval processes for expense management</p>
                            </div>
                            <button class="px-4 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity" onclick="openAddWorkflowModal()">
                                <i class="fas fa-plus me-2"></i>Create Workflow
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach($workflows as $workflow): ?>
                            <div class="glass-card rounded-2xl p-6 card-shadow">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-slate-800"><?php echo htmlspecialchars($workflow['name']); ?></h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo ucfirst($workflow['type']); ?>
                                    </span>
                            </div>
                                
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm text-slate-500">Rule</p>
                                        <p class="font-medium text-slate-800"><?php echo $workflow['approval_rule'] ?: 'Sequential'; ?></p>
                                    </div>
                                    
                                <?php if($workflow['approval_rule']): ?>
                                    <div>
                                        <p class="text-sm text-slate-500">Value</p>
                                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($workflow['approval_value']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Show workflow steps -->
                                <?php 
                                $stepStmt = $pdo->prepare("
                                    SELECT ws.*, u.name as approver_name 
                                    FROM workflow_steps ws 
                                    JOIN users u ON ws.approver_id = u.id 
                                    WHERE ws.workflow_id = ? 
                                    ORDER BY ws.step_order
                                ");
                                $stepStmt->execute([$workflow['id']]);
                                $steps = $stepStmt->fetchAll();
                                ?>
                                
                                    <div>
                                        <p class="text-sm text-slate-500 mb-2">Approval Steps</p>
                                        <div class="space-y-2">
                                            <?php foreach($steps as $index => $step): ?>
                                            <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50">
                                                <div class="w-6 h-6 rounded-full bg-purple-100 text-purple-600 text-xs font-semibold flex items-center justify-center">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                                <span class="text-slate-800"><?php echo htmlspecialchars($step['approver_name']); ?></span>
                                            </div>
                                    <?php endforeach; ?>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- All Expenses Tab -->
                    <div class="tab-pane fade" id="expenses" style="display: none;">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-slate-800">All Expenses</h2>
                            <p class="text-slate-600">View and manage all expense submissions</p>
                        </div>
                        
                        <div class="glass-card rounded-2xl p-6 card-shadow">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                <thead>
                                        <tr class="border-b border-slate-200">
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Employee</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Amount</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Converted Amount</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Category</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Date</th>
                                            <th class="text-left py-3 px-4 font-semibold text-slate-700">Status</th>
                                            <th class="text-center py-3 px-4 font-semibold text-slate-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($expenses as $expense): ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                            <td class="py-4 px-4">
                                                <div class="font-medium text-slate-800"><?php echo htmlspecialchars($expense['employee_name']); ?></div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="font-semibold text-slate-800"><?php echo $expense['amount'] . ' ' . $expense['currency']; ?></div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="font-medium text-slate-800"><?php echo $expense['converted_amount'] . ' ' . $company['currency']; ?></div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700"><?php echo htmlspecialchars($expense['category']); ?></span>
                                            </td>
                                            <td class="py-4 px-4 text-slate-600"><?php echo $expense['expense_date']; ?></td>
                                            <td class="py-4 px-4">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-<?php 
                                                    echo $expense['status'] === 'approved' ? 'emerald' : 
                                                         ($expense['status'] === 'rejected' ? 'red' : 'amber'); 
                                                ?>-100 text-<?php 
                                                    echo $expense['status'] === 'approved' ? 'emerald' : 
                                                         ($expense['status'] === 'rejected' ? 'red' : 'amber'); 
                                                ?>-800">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                            <td class="py-4 px-4">
                                                <div class="flex gap-2 justify-center">
                                                    <button class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors view-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                        <i class="fas fa-eye text-sm"></i>
                                            </button>
                                            <?php if($expense['status'] === 'pending'): ?>
                                                    <button class="p-2 rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 transition-colors approve-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                        <i class="fas fa-check text-sm"></i>
                                            </button>
                                                    <button class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors reject-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                        <i class="fas fa-times text-sm"></i>
                                            </button>
                                            <?php endif; ?>
                                                </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <!-- Company Settings Tab -->
                    <div class="tab-pane fade" id="company" style="display: none;">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-slate-800">Company Settings</h2>
                            <p class="text-slate-600">Manage your company information and preferences</p>
                                            </div>
                        
                        <div class="glass-card rounded-2xl p-6 card-shadow">
                            <form id="companySettingsForm" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Name</label>
                                        <input type="text" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" name="name" 
                                               value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                                        </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Country</label>
                                        <input type="text" class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-500" value="<?php echo htmlspecialchars($company['country']); ?>" readonly>
                                            </div>
                                        </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Default Currency</label>
                                    <input type="text" class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-500" value="<?php echo htmlspecialchars($company['currency']); ?>" readonly>
                                    </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-6 py-3 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity">
                                        <i class="fas fa-save me-2"></i>Update Company
                                    </button>
                                    </div>
                                </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Add User</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeAddUserModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
                </div>
            <form action="add_user.php" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Name</label>
                    <input type="text" name="name" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                                <option value="employee">Employee</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Organization</label>
                    <input type="text" name="organization" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Country</label>
                    <input type="text" name="country" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" value="<?php echo htmlspecialchars($company['country']); ?>" required>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="px-6 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Edit User</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeEditUserModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="editUserForm" class="p-6 space-y-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Name</label>
                    <input type="text" name="name" id="edit_name" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                    <select name="role" id="edit_role" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        <option value="employee">Employee</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                            </select>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Organization</label>
                    <input type="text" name="organization" id="edit_organization" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Country</label>
                    <input type="text" name="country" id="edit_country" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                    </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="px-6 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity">Update User</button>
                    </div>
                </form>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Confirm Delete</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeDeleteUserModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-slate-700 mb-2">Are you sure you want to delete user <strong id="delete_user_name"></strong>?</p>
                <p class="text-red-600 text-sm">This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-slate-200">
                <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeDeleteUserModal()">Cancel</button>
                <button type="button" class="px-4 py-2 rounded-lg bg-red-500 text-white font-semibold hover:bg-red-600 transition-colors" id="confirmDeleteUser">Delete User</button>
            </div>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div id="viewExpenseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Expense Details</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeViewExpenseModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6" id="expenseDetails">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-slate-200">
                <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeViewExpenseModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Add Workflow Modal -->
    <div id="addWorkflowModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Create Approval Workflow</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeAddWorkflowModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
                </div>
            <form action="add_workflow.php" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Workflow Name</label>
                    <input type="text" name="name" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Workflow Type</label>
                    <select name="type" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" id="workflowType" required>
                                <option value="sequential">Sequential Approval</option>
                                <option value="conditional">Conditional Approval</option>
                            </select>
                        </div>
                        
                        <!-- Conditional rules (shown only when conditional is selected) -->
                <div id="conditionalRules" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Approval Rule</label>
                        <select name="approval_rule" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all">
                                    <option value="percentage">Percentage Rule</option>
                                    <option value="specific_approver">Specific Approver Rule</option>
                                    <option value="hybrid">Hybrid Rule</option>
                                </select>
                            </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Approval Value</label>
                        <input type="text" name="approval_value" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" 
                                       placeholder="e.g., 60 for percentage, CFO for specific approver, 60|CFO for hybrid">
                            </div>
                        </div>

                        <!-- Approval steps -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Approval Steps</label>
                    <div id="approvalSteps" class="space-y-2">
                        <div class="step">
                            <select name="approvers[]" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                                        <option value="">Select Approver</option>
                                        <?php foreach($approvers as $approver): ?>
                                        <option value="<?php echo $approver['id']; ?>"><?php echo htmlspecialchars($approver['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                    <button type="button" class="mt-2 px-4 py-2 rounded-lg border border-purple-300 text-purple-600 hover:bg-purple-50 transition-colors" id="addStep">
                                <i class="fas fa-plus me-1"></i>Add Step
                            </button>
                        </div>
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeAddWorkflowModal()">Cancel</button>
                    <button type="submit" class="px-6 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity">Create Workflow</button>
                    </div>
                </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
            document.getElementById('addUserModal').classList.add('flex');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
            document.getElementById('addUserModal').classList.remove('flex');
        }

        function openEditUserModal() {
            document.getElementById('editUserModal').classList.remove('hidden');
            document.getElementById('editUserModal').classList.add('flex');
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
            document.getElementById('editUserModal').classList.remove('flex');
        }

        function openAddWorkflowModal() {
            document.getElementById('addWorkflowModal').classList.remove('hidden');
            document.getElementById('addWorkflowModal').classList.add('flex');
        }

        function closeAddWorkflowModal() {
            document.getElementById('addWorkflowModal').classList.add('hidden');
            document.getElementById('addWorkflowModal').classList.remove('flex');
        }

        function openViewExpenseModal() {
            document.getElementById('viewExpenseModal').classList.remove('hidden');
            document.getElementById('viewExpenseModal').classList.add('flex');
        }

        function closeViewExpenseModal() {
            document.getElementById('viewExpenseModal').classList.add('hidden');
            document.getElementById('viewExpenseModal').classList.remove('flex');
        }

        function openDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.remove('hidden');
            document.getElementById('deleteUserModal').classList.add('flex');
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.add('hidden');
            document.getElementById('deleteUserModal').classList.remove('flex');
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const navItems = document.querySelectorAll('.nav-item');
                    const tabPanes = document.querySelectorAll('.tab-pane');
            
            // Initialize - hide all tabs except dashboard
            tabPanes.forEach(pane => {
                if (pane.id !== 'dashboard') {
                    pane.classList.remove('show', 'active');
                    pane.style.display = 'none';
                } else {
                    pane.classList.add('show', 'active');
                    pane.style.display = 'block';
                }
            });
            
            // Force hide all non-dashboard tabs immediately
            setTimeout(() => {
                tabPanes.forEach(pane => {
                    if (pane.id !== 'dashboard') {
                        pane.style.display = 'none';
                        pane.classList.remove('show', 'active');
                    }
                });
            }, 100);
            
            navItems.forEach(function (navItem) {
                navItem.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Hide all tab panes
                    tabPanes.forEach(pane => {
                        pane.classList.remove('show', 'active');
                        pane.style.display = 'none';
                    });
                    
                    // Show target tab
                    const targetPane = document.getElementById(targetTab);
                    if (targetPane) {
                        targetPane.classList.add('show', 'active');
                        targetPane.style.display = 'block';
                    }
                    
                    // Update active state in sidebar
                    navItems.forEach(item => {
                        const icon = item.querySelector('.w-8.h-8');
                        const text = item.querySelector('span');
                        
                        if (item === this) {
                            // Active state
                            icon.className = 'w-8 h-8 rounded-lg gradient-bg flex items-center justify-center text-white';
                            text.className = 'font-medium text-purple-700';
                            item.className = 'nav-item flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-purple-50 to-blue-50 text-purple-700 transition-all duration-200 active';
                        } else {
                            // Inactive state
                            icon.className = 'w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600';
                            text.className = 'font-medium';
                            item.className = 'nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 hover:text-purple-700 transition-all duration-200';
                        }
                    });
                });
            });

            // Workflow type toggle
            document.getElementById('workflowType').addEventListener('change', function() {
                const conditionalRules = document.getElementById('conditionalRules');
                if (this.value === 'conditional') {
                    conditionalRules.classList.remove('hidden');
                    conditionalRules.classList.add('block');
                } else {
                    conditionalRules.classList.add('hidden');
                    conditionalRules.classList.remove('block');
                }
            });

            // Add approval step
            document.getElementById('addStep').addEventListener('click', function() {
                const stepsContainer = document.getElementById('approvalSteps');
                const newStep = document.createElement('div');
                newStep.className = 'step flex items-center gap-2';
                newStep.innerHTML = `
                    <select name="approvers[]" class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                        <option value="">Select Approver</option>
                        <?php foreach($approvers as $approver): ?>
                        <option value="<?php echo $approver['id']; ?>"><?php echo htmlspecialchars($approver['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors remove-step">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                `;
                stepsContainer.appendChild(newStep);
            });

            // Remove approval step
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-step')) {
                    e.target.closest('.step').remove();
                }
            });

            // Edit user functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-user')) {
                    const btn = e.target.closest('.edit-user');
                    document.getElementById('edit_user_id').value = btn.dataset.userId;
                    document.getElementById('edit_name').value = btn.dataset.userName;
                    document.getElementById('edit_email').value = btn.dataset.userEmail;
                    document.getElementById('edit_role').value = btn.dataset.userRole;
                    document.getElementById('edit_organization').value = btn.dataset.userOrganization;
                    document.getElementById('edit_country').value = btn.dataset.userCountry;
                    openEditUserModal();
                }
            });

            // Delete user functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-user')) {
                    const btn = e.target.closest('.delete-user');
                    document.getElementById('delete_user_name').textContent = btn.dataset.userName;
                    document.getElementById('confirmDeleteUser').dataset.userId = btn.dataset.userId;
                    openDeleteUserModal();
                }
            });

            // Handle edit user form submission
            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user.');
                });
            });

            // Handle delete user confirmation
            document.getElementById('confirmDeleteUser').addEventListener('click', function() {
                const userId = this.dataset.userId;
                
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({user_id: userId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`user-row-${userId}`).remove();
                        closeDeleteUserModal();
                        showAlert('User deleted successfully', 'success');
                    } else {
                        alert('Error deleting user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user.');
                });
            });

            // Handle expense actions
            document.addEventListener('click', function(e) {
                if (e.target.closest('.approve-expense')) {
                    const btn = e.target.closest('.approve-expense');
                    const expenseId = btn.dataset.expenseId;
                    updateExpenseStatus(expenseId, 'approved');
                } else if (e.target.closest('.reject-expense')) {
                    const btn = e.target.closest('.reject-expense');
                    const expenseId = btn.dataset.expenseId;
                    updateExpenseStatus(expenseId, 'rejected');
                } else if (e.target.closest('.view-expense')) {
                    const btn = e.target.closest('.view-expense');
                    const expenseId = btn.dataset.expenseId;
                    viewExpenseDetails(expenseId);
                }
            });

            // Handle company settings form
            document.getElementById('companySettingsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('update_company.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Company settings updated successfully', 'success');
                    } else {
                        showAlert('Error updating company: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while updating company settings.', 'danger');
                });
            });

            // Search and filter functionality
            document.getElementById('userSearch').addEventListener('input', filterUsers);
            document.getElementById('roleFilter').addEventListener('change', filterUsers);
        });

        // Helper function to update expense status
        function updateExpenseStatus(expenseId, status) {
            fetch('update_expense.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({expense_id: expenseId, status: status})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating expense: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the expense.');
            });
        }

        // Helper function to show alerts
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
            alertDiv.className = `${bgColor} border px-4 py-3 rounded-lg mb-4`;
            alertDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <span>${message}</span>
                    <button type="button" class="hover:opacity-75" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            const container = document.getElementById('alertContainer');
            container.appendChild(alertDiv);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Helper function to view expense details
        function viewExpenseDetails(expenseId) {
            fetch(`get_expense_details.php?id=${expenseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const expense = data.expense;
                        document.getElementById('expenseDetails').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Employee Information</h6>
                                    <p><strong>Name:</strong> ${expense.employee_name}</p>
                                    <p><strong>Email:</strong> ${expense.employee_email || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Expense Information</h6>
                                    <p><strong>Amount:</strong> ${expense.amount} ${expense.currency}</p>
                                    <p><strong>Converted:</strong> ${expense.converted_amount} ${expense.company_currency}</p>
                                    <p><strong>Category:</strong> ${expense.category}</p>
                                    <p><strong>Date:</strong> ${expense.expense_date}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-${expense.status === 'approved' ? 'success' : expense.status === 'rejected' ? 'danger' : 'warning'}">
                                            ${expense.status}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Description</h6>
                                    <p>${expense.description || 'No description provided'}</p>
                                </div>
                            </div>
                        `;
                        openViewExpenseModal();
                    } else {
                        alert('Error loading expense details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading expense details.');
                });
        }

        // Helper function to filter users
        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('#users tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const role = row.cells[3].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesRole = !roleFilter || role.includes(roleFilter);
                
                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Helper function to clear filters
        function clearFilters() {
            document.getElementById('userSearch').value = '';
            document.getElementById('roleFilter').value = '';
            filterUsers();
        }
    </script>
</body>
</html>