<?php
require_once (__DIR__ . '/../config.php');

class TodoModel
{
    private $conn;

    public function __construct()
    {
        // Inisialisasi koneksi database PostgreSQL
        $this->conn = pg_connect('host=' . DB_HOST . ' port=' . DB_PORT . ' dbname=' . DB_NAME . ' user=' . DB_USER . ' password=' . DB_PASSWORD);
        if (!$this->conn) {
            die('Koneksi database gagal');
        }
    }

    /**
     * Mengambil daftar todo dengan filter, pencarian, dan diurutkan.
     * @param string $filter Status filter ('all', 'finished', 'unfinished').
     * @param string $search Kata kunci pencarian.
     * @return array Daftar todos.
     */
    public function getAllTodos(string $filter = 'all', string $search = '')
    {
        $params = [];
        $whereClauses = [];
        $i = 1;

        // 1. Filter Status
        if ($filter === 'finished') {
            $whereClauses[] = "is_finished = TRUE";
        } elseif ($filter === 'unfinished') {
            $whereClauses[] = "is_finished = FALSE";
        }

        // 2. Pencarian
        if (!empty($search)) {
            $searchPattern = '%' . strtolower($search) . '%';
            // Pencarian di title dan description
            $whereClauses[] = "(LOWER(title) LIKE $" . $i++ . " OR LOWER(description) LIKE $" . $i++ . ")";
            $params[] = $searchPattern;
            $params[] = $searchPattern;
        }

        // Gabungkan klausa WHERE
        $where = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';
        
        // 3. Pengurutan (Default berdasarkan sort_order)
        $orderBy = ' ORDER BY sort_order ASC, created_at DESC';

        $query = 'SELECT * FROM todo' . $where . $orderBy;
        
        $result = pg_query_params($this->conn, $query, $params);
        $todos = [];
        if ($result && pg_num_rows($result) > 0) {
            while ($row = pg_fetch_assoc($result)) {
                // Ubah 't'/'f' PostgreSQL menjadi boolean PHP/integer
                $row['is_finished'] = ($row['is_finished'] === 't' || $row['is_finished'] === 1) ? 1 : 0;
                $todos[] = $row;
            }
        }
        return $todos;
    }

    /**
     * Memeriksa apakah judul sudah ada.
     * @param string $title Judul todo.
     * @param int|null $excludeId ID todo yang dikecualikan (untuk edit).
     * @return bool True jika judul sudah ada.
     */
    public function isTitleExists(string $title, ?int $excludeId = null): bool
    {
        $query = 'SELECT COUNT(*) FROM todo WHERE LOWER(title) = LOWER($1)';
        $params = [$title];
        
        if ($excludeId !== null) {
            $query .= ' AND id != $2';
            $params[] = $excludeId;
        }

        $result = pg_query_params($this->conn, $query, $params);
        if ($result) {
            return pg_fetch_result($result, 0, 0) > 0;
        }
        return false;
    }

    /**
     * Mendapatkan urutan sorting maksimum dan menambahkannya 1.
     * @return int Urutan sort berikutnya.
     */
    private function getNextSortOrder(): int
    {
        $query = 'SELECT COALESCE(MAX(sort_order), 0) FROM todo';
        $result = pg_query($this->conn, $query);
        return $result ? (int)pg_fetch_result($result, 0, 0) + 1 : 1;
    }

    /**
     * Membuat todo baru.
     * @param string $title Judul.
     * @param string $description Deskripsi.
     * @return bool True jika berhasil.
     */
    public function createTodo(string $title, string $description): bool
    {
        if ($this->isTitleExists($title)) {
            return false; // Judul duplikat
        }
        
        $sortOrder = $this->getNextSortOrder();
        $query = 'INSERT INTO todo (title, description, is_finished, created_at, updated_at, sort_order) 
                  VALUES ($1, $2, FALSE, NOW(), NOW(), $3)';
        $result = pg_query_params($this->conn, $query, [$title, $description, $sortOrder]);
        
        return $result !== false;
    }

    /**
     * Memperbarui todo yang sudah ada.
     * @param int $id ID todo.
     * @param string $title Judul.
     * @param string $description Deskripsi.
     * @param int $isFinished Status selesai (0 atau 1).
     * @return bool True jika berhasil.
     */
    public function updateTodo(int $id, string $title, string $description, int $isFinished): bool
    {
        if ($this->isTitleExists($title, $id)) {
            return false; // Judul duplikat
        }

        $query = 'UPDATE todo SET title=$1, description=$2, is_finished=$3, updated_at=NOW() WHERE id=$4';
        $result = pg_query_params($this->conn, $query, [$title, $description, (bool)$isFinished, $id]);
        
        return $result !== false;
    }

    /**
     * Menghapus todo.
     * @param int $id ID todo.
     * @return bool True jika berhasil.
     */
    public function deleteTodo(int $id): bool
    {
        $query = 'DELETE FROM todo WHERE id=$1';
        $result = pg_query_params($this->conn, $query, [$id]);
        
        return $result !== false;
    }

    /**
     * Mengambil detail todo berdasarkan ID.
     * @param int $id ID todo.
     * @return array|null Detail todo.
     */
    public function getTodoById(int $id): ?array
    {
        $query = 'SELECT * FROM todo WHERE id=$1';
        $result = pg_query_params($this->conn, $query, [$id]);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $row['is_finished'] = ($row['is_finished'] === 't' || $row['is_finished'] === 1) ? 1 : 0;
            return $row;
        }
        return null;
    }

    /**
     * Memperbarui urutan sorting untuk todos.
     * @param array $todoIds Array ID todo dalam urutan baru.
     * @return bool True jika berhasil.
     */
    public function updateSortOrder(array $todoIds): bool
    {
        pg_query($this->conn, 'BEGIN');
        $success = true;
        
        foreach ($todoIds as $index => $id) {
            $sortOrder = $index + 1;
            $query = 'UPDATE todo SET sort_order = $1 WHERE id = $2';
            $result = pg_query_params($this->conn, $query, [$sortOrder, $id]);
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            pg_query($this->conn, 'COMMIT');
        } else {
            pg_query($this->conn, 'ROLLBACK');
        }
        
        return $success;
    }
}