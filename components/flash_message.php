<?php foreach (Session::pullFlash() as $flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status">
        <span><?= e($flash['message']) ?>
        </span>
</div>
<?php endforeach; ?>