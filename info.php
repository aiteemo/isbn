<?php
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('PRC');
}
define('BASEDIR', __DIR__);
define('DEBUG', false);
if (file_exists('/tmp/teemo/push.conf.lock')) {
    include_once BASEDIR . '/App/Config.php';
} else {
    include_once BASEDIR . '/App/ConfigTest.php';
}
include BASEDIR . '/App/Loader.php';
include BASEDIR . '/App/Common.php';
spl_autoload_register('\\Imooc\\Loader::autoload');

$m = M();

session_start();
if (!$_SESSION['info']) {
    header("Location:index.php");
}
$info = $_SESSION['info'];
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>AiTeemo ISBN</title>
    <meta name="keywords" content="isbn,isbn查询 isbn api,开源isbn">
    <meta name="description" content="致力于提供开源的ISBN查询服务"/>
</head>
<body>
<div class="container">
    <div class="py-5 text-center">
        <div><img class="d-block mx-auto mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt=""
                  width="72" height="72"></div>
        <h2>ISBN 查询结果</h2>
        <p class="lead">如果有误!请您反馈给我们,我们校对之后会将您反馈的信息录入ISBN库</p>
    </div>

    <div class="row">
        <div class="col-md-12 order-md-1">
            <img class="mr-3" width="50px;" src="<?php echo $info['photo_url']; ?>" alt="Generic placeholder image">

            <h4 class="mb-3"><?php echo $info['book_name']; ?></h4>
            <form class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ISBN">ISBN</label>
                        <input type="text" class="form-control" id="ISBN" placeholder=""
                               value="<?php echo $info['isbn']; ?>" required>
                        <div class="invalid-feedback">
                            Valid first name is required.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="book_name">Book name</label>
                        <input type="text" class="form-control" id="book_name" placeholder=""
                               value="<?php echo $info['book_name']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Author">Author</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@</span>
                            </div>
                            <input type="text" class="form-control" id="Author" placeholder="Author"
                                   value="<?php echo $info['author']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="Publishing">Publishing</label>
                        <input type="text" class="form-control" id="Publishing" placeholder=""
                               value="<?php echo $info['publishing']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Weight">Weight</label>
                        <input type="text" class="form-control" id="Weight" placeholder=""
                               value="<?php echo $info['weight']; ?>" required>
                        <div class="invalid-feedback">
                            Valid first name is required.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="Size">Size</label>
                        <input type="text" class="form-control" id="Size" placeholder=""
                               value="<?php echo $info['size']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Pages">Pages</label>
                        <input type="text" class="form-control" id="Pages" placeholder=""
                               value="<?php echo $info['pages']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="Price">Price</label>
                        <input type="text" class="form-control" id="Price" placeholder=""
                               value="<?php echo $info['price']; ?>" required>
                    </div>
                </div>
                <hr class="mb-4">
                <!--<button class="btn btn-primary btn-lg btn-block" type="submit">Continue to checkout</button>-->
            </form>
        </div>
    </div>
    <?php require "footer.php"; ?>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.slim.min.js"
            integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
            crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous"></script>
</body>
</html>