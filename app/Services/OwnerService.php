<?php
namespace App\Services;

use PDO;

class OwnerService
{
    private PDO $db;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->bookingService = new BookingService();
    }

    // ---------- DASHBOARD KPIs ----------

    public function getKpis(int $propertyId): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        $totalRooms = (int)$stmt->fetchColumn();

        // Projected occupancy based on confirmed/checked-in bookings overlapping today
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT b.room_id)
            FROM bookings b
            WHERE b.property_id = ? AND b.status IN ('confirmed','checked_in')
              AND CURDATE() >= b.check_in_date AND CURDATE() < b.check_out_date
        ");
        $stmt->execute([$propertyId]);
        $bookedRooms = (int)$stmt->fetchColumn();

        // Physical occupancy (checked-in guests)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE property_id = ? AND status = 'occupied'");
        $stmt->execute([$propertyId]);
        $occupied = (int)$stmt->fetchColumn();

        $occupancy = $totalRooms > 0 ? round(($bookedRooms / $totalRooms) * 100, 1) : 0;

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(p.amount),0)
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE b.property_id = ? AND p.status = 'completed'
        ");
        $stmt->execute([$propertyId]);
        $revenue = (float)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT COALESCE(AVG(total),0) FROM (
                SELECT SUM(p.amount) as total
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                WHERE b.property_id = ? AND p.status = 'completed'
                GROUP BY b.id
            ) t
        ");
        $stmt->execute([$propertyId]);
        $adr = round((float)$stmt->fetchColumn(), 2);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ? AND status NOT IN ('cancelled')");
        $stmt->execute([$propertyId]);
        $bookings = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE property_id = ? AND status IN ('confirmed','checked_in') AND check_in_date = CURDATE()
        ");
        $stmt->execute([$propertyId]);
        $dueToday = (int)$stmt->fetchColumn();

        return [
            'occupancy'         => $occupancy,
            'revenue'           => $revenue,
            'adr'               => $adr,
            'bookings'          => $bookings,
            'due_today'         => $dueToday,
            'total_rooms'       => $totalRooms,
            'booked_rooms'      => $bookedRooms,
            'occupied_rooms'    => $occupied,
        ];
    }

    public function getOccupancyTrend(int $propertyId, int $days = 7): array
    {
        $stmt = $this->db->prepare("
            SELECT d.date,
                   COALESCE(COUNT(DISTINCT b.room_id), 0) as occupied_cnt,
                   r.total_rooms
            FROM (
                SELECT CURDATE() - INTERVAL (a.a) DAY AS date
                FROM (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) a
            ) d
            CROSS JOIN (
                SELECT COUNT(*) as total_rooms FROM rooms WHERE property_id = ?
            ) r
            LEFT JOIN bookings b ON b.property_id = ? AND b.status IN ('confirmed','checked_in')
                AND d.date >= b.check_in_date AND d.date < b.check_out_date
            GROUP BY d.date, r.total_rooms
            ORDER BY d.date ASC
            LIMIT ?
        ");
        $stmt->execute([$propertyId, $propertyId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------- RATE & INVENTORY ----------

    public function getRoomsWithRates(int $propertyId, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT r.id, r.room_number, r.room_type, r.base_rate, r.status as room_status,
                   rr.rate as override_rate, rr.status as override_status
            FROM rooms r
            LEFT JOIN room_rates rr ON rr.room_id = r.id AND rr.date = ?
            WHERE r.property_id = ?
            ORDER BY r.room_number
        ");
        $stmt->execute([$date, $propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyRateOverride(int $roomId, string $date, ?float $rate, ?string $status): void
    {
        if ($rate === null && $status === null) {
            $stmt = $this->db->prepare("DELETE FROM room_rates WHERE room_id = ? AND date = ?");
            $stmt->execute([$roomId, $date]);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO room_rates (room_id, date, rate, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rate = VALUES(rate), status = VALUES(status)
        ");
        $stmt->execute([$roomId, $date, $rate, $status]);
    }

    public function applyBulkRateOverride(int $roomId, string $startDate, string $endDate, ?float $rate, ?string $status): void
    {
        $this->db->beginTransaction();
        try {
            $period = new \DatePeriod(
                new \DateTime($startDate),
                new \DateInterval('P1D'),
                (new \DateTime($endDate))->modify('+1 day')
            );
            $stmt = $this->db->prepare("
                INSERT INTO room_rates (room_id, date, rate, status)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rate = VALUES(rate), status = VALUES(status)
            ");
            foreach ($period as $dt) {
                $stmt->execute([$roomId, $dt->format('Y-m-d'), $rate, $status]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ---------- ROOM CRUD ----------

    public function createRoom(int $propertyId, array $data): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO rooms (property_id, room_number, room_type, base_rate, occupancy, bed_count, ac, status, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $propertyId,
            $data['room_number'],
            $data['room_type'],
            (float)($data['base_rate'] ?? 0),
            (int)($data['occupancy'] ?? 1),
            (int)($data['bed_count'] ?? 1),
            !empty($data['ac']) ? 1 : 0,
            $data['status'] ?? 'available',
            $data['description'] ?? '',
        ]);
        $id = (int)$this->db->lastInsertId();
        return ['id' => $id, ...$data];
    }

    public function updateRoom(int $roomId, array $data): void
    {
        $fields = [];
        $params = [];
        foreach (['room_number','room_type','base_rate','occupancy','bed_count','ac','status','description'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                if ($f === 'ac') {
                    $params[] = !empty($data[$f]) ? 1 : 0;
                } elseif (in_array($f, ['base_rate','occupancy','bed_count'])) {
                    $params[] = (float)$data[$f];
                } else {
                    $params[] = $data[$f];
                }
            }
        }
        if (empty($fields)) return;
        $params[] = $roomId;
        $this->db->prepare("UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
    }

    public function deleteRoom(int $roomId): void
    {
        $this->db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);
    }

    // ---------- ALERTS ----------

    public function getAlerts(int $propertyId): array
    {
        $alerts = [];
        $totalRooms = 0;
        $stmtR = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE property_id = ?");
        $stmtR->execute([$propertyId]);
        $totalRooms = (int)$stmtR->fetchColumn();

        // Low-occupancy dates (next 14 days, occupancy < 50%)
        $stmt = $this->db->prepare("
            SELECT d.date, COUNT(DISTINCT b.room_id) as occupied
            FROM (
                SELECT CURDATE() + INTERVAL a.a DAY AS date
                FROM (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13) a
            ) d
            LEFT JOIN bookings b ON b.property_id = ? AND b.status IN ('confirmed','checked_in')
                AND d.date >= b.check_in_date AND d.date < b.check_out_date
            GROUP BY d.date
            HAVING (COUNT(DISTINCT b.room_id) / ?) < 0.50
            ORDER BY d.date
            LIMIT 5
        ");
        $stmt->execute([$propertyId, $totalRooms]);
        $lowOccDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lowOccDates as $row) {
            $pct = $totalRooms > 0 ? round(($row['occupied'] / $totalRooms) * 100, 0) : 0;
            $alerts[] = [
                'type'     => 'low_occupancy',
                'title'    => 'Low occupancy on ' . date('D M j', strtotime($row['date'])),
                'detail'   => "Only {$pct}% booked ({$row['occupied']} of {$totalRooms} rooms). Consider a promotion.",
                'priority' => 'Medium',
                'time'     => $row['date'],
            ];
        }

        // Overbooking conflicts — overlapping confirmed bookings on same room
        $conflicts = $this->bookingService->getOverbookingConflicts($propertyId);
        foreach ($conflicts as $c) {
            $alerts[] = [
                'type'     => 'overbooking',
                'title'    => 'Overbooking: ' . $c['room_number'] . ' (' . $c['room_type'] . ')',
                'detail'   => "Booking #{$c['booking_id']} ({$c['check_in_date']}–{$c['check_out_date']}) conflicts with #{$c['conflict_id']}.",
                'priority' => 'High',
                'time'     => date('Y-m-d'),
            ];
        }

        // Recent maintenance issues
        $stmt = $this->db->prepare("
            SELECT mo.id, mo.issue_description, mo.status, mo.priority, mo.created_at
            FROM maintenance_orders mo
            JOIN rooms r ON mo.room_id = r.id
            WHERE r.property_id = ? AND mo.status IN ('open','in_progress')
            ORDER BY mo.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$propertyId]);
        $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($maintenance as $m) {
            $alerts[] = [
                'type'     => 'maintenance',
                'title'    => 'Maintenance: ' . htmlspecialchars($m['issue_description']),
                'detail'   => "Status: {$m['status']}, Priority: {$m['priority']}",
                'priority' => $m['priority'] === 'urgent' ? 'High' : 'Medium',
                'time'     => date('M j', strtotime($m['created_at'])),
            ];
        }

        // Sort by priority High > Medium, then by time descending
        $priorityOrder = ['High' => 0, 'Medium' => 1];
        usort($alerts, function ($a, $b) use ($priorityOrder) {
            $pa = $priorityOrder[$a['priority']] ?? 2;
            $pb = $priorityOrder[$b['priority']] ?? 2;
            if ($pa !== $pb) return $pa - $pb;
            return strcmp($b['time'] ?? '', $a['time'] ?? '');
        });

        return $alerts;
    }

    // ---------- STAFF SCHEDULING ----------

    public function getSchedules(int $propertyId, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT s.*, u.first_name, u.last_name, u.role as user_role
            FROM schedules s
            JOIN users u ON s.user_id = u.id
            WHERE s.property_id = ? AND s.date = ?
            ORDER BY s.start_time ASC
        ");
        $stmt->execute([$propertyId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getScheduleSummary(int $propertyId): array
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM schedules WHERE property_id = ? AND date = ? AND status = 'confirmed'");
        $stmt->execute([$propertyId, $today]);
        $onDuty = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM schedules WHERE property_id = ? AND date >= ? AND status = 'pending'");
        $stmt->execute([$propertyId, $today]);
        $openShifts = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE property_id = ? AND status IN ('confirmed','checked_in') AND check_in_date = ?
        ");
        $stmt->execute([$propertyId, $today]);
        $arrivals = (int)$stmt->fetchColumn();

        return [
            'on_duty'     => $onDuty,
            'open_shifts' => $openShifts,
            'arrivals'    => $arrivals,
        ];
    }

    public function upsertSchedule(int $propertyId, int $userId, string $date, string $startTime, string $endTime, string $status = 'pending', ?string $notes = null, ?int $scheduleId = null): int
    {
        if ($scheduleId) {
            $stmt = $this->db->prepare("
                UPDATE schedules SET user_id=?, date=?, start_time=?, end_time=?, status=?, notes=? WHERE id=? AND property_id=?
            ");
            $stmt->execute([$userId, $date, $startTime, $endTime, $status, $notes, $scheduleId, $propertyId]);
            return $scheduleId;
        }

        $stmt = $this->db->prepare("
            INSERT INTO schedules (property_id, user_id, date, start_time, end_time, role, status, notes)
            VALUES (?, ?, ?, ?, ?, (SELECT role FROM users WHERE id = ?), ?, ?)
        ");
        $stmt->execute([$propertyId, $userId, $date, $startTime, $endTime, $userId, $status, $notes]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteSchedule(int $scheduleId, int $propertyId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE id = ? AND property_id = ?");
        $stmt->execute([$scheduleId, $propertyId]);
        return $stmt->rowCount() > 0;
    }

    // ---------- EXPENSES ----------

    public function getExpenses(int $propertyId, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, title, vendor, amount, category, date, status, notes, created_at
                FROM expenses
                WHERE property_id = ?
                ORDER BY date DESC, created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$propertyId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function createExpense(int $propertyId, array $data): int
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO expenses (property_id, title, vendor, amount, category, date, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $propertyId,
                $data['title'],
                $data['vendor'] ?? null,
                (float)($data['amount'] ?? 0),
                $data['category'] ?? null,
                $data['date'] ?? date('Y-m-d'),
                $data['status'] ?? 'pending',
                $data['notes'] ?? null,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Expenses table not available. Run the database migration first.');
        }
    }

    public function updateExpense(int $id, int $propertyId, array $data): bool
    {
        try {
            $allowed = ['title', 'vendor', 'amount', 'category', 'date', 'status', 'notes'];
            $sets = [];
            $params = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[] = "`$field` = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($sets)) return false;
            $params[] = $id;
            $params[] = $propertyId;
            $sql = "UPDATE expenses SET " . implode(', ', $sets) . " WHERE id = ? AND property_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Expenses table not available. Run the database migration first.');
        }
    }

    public function deleteExpense(int $id, int $propertyId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM expenses WHERE id = ? AND property_id = ?");
            $stmt->execute([$id, $propertyId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getExpenseSummary(int $propertyId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE property_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
            $stmt->execute([$propertyId]);
            $monthly = (float)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM expenses WHERE property_id = ? AND status = 'pending'");
            $stmt->execute([$propertyId]);
            $pending = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE property_id = ? AND status = 'pending'");
            $stmt->execute([$propertyId]);
            $pendingAmount = (float)$stmt->fetchColumn();

            return [
                'monthly_spend' => $monthly,
                'pending_count' => $pending,
                'pending_amount' => $pendingAmount,
            ];
        } catch (\PDOException $e) {
            return ['monthly_spend' => 0, 'pending_count' => 0, 'pending_amount' => 0];
        }
    }

    // ---------- REPORTS ----------

    public function getOccupancyReport(int $propertyId, int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT d.date,
                   COUNT(DISTINCT b.room_id) as occupied_cnt,
                   r.total_rooms
            FROM (
                SELECT CURDATE() - INTERVAL (a.a) DAY AS date
                FROM (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29) a
            ) d
            CROSS JOIN (
                SELECT COUNT(*) as total_rooms FROM rooms WHERE property_id = ?
            ) r
            LEFT JOIN bookings b ON b.property_id = ? AND b.status IN ('confirmed','checked_in')
                AND d.date >= b.check_in_date AND d.date < b.check_out_date
            GROUP BY d.date, r.total_rooms
            ORDER BY d.date ASC
            LIMIT ?
        ");
        $stmt->execute([$propertyId, $propertyId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenueReport(int $propertyId): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(b.check_in_date, '%Y-%m') as month,
                   COUNT(DISTINCT b.id) as bookings,
                   COALESCE(SUM(p.amount),0) as revenue
            FROM bookings b
            LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed'
            WHERE b.property_id = ? AND b.status NOT IN ('cancelled')
            GROUP BY DATE_FORMAT(b.check_in_date, '%Y-%m')
            ORDER BY month ASC
            LIMIT 12
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------- UTILITY ----------

    public function getStaffUsers(int $propertyId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, first_name, last_name, role
            FROM users
            WHERE (property_id = ? OR property_id IS NULL) AND role IN ('front_office','housekeeping','maintenance','fnb','manager','security')
            ORDER BY role, first_name
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOccupancyForDate(int $propertyId, string $date): float
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        $total = (int)$stmt->fetchColumn();
        if ($total === 0) return 0;

        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT b.room_id)
            FROM bookings b
            WHERE b.property_id = ? AND b.status IN ('confirmed','checked_in')
              AND ? >= b.check_in_date AND ? < b.check_out_date
        ");
        $stmt->execute([$propertyId, $date, $date]);
        $occupied = (int)$stmt->fetchColumn();
        return round(($occupied / $total) * 100, 1);
    }

    // ---------- PROPERTY SETTINGS ----------

    public function getProperty(int $propertyId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$propertyId]);
        return $stmt->fetch() ?: null;
    }

    public function updateProperty(int $propertyId, array $data): bool
    {
        $allowed = ['name', 'code', 'property_type', 'address', 'city', 'state', 'country', 'postal_code', 'phone', 'email', 'description', 'time_zone'];
        $sets = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($sets)) {
            return false;
        }
        $params[] = $propertyId;
        $sql = "UPDATE properties SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
