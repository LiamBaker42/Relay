<?php
require_once 'models/EventData.php';
require_once 'models/StaffData.php';

session_start();

class GoalsPageController
{
    private $eventModel;
    private $staffModel;

    public function __construct()
    {
        $this->eventModel = new EventData();
        $this->staffModel = new StaffData();
    }
    //getter for the model to use later in the class.
    public function getEventModel()
    {
        return $this->eventModel;
    }

    // Show goals page with or without search and staff filter
    public function showGoalsPage($searchQuery = null, $selectedStaffId = null)
    {
        // Get the list of staff for the dropdown (fetched from the database)
        $staffList = $this->staffModel->getAllStaff();  // Assuming this method fetches staff

        // Fetch events based on the search query and selected staff filter
        if ($searchQuery) {
            if ($selectedStaffId) {
                // Filter events by search query and selected staff
                $events = $this->eventModel->getEventsWithSearchForStaff($searchQuery, $selectedStaffId);
            } else {
                $events = $this->eventModel->getEventsWithSearch($searchQuery);
            }
        } else {
            // No search query, fetch events filtered by staff (if a staff is selected)
            if ($selectedStaffId) {
                $events = $this->eventModel->getEventsForStaff($selectedStaffId);
            } else {
                $events = $this->eventModel->getAllEventsWithStaffAndContactsAndGoals();
            }
        }
        foreach ($events as &$event) {
            // Check if all goals for this event are completed
            $isTaskCompleted = true;  // Assume the task is completed unless proven otherwise

            foreach ($event['goals'] as $goal) {
                if (!$goal['isCompleted']) {
                    $isTaskCompleted = false;  // Found an incomplete goal
                    break;
                }
            }

            // Add the task completion status to the event array
            $event['isTaskCompleted'] = $isTaskCompleted;
        }

        // Add the "daysToCompletion" or "daysOverdue" to each event if it's not completed
        foreach ($events as &$event) {
            // Only calculate days if the task is not completed
            if (!$event['isTaskCompleted']) {
                $currentDate = time(); // Current timestamp
                $eventDate = strtotime($event['EventTime']); // Event timestamp

                // Calculate the difference in days
                $daysDifference = ($eventDate - $currentDate) / (60 * 60 * 24); // Days difference

                // Add "daysToCompletion" or "daysOverdue" field
                if ($daysDifference > 0) {
                    $event['daysToCompletion'] = floor($daysDifference); // Days to completion
                } else {
                    $event['daysOverdue'] = abs(floor($daysDifference)); // Days overdue if past
                }
            }
        }

        // Now sort by both completion status and days to completion/overdue
        usort($events, function($a, $b) {
            // First, sort by task completion status (completed tasks at the bottom)
            $completionComparison = $a['isTaskCompleted'] <=> $b['isTaskCompleted'];
            if ($completionComparison !== 0) {
                return $completionComparison;
            }

            // If completion status is the same, sort by event date (closest to current date first)
            $currentDate = time(); // Get current timestamp
            $eventDateA = strtotime($a['EventTime']);
            $eventDateB = strtotime($b['EventTime']);

            // Compare the dates and return result (earlier dates first)
            return $eventDateA <=> $eventDateB;
        });
        // Number of records per page
        $recordsPerPage = 3;

        // Get the current page from the URL, defaulting to 1 if not set
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        // Calculate the starting index (offset) for the slice
        $startIndex = ($page - 1) * $recordsPerPage;

        // Slice the array to get only the events for the current page
        $paginatedEvents = array_slice($events, $startIndex, $recordsPerPage);

        // Calculate the total number of pages
        $totalRecords = count($events);
        $totalPages = ceil($totalRecords / $recordsPerPage);

        // Pass the data (events, staff list, and selected staff ID) to the view
        include_once("views/goalsPage.phtml");
    }
    //removes staff from event
    public function removeStaffFromEvent($eventId, $staffId)
    {
        if ($this->eventModel->deleteStaffFromEvent($eventId, $staffId)) {
            $_SESSION['success_message'] = "Staff successfully removed from the event.";
        } else {
            $_SESSION['error_message'] = "Failed to remove staff from the event.";
        }

        // Redirect after removal
        header("Location: goalsPage.php");
        exit();
    }
    //removes contacts from event.
    public function removeContactFromEvent($eventId, $contactId)
    {
        if ($this->eventModel->deleteContactFromEvent($eventId, $contactId)) {
            $_SESSION['success_message'] = "Contact successfully removed from the event.";
        } else {
            $_SESSION['error_message'] = "Failed to remove contact from the event.";
        }
        // Redirect after removal
        header("Location: goalsPage.php");
        exit();
    }
}

// Handle request
$controller = new GoalsPageController();

$searchQuery = $_GET['search'] ?? null;  // Get search query from the URL
$selectedStaffId = $_GET['staff_id'] ?? null;  // Get selected staff ID from the URL

// Process POST requests for updating task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eventId'], $_POST['staffId'])) {
        $eventId = $_POST['eventId'];
        $staffId = $_POST['staffId'];
        $controller->removeStaffFromEvent($eventId, $staffId);
    } elseif (isset($_POST['eventId'], $_POST['contactId'])) {
        $eventId = $_POST['eventId'];
        $contactId = $_POST['contactId'];
        $controller->removeContactFromEvent($eventId, $contactId);
    } elseif (isset($_POST['taskId'], $_POST['isCompleted'])) {
        $taskId = $_POST['taskId'];
        $isCompleted = $_POST['isCompleted'];

        // Call the model method to update the task's completion status
        $eventModel = $controller->getEventModel();
        if ($eventModel->updateTaskCompletionStatus($taskId, $isCompleted)) {
            $_SESSION['success_message'] = "Task status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update task status.";
        }

        // Redirect to avoid resubmission
        header("Location: goalsPage.php");
        exit();
    }
} else {
    // Show the goals page, passing the search query and selected staff ID
    $controller->showGoalsPage($searchQuery, $selectedStaffId);
}
