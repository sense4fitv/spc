/**
 * Notification Manager
 * Handles real-time notifications, badge updates, and UI updates
 */

class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.audio = null;
        this.privateChannel = null;
        this.globalChannel = null;
        this.isInitialized = false;
    }

    /**
     * Initialize notification manager
     */
    async init() {
        if (this.isInitialized) {
            return;
        }

        // Initialize audio for notification sound
        this.audio = new Audio('/assets/audio/newNotification.mp3');
        this.audio.volume = 0.5; // 50% volume

        // Wait for Pusher channels to be ready
        const channelsReadyHandler = (event) => {
            this.privateChannel = event.detail.privateChannel;
            this.globalChannel = event.detail.globalChannel;
            this.bindEvents();
            // Remove listener after first setup
            document.removeEventListener('pusher:channels-ready', channelsReadyHandler);
        };
        document.addEventListener('pusher:channels-ready', channelsReadyHandler);
        
        // Also check if channels are already ready (in case event was fired before listener was set)
        if (window.pusherClient && window.pusherClient.getPrivateChannel() && window.pusherClient.getGlobalChannel()) {
            this.privateChannel = window.pusherClient.getPrivateChannel();
            this.globalChannel = window.pusherClient.getGlobalChannel();
            this.bindEvents();
        }

        // Load initial notifications and count
        await this.loadUnreadCount();
        await this.loadNotifications();

        // Bind UI events
        this.bindUIEvents();

        this.isInitialized = true;
    }

    /**
     * Bind Pusher event listeners
     */
    bindEvents() {
        // Listen for new notifications on private channel
        if (this.privateChannel) {
            console.log('Binding events to private channel');
            this.privateChannel.bind('new-notification', (data) => {
                console.log('=== PRIVATE NOTIFICATION RECEIVED ===');
                console.log('Full data:', data);
                console.log('Notification object:', data.notification);
                
                if (data.notification) {
                    this.handleNewNotification(data.notification);
                } else {
                    console.error('Notification data missing! Received:', data);
                    // Fallback: try to use data directly if notification property doesn't exist
                    this.handleNewNotification(data);
                }
            });
        } else {
            console.error('Private channel is null! Cannot bind events.');
        }

        // Listen for global notifications
        if (this.globalChannel) {
            console.log('Binding events to global channel');
            this.globalChannel.bind('global-notification', (data) => {
                console.log('Global notification received:', data);
                if (data.notification) {
                    this.handleGlobalNotification(data.notification);
                } else {
                    this.handleGlobalNotification(data);
                }
            });
        }
    }

    /**
     * Handle new private notification
     */
    handleNewNotification(notification) {
        console.log('Handling new notification:', notification);
        
        // Play sound
        this.playNotificationSound();

        // Add to notifications array (prepend)
        this.notifications.unshift(notification);
        
        // Update unread count
        this.unreadCount++;
        this.updateBadge();

        // Update dropdown (always update, not just if open)
        this.updateDropdown();

        // Show toast notification
        this.showToastNotification(notification);
    }

    /**
     * Handle global notification
     */
    handleGlobalNotification(notification) {
        // Play sound
        this.playNotificationSound();

        // Show toast notification
        this.showToastNotification(notification);
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        if (this.audio) {
            // Reset audio to beginning
            this.audio.currentTime = 0;
            
            // Play audio - browser might block autoplay, but user interaction should allow it
            const playPromise = this.audio.play();
            
            if (playPromise !== undefined) {
                playPromise
                    .then(() => {
                        // Audio started playing successfully
                        console.log('Notification sound played');
                    })
                    .catch(error => {
                        // Browser blocked autoplay - try again after user interaction
                        console.warn('Failed to play notification sound (autoplay blocked):', error);
                        // Try again on next user interaction
                        document.addEventListener('click', () => {
                            this.audio.play().catch(() => {});
                        }, { once: true });
                    });
            }
        }
    }

    /**
     * Load unread count from API
     */
    async loadUnreadCount() {
        try {
            const response = await fetch('/api/notifications/unread-count');
            const result = await response.json();

            if (result.success) {
                this.unreadCount = result.count || 0;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to load unread count:', error);
        }
    }

    /**
     * Load notifications from API
     */
    async loadNotifications(limit = 20) {
        try {
            const response = await fetch(`/api/notifications/unread?limit=${limit}`);
            const result = await response.json();

            if (result.success) {
                this.notifications = result.data || [];
                this.updateDropdown();
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    /**
     * Update badge count
     */
    updateBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.style.display = 'flex'; // Use flex to center the number
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            } else {
                badge.style.display = 'none';
            }
        }
    }

    /**
     * Update dropdown with notifications
     */
    updateDropdown() {
        const dropdown = document.querySelector('.notification-dropdown-list');
        if (!dropdown) {
            return;
        }

        // Clear existing notifications (except header and footer)
        const items = dropdown.querySelectorAll('.notification-item');
        items.forEach(item => item.remove());

        // Find the divider <li> element (not the <hr> inside it)
        const dividerLi = Array.from(dropdown.children).find(li => 
            li.querySelector('.dropdown-divider') !== null
        );

        if (this.notifications.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'notification-item';
            emptyItem.innerHTML = `
                <div class="dropdown-item text-center text-muted small py-3">
                    Nu ai notificări noi
                </div>
            `;
            
            if (dividerLi) {
                dropdown.insertBefore(emptyItem, dividerLi);
            } else {
                dropdown.appendChild(emptyItem);
            }
            return;
        }

        // Add notifications
        this.notifications.forEach(notification => {
            const item = this.createNotificationItem(notification);
            
            if (dividerLi) {
                dropdown.insertBefore(item, dividerLi);
            } else {
                dropdown.appendChild(item);
            }
        });
    }

    /**
     * Create notification item HTML
     */
    createNotificationItem(notification) {
        const item = document.createElement('li');
        item.className = 'notification-item';
        
        const typeClass = this.getTypeClass(notification.type);
        const typeIcon = this.getTypeIcon(notification.type);
        const link = notification.link || '#';

        item.innerHTML = `
            <a class="dropdown-item dropdown-item-custom" href="${link}" data-notification-id="${notification.id}">
                <div class="icon-box ${typeClass}">
                    <i class="bi ${typeIcon}"></i>
                </div>
                <div>
                    <div class="small fw-bold text-dark">${this.escapeHtml(notification.title)}</div>
                    <div class="small text-muted" style="font-size: 0.8rem;">${this.escapeHtml(notification.message)}</div>
                </div>
            </a>
        `;

        // Mark as read on click
        item.querySelector('a').addEventListener('click', async (e) => {
            await this.markAsRead(notification.id);
        });

        return item;
    }

    /**
     * Get CSS class for notification type
     */
    getTypeClass(type) {
        const classes = {
            'info': 'bg-info-subtle text-info',
            'success': 'bg-success-subtle text-success',
            'warning': 'bg-warning-subtle text-warning',
            'error': 'bg-danger-subtle text-danger'
        };
        return classes[type] || classes.info;
    }

    /**
     * Get icon for notification type
     */
    getTypeIcon(type) {
        const icons = {
            'info': 'bi-info-circle',
            'success': 'bi-check2-circle',
            'warning': 'bi-exclamation-triangle',
            'error': 'bi-x-circle'
        };
        return icons[type] || icons.info;
    }

    /**
     * Show toast notification
     */
    showToastNotification(notification) {
        const toast = document.getElementById('liveToast');
        if (!toast) {
            return;
        }

        const icon = document.getElementById('toastIcon');
        const message = document.getElementById('toastMessage');

        if (icon) {
            icon.className = `bi ${this.getTypeIcon(notification.type)} text-${notification.type === 'error' ? 'danger' : notification.type}`;
        }

        if (message) {
            message.textContent = notification.title;
        }

        const toastInstance = bootstrap.Toast.getOrCreateInstance(toast);
        toastInstance.show();
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();

            if (result.success) {
                // Remove from unread notifications
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
                this.updateDropdown();
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/read-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();

            if (result.success) {
                this.notifications = [];
                this.unreadCount = 0;
                this.updateBadge();
                this.updateDropdown();
            }
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    }

    /**
     * Bind UI events
     */
    bindUIEvents() {
        // Mark all as read button
        const markAllButton = document.querySelector('.mark-all-read-btn');
        if (markAllButton) {
            markAllButton.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.markAllAsRead();
            });
        }

        // Reload notifications when dropdown is opened
        const dropdownButton = document.getElementById('notificationDropdownButton');
        
        if (dropdownButton) {
            console.log('Setting up dropdown event listeners for notification bell');
            
            // Method 1: Use Bootstrap dropdown events (on the button element itself)
            dropdownButton.addEventListener('show.bs.dropdown', async (e) => {
                console.log('Dropdown opening (show.bs.dropdown event), reloading notifications...');
                await this.loadNotifications();
            });
            
            // Method 2: Also listen on click as fallback (with delay to let Bootstrap handle it)
            dropdownButton.addEventListener('click', async (e) => {
                // Wait a bit for Bootstrap to toggle the dropdown
                setTimeout(async () => {
                    const dropdownMenu = document.querySelector('.notification-dropdown-list');
                    if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                        console.log('Dropdown is now visible, reloading notifications...');
                        await this.loadNotifications();
                    }
                }, 150);
            });
        } else {
            console.error('Notification dropdown button not found!');
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export singleton instance
window.notificationManager = new NotificationManager();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize Pusher first
    await window.pusherClient.init();
    
    // Then initialize notification manager
    await window.notificationManager.init();
});


