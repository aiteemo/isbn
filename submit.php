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

$msg = '';
$m   = M();
if ($_GET && isset($_GET['isbn'])) {
    // 是否存在
    $search = $m->query("select * from isbn WHERE isbn='" . $_GET['isbn'] . "'");
    if ($search) {
        $msg = '已经存在';
    } else {
        $arr['photo_url']  = $_GET['photo_url'];
        $arr['isbn']       = $_GET['isbn'];
        $arr['book_name']  = $_GET['book_name'];
        $arr['author']     = $_GET['author'];
        $arr['publishing'] = $_GET['publishing'];
        $arr['weight']     = $_GET['weight'];
        $arr['size']       = $_GET['size'];
        $arr['pages']      = $_GET['pages'];
        $arr['price']      = $_GET['price'];
        $arr['state']      = 2;
        $res               = $m->insert("isbn", $arr);
        $msg               = $res ? '提交成功' : '提交失败!稍后重试';
    }
}

$info = json_decode('{
    "isbn": "9787121198854",
    "book_name": "高性能MySQL",
    "author": "施瓦茨 (Baron Schwartz)",
    "publishing": "电子工业出版社",
    "asin": "B00C1W58DE",
    "weight": "1.1 Kg",
    "size": "23.2 x 17.8 x 3.6 cm",
    "pages": "764",
    "photo_url": "http://isbn.szmesoft.com/ISBN/GetBookPhoto?ID=39E1B56D7FACD2075FE9F24216D093AD",
    "price": "128.00元"
}', true);
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
    <meta name="description" content="ISBN库"/>
</head>
<body>
<div class="container">
    <div class="py-5 text-center">
        <div><img class="d-block mx-auto mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt=""
                  width="72" height="72"></div>
        <h2>ISBN 提交</h2>
        <p class="lead">感谢您的提交! 我们校对之后会将您提交的信息录入ISBN库</p>
    </div>

    <div class="row">
        <div class="col-md-12 order-md-1">
            <img class="mr-3" width="50px;" src="<?php echo $info['photo_url']; ?>" alt="Generic placeholder image">

            <h4 class="mb-3"><?php echo $info['book_name']; ?></h4>
            <form class="needs-validation" novalidate>

                <div class="row">
                    <div class="col-md-12 mb-12">
                        <label for="photo_url">图书log地址</label>
                        <input type="text" class="form-control" id="photo_url" name="photo_url" placeholder=""
                               value="<?php echo $info['photo_url']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="isbn">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" placeholder=""
                               value="<?php echo $info['isbn']; ?>" required>
                        <div class="invalid-feedback">
                            Valid first name is required.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="book_name">Book name</label>
                        <input type="text" class="form-control" id="book_name" name="book_name" placeholder=""
                               value="<?php echo $info['book_name']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="author">Author</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">@</span>
                            </div>
                            <input type="text" class="form-control" id="author" name="author" placeholder="Author"
                                   value="<?php echo $info['author']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="publishing">Publishing</label>
                        <input type="text" class="form-control" id="publishing" name="publishing" placeholder=""
                               value="<?php echo $info['publishing']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="weight">Weight</label>
                        <input type="text" class="form-control" id="weight" name="weight" placeholder=""
                               value="<?php echo $info['weight']; ?>" required>
                        <div class="invalid-feedback">
                            Valid first name is required.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="size">Size</label>
                        <input type="text" class="form-control" id="size" name="size" placeholder=""
                               value="<?php echo $info['size']; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pages">Pages</label>
                        <input type="text" class="form-control" id="pages" name="pages" placeholder=""
                               value="<?php echo $info['pages']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="price">Price</label>
                        <input type="text" class="form-control" id="price" name="price" placeholder=""
                               value="<?php echo $info['price']; ?>" required>
                    </div>
                </div>
                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">submit<?php echo $msg; ?></button>
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