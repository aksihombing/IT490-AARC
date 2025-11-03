<?php
// redirects to login page if no user isn't logged in 
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// saving logged in user id and club id for controling what club events are being viewed and who can create events
$user_id = $_SESSION['user_id'];
$club_id = $_GET['club_id'];

?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Book Club Calendar</title>
    <link rel="stylesheet" href="baseStyle.css">
    <script src="/daypilot-html5/js/daypilot/daypilot-all.min.js"></script>
    <link rel="stylesheet" href="/themes/scheduler_8.css">
  </head>

  <body>
    <a href='/frontend/index.php?content=bookClub'>Back to Book Clubs</a>

    <h2>Book Club Event Calendar</h2>

    <div id="calendar"></div>

    <script>
      const userId = <?=json_encode($user_id)?>;
      const clubId = <?=json_encode($club_id)?>;  

      const dp = new DayPilot.Calendar("calendar");
      dp.viewType = "Week";

      /* const urlParams = new URLSearchParams(window.location.search);
      const clubId = urlParams.get("club_id") || 1;
      ^^ probably dont need anymore with variable additions at at start of file and also this wasnt working so */

      // loads the existing club events
      async function loadEvents() {
        const res = await fetch(`events_functions.php?action=list&club_id=${clubId}`);
        const data = await res.json();
        dp.events.list = data.events || [];
        dp.update();
      }

      // event creation
      dp.onTimeRangeSelected = async args => {

        // owner only verification
        const ownerVer = await fetch (`events_functions.php?action=check_owner&club_id=${clubId}&user_id=${userId}`)
        const ownerData = await ownerVer.json();
        if (ownerData.status !== "owner") {
          alert("only the club owner can schedule events");
          dp.clearSelection();
          return;
        }  

        const title = prompt("Event Title:");
        if (!title) return;
        const body = new FormData();
        body.append("action", "create");
        body.append("club_id", clubId);
        body.append("user_id", userId);
        body.append("title", title);
        body.append("event_date", args.start.toString("yyyy-MM-dd"));
        const res = await fetch("events_functions.php", { method: "POST", body });
        const json = await res.json();
        alert(json.message || json.status);
        await loadEvents();
        dp.clearSelection();
      };
        
      // event rsvp
      dp.onEventClick = async args => {
        if (!confirm(`Cancel event "${args.e.text()}"?`)) return;
        const body = new FormData();
        body.append("action", "event_cancel");
        body.append("event_id", args.e.id());
        const res = await fetch("clubs_functions.php", { method: "POST", body });
        const json = await res.json();
        alert(json.message || json.status);
        await loadEvents();
      };

      dp.init();
      loadEvents();
    </script>

  </body>
</html>
