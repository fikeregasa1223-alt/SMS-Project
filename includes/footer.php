<?php
declare(strict_types=1);

$extraJs = $extraJs ?? [];   // ← KUN MUHIIMAA
?>
        </main>

        <footer class="px-4 py-3 text-muted small border-top">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <span>&copy; <?= date('Y') ?> <?= e(setting('school_name', 'SMS')) ?>. All rights reserved.</span>
                <span><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></span>
            </div>
        </footer>
    </div><!-- /.main -->
</div><!-- /.app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/components.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>

<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
    <?php if (preg_match('#^https?://#', $js)): ?>
        <script src="<?= e($js) ?>"></script>
    <?php else: ?>
        <script src="<?= url($js) ?>"></script>
    <?php endif; ?>
<?php endforeach; endif; ?>
</body>
</html>