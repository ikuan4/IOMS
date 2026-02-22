<?php $__env->startSection('title', 'Tickets'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <?php $viewMode = $viewMode ?? 'all'; ?>
            <?php if($viewMode === 'my'): ?>
                <h2>PENDING TICKETS</h2>
                <p class="muted">Tickets assigned to you. Filter by pending, forwarded, resolved, or closed.</p>
            <?php else: ?>
                <h2>TICKET MANAGEMENT MODULE</h2>
                <p class="muted">Manage tickets, assignments, and status.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $indexUrl = $viewMode === 'my' ? route('tickets.pending') : route('tickets.index');

        $cards = $viewMode === 'my'
            ? [
                'pending' => [ 'label' => 'Pending Tickets', 'count' => $statusCounts['pending'] ?? 0, ],
                'forwarded' => [ 'label' => 'Forwarded Tickets', 'count' => $statusCounts['forwarded'] ?? 0, ],
                'resolved' => [ 'label' => 'Resolved Tickets', 'count' => $statusCounts['resolved'] ?? 0, ],
                'closed' => [ 'label' => 'Closed Tickets', 'count' => $statusCounts['closed'] ?? 0, ],
            ]
            : [
                'all' => [ 'label' => 'All Tickets', 'count' => $statusCounts['all'] ?? 0, ],
                'pending' => [ 'label' => 'Pending Tickets', 'count' => $statusCounts['pending'] ?? 0, ],
                'open' => [ 'label' => 'Open', 'count' => $statusCounts['open'] ?? 0, ],
                'in_progress' => [ 'label' => 'In Progress', 'count' => $statusCounts['in_progress'] ?? 0, ],
                'resolved' => [ 'label' => 'Resolved', 'count' => $statusCounts['resolved'] ?? 0, ],
                'closed' => [ 'label' => 'Closed', 'count' => $statusCounts['closed'] ?? 0, ],
                'deleted' => [ 'label' => 'Deleted', 'count' => $statusCounts['deleted'] ?? 0, ],
            ];
    ?>

    <div style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                ?>

                <?php
                    $qs = http_build_query($params);
                    $href = $indexUrl . ($qs !== '' ? ('?' . $qs) : '');
                ?>
                <a href="<?php echo e($href); ?>" style="text-decoration:none;color:inherit;">
                    <div class="card" style="padding:16px 18px;border-radius:12px;border:2px solid <?php echo e($isActiveCard ? '#22c55e' : '#e5e7eb'); ?>;box-shadow:<?php echo e($isActiveCard ? '0 0 0 1px rgba(34,197,94,0.15)' : 'none'); ?>;display:flex;flex-direction:column;justify-content:space-between;height:100%;">
                        <div style="font-size:14px; opacity:0.8;"><?php echo e($card['label']); ?></div>
                        <div style="margin-top:8px; font-size:24px; font-weight:700;"><?php echo e($card['count']); ?></div>
                        <?php if($isActiveCard): ?>
                            <div style="margin-top:8px; font-size:12px; color:#16a34a;">Currently applied to table ↓</div>
                        <?php else: ?>
                            <div style="margin-top:8px; font-size:12px; opacity:0.6;">Click to filter table</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px; flex-wrap:wrap;">
        <form method="GET" action="<?php echo e($indexUrl); ?>" id="ticketSearchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="<?php echo e($status ?? 'all'); ?>">
            <input type="hidden" name="per_page" id="searchPerPage" value="<?php echo e(request()->query('per_page', 10)); ?>">
            <input type="text" name="search" id="ticketSearchInput" value="<?php echo e($search); ?>" placeholder="Search by subject, type, or assignee..." oninput="debouncedTicketSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        <?php if($viewMode !== 'my' && (auth()->user()->isSuperAdmin() || auth()->user()->can('create', \App\Models\Ticket::class))): ?>
            <a href="<?php echo e(route('tickets.create')); ?>" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                <span data-feather="plus"></span>
                New Ticket
            </a>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:12px;overflow:hidden;">
        <div id="ticketsTableWrapper">
            <?php echo $__env->make('tickets._tickets_table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <script>
        let __ticketSearchTimer = null;
        function debouncedTicketSearch() {
            clearTimeout(__ticketSearchTimer);
            __ticketSearchTimer = setTimeout(() => { ajaxFetchTickets(1); }, 300);
        }

        function ajaxFetchTickets(page = 1) {
            const form = document.getElementById('ticketSearchForm');
            const params = new URLSearchParams(new FormData(form));
            const perPageSelect = document.getElementById('per_page');
            if (perPageSelect) params.set('per_page', perPageSelect.value);
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('ticketsTableWrapper').innerHTML = html;
                    bindTicketPaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindTicketPaginationLinks(){
            const wrapper = document.getElementById('ticketsTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchTickets(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
        }

        window.addEventListener('load', function(){
            bindTicketPaginationLinks();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/tickets/index.blade.php ENDPATH**/ ?>