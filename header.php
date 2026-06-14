<?php
require_once 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookClub Portal | Shared Hosting Edition</title>
    <!-- Tailwind Engine via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F6F0;
            color: #2D2D2D;
        }
        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-indigo-150 selection:text-indigo-900">

    <!-- Global Navigation -->
    <header class="bg-white/80 border-b border-slate-200/60 sticky top-0 z-40 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
            
            <!-- Logo Brand -->
            <a href="index.php" class="flex items-center space-x-2.5 transition-all hover:opacity-95">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <span class="text-2xl font-serif font-bold tracking-tight text-indigo-950">
                    BookClub <span class="italic text-indigo-600 font-serif">Portal</span>
                </span>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center space-x-2 bg-slate-100/60 p-1.5 rounded-full border border-slate-200/50">
                <a href="index.php" class="flex items-center space-x-1.5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all <?php echo $current_page === 'index.php' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-600'; ?>">
                    Explore
                </a>
                <a href="catalog.php" class="flex items-center space-x-1.5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all <?php echo $current_page === 'catalog.php' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-600'; ?>">
                    Catalog
                </a>
                <?php if ($user_id): ?>
                    <a href="my-list.php" class="flex items-center space-x-1.5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all <?php echo $current_page === 'my-list.php' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-600'; ?>">
                        My List
                    </a>
                <?php endif; ?>
                <a href="members.php" class="flex items-center space-x-1.5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all <?php echo $current_page === 'members.php' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-600'; ?>">
                    Members
                </a>
                <?php if ($user_id && $user_role === 'admin'): ?>
                    <a href="admin.php" class="flex items-center space-x-1.5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all <?php echo $current_page === 'admin.php' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-550 border border-transparent hover:text-rose-600 hover:border-rose-100'; ?>">
                        Admin Panel
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Actions Session Control -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <?php if ($user_id): ?>
                    <span class="hidden sm:inline-flex items-center bg-indigo-50 text-indigo-700 font-semibold text-xs px-4 py-2 rounded-full border border-indigo-100/80">
                        <?php echo htmlspecialchars(explode('@', $user_email)[0]); ?>
                        <?php if ($user_role === 'admin'): ?>
                            <span class="ml-1.5 bg-rose-100 text-rose-700 text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-md">Admin</span>
                        <?php endif; ?>
                    </span>
                    <a href="auth.php?logout=true" class="rounded-full bg-slate-900 border border-slate-800 text-white px-4 py-2 text-xs font-semibold uppercase tracking-wider hover:bg-slate-800 transition-colors">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="auth.php" class="rounded-full bg-indigo-600 text-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-indigo-700 transition-colors shadow-sm">
                        Sign In
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Hamburger Button -->
                <button id="mobile-menu-toggle" class="block md:hidden p-2 rounded-xl border border-slate-200 text-slate-550 hover:bg-slate-50 hover:text-slate-800 transition-colors active:scale-95" aria-label="Toggle navigation menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

        </div>
    </header>

    <!-- Mobile Navigation Menu Dropdown (visible only on viewports below md width) -->
    <div id="mobile-menu-drawer" class="hidden md:hidden bg-white/95 border-b border-slate-200 p-4 space-y-1.5 sticky top-[73px] z-30 shadow-md backdrop-blur-md animate-in slide-in-from-top-4 duration-150">
        <a href="index.php" class="block rounded-xl px-4 py-3 text-xs font-bold uppercase tracking-wider transition-colors <?php echo $current_page === 'index.php' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-indigo-50/50 hover:text-indigo-700'; ?>">
            Explore
        </a>
        <a href="catalog.php" class="block rounded-xl px-4 py-3 text-xs font-bold uppercase tracking-wider transition-colors <?php echo $current_page === 'catalog.php' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-indigo-50/50 hover:text-indigo-700'; ?>">
            Catalog
        </a>
        <?php if ($user_id): ?>
            <a href="my-list.php" class="block rounded-xl px-4 py-3 text-xs font-bold uppercase tracking-wider transition-colors <?php echo $current_page === 'my-list.php' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-indigo-50/50 hover:text-indigo-700'; ?>">
                My List
            </a>
        <?php endif; ?>
        <a href="members.php" class="block rounded-xl px-4 py-3 text-xs font-bold uppercase tracking-wider transition-colors <?php echo $current_page === 'members.php' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-indigo-50/50 hover:text-indigo-700'; ?>">
            Members
        </a>
        <?php if ($user_id && $user_role === 'admin'): ?>
            <a href="admin.php" class="block rounded-xl px-4 py-3 text-xs font-bold uppercase tracking-wider transition-colors <?php echo $current_page === 'admin.php' ? 'bg-rose-600 text-white' : 'text-rose-600 hover:bg-rose-50'; ?>">
                Admin Panel
            </a>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('mobile-menu-toggle');
            const drawer = document.getElementById('mobile-menu-drawer');
            if (toggleBtn && drawer) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    drawer.classList.toggle('hidden');
                });
                
                // Hide drawer if clicking outside
                document.addEventListener('click', function(e) {
                    if (!drawer.contains(e.target) && e.target !== toggleBtn) {
                        drawer.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Main Body Container -->
    <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
