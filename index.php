<?php
require_once 'header.php';

$action_error = '';
$success_message = '';

// Handle DB actions and mutations on index
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // ADMIN ONLY PORTALS
    if ($user_id && $user_role === 'admin') {
        $action = $_POST['action_type'];
        
        // 1. Quick bulletin publish
        if ($action === 'quick_bulletin') {
            $title = mysqli_real_escape_string($conn, trim($_POST['title']));
            $content = mysqli_real_escape_string($conn, trim($_POST['content']));
            $date_now = date('Y-m-d H:i:s');
            
            if (empty($title) || empty($content)) {
                $action_error = "Notice title and descriptive content are mandatory fields.";
            } else {
                $ins = mysqli_query($conn, "INSERT INTO announcements (title, content, created_by, created_at) VALUES ('$title', '$content', $user_id, '$date_now')");
                if ($ins) {
                    $success_message = "Governance bulletin successfully published to reader channels!";
                } else {
                    $action_error = "In-situ database notice registry failed: " . mysqli_error($conn);
                }
            }
        }
        
        // 2. Rapid book ingestion
        elseif ($action === 'quick_book') {
            $title = mysqli_real_escape_string($conn, trim($_POST['title']));
            $author = mysqli_real_escape_string($conn, trim($_POST['author']));
            $genre = mysqli_real_escape_string($conn, trim($_POST['genre']));
            $published_year = (int)$_POST['published_year'];
            $cover_url = mysqli_real_escape_string($conn, trim($_POST['cover_url']));
            $description = mysqli_real_escape_string($conn, trim($_POST['description']));
            
            if (empty($title) || empty($author)) {
                $action_error = "Title and Author Author are both required for instant ingestion.";
            } else {
                if (empty($cover_url)) {
                    $cover_url = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&q=80&w=400';
                }
                if (empty($genre)) {
                    $genre = 'General Literature';
                }
                if ($published_year <= 0) {
                    $published_year = (int)date('Y');
                }
                
                $ins = mysqli_query($conn, "INSERT INTO books (title, author, genre, published_year, cover_url, description, created_by) VALUES ('$title', '$author', '$genre', $published_year, '$cover_url', '$description', $user_id)");
                if ($ins) {
                    $success_message = "Rapid volume ingestion accomplished: Master registry updated with '$title'!";
                } else {
                    $action_error = "Listing ingestion failed: " . mysqli_error($conn);
                }
            }
        }
        
        // 3. Direct bulletin removal
        elseif ($action === 'delete_bulletin_index') {
            $ann_id = (int)$_POST['ann_id'];
            $del = mysqli_query($conn, "DELETE FROM announcements WHERE id = $ann_id");
            if ($del) {
                $success_message = "Notice cleared from live community monitors.";
            } else {
                $action_error = "Procedure rejected: " . mysqli_error($conn);
            }
        }
    }
    
    // MEMBER ONLY UPDATES
    if ($user_id && $user_role === 'member' && $_POST['action_type'] === 'update_member_notes') {
        $book_id = (int)$_POST['book_id'];
        $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
        
        $upd = mysqli_query($conn, "UPDATE reading_list SET notes = '$notes', updated_at = '" . date('Y-m-d') . "' WHERE user_id = $user_id AND book_id = $book_id");
        if ($upd) {
            $success_message = "Currently reading margin commentary successfully saved!";
        } else {
            $action_error = "Failed to update page journal notes: " . mysqli_error($conn);
        }
    }
    
    // Quick ADD to Shelf from Spotlight Suggestion
    if ($user_id && $_POST['action_type'] === 'index_quick_add') {
        $book_id = (int)$_POST['book_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $date_now = date('Y-m-d');

        $chk = mysqli_query($conn, "SELECT id FROM reading_list WHERE user_id = $user_id AND book_id = $book_id");
        if ($chk && mysqli_num_rows($chk) === 0) {
            $ins = mysqli_query($conn, "INSERT INTO reading_list (user_id, book_id, status, updated_at) VALUES ($user_id, $book_id, '$status', '$date_now')");
            if ($ins) {
                $success_message = "Added spotlight suggestion to your custom shelves!";
            } else {
                $action_error = "Db insertion error: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch Global Numbers
$total_books_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM books");
$total_books = mysqli_fetch_assoc($total_books_query)['total'];

$active_logs_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reading_list");
$active_logs = mysqli_fetch_assoc($active_logs_query)['total'];

$members_count_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total_members = mysqli_fetch_assoc($members_count_query)['total'];

// Fetch latest bulletins
$announcements_query = mysqli_query($conn, "
    SELECT a.*, u.email as author_name 
    FROM announcements a 
    JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 3
");

// Fetch 3 recommended books for recommendation slots
$featured_query = mysqli_query($conn, "
    SELECT b.*, COALESCE(ROUND(AVG(rl.rating), 1), 4.5) AS computed_rating 
    FROM books b
    LEFT JOIN reading_list rl ON b.id = rl.book_id
    GROUP BY b.id
    ORDER BY b.id DESC
    LIMIT 3
");
?>

<div class="space-y-8" id="portal-dashboard-root">
    
    <!-- Status Notices Segment -->
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
         1. ADMINISTRATIVE COMMAND CENTER
         ============================================== -->
    <?php if ($user_id && $user_role === 'admin'): ?>
        
        <!-- Welcome Administrative Hero Header -->
        <div class="bg-indigo-950 text-white rounded-[2rem] p-8 relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-md border border-indigo-900/50">
            <div class="relative z-10 space-y-3 max-w-2xl">
                <span class="inline-flex items-center space-x-1.5 rounded-full bg-rose-500/10 text-rose-350 border border-rose-500/25 px-3 py-1 text-xs font-extrabold uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                    Superintend Session Verified
                </span>
                <h2 class="text-3xl font-serif font-bold italic tracking-tight text-white leading-tight">Hello, Governor <?php echo htmlspecialchars(explode('@', $user_email)[0]); ?>!</h2>
                <p class="text-indigo-200 text-xs leading-relaxed">This is your overall system control workspace. Monitor community activities, direct catalog contents, enroll active member profiles, and broadcast notice flashes.</p>
            </div>
            
            <div class="relative z-10 flex gap-2 shrink-0">
                <a href="members.php" class="bg-indigo-700/50 hover:bg-indigo-700 text-white border border-indigo-600/50 rounded-2xl px-5 py-3 text-xs font-bold uppercase tracking-wider transition-colors inline-block text-center">Manage Members</a>
                <a href="admin.php" class="bg-rose-600 hover:bg-rose-700 text-white rounded-2xl px-5 py-3 text-xs font-bold uppercase tracking-wider transition-colors inline-block text-center">Governance Panel</a>
            </div>

            <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-750 rounded-full blur-3xl opacity-40"></div>
        </div>

        <!-- Admin Metrics Bento Layout -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white border border-slate-200 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Registered Members</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_members; ?> Profiles</span>
                <a href="members.php" class="text-[10px] font-bold text-indigo-600 hover:underline inline-block mt-2">Open Directory ➔</a>
            </div>
            <div class="bg-white border border-slate-200 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Catalog Volume Capacity</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_books; ?> Books</span>
                <a href="catalog.php" class="text-[10px] font-bold text-indigo-600 hover:underline inline-block mt-2">Manage Volumes ➔</a>
            </div>
            <div class="bg-white border border-slate-200 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Readers Active Shelves</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $active_logs; ?> Tracking Logs</span>
                <span class="text-[10px] font-semibold text-slate-400 block mt-2">Active database nodes link</span>
            </div>
            <div class="bg-emerald-950 text-white border border-emerald-900/20 rounded-[1.8rem] p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="block text-[9px] font-extrabold text-emerald-400 uppercase tracking-widest">Relational Engine</span>
                    <span class="block text-lg font-serif font-bold mt-1">MySQLi Operational</span>
                </div>
                <div class="flex items-center space-x-1.5 md:mt-3 text-[10px] text-emerald-350 font-medium.">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Shared hosting binding: Active</span>
                </div>
            </div>
        </div>

        <!-- Admin Workflows Bento Rails -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Quick Action Cards (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Rapid Book Ingestion Card -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-7 shadow-xs space-y-4">
                    <div>
                        <h3 class="text-lg font-serif font-bold text-slate-900 leading-tight">Rapid Book Ingestion</h3>
                        <p class="text-[11px] text-slate-450 mt-0.5 font-medium">Quickly enroll a volume into catalog pools without leaving your homepage console.</p>
                    </div>

                    <form action="index.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <input type="hidden" name="action_type" value="quick_book" />
                        
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_title">Book Title*</label>
                            <input 
                                id="quick_b_title"
                                type="text" 
                                name="title" 
                                required 
                                placeholder="The Great Gatsby" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_author">Author Name*</label>
                            <input 
                                id="quick_b_author"
                                type="text" 
                                name="author" 
                                required 
                                placeholder="F. Scott Fitzgerald" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_genre">Category Classification</label>
                            <input 
                                id="quick_b_genre"
                                type="text" 
                                name="genre" 
                                placeholder="Classic Fiction" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_year">Publication Year</label>
                            <input 
                                id="quick_b_year"
                                type="number" 
                                name="published_year" 
                                value="1925" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_cover">Cover Image URL</label>
                            <input 
                                id="quick_b_cover"
                                type="url" 
                                name="cover_url" 
                                placeholder="https://images.unsplash.com/your-custom-photographic-cover" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="quick_b_desc">Brief Synopsis / Description</label>
                            <textarea 
                                id="quick_b_desc"
                                name="description" 
                                rows="2" 
                                placeholder="Classic narrative detailing an elegant story inside Long Island..." 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            ></textarea>
                        </div>

                        <div class="sm:col-span-2 pt-2 text-right">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-805 text-white rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-sm transition-colors cursor-pointer">
                                Ingest Volume Registry
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live bulletins board controller -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-7 shadow-xs space-y-4">
                    <div>
                        <h3 class="text-lg font-serif font-bold text-slate-900 leading-tight">Live Broadcaster Monitor</h3>
                        <p class="text-[11px] text-slate-455 mt-0.5 font-medium">Currently visible notices flashes. Remove any bulletin directly with the actions buttons.</p>
                    </div>

                    <div class="divide-y divide-slate-100 max-h-[300px] overflow-y-auto pr-1">
                        <?php if ($announcements_query && mysqli_num_rows($announcements_query) > 0): ?>
                            <?php 
                            mysqli_data_seek($announcements_query, 0); // reset pointer
                            while ($bell = mysqli_fetch_assoc($announcements_query)): 
                            ?>
                                <div class="py-3.5 flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-bold uppercase text-slate-400 font-mono"><?php echo htmlspecialchars($bell['created_at']); ?></span>
                                            <span class="text-[9px] font-extrabold uppercase bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded">Author ID #<?php echo htmlspecialchars($bell['created_by']); ?></span>
                                        </div>
                                        <h4 class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($bell['title']); ?></h4>
                                        <p class="text-xs text-slate-500 leading-normal line-clamp-2"><?php echo htmlspecialchars($bell['content']); ?></p>
                                    </div>
                                    
                                    <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this announcement?');">
                                        <input type="hidden" name="action_type" value="delete_bulletin_index" />
                                        <input type="hidden" name="ann_id" value="<?php echo $bell['id']; ?>" />
                                        <button type="submit" class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-650 border border-slate-200 transition-colors cursor-pointer" title="Direct Delete Bulletin">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic text-center py-6">No broadcasts posted currently.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right: Admin Bulletins Publisher & Telemetry (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Fast notices dispatch desk -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-4">
                    <h3 class="text-md font-serif font-bold text-slate-900 border-b border-slate-105 pb-2.5 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        Post Fast Bulletin
                    </h3>

                    <form action="index.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action_type" value="quick_bulletin" />
                        
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="bell_title">Notice Headline</label>
                            <input 
                                id="bell_title"
                                type="text" 
                                name="title" 
                                required
                                placeholder="Summer Reading Challenge"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-450 mb-1" for="bell_body">Descriptions Details</label>
                            <textarea 
                                id="bell_body"
                                name="content" 
                                rows="3" 
                                required
                                placeholder="Attention readers! Log physical volumes to participate..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none transition-all"
                            ></textarea>
                        </div>

                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white rounded-xl py-2.5 text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                            Broadcast Notice
                        </button>
                    </form>
                </div>

                <!-- Micro Network logs card -->
                <div class="bg-slate-900 rounded-[2rem] p-6 text-white shadow-md space-y-4 font-mono text-[11px] leading-relaxed">
                    <span class="block text-[9px] font-sans font-extrabold tracking-widest uppercase text-indigo-400">Governance Telemetry Trace</span>
                    <div class="space-y-2 text-slate-400">
                        <p class="flex justify-between border-b border-white/5 pb-1"><span class="text-slate-500">System Mode</span> <span class="text-rose-400 font-bold">Production (ESM cjs)</span></p>
                        <p class="flex justify-between border-b border-white/5 pb-1"><span class="text-slate-500">HMR Status</span> <span class="text-emerald-400">HMR Disabled</span></p>
                        <p class="flex justify-between border-b border-white/5 pb-1"><span class="text-slate-500">Reverse Ingress</span> <span class="text-slate-300">nginx:3000 mapping</span></p>
                        <p class="flex justify-between"><span class="text-slate-500">Session Keys</span> <span class="text-slate-300">Verified buffer</span></p>
                    </div>
                </div>

            </div>

        </div>

    <!-- ==============================================
         2. PERSONAL READING ROOM (MEMBER VIEW)
         ============================================== -->
    <?php elseif ($user_id && $user_role === 'member'): ?>
        <?php
        // Count member reading metrics
        $completed_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reading_list WHERE user_id = $user_id AND status = 'Completed'");
        $user_completed = mysqli_fetch_assoc($completed_query)['total'];

        $reading_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reading_list WHERE user_id = $user_id AND status = 'Reading'");
        $user_reading = mysqli_fetch_assoc($reading_query)['total'];

        $want_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reading_list WHERE user_id = $user_id AND status = 'Want to Read'");
        $user_want = mysqli_fetch_assoc($want_query)['total'];

        $journal_ratings = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reading_list WHERE user_id = $user_id AND rating IS NOT NULL");
        $user_ratings_written = mysqli_fetch_assoc($journal_ratings)['total'];

        // Get active book logs details
        $active_book_details = null;
        $active_book_query = mysqli_query($conn, "
            SELECT b.*, rl.rating, rl.notes 
            FROM reading_list rl
            JOIN books b ON rl.book_id = b.id
            WHERE rl.user_id = $user_id AND rl.status = 'Reading'
            LIMIT 1
        ");
        if ($active_book_query && mysqli_num_rows($active_book_query) > 0) {
            $active_book_details = mysqli_fetch_assoc($active_book_query);
        }
        ?>

        <!-- Member Cozy Greeting Hero -->
        <div class="bg-[#F3EFE9] border border-[#DECEB8] text-slate-800 rounded-[2rem] p-8 relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xs">
            <div class="relative z-10 space-y-3.5 max-w-xl">
                <span class="inline-flex rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-[9px] font-extrabold uppercase tracking-widest px-3 py-1">My Reader Room Lockbox</span>
                <h2 class="text-3.5xl font-serif font-bold italic tracking-tight text-indigo-950 leading-tight">Welcome, reader <?php echo htmlspecialchars(explode('@', $user_email)[0]); ?>!</h2>
                <p class="text-slate-600 text-xs leading-relaxed">This is your highly personal screen space. View catalog metrics, read bulletins from book club leaders, and check notes on books currently open in your shelves.</p>
            </div>
            
            <div class="relative z-10 shrink-0">
                <a href="my-list.php" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl px-5 py-3 text-xs font-bold uppercase tracking-wider shadow-sm transition-colors inline-block text-center mr-2">Open My Shelf</a>
                <a href="#active-notice-board" class="bg-slate-100 hover:bg-slate-205 border border-slate-250 text-slate-700 rounded-2xl px-5 py-3 text-xs font-bold uppercase tracking-wider transition-colors inline-block text-center select-none">Bulletins</a>
            </div>

            <div class="absolute -right-12 -top-12 w-40 h-40 bg-indigo-200 rounded-full blur-2.5xl opacity-40"></div>
        </div>

        <!-- Personal Reading Progress Bento Boxes -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="bg-white border border-slate-210 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Completed Books</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $user_completed; ?> Volumes</span>
            </div>
            <div class="bg-white border border-slate-210 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">In Progress</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $user_reading; ?> Books</span>
            </div>
            <div class="bg-white border border-slate-210 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Pinned To-Read</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $user_want; ?> Saved</span>
            </div>
            <div class="bg-white border border-slate-210 rounded-[1.8rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Ratings Casted</span>
                <span class="block text-3xl font-serif font-bold text-slate-900 mt-1"><?php echo $user_ratings_written; ?> Written</span>
            </div>
        </div>

        <!-- Member Interactive Section Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Inline Active Reading Notes (8 cols) -->
            <div class="lg:col-span-8 bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-6">
                
                <div>
                    <h3 class="text-xl font-serif font-bold text-slate-900 tracking-tight leading-tight">Currently Reading Shelf Log</h3>
                    <p class="text-[11px] text-slate-450 mt-0.5">Record or update active quotes/notes in real-time right from your homepage.</p>
                </div>

                <?php if ($active_book_details): ?>
                    <div class="flex flex-col sm:flex-row gap-6 bg-slate-50/50 p-5 rounded-2xl border border-slate-105">
                        
                        <!-- Book Cover and stars -->
                        <div class="w-28 shrink-0 space-y-3 mx-auto sm:mx-0">
                            <div class="h-38 rounded-xl border border-slate-200 relative overflow-hidden bg-slate-100 shadow-sm">
                                <img 
                                    src="<?php echo htmlspecialchars($active_book_details['cover_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($active_book_details['title']); ?>"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="text-center font-bold text-xs text-amber-500 font-serif">
                                <?php if ($active_book_details['rating']): ?>
                                    <span><?php echo str_repeat('★', $active_book_details['rating']); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400 font-sans text-[10px] font-medium leading-none">Unrated (Stars)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notes Form controller -->
                        <div class="grow space-y-3.5">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-indigo-600 bg-indigo-50 border border-indigo-150 rounded px-2 py-0.5">Currently Reading</span>
                                <h4 class="text-lg font-bold text-slate-900 font-serif mt-1 leading-normal"><?php echo htmlspecialchars($active_book_details['title']); ?></h4>
                                <p class="text-xs text-slate-500 font-medium leading-none">by <?php echo htmlspecialchars($active_book_details['author']); ?></p>
                            </div>

                            <form action="index.php" method="POST" class="space-y-3">
                                <input type="hidden" name="action_type" value="update_member_notes" />
                                <input type="hidden" name="book_id" value="<?php echo $active_book_details['id']; ?>" />
                                
                                <div>
                                    <label class="block text-[9px] font-extrabold uppercase tracking-widest text-slate-400 mb-1" for="member_notes_in">My Active Reading Commentary / Quote Notations</label>
                                    <textarea 
                                        id="member_notes_in"
                                        name="notes" 
                                        rows="3" 
                                        placeholder="Add thoughts, key chapters, or favorite quotes..." 
                                        class="w-full rounded-xl border border-slate-250 bg-white px-4 py-2.5 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none transition-all leading-normal"
                                    ><?php echo htmlspecialchars($active_book_details['notes']); ?></textarea>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="bg-indigo-650 hover:bg-indigo-600 font-bold border border-indigo-500/10 text-white rounded-xl px-5 py-2 text-xs uppercase tracking-wider transition-colors cursor-pointer">
                                        Save Margin Notes
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                <?php else: ?>
                    <div class="border border-dashed border-slate-200 rounded-[1.5rem] p-8 text-center text-slate-400 text-xs py-12 space-y-4">
                        <p class="italic">You are not tracking any books under 'Reading' status right now.</p>
                        <a href="catalog.php" class="bg-indigo-600 text-white font-bold rounded-full px-5 py-2.5 text-[11px] uppercase tracking-wider inline-block">Browse Books Catalog</a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right: Member Bulletin Timeline (4 cols) -->
            <div class="lg:col-span-4 bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs space-y-4" id="active-notice-board">
                <h3 class="text-md font-serif font-bold text-slate-900 border-b border-slate-105 pb-3">Governance Bulletin Broadcasts</h3>
                
                <div class="space-y-4 overflow-y-auto max-h-[385px] pr-1">
                    <?php if ($announcements_query && mysqli_num_rows($announcements_query) > 0): ?>
                        <?php 
                        mysqli_data_seek($announcements_query, 0); // reset pointer
                        while ($anno = mysqli_fetch_assoc($announcements_query)): 
                        ?>
                            <div class="border-l-3 border-indigo-500 pl-4 py-1 space-y-1">
                                <span class="block text-[9px] font-bold uppercase text-slate-400 font-mono"><?php echo htmlspecialchars($anno['created_at']); ?></span>
                                <h4 class="text-xs font-bold text-slate-800 leading-snug"><?php echo htmlspecialchars($anno['title']); ?></h4>
                                <p class="text-[11px] text-slate-500 leading-normal"><?php echo htmlspecialchars($anno['content']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-400 italic text-center py-6">No general bulletins dispatched by administrators yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <!-- ==============================================
         3. STANDARD VISITOR / GUEST LANDING PAGE
         ============================================== -->
    <?php else: ?>
        
        <!-- Interactive Welcome Visitor Card -->
        <div class="bg-indigo-950 text-white rounded-[2rem] p-8 relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-md min-h-[300px]">
            <div class="relative z-10 space-y-3.5 max-w-xl">
                <span class="inline-flex rounded-full bg-white/10 text-indigo-305 text-[9px] font-extrabold uppercase tracking-widest px-3 py-1">Community Portal</span>
                <h2 class="text-3.5xl font-serif font-bold italic tracking-tight text-white leading-tight">Welcome, Book Enthusiast!</h2>
                <p class="text-indigo-200 text-xs leading-relaxed">BookClub Portal is a premium custom-built cataloging engine. Track your reading shelves progression, write reviews, and contribute to our physical and fantasy collection volumes.</p>
            </div>
            
            <div class="relative z-10 shrink-0">
                <a href="auth.php" class="bg-white text-indigo-950 hover:bg-slate-50 rounded-2xl px-6 py-3.5 text-xs font-bold uppercase tracking-wider shadow-sm transition-all inline-block text-center">Join Active Session</a>
            </div>

            <div class="absolute -right-12 -top-12 w-44 h-44 bg-indigo-750 rounded-full blur-3xl opacity-40"></div>
        </div>

        <!-- Telemetry boxes -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="bg-white border border-slate-200 rounded-[1.5rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Catalog Items</span>
                <span class="block text-2.5xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_books; ?> Books</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-[1.5rem] p-6 shadow-xs">
                <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Active Members</span>
                <span class="block text-2.5xl font-serif font-bold text-slate-900 mt-1"><?php echo $total_members; ?> Profiles</span>
            </div>
            <div class="bg-[#F6EEE5] border border-[#DECEB8] rounded-[1.5rem] p-6 shadow-xs col-span-2 sm:col-span-1">
                <span class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Tracking Logs</span>
                <span class="block text-2.5xl font-serif font-bold text-indigo-950 mt-1"><?php echo $active_logs; ?> Completed</span>
            </div>
        </div>

    <?php endif; ?>

    <!-- ==============================================
         LOWER COMMON WORKSPACE: MODERATORS SUGGESTION STARLIGHTS
         ============================================== -->
    <?php
    $spotlight_query = mysqli_query($conn, "
        SELECT b.*, COALESCE(ROUND(AVG(rl.rating), 1), 4.8) AS computed_rating, COUNT(rl.rating) as count_reviews
        FROM books b
        LEFT JOIN reading_list rl ON b.id = rl.book_id
        GROUP BY b.id
        ORDER BY computed_rating DESC
        LIMIT 1
    ");
    $spotlight = mysqli_fetch_assoc($spotlight_query);
    ?>

    <?php if ($spotlight): ?>
        <section class="space-y-6 pt-6 border-t border-slate-200/50">
            <div>
                <h3 class="text-2xl font-serif font-bold text-slate-900 tracking-tight leading-tight">Featured Library Spotlight selection</h3>
                <p class="text-xs text-slate-500 font-medium">Top shelf catalog recommendation based on community stars feedback.</p>
            </div>

            <!-- Spotlight Card layout -->
            <div class="bg-white rounded-[2rem] border border-slate-200 p-6 md:p-8 flex flex-col md:flex-row gap-8 shadow-xs relative overflow-hidden items-stretch">
                
                <!-- Book Meta block (Left) -->
                <div class="md:w-7/12 flex flex-col justify-between space-y-6">
                    <div class="space-y-3.5">
                        <span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[9px] font-bold uppercase tracking-widest inline-block">Recommendation Selection</span>
                        <h4 class="text-3xl font-serif font-bold text-slate-900 leading-tight"><?php echo htmlspecialchars($spotlight['title']); ?></h4>
                        <p class="text-slate-500 text-xs font-semibold">Author: <strong class="text-indigo-950 font-bold"><?php echo htmlspecialchars($spotlight['author']); ?></strong> | Genre: <?php echo htmlspecialchars($spotlight['genre']); ?></p>
                        
                        <div class="flex items-center gap-2 text-amber-500 font-serif">
                            <span class="text-sm font-semibold text-slate-700"><?php echo floatval($spotlight['computed_rating']); ?> / 5 Stars</span>
                            <span class="text-[#F59E08]"><?php echo str_repeat('★', round($spotlight['computed_rating'])); ?></span>
                        </div>

                        <p class="text-slate-600 text-xs leading-relaxed line-clamp-4"><?php echo htmlspecialchars($spotlight['description']); ?></p>
                    </div>

                    <div>
                        <?php if ($user_id): ?>
                            <?php
                            $target_book_id = $spotlight['id'];
                            $check_tracked = mysqli_query($conn, "SELECT status FROM reading_list WHERE user_id = $user_id AND book_id = $target_book_id LIMIT 1");
                            if ($check_tracked && mysqli_num_rows($check_tracked) > 0) {
                                $track = mysqli_fetch_assoc($check_tracked);
                                echo '
                                <div class="inline-flex items-center space-x-1 bg-indigo-50 border border-indigo-150 text-indigo-700 px-4 py-2 rounded-xl text-xs font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>Tracked in My List as: ' . htmlspecialchars($track['status']) . '</span>
                                </div>';
                            } else {
                            ?>
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="action_type" value="index_quick_add" />
                                    <input type="hidden" name="book_id" value="<?php echo $target_book_id; ?>" />
                                    <input type="hidden" name="status" value="Reading" />
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-2.5 text-xs font-bold uppercase tracking-wider transition-colors shadow-sm cursor-pointer">Add to My Shelf</button>
                                </form>
                            <?php } ?>
                        <?php else: ?>
                            <a href="auth.php" class="bg-slate-900 text-white rounded-xl px-5 py-2.5 text-xs font-bold uppercase tracking-wider transition-colors inline-block text-center mr-2">Login to track book</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Graphic Cover block (Right) -->
                <div class="md:w-5/12 bg-slate-50 border border-slate-100 rounded-2xl overflow-hidden p-6 flex items-center justify-center min-h-[220px]">
                    <div class="w-34 h-48 bg-indigo-150 rounded-xl shadow-md overflow-hidden relative transition-all hover:scale-102 flex flex-col justify-end p-4">
                        <img 
                            src="<?php echo htmlspecialchars($spotlight['cover_url']); ?>" 
                            alt="<?php echo htmlspecialchars($spotlight['title']); ?>"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
                        <p class="text-white font-bold text-xs relative z-10 truncate"><?php echo htmlspecialchars($spotlight['title']); ?></p>
                        <p class="text-white/80 text-[10px] italic relative z-10 truncate leading-none mt-0.5"><?php echo htmlspecialchars($spotlight['author']); ?></p>
                    </div>
                </div>

            </div>
        </section>
    <?php endif; ?>

</div>

<?php
require_once 'footer.php';
?>
p';
?>
