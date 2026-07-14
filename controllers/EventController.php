<?php
require_once 'models/EventData.php';

class EventController {
    private $eventModel;

    public function __construct() {
        $this->eventModel = new EventData();
    }

    // Get data for the community page
    public function getCommunityPageData() {
        // Get current month and year.
        $currentTime = new DateTime();

        // Set default month and year based current time
        $month =  (int)$currentTime->format('m');
        $year =  (int)$currentTime->format('Y');

        // Handle month change: if buttons are pressed, adjust the month and year
        if (isset($_GET['prevMonth'])) {
            $month = ($month == 1) ? 12 : $month - 1;
            $year = ($month == 12) ? $year - 1 : $year;
        }

        if (isset($_GET['nextMonth'])) {
            $month = ($month == 12) ? 1 : $month + 1;
            $year = ($month == 1) ? $year + 1 : $year;
        }

        // Calculate start and end date for the selected month
        $startOfMonth = new DateTime("$year-$month-01");
        $endOfMonth = new DateTime("$year-$month-01");
        $endOfMonth->modify('last day of this month');

        // Get events for the selected month
        $events = $this->eventModel->getEventsByMonth($startOfMonth, $endOfMonth);

        // Separate upcoming and past events
        $currentTime = new DateTime();
        $upcomingEvents = [];
        $pastEvents = [];
        foreach ($events as $event) {
            $eventTime = new DateTime($event['EventTime']);
            if ($eventTime > $currentTime) {
                $upcomingEvents[] = $event;
            } else {
                $pastEvents[] = $event;
            }
        }

        // Return the prepared data
        return [
            'month' => $month,
            'year' => $year,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents
        ];
    }
}
