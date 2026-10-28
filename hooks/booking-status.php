<?php

/**
 * Hook `page.changeStatus:after`: status changes of booking requests (mail to the
 * requester, Google Calendar events). The publish date of newsletters and notes
 * is set by the gs-mmh-web-plugin hook of the same name.
 */

use Kirby\Cms\App;
use Kirby\Cms\Page;

return function (Page $newPage, Page $oldPage) {
    // Handle booking request status changes
    if ($newPage->intendedTemplate()->name() === 'booking-request') {
        $kirby = App::instance();
        $oldStatus = $oldPage->status();
        $newStatus = $newPage->status();

        // Only process if status actually changed
        if ($oldStatus !== $newStatus) {
            // Update processed timestamp and user
            $newPage->update([
                'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => '- ' . ($kirby->user() ? $kirby->user()->id() : 'system'),
            ]);

            // Get room names for email
            $roomNames = [];
            $roomIds = $newPage->requested_rooms()->toPages();
            foreach ($roomIds as $room) {
                $roomNames[] = $room->title()->value();
            }

            $recurrenceLabels = [
                'weekly' => 'Wöchentlich',
                'biweekly' => 'Alle 2 Wochen',
                'monthly' => 'Monatlich',
            ];

            $emailData = [
                'requester_name' => $newPage->requester_name()->value(),
                'requester_email' => $newPage->requester_email()->value(),
                'room_names' => implode(', ', $roomNames),
                'request_date' => $newPage->request_date()->toDate('d.m.Y'),
                'request_time_start' => $newPage->request_time_start()->value(),
                'request_time_end' => $newPage->request_time_end()->value(),
                'is_recurring' => $newPage->is_recurring()->toBool(),
                'recurrence_pattern' => $newPage->recurrence_pattern()->value(),
                'recurrence_end_date' => $newPage->recurrence_end_date()->isNotEmpty() ? $newPage->recurrence_end_date()->toDate('d.m.Y') : '',
                'expected_attendees' => $newPage->expected_attendees()->toInt(),
                'admin_notes' => $newPage->admin_notes()->value(),
                'denial_reason' => $newPage->denial_reason()->value(),
            ];

            $roomsPage = $kirby->site()->find('rooms');
            $fromEmail = $roomsPage ? $roomsPage->notification_email()->or('noreply@' . $kirby->url('host'))->value() : 'noreply@' . $kirby->url('host');

            // Send approval email and create calendar events
            if ($newStatus === 'listed' && $oldStatus !== 'listed') {
                // Send confirmation email
                // try {
                //     $kirby->email([
                //         'from' => $fromEmail,
                //         'replyTo' => $fromEmail,
                //         'to' => $emailData['requester_email'],
                //         'subject' => 'Buchungsanfrage genehmigt: ' . $emailData['room_names'] . ' am ' . $emailData['request_date'],
                //         'body' => snippet('content-types/rooms/emails/booking-approved', $emailData, true)
                //     ]);
                // } catch (Exception $e) {
                //     error_log('Booking approval email failed: ' . $e->getMessage());
                // }
                // Create Google Calendar events if configured
                $calendarCredentials = $kirby->option('google.calendar.credentials');
                if ($calendarCredentials && file_exists($calendarCredentials)) {
                    try {
                        require_once dirname(__DIR__) . '/snippets/googleCalendarIntegration.php';
                        $calendarResults = createBookingCalendarEvents($newPage);

                        // Check if all events were created successfully
                        $allSuccess = true;
                        foreach ($calendarResults as $result) {
                            if (!($result['success'] ?? false)) {
                                $allSuccess = false;
                                error_log('Calendar event creation failed for ' . ($result['room'] ?? 'unknown') . ': ' . ($result['message'] ?? 'Unknown error'));
                            }
                        }

                        // Update the calendar_event_added field
                        if ($allSuccess && !empty($calendarResults)) {
                            // $newPage->update([
                            //     'calendar_event_added' => true
                            // ]);
                        }
                    } catch (Exception $e) {
                        error_log('Google Calendar integration failed: ' . $e->getMessage());
                    }
                } else {
                    error_log('Google Calendar credentials not configured or file not found.');
                }
            }

            // Send denial email
            if ($newStatus === 'unlisted' && $oldStatus !== 'unlisted') {
                try {
                    $kirby->email([
                        'from' => $fromEmail,
                        'replyTo' => $fromEmail,
                        'to' => $emailData['requester_email'],
                        'subject' => 'Buchungsanfrage abgelehnt: ' . $emailData['room_names'] . ' am ' . $emailData['request_date'],
                        'body' => snippet('content-types/rooms/emails/booking-denied', $emailData, true),
                    ]);
                } catch (Exception $e) {
                    error_log('Booking denial email failed: ' . $e->getMessage());
                }
            }
        }
    }
};
