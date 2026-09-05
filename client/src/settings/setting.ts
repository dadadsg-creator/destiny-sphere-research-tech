/**
 * Порт `setting.as` — Фаза 3.13, источник: docs/client-economy-social-ui.md
 * §0.3. В оригинале — тонкая обёртка над `debug.link.setting_mov`
 * (MovieClip, хранящий данные во Flash `SharedObject`, закомментированная
 * версия реализации видна в `setting.as:45-87`). Ни один из этих ключей не
 * уходит на сервер — чисто клиентские предпочтения. Здесь `SharedObject`
 * заменён на `localStorage` — прямой браузерный аналог (тоже
 * персистентное key-value хранилище per-origin).
 *
 * `file` — область видимости (в оригинале имя файла `SharedObject`, здесь —
 * просто префикс ключа); большинство настроек `settingmenu.as` сохраняются
 * БЕЗ `file` (общие для всех миров), а `advhint`/`arch_*`/`unit_table` и
 * т.п. — per-world, с `file=game.getnameLocalNames()` (см. §0.3 доклада).
 */

const GLOBAL_BUCKET = '_global_';

function storagePrefix(file?: string): string {
  return `ds_setting:${file ?? GLOBAL_BUCKET}:`;
}

function storageKey(name: string, file?: string): string {
  return storagePrefix(file) + name;
}

export function saveSetting(name: string, value: unknown, file?: string): void {
  try {
    localStorage.setItem(storageKey(name, file), JSON.stringify(value));
  } catch {
    /* localStorage недоступен (приватный режим и т.п.) — настройка просто не сохранится, как и в оригинале при сбое SharedObject. */
  }
}

export function loadSetting<T = unknown>(name: string, file?: string): T | undefined {
  try {
    const raw = localStorage.getItem(storageKey(name, file));
    return raw === null ? undefined : (JSON.parse(raw) as T);
  } catch {
    return undefined;
  }
}

export function clearSetting(file?: string): void {
  try {
    const prefix = storagePrefix(file);
    const toRemove: string[] = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key?.startsWith(prefix)) toRemove.push(key);
    }
    toRemove.forEach((key) => localStorage.removeItem(key));
  } catch {
    /* см. saveSetting */
  }
}
