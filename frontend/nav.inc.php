<?php
// nav.inc.php
// assumes $userData is already defined in index.php
$username = $userData['username'] ?? 'User';
?>

<html>
<!-- SCRAPPED NAV BAR
<style>
    nav ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        overflow: hidden;
        background-color: #6C5A49;
        border-radius: 10px;
    }

    nav ul li {
        float: left;
    }

    nav ul li a {
        display: block;
        color: white;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
    }

    nav ul li a:hover {
        background-color: #5A4536;
    }
</style>
-->

<!-- https://getbootstrap.com/docs/5.3/components/navbar/ -->
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">AARC Library</a>
        <div class="collapse navbar-collapse" id="navbardSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
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