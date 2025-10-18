<?php
// Include necessary files and initialize database connection
include 'Models/ConversationData.php';
include 'Models/EventData.php';

// Retrieve parameters from GET or POST
$goalID = $_GET['goalID'] ?? $_POST['goalID'] ?? null;
$eventID = $_GET['eventID'] ?? $_POST['eventID'] ?? null;

if (!$goalID || !$eventID) {
    die("Invalid goal or event ID.");
}

// Models to fetch data
$conversationData = new ConversationData();
$eventData = new EventData();

// Fetch goal, event, staff, and contact details
$goal = $eventData->getGoalById($goalID);
$event = $eventData->getEventDetails($eventID);
$staff = $eventData->getStaffForEvent($eventID);
$contact = $eventData->getContactsForEvent($eventID);

// Extract Staff IDs
$staffIds = !empty($staff) ? array_map('intval', array_column($staff, 'StaffID')) : [];

// Extract Contact IDs
$contactIds = !empty($contact) ? array_map('intval', array_column($contact, 'ContactID')) : [];

// Fetch linked and available conversations
$linkedConversations = $conversationData->getConversationsForGoal($goalID);

// Check if there are valid staff and contact IDs to query
if (!empty($staffIds) && !empty($contactIds)) {
    $availableConversations = $conversationData->getAvailableConversationsForGoal($goalID, $staffIds, $contactIds);
} else {
    $availableConversations = []; // No available conversations if staff or contacts are empty
}

// Handle form submission for linking a conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conversationID'])) {
    $conversationID = $_POST['conversationID'];

    // Link the selected conversation to the goal
    $isLinked = $conversationData->linkConversationToGoal($conversationID, $goalID);

    if ($isLinked) {
        header("Location: linkConversations.php?goalID=$goalID&eventID=$eventID&success=1");
        exit;
    } else {
        $error = "Failed to link conversation. Please try again.";
    }
}

// Handle form submission for unlinking a conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlinkConversationID'])) {
    $unlinkConversationID = $_POST['unlinkConversationID'];

    // Unlink the conversation from the goal
    $isUnlinked = $conversationData->unlinkConversationFromGoal($unlinkConversationID, $goalID);

    if ($isUnlinked) {
        header("Location: linkConversations.php?goalID=$goalID&eventID=$eventID&success=2");
        exit;
    } else {
        $error = "Failed to unlink conversation. Please try again.";
    }
}

// Include the template with data to render the page
include 'Views/linkConversations.phtml';
?>
