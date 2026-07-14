<?php
session_start();

// Check if staffId is posted
if (isset($_POST['staffId'])) {
    $_SESSION['staffId'] = htmlspecialchars($_POST['staffId']);

    // Determine where to redirect
    $redirectPage = isset($_POST['redirect']) ? $_POST['redirect'] : '/staffDetails.php';

    header("Location: $redirectPage");
    exit();
} else {
    echo "Staff ID not provided.";
}

