<?php
session_start();

// Define PDO connection
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

// Check if user is logged in and is manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager' || !isset($_SESSION['company_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get company info
$stmt = $pdo->prepare("SELECT * FROM companies WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$company = $stmt->fetch();

// Get pending approvals for this manager
// First, get all pending expenses in the company
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        u.name as employee_name,
        u.email as employee_email
    FROM expenses e
    JOIN users u ON e.employee_id = u.id
    WHERE e.status = 'pending'
    AND u.company_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$_SESSION['company_id']]);
$pendingApprovals = $stmt->fetchAll();

// Get all expenses for overview (including approved/rejected)
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        u.name as employee_name
    FROM expenses e
    JOIN users u ON e.employee_id = u.id
    WHERE u.company_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$_SESSION['company_id']]);
$allExpenses = $stmt->fetchAll();

// Calculate statistics
$totalPending = count($pendingApprovals);
$totalApproved = count(array_filter($allExpenses, fn($e) => $e['status'] === 'approved'));
$totalRejected = count(array_filter($allExpenses, fn($e) => $e['status'] === 'rejected'));
$totalAmount = array_sum(array_column($allExpenses, 'converted_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - ExpenseFlow</title>
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
                            <i class="fas fa-user-tie text-sm"></i>
                        </div>
                        <div class="text-sm">
                            <div class="font-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="text-white/80">Manager</div>
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

        <!-- Manager Header -->
        <div class="glass-card rounded-2xl p-8 card-shadow mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Expense Approval Dashboard</h1>
                    <p class="text-slate-600">Review and approve expense requests from your team members</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full gradient-bg flex items-center justify-center">
                        <i class="fas fa-user-tie text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-slate-800"><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="text-slate-600">Manager</p>
                    </div>
                </div>
            </div>
            </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $totalPending; ?></h3>
                        <p class="text-slate-600 font-medium">Pending Approvals</p>
                        <p class="text-sm text-slate-500 mt-1">Awaiting your review</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center">
                        <i class="fas fa-clock text-white text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $totalApproved; ?></h3>
                        <p class="text-slate-600 font-medium">Approved</p>
                        <p class="text-sm text-slate-500 mt-1">Expenses approved</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $totalRejected; ?></h3>
                        <p class="text-slate-600 font-medium">Rejected</p>
                        <p class="text-sm text-slate-500 mt-1">Expenses rejected</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-500 flex items-center justify-center">
                        <i class="fas fa-times-circle text-white text-lg"></i>
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

        <!-- Approvals to Review -->
        <div class="glass-card rounded-2xl p-6 card-shadow">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-amber-600 text-sm"></i>
                </div>
                <h2 class="text-xl font-semibold text-slate-800">Approvals to Review</h2>
            </div>
            
            <div>
                <?php if (count($pendingApprovals) > 0): ?>
        <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="text-left py-3 px-4 font-semibold text-slate-700">Approval Subject</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-700">Request Owner</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-700">Category</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-700">Request Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-700">Total Amount</th>
                                    <th class="text-center py-3 px-4 font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                            <tbody>
                                <?php foreach($pendingApprovals as $approval): ?>
                                <tr id="approval-row-<?php echo $approval['id']; ?>" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <div>
                                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($approval['description']); ?></p>
                                            <p class="text-sm text-slate-500">Expense ID: <?php echo $approval['id']; ?></p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div>
                                            <p class="font-medium text-slate-800"><?php echo htmlspecialchars($approval['employee_name']); ?></p>
                                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($approval['employee_email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700"><?php echo htmlspecialchars($approval['category']); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div>
                                            <p class="font-semibold text-slate-800"><?php echo number_format($approval['converted_amount'], 2); ?> <?php echo $company['currency']; ?></p>
                                            <p class="text-sm text-slate-500"><?php echo number_format($approval['amount'], 2); ?> <?php echo $approval['currency']; ?></p>
                                            <?php if($approval['currency'] !== $company['currency']): ?>
                                            <p class="text-xs text-emerald-600">Auto-converted</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex gap-2 justify-center">
                                            <button class="px-3 py-1 rounded-lg bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors approve-expense" data-expense-id="<?php echo $approval['id']; ?>">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                            <button class="px-3 py-1 rounded-lg bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition-colors reject-expense" data-expense-id="<?php echo $approval['id']; ?>">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                    </tbody>
            </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clipboard-check text-emerald-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-800 mb-2">No Pending Approvals</h3>
                        <p class="text-slate-600">All caught up! Enjoy your break.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Expenses Overview -->
        <div class="glass-card rounded-2xl p-6 card-shadow mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-history text-blue-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-800">All Expense Requests</h3>
            </div>
            
            <?php if (count($allExpenses) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="text-left py-3 px-4 font-semibold text-slate-700">Employee</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700">Description</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700">Amount</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700">Status</th>
                                <th class="text-center py-3 px-4 font-semibold text-slate-700">Your Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allExpenses as $expense): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-4">
                                    <div class="font-medium text-slate-800"><?php echo htmlspecialchars($expense['employee_name']); ?></div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-medium text-slate-800"><?php echo htmlspecialchars($expense['description']); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo htmlspecialchars($expense['category']); ?></div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-semibold text-slate-800"><?php echo number_format($expense['converted_amount'], 2); ?> <?php echo $company['currency']; ?></div>
                                    <div class="text-sm text-slate-500"><?php echo number_format($expense['amount'], 2); ?> <?php echo $expense['currency']; ?></div>
                                </td>
                                <td class="py-4 px-4 text-slate-600"><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
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
                                <td class="py-4 px-4 text-center">
                                    <?php if($expense['status'] === 'pending'): ?>
                                        <div class="flex gap-2 justify-center">
                                            <button class="p-2 rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 transition-colors approve-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                <i class="fas fa-check text-sm"></i>
                                            </button>
                                            <button class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors reject-expense" 
                                                    data-expense-id="<?php echo $expense['id']; ?>">
                                                <i class="fas fa-times text-sm"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-sm"><?php echo ucfirst($expense['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-receipt text-slate-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-800 mb-2">No Expense Requests</h3>
                    <p class="text-slate-600">No expense requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Comments Modal -->
    <div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800" id="approvalModalTitle">Add Comments</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeApprovalModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Comments (Optional)</label>
                    <textarea class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" id="approvalComments" rows="3" placeholder="Add any comments about this expense..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-slate-200">
                <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeApprovalModal()">Cancel</button>
                <button type="button" class="px-4 py-2 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity" id="confirmApproval">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function closeModal() {
            document.getElementById('expenseDetailsModal').classList.add('hidden');
            document.getElementById('expenseDetailsModal').classList.remove('flex');
        }

        function openModal() {
            document.getElementById('expenseDetailsModal').classList.remove('hidden');
            document.getElementById('expenseDetailsModal').classList.add('flex');
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
            document.getElementById('approvalModal').classList.remove('flex');
        }

        function openApprovalModal() {
            document.getElementById('approvalModal').classList.remove('hidden');
            document.getElementById('approvalModal').classList.add('flex');
        }

        // Handle direct approve/reject for bottom section
        document.addEventListener('click', function(e) {
            if (e.target.closest('.approve-expense')) {
                const btn = e.target.closest('.approve-expense');
                const expenseId = btn.dataset.expenseId;
                updateExpenseStatus(expenseId, 'approved');
            } else if (e.target.closest('.reject-expense')) {
                const btn = e.target.closest('.reject-expense');
                const expenseId = btn.dataset.expenseId;
                updateExpenseStatus(expenseId, 'rejected');
            }
        });

        // Update expense status directly
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
                    showAlert(`Expense ${status} successfully!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Error updating expense: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while updating the expense.', 'danger');
            });
        }

        // Show alert function
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
    </script>
</body>
</html>