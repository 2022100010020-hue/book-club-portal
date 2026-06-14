<?php
require_once 'db.php';

header('Content-Type: application/json');

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid book identification']);
    exit();
}

$reviews_query = "
    SELECT rl.rating, rl.notes, rl.updated_at, u.email 
    FROM reading_list rl
    JOIN users u ON rl.user_id = u.id
    WHERE rl.book_id = $book_id AND (rl.rating IS NOT NULL OR (rl.notes IS NOT NULL AND rl.notes != ''))
    ORDER BY rl.updated_at DESC
";

$result = mysqli_query($conn, $reviews_query);
$reviews = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Obfuscate email slightly to keep privacy elegant
        $parts = explode('@', $row['email']);
        $display_name = htmlspecialchars($parts[0]);
        
        $reviews[] = [
            'author' => $display_name,
            'rating' => $row['rating'] !== null ? (int)$row['rating'] : null,
            'notes' => htmlspecialchars($row['notes']),
            'date' => $row['updated_at']
        ];
    }
    echo json_encode(['success' => true, 'reviews' => $reviews]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database failure: ' . mysqli_error($conn)]);
}
?>
