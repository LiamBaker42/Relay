<?php
// Include necessary models
require_once 'models/EventData.php';
require_once 'models/StaffData.php';

// Initialize $viewData
$viewData = [];
$eventId = $_GET['id'] ?? null;
if ($eventId) {
    $eventData = new EventData();
    $staffData = new StaffData();

    // Get the page number from the URL (default is 1)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 1; // Set how many items per page

    // Search functionality
    if (isset($_GET['search'])) {
        $staffList = $staffData->searchStaffForEvents($eventId,$_GET['search']);
    } else {
        $staffList = $staffData->getAllStaffForEvents($eventId);
    }
    // Calculate the total number of pages
    $totalItems = count($staffList);
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Slice the staff list based on the current page
    $offset = ($page - 1) * $itemsPerPage;
    $currentStaffList = array_slice($staffList, $offset, $itemsPerPage);
    // Add staff to event logic
    if (isset($_POST['addStaff'])) {
        $staffId = $_POST['staffId'];
        $eventData->addStaffToEvent($eventId, $staffId);
        header("Location: goalsPage.php"); // Refresh page after adding
    }

    // Store the necessary data to pass to the view
    $viewData = [
        'eventId' => $eventId,
        'staffList' => $currentStaffList,
    ];

}

// Include the view file
include 'views/addStaff.phtml';
?>
