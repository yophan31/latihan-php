<?php
require_once (__DIR__ . '/../models/TodoModel.php');

class TodoController
{
    private $todoModel;
    private $error = '';
    private $success = '';

    public function __construct()
    {
        $this->todoModel = new TodoModel();
    }

    public function index()
    {
        // Ambil parameter filter dan search dari URL
        $filter = $_GET['filter'] ?? 'all'; // default 'all'
        $search = $_GET['search'] ?? '';

        // Ambil data todo
        $todos = $this->todoModel->getAllTodos($filter, $search);
        
        // Teruskan error/success message ke view
        $error = $this->error;
        $success = $this->success;
        $currentFilter = $filter;
        $currentSearch = htmlspecialchars($search);
        
        include (__DIR__ . '/../views/TodoView.php');
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');

            if (empty($title)) {
                $this->error = 'Judul tidak boleh kosong.';
            } elseif ($this->todoModel->isTitleExists($title)) {
                $this->error = 'Todo dengan judul "' . htmlspecialchars($title) . '" sudah ada.';
            } else {
                if ($this->todoModel->createTodo($title, $description)) {
                    $this->success = 'Todo baru berhasil ditambahkan.';
                } else {
                    $this->error = 'Gagal menambahkan todo. Coba lagi.';
                }
            }
        }
        // Jika ada error/success, tampilkan lagi halaman index
        if (!empty($this->error) || !empty($this->success)) {
            $this->index();
            return;
        }
        header('Location: index.php');
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            // Mengubah 'status' menjadi 'is_finished'
            $isFinished = (int)$_POST['is_finished']; 

            if (empty($title)) {
                $this->error = 'Judul tidak boleh kosong.';
            } elseif ($this->todoModel->isTitleExists($title, $id)) {
                $this->error = 'Todo dengan judul "' . htmlspecialchars($title) . '" sudah ada.';
            } else {
                if ($this->todoModel->updateTodo($id, $title, $description, $isFinished)) {
                    $this->success = 'Todo berhasil diperbarui.';
                } else {
                    $this->error = 'Gagal memperbarui todo. Coba lagi.';
                }
            }
        }
        // Jika ada error/success, tampilkan lagi halaman index
        if (!empty($this->error) || !empty($this->success)) {
            $this->index();
            return;
        }
        header('Location: index.php');
        exit;
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            if ($this->todoModel->deleteTodo($id)) {
                $this->success = 'Todo berhasil dihapus.';
            } else {
                $this->error = 'Gagal menghapus todo.';
            }
        }
        // Jika ada error/success, tampilkan lagi halaman index
        if (!empty($this->error) || !empty($this->success)) {
            $this->index();
            return;
        }
        header('Location: index.php');
        exit;
    }
    
    // Fitur: Melihat detail todo
    public function detail()
    {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $todo = $this->todoModel->getTodoById($id);
            if ($todo) {
                // Tampilkan detail di view terpisah atau modal. Untuk kesederhanaan, kita akan menggunakan modal di index.
                // Logika ini akan diproses di TodoView.php
                $detailTodo = $todo;
                $this->index();
                return;
            } else {
                $this->error = 'Todo tidak ditemukan.';
            }
        } else {
            $this->error = 'ID Todo tidak valid.';
        }
        
        $this->index();
        exit;
    }

    // Fitur: Menyimpan urutan sorting
    public function sort()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['todo_ids'])) {
            $todoIds = array_map('intval', $_POST['todo_ids']); // Pastikan semua ID adalah integer
            if ($this->todoModel->updateSortOrder($todoIds)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan urutan.']);
            }
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid.']);
        exit;
    }
}