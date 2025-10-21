













<?php
// MAIN LANDING PAGE (Displays login + registration forms)

// check session state
if (!isset($_SESSION['session_key'])):
  ?>
  <section id="auth-section">
    <h2>Register New User</h2>
    <form name="register" action="register.php" method="post">
      <label for="emailAddress">Email:</label>
      <input type="email" id="emailAddress" name="emailAddress" size="25" required>
      <br>

      <label for="username">Username:</label>
      <input type="text" id="username" name="username" size="25" required>
      <br>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" size="25" required>
      <br>

      <input type="submit" value="Register">
    </form>

    <?php
    if (isset($_GET['register_error'])) {
      echo "<p style='color:red;'>Registration Failed: " . htmlspecialchars($_GET['register_error']) . "</p>";
    }
    if (isset($_GET['register_success'])) {
      echo "<p style='color:green;'>Registration successful! You may now log in.</p>";
    }
    ?>

    <hr>

    <h2>Log In</h2>
    <h4>For existing users</h4>

    <form name="login" action="login.php" method="post">
      <label for="loginUsername">Username:</label>
      <input type="text" id="loginUsername" name="username" size="25" required>
      <br>

      <label for="loginPassword">Password:</label>
      <input type="password" id="loginPassword" name="password" size="25" required>
      <br>

      <input type="submit" value="Login">
    </form>

    <?php
    if (isset($_GET['error'])) {
      echo "<p style='color:red;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>
  </section>

<?php else: ?>
  

  <section id="welcome-section">
    <h2>Welcome!</h2>
    <p>You are logged in successfully.</p>

    <br>
    <br>

    <section id="recent-books">
      <h3>Recent Books</h3>
      
    </section>

    <br>
    <br>
    <p><a id="logoutbutton" href="logout.php">Logout</a></p>
  </section>
<?php endif; ?>