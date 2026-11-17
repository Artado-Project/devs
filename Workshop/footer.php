<footer class="border-t border-white/[0.07] bg-[#090a0f] text-slate-500 text-xs py-8 px-4 sm:px-6 mt-auto">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        
        <div class="flex items-center gap-3">
            <img src="https://devs.artado.xyz/homepage/images/logo.png" alt="Artado" class="w-5 h-5 object-contain grayscale opacity-60">
            <span class="text-slate-400 font-medium">&copy; <?=date('Y')?> Artado Project</span>
            <span class="text-slate-700">•</span>
            <span class="inline-flex items-center gap-1.5 text-[11px] text-emerald-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Tüm sistemler çalışıyor
            </span>
        </div>

        <div class="flex items-center gap-5 text-slate-400 text-xs font-medium">
            <a href="https://forum.artado.xyz" target="_blank" rel="noopener" class="hover:text-white transition-colors">Forum</a>
            <a href="https://devs.artado.xyz" target="_blank" rel="noopener" class="hover:text-white transition-colors">Geliştirici</a>
            <a href="https://discord.com/invite/WXCsr8zTN6" target="_blank" rel="noopener" class="hover:text-[#5865F2] transition-colors"><i class="fab fa-discord"></i></a>
            <a href="https://github.com/Artado-Project" target="_blank" rel="noopener" class="hover:text-white transition-colors"><i class="fab fa-github"></i></a>
        </div>

    </div>
</footer>

<!-- Global Toast Container -->
<div id="toastContainer" class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none"></div>

<script>
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border text-xs font-medium backdrop-blur-xl transition-all duration-200 transform translate-y-2 opacity-0 shadow-2xl`;
        
        if (type === 'success') {
            toast.className += ' bg-[#0f1912]/95 border-emerald-500/30 text-emerald-300';
            toast.innerHTML = `<i class="fas fa-check-circle text-emerald-400 text-xs"></i><span>${message}</span>`;
        } else if (type === 'error') {
            toast.className += ' bg-[#1f0f12]/95 border-rose-500/30 text-rose-300';
            toast.innerHTML = `<i class="fas fa-exclamation-circle text-rose-400 text-xs"></i><span>${message}</span>`;
        } else {
            toast.className += ' bg-[#121620]/95 border-white/10 text-slate-200';
            toast.innerHTML = `<i class="fas fa-info-circle text-blue-400 text-xs"></i><span>${message}</span>`;
        }

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 200);
        }, 3000);
    }
</script>