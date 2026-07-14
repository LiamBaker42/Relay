<?php

require_once 'Models/StaffData.php';
require_once 'Models/EventData.php';
require_once 'Models/ConversationData.php';
require_once 'Models/ContactData.php';
session_start();
if (isset($_GET['staff_id']) && !empty($_GET['staff_id'])) {
    $_SESSION['staffId'] = $_GET['staff_id'];
}

$selectedStaffId = $_GET['staff_id'] ?? null;
$contactModel = new ContactData();
$conversationModel = new ConversationData();
$eventModel = new EventData();
$staffModel = new StaffData();

$staffList = $staffModel->getAllStaff();
// Fetch data for the dashboard
$totalConversations = $conversationModel->getTotalConversations($selectedStaffId);
$recentConversations = $conversationModel->getConversationsForStaff($selectedStaffId);
$topContacts = $contactModel->getContactsByStaff($selectedStaffId);
$activeEventsCount = $eventModel->getActiveEventsCount($selectedStaffId);
$recentActivity = $eventModel->getRecentActivity($selectedStaffId);
$goalProgress = $eventModel->getGoalProgress($selectedStaffId);
$events = $eventModel->getEventsForStaff($selectedStaffId);

// Pass data to the view
include 'Views/dashboard.phtml';
