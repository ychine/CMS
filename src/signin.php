
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sign In | CourseDock</title>
        <link href="src/styles.css?v=1.0" rel="stylesheet">
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
        </di>

        <div class="container">
        
        
                <form action="src/scripts/sampledashboard.php" method="POST" >
                    <div class="signinbox">
                        <h3>Log In to CourseDock</h3>
                        <p class="subtext">Enter your username or email and password to log in.</p> 

                        <hr>

                        <div class="tfieldname">Username or email address</div>
                        
                        <div class="tf">
                            <input type="text" id="usernamelogin" name="username" >
                        </div>
                        <div class="fieldrow">
                            <div class="tfieldname">Password</div>
                            <div class="forgotpass"><a href="src/resetpass.php">Forgot password?</a></div>
                        </div>
                        <div class="tf">
                            <input type="password" name="password" id="passwordlogin" >
                    
                        <br>

                        <button class="btnlogin" type="submit">Login</button>
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

        
        <script>
    const form = document.querySelector('form');
    const usernameField = document.getElementById('usernamelogin');
    const passwordField = document.getElementById('passwordlogin');

    form.addEventListener('submit', function(e) {
        const username = usernameField.value.trim();
        const password = passwordField.value.trim();

        if (username === '' || password === '') {
            e.preventDefault();

           
            if (username === '') usernameField.classList.add('error-border');
            if (password === '') passwordField.classList.add('error-border');

            
            const toast = document.createElement('div');
            toast.className = 'toast error';
            toast.innerText = 'Please fill in all fields.';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove(); 
            }, 3000);

            return;
        }
    });

   
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'invalid') {
        const toast = document.createElement('div');
        toast.className = 'toast error';
        toast.innerText = 'Invalid username or password.';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    usernameField.addEventListener('input', () => {
        if (usernameField.value.trim() !== '') {
            usernameField.classList.remove('error-border');
        }
    });

    passwordField.addEventListener('input', () => {
        if (passwordField.value.trim() !== '') {
            passwordField.classList.remove('error-border');
        }
    });
</script>


        



        
    </body>
    </html>




