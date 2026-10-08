<?php
/**
 * Nine-stage tracking timeline. Expects $trackingResult (a shipment row with id, status, expected_arrival).
 */
$stages = Shipment::statuses();
$currentIndex = (int) array_search($trackingResult['status'], $stages, true);
$reachedAt = [];
foreach ((new Shipment())->events((int) $trackingResult['id']) as $event) {
    $reachedAt[$event['status']] = $event['created_at'];
}
$stageHelp = [
    'order_received' => 'We have your order.',
    'payment_confirmed' => 'Your payment is confirmed.',
    'ordered_from_supplier' => 'Ordered from our supplier abroad.',
    'shipped_from_origin' => 'Left the origin country.',
    'in_transit' => 'On its way to Tanzania.',
    'arrived_at_port' => 'Arrived at the port of entry.',
    'cleared' => 'Customs clearance completed.',
    'ready_for_pickup' => 'Ready at our shop for pickup or delivery.',
    'delivered' => 'Delivered. Thank you for your order.',
];
?>
<div class="shipment-timeline-wrap">
    <div class="shipment-timeline-head">
        <div>
            <small>Current stage</small>
            <strong><?= h(readable_status($trackingResult['status'])) ?></strong>
            <span class="text-muted">&middot; step <?= $currentIndex + 1 ?> of <?= count($stages) ?></span>
        </div>
        <?php if (!empty($trackingResult['expected_arrival']) && $trackingResult['status'] !== 'delivered'): ?>
            <div class="text-end">
                <small>Expected arrival</small>
                <strong><?= h(date('M j, Y', strtotime($trackingResult['expected_arrival']))) ?></strong>
            </div>
        <?php endif; ?>
    </div>
    <div class="shipment-progress" role="progressbar" aria-label="Shipment progress" aria-valuemin="1" aria-valuemax="<?= count($stages) ?>" aria-valuenow="<?= $currentIndex + 1 ?>">
        <span style="width: <?= round(($currentIndex) / (count($stages) - 1) * 100) ?>%"></span>
    </div>
    <ol class="shipment-timeline">
        <?php foreach ($stages as $index => $stage): ?>
            <?php $state = $index < $currentIndex ? 'is-done' : ($index === $currentIndex ? 'is-current' : 'is-upcoming'); ?>
            <li class="<?= $state ?>" <?= $state === 'is-current' ? 'aria-current="step"' : '' ?>>
                <span class="shipment-timeline-marker">
                    <?php if ($state === 'is-done' || ($state === 'is-current' && $stage === 'delivered')): ?>
                        <i class="bi bi-check-lg"></i>
                    <?php else: ?>
                        <?= $index + 1 ?>
                    <?php endif; ?>
                </span>
                <div>
                    <strong><?= h(readable_status($stage)) ?></strong>
                    <?php if ($state !== 'is-upcoming'): ?>
                        <small><?= h($stageHelp[$stage] ?? '') ?></small>
                    <?php endif; ?>
                </div>
                <?php if (isset($reachedAt[$stage]) && $state !== 'is-upcoming'): ?>
                    <time datetime="<?= h(date('c', strtotime($reachedAt[$stage]))) ?>"><?= h(date('M j, H:i', strtotime($reachedAt[$stage]))) ?></time>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
