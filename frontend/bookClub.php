<?php


session_start();
require_once __DIR__ . '../../rabbitMQ/rabbitMQLib.inc';  
require_once __DIR__ . '../../rabbitMQ/get_host_info.inc'; 

$sql = "SELECT * FROM accounts WHERE username = ?";
$myQuery = $pdo->prepare($sql);
$myQuery->execute([$username]);
$result = $myQuery->fetch();
// above im adding sessions so hopefully it can connect and display the users info
?> 
<!doctype html>

<html>
<head>
  <meta charset="utf-8">
  <title>Book Clubs</title>
  <link rel="stylesheet" href="bootstrap-5.3.8/dist/css/bootstrap.css">
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous">
  </script>

  <script src="js/daypilot/daypilot-all.min.js" type="text/javascript"></script>
  <link type="text/css" rel="stylesheet" href="themes/scheduler_8.css" />    

  <script src="bootstrap-5.3.8/dist/js/bootstrap.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">



</head>
<body>

<div class="container m-5"><!-- reusing the same container to match -->
  <!-- Page heading -->
  <h2 class ="mb-4">Book Clubs</h2>   <!-- added bottom margin -->

<h2 class="mb-4">My Book Clubs</h2> <!-- added bottom margin -->

<section id="myClubs" class="mb-4">
  <div class="card"> <!-- bootstrap card wrapper-->
  <div class="card-body">
  <h3 class="card-title h5">My Clubs</h3> <!-- bootstrap card title -->
  <ul id="clubList" class="list-group list-group-flush mt-3">
  </ul>
  </div>
  </div>
</section>

<section id="createClub" class="mb-4">
  <div class="card"><!-- Bootstrap card -->
  <div class="card-body">
  <h3 class="card-title h5 mb-3">Create Club</h3>
  <form id="formCreate" class="row g-3"> <!-- grid for form spacing -->
    <div class="col-12">
    <label class="form-label">Name:</label><input name="club_name" class="form-control" required></div>
    <div class="col-12">
    <label class>Description:</label><textarea name="description" class="form-control"></textarea></div>
    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
    <input type="hidden" name="action" value="create">
    <div class="col-12">
    <button type="submit" class="btn btn-dark">Create</button> <!-- changed button style --></div>
  </form>
  </div>
  </div>
</section>

<!--
<section id="inviteMember">
  <h3>Invite Member</h3>
  <form id="formInvite">
    <label>Club ID:</label><input name="club_id" required><br>
    <label>User ID:</label><input name="user_id" required><br>
    <input type="hidden" name="action" value="invite">
    <button type="submit">Invite</button>
  </form>
</section>
-->

<section id="events">
  <div class="card"><!-- Bootstrap card -->
  <div class="card-body">
  <h3 class="card-title h5 mb-3">Schedule Event</h3>
  <form id="formEvent" class="row g-3"> <!-- grid for form spacing, same layout-->
    <div class="col-md-4">
    <label class="form-label">Club ID:</label><input name="club_id" class="form-control" required></div>
    <div class="col-md-6">
    <label class="form-label">Title:</label><input name="title" class="form-control" required></div>
    <div class="col-md-6">
    <label class="form-label">Date:</label><input type="date" name="event_date" class="form-control" required></div>
    <div class="col-12">
    <label class="form-label">Description:</label><textarea name="description" class="form-control"> </textarea></div>
    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
    <input type="hidden" name="action" value="event_create">
    <div class="col-12">
    <button type="submit" class="btn btn-dark">Create Event</button></div>
  </form>
  </div>
  </div>

  <h2>List View of Events Booked</h2>
    <article>
    <h2><?=$result["username"]?>'s profile:</h2>
    <h2>Below is the user's information</h2>
    <h3>The club ID is: <?=$result['club_id']?></h3>
    <h3>The title: <?=$result['title']?></h3>
    <h3>The event date is: <?=$result['event_date']?></h3>
    <h3>The description of the event created is: <?=$result['description']?></h3>

</article>
</section>

<div id="output" style="margin-top:1rem;color:#333;"></div>

</div> <!-- end of container -->

<!--bootstrap edits ended here, tbc-->

<script>
const USER_ID = <?= json_encode($_SESSION['user_id'] ?? 1) ?>;

document.addEventListener("DOMContentLoaded", loadClubs); // should auto-load clubs list when page loads

// invite generation function
async function generateInvite(clubId) {
  const res = await fetch('includes/clubs_functions.php', {
    method: 'POST',
    body: new URLSearchParams({ 
      action: 'invite_link', 
      club_id: clubId, 
      user_id: USER_ID })
  });
  const json = await res.json();
  if (json.status === 'success' && json.link) {
    alert(`Invite link: ${json.link}`);
  } else {
    alert(`Failed to generate invite link: ${json.message || 'unknown error'}`);
  }
}

// club list loading function
async function loadClubs() {
  const list = document.getElementById('clubList');

  try {
    const res = await fetch('includes/clubs_functions.php', {
      method: 'POST',
      body: new URLSearchParams({
        action: 'list',
        user_id: USER_ID
      })
    });

    const json = await res.json();

    if (!json.clubs || json.clubs.length === 0) {
      list.innerHTML = '<li>no clubs found</li>';
      return;
    }

    list.innerHTML = '';
    json.clubs.forEach(c => {
      const li = document.createElement('li');
      let inviteLinkHTML = '';
      //modified existing login here for club list to include the link I Hope
      if (c.owner_id == USER_ID) {
        inviteLinkHTML = `<button onclick="generateInvite(${c.club_id})">Generate Invite Link</button>`;
      }

      li.innerHTML = `<strong>${c.name}</strong> — ${c.description || 'No club description'} 
        (<a href="calendar.php?club_id=${c.club_id}">View Calendar</a>) ${inviteLinkHTML}`;
        
      list.appendChild(li); 
    });
  } catch (err) {
    list.innerHTML = `<li>error loading clubs: ${err.message}</li>`;
  }
}


async function postForm(form){
  const data = new FormData(form);
    
  if (!data.has('user_id')) data.append('user_id', USER_ID);

  const res = await fetch('includes/clubs_functions.php', {method:'POST', body:data});
  const out = document.getElementById('output');

  if(!res.ok){ out.textContent = 'network error'; return; }
  
  const json = await res.json();
  out.textContent = json.message || json.status;

  // refresh clubs automatically after forms submitted
  if (['create', 'invite'].includes(data.get('action'))) {
    await loadClubs();
  }

  form.reset();
}

document.getElementById('formCreate').onsubmit = e => {e.preventDefault();postForm(e.target);}
// document.getElementById('formInvite').onsubmit = e => {e.preventDefault();postForm(e.target);}
document.getElementById('formEvent').onsubmit = e => {e.preventDefault();postForm(e.target);}
</script>

</body>
</html>