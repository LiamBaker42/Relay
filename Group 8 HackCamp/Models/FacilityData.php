<?php
// FacilityData Model
class FacilityData {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllFacilities() {
        $query = "SELECT * FROM Facilities ORDER BY Name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchFacilities($searchTerm) {
        $query = "SELECT * FROM Facilities WHERE Name LIKE :searchTerm OR Address LIKE :searchTerm ORDER BY Name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}