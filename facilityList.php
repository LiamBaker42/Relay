<?php

include 'models/FacilityData.php';


// Initialize database connection and model
$db = new PDO('sqlite:database.sqlite'); // Adjust to your database connection
$facilityData = new FacilityData($db);

$searchQuery = $_GET['search'] ?? '';
$facilities = empty($searchQuery)
    ? $facilityData->getAllFacilities()
    : $facilityData->searchFacilities($searchQuery);

include 'views/facilityList.phtml';
