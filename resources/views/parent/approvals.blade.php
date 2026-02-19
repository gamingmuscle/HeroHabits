@extends('layouts.parent')

@section('title', 'Quest Approvals')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>Quest Approvals</h2>
        <div class="bulk-actions">
            <span id="selectedCount" style="color: #666; font-size: 0.9rem;">0 selected</span>
            <button class="btn btn-success" onclick="bulkAccept()" id="bulkAcceptBtn" disabled>
                ✓ Accept Selected
            </button>
            <button class="btn btn-danger" onclick="bulkDeny()" id="bulkDenyBtn" disabled>
                ✕ Deny Selected
            </button>
        </div>
    </div>

    <div id="stats-container"></div>
    <div id="approvals-container" class="loading">
        Loading pending approvals...
    </div>
</div>
@endsection

@push('scripts')
<script>
let approvals = [];
let selectedIds = new Set();

// Load approvals on page load
document.addEventListener('DOMContentLoaded', loadApprovals);

async function loadApprovals() {
    try {
        const result = await api.parent.getApprovals();
        approvals = result.pending;
        renderStats();
        renderApprovals();
    } catch (error) {
        notify.error('Failed to load approvals: ' + error.message);
        document.getElementById('approvals-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load approvals</p></div>';
    }
}

function renderStats() {
    const container = document.getElementById('stats-container');

    if (approvals.length === 0) {
        container.innerHTML = '';
        return;
    }

    const totalGold = approvals.reduce((sum, a) => sum + a.gold_earned, 0);
    const uniqueChildren = new Set(approvals.map(a => a.child_id)).size;

    container.innerHTML = `
        <div class="stats-banner">
            <div class="stat-item">
                <div class="stat-number">${approvals.length}</div>
                <div class="stat-label">Pending Approvals</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">${totalGold}</div>
                <div class="stat-label">Total Gold Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">${uniqueChildren}</div>
                <div class="stat-label">Children Waiting</div>
            </div>
        </div>
    `;
}

function renderApprovals() {
    const container = document.getElementById('approvals-container');

    if (approvals.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <h3>All caught up!</h3>
                <p>No pending quest approvals at this time.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="approvals-grid">' +
        approvals.map(approval => createApprovalCard(approval)).join('') +
        '</div>';
}

function createApprovalCard(approval) {
    const isSelected = selectedIds.has(approval.id);
    const date = new Date(approval.completion_date);
    const dateStr = date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });

    return `
        <div class="approval-card">
            <div class="approval-checkbox">
                <input type="checkbox"
                       ${isSelected ? 'checked' : ''}
                       onchange="toggleSelection(${approval.id})"
                       id="checkbox-${approval.id}">
            </div>
            <div class="approval-content">
                <div class="approval-header">
                    <div class="approval-child">
                        <img src="${approval.child?.avatar_image ? '/Assets/Profile/' + approval.child.avatar_image : '/Assets/Profile/princess_3tr.png'}"
                             alt="${escapeHtml(approval.child?.name || 'Child')}"
                             class="child-avatar">
                        <span class="child-name">${escapeHtml(approval.child?.name || 'Unknown Child')}</span>
                    </div>
                    <div class="approval-date">${dateStr}</div>
                </div>
                <h3 class="quest-title">${escapeHtml(approval.quest?.title || 'Quest')}</h3>
                ${approval.quest?.description ? `<p class="quest-description">${escapeHtml(approval.quest.description)}</p>` : ''}
                <div class="gold-reward">⭐ ${approval.gold_earned} Gold</div>
                <div class="approval-actions">
                    <button class="btn btn-success btn-small" onclick="acceptApproval(${approval.id})">
                        ✓ Accept
                    </button>
                    <button class="btn btn-danger btn-small" onclick="denyApproval(${approval.id})">
                        ✕ Deny
                    </button>
                </div>
            </div>
        </div>
    `;
}

function toggleSelection(id) {
    if (selectedIds.has(id)) {
        selectedIds.delete(id);
    } else {
        selectedIds.add(id);
    }
    updateBulkActions();
}

function updateBulkActions() {
    const count = selectedIds.size;
    document.getElementById('selectedCount').textContent = `${count} selected`;
    document.getElementById('bulkAcceptBtn').disabled = count === 0;
    document.getElementById('bulkDenyBtn').disabled = count === 0;
}

async function acceptApproval(id) {
    try {
        const result = await api.parent.acceptApproval(id);
        notify.success(result.message || 'Quest approved!');
        await loadApprovals();
        selectedIds.clear();
        updateBulkActions();
    } catch (error) {
        notify.error('Failed to accept: ' + error.message);
    }
}

async function denyApproval(id) {
    if (!confirm('Are you sure you want to deny this quest completion?')) {
        return;
    }

    try {
        const result = await api.parent.denyApproval(id);
        notify.success(result.message || 'Quest denied');
        await loadApprovals();
        selectedIds.clear();
        updateBulkActions();
    } catch (error) {
        notify.error('Failed to deny: ' + error.message);
    }
}

async function bulkAccept() {
    if (selectedIds.size === 0) return;

    const count = selectedIds.size;
    if (!confirm(`Accept ${count} quest completion${count > 1 ? 's' : ''}?`)) {
        return;
    }

    try {
        const ids = Array.from(selectedIds);
        await api.parent.bulkAccept(ids);
        notify.success(`${count} quest${count > 1 ? 's' : ''} approved!`);
        await loadApprovals();
        selectedIds.clear();
        updateBulkActions();
    } catch (error) {
        notify.error('Failed to bulk accept: ' + error.message);
    }
}

async function bulkDeny() {
    if (selectedIds.size === 0) return;

    const count = selectedIds.size;
    if (!confirm(`Deny ${count} quest completion${count > 1 ? 's' : ''}? This cannot be undone.`)) {
        return;
    }

    try {
        const ids = Array.from(selectedIds);
        await api.parent.bulkDeny(ids);
        notify.success(`${count} quest${count > 1 ? 's' : ''} denied`);
        await loadApprovals();
        selectedIds.clear();
        updateBulkActions();
    } catch (error) {
        notify.error('Failed to bulk deny: ' + error.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
