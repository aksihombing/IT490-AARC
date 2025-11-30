<?php
require_once('includes/validate.inc.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>AARC Portal</title>
  
  <!-- bootstrap setups -->
  <link rel="stylesheet" href="bootstrap-5.3.8/dist/css/bootstrap.css"> 
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
  <script src="bootstrap-5.3.8/dist/js/bootstrap.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">


</head>

<body>
  <header>
    <?php if ($userData): ?>
      <?php include("nav.inc.php"); ?>
    <?php endif; ?>
  </header>

  <main>
    <?php
    // PAGE CONTENT HANDLER
    if (isset($_REQUEST['content'])) {

      $content = $_REQUEST['content'];
      include("$content.php");
    } 
    
    else {
      include("main.inc.php");
    }
    ?>
  </main>

  <footer>
    <?php include("footer.inc.php"); ?>
  </footer>

</body>

</html>
