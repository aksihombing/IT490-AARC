
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
     <!--list-group for list styling, flush to remove borders and added a margin-top for spacing-->
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

</body>
</html>