<?php
require_once 'Database.php';

class EventData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Fetch events by month and year, used to get the events for the calender.
    public function getEventsByMonth($startDate, $endDate) {
        $stmt = $this->db->prepare("SELECT * FROM Events WHERE EventTime BETWEEN :startDate AND :endDate ORDER BY EventTime ASC");
        $stmt->execute([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s')
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Used to get the event details to fill the form for editing.
    public function getEventDetails($eventID) {
        $stmt = $this->db->prepare("SELECT * FROM Events WHERE EventID = :eventID");
        $stmt->execute(['eventID' => $eventID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getActiveEventsCount($staffId) {
        $query = "
        SELECT COUNT(*) AS activeEvents
        FROM Events e
        INNER JOIN EventStaff es ON e.EventID = es.EventID
        INNER JOIN Staff s ON es.StaffID = s.StaffID
        WHERE e.EventTime > DATETIME('now')
        AND s.StaffID = :staffId
    ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffId', $staffId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['activeEvents'];
    }


    //Returns all the events data, used in constructing all the data for the Goals page
    public function getAllEvents() {
        $query = "
        SELECT
            e.EventID,
            e.EventDetails,
            e.EventTime
        FROM
            Events e
    ";

        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        // Fetch all results
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $events;
    }
    //Returns the staff for specific events also used to build the goals page
    public function getStaffForEvent($eventId) {
        $query = "
        SELECT
            s.StaffID,
            s.Name AS staffName
        FROM
            EventStaff es
        JOIN Staff s ON es.StaffID = s.StaffID
        WHERE
            es.EventID = :eventId
    ";

        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Fetch all results
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $staff;
    }
    //Returns all the contacts for a specific event also used to build the goals page.
    public function getContactsForEvent($eventId) {
        $query = "
        SELECT
            c.ContactID,
            c.Name AS contactName,
            MAX(conv.ConversationDate) AS lastContacted
        FROM
            EventContacts ec
        JOIN Contacts c ON ec.ContactID = c.ContactID
        LEFT JOIN Connections conn ON c.ContactID = conn.ContactID
        LEFT JOIN Conversations conv ON conn.ConnectionID = conv.ConnectionID
        WHERE
            ec.EventID = :eventId
        GROUP BY c.ContactID, c.Name
    ";

        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Fetch all results
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $contacts;
    }
    //Returns all the goals for a specific event. also used in making the goals page.
    public function getGoalsForEvent($eventId) {
        $query = "
        SELECT
            tg.GoalsID AS goalID,
            tg.Goals AS goal,
            tg.isCompleted AS isCompleted
        FROM
            TaskGoals tg
        WHERE
            tg.EventID = :eventId
        ORDER BY tg.Goals ASC
    ";

        // Prepare and execute the query for goals
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Fetch all goals
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getConversationsForEvent($eventId) {
        $query = "
        SELECT
            tg.GoalsID AS goalID,
            c.conversationID,
            c.summary AS conversationSummary,
            s.Name AS staffName,
            co.Name AS contactName
        FROM
            TaskGoals tg
        LEFT JOIN Convo_Tasks ct ON tg.GoalsID = ct.goalsID
        LEFT JOIN Conversations c ON ct.conversationID = c.conversationID
        LEFT JOIN Connections con ON c.connectionID = con.ConnectionID
        LEFT JOIN Staff s ON con.StaffID = s.StaffID
        LEFT JOIN Contacts co ON con.ContactID = co.ContactID
        WHERE
            tg.EventID = :eventId
        ORDER BY tg.GoalsID ASC
    ";

        // Prepare and execute the query for conversations
        $stmtConvos = $this->db->prepare($query);
        $stmtConvos->execute([':eventId' => $eventId]);

        // Fetch all conversations
        return $stmtConvos->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventData($eventId) {
        // Fetch goals
        $goals = $this->getGoalsForEvent($eventId);

        // Fetch conversations
        $conversations = $this->getConversationsForEvent($eventId);

        // Combine goals and their corresponding conversations
        foreach ($goals as &$goal) {
            // Initialize an empty array for conversations
            $goal['conversations'] = [];

            // Loop through the conversations and assign them to the corresponding goal
            foreach ($conversations as $conversation) {
                if ($conversation['goalID'] == $goal['goalID']) {
                    $goal['conversations'][] = [
                        'conversationID' => $conversation['ConversationID'],
                        'conversationSummary' => $conversation['conversationSummary'],
                        'staffName' => $conversation['staffName'],
                        'contactName' => $conversation['contactName'],
                    ];
                }
            }
        }

        return $goals;
    }




    // Fetch a specific goal by its ID
    public function getGoalById($goalID) {
        $stmt = $this->db->prepare("
            SELECT g.goalsID, g.goals, g.isCompleted, g.eventID
            FROM TaskGoals g
            WHERE g.goalsID = ?
        ");
        $stmt->execute([$goalID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    //Used for the check boxes on the goals page to mark a goal completed.
    public function updateTaskCompletionStatus($taskId, $isCompleted) {
        $query = "UPDATE TaskGoals SET isCompleted = :isCompleted WHERE GoalsID = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':isCompleted', $isCompleted, PDO::PARAM_INT);
        $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    //Returns all the data from the other data collections methods into one array to be displayed.
    public function getAllEventsWithStaffAndContactsAndGoals() {
        // Step 1: Get all events
        $events = $this->getAllEvents();

        // Step 2: For each event, get the associated staff, contacts, and goals
        foreach ($events as &$event) {
            // Fetch staff for the event
            $event['staff'] = $this->getStaffForEvent($event['EventID']);

            // Fetch contacts for the event
            $event['contacts'] = $this->getContactsForEvent($event['EventID']);

            // Fetch goals for the event
            $event['goals'] = $this->getEventData($event['EventID']);
        }

//        echo '<pre>';
//        print_r($events);
//        echo '</pre>';

        return $events;
    }

    // Fetch event goals. Used in editing the Events details to fill in the form.
    public function getEventGoals($eventId) {
        $query = "SELECT GoalsID, Goals FROM TaskGoals WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to search events based on query. also groups the results into a array to be displayed.
    public function getEventsWithSearch($searchQuery)
    {
        // Prepare the base query for searching events, staff, contacts, connections, and conversations
        $query = "
    SELECT
        e.EventID,
        e.EventDetails,
        e.EventTime,
        GROUP_CONCAT(DISTINCT Staff.StaffID) AS staffIDs,
        GROUP_CONCAT(DISTINCT Staff.Name) AS staffNames,
        GROUP_CONCAT(DISTINCT Contacts.ContactID) AS contactIDs,
        GROUP_CONCAT(DISTINCT Contacts.Name) AS contactNames,
        GROUP_CONCAT(DISTINCT Connections.ConnectionID) AS connectionIDs,
        GROUP_CONCAT(DISTINCT Connections.ContactID) AS connectedContactIDs,
        GROUP_CONCAT(DISTINCT Conversations.ConversationID) AS conversationIDs,
        MAX(Conversations.ConversationDate) AS lastContacted
    FROM
        Events e
    LEFT JOIN EventStaff s ON s.EventID = e.EventID
    LEFT JOIN Staff ON Staff.StaffID = s.StaffID
    LEFT JOIN EventContacts c ON c.EventID = e.EventID
    LEFT JOIN Contacts ON Contacts.ContactID = c.ContactID
    LEFT JOIN Connections ON Connections.ContactID = Contacts.ContactID
    LEFT JOIN Conversations ON Conversations.ConnectionID = Connections.ConnectionID
    WHERE
        e.EventDetails LIKE :searchQuery
        OR Staff.Name LIKE :searchQuery
        OR Contacts.Name LIKE :searchQuery
        OR Conversations.ConversationDate LIKE :searchQuery
    GROUP BY
        e.EventID
    ";

        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%');
        $stmt->execute();

        // Fetch results
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch additional associated data for each event
        foreach ($events as &$event) {
            // Split staff and contact names and IDs
            $event['staff'] = [];
            $event['contacts'] = [];
            $event['connections'] = [];
            $event['conversations'] = [];

            $staffIDs = explode(',', $event['staffIDs']);
            $staffNames = explode(',', $event['staffNames']);
            foreach ($staffIDs as $index => $staffID) {
                $event['staff'][] = [
                    'StaffID' => $staffID,
                    'staffName' => $staffNames[$index]
                ];
            }

            $contactIDs = explode(',', $event['contactIDs']);
            $contactNames = explode(',', $event['contactNames']);
            foreach ($contactIDs as $index => $contactID) {
                $event['contacts'][] = [
                    'ContactID' => $contactID,
                    'contactName' => $contactNames[$index],
                    'lastContacted' => isset($event['lastContacted']) ? $event['lastContacted'] : null, // Add the lastContacted field
                ];
            }

            $connectionIDs = explode(',', $event['connectionIDs']);
            $connectedContactIDs = explode(',', $event['connectedContactIDs']);
            foreach ($connectionIDs as $index => $connectionID) {
                $contactID = isset($connectedContactIDs[$index]) ? $connectedContactIDs[$index] : null;
                $event['connections'][] = [
                    'ConnectionID' => $connectionID,
                    'ContactID' => $contactID,
                ];
            }

            $conversationIDs = explode(',', $event['conversationIDs']);
            foreach ($conversationIDs as $conversationID) {
                $event['conversations'][] = [
                    'ConversationID' => $conversationID
                ];
            }

            // Retrieve event goals (unchanged from before)
            $event['goals'] = $this->getEventData($event['EventID']);
        }

        return $events;
    }





    // Add a new event. used in event edit to add the new ones.
    public function addEvent($date, $description) {
        $query = "INSERT INTO Events (EventTime, EventDetails) VALUES (:date, :description)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':date' => $date,
            ':description' => $description,
        ]);
        return $this->db->lastInsertId();
    }

    // Update an existing event. used in event edit to edit current ones.
    public function updateEvent($eventId, $date, $description) {
        $query = "UPDATE Events SET EventTime = :date, EventDetails = :description WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':date' => $date,
            ':description' => $description,
            ':eventId' => $eventId,
        ]);
    }

    // Delete an event.
    public function deleteEvent($eventId) {
        // Delete from Events table
        $query = "DELETE FROM Events WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Delete associated goals from TaskGoals table
        $query = "DELETE FROM TaskGoals WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Delete associated staff from EventStaff table
        $query = "DELETE FROM EventStaff WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);

        // Delete associated contacts from EventContacts table
        $query = "DELETE FROM EventContacts WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);
    }


    // Add goals to an event
    public function addEventGoals($eventId, $goals) {
        $query = "INSERT INTO TaskGoals (Goals, EventID) VALUES (:goal, :eventId)";
        $stmt = $this->db->prepare($query);
        foreach ($goals as $goal) {
            $stmt->execute([
                ':goal' => $goal,
                ':eventId' => $eventId,
            ]);
        }
    }

    // Update goals for an event
    public function updateEventGoals($eventId, $goals) {
        // Remove old goals
        $this->deleteEventGoals($eventId);

        // Add new goals
        $this->addEventGoals($eventId, $goals);
    }

    // Delete all goals for an event
    private function deleteEventGoals($eventId) {
        $query = "DELETE FROM TaskGoals WHERE EventID = :eventId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':eventId' => $eventId]);
    }
    // Method to add staff to an event
    public function addStaffToEvent($eventId, $staffId) {
        // Check if the staff member is already associated with the event
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM eventStaff WHERE EventID = ? AND StaffID = ?");
        $stmt->execute([$eventId, $staffId]);
        $exists = $stmt->fetchColumn();

        // If the staff member is already in the event, do nothing or handle the error
        if ($exists) {
            // Optionally, throw an exception or return an error message
            // throw new Exception('Staff is already assigned to this event.');
            return 'Staff is already assigned to this event.';  // Or return any other message you prefer
        }

        // Insert the new staff member into the event
        $stmt = $this->db->prepare("INSERT INTO eventStaff (EventID, StaffID) VALUES (?, ?)");
        $stmt->execute([$eventId, $staffId]);

        return 'Staff has been added to the event successfully.';  // Or return success message
    }


    // Method to add contacts to an event
    public function addContactToEvent($eventId, $contactId) {
        // Check if the contact is already associated with the event
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM EventContacts WHERE eventId = ? AND contactId = ?");
        $stmt->execute([$eventId, $contactId]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            // Contact is already associated with this event
            $_SESSION['error_message'] = "This contact is already associated with the event.";
            return false;
        }

        // Insert the contact-event association
        $stmt = $this->db->prepare("INSERT INTO EventContacts (eventId, contactId) VALUES (?, ?)");
        $stmt->execute([$eventId, $contactId]);

        $_SESSION['success_message'] = "Contact successfully added to the event.";
        return true;
    }

    public function deleteStaffFromEvent($eventId, $staffId) {
        $query = "DELETE FROM eventStaff WHERE EventID = :eventId AND StaffID = :staffId";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':eventId' => $eventId,
            ':staffId' => $staffId
        ]);
    }
    //Deletes contacts from events
    public function deleteContactFromEvent($eventId, $contactId) {
        // Prepare the SQL statement to delete the contact from the event
        $query = "DELETE FROM eventContacts WHERE EventID = :eventId AND ContactID = :contactId";
        $stmt = $this->db->prepare($query);

        // Execute the statement with the provided parameters
        return $stmt->execute([
            ':eventId' => $eventId,
            ':contactId' => $contactId
        ]);
    }
    //Returns the filtered view of the events based on staff members.
    public function getEventsForStaff($staffId)
    {
        // Prepare the query to fetch events for a particular staff member, along with associated staff, contacts, and last contacted information
        $query = "
        SELECT
            e.EventID,
            e.EventDetails,
            e.EventTime,
            GROUP_CONCAT(DISTINCT Staff.StaffID) AS staffIDs,
            GROUP_CONCAT(DISTINCT Staff.Name) AS staffNames,
            GROUP_CONCAT(DISTINCT Contacts.ContactID) AS contactIDs,
            GROUP_CONCAT(DISTINCT Contacts.Name) AS contactNames,
            MAX(Conversations.ConversationDate) AS lastContacted
        FROM
            Events e
        LEFT JOIN EventStaff es ON es.EventID = e.EventID
        LEFT JOIN Staff ON Staff.StaffID = es.StaffID
        LEFT JOIN EventContacts ec ON ec.EventID = e.EventID
        LEFT JOIN Contacts ON Contacts.ContactID = ec.ContactID
        LEFT JOIN Connections ON Connections.ContactID = Contacts.ContactID
        LEFT JOIN Conversations ON Conversations.ConnectionID = Connections.ConnectionID
        WHERE
            es.StaffID = :staffId
        GROUP BY
            e.EventID
        ORDER BY
            e.EventTime ASC
    ";

        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':staffId', $staffId);
        $stmt->execute();

        // Fetch results
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process the fetched results to split and structure them properly
        foreach ($events as &$event) {
            // Initialize arrays for structured data
            $event['staff'] = [];
            $event['contacts'] = [];

            // Split staff and contact IDs/names using GROUP_CONCAT
            $staffIDs = explode(',', $event['staffIDs']);
            $staffNames = explode(',', $event['staffNames']);
            foreach ($staffIDs as $index => $staffID) {
                $event['staff'][] = [
                    'StaffID' => $staffID,
                    'staffName' => $staffNames[$index]
                ];
            }

            $contactIDs = explode(',', $event['contactIDs']);
            $contactNames = explode(',', $event['contactNames']);
            foreach ($contactIDs as $index => $contactID) {
                $event['contacts'][] = [
                    'ContactID' => $contactID,
                    'contactName' => $contactNames[$index],
                    'lastContacted' => isset($event['lastContacted']) ? $event['lastContacted'] : null,  // Add last contacted info
                ];
            }

            // Retrieve event goals (unchanged from before)
            $event['goals'] = $this->getEventData($event['EventID']);
        }

        return $events;
    }

    public function getRecentActivity($staffId) {
        $query = "SELECT 'Event' AS type, e.EventDetails AS description, e.EventTime AS date 
        FROM Events e
        INNER JOIN EventStaff es ON e.EventID = es.EventID
        INNER JOIN Staff s ON es.StaffID = s.StaffID
        WHERE s.StaffID = :staffId

        ORDER BY date DESC 
        LIMIT 5
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffId', $staffId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGoalProgress($staffId) {
        $query = "
        SELECT tg.Goals, 
               SUM(CASE WHEN tg.isCompleted = 1 THEN 1 ELSE 0 END) AS completed,
               COUNT(*) AS total
        FROM TaskGoals tg
        INNER JOIN Events e ON tg.EventID = e.EventID
        INNER JOIN EventStaff es ON e.EventID = es.EventID   
        INNER JOIN Staff s ON es.StaffID = s.StaffID
        WHERE s.StaffID = :staffId
        GROUP BY tg.Goals
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffId', $staffId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}

