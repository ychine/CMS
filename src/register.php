
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

    
     <div class="container">


            <form action="testform.php" method="POST">
                    <div class="signinbox" id="signupStep1">
                    
                        
                    <h3>Registration</h3>

                        <p class="subtext">Create your username and password.</p>

                        <hr>

                        <div class="tfieldname">Create your username</div> 
                        
                            <div class="tf">
                                <input type="text" name="username" placeholder="" id = "username" >
                            </div>
                            
                        <div class="tfieldname">Password</div> 
                        
                            <div class="tf">
                                <input type="password" name="password" placeholder="**********" id="password" >
                            </div>

                        <div class="tfieldname">Confirm Password</div> 
                        
                            <div class="tf">
                                <input type="password"  name="confirmpass"  placeholder="**********" id="confirmpass" >
                            </div>

                        <br>
                        <button class="btnlogin"  type = "button"  onclick="validations()">Proceed to Personal Information ➜</button>

                        <br> <br>

                    </div>
                

                    <div class="signinbox" id="signupStep2" style="display: none;">

                        <h3 class="registration-title">
                        <a href="#" class="back-arrow" onclick="goBackStep1(event)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                        Personal Information
                    </h3>
                        <p class="subtext">Create a profile and put information.</p>
                        <hr>
                        
                    <div class="fieldrow aligned-fields">
                        <div style="width: 50%;">
                        <div class="tfieldname">First Name</div> 
                            <div class="tf">
                                <input type="text" name="fname" placeholder="JUAN" id = "fname">
                            </div>
                        </div>

                        <div style="width: 50%;">
                            <div class="tfieldname">Surname</div>   
                            <div class="tf">
                                <input type="text" name="lname" placeholder="DELA CRUZ"id = "lname">
                            </div>
                        </div>
                    </div>  

                    
               
                    <div class="tfieldname">Email Address</div> 

                        <div class="tf">
                            <input type="text" name="email" placeholder="example@mail.com" id = "email">
                        </div>

                    <div class="tfieldname">Gender</div>
                    
                <div class="gender-options">

                    <label class="radio-wrap">
                        <input type="radio" name="gender" value="Male"  />
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
                <br>
            
                <button class="btnlogin" type="submit">Submit</button>
                    <br><br>
            
                </div>
            </form> 
           

                
            <br>
            
            <div class="signinbox">
                <div class="new-user-line">
                    <span class="newt">I already have an Account.</span>
                    <span class="reglink"><a href="../index.php">Log In</a></span>
                </div>
            </div>

            <div class="footer">
               
            <hr><br>
                © 2025 CourseDock. All rights reserved.
                
                <a href="../src/about.php">About CourseDock</a>
                <a href="#">Contact our Support</a>


            </div>
    </div>


    


    <script>

        function goBackStep1(event) {
            event.preventDefault();
            document.getElementById('signupStep2').style.display = 'none';
            document.getElementById('signupStep1').style.display = 'block';
        }
       
        function showToast(message, type = 'error') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerText = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3500);
        }

       
                    function validations() {
                var username = document.getElementById("username").value;
                var password = document.getElementById("password").value;
                var confirmPassword = document.getElementById("confirmpass").value;

                document.getElementById("username").classList.remove('error-border');
                document.getElementById("password").classList.remove('error-border');
                document.getElementById("confirmpass").classList.remove('error-border');

                var errorMessage = '';
                var isValid = true;

                if (username === "") {
                    document.getElementById("username").classList.add('error-border');
                    isValid = false;
                    errorMessage = 'Please fill in all fields correctly.'; 
                }

                
                if (isValid) {

                    if (password === "") {
                        document.getElementById("password").classList.add('error-border');
                        isValid = false;
                        errorMessage = 'Please fill in all fields correctly.'; 
                    }

                    
                    if (confirmPassword === "") {
                        document.getElementById("confirmpass").classList.add('error-border');
                        isValid = false;
                        errorMessage = 'Please fill in all fields correctly.'; 
                    }

                    
                    if (password !== confirmPassword) {
                        document.getElementById("password").classList.add('error-border');
                        document.getElementById("confirmpass").classList.add('error-border');
                        isValid = false;
                        errorMessage = "Passwords do not match! Please try again."; 
                    }
                }

                
                if (isValid) {
                    document.getElementById('signupStep1').style.display = 'none';
                    document.getElementById('signupStep2').style.display = 'block';
                } else {
                    showToast(errorMessage);
                }

                            document.querySelector("form").addEventListener("submit", function (e) {
                    let isValidStep2 = true;

                    const fname = document.getElementById("fname");
                    const lname = document.getElementById("lname");
                    const email = document.getElementById("email");
                    const gender = document.querySelector('input[name="gender"]:checked');
                    const genderOptions = document.querySelector(".gender-options");

                    [fname, lname, email].forEach(field => {
                        field.classList.remove('error-border');
                        if (field.value.trim() === "") {
                            field.classList.add('error-border');
                            isValidStep2 = false;
                        }
                    });

                    
                    

                    if (!gender) {
                        isValidStep2 = false;

                        genderOptions.classList.add("shake");

                        setTimeout(() => {
                            genderOptions.classList.remove("shake");
                        }, 400);
                    }

                    if (!isValidStep2) {
                        e.preventDefault();
                        showToast("Please fill in all fields correctly.");
                    }
                });

                


                document.querySelector('input[name="username"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                document.querySelector('input[name="password"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                document.querySelector('input[name="confirmpass"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                document.querySelector('input[name="fname"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                document.querySelector('input[name="lname"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                document.querySelector('input[name="email"]').addEventListener('input', function() {
                    if (this.classList.contains('error-border')) {
                        this.classList.remove('error-border');
                    }
                });

                
            }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const success = urlParams.get('success');

            if (error) {
                showToast(error, 'error');
            }

            if (success) {
                showToast(success, 'success');
            }
        });
        
    </script>
    
</body>
</html>