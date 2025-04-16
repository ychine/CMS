<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="../img/cdicon.svg" rel="icon">
    <link href="styles.css?v=1.0.1" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CourseDock</title>
   
    

</head>
<body>
    <div class="header">
        <img src="../img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>


     <div class="container">
                
                <div class="signinbox" id="signupStep1">

                    
                    <h3>Sign-up</h3>
                    <p class="subtext">Create your username and password.</p>
                    

                    <div class="tfieldname">Create your username</div> 
                    
                        <div class="tf">
                            <input type="text" placeholder="">
                        </div>
                        
                    <div class="tfieldname">Password</div> 
                    
                        <div class="tf">
                            <input type="password" placeholder="**********">
                        </div>

                    <div class="tfieldname">Confirm Password</div> 
                    
                        <div class="tf">
                            <input type="password"  placeholder="**********">
                        </div>

                    <br>
                    <button class="btnlogin" style="width: 85%;" onclick="showStep2()">Proceed to Personal Information ➜</button>

                    <br> <br>

                </div>

                
                <div class="signinbox" id="signupStep2" style="display: none;">
                    
                    <h3>Personal Information</h3>
                    <p class="subtext">Create a profile and put information.</p>
                    
                    <div class="tfieldname">Pakiayos po itohhhh</div> 
                    
                        <div class="tf">
                            <input type="text" placeholder="">
                        </div>
                        
                    
                    <div class="tfieldname">Password</div> 
                    
                        <div class="tf">
                            <input type="password" placeholder="**********">
                        </div>

                    <div class="tfieldname">Confirm Password</div> 
                    
                        <div class="tf">
                            <input type="password"  placeholder="**********">
                        </div>

                    <br>
                

                    <br> <br>

                </div>

                
           
    
            <div class="footer">
               
            <hr><br>
                © 2025 PLP - TeamOG1E. All rights reserved.
                
                <a href="#">About CourseDock</a>
                <a href="#">Contact our Support</a>

            </div>
    </div>

    <script> 
    function showStep2() {
        document.getElementById('signupStep1').style.display = 'none';
        document.getElementById('signupStep2').style.display = 'block';
    }
</script>
    
</body>
</html>




