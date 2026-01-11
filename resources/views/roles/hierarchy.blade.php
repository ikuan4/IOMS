@extends('layouts.dashboard')

@section('title', 'Roles by Branch')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Roles Hierarchy</h2>
            <p class="muted">Drag roles to reorder or edit priority numbers directly.</p>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
        <div class="card" style="padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div>
                    <h5 style="margin:0;font-size:16px;font-weight:600;">Branch</h5>
                    <p class="muted" style="margin:4px 0 0 0;">Choose branch to filter roles.</p>
                </div>

                <div style="min-width:240px;">
                    @if($isDeveloper ?? false)
                        <form method="GET" action="{{ route('roles.hierarchy', $role->id ?? 0) }}" id="branchForm">
                            <select name="branch_id" onchange="this.form.submit()" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
                                <option value="">-- Select Branch --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ (int)($selectedBranchId ?? 0) === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <div style="padding:10px 12px;border-radius:8px;border:1px solid #e6edf3;background:#f8fafc;font-size:14px;">{{ optional(\App\Models\Branch::find($selectedBranchId))->name ?? 'My Branch' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card" style="padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div>
                    <h5 style="margin:0;font-size:16px;font-weight:600;">Roles</h5>
                    <p class="muted" style="margin:4px 0 0 0;font-size:13px;">Lower priority number = Higher hierarchy. Edit priority numbers and click Save.</p>
                </div>
                <button id="saveHierarchyBtn" class="btn" style="background:var(--accent);color:white;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:none;">
                    Save Changes
                </button>
            </div>

            @if($rolesForView && $rolesForView->count())
                <div id="rolesContainer" class="roles-hierarchy-container">
                    @foreach($rolesForView as $index => $r)
                        <div class="role-row" data-role-id="{{ $r->id }}" data-priority="{{ $r->priority ?? 1 }}">
                            <div class="role-content">
                                <div class="role-name">{{ $r->name }}</div>
                                <div class="role-description">{{ $r->description ?? 'No description' }}</div>
                            </div>
                            <div class="role-priority">
                                <input type="number"
                                       class="priority-input"
                                       value="{{ $r->priority ?? 1 }}"
                                       min="1"
                                       max="100"
                                       data-role-id="{{ $r->id }}"
                                       title="1 = Highest, 100 = Lowest">
                            </div>
                        </div>
                    @endforeach
                </div>

                <form id="updatePriorityForm" method="POST" action="{{ route('roles.hierarchy.update') }}" style="display:none;">
                    @csrf
                    <input type="hidden" name="priorities" id="prioritiesInput">
                </form>
            @else
                <p class="muted">No roles available for the selected branch or you are not part of any roles.</p>
            @endif
        </div>
    </div>

    <style>
        .roles-hierarchy-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0;
            background: transparent;
            border-radius: 0;
        }

        .role-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--card);
            border: 2px solid rgba(0, 0, 0, 0.06);
            border-radius: 8px;
            transition: all 0.2s;
        }

        [data-theme="dark"] .role-row {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .role-row:hover {
            border-color: var(--accent);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .role-content {
            flex: 1;
            text-align: left;
        }

        .role-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .role-description {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.4;
        }

        .role-priority {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .priority-input {
            width: 80px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: var(--bg);
            color: var(--text);
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
        }

        [data-theme="dark"] .priority-input {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .priority-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let hasChanges = false;

            const container = document.getElementById('rolesContainer');
            const saveBtn = document.getElementById('saveHierarchyBtn');

            if (!container) return;

            // Sort roles by priority on page load (top to bottom)
            sortRolesByPriority();

            // Add input event listeners to priority inputs
            const priorityInputs = container.querySelectorAll('.priority-input');
            priorityInputs.forEach(input => {
                input.addEventListener('change', handlePriorityChange);
            });

            // Save button click
            if (saveBtn) {
                saveBtn.addEventListener('click', savePriorities);
            }

            function handlePriorityChange(e) {
                const input = e.target;
                let newPriority = parseInt(input.value) || 1;

                // Clamp value between 1 and 100
                if (newPriority < 1) {
                    newPriority = 1;
                    input.value = 1;
                } else if (newPriority > 100) {
                    newPriority = 100;
                    input.value = 100;
                }

                // Update data attribute
                const row = input.closest('.role-row');
                row.setAttribute('data-priority', newPriority);

                // Only mark as changed, don't sort immediately
                markAsChanged();
            }

            function sortRolesByPriority() {
                const rows = Array.from(container.querySelectorAll('.role-row'));

                rows.sort((a, b) => {
                    const priorityA = parseInt(a.querySelector('.priority-input').value) || 1;
                    const priorityB = parseInt(b.querySelector('.priority-input').value) || 1;

                    // Sort ascending (lower number = higher priority = top)
                    if (priorityA !== priorityB) {
                        return priorityA - priorityB;
                    }
                    // If priorities are equal, maintain relative order by role ID
                    const idA = parseInt(a.getAttribute('data-role-id'));
                    const idB = parseInt(b.getAttribute('data-role-id'));
                    return idA - idB;
                });

                // Reorder in DOM
                rows.forEach(row => container.appendChild(row));
            }

            function markAsChanged() {
                hasChanges = true;
                if (saveBtn) {
                    saveBtn.style.display = 'block';
                }
            }

            function savePriorities() {
                // First, sort by current priorities
                sortRolesByPriority();

                const rows = Array.from(container.querySelectorAll('.role-row'));
                const form = document.getElementById('updatePriorityForm');

                if (!form) return;

                // Remove any existing priority inputs
                const existingInputs = form.querySelectorAll('input[name^="priorities"]');
                existingInputs.forEach(input => {
                    if (input.id !== 'prioritiesInput') {
                        input.remove();
                    }
                });

                // Add priority inputs as separate form fields
                rows.forEach(row => {
                    const roleId = row.getAttribute('data-role-id');
                    const priority = row.querySelector('.priority-input').value;

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `priorities[${roleId}]`;
                    input.value = priority;
                    form.appendChild(input);
                });

                // Submit the form
                form.submit();
            }
        });
    </script>
@endsection
