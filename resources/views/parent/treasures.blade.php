@extends('layouts.parent')

@section('title', 'Manage Treasures')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>Manage Treasures</h2>
        <button class="btn btn-primary" onclick="openCreateModal()">
            + Create New Treasure
        </button>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('treasures')">💎 Treasures</button>
        <button class="tab" onclick="switchTab('purchases')">🛒 Purchase History</button>
    </div>

    <div id="treasures-tab" class="tab-content active">
        <div id="treasures-container" class="loading">
            Loading treasures...
        </div>
    </div>

    <div id="purchases-tab" class="tab-content">
        <div id="purchases-container" class="loading">
            Loading purchase history...
        </div>
    </div>
</div>

{{-- Create/Edit Treasure Modal --}}
<div id="treasureModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create New Treasure</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form id="treasureForm" onsubmit="saveTreasure(event)">
            <input type="hidden" id="treasureId">

            <div class="form-group">
                <label for="title">Treasure Title *</label>
                <input type="text" id="title" name="title" required>
                <div class="help-text">Give your treasure a fun, appealing name</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
                <div class="help-text">Optional details about what this reward is</div>
            </div>

            <div class="form-group">
                <label for="gold_cost">Gold Cost *</label>
                <input type="number" id="gold_cost" name="gold_cost" min="1" max="10000" required>
                <div class="help-text">How much gold this costs (1-10000)</div>
            </div>

            <div class="form-group">
                <label for="is_available">Availability</label>
                <select id="is_available" name="is_available">
                    <option value="1">Available (visible in shop)</option>
                    <option value="0">Unavailable (hidden from shop)</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Treasure</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let treasures = [];
let purchases = [];
let editingTreasureId = null;
let currentTab = 'treasures';

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
    loadTreasures();
    loadPurchases();
});

function switchTab(tab) {
    currentTab = tab;

    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
    document.getElementById(`${tab}-tab`).classList.add('active');
}

async function loadTreasures() {
    try {
        const result = await api.parent.getTreasures();
        treasures = result.treasures;
        renderTreasures();
    } catch (error) {
        notify.error('Failed to load treasures: ' + error.message);
        document.getElementById('treasures-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load treasures</p></div>';
    }
}

async function loadPurchases() {
    try {
        const result = await api.parent.getPurchases();
        purchases = result.purchases;
        renderPurchases();
    } catch (error) {
        console.error('Failed to load purchases:', error);
        document.getElementById('purchases-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load purchase history</p></div>';
    }
}

function renderTreasures() {
    const container = document.getElementById('treasures-container');

    if (treasures.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">💎</div>
                <p>No treasures yet. Create your first treasure to get started!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="treasures-grid">' +
        treasures.map(treasure => createTreasureCard(treasure)).join('') +
        '</div>';
}

function createTreasureCard(treasure) {
    return `
        <div class="treasure-card">
            <div class="treasure-header">
                <h3 class="treasure-title">${escapeHtml(treasure.title)}</h3>
                ${treasure.is_available
                    ? '<span class="badge badge-available">Available</span>'
                    : '<span class="badge badge-unavailable">Hidden</span>'}
            </div>
            ${treasure.description ? `<p class="treasure-description">${escapeHtml(treasure.description)}</p>` : '<p class="treasure-description" style="opacity: 0.5;">No description</p>'}
            <div class="treasure-cost">💰 ${treasure.gold_cost} Gold</div>
            <div class="treasure-actions">
                <button class="btn btn-secondary btn-small" onclick="editTreasure(${treasure.id})">
                    ✏️ Edit
                </button>
                <button class="btn btn-secondary btn-small" onclick="toggleTreasure(${treasure.id})">
                    ${treasure.is_available ? '👁️‍🗨️ Hide' : '👁️ Show'}
                </button>
                <button class="btn btn-danger btn-small" onclick="deleteTreasure(${treasure.id})">
                    🗑️ Delete
                </button>
            </div>
        </div>
    `;
}

function renderPurchases() {
    const container = document.getElementById('purchases-container');

    if (purchases.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <p>No purchases yet. Children will see purchased items here.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="purchases-list">' +
        purchases.map(purchase => createPurchaseCard(purchase)).join('') +
        '</div>';
}

function createPurchaseCard(purchase) {
    const date = new Date(purchase.purchased_at || purchase.created_at);
    const dateStr = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    return `
        <div class="purchase-card">
            <div class="purchase-info">
                <div class="purchase-child">
                    <img src="${purchase.child?.avatar_image ? '/Assets/Profile/' + purchase.child.avatar_image : '/Assets/Profile/princess_3tr.png'}"
                         alt="${escapeHtml(purchase.child?.name || 'Child')}"
                         class="child-avatar-small">
                    <span class="purchase-child-name">${escapeHtml(purchase.child?.name || 'Unknown Child')}</span>
                </div>
                <div class="purchase-treasure">${escapeHtml(purchase.treasure?.title || 'Unknown Treasure')}</div>
                <div class="purchase-date">${dateStr}</div>
            </div>
            <div class="purchase-cost">-${purchase.gold_spent} 💰</div>
        </div>
    `;
}

function openCreateModal() {
    editingTreasureId = null;
    document.getElementById('modalTitle').textContent = 'Create New Treasure';
    document.getElementById('treasureForm').reset();
    document.getElementById('treasureId').value = '';
    document.getElementById('treasureModal').classList.add('show');
}

function editTreasure(id) {
    const treasure = treasures.find(t => t.id === id);
    if (!treasure) return;

    editingTreasureId = id;
    document.getElementById('modalTitle').textContent = 'Edit Treasure';
    document.getElementById('treasureId').value = treasure.id;
    document.getElementById('title').value = treasure.title;
    document.getElementById('description').value = treasure.description || '';
    document.getElementById('gold_cost').value = treasure.gold_cost;
    document.getElementById('is_available').value = treasure.is_available ? '1' : '0';
    document.getElementById('treasureModal').classList.add('show');
}

function closeModal() {
    document.getElementById('treasureModal').classList.remove('show');
    editingTreasureId = null;
}

async function saveTreasure(event) {
    event.preventDefault();

    const formData = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        gold_cost: parseInt(document.getElementById('gold_cost').value),
        is_available: document.getElementById('is_available').value === '1'
    };

    try {
        if (editingTreasureId) {
            await api.parent.updateTreasure(editingTreasureId, formData);
            notify.success('Treasure updated successfully!');
        } else {
            await api.parent.createTreasure(formData);
            notify.success('Treasure created successfully!');
        }

        closeModal();
        await loadTreasures();
    } catch (error) {
        notify.error('Failed to save treasure: ' + error.message);
    }
}

async function toggleTreasure(id) {
    try {
        await api.parent.toggleTreasure(id);
        const treasure = treasures.find(t => t.id === id);
        notify.success(`Treasure ${treasure.is_available ? 'hidden' : 'shown'}!`);
        await loadTreasures();
    } catch (error) {
        notify.error('Failed to toggle treasure: ' + error.message);
    }
}

async function deleteTreasure(id) {
    const treasure = treasures.find(t => t.id === id);
    if (!confirm(`Are you sure you want to delete "${treasure.title}"? This cannot be undone.`)) {
        return;
    }

    try {
        await api.parent.deleteTreasure(id);
        notify.success('Treasure deleted successfully!');
        await loadTreasures();
    } catch (error) {
        notify.error('Failed to delete treasure: ' + error.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.getElementById('treasureModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endpush
