<?php
// Mocking the includes for this demonstration
// include 'includes/auth.php'; 
include 'api/books_api.php';

$data = null;
$searchQuery = "";

if (isset($_POST['search'])) {
    $searchQuery = $_POST['book'];
    $data = get_books($searchQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPSC NCERT Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-8">
    <div class="max-w-2xl mx-auto">
        <header class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-blue-900 mb-2">UPSC Library</h1>
            <p class="text-gray-600">Search for NCERT textbooks and UPSC materials</p>
        </header>

        <form method="post" class="flex gap-2 mb-8">
            <input 
                type="text" 
                name="book" 
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                placeholder="e.g. History Class 11, Polity..." 
                class="flex-1 p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
            <button 
                name="search" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition"
            >
                Search
            </button>
        </form>

        <?php if ($data && isset($data['items'])): ?>
            <div class="space-y-4">
                <?php foreach ($data['items'] as $b): 
                    $info = $b['volumeInfo'];
                    $thumbnail = $info['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/128x192?text=No+Cover';
                ?>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex gap-4 hover:shadow-md transition">
                        <img src="<?php echo $thumbnail; ?>" alt="Cover" class="w-24 h-36 object-cover rounded shadow-sm">
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($info['title']); ?></h4>
                            <p class="text-sm text-blue-600 font-medium mb-1">
                                <?php echo htmlspecialchars(implode(', ', $info['authors'] ?? ['Unknown Author'])); ?>
                            </p>
                            <p class="text-xs text-gray-500 mb-2">Published: <?php echo $info['publishedDate'] ?? 'N/A'; ?></p>
                            <p class="text-sm text-gray-600 line-clamp-2">
                                <?php echo htmlspecialchars($info['description'] ?? 'No description available.'); ?>
                            </p>
                            <?php if (isset($info['previewLink'])): ?>
                                <a href="<?php echo $info['previewLink']; ?>" target="_blank" class="inline-block mt-3 text-sm text-blue-600 hover:underline">View Preview &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($_POST['search'])): ?>
            <p class="text-center text-gray-500">No books found. Try adjusting your search keywords.</p>
        <?php endif; ?>
    </div>
</body>
</html>