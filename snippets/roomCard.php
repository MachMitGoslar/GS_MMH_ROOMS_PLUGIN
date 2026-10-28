<?php
/**
 * Room Card Snippet
 * Displays a room teaser card for the rooms listing
 *
 * @var \Kirby\Cms\Page $room The room page object
 */
?>

<article class="room-card">
    <!-- Image -->
    <div class="room-card-image-wrapper">
      <?php if ($cover = $room->cover()) : ?>
            <?php snippet('utilities/image', [
            'file' => $cover,
            'role' => 'card',
            'ratio' => '3:2',
            'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
            'class' => 'room-card-image',
            'alt' => $room->title()->value(),
        ]) ?>
      <?php else : ?>
        <div class="room-card-placeholder">
          <?php snippet('utilities/icon', ['name' => 'home']) ?>

        </div>
      <?php endif ?>

      <!-- Quick badges -->
      <div class="room-card-badges">
        <span class="room-card-badge badge-capacity">
          <?= $room->capacity()->or('–') ?> Pers.
        </span>
        <?php if ($room->accessible()->toBool()) : ?>
          <span class="room-card-badge badge-accessible"><?php snippet('utilities/icon', ['name' => 'accessibility', 'size' => 16, 'label' => 'Barrierefrei']) ?></span>
        <?php endif ?>
      </div>
    </div>

    <!-- Content -->
    <div class="room-card-content">
      <h3 class="room-card-title font-headline font-line-height-narrow"><?= $room->title()->html() ?></h3>

      <?php if ($room->short_description()->isNotEmpty()) : ?>
        <p class="room-card-description font-footnote">
            <?= $room->short_description()->excerpt(120) ?>
        </p>
      <?php endif ?>

      <!-- Equipment preview -->
      <?php $equipment = $room->equipment()->toStructure()->limit(4); ?>
      <?php if ($equipment->count() > 0) : ?>
        <div class="room-card-equipment">
            <?php foreach ($equipment as $item) : ?>
            <span class="equipment-icon-small" title="<?= $item->name()->html() ?>">
                <?= snippet('content-types/rooms/equipmentIcon', ['icon' => $item->icon()->value(), 'small' => true]) ?>
            </span>
            <?php endforeach ?>
            <?php if ($room->equipment()->toStructure()->count() > 4) : ?>
            <span class="equipment-more font-footnote">+<?= $room->equipment()->toStructure()->count() - 4 ?></span>
            <?php endif ?>
        </div>
      <?php endif ?>

      <!-- Pricing hint -->
      <?php if ($room->pricing_enabled()->toBool() && $room->hourly_rate()->isNotEmpty()) : ?>
        <div class="room-card-pricing font-footnote">
          ab <?= number_format($room->hourly_rate()->toFloat(), 0, ',', '.') ?> €/Stunde
        </div>
      <?php else : ?>
        <div class="room-card-pricing room-card-free font-footnote">
          Kostenlos nutzbar
        </div>
      <?php endif ?>

      <!-- CTA -->
      <a href="<?= $room->url() ?>" class="room-card-cta gs-c-btn" data-type="secondary" data-size="small">
        Details ansehen
      </a>
    </div>
</article>
