/**
 * Bus Pass Management System — Admin JavaScript
 * Handles: sidebar toggle, live clock, AJAX actions, DataTables,
 *          SweetAlert confirmations, document lightbox, notifications
 */

'use strict';

/* ── DOM Ready ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initLiveClock();
    initDataTables();
    initTooltips();
    loadPendingCount();
    loadNotifications();
    initFormEnhancements();
});

/* ── Sidebar toggle ─────────────────────────────────────── */
function initSidebar() {
    const sidebar     = document.getElementById('adminSidebar');
    const mainContent = document.getElementById('mainContent');
    const toggle      = document.getElementById('sidebarToggle');
    const overlay     = document.getElementById('sidebarOverlay');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            // Mobile: slide in/out
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        } else {
            // Desktop: collapse/expand
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });

    // Restore collapsed state on desktop
    if (window.innerWidth >= 992 && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }
}

/* ── Live clock ─────────────────────────────────────────── */
function initLiveClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;

    function tick() {
        const now = new Date();
        el.textContent = now.toLocaleString('en-IN', {
            weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
    tick();
    setInterval(tick, 1000);
}

/* ── DataTables init ────────────────────────────────────── */
function initDataTables() {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) return;
    const tables = document.querySelectorAll('.datatable');
    tables.forEach(table => {
        if ($.fn.DataTable.isDataTable(table)) return;
        $(table).DataTable({
            responsive: true,
            pageLength: 15,
            order: [[0, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search…',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_–_END_ of _TOTAL_ applications',
                emptyTable: 'No applications found.',
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next:     '<i class="bi bi-chevron-right"></i>'
                }
            },
            dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-3 align-items-center'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        });
    });
}

/* ── Bootstrap tooltips ─────────────────────────────────── */
function initTooltips() {
    if (typeof bootstrap === 'undefined') return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
}

/* ── Pending count badge in sidebar ─────────────────────── */
function loadPendingCount() {
    const badge = document.getElementById('pendingCountBadge');
    if (!badge) return;

    const base = getApiBase();
    fetch(base + 'api/validate_route.php?action=pending_count')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                badge.textContent = data.count > 0 ? data.count : '';
            }
        })
        .catch(err => {
            console.error('Pending count fetch failed:', err);
            badge.textContent = '';
        });
}

/* ── Notifications dropdown ─────────────────────────────── */
function loadNotifications() {
    const list  = document.getElementById('notifList');
    const badge = document.getElementById('notifBadge');
    if (!list || !badge) return;

    const base = getApiBase();
    fetch(base + 'api/validate_route.php?action=notifications')
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.notifications?.length) {
                list.innerHTML = '<li class="dropdown-item-text text-muted small px-3 py-2">No new notifications.</li>';
                badge.textContent = '0';
                return;
            }
            badge.textContent = data.notifications.length;
            list.innerHTML = data.notifications.map(n => `
                <li>
                    <a class="dropdown-item small py-2" href="${escHtml(n.url || '#')}">
                        <div class="fw-semibold">${escHtml(n.title)}</div>
                        <div class="text-muted">${escHtml(n.body)}</div>
                    </a>
                </li>
            `).join('<li><hr class="dropdown-divider my-0"></li>');
        })
        .catch(err => {
            console.error('Notifications fetch failed:', err);
            list.innerHTML = '<li class="dropdown-item-text text-muted small px-3 py-3">Unable to load notifications.</li>';
        });
}

/* ── Document Verification (AJAX) ───────────────────────── */

/**
 * Verify or reject a document.
 * @param {number} docId
 * @param {string} action  'verified' | 'rejected'
 * @param {number} appId
 */
window.verifyDocument = function(docId, action, appId) {
    const label    = action === 'verified' ? 'Verify' : 'Reject';
    const btnClass = action === 'verified' ? 'btn-success' : 'btn-danger';
    const notesEl  = document.getElementById('doc_notes_' + docId);
    const notes    = notesEl ? notesEl.value.trim() : '';

    Swal.fire({
        title: `${label} Document?`,
        text:  action === 'rejected'
            ? 'Please make sure you have entered rejection notes above.'
            : 'This will mark the document as verified.',
        icon:  action === 'verified' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: label,
        confirmButtonColor: action === 'verified' ? '#16a34a' : '#dc2626',
        cancelButtonText: 'Cancel',
    }).then(result => {
        if (!result.isConfirmed) return;

        const base = getApiBase();
        const form = new FormData();
        form.append('doc_id',        docId);
        form.append('action',        action);
        form.append('application_id', appId);
        form.append('notes',         notes);
        form.append('csrf_token',    getCsrfToken());

        fetch(base + 'api/verify_documents.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', data.message || 'Action failed.');
                }
            })
            .catch(() => showToast('error', 'Network error. Please try again.'));
    });
};

/* ── Route Validation (AJAX) ────────────────────────────── */
window.validateRoute = function(appId) {
    Swal.fire({
        title: 'Validate Route?',
        text:  'Confirm that the requested route and stops are valid for this application.',
        icon:  'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Validate',
        confirmButtonColor: '#2563eb',
    }).then(result => {
        if (!result.isConfirmed) return;

        const base = getApiBase();
        const form = new FormData();
        form.append('application_id', appId);
        form.append('csrf_token',     getCsrfToken());

        fetch(base + 'api/validate_route.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast('error', data.message || 'Validation failed.');
                }
            })
            .catch(err => {
                console.error('Route validation failed:', err);
                showToast('error', 'Network or server error during route validation.');
            });
    });
};

/* ── Approve / Reject Application (AJAX) ────────────────── */
window.updateApplicationStatus = function(appId, action) {
    const isApprove = action === 'approved';
    const title     = isApprove ? 'Approve Application?' : 'Reject Application?';
    const icon      = isApprove ? 'success' : 'error';
    const btnColor  = isApprove ? '#16a34a' : '#dc2626';

    Swal.fire({
        title,
        icon,
        html: isApprove
            ? `<p>This will approve application <strong>#${appId}</strong> and generate a bus pass.</p>
               <div class="mt-2">
                 <label class="form-label text-start d-block">Admin Remarks (optional)</label>
                 <textarea id="swal_remarks" class="form-control" rows="2" placeholder="Enter any remarks…"></textarea>
               </div>`
            : `<p>This will <strong class="text-danger">permanently reject</strong> application <strong>#${appId}</strong>.</p>
               <div class="mt-2">
                 <label class="form-label text-start d-block">Rejection Reason <span class="text-danger">*</span></label>
                 <textarea id="swal_remarks" class="form-control" rows="3" placeholder="Enter clear rejection reason…"></textarea>
               </div>`,
        showCancelButton: true,
        confirmButtonText: isApprove ? 'Approve & Issue Pass' : 'Reject Application',
        confirmButtonColor: btnColor,
        preConfirm: () => {
            const val = document.getElementById('swal_remarks')?.value.trim();
            if (!isApprove && !val) {
                Swal.showValidationMessage('Rejection reason is required.');
                return false;
            }
            return val;
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        const base = getApiBase();
        const form = new FormData();
        form.append('application_id', appId);
        form.append('action',         action);
        form.append('remarks',        result.value || '');
        form.append('csrf_token',     getCsrfToken());

        fetch(base + 'api/update_status.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: isApprove ? 'Approved!' : 'Rejected',
                        text:  data.message,
                        timer: 2000, showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    showToast('error', data.message || 'Action failed.');
                }
            })
            .catch(err => {
                console.error('Update status failed:', err);
                showToast('error', 'Network or server error during status update.');
            });
    });
};

/* ── Request Correction (AJAX) ──────────────────────────── */
window.submitCorrectionRequest = function(form) {
    const formData = new FormData(form);
    const fields   = [...form.querySelectorAll('input[name="fields_to_fix[]"]:checked')].map(cb => cb.value);

    if (fields.length === 0) {
        showToast('warning', 'Please select at least one field to correct.');
        return false;
    }

    if (!formData.get('correction_message')?.trim()) {
        showToast('warning', 'Please enter a correction message for the applicant.');
        return false;
    }

    Swal.fire({
        title: 'Send Correction Request?',
        text:  'The applicant will be notified to re-submit the selected items.',
        icon:  'warning',
        showCancelButton: true,
        confirmButtonText: 'Send Request',
        confirmButtonColor: '#ea580c',
    }).then(result => {
        if (!result.isConfirmed) return;

        const base = getApiBase();
        fetch(base + 'api/request_correction.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon:'success', title:'Sent!', text: data.message, timer:2000, showConfirmButton:false })
                        .then(() => window.history.back());
                } else {
                    showToast('error', data.message || 'Failed to send request.');
                }
            })
            .catch(err => {
                console.error('Send correction failed:', err);
                showToast('error', 'Network or server error during correction request.');
            });
    });

    return false; // prevent default form submit
};

/* ── Document Lightbox ──────────────────────────────────── */
window.openDocLightbox = function(src, label, mimeType) {
    const modal = document.getElementById('docLightbox');
    if (!modal) return;

    const body  = modal.querySelector('.modal-body');
    const title = modal.querySelector('.modal-title');
    if (title) title.textContent = label;

    if (mimeType && mimeType.includes('pdf')) {
        body.innerHTML = `<embed src="${escHtml(src)}" type="application/pdf" width="100%" height="600px">`;
    } else {
        body.innerHTML = `<img src="${escHtml(src)}" class="img-fluid w-100" alt="${escHtml(label)}">`;
    }

    new bootstrap.Modal(modal).show();
};

/* ── Form Enhancements ──────────────────────────────────── */
function initFormEnhancements() {
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Correction checklist item click
    document.querySelectorAll('.correction-field-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.type === 'checkbox') return;
            const cb = item.querySelector('input[type="checkbox"]');
            if (cb) {
                cb.checked = !cb.checked;
                item.classList.toggle('selected', cb.checked);
            }
        });
        const cb = item.querySelector('input[type="checkbox"]');
        if (cb) cb.addEventListener('change', () => item.classList.toggle('selected', cb.checked));
    });
}

/* ── Utility: Toast notification ────────────────────────── */
function showToast(type, message) {
    const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: iconMap[type] || 'info',
        title: message,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });
}

/* ── Utility: Get API base URL ──────────────────────────── */
function getApiBase() {
    const path = window.location.pathname;
    return path.includes('/admin/') ? '../' : './';
}

/* ── Utility: Get CSRF token ────────────────────────────── */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

/* ── Utility: Escape HTML ────────────────────────────────── */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Chart.js helper: Status Doughnut ────────────────────── */
window.renderStatusChart = function(canvasId, labels, values, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, font: { family: 'Inter', size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} (${((ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0))*100).toFixed(1)}%)`
                    }
                }
            },
            animation: { animateRotate: true, duration: 700 }
        }
    });
};
