<?php
// MAIN LANDING PAGE (Displays login + registration forms)

/*
10-20-25
WAS GETTING "Unknown column 'year' in 'field list' from recent_books api call

i think this is because of its not reworked the way i call it yet 

*/


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

  <?php
  // to load pre-loaded book data from cache db
  require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');

  $recentBooks = [];
  //$popularBooks = []; // scrapped

  try {
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibrarySearch'); // no special queue for LibrarySearch

    // Recent books
    $recentResponse = $client->send_request(['type' => 'recent_books']);
    if ($recentResponse['status'] === 'success') {
      $recentBooks = $recentResponse['data'];
    }

  } catch (Exception $e) {
    echo "<p style='color:red;'>Error loading featured books: " . htmlspecialchars($e->getMessage()) . "</p>";
  }


  ?>



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
            // THIS IS SUBJECT TO CHANGE DEPENDING ON CHIZZY'S STRUCTURE
            $book_id = urlencode($book['isbn']);
            ?>
            <li>
              <a href="book_page.php?id=<?php echo $book_id; ?>">

                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                <br>
                by <?php echo htmlspecialchars($book['author']); ?>
                (<?php echo htmlspecialchars($book['publish_year']); ?>)
                <?php if (!empty($book['cover_url'])): ?>
                  <br><img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="Cover" width="80">
                <?php endif; ?>
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
    <p><a id="logoutbutton" href="logout.php">Logout</a></p>
  </section>
<?php endif; ?>
