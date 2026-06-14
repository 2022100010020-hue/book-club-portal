<?php
require_once 'db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'member';

// Force authentication
if (!$user_id) {
    header('Location: auth.php');
    exit();
}

require_once 'header.php';

$action_error = '';
$success_message = '';

// Handle Admin-only Account Mutations (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if ($user_role === 'admin') {
        $action = $_POST['action_type'];
        
        // 1. ADD / CREATE USER
        if ($action === 'create_user') {
            $email = mysqli_real_escape_string($conn, trim($_POST['email']));
            $password = trim($_POST['password']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $role = ($role === 'admin') ? 'admin' : 'member';
            $joined_at = date('Y-m-d');
            
            if (empty($email) || empty($password)) {
                $action_error = "All fields (email, secure password) are mandatory.";
            } elseif (strlen($password) < 6) {
                $action_error = "Password must be at least 6 characters in length.";
            } else {
                // Check uniqueness
                $chk = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
                if ($chk && mysqli_num_rows($chk) > 0) {
                    $action_error = "That email address is already recorded inside active accounts.";
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $ins = mysqli_query($conn, "INSERT INTO users (email, password, role, joined_at) VALUES ('$email', '$hashed', '$role', '$joined_at')");
                    if ($ins) {
                        $success_message = "New user account successfully created!";
                    } else {
                        $action_error = "Database enrollment procedure failed: " . mysqli_error($conn);
                    }
                }
            }
        }
        
        // 2. EDIT / UPDATE USER (Email, Role, optional Password)
        elseif ($action === 'update_user') {
            $target_id = (int)$_POST['target_id'];
            $email = mysqli_real_escape_string($conn, trim($_POST['email']));
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $role = ($role === 'admin') ? 'admin' : 'member';
            $password = trim($_POST['password']);
            
            if ($target_id === $user_id && $role !== 'admin') {
                $action_error = "Safety Demotion Lock: You cannot demote yourself from administrative rights!";
            } elseif (empty($email)) {
                $action_error = "User email address field cannot be left blank.";
            } else {
                // Check uniqueness
                $chk = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $target_id");
                if ($chk && mysqli_num_rows($chk) > 0) {
                    $action_error = "This email address is already utilized by another active profile.";
                } else {
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            $action_error = "Password update must contain at least 6 characters.";
                        } else {
                            $hashed = password_hash($password, PASSWORD_BCRYPT);
                            $upd = mysqli_query($conn, "UPDATE users SET email = '$email', password = '$hashed', role = '$role' WHERE id = $target_id");
                        }
                    } else {
                        $upd = mysqli_query($conn, "UPDATE users SET email = '$email', role = '$role' WHERE id = $target_id");
                    }
                    
                    if (isset($upd) && $upd) {
                        $success_message = "Member records updated successfully!";
                        if ($target_id === $user_id) {
                            $_SESSION['user_email'] = $email;
                        }
                    } elseif (!isset($action_error) || empty($action_error)) {
                        $action_error = "Database modification request failed: " . mysqli_error($conn);
                    }
                }
            }
        }
        
        // 3. DELETE USER (Cascades reading_list and book uploads)
        elseif ($action === 'delete_user') {
            $target_id = (int)$_POST['target_id'];
            if ($target_id === $user_id) {
                $action_error = "Session Protection Lock: You cannot delete your currently active admin session!";
            } else {
                $del = mysqli_query($conn, "DELETE FROM users WHERE id = $target_id");
                if ($del) {
                    $success_message = "Member profile and all their linked tracking logs were fully cleared!";
                } else {
                    $action_error = "Database deletion procedure failed: " . mysqli_error($conn);
                }
            }
        }
    }
    
    // Handle Member Self Profile updates
    elseif ($user_role === 'member' && $_POST['action_type'] === 'update_own_profile') {
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $password = trim($_POST['password']);
        
        if (empty($email)) {
            $action_error = "Your email address cannot be left blank.";
        } else {
            $chk = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
            if ($chk && mysqli_num_rows($chk) > 0) {
                $action_error = "This email address is already registered on another active reader profile.";
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $action_error = "Updated password must be at least 6 characters.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_BCRYPT);
                        $upd = mysqli_query($conn, "UPDATE users SET email = '$email', password = '$hashed' WHERE id = $user_id");
                    }
                } else {
                    $upd = mysqli_query($conn, "UPDATE users SET email = '$email' WHERE id = $user_id");
                }
                
                if (isset($upd) && $upd) {
                    $success_message = "Your account settings have been saved successfully!";
                    $_SESSION['user_email'] = $email;
                } elseif (!isset($action_error) || empty($action_error)) {
                    $action_error = "Db override failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<div class="space-y-8" id="members-page-viewport">

    <!-- Header Bento Segment -->
    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-xs">
        <div>
            <h1 class="text-3xl font-serif font-bold tracking-tight text-slate-900">
                <?php echo ($user_role === 'admin') ? "Governance & Directory Console" : "My Reader Chamber"; ?>
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                <?php echo ($user_role === 'admin') ? "Control center for member account registration, secure updates, and catalog assignments." : "Your highly personal room to monitor credentials, track statistics, and adjust security values."; ?>
            </p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 border border-indigo-200 text-indigo-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
    </div>

    <!-- Alert notifications -->
    <?php if (!empty($action_error)): ?>
        <div class="bg-red-50 text-red-800 text-xs font-bold border border-red-100 rounded-2xl p-4.5 flex items-center shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span><?php echo htmlspecialchars($action_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-100 rounded-2xl p-4.5 flex items-center shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- ==============================================
         ADMIN VIEW: GLOBAL DIRECTORY AND CRUD CONTROLS
         ============================================== -->
    <?php if ($user_role === 'admin'): ?>
        <?php
        // Fetch count statistics
        $members_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
        $total_members = mysqli_fetch_assoc($members_count_q)['total'];

        $books_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM books");
        $total_books = mysqli_fetch_assoc($books_count_q)['total'];

        $completed_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list WHERE status = 'Completed'");
        $total_completed = mysqli_fetch_assoc($completed_count_q)['total'];

        // Get full list of users
        $all_members_q = mysqli_query($conn, "SELECT id, email, role, joined_at FROM users ORDER BY joined_at ASC");
        ?>

        <!-- Admin Metrics Bento Panel -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Global Accounts</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_members; ?> Profiles</span>
            </div>
            <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Global Volumes</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_books; ?> Books</span>
            </div>
            <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Readers Accomplishments</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_completed; ?> Finished</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Members CRUD Table Directory (8 Cols) -->
            <div class="lg:col-span-8 bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-serif font-bold text-slate-900 tracking-tight">Active Members Directory</h2>
                        <p class="text-[11px] text-slate-400 font-medium">Click Actions next to registries to execute immediate edits or hard deletions</p>
                    </div>
                    <button onclick="openAdminUserModal('create_user')" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full px-4 py-2 text-xs font-bold uppercase tracking-wider flex items-center space-x-1 shadow-sm transition-colors cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span>Add Member</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead>
                            <tr class="text-left font-bold uppercase tracking-wider text-slate-400 text-[10px] border-b border-slate-100">
                                <th class="pb-3 pr-2">ID</th>
                                <th class="pb-3 px-2">Account email</th>
                                <th class="pb-3 px-2">Membership Joined</th>
                                <th class="pb-3 px-2">Role</th>
                                <th class="pb-3 pl-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($all_members_q && mysqli_num_rows($all_members_q) > 0): ?>
                                <?php while ($member = mysqli_fetch_assoc($all_members_q)): 
                                    $isMe = ($member['id'] === $user_id);
                                ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3.5 pr-2 font-mono font-bold text-slate-400">#<?php echo $member['id']; ?></td>
                                        <td class="py-3.5 px-2">
                                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                                <span><?php echo htmlspecialchars($member['email']); ?></span>
                                                <?php if ($isMe): ?>
                                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded-full">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-2 text-slate-500 font-medium">
                                            <?php echo date('M d, Y', strtotime($member['joined_at'])); ?>
                                        </td>
                                        <td class="py-3.5 px-2">
                                            <span class="inline-flex rounded-full text-[9px] font-extrabold uppercase px-2.5 py-0.5 border <?php echo $member['role'] === 'admin' ? 'bg-rose-550/10 text-rose-700 border-rose-200/50' : 'bg-slate-100 text-slate-650 border-slate-200'; ?>">
                                                <?php echo htmlspecialchars($member['role']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 pl-2 text-right">
                                            <div class="inline-flex items-center space-x-1.5">
                                                <button onclick="openAdminUserModal('update_user', <?php echo htmlspecialchars(json_encode($member)); ?>)" class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-100 transition-colors cursor-pointer" title="Edit Member Credentials">
                                                    <svg xmlns="http://www.w3.org/2005/Atom" class="h-3.5 w-3.5 hover:scale-105" fill="none" viewBox="0 0 24 24" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <?php if (!$isMe): ?>
                                                    <form method="POST" action="members.php" onsubmit="return confirm('Are you absolutely sure you want to completely delete this user and all associated shelves data? This action is non-reversible.');">
                                                        <input type="hidden" name="action_type" value="delete_user" />
                                                        <input type="hidden" name="target_id" value="<?php echo $member['id']; ?>" />
                                                        <button type="submit" class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-100 transition-colors cursor-pointer" title="Delete Member">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="p-2 rounded-xl text-slate-300 select-none">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400 italic">No users registered inside database pools.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Admin Fast Track Shortcuts (4 Cols) -->
            <div class="lg:col-span-4 bg-slate-900 rounded-[2rem] p-7 text-white shadow-xl space-y-6">
                <div>
                    <h3 class="text-md font-serif font-bold text-indigo-305 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Administrative Guard
                    </h3>
                    <p class="text-[11px] text-slate-420 mt-1 leading-relaxed">Governance overrides have absolute relational integrity. Deleting a user cascade-clears linked stars ratings, commentary logs, and their book additions.</p>
                </div>

                <div class="space-y-3.5 pt-2">
                    <div class="flex items-center justify-between border-b border-white/5 pb-2.5">
                        <span class="text-xs text-slate-400">Database Engine</span>
                        <span class="text-xs font-mono font-bold text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-900/30">MariaDB InnoDB</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-white/5 pb-2.5">
                        <span class="text-xs text-slate-400">Governance Level</span>
                        <span class="text-xs font-bold text-rose-400">Super Administrator</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Session Port</span>
                        <span class="text-xs font-mono text-slate-400">nginx:3000 proxies PHP</span>
                    </div>
                </div>

                <div class="pt-2">
                    <button onclick="openAdminUserModal('create_user')" class="w-full bg-indigo-650 hover:bg-indigo-600 border border-indigo-500/30 text-white rounded-xl py-3 text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Enroll New Member Account
                    </button>
                </div>
            </div>
        </div>

    <!-- ==============================================
         MEMBER VIEW: OWN ACCOUNT PROFILE & CONTROL
         ============================================== -->
    <?php else: ?>
        <?php
        // Fetch Member stat counters
        $completed_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list WHERE user_id = $user_id AND status = 'Completed'");
        $my_completed = mysqli_fetch_assoc($completed_q)['total'];

        $reading_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list WHERE user_id = $user_id AND status = 'Reading'");
        $my_reading = mysqli_fetch_assoc($reading_q)['total'];

        $want_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list WHERE user_id = $user_id AND status = 'Want to Read'");
        $my_want = mysqli_fetch_assoc($want_q)['total'];

        $reviews_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list WHERE user_id = $user_id AND (rating IS NOT NULL OR (notes IS NOT NULL AND notes != ''))");
        $my_reviews = mysqli_fetch_assoc($reviews_q)['total'];

        // Get registration date
        $me_q = mysqli_query($conn, "SELECT joined_at FROM users WHERE id = $user_id LIMIT 1");
        $me_joined = mysqli_fetch_assoc($me_q)['joined_at'];
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Personal Reader Card Stats (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Welcome greeting block -->
                <div class="rounded-[2rem] bg-indigo-950 text-white p-7 relative overflow-hidden flex flex-col justify-between shadow-md">
                    <div class="relative z-10 space-y-3">
                        <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-400/20 text-[9px] font-extrabold uppercase Tracking-widest px-3 py-1 rounded-full inline-block">My Credentials Verified</span>
                        <h2 class="text-2xl font-serif font-bold italic text-white">Beautiful reading hours, <?php echo htmlspecialchars(explode('@', $user_email)[0]); ?>!</h2>
                        <p class="text-indigo-200 text-xs max-w-xl leading-relaxed">Your virtual shelf logs are completely encrypted and locked under your session. Your fellow readers can see your commentaries, but never your private passwords, profiles, or keys.</p>
                    </div>
                    <div class="mt-6 border-t border-white/10 pt-4 flex items-center justify-between text-xs text-indigo-300">
                        <span>Reader Registry Level: <strong class="text-white text-[11px] uppercase z-10 relative">Member</strong></span>
                        <span class="font-bold flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Active since <?php echo date('F d, Y', strtotime($me_joined)); ?></span>
                    </div>

                    <div class="absolute -right-12 -top-12 w-40 h-40 bg-indigo-750 rounded-full blur-2.5xl opacity-40"></div>
                </div>

                <!-- Personal Progress Bento Blocks -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="bg-white border border-slate-205 rounded-2xl p-5 shadow-xs">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Completed</p>
                        <p class="text-2xl font-serif font-bold text-slate-900 mt-1"><?php echo $my_completed; ?> books</p>
                    </div>
                    <div class="bg-white border border-slate-205 rounded-2xl p-5 shadow-xs">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">In Progress</p>
                        <p class="text-2xl font-serif font-bold text-slate-900 mt-1"><?php echo $my_reading; ?> volumes</p>
                    </div>
                    <div class="bg-white border border-slate-205 rounded-2xl p-5 shadow-xs">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Want to Read</p>
                        <p class="text-2xl font-serif font-bold text-slate-900 mt-1"><?php echo $my_want; ?> titles</p>
                    </div>
                    <div class="bg-white border border-slate-205 rounded-2xl p-5 shadow-xs">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Journal Notes</p>
                        <p class="text-2xl font-serif font-bold text-slate-900 mt-1"><?php echo $my_reviews; ?> reviews</p>
                    </div>
                </div>

                <!-- Personal Shelf Overview -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-md font-serif font-bold text-slate-900">Your Shelf Directory Shortcuts</h3>
                            <p class="text-[10px] text-slate-400 font-medium">Overview of books currently pinned in your tracking logs</p>
                        </div>
                        <a href="my-list.php" class="text-xs font-bold text-indigo-650 hover:text-indigo-800 flex items-center space-x-0.5">
                            <span>Open My List</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>

                    <?php
                    $my_list_q = mysqli_query($conn, "
                        SELECT b.title, b.author, rl.status, rl.rating 
                        FROM reading_list rl
                        JOIN books b ON rl.book_id = b.id
                        WHERE rl.user_id = $user_id
                        ORDER BY rl.updated_at DESC
                        LIMIT 4
                    ");
                    ?>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php if ($my_list_q && mysqli_num_rows($my_list_q) > 0): ?>
                            <?php while ($log = mysqli_fetch_assoc($my_list_q)): ?>
                                <div class="py-3 flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-slate-800"><?php echo htmlspecialchars($log['title']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium">by <?php echo htmlspecialchars($log['author']); ?></p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 text-[9px] font-extrabold uppercase rounded bg-indigo-50 border border-indigo-100 text-indigo-700"><?php echo htmlspecialchars($log['status']); ?></span>
                                        <?php if ($log['rating']): ?>
                                            <span class="text-amber-500 font-bold"><?php echo str_repeat('★', $log['rating']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="py-8 text-center text-slate-400 italic">Your personal shelves are currently empty. Visit the catalog to add books!</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right: Personal Account Settings (4 Cols) -->
            <div class="lg:col-span-4 bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-4">
                <h3 class="text-md font-serif font-bold text-slate-900 border-b border-slate-105 pb-3">Security & Account Settings</h3>
                
                <form action="members.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action_type" value="update_own_profile" />
                    
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5" for="me_email">Profile Email</label>
                        <input 
                            id="me_email"
                            type="email" 
                            name="email" 
                            required
                            value="<?php echo htmlspecialchars($user_email); ?>"
                            class="w-full rounded-xl border border-slate-250 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-200 transition-all"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5" for="me_pass">New Password <span class="capitalize text-slate-400 font-medium">(Leave empty to keep current)</span></label>
                        <input 
                            id="me_pass"
                            type="password" 
                            name="password" 
                            placeholder="At least 6 characters" 
                            class="w-full rounded-xl border border-slate-250 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-200 transition-all"
                        />
                    </div>

                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white rounded-xl py-2.5 text-xs font-bold uppercase tracking-wider shadow-xs transition-colors cursor-pointer">
                        Update Settings
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ==============================================
     ADMIN USER CREDENTIALS CRUD MODAL OVERLAYS
     ============================================== -->
<?php if ($user_role === 'admin'): ?>
    <div id="admin-user-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background screen blur -->
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true" onclick="closeAdminUserModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content container -->
            <div class="inline-block align-bottom bg-white rounded-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="bg-white p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-md font-serif font-bold text-slate-900" id="modal-header-title">Enroll Member</h3>
                        <button onclick="closeAdminUserModal()" class="p-1.5 rounded-lg border border-slate-100 text-slate-400 hover:text-slate-700 transition-colors cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form action="members.php" method="POST" class="space-y-4" id="admin-user-flow-form">
                        <input type="hidden" name="action_type" id="modal_action_type" value="create_user" />
                        <input type="hidden" name="target_id" id="modal_target_id" value="" />

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5" for="modal_email">Account Email</label>
                            <input 
                                id="modal_email"
                                type="email" 
                                name="email" 
                                required
                                placeholder="name@domain.com"
                                class="w-full rounded-xl border border-slate-250 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-850 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-200 transition-all"
                            />
                        </div>

                        <div>
                            <div class="flex justify-between items-baseline mb-1.5">
                                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400" for="modal_password">Password</label>
                                <span id="modal_password_warning" class="text-[9px] font-medium text-slate-400 hidden">Leave blank to keep existing</span>
                            </div>
                            <input 
                                id="modal_password"
                                type="password" 
                                name="password" 
                                placeholder="Minimum 6 characters" 
                                class="w-full rounded-xl border border-slate-250 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-850 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-200 transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5" for="modal_role">Security Role Access</label>
                            <select 
                                id="modal_role"
                                name="role"
                                class="w-full rounded-xl border border-slate-255 bg-slate-50/50 px-4 py-2 text-xs font-bold text-slate-705 focus:border-indigo-500 focus:bg-white focus:outline-none cursor-pointer"
                            >
                                <option value="member">Standard Member</option>
                                <option value="admin">Super Administrator</option>
                            </select>
                        </div>

                        <div class="pt-2 flex justify-end space-x-2">
                            <button type="button" onclick="closeAdminUserModal()" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wide cursor-pointer">Cancel</button>
                            <button type="submit" id="modal_submit_btn" class="px-5 py-2 hover:bg-slate-800 bg-slate-900 border border-slate-800 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition-colors uppercase tracking-wide cursor-pointer">Register Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAdminUserModal(action, user = null) {
            const modal = document.getElementById('admin-user-modal');
            const actionInput = document.getElementById('modal_action_type');
            const targetIdInput = document.getElementById('modal_target_id');
            const emailInput = document.getElementById('modal_email');
            const passwordInput = document.getElementById('modal_password');
            const roleSelect = document.getElementById('modal_role');
            const headerTitle = document.getElementById('modal-header-title');
            const submitBtn = document.getElementById('modal_submit_btn');
            const passwordWarning = document.getElementById('modal_password_warning');

            if (!modal) return;

            actionInput.value = action;

            if (action === 'create_user') {
                headerTitle.textContent = "Enroll New Member Account";
                submitBtn.textContent = "Register Account";
                emailInput.value = '';
                passwordInput.value = '';
                passwordInput.required = true;
                passwordWarning.classList.add('hidden');
                targetIdInput.value = '';
                roleSelect.value = 'member';
                roleSelect.disabled = false;
            } else if (action === 'update_user' && user) {
                headerTitle.textContent = "Edit Member Registry Settings";
                submitBtn.textContent = "Save Changes";
                emailInput.value = user.email;
                passwordInput.value = '';
                passwordInput.required = false;
                passwordWarning.classList.remove('hidden');
                targetIdInput.value = user.id;
                roleSelect.value = user.role;
                
                // Block demoting self inside modal too
                if (parseInt(user.id) === <?php echo $user_id; ?>) {
                    roleSelect.disabled = true;
                } else {
                    roleSelect.disabled = false;
                }
            }

            modal.classList.remove('hidden');
        }

        function closeAdminUserModal() {
            const modal = document.getElementById('admin-user-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
    </script>
<?php endif; ?>

<?php
require_once 'footer.php';
?>

