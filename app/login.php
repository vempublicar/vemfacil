<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container d-flex justify-content-center align-items-center vh-100">
    <form action="src/access.php" method="POST" class="bg-white p-4 rounded shadow" style="width: 100%; max-width: 400px;">
      <h3 class="text-center mb-4">Acesso ao Sistema</h3>

      <div class="mb-3">
        <label for="username" class="form-label">Usuário</label>
        <input type="text" class="form-control" id="username" name="username" required />
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <input type="password" class="form-control" id="password" name="password" required />
      </div>

      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
  </div>

  <script>
    // Validação simples no frontend
    document.querySelector("form").addEventListener("submit", function(e) {
      const user = document.getElementById("username").value.trim();
      const pass = document.getElementById("password").value.trim();

      if (!user || !pass) {
        alert("Preencha todos os campos.");
        e.preventDefault();
      }
    });
  </script>

</body>
</html>
