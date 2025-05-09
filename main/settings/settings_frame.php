<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f3f4f6;
      color: #222;
      font-family: 'Inter', sans-serif;
      transition: background 0.3s, color 0.3s;
    }
    .settings-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      padding: 2rem 2.5rem;
      max-width: 400px;
      margin: 3rem auto;
      text-align: center;
    }
    .toggle-switch {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      margin: 2rem 0 0 0;
    }
    .switch {
      position: relative;
      display: inline-block;
      width: 48px;
      height: 26px;
    }
    .switch input { display: none; }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background: #d1d5db;
      border-radius: 26px;
      transition: background 0.3s;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 20px;
      width: 20px;
      left: 3px;
      bottom: 3px;
      background: #fff;
      border-radius: 50%;
      transition: transform 0.3s;
      box-shadow: 0 1px 4px rgba(0,0,0,0.12);
    }
    input:checked + .slider {
      background: #2563eb;
    }
    input:checked + .slider:before {
      transform: translateX(22px);
    }
    /* Dark mode styles */
    body.dark {
      background: #18181b;
      color: #f3f4f6;
    }
    .dark .settings-card {
      background: #23232a;
      color: #f3f4f6;
      box-shadow: 0 4px 24px rgba(0,0,0,0.32);
    }
    .dark .slider {
      background: #374151;
    }
    .dark input:checked + .slider {
      background: #60a5fa;
    }
  </style>
</head>
<body>
  <div class="settings-card">
    <h2 class="text-xl font-bold mb-4">Settings</h2>
    <div class="toggle-switch">
      <span><i class="fas fa-sun"></i></span>
      <label class="switch">
        <input type="checkbox" id="darkModeToggle">
        <span class="slider"></span>
      </label>
      <span><i class="fas fa-moon"></i></span>
    </div>
    <div class="mt-4 text-sm text-gray-500">Toggle dark mode for the site.</div>
  </div>
  <script>
    // On load, set dark mode if previously chosen
    if (localStorage.getItem('darkMode') === 'enabled') {
      document.body.classList.add('dark');
      document.getElementById('darkModeToggle').checked = true;
    }
    document.getElementById('darkModeToggle').addEventListener('change', function() {
      if (this.checked) {
        document.body.classList.add('dark');
        localStorage.setItem('darkMode', 'enabled');
      } else {
        document.body.classList.remove('dark');
        localStorage.setItem('darkMode', 'disabled');
      }
    });
  </script>
</body>
</html>
