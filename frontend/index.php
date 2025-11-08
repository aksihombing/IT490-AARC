<?php
include_once(__DIR__ . '/includes/validate.php'); 
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>AARC Portal</title>
  <link rel="stylesheet" href="baseStyle.css"> 
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