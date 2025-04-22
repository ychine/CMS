<?php

$toastMessage = "";
$toastType = "";

$mysqli = new mysqli("localhost", "root", "", "cms");

if ($mysqli->connect_error) {
    $toastMessage = "❌ Failed to connect to database.";
    $toastType = "error";
} else {
    $toastMessage = "✅ Successfully connected to database.";
    $toastType = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password | CourseDock</title>
    <link href="../img/cdicon.svg" rel="icon">
    <link href="styles.css?v=1.0" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">


    <style>
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            color: #fff;
            border-radius: 10px;
            font-family: 'Onest', sans-serif;
            font-size: 14px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadein 0.5s, fadeout 0.5s 3s;
        }

        .toast.success {
            background-color: #28a745;
        }

        .toast.error {
            background-color: #dc3545;
        }

        @keyframes fadein {
            from { opacity: 0; bottom: 20px; }
            to { opacity: 1; bottom: 30px; }
        }

        @keyframes fadeout {
            from { opacity: 1; bottom: 30px; }
            to { opacity: 0; bottom: 20px; }
        }

    </style>
</head>
<body>
    <div class="header">
        <img src="../img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>
    

     <div class="container">
            <?php if (!empty($toastMessage)) : ?>
                    <div class="toast <?php echo $toastType; ?>">
                        <?php echo $toastMessage; ?>
                    </div>
            <?php endif; ?>

            <form>
                <div class="signinbox">
                   <h3>Account recovery</h3>

                    <hr>
                  
                    <div class="tfieldname">Enter your email address.</div>
                    <div class="tf">
                        <input type="text" id="emailrecover">
                    </div>
                    <p class="subtext">A code will be sent to your email address.</p>
                
                    <button class="btnlogin" onclick="">Send Code</button>
                    <br><br>
                    
                
            </div>
               
            </form>
            <br>
            <div class="signinbox">
                <div class="new-user-line">
                    <span class="newt">New to CourseDock?</span>
                    <span class="reglink"><a href="./register.php">Register here.</a></span>
                </div>
            </div>
            <div class="footer">
               
            <hr><br>
                © 2025 PLP - TeamOG1E. All rights reserved.
                
                <a href="#">About CourseDock</a>
                <a href="#">Contact our Support</a>

            </div>
     </div>
    
    



    
</body>
</html>




