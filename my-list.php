<?php
require_once 'header.php';

// Force authentication for this page
if (!$user_id) {
    header('Location: auth.php');
    exit();
}

$action_error = '';
$success_message = '';

// Process Status or Notes Updates
if (isset($_POST['action_type']) && $_POST['action_type'] === 'update_tracking') {
    $item_id = (int)$_POST['item_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $rating = isset($_POST['rating']) && $_POST['rating'] !== 'NULL' ? (int)$_POST['rating'] : 'NULL';
    $date_now = date('Y-m-d');

    // Make sure user owns this list record
    $chk = mysqli_query($conn, "SELECT id FROM reading_list WHERE id = $item_id AND user_id = $user_id LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $update_sql = "
            UPDATE reading_list 
            SET status = '$status', rating = $rating, notes = '$notes', updated_at = '$date_now' 
            WHERE id = $item_id AND user_id = $user_id
        ";
        if (mysqli_query($conn, $update_sql)) {
            $success_message = "Shelf progress logs saved to registry!";
        } else {
            $action_error = "Database write failed: " . mysqli_error($conn);
        }
    } else {
        $action_error = "Unauthorized attempt to override record values.";
    }
}

// Process Remove from Shelf
if (isset($_POST['action_type']) && $_POST['action_type'] === 'remove_item') {
    $item_id = (int)$_POST['item_id'];

    $chk = mysqli_query($conn, "SELECT id FROM reading_list WHERE id = $item_id AND user_id = $user_id LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        if (mysqli_query($conn, "DELETE FROM reading_list WHERE id = $item_id")) {
            $success_message = "Book removed from your shelves.";
        } else {
            $action_error = "Database query deletion failure.";
        }
    }
}

// Filters URL status
$active_tab = isset($_GET['tab']) ? mysqli_real_escape_string($conn, $_GET['tab']) : 'All';

// Assemble Query
$query_sql = "
    SELECT rl.*, b.title, b.author, b.genre, b.cover_url, b.published_year 
    FROM reading_list rl
    JOIN books b ON rl.book_id = b.id
    WHERE rl.user_id = $user_id
";

if ($active_tab !== 'All') {
    $query_sql .= " AND rl.status = '$active_tab'";
}

$query_sql .= " ORDER BY rl.updated_at DESC";
$reading_list_result = mysqli_query($conn, $query_sql);

// Calculate current counts
$c_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reading_list WHERE user_id = $user_id"))['cnt'];
$c_want = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reading_list WHERE user_id = $user_id AND status = 'Want to Read'"))['cnt'];
$c_reading = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reading_list WHERE user_id = $user_id AND status = 'Reading'"))['cnt'];
$c_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reading_list WHERE user_id = $user_id AND status = 'Completed'"))['cnt'];
?>

<div class="space-y-6">

    <!-- Bento Header bar -->
    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xs">
        <div>
            <h1 class="text-3xl font-serif font-bold tracking-tight text-slate-900">My Reading Shelf</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Organize your reading logs, tracking states, note-taking essays, and ratings</p>
        </div>
        <div class="flex space-x-2 text-xs font-bold text-slate-500">
            <div class="bg-indigo-50 border border-indigo-100/50 text-indigo-750 px-4 py-2.5 rounded-2xl flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-650" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                <span>Shelf Slot Capacity: <?php echo $c_all; ?> Tractions</span>
            </div>
        </div>
    </div>

    <!-- Alert notes -->
    <?php if (!empty($action_error)): ?>
        <div class="bg-red-50 text-red-850 text-xs font-bold border border-red-100 rounded-xl p-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-red-650 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span><?php echo htmlspecialchars($action_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-100 rounded-xl p-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-emerald-650 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-1.5 overflow-x-auto bg-white rounded-full border border-slate-200/80 p-1.5 shadow-inner max-w-lg">
        <a href="my-list.php?tab=All" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all <?php echo $active_tab === 'All' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-900'; ?>">
            All (<?php echo $c_all; ?>)
        </a>
        <a href="my-list.php?tab=Want to Read" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all <?php echo $active_tab === 'Want to Read' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-900'; ?>">
            Want to Read (<?php echo $c_want; ?>)
        </a>
        <a href="my-list.php?tab=Reading" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all <?php echo $active_tab === 'Reading' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-900'; ?>">
            Reading (<?php echo $c_reading; ?>)
        </a>
        <a href="my-list.php?tab=Completed" class="px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all <?php echo $active_tab === 'Completed' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-indigo-900'; ?>">
            Completed (<?php echo $c_completed; ?>)
        </a>
    </div>

    <!-- Items Grid Container -->
    <?php if ($reading_list_result && mysqli_num_rows($reading_list_result) > 0): ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <?php while ($item = mysqli_fetch_assoc($reading_list_result)): ?>
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xs flex flex-col sm:flex-row gap-5 relative overflow-hidden group">
                    
                    <!-- Cover Photo left column -->
                    <div class="w-28 h-40 rounded-xl overflow-hidden shadow-md relative bg-slate-50 shrink-0 mx-auto sm:mx-0">
                        <img src="<?php echo htmlspecialchars($item['cover_url']); ?>" alt="" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute bottom-2 left-2 rounded bg-slate-900/80 px-2 py-0.5 text-[8px] font-extrabold text-white uppercase tracking-wider">
                            <?php echo htmlspecialchars($item['genre']); ?>
                        </div>
                    </div>

                    <!-- Details and Logs logs -->
                    <div class="grow flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-serif font-bold text-slate-950 text-base leading-tight"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-xs text-slate-500 font-medium">By <?php echo htmlspecialchars($item['author']); ?></p>
                                </div>
                                <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-[10px] uppercase font-bold text-indigo-700 rounded-full tracking-wide">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                            </div>

                            <div class="mt-2 text-xs text-slate-650 bg-slate-50/50 p-3 rounded-xl border border-slate-150/60 leading-relaxed italic min-h-[44px]">
                                <?php echo !empty($item['notes']) ? '"' . htmlspecialchars($item['notes']) . '"' : '<span class="text-slate-450 italic font-medium text-[11px]">Save details, favorite phrases or essay summaries.</span>'; ?>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 pt-3 mt-1.5">
                            <span class="text-[9px] font-bold text-slate-400 font-mono">Logged: <?php echo htmlspecialchars($item['updated_at']); ?></span>
                            
                            <div class="flex items-center space-x-2">
                                <?php if ($item['status'] === 'Completed' && $item['rating'] !== null): ?>
                                    <div class="flex text-amber-500 text-[11px] font-bold">
                                        <?php echo str_repeat('★', $item['rating']); ?>
                                    </div>
                                <?php endif; ?>

                                <button onclick="openEditProgressModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 py-1.5 text-[11px] font-bold text-indigo-600 flex items-center space-x-1 hover:border-indigo-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <span>Log progress</span>
                                </button>
                                
                                <form action="my-list.php" method="POST" onsubmit="return confirm('Remove book tracking log? This has no effect on general catalog books.');" class="inline">
                                    <input type="hidden" name="action_type" value="remove_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="p-1.5 rounded-xl border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-150 shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>

                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="rounded-[2rem] border border-dashed border-slate-350 bg-white p-16 text-center shadow-xs">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            </div>
            <h3 class="mt-4 text-lg font-serif font-bold text-slate-900">Your shelf classification tab is currently empty</h3>
            <p class="mt-1.5 text-xs text-slate-400">Head over to the Catalog to allocate books into your tracking lists.</p>
            <a href="catalog.php" class="mt-4 inline-block bg-indigo-650 hover:bg-indigo-700 text-white font-bold text-xs uppercase px-4.5 py-2.5 rounded-xl transition-colors shadow">Browse Catalog</a>
        </div>
    <?php endif; ?>

</div>


<!-- MODAL: EDIT TRACKING PROGRESS DETAIL -->
<div id="edit-progress-modal" class="fixed inset-0 z-50 p-4 bg-black/45 backdrop-blur-xs hidden flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <h3 class="font-serif font-bold text-slate-900 text-lg">Log Progress Updates</h3>
            <button onclick="closeModal('edit-progress-modal')" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-150 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form action="my-list.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action_type" value="update_tracking">
            <input type="hidden" name="item_id" id="progress-item-id">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Shelf Folder Group</label>
                <select name="status" id="progress-status" onchange="onStatusChange(this.value)" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-700 outline-none cursor-pointer bg-white font-medium">
                    <option value="Want to Read">Want to Read</option>
                    <option value="Reading">Reading</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div id="rating-field-div" class="hidden">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Personal Star Rating</label>
                <select name="rating" id="progress-rating" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-700 outline-none cursor-pointer bg-white font-medium">
                    <option value="NULL">No Rating</option>
                    <option value="5">★ ★ ★ ★ ★ (5 Stars - Stellar)</option>
                    <option value="4">★ ★ ★ ★ ☆ (4 Stars - Recommended)</option>
                    <option value="3">★ ★ ★ ☆ ☆ (3 Stars - Decent)</option>
                    <option value="2">★ ★ ☆ ☆ ☆ (2 Stars - Mediocre)</option>
                    <option value="1">★ ☆ ☆ ☆ ☆ (1 Star - Bad)</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 font-bold font-bold">Personal Reading Log Essays / Notes</label>
                <textarea rows="4" name="notes" id="progress-notes" placeholder="e.g. Cried at the chapter about her sibling, love the sci-fi ecosystem!" class="w-full rounded-2xl border border-slate-250 py-2 px-3 text-xs text-slate-900 outline-none focus:border-indigo-500 resize-none"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-progress-modal')" class="grow rounded-xl py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">Cancel</button>
                <button type="submit" class="grow rounded-xl bg-indigo-600 py-2.5 text-xs font-bold text-white hover:bg-indigo-700 transition-all shadow-xs">Save details</button>
            </div>
        </form>
    </div>
</div>


<script>
function openEditProgressModal(item) {
    document.getElementById('progress-item-id').value = item.id;
    document.getElementById('progress-status').value = item.status;
    document.getElementById('progress-notes').value = item.notes || '';
    
    const rVal = item.rating === null ? 'NULL' : item.rating;
    document.getElementById('progress-rating').value = rVal;

    onStatusChange(item.status);
    openModal('edit-progress-modal');
}

function onStatusChange(status) {
    const ratingDiv = document.getElementById('rating-field-div');
    if (status === 'Completed') {
        ratingDiv.classList.remove('hidden');
    } else {
        ratingDiv.classList.add('hidden');
    }
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>

<?php
require_once 'footer.php';
?>
