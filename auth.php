<?php
require_once 'db.php';

$error = '';
$success = '';

// Handle Login Form Submission
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill out both email and password Fields.';
    } else {
        $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email or password combination.';
            }
        } else {
            $error = 'Account does not exist. Please sign up below!';
        }
    }
}

// Handle Register Form Submission
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are strictly required for registration.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if user already exists
        $check_query = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error = 'This email is already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $joined_date = date('Y-m-d');

            $insert_query = "INSERT INTO users (email, password, role, joined_at) VALUES ('$email', '$hashed_password', 'member', '$joined_date')";
            if (mysqli_query($conn, $insert_query)) {
                // Automatically log the user in to bypass double input friction
                $new_user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'member';

                // Redirect to dashboard immediately
                header('Location: index.php');
                exit();
            } else {
                $error = 'Database enrollment failed: ' . mysqli_error($conn);
            }
        }
    }
}

// Logging Out
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: auth.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sovereign Authentication | BookClub Portal</title>
    <!-- Premium Fonts and Tailwind CLI via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F6F0;
            color: #2D2D2D;
        }
        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center p-4">

    <!-- Auth Frame Container -->
    <div class="w-full max-w-md bg-white rounded-[2rem] border border-slate-200/80 shadow-lg p-8 space-y-6">
        
        <!-- Header Brand Info -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h1 class="text-3xl font-serif font-bold text-indigo-950 tracking-tight mt-3">BookClub <span class="italic text-indigo-600 font-serif">Portal</span></h1>
            <p class="text-xs text-slate-500 font-medium">Connect on shared hosting with standard PHP & MySQL</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-800 text-xs font-bold border border-red-100 rounded-xl p-4.5">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-100 rounded-xl p-4.5">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Tab togglers to shift Sign In / Sign Up smoothly -->
        <div class="grid grid-cols-2 bg-slate-100 p-1 rounded-xl" id="auth-tabs">
            <button id="show-login-btn" onclick="toggleAuth('login')" class="py-2.5 rounded-lg text-xs font-bold uppercase transition-all bg-white text-indigo-900 shadow-sm">Sign In</button>
            <button id="show-signup-btn" onclick="toggleAuth('register')" class="py-2.5 rounded-lg text-xs font-bold uppercase transition-all text-slate-500 hover:text-indigo-900">Sign Up</button>
        </div>

        <!-- 1. LOGIN FORM -->
        <form action="auth.php" method="POST" id="login-form" class="space-y-4">
            <input type="hidden" name="action" value="login">
            
            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
                <input type="email" name="email" required placeholder="name@domain.com" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-3 px-4.5 text-xs text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-3 px-4.5 text-xs text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>

            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl text-xs uppercase tracking-wider transition-colors shadow">Sign In Session</button>
        </form>

        <!-- 2. REGISTER FORM (hidden by default) -->
        <form action="auth.php" method="POST" id="register-form" class="space-y-4 hidden">
            <input type="hidden" name="action" value="register">

            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
                <input type="email" name="email" required placeholder="name@domain.com" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-3 px-4.5 text-xs text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Choose Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-3 px-4.5 text-xs text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Re-type your password" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 py-3 px-4.5 text-xs text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all">
            </div>

            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl text-xs uppercase tracking-wider transition-colors shadow">Create Free Account</button>
        </form>

        <div class="text-center pt-2">
            <a href="index.php" class="text-xs text-slate-500 hover:text-indigo-600 underline font-medium">Continue as Guest Explorer →</a>
        </div>

    </div>

    <!-- Quick instructions helper -->
    <div class="mt-8 text-center text-xs text-slate-400 font-medium">
        <span>Default Seed Account: <strong class="text-slate-500">admin@bookclub.org</strong> / <strong class="text-slate-500">pass123</strong></span>
    </div>

    <script>
        function toggleAuth(mode) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const showLoginBtn = document.getElementById('show-login-btn');
            const showSignupBtn = document.getElementById('show-signup-btn');

            if (mode === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                showLoginBtn.classList.add('bg-white', 'text-indigo-900', 'shadow-sm');
                showLoginBtn.classList.remove('text-slate-500');
                showSignupBtn.classList.remove('bg-white', 'text-indigo-900', 'shadow-sm');
                showSignupBtn.classList.add('text-slate-500');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                showSignupBtn.classList.add('bg-white', 'text-indigo-900', 'shadow-sm');
                showSignupBtn.classList.remove('text-slate-500');
                showLoginBtn.classList.remove('bg-white', 'text-indigo-900', 'shadow-sm');
                showLoginBtn.classList.add('text-slate-500');
            }
        }
    </script>
</body>
</html>
