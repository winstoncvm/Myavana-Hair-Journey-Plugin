/**
 * MYAVANA Client-Side Cache Manager
 * localStorage caching for user preferences and API responses
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    window.MyavanaCache = {
        prefix: 'myavana_',
        defaultExpiration: 3600000, // 1 hour in milliseconds
        maxSize: 5242880, // 5MB in bytes
        enabled: typeof(Storage) !== 'undefined'
    };

    /**
     * Initialize Cache Manager
     */
    function initCacheManager() {
        if (!MyavanaCache.enabled) {
            console.warn('[MyavanaCache] localStorage not supported');
            return;
        }

        // Clean up expired cache on init
        cleanupExpiredCache();

        // Monitor cache size
        monitorCacheSize();

        console.log('[MyavanaCache] Initialized');
    }

    /**
     * Set cache item
     *
     * @param {string} key Cache key
     * @param {*} value Value to cache
     * @param {number} expiration Expiration in milliseconds (optional)
     * @returns {boolean} Success
     */
    function set(key, value, expiration) {
        if (!MyavanaCache.enabled) {
            return false;
        }

        expiration = expiration || MyavanaCache.defaultExpiration;

        const cacheItem = {
            value: value,
            timestamp: Date.now(),
            expiration: Date.now() + expiration
        };

        try {
            const fullKey = MyavanaCache.prefix + key;
            localStorage.setItem(fullKey, JSON.stringify(cacheItem));

            // Update cache metadata
            updateCacheMetadata();

            return true;
        } catch (e) {
            // Handle quota exceeded error
            if (e.name === 'QuotaExceededError' || e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
                console.warn('[MyavanaCache] Storage quota exceeded, clearing old cache');
                clearOldestItems(5);

                // Try again
                try {
                    const fullKey = MyavanaCache.prefix + key;
                    localStorage.setItem(fullKey, JSON.stringify(cacheItem));
                    return true;
                } catch (e2) {
                    console.error('[MyavanaCache] Failed to set cache after cleanup:', e2);
                    return false;
                }
            }

            console.error('[MyavanaCache] Error setting cache:', e);
            return false;
        }
    }

    /**
     * Get cache item
     *
     * @param {string} key Cache key
     * @returns {*|null} Cached value or null
     */
    function get(key) {
        if (!MyavanaCache.enabled) {
            return null;
        }

        try {
            const fullKey = MyavanaCache.prefix + key;
            const item = localStorage.getItem(fullKey);

            if (!item) {
                return null;
            }

            const cacheItem = JSON.parse(item);

            // Check if expired
            if (Date.now() > cacheItem.expiration) {
                remove(key);
                return null;
            }

            return cacheItem.value;
        } catch (e) {
            console.error('[MyavanaCache] Error getting cache:', e);
            return null;
        }
    }

    /**
     * Remove cache item
     *
     * @param {string} key Cache key
     * @returns {boolean} Success
     */
    function remove(key) {
        if (!MyavanaCache.enabled) {
            return false;
        }

        try {
            const fullKey = MyavanaCache.prefix + key;
            localStorage.removeItem(fullKey);
            updateCacheMetadata();
            return true;
        } catch (e) {
            console.error('[MyavanaCache] Error removing cache:', e);
            return false;
        }
    }

    /**
     * Check if cache item exists and is valid
     *
     * @param {string} key Cache key
     * @returns {boolean} Exists and valid
     */
    function has(key) {
        return get(key) !== null;
    }

    /**
     * Clear all cache
     *
     * @returns {boolean} Success
     */
    function clear() {
        if (!MyavanaCache.enabled) {
            return false;
        }

        try {
            const keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (key.startsWith(MyavanaCache.prefix)) {
                    localStorage.removeItem(key);
                }
            });

            updateCacheMetadata();
            return true;
        } catch (e) {
            console.error('[MyavanaCache] Error clearing cache:', e);
            return false;
        }
    }

    /**
     * Clear cache by pattern
     *
     * @param {string} pattern Pattern to match (e.g., 'user_*', '*_analytics')
     * @returns {number} Number of items cleared
     */
    function clearPattern(pattern) {
        if (!MyavanaCache.enabled) {
            return 0;
        }

        let cleared = 0;
        const regex = new RegExp('^' + MyavanaCache.prefix + pattern.replace('*', '.*') + '$');

        try {
            const keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (regex.test(key)) {
                    localStorage.removeItem(key);
                    cleared++;
                }
            });

            updateCacheMetadata();
            return cleared;
        } catch (e) {
            console.error('[MyavanaCache] Error clearing pattern:', e);
            return cleared;
        }
    }

    /**
     * Clean up expired cache items
     *
     * @returns {number} Number of items cleaned
     */
    function cleanupExpiredCache() {
        if (!MyavanaCache.enabled) {
            return 0;
        }

        let cleaned = 0;
        const now = Date.now();

        try {
            const keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (key.startsWith(MyavanaCache.prefix)) {
                    const item = localStorage.getItem(key);
                    if (item) {
                        try {
                            const cacheItem = JSON.parse(item);
                            if (now > cacheItem.expiration) {
                                localStorage.removeItem(key);
                                cleaned++;
                            }
                        } catch (e) {
                            // Invalid JSON, remove it
                            localStorage.removeItem(key);
                            cleaned++;
                        }
                    }
                }
            });

            if (cleaned > 0) {
                updateCacheMetadata();
                console.log('[MyavanaCache] Cleaned ' + cleaned + ' expired items');
            }

            return cleaned;
        } catch (e) {
            console.error('[MyavanaCache] Error cleaning cache:', e);
            return cleaned;
        }
    }

    /**
     * Clear oldest cache items
     *
     * @param {number} count Number of items to clear
     * @returns {number} Number of items cleared
     */
    function clearOldestItems(count) {
        if (!MyavanaCache.enabled) {
            return 0;
        }

        try {
            const items = [];
            const keys = Object.keys(localStorage);

            keys.forEach(function(key) {
                if (key.startsWith(MyavanaCache.prefix)) {
                    const item = localStorage.getItem(key);
                    if (item) {
                        try {
                            const cacheItem = JSON.parse(item);
                            items.push({
                                key: key,
                                timestamp: cacheItem.timestamp
                            });
                        } catch (e) {
                            // Invalid JSON, mark for removal
                            items.push({
                                key: key,
                                timestamp: 0
                            });
                        }
                    }
                }
            });

            // Sort by timestamp (oldest first)
            items.sort((a, b) => a.timestamp - b.timestamp);

            // Remove oldest items
            const toRemove = items.slice(0, count);
            toRemove.forEach(function(item) {
                localStorage.removeItem(item.key);
            });

            updateCacheMetadata();
            return toRemove.length;
        } catch (e) {
            console.error('[MyavanaCache] Error clearing oldest items:', e);
            return 0;
        }
    }

    /**
     * Get cache size in bytes
     *
     * @returns {number} Size in bytes
     */
    function getSize() {
        if (!MyavanaCache.enabled) {
            return 0;
        }

        let size = 0;

        try {
            const keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (key.startsWith(MyavanaCache.prefix)) {
                    const item = localStorage.getItem(key);
                    if (item) {
                        size += item.length + key.length;
                    }
                }
            });

            return size;
        } catch (e) {
            console.error('[MyavanaCache] Error calculating size:', e);
            return 0;
        }
    }

    /**
     * Get cache statistics
     *
     * @returns {object} Statistics
     */
    function getStats() {
        if (!MyavanaCache.enabled) {
            return {
                enabled: false,
                count: 0,
                size: 0,
                sizeFormatted: '0 B'
            };
        }

        const size = getSize();
        const count = Object.keys(localStorage).filter(key => key.startsWith(MyavanaCache.prefix)).length;

        return {
            enabled: true,
            count: count,
            size: size,
            sizeFormatted: formatBytes(size),
            maxSize: MyavanaCache.maxSize,
            maxSizeFormatted: formatBytes(MyavanaCache.maxSize),
            percentUsed: (size / MyavanaCache.maxSize * 100).toFixed(2)
        };
    }

    /**
     * Monitor cache size and warn if approaching limit
     */
    function monitorCacheSize() {
        const size = getSize();
        const percentUsed = (size / MyavanaCache.maxSize) * 100;

        if (percentUsed > 80) {
            console.warn('[MyavanaCache] Cache size is at ' + percentUsed.toFixed(2) + '%');

            if (percentUsed > 90) {
                console.warn('[MyavanaCache] Clearing old cache items to free space');
                clearOldestItems(10);
            }
        }
    }

    /**
     * Update cache metadata
     */
    function updateCacheMetadata() {
        try {
            const metadata = {
                lastUpdated: Date.now(),
                count: Object.keys(localStorage).filter(key => key.startsWith(MyavanaCache.prefix)).length,
                size: getSize()
            };

            localStorage.setItem(MyavanaCache.prefix + '_metadata', JSON.stringify(metadata));
        } catch (e) {
            // Metadata is non-critical, just log the error
            console.error('[MyavanaCache] Error updating metadata:', e);
        }
    }

    /**
     * Format bytes to human readable string
     *
     * @param {number} bytes Bytes
     * @returns {string} Formatted string
     */
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';

        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Cache AJAX response
     *
     * @param {string} action AJAX action
     * @param {object} params Request parameters
     * @param {object} response Response data
     * @param {number} expiration Expiration in milliseconds
     */
    function cacheAjaxResponse(action, params, response, expiration) {
        const key = 'ajax_' + action + '_' + JSON.stringify(params);
        return set(key, response, expiration);
    }

    /**
     * Get cached AJAX response
     *
     * @param {string} action AJAX action
     * @param {object} params Request parameters
     * @returns {*|null} Cached response or null
     */
    function getCachedAjaxResponse(action, params) {
        const key = 'ajax_' + action + '_' + JSON.stringify(params);
        return get(key);
    }

    /**
     * Cache user preference
     *
     * @param {string} preference Preference name
     * @param {*} value Preference value
     */
    function setPreference(preference, value) {
        return set('pref_' + preference, value, 31536000000); // 1 year
    }

    /**
     * Get user preference
     *
     * @param {string} preference Preference name
     * @param {*} defaultValue Default value if not found
     * @returns {*} Preference value
     */
    function getPreference(preference, defaultValue) {
        const value = get('pref_' + preference);
        return value !== null ? value : defaultValue;
    }

    /**
     * Cache user data
     *
     * @param {number} userId User ID
     * @param {string} dataType Data type
     * @param {*} data Data to cache
     * @param {number} expiration Expiration in milliseconds
     */
    function cacheUserData(userId, dataType, data, expiration) {
        const key = 'user_' + userId + '_' + dataType;
        return set(key, data, expiration);
    }

    /**
     * Get cached user data
     *
     * @param {number} userId User ID
     * @param {string} dataType Data type
     * @returns {*|null} Cached data or null
     */
    function getCachedUserData(userId, dataType) {
        const key = 'user_' + userId + '_' + dataType;
        return get(key);
    }

    /**
     * Clear user cache
     *
     * @param {number} userId User ID
     */
    function clearUserCache(userId) {
        return clearPattern('user_' + userId + '_*');
    }

    // Public API
    window.MyavanaCache = $.extend(window.MyavanaCache, {
        init: initCacheManager,
        set: set,
        get: get,
        remove: remove,
        has: has,
        clear: clear,
        clearPattern: clearPattern,
        cleanupExpired: cleanupExpiredCache,
        getSize: getSize,
        getStats: getStats,
        cacheAjax: cacheAjaxResponse,
        getCachedAjax: getCachedAjaxResponse,
        setPreference: setPreference,
        getPreference: getPreference,
        cacheUserData: cacheUserData,
        getCachedUserData: getCachedUserData,
        clearUserCache: clearUserCache
    });

    // Initialize on document ready
    $(document).ready(function() {
        initCacheManager();

        // Clean up cache every 5 minutes
        setInterval(cleanupExpiredCache, 300000);
    });

    console.log('[MyavanaCache] Module loaded');

})(jQuery);
