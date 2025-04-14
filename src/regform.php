<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CourseDock</title>
    <link href="regform.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

</head>
<body class="fade-in">

  <img src="../img/COURSEDOCK.svg" alt="CourseDock Logo" class="logo">
  <p class="subtitle">Courseware Monitoring System</p>
  <h2 class="form-title">Registration Form</h2>

  <div class="form-box">

    <div class="form-section">
      <h3>Sign-up</h3>
      <p class="subtext">Create a profile and put information.</p>

      <label>Create your username</label> 
      <input type="text" placeholder="">

      <label>Password</label>
      <input type="password" placeholder="**********">

      <label>Confirm Password</label>
      <input type="password" placeholder="**********">

      <button class="btn" style="width: 85%;">Proceed to Personal Information ➜</button>
    </div>

    <!-- Personal Info Section -->
    <div class="form-section">
      <h3>Personal Information</h3>

      <div class="row">
        <div class="field">
          <label>First name</label>
          <input type="text" placeholder="" style = "width: 100%";>
        </div>
        <div class="field">
          <label>Surname</label>
          <input type="text" placeholder="" style = "width: 100%";>
        </div>
      </div>

      <label>Email Address</label>
      <input type="email" placeholder="">

      <label>Gender</label> 
      <div class="gender-options">
  <label class="radio-wrap">
    <input type="radio" name="gender" value="Male" />
    <span class="radio-label">Male</span>
  </label>

  <label class="radio-wrap">
    <input type="radio" name="gender" value="Female" />
    <span class="radio-label">Female</span>
  </label>

  <label class="radio-wrap">
    <input type="radio" name="gender" value="Preferably not to say" />
    <span class="radio-label">Prefer not to say</span>
  </label>
</div>


      <label>Join Faculty with code?</label> 
      <div class="faculty-join">
        <input type="text" placeholder="Enter faculty code">
        <button class="btn-join"> Join ➜</button>
      </div>
    </div>
  </div>
</body>
</html>