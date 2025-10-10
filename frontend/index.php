<?php

session_start();
?>
<html>
<?php
if (!isset($_SESSION['login'])) {
?>
  <h2>Home Page</h2><br>
  <br>


  <h2> REGISTER USER </h2>
  <form name="register" action="register.php" method="post">
    <label>Email:</label>
    <input type="text" name="emailAddress" size="20">
    <br>
    <label>Username:</label>
    <input type="text" name="username" size="20">
    <br>
    <label>Password</label>
    <input type="password" name="password" size="20">
    <br>
    <input type="submit" value="Register">
    <!-- I dont think this is needed
     <input type="hidden" name="content" value="validate">
    -->
  </form>
  <?php
  if (isset($_GET['error'])){
    echo "<p style='color:red;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
  }


  ?>
  <br>
  <br>


  <h2>LOG IN</h2>
  <h4>For existing users</h4>


  <form name="login"  action="login.php" method="post">
    <label>Username:</label>
    <input type="text" name="username" size="20">
    <br>
    <label>Password</label>
    <input type="password" name="password" size="20">
    <br>
   
    <input type="submit" value="Login">
    <!-- I dont think this is needed
     <input type="hidden" name="content" value="validate">
    -->


  </form>
  <br>




  <?php
} else {
   ?>
  <h2>Home</h2>
  <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
  <p><a href="logout.php">Logout</a></p>
<?php
}


?>






</html>


