<?php
// index.php

if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = 'index';
}
include (__DIR__ . '/../controllers/TodoController.php'); // Perhatikan path-nya

$todoController = new TodoController();
// Tambahkan $detailTodo jika diperlukan untuk detail langsung
$detailTodo = null; 

switch ($page) {
    case 'index':
        $todoController->index();
        break;
    case 'create':
        $todoController->create();
        break;
    case 'update':
        $todoController->update();
        break;
    case 'delete':
        $todoController->delete();
        break;
    // Tambahkan case untuk Detail
    case 'detail':
        $todoController->detail();
        break;
    // Tambahkan case untuk Sorting (AJAX)
    case 'sort':
        $todoController->sort();
        break;
    default:
        $todoController->index();
        break;
}