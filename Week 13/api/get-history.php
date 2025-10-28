<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Initialize session
initSession();

header('Content-Type: application/json');

// Helper function to safely prepare statements
function safePrepare($conn, $query, $context = '') {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Failed to prepare query" . ($context ? " ($context)" : "") . ": " . $conn->error);
        error_log("Query was: " . substr($query, 0, 200));
        return false;
    }
    return $stmt;
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$activities = [];

try {

if ($userType === 'tenant') {
    // Get tenant ID
    $stmt = safePrepare($conn, "SELECT id FROM tenants WHERE user_id = ?", "get tenant id");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare tenant query");
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tenant) {
        echo json_encode([
            'success' => true,
            'activities' => [],
            'has_more' => false,
            'total' => 0,
            'message' => 'No tenant profile found'
        ]);
        exit;
    }
    
    $tenantId = $tenant['id'];
    
    // RESERVATIONS
    if ($category === 'all' || $category === 'reservations') {
        $query = "
            SELECT 
                pr.id,
                'reservation' as activity_type,
                pr.status,
                pr.created_at as activity_date,
                pr.move_in_date,
                pr.lease_duration,
                pr.reservation_fee,
                pr.approval_date,
                pr.cancellation_reason,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                p.address as property_address,
                CONCAT(u.first_name, ' ', u.last_name) as landlord_name,
                pi.image_url as property_image
            FROM property_reservations pr
            JOIN properties p ON pr.property_id = p.id
            JOIN landlords l ON p.landlord_id = l.id
            JOIN users u ON l.user_id = u.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE pr.tenant_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(pr.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(pr.created_at) <= ?";
        }
        
        $query .= " ORDER BY pr.created_at DESC";
        
        $stmt = safePrepare($conn, $query, "property requests");
        if ($stmt === false) {
            // Skip this section if query fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $tenantId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $tenantId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $tenantId, $dateTo);
            } else {
                $stmt->bind_param("i", $tenantId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // VISITS
    if ($category === 'all' || $category === 'visits') {
        $query = "
            SELECT 
                bv.id,
                'visit' as activity_type,
                bv.status,
                bv.created_at as activity_date,
                bv.visit_date,
                bv.visit_time,
                bv.message as visit_message,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                p.address as property_address,
                CONCAT(u.first_name, ' ', u.last_name) as landlord_name,
                pi.image_url as property_image
            FROM booking_visits bv
            JOIN properties p ON bv.property_id = p.id
            JOIN landlords l ON p.landlord_id = l.id
            JOIN users u ON l.user_id = u.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE bv.tenant_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(bv.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(bv.created_at) <= ?";
        }
        
        $query .= " ORDER BY bv.created_at DESC";
        
        $stmt = safePrepare($conn, $query, "visits");
        if ($stmt === false) {
            // Skip this section if query fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $tenantId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $tenantId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $tenantId, $dateTo);
            } else {
                $stmt->bind_param("i", $tenantId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // SEARCHES / BROWSING
    if ($category === 'all' || $category === 'searches') {
        $query = "
            SELECT 
                bh.id,
                'search' as activity_type,
                'viewed' as status,
                bh.viewed_at as activity_date,
                bh.view_duration,
                bh.saved,
                bh.contact_clicked,
                bh.source,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                pi.image_url as property_image
            FROM browsing_history bh
            JOIN properties p ON bh.property_id = p.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE bh.user_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(bh.viewed_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(bh.viewed_at) <= ?";
        }
        
        $query .= " ORDER BY bh.viewed_at DESC LIMIT 50";
        
        $stmt = $conn->prepare($query);
        if ($dateFrom && $dateTo) {
            $stmt->bind_param("iss", $userId, $dateFrom, $dateTo);
        } elseif ($dateFrom) {
            $stmt->bind_param("is", $userId, $dateFrom);
        } elseif ($dateTo) {
            $stmt->bind_param("is", $userId, $dateTo);
        } else {
            $stmt->bind_param("i", $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Group by date
        $searchesByDate = [];
        while ($row = $result->fetch_assoc()) {
            $date = date('Y-m-d', strtotime($row['activity_date']));
            if (!isset($searchesByDate[$date])) {
                $searchesByDate[$date] = [
                    'date' => $date,
                    'properties' => [],
                    'saved_count' => 0,
                    'contacted_count' => 0
                ];
            }
            $searchesByDate[$date]['properties'][] = $row;
            if ($row['saved']) $searchesByDate[$date]['saved_count']++;
            if ($row['contact_clicked']) $searchesByDate[$date]['contacted_count']++;
        }
        
        // Convert to activities
        foreach ($searchesByDate as $date => $data) {
            $activities[] = [
                'activity_type' => 'search_summary',
                'activity_date' => $date . ' 00:00:00',
                'properties_count' => count($data['properties']),
                'saved_count' => $data['saved_count'],
                'contacted_count' => $data['contacted_count'],
                'top_property' => $data['properties'][0]['property_title'] ?? null,
                'status' => 'completed'
            ];
        }
    }
    
    // USER INTERACTIONS (saves, unsaves, contacts, shares)
    if ($category === 'all' || $category === 'interactions') {
        $query = "
            SELECT 
                ui.id,
                ui.interaction_type as activity_type,
                ui.created_at as activity_date,
                'completed' as status,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                p.address as property_address,
                CONCAT(u.first_name, ' ', u.last_name) as landlord_name,
                pi.image_url as property_image
            FROM user_interactions ui
            JOIN properties p ON ui.property_id = p.id
            JOIN landlords l ON p.landlord_id = l.id
            JOIN users u ON l.user_id = u.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE ui.user_id = ?
            AND ui.interaction_type IN ('save', 'unsave', 'contact', 'share', 'review')
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(ui.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(ui.created_at) <= ?";
        }
        
        $query .= " ORDER BY bh.viewed_at DESC LIMIT 50";
        
        $stmt = safePrepare($conn, $query, "browsing history");
        if ($stmt === false) {
            // Skip this section if query fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $userId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $userId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $userId, $dateTo);
            } else {
                $stmt->bind_param("i", $userId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // AI ACTIVITY
    if ($category === 'all' || $category === 'ai-activity') {
        // Check for AI recommendation updates
        $query = "
            SELECT 
                'ai_recommendation' as activity_type,
                calculated_at as activity_date,
                'active' as status,
                cosine_similarity as similarity_score,
                COUNT(*) as recommendations_count
            FROM similarity_scores
            WHERE tenant_id = ? AND is_valid = 1
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(calculated_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(calculated_at) <= ?";
        }
        
        $query .= " GROUP BY DATE(calculated_at) ORDER BY calculated_at DESC LIMIT 10";
        
        $stmt = $conn->prepare($query);
        
        // Check if prepare was successful
        if ($stmt === false) {
            error_log("Failed to prepare AI activity query: " . $conn->error);
            // Skip this section if query preparation fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $tenantId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $tenantId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $tenantId, $dateTo);
            } else {
                $stmt->bind_param("i", $tenantId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $row['avg_score'] = round($row['similarity_score'] * 100);
                $activities[] = $row;
            }
            
            $stmt->close();
        }
    }
    
} elseif ($userType === 'landlord') {
    // Get landlord ID
    $stmt = safePrepare($conn, "SELECT id FROM landlords WHERE user_id = ?", "get landlord id");
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare landlord query");
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $landlord = $result->fetch_assoc();
    $stmt->close();
    
    if (!$landlord) {
        echo json_encode([
            'success' => true,
            'activities' => [],
            'has_more' => false,
            'total' => 0,
            'message' => 'No landlord profile found'
        ]);
        exit;
    }
    
    $landlordId = $landlord['id'];
    
    // RESERVATIONS (for landlord's properties)
    if ($category === 'all' || $category === 'reservations') {
        $query = "
            SELECT 
                pr.id,
                'reservation' as activity_type,
                pr.status,
                pr.created_at as activity_date,
                pr.move_in_date,
                pr.lease_duration,
                pr.reservation_fee,
                pr.approval_date,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
                u.email as tenant_email,
                pi.image_url as property_image
            FROM property_reservations pr
            JOIN properties p ON pr.property_id = p.id
            JOIN tenants t ON pr.tenant_id = t.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE p.landlord_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(pr.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(pr.created_at) <= ?";
        }
        
        $query .= " ORDER BY pr.created_at DESC";
        
        $stmt = safePrepare($conn, $query, "landlord property reservations");
        if ($stmt === false) {
            // Skip this section if query fails  
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $landlordId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $landlordId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $landlordId, $dateTo);
            } else {
                $stmt->bind_param("i", $landlordId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // VISITS (for landlord's properties)
    if ($category === 'all' || $category === 'visits') {
        $query = "
            SELECT 
                bv.id,
                'visit' as activity_type,
                bv.status,
                bv.created_at as activity_date,
                bv.visit_date,
                bv.visit_time,
                bv.message as visit_message,
                bv.phone_number as tenant_phone,
                p.title as property_title,
                p.city as property_city,
                CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
                pi.image_url as property_image
            FROM booking_visits bv
            JOIN properties p ON bv.property_id = p.id
            JOIN tenants t ON bv.tenant_id = t.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            WHERE p.landlord_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(bv.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(bv.created_at) <= ?";
        }
        
        $query .= " ORDER BY bv.created_at DESC";
        
        $stmt = safePrepare($conn, $query, "landlord visits");
        if ($stmt === false) {
            // Skip this section if query fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $landlordId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $landlordId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $landlordId, $dateTo);
            } else {
                $stmt->bind_param("i", $landlordId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // PROPERTY VIEWS/INTEREST
    if ($category === 'all' || $category === 'searches') {
        $query = "
            SELECT 
                'property_views' as activity_type,
                DATE(bh.viewed_at) as activity_date,
                p.id as property_id,
                p.title as property_title,
                COUNT(*) as views_count,
                SUM(bh.saved) as saves_count,
                SUM(bh.contact_clicked) as contacts_count,
                'completed' as status
            FROM browsing_history bh
            JOIN properties p ON bh.property_id = p.id
            WHERE p.landlord_id = ?
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(bh.viewed_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(bh.viewed_at) <= ?";
        }
        
        $query .= " GROUP BY DATE(bh.viewed_at), p.id ORDER BY bh.viewed_at DESC LIMIT 50";
        
        $stmt = safePrepare($conn, $query, "landlord property views");
        if ($stmt === false) {
            // Skip this section if query fails
        } else {
            if ($dateFrom && $dateTo) {
                $stmt->bind_param("iss", $landlordId, $dateFrom, $dateTo);
            } elseif ($dateFrom) {
                $stmt->bind_param("is", $landlordId, $dateFrom);
            } elseif ($dateTo) {
                $stmt->bind_param("is", $landlordId, $dateTo);
            } else {
                $stmt->bind_param("i", $landlordId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
        }
    }
    
    // USER INTERACTIONS ON LANDLORD'S PROPERTIES
    if ($category === 'all' || $category === 'interactions') {
        $query = "
            SELECT 
                ui.id,
                ui.interaction_type as activity_type,
                ui.created_at as activity_date,
                'completed' as status,
                p.title as property_title,
                p.rent_amount as property_price,
                p.city as property_city,
                CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
                pi.image_url as property_image
            FROM user_interactions ui
            JOIN properties p ON ui.property_id = p.id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
            LEFT JOIN users u ON ui.user_id = u.id
            WHERE p.landlord_id = ?
            AND ui.interaction_type IN ('save', 'unsave', 'contact', 'share', 'review')
        ";
        
        if ($dateFrom) {
            $query .= " AND DATE(ui.created_at) >= ?";
        }
        if ($dateTo) {
            $query .= " AND DATE(ui.created_at) <= ?";
        }
        
        $query .= " ORDER BY ui.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if ($dateFrom && $dateTo) {
            $stmt->bind_param("iss", $landlordId, $dateFrom, $dateTo);
        } elseif ($dateFrom) {
            $stmt->bind_param("is", $landlordId, $dateFrom);
        } elseif ($dateTo) {
            $stmt->bind_param("is", $landlordId, $dateTo);
        } else {
            $stmt->bind_param("i", $landlordId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
}

// Sort all activities by date
usort($activities, function($a, $b) {
    return strtotime($b['activity_date']) - strtotime($a['activity_date']);
});

// Apply pagination
$total = count($activities);
$activities = array_slice($activities, $offset, $limit);

} catch (Exception $e) {
    // Log the error and return a friendly message
    error_log("History API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while loading activities: ' . $e->getMessage(),
        'activities' => [],
        'total' => 0,
        'has_more' => false
    ]);
    if ($conn) $conn->close();
    exit;
}

$conn->close();

echo json_encode([
    'success' => true,
    'activities' => $activities,
    'total' => $total,
    'has_more' => ($offset + $limit) < $total
]);
?>
