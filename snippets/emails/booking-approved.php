<?php
/**
 * Booking Approved Email
 * Sent to requester when their booking is approved
 *
 * @var string $requester_name
 * @var string $room_names
 * @var string $request_date
 * @var string $request_time_start
 * @var string $request_time_end
 * @var bool $is_recurring
 * @var string $recurrence_pattern
 * @var string $recurrence_end_date
 * @var int $expected_attendees
 * @var string $admin_notes (optional public notes)
 */

$siteName = site()->title()->value();
$recurrenceLabels = [
    'weekly' => 'Wöchentlich',
    'biweekly' => 'Alle 2 Wochen',
    'monthly' => 'Monatlich',
];
?>
Guten Tag <?= $requester_name ?>,

gute Nachrichten! Ihre Buchungsanfrage wurde genehmigt.

== Bestätigte Buchung ==

Raum/Räume: <?= $room_names ?>

Datum: <?= $request_date ?>

Uhrzeit: <?= $request_time_start ?> - <?= $request_time_end ?> Uhr
<?php if ($is_recurring && $recurrence_pattern) : ?>
Wiederholung: <?= $recurrenceLabels[$recurrence_pattern] ?? $recurrence_pattern ?>
    <?php if ($recurrence_end_date) : ?>
 bis <?= $recurrence_end_date ?>
    <?php endif ?>
<?php endif ?>

Erwartete Teilnehmer: <?= $expected_attendees ?> Personen
<?php if (!empty($admin_notes)) : ?>
== Hinweise ==

    <?= $admin_notes ?>
<?php endif ?>


== Wichtige Informationen ==

- Bitte erscheinen Sie pünktlich zur vereinbarten Zeit
- Hinterlassen Sie den Raum in sauberem Zustand
- Bei Verhinderung oder Änderungen kontaktieren Sie uns bitte rechtzeitig


Bei Fragen können Sie auf diese E-Mail antworten.


Mit freundlichen Grüßen,
Ihr <?= $siteName ?>-Team
