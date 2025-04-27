<?php

$token = $_GET["token"];

$token_hash = hash("sha256", $token);

$mysqli = require __DIR__ ."/database.php";

$sql = "SELECT * FROM accounts
        WHERE reset_token_hash = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("s", $token_hash);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user === null) {
    die("token not found");
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("token has expired");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="../img/cdicon.svg" rel="icon">
    <link href="../styles.css?v=1.0" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CourseDock</title>

</head>
<body>

    <div class="header">
        <img src="../../img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>

    <div class="container">

        <form method="POST" action="process-reset-password.php">

            <div class="signinbox" id="signupStep1">

                <h3>Reset Password</h3>
                <p class="subtext">Create a new password.</p>

                <hr>

                <input type = "hidden" name = "token" value = "<?= htmlspecialchars($token)?>">
                <div class="tfieldname">New Password</div> 
                <div class="tf">
                    <input type="password" name="password" placeholder="**********" id="password">
                </div>

                <div class="tfieldname">Confirm Password</div> 
                <div class="tf">
                    <input type="password" name="password_confirm" placeholder="**********" id="password_confirm">
                </div>

                <br>
                <button class="btnlogin" type="submit">Send ➜</button>

                <br><br>

            </div> 
        </form> 

    </div> 

</body>
</html>
