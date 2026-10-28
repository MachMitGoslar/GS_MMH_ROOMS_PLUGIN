<?php

/**
 * Google Calendar Integration for Room Bookings
 *
 * Creates calendar events when bookings are approved.
 * Requires a Google Service Account with Calendar API access.
 *
 * Setup:
 * 1. Create a Google Cloud Project
 * 2. Enable Google Calendar API
 * 3. Create a Service Account and download JSON key
 * 4. Share each room's calendar with the service account email
 * 5. Store the JSON key path in config or .env
 */

use Kirby\Cms\App;
use Kirby\Http\Remote;

/**
 * Create a Google Calendar event for an approved booking
 */
function createCalendarEvent(
    string $calendarId,
    string $summary,
    string $description,
    string $startDateTime,
    string $endDateTime,
    ?string $location = null,
): array {
    $kirby = App::instance();

    // Get service account credentials path from config
    $credentialsPath = $kirby->option('google.calendar.credentials');

    if (!$credentialsPath || !file_exists($credentialsPath)) {
        return [
            'success' => false,
            'message' => 'Google Calendar credentials not configured',
        ];
    }

    try {
        // Get access token using service account
        $accessToken = getGoogleAccessToken($credentialsPath);

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to obtain Google access token',
            ];
        }

        // Create the event
        $event = [
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $startDateTime,
                'timeZone' => 'Europe/Berlin',
            ],
            'end' => [
                'dateTime' => $endDateTime,
                'timeZone' => 'Europe/Berlin',
            ],
        ];

        if ($location) {
            $event['location'] = $location;
        }

        $response = Remote::request(
            'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events',
            [
                'method' => 'POST',
                'headers' => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
                'data' => json_encode($event),
            ],
        );

        if ($response->code() === 200 || $response->code() === 201) {
            $result = json_decode($response->content(), true);

            return [
                'success' => true,
                'eventId' => $result['id'] ?? null,
                'htmlLink' => $result['htmlLink'] ?? null,
            ];
        } else {
            $error = json_decode($response->content(), true);

            return [
                'success' => false,
                'message' => $error['error']['message'] ?? 'Unknown error',
                'code' => $response->code(),
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

/**
 * Get Google OAuth2 access token using service account
 */
function getGoogleAccessToken(string $credentialsPath): ?string
{
    $credentials = json_decode(file_get_contents($credentialsPath), true);

    if (!$credentials) {
        return null;
    }

    // Create JWT
    $header = base64_encode(json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT',
    ]));

    $now = time();
    $claim = base64_encode(json_encode([
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar.events',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ]));

    // Sign the JWT
    $signature = '';
    $privateKey = openssl_pkey_get_private($credentials['private_key']);
    openssl_sign(
        $header . '.' . $claim,
        $signature,
        $privateKey,
        'SHA256',
    );
    $signature = base64_encode($signature);

    $jwt = $header . '.' . $claim . '.' . $signature;

    // Exchange JWT for access token
    $response = Remote::request('https://oauth2.googleapis.com/token', [
        'method' => 'POST',
        'data' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ],
    ]);

    if ($response->code() === 200) {
        $result = json_decode($response->content(), true);

        return $result['access_token'] ?? null;
    }

    return null;
}

/**
 * Create calendar events for all rooms in an approved booking
 */
function createBookingCalendarEvents($bookingRequest): array
{
    $kirby = App::instance();
    $results = [];

    // Get booking details
    $requesterName = $bookingRequest->requester_name()->value();
    $requesterOrg = $bookingRequest->requester_organization()->value();
    $purpose = $bookingRequest->purpose()->value();
    $attendees = $bookingRequest->expected_attendees()->value();
    $specialReqs = $bookingRequest->special_requirements()->value();

    $requestDate = $bookingRequest->request_date()->value();
    $timeStart = $bookingRequest->request_time_start()->value();
    $timeEnd = $bookingRequest->request_time_end()->value();

    // Build datetime strings
    $startDateTime = $requestDate . 'T' . $timeStart . ':00';
    $endDateTime = $requestDate . 'T' . $timeEnd . ':00';

    // Build event description
    $description = "Buchung: {$requesterName}";
    if ($requesterOrg) {
        $description .= " ({$requesterOrg})";
    }
    $description .= "\n\nZweck: {$purpose}";
    $description .= "\nTeilnehmer: {$attendees} Personen";
    if ($specialReqs) {
        $description .= "\n\nBesondere Anforderungen:\n{$specialReqs}";
    }

    // Get rooms and create events
    $rooms = $bookingRequest->requested_rooms()->toPages();

    foreach ($rooms as $room) {
        $calendarId = $room->google_calendar_id()->value();

        if (!$calendarId) {
            $results[] = [
                'room' => $room->title()->value(),
                'success' => false,
                'message' => 'No calendar ID configured for this room',
            ];

            continue;
        }

        $summary = $requesterOrg ?: $requesterName;
        $location = $room->title()->value();

        $result = createCalendarEvent(
            $calendarId,
            $summary,
            $description,
            $startDateTime,
            $endDateTime,
            $location,
        );

        $result['room'] = $room->title()->value();
        array_push($results, $result);
    }

    // Handle recurring bookings
    if ($bookingRequest->is_recurring()->toBool()) {
        $pattern = $bookingRequest->recurrence_pattern()->value();
        $endDate = $bookingRequest->recurrence_end_date()->value();

        if ($endDate) {
            $results = array_merge(
                $results,
                createRecurringEvents($bookingRequest, $rooms, $pattern, $endDate),
            );
        }
    }

    return $results;
}

/**
 * Create recurring calendar events
 */
function createRecurringEvents($bookingRequest, $rooms, string $pattern, string $endDate): array
{
    $results = [];

    $requestDate = new DateTime($bookingRequest->request_date()->value());
    $endDateObj = new DateTime($endDate);
    $timeStart = $bookingRequest->request_time_start()->value();
    $timeEnd = $bookingRequest->request_time_end()->value();

    // Determine interval
    $interval = match ($pattern) {
        'weekly' => new DateInterval('P1W'),
        'biweekly' => new DateInterval('P2W'),
        'monthly' => new DateInterval('P1M'),
        default => new DateInterval('P1W')
    };

    // Build event details (same as single event)
    $requesterName = $bookingRequest->requester_name()->value();
    $requesterOrg = $bookingRequest->requester_organization()->value();
    $purpose = $bookingRequest->purpose()->value();
    $attendees = $bookingRequest->expected_attendees()->value();
    $specialReqs = $bookingRequest->special_requirements()->value();

    $description = "Buchung: {$requesterName}";
    if ($requesterOrg) {
        $description .= " ({$requesterOrg})";
    }
    $description .= "\n\nZweck: {$purpose}";
    $description .= "\nTeilnehmer: {$attendees} Personen";
    if ($specialReqs) {
        $description .= "\n\nBesondere Anforderungen:\n{$specialReqs}";
    }

    // Skip first date (already created)
    $currentDate = clone $requestDate;
    $currentDate->add($interval);

    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $startDateTime = $dateStr . 'T' . $timeStart . ':00';
        $endDateTime = $dateStr . 'T' . $timeEnd . ':00';

        foreach ($rooms as $room) {
            $calendarId = $room->google_calendar_id()->value();

            if (!$calendarId) {
                continue;
            }

            $summary = $requesterOrg ?: $requesterName;
            $location = $room->title()->value();

            $result = createCalendarEvent(
                $calendarId,
                $summary,
                $description,
                $startDateTime,
                $endDateTime,
                $location,
            );

            $result['room'] = $room->title()->value();
            $result['date'] = $dateStr;
            $results[] = $result;
        }

        $currentDate->add($interval);
    }

    return $results;
}
