

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CourseDock</title>
    <link href="src/styles.css?v=1.0.1" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

</head>
<body>

    <div class="header">
        <img src="./img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>

    <div class="container">
    
            <form>
                <div class="signinbox"  >
                    <h3>Login to CourseDock</h3>
                    <div class="tfieldname">Username or email address</div>
                    <div class="tf">
                        <input type="text" id="usernamelogin">
                    </div>

                    <div class="fieldrow">
                        <div class="tfieldname">Password</div>
                        <div class="forgotpass"><a href="src/resetpass.php">Forgot password?</a></div>
                    </div>
                    <div class="tf">
                        <input type="password" id="passwordlogin">
                    
                    <br><br>
                    <button class="btnlogin" onclick="">Login</button>
                    <br>
                    </div>
                </div>
            </form>
            
            <br>
            <div class="signinbox">
                <div class="new-user-line">
                    <span class="newt">New to CourseDock?</span>
                    <span class="reglink"><a href="./src/register.php">Register here.</a></span>
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




