<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ExpenseFlow — Expense Management System</title>
  <meta name="description" content="ExpenseFlow — modern expense management: submit receipts, approvals, OCR, rules, reimbursements, analytics and integrations." />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Tailwind config override for gradient utility (optional) */
    :root{
      --gradient-from: #7F00FF;
      --gradient-to:   #00C6FF;
    }
    .gradient-bg{
      background-image: linear-gradient(110deg, var(--gradient-from), var(--gradient-to));
    }
    /* small shadow accent */
    .card-shadow { box-shadow: 0 8px 30px rgba(34, 24, 64, 0.12); }
  </style>
</head>
<body class="antialiased text-slate-800 bg-white">

  <!-- NAVBAR -->
  <header class="fixed w-full z-40">
    <nav class="gradient-bg text-white">
      <div class="max-w-7xl mx-auto px-6 lg:px-10">
        <div class="flex items-center justify-between h-20">
          <div class="flex items-center gap-4">
            <a href="#" class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <!-- logo mark -->
                <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="white">
                  <path d="M3 12h7l3 7 6-14" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <span class="font-semibold text-lg tracking-wide">ExpenseFlow</span>
            </a>
          </div>

          <!-- desktop links -->
          <div class="hidden md:flex items-center gap-8">
            <a href="#features" class="hover:underline">Features</a>
            <a href="#workflow" class="hover:underline">Workflow</a>
            <a href="#roles" class="hover:underline">Roles</a>
            <a href="#contact" class="hover:underline">Contact</a>
            <div class="flex items-center gap-3">
              <a href="login.php" class="px-4 py-2 rounded-md bg-white text-gradient text-slate-800 font-medium shadow-sm">Login</a>
              <a href="signup.php" class="px-4 py-2 rounded-md bg-white text-gradient text-slate-800 font-medium shadow-sm">Sign Up</a>
            </div>
          </div>

          <!-- mobile menu button -->
          <div class="md:hidden">
            <button id="navToggle" aria-label="Toggle menu" class="p-2 rounded-md bg-white/10">
              <svg id="navOpenIcon" class="w-6 h-6" fill="none" stroke="white" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
              <svg id="navCloseIcon" class="w-6 h-6 hidden" fill="none" stroke="white" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- mobile menu -->
      <div id="mobileMenu" class="md:hidden px-6 pb-6 hidden">
        <a href="#features" class="block py-2">Features</a>
        <a href="#workflow" class="block py-2">Workflow</a>
        <a href="#roles" class="block py-2">Roles</a>
        <a href="#contact" class="block py-2">Contact</a>
        <div class="flex gap-3 mt-3">
          <a href="#" class="flex-1 text-center py-2 rounded-md bg-white/20">Watch Demo</a>
          <a href="#" class="flex-1 text-center py-2 rounded-md bg-white text-slate-800 font-medium">Get Started</a>
        </div>
      </div>
    </nav>
  </header>

  <main>

    <!-- HERO -->
    <section class="relative overflow-hidden">
      <div class="gradient-bg px-6 lg:px-10">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center py-20">
          <div class="text-white">
            <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight drop-shadow-md">
              Expense management that <span class="inline-block">actually</span> saves time.
            </h1>
            <p class="mt-6 text-lg max-w-xl text-white/90">
              ExpenseFlow automates expense submission, approval workflows, receipt OCR and conditional rules so your team spends less time on admin and more time on work that matters.
            </p>

            <div class="mt-8 flex gap-4">
              <a href="#" class="inline-flex items-center gap-3 px-6 py-3 bg-white text-slate-800 rounded-lg font-semibold shadow hover:scale-[1.01] transition">
                <!-- play icon -->
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M5 3v18l15-9L5 3z"/>
                </svg>
                Watch Demo
              </a>
              <a href="#" class="inline-flex items-center gap-3 px-6 py-3 rounded-lg bg-white/10 border border-white/20 hover:bg-white/20 transition">
                Get Started
              </a>
            </div>

            <div class="mt-8 flex gap-6 items-center">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M11 11V3h2v8h-2zm-8 9h18"></path></svg>
                </div>
                <div class="text-sm text-white/90">Trusted by finance teams worldwide</div>
              </div>
              <div class="text-sm text-white/80">Free 14-day trial · No credit card</div>
            </div>
          </div>

          <!-- Illustration -->
          <div class="relative">
            <div class="mx-auto w-full max-w-xl">
              <!-- composed vector illustration (abstract finance) -->
              <svg viewBox="0 0 800 600" class="w-full h-auto" xmlns="http://www.w3.org/2000/svg" fill="none">
                <defs>
                  <linearGradient id="g1" x1="0" x2="1">
                    <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.9"/>
                    <stop offset="1" stop-color="#FFFFFF" stop-opacity="0.08"/>
                  </linearGradient>
                </defs>

                <!-- large rounded card -->
                <rect x="40" y="60" rx="28" ry="28" width="520" height="360" fill="url(#g1)" transform="rotate(-6 40 60)" opacity="0.06"/>

                <!-- receipt card -->
                <g transform="translate(80,70)">
                  <rect width="340" height="220" rx="20" fill="#fff" class="card-shadow"/>
                  <rect x="18" y="18" width="120" height="18" rx="6" fill="#7F00FF" opacity="0.95"/>
                  <rect x="18" y="48" width="260" height="12" rx="6" fill="#E9EAFB"/>
                  <rect x="18" y="72" width="220" height="12" rx="6" fill="#F4F9FF"/>
                  <rect x="18" y="96" width="140" height="12" rx="6" fill="#E9EAFB"/>
                  <circle cx="300" cy="110" r="26" fill="#00C6FF"/>
                  <text x="288" y="116" fill="white" font-size="14" font-weight="700">$</text>
                </g>

                <!-- bar chart -->
                <g transform="translate(420,160)">
                  <rect x="0" y="40" width="30" height="80" rx="6" fill="#7F00FF"/>
                  <rect x="50" y="0" width="30" height="120" rx="6" fill="#00C6FF"/>
                  <rect x="100" y="20" width="30" height="100" rx="6" fill="#7F00FF" opacity="0.85"/>
                  <rect x="150" y="60" width="30" height="60" rx="6" fill="#00C6FF" opacity="0.85"/>
                </g>

                <!-- small user avatars -->
                <g transform="translate(520,60)">
                  <circle cx="20" cy="20" r="18" fill="#fff" opacity="0.9"/>
                  <circle cx="60" cy="20" r="18" fill="#fff" opacity="0.9"/>
                  <path d="M10 45c20 10 60 10 80 0" stroke="#000" stroke-opacity="0.04" stroke-width="6" stroke-linecap="round"/>
                </g>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- decorative wave -->
      <div class="relative -mt-1">
        <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-20">
          <path d="M0 0h1440v44c-150 30-300 40-480 40S420 74 240 44 0 0 0 0z" fill="white"/>
        </svg>
      </div>
    </section>

    <!-- PROBLEM + FEATURES -->
    <section id="features" class="max-w-7xl mx-auto px-6 lg:px-10 -mt-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Problem -->
        <div class="bg-white p-8 rounded-2xl shadow card-shadow">
          <h3 class="text-xl font-semibold">The problem</h3>
          <p class="mt-4 text-slate-600">
            Manual receipts, slow approvals and scattered records cause delays and errors. Finance teams waste hours reconciling and chasing approvals.
          </p>
          <ul class="mt-6 space-y-3 text-sm text-slate-600">
            <li>• Paper receipts and email threads</li>
            <li>• No central policy enforcement</li>
            <li>• Delayed reimbursements and missing audits</li>
          </ul>
        </div>

        <!-- Feature grid -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="p-6 rounded-2xl bg-gradient-to-r from-white to-white/80 card-shadow">
            <h4 class="font-semibold">Smart Receipt Capture</h4>
            <p class="mt-2 text-sm text-slate-600">Upload receipts or snap a photo — built-in OCR extracts amounts, vendors, and dates automatically.</p>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-white to-white/80 card-shadow">
            <h4 class="font-semibold">Approval Workflows</h4>
            <p class="mt-2 text-sm text-slate-600">Multi-step approvals with conditional routing, manager thresholds, and audit trails.</p>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-white to-white/80 card-shadow">
            <h4 class="font-semibold">Policy & Conditional Rules</h4>
            <p class="mt-2 text-sm text-slate-600">Enforce per-category limits, auto-reject outliers, and add custom rules per department.</p>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-white to-white/80 card-shadow">
            <h4 class="font-semibold">Integrations & API</h4>
            <p class="mt-2 text-sm text-slate-600">Connect to payroll, accounting, and single sign-on systems. Full REST API for automations.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- WORKFLOW -->
    <section id="workflow" class="max-w-7xl mx-auto px-6 lg:px-10 mt-14">
      <div class="text-center">
        <h2 class="text-3xl font-bold">How it works</h2>
        <p class="mt-3 text-slate-600 max-w-2xl mx-auto">A simple flow that replaces manual steps with automation — from submission to reimbursement.</p>
      </div>

      <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="p-6 rounded-2xl bg-white card-shadow text-center">
          <div class="w-14 h-14 mx-auto rounded-full gradient-bg flex items-center justify-center text-white">
            <span class="font-bold">1</span>
          </div>
          <h3 class="mt-4 font-semibold">Submit</h3>
          <p class="mt-2 text-sm text-slate-600">Upload receipts via mobile, web, or email. OCR auto-fills the form to save time.</p>
        </div>

        <div class="p-6 rounded-2xl bg-white card-shadow text-center">
          <div class="w-14 h-14 mx-auto rounded-full gradient-bg flex items-center justify-center text-white">
            <span class="font-bold">2</span>
          </div>
          <h3 class="mt-4 font-semibold">Approve</h3>
          <p class="mt-2 text-sm text-slate-600">Managers receive contextual approvals with inline receipts and policy warnings.</p>
        </div>

        <div class="p-6 rounded-2xl bg-white card-shadow text-center">
          <div class="w-14 h-14 mx-auto rounded-full gradient-bg flex items-center justify-center text-white">
            <span class="font-bold">3</span>
          </div>
          <h3 class="mt-4 font-semibold">Reimburse</h3>
          <p class="mt-2 text-sm text-slate-600">Export to payroll or trigger reimbursements automatically with audit-ready reports.</p>
        </div>
      </div>
    </section>

    <!-- ROLES -->
    <section id="roles" class="max-w-7xl mx-auto px-6 lg:px-10 mt-14">
      <div class="text-center">
        <h2 class="text-3xl font-bold">Roles & Permissions</h2>
        <p class="mt-3 text-slate-600 max-w-2xl mx-auto">Built for Employees, Managers, and Admins — granular access and control for each role.</p>
      </div>

      <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-white card-shadow">
          <h3 class="font-semibold">Employee</h3>
          <p class="mt-2 text-sm text-slate-600">Submit expenses, attach receipts, check statuses and see policy hints in-app.</p>
          <ul class="mt-4 text-sm text-slate-600 space-y-2">
            <li>• Mobile & web submission</li>
            <li>• Personal expense history</li>
            <li>• Quick-scan receipt OCR</li>
          </ul>
        </div>

        <div class="p-6 rounded-2xl bg-white card-shadow">
          <h3 class="font-semibold">Manager</h3>
          <p class="mt-2 text-sm text-slate-600">Approve or reject, add comments, and escalate exceptions with one-click actions.</p>
          <ul class="mt-4 text-sm text-slate-600 space-y-2">
            <li>• Approval inbox</li>
            <li>• Policy warnings</li>
            <li>• Bulk approvals</li>
          </ul>
        </div>

        <div class="p-6 rounded-2xl bg-white card-shadow">
          <h3 class="font-semibold">Admin</h3>
          <p class="mt-2 text-sm text-slate-600">Configure rules, integrate systems, audit trails, and export reports for accounting.</p>
          <ul class="mt-4 text-sm text-slate-600 space-y-2">
            <li>• Role management</li>
            <li>• Policy & limit configuration</li>
            <li>• API keys & integrations</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- CTA / PRICING TEASE -->
    <section class="max-w-7xl mx-auto px-6 lg:px-10 mt-16">
      <div class="rounded-2xl overflow-hidden bg-gradient-to-r from-[#f8fcff] to-white border card-shadow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-8 items-center">
          <div>
            <h3 class="text-2xl font-bold">Ready to remove expense chaos?</h3>
            <p class="mt-3 text-slate-600 max-w-xl">Start a free 14-day trial. No credit card required. Or schedule a demo to see ExpenseFlow in action.</p>
            <div class="mt-6 flex gap-3">
              <a href="#" class="px-6 py-3 bg-gradient-to-r from-[#7F00FF] to-[#00C6FF] text-white rounded-lg font-semibold">Get Started — Free</a>
              <a href="#" class="px-6 py-3 border rounded-lg">Schedule a Demo</a>
            </div>
          </div>

          <div class="p-4">
            <div class="bg-white rounded-lg p-4 shadow-inner">
              <div class="text-sm font-medium">Starter</div>
              <div class="mt-3 flex items-baseline gap-2">
                <div class="text-3xl font-extrabold">$0</div>
                <div class="text-sm text-slate-500">/ 14-day trial</div>
              </div>
              <ul class="mt-4 text-sm text-slate-600 space-y-2">
                <li>• Receipt OCR</li>
                <li>• Approval workflows</li>
                <li>• Email & mobile submission</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact" class="mt-16 bg-slate-900 text-white">
      <div class="max-w-7xl mx-auto px-6 lg:px-10 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M3 12h7l3 7 6-14"/></svg>
            </div>
            <div>
              <div class="text-lg font-semibold">ExpenseFlow</div>
              <div class="text-sm text-slate-400">Simple. Fast. Auditable.</div>
            </div>
          </div>
          <p class="mt-4 text-sm text-slate-400 max-w-sm">Built to streamline expense operations and reduce reconciliation overhead for finance teams.</p>
        </div>

        <div>
          <h4 class="font-semibold">Product</h4>
          <ul class="mt-4 text-sm text-slate-400 space-y-2">
            <li><a href="#features" class="hover:underline">Features</a></li>
            <li><a href="#workflow" class="hover:underline">Workflow</a></li>
            <li><a href="#" class="hover:underline">Docs & API</a></li>
          </ul>
        </div>

        <div>
          <h4 class="font-semibold">Contact</h4>
          <p class="mt-4 text-sm text-slate-400">hello@expenseflow.example</p>
          <p class="mt-2 text-sm text-slate-400">© <span id="year"></span> ExpenseFlow — All rights reserved.</p>
        </div>
      </div>
    </footer>

  </main>

  <!-- Small script for nav toggle + year -->
  <script>
    const navToggle = document.getElementById('navToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const openIcon = document.getElementById('navOpenIcon');
    const closeIcon = document.getElementById('navCloseIcon');

    navToggle && navToggle.addEventListener('click', () => {
      if (mobileMenu.classList.contains('hidden')) {
        mobileMenu.classList.remove('hidden');
        openIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
      } else {
        mobileMenu.classList.add('hidden');
        openIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
      }
    });

    // set year in footer
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>

</body>
</html>
