<?php
session_start();
include 'db.php';

$msg = "";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM users
        WHERE email=?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){
        $u = mysqli_fetch_assoc($result);

        if(password_verify($password, $u['password'])){
            $_SESSION['id'] = $u['id'];
            $_SESSION['name'] = $u['name'];
            $_SESSION['role'] = $u['role'];

            mysqli_stmt_close($stmt);

            if($u['role'] === 'cashier'){
                header("Location: cashier.php");
                exit();
            }

            if($u['role'] === 'student'){
                header("Location: student.php");
                exit();
            }
        }
    }

    $msg = "Invalid email or password.";

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Service Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body{
    background:linear-gradient(135deg,#800000 0%,#500000 100%);
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
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    width:100%;
    max-width:400px;
}

.portal-box h1{
    color:#800000;
    text-align:center;
    margin-bottom:5px;
    font-size:26px;
}

.portal-box .subtitle{
    text-align:center;
    color:#777;
    font-size:14px;
    margin-bottom:25px;
}

.portal-box h2{
    color:#800000;
    text-align:center;
    margin:20px 0 15px 0;
    font-size:20px;
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

.divider{
    border-top:2px solid #eee;
    margin:25px 0;
}

.msg{
    text-align:center;
    color:#800000;
    margin-bottom:15px;
    font-weight:bold;
}

.portal-box a{
    display:block;
    text-align:center;
    color:#800000;
    text-decoration:none;
    font-weight:bold;
    margin-top:15px;
}

.portal-box a:hover{
    text-decoration:underline;
}
</style>
</head>

<body>

<div class="portal-box">

<div style="text-align:center; margin-bottom:15px;">
    <img src="picture1.png" alt="UM Tagum College Logo" style="width:200px; height:auto;">
</div>


    <h1>Student Service Portal</h1>

    <div class="subtitle">
        Login to access your account
    </div>

    <?php if($msg): ?>
        <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <h2>Login</h2>

    <form method="POST">

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
            required
        >

        <button type="submit" name="login">
            Login
        </button>

    </form>

    <div class="divider"></div>

    <a href="register.php">
        Register Student
    </a>

</div>

</body>
</html>

