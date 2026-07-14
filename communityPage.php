<?php
require_once 'controllers/EventController.php';

// Instantiate the controller
$eventController = new EventController();

// Fetch data from the controller
$data = $eventController->getCommunityPageData();

// Extract data for the view
$month = $data['month'];
$year = $data['year'];
$upcomingEvents = $data['upcomingEvents'];
$pastEvents = $data['pastEvents'];

// Include the view file
require 'views/communityPage.phtml';
