<?php
require_once 'models/ConversationData.php';
session_start();

// Retain existing values if already set in the session or context
$staffId = $_GET['contactId'] ?? $_SESSION['staff_id'];
$contactId = $_GET['contactId'] ?? ($_SESSION['contactId'] ?? $contactId ?? null);
$conversationId = $_GET['conversationId'] ?? ($_SESSION['conversationId'] ?? $conversationId ?? null);

// Retain values in session after form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = $_POST['staffId'] ?? $staffId;
    $contactId = $_POST['contactId'] ?? $contactId;
    $conversationId = $_POST['conversationId'] ?? $conversationId;

    // Update the session values after form submission
    $_SESSION['staffId'] = $staffId;
    $_SESSION['contactId'] = $contactId;
    $_SESSION['conversationId'] = $conversationId;
}

if (!$staffId || !$contactId) {
    exit('Required parameters are missing.');
}

$conversationData = new ConversationData();
$conversationDetails = null;

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeId = $_POST['conversationType'] ?? null;
    $summary = $_POST['summary'] ?? null;
    $conversationDate = $_POST['conversationDate'] ?? null; // Get the ConversationDate

    if (!$typeId || !$summary || !$conversationDate) {
        exit('All fields are required.');
    }

    if (isset($_POST['conversationId']) && $_POST['conversationId']) {
        // Update existing conversation with ConversationDate
        $conversationData->updateConversation($_POST['conversationId'], $typeId, $summary, $conversationDate);
    } else {
        // Add new conversation with ConversationDate
        $conversationData->addConversation($staffId, $contactId, $typeId, $summary, $conversationDate);
    }

    // Unset session variables for staff, contact, and conversation IDs
    unset($_SESSION['conversationId']);

    // Redirect back to contact details page
    header("Location: ContactDetails.php?id=" . htmlspecialchars($contactId));
    exit;
}

if ($conversationId) {
    // Fetch conversation details for editing
    $conversationDetails = $conversationData->getConversationDetails($conversationId);
}

// Fetch all conversation types for the dropdown
$conversationTypes = $conversationData->getAllConversationTypes();
include 'views/conversationEdit.phtml';
