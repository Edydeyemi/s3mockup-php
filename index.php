<?php

namespace edydeyemi\S3Test;

if (session_id() == '') {
    session_start();
}
include 'vendor/autoload.php';
include 'FileHandler.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('.env');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $files = new FileHandler;
    $upload = $files->uploadBucket('file');
    $_SESSION['files'][] = $upload['file_name'];
    $_SESSION['flash'] = [
        "msg" => $upload['msg'],
        "status" => $upload['status'],
    ];
    var_dump($upload);
    // header('Location:index.php');
}
if (isset($_SESSION['flash'])) {
    $flash_msg = $_SESSION['flash']['msg'];
    $status = $_SESSION['flash']['status'];

    //
}
if (isset($flash_msg)) {
    unset($_SESSION['flash']);
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>S3 Uploader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="mb-3 col-md-6 col-9 mt-5">
                <h3>AWS S3 Test</h3>
                <hr>
                <div class="row">
                    <?php if (isset($flash_msg)) : ?>
                        <div class="alert alert-<?= $status == 'SUCCESS' ? 'success' : 'danger'; ?> mb-3" role="alert">
                            <?= $flash_msg; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="post" action="index.php" enctype="multipart/form-data">
                    <label for="exampleFormControlInput1" class="form-label">Upload File</label>
                    <input type="file" name="file" class="form-control" id="exampleFormControlInput1" required>
                    <input type="submit" class="btn btn-primary mt-5 w-100" value="Upload File">
                </form>

                <div class="row">
                    <h4 class="mt-3">Uploaded Files</h4>
                    <?php if (!empty($_SESSION['files'])) : ?>
                        <ul>
                            <?php foreach ($_SESSION['files'] as $file) : ?>
                                <li><a href="download.php?file=<?= $file; ?>" target="_blank"><?= $file; ?></a> <a href="delete.php?file=<?= $file; ?>"><i class="fa fa-trash"></i></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>

</html>