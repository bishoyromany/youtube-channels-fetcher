<?php
if (isset($_POST['key'])) {
    $data = ['KEY' => $_POST['key']];

    file_put_contents(__DIR__ . "/configs.json", json_encode($data));

    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
