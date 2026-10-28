<?php

/**
 * Booking Request Handler
 * Processes room booking form submissions
 *
 * @return array JSON response data
 */

use Kirby\Cms\App;
use Kirby\Toolkit\Str;

/**
 * Handle booking request submission
 */
function handleBookingRequest(): array
{
    $kirby = App::instance();

    // Verify CSRF token
    // if (csrf(get('csrf')) !== true) {
    //     return [
    //         'success' => false,
    //         'message' => 'Ungültige Anfrage. Bitte laden Sie die Seite neu und versuchen Sie es erneut.'
    //     ];
    // }

    // Get rooms parent page
    $roomsPage = $kirby->site()->find('rooms');
    if (!$roomsPage) {
        return [
            'success' => false,
            'message' => 'Systemfehler: Räume-Seite nicht gefunden.',
        ];
    }

    // Get or create booking-requests container
    $requestsPage = $roomsPage->find('booking-requests');
    if (!$requestsPage) {
        // Create the requests container if it doesn't exist
        try {
            $kirby->impersonate('kirby');
            $requestsPage = $roomsPage->createChild([
                'slug' => 'booking-requests',
                'template' => 'booking-requests',
                'content' => [
                    'title' => 'Buchungsanfragen',
                ],
            ]);
            $kirby->impersonate();
        } catch (Exception $e) {
            $kirby->impersonate();

            return [
                'success' => false,
                'message' => 'Systemfehler: Anfragen-Container konnte nicht erstellt werden.',
            ];
        }
    }

    // Validate required fields
    $requiredFields = [
        'rooms' => 'Bitte wählen Sie mindestens einen Raum aus.',
        'request_date' => 'Bitte wählen Sie ein Datum aus.',
        'request_time_start' => 'Bitte wählen Sie eine Startzeit aus.',
        'request_time_end' => 'Bitte wählen Sie eine Endzeit aus.',
        'expected_attendees' => 'Bitte geben Sie die erwartete Teilnehmerzahl an.',
        'purpose' => 'Bitte beschreiben Sie den Verwendungszweck.',
        'requester_name' => 'Bitte geben Sie Ihren Namen an.',
        'requester_email' => 'Bitte geben Sie Ihre E-Mail-Adresse an.',
        'privacy_accepted' => 'Bitte akzeptieren Sie die Datenschutzerklärung.',
    ];

    foreach ($requiredFields as $field => $errorMessage) {
        $value = get($field);
        if (empty($value) || (is_array($value) && count($value) === 0)) {
            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
    }

    // Validate email format
    $email = get('requester_email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
        ];
    }

    // Validate time (end must be after start)
    $timeStart = get('request_time_start');
    $timeEnd = get('request_time_end');
    if ($timeEnd <= $timeStart) {
        return [
            'success' => false,
            'message' => 'Die Endzeit muss nach der Startzeit liegen.',
        ];
    }

    // Validate date (must be in the future with lead time)
    $requestDate = get('request_date');
    $leadTime = $roomsPage->lead_time_days()->or(1)->toInt();
    $minDate = date('Y-m-d', strtotime("+{$leadTime} days"));

    if ($requestDate < $minDate) {
        return [
            'success' => false,
            'message' => "Das Datum muss mindestens {$leadTime} Tag(e) in der Zukunft liegen.",
        ];
    }

    // Get selected room IDs and validate they exist
    $selectedRoomIds = get('rooms');
    if (!is_array($selectedRoomIds)) {
        $selectedRoomIds = [$selectedRoomIds];
    }
    $validRoomIds = [];
    foreach ($selectedRoomIds as $roomId) {
        $room = $kirby->site()->find($roomId);
        if ($room && $room->intendedTemplate()->name() === 'room') {
            array_push($validRoomIds, $roomId);
        }
    }

    if (empty($validRoomIds)) {
        return [
            'success' => false,
            'message' => 'Die ausgewählten Räume sind ungültig.',
        ];
    }

    // Generate unique slug for the request
    $slug = date('Ymd', strtotime($requestDate)) . '-' . Str::slug(get('requester_name')) . '-' . Str::random(4);

    // Prepare content data
    $content = [
        'title' => get('requester_name') . ' - ' . date('d.m.Y', strtotime($requestDate)),
        'requester_name' => get('requester_name'),
        'requester_email' => $email,
        'requester_phone' => get('requester_phone', ''),
        'requester_organization' => get('requester_organization', ''),
        'requested_rooms' => implode("\n", $validRoomIds),
        'request_date' => $requestDate,
        'request_time_start' => $timeStart,
        'request_time_end' => $timeEnd,
        'is_recurring' => get('is_recurring') ? true : false,
        'recurrence_pattern' => get('recurrence_pattern', ''),
        'recurrence_end_date' => get('recurrence_end_date', ''),
        'expected_attendees' => (int) get('expected_attendees'),
        'purpose' => get('purpose'),
        'special_requirements' => get('special_requirements', ''),
        'created_at' => date('Y-m-d H:i:s'),
        'ip_address' => /*$kirby->request()->ip() ??*/ '',
        'user_agent' => substr($kirby->request()->header('User-Agent') ?? '', 0, 255),
    ];

    // Create the booking request page (requires elevated permissions)
    try {
        $kirby->impersonate('kirby');
        $bookingRequest = $requestsPage->createChild([
            'slug' => $slug,
            'template' => 'booking-request',
            'draft' => true,
            'content' => $content,
        ]);
        $kirby->impersonate();

        // Send notification emails
        sendBookingNotifications($kirby, $bookingRequest, $content);

        return [
            'success' => true,
            'message' => 'Ihre Buchungsanfrage wurde erfolgreich übermittelt. Sie erhalten in Kürze eine Bestätigung per E-Mail.',
            'reference' => $slug,
        ];
    } catch (Exception $e) {
        $kirby->impersonate();

        return [
            'success' => false,
            'message' => 'Fehler beim Speichern der Anfrage: ' . $e->getMessage(),
        ];
    }
}

/**
 * Send notification emails for new booking request
 */
function sendBookingNotifications(App $kirby, $bookingRequest, array $data): void
{
    $roomsPage = $kirby->site()->find('rooms');

    // Get room names for email
    $roomNames = [];
    $roomIds = explode("\n", $data['requested_rooms']);
    foreach ($roomIds as $roomId) {
        $roomId = trim(str_replace('- ', '', $roomId));
        if ($room = $kirby->site()->find($roomId)) {
            $roomNames[] = $room->title()->value();
        }
    }

    $emailData = [
        'requester_name' => $data['requester_name'],
        'requester_email' => $data['requester_email'],
        'requester_phone' => $data['requester_phone'],
        'requester_organization' => $data['requester_organization'],
        'room_names' => implode(', ', $roomNames),
        'request_date' => date('d.m.Y', strtotime($data['request_date'])),
        'request_time_start' => $data['request_time_start'],
        'request_time_end' => $data['request_time_end'],
        'is_recurring' => $data['is_recurring'],
        'recurrence_pattern' => $data['recurrence_pattern'],
        'recurrence_end_date' => $data['recurrence_end_date'] ? date('d.m.Y', strtotime($data['recurrence_end_date'])) : '',
        'expected_attendees' => $data['expected_attendees'],
        'purpose' => $data['purpose'],
        'special_requirements' => $data['special_requirements'],
        'panel_url' => $kirby->url() . '/panel/pages/' . str_replace('/', '+', $bookingRequest->id()),
    ];

    // Send confirmation to requester
    try {
        $kirby->email([
            'from' => $roomsPage->notification_email()->or('noreply@' . $kirby->url('host'))->value(),
            'replyTo' => $roomsPage->notification_email()->or('info@' . $kirby->url('host'))->value(),
            'to' => $data['requester_email'],
            'subject' => 'Ihre Buchungsanfrage bei ' . $kirby->site()->title(),
            'body' => snippet('content-types/rooms/emails/booking-received', $emailData, true),
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Booking confirmation email failed: ' . $e->getMessage());
    }

    // Send notification to admin
    $adminEmail = $roomsPage->notification_email()->value();
    if ($adminEmail) {
        try {
            $kirby->email([
                'from' => $adminEmail,
                'to' => $adminEmail,
                'subject' => 'Neue Buchungsanfrage: ' . implode(', ', $roomNames) . ' am ' . $emailData['request_date'],
                'body' => snippet('content-types/rooms/emails/admin-notification', $emailData, true),
            ]);
        } catch (Exception $e) {
            error_log('Admin notification email failed: ' . $e->getMessage());
        }
    }
}
