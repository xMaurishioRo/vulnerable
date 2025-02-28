<?php
// Configuración segura de cookies de sesión -- ¡ACTIVA ESTO MAUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU!
session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => 'vulnerable-production.up.railway.app', // Cambia esto por tu dominioo
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => false,
    'samesite' => 'Strict'
]);

session_start();
OB_start();
require 'conf.php';

// Regenerar el ID de sesión para evitar session fixation
session_regenerate_id(true);

// Función para hashear contraseñas
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

// Función para sanitizar y validar entradas
function cleanInput($input)
{
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Validación de nombre de usuario
function validateUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username);
}

// Validación de contraseña
function validatePassword($password)
{
    return strlen($password) >= 8 && strlen($password) <= 32;
}

// Verificación del token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo "Token CSRF enviado: " . $_POST['csrf_token'] . "<br>";
    // echo "Token CSRF en sesión: " . $_SESSION['csrf_token'] . "<br>";

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token inválido.");
    }
}

// Generar un nuevo token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Registro de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $user = cleanInput($_POST['username']);
    $pass = cleanInput($_POST['password']);

    if (validateUsername($user)) {
        if (validatePassword($pass)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo "El nombre de usuario ya existe.";
            } else {
                $pass = hashPassword($pass);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->bind_param('ss', $user, $pass);
                $stmt->execute();
                echo "Registro exitoso.";
            }
        } else {
            echo ("Contraseña no válida.");
        }
    } else {
        echo ("Nombre de usuario no válido.");
    }
}

// Inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = cleanInput($_POST['username']);
    $stmt = $conn->prepare("SELECT id, password, login_attempts, last_login_attempt FROM users WHERE username = ?");
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        if ($result['login_attempts'] >= 5 && (time() - strtotime($result['last_login_attempt'])) < 300) {
            die("Demasiados intentos fallidos. Inténtelo de nuevo en 5 minutos.");
        }

        if (password_verify($_POST['password'], $result['password'])) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user'] = $user;
            $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, last_login_attempt = NULL WHERE id = ?");
            $stmt->bind_param('i', $result['id']);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1, last_login_attempt = NOW() WHERE id = ?");
            $stmt->bind_param('i', $result['id']);
            $stmt->execute();
            echo "Inicio de sesión exitoso.";
        }
    } else {
        echo "Credenciales incorrectas.";
    }
}

// Cerrar sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Enviar comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $comment = cleanInput($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO comments (user_id, comment) VALUES (?, ?)");
    $stmt->bind_param('is', $user_id, $comment);
    $stmt->execute();
    echo "Comentario enviado.";
}

// Ver comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_comments'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT comment FROM comments WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($result) {
        echo "<script>alert('";
        foreach ($result as $row) {
            echo htmlspecialchars($row['comment']) . "\\n";
        }
        echo "');</script>";
    } else {
        echo "<script>alert('No hay comentarios.');</script>";
    }
}
OB_end_flush();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/images/2165674.png">
    <title>ÑO</title>
    <style>
        @import url(https://fonts.googleapis.com/css?family=Share+Tech+Mono);

        .image-replacement {
            text-indent: 100%;
            white-space: nowrap;
            overflow: hidden
        }

        span.amp {
            font-family: Baskerville, 'Goudy Old Style', Palatino, 'Book Antiqua', serif !important;
            font-style: italic
        }



        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        body {
            color: #db0e15;
            font-family: 'Share Tech Mono', monospace;
            font-size: 26px;
            font-weight: 300;
            text-shadow: 0 0 5px rgba(219, 14, 21, .8);
            background: url(https://image.ibb.co/h2hLAJ/bg.png) #000;
            position: relative;
            height: 100vh
        }

        .container {
            left: 0;
            right: 0;
            position: absolute;
            top: 50%;
            -webkit-transform: translate(0, -50%);
            transform: translate(0, -50%)
        }

        .container.is-success {
            -webkit-filter: hue-rotate(180deg) brightness(2.7);
            filter: hue-rotate(180deg) brightness(2.7)
        }

        .screen::after {
            content: '';
            display: block;
            background: url(https://image.ibb.co/dbSZLJ/bottom.png) no-repeat center;
            margin: 0 auto;
            width: 100%;
            max-width: 500px;
            height: 28px
        }

        .title {
            text-transform: uppercase;
            text-align: center;
            font-size: 1em;
            font-weight: 300
        }

        .title::after,
        .title::before {
            content: '';
            display: inline-block;
            background: url(https://image.ibb.co/gpxZLJ/top_outer.png);
            width: 144px;
            height: 20px;
            margin: 0 20px
        }

        .container:not(.is-success) .title.is-success {
            color: #22edfc
        }

        .box--outer {
            position: relative;
            margin: 0 auto;
            width: 90%;
            max-width: 1080px;
            border-top: #000 18px solid;
            border-bottom: #000 18px solid;
            -o-border-image: url(https://image.ibb.co/kHHeny/hor_line.png) 17 11 17 round;
            border-image: url(https://image.ibb.co/kHHeny/hor_line.png) 17 11 17 round
        }

        .box {
            display: inline-block;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
            max-width: 1080px
        }

        .box--inner {
            display: inline-block;
            width: calc(100% - 105px);
            max-width: 1010px
        }

        .box--inner::after,
        .box--inner::before {
            content: '';
            display: inline-block;
            background: url(https://image.ibb.co/kvJfud/box_inner.png) no-repeat center;
            max-width: 642px;
            width: 100%;
            height: 27px
        }

        .content {
            position: relative;
            display: block;
            max-height: 600px;
            min-height: 400px;
            height: 100%
        }

        .content .holder {
            left: 0;
            right: 0;
            position: absolute;
            top: 50%;
            -webkit-transform: translate(0, -50%);
            transform: translate(0, -50%)
        }

        .col.col__center,
        .col.col__left {
            display: inline-block
        }

        .col.col__left {
            width: 130px
        }

        .col.col__center {
            width: 350px;
            margin-right: 130px
        }

        .label::after {
            content: ':';
            margin-left: -15px;
            display: inline-block
        }

        #email,
        #login,
        #password {
            border: 2px solid #d7001e;
            margin: 10px 0;
            padding: 10px;
            width: auto;
            max-width: 100%;
            overflow: visible;
            outline: 0;
            background: 0 0;
            color: inherit;
            font: inherit;
            line-height: normal
        }

        #submitButton {
            border: 2px solid #d7001e;
            margin: 10px 0;
            padding: 10px;
            width: auto;
            max-width: 100%;
            overflow: visible;
            outline: 0;
            background: 0 0;
            color: inherit;
            font: inherit;
            line-height: normal
        }

        #commets {
            border: 2px solid #d7001e;
            margin: 10px 0;
            padding: 10px;
            width: auto;
            max-width: 100%;
            overflow: visible;
            outline: 0;
            background: 0 0;
            color: inherit;
            font: inherit;
            line-height: normal
        }

        #email::-moz-selection,
        #login::-moz-selection,
        #password::-moz-selection {
            background: #000
        }

        #email::selection,
        #login::selection,
        #password::selection {
            background: #000
        }

        #submit {
            border: none;
            margin: 20px;
            padding: 10px 40px;
            width: auto;
            overflow: visible;
            outline: 0;
            cursor: pointer;
            background: 0 0;
            color: inherit;
            font: inherit;
            line-height: normal;
            -webkit-font-smoothing: inherit;
            -moz-osx-font-smoothing: inherit;
            -webkit-appearance: none;
            background: rgba(219, 14, 21, .2);
            text-transform: uppercase
        }

        @media only screen and (max-width:1260px) {

            .title::after,
            .title::before {
                width: 42px;
                height: 18px
            }

            .col.col__center,
            .col.col__left {
                display: block;
                width: auto;
                margin: 0
            }

            .box--outer::after,
            .box--outer::before {
                display: none
            }

            .title::after,
            .title::before {
                margin: 0 5px
            }

            .content {
                height: 440px
            }

            .box::after,
            .box::before {
                height: 500px
            }

            #submit {
                margin-bottom: 0
            }
        }

        @media only screen and (max-width:600px) {
            .container {
                font-size: .8em
            }

            .title::after,
            .title::before {
                width: 4%;
                height: 18px
            }

            .box--outer {
                -o-border-image: url(https://image.ibb.co/kHHeny/hor_line.png) 17 330 17 round;
                border-image: url(https://image.ibb.co/kHHeny/hor_line.png) 17 330 17 round
            }

            .box::after,
            .box::before {
                width: 22px;
                /* background: url(ver-line-mobile.png) no-repeat center; */
                background-color: yellow  no-repeat center;
                background-size: contain
            }

            .box--inner {
                width: calc(100% - 44px)
            }

            #email,
            #login,
            #password {
                max-width: 70%
            }

            .screen::after {
                max-width: 180px
            }
        }

        .flash {
            -webkit-animation: flashText 1s ease-out alternate infinite;
            animation: flashText 1s ease-out alternate infinite
        }

        @-webkit-keyframes flashText {
            0% {
                opacity: .3
            }

            100% {
                opacity: 1
            }
        }

        @keyframes flashText {
            0% {
                opacity: .3
            }

            100% {
                opacity: 1
            }
        }

        .typewriter .typewriter-line {
            visibility: hidden
        }

        @-webkit-keyframes flicker {
            0% {
                opacity: .86139
            }

            5% {
                opacity: .12793
            }

            10% {
                opacity: .36759
            }

            15% {
                opacity: .9766
            }

            20% {
                opacity: .61364
            }

            25% {
                opacity: .94477
            }

            30% {
                opacity: .57811
            }

            35% {
                opacity: .03416
            }

            40% {
                opacity: .21835
            }

            45% {
                opacity: .62054
            }

            50% {
                opacity: .89452
            }

            55% {
                opacity: .89997
            }

            60% {
                opacity: .37872
            }

            65% {
                opacity: .04929
            }

            70% {
                opacity: .14477
            }

            75% {
                opacity: .27512
            }

            80% {
                opacity: .84701
            }

            85% {
                opacity: .85952
            }

            90% {
                opacity: .76553
            }

            95% {
                opacity: .91372
            }

            100% {
                opacity: .05536
            }
        }

        @keyframes flicker {
            0% {
                opacity: .86139
            }

            5% {
                opacity: .12793
            }

            10% {
                opacity: .36759
            }

            15% {
                opacity: .9766
            }

            20% {
                opacity: .61364
            }

            25% {
                opacity: .94477
            }

            30% {
                opacity: .57811
            }

            35% {
                opacity: .03416
            }

            40% {
                opacity: .21835
            }

            45% {
                opacity: .62054
            }

            50% {
                opacity: .89452
            }

            55% {
                opacity: .89997
            }

            60% {
                opacity: .37872
            }

            65% {
                opacity: .04929
            }

            70% {
                opacity: .14477
            }

            75% {
                opacity: .27512
            }

            80% {
                opacity: .84701
            }

            85% {
                opacity: .85952
            }

            90% {
                opacity: .76553
            }

            95% {
                opacity: .91372
            }

            100% {
                opacity: .05536
            }
        }

        @-webkit-keyframes steady {
            from {
                background: rgba(255, 230, 230, .1)
            }

            to {
                background: rgba(49, 45, 45, .1)
            }
        }

        @keyframes steady {
            from {
                background: rgba(255, 230, 230, .1)
            }

            to {
                background: rgba(49, 45, 45, .1)
            }
        }

        body {
            position: relative;
            overflow: hidden
        }

        body::after {
            content: " ";
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            z-index: 2;
            pointer-events: none;
            background: rgba(49, 45, 45, .1);
            opacity: 0;
            pointer-events: none;
            -webkit-animation: flicker .15s infinite, steady 4s;
            animation: flicker .15s infinite, steady 4s
        }

        body.off {
            background: #000
        }

        body.off::after {
            -webkit-animation: none;
            animation: none
        }

        @-webkit-keyframes turn-on {
            0% {
                -webkit-transform: scale(1, .8) translate3d(0, 0, 0);
                transform: scale(1, .8) translate3d(0, 0, 0);
                opacity: 1
            }

            5% {
                -webkit-transform: scale(1.09139, 1.34235) translate3d(0, 100%, 0);
                transform: scale(1.09139, 1.34235) translate3d(0, 100%, 0)
            }

            10% {
                -webkit-transform: scale(.63084, 1.40698) translate3d(0, -100%, 0);
                transform: scale(.63084, 1.40698) translate3d(0, -100%, 0)
            }

            15% {
                -webkit-transform: scale(.75142, 1.34118) translate3d(0, 100%, 0);
                transform: scale(.75142, 1.34118) translate3d(0, 100%, 0)
            }

            20% {
                -webkit-transform: scale(.96173, 1.36858) translate3d(0, -100%, 0);
                transform: scale(.96173, 1.36858) translate3d(0, -100%, 0)
            }

            25% {
                -webkit-transform: scale(1.1348, 1.02729) translate3d(0, 100%, 0);
                transform: scale(1.1348, 1.02729) translate3d(0, 100%, 0)
            }

            30% {
                -webkit-transform: scale(.97038, .71092) translate3d(0, -100%, 0);
                transform: scale(.97038, .71092) translate3d(0, -100%, 0)
            }

            35% {
                -webkit-transform: scale(.6067, 1.31101) translate3d(0, 100%, 0);
                transform: scale(.6067, 1.31101) translate3d(0, 100%, 0)
            }

            40% {
                -webkit-transform: scale(.786, .9326) translate3d(0, -100%, 0);
                transform: scale(.786, .9326) translate3d(0, -100%, 0)
            }

            45% {
                -webkit-transform: scale(1.38602, 1.14399) translate3d(0, 100%, 0);
                transform: scale(1.38602, 1.14399) translate3d(0, 100%, 0)
            }

            50% {
                -webkit-transform: scale(.69973, .90412) translate3d(0, -100%, 0);
                transform: scale(.69973, .90412) translate3d(0, -100%, 0)
            }

            51% {
                -webkit-transform: scale(1, 1) translate3d(0, 0, 0);
                transform: scale(1, 1) translate3d(0, 0, 0);
                -webkit-filter: brightness(1) saturate(1);
                filter: brightness(1) saturate(1);
                opacity: 0
            }

            100% {
                -webkit-transform: scale(1, 1) translate3d(0, 0, 0);
                transform: scale(1, 1) translate3d(0, 0, 0);
                -webkit-filter: contrast(1) brightness(1.2) saturate(1.3);
                filter: contrast(1) brightness(1.2) saturate(1.3);
                opacity: 1
            }
        }

        @keyframes turn-on {
            0% {
                -webkit-transform: scale(1, .8) translate3d(0, 0, 0);
                transform: scale(1, .8) translate3d(0, 0, 0);
                opacity: 1
            }

            5% {
                -webkit-transform: scale(1.09139, 1.34235) translate3d(0, 100%, 0);
                transform: scale(1.09139, 1.34235) translate3d(0, 100%, 0)
            }

            10% {
                -webkit-transform: scale(.63084, 1.40698) translate3d(0, -100%, 0);
                transform: scale(.63084, 1.40698) translate3d(0, -100%, 0)
            }

            15% {
                -webkit-transform: scale(.75142, 1.34118) translate3d(0, 100%, 0);
                transform: scale(.75142, 1.34118) translate3d(0, 100%, 0)
            }

            20% {
                -webkit-transform: scale(.96173, 1.36858) translate3d(0, -100%, 0);
                transform: scale(.96173, 1.36858) translate3d(0, -100%, 0)
            }

            25% {
                -webkit-transform: scale(1.1348, 1.02729) translate3d(0, 100%, 0);
                transform: scale(1.1348, 1.02729) translate3d(0, 100%, 0)
            }

            30% {
                -webkit-transform: scale(.97038, .71092) translate3d(0, -100%, 0);
                transform: scale(.97038, .71092) translate3d(0, -100%, 0)
            }

            35% {
                -webkit-transform: scale(.6067, 1.31101) translate3d(0, 100%, 0);
                transform: scale(.6067, 1.31101) translate3d(0, 100%, 0)
            }

            40% {
                -webkit-transform: scale(.786, .9326) translate3d(0, -100%, 0);
                transform: scale(.786, .9326) translate3d(0, -100%, 0)
            }

            45% {
                -webkit-transform: scale(1.38602, 1.14399) translate3d(0, 100%, 0);
                transform: scale(1.38602, 1.14399) translate3d(0, 100%, 0)
            }

            50% {
                -webkit-transform: scale(.69973, .90412) translate3d(0, -100%, 0);
                transform: scale(.69973, .90412) translate3d(0, -100%, 0)
            }

            51% {
                -webkit-transform: scale(1, 1) translate3d(0, 0, 0);
                transform: scale(1, 1) translate3d(0, 0, 0);
                -webkit-filter: brightness(1) saturate(1);
                filter: brightness(1) saturate(1);
                opacity: 0
            }

            100% {
                -webkit-transform: scale(1, 1) translate3d(0, 0, 0);
                transform: scale(1, 1) translate3d(0, 0, 0);
                -webkit-filter: contrast(1) brightness(1.2) saturate(1.3);
                filter: contrast(1) brightness(1.2) saturate(1.3);
                opacity: 1
            }
        }

        @-webkit-keyframes turn-off {
            0% {
                -webkit-transform: scale(1, 1.3) translate3d(0, 0, 0);
                transform: scale(1, 1.3) translate3d(0, 0, 0);
                -webkit-filter: brightness(1);
                filter: brightness(1);
                opacity: 1
            }

            60% {
                -webkit-transform: scale(1.3, .001) translate3d(0, 0, 0);
                transform: scale(1.3, .001) translate3d(0, 0, 0);
                -webkit-filter: brightness(10);
                filter: brightness(10)
            }

            100% {
                -webkit-animation-timing-function: cubic-bezier(.755, .05, .855, .06);
                animation-timing-function: cubic-bezier(.755, .05, .855, .06);
                -webkit-transform: scale(0, .0001) translate3d(0, 0, 0);
                transform: scale(0, .0001) translate3d(0, 0, 0);
                -webkit-filter: brightness(50);
                filter: brightness(50)
            }
        }

        @keyframes turn-off {
            0% {
                -webkit-transform: scale(1, 1.3) translate3d(0, 0, 0);
                transform: scale(1, 1.3) translate3d(0, 0, 0);
                -webkit-filter: brightness(1);
                filter: brightness(1);
                opacity: 1
            }

            60% {
                -webkit-transform: scale(1.3, .001) translate3d(0, 0, 0);
                transform: scale(1.3, .001) translate3d(0, 0, 0);
                -webkit-filter: brightness(10);
                filter: brightness(10)
            }

            100% {
                -webkit-animation-timing-function: cubic-bezier(.755, .05, .855, .06);
                animation-timing-function: cubic-bezier(.755, .05, .855, .06);
                -webkit-transform: scale(0, .0001) translate3d(0, 0, 0);
                transform: scale(0, .0001) translate3d(0, 0, 0);
                -webkit-filter: brightness(50);
                filter: brightness(50)
            }
        }

        .screen {
            width: 100%;
            height: 100%;
            border: none
        }

        .container.off>.screen {
            -webkit-animation: turn-off .55s cubic-bezier(.23, 1, .32, 1);
            animation: turn-off .55s cubic-bezier(.23, 1, .32, 1);
            -webkit-animation-fill-mode: forwards;
            animation-fill-mode: forwards
        }

        .container.on>.screen {
            -webkit-animation: turn-on 2s linear;
            animation: turn-on 2s linear;
            -webkit-animation-fill-mode: forwards;
            animation-fill-mode: forwards
        }
    </style>
    <style>
        #comment {
            border: 2px solid #d7001e;
            margin: 10px 0;
            padding: 10px;
            width: auto;
            max-width: 100%;
            overflow: visible;
            outline: 0;
            background: 0 0;
            color: inherit;
            font: inherit;
            line-height: normal;
        }

        #comment::selection {
            background: #000;
        }
    </style>

    <script>
        window.console = window.console || function (t) { };
    </script>



</head>

<body translate="no" class="">
    <div class="container on">
        <div class="screen">
            <h3 class="title">CONNECTION ESTABLISHED</h3>
            <div class="box--outer">
                <div class="box">
                    <div class="box--inner">
                        <div class="content">
                            <div class="holder">
                                <?php if (isset($_SESSION['user'])): ?>
                                    <b>GG papá</b>
                                    <br>
                                    <br>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                                        <div class="col col__left label">Comments</div>
                                        <div class="col col__center">
                                            <input type="text" name="comment" id="commets" placeholder=" ">
                                        </div>
                                        <button type="submit" name="comment_submit" id="comment">Submit</button>
                                    </form>
                                    <form method="POST">
                                        <button type="submit" name="logout"
                                            style="border: none; margin: 20px; padding: 10px 40px; width: auto; overflow: visible; outline: 0; cursor: pointer; background: rgba(219, 14, 21, .2); color: inherit; font: inherit; line-height: normal; text-transform: uppercase;">Logout</button>
                                    </form>
                                    <form method="POST">
                                        <button type="submit" name="view_comments"
                                            style="border: none; margin: 20px; padding: 10px 40px; width: auto; overflow: visible; outline: 0; cursor: pointer; background: rgba(219, 14, 21, .2); color: inherit; font: inherit; line-height: normal; text-transform: uppercase;">View
                                            My Comments</button>
                                    </form>
                                <?php else: ?>
                                    <b>Welcome</b> — Please enter your credentials to access the system.
                                    <br>
                                    <br>
                                    <div class="row">
                                        <button type="button" id="toggleButton"
                                            style="border: none; margin: 20px; padding: 10px 40px; width: auto; overflow: visible; outline: 0; cursor: pointer; background: rgba(219, 14, 21, .2); color: inherit; font: inherit; line-height: normal; text-transform: uppercase;">[[Register]]</button>
                                    </div>
                                    <form method="post" id="authForm">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                        <div class="row">
                                            <div class="col col__left label">Username</div>
                                            <div class="col col__center">
                                                <input type="text" id="login" name="username" maxlength="32"
                                                    required="required" placeholder="" autocomplete="username">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col col__left label">Password</div>
                                            <div class="col col__center">
                                                <input type="password" id="password" name="password" required="required"
                                                    placeholder="" data-error="" maxlength="32" autocomplete="new-password"
                                                    autofocus="true">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <button type="submit" id="submitButton" name="login">[Login]</button>
                                        </div>
                                    </form>
                                    <script>
                                        document.getElementById('toggleButton').addEventListener('click', function () {
                                            var submitButton = document.getElementById('submitButton');
                                            var authForm = document.getElementById('authForm');
                                            if (submitButton.name === 'login') {
                                                submitButton.name = 'register';
                                                submitButton.textContent = '[Register]';
                                                this.textContent = 'Login';
                                            } else {
                                                submitButton.name = 'login';
                                                submitButton.textContent = '[Login]';
                                                this.textContent = 'Register';
                                            }
                                        });
                                    </script>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>