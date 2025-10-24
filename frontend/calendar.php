<?php

// angela's bookClub.php reworked so it works with rmq and db
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: /frontend/login.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book Club Calendar</title>
  <link rel="stylesheet" href="/css/baseStyle.css">
  <script src="/daypilot-html5/js/daypilot/daypilot-all.min.js"></script>
  <link rel="stylesheet" href="/themes/scheduler_8.css">
</head>
<body>
<a href="bookClub.php" style="text-decoration:none; color:#333;">Back to My Clubs</a>

<h2>Book Club Meeting Calendar</h2>

<div id="calendar"></div>

<script>
const dp = new DayPilot.Calendar("calendar");
dp.viewType = "Week";

const urlParams = new URLSearchParams(window.location.search);
const clubId = urlParams.get("club_id") || 1;

async function loadEvents() {
  const res = await fetch(`events_api.php?action=list&club_id=${clubId}`);
  const data = await res.json();
  dp.events.list = data.events || [];
  dp.update();
}

dp.onTimeRangeSelected = async args => {
  const title = prompt("Event Title:");
  if (!title) return;
  const body = new FormData();
  body.append("action", "event_create");
  body.append("club_id", clubId);
  body.append("title", title);
  body.append("event_date", args.start.toString("yyyy-MM-dd"));
  const res = await fetch("clubs_functions.php", { method: "POST", body });
  const json = await res.json();
  alert(json.message || json.status);
  await loadEvents();
  dp.clearSelection();
};

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
