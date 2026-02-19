@extends('layouts.child')

@section('title', 'Treasure Shop')



@section('content')
<div class="content-box">
    <div class="page-header">
        <h2>💎 Treasure Shop</h2>
        <p class="page-subtitle">Spend your hard-earned gold on awesome treasures!</p>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('shop')">💎 Shop</button>
        <button class="tab" onclick="switchTab('purchases')">🛒 My Purchases</button>
        <div class="treasure-shop-balance" id="balance-display">
            <span class="balance-amount" id="gold-balance">
                <img src="{{ asset('Assets/Icons & Logo/gold_coin.png') }}" alt="Gold" class="gold-icon"> --
            </span>
        </div>
    </div>

    <div id="tab-shop" class="tab-content active">
        <div id="shop-container" class="loading">
            Loading treasures...
        </div>
    </div>

    <div id="tab-purchases" class="tab-content">
        <div id="purchases-container" class="loading">
            Loading purchases...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let treasures = [];
let purchases = [];
let goldBalance = 0;
let currentTab = 'shop';
let purchasesLoaded = false;

document.addEventListener('DOMContentLoaded', () => {
    loadTreasures();
});

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');

    currentTab = tab;

    if (tab === 'purchases' && !purchasesLoaded) {
        loadPurchases();
    }
}

async function loadTreasures() {
    try {
        const result = await api.child.getTreasures();
        treasures = result.treasures;
        goldBalance = result.child_gold_balance;
        updateBalanceDisplay(goldBalance);
        renderTreasures();
    } catch (error) {
        notify.error('Failed to load treasures: ' + error.message);
        document.getElementById('shop-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load treasures</p></div>';
    }
}

function updateBalanceDisplay(balance) {
    document.getElementById('gold-balance').innerHTML =
        '<img src="{{ asset("Assets/Icons & Logo/gold_coin.png") }}" alt="Gold" class="gold-icon"> ' + balance;
}

function renderTreasures() {
    const container = document.getElementById('shop-container');

    if (treasures.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">💎</div>
                <h3>No Treasures Available</h3>
                <p>Ask your parent to add some treasures to the shop!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '<div class="shop-grid">' +
        treasures.map(treasure => createTreasureCard(treasure)).join('') +
        '</div>';
}

function createTreasureCard(treasure) {
    const canAfford = treasure.can_afford;
    const cardClass = canAfford ? 'shop-item' : 'shop-item cant-afford';

    return `
        <div class="${cardClass}">
            <div class="shop-item-icon">🎁</div>
            <div class="shop-item-title">${escapeHtml(treasure.title)}</div>
            ${treasure.description
                ? `<div class="shop-item-description">${escapeHtml(treasure.description)}</div>`
                : '<div class="shop-item-description" style="opacity: 0.4;">No description</div>'}
            <div class="shop-item-cost">
                ${treasure.gold_cost} <img src="{{ asset('Assets/Icons & Logo/gold_coin.png') }}" alt="Gold" class="gold-icon">
            </div>
            <button class="btn-buy" onclick="purchaseTreasure(${treasure.id}, '${escapeHtml(treasure.title)}', ${treasure.gold_cost})" ${canAfford ? '' : 'disabled'}>
                ${canAfford ? 'Buy' : 'Not Enough Gold'}
            </button>
        </div>
    `;
}

async function purchaseTreasure(id, title, cost) {
    if (!confirm(`Buy "${title}" for ${cost} gold?`)) {
        return;
    }

    try {
        const result = await api.child.purchaseTreasure(id);
        notify.success(result.message || `You bought "${title}"!`);

        goldBalance = result.new_balance;
        updateBalanceDisplay(goldBalance);

        // Refresh shop to update affordability
        await loadTreasures();

        // Reset purchases so they reload on next tab visit
        purchasesLoaded = false;
    } catch (error) {
        notify.error(error.message || 'Failed to purchase treasure');
    }
}

async function loadPurchases() {
    try {
        const result = await api.child.getPurchases();
        purchases = result.purchases || [];
        purchasesLoaded = true;
        renderPurchases();
    } catch (error) {
        notify.error('Failed to load purchases: ' + error.message);
        document.getElementById('purchases-container').innerHTML =
            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load purchases</p></div>';
    }
}

function renderPurchases() {
    const container = document.getElementById('purchases-container');

    if (purchases.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <h3>No Purchases Yet</h3>
                <p>Buy some treasures from the shop to see them here!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = purchases.map(purchase => createPurchaseItem(purchase)).join('');
}

function createPurchaseItem(purchase) {
    const date = new Date(purchase.purchased_at || purchase.created_at);
    const dateStr = date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });

    return `
        <div class="purchase-item">
            <div class="purchase-item-info">
                <div class="purchase-item-treasure">🎁 ${escapeHtml(purchase.treasure?.title || 'Treasure')}</div>
                <div class="purchase-item-date">${dateStr}</div>
            </div>
            <div class="purchase-item-cost">
                -${purchase.gold_spent} <img src="{{ asset('Assets/Icons & Logo/gold_coin.png') }}" alt="Gold" class="gold-icon">
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
