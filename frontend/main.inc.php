<?php
if (!isset($_SESSION['session_id'])) {
?>
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
  </form>
  <br>



<?php
} else { 
   echo "<h2 style='font-size:20px;'>Welcome. Thanks for logging in.</h2>"; 
}
?>