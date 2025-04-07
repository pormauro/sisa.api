<?php
// reset_password_form.php

// Se espera que el token llegue como parámetro GET: ?token=...
$token = $_GET['token'] ?? '';
if (!$token) {
    die("Token no proporcionado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 100%;
      max-width: 400px;
      background: #fff;
      margin: 50px auto;
      padding: 20px;
      box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    .form-group {
      position: relative;
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-bottom: 5px;
    }
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 40px;
      cursor: pointer;
      user-select: none;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #007BFF;
      color: #fff;
      border: none;
      cursor: pointer;
      font-size: 1rem;
    }
    button:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Restablecer Contraseña</h2>
    <form id="resetForm">
      <!-- Se incluye el token como campo oculto -->
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <div class="form-group">
        <label for="new_password">Nueva Contraseña:</label>
        <input type="password" id="new_password" name="new_password" required>
        <span class="toggle-password" onclick="togglePassword('new_password')">&#128065;</span>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirmar Contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <span class="toggle-password" onclick="togglePassword('confirm_password')">&#128065;</span>
      </div>
      <button type="submit">Restablecer Contraseña</button>
    </form>
  </div>

  <script>
    // Función para alternar la visibilidad del campo de contraseña
    function togglePassword(id) {
      var input = document.getElementById(id);
      input.type = (input.type === "password") ? "text" : "password";
    }

    // Manejo del envío del formulario usando fetch
    document.getElementById('resetForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var token = document.querySelector('input[name="token"]').value;
      var newPassword = document.getElementById('new_password').value;
      var confirmPassword = document.getElementById('confirm_password').value;

      if (newPassword !== confirmPassword) {
        alert("Las contraseñas no coinciden.");
        return;
      }

      // Enviar la petición al endpoint /reset_password
      fetch('/reset_password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          // Se envía el token en el header Authorization
          'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
          new_password: newPassword,
          confirm_password: confirmPassword
        })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message || data.error);
        // Si se actualiza correctamente, se puede redirigir al login
        if (data.message) {
          window.location.href = '/login';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert("Ocurrió un error al restablecer la contraseña.");
      });
    });
  </script>
</body>
</html>
