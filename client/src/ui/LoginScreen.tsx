/**
 * Экран входа живого режима — замена логина/пароля, раньше вшитых в сборку
 * из .env.local (VITE_TEST_*). Нужен для выкладки клиента на
 * alpha.destinysphere.ru: всё, что попадает в сборку, видно любому
 * посетителю страницы, поэтому пароль вводится только здесь, в браузере.
 *
 * Два шага, как у настоящего сайта:
 *  1. логин/пароль -> ds/index_login.php (GameSession.login), в ответ —
 *     страница выбора мира, из которой берётся список миров аккаунта;
 *  2. клик по миру -> ds/index_start.php (GameSession.enterWorld).
 *
 * Пароль не сохраняется нигде. В localStorage — только последний логин и
 * последний выбранный uiid, для удобства повторного входа.
 */
import { useState } from 'preact/hooks';
import { GameSession, type WorldEntry } from '../protocol/gameProtocol';
import './login.css';

const LAST_LOGIN_KEY = 'ds.login.lastLogin';
const LAST_UIID_KEY = 'ds.login.lastUiid';

function readStored(key: string): string {
  try {
    return localStorage.getItem(key) ?? '';
  } catch {
    return '';
  }
}

function writeStored(key: string, value: string): void {
  try {
    localStorage.setItem(key, value);
  } catch {
    // Хранилище недоступно (приватный режим и т.п.) — просто не запоминаем.
  }
}

function errorText(err: unknown): string {
  return err instanceof Error ? err.message : String(err);
}

export interface LoginScreenProps {
  /** Вызывается после успешного enterWorld() — сессия готова к sendCommand(). */
  onEntered: (session: GameSession) => void;
}

export function LoginScreen({ onEntered }: LoginScreenProps) {
  const [login, setLogin] = useState(readStored(LAST_LOGIN_KEY));
  const [pass, setPass] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [session, setSession] = useState<GameSession | null>(null);
  const [worlds, setWorlds] = useState<WorldEntry[] | null>(null);
  const lastUiid = readStored(LAST_UIID_KEY);

  const submitCredentials = async (e: Event) => {
    e.preventDefault();
    if (busy || !login || !pass) return;
    setBusy(true);
    setError(null);
    try {
      const s = new GameSession({ baseUrl: '' });
      const list = await s.login(login, pass);
      writeStored(LAST_LOGIN_KEY, login);
      if (list.length === 0) {
        setError('Логин прошёл, но на аккаунте нет доступных миров.');
        return;
      }
      setSession(s);
      setWorlds(list);
      setPass('');
    } catch (err) {
      setError(errorText(err));
    } finally {
      setBusy(false);
    }
  };

  const enter = async (uiid: string) => {
    if (busy || !session) return;
    setBusy(true);
    setError(null);
    try {
      await session.enterWorld(uiid);
      writeStored(LAST_UIID_KEY, uiid);
      onEntered(session);
    } catch (err) {
      // После неудачного входа ck сессии уже может быть недействителен —
      // надёжнее начать заново с логина.
      setError(errorText(err));
      setSession(null);
      setWorlds(null);
    } finally {
      setBusy(false);
    }
  };

  const backToLogin = () => {
    setSession(null);
    setWorlds(null);
    setError(null);
  };

  return (
    <div class="ds-modal-overlay">
      <div class="ds-window ds-login">
        <div class="ds-window__titlebar">
          <span class="ds-window__title">{worlds ? 'Выбор мира' : 'Вход в игру'}</span>
        </div>
        <div class="ds-window__content">
          {worlds ? (
            <>
              <div class="ds-login__worlds">
                {worlds.map((w) => (
                  <button
                    type="button"
                    key={w.uiid}
                    class={`ds-login__world${w.uiid === lastUiid ? ' ds-login__world--last' : ''}`}
                    disabled={busy}
                    onClick={() => void enter(w.uiid)}
                  >
                    {w.name}
                  </button>
                ))}
              </div>
              <div class="ds-popup__buttons">
                <button type="button" class="ds-popup__btn" disabled={busy} onClick={backToLogin}>
                  Другой аккаунт
                </button>
              </div>
            </>
          ) : (
            <form class="ds-login__form" onSubmit={(e) => void submitCredentials(e)}>
              <label class="ds-login__field">
                <span>Логин</span>
                <input
                  type="text"
                  name="login"
                  autocomplete="username"
                  value={login}
                  onInput={(e) => setLogin((e.target as HTMLInputElement).value)}
                  autoFocus={!login}
                />
              </label>
              <label class="ds-login__field">
                <span>Пароль</span>
                <input
                  type="password"
                  name="password"
                  autocomplete="current-password"
                  value={pass}
                  onInput={(e) => setPass((e.target as HTMLInputElement).value)}
                  autoFocus={!!login}
                />
              </label>
              <div class="ds-popup__buttons">
                <button type="submit" class="ds-popup__btn" disabled={busy || !login || !pass}>
                  {busy ? 'Вход…' : 'Войти'}
                </button>
              </div>
            </form>
          )}
          {busy && worlds && <div class="ds-login__status">Входим в мир…</div>}
          {error && <div class="ds-login__error">{error}</div>}
        </div>
      </div>
    </div>
  );
}
