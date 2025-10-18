<?php
require_once 'models/StaffData.php';
require_once 'models/ContactData.php';
session_start();
if (!isset($_SESSION['staffId'])) {
    // If session staffId is not set, set it from the GET parameter
    $_SESSION['staffId'] = isset($_GET['id']) ? (int)$_GET['id'] : null;
}

class StaffDetailsController {
    private $staffModel;
    private $contactModel;

    public function __construct() {
        $this->staffModel = new StaffData();
        $this->contactModel = new ContactData();
    }

    public function showStaffDetails($staffID) {
        // Fetch staff details
        $staffDetails = $this->staffModel->getStaffDetails($staffID);

        // Pagination variables
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $itemsPerPage = 5; // Set how many items per page

        // Fetch staff's contacts, conversation counts, and last contacted dates
        $allContacts = $this->contactModel->getContactsByStaff($staffID);

        // Calculate the total number of pages
        $totalItems = count($allContacts);
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Slice the contacts list based on the current page
        $offset = ($page - 1) * $itemsPerPage;
        $contacts = array_slice($allContacts, $offset, $itemsPerPage);

        $pageTitle = "Staff Details";

        // Pass data to the view
        include 'views/staffDetails.phtml';
    }

}

// Handle request
if (isset($_GET['id'])) {
    // If the 'id' is in the URL, use it to show staff details
    $controller = new StaffDetailsController();
    $controller->showStaffDetails($_GET['id']);
} else if (isset($_SESSION['staffId'])) {
    // If the 'id' is not in the URL, but the 'staffId' is in the session, use it better version of storing staff id
    $controller = new StaffDetailsController();
    $controller->showStaffDetails($_SESSION['staffId']);
} else {
    // If neither 'id' in the URL nor 'staffId' in the session is available
    echo "Staff ID not provided.";
}

