    <footer class="ultra-compact-footer">
        <div class="footer-wrap">
            <div class="footer-left">
                <img src="<?php echo BASE_URL; ?>assest/img/artado-yeni.png" alt="Logo">
                <span class="brand-name">Artado</span>
                <div class="compact-socials">
                    <a href="https://discord.com/invite/WXCsr8zTN6"><i class="fab fa-discord"></i></a>
                    <a href="https://github.com/Artado-Project"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-center">
                <nav class="footer-nav-compact">
                    <a href="<?php echo BASE_URL; ?>">Ana Sayfa</a>
                    <a href="<?php echo BASE_URL; ?>katki.php">Destekçiler</a>
                    <a href="https://forum.artado.xyz">Forum</a>
                    <a href="https://myacc.artado.xyz/privacy">Gizlilik</a>
                </nav>
            </div>
            
            <div class="footer-right">
                <button id="expandFooter" class="expand-footer-btn" aria-label="Genişlet"><i class="fas fa-plus"></i></button>
                <div class="right-info">
                   <span class="copyright">&copy; <?php echo date('Y'); ?> Artado</span>
                   <span class="separator">|</span>
                   <span class="credits">Oyunlayıcı</span>
                </div>
            </div>
        </div>

        <!-- Genişletilebilir Mobil Bölümü -->
        <div id="expandedFooterContent" class="expanded-footer-content">
             <div class="footer-detailed-grid">
                <div class="footer-col">
                    <h4>Kurumsal</h4>
                    <a href="https://artadosearch.com/manifesto">Hakkımızda</a>
                    <a href="https://myacc.artado.xyz/privacy">Gizlilik Politikası</a>
                    <a href="mailto:arda@artadosearch.com">İletişim</a>
                </div>
                <div class="footer-col">
                    <h4>Bağlantılar</h4>
                    <a href="https://forum.artado.xyz">Forum</a>
                    <a href="https://discord.com/invite/WXCsr8zTN6">Discord Topluluğu</a>
                    <a href="https://www.patreon.com/artadosoft">Destek Ol</a>
                </div>
             </div>
        </div>
    </footer>

    <!-- Ek Özellik: Yukarı Çık Butonu -->
    <button id="backToTop" title="Yukarı Çık"><i class="fas fa-chevron-up"></i></button>

    <script src="<?php echo BASE_URL; ?>assest/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
