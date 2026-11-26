/**
 * FORT EXPLORER - Main Frontend Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Navbar scroll effect
    const navbar = document.querySelector('.fe-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. Quick Wishlist Toggle via AJAX
    document.querySelectorAll('.fe-wishlist-toggle-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const destId = btn.getAttribute('data-dest-id');
            if (!destId) return;

            // Visual feedback
            const icon = btn.querySelector('i');
            const originalClass = icon ? icon.className : '';
            if (icon) {
                icon.className = 'fa-solid fa-spinner fa-spin';
            }

            try {
                const response = await fetch('api/toggle-wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `destination_id=${encodeURIComponent(destId)}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        if (icon) icon.className = 'fa-solid fa-heart text-danger';
                        showToast(data.message || 'Added to your Wishlist!', 'success');
                    } else {
                        btn.classList.remove('active');
                        if (icon) icon.className = 'fa-regular fa-heart';
                        showToast(data.message || 'Removed from your Wishlist.', 'info');
                    }
                } else if (data.status === 'unauthorized') {
                    showToast('Please sign in to save places to your Wishlist.', 'warning');
                    setTimeout(() => {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    }, 1200);
                } else {
                    if (icon) icon.className = originalClass;
                    showToast(data.message || 'Action failed. Please try again.', 'error');
                }
            } catch (err) {
                if (icon) icon.className = originalClass;
                showToast('Network error while updating wishlist.', 'error');
            }
        });
    });

    // 3. Interactive Star Rating Picker in forms
    const starPicker = document.querySelector('.rating-picker');
    if (starPicker) {
        const stars = starPicker.querySelectorAll('.rating-star-icon');
        const input = starPicker.querySelector('input[name="rating"]');

        stars.forEach(star => {
            star.addEventListener('mouseover', () => {
                const val = parseInt(star.getAttribute('data-value'));
                highlightStars(stars, val);
            });

            star.addEventListener('mouseout', () => {
                const currentVal = parseInt(input.value) || 5;
                highlightStars(stars, currentVal);
            });

            star.addEventListener('click', () => {
                const val = parseInt(star.getAttribute('data-value'));
                input.value = val;
                highlightStars(stars, val);
            });
        });

        function highlightStars(starList, count) {
            starList.forEach(s => {
                const sVal = parseInt(s.getAttribute('data-value'));
                if (sVal <= count) {
                    s.classList.remove('fa-regular', 'text-muted');
                    s.classList.add('fa-solid', 'text-warning');
                } else {
                    s.classList.remove('fa-solid', 'text-warning');
                    s.classList.add('fa-regular', 'text-muted');
                }
            });
        }
    }

    // 4. Detail Page Gallery Thumbnail Clicker
    const mainGalleryImg = document.getElementById('mainGalleryImage');
    const thumbs = document.querySelectorAll('.dest-gallery-thumb');
    if (mainGalleryImg && thumbs.length > 0) {
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                thumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                mainGalleryImg.src = thumb.getAttribute('data-full-img');
            });
        });
    }

    // 5. Live Search Autocomplete
    const searchInputs = document.querySelectorAll('input[name="q"]');
    searchInputs.forEach(input => {
        let debounceTimer = null;
        let dropdown = null;

        input.setAttribute('autocomplete', 'off');

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = input.value.trim();

            if (query.length < 2) {
                if (dropdown) dropdown.remove();
                return;
            }

            debounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`api/search-autocomplete.php?q=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    
                    if (dropdown) dropdown.remove();

                    if (!data.results || data.results.length === 0) return;

                    dropdown = document.createElement('div');
                    dropdown.className = 'fe-search-autocomplete-dropdown shadow-lg';
                    dropdown.style.cssText = `
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        background: #ffffff;
                        border-radius: 12px;
                        border: 1px solid #e2d9cc;
                        margin-top: 8px;
                        z-index: 1050;
                        overflow: hidden;
                        text-align: left;
                    `;

                    let html = '<div class="list-group list-group-flush">';
                    data.results.forEach(item => {
                        html += `
                            <a href="${item.url}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-2.5 text-decoration-none">
                                <img src="${item.image}" alt="${item.title}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px;">
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark small text-truncate mb-0">
                                        ${item.title} ${item.is_fort ? '<span class="badge bg-danger-subtle text-danger" style="font-size:0.65rem;">Fort</span>' : ''}
                                    </div>
                                    <span class="text-muted" style="font-size: 0.75rem;">${item.subtitle}</span>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    dropdown.innerHTML = html;

                    const parent = input.parentElement;
                    parent.style.position = 'relative';
                    parent.appendChild(dropdown);

                } catch (e) {
                    // Ignore network error in live search
                }
            }, 250);
        });

        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (dropdown && !input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.remove();
            }
        });
    });
});

/**
 * Toast notification popup helper
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('fe-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'fe-toast-container';
        container.style.cssText = 'position:fixed;bottom:25px;right:25px;z-index:99999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#163820' : (type === 'error' ? '#8b2616' : (type === 'warning' ? '#d97706' : '#1e2124'));
    toast.style.cssText = `background:${bg};color:#fff;padding:12px 20px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.3);font-size:0.92rem;font-weight:500;display:flex;align-items:center;gap:10px;border-left:4px solid #c59b27;transition:all 0.3s ease;transform:translateY(20px);opacity:0;`;

    const icon = type === 'success' ? 'fa-circle-check' : (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info');
    toast.innerHTML = `<i class="fa-solid ${icon} text-warning"></i> <span>${message}</span>`;
    container.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    }, 50);

    // Auto dismiss
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 350);
    }, 3500);
}
