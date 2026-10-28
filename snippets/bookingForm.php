<?php
/**
 * Booking Request Form Snippet
 * Multi-room booking form with date/time selection
 *
 * @var \Kirby\Cms\Pages $rooms Available rooms
 * @var \Kirby\Cms\Page $settings The rooms parent page with settings
 */

$leadTime = $settings->lead_time_days()->or(1)->toInt();
$maxFuture = $settings->max_future_days()->or(90)->toInt();
$timeSlot = $settings->time_slot_minutes()->or('15')->value();
$openingTime = $settings->opening_time()->or('08:00')->value();
$closingTime = $settings->closing_time()->or('22:00')->value();

$minDate = date('Y-m-d', strtotime("+{$leadTime} days"));
$maxDate = date('Y-m-d', strtotime("+{$maxFuture} days"));
?>

<form class="booking-form" method="post" action="<?= kirby()->url('api') ?>/booking/submit" id="room-booking-form" data-booking-form>
  <input type="hidden" name="csrf" value="<?= csrf() ?>">

  <!-- Room Selection -->
  <fieldset class="form-section">
    <legend class="font-headline">1. Räume auswählen</legend>
    <p class="form-help font-footnote">Wählen Sie einen oder mehrere Räume für Ihre Veranstaltung.</p>

    <div class="room-selection-grid">
      <?php foreach ($rooms as $room) : ?>
        <label class="room-checkbox-card">
          <input type="checkbox"
                 name="rooms[]"
                 value="<?= $room->id() ?>"
                 data-capacity="<?= $room->capacity()->or(0) ?>"
                 class="room-checkbox">
          <span class="room-checkbox-content">
            <?php if ($cover = $room->cover()) : ?>
              <img src="<?= $cover->crop(120, 80)->url() ?>" alt="<?= $room->title()->html() ?>" class="room-checkbox-image">
            <?php endif ?>
            <span class="room-checkbox-info">
              <span class="room-checkbox-title font-body"><?= $room->title()->html() ?></span>
              <span class="room-checkbox-capacity font-footnote"><?= $room->capacity() ?> Personen</span>
            </span>
            <span class="room-checkbox-indicator"></span>
          </span>
            </input>
        </label>
      <?php endforeach ?>
    </div>

    <div class="selected-capacity-display" id="total-capacity" style="display: none;">
      <span class="font-footnote">Gesamtkapazität: <strong id="capacity-value">0</strong> Personen</span>
    </div>
  </fieldset>

  <!-- Date & Time -->
  <fieldset class="form-section">
    <legend class="font-headline">2. Datum & Uhrzeit</legend>

    <div class="form-row">
      <div class="form-group form-group-date">
        <label for="request_date" class="form-label">Datum *</label>
        <input type="date"
               id="request_date"
               name="request_date"
               min="<?= $minDate ?>"
               max="<?= $maxDate ?>"
               required
               class="form-input">
        <span class="form-hint font-footnote">Mind. <?= $leadTime ?> Tag(e) Vorlauf</span>
      </div>

      <div class="form-group form-group-time">
        <label for="request_time_start" class="form-label">Von *</label>
        <input type="time"
               id="request_time_start"
               name="request_time_start"
               min="<?= $openingTime ?>"
               max="<?= $closingTime ?>"
               step="<?= $timeSlot * 60 ?>"
               required
               class="form-input">
      </div>

      <div class="form-group form-group-time">
        <label for="request_time_end" class="form-label">Bis *</label>
        <input type="time"
               id="request_time_end"
               name="request_time_end"
               min="<?= $openingTime ?>"
               max="<?= $closingTime ?>"
               step="<?= $timeSlot * 60 ?>"
               required
               class="form-input">
      </div>
    </div>

    <!-- Recurring Option -->
    <div class="form-row">
      <div class="form-group form-group-checkbox">
        <label class="form-checkbox-label">
          <input type="checkbox"
                 id="is_recurring"
                 name="is_recurring"
                 value="1"
                 class="form-checkbox">
          <span>Dies ist ein wiederkehrender Termin</span>
        </label>
      </div>
    </div>

    <div class="recurring-options" id="recurring-options" style="display: none;">
      <div class="form-row">
        <div class="form-group">
          <label for="recurrence_pattern" class="form-label">Wiederholung</label>
          <select id="recurrence_pattern" name="recurrence_pattern" class="form-input">
            <option value="weekly">Wöchentlich</option>
            <option value="biweekly">Alle 2 Wochen</option>
            <option value="monthly">Monatlich</option>
          </select>
        </div>

        <div class="form-group">
          <label for="recurrence_end_date" class="form-label">Bis (Enddatum)</label>
          <input type="date"
                 id="recurrence_end_date"
                 name="recurrence_end_date"
                 min="<?= $minDate ?>"
                 max="<?= $maxDate ?>"
                 class="form-input">
        </div>
      </div>
    </div>
  </fieldset>

  <!-- Event Details -->
  <fieldset class="form-section">
    <legend class="font-headline">3. Veranstaltungsdetails</legend>

    <div class="form-row">
      <div class="form-group">
        <label for="expected_attendees" class="form-label">Erwartete Teilnehmerzahl *</label>
        <input type="number"
               id="expected_attendees"
               name="expected_attendees"
               min="1"
               required
               class="form-input"
               placeholder="z.B. 15">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group form-group-full">
        <label for="purpose" class="form-label">Verwendungszweck *</label>
        <textarea id="purpose"
                  name="purpose"
                  rows="3"
                  required
                  class="form-input"
                  placeholder="Beschreiben Sie kurz Ihre geplante Veranstaltung..."></textarea>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group form-group-full">
        <label for="special_requirements" class="form-label">Besondere Anforderungen</label>
        <textarea id="special_requirements"
                  name="special_requirements"
                  rows="2"
                  class="form-input"
                  placeholder="z.B. Beamer benötigt, Catering, barrierefreier Zugang..."></textarea>
      </div>
    </div>
  </fieldset>

  <!-- Contact Information -->
  <fieldset class="form-section">
    <legend class="font-headline">4. Kontaktdaten</legend>

    <div class="form-row">
      <div class="form-group">
        <label for="requester_name" class="form-label">Ihr Name *</label>
        <input type="text"
               id="requester_name"
               name="requester_name"
               required
               class="form-input"
               placeholder="Vor- und Nachname">
      </div>

      <div class="form-group">
        <label for="requester_organization" class="form-label">Organisation</label>
        <input type="text"
               id="requester_organization"
               name="requester_organization"
               class="form-input"
               placeholder="Firma, Verein, etc.">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="requester_email" class="form-label">E-Mail-Adresse *</label>
        <input type="email"
               id="requester_email"
               name="requester_email"
               required
               class="form-input"
               placeholder="ihre@email.de">
      </div>

      <div class="form-group">
        <label for="requester_phone" class="form-label">Telefonnummer</label>
        <input type="tel"
               id="requester_phone"
               name="requester_phone"
               class="form-input"
               placeholder="+49 123 456789">
      </div>
    </div>
  </fieldset>

  <!-- Privacy & Submit -->
  <fieldset class="form-section form-section-submit">
    <div class="form-row">
      <div class="form-group form-group-checkbox form-group-full">
        <label class="form-checkbox-label">
          <input type="checkbox"
                 id="privacy_accepted"
                 name="privacy_accepted"
                 value="1"
                 required
                 class="form-checkbox">
          <span>Ich habe die <a href="<?= url('datenschutz') ?>" target="_blank">Datenschutzerklärung</a> gelesen und bin mit der Verarbeitung meiner Daten einverstanden. *</span>
        </label>
      </div>
    </div>

    <div class="form-row form-row-submit">
      <button type="submit" class="gs-c-btn" data-type="primary" data-size="large">
        Anfrage absenden
      </button>
    </div>

    <p class="form-note font-footnote">
      * Pflichtfelder. Nach dem Absenden erhalten Sie eine Bestätigung per E-Mail.
      Wir melden uns zeitnah bei Ihnen.
    </p>
  </fieldset>
</form>

<!-- Form Feedback -->
<div class="booking-form-feedback" id="booking-feedback" style="display: none;">
  <div class="feedback-success" id="feedback-success">
    <h3 class="font-headline gs-c-icon-text"><?php snippet('utilities/icon', ['name' => 'check-circle']) ?> Anfrage erfolgreich gesendet!</h3>
    <p class="font-body">Vielen Dank für Ihre Buchungsanfrage. Sie erhalten in Kürze eine Bestätigung per E-Mail.</p>
    <p class="font-body">Wir werden Ihre Anfrage schnellstmöglich bearbeiten und uns bei Ihnen melden.</p>
  </div>
  <div class="feedback-error" id="feedback-error">
    <h3 class="font-headline gs-c-icon-text"><?php snippet('utilities/icon', ['name' => 'x-circle']) ?> Fehler beim Senden</h3>
    <p class="font-body" id="error-message">Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie uns direkt.</p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('room-booking-form');
  const roomCheckboxes = document.querySelectorAll('.room-checkbox');
  const capacityDisplay = document.getElementById('total-capacity');
  const capacityValue = document.getElementById('capacity-value');
  const recurringCheckbox = document.getElementById('is_recurring');
  const recurringOptions = document.getElementById('recurring-options');
  const feedback = document.getElementById('booking-feedback');
  const feedbackSuccess = document.getElementById('feedback-success');
  const feedbackError = document.getElementById('feedback-error');

  // Update total capacity when rooms are selected
  function updateCapacity() {
    let total = 0;
    let anySelected = false;

    roomCheckboxes.forEach(function(cb) {
      if (cb.checked) {
        anySelected = true;
        total += parseInt(cb.dataset.capacity) || 0;
      }
    });

    capacityDisplay.style.display = anySelected ? 'block' : 'none';
    capacityValue.textContent = total;
  }

  roomCheckboxes.forEach(function(cb) {
    cb.addEventListener('change', updateCapacity);
  });

  // Toggle recurring options
  recurringCheckbox.addEventListener('change', function() {
    recurringOptions.style.display = this.checked ? 'block' : 'none';
  });

  // Time validation
  const timeStart = document.getElementById('request_time_start');
  const timeEnd = document.getElementById('request_time_end');

  timeStart.addEventListener('change', function() {
    timeEnd.min = this.value;
  });

  // Form submission
  form.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate at least one room selected
    const selectedRooms = document.querySelectorAll('.room-checkbox:checked');
    if (selectedRooms.length === 0) {
      alert('Bitte wählen Sie mindestens einen Raum aus.');
      return;
    }

    // Validate time
    if (timeEnd.value <= timeStart.value) {
      alert('Die Endzeit muss nach der Startzeit liegen.');
      return;
    }

    // Disable submit button during submission
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Wird gesendet...';

    const formData = new FormData(form);
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();
      // Show feedback
      form.style.display = 'none';
      feedback.style.display = 'block';

      if (result.success) {
        feedbackSuccess.style.display = 'block';
        feedbackError.style.display = 'none';
      } else {
        feedbackSuccess.style.display = 'none';
        feedbackError.style.display = 'block';
        document.getElementById('error-message').textContent = result.message || 'Unbekannter Fehler';
      }

      // Scroll to feedback (fixes Firefox viewport issues)
      feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });

    } catch (error) {
      form.style.display = 'none';
      feedback.style.display = 'block';
      feedbackSuccess.style.display = 'none';
      feedbackError.style.display = 'block';
      feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } finally {
      // Re-enable submit button
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });
});
</script>
