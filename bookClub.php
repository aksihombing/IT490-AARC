<?php

session_start();
$is_logged_in = isset($_SESSION['login']);
?>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Book Club Scheduler</title>

    <style type="text/css">
        p, body, td { font-family: Tahoma, Arial, Helvetica, sans-serif; font-size: 10pt; }
        body { padding: 0px; margin: 0px; background-color: #ffffff; }
        a { color: #1155a3; }
        .space { margin: 10px 0px 10px 0px; }
        
        .main { padding: 10px; margin-top: 10px; }
        main { padding: 10px; margin-top: 10px; } 
        
        header.app-header { 
            background: #003267; 
            background: linear-gradient(to right, #011329 0%,#00639e 44%,#011329 100%); 
            padding:20px 10px; 
            color: white; 
            box-shadow: 0px 0px 10px 5px rgba(0,0,0,0.75); 
        }
        header.app-header a { color: white; }
        header.app-header h1 a { text-decoration: none; }
        header.app-header h1 { padding: 0px; margin: 0px; }
    </style>

    <?php if ($is_logged_in): ?>
    <script src="js/daypilot/daypilot-all.min.js"></script>
    <link type="text/css" rel="stylesheet" href="themes/scheduler_8.css"/>
    <?php endif; ?>
</head>
<body>

<main>

<?php
if (!$is_logged_in) {
?>
    <section style="padding: 20px; border: 1px solid #ccc; background-color: #f9f9f9; border-radius: 5px;">
        <h2>Access Restricted</h2>
        <p>You must be logged in to view the Book Club Meeting Scheduler.</p>
        <p>Please proceed to your dedicated <a href="login.php" style="font-weight: bold;">Login Page</a> or <a href="register.php" style="font-weight: bold;">Registration Page</a>.</p>
    </section>
    <?php
    if (isset($_GET['error'])){
        echo "<p style='color:red; margin-top: 10px;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>

<?php
} else {  
    ?>
    <section>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <p><a href="logout.php">Logout</a></p>
    </section>

    <header class="app-header">
        <h1>Book Club Meeting Scheduler</h1>
        <p>Drag and drop to reschedule meetings (if logged in).</p>
    </header>

    <section id="dp"></section>

    <script type="text/javascript">
        var dp = new DayPilot.Scheduler("dp");

        dp.cellWidth = 40;

        dp.startDate = new DayPilot.Date("2020-08-01");
        dp.days = dp.startDate.daysInMonth();
        dp.scale = "Day";
        dp.timeHeaders = [
            {groupBy: "Month"},
            {groupBy: "Day", format: "d"}
        ];

        dp.treeEnabled = true;
        dp.resources =
            [
                {
                    name: "Main Locations", id: "G1", expanded: true, children: [
                        {name: "Library Meeting Room", id: "Library Meeting Room"},
                        {name: "Coffee Shop Corner", id: "Coffee Shop Corner"},
                        {name: "Online Video Chat", id: "Online Video Chat"},
                        {name: "Host's Home (A)", id: "Host's Home (A)"},
                        {name: "Host's Home (B)", id: "Host's Home (B)"},
                    ]
                }
            ];
            
        dp.init();
        loadEvents();

        function loadEvents() {
            dp.events.load("bookClubEvents.php");
        }
        
    </script>
<?php
}
?>
</main>
</body>
</html>
