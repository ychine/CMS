<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="src/styles.css" rel="stylesheet">
    <link href="img/cdicon.svg" rel="icon">
    <title>CourseDock</title>
</head>

<body>
    <?php 
  
    include "./src/signin.php";
    
    ?>

    <?php if (isset($_GET['registersuccess']) && $_GET['registersuccess'] == 1): ?>
   
        <div class="toast success" id="toast-success">
            Registration successful! You can now log in.
        </div>
        
        <script>
          
            setTimeout(function() {
                const toast = document.getElementById('toast-success');
                if (toast) {
                    toast.style.display = 'none';
                }
            }, 3500);
        </script>
    <?php endif; ?>
    
</body>

</html>