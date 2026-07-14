<?php
require_once 'Models/ConversationData.php';

$conversationModel = new ConversationData();
$message = "";

// Handle POST request to update conversation summary
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversationID = $_POST['conversationID'];
    $updatedSummary = $_POST['summary'];

    // Update conversation summary in the database
    if ($conversationModel->updateConversationSummary($conversationID, $updatedSummary)) {
        $message = "Conversation updated successfully.";
    } else {
        $message = "Failed to update the conversation. Please try again.";
    }
}

// Fetch conversation details for display
$conversationID = $_GET['id'];
$conversationDetails = $conversationModel->getConversationDetails($conversationID);

// Pass data to the view
include 'Views/conversationDetails.phtml';
