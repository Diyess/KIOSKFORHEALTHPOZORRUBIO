<?php
session_start();

include "db.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if(empty($username) || empty($password)){

        $error = "All fields are required.";

    }else{

        $stmt = $conn->prepare("
            SELECT id, username, password
            FROM admins
            WHERE username = ?
        ");

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows > 0){

            $admin = $result->fetch_assoc();

            if($password === $admin["password"]){

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];

                header("Location: admin_dashboard.php");
                exit;

            }else{

                $error = "Invalid password.";

            }

        }else{

            $error = "Admin not found.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

body{
    background: linear-gradient(135deg, #ffe4ef 0%, #ffffff 100%);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.login-box{
    width:100%;
    max-width:480px;
    background:white;
    padding:50px 45px;
    border-radius:20px;
    box-shadow:0 15px 35px rgba(236,72,153,0.15);
    border:1px solid #fbcfe8;
}

.title{
    text-align:center;
    margin-bottom:35px;
}

.title h1{
    color:#ec4899;
    font-size:36px;
    font-weight:700;
}

.title p{
    color:#64748b;
    margin-top:8px;
    font-size:18px;
    font-weight:500;
}

.input-group{
    margin-bottom:24px;
}

.input-group label{
    display:block;
    margin-bottom:8px;
    font-size:17px;
    font-weight:500;
    color:#334155;
}

.input-group input{
    width:100%;
    padding:16px;
    border:2px solid #fbcfe8;
    border-radius:12px;
    font-size:17px;
    font-family:'Poppins', sans-serif;
    transition:border-color 0.2s;
}

.input-group input:focus{
    outline:none;
    border-color:#ec4899;
}

.btn{
    width:100%;
    padding:16px;
    border:none;
    background:#ec4899;
    color:white;
    border-radius:12px;
    font-size:19px;
    font-weight:600;
    font-family:'Poppins', sans-serif;
    cursor:pointer;
    transition:background 0.2s;
}

.btn:hover{
    background:#db2777;
}

.error{
    background:#fce7f3;
    color:#db2777;
    padding:14px;
    border-radius:10px;
    margin-bottom:20px;
    font-size:16px;
    font-weight:500;
    text-align:center;
    border:1px solid #fbcfe8;
}

/* Fit comfortably on a 14" laptop screen */
@media (min-width: 1200px){
    .login-box{
        max-width:520px;
        padding:55px 50px;
    }
    .title h1{
        font-size:40px;
    }
}

@media (max-width: 480px){
    .login-box{
        padding:35px 25px;
        border-radius:16px;
    }
    .title h1{
        font-size:30px;
    }
    .title p{
        font-size:16px;
    }
    .input-group input,
    .btn{
        font-size:16px;
        padding:14px;
    }
}

</style>
</head>
<body>

<div class="login-box">

    <div class="title">
        <h1>HealthKiosk</h1>
        <p>Admin Login</p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <label>Username</label>
            <input
                type="text"
                name="username"
                required
            >
        </div>

        <div class="input-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                required
            >
        </div>

        <button type="submit" class="btn">
            Login
        </button>

    </form>

</div>

</body>
</html>