<?php require_once('includes/clubs.inc.php'); ?>
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

        <?php if (empty($clubs)): ?>
          <li class="list-group-item">You are not in any clubs yet.</li>
        <?php else: ?>
          <?php foreach ($clubs as $c): ?>
            <li class="list-group-item">
              <strong><?= htmlspecialchars($c['name']) ?></strong>
              <?php if (!empty($c['description'])):
                htmlspecialchars($c['description']) ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>       
        
      </ul>
    </div>
  </div>
</section>

<section id="createClub" class="mb-4">
  <div class="card"><!-- Bootstrap card -->
    <div class="card-body">
      <h3 class="card-title h5 mb-3">Create Club</h3>
      <form id="formCreate" class="row g-3" method="POST"> <!-- grid for form spacing -->
        <div class="col-12">
          <label class="form-label">Name:</label>
          <input name="club_name" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Description:</label>
          <textarea name="description" class="form-control"></textarea>
        </div>
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
        <input type="hidden" name="action" value="create_club">
        <div class="col-12">
          <button type="submit" class="btn btn-dark">Create</button> <!-- changed button style -->
        </div>
      </form>
    </div>
  </div>
</section>


<section id="events">
  <div class="card"><!-- Bootstrap card -->
    <div class="card-body">
      <h3 class="card-title h5 mb-3">Schedule Event</h3>
      <form id="formEvent" class="row g-3" method="POST"> <!-- grid for form spacing, same layout-->
        <div class="col-md-4">
          <label class="form-label">Select Club:</label>
          <select name="club_id" class="form-select" required>
            <option value="">Choose a club</option>
              <?php foreach ($clubs as $c): ?>
                <option value="<?= (int)$c['club_id'] ?>"
                  <?php if (!empty($selectedClubId) && (int)$selectedClubId === (int)$c['club_id']) echo 'selected'; ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Title:</label>
          <input name="title" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Start Time:</label>
          <input type="datetime-local" name="start_time" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">End Time:</label>
          <input type="datetime-local" name="end_time" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Description:</label>
          <textarea name="description" class="form-control"></textarea>
        </div>
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? 1 ?>">
        <input type="hidden" name="action" value="create_event">
        <div class="col-12">
          <button type="submit" class="btn btn-dark">Create Event</button>
        </div>
      </form>
    </div>
  </div>

  <h2>List of Events Booked</h2>
  <article>
    <?php if (empty($events)): ?>
      <p class="text-muted">You have not RSVPed for any events yet!</p>
    <?php else: ?>
      <?php foreach ($events as $e): ?>
        <div class="border rounded p-2 mb-2">
          <strong><?= htmlspecialchars($e['title']) ?></strong><br>
          <?= htmlspecialchars(date('Y-m-d H:i', strtotime($e['startTime']))) ?><br>
          <?= htmlspecialchars($e['description'] ?? '') ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </article>
</section>

<div id="output" style="margin-top:1rem;color:#333;"></div>

</div> <!-- end of container -->

<!--bootstrap edits ended here, tbc-->

</body>
</html>