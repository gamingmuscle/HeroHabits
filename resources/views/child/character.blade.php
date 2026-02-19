@extends('layouts.child')

@section('title', 'My Character')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>⚔️ My Character Sheet ⚔️</h2>
        <p class="page-subtitle">Your heroic journey and abilities</p>
    </div>

    <div class="character-profile">
        <div class="profile-top">
            <img src="{{ $child->avatar_image ? asset('Assets/Profile/' . $child->avatar_image) : asset('Assets/Profile/princess_3tr.png') }}"
                 alt="{{ $child->name }}"
                 class="character-avatar">

            <div class="character-info">
                <h1 class="character-name">{{ $child->name }}</h1>
                <p class="character-title">Young Hero in Training</p>
                <div class="level-display">
                    Level {{ $child->level }}
                </div>
                <div class="xp-section" id="xp-section">
                    <div class="xp-label">Experience Progress</div>
                    <div class="xp-bar">
                        <div class="xp-fill" id="xp-fill" style="width: 0%">
                            <span id="xp-percentage">0%</span>
                        </div>
                    </div>
                    <div class="xp-text" id="xp-text">
                        Loading...
                    </div>
                </div>
            </div>
        </div>


    </div>

    <div class="main-stats-section">
        <div class="traits-section">
            <div id="traits-container" class="loading">
                Loading traits...
            </div>
        </div>

        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">{{ $child->gold_balance }}</div>
                    <div class="stat-label">Current Gold</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✨</div>
                    <div class="stat-value" id="total-treasures">-</div>
                    <div class="stat-label">Total Treasures</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value" id="quests-completed">-</div>
                    <div class="stat-label">Quests Completed</div>
                </div>
            </div>

            <h2 class="section-title" style="margin-top: 30px;">🎒 My Treasure Inventory 🎒</h2>
            <div id="inventory-container" class="loading">
                Loading inventory...
            </div>
        </div>


    </div>
</div>
@endsection

@push('scripts')
<script>
let childData = {
    id: {{ $child->id }},
    level: {{ $child->level }},
    experience_points: {{ $child->experience_points }},
    gold_balance: {{ $child->gold_balance }}
};

// Load child's trait data
document.addEventListener('DOMContentLoaded', loadCharacterData);

async function loadCharacterData() {
    try {
        // Load XP progress from API (includes correct calculations)
        const result = await api.child.getTraits();
        const childInfo = result.child;

        // Update XP bar using backend-calculated values
        const progressPercentage = childInfo.progress_percentage || 0;
        const currentXP = childInfo.experience_points;
        const xpToNextLevel = childInfo.xp_to_next_level;

        document.getElementById('xp-fill').style.width = Math.min(100, progressPercentage) + '%';
        document.getElementById('xp-percentage').textContent = Math.round(progressPercentage) + '%';
        document.getElementById('xp-text').textContent = `${currentXP} XP (${xpToNextLevel} needed for Level ${childData.level + 1})`;

        // Load quest stats
        await loadQuestStats();

        // Load traits (already loaded above, just render)
        renderTraits(result.traits || []);

        // Load inventory
        await loadInventory();
    } catch (error) {
        console.error('Failed to load character data:', error);
    }
}

async function loadQuestStats() {
    try {
        const result = await api.child.getStats();
        document.getElementById('quests-completed').textContent = result.stats.total_completed || 0;
    } catch (error) {
        console.error('Failed to load quest stats:', error);
        document.getElementById('quests-completed').textContent = '?';
    }
}


function renderTraits(traits) {
    const container = document.getElementById('traits-container');

    if (traits.length === 0) {
        container.innerHTML = '<div class="empty-traits">No traits yet! Complete quests to develop your character traits.</div>';
        return;
    }

    container.innerHTML = '<div class="traits-grid">' +
        traits.map(trait => createTraitCard(trait)).join('') +
        '</div>';
}

function createTraitCard(trait) {
    const progressPercentage = Math.min(100, Math.max(0, trait.progress_percentage || 0));
    const totalXP = trait.experience_points + trait.xp_to_next_level;

    return `
        <div class="trait-card">
            <div class="trait-ability-score">
                <div class="trait-name">
                    ${escapeHtml(trait.name)}
                </div>
                <div class="trait-level-display">
                    ${trait.level}
                </div>
                <div class="trait-progress">
                    <div class="trait-progress-fill" style="width: ${progressPercentage}%"></div>
                    <div class="trait-xp-tooltip">
                        ${trait.experience_points} / ${totalXP} XP (${Math.round(progressPercentage)}%)
                    </div>
                </div>
            </div>
            <div class="trait-description-section">
                <p class="trait-description">${escapeHtml(trait.description)}</p>
            </div>
        </div>
    `;
}

async function loadInventory() {
    try {
        const result = await api.child.getPurchases();
        const purchases = result.purchases || [];

        // Update total treasures count
        document.getElementById('total-treasures').textContent = purchases.length;

        renderInventory(purchases);
    } catch (error) {
        console.error('Failed to load inventory:', error);
        document.getElementById('inventory-container').innerHTML =
            '<div class="empty-inventory">Unable to load inventory</div>';
    }
}

function renderInventory(purchases) {
    const container = document.getElementById('inventory-container');

    if (purchases.length === 0) {
        container.innerHTML = '<div class="empty-inventory">No treasures purchased yet! Visit the Treasure Shop to spend your gold.</div>';
        return;
    }

    container.innerHTML = '<div class="inventory-grid">' +
        purchases.map(purchase => createInventoryItem(purchase)).join('') +
        '</div>';
}

function createInventoryItem(purchase) {
    const treasure = purchase.treasure;
    const purchaseDate = new Date(purchase.purchased_at).toLocaleDateString();

    return `
        <div class="inventory-item">
            <div class="inventory-item-icon">🎁</div>
            <div class="inventory-item-title">${escapeHtml(treasure.title)}</div>
            <div class="inventory-item-description">${escapeHtml(treasure.description || '')}</div>
            <div class="inventory-item-cost">💰 ${treasure.gold_cost} Gold</div>
            <div style="text-align: center; color: #999; font-size: 0.8rem; margin-top: 8px;">
                Purchased: ${purchaseDate}
            </div>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
