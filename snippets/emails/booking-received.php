<?php
/**
 * Booking Request Confirmation Email
 * Sent to requester after submitting a booking request
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
 * @var string $purpose
 * @var string $special_requirements
 */

$siteName = site()->title()->value();
$recurrenceLabels = [
    'weekly' => 'Wöchentlich',
    'biweekly' => 'Alle 2 Wochen',
    'monthly' => 'Monatlich',
];
?>
Guten Tag <?= $requester_name ?>,

vielen Dank für Ihre Buchungsanfrage bei <?= $siteName ?>.

Wir haben Ihre Anfrage erhalten und werden diese schnellstmöglich bearbeiten. Sie erhalten eine weitere E-Mail, sobald Ihre Anfrage bearbeitet wurde.

== Ihre Anfrage im Überblick ==

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


== Was passiert als nächstes? ==

Unser Team wird Ihre Anfrage prüfen und sich innerhalb von 2-3 Werktagen bei Ihnen melden. Bei Fragen können Sie auf diese E-Mail antworten.


Mit freundlichen Grüßen,
Ihr <?= $siteName ?>-Team
