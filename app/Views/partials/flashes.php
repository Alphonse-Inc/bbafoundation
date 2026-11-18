<?php $messages = flashes(); ?>
<?php if ($messages !== []): ?>
    <div class="toasts" role="status">
        <?php foreach ($messages as $type => $message): ?>
            <div class="toast"><?= e($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
