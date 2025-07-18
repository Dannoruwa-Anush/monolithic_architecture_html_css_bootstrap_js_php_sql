<?php

function renderConfirmationModal(
    $modalId,
    $title,
    $body,
    $formAction = '',
    $hiddenInputName = 'entityId',
    $submitLabel = 'Confirm',
    $submitClass = 'btn-primary'
) {
?>
<div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
      <input type="hidden" name="<?= htmlspecialchars($hiddenInputName) ?>" id="<?= $modalId ?>Input">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="<?= $modalId ?>Label"><?= htmlspecialchars($title) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?= htmlspecialchars($body) ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn <?= htmlspecialchars($submitClass) ?>"><?= htmlspecialchars($submitLabel) ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php
}
?>
