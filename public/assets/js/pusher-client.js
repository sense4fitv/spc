/**
 * Pusher Client
 * Initializes Pusher connection and manages channel subscriptions
 */

class PusherClient {
    constructor() {
        this.pusher = null;
        this.privateChannel = null;
        this.globalChannel = null;
        this.userId = null;
        this.isConnected = false;
    }

    /**
     * Initialize Pusher connection
     */
    async init() {
        try {
            // Get Pusher config from API
            const response = await fetch('/api/pusher/config');
            const result = await response.json();

            if (!result.success) {
                console.error('Failed to get Pusher config:', result.message);
                return false;
            }

            const config = result.data;
            this.userId = result.userId;

            // Load Pusher JS library if not already loaded
            if (typeof Pusher === 'undefined') {
                await this.loadPusherLibrary();
            }

            // Initialize Pusher
            this.pusher = new Pusher(config.key, {
                cluster: config.cluster,
                forceTLS: config.useTLS,
                authEndpoint: '/api/pusher/auth',
                auth: {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            });

            // Connection event listeners
            this.pusher.connection.bind('connected', () => {
                console.log('Pusher connected');
                this.isConnected = true;
                this.subscribeToChannels();
            });

            this.pusher.connection.bind('disconnected', () => {
                console.log('Pusher disconnected');
                this.isConnected = false;
            });

            this.pusher.connection.bind('error', (err) => {
                console.error('Pusher connection error:', err);
                this.isConnected = false;
            });

            // Subscribe immediately if already connected
            if (this.pusher.connection.state === 'connected') {
                this.isConnected = true;
                this.subscribeToChannels();
            }

            return true;
        } catch (error) {
            console.error('Failed to initialize Pusher:', error);
            return false;
        }
    }

    /**
     * Load Pusher JS library dynamically
     */
    loadPusherLibrary() {
        return new Promise((resolve, reject) => {
            if (typeof Pusher !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load Pusher library'));
            document.head.appendChild(script);
        });
    }

    /**
     * Subscribe to private and global channels
     */
    subscribeToChannels() {
        if (!this.userId) {
            console.error('Cannot subscribe: userId not available');
            return;
        }

        let privateReady = false;
        let globalReady = false;

        const checkAndNotify = () => {
            if (privateReady && globalReady) {
                console.log('Both channels ready, notifying notification manager');
                this.notifyChannelsReady();
            }
        };

        // Subscribe to private user channel
        this.privateChannel = this.pusher.subscribe(`private-user-${this.userId}`);
        
        this.privateChannel.bind('pusher:subscription_succeeded', () => {
            console.log('Subscribed to private channel:', `private-user-${this.userId}`);
            privateReady = true;
            checkAndNotify();
        });

        this.privateChannel.bind('pusher:subscription_error', (status) => {
            console.error('Private channel subscription error:', status);
        });

        // Subscribe to global channel
        this.globalChannel = this.pusher.subscribe('global');
        
        this.globalChannel.bind('pusher:subscription_succeeded', () => {
            console.log('Subscribed to global channel');
            globalReady = true;
            checkAndNotify();
        });

        this.globalChannel.bind('pusher:subscription_error', (status) => {
            console.error('Global channel subscription error:', status);
        });
    }

    /**
     * Notify that channels are ready
     */
    notifyChannelsReady() {
        // Only notify if both channels are ready and exist
        if (this.privateChannel && this.globalChannel) {
            document.dispatchEvent(new CustomEvent('pusher:channels-ready', {
                detail: {
                    privateChannel: this.privateChannel,
                    globalChannel: this.globalChannel
                }
            }));
        }
    }

    /**
     * Get private channel
     */
    getPrivateChannel() {
        return this.privateChannel;
    }

    /**
     * Get global channel
     */
    getGlobalChannel() {
        return this.globalChannel;
    }

    /**
     * Disconnect Pusher
     */
    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
            this.isConnected = false;
        }
    }
}

// Export singleton instance
window.pusherClient = new PusherClient();

