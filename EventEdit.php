<?php
require_once 'Models/EventData.php';
session_start();
if (isset($_SESSION['eventId'])){
    unset($_SESSION['eventId']);
}
if (!isset($_SESSION['eventId'])) {
    // Set session eventId from the GET parameter if not already set
    $_SESSION['eventId'] = isset($_GET['id']) ? (int)$_GET['id'] : null;
}

$eventData = new EventData();

// Check if we are editing an existing event
$event = null;
$eventId = $_SESSION['eventId'];

if ($eventId) {
    // Get event data if editing an existing event
    $event = $eventData->getEventDetails($eventId);
    $goals = $eventData->getEventGoals($eventId);
} else {
    $goals = []; // No goals for a new event
}

// Handle form submission for adding/updating/deleting events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Handle event deletion
        if ($eventId) {
            $eventData->deleteEvent($eventId);
            header('Location: goalsPage.php');
            exit;
        }
    } else {
        // Handle add/update event
        $date = $_POST['date'];
        $description = $_POST['description'];
        $submittedGoals = $_POST['goals'] ?? [];

        if ($eventId) {
            // Update existing event
            $eventData->updateEvent($eventId, $date, $description);
            $eventData->updateEventGoals($eventId, $submittedGoals);
        } else {
            // Add new event
            $newEventId = $eventData->addEvent($date, $description);
            $eventData->addEventGoals($newEventId, $submittedGoals);
        }

        // Redirect to the events overview page after add/update
        header('Location: goalsPage.php');
        exit;
    }
}

include 'Views/eventEdit.phtml'; // Include the HTML form
?>
