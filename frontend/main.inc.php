<?php
// MAIN LANDING PAGE (Displays login + registration forms)

// check session state
if (!isset($_SESSION['session_key'])):
  ?>
  <!-- https://getbootstrap.com/docs/4.4/components/forms/ -->
  <!-- REGISTER -->
  <div class="container align-self-center d-flex justify-content-center mt-4">
    <div class="border border-secondary-subtle rounded p-4 w-100" style="max-width:600px;">
      <h2>Register</h2>
      <small class="form-text text-muted">Create a new account.</small>
      <form name="register" action="/includes/register.php" method="post">
        <!-- email -->
        <div class="form-group col-md-12">
          <label for="emailAddress">Email:</label>
          <input type="email" class="form-control" id="emailAddress" name="emailAddress" aria-describedby="emailHelp"
            required>
        </div>

        <!-- username -->
        <div class="form-group col-md-12">
          <label for="username">Username:</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>

        <!-- password -->
        <div class="form-group col-md-12 mb-5">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-dark" value="Register">Submit</button>
        </div>


      </form>
      <?php
      if (isset($_GET['register_error'])) {
        echo "<p class='mt-2' style='color:red;'>Registration Failed: " . htmlspecialchars($_GET['register_error']) . "</p>";
      }
      if (isset($_GET['register_success'])) {
        echo "<p class='mt-2' style='color:green;'>Registration successful! You may now log in.</p>";
      }
      ?>
    </div>
  </div>

  <!-- LOGIN -->
  <div class="container align-self-center d-flex justify-content-center mt-4">
    <div class="border border-secondary-subtle rounded p-4 w-100" style="max-width:600px;">
      <h2>Login</h2>
      <small class="form-text text-muted">Already have an account with us?</small>
      <form name="login" action="/includes/login.php" method="post">
        <!-- username -->
        <div class="form-group col-md-12">
          <label for="loginUsername">Username:</label>
          <input type="text" class="form-control" id="loginUsername" name="username" required>
        </div>

        <!-- password -->
        <div class="form-group col-md-12 mb-5">
          <label for="loginPassword">Password</label>
          <input type="password" class="form-control" id="loginPassword" name="password" required>
        </div>

        <div class="form-group col-md-12">
          <button type="submit" class="btn btn-dark" value="Login">Submit</button>
        </div>

      </form>
      <?php
      if (isset($_GET['error'])) {
        echo "<p class='mt-2' style='color:red;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
      }
      ?>
    </div>
  </div>



<?php else: ?>

  <?php
  //  FOR RECENT BOOKS !! -------------
  // to load pre-loaded book data from cache db
  require_once(__DIR__ . '/includes/recent.inc.php');


  ?>



  <div> <!-- TO DO : REFINE CONTAINERS -->
    <h2>Welcome to the AARC Library</h2>

    <h3>Recent Books</h3>
    <?php if (!empty($recentBooks)): ?>
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

   <!-- Removed logout button because i dont think its THAT necessary
    <div class="container d-flex justify-content-center mt-3 mb-3">
      <a class="btn btn-dark" role="button" href="/includes/logout.php">Logout</a>
    </div> 
    -->
  </div>
<?php endif; ?>