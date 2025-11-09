<?php 
session_start();
include('config/db.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login — ExpenseFlow</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{
      --gradient-from: #7F00FF;
      --gradient-to:   #00C6FF;
    }
    .gradient-bg{
      background-image: linear-gradient(110deg, var(--gradient-from), var(--gradient-to));
    }
    .error{
      color: red;
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center text-slate-800">

  <!-- Login Card -->
  <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-xl">

    <!-- Logo & Title -->
    <div class="text-center mb-6">
      <div class="mx-auto w-12 h-12 rounded-xl gradient-bg flex items-center justify-center text-white mb-3">
        <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12h7l3 7 6-14"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold">Welcome Back</h1>
      <p class="text-sm text-slate-500">Log in to your ExpenseFlow account</p>
    </div>

    <!-- Form -->
    <form method="POST" action="">
      <label class="block mb-3">
        <span class="text-sm font-medium">Email</span>
        <input type="email" name="email" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] focus:border-transparent outline-none" placeholder="you@example.com" required>
      </label>

      <label class="block mb-3">
        <span class="text-sm font-medium">Password</span>
        <input type="password" name="password" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] focus:border-transparent outline-none" placeholder="••••••••" required>
      </label>

      <div class="flex items-center justify-between mb-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" class="rounded border-slate-300">
          Remember Me
        </label>
        <a href="#" class="text-sm text-[var(--gradient-from)] hover:underline">Forgot Password?</a>
      </div>

      <button type="submit" name="login" class="w-full py-3 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition">Log In</button>
      <?php
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['company_id'] = $user['company_id'];

                if ($user['role'] == 'admin') {
                    header("Location: dashboard/admin.php");
                } elseif ($user['role'] == 'employee') {
                    header("Location: dashboard/employee.php");
                } elseif ($user['role'] == 'manager') {
                    header("Location: dashboard/manager.php");
                } else {
                    echo "<div class='error'>Unknown role!</div>";
                }
                exit();
            } else {
                echo "<div class='error'>Invalid password!</div>";
            }
        } else {
            echo "<div class='error'>User not found! Signup if you don't have an account</div>";
        }
    }
    ?>
    </form>

    <!-- Footer -->
    <p class="text-center text-sm text-slate-500 mt-6">
      Don’t have an account?
      <a href="signup.php" class="text-[var(--gradient-from)] font-medium hover:underline">Sign Up</a>
    </p>
  </div>

</body>
</html>
