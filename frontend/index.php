<?php

session_start();
?>
<html>
<script>
// taken from Prof's code
function HandleLoginResponse(response)
 {
  const data = JSON.parse(response);
  if (data.status === 'success') {
    window.location = 'index.php'; // reload to show the logged-in Home view
  } else {
    document.getElementById("textResponse").innerHTML = "response: " + (data.message || response) + "<p>";
  }
}
function SendLoginRequest(username,password) // gets username and password elements
{
	var request = new XMLHttpRequest();
	request.open("POST","login.php",true);
	request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	request.onreadystatechange= function ()
	{
		
		if ((this.readyState == 4)&&(this.status == 200)) // Status = Good!
		{
			HandleLoginResponse(this.responseText);
		}		
	}
	request.send("type=login&username="+username+"&password="+password); // takes in user and password
}
</script>


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

  <br>
  <br>

  <h2>LOG IN</h2>
  <h4>For existing users</h4>

  <form name="login" onsubmit="SendLoginRequest(this.username.value, this.password.value);"> 
    <!-- .this = instance of variable
          .value = value attribute from HTML element -->
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
