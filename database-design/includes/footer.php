</div>
</main>

<footer
    style="background: rgba(9,11,16,0.9); border-top: 1px solid var(--panel-border); padding: 30px 0; text-align: center; font-size: 14px; color: var(--color-text-muted);">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Bus Pass Management. All Rights Reserved. Built with <i class="fas fa-heart"
                style="color: #ef4444;"></i> for a modern transit experience.</p>
    </div>
</footer>

<!-- Global scripts -->
<script src="/Bus-pass-managemnet/assets/js/main.js"></script>

<!-- Conditionally load search script for passes page -->
<?php if (strpos($_SERVER['REQUEST_URI'], 'admin/passes.php') !== false): ?>
    <script src="/Bus-pass-managemnet/assets/js/search.js"></script>
<?php endif; ?>

<!-- Display system notices / flash alerts -->
<?php display_flash_messages(); ?>

</body>

</html>