<?php
// MAIN LANDING PAGE (Displays login + registration forms)

// check session state
if (!isset($_SESSION['session_key'])):
  ?>
  <!-- https://getbootstrap.com/docs/4.4/components/forms/ -->
  <!-- REGISTER -->
  <div class="container m-5">
    <h2>Register</h2>
    <small class="form-text text-muted">Create a new account.</small>
    <form name="register" action="/includes/register.php" method="post">
      <!-- email -->
      <div class="form-group">
        <label for="emailAddress">Email:</label>
        <input type="email" class="form-control" id="emailAddress" name="emailAddress" aria-describedby="emailHelp"
          required>
      </div>

      <!-- username -->
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>

      <!-- password -->
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>

      <button type="submit" class="btn btn-dark" value="Register">Submit</button>


    </form>
    <?php
    if (isset($_GET['register_error'])) {
      echo "<p style='color:red;'>Registration Failed: " . htmlspecialchars($_GET['register_error']) . "</p>";
    }
    if (isset($_GET['register_success'])) {
      echo "<p style='color:green;'>Registration successful! You may now log in.</p>";
    }
    ?>
  </div>

  <!-- LOGIN -->
  <div class="container m-5">
    <h2>Login</h2>
    <small class="form-text text-muted">Already have an account with us?</small>
    <form name="login" action="/includes/login.php" method="post">
      <!-- username -->
      <div class="form-group">
        <label for="loginUsername">Username:</label>
        <input type="text" class="form-control" id="loginUsername" name="username" required>
      </div>

      <!-- password -->
      <div class="form-group">
        <label for="loginPassword">Password</label>
        <input type="password" class="form-control" id="loginPassword" name="password" required>
      </div>

      <button type="submit" class="btn btn-dark" value="Login">Submit</button>

    </form>
    <?php
    if (isset($_GET['error'])) {
      echo "<p style='color:red;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>
  </div>



<?php else: ?>

  <?php
  //  FOR RECENT BOOKS !! -------------
  // to load pre-loaded book data from cache db
  require_once(__DIR__ . '/includes/recent.inc.php');


  ?>



  <section id="welcome-section">
    <h2>Welcome!</h2>
    <p>You are logged in successfully.</p>

    <h3>Recent Books</h3>
    <?php if (!empty($recentBooks)): ?>
      <h2>Results:</h2>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach ($recentBooks as $book):
          $olid = urlencode($book['olid']);
          // used for book.php GET queries
          // echo "<p>OLID : $olid</p>";// DEBUGGING
          ?>

          <div class="col"> <!-- columnss -->
            <div class="card h-100"> <!-- design per card, height=100% -->
              <!-- book image -->
              <div class="position-relative">
                <?php if (!empty($book['cover_url']) && str_contains($book['cover_url'], 'https')): ?>
                  <img src="
                        <?php echo htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="card-img-top">
                <?php else: ?>
                  <img src="images/gray.jpg" class="card-img-top" alt="No book cover available">
                  <div class="position-absolute top-50 start-50 text-white px-2 py-1">No Book Cover Available
                  </div>
                <?php endif; ?>
              </div>

              <!-- book info -->
              <div class="card-body">
                <h5 class="card-title">
                  <a href="index.php?content=book&olid=<?php echo $olid; ?>" class="stretched-link text-decoration-none">
                    <?php echo htmlspecialchars($book['title']); ?>
                  </a>
                </h5>
                <!-- other details -->
                <p class="card-text mb-1">
                  <?php echo htmlspecialchars($book['author']); ?>
                </p>
                <p class="card-text text-muted">
                  <?php echo htmlspecialchars($book['publish_year']); ?>
                </p>
              </div>

            </div><!-- end card body -->

          </div><!-- end card col -->

        <?php endforeach; ?>

      </div> <!-- END OF OVERALL CARD DISPLAYS-->
    <?php else: ?>
      <p>No recent releases available right now.</p>
    <?php endif; ?>


    <a class="btn btn-dark" role="button" href="/includes/logout.php">Logout</a>
  </section>
<?php endif; ?>