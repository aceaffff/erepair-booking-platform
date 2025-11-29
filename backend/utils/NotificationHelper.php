<?php

class NotificationHelper {
    
    public static function createNotification(PDO $db, int $userId, string $type, string $title, string $message, ?string $link = null): bool {
        try {
            // Ensure notifications table exists with correct structure
            $db->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$userId, $title, $message, $type, $link]);
            
            if ($result) {
                error_log("Notification created successfully: User {$userId}, Type: {$type}, Title: {$title}");
            } else {
                error_log("Failed to create notification: User {$userId}, Type: {$type}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    public static function notifyBookingApproved(PDO $db, int $customerId, string $shopName, string $service, string $bookingDate, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'booking_approved',
            '✅ Booking Approved!',
            "Your booking for {$service} at {$shopName} on {$bookingDate} has been approved.",
            $link
        );
    }
    
    public static function notifyBookingRejected(PDO $db, int $customerId, string $shopName, string $service, ?int $bookingId = null, ?string $reason = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        
        $message = "Your booking for {$service} at {$shopName} has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        } else {
            $message .= " Please contact the shop for more information.";
        }
        
        return self::createNotification(
            $db,
            $customerId,
            'booking_rejected',
            '❌ Booking Rejected',
            $message,
            $link
        );
    }
    
    public static function notifyTechnicianAssigned(PDO $db, int $customerId, string $technicianName, string $service, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'technician_assigned',
            '👨‍🔧 Technician Assigned',
            "{$technicianName} has been assigned to your {$service} repair.",
            $link
        );
    }
    
    public static function notifyBookingCompleted(PDO $db, int $customerId, string $shopName, string $service, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'booking_completed',
            '🎉 Repair Completed!',
            "Your {$service} repair at {$shopName} is complete. Your device is ready for pickup!",
            $link
        );
    }
    
    public static function notifyBookingInProgress(PDO $db, int $customerId, string $shopName, string $service, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'booking_in_progress',
            '🔧 Repair Work Started!',
            "Our technician has started working on your {$service} repair at {$shopName}. We'll keep you updated on the progress.",
            $link
        );
    }
    
    public static function notifyNewJob(PDO $db, int $technicianUserId, string $service, string $customerName, string $bookingDate, ?int $bookingId = null): bool {
        $link = $bookingId ? "tech_jobs.php?booking_id={$bookingId}" : "tech_jobs.php";
        return self::createNotification(
            $db,
            $technicianUserId,
            'new_job',
            '🔧 New Job Assigned',
            "You have been assigned to {$service} for {$customerName} on {$bookingDate}.",
            $link
        );
    }
    
    public static function notifyNewBooking(PDO $db, int $shopOwnerUserId, string $customerName, string $service, string $bookingDate, ?int $bookingId = null): bool {
        $link = $bookingId ? "booking_manage.php?booking_id={$bookingId}" : "booking_manage.php";
        return self::createNotification(
            $db,
            $shopOwnerUserId,
            'new_booking',
            '📅 New Booking Request',
            "{$customerName} has requested {$service} on {$bookingDate}.",
            $link
        );
    }
    
    public static function notifyShopApproval(PDO $db, int $shopOwnerUserId, string $shopName, bool $approved, string $rejectionReason = ''): bool {
        $link = "shop_dashboard.php";
        if ($approved) {
            return self::createNotification(
                $db,
                $shopOwnerUserId,
                'shop_approved',
                '🎉 Shop Approved!',
                "Your shop '{$shopName}' has been approved by the admin. You can now start receiving bookings!",
                $link
            );
        } else {
            $message = "Your shop application for '{$shopName}' was not approved.";
            if (!empty($rejectionReason)) {
                $message .= " Reason: " . htmlspecialchars($rejectionReason);
            } else {
                $message .= " Please contact support for more information.";
            }
            return self::createNotification(
                $db,
                $shopOwnerUserId,
                'shop_rejected',
                '❌ Shop Application Status',
                $message,
                $link
            );
        }
    }
    
    public static function notifyRescheduleAccepted(PDO $db, int $customerId, string $newDate, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'reschedule_accepted',
            '✅ Reschedule Approved',
            "Your reschedule request has been accepted. New date: {$newDate}",
            $link
        );
    }
    
    public static function notifyRescheduleDeclined(PDO $db, int $customerId, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'reschedule_declined',
            '❌ Reschedule Declined',
            "Your reschedule request has been declined. Please contact the shop for assistance.",
            $link
        );
    }
    
    // New notifications for enhanced booking workflow
    
    public static function notifyBookingDiagnosed(PDO $db, int $customerId, string $shopName, float $estimatedCost, int $estimatedDays, ?int $bookingId = null): bool {
        $link = $bookingId ? "customer_bookings.php?booking_id={$bookingId}" : "customer_bookings.php";
        return self::createNotification(
            $db,
            $customerId,
            'booking_diagnosed',
            '🔍 Diagnosis Complete!',
            "Your device has been diagnosed at {$shopName}. Estimated cost: ₱" . number_format($estimatedCost, 2) . " | Estimated time: {$estimatedDays} day(s). Please review and confirm.",
            $link
        );
    }
    
    public static function notifyCustomerConfirmed(PDO $db, int $shopOwnerUserId, string $customerName, string $service, ?int $bookingId = null): bool {
        $link = $bookingId ? "booking_manage.php?booking_id={$bookingId}" : "booking_manage.php";
        return self::createNotification(
            $db,
            $shopOwnerUserId,
            'customer_confirmed',
            '✅ Customer Confirmed Booking',
            "{$customerName} has accepted the quotation for {$service}. Please review and approve.",
            $link
        );
    }
    
    public static function notifyCustomerCancelled(PDO $db, int $shopOwnerUserId, string $customerName, string $service, string $reason = '', ?int $bookingId = null): bool {
        $message = "{$customerName} has cancelled the booking for {$service}.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $link = $bookingId ? "booking_manage.php?booking_id={$bookingId}" : "booking_manage.php";
        return self::createNotification(
            $db,
            $shopOwnerUserId,
            'customer_cancelled',
            '❌ Customer Cancelled Booking',
            $message,
            $link
        );
    }
    
    public static function notifyDiagnosisRequired(PDO $db, int $shopOwnerUserId, string $customerName, string $deviceType, ?int $bookingId = null): bool {
        $link = $bookingId ? "booking_manage.php?booking_id={$bookingId}" : "booking_manage.php";
        return self::createNotification(
            $db,
            $shopOwnerUserId,
            'diagnosis_required',
            '🔧 New Device for Diagnosis',
            "{$customerName} has submitted a {$deviceType} for diagnosis. Please review and provide quotation.",
            $link
        );
    }
}
?>

