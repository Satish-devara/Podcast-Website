<!-- dashboard.php with pagination, genre tags, and audio support -->
<?php
include('db.php');
if (!isset($_SESSION['user_id'])) header("Location: index.php");

if (isset($_POST['create'])) {
    $filePath = null;
$sourceType = "uploaded";

if (!empty($_FILES['audio']['name'])) {
    $targetDir = "uploads/";
    $filePath = $targetDir . basename($_FILES["audio"]["name"]);
    move_uploaded_file($_FILES["audio"]["tmp_name"], $filePath);
} elseif (!empty($_POST['online_url'])) {
    $filePath = $_POST['online_url'];
    $sourceType = "online";
}
$stmt = $conn->prepare("INSERT INTO podcasts (title, genre, description, user_id, audio_path, source_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiss", $_POST['title'], $_POST['genre'], $_POST['description'], $_SESSION['user_id'], $filePath, $sourceType);

    $stmt->execute();
}

if (isset($_POST['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM podcasts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_POST['delete_id'], $_SESSION['user_id']);
    $stmt->execute();
}

if (isset($_POST['edit_id'])) {
    $stmt = $conn->prepare("UPDATE podcasts SET title=?, genre=?, description=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssii", $_POST['title'], $_POST['genre'], $_POST['description'], $_POST['edit_id'], $_SESSION['user_id']);
    $stmt->execute();
}

if (isset($_POST['like_id'])) {
    $conn->query("UPDATE podcasts SET likes = likes + 1 WHERE id = " . $_POST['like_id']);
}

$filter = $_GET['genre'] ?? null;
$search = $_GET['search'] ?? "";
$page = $_GET['page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$sql = "SELECT SQL_CALC_FOUND_ROWS p.*, u.username, u.profile_pic FROM podcasts p JOIN users u ON p.user_id = u.id WHERE p.title LIKE ?";
$params = ["%$search%"];
$types = "s";

if ($filter) {
    $sql .= " AND p.genre = ?";
    $params[] = $filter;
    $types .= "s";
}

$sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total = $conn->query("SELECT FOUND_ROWS() AS total")->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

$userId = $_SESSION['user_id'];
$userData = $conn->query("SELECT profile_pic FROM users WHERE id = $userId")->fetch_assoc();
$profilePic = $userData['profile_pic'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Podcast Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-400 to-purple-400 leading-loose p-6">
  <div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <img src="<?= $profilePic ?>" alt="Profile" class="w-14 h-14 rounded-full">
        <h1 class="text-2xl font-semibold">Welcome, <?= $_SESSION['username'] ?></h1>
      </div>
      <a href="logout.php" class="text-red-600 font-semibold">Logout</a>
    </div>

    <form method="GET" class="flex gap-4 mb-4">
      <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search podcasts..." class="p-2 border rounded w-full">
      <select name="genre" class="p-2 border rounded">
        <option value="">All Genres</option>
        <option <?= $filter == "Tech" ? "selected" : "" ?>>Tech</option>
        <option <?= $filter == "Music" ? "selected" : "" ?>>Music</option>
        <option <?= $filter == "Education" ? "selected" : "" ?>>Education</option>
      </select>
      <button class="bg-blue-500 text-white px-4 py-2 rounded">Filter</button>
    </form>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow mb-6" >
      <h2 class="text-xl font-bold mb-2">Create New Podcast</h2>
      <input name="title" placeholder="Podcast Title" class="block w-full p-2 border rounded mb-2">
      <input name="genre" placeholder="Genre" class="block w-full p-2 border rounded mb-2">
      <textarea name="description" placeholder="Description" class="block w-full p-2 border rounded mb-2"></textarea>
      <input type="file" name="audio" accept="audio/*" class="block mb-2">
      <input name="online_url" placeholder="Or paste an online audio URL" class="block w-full p-2 border rounded mb-2">
      <button name="create" class="bg-green-600 text-white px-4 py-2 rounded">Create Podcast</button>
    </form>

    <div class="grid gap-4">
      <?php while ($row = $result->fetch_assoc()) { ?>
        <div class="bg-white p-4 rounded shadow border-l-4 border-<?= strtolower($row['genre']) == 'music' ? 'pink' : (strtolower($row['genre']) == 'tech' ? 'blue' : 'green') ?>-500">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold"><?= htmlspecialchars($row['title']) ?></h2>
            <form method="POST" class="inline">
              <input type="hidden" name="like_id" value="<?= $row['id'] ?>">
              <button class="text-pink-500">❤️ <?= $row['likes'] ?></button>
            </form>
          </div>
          <span class="inline-block px-2 py-1 text-xs bg-gray-200 rounded-full text-gray-600 mt-1 mb-2">Genre: <?= htmlspecialchars($row['genre']) ?></span>
          <p class="text-gray-700 mb-2"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
          <?php if ($row['audio_path']) { ?>
  <audio controls class="w-full mb-2">
    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/*">
    Your browser does not support audio.
  </audio>
  <p class="text-xs text-gray-500 italic">Source: <?= $row['source_type'] == 'online' ? 'Online' : 'Uploaded' ?></p>
<?php } ?>

          <div class="text-sm text-gray-500">Posted by <?= htmlspecialchars($row['username']) ?></div>
          <?php if ($row['user_id'] == $_SESSION['user_id']) { ?>
            <div class="mt-3 flex gap-2">
              <form method="POST" class="w-full">
                <input name="title" value="<?= htmlspecialchars($row['title']) ?>" class="w-full p-1 border rounded mb-1">
                <input name="genre" value="<?= htmlspecialchars($row['genre']) ?>" class="w-full p-1 border rounded mb-1">
                <textarea name="description" class="w-full p-1 border rounded mb-1"><?= htmlspecialchars($row['description']) ?></textarea>
                <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                <button class="bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
              </form>
              <form method="POST">
                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                <button class="bg-red-600 text-white px-3 py-1 rounded mt-2">Delete</button>
              </form>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <p>🎙️1. The Joe Rogan Experience</p>
    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/4rOoJ6Egrf8K2IrywzwOMk/video?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
        <p>🎙️2. The Daily</p>
        <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/3IM0lmZxpFAY7CwMuv9H4g?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
            <p>🎙️3. This American Life</p>
            <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/41zWZdWCpVQrKj7ykQnXRc?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                <p>🎙️4. Stuff You Should Know</p>
                <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/0ofXAdFIQQRsCYj9754UFx?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                    <p>🎙️5. Pod Save America</p>
                    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/5JGorGvdwljJHTl6wpMXN3?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                        <p>🎙️6. The Ben Shapiro Show</p>
                        <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/1WErgoXiZwgctkHLzqU6nf/video?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                            <p>🎙️7. Call Her Daddy</p>
                            <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/7bnjJ7Va1nM07Um4Od55dW/video?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                <p>🎙️8. Office Ladies </p>
                                <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/3OHCFs84lqizjkL4C9bNTA?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                    <p>🎙️9. On Purpose with Jay Shetty</p>
                                    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/5EqqB52m2bsr4k1Ii7sStc?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                        <p>🎙️10. My Favorite Murder</p>
                                        <iframe style="border-radius:12px" src="https://open.spotify.com/embed/show/0U9S5J2ltMaKdxIfLuEjzE?utm_source=generator" width="100%" height="152" frameborder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                            <p>🎙️11.Why thinking about death will leads you to live better</p>
                                            <iframe style="border-radius:12px" src="https://open.spotify.com/embed/episode/5LXt6zzmWDP2RYSHqdFEEr?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                                <p>🎙️12. Can bigTech and Provacy coexist?</p>
                                                <iframe style="border-radius:12px" src="https://open.spotify.com/embed/episode/15NgO1IqvQeTGrGBKU3mWX?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>  
                                                    <p>🎙️13. How to mix Bussiness and Family</p>
                                                    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/episode/3s6UfEV6i6bVp3xvy9dpDR?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                                    <p>🎙️14. The power of gaming together in lonely world</p>
                                                    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/episode/674NnkKouirq73uIX30MV5?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
                                                    <p>🎙️15. An ethisictic's guide to live a good life</p>
                                                    <iframe style="border-radius:12px" src="https://open.spotify.com/embed/episode/1MuJrYYS3f3CNGd3gfe5au?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
    <div class="flex justify-center gap-2 mt-6">
      <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&genre=<?= urlencode($filter) ?>"
           class="px-3 py-1 border <?= $page == $i ? 'bg-blue-500 text-white' : 'bg-white' ?> rounded">
          <?= $i ?>
        </a>
      <?php } ?>
    </div>
  </div>
</body>
</html>
