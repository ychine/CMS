<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="../img/cdicon.svg" rel="icon">
    <link href="styles.css?v=1.0" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CourseDock</title>

</head>
<body>
    <div class="header">
        <img src="../img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>

    <br><br><br><br>
     <div class="container">

                    <div class="signinbox" id="signupStep1">
                    
                        
                        <h3>Registration</h3>
                        <p class="subtext">Create your username and password.</p>

                    <hr>

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
                        <button class="btnlogin"  onclick="showStep2()">Proceed to Personal Information ➜</button>

                        <br> <br>

                    </div>
                

                    <div class="signinbox" id="signupStep2" style="display: none;">

                        <h3>Personal Information</h3>
                        <p class="subtext">Create a profile and put information.</p>
                        <hr>
                        
                    <div class="fieldrow aligned-fields">
                        <div style="width: 50%;">
                        <div class="tfieldname">First Name</div> 
                            <div class="tf">
                                <input type="text" placeholder="JUAN">
                            </div>
                        </div>

                        <div style="width: 50%;">
                            <div class="tfieldname">Surname</div>   
                            <div class="tf">
                                <input type="text" placeholder="DELA CRUZ">
                            </div>
                        </div>
                    </div>  

                    
               
                    <div class="tfieldname">Email Address</div> 

                        <div class="tf">
                            <input type="text" placeholder="example@mail.com">
                        </div>

                    <div class="tfieldname">Gender</div>
                    
                <div class="gender-options">

                    <label class="radio-wrap">
                        <input type="radio" name="gender" value="Male" />
                        <span class="radio-label">Male</span>
                    </label>

                    <label class="radio-wrap">
                        <input type="radio" name="gender" value="Female" />
                        <span class="radio-label">Female</span>
                    </label>

                </div>


                <!--
                <div class="tfieldname">Join Faculty with a Code?</div> 
                <div class="fieldrow aligned-fields">
                    <div style="width: 57%;">
                        <div class="tf">
                            <input type="text" placeholder="">
                        </div>
                    </div>

                    <div class="faculty-join">
                       <button class="btnjoin"> Join ➜</button>
                    </div>

                    
                </div> 
                --> 
                
                <br><br>
                <button class="btnlogin" onclick="">Submit</button>
                    <br><br>
                </div>
           

                
            <br>
            
            <div class="signinbox">
                <div class="new-user-line">
                    <span class="newt">I already have an Account.</span>
                    <span class="reglink"><a href="../index.php">Log In</a></span>
                </div>
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




