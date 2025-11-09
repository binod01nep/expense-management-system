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

// Check if user is logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee' || !isset($_SESSION['company_id'])) {
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

// Get user's expenses
$stmt = $pdo->prepare("
    SELECT * FROM expenses 
    WHERE employee_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$expenses = $stmt->fetchAll();

// Calculate statistics
$totalToSubmit = array_sum(array_column(array_filter($expenses, fn($e) => $e['status'] === 'draft'), 'amount'));
$waitingApproval = array_sum(array_column(array_filter($expenses, fn($e) => $e['status'] === 'pending'), 'converted_amount'));
$approvedAmount = array_sum(array_column(array_filter($expenses, fn($e) => $e['status'] === 'approved'), 'converted_amount'));

// Get expense categories (you might want to create a categories table)
$categories = ['Food', 'Travel', 'Office Supplies', 'Entertainment', 'Transportation', 'Accommodation', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - ExpenseFlow</title>
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
        .receipt-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .receipt-upload-area:hover {
            border-color: #7F00FF;
            background-color: #f8fafc;
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
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div class="text-sm">
                            <div class="font-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="text-white/80">Employee</div>
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

        <!-- Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($totalToSubmit, 2); ?> Rs</h3>
                        <p class="text-slate-600 font-medium">To Submit</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center">
                        <i class="fas fa-clock text-white text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($waitingApproval, 2); ?> <?php echo $company['currency']; ?></h3>
                        <p class="text-slate-600 font-medium">Waiting Approval</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-white text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($approvedAmount, 2); ?> <?php echo $company['currency']; ?></h3>
                        <p class="text-slate-600 font-medium">Approved</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Expense Submission -->
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg gradient-bg flex items-center justify-center">
                        <i class="fas fa-plus-circle text-white text-sm"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-slate-800">New Expense Submission</h2>
                </div>
                
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full gradient-bg flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                        <span class="text-sm font-medium text-purple-700">Draft</span>
                    </div>
                    <div class="w-8 h-px bg-slate-200"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center">
                            <i class="fas fa-clock text-slate-500 text-xs"></i>
                        </div>
                        <span class="text-sm text-slate-500">Waiting Approval</span>
                    </div>
                    <div class="w-8 h-px bg-slate-200"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center">
                            <i class="fas fa-check text-slate-500 text-xs"></i>
                        </div>
                        <span class="text-sm text-slate-500">Approved</span>
                    </div>
                </div>

                <!-- Receipt Upload -->
                <div class="receipt-upload-area mb-6" onclick="document.getElementById('receiptFile').click()">
                    <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                    <h6 class="font-medium text-slate-700">Upload Receipt</h6>
                    <p class="text-slate-500 text-sm">Click to upload receipt or take a photo</p>
                    <input type="file" id="receiptFile" accept="image/*" style="display: none;" onchange="handleReceiptUpload(event)">
                </div>

                <!-- Expense Form -->
                <form id="expenseForm" method="POST" action="submit_expense.php" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                        <input type="text" name="description" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" placeholder="e.g., Restaurant bill" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Expense Date</label>
                        <input type="date" name="expense_date" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Total Amount</label>
                        <div class="flex gap-2">
                            <input type="number" name="amount" id="expenseAmount" class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" placeholder="567" step="0.01" required oninput="convertCurrency()">
                            <select name="currency" id="expenseCurrency" class="w-32 px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all" required onchange="convertCurrency()">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="INR">INR (₹)</option>
                                <option value="CAD">CAD (C$)</option>
                                <option value="AUD">AUD (A$)</option>
                            </select>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Enter amount in the currency you spent</p>
                        <div id="conversionDisplay" class="mt-2 hidden">
                            <p class="text-sm text-emerald-600">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <span id="conversionText"></span>
                            </p>
                        </div>
                    </div>


                    <button type="submit" class="w-full py-3 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition-opacity">
                        <i class="fas fa-paper-plane me-2"></i>Submit Expense
                    </button>
                </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Expense History -->
            <div class="glass-card rounded-2xl p-6 card-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-history text-blue-600 text-sm"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-slate-800">Expense History</h2>
                </div>
                
                <?php if (count($expenses) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach($expenses as $expense): ?>
                        <div class="p-4 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="font-medium text-slate-800"><?php echo htmlspecialchars($expense['description']); ?></h4>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-<?php echo $expense['status'] === 'approved' ? 'emerald' : ($expense['status'] === 'rejected' ? 'red' : 'amber'); ?>-100 text-<?php echo $expense['status'] === 'approved' ? 'emerald' : ($expense['status'] === 'rejected' ? 'red' : 'amber'); ?>-800">
                                            <?php echo ucfirst($expense['status']); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-slate-600">
                                        <span><i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></span>
                                        <span><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($expense['category']); ?></span>
                                    </div>
                                    <div class="mt-2">
                                        <span class="font-semibold text-slate-800"><?php echo number_format($expense['amount'], 2); ?> <?php echo $expense['currency']; ?></span>
                                        <?php if($expense['converted_amount']): ?>
                                        <span class="text-sm text-slate-500 ml-2">(<?php echo number_format($expense['converted_amount'], 2); ?> <?php echo $company['currency']; ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" onclick="viewExpenseDetails(<?php echo $expense['id']; ?>)">
                                        <i class="fas fa-eye text-sm"></i>
                                    </button>
                                    <?php if($expense['status'] === 'draft'): ?>
                                    <button class="p-2 rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 transition-colors" onclick="editExpense(<?php echo $expense['id']; ?>)">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-receipt text-6xl text-slate-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-slate-600 mb-2">No expenses submitted yet</h3>
                        <p class="text-slate-500">Start by submitting your first expense on the left.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Expense Details Modal -->
    <div id="expenseDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-200">
                <h3 class="text-xl font-semibold text-slate-800">Expense Details</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6" id="expenseDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-slate-200">
                <button type="button" class="px-4 py-2 text-slate-600 hover:text-slate-800 transition-colors" onclick="closeModal()">Close</button>
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
        // Handle receipt upload
        function handleReceiptUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Here you would implement OCR functionality
                // For now, we'll just show a success message
                showAlert('Receipt uploaded successfully! Please fill in the expense details.', 'success');
                
                // You could also preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Display preview or process with OCR
                    console.log('Receipt uploaded:', file.name);
                };
                reader.readAsDataURL(file);
            }
        }

        // Handle expense form submission
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('submit_expense.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Expense submitted successfully!', 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Error submitting expense: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while submitting the expense.', 'danger');
            });
        });

        // View expense details
        function viewExpenseDetails(expenseId) {
            fetch(`get_expense_details.php?id=${expenseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const expense = data.expense;
                        document.getElementById('expenseDetailsContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Expense Information</h6>
                                    <p><strong>Description:</strong> ${expense.description}</p>
                                    <p><strong>Category:</strong> ${expense.category}</p>
                                    <p><strong>Date:</strong> ${expense.expense_date}</p>
                                    <p><strong>Amount:</strong> ${expense.amount} ${expense.currency}</p>
                                    <p><strong>Converted:</strong> ${expense.converted_amount} ${expense.company_currency}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge status-${expense.status}">${expense.status}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Additional Details</h6>
                                    <p><strong>Paid By:</strong> ${expense.paid_by || 'N/A'}</p>
                                    <p><strong>Remarks:</strong> ${expense.remarks || 'None'}</p>
                                    <p><strong>Submitted:</strong> ${expense.created_at}</p>
                                </div>
                            </div>
                            ${expense.status_history ? `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Status History</h6>
                                        <div class="timeline">
                                            ${expense.status_history}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        `;
                        openModal();
                    } else {
                        showAlert('Error loading expense details: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while loading expense details.', 'danger');
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

        // Edit expense (for draft expenses)
        function editExpense(expenseId) {
            // This would open an edit modal or redirect to edit page
            showAlert('Edit functionality will be implemented', 'info');
        }

        // Currency conversion function
        function convertCurrency() {
            const amount = document.getElementById('expenseAmount').value;
            const fromCurrency = document.getElementById('expenseCurrency').value;
            const toCurrency = '<?php echo $company['currency']; ?>';
            
            if (amount && fromCurrency && fromCurrency !== toCurrency) {
                fetch(`get_currency_rate.php?from=${fromCurrency}&to=${toCurrency}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const convertedAmount = (amount * data.rate).toFixed(2);
                            document.getElementById('conversionText').textContent = 
                                `${amount} ${fromCurrency} = ${convertedAmount} ${toCurrency}`;
                            document.getElementById('conversionDisplay').classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Currency conversion error:', error);
                    });
            } else {
                document.getElementById('conversionDisplay').classList.add('hidden');
            }
        }
    </script>
</body>
</html>