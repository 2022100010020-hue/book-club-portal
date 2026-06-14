<?php
require_once 'db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'member';

$action_error = '';
$form_error = '';
$success_message = '';

// Genres list
$genres = ['Fiction', 'Science Fiction', 'Biography', 'Mystery', 'Self-Help', 'Fantasy', 'History', 'Thriller', 'Poetry'];

// Check and process book tracking request
if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_to_reading_list') {
    if (!$user_id) {
        header('Location: auth.php');
        exit();
    }
    $book_id = (int)$_POST['book_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $date_now = date('Y-m-d');

    // Check if record already exists on list
    $chk = mysqli_query($conn, "SELECT id FROM reading_list WHERE user_id = $user_id AND book_id = $book_id");
    if (mysqli_num_rows($chk) > 0) {
        $action_error = "This book is already recorded on one of your folders!";
    } else {
        $ins = mysqli_query($conn, "INSERT INTO reading_list (user_id, book_id, status, rating, notes, updated_at) VALUES ($user_id, $book_id, '$status', NULL, '', '$date_now')");
        if ($ins) {
            $success_message = "Book added layout was registered onto your shelf successfully!";
        } else {
            $action_error = "Db logging failed: " . mysqli_error($conn);
        }
    }
}

// Check and process Add New Book request
if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_book') {
    if (!$user_id) {
        header('Location: auth.php');
        exit();
    }
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre = trim($_POST['genre']);
    $published_year = (int)$_POST['published_year'];
    $cover_url = trim($_POST['cover_url']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($author)) {
        $form_error = "Book Title and Author name fields are strictly required.";
    } else {
        if (empty($cover_url)) {
            $cover_url = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&q=80&w=400';
        }
        if (empty($description)) {
            $description = 'No summary or descriptive logs provided for this title.';
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, genre, description, cover_url, published_year, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssii", $title, $author, $genre, $description, $cover_url, $published_year, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "New book registered inside the community catalog successfully!";
        } else {
            $action_error = "Enrollment query failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Check and process Edit Book request
if (isset($_POST['action_type']) && $_POST['action_type'] === 'edit_book') {
    if (!$user_id) {
        header('Location: auth.php');
        exit();
    }
    $book_id = (int)$_POST['book_id'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre = trim($_POST['genre']);
    $published_year = (int)$_POST['published_year'];
    $cover_url = trim($_POST['cover_url']);
    $description = trim($_POST['description']);

    // Confirm ownership
    $chk_own = mysqli_query($conn, "SELECT created_by FROM books WHERE id = $book_id LIMIT 1");
    if ($chk_own && mysqli_num_rows($chk_own) > 0) {
        $verify = mysqli_fetch_assoc($chk_own);
        if ($verify['created_by'] !== $user_id && $user_role !== 'admin') {
            $action_error = "Unauthorized! You do not have permission to modify this book.";
        } elseif (empty($title) || empty($author)) {
            $form_error = "Book Title and Author cannot be empty.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE books SET title = ?, author = ?, genre = ?, description = ?, cover_url = ?, published_year = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssssii", $title, $author, $genre, $description, $cover_url, $published_year, $book_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Book updates completed successfully!";
            } else {
                $action_error = "Database modification failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Check and process Delete Book request
if (isset($_POST['action_type']) && $_POST['action_type'] === 'delete_book') {
    if (!$user_id) {
        header('Location: auth.php');
        exit();
    }
    $book_id = (int)$_POST['book_id'];

    // Verify creator
    $chk_own = mysqli_query($conn, "SELECT created_by FROM books WHERE id = $book_id LIMIT 1");
    if ($chk_own && mysqli_num_rows($chk_own) > 0) {
        $verify = mysqli_fetch_assoc($chk_own);
        if ($verify['created_by'] !== $user_id && $user_role !== 'admin') {
            $action_error = "Unauthorized! You do not have permission to delete this book.";
        } else {
            // Delete associated reading lists first
            mysqli_query($conn, "DELETE FROM reading_list WHERE book_id = $book_id");

            $del = mysqli_query($conn, "DELETE FROM books WHERE id = $book_id");
            if ($del) {
                $success_message = "Book successfully deleted from database catalog!";
            } else {
                $action_error = "Delete protocol failed: " . mysqli_error($conn);
            }
        }
    }
}
require_once 'header.php';
// Load filters from URL parameters safely
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$selected_genre = isset($_GET['genre']) && $_GET['genre'] !== 'All' ? mysqli_real_escape_string($conn, trim($_GET['genre'])) : 'All';

// Assemble query dynamically
$book_sql = "
    SELECT b.*, u.email as creator_email, COALESCE(ROUND(AVG(rl.rating), 1), 4.5) AS computed_rating, COUNT(rl.rating) as count_reviews
    FROM books b
    JOIN users u ON b.created_by = u.id
    LEFT JOIN reading_list rl ON b.id = rl.book_id
";

$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
}
if ($selected_genre !== 'All') {
    $where_clauses[] = "b.genre = '$selected_genre'";
}

if (!empty($where_clauses)) {
    $book_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$book_sql .= " GROUP BY b.id ORDER BY b.title ASC";
$books_result = mysqli_query($conn, $book_sql);
?>

<div class="space-y-6">

    <!-- Header Bento Bar -->
    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xs">
        <div>
            <h1 class="text-3xl font-serif font-bold tracking-tight text-slate-900">Books Catalog</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Manage, browse, and register shared volumes in the physical registry</p>
        </div>

        <?php if ($user_id): ?>
            <button onclick="openModal('add-book-modal')" class="flex items-center space-x-2 rounded-full bg-indigo-600 px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add New Book</span>
            </button>
        <?php else: ?>
            <div class="text-xs font-semibold text-slate-400 bg-slate-50 rounded-full px-4.5 py-2.5 border border-slate-200">
                Sign in to register new books.
            </div>
        <?php endif; ?>
    </div>

    <!-- Alert notifications -->
    <?php if (!empty($action_error)): ?>
        <div class="bg-red-50 text-red-800 text-xs font-bold border border-red-100 rounded-xl p-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-red-600 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span><?php echo htmlspecialchars($action_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-100 rounded-xl p-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-emerald-600 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Search Controls -->
    <form action="catalog.php" method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-12 bg-white rounded-[1.8rem] border border-slate-200 p-4.5 shadow-xs">
        <div class="sm:col-span-8 relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4.5 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
                type="text"
                name="search"
                placeholder="Search books by title, author..."
                value="<?php echo htmlspecialchars($search); ?>"
                class="w-full rounded-2xl border border-slate-250 bg-slate-50/50 py-3 pl-11 pr-5 text-xs font-medium text-slate-900 outline-none transition-all placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500"
            />
        </div>

        <div class="sm:col-span-4 flex gap-2">
            <select
                name="genre"
                onchange="this.form.submit()"
                class="grow rounded-2xl border border-slate-250 bg-slate-50/50 py-3 px-4.5 text-xs font-bold text-slate-700 outline-none cursor-pointer focus:border-indigo-500 focus:bg-white"
            >
                <option value="All">All Genres</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo $selected_genre === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-indigo-600 text-white rounded-2xl px-5 text-xs font-bold hover:bg-indigo-700 transition-colors">Search</button>
        </div>
    </form>

    <!-- Books Listing Grid -->
    <?php if ($books_result && mysqli_num_rows($books_result) > 0): ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php while ($book = mysqli_fetch_assoc($books_result)): 
                $isCreator = ($user_id && $book['created_by'] == $user_id);
                
                // Get list status if logged in
                $relation_label = '';
                if ($user_id) {
                    $rel_q = mysqli_query($conn, "SELECT status FROM reading_list WHERE user_id = $user_id AND book_id = " . $book['id'] . " LIMIT 1");
                    if (mysqli_num_rows($rel_q) > 0) {
                        $relation_label = mysqli_fetch_assoc($rel_q)['status'];
                    }
                }
            ?>
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xs hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 transition-all cursor-pointer" onclick="openDetailsModal(<?php echo htmlspecialchars(json_encode($book)); ?>, '<?php echo $relation_label; ?>')">
                    
                    <div>
                        <!-- Aspect-cover Wrap -->
                        <div class="relative aspect-[3/4] w-full overflow-hidden bg-slate-100">
                            <img
                                src="<?php echo htmlspecialchars($book['cover_url']); ?>"
                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                class="h-full w-full object-cover group-hover:scale-[1.03] transition-all duration-300"
                            />

                            <?php if (!empty($relation_label)): ?>
                                <div class="absolute top-3 right-3 rounded-full bg-emerald-600/90 py-1.5 px-3 text-[9px] font-extrabold text-white shadow backdrop-blur-xs flex items-center space-x-1.5 uppercase tracking-widest">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span><?php echo htmlspecialchars($relation_label); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Meta Genre -->
                            <div class="absolute bottom-3 left-3 rounded-full bg-slate-950/80 px-3 py-1 text-[9px] font-extrabold text-white uppercase tracking-widest">
                                <?php echo htmlspecialchars($book['genre']); ?>
                            </div>

                            <?php if ($isCreator): ?>
                                <div class="absolute top-3 left-3 rounded-full bg-indigo-600 py-1 px-2.5 text-[9px] font-extrabold text-white shadow uppercase tracking-widest">
                                    Mine
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info details -->
                        <div class="p-5 space-y-2">
                            <h3 class="line-clamp-1 font-serif font-bold text-slate-900 text-[15px] tracking-tight group-hover:text-indigo-600 transition-colors">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </h3>
                            
                            <div class="flex items-center justify-between text-xs font-medium text-slate-500">
                                <span class="truncate pr-2">By <?php echo htmlspecialchars($book['author']); ?></span>
                                <span class="shrink-0 font-mono text-[10px] bg-slate-150 px-1.5 py-0.5 rounded-md"><?php echo $book['published_year']; ?></span>
                            </div>

                            <div class="flex items-center space-x-1 text-[#F59E0B] pt-1 border-t border-slate-100 text-xs">
                                <span><?php echo str_repeat('★', round($book['computed_rating'])) . str_repeat('☆', 5 - round($book['computed_rating'])); ?></span>
                                <span class="text-[11px] font-bold text-slate-700 ml-1"><?php echo floatval($book['computed_rating']); ?> Avg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer panel actions -->
                    <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-3.5 flex items-center justify-between pointer-events-auto" onclick="event.stopPropagation();">
                        <span class="text-[10px] text-slate-400 font-bold uppercase truncate max-w-[120px]">
                            <?php echo htmlspecialchars(explode('@', $book['creator_email'])[0]); ?>
                        </span>

                        <div class="flex items-center space-x-1.5">
                            <button onclick="openDetailsModal(<?php echo htmlspecialchars(json_encode($book)); ?>, '<?php echo $relation_label; ?>')" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-100 transition-colors shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>

                            <?php if ($isCreator || $user_role === 'admin'): ?>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($book)); ?>)" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-505 hover:text-indigo-600 hover:border-indigo-150 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <button onclick="triggerDelete(<?php echo $book['id']; ?>)" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-150 transition-colors shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-16 text-center shadow-xs">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
            <h3 class="mt-4 text-lg font-serif font-bold text-slate-900">No books registered matching your parameters</h3>
            <p class="mt-1.5 text-xs text-slate-400">Try typing another query or create your own book!</p>
        </div>
    <?php endif; ?>

</div>


<!-- MODAL 1: ADD BOOK REGISTRY -->
<div id="add-book-modal" class="fixed inset-0 z-50 p-4 bg-black/45 backdrop-blur-xs hidden flex items-center justify-center">
    <div class="w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <h3 class="font-serif font-bold text-slate-900 text-lg">Add New Book Registry</h3>
            <button onclick="closeModal('add-book-modal')" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-150 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form action="catalog.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action_type" value="add_book">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Book Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Moby Dick" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-950 outline-none hover:border-slate-300 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Author Name *</label>
                    <input type="text" name="author" required placeholder="e.g. Herman Melville" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-950 outline-none hover:border-slate-300 focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Genre Category</label>
                    <select name="genre" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-700 outline-none cursor-pointer focus:border-indigo-500 bg-white font-medium">
                        <?php foreach ($genres as $g): ?>
                            <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Published Year</label>
                    <input type="number" name="published_year" value="<?php echo date('Y'); ?>" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-950 outline-none focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Cover Image URL</label>
                <input type="url" name="cover_url" placeholder="https://images.unsplash.com/... or leave blank" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-950 outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Brief Summary/ Narrative</label>
                <textarea rows="3" name="description" placeholder="Write a summary description of the book..." class="w-full rounded-2xl border border-slate-250 py-2 px-3 text-xs text-slate-950 outline-none focus:border-indigo-500 resize-none"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('add-book-modal')" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow hover:bg-indigo-700">Confirm and Add</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL 2: EDIT BOOK DESIGN -->
<div id="edit-book-modal" class="fixed inset-0 z-50 p-4 bg-black/45 backdrop-blur-xs hidden flex items-center justify-center">
    <div class="w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <h3 class="font-serif font-bold text-slate-900 text-lg">Edit Book Registry</h3>
            <button onclick="closeModal('edit-book-modal')" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-150 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form action="catalog.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action_type" value="edit_book">
            <input type="hidden" name="book_id" id="edit-book-id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Book Title *</label>
                    <input type="text" name="title" id="edit-title" required class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-900 outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Author Name *</label>
                    <input type="text" name="author" id="edit-author" required class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-900 outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Genre Category</label>
                    <select name="genre" id="edit-genre" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-700 outline-none bg-white font-medium">
                        <?php foreach ($genres as $g): ?>
                            <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Published Year</label>
                    <input type="number" name="published_year" id="edit-published-year" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-900 outline-none focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Cover Image URL</label>
                <input type="url" name="cover_url" id="edit-cover" class="w-full rounded-2xl border border-slate-250 py-2.5 px-3.5 text-xs text-slate-900 outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Brief Summary/ Narrative</label>
                <textarea rows="3" name="description" id="edit-desc" class="w-full rounded-2xl border border-slate-250 py-2 px-3 text-xs text-slate-900 outline-none focus:border-indigo-500 resize-none"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-book-modal')" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow hover:bg-indigo-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL 3: DELETE CONFIRMATION -->
<div id="delete-book-modal" class="fixed inset-0 z-50 p-4 bg-black/45 backdrop-blur-xs hidden flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-[2rem] shadow-2xl p-6 text-center border border-slate-100 animate-in scale-in duration-150">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        </div>
        
        <h3 class="text-lg font-serif font-bold text-slate-900 mb-2">Delete Book Registry?</h3>
        <p class="text-xs text-slate-400 mb-6 leading-relaxed">
            This action is permanent. Book indexes on other users' tracked shelves will remain listed but flagged as archived.
        </p>

        <form action="catalog.php" method="POST" class="flex items-center justify-center space-x-2">
            <input type="hidden" name="action_type" value="delete_book">
            <input type="hidden" name="book_id" id="delete-book-id">
            
            <button type="button" onclick="closeModal('delete-book-modal')" class="grow rounded-xl py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">No, Keep</button>
            <button type="submit" class="grow rounded-xl bg-red-600 py-2.5 text-xs font-bold text-white hover:bg-red-700 transition-all shadow-xs">Yes, Delete</button>
        </form>
    </div>
</div>


<!-- MODAL 4: DETAILED BOOK SIDE PANEL (SLIDE-OVER) -->
<div id="details-book-panel" class="fixed inset-0 z-50 bg-black/45 backdrop-blur-xs hidden flex justify-end" onclick="closeDetailsModalOuter()">
    <div class="w-full max-w-lg bg-[#F8F6F0] h-full overflow-y-auto flex flex-col justify-between p-7 lg:p-8 shadow-2xl rounded-l-[2rem] border-l border-slate-200/50" onclick="event.stopPropagation();">
        
        <div id="view-upper-area">
            <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-6">
                <h3 class="font-serif font-bold text-slate-900 text-lg flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    <span>Registry Details</span>
                </h3>
                <button onclick="closeModal('details-book-panel')" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-200/50 hover:text-slate-950 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Book Showcase Card -->
            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm flex flex-col sm:flex-row gap-5">
                <div class="h-52 w-36 shrink-0 rounded-xl overflow-hidden shadow-md border border-slate-150 relative bg-slate-50">
                    <img id="detail-cover-img" src="" alt="" class="absolute inset-0 w-full h-full object-cover">
                </div>

                <div class="space-y-4 grow">
                    <div>
                        <span id="detail-genre-badge" class="inline-block rounded-full bg-indigo-50 border border-indigo-100 px-3 py-0.5 text-[9px] font-bold text-indigo-700 uppercase tracking-widest"></span>
                        <h2 id="detail-title" class="text-xl font-serif font-bold text-slate-900 tracking-tight mt-2 leading-tight"></h2>
                        <p id="detail-author" class="text-xs text-slate-500 font-bold mt-1"></p>
                    </div>

                    <div class="text-xs space-y-2 text-slate-600 border-t border-slate-100 pt-3">
                        <div class="flex justify-between items-center">
                          <span class="text-slate-400 font-bold uppercase text-[9px]">Published:</span>
                          <span id="detail-published" class="font-bold text-slate-800 font-mono"></span>
                        </div>
                        <div class="flex justify-between items-center">
                          <span class="text-slate-400 font-bold uppercase text-[9px]">Curated By:</span>
                          <span id="detail-creator" class="font-bold text-slate-800 truncate max-w-[150px]"></span>
                        </div>
                        <div class="flex justify-between items-center">
                          <span class="text-slate-400 font-bold uppercase text-[9px]">Global Score:</span>
                          <span class="font-extrabold text-amber-500 flex items-center gap-1">★ <span id="detail-rating"></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-2">
                <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest pl-1">Story Narrative</h4>
                <div id="detail-description" class="rounded-[1.8rem] bg-white border border-slate-200 p-5 text-slate-600 text-xs leading-relaxed italic"></div>
            </div>

            <!-- Member Reviews Thread -->
            <div class="mt-6 space-y-3">
                <h4 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest pl-1">Reader Reviews & Commentary</h4>
                <div id="reviews-loader" class="hidden text-xs text-slate-400 italic pl-1 flex items-center space-x-1.5 animate-pulse">
                    <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Retrieving community logs...</span>
                </div>
                <div id="reviews-container" class="space-y-2.5 max-h-[220px] overflow-y-auto pr-1"></div>
            </div>
        </div>

        <!-- Add Actions -->
        <div id="view-bottom-panel" class="border-t border-slate-200 pt-5 mt-6">
            <?php if ($user_id): ?>
                <div id="status-selection-box">
                    <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest pl-1 mb-2">Track on your shelves:</label>
                    
                    <form action="catalog.php" method="POST" class="grid grid-cols-3 gap-2">
                        <input type="hidden" name="action_type" value="add_to_reading_list">
                        <input type="hidden" name="book_id" id="register-book-id">
                        
                        <button type="submit" name="status" value="Want to Read" class="rounded-xl bg-white border border-slate-250 py-3 text-[10px] font-bold text-slate-700 uppercase tracking-wider hover:bg-slate-50 transition-colors shadow-xs">Want to Read</button>
                        <button type="submit" name="status" value="Reading" class="rounded-xl bg-indigo-600 py-3 text-[10px] font-bold text-white uppercase tracking-wider hover:bg-indigo-700 transition-colors shadow-xs">Reading</button>
                        <button type="submit" name="status" value="Completed" class="rounded-xl bg-emerald-600 py-3 text-[10px] font-bold text-white uppercase tracking-wider hover:bg-emerald-700 transition-colors shadow-xs">Completed</button>
                    </form>
                </div>
                <div id="already-tracked-badge" class="rounded-[1.5rem] bg-indigo-50 border border-indigo-100 p-5 text-center space-y-2 text-indigo-900 hidden">
                    <span class="block text-xs font-extrabold uppercase tracking-wide mb-1">Already registered!</span>
                    <span id="tracked-status-span" class="text-[10px] inline-block font-extrabold text-indigo-700 bg-white/80 border border-indigo-100 rounded-full py-1 px-4 text-center mt-1"></span>
                </div>
            <?php else: ?>
                <div class="rounded-2xl bg-white border border-slate-255 p-5 text-center">
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Sign in to track this book.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>


<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function openEditModal(book) {
    document.getElementById('edit-book-id').value = book.id;
    document.getElementById('edit-title').value = book.title;
    document.getElementById('edit-author').value = book.author;
    document.getElementById('edit-genre').value = book.genre;
    document.getElementById('edit-published-year').value = book.published_year;
    document.getElementById('edit-cover').value = book.cover_url;
    document.getElementById('edit-desc').value = book.description;
    
    openModal('edit-book-modal');
}

function triggerDelete(id) {
    document.getElementById('delete-book-id').value = id;
    openModal('delete-book-modal');
}

function openDetailsModal(book, trackerLabel) {
    document.getElementById('detail-cover-img').src = book.cover_url;
    document.getElementById('detail-genre-badge').innerText = book.genre;
    document.getElementById('detail-title').innerText = book.title;
    document.getElementById('detail-author').innerText = "By " + book.author;
    document.getElementById('detail-published').innerText = book.published_year;
    document.getElementById('detail-creator').innerText = book.creator_email.split('@')[0];
    document.getElementById('detail-rating').innerText = parseFloat(book.computed_rating) + " (" + book.count_reviews + " reviews)";
    document.getElementById('detail-description').innerText = '"' + book.description + '"';
    
    const regBookId = document.getElementById('register-book-id');
    if (regBookId) {
        regBookId.value = book.id;
        
        const selectionBox = document.getElementById('status-selection-box');
        const trackedBadge = document.getElementById('already-tracked-badge');
        const trackedSpan = document.getElementById('tracked-status-span');

        if (trackerLabel && trackerLabel !== '') {
            selectionBox.classList.add('hidden');
            trackedBadge.classList.remove('hidden');
            trackedSpan.innerText = "OWNED AS " + trackerLabel;
        } else {
            selectionBox.classList.remove('hidden');
            trackedBadge.classList.add('hidden');
        }
    }

    // Fetch and render reviews via AJAX API
    const reviewsContainer = document.getElementById('reviews-container');
    const loader = document.getElementById('reviews-loader');
    if (reviewsContainer && loader) {
        reviewsContainer.innerHTML = '';
        loader.classList.remove('hidden');
        
        fetch('api-reviews.php?book_id=' + book.id)
            .then(res => res.json())
            .then(data => {
                loader.classList.add('hidden');
                if (data.success && data.reviews.length > 0) {
                    let html = '';
                    data.reviews.forEach(rev => {
                        let stars = rev.rating ? '★'.repeat(rev.rating) + '☆'.repeat(5 - rev.rating) : '';
                        let notesHtml = rev.notes ? `<p class="text-slate-600 mt-1 leading-relaxed">"${rev.notes}"</p>` : '';
                        let ratingBadge = rev.rating ? `<span class="text-amber-500 font-bold ml-1.5 text-[10px] bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded-md">${stars}</span>` : '';
                        
                        html += `
                            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 space-y-1 text-xs shrink-0 shadow-sm hover:border-indigo-100 transition-all">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 flex items-center">
                                        ${rev.author}
                                        ${ratingBadge}
                                    </span>
                                    <span class="text-[9px] font-mono text-slate-400 font-bold uppercase">${rev.date}</span>
                                </div>
                                ${notesHtml}
                            </div>
                        `;
                    });
                    reviewsContainer.innerHTML = html;
                } else {
                    reviewsContainer.innerHTML = `
                        <div class="border border-dashed border-slate-200 rounded-2xl p-5 text-center text-slate-400 text-xs italic bg-white">
                            No commentary submitted for this volume yet.
                        </div>
                    `;
                }
            })
            .catch(err => {
                loader.classList.add('hidden');
                reviewsContainer.innerHTML = `<div class="text-xs text-red-500 font-bold">Failed to connect to reviews engine.</div>`;
            });
    }

    openModal('details-book-panel');
}

function closeDetailsModalOuter() {
    closeModal('details-book-panel');
}
</script>

<?php
require_once 'footer.php';
?>
