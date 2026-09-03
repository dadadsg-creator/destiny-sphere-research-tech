# Портал входа в игру (`GE_*`, Game Entrance)

> Анализ основан на полном прочтении всех 25 файлов `ds/classes/GE/`:
> `GE_ControlStart`, `GE_DataStartWorldsList`, `GE_DisciplinaryRequest`,
> `GE_ExecChangeMail`, `GE_ExecGetPremiumPresent`, `GE_ExecPremiumSupport`,
> `GE_ExecStartCreateInstance`, `GE_ExecStartGameEnter`, `GE_ExecStartWorldsList`,
> `GE_ExecSupport`, `GE_ExecTurnirRegistration`, `GE_ForumRegistration`,
> `GE_ImageProtection`, `GE_LogicCanEnterWorld`, `GE_LogicCanRegInWorld`,
> `GE_LogicGetPremiumPresent`, `GE_LogicWorldsList`, `GE_Login`, `GE_MetaUALogin`,
> `GE_OutStartWorldsList`, `GE_PasswordChange`, `GE_RamblerLogin`,
> `GE_RamblerRegistration`, `GE_SearchBase`, `GE_SearchStart` — плюс точки входа
> `ds/index_login.php`, `ds/index_start.php`, `ds/reg_wizard.php` (частично) и
> класс `sys/auth.class.php` (`GlobalClassAuth`, привязка meta.ua). Файлы читались
> через `iconv -f CP1251 -t UTF-8`.
>
> Смежные документы, с которыми этот текст сверялся, чтобы не дублировать:
> [protocol-client-server.md, §3](protocol-client-server.md) уже разбирает сам
> веб-логин (`GE_Login`) как первый из трёх протоколов клиент-сервер и отдельно —
> антибот-CAPTCHA «`turing=`» (`ds/antibot.php`, §3.1-3.2), которая **не входит**
> в каталог `GE_*` и здесь не переписывается заново, только упоминается как
> смежный, но независимый механизм (см. §9 ниже). [client-engine-and-protocol.md,
> §8.8](client-engine-and-protocol.md) — серверная сторона VK-логина по
> `schema.sql`, включая честный вывод, что `ds/authorization.php` и
> `GE_KlaseLogin.class.php` физически отсутствуют в архиве. Личный веб-кабинет
> вне Flash-игры (`UC_*`, куда ведёт часть ссылок со страницы входа) — см.
> `docs/user-cabinet.md` (на момент написания этого документа мог быть ещё не
> создан — ссылка по имени). [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md)
> и [mechanics-tournaments.md](mechanics-tournaments.md) уже разбирают отдельные
> операции (`GE_ExecGetPremiumPresent`, `GE_ExecTurnirRegistration`,
> `GE_ExecStartCreateInstance` в турнирной ветке) — здесь эти находки только
> кратко резюмируются со ссылкой, не повторяются целиком.
>
> **Важное следствие чтения этого каталога — см. §11.** При проверке одного из
> файлов (`GE_Login`/`GE_MetaUALogin` тянут за собой `ds/index_login.php`) был
> найден незамеченный предыдущими проходами захардкоженный секрет (ключ
> симметричного шифрования meta.ua SSO). Он заменён на `REDACTED_API_SECRET` во
> всех трёх местах, где встречался, и добавлен в `REDACTED_CREDENTIALS.txt` как
> запись `[T]` — подробности в §11.

## TL;DR

`GE` — это «портал перед игрой»: всё, что происходит **до** того, как игрок
оказался внутри конкретного игрового мира за Flash-клиентом `ds3.swf`, и не
относится к XML-протоколу `ds/useraction.php` ([protocol-client-server.md,
§2](protocol-client-server.md)). Это классический синхронный веб-1.0 слой —
HTML-формы, `Header("Location: …")`-редиректы, Smarty-шаблоны, `session_*` — а
не игровой движок. По функциям `GE` — это четыре связанные, но разделимые вещи:

1. **Логин** (`GE_Login`, `ds/index_login.php`) — логин/пароль, плюс три
   параллельных внешних SSO-ветки (klase.lt, meta.ua, отчасти Rambler ID),
   отдельно от полноценной VK-интеграции, которая живёт **не** в `GE`, а в
   отсутствующем в архиве `ds/authorization.php` (см. §3.5).
2. **Список миров и общая витрина игрока** (`GE_ControlStart` →
   `GE_ExecStartWorldsList` → `GE_DataStartWorldsList`/`GE_OutStartWorldsList`,
   `ds/index_start.php`) — куда игрок попадает сразу после логина: список
   миров для входа/регистрации, премиум-статус, бан, активация e-mail,
   опросник, реферальная ссылка.
3. **Регистрация нового игрового инстанса в конкретном мире**
   (`GE_ExecStartCreateInstance` + `GE_LogicCanRegInWorld`) — создание расы/
   соты, с турнирными и форумными ответвлениями.
4. **Точечные сервисные операции с той же страницы**: смена пароля
   (`GE_PasswordChange`), смена e-mail (`GE_ExecChangeMail`), получение
   премиум-подарка (`GE_ExecGetPremiumPresent`), формы саппорта
   (`GE_ExecSupport`/`GE_ExecPremiumSupport`), «дисциплинарные» жалобы
   (`GE_DisciplinaryRequest`), картиночная CAPTCHA (`GE_ImageProtection`).

Финальный шаг любого успешного логина/регистрации — HTTP-редирект на
`index_start.php` или (после выбора мира) на `detect_player.php`/`index.php`
с параметром `ck` (клиентский анти-replay ключ, тот же, что потом проверяется
на каждой команде игрового XML-протокола — см. [protocol-client-server.md,
§2.1](protocol-client-server.md)) — именно в этот момент embedding Flash-клиента
подхватывает эстафету (см. [frontend-templates.md](frontend-templates.md) §1.2).

## 1. Место `GE` в архитектуре сайта

```text
Браузер (обычный HTML, без Flash)
   │
   ├─ ds/index_login.php ──────────► new GE_Login()
   │                                     │ логин/пароль (VD_User::Validate)
   │                                     │ ИЛИ klase.lt OAuth (GE_KlaseLogin — ОТСУТСТВУЕТ)
   │                                     │ ИЛИ meta.ua SSO (GE_MetaUALogin + GlobalClassAuth)
   │                                     ▼
   │                          $_SESSION[uid/lang/s_login/s_pass/profi_game/…]
   │                          Header: Location: index_start.php?ck=…
   ▼
ds/index_start.php ─────────► new GE_ControlStart()
   │   (требует активную сессию — иначе редирект обратно на index_login.php,
   │    ds/index_start.php:52-55)
   │
   ├─ ?start=…             → GE_ExecStartGameEnter::Exec()  → вход в уже
   │                          зарегистрированный мир → Location: detect_player.php
   │                          / index.php?ck=…  (дальше — Flash, ds3.swf)
   ├─ ?add=…               → GE_ExecStartCreateInstance::Exec() → создание
   │                          инстанса (раса+сота) в новом мире → тот же редирект
   ├─ ?add_premium_present → GE_ExecGetPremiumPresent::Get()
   ├─ ?support=…           → GE_ExecSupport::Exec()
   ├─ ?changemail=…        → GE_ExecChangeMail::Exec()
   ├─ ?submitpoll=…        → GE_SearchStart::SubmitPoll() (портальный опросник)
   │
   └─ (всегда в конце) ────► GE_ExecStartWorldsList::Exec($mes) → рендерит
                              index_start_new.tpl (Smarty) через
                              GE_OutStartWorldsList::FormOut()
```

Ключевая точка стыковки с игровым движком — `GE_ExecStartGameEnter::Exec()`
(`ds/classes/GE/GE_ExecStartGameEnter.class.php:61-195`): именно здесь портал
входа заканчивается и начинается собственно игра — выставляется `$_SESSION`
(раса, мир, сота, `windowKey`, `dbId`) и происходит `Header("Location: …")` на
`detect_player.php` (или сразу `index.php`, если `DISABLE_DETECT_PLAYER`) —
после этого управление переходит к встраиванию Flash (см.
[frontend-templates.md, §1](frontend-templates.md)) и далее к XML-протоколу
`ds/useraction.php` (см. [protocol-client-server.md, §2](protocol-client-server.md)).

**Отношение к личному кабинету (`UC`).** Со страницы `index_start_new.tpl`
есть отдельная ссылка «войти в кабинет», управляемая флагом `CABINET_LOGIN`
(`GE_DataStartWorldsList::GetAllowUserCabinet()`,
`GE_ExecStartWorldsList.class.php:326-359`) — кабинет открыт, только если
**хотя бы в одном** из миров, где у игрока есть инстанс, включена мировая
настройка `ALLOW_USER_CABINET`. Сам вход в кабинет (`UC_ExecLogin` и весь
дальнейший веб-интерфейс личного кабинета — глобальные альянсы, платежи,
переводы между мирами) реализован **не в `GE`**, а в отдельном префиксе `UC`
(84 файла) — см. `docs/user-cabinet.md`. `GE` только решает, показывать ли
саму ссылку на кабинет.

## 2. Таблица всех 25 файлов `ds/classes/GE/`

Файлы следуют условному соглашению именования (не строгий MVC, но
прослеживается): `Control*` — точка входа/роутер, `Data*` — DTO для шаблона,
`Exec*` — исполнитель конкретного действия, `Logic*` — чистая бизнес-логика
без вывода, `Out*` — сборка HTML через Smarty, `Search*` — доступ к БД;
плюс несколько самостоятельных классов вне этой схемы (`GE_Login`,
`GE_PasswordChange`, интеграции с соцсетями, `GE_ImageProtection`).

| Файл | Роль | Кратко |
|---|---|---|
| `GE_ControlStart.class.php` | Control | Роутер `index_start.php`: разбирает `$_REQUEST` (`start`/`add`/`add_premium_present`/`support`/`changemail`/`activationemail`/`submitpoll`) и вызывает соответствующий `Exec*`, в конце всегда рендерит список миров (§4) |
| `GE_Login.class.php` | — (веб-логин) | `ds/index_login.php`: логин/пароль + klase.lt/meta.ua SSO-ветки, старт сессии, редирект на `index_start.php` (§3) |
| `GE_ExecStartWorldsList.class.php` | Exec | Собирает все данные для страницы списка миров (баны, премиум, опросник, подарок и т.д.) в `GE_DataStartWorldsList` |
| `GE_DataStartWorldsList.class.php` | Data | Чистый DTO (сеттеры/геттеры) — данные для шаблона `index_start_new.tpl` |
| `GE_OutStartWorldsList.class.php` | Out | Собственно рендер `index_start_new.tpl` через Smarty, ~30 присваиваемых переменных |
| `GE_LogicWorldsList.class.php` | Logic | `GetWorldsToReg()`/`GetWorldsToEnter()` — фильтрация списка миров по премиум-статусу, `GAME_DOWN`, `ONE_WORLD_REGISTRATION_ONLY`, свободным местам (§4.1) |
| `GE_LogicCanEnterWorld.class.php` | Logic | Тонкая обёртка над `GetWorldsToEnter()` — «разрешено ли войти именно в этот `DBID`/`worldID`» |
| `GE_LogicCanRegInWorld.class.php` | Logic | Аналогично для регистрации — обёртка над `GetWorldsToReg()` |
| `GE_SearchStart.class.php` | Search | Самый большой файл (1006 строк) — ~45 статических методов прямого доступа к PostgreSQL: миры, премиум, Rambler ID, опросник, e-mail и т.д. — общий «фасад» БД, используется почти всеми остальными классами `GE` |
| `GE_SearchBase.class.php` | Search (базовый) | Хранит общий `DS_Common $common`, от него наследует `GE_SearchStart` |
| `GE_ExecStartGameEnter.class.php` | Exec | Вход в уже зарегистрированный мир: валидация инстанса, `windowKey`-локер, редирект на `detect_player.php`/Flash (§6) |
| `GE_ExecStartCreateInstance.class.php` | Exec | Регистрация нового инстанса (раса+сота) в мире, включая турнирную и форумную ветки (§5) |
| `GE_ExecTurnirRegistration.class.php` | Exec | Довешивает к уже созданному турнирному инстансу годовую защиту новичка и регистрацию в реестре миров — см. [mechanics-tournaments.md](mechanics-tournaments.md) |
| `GE_ForumRegistration.class.php` | — | Автоматическая регистрация на форуме (phpBB-подобная схема) — два независимых пути в зависимости от версии сайта (§5.3) |
| `GE_PasswordChange.class.php` | — | Отдельная форма смены пароля (`pass_change.php`?) — MD5 без соли (§7) |
| `GE_ExecChangeMail.class.php` | Exec | Смена e-mail (одна попытка на аккаунт) + письмо активации через Smarty (§7.2) |
| `GE_ExecGetPremiumPresent.class.php` | Exec | Разовый премиум-подарок (обычный + юбилейный «5-летие» + отдельный VK-вариант, вероятно мёртвый — §7.3) |
| `GE_LogicGetPremiumPresent.class.php` | Logic | Проверки «уже брал / не брал» подарок, читает `ds_present` |
| `GE_ExecSupport.class.php` | Exec | Обычная форма саппорта — правильно различает премиум/не премиум лимиты и e-mail адресата |
| `GE_ExecPremiumSupport.class.php` | Exec | Отдельная «премиум»-версия формы саппорта — **мёртвый код**, нет ни одного вызывающего места во всём `ds/` (§7.4) |
| `GE_DisciplinaryRequest.class.php` | — | Многостраничная форма жалоб (мультиаккаунты, кража аккаунта, «просьба о ситтинге», мат, общие вопросы) — email на `dvsupport@destinysphere.com`, вложения-скриншоты (§7.5) |
| `GE_ImageProtection.class.php` | — | Классическая картиночная CAPTCHA (сторонняя библиотека PhpCaptcha 2005 г., GD) — независима от антибот-«turing» (§8) |
| `GE_RamblerLogin.class.php` | — | Интеграция с Rambler ID (устаревший `auth.cgi`/актуальный `int-id.rambler.ru`) (§6.3) |
| `GE_RamblerRegistration.class.php` | — | **Пустая заглушка** — конструктор и два приватных метода без единой строки логики (§6.3) |
| `GE_MetaUALogin.class.php` | — | Обёртка входа для украинского портала meta.ua поверх `GlobalClassAuth` (`sys/auth.class.php`) (§6.2) |

## 3. Флоу веб-логина (`GE_Login`, `ds/index_login.php`)

Детальный разбор самого класса `GE_Login` (конструктор, `_execLogin()`,
`_reciveParams()`, `showLoginForm()`) уже сделан в
[protocol-client-server.md, §3](protocol-client-server.md) — сюда он не
переносится дословно, только резюме плюс то, что не попало в первый проход:
три SSO-ветки.

### 3.1. Обычный логин/пароль

`GE_Login::_getUIDByLoginAndPassword()` (`GE_Login.class.php:116-140`) →
`VD_User::Validate($login, $password, $common)`. Внутри — **`ds_user.password`
хранится как обычный `md5($password)` без соли**
(`ds/classes/VD/VD_User.class.php:17-20`, буквально `password = '%s'` со
значением `md5($password)`), сравнение делается прямо в SQL-запросе. Это же
подтверждается на стороне смены пароля — см. §7.1. При успехе — заполнение
`$_SESSION` (`uid`, `lang`, `s_login`, `s_pass`, `profi_game` и т.д.,
`GE_Login.class.php:249-299`) и редирект `Location: index_start.php?ck=…`.

### 3.2. Klase.lt (Литва) — `_checkKlaseLogin()`

Определяется по `HTTP_HOST` (`ds.klase.lt`), классический OAuth 1.0a: если нет
`$_REQUEST['cb']` — вызывает глобальную функцию `GetToken($callback_url)`
(не найдена ни в одном классе, вероятно из `sys/OAuth.php`, который
подключается только в `index_login.php:15` именно для этого хоста); при
обратном вызове — `GetUserData()`, затем `new GE_KlaseLogin($token)`.
**Как уже задокументировано в
[client-engine-and-protocol.md, §8.8](client-engine-and-protocol.md) —
`GE_KlaseLogin.class.php` физически отсутствует в архиве**, несмотря на
прямую ссылку в `GE_Login.class.php:167,180`. Не восстанавливается —
только подтверждается отсутствие (см. §11 и общий чеклист `docs/README.md`).

### 3.3. Meta.ua (Украина) — `_checkMetaUALogin()` / `GE_MetaUALogin`

Определяется по `HTTP_HOST` (`ds.meta.ua`), но, в отличие от klase.lt,
**не заглушка** — реализация есть целиком:

1. `ds/index_login.php:19-24` — если хост `ds.meta.ua`, подключает
   `sys/auth.class.php` и сразу же (до какого-либо запроса пользователя)
   создаёт `new GlobalClassAuth($secret, "destinysphere")` — сторонняя
   (авторства `Ivan Sologub <fiva@meta.ua>`) реализация центральной SSO
   («паспорта») meta.ua через 3DES-шифрованную куку `lauth` на домене
   `.meta.ua` (`sys/auth.class.php`, класс `GlobalClassCrypt`/`GlobalClassAuth`,
   `mcrypt` tripledes/cfb). Тот же вызов продублирован в `ds/reg_wizard.php:31`
   и `sys_dyn_components/LoginFormU.php:29`.
2. Если кука/SSO валидны — объект несёт `$sUserName` (это и есть
   `$metaua_user`, передаётся в `GE_Login` через `global $metaua_user`).
3. `GE_Login::_checkMetaUALogin()` (`GE_Login.class.php:186-217`) заворачивает
   его в `new GE_MetaUALogin($metaua_user)` → `execLogin()`
   (`GE_MetaUALogin.class.php:29-54`) → `GE_SearchStart::GetUserIdByMetaUALogin()`
   ищет `game_uid` в таблице `ds_oauthinfo` по `token_username`.
4. **Важно (сверка с client-engine-and-protocol.md §8.8):** таблица
   `ds_oauthinfo` обслуживает **только** klase.lt и meta.ua — у VKontakte с
   ней нет ничего общего (там отдельная колонка `ds_user.id_user_vkontakte`).
   Этот документ подтверждает вторую половину той находки буквально на уровне
   вызывающего кода `GE_Login`/`GE_MetaUALogin`.

### 3.4. Rambler ID — отдельный, не встроенный в `GE_Login` путь

В отличие от klase.lt/meta.ua, интеграция с Rambler ID (`GE_RamblerLogin`) **не
вызывается изнутри `GE_Login`** — судя по коду, это отдельный, самостоятельный
класс, предназначенный для вызова с какой-то другой точки входа (не найденной
в периметре этой задачи — вероятно, отдельный скрипт вроде `rambler_login.php`,
не входящий в `ds/classes/GE/`). `GE_RamblerLogin::__construct()`
(`GE_RamblerLogin.class.php:98-110`) читает куку/параметр `rsid`, проверяет его
через HTTP-запрос к `int-id.rambler.ru/check-rsid/<IP>/<rsid>`
(`_getRamblerID()`, `:233-260` — заменяет устаревший `auth.cgi`, что
подтверждено комментарием разработчика прямо в файле: «ЗАМЕНЕН НА int-id.rambler.ru
1 февраля 2010 г»), парсит tab-separated ответ, достаёт `LOGIN` (e-mail) и ищет
пользователя по e-mail (`GE_SearchStart::GetUserIdByEMail()`). Отдельно есть
таблица `ds_ramblerlogin` с двумя булевыми флагами
(`login_enable`/`registration_enable`) на конкретный Rambler-логин —
административный способ точечно забанить вход/регистрацию через Rambler
(`DisableRamblerLogin`/`DisableRamblerRegistration`,
`GE_SearchStart.class.php:624-662`) без бана самого игрового аккаунта.

`GE_RamblerRegistration.class.php` — **полностью пустая заготовка**:
конструктор принимает `array $data`, вызывает `_getParams()`, но оба приватных
метода (`_getParams`, `_out`) буквально пусты (`{ }`, без единой строки кода,
`GE_RamblerRegistration.class.php:5-18`). Похоже на незавершённую попытку
довести регистрацию через Rambler ID до отдельного флоу (в духе
`GE_ExecStartCreateInstance`), брошенную на стадии заготовки.

### 3.5. VKontakte — не часть `GE` вообще

Важное разграничение, которое легко упустить: несмотря на то, что klase.lt и
meta.ua интегрированы прямо в `GE_Login`, **вход через ВКонтакте устроен
принципиально иначе** и не проходит ни через `GE_Login`, ни через какой-либо
другой файл `ds/classes/GE/`. Как показано в
[client-engine-and-protocol.md, §8.8](client-engine-and-protocol.md), клиентский
`VKGATE.as` обращается напрямую к `http://vk.destinysphere.ru/ds/authorization.php`
— отдельному, физически отсутствующему в архиве скрипту, не связанному с
`index_login.php`/`GE_Login`. Единственные точки, где `GE` **потребляет**
результат этой (нигде не прочитанной) VK-авторизации — `DE_User`/`DS_User` со
стороны данных (`id_user_vkontakte`) и:

- `GE_ExecGetPremiumPresent::GetPremPresentVk()`
  (`GE_ExecGetPremiumPresent.class.php:104-146`) — **обнаружена дополнительная
  проблема этой сессии**: метод вызывает `GE_SearchStart::CanUserGetPremPresent()`,
  `GE_SearchStart::IfPremPresentIsReceived()` и `GE_SearchStart::GetPremPresentCount()`
  — ни один из этих трёх методов не определён ни в `GE_SearchStart.class.php`,
  ни где-либо ещё в `old-sphere/` (проверено `grep` по всему дереву). Раз ни
  одного вызывающего места для самого `GetPremPresentVk()` тоже не найдено
  (см. ту же находку в client-engine-and-protocol.md §8.8), это, по всей
  видимости, целиком мёртвый и вдобавок **сломанный** (упал бы фатальной
  ошибкой `Call to undefined method`, если бы вообще выполнился) фрагмент кода
  промо-акции для конкретного VK-приложения — не переносить в ремастер.

## 4. Список миров и стартовая витрина (`GE_ExecStartWorldsList`)

`GE_ControlStart` в конце **любого** запроса к `index_start.php` вызывает
`GE_ExecStartWorldsList::Exec($mes, $idQuestion)`
(`GE_ControlStart.class.php:124`) — эта витрина есть всегда, независимо от
того, что ещё произошло по запросу (вход, регистрация, смена пароля и т.п.).
`Exec()` (`GE_ExecStartWorldsList.class.php:50-143`) последовательно
наполняет `GE_DataStartWorldsList` пятнадцатью блоками данных:

| Блок | Метод | Источник |
|---|---|---|
| Бан пользователя | `_checkUserBan()` | `DE_User::isActive()`/`getBanReason()` |
| Активация e-mail | `_checkMailActivation()` | `constant('ACTIVATION_EMAIL_ENABLED')`, `CAN_ENTER_WITHOUT_ACTIVATION` дней грейс-периода |
| Логин/e-mail игрока | `_getUserInfo()` | `DE_User` |
| Миры для входа | `_getWorldsToEnter()` | `GE_LogicWorldsList::GetWorldsToEnter()` (§4.1) |
| Миры для регистрации | `_getWorldsToReg()` | `GE_LogicWorldsList::GetWorldsToReg()` (§4.1) |
| Список рас | `_getRaceList()` | `ds_race` + локализация |
| Все миры (справочно) | `_getAllWorlds()` | `GE_SearchStart::GetWorldCollection()` |
| Оставшиеся дни премиума | `_getProfiData()` | `ds_userprofidata.enddate` |
| Доступность подарка | `_getPresentData()` | `GE_LogicGetPremiumPresent` |
| Прошлая неудачная регистрация | `_getPrevRegInfo()` | `$_SESSION['reg_world'/'reg_race'/'reg_combname']` |
| Анти-CSRF ключ | `_getClientKey()` | `$_SESSION['client_key']` |
| Доступность смены e-mail | `_getChangeMailInfo()` | `GE_SearchStart::GetCountEmailChanges()` — только 1 раз за всё время |
| Доступ в кабинет | `_checkAllowUserCabinet()` | мировая настройка `ALLOW_USER_CABINET`, см. §1 |
| Хелп для новичков | `_checkNewUserHelp()` | `ds_user_help_show`, счётчик показов декрементируется при каждом показе |
| Опросник + баннер | `_checkCurrentPoll()`/`_getBannerData()` | `ds_poll_*`, партнёрский баннер только для `VERSION_NAME IN (LOCAL, ITALY)` и `partner_id=2` |

Финальный рендер — `GE_OutStartWorldsList::FormOut()`
(`GE_OutStartWorldsList.class.php:13-106`) — Smarty-шаблон
`index_start_new.tpl`, ~30 присваиваемых переменных, включая реферальную
ссылку `reg_wizard.php?ur=<UID>` (`:85-86`).

### 4.1. Фильтрация списка миров (`GE_LogicWorldsList`)

`GetWorldsToEnter($user, $isProfy)` (`GE_LogicWorldsList.class.php:106-155`) —
миры, где у игрока уже есть инстанс (`ds_userinstancecollection`), с пометкой
`game_down` (по `GameDown()` + статусу мира `CLOSE`), после чего для
не-админов выкидывает премиумные миры без индивидуального разрешения
(`ds_usersallowedcollection`).

`GetWorldsToReg($user, $isProfy, $userHasInstances)` (`:16-104`) — миры, где
игрока ещё нет, с более сложной цепочкой фильтров: `GAME_DOWN`/статусы
`CLOSE`/`REGISTRATION_CLOSE`/`TURNIR_STARTED`, премиум-only миры, затем (если
`constant('ONE_WORLD_REGISTRATION_ONLY')` включён и у игрока уже есть
инстансы) — жёсткое ограничение «только премиумные/специально разрешённые
миры», с явным списком мировых **исключений по `world_identifier`**
(`EDN`/`ORN`/`ZME`/`VSV`/`TRN` — расшифровка кодов не установлена, см. §9), и
наконец — фильтр по наличию свободных мест (`max_users <= 0`).

## 5. Регистрация нового инстанса (`GE_ExecStartCreateInstance`)

`Exec($data, $redirect = true, $turnirReg = false)`
(`GE_ExecStartCreateInstance.class.php:72-495`) — самый длинный
однофункциональный метод во всём каталоге `GE`. Основной путь:

1. Разбор `world` (`worldID_DBID`), `race` (0-3), `comb` (имя соты) из
   `$_REQUEST`; **защитный код (`GE_ImageProtection::Validate`) для
   регистрации закомментирован целиком** (`:115-121`) — см. §8 про CAPTCHA.
2. `GE_LogicCanRegInWorld::Test()` — можно ли вообще регистрироваться в этом
   мире (пропускается для `$turnirReg == true`, см.
   [mechanics-tournaments.md](mechanics-tournaments.md)).
3. `VD_CombName::Validate()` — валидация имени соты.
4. `GE_SearchStart::GetFreeCombs($worldID, $race)` — есть ли вообще свободная
   сота нужной расы на базовом кольце этой расы (`ds_racehasbasering`).
5. **Развилка создания инстанса** по игровому модификатору
   `COMB_CREATE_FROM_TEMPLATES` (`GE_SearchStart::GetCombTemplateModifier()`):
   - если включён — клонирование стартовой соты из шаблона через
     `TN_Search::GetCombTemplate()` + `SI_ControlApplyTemplate::Apply()`
     (турнирный/шаблонный механизм, см.
     [mechanics-tournaments.md, §…](mechanics-tournaments.md) и
     [mechanics-world-transfer.md](mechanics-world-transfer.md));
   - иначе — обычное создание через `WorldUtils::addNewInstance()`
     (базовый инфраструктурный класс, см. [game-engine.md](game-engine.md) §4).
6. Годовой премиум новому игроку на первом/втором инстансе (`< 2` строк в
   `ds_userinstancecollection`) — этот же механизм уже разобран в
   [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md)
   («Годовой премиум при создании инстанса»), здесь не повторяется.
7. Автоматическая регистрация на форуме — `GE_ForumRegistration` (§5.3).
8. Финальный редирект: `Location: index_start.php?ck=…&uiid=<newUIID>_<dbId>_<world>`.

### 5.1. Дефект: неопределённые переменные в турнирной ветке

Отдельный блок кода (`GE_ExecStartCreateInstance.class.php:204-249`),
срабатывающий при мировом модификаторе `TURNIR_WORLD == 1`, содержит **баг**:

```php
if ($DBID != 0) {                                    // GE_ExecStartCreateInstance.class.php:225
    ...
    $sql = "INSERT INTO ds_userinstancecollection
            (id_user, id_userinstance, id_world, dbname, admin, id_worldcollection)
            SELECT
                ".$userId.",              // :233 — должно быть self::$UID
                ".$newUIID.",
                ".$worldID.",
            ...
            WHERE
                dbname = '".$turnirDbName."'
                AND
                id_world = ".$worldId;    // :244 — должно быть self::$worldID
```

`$DBID` на строке 225, `$userId` на строке 233 и `$worldId` на строке 244 —
это **не** те же имена, что у статических свойств класса, где реально хранятся
эти значения (`self::$DBID`, `self::$UID`, `self::$worldID` — без `self::` и,
для `$userId`/`$worldId`, ещё и с другим регистром последних букв), и нигде в
самом методе как локальные переменные не присваиваются. В PHP это не фатальная
ошибка (только
`E_NOTICE`), но `$DBID` резолвится в `NULL`, а `NULL != 0` в PHP при нестрогом
сравнении — `false` (`null == 0` истинно). **Следствие: весь блок
`if ($DBID != 0) { … }` никогда не выполняется** — при регистрации в турнирном
мире, физически расположенном не на мастер-БД (`dbId != 0`), запись в
`ds_userinstancecollection` мастер-базы, дающая доступ «заходить в этот мир»,
не вставляется этим путём. Не установлено (вне периметра этой задачи), чинит
ли эту дыру какой-то другой код (например, тот же `WorldUtils::addNewInstance()`
или `SI_ControlApplyTemplate::Apply()` из шага 5 выше, вызываемые чуть раньше в
том же методе) — возможно, поэтому баг никогда не проявлялся на практике.

### 5.2. Годовой доступ и `ds_userprofidata` — без источника trial-флага

Тот же блок (`:251-302`) при `< 2` записей в `ds_userinstancecollection`
напрямую пишет `INSERT`/`UPDATE` в `ds_userprofidata` с `enddate = now() +
365 days`, **не указывая колонку `trial`** — уже отмечено как открытый вопрос
в [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md).
Дополнительно этот же файл показывает, что старая логика прямой выдачи
2000 серебра новому игроку (`ds_money_account`) **закомментирована целиком**
(`:272-292`, помечена в самом коде как «убрана»).

### 5.3. Автоматическая регистрация на форуме (`GE_ForumRegistration`)

Два независимых, взаимоисключающих пути (`GE_ForumRegistration::forumRegistration()`,
`GE_ForumRegistration.class.php:4-90`):

- **Литовская версия** (`DEFAULT_LANGUAGE_SHORT == 'L'`,
  `GE_ForumRegistration.class.php:7`, комментарий разработчика буквально
  «для Литвы особый случай») — прямая работа с MySQL-таблицами
  `<FORUM_PREFIX>_groups`/`<FORUM_PREFIX>_users`/`<FORUM_PREFIX>_user_group`
  через `DS_MySQLWork::Singleton()->connect('FORUM')` — классическая ручная
  вставка пользователя phpBB-подобной схемы форума прямо из игрового кода,
  включая генерацию `group_id`/`user_id` через `SELECT max(...)` (без
  транзакционной защиты от гонки, если два игрока регистрируются
  одновременно — не проверялось, есть ли внешняя блокировка).
- **Остальные версии** (`FORUM_REGISTRATION_THROUGH_GATE == 1`,
  `UnlimForumRegistration()`, `:92-151`) — не прямая работа с БД форума, а
  HTTP `POST`-запрос на `constant('URL_FORUM_REGISTRATION_GATE')` (внешний
  гейт-скрипт форума), с `check => md5('FORUM'.date('Y-m'))` как примитивной
  формой подписи запроса (без секретного ключа — это не HMAC, просто
  предсказуемый по месяцу хэш; технически не «секрет» в смысле
  `REDACTED_API_SECRET`, но и не полноценная защита от подделки запроса, если
  гейт публично доступен). При пустом ответе гейта — заглушка `$forumUserId =
  99999999` («затыка, на случай когда тормозит гейт регистрации на форуме»,
  комментарий разработчика, `:146`).

Оба пути вызываются из `GE_ExecStartCreateInstance` только при `VERSION_NAME
!= 'LIMITED'` (`:272,294`) — то есть автоматической регистрации на форуме нет
у версии Unlimited.

## 6. Вход в конкретный мир (`GE_ExecStartGameEnter`)

`Exec($data)` (`GE_ExecStartGameEnter.class.php:61-195`) обрабатывает
`?start=…&uiid=<UIID>_<DBID>_<idWorld>`:

1. Если включён `$GLOBALS['INDEX_START_BLOCKS']['PROTECTION_CODE']` —
   проверка картиночной CAPTCHA (`GE_ImageProtection::Validate`, §8). По
   умолчанию в снятом конфиге этот флаг **выключен**
   (`ds/config/settings_secondary.php:147`, `'PROTECTION_CODE' => 0`) — то
   есть в текущем снимке кода вход в мир вообще не требует CAPTCHA.
2. `VD_UserInstance::Validate()` — что `UIID` действительно принадлежит
   текущему `UID`.
3. **Анти-параллельность**: `_updateWindowKey()` — случайное 2-значное число
   `windowKey`, кладётся и в `$_SESSION`, и в таблицу
   `ds_userinstancewindowkey` (`GE_SearchStart::UpdateWindowKey()`) — судя по
   названию и месту, используется где-то в игровом клиенте/сервере для
   различения нескольких открытых вкладок одного игрока (сам механизм
   использования `windowKey` вне `GE` не разбирался в периметре этой задачи).
4. `GE_LogicCanEnterWorld::Test()` — финальная проверка прав на вход.
5. `ME_LoginHistory::Exec()` — та же история входов, что и в `GE_Login::_execLogin()`.
6. `TMP_NewYear::givePresent($ui)` — тот же новогодний подарок 2013/2014, что
   уже разобран в [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md)
   (там — вариант при веб-логине, здесь — вариант непосредственно при входе в
   мир; оба вызываются из разных мест `GE`).
7. Выбор чата: если у мира включён `CHAT_ENABLE`, добавляет `&sport=<port>` к
   URL редиректа (`DS_World::GetPortByWorldId()`) — это тот же самый
   TCP-порт внешнего чат-сервера, уже разобранный в
   [admin-panel.md, §2.4.1](admin-panel.md).
8. Финальный `Header("Location: ../<путь-версии>/detect_player.php?ck=…&VERS=…[&sport=…]")`
   (или сразу `index.php`, если `DISABLE_DETECT_PLAYER`) — передаёт эстафету
   Flash-встраиванию.

## 7. Точечные сервисные операции

### 7.1. Смена пароля (`GE_PasswordChange`)

Отдельная форма (не через `GE_ControlStart` — судя по прямому использованию
`Smarty`/собственному `_out()`, скорее всего отдельная точка входа вроде
`pass_change.php`, не найденная в периметре этой задачи). Проверка старого
пароля — тот же `VD_User::Validate()`, что и обычный логин. Новый пароль:
`VD_Password::Validate()` (формальные требования — не разбирался построчно),
запрет совпадения со старым, обязательное подтверждение. Сохранение —
`GE_SearchStart::ChangeUserPassword()` — **буквально `md5($newPass)` без
соли** (`GE_SearchStart.class.php:69-85`), тот же алгоритм, что и при
исходной регистрации (см. §3.1). `GE_SearchStart::LogPasswordChange()`
(вставка в `ds_password_change`) присутствует в коде, но сам вызов
**закомментирован** (`:99-100`) — история смены пароля нигде реально не
пишется этим путём.

### 7.2. Смена e-mail (`GE_ExecChangeMail`)

Разрешена **ровно один раз за всё время жизни аккаунта**
(`GE_SearchStart::GetCountEmailChanges()` — считает записи в
`ds_userdata_change_log`, `GE_ExecChangeMail.class.php:33-36`). После смены —
письмо активации через Smarty-шаблон `activate_latter.tpl` со ссылкой
`index_registration.php?action=activate&key=md5(<логин>)` (`:60`) — ключ
активации детерминирован по логину, а не случаен (тот же паттерн, что и
исходное письмо регистрации `reg_latter.tpl`, использующее ту же формулу
`md5($user->getName())`).

### 7.3. Премиум-подарок (`GE_ExecGetPremiumPresent`/`GE_LogicGetPremiumPresent`)

Основной механизм (`Get()`, обычный `PREMIUM_PRESENT_DAYS=1` день, либо
5-дневный «юбилейный», если ещё не брали — `add_date > '2010-09-01'`) уже
разобран в [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md);
здесь только отмечается связанный `GetPremPresentVk()` — отдельный, судя по
всему, полностью мёртвый и сломанный VK-вариант (см. §3.5).

### 7.4. Формы саппорта — правильная и мёртвая параллельная версия

`GE_ExecSupport::Exec()` (`GE_ExecSupport.class.php:5-81`) — рабочая версия,
доступная с общей страницы входа (`?support=…`, роутится из
`GE_ControlStart.class.php:85-87`): корректно различает премиум/непремиум
(с поправкой на сессионный триал — если премиум получен **только** через
`$_SESSION['after_reg_trial']`, считается непремиумом при выборе лимита/адреса,
уже отмечено в [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md)),
шлёт письмо **дважды** — на фиксированный `dada@destinysphere.com` (без
разбора премиум/не премиум, `:71`) и на адрес по правилу выше, логирует в
`GE_SearchStart::SetSupportRequest()`.

`GE_ExecPremiumSupport::Exec()` (`GE_ExecPremiumSupport.class.php:5-56`) —
**отдельный класс с похожим, но не идентичным назначением, никем не
вызываемый нигде в `old-sphere/`** (проверено `grep -rl GE_ExecPremiumSupport`
по всему дереву — единственное совпадение — сам файл определения). Не
маршрутизируется из `GE_ControlStart` (там для `support` жёстко вызывается
`GE_ExecSupport`, без ветвления на премиум/непремиум класс). Внутри —
дополнительный дефект, даже если бы код когда-то вызывался: файл сам
переопределяет глобальную константу `define('MAX_SUPPORT_MESSAGES', 10)`
(`:3`), но проверяет лимит именно по этой константе
(`constant('MAX_SUPPORT_MESSAGES')`, `:40`) — **не** по
`MAX_PREMIUM_SUPPORT_MESSAGES`, хотя класс называется «Premium». Хуже того,
глобальный конфиг уже определяет `MAX_SUPPORT_MESSAGES = 3`
(`ds/config/settings.php:354`, для обычных пользователей) раньше, чем
подключился бы этот файл класса — а PHP `define()` не переопределяет уже
существующую константу — то есть даже собственный `define()` этого файла
никогда не сработал бы. **Итог: `GE_ExecPremiumSupport` — код, который (а) не
вызывается, (б) даже если бы вызывался, из-за коллизии имени константы дал бы
премиум-пользователям более низкий лимит сообщений (3), чем указано в его же
собственном (недостижимом) `define()`.** Живая, корректная логика для
премиум-саппорта — целиком в `GE_ExecSupport` (см. выше).

### 7.5. Дисциплинарные жалобы (`GE_DisciplinaryRequest`)

Отдельная многошаговая Smarty-форма (`dv_request_select.tpl` →
`desciplinary_request.tpl`) для шести типов обращений — таблица
`self::$dataMask` (`GE_DisciplinaryRequest.class.php:29-42`) задаёт обязательные
поля на каждый тип:

| Тип | Обязательные поля | Смысл |
|---|---|---|
| `Mat` | User_Name, Game_World, Date, Text, Violation_Type, Email | Жалоба на нарушение (мат/оскорбления) |
| `MultiAccount` | User_Name, Game_World, Reason, Email | Жалоба на мультиаккаунтинг |
| `SittingRequest` | User_Name_For_Sitting, Sitter_User_name, Sitting_Comb/World/Start/End_Date, Read_Rules, Email | Официальная просьба разрешить «ситтинг» (временную передачу управления аккаунтом другому игроку) |
| `AccountSteal` | User_Name, User_Comb, Lost_List, User_Know_Password, Email | Заявление о краже аккаунта |
| `Same_IP` | User_List, Constant_Play, Email, Read_Rules_Same_Comp | Жалоба/декларация игры с одного IP |
| `Other` | Email, Additional_Info | Прочее |

Лимит — **7 заявок за 7 дней** на пользователя (`_checkLimit()`,
`GE_DisciplinaryRequest.class.php:321-343`, читает `ds_dv_request`), письмо
уходит на `dvsupport@destinysphere.com` (не тот же адрес, что у обычного
саппорта из §7.4), с поддержкой вложений-скриншотов (до 500 КБ суммарно,
`gif`/`jpg`/`jpeg`/`png`/`bmp`, MIME multipart вручную собирается в
`_send_mail()`). Каждая заявка также логируется в БД (`_logRequest()`,
`ds_dv_request`, включая полный `print_r($data)` — то есть в этой таблице
может осесть содержимое любых полей формы, не только текстовых).

Побочная находка для открытого вопроса из
[client-engine-and-protocol.md, §9, п.12](client-engine-and-protocol.md)
(является ли `SIDIX` именем сессионной куки веб-логина): список
исключаемых из письма служебных полей формы включает буквально `'SIDIX'`
(`GE_DisciplinaryRequest.class.php:262`, наряду с `hotlog`, `cnt`, `ck`) — то
есть поле `SIDIX` реально приходит в `$_REQUEST` на страницах портала вне
Flash-игры. Это не подтверждает и не опровергает гипотезу «`SIDIX` = имя
сессионной куки» напрямую (это может быть просто общий счётчик/трекинг-параметр,
добавляемый шаблонами сайта повсеместно), но является дополнительным
подтверждением, что `SIDIX` — не специфичное для игрового XML-протокола имя,
а нечто вездесущее на портале.

## 8. Картиночная CAPTCHA (`GE_ImageProtection`) — отдельный механизм, не «turing»

`GE_ImageProtection.class.php` — адаптация стороннего класса **PhpCaptcha**
(Edward Eliot, 2005, лицензия Freeware с сохранением копирайта — сохранена в
шапке файла) поверх GD: классическая картинка с зашумлёнными линиями и
искажённым текстом (`imagefttext`, TrueType-шрифты, случайный угол/размер/цвет),
код хранится как `md5(strtoupper($code))` в `$_SESSION['php_captcha']`
(`GenerateCode()`, `:202-222`), сверяется в статическом `Validate($userCode)`
(`:368-383`), с одноразовым сбросом после успешной проверки.

**Это не то же самое, что антибот «`turing=`»**, уже подробно
задокументированный в [protocol-client-server.md, §3.1-3.2](protocol-client-server.md)
(`ds/antibot.php`, вопрос-ответ по ходу игровой сессии, вне `GE`). Различия:

| | `GE_ImageProtection` | `turing=` / `ds/antibot.php` |
|---|---|---|
| Тип | Классическая image-CAPTCHA (искажённый текст) | Текстовый вопрос-ответ («живой юзер») |
| Когда проверяется | При входе в уже зарегистрированный мир (`GE_ExecStartGameEnter`), **если** включён флаг | Периодически во время игровой сессии, по таймеру `delta` |
| Где живёт код проверки | `ds/classes/GE/` | `ds/antibot.php` + `ds/classes/DS/DS_Question`/`DS_Botlist` |
| Статус в снятом конфиге | **Выключен** (`INDEX_START_BLOCKS['PROTECTION_CODE'] = 0`, `settings_secondary.php:147`) и явно закомментирован для регистрации (`GE_ExecStartCreateInstance.class.php:115-121`, см. §5) | Включается per-мир флагом `ENABLE_ANTIBOT` (независимый механизм) |

Итого: в снятом снимке кода **регистрация нового игрока и вход в мир не
защищены картиночной CAPTCHA практически нигде** — код есть и рабочий, но
либо отключён конфигом, либо явно закомментирован в вызывающем месте. Это не
интерпретируется как «баг» (решение могло быть намеренным — например, в
пользу антибот-вопросов из `ds/antibot.php` как основной защиты), но стоит
явно зафиксировать при проектировании ремастера.

## 9. Таблицы БД, задействованные `GE` напрямую

Не претендует на полноту — только таблицы, встреченные буквально в SQL внутри
прочитанных файлов `GE_SearchStart`/`GE_LogicWorldsList`/др. (структура — по
`schema.sql`, см. оговорку о его возможном расхождении с живой БД в
[docs/README.md](README.md)):

| Таблица | Используется для |
|---|---|
| `ds_user` | Логин/пароль (`md5`), e-mail, флаг активации |
| `ds_userdata_change_log` | История смен пароля/e-mail (для лимита «1 раз») |
| `ds_worldcollection` | Мастер-реестр миров (все версии) — статус, `profi`, `max_users` |
| `ds_userinstancecollection` | Связь игрок↔мир (в каких мирах уже зарегистрирован) |
| `ds_usersallowedcollection` | Индивидуальные исключения из премиум/лимитных ограничений на конкретный мир |
| `ds_multiworldaccess` | Флаг «доступ сразу к нескольким мирам», обходит `ONE_WORLD_REGISTRATION_ONLY` |
| `ds_worldstatus` | Текстовые статусы мира (`CLOSE`/`REGISTRATION_CLOSE`/`TURNIR_STARTED`/…) |
| `ds_userprofidata` | Премиум-подписка игрока (`enddate`, `profi`) |
| `ds_present` | Учёт выданных подарков (`type=2` — премиум-подарок) |
| `ds_oauthinfo` | Токены klase.lt/meta.ua (**не** VK, см. §3.3) |
| `ds_ramblerlogin` | Индивидуальные бан-флаги входа/регистрации через Rambler ID |
| `ds_password_change` | Таблица есть, но запись в неё закомментирована (см. §7.1) |
| `ds_userinstancewindowkey` | Анти-параллельность окон при входе (`windowKey`) |
| `ds_user_help_show` | Счётчик показов хелпа новичкам |
| `ds_poll_question`/`ds_poll_answer`/`ds_poll_result`/`ds_poll_question_worlds` | Портальный опросник на странице входа |
| `ds_dv_request` | Лог дисциплинарных жалоб (§7.5) |
| `ds_racehasbasering` | Какое кольцо — «домашнее» для расы (для поиска свободной соты при регистрации) |
| `ds_gamemodifier` | Модификаторы мира (`COMB_CREATE_FROM_TEMPLATES`, `TURNIR_WORLD` и т.д.) |

## 10. Кто вызывает `GE` извне каталога

Помимо самих точек входа (`index_login.php`, `index_start.php`, отчасти
`reg_wizard.php`), `GE`-классы используются точечно из других подсистем —
подтверждает роль `GE` как «общего фасада для до-игровых операций»:

- `mechanics-events-turns.md` — регистрация нового инстанса при заселении NPC
  ссылается на `GE_ExecStartCreateInstance` как на общий механизм создания
  инстанса.
- `mechanics-tournaments.md` — турнирный движок (`TN_*`, `Admin/…/Turnir/`)
  вызывает `GE_ExecStartCreateInstance::Exec($data, false, true)` напрямую
  (третий параметр отключает обычную проверку регистрации) и
  `GE_ExecTurnirRegistration::Add(...)`.
- `sys_dyn_components/LoginFormU.php`, `ds/reg_wizard.php` — используют тот же
  `GlobalClassAuth`/`GE_MetaUALogin`, что и `index_login.php` (см. §3.3, §11).

## 11. Находка по безопасности: незамеченный секрет meta.ua SSO (исправлено в этой сессии)

При чтении `ds/index_login.php` (подключается из `GE_Login`) обнаружен
хардкоженный ключ, ранее не пойманный ни одним из предыдущих проходов по
секретам (задокументированных в `REDACTED_CREDENTIALS.txt`):

```php
$metaua_user = new GlobalClassAuth("REDACTED_API_SECRET", "destinysphere");
```

Несмотря на то, что параметр в определении класса называется `$sPublicKey`
(`sys/auth.class.php`), по факту он используется как **симметричный ключ
шифрования/расшифровки** через `mcrypt_generic_init($this->rTd,
$this->sPublicKey, ...)` (`sys/auth.class.php:112,142`, режим 3DES/CFB) для
куки `lauth`, которой обменивается сайт с центральным «паспортом» meta.ua —
то есть это самый настоящий секрет, а не публичная информация. Длина строки
(24 байта) в точности совпадает с размером ключа `MCRYPT_TRIPLEDES` в
libmcrypt, что дополнительно подтверждает, что это не случайный плейсхолдер.

Строка встречалась **трижды**, во всех трёх местах, где код проверяет
`HTTP_HOST` на `ds.meta.ua`:

- `ds/index_login.php:24`
- `ds/reg_wizard.php:31`
- `sys_dyn_components/LoginFormU.php:29`

Все три заменены на `REDACTED_API_SECRET` в рамках этой сессии, находка
задокументирована как запись `[T]` в `REDACTED_CREDENTIALS.txt` (с указанием
всех трёх мест и обоснованием, почему это секрет, а не публичный ключ,
несмотря на имя переменной). Отдельно поискано ещё раз по всему `old-sphere/`
после правки — открытых вхождений строки больше не осталось.

## 12. Открытые вопросы

1. **`ds/authorization.php` и `GE_KlaseLogin.class.php` физически отсутствуют
   в архиве** — подтверждено ещё раз с этой стороны (вызывающий код
   `GE_Login.class.php:167,180` для klase.lt). Это тот же зафиксированный
   пробел, что и для VK (`docs/README.md`, чеклист); не восстанавливается.
2. **`GetToken()`/`GetUserData()`** (глобальные функции OAuth 1.0a для
   klase.lt, вызываются из `GE_Login::_checkKlaseLogin()`) нигде не найдены
   как определения в прочитанных файлах `GE` — вероятно, живут в
   `sys/OAuth.php` (подключается условно только для хоста `ds.klase.lt` в
   `index_login.php:15`), который не входил в периметр этой задачи.
3. **`GE_ExecPremiumSupport` — мёртвый и внутренне сломанный класс** (§7.4):
   не вызывается нигде в `old-sphere/`, и даже при вызове перепутал бы
   константу лимита сообщений. Не переносить логику в ремастер как есть —
   образцом должен служить `GE_ExecSupport`.
4. **`GetPremPresentVk()` вызывает три несуществующих метода**
   `GE_SearchStart::CanUserGetPremPresent()`/`IfPremPresentIsReceived()`/
   `GetPremPresentCount()` (§3.5, §7.3) — ни один не определён нигде в
   архиве. Метод либо никогда не запускался в проде, либо запускался и падал.
5. **Баг с неопределёнными переменными в турнирной ветке
   `GE_ExecStartCreateInstance`** (§5.1, строки 225/233/244) — блок
   регистрации доступа к турнирному миру на мастер-БД, вероятно, никогда не
   выполняется из-за опечатки в имени переменной (`$DBID` вместо
   `self::$DBID`). Не установлено, компенсируется ли это другим кодом.
6. **Смысл кодов `world_identifier` `EDN`/`ORN`/`ZME`/`VSV`/`TRN`**
   (`GE_LogicWorldsList.class.php:75-79`, список миров-исключений из
   `ONE_WORLD_REGISTRATION_ONLY`) — не расшифрован; `TRN`, вероятно, турнирный
   мир (согласуется с `mechanics-tournaments.md`), остальные три — не
   установлено.
7. **`windowKey` (`ds_userinstancewindowkey`)** — назначение
   («блокировка параллельной работы», согласно комментарию разработчика в
   `GE_ExecStartGameEnter.class.php:123`) зафиксировано только со стороны
   записи; где и как это значение читается/используется дальше (в игровом
   XML-протоколе или на клиенте) — не проверялось в периметре этой задачи.
8. **Реальная точка входа для `GE_RamblerLogin`** не найдена — класс
   существует и рабочий, но ни один просмотренный `.php`-скрипт верхнего
   уровня его не создаёт (в отличие от `GE_Login`/`GE_MetaUALogin`, у которых
   явно виден `index_login.php`). Возможно, живёт в не прочитанном в этой
   задаче скрипте.
9. **`GE_RamblerRegistration` — пустая заготовка** (§3.4) — не установлено,
   планировалась ли регистрация через Rambler ID когда-либо в проде, или это
   заброшенный черновик с самого начала.
10. **CAPTCHA `GE_ImageProtection` фактически отключена в снятом конфиге**
    (§8) — не установлено, было ли это временным отключением или постоянным
    решением в пользу антибот-вопросов `ds/antibot.php`; для ремастера решение
    нужно принимать заново, а не переносить оба механизма «как есть».
