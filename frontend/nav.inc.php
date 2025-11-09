<?php
// nav.inc.php
// assumes $userData is already defined in index.php
$username = $userData['username'] ?? 'User';
?>

<html>

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

<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="index.php?content=browse&page=1">Browse All</a></li>
        <li><a href="index.php?content=search">Search</a></li>
        <li><a href="index.php?content=bookClub">Book Clubs</a></li>

        <li>
            <?php echo htmlspecialchars($username); ?>
            <ul>
                <li><a href="index.php?content=my_library">My Library</a></li>
                <li><a href="includes/logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>


</html>