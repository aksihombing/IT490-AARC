<?php
// nav.inc.php
// assumes $userData is already defined in index.php
$username = $userData['username'] ?? 'User';
?>

<html>
<!-- https://getbootstrap.com/docs/5.3/components/navbar/ -->
<nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">AARC Library</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button> <!-- default responsive hamburger icon -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?content=browse&page=1">Browse All</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?content=search">Search</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?content=bookClub">Book Clubs</a></li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <?php echo htmlspecialchars($username); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?content=my_library">My Library</a></li>
                        <li><a class="dropdown-item" href="/includes/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>


</html>
