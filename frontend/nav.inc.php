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

<<<<<<< HEAD
    ul li {
        float: left;
    }

    ul li a {
=======
    nav ul li {
        float: left;
    }

    nav ul li a {
>>>>>>> ec0357777b444cf6cd7a1e24d3e9952576a43809
        display: block;
        color: white;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
    }

<<<<<<< HEAD
    ul li a:hover {
=======
    nav ul li a:hover {
>>>>>>> ec0357777b444cf6cd7a1e24d3e9952576a43809
        background-color: #5A4536;
    }
</style>

<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
<<<<<<< HEAD
        <li><a href="index.php?content=search">Search</a></li>
        <li><a href="index.php?content=bookclubs">Book Clubs</a></li>
=======
        <li><a href="index.php?content=browse&page=1">Browse All</a></li>
        <li><a href="index.php?content=search">Search</a></li>
        <li><a href="index.php?content=bookClub">Book Clubs</a></li>
>>>>>>> ec0357777b444cf6cd7a1e24d3e9952576a43809

        <li>
            <?php echo htmlspecialchars($username); ?>
            <ul>
<<<<<<< HEAD
                <li><a href="my_library.php">My Library</a></li>
=======
                <li><a href="index.php?content=my_library">My Library</a></li>
>>>>>>> ec0357777b444cf6cd7a1e24d3e9952576a43809
                <li><a href="index.php?content=logout">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>


</html>