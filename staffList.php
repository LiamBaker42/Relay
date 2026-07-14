<?php
require_once 'models/StaffData.php';
session_start();
// Clears the staffId that is used to check what staff members' contacts are being looked at
unset($_SESSION['staffId']);

$staffModel = new StaffData();
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get the page number from the URL (default is 1)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 5; // Set how many items per page

// Perform search if query exists
if ($searchQuery) {
    $staffList = $staffModel->searchStaff($searchQuery);
} else {
    // Fetch all staff members if no search query
    $staffList = $staffModel->getAllStaff();
}

// Calculate the total number of pages
$totalItems = count($staffList);
$totalPages = ceil($totalItems / $itemsPerPage);

// Slice the staff list based on the current page
$offset = ($page - 1) * $itemsPerPage;
$currentStaffList = array_slice($staffList, $offset, $itemsPerPage);

$pageTitle = "Staff List";

// Pass data to the view
include 'views/staffList.phtml';
?>
