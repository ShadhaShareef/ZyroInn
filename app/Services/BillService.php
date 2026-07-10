<?php
namespace App\Services;

use PDO;

class BillService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function generateBillData(int $bookingId): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, r.room_number, r.room_type, r.base_rate,
                   g.first_name, g.last_name, g.email, g.phone,
                   p.name AS property_name, p.address, p.city, p.state, p.country
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            LEFT JOIN guests g ON b.guest_id = g.id
            JOIN properties p ON b.property_id = p.id
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new \RuntimeException("Booking #$bookingId not found.");
        }

        $checkIn = new \DateTime($booking['check_in_date']);
        $checkOut = new \DateTime($booking['check_out_date']);
        $nights = max(1, $checkIn->diff($checkOut)->days);

        $roomCharges = $nights * (float)$booking['base_rate'];

        $addonStmt = $this->db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
        $addonStmt->execute([$bookingId]);
        $addons = $addonStmt->fetchAll();

        $addonsCharges = 0.0;
        foreach ($addons as $addon) {
            $addonsCharges += (float)$addon['price'] * (int)$addon['quantity'];
        }

        $totalCharges = $roomCharges + $addonsCharges;

        $paymentStmt = $this->db->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'");
        $paymentStmt->execute([$bookingId]);
        $payments = $paymentStmt->fetchAll();

        $paidAmount = 0.0;
        foreach ($payments as $payment) {
            $paidAmount += (float)$payment['amount'];
        }

        return [
            'booking' => $booking,
            'nights' => $nights,
            'roomCharges' => $roomCharges,
            'addons' => $addons,
            'addonsCharges' => $addonsCharges,
            'totalCharges' => $totalCharges,
            'payments' => $payments,
            'paidAmount' => $paidAmount,
            'outstandingBalance' => $totalCharges - $paidAmount,
            'generatedAt' => date('Y-m-d H:i:s'),
        ];
    }

    public function renderHtml(array $data): string
    {
        $b = $data['booking'];
        $guestName = htmlspecialchars(trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')) ?: 'Guest');
        $propertyName = htmlspecialchars($b['property_name'] ?? 'ZyroInn');
        $propertyAddr = htmlspecialchars(trim(($b['address'] ?? '') . ', ' . ($b['city'] ?? '') . ', ' . ($b['state'] ?? '')));
        $roomInfo = htmlspecialchars('Room ' . ($b['room_number'] ?? '') . ' (' . ($b['room_type'] ?? '') . ')');

        $rows = '';

        $rows .= '<tr>
            <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;">Room Accommodation</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;">' . $data['nights'] . ' night(s) at $' . number_format($b['base_rate'], 2) . '/night</td>
            <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;text-align:right;font-weight:600;">$' . number_format($data['roomCharges'], 2) . '</td>
        </tr>';

        foreach ($data['addons'] as $addon) {
            $total = (float)$addon['price'] * (int)$addon['quantity'];
            $rows .= '<tr>
                <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;">' . htmlspecialchars($addon['name']) . '</td>
                <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;">' . htmlspecialchars($addon['description']) . ' (Qty: ' . (int)$addon['quantity'] . ')</td>
                <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;text-align:right;font-weight:600;">$' . number_format($total, 2) . '</td>
            </tr>';
        }

        $paymentRows = '';
        foreach ($data['payments'] as $payment) {
            $paymentRows .= '<tr>
                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#059669;">' . date('M d, Y H:i', strtotime($payment['paid_at'] ?? $payment['created_at'])) . '</td>
                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#059669;text-transform:uppercase;">' . htmlspecialchars($payment['method']) . '</td>
                <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#059669;text-align:right;font-weight:600;">-$' . number_format($payment['amount'], 2) . '</td>
            </tr>';
        }

        $balanceClass = $data['outstandingBalance'] > 0 ? 'color:#d97706;' : 'color:#059669;';

        return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>E-Bill — Booking #BKG-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT) . '</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">

<!-- Header -->
<tr><td style="background:#1e293b;padding:28px 32px;text-align:center;">
    <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">' . htmlspecialchars($propertyName) . '</h1>
    <p style="margin:4px 0 0;color:#94a3b8;font-size:12px;">' . $propertyAddr . '</p>
</td></tr>

<!-- Title -->
<tr><td style="padding:24px 32px 8px;">
    <h2 style="margin:0;font-size:16px;color:#374151;">E-Bill / Tax Invoice</h2>
    <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">Booking #BKG-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT) . ' &middot; Generated ' . $data['generatedAt'] . '</p>
</td></tr>

<!-- Guest Info -->
<tr><td style="padding:8px 32px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:10px;padding:14px 18px;">
        <tr>
            <td style="font-size:12px;color:#6b7280;width:50%;">Guest</td>
            <td style="font-size:12px;color:#6b7280;width:50%;">Check-In</td>
        </tr>
        <tr>
            <td style="font-size:14px;font-weight:600;color:#111827;">' . $guestName . '</td>
            <td style="font-size:14px;color:#111827;">' . date('M d, Y', strtotime($b['check_in_date'])) . '</td>
        </tr>
        <tr><td colspan="2" style="height:8px;"></td></tr>
        <tr>
            <td style="font-size:12px;color:#6b7280;">Room</td>
            <td style="font-size:12px;color:#6b7280;">Check-Out</td>
        </tr>
        <tr>
            <td style="font-size:14px;font-weight:600;color:#111827;">' . $roomInfo . '</td>
            <td style="font-size:14px;color:#111827;">' . date('M d, Y', strtotime($b['check_out_date'])) . '</td>
        </tr>
    </table>
</td></tr>

<!-- Charges -->
<tr><td style="padding:16px 32px 0;">
    <h3 style="margin:0 0 8px;font-size:13px;color:#374151;text-transform:uppercase;letter-spacing:0.5px;">Charges</h3>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <thead><tr style="background:#f9fafb;">
            <th style="padding:10px 14px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Item</th>
            <th style="padding:10px 14px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Details</th>
            <th style="padding:10px 14px;text-align:right;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Amount</th>
        </tr></thead>
        <tbody>' . $rows . '</tbody>
    </table>
</td></tr>

<!-- Total -->
<tr><td style="padding:4px 32px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="padding:12px 14px;border-top:2px solid #1e293b;font-size:15px;font-weight:700;color:#111827;">Total Charged</td>
            <td style="padding:12px 14px;border-top:2px solid #1e293b;font-size:15px;font-weight:700;color:#111827;text-align:right;">$' . number_format($data['totalCharges'], 2) . '</td>
        </tr>
    </table>
</td></tr>

<!-- Payments -->
<tr><td style="padding:8px 32px 0;">
    <h3 style="margin:0 0 8px;font-size:13px;color:#374151;text-transform:uppercase;letter-spacing:0.5px;">Payments Received</h3>
    ' . ($paymentRows ? '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">' . $paymentRows . '</table>' : '<p style="margin:0;font-size:12px;color:#9ca3af;font-style:italic;">No payments logged.</p>') . '
</td></tr>

<!-- Balance -->
<tr><td style="padding:12px 32px 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="padding:12px 14px;border-top:2px solid #d1d5db;font-size:14px;font-weight:700;' . $balanceClass . '">Outstanding Balance</td>
            <td style="padding:12px 14px;border-top:2px solid #d1d5db;font-size:14px;font-weight:700;text-align:right;' . $balanceClass . '">$' . number_format($data['outstandingBalance'], 2) . '</td>
        </tr>
    </table>
</td></tr>

<!-- Footer -->
<tr><td style="background:#f9fafb;padding:16px 32px;text-align:center;border-top:1px solid #e5e7eb;">
    <p style="margin:0;font-size:11px;color:#9ca3af;">Thank you for choosing ' . htmlspecialchars($propertyName) . '!<br>This is a computer-generated invoice.</p>
</td></tr>

</table>
</td></tr></table>
</body>
</html>';
    }

    public function saveHtml(int $bookingId, string $html): string
    {
        $dir = __DIR__ . '/../../storage/bills';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir . '/bill_' . $bookingId . '_' . date('Ymd_His') . '.html';
        file_put_contents($path, $html);
        return $path;
    }

    public function generateAndSave(int $bookingId): array
    {
        $data = $this->generateBillData($bookingId);
        $html = $this->renderHtml($data);
        $path = $this->saveHtml($bookingId, $html);
        return ['data' => $data, 'html' => $html, 'path' => $path];
    }
}
