<?php
$hash = '';
$str = filter_input(INPUT_POST, 'str');
$salt = filter_input(INPUT_POST, 'salt');
if (!empty($str) && !empty($salt)) {
    $hash = crypt($str, $salt);
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        exit($hash);
    }
}
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Generate a password hash</title>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    </head>
    <body>
        <div class="container">
            <form method="POST" role="form" id="generator">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <input type="password" name="str" class="form-control" placeholder="Password" required>
                        <div class="input-group">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    Type <span class="caret"></span>
                                </button>
                                <ul id="salt-select" class="dropdown-menu">
                                    <li><a href="#" data-bind="">CRYPT_DES</a></li>
                                    <li><a href="#" data-bind="$1$">CRYPT_MD5</a></li>
                                    <li><a href="#" data-bind="$2a$07$">CRYPT_BLOWFISH</a></li>
                                    <li><a href="#" data-bind="$5$">CRYPT_SHA256</a></li>
                                    <li><a href="#" data-bind="$6$">CRYPT_SHA512</a></li>
                                </ul>
                            </div>
                            <input type="text" name="salt" class="form-control" placeholder="Salt" required>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-lg btn-primary">
                            Generate
                        </button>
                        <button type="reset" class="btn btn-lg btn-default">
                            Reset
                        </button>
                    </div>
                </div>
            </form>
            <div class="alert alert-success">
                <span class="glyphicon glyphicon-info-sign"></span>
                <strong>Your ADMIN_HASH</strong>:
                <span id="myhash"><?php echo $hash; ?></span>
            </div>
        </div>

        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        <script type="text/javascript">
        $(function() {
            $("#salt-select > li > a").click(function() {
                $("#generator input[name='salt']")
                    .focus()
                    .val($(this).attr('data-bind'));
            });

            $('#generator').submit(function() {
                $.ajax({
                    type: "POST",
                    data: $("#generator").serialize(),
                    success: function(data) {
                        $('#myhash').html(data);
                    }
                });
                return false;
            });
        });
        </script>
    </body>
</html>
