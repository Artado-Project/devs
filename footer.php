<?php
/**
 * Modern Responsive Footer - Artado Developers
 * Resimdeki tasarıma uygun responsive footer
 */
?>
<footer class="bg-gradient-to-r from-gray-900 to-gray-800 text-white">
    <!-- Main Footer Content -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="lg:col-span-2">
                <div class="flex items-center space-x-4 mb-6">
                    <img src="homepage/images/logo.png" alt="Artado Developers" class="w-12 h-12 rounded-lg">
                    <div>
                        <h3 class="text-2xl font-bold">Artado Developers</h3>
                        <p class="text-gray-400">Açık Kaynak Proje Platformu</p>
                    </div>
                </div>
                <p class="text-gray-300 mb-6 max-w-md">
                    Geliştiriciler için tasarlanmış açık kaynak proje paylaşım platformu. 
                    Yaratıcılığınızı sergileyin, topluluğa katkıda bulunun.
                </p>
                
                <!-- Social Media Icons -->
                <div class="flex flex-wrap gap-3">
                    <a href="https://github.com/Artado-Project" target="_blank" 
                       class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center transition-colors">
                        <i class="fab fa-github text-lg"></i>
                    </a>
                    <a href="https://x.com/ArtadoL" target="_blank" 
                       class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center transition-colors">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <a href="https://forum.artado.xyz" target="_blank" 
                       class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center transition-colors">
                        <i class="fas fa-comments text-lg"></i>
                    </a>
                    <a href="https://matrix.to/#/#artadoproject:matrix.org" target="_blank" 
                       class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center transition-colors">
                        <i class="fas fa-matrix-org text-lg"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-6">Hızlı Linkler</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="Workshop" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-tools mr-2 w-4"></i>
                            Workshop
                        </a>
                    </li>
                    <li>
                        <a href="login" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-sign-in-alt mr-2 w-4"></i>
                            Giriş Yap
                        </a>
                    </li>
                    <li>
                        <a href="register" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-user-plus mr-2 w-4"></i>
                            Kayıt Ol
                        </a>
                    </li>
                    <li>
                        <a href="katki" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-hands-helping mr-2 w-4"></i>
                            Katkıda Bulun
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Resources -->
            <div>
                <h4 class="text-lg font-semibold mb-6">Kaynaklar</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="https://artado.xyz" target="_blank" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-globe mr-2 w-4"></i>
                            Artado.xyz
                        </a>
                    </li>
                    <li>
                        <a href="https://forum.artado.xyz" target="_blank" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-comments mr-2 w-4"></i>
                            Forum
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Artado-Project" target="_blank" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fab fa-github mr-2 w-4"></i>
                            GitHub
                        </a>
                    </li>
                    <li>
                        <a href="mailto:info@artado.xyz" class="text-gray-300 hover:text-white transition-colors flex items-center">
                            <i class="fas fa-envelope mr-2 w-4"></i>
                            İletişim
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Bottom Bar -->
    <div class="border-t border-gray-700">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col lg:flex-row justify-between items-center space-y-4 lg:space-y-0">
                <!-- Copyright -->
                <div class="text-gray-400 text-center lg:text-left">
                    <p>&copy; 2024 Artado Developers. Tüm hakları saklıdır.</p>
                </div>
                
                <!-- Legal Links -->
                <div class="flex flex-wrap justify-center lg:justify-end space-x-6 text-sm">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Gizlilik Politikası</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Kullanım Şartları</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookie Politikası</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Back to Top Button -->
    <button id="backToTop" 
            class="fixed bottom-8 right-8 bg-purple-600 hover:bg-purple-700 text-white p-3 rounded-full shadow-lg transition-all duration-300 opacity-0 invisible">
        <i class="fas fa-arrow-up"></i>
    </button>
</footer>

<!-- Footer Scripts -->
<script>
// Back to top button functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.getElementById('backToTop');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.remove('opacity-0', 'invisible');
            backToTopButton.classList.add('opacity-100', 'visible');
        } else {
            backToTopButton.classList.add('opacity-0', 'invisible');
            backToTopButton.classList.remove('opacity-100', 'visible');
        }
    });
    
    // Smooth scroll to top
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// Add hover effects to social media icons
document.querySelectorAll('footer a[href*="github"], footer a[href*="twitter"], footer a[href*="forum"], footer a[href*="matrix"]').forEach(icon => {
    icon.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
    });
    
    icon.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>
