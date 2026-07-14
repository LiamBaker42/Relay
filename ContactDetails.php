<?php
require_once 'models/ContactData.php';
session_start();

// Make sure staff ID is in the session
if (!isset($_SESSION['staffId'])) {
    die("Staff ID is not set in the session.");
}

$staffID = $_SESSION['staffId'];

class ContactDetails
{
    private $contactModel;
    private $staffID;

    public function __construct($staffID)
    {
        $this->contactModel = new ContactData();
        $this->staffID = $staffID;
    }

    public function showContactDetails($contactID)
    {
        // Fetch contact details
        $contactDetails = $this->contactModel->getContactDetails($contactID);

        // Fetch the ConnectionID associated with this contact and staff
        $connectionId = $this->contactModel->getConnectionIdByContact($contactID, $this->staffID);

        // Fetch all conversations related to this connection (not contact)
        $conversations = $this->contactModel->getConversationsByConnection($connectionId);

        // Pagination logic for conversations
        $itemsPerPage = 5; // Number of conversations per page
        $totalConversations = count($conversations); // Total number of conversations
        $totalPages = ceil($totalConversations / $itemsPerPage); // Total pages

        // Get the current page from the URL, default to 1 if not set
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, min($page, $totalPages)); // Ensure the page is within range

        // Calculate the offset and slice the array
        $offset = ($page - 1) * $itemsPerPage;
        $conversations = array_slice($conversations, $offset, $itemsPerPage);

        // Fetch the last conversation date
        $conversationsdate = $this->contactModel->getConversationsWithLastContact($contactID);
        $lastContacted = !empty($conversationsdate) ? $conversationsdate[0]['ConversationDate'] : null;

        // Pass data to the view
        include 'views/contactDetails.phtml';
    }
}

// Handle request
if (isset($_GET['id'])) {
    $controller = new ContactDetails($staffID);
    $controller->showContactDetails($_GET['id']);
} else {
    echo "Contact ID not provided.";
}
