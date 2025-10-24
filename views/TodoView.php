<!DOCTYPE html>
<html>
<head>
    <title>PHP - Aplikasi Todolist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .todo-item { cursor: grab; }
        .placeholder-row { background:#f8f9fa; border: 2px dashed #dee2e6; height: 60px; }
        .truncate { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
<div class="container-fluid p-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="h3">Todo List</h1>
                    <div class="text-muted small">Kelola tugas harian Anda</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTodo">Tambah</button>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <form id="filterForm" class="d-flex" method="GET" action="">
                        <input type="hidden" name="page" value="list" />
                        <select name="filter" id="filterSelect" class="form-select me-2" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= ($filter ?? 'all') === 'all' ? 'selected' : '' ?>>Semua</option>
                            <option value="finished" <?= ($filter ?? '') === 'finished' ? 'selected' : '' ?>>Selesai</option>
                            <option value="unfinished" <?= ($filter ?? '') === 'unfinished' ? 'selected' : '' ?>>Belum Selesai</option>
                        </select>
                        <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" class="form-control me-2" placeholder="Cari judul atau deskripsi">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Seret dan pindahkan untuk mengurutkan ulang. Perubahan disimpan otomatis.</small>
                </div>
            </div>

            <div id="alertPlaceholder">
                <?php if (!empty($_GET['err'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_GET['err']) ?></div>
                <?php endif; ?>
            </div>

            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Judul</th>
                        <th>Deskripsi</th>
                        <th style="width:140px">Status</th>
                        <th style="width:180px">Tanggal Dibuat</th>
                        <th style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody id="todoList">
                <?php if (!empty($todos)): ?>
                    <?php foreach ($todos as $i => $todo): ?>
                    <tr class="todo-item" data-id="<?= $todo['id'] ?>">
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($todo['title']) ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($todo['description'] ? (mb_strimwidth($todo['description'],0,80,'...') ) : '') ?></div>
                        </td>
                        <td class="truncate"><?= nl2br(htmlspecialchars($todo['description'])) ?></td>
                        <td>
                            <?php if ($todo['is_finished']): ?>
                                <span class="badge bg-success">Selesai</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Belum Selesai</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d F Y - H:i', strtotime($todo['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="showDetail(<?= $todo['id'] ?>)">Detail</button>
                            <button class="btn btn-sm btn-warning" onclick="showModalEditTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars(addslashes($todo['title'])) ?>', '<?= htmlspecialchars(addslashes($todo['description'])) ?>', <?= $todo['is_finished'] ? 1 : 0 ?>)">Ubah</button>
                            <button class="btn btn-sm btn-danger" onclick="showModalDeleteTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars(addslashes($todo['title'])) ?>')">Hapus</button>
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

<!-- MODAL ADD TODO -->
<div class="modal fade" id="addTodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="?page=create" method="POST" id="formAdd">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Todo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="text-muted small">Judul harus unik.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT TODO -->
<div class="modal fade" id="editTodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="?page=update" method="POST" id="formEdit">
                <input type="hidden" name="id" id="inputEditTodoId">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Todo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" name="title" class="form-control" id="inputEditTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" id="inputEditDescription" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_finished" id="inputEditFinished">
                        <label class="form-check-label" for="inputEditFinished">Selesai</label>
                    </div>
                    <div class="text-muted small mt-2">Judul harus unik.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DELETE TODO -->
<div class="modal fade" id="deleteTodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Todo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Kamu akan menghapus todo <strong class="text-danger" id="deleteTodoActivity"></strong>. Apakah yakin?
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a id="btnDeleteTodo" class="btn btn-danger">Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="detailTodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailTitle"></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="detailDescription"></p>
                <p class="small text-muted">Status: <span id="detailStatus"></span></p>
                <p class="small text-muted">Dibuat: <span id="detailCreated"></span></p>
                <p class="small text-muted">Diubah: <span id="detailUpdated"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function showModalEditTodo(id, title, description, is_finished) {
    document.getElementById('inputEditTodoId').value = id;
    document.getElementById('inputEditTitle').value = title || '';
    document.getElementById('inputEditDescription').value = description || '';
    document.getElementById('inputEditFinished').checked = is_finished ? true : false;
    new bootstrap.Modal(document.getElementById('editTodo')).show();
}
function showModalDeleteTodo(id, title) {
    document.getElementById('deleteTodoActivity').innerText = title;
    document.getElementById('btnDeleteTodo').setAttribute('href', '?page=delete&id=' + id);
    new bootstrap.Modal(document.getElementById('deleteTodo')).show();
}
function showDetail(id) {
    fetch('?page=detail&id=' + id)
        .then(r => r.json())
        .then data => {
            document.getElementById('detailTitle').innerText = data.title || 'Detail';
            document.getElementById('detailDescription').innerText = data.description || '(Tidak ada deskripsi)';
            document.getElementById('detailStatus').innerText = data.is_finished ? 'Selesai' : 'Belum Selesai';
            document.getElementById('detailCreated').innerText = new Date(data.created_at).toLocaleString();
            document.getElementById('detailUpdated').innerText = new Date(data.updated_at).toLocaleString();
            new bootstrap.Modal(document.getElementById('detailTodo')).show();
        });
}

// Init Sortable
const todoList = document.getElementById('todoList');
new Sortable(todoList, {
    animation: 150,
    handle: '.todo-item',
    ghostClass: 'placeholder-row',
    onEnd: function () {
        // collect ids in order
        const ids = Array.from(todoList.querySelectorAll('tr[data-id]')).map(r => r.getAttribute('data-id'));
        // send to server
        fetch('?page=reorder', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ids: ids})
        });
    }
});

</script>
</body>
</html>
