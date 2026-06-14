<?php
require_once 'db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'member';

// Force admin routing authority
if (!$user_id || $user_role !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'header.php';

$action_error = '';
$success_message = '';

// Handle updating role
if (isset($_POST['action_type']) && $_POST['action_type'] === 'update_member_role') {
    $target_id = (int)$_POST['target_id'];
    $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'member';
    
    if ($target_id === $user_id) {
        $action_error = "Safety Protocol: You cannot demote yourself from the admin team!";
    } else {
        $upd = mysqli_query($conn, "UPDATE users SET role = '$new_role' WHERE id = $target_id");
        if ($upd) {
            $success_message = "Member role changed successfully!";
        } else {
            $action_error = "Database operation failed: " . mysqli_error($conn);
        }
    }
}

// Handle posting announcement
if (isset($_POST['action_type']) && $_POST['action_type'] === 'create_announcement') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $created_date = date('Y-m-d');
    
    if (empty($title) || empty($content)) {
        $action_error = "Announcement title and detailed content cannot be empty.";
    } else {
        $ins = mysqli_query($conn, "INSERT INTO announcements (title, content, created_at, created_by) VALUES ('$title', '$content', '$created_date', $user_id)");
        if ($ins) {
            $success_message = "Announcement published on portal streams successfully!";
        } else {
            $action_error = "Announcements engine failed: " . mysqli_error($conn);
        }
    }
}

// Handle removing announcement
if (isset($_POST['action_type']) && $_POST['action_type'] === 'delete_announcement') {
    $announcement_id = (int)$_POST['announcement_id'];
    $del = mysqli_query($conn, "DELETE FROM announcements WHERE id = $announcement_id");
    if ($del) {
        $success_message = "Announcement removed successfully!";
    } else {
        $action_error = "Delete query execution failed: " . mysqli_error($conn);
    }
}

// Calculate live database statistics
$books_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM books");
$total_books = mysqli_fetch_assoc($books_count_q)['total'];

$members_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_members = mysqli_fetch_assoc($members_count_q)['total'];

$announcements_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM announcements");
$total_announcements = mysqli_fetch_assoc($announcements_count_q)['total'];

$reading_list_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM reading_list");
$total_reading_logs = mysqli_fetch_assoc($reading_list_count_q)['total'];

// Fetch represented users list for the directory and role tracking
$members_q = mysqli_query($conn, "SELECT id, email, role, joined_at FROM users ORDER BY joined_at DESC");

// Fetch real-time announcements list
$announcements_q = mysqli_query($conn, "
    SELECT a.*, u.email as author_email 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");
?>

<div class="space-y-8" id="admin_portal_container">

    <!-- Header Bento Segment -->
    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-xs" id="admin_header_bento">
        <div>
            <h1 class="text-3xl font-serif font-bold tracking-tight text-slate-900">Governance Console</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Admin overrides for member roles, global bulletins, and portal telemetry metrics</p>
        </div>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 border border-rose-200 text-rose-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
        </div>
    </div>

    <!-- Alert systems for feedback representation -->
    <?php if (!empty($action_error)): ?>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700 flex items-center space-x-3" id="admin_action_error_alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span><?php echo htmlspecialchars($action_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-700 flex items-center space-x-3" id="admin_success_alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Bento Grid metrics for portal surveillance -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" id="admin_telemetry_grid">
        <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs flex items-center space-x-4 hover:border-rose-100 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <div>
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Active Accounts</span>
                <span class="block text-2xl font-bold text-slate-800 tracking-tight mt-0.5"><?php echo $total_members; ?> Members</span>
            </div>
        </div>

        <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs flex items-center space-x-4 hover:border-rose-100 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            </div>
            <div>
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Catalog Volume</span>
                <span class="block text-2xl font-bold text-slate-800 tracking-tight mt-0.5"><?php echo $total_books; ?> Books</span>
            </div>
        </div>

        <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs flex items-center space-x-4 hover:border-rose-100 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </div>
            <div>
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Global Bulletins</span>
                <span class="block text-2xl font-bold text-slate-800 tracking-tight mt-0.5"><?php echo $total_announcements; ?> Posts</span>
            </div>
        </div>

        <div class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-xs flex items-center space-x-4 hover:border-rose-100 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            </div>
            <div>
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Personal Reads</span>
                <span class="block text-2xl font-bold text-slate-800 tracking-tight mt-0.5"><?php echo $total_reading_logs; ?> Tracking Logs</span>
            </div>
        </div>
    </div>

    <!-- Core Governance Rows: Roles grid on left, Announcements grid on right -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12" id="admin_functional_sections">
        
        <!-- Member Directory and Role Editor (Bento Segment 1 - 7 cols) -->
        <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs lg:col-span-7 flex flex-col justify-between" id="bento_role_management">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div>
                        <h2 class="text-xl font-serif font-bold text-slate-900">Member Role Registry</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Manage permissions, set administrative accesses and check joined streams</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead>
                            <tr class="text-left font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 text-[10px]">
                                <th class="pb-3 pr-2">Member Email</th>
                                <th class="pb-3 px-2">Joined Date</th>
                                <th class="pb-3 px-2 text-right">Access Level</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($members_q && mysqli_num_rows($members_q) > 0): ?>
                                <?php while ($member = mysqli_fetch_assoc($members_q)): ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3.5 pr-2">
                                            <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($member['email']); ?></div>
                                            <div class="text-[10px] text-slate-400 font-medium font-serif mt-0.5">group id: <?php echo $member['id'] == 1 ? 'super_admin' : 'member_v1'; ?></div>
                                        </td>
                                        <td class="py-3.5 px-2 text-slate-500 font-mono text-[11px]">
                                            <?php echo date('M d, Y', strtotime($member['joined_at'])); ?>
                                        </td>
                                        <td class="py-3.5 px-2 text-right">
                                            <form method="POST" action="admin.php" class="inline-flex items-center space-x-2">
                                                <input type="hidden" name="action_type" value="update_member_role" />
                                                <input type="hidden" name="target_id" value="<?php echo $member['id']; ?>" />
                                                
                                                <select name="new_role" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white py-1 px-2.5 font-semibold text-[11px] text-slate-705 shadow-xs focus:border-rose-100 focus:outline-none transition-all <?php echo $member['role'] === 'admin' ? 'text-rose-600 bg-rose-50/50 border-rose-150' : 'text-slate-600'; ?>" <?php echo $member['id'] === $user_id ? 'disabled' : ''; ?>>
                                                    <option value="member" <?php echo $member['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                                    <option value="admin" <?php echo $member['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                </select>
                                            </form>
                                            <?php if ($member['id'] === $user_id): ?>
                                                <span class="block text-[9px] text-[#A8A29E] font-extrabold uppercase mt-1 mr-2">You</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-430 font-medium">No members found in registry database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-6 border-t border-slate-100 pt-4 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                <span>Database connections active.</span>
                <span class="font-mono text-[10px] uppercase">port: 3000 / persistent mysql</span>
            </div>
        </div>

        <!-- Global Announcement Composer (Bento Segment 2 - 5 cols) -->
        <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs lg:col-span-12 flex flex-col justify-between" id="bento_announs_editor">
            <div>
                <h2 class="text-xl font-serif font-bold text-slate-900 border-b border-slate-100 pb-4 mb-5">Club Announcements Broadcaster</h2>
                
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    
                    <!-- Announcement Form composer -->
                    <div class="p-6 bg-slate-50/60 rounded-2xl border border-slate-100">
                        <h3 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-4">Compose New Bulletin</h3>
                        <form method="POST" action="admin.php" class="space-y-4">
                            <input type="hidden" name="action_type" value="create_announcement" />
                            
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1.5" for="ann_title">Post Title</label>
                                <input 
                                    id="ann_title"
                                    type="text" 
                                    name="title" 
                                    placeholder="e.g., July Reading List update! 📖" 
                                    required
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:border-rose-350 focus:outline-none focus:ring-1 focus:ring-rose-200 transition-all font-serif"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1.5" for="ann_content">Bulletin Content</label>
                                <textarea 
                                    id="ann_content"
                                    name="content" 
                                    rows="5"
                                    placeholder="Write details about physical locations, timing schedules, reading assignments, or review schedules..." 
                                    required
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:border-rose-350 focus:outline-none focus:ring-1 focus:ring-rose-200 transition-all"
                                ></textarea>
                            </div>

                            <button type="submit" class="w-full cursor-pointer flex items-center justify-center space-x-2 rounded--xl font-semibold capitalize tracking-wide transition-all bg-slate-900 hover:bg-slate-800 text-white rounded-xl py-2.5 text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                <span>Broadcast Notice</span>
                            </button>
                        </form>
                    </div>

                    <!-- Live Announcements Monitor Streams -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 mb-4">Board streams (Delete override enabled)</h3>
                        
                        <div class="space-y-3.5 max-h-[350px] overflow-y-auto pr-2">
                            <?php if ($announcements_q && mysqli_num_rows($announcements_q) > 0): ?>
                                <?php while ($ann = mysqli_fetch_assoc($announcements_q)): ?>
                                    <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs flex items-start justify-between gap-4">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center space-x-2">
                                                <span class="rounded-md bg-stone-100 px-2 py-0.5 font-mono text-[9px] font-bold text-stone-500">Notice</span>
                                                <span class="text-[10px] text-slate-400 font-semibold"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                                            </div>
                                            <h4 class="font-serif font-bold text-sm text-slate-900"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                            <p class="text-xs text-slate-500 line-clamp-2"><?php echo htmlspecialchars($ann['content']); ?></p>
                                            <div class="text-[9px] font-semibold text-slate-400">By Admin: <?php echo htmlspecialchars(explode('@', $ann['author_email'])[0]); ?></div>
                                        </div>

                                        <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this announcement?');" class="shrink-0">
                                            <input type="hidden" name="action_type" value="delete_announcement" />
                                            <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>" />
                                            <button type="submit" class="p-1.5 rounded-lg border border-slate-100 text-slate-400 hover:text-red-650 hover:border-red-100 hover:bg-red-50/50 transition-all cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-430 text-xs font-semibold bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                    No announcements in general stream. Please broadcast one.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once 'footer.php'; ?>
