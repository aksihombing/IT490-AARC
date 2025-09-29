<html>
<script>
// Created by Rea S.
function HandleLoginResponse(response)
{
	var text = JSON.parse(response);
//	document.getElementById("textResponse").innerHTML = response+"<p>";	
	document.getElementById("textResponse").innerHTML = "response: "+text+"<p>";
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
	request.send("type=login&uname="+username+"&pword="+password); // takes in user and password
}
</script>

<!-- HTML START-->

<!-- KEHOE's CODE
 <h1>login page</h1>
<body>
<div id="textResponse">
awaiting response
</div>
<script>
SendLoginRequest("kehoed","12345");
</script>
</body>
-->
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
    <input type="text" name="uname" size="20">
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

  <form name="login" onsubmit="SendLoginRequest(this.uname.value, this.password.value);"> 
    <!-- .this = instance of variable
          .value = value attribute from HTML element -->
    <label>Username:</label>
    <input type="text" name="uname" size="20">
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
   echo "<h2> Gotcha! INFO: {$_SESSION['uname']})</h2>";

}
?>



</html>