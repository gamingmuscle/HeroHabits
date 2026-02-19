/**
 * Hero Habits Notification System
 * Toast notifications for user feedback
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create notification container
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        `;
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        const colors = {
            success: { bg: '#10b981', text: '#fff' },
            error: { bg: '#ef4444', text: '#fff' },
            warning: { bg: '#f59e0b', text: '#fff' },
            info: { bg: '#3b82f6', text: '#fff' }
        };

        const color = colors[type] || colors.info;

        notification.style.cssText = `
            background: ${color.bg};
            color: ${color.text};
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            cursor: pointer;
        `;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        notification.innerHTML = `
            <span style="font-size: 18px; font-weight: bold;">${icons[type] || icons.info}</span>
            <span style="flex: 1;">${message}</span>
        `;

        // Add CSS animation
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        this.container.appendChild(notification);

        // Click to dismiss
        notification.addEventListener('click', () => {
            this.dismiss(notification);
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(notification);
            }, duration);
        }

        return notification;
    }

    dismiss(notification) {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }

    /**
     * Show celebratory level-up notification
     */
    levelUp(childName, newLevel, levelsGained = 1, duration = 6000) {
        const notification = document.createElement('div');
        notification.className = 'notification notification-levelup';

        notification.style.cssText = `
            background: linear-gradient(135deg, #7E57C2 0%, #9575CD 100%);
            color: white;
            padding: 24px 28px;
            border-radius: 16px;
            box-shadow:
                0 10px 40px rgba(126, 87, 194, 0.5),
                0 0 0 3px rgba(255, 215, 0, 0.3),
                inset 0 2px 4px rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 16px;
            font-weight: 600;
            animation: levelUpBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            cursor: pointer;
            min-width: 320px;
            border: 3px solid #FFD700;
        `;

        const levelText = levelsGained > 1
            ? `Level ${newLevel - levelsGained + 1} → ${newLevel}`
            : `Level ${newLevel}`;

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="
                    font-size: 48px;
                    animation: spin 1s ease-in-out;
                    filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
                ">⭐</div>
                <div style="flex: 1;">
                    <div style="font-size: 20px; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                        🎉 LEVEL UP! 🎉
                    </div>
                    <div style="font-size: 16px; margin-top: 4px; opacity: 0.95;">
                        ${childName} reached ${levelText}!
                    </div>
                </div>
            </div>
            <div style="
                background: rgba(255, 255, 255, 0.2);
                height: 4px;
                border-radius: 2px;
                overflow: hidden;
            ">
                <div style="
                    background: linear-gradient(90deg, #FFD700, #FFA500);
                    height: 100%;
                    width: 100%;
                    animation: progressFill 0.8s ease-out 0.3s backwards;
                    box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
                "></div>
            </div>
        `;

        // Add special level-up animations
        if (!document.getElementById('levelup-animation-styles')) {
            const style = document.createElement('style');
            style.id = 'levelup-animation-styles';
            style.textContent = `
                @keyframes levelUpBounce {
                    0% {
                        transform: scale(0) rotate(-180deg);
                        opacity: 0;
                    }
                    50% {
                        transform: scale(1.1) rotate(10deg);
                    }
                    100% {
                        transform: scale(1) rotate(0deg);
                        opacity: 1;
                    }
                }
                @keyframes spin {
                    0%, 100% { transform: rotate(0deg) scale(1); }
                    25% { transform: rotate(10deg) scale(1.2); }
                    50% { transform: rotate(-10deg) scale(1.1); }
                    75% { transform: rotate(5deg) scale(1.15); }
                }
                @keyframes progressFill {
                    from { width: 0%; }
                    to { width: 100%; }
                }
                @keyframes confetti {
                    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        // Add confetti effect
        this.createConfetti();

        this.container.appendChild(notification);

        // Click to dismiss
        notification.addEventListener('click', () => {
            this.dismiss(notification);
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(notification);
            }, duration);
        }

        // Play a success sound if available
        this.playLevelUpSound();

        return notification;
    }

    /**
     * Create confetti animation
     */
    createConfetti() {
        const colors = ['#FFD700', '#FFA500', '#FF69B4', '#7E57C2', '#00CED1', '#32CD32'];
        const confettiCount = 30;

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                top: -10px;
                left: ${Math.random() * 100}%;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                animation: confetti ${2 + Math.random() * 2}s linear forwards;
                animation-delay: ${Math.random() * 0.5}s;
                z-index: 10000;
                pointer-events: none;
            `;
            document.body.appendChild(confetti);

            setTimeout(() => confetti.remove(), 4000);
        }
    }

    /**
     * Play level-up sound (if audio is enabled)
     */
    playLevelUpSound() {
        // Optional: Add audio file for level-up sound
        // const audio = new Audio('/assets/sounds/level-up.mp3');
        // audio.volume = 0.3;
        // audio.play().catch(() => {}); // Ignore if autoplay blocked
    }
}

// Global instance
const notify = new NotificationSystem();
