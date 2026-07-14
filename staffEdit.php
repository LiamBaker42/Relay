<?php
require_once 'Models/StaffData.php';
session_start();
if (!isset($_SESSION['staffId'])) {
    // If session staffId is not set, set it from the GET parameter, takes it from the url where it was set not ideal.
    $_SESSION['staffId'] = isset($_GET['id']) ? (int)$_GET['id'] : null;
}
$staffData = new StaffData();

// Check if we are editing an existing staff member
$staff = null;
$staffId = $_SESSION['staffId'];
if ($staffId) {
    // Get staff data if we're editing an existing staff member
    $staff = $staffData->getStaffDetails($staffId);
}

// Fetch staff types for the dropdown
$staffTypes = $staffData->getStaffTypes();  // Make sure you have a method for fetching staff types

// Handle form submission for adding or updating staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Handle staff deletion
        if ($staffId) {
            $staffData->deleteStaff($staffId);
            header('Location: staffList.php');
            exit;
        }
    } else {
        // Handle add/update staff
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $typeId = $_POST['typeId'];  // Capture the selected Staff Type

        if ($staffId) {
            // Update existing staff member
            $staffData->updateStaff($staffId, $name, $email, $phone, $typeId);
        } else {
            // Add new staff member
            $staffData->addStaff($name, $email, $phone, $typeId);
        }

        // Redirect to the staff list page after add/update
        header('Location: staffList.php');
        exit;
    }
}
include 'Views/staffEdit.phtml';
