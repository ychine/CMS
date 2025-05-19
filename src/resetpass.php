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
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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

        <form method="post" action="scripts/send-password-reset.php" id="resetForm">
            <div class="signinbox">
            <h3 class="registration-title">
                        <a href="../index.php" class="back-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                        Account Recovery
                    </h3>

                <hr>
                <div class="tfieldname">Enter your email address.</div>
                <div class="tf">
                    <input type="email" id="email" name="email">
                </div>
                <p class="subtext">A code will be sent to your email address.</p>

                <button class="btnlogin" type="submit">Send Code</button>
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
            © 2025 CourseDock. All rights reserved.
            <a href="../src/about.php">About CourseDock</a>
            <a href="#">Contact our Support</a>
        </div>
    </div>

    <script>
    const form = document.getElementById('resetForm');
    const emailField = document.getElementById('email');

    form.addEventListener('submit', function(e) {
        const email = emailField.value.trim();

        if (email === '') {
            e.preventDefault();

            
            emailField.classList.add('error-border');

           
            const toast = document.createElement('div');
            toast.className = 'toast error';
            toast.innerText = 'Please enter your email address.';
            document.body.appendChild(toast);

            
            setTimeout(() => {
                toast.remove();
            }, 3000);

            return;
        }
    });

    
    emailField.addEventListener('input', () => {
        if (emailField.value.trim() !== '') {
            emailField.classList.remove('error-border');
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'emailnotfound') {
        const toast = document.createElement('div');
        toast.className = 'toast error';
        toast.innerText = 'Email address not found.';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    if (urlParams.get('success') === 'emailsent') {
    const toast = document.createElement('div');
    toast.className = 'toast success';
    toast.innerText = 'Password reset email sent!';
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
    }
    </script>

</body>
</html>