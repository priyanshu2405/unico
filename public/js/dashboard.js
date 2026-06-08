// dashbord click active start
document.addEventListener("DOMContentLoaded", function () {
    const navLinks = document.querySelectorAll("#sidebar .nav-link");
    const currentPath = window.location.pathname;

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === '#' || href === '' || href === 'javascript:void(0)') {
            return;
        }

        // Link's own path
        const linkPath = new URL(link.href, window.location.origin).pathname;

        // If current path matches link path, ensure it's active
        if (linkPath === currentPath) {
            link.classList.add("active");
        } else {
            // Otherwise, if this link was marked active by localStorage or leftover, remove it
            // Blade already handles correct server-side active state, so we just cleanup mismatches
            link.classList.remove("active");
        }

        // Click handler
        link.addEventListener("click", function () {
            navLinks.forEach(l => l.classList.remove("active"));
            this.classList.add("active");
        });
    });
});
// dashbord click active end



document.addEventListener('click', function (e) {

    const badge = e.target.closest('.user-status-toggle');
    const vBadge = e.target.closest('.verification-status-toggle');

    if (badge) {
        const userId = badge.dataset.id;
        fetch(`/dashboard/users/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'active') {
                badge.className = 'badge px-3 py-2 user-status-toggle bg-success-subtle text-success';
                badge.innerHTML = '<i class="fas fa-unlock me-1"></i> Active';
                badge.title = 'Click to freeze';
                toastr.success(data.message);
            } else if (data.status === 'frozen') {
                badge.className = 'badge px-3 py-2 user-status-toggle bg-danger-subtle text-danger';
                badge.innerHTML = '<i class="fas fa-lock me-1"></i> Frozen';
                badge.title = 'Click to unfreeze';
                toastr.warning(data.message);
            } else {
                toastr.error(data.message || 'Action not allowed');
            }
        })
        .catch(() => toastr.error('Something went wrong'));
    }

    if (vBadge) {
        const userId = vBadge.dataset.id;
        const currentStatus = vBadge.innerText.trim().toLowerCase();
        const newStatus = currentStatus === 'approved' ? 'pending' : 'verified';

        fetch(`/dashboard/approvals/${userId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (newStatus === 'verified') {
                    vBadge.className = 'badge px-3 py-2 verification-status-toggle bg-success-subtle text-success';
                    vBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Approved';
                    vBadge.title = 'Click to mark as pending';
                } else {
                    vBadge.className = 'badge px-3 py-2 verification-status-toggle bg-warning-subtle text-warning';
                    vBadge.innerHTML = '<i class="fas fa-clock me-1"></i> Pending';
                    vBadge.title = 'Click to verify';
                }
                toastr.success(data.success);
            } else {
                toastr.error('Failed to update status');
            }
        })
        .catch(() => toastr.error('Something went wrong'));
    }
});

/**
 * Shared Header Flag UI Update
 */
function updateHeaderFlagUI(iso2, name) {
    if (!iso2) return;
    const code = iso2.toUpperCase();
    const flagUrl = `https://flagcdn.com/w40/${iso2.toLowerCase()}.png`;
    
    const headerFlag = document.getElementById('header-country-flag');
    const headerCode = document.getElementById('header-country-code');
    const headerCodeTop = document.getElementById('header-country-code-top');
    const headerContainer = document.getElementById('header-country-container');

    if (headerFlag) headerFlag.src = flagUrl;
    if (headerCode) headerCode.textContent = code;
    if (headerCodeTop) headerCodeTop.textContent = code;
    if (headerContainer) headerContainer.title = name;
}

/**
 * Global Header Sync with IP (when no phone input is present)
 */
function syncGlobalHeaderWithIP() {
    if (document.querySelectorAll(".intl-phone").length === 0) {
        fetch("https://ipapi.co/json")
            .then(res => res.json())
            .then(data => {
                updateHeaderFlagUI(data.country_code, data.country_name);
            })
            .catch(() => {
                // Fallback handled by PHP initial load
            });
    }
}

/**
 * Initialize intl-tel-input for all elements with class .intl-phone
 */
function initIntlPhone() {
    const phoneInputs = document.querySelectorAll(".intl-phone");
    phoneInputs.forEach(input => {
        // Prevent double initialization
        if (input.dataset.intlInitialized) return;
        
        const fullPhoneInput = document.querySelector(input.dataset.fullField || "#full_phone");
        
        const iti = window.intlTelInput(input, {
            initialCountry: "auto",
            geoIpLookup: function(success, failure) {
                fetch("https://ipapi.co/json")
                    .then(res => res.json())
                    .then(data => {
                        success(data.country_code);
                        updateHeaderFlagUI(data.country_code, data.country_name);
                    })
                    .catch(() => {
                        success("pk");
                        updateHeaderFlagUI("pk", "Pakistan");
                    });
            },
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
        });

        const updateFullPhone = () => {
            if (fullPhoneInput) {
                fullPhoneInput.value = iti.getNumber();
            }
        };

        const syncHeaderAndDropdown = () => {
            const countryData = iti.getSelectedCountryData();
            if (countryData && countryData.iso2) {
                updateHeaderFlagUI(countryData.iso2, countryData.name);

                // Sync with country dropdown if it exists
                const countrySelect = document.getElementById('country');
                if (countrySelect) {
                    const countryName = countryData.name.split(' (')[0];
                    for (let i = 0; i < countrySelect.options.length; i++) {
                        if (countrySelect.options[i].text === countryName || countrySelect.options[i].value === countryName) {
                            countrySelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
        };

        input.addEventListener('change', updateFullPhone);
        input.addEventListener('keyup', updateFullPhone);
        input.addEventListener('countrychange', () => {
            updateFullPhone();
            syncHeaderAndDropdown();
        });

        // Numeric only and digit limitation
        input.addEventListener('keypress', function(e) {
            if (e.which < 48 || e.which > 57) e.preventDefault();
            
            const placeholder = this.getAttribute('placeholder');
            if (placeholder) {
                const maxDigits = placeholder.replace(/[^0-9]/g, '').length;
                if (this.value.replace(/[^0-9]/g, '').length >= maxDigits) {
                    e.preventDefault();
                }
            }
        });

        // Store instance for later use
        input.iti = iti;
        input.dataset.intlInitialized = "true";

        // Initial sync if value exists or after load
        setTimeout(syncHeaderAndDropdown, 500);

        // Sync dropdown change back to iti
        const countrySelect = document.getElementById('country');
        if (countrySelect) {
            countrySelect.addEventListener('change', function() {
                const selectedCountryName = this.options[this.selectedIndex].text;
                const countryData = iti.getCountryData();
                const matchedCountry = countryData.find(c => c.name.includes(selectedCountryName) || selectedCountryName.includes(c.name));
                if (matchedCountry) {
                    iti.setCountry(matchedCountry.iso2);
                }
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initIntlPhone();
    syncGlobalHeaderWithIP();
});
