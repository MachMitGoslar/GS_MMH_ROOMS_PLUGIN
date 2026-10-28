<?php

/**
 * Rooms (Räume) Listing Template
 * @var \Kirby\Cms\Site $site
 * @var \Kirby\Cms\Page $page
 */
?>
<?php snippet('layout/head', slots: true); ?>
<?php slot('head') ?>
<?= css(kirby()->plugin('gs-mmh/gs-mmh-rooms-plugin')->asset('rooms.css')->url()) ?>
<?php endslot() ?>
<?php endsnippet() ?>
<?php snippet('layout/header'); ?>

<main class="main">
  <!-- Hero Section -->
  <section class="rooms-hero">
    <?php if ($page->cover() && $cover = $page->cover()->toFile()) : ?>
      <div class="rooms-hero-image">
        <?php snippet('utilities/image', [
            'file' => $cover->toFile(),
            'role' => 'hero',
            'ratio' => '16:5',
            'lazy' => false,
            'alt' => $page->title()->value(),
        ]) ?>
        <div class="rooms-hero-overlay"></div>
      </div>
    <?php endif ?>
    <div class="rooms-hero-content">
      <div class="grid content">
        <div class="grid-item" data-span="1/1">
          <h1 class="font-titleXXL"><?= $page->headline()->or($page->title())->html() ?></h1>
          <?php if ($page->subheadline()->isNotEmpty()) : ?>
            <p class="font-titleXL font-weight-light"><?= $page->subheadline()->html() ?></p>
          <?php endif ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Introduction -->
  <?php if ($page->description()->isNotEmpty()) : ?>
    <section class="grid content rooms-intro">
      <div class="grid-item" data-span="2/3">
        <?php foreach ($page->description()->toBlocks() as $block) : ?>
            <?php if ($block->isVisible()) : ?>
            <div class="c-blog c-blog-<?= $block->type() ?>">
                <?= $block ?>
            </div>
            <?php endif ?>
        <?php endforeach ?>
      </div>
    </section>
  <?php endif ?>

  <!-- Rooms Grid -->
  <section class="grid content rooms-section">
    <div class="grid-item" data-span="1/1">
      <h2 class="font-title section-title">Verfügbare Räume</h2>
    </div>

    <?php $rooms = $page->children()->listed(); ?>

    <?php if ($rooms->count() > 0) : ?>
      <div class="grid-item" data-span="1/1">
        <ul class="grid rooms-grid">
          <?php foreach ($rooms as $room) : ?>
            <li class="room-card-wrapper">
                <?= snippet('content-types/rooms/roomCard', ['room' => $room]) ?>
            </li>
          <?php endforeach ?>
        </ul>
      </div>
    <?php else : ?>
      <div class="grid-item" data-span="1/1">
        <p class="font-body rooms-empty">Aktuell sind keine Räume verfügbar.</p>
      </div>
    <?php endif ?>
  </section>

  <!-- Booking Request CTA -->
  <section class="grid rooms-cta mb-8">
    <div class="grid-item" data-span="1/1">
      <div class="rooms-cta-box">
        <h2 class="font-title">Räume anfragen</h2>
        <p class="font-body">Sie möchten einen oder mehrere Räume für Ihre Veranstaltung nutzen? Stellen Sie jetzt eine Anfrage!</p>
        <a href="#booking-form" class="gs-c-btn" data-type="primary" data-size="large">
          Buchungsanfrage stellen
        </a>
      </div>
    </div>
  </section>

  <!-- Booking Info -->
  <?php if ($page->booking_info()->isNotEmpty()) : ?>
    <section class="grid rooms-booking-info">
      <div class="grid-item" data-span="2/3">
        <h2 class="font-title">Buchungshinweise</h2>
        <?php foreach ($page->booking_info()->toBlocks() as $block) : ?>
            <?php if ($block->isVisible()) : ?>
            <div class="c-blog c-blog-<?= $block->type() ?>">
                <?= $block ?>
            </div>
            <?php endif ?>
        <?php endforeach ?>
      </div>
    </section>
  <?php endif ?>

  <!-- Booking Form -->
  <section id="booking-form" class="grid content rooms-form-section">

    <div class="grid-item" data-span="2/3">

      <h2 class="font-title mb-4">Buchungsanfrage</h2>

      <?= snippet('content-types/rooms/bookingForm', ['rooms' => $rooms, 'settings' => $page]) ?>
    </div>
    <div class="grid-item" data-span="1/3">
      <!-- <div class="booking-sidebar">
        <h3 class="font-headline">Kontakt</h3>
        <p class="font-body">
          Bei Fragen zur Raumbuchung erreichen Sie uns unter:
        </p>
        <p class="font-body">
          <strong>E-Mail:</strong> <a href="mailto:machmit@goslar.de">machmit@goslar.de</a><br>
          <strong>Telefon:</strong> <a href="tel:+495321704525">05321 704 525</a>
        </p>
        <p class="font-footnote">
          <strong>Vorlaufzeit:</strong> mind. <?= $page->lead_time_days()->or(1) ?> Tag(e)<br>
          <strong>Buchbar bis:</strong> <?= $page->max_future_days()->or(90) ?> Tage im Voraus
        </p>
      </div> -->

      <!-- Availability Calendar -->
      <?php if ($page->show_calendar_overview()->toBool() && $page->overview_calendar_url()->isNotEmpty()) : ?>
        <div class="room-calendar">
          <h2 class="font-title mb-4">Verfügbarkeit</h2>
          <div class="calendar-embed">
            <iframe src="<?= $page->overview_calendar_url() ?>"
              style="border: 0"
              width="100%"
              height="400"
              frameborder="0"
              scrolling="no"
              loading="lazy"></iframe>
          </div>
          <p class="font-footnote calendar-note">
            Grau hinterlegte Zeiten sind bereits belegt.
          </p>
        </div>
      <?php endif ?>
    </div>
  </section>
</main>

<?php snippet('layout/footer'); ?>
<?php snippet('layout/foot'); ?>
