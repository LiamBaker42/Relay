<?php
require_once 'Models/ContactData.php';
// contactEdit.php
session_start();

if (isset($_SESSION['staffId']) && !empty($_SESSION['staffId'])) {
    $staffId = $_SESSION['staffId'];
    // Proceed with further processing
} else {
    // Handle the case where staffId is not set
    $staffId = null;
    // Optionally, display an error message or redirect the user
}


$contactData = new ContactData();


$contactId = $_POST['contactId'] ?? null;
$contact = null;

// Check if the 'edit_contact' button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_contact'])) {
    if ($contactId) {
        // Fetch contact details if editing an existing contact
        $contact = $contactData->getContactDetails($contactId);
    }
}

// Handle form submission for adding/updating contacts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    $name = $_POST['name'];
    $number = $_POST['number'];
    $email = $_POST['email'];

    if ($contactId) {
        // Update existing contact
        $contactData->updateContact($contactId, $name, $number, $email);
    } else {
        // Add new contact and associate it with the current staff member
        $contactData->addContact($staffId, $name, $number, $email);
    }

    // Redirect back to the staff details page with the correct staffId
    header("Location: staffDetails.php?staffId=" . urlencode($staffId));
    exit;
}

// Handle contact deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contact'])) {
    if ($contactId) {
        // Delete the contact and its relationship in the Connections table
        $contactData->deleteContactConnection($staffId, $contactId);

        // Redirect back to the staff details page with the correct staffId
        header("Location: staffDetails.php?staffId=" . urlencode($staffId));
        exit;
    }
}
?>

<!-- Include HTML for the contact form, pre-filling if $contact is set -->
<?php include 'Views/contactEdit.phtml'; ?>
