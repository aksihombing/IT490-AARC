<?php
// MAIN LANDING PAGE (Displays login + registration forms)
// check session state
if (!isset($_SESSION['session_key'])):
  ?>
  <?php include __DIR__ . '/includes/recent.inc.php'; ?>

  <section id="auth-section">
    <h2>Register New User</h2>
    <form name="register" action="includes/register.php" method="post">
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

    <form name="login" action="includes/login.php" method="post">
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
      <?php if (!empty($recentBooks)): ?>
        <ul>
          <?php foreach ($recentBooks as $book):
            $olid = urlencode($book['olid']);
            ?>
            <br><br> <!-- might be best to do a css thing here but might have to wait off a bit -->
            <li>
              <a href="index.php?content=book&olid=<?php echo htmlspecialchars($olid); ?>
              ">

                <?php if (!empty($book['cover_url'])): ?>
                  <br>
                  <!--  < ?php echo "<p>OLID : $olid</p>";// DEBUGGING ?> -->
                  <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="Cover" width="80">
                <?php endif; ?>
                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                <br>
                by <?php echo htmlspecialchars($book['author']); ?>
                (<?php echo htmlspecialchars($book['publish_year']); ?>)
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No recent releases available right now.</p>
      <?php endif; ?>
    </section>

    <br>
    <br>
    <p><a id="logoutbutton" href="includes/logout.php">Logout</a></p>
  </section>
<?php endif; ?>