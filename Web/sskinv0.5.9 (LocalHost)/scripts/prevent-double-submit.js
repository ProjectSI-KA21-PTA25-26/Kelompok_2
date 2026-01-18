/**
 * Prevent Double Form Submission
 * Mencegah form dikirim lebih dari sekali saat tombol diklik berkali-kali
 * karena loading yang lambat
 */
(function() {
    'use strict';
    
    // Tunggu sampai DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Get all forms in the page
        const forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            let isSubmitting = false;
            
            form.addEventListener('submit', function(e) {
                // Jika sudah dalam proses submit, cegah submit lagi
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }
                
                // Set flag bahwa form sedang diproses
                isSubmitting = true;
                
                // Find all submit buttons in this form and disable them
                const submitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"], .btn-book');
                
                submitButtons.forEach(function(button) {
                    button.disabled = true;
                    
                    // Simpan text asli
                    if (!button.dataset.originalText) {
                        button.dataset.originalText = button.value || button.textContent;
                    }
                    
                    // Ubah text menjadi "Memproses..."
                    if (button.tagName === 'INPUT') {
                        button.value = 'Memproses...';
                    } else {
                        button.textContent = 'Memproses...';
                    }
                    
                    // Tambah visual feedback
                    button.style.opacity = '0.6';
                    button.style.cursor = 'not-allowed';
                });
                
                // Safety: Re-enable setelah 5 detik jika tidak ada redirect
                // (misalnya ada error tanpa page reload)
                setTimeout(function() {
                    isSubmitting = false;
                    
                    submitButtons.forEach(function(button) {
                        button.disabled = false;
                        
                        // Restore text asli
                        if (button.dataset.originalText) {
                            if (button.tagName === 'INPUT') {
                                button.value = button.dataset.originalText;
                            } else {
                                button.textContent = button.dataset.originalText;
                            }
                        }
                        
                        button.style.opacity = '1';
                        button.style.cursor = 'pointer';
                    });
                }, 5000);
            });
        });
    }
})();
