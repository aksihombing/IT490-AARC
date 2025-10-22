/*
This page will allow for all data management such as create clubs, invite members, and it is
managed by sending requests to the rabbitmq listener

The front-end uses $userID as well as ot help eith the db

The dbs' table is being used like the club, club members, and club events
*/

<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/session_check.inc';

$user_id = session_check();
$client = new rabbitMQClient(__DIR__ . '/host.ini', 'ClubProcessor');
$message = $error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_club') {
        $request = [
            'type' => 'club.create',
            'user_id' => $user_id,
            'club_name' => trim(string: $_POST['club_name']),
            'description' => trim(string: $_POST['club_description'])
        ];
        $response = $client->send_request($request);
        if ($response['status'] === 'success') {
            $message = "Club created successfully! ID: {$response['club_id']}";
        } else {
            $error = "Failed to create club: " . ($response['message'] ?? 'Error');
        }

    } elseif ($action === 'invite') {
        $request = [
            'type' => 'club.invite',
            'club_id' => (int) $_POST['club_id'],
            'user_id' => (int) $_POST['invitee_id']
        ];
        $response = $client->send_request($request);
        if ($response['status'] === 'success') {
            $message = "Invite sent successfully!";
        } else {
            $error = "Invite failed: " . ($response['message'] ?? 'Error');
        }

    } elseif ($action === 'create_event') {
        $request = [
            'type' => 'event.create',
            'user_id' => $user_id,
            'title' => trim(string: $_POST['title']),
            'description' => trim(string: $_POST['description']),
            'location' => trim(string: $_POST['location']),
            'startTime' => $_POST['startTime'],
            'endTime' => $_POST['endTime']
        ];
        $response = $client->send_request($request);
        if ($response['status'] === 'success') {
            $message = "Event created! ID: {$response['event_id']}";
        } else {
            $error = "Event creation failed: " . ($response['message'] ?? 'Error');
        }

    } elseif ($action === 'rsvp') {
        $request = [
            'type' => 'event.rsvp',
            'user_id' => $user_id,
            'event_id' => (int) $_POST['eventID'],
            'rsvp_status' => $_POST['rsvpStatus']
        ];
        $response = $client->send_request($request);
        if ($response['status'] === 'success') {
            $message = "RSVP updated to: " . htmlspecialchars(string: $_POST['rsvpStatus']);
        } else {
            $error = "RSVP failed: " . ($response['message'] ?? 'Error');
        }
    }
}


$list_request = ['type' => 'event.list_all'];
$list_response = $client->send_request($list_request);
$upcoming_events = $list_response['status'] === 'success' ? $list_response['events'] : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Club Management</title>
</head>

<body>
    <h1>Book Club & Event Management</h1>
    <p>User ID: <?php echo $user_id; ?></p>

    <?php if ($error)
        echo "<p>Error: $error</p>"; ?>
    <?php if ($message)
        echo "<p>Success: $message</p>"; ?>

    <hr>

    <h2>Book Club Management</h2>

    <section>
        <h3>Create a Club</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_club">
            <label>Name:</label>
            <input type="text" name="club_name" required>
            <br><br>
            <label>Description:</label>
            <textarea name="club_description"></textarea>
            <br><br>
            <button type="submit">Create Club</button>
        </form>
    </section>

    <section>
        <h3>Invite a Member</h3>
        <form method="POST">
            <input type="hidden" name="action" value="invite">
            <label>Your Club ID:</label>
            <input type="number" name="club_id" required>
            <br><br>
            <label>User ID to Invite:</label>
            <input type="number" name="invitee_id" required><br><br>
            <button type="submit">Invite Member</button>
        </form>
    </section>

    <hr>

    <h2>Event Manager</h2>

    <section>
        <h3>Create a New Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_event">
            <label>Title:</label> <input type="text" name="title" required><br><br>
            <label>Location:</label> <input type="text" name="location"><br><br>
            <label>Start Time:</label> <input type="datetime-local" name="startTime" required><br><br>
            <label>End Time:</label> <input type="datetime-local" name="endTime" required><br><br>
            <label>Description:</label> <textarea name="description"></textarea><br><br>
            <button type="submit">Post Event</button>
        </form>
    </section>

    <section>
        <h3>Upcoming Events</h3>
        <?php if (empty($upcoming_events)): ?>
            <p>No events happening right now.</p>
        <?php else: ?>
            <?php foreach ($upcoming_events as $event): ?>
                <article>
                    <h4><?php echo htmlspecialchars(string: $event['title']); ?></h4>
                    <p>
                        Hosted by: **<?php echo htmlspecialchars(string: $event['creatorUsername']); ?>**<br>
                        When: <?php echo date(format: 'F j, g:i a', timestamp: strtotime(datetime: $event['startTime'])); ?><br>
                        Location: <?php echo htmlspecialchars(string: $event['location'] ?? 'N/A'); ?>
                    </p>

                    <form method="POST">
                        <input type="hidden" name="action" value="rsvp">
                        <input type="hidden" name="eventID" value="<?php echo $event['eventID']; ?>">
                        <select name="rsvpStatus" onchange="this.form.submit()">
                            <option value="Going">Going</option>
                            <option value="Maybe">Maybe</option>
                            <option value="Not Going">Not Going</option>
                        </select>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
          <footer>
    <?php include("footer.inc.php"); ?>
  </footer>
    </section>

</body>

</html>
