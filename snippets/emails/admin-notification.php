<?php
/**
 * Admin Notification Email for New Booking Request
 * Sent to admin when a new booking request is submitted
 *
 * @var string $requester_name
 * @var string $requester_email
 * @var string $requester_phone
 * @var string $requester_organization
 * @var string $room_names
 * @var string $request_date
 * @var string $request_time_start
 * @var string $request_time_end
 * @var bool $is_recurring
 * @var string $recurrence_pattern
 * @var string $recurrence_end_date
 * @var int $expected_attendees
 * @var string $purpose
 * @var string $special_requirements
 * @var string $panel_url
 */

$recurrenceLabels = [
    'weekly' => 'Wöchentlich',
    'biweekly' => 'Alle 2 Wochen',
    'monthly' => 'Monatlich',
];
?>
Neue Buchungsanfrage eingegangen

== Anfragende Person ==

Name: <?= $requester_name ?>

E-Mail: <?= $requester_email ?>
<?php if ($requester_phone) : ?>
Telefon: <?= $requester_phone ?>
<?php endif ?>
<?php if ($requester_organization) : ?>
Organisation: <?= $requester_organization ?>
<?php endif ?>


== Buchungsdetails ==

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

Verwendungszweck:
<?= $purpose ?>
<?php if ($special_requirements) : ?>
Besondere Anforderungen:
    <?= $special_requirements ?>
<?php endif ?>


== Aktion erforderlich ==

Bitte bearbeiten Sie diese Anfrage im Panel:
<?= $panel_url ?>


Diese Nachricht wurde automatisch generiert.
