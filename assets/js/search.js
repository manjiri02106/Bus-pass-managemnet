/**
 * AJAX Search and Filters for Admin Pass Management
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('admin-search-form');
    if (searchForm) {
        const searchInput = document.getElementById('search_query');
        const statusFilter = document.getElementById('filter_status');
        const categoryFilter = document.getElementById('filter_category');
        const routeFilter = document.getElementById('filter_route');
        const tableBody = document.getElementById('passes-table-body');
        const paginationContainer = document.getElementById('pagination-container');

        let debounceTimer;
        let currentPage = 1;

        const fetchPasses = () => {
            const query = searchInput ? encodeURIComponent(searchInput.value) : '';
            const status = statusFilter ? encodeURIComponent(statusFilter.value) : '';
            const category = categoryFilter ? encodeURIComponent(categoryFilter.value) : '';
            const route = routeFilter ? encodeURIComponent(routeFilter.value) : '';

            // Loading state indicator
            if (tableBody) {
                tableBody.style.opacity = '0.5';
            }

            const url = `passes.php?ajax=1&search=${query}&status=${status}&category=${category}&route=${route}&page=${currentPage}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (tableBody) {
                            tableBody.innerHTML = data.html;
                            tableBody.style.opacity = '1';
                        }
                        if (paginationContainer) {
                            paginationContainer.innerHTML = data.pagination;
                        }
                        
                        // Re-bind pagination clicks
                        bindPaginationEvents();
                    } else {
                        showToast(data.message || 'Failed to fetch passes.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showToast('An error occurred while searching passes.', 'error');
                    if (tableBody) tableBody.style.opacity = '1';
                });
        };

        const bindPaginationEvents = () => {
            const pageButtons = document.querySelectorAll('.page-btn');
            pageButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(btn.getAttribute('data-page'));
                    if (page && page !== currentPage) {
                        currentPage = page;
                        fetchPasses();
                    }
                });
            });
        };

        // Input triggers
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentPage = 1; // Reset to page 1 on new search
                    fetchPasses();
                }, 300);
            });
        }

        [statusFilter, categoryFilter, routeFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => {
                    currentPage = 1; // Reset to page 1
                    fetchPasses();
                });
            }
        });

        // Initialize first load
        fetchPasses();
    }
});
