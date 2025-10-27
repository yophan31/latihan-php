<!DOCTYPE html>
<html>
<head>
    <title>PHP - Aplikasi Todolist</title>
    <link href="/assets/vendor/bootstrap-5.3.8-dist/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Gaya untuk item yang bisa di-drag */
        .sortable-item {
            cursor: grab;
        }
        .sortable-item:active {
            cursor: grabbing;
        }
        /* Gaya umpan balik saat item diseret */
        .sortable-ghost {
            opacity: 0.4;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container-fluid p-5">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Todo List</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTodo">Tambah Data</button>
            </div>
            <hr />

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filterStatus" class="form-label">Filter Status</label>
                    <select class="form-select" id="filterStatus" onchange="applyFilterAndSearch()">
                        <option value="all" <?= ($currentFilter == 'all' ? 'selected' : '') ?>>Semua</option>
                        <option value="finished" <?= ($currentFilter == 'finished' ? 'selected' : '') ?>>Selesai</option>
                        <option value="unfinished" <?= ($currentFilter == 'unfinished' ? 'selected' : '') ?>>Belum Selesai</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="searchTodo" class="form-label">Pencarian</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchTodo" placeholder="Cari judul/deskripsi..." value="<?= $currentSearch ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="applyFilterAndSearch()">Cari</button>
                        <button class="btn btn-outline-danger" type="button" onclick="resetSearch()">Reset</button>
                    </div>
                </div>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Judul</th>
                        <th scope="col">Deskripsi Singkat</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tanggal Dibuat</th>
                        <th scope="col">Tindakan</th>
                    </tr>
                </thead>
                <tbody id="todoList">
                <?php if (!empty($todos)): ?>
                    <?php foreach ($todos as $i => $todo): ?>
                    <tr class="sortable-item" data-id="<?= $todo['id'] ?>">
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($todo['title']) ?></td>
                        <td><?= htmlspecialchars(substr($todo['description'] ?? '', 0, 50)) . (strlen($todo['description'] ?? '') > 50 ? '...' : '') ?></td>
                        <td>
                            <?php if ($todo['is_finished']): ?>
                                <span class="badge bg-success">Selesai</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Belum Selesai</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d F Y - H:i', strtotime($todo['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" 
                                onclick="showModalDetailTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars(addslashes($todo['title'])) ?>', '<?= htmlspecialchars(addslashes($todo['description'])) ?>', <?= $todo['is_finished'] ?>, '<?= date('d F Y H:i:s', strtotime($todo['created_at'])) ?>', '<?= date('d F Y H:i:s', strtotime($todo['updated_at'])) ?>')">
                                Detail
                            </button>
                            <button class="btn btn-sm btn-warning"
                                onclick="showModalEditTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars(addslashes($todo['title'])) ?>', '<?= htmlspecialchars(addslashes($todo['description'])) ?>', <?= $todo['is_finished'] ?>)">
                                Ubah
                            </button>
                            <button class="btn btn-sm btn-danger"
                                onclick="showModalDeleteTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars(addslashes($todo['title'])) ?>')">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data tersedia!</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="detailTodo" tabindex="-1" aria-labelledby="detailTodoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailTodoLabel">Detail Todo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Judul:</strong> <span id="detailTitle"></span></p>
                <p><strong>Deskripsi:</strong> <span id="detailDescription" class="text-wrap"></span></p>
                <p><strong>Status:</strong> <span id="detailStatus"></span></p>
                <p><strong>Dibuat Pada:</strong> <span id="detailCreatedAt"></span></p>
                <p><strong>Diperbarui Pada:</strong> <span id="detailUpdatedAt"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addTodo" tabindex="-1" aria-labelledby="addTodoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTodoLabel">Tambah Data Todo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?page=create" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inputTitle" class="form-label">Judul</label>
                        <input type="text" name="title" class="form-control" id="inputTitle"
                            placeholder="Contoh: Belajar membuat aplikasi website" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputDescription" class="form-label">Deskripsi (Opsional)</label>
                        <textarea name="description" class="form-control" id="inputDescription"
                            placeholder="Jelaskan detail todo Anda"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editTodo" tabindex="-1" aria-labelledby="editTodoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTodoLabel">Ubah Data Todo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?page=update" method="POST">
                <input name="id" type="hidden" id="inputEditTodoId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inputEditTitle" class="form-label">Judul</label>
                        <input type="text" name="title" class="form-control" id="inputEditTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputEditDescription" class="form-label">Deskripsi (Opsional)</label>
                        <textarea name="description" class="form-control" id="inputEditDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="selectEditIsFinished" class="form-label">Status</label>
                        <select class="form-select" name="is_finished" id="selectEditIsFinished">
                            <option value="0">Belum Selesai</option>
                            <option value="1">Selesai</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteTodo" tabindex="-1" aria-labelledby="deleteTodoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTodoLabel">Hapus Data Todo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    Kamu akan menghapus todo <strong class="text-danger" id="deleteTodoTitle"></strong>.
                    Apakah kamu yakin?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a id="btnDeleteTodo" class="btn btn-danger">Ya, Tetap Hapus</a>
            </div>
        </div>
    </div>
</div>

<script src="/assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
/**
 * Fungsi untuk menampilkan modal detail todo.
 */
function showModalDetailTodo(id, title, description, isFinished, createdAt, updatedAt) {
    document.getElementById("detailTitle").innerText = title;
    document.getElementById("detailDescription").innerText = description;
    document.getElementById("detailStatus").innerText = isFinished ? 'Selesai' : 'Belum Selesai';
    document.getElementById("detailCreatedAt").innerText = createdAt;
    document.getElementById("detailUpdatedAt").innerText = updatedAt;

    var myModal = new bootstrap.Modal(document.getElementById("detailTodo"));
    myModal.show();
}

/**
 * Fungsi untuk menampilkan modal edit todo.
 */
function showModalEditTodo(todoId, title, description, isFinished) {
    document.getElementById("inputEditTodoId").value = todoId;
    document.getElementById("inputEditTitle").value = title;
    document.getElementById("inputEditDescription").value = description;
    document.getElementById("selectEditIsFinished").value = isFinished;
    var myModal = new bootstrap.Modal(document.getElementById("editTodo"));
    myModal.show();
}

/**
 * Fungsi untuk menampilkan modal hapus todo.
 */
function showModalDeleteTodo(todoId, title) {
    document.getElementById("deleteTodoTitle").innerText = title;
    document.getElementById("btnDeleteTodo").setAttribute("href", `?page=delete&id=${todoId}`);
    var myModal = new bootstrap.Modal(document.getElementById("deleteTodo"));
    myModal.show();
}

/**
 * Fungsi untuk menerapkan filter dan pencarian.
 */
function applyFilterAndSearch() {
    const filter = document.getElementById('filterStatus').value;
    const search = document.getElementById('searchTodo').value;
    let url = 'index.php';
    const params = [];
    
    if (filter !== 'all') {
        params.push(`filter=${filter}`);
    }
    if (search.trim() !== '') {
        params.push(`search=${encodeURIComponent(search.trim())}`);
    }
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    window.location.href = url;
}

/**
 * Fungsi untuk mereset pencarian dan filter.
 */
function resetSearch() {
    window.location.href = 'index.php';
}

/**
 * Inisialisasi SortableJS dan pengiriman urutan ke server.
 */
document.addEventListener('DOMContentLoaded', (event) => {
    const todoList = document.getElementById('todoList');

    if (todoList) {
        new Sortable(todoList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                // Dapatkan ID dalam urutan baru
                const itemEls = evt.from.children;
                const newOrder = [];
                for (let i = 0; i < itemEls.length; i++) {
                    newOrder.push(itemEls[i].getAttribute('data-id'));
                }

                // Kirim urutan baru ke server
                saveSortOrder(newOrder);
            },
        });
    }
});

/**
 * Kirim permintaan AJAX untuk menyimpan urutan baru.
 * @param {Array<string>} todoIds Array of todo IDs in new order.
 */
function saveSortOrder(todoIds) {
    fetch('?page=sort', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'todo_ids[]=' + todoIds.join('&todo_ids[]=')
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Gagal menyimpan urutan di server.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Perbarui penomoran # di tampilan
            const rows = document.getElementById('todoList').children;
            for (let i = 0; i < rows.length; i++) {
                rows[i].querySelector('td:first-child').innerText = i + 1;
            }
            console.log('Urutan berhasil disimpan.');
        } else {
            alert('Gagal menyimpan urutan: ' + (data.message || 'Kesalahan server.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan urutan.');
    });
}
</script>
</body>
</html>