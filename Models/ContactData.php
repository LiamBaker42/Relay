<?php
require_once 'Database.php';

class ContactData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Method to get all contacts when adding contacts to the events.
    public function getAllContacts($eventId) {
        // Query to select all contacts who are NOT already assigned to the event
        $stmt = $this->db->prepare("
        SELECT c.* 
        FROM contacts c
        LEFT JOIN EventContacts ec ON c.ContactID = ec.ContactID AND ec.EventID = ?
        WHERE ec.ContactID IS NULL
    ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Method to search for contacts used when searching contacts to add to the events.
    public function searchContacts($searchTerm, $eventId) {
        $stmt = $this->db->prepare("
        SELECT c.* 
        FROM contacts c
        LEFT JOIN EventContacts ec ON c.ContactID = ec.ContactID AND ec.EventID = ?
        WHERE ec.ContactID IS NULL AND c.Name LIKE ?
    ");
        $stmt->execute([$eventId, '%' . $searchTerm . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Method to return a contacts details. Used in the contact edit page to fill in the form. Also used in the contact details page.
    public function getContactDetails($contactID) {
        $stmt = $this->db->prepare("SELECT * FROM Contacts WHERE ContactID = :contactID");
        $stmt->execute(['contactID' => $contactID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Method to get contacts for a staff member along with their conversation count. Used in the staff details page.
    public function getContactsByStaff($staffId) {
        $query = "
        SELECT c.*, 
               COUNT(conv.ConversationID) AS conversationCount,
               MAX(conv.ConversationDate) AS lastContacted
        FROM Contacts c
        INNER JOIN Connections conn ON c.ContactID = conn.ContactID
        LEFT JOIN Conversations conv ON conn.ConnectionID = conv.ConnectionID
        WHERE conn.StaffID = :staffId
        GROUP BY c.ContactID
    ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffId', $staffId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Add a new contact. Used in add contactEdit page.
    public function addContact($staffId, $name, $number, $email) {
        // Start a transaction to ensure both inserts are handled together
        $this->db->beginTransaction();

        try {
            // Step 1: Check if the contact already exists in the Contacts table
            $stmt = $this->db->prepare("SELECT ContactID FROM Contacts WHERE Name = :name AND Number = :number AND Email = :email");
            $stmt->execute([
                ':name' => $name,
                ':number' => $number,
                ':email' => $email
            ]);
            $existingContact = $stmt->fetch();

            // Step 2: If the contact doesn't exist, insert it into the Contacts table
            if (!$existingContact) {
                // Insert the new contact
                $stmt = $this->db->prepare("INSERT INTO Contacts (Name, Number, Email) VALUES (:name, :number, :email)");
                $stmt->execute([
                    ':name' => $name,
                    ':number' => $number,
                    ':email' => $email
                ]);
                // Get the new ContactID
                $contactId = $this->db->lastInsertId();
            } else {
                // If contact exists, use the existing ContactID
                $contactId = $existingContact['ContactID'];
            }

            // Step 3: Check if the connection between Staff and Contact already exists
            $stmt = $this->db->prepare("SELECT ConnectionID FROM Connections WHERE StaffID = :staffId AND ContactID = :contactId");
            $stmt->execute([
                ':staffId' => $staffId,
                ':contactId' => $contactId
            ]);
            $existingConnection = $stmt->fetch();

            // If the connection doesn't exist, insert it into the Connections table
            if (!$existingConnection) {
                $stmt = $this->db->prepare("INSERT INTO Connections (StaffID, ContactID) VALUES (:staffId, :contactId)");
                $stmt->execute([
                    ':staffId' => $staffId,
                    ':contactId' => $contactId
                ]);
            }

            // Commit the transaction
            $this->db->commit();

        } catch (Exception $e) {
            // If something goes wrong, roll back the transaction
            $this->db->rollBack();
            throw $e;  // Rethrow the exception to handle it elsewhere
        }
    }

    // Update contact details. Also contactEdit page.
    public function updateContact($contactId, $name, $number, $email) {
        $stmt = $this->db->prepare("UPDATE Contacts SET Name = :name, Number = :number, Email = :email WHERE ContactID = :contactId");
        $stmt->execute([
            ':name' => $name,
            ':number' => $number,
            ':email' => $email,
            ':contactId' => $contactId
        ]);
    }

    // Delete the connection between staff and contact. Used when a contact is deleted from a staffs contact list.
    public function deleteContactConnection($staffId, $contactId) {
        $stmt = $this->db->prepare("DELETE FROM Connections WHERE StaffID = :staffId AND ContactID = :contactId");
        $stmt->execute([
            ':staffId' => $staffId,
            ':contactId' => $contactId
        ]);
    }


    // Method to get ConnectionID by ContactID. Used in the contactDetails page to get all the connections of that contact.
    public function getConnectionIdByContact($contactID, $staffID) {
        $query = "
        SELECT ConnectionID 
        FROM Connections 
        WHERE ContactID = :contactID 
        AND StaffID = :staffID
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':contactID', $contactID, PDO::PARAM_INT);
        $stmt->bindParam(':staffID', $staffID, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['ConnectionID'] ?? null; // Return ConnectionID or null if not found
    }


// Method to get Conversations by ConnectionID
    public function getConversationsByConnection($connectionId) {
        $query = "
        SELECT conv.*, ct.ConversationType
        FROM Conversations conv
        JOIN ConversationsType ct ON conv.TypeID = ct.TypeID
        WHERE conv.ConnectionID = :connectionId
    ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':connectionId', $connectionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //Method to get the conversations of the contact with their last contacted date. also contact details page.
    public function getConversationsWithLastContact($contactId) {
        $query = "
        SELECT c.ConversationID, ct.ConversationType, c.Summary, c.ConversationDate
        FROM Conversations c
        JOIN ConversationsType ct ON c.TypeID = ct.TypeID
        WHERE c.ConnectionID IN (
            SELECT ConnectionID FROM Connections WHERE ContactID = :contactId
        )
        ORDER BY c.ConversationDate DESC"; // Order by date to get the most recent

        $stmt = $this->db->prepare($query);
        $stmt->execute([':contactId' => $contactId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}