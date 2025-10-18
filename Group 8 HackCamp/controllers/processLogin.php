<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the username (staff_id) is numeric
    if (is_numeric($username)) {
        // If username is numeric (staff_id), redirect to the dashboard with the staff_id as a parameter
        header("Location: /dashboard.php?staff_id=" . urlencode($username));
        exit();
    } else {
        // If it's not a number, redirect to the staff list page (or handle it however you need)
        header("Location: /staffList.php");
        exit();
    }
}
