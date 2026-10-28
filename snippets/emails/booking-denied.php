<?php
/**
 * Booking Denied Email
 * Sent to requester when their booking is denied
 *
 * @var string $requester_name
 * @var string $room_names
 * @var string $request_date
 * @var string $request_time_start
 * @var string $request_time_end
 * @var string $denial_reason
 */

$siteName = site()->title()->value();
?>
Guten Tag <?= $requester_name ?>,

leider müssen wir Ihnen mitteilen, dass Ihre Buchungsanfrage nicht genehmigt werden konnte.

== Ihre Anfrage ==

Raum/Räume: <?= $room_names ?>

Datum: <?= $request_date ?>

Uhrzeit: <?= $request_time_start ?> - <?= $request_time_end ?> Uhr
<?php if (!empty($denial_reason)) : ?>
== Begründung ==

    <?= $denial_reason ?>
<?php endif ?>


== Alternative Möglichkeiten ==

Sie können gerne eine neue Anfrage für einen anderen Termin oder Raum stellen. Besuchen Sie dazu unsere Räume-Seite.

Bei Fragen können Sie auf diese E-Mail antworten.


Mit freundlichen Grüßen,
Ihr <?= $siteName ?>-Team
