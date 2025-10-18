<?php
require_once 'Database.php';
class ConversationData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    //Method to return all types of conversations, used in the converastion edit to fill the types box.
    public function getAllConversationTypes() {
        $query = "SELECT TypeID, ConversationType FROM ConversationsType";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to fetch conversation details. used in the edit to fill the form.
    public function getConversationDetails($conversationID) {
        $query = "
        SELECT 
            c.conversationID, 
            c.summary, 
            s.Name AS staffName, 
            co.Name AS contactName,
            c.ConversationDate,
            c.TypeID,
            ct.ConversationType
        FROM Conversations c
        LEFT JOIN Connections con ON c.connectionID = con.ConnectionID
        LEFT JOIN Staff s ON con.StaffID = s.StaffID
        LEFT JOIN Contacts co ON con.ContactID = co.ContactID
        LEFT JOIN ConversationsType ct ON ct.TypeID = c.TypeID
        WHERE c.conversationID = :conversationID
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':conversationID' => $conversationID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateConversationSummary($conversationID, $summary) {
        $query = "UPDATE Conversations SET summary = :summary WHERE conversationID = :conversationID";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':summary' => $summary,
            ':conversationID' => $conversationID
        ]);
    }

    // Add new conversation with ConversationDate. used to add new converstaions.
    public function addConversation($staffId, $contactId, $typeId, $summary, $conversationDate) {
        // Get connection ID from the connection table
        $query = "SELECT ConnectionID FROM Connections WHERE StaffID = :staffId AND ContactID = :contactId";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':staffId' => $staffId, ':contactId' => $contactId]);
        $connection = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$connection) {
            exit('No connection found for the provided staff and contact IDs.');
        }

        // Insert new conversation with ConversationDate
        $query = "INSERT INTO Conversations (ConnectionID, TypeID, Summary, ConversationDate) 
                  VALUES (:connectionID, :typeID, :summary, :conversationDate)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':connectionID' => $connection['ConnectionID'],
            ':typeID' => $typeId,
            ':summary' => $summary,
            ':conversationDate' => $conversationDate // Ensure this is passed as a string formatted as 'YYYY-MM-DD HH:MM:SS'
        ]);
    }

    // Update existing conversation with ConversationDate. updates convos.
    public function updateConversation($conversationId, $typeId, $summary, $conversationDate) {
        // Update the existing conversation including the ConversationDate
        $query = "UPDATE Conversations 
                  SET TypeID = :typeID, Summary = :summary, ConversationDate = :conversationDate 
                  WHERE ConversationID = :conversationID";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':conversationID' => $conversationId,
            ':typeID' => $typeId,
            ':summary' => $summary,
            ':conversationDate' => $conversationDate // Pass the new ConversationDate here
        ]);
    }

    // Fetch conversations already linked to a specific goal
    public function getConversationsForGoal($goalID) {
        $stmt = $this->db->prepare("
        SELECT c.conversationID, c.summary, co.Name as contactName, s.Name as staffName
        FROM Conversations c
        INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
        INNER JOIN Convo_Tasks ct ON c.conversationID = ct.conversationID
        INNER JOIN Contacts co ON cn.contactID = co.ContactID
        INNER JOIN Staff s ON cn.staffID = s.StaffID
        WHERE ct.goalsID = ?
    ");
        $stmt->execute([$goalID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Link a conversation to a goal
    public function linkConversationToGoal($conversationID, $goalID) {
        $stmt = $this->db->prepare("
        INSERT INTO Convo_Tasks (conversationID, goalsID)
        VALUES (?, ?)
    ");
        return $stmt->execute([$conversationID, $goalID]);
    }

    // Unlink a conversation from a goal
    public function unlinkConversationFromGoal($conversationID, $goalID) {
        $stmt = $this->db->prepare("
        DELETE FROM Convo_Tasks
        WHERE conversationID = ? AND goalsID = ?
    ");
        return $stmt->execute([$conversationID, $goalID]);
    }


    public function getConversationsForStaff($staffIds) {
        // Check if $staffIds is an array
        if (is_array($staffIds)) {
            $staffIdsString = implode(',', $staffIds); // Convert array to a comma-separated string
            $query = "
        SELECT c.conversationID
        FROM Conversations c
        INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
        INNER JOIN Staff s ON cn.staffID = s.StaffID
        WHERE s.staffID IN ($staffIdsString)
        ";
            $isArray = true; // Flag for handling return type
        } else {
            $query = "
        SELECT c.summary, c.ConversationDate, s.Name AS staffName, co.Name AS contactName
        FROM Conversations c
        INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
        INNER JOIN Staff s ON cn.staffID = s.StaffID
        INNER JOIN Contacts co ON co.ContactID = cn.ContactID
        WHERE s.staffID = :staffID
        ORDER BY c.ConversationDate DESC
        LIMIT 5
        ";
            $isArray = false; // Flag for handling return type
        }

        $stmt = $this->db->prepare($query);

        // Bind parameter if $staffIds is not an array
        if (!$isArray) {
            $stmt->bindParam(':staffID', $staffIds, PDO::PARAM_INT);
        }

        $stmt->execute();

        // Return based on the flag
        if ($isArray) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // Return conversation IDs for arrays
        } else {
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return detailed conversation data for a single ID
        }
    }


    public function getConversationsForContacts($contactIds) {
        $contactIdsString = implode(',', $contactIds); // Convert array to a comma-separated string
        $query = "
        SELECT c.conversationID
        FROM Conversations c
        INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
        INNER JOIN Contacts co ON cn.contactID = co.ContactID
        WHERE co.contactID IN ($contactIdsString)
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLinkedConversations($goalID) {
        $query = "
        SELECT conversationID
        FROM Convo_Tasks
        WHERE goalsID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$goalID]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableConversationsForGoal($goalID, $staffIds, $contactIds) {
        // Get conversations related to staff
        $staffConversations = $this->getConversationsForStaff($staffIds);
        // Get conversations related to contacts
        $contactConversations = $this->getConversationsForContacts($contactIds);
        // Get linked conversations for the goal
        $linkedConversations = $this->getLinkedConversations($goalID);

        // Find the intersection of staff and contact conversations, excluding those linked to the goal
        $availableConversations = array_intersect($staffConversations, $contactConversations);
        $availableConversations = array_diff($availableConversations, $linkedConversations);

        // If no available conversations, return empty
        if (empty($availableConversations)) {
            return []; // Return empty if no conversations are available
        }

        // Convert the array of available conversations into a comma-separated string
        $availableConversationsString = implode(',', $availableConversations);

        // Build the SQL query directly with the available conversation IDs
        $query = "
        SELECT c.conversationID, c.summary, co.Name as contactName, s.Name as staffName
        FROM Conversations c
        INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
        INNER JOIN Contacts co ON cn.contactID = co.ContactID
        INNER JOIN Staff s ON cn.staffID = s.StaffID
        WHERE c.conversationID IN ($availableConversationsString)
    ";

        // Debugging: Output the final query and parameters
        echo "Prepared Query: " . $query . PHP_EOL;
        var_dump($availableConversationsString); // Ensure correct parameters are being passed

        // Execute the query directly
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        // Fetch and return the results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalConversations($staffID) {
        $query = "SELECT COUNT(c.ConversationID) AS total 
              FROM Conversations c
              INNER JOIN Connections cn ON c.ConnectionID = cn.ConnectionID
              INNER JOIN Staff s ON cn.StaffID = s.StaffID
              WHERE s.StaffID = :staffID";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffID', $staffID, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }



}
