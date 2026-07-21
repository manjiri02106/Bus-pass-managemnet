/**
 * Global Javascript Helpers for Bus Pass Management System
 */

// Toast notifications controller
function showToast(message, type = 'info') {
    // Create toast container if not exists
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Choose icon based on type
    let iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check-circle';
    if (type === 'error') iconClass = 'fa-exclamation-circle';
    if (type === 'warning') iconClass = 'fa-exclamation-triangle';

    toast.innerHTML = `
        <i class="fas ${iconClass}"></i>
        <div class="toast-message">${message}</div>
    `;

    container.appendChild(toast);

    // Trigger transition reflow
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Remove toast after 4s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4000);
}

// Modal Toggle Utility
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
    }
}

// Preview Uploaded Image
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
                preview.style.display = 'block';
            } else {
                preview.style.backgroundImage = `url(${e.target.result})`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Stepper Form Logic (apply.php)
document.addEventListener('DOMContentLoaded', () => {
    const multiStepForm = document.querySelector('.multi-step-form');
    if (multiStepForm) {
        const steps = Array.from(multiStepForm.querySelectorAll('.step-content'));
        const indicators = Array.from(document.querySelectorAll('.step-indicator'));
        const nextBtns = Array.from(multiStepForm.querySelectorAll('.btn-next'));
        const prevBtns = Array.from(multiStepForm.querySelectorAll('.btn-prev'));
        
        let currentStep = 0;

        const updateFormSteps = () => {
            steps.forEach((step, idx) => {
                if (idx === currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            indicators.forEach((ind, idx) => {
                if (idx === currentStep) {
                    ind.classList.add('active');
                    ind.classList.remove('completed');
                } else if (idx < currentStep) {
                    ind.classList.add('completed');
                    ind.classList.remove('active');
                } else {
                    ind.classList.remove('active', 'completed');
                }
            });
        };

        // Validate fields in current step before moving forward
        const validateStep = (stepIdx) => {
            const currentStepEl = steps[stepIdx];
            const requiredFields = currentStepEl.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    field.addEventListener('input', function removeInvalid() {
                        field.classList.remove('is-invalid');
                        field.removeEventListener('input', removeInvalid);
                    });
                }
            });

            if (!isValid) {
                showToast('Please fill out all required fields.', 'error');
            }
            return isValid;
        };

        nextBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (validateStep(currentStep)) {
                    // Custom calculation logic hook before payment step
                    if (currentStep === 1) {
                        calculatePassPrice();
                    }
                    currentStep++;
                    updateFormSteps();
                }
            });
        });

        prevBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                currentStep--;
                updateFormSteps();
            });
        });

        // Dynamic price calculation helper function
        const calculatePassPrice = () => {
            const routeSelect = document.getElementById('route_id');
            const categorySelect = document.getElementById('category_id');
            const durationSelect = document.getElementById('duration');
            
            if (!routeSelect || !categorySelect || !durationSelect) return;

            const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];
            const selectedCategoryOption = categorySelect.options[categorySelect.selectedIndex];
            
            const basePrice = parseFloat(selectedRouteOption.getAttribute('data-price') || 0);
            const discountPct = parseFloat(selectedCategoryOption.getAttribute('data-discount') || 0);
            const months = parseInt(durationSelect.value || 1);

            // Calculation formula: (BasePrice * Months) * ((100 - DiscountPct) / 100)
            const subtotal = basePrice * months;
            const discountAmount = subtotal * (discountPct / 100);
            const finalPrice = subtotal - discountAmount;

            // Display in UI summary
            const priceSummary = document.getElementById('calc_base_price');
            const discountSummary = document.getElementById('calc_discount');
            const finalSummary = document.getElementById('calc_final_price');
            const hiddenPriceInput = document.getElementById('price_input');

            if (priceSummary) priceSummary.innerText = `$${subtotal.toFixed(2)}`;
            if (discountSummary) discountSummary.innerText = `-$${discountAmount.toFixed(2)} (${discountPct}%)`;
            if (finalSummary) finalSummary.innerText = `$${finalPrice.toFixed(2)}`;
            if (hiddenPriceInput) hiddenPriceInput.value = finalPrice.toFixed(2);
        };
    }
});
