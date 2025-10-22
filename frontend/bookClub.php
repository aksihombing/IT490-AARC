<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book Clubs</title>
  <link rel="stylesheet" href="/css/baseStyle.css">
</head>
<body>
<h2>My Book Clubs</h2>

<section id="myClubs">
  <h3>My Clubs</h3>
  <button onclick="loadClubs()">Refresh List</button>
  <ul id="clubList"></ul>
</section>

<script>
async function loadClubs() {
  const res = await fetch('clubs_functions.php', {
    method: 'POST',
    body: new URLSearchParams({ action: 'list', user_id: '<?= $_SESSION['user_id'] ?? 1 ?>' })
  });
  const json = await res.json();
  const list = document.getElementById('clubList');
  list.innerHTML = '';
  if (!json.clubs || json.clubs.length === 0) {
    list.innerHTML = '<li>No clubs found.</li>';
    return;
  }
  json.clubs.forEach(c => {
    const li = document.createElement('li');
    li.innerHTML = `<strong>${c.name}</strong> — ${c.description || 'No description'} 
                    (<a href="calendar.php?club_id=${c.club_id}">View Calendar</a>)`;
    list.appendChild(li);
  });
}
</script>

<section id="createClub">
  <h3>Create Club</h3>
  <form id="formCreate">
    <label>Name:</label><input name="club_name" required><br>
    <label>Description:</label><textarea name="description"></textarea><br>
    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
    <input type="hidden" name="action" value="create">
    <button type="submit">Create</button>
  </form>
</section>

<section id="inviteMember">
  <h3>Invite Member</h3>
  <form id="formInvite">
    <label>Club ID:</label><input name="club_id" required><br>
    <label>User ID:</label><input name="user_id" required><br>
    <input type="hidden" name="action" value="invite">
    <button type="submit">Invite</button>
  </form>
</section>

<section id="events">
  <h3>Schedule Event</h3>
  <form id="formEvent">
    <label>Club ID:</label><input name="club_id" required><br>
    <label>Title:</label><input name="title" required><br>
    <label>Date:</label><input type="date" name="event_date" required><br>
    <label>Description:</label><textarea name="description"></textarea><br>
    <input type="hidden" name="action" value="event_create">
    <button type="submit">Create Event</button>
  </form>
</section>

<div id="output" style="margin-top:1rem;color:#333;"></div>

<script>
async function postForm(form){
  const data = new FormData(form);
  const res = await fetch('clubs_functions.php', {method:'POST', body:data});
  const out = document.getElementById('output');
  if(!res.ok){ out.textContent = 'network error'; return; }
  const json = await res.json();
  out.textContent = json.message || json.status;

  form.reset();
}

document.getElementById('formCreate').onsubmit = e => {e.preventDefault();postForm(e.target);}
document.getElementById('formInvite').onsubmit = e => {e.preventDefault();postForm(e.target);}
document.getElementById('formEvent').onsubmit = e => {e.preventDefault();postForm(e.target);}
</script>

</body>
</html>
