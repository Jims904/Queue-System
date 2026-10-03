<?php
session_start();
include 'db.php';

$msg = "";

if(isset($_POST['register'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if(strlen($password) < 6){
        $msg = "Password must be at least 6 characters.";
    } else {

        $stmt = mysqli_prepare($conn, "
            SELECT id
            FROM users
            WHERE email=?
            LIMIT 1
        ");

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $check = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($check) > 0){

            $msg = "Email already exists!";
            mysqli_stmt_close($stmt);

        } else {

            mysqli_stmt_close($stmt);

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = 'student';

            $stmt = mysqli_prepare($conn, "
                INSERT INTO users
                (
                    name,
                    email,
                    password,
                    role
                )
                VALUES(?,?,?,?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $email,
                $hashed_password,
                $role
            );

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            }

            $msg = "Registration failed!";
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Student Registration</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body{
    background:linear-gradient(
        135deg,
        #800000 0%,
        #500000 100%
    );

    min-height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    padding:20px;
}

.portal-box{
    background:white;
    padding:35px 30px;
    border-radius:15px;

    box-shadow:
        0 10px 30px rgba(0,0,0,0.3);

    width:100%;
    max-width:400px;
}

.portal-box h1{
    color:#800000;
    text-align:center;
    margin-bottom:5px;
    font-size:26px;
}

.subtitle{
    text-align:center;
    color:#777;
    font-size:14px;
    margin-bottom:25px;
}

.msg{
    text-align:center;
    color:#800000;
    margin-bottom:15px;
    font-weight:bold;
}

.portal-box input{
    width:100%;
    padding:12px;
    margin:8px 0;

    border:2px solid #ddd;
    border-radius:8px;

    font-size:15px;
}

.portal-box input:focus{
    border-color:#800000;
    outline:none;
}

.portal-box button{
    width:100%;
    padding:12px;

    background:#800000;
    color:white;

    border:none;
    border-radius:8px;

    font-size:16px;
    font-weight:bold;

    cursor:pointer;

    margin-top:10px;
}

.portal-box button:hover{
    background:#500000;
}

.portal-box a{
    display:block;

    text-align:center;

    color:#800000;

    text-decoration:none;

    font-weight:bold;

    margin-top:18px;
}

.portal-box a:hover{
    text-decoration:underline;
}
</style>

</head>

<body>

<div class="portal-box">

    <h1>Student Registration</h1>

    <div class="subtitle">
        Create your student account
    </div>

    <?php if($msg): ?>

        <p class="msg">
            <?php echo htmlspecialchars($msg); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="name"
            placeholder="Full Name"
            required
        >

        <input
            type="email"
            name="email"
            placeholder="Email"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            minlength="6"
            required
        >

        <button
            type="submit"
            name="register"
        >
            Register
        </button>

    </form>

    <a href="index.php">
        Back to Login
    </a>

</div>

</body>
</html>
