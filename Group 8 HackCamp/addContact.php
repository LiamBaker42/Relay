<?php
// Include necessary models
require_once 'models/EventData.php';
require_once 'models/ContactData.php';

// Initialize $viewData
$viewData = [];

// Retrieve the event ID from the URL
$eventId = $_GET['id'] ?? null;

if ($eventId) {
    $eventData = new EventData();
    $contactData = new ContactData();

    // Get the page number from the URL (default is 1)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 5; // Number of contacts per page

    // Search functionality
    if (isset($_GET['search'])) {
        $contactList = $contactData->searchContacts($_GET['search'],$eventId);
    } else {
        $contactList = $contactData->getAllContacts($eventId);
    }

    // Calculate the total number of pages
    $totalItems = count($contactList);
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Slice the contact list based on the current page
    $offset = ($page - 1) * $itemsPerPage;
    $currentContactList = array_slice($contactList, $offset, $itemsPerPage);

    // Add contact to event logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addContact'])) {
        $contactId = $_POST['contactId'];
        $eventData->addContactToEvent($eventId, $contactId);

        // Refresh the page after adding a contact
        header("Location: goalsPage.php"); // Refresh page after adding
        exit();
    }

    // Pass data to the view
    $viewData = [
        'eventId' => $eventId,
        'contactList' => $currentContactList,
        'totalPages' => $totalPages,
        'page' => $page
    ];
}

// Include the view file
include 'views/addContact.phtml';
?>
