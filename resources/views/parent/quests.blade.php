@extends('layouts.parent')

@section('title', 'Manage Quests')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>Manage Quests</h2>
        <button class="btn btn-primary" onclick="openCreateModal()">
            + Create New Quest
        </button>
    </div>

    <div id="quests-container" class="loading">
        Loading quests...
    </div>
</div>

{{-- Create/Edit Quest Modal --}}
<div id="questModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create New Quest</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form id="questForm" onsubmit="saveQuest(event)">
            <input type="hidden" id="questId">

            <div class="form-group">
                <label for="title">Quest Title *</label>
                <input type="text" id="title" name="title" required>
                <div class="help-text">Give your quest a clear, motivating name</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
                <div class="help-text">Optional details about what needs to be done</div>
            </div>

            <div class="form-group">
                <label for="gold_reward">Gold Reward *</label>
                <input type="number" id="gold_reward" name="gold_reward" min="1" max="1000" required>
                <div class="help-text">How much gold to award (1-1000)</div>
            </div>

            <div class="form-group">
                <label>Character Traits</label>
                <div id="traits-container" class="traits-checkboxes">
                    <div style="color: #999; font-style: italic;">Loading traits...</div>
                </div>
                <div class="help-text">Select traits this quest reinforces (XP will be split among selected traits)</div>
            </div>

            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1">Active (visible to children)</option>
                    <option value="0">Inactive (hidden from children)</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Quest</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let quests = [];
let traits = [];
let editingQuestId = null;

// Load quests and traits on page load
document.addEventListener('DOMContentLoaded', async () => {
    await loadTraits();
    await loadQuests();
});

async function loadTraits() {
    try {
        const result = await api.parent.getTraits();
        traits = result.traits || [];
        renderTraits();
    } catch (error) {
        console.error('Failed to load traits:', error);
        document.getElementById('traits-container').innerHTML =
            '<div style="color: #999; font-style: italic;">Failed to load traits</div>';
    }
}

function renderTraits() {
    const container = document.getElementById('traits-container');

    if (traits.length === 0) {
        container.innerHTML = '<div style="color: #999; font-style: italic;">No traits available</div>';
        return;
    }

    container.innerHTML = traits.map(trait => `
        <div class="trait-checkbox">
            <input type="checkbox" id="trait_${trait.id}" name="trait_ids[]" value="${trait.id}">
            <label for="trait_${trait.id}" class="trait-checkbox-label">
                <span class="trait-icon">${trait.icon || '⭐'}</span>
                <div class="trait-info">
                    <span class="trait-name">${escapeHtml(trait.name)}</span>
                    <span class="trait-description">${escapeHtml(trait.description)}</span>
                </div>
            </label>
        </div>
    `).join('');
}

async function loadQuests() {
    try {
        const result = await api.parent.getQuests();
        quests = result.quests;
        renderQuests();
    } catch (error) {
        notify.error('Failed to load quests: ' + error.message);
        document.getElementById('quests-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load quests</p></div>';
    }
}

function renderQuests() {
    const container = document.getElementById('quests-container');

    if (quests.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🎯</div>
                <p>No quests yet. Create your first quest to get started!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="quests-grid">' +
        quests.map(quest => createQuestCard(quest)).join('') +
        '</div>';
}

function createQuestCard(quest) {
    const traitTags = quest.traits && quest.traits.length > 0
        ? `<div class="quest-traits">
            ${quest.traits.map(trait =>
                `<span class="trait-tag">${trait.icon || '⭐'} ${escapeHtml(trait.name)}</span>`
            ).join('')}
           </div>`
        : '';

    return `
        <div class="quest-card">
            <div class="quest-header">
                <h3 class="quest-title">${escapeHtml(quest.title)}</h3>
                <div class="quest-badges">
                    ${quest.is_active
                        ? '<span class="badge badge-active">Active</span>'
                        : '<span class="badge badge-inactive">Inactive</span>'}
                    ${quest.pending_count > 0
                        ? `<span class="badge badge-pending">${quest.pending_count} Pending</span>`
                        : ''}
                </div>
            </div>
            ${quest.description ? `<p class="quest-description">${escapeHtml(quest.description)}</p>` : ''}
            ${traitTags}
            <div class="quest-reward">⭐ ${quest.gold_reward} Gold</div>
            <div class="quest-actions">
                <button class="btn btn-secondary btn-small" onclick="editQuest(${quest.id})">
                    ✏️ Edit
                </button>
                <button class="btn btn-secondary btn-small" onclick="toggleQuest(${quest.id})">
                    ${quest.is_active ? '🔴 Deactivate' : '🟢 Activate'}
                </button>
                <button class="btn btn-danger btn-small" onclick="deleteQuest(${quest.id})">
                    🗑️ Delete
                </button>
            </div>
        </div>
    `;
}

function openCreateModal() {
    editingQuestId = null;
    document.getElementById('modalTitle').textContent = 'Create New Quest';
    document.getElementById('questForm').reset();
    document.getElementById('questId').value = '';

    // Uncheck all trait checkboxes
    document.querySelectorAll('input[name="trait_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    document.getElementById('questModal').classList.add('show');
}

function editQuest(id) {
    const quest = quests.find(q => q.id === id);
    if (!quest) return;

    editingQuestId = id;
    document.getElementById('modalTitle').textContent = 'Edit Quest';
    document.getElementById('questId').value = quest.id;
    document.getElementById('title').value = quest.title;
    document.getElementById('description').value = quest.description || '';
    document.getElementById('gold_reward').value = quest.gold_reward;
    document.getElementById('is_active').value = quest.is_active ? '1' : '0';

    // Check the traits associated with this quest
    const questTraitIds = quest.traits ? quest.traits.map(t => t.id) : [];
    document.querySelectorAll('input[name="trait_ids[]"]').forEach(checkbox => {
        checkbox.checked = questTraitIds.includes(parseInt(checkbox.value));
    });

    document.getElementById('questModal').classList.add('show');
}

function closeModal() {
    document.getElementById('questModal').classList.remove('show');
    editingQuestId = null;
}

async function saveQuest(event) {
    event.preventDefault();

    // Collect selected trait IDs
    const selectedTraitIds = Array.from(
        document.querySelectorAll('input[name="trait_ids[]"]:checked')
    ).map(checkbox => parseInt(checkbox.value));

    const formData = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        gold_reward: parseInt(document.getElementById('gold_reward').value),
        is_active: document.getElementById('is_active').value === '1',
        trait_ids: selectedTraitIds
    };

    try {
        if (editingQuestId) {
            await api.parent.updateQuest(editingQuestId, formData);
            notify.success('Quest updated successfully!');
        } else {
            await api.parent.createQuest(formData);
            notify.success('Quest created successfully!');
        }

        closeModal();
        await loadQuests();
    } catch (error) {
        notify.error('Failed to save quest: ' + error.message);
    }
}

async function toggleQuest(id) {
    try {
        await api.parent.toggleQuest(id);
        const quest = quests.find(q => q.id === id);
        notify.success(`Quest ${quest.is_active ? 'deactivated' : 'activated'}!`);
        await loadQuests();
    } catch (error) {
        notify.error('Failed to toggle quest: ' + error.message);
    }
}

async function deleteQuest(id) {
    const quest = quests.find(q => q.id === id);
    if (!confirm(`Are you sure you want to delete "${quest.title}"? This cannot be undone.`)) {
        return;
    }

    try {
        await api.parent.deleteQuest(id);
        notify.success('Quest deleted successfully!');
        await loadQuests();
    } catch (error) {
        notify.error('Failed to delete quest: ' + error.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.getElementById('questModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endpush
