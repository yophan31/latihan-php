<?php
// Simple controller using PDO. Adjust DB params or require your config.php if you have one.
$dsn = "pgsql:host=localhost;port=5432;dbname=your_db;user=your_user;password=your_pass";
$pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function fetchTodos($pdo, $filter = 'all', $q = '') {
    $sql = "SELECT * FROM todos WHERE 1=1";
    $params = [];
    if ($filter === 'finished') {
        $sql .= " AND is_finished = true";
    } elseif ($filter === 'unfinished') {
        $sql .= " AND is_finished = false";
    }
    if ($q !== '') {
        $sql .= " AND (title ILIKE :q OR description ILIKE :q)";
        $params[':q'] = "%$q%";
    }
    $sql .= " ORDER BY pos ASC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTodo($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createTodo($pdo, $title, $description) {
    // unique title check (case-insensitive)
    $stmt = $pdo->prepare("SELECT id FROM todos WHERE lower(title) = lower(:title)");
    $stmt->execute([':title' => $title]);
    if ($stmt->fetch()) {
        return ['error' => 'Judul sudah digunakan'];
    }
    // determine max pos
    $max = $pdo->query("SELECT COALESCE(MAX(pos),0) as m FROM todos")->fetch(PDO::FETCH_ASSOC)['m'];
    $pos = $max + 1;
    $stmt = $pdo->prepare("INSERT INTO todos (title, description, is_finished, created_at, updated_at, pos) VALUES (:title, :description, false, now(), now(), :pos) RETURNING id");
    $stmt->execute([':title'=>$title, ':description'=>$description, ':pos'=>$pos]);
    return ['id' => $stmt->fetchColumn()];
}

function updateTodo($pdo, $id, $title, $description, $is_finished) {
    // unique title check excluding current id
    $stmt = $pdo->prepare("SELECT id FROM todos WHERE lower(title) = lower(:title) AND id != :id");
    $stmt->execute([':title'=>$title, ':id'=>$id]);
    if ($stmt->fetch()) {
        return ['error' => 'Judul sudah digunakan'];
    }
    $stmt = $pdo->prepare("UPDATE todos SET title = :title, description = :description, is_finished = :is_finished, updated_at = now() WHERE id = :id");
    $stmt->execute([':title'=>$title, ':description'=>$description, ':is_finished'=>$is_finished ? true : false, ':id'=>$id]);
    return ['ok' => true];
}

function deleteTodo($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    return ['ok'=>true];
}

function reorderTodos($pdo, $ids) {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE todos SET pos = :pos WHERE id = :id");
    $pos = 1;
    foreach ($ids as $id) {
        $stmt->execute([':pos'=>$pos, ':id'=>$id]);
        $pos++;
    }
    $pdo->commit();
    return ['ok'=>true];
}

// Routing based on ?page (adjust integration with your existing index.php)
$page = $_GET['page'] ?? 'list';
if ($page === 'list') {
    $filter = $_GET['filter'] ?? 'all';
    $q = $_GET['q'] ?? '';
    $todos = fetchTodos($pdo, $filter, $q);
    include __DIR__ . '/../views/TodoView.php';
    exit;
}
if ($page === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $res = createTodo($pdo, $title, $description);
    if (isset($res['error'])) {
        // you can store error in session/redirect back; simple alert:
        header('Location: ?page=list&err=' . urlencode($res['error']));
    } else {
        header('Location: ?page=list');
    }
    exit;
}
if ($page === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_finished = isset($_POST['is_finished']) ? 1 : 0;
    $res = updateTodo($pdo, $id, $title, $description, $is_finished);
    if (isset($res['error'])) {
        header('Location: ?page=list&err=' . urlencode($res['error']));
    } else {
        header('Location: ?page=list');
    }
    exit;
}
if ($page === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    deleteTodo($pdo, $id);
    header('Location: ?page=list');
    exit;
}
if ($page === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $todo = getTodo($pdo, $id);
    header('Content-Type: application/json');
    echo json_encode($todo ?: new stdClass());
    exit;
}
if ($page === 'reorder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = $data['ids'] ?? [];
    reorderTodos($pdo, $ids);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true]);
    exit;
}
