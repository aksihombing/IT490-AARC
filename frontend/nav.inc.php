<?php
// nav.inc.php
// assumes $userData is already defined in index.php
$username = $userData['username'] ?? 'User';
?>

<html>

<style>
    ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        overflow: hidden;
        background-color: #6C5A49;
        border-radius: 10px;
    }

    ul li {
        float: left;
    }

    ul li a {
        display: block;
        color: white;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
    }

    ul li a:hover {
        background-color: #5A4536;
    }
</style>

<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="index.php?content=search">Search</a></li>
        <li><a href="index.php?content=bookclubs">Book Clubs</a></li>

        <li>
            <?php echo htmlspecialchars($username); ?>
            <ul>
                <li><a href="my_library.php">My Library</a></li>
                <li><a href="index.php?content=logout">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>


</html>