import AsyncStorage from '@react-native-async-storage/async-storage';
import SQLite from 'react-native-sqlite-storage';

const DB_NAME = 'estrategia_offline.db';

interface SyncQueueItem {
  id?: number;
  endpoint: string;
  method: string;
  data: string;
  timestamp: string;
  retries: number;
}

class SyncServiceClass {
  private db: any = null;
  private isOnline: boolean = true;
  private syncInProgress: boolean = false;

  async init(): Promise<void> {
    this.db = await SQLite.openDatabase({name: DB_NAME, location: 'default'});
    await this.db.executeSql(`
      CREATE TABLE IF NOT EXISTS sync_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        endpoint TEXT NOT NULL,
        method TEXT NOT NULL,
        data TEXT,
        timestamp TEXT NOT NULL,
        retries INTEGER DEFAULT 0
      )
    `);
    await this.db.executeSql(`
      CREATE TABLE IF NOT EXISTS offline_cache (
        cache_key TEXT PRIMARY KEY,
        cache_data TEXT NOT NULL,
        cache_expiry INTEGER NOT NULL
      )
    `);
  }

  async setOnlineStatus(online: boolean): Promise<void> {
    this.isOnline = online;
    if (online) await this.processSyncQueue();
  }

  async addToQueue(item: SyncQueueItem): Promise<void> {
    await this.db.executeSql(
      'INSERT INTO sync_queue (endpoint, method, data, timestamp, retries) VALUES (?, ?, ?, ?, 0)',
      [item.endpoint, item.method, item.data || '', new Date().toISOString()]
    );
  }

  async processSyncQueue(): Promise<{processed: number; failed: number}> {
    if (!this.isOnline || this.syncInProgress) return {processed: 0, failed: 0};
    this.syncInProgress = true;

    const [results] = await this.db.executeSql(
      'SELECT * FROM sync_queue ORDER BY id ASC LIMIT 50'
    );

    let processed = 0, failed = 0;
    const BASE_URL = await AsyncStorage.getItem('api_base_url') || 'http://localhost:81';

    for (let i = 0; i < results.rows.length; i++) {
      const item = results.rows.item(i);
      try {
        const token = await AsyncStorage.getItem('auth_token');
        const response = await fetch(BASE_URL + item.endpoint, {
          method: item.method,
          headers: {
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
          },
          body: item.data || undefined,
        });

        if (response.ok) {
          await this.db.executeSql('DELETE FROM sync_queue WHERE id = ?', [item.id]);
          processed++;
        } else if (response.status >= 500) {
          // Error del servidor - reintentar
          const newRetries = (item.retries || 0) + 1;
          if (newRetries <= 5) {
            await this.db.executeSql('UPDATE sync_queue SET retries = ? WHERE id = ?', [newRetries, item.id]);
          } else {
            await this.db.executeSql('DELETE FROM sync_queue WHERE id = ?', [item.id]);
          }
          failed++;
        } else {
          await this.db.executeSql('DELETE FROM sync_queue WHERE id = ?', [item.id]);
          failed++;
        }
      } catch (e) {
        failed++;
      }
    }

    this.syncInProgress = false;
    return {processed, failed};
  }

  async getQueueSize(): Promise<number> {
    const [results] = await this.db.executeSql('SELECT COUNT(*) as count FROM sync_queue');
    return results.rows.item(0).count;
  }

  async cacheData(key: string, data: any, ttlMinutes: number = 60): Promise<void> {
    const expiry = Date.now() + (ttlMinutes * 60 * 1000);
    await this.db.executeSql(
      'INSERT OR REPLACE INTO offline_cache (cache_key, cache_data, cache_expiry) VALUES (?, ?, ?)',
      [key, JSON.stringify(data), expiry]
    );
  }

  async getCachedData(key: string): Promise<any | null> {
    const [results] = await this.db.executeSql(
      'SELECT cache_data, cache_expiry FROM offline_cache WHERE cache_key = ?',
      [key]
    );
    if (results.rows.length > 0) {
      const row = results.rows.item(0);
      if (row.cache_expiry > Date.now()) {
        return JSON.parse(row.cache_data);
      }
      await this.db.executeSql('DELETE FROM offline_cache WHERE cache_key = ?', [key]);
    }
    return null;
  }

  async clearExpiredCache(): Promise<void> {
    await this.db.executeSql('DELETE FROM offline_cache WHERE cache_expiry < ?', [Date.now()]);
  }
}

const SyncService = new SyncServiceClass();
export default SyncService;
