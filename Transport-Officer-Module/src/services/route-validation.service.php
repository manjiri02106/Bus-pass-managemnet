<?php
// route-validation.service.php - Service class to validate route distance and handle dispatcher logs

class RouteValidationService {
    
    /**
     * Verifies if travel distance is within standard limits.
     * @param float $distance Route distance
     * @param float $maxDistance Threshold limit from config
     * @return bool
     */
    public function validateDistance($distance, $maxDistance) {
        return $distance <= $maxDistance;
    }
    
    /**
     * Determines the appropriate routing queue based on route distance rules.
     * @param float $distance
     * @param float $maxDistance
     * @return string Routing queue name
     */
    public function determineRoutingQueue($distance, $maxDistance) {
        if ($distance > $maxDistance) {
            return 'Admin Exception';
        }
        return 'Transport Dept';
    }
    
    /**
     * Logs validation results in database.
     * @param PDO $pdo
     * @param int $applicationId
     * @param int $routeId
     * @param bool $isValid
     * @param string $notes
     * @return bool
     */
    public function recordValidationLog(PDO $pdo, $applicationId, $routeId, $isValid, $notes) {
        $stmt = $pdo->prepare("
            INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$applicationId, $routeId, $isValid ? 1 : 0, $notes]);
    }
    
    /**
     * Reroutes application to the correct department queue.
     * @param PDO $pdo
     * @param int $applicationId
     * @param string $newDept
     * @param string $comments
     * @param int $officerId
     * @return bool
     */
    public function dispatchWorkflowRoute(PDO $pdo, $applicationId, $newDept, $comments, $officerId) {
        if (!in_array($newDept, ['Transport Dept', 'Academic HOD', 'Admin Exception'])) {
            throw new InvalidArgumentException("Invalid department destination: $newDept");
        }
        
        // Fetch current department
        $stmt_curr = $pdo->prepare("SELECT routing_dept FROM applications WHERE id = ?");
        $stmt_curr->execute([$applicationId]);
        $oldDept = $stmt_curr->fetchColumn();
        
        // Update applications table
        $stmt_upd = $pdo->prepare("UPDATE applications SET routing_dept = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt_upd->execute([$newDept, $applicationId]);
        
        if ($success) {
            $notes = "Workflow Route: Routed from '$oldDept' to '$newDept'. Officer comment: $comments";
            $this->recordValidationLog($pdo, $applicationId, 0, true, $notes);
        }
        
        return $success;
    }
}
?>
