<?php
require_once 'Database.php';

class StaffData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Fetch all staff used to display all staff on staffList and for the goals page staff view
    public function getAllStaff() {
        $sql = "
        SELECT staff.*, COUNT(connections.ContactID) AS contactCount
        FROM staff
        LEFT JOIN connections ON staff.StaffID = connections.StaffID
        GROUP BY staff.StaffID
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to get details of a specific staff member, including their type.
    //Used on the Staff Details page to show their contact info, and in the staff edit page to fill in the form.
    public function getStaffDetails($staffId) {
        $query = "
            SELECT s.*, st.Type 
            FROM Staff s
            INNER JOIN StaffType st ON s.TypeID = st.TypeID
            WHERE s.StaffID = :staffId
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':staffId', $staffId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch staff types (used for the dropdown in the update form)
    public function getStaffTypes() {
        $query = "SELECT TypeID, Type FROM StaffType";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search staff by name, email, or phone. used in the StaffList
    public function searchStaff($searchTerm) {
        $stmt = $this->db->prepare("SELECT 
                                        Staff.*, 
                                        COUNT(Connections.ContactID) AS contactCount
                                    FROM Staff
                                    LEFT JOIN Connections ON Staff.StaffID = Connections.StaffID
                                    LEFT JOIN Contacts ON Connections.ContactID = Contacts.ContactID
                                    WHERE Staff.Name LIKE :searchTerm
                                    OR Staff.Email LIKE :searchTerm  
                                    OR Staff.Phone LIKE :searchTerm  
                                    GROUP BY Staff.StaffID");
        $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add new staff Used to add new staff members in staff edit
    public function addStaff($name, $email, $phone, $typeId) {
        $stmt = $this->db->prepare("INSERT INTO Staff (Name, Email, Phone, TypeID) VALUES (:name, :email, :phone, :typeId)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':typeId' => $typeId
        ]);
    }

    // Update staff details including the staff type. also in staff edit if a staff id is given
    public function updateStaff($staffId, $name, $email, $phone, $typeId) {
        $stmt = $this->db->prepare("UPDATE Staff SET Name = :name, Email = :email, Phone = :phone, TypeID = :typeId WHERE StaffID = :staffId");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':typeId' => $typeId,
            ':staffId' => $staffId
        ]);
    }

    // Delete a staff member also used in staff edit only visable if staff id is given
    public function deleteStaff($staffId) {
        $stmt = $this->db->prepare("DELETE FROM Staff WHERE StaffID = :staffId");
        $stmt->execute([':staffId' => $staffId]);
        if ($stmt->rowCount() > 0) {
            echo "Staff member deleted successfully.";
        } else {
            echo "No staff member found with that ID or the staff was already deleted.";
        }
    }

    // Method to get all staff used in adding staff to events checks if their not part of event first.
    public function getAllStaffForEvents($eventId) {
        // Query to select all staff who are NOT already assigned to the event
        $stmt = $this->db->prepare("
        SELECT s.* 
        FROM Staff s
        LEFT JOIN EventStaff es ON s.StaffID = es.StaffID AND es.EventID = ?
        WHERE es.StaffID IS NULL
    ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Method to search for staff used in the add staff to events page when the list is searched.
    public function searchStaffForEvents($eventId, $searchTerm) {
        // Query to search for staff who are NOT already assigned to the event
        $stmt = $this->db->prepare("
        SELECT s.* 
        FROM Staff s
        LEFT JOIN EventStaff es ON s.StaffID = es.StaffID AND es.EventID = ?
        WHERE es.StaffID IS NULL AND s.Name LIKE ?
    ");
        $stmt->execute([$eventId, '%' . $searchTerm . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

