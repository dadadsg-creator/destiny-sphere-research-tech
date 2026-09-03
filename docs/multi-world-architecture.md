# Мульти-версионная и мульти-мировая архитектура Destiny Sphere

Документ описывает, как один и тот же код (`old-sphere/`) обслуживает три параллельные версии игры
(Classic / Unlimited / Pulsar), и как внутри каждой версии код работает с несколькими игровыми "мирами"
(отдельными базами данных). Все находки подкреплены путями к файлам и номерами строк. Там, где по коду
нельзя установить факт однозначно, это явно помечено как предположение или вынесено в раздел "Открытые
вопросы".

Ключевой файл для всего раздела — `ds/config/settings.php`. Он хранит комментарии в кодировке windows-1251;
в примерах ниже они переданы в транслитерированном/переведённом виде.

---

## 1. Как определяется версия сайта (Classic / Unlimited / Pulsar)

Определение версии происходит в `ds/config/settings.php:21-323` и завязано на UID процесса веб-сервера
(`posix_getuid()`), с резервным вариантом через `$_SERVER['SERVER_ADDR']` / `$_SERVER['SERVER_NAME']`, если
сервер работает под общим системным пользователем (uid 33, обычно `www-data`).

```php
$uid = posix_getuid();                                            // settings.php:21
if ($uid==201 || $uid==33 && ($_SERVER['SERVER_ADDR'] == '94.242.35.154' || ...))   // settings.php:29
    // ветка Unlimited
else if ($uid==202 || $uid==33 && ($_SERVER['SERVER_NAME']=='ds-game.su' || ...))   // settings.php:110
    // ветка Pulsar
else if ($uid==200 || $uid==33 && ($_SERVER['SERVER_ADDR'] == '94.242.35.153' || ...)) // settings.php:148
    // ветка Classic
```

Обратите внимание: `&&` в PHP имеет более высокий приоритет, чем `||`, поэтому условие фактически читается
как `$uid==201 || ($uid==33 && (...))` — это не баг, а осознанная (хоть и нечитаемая) конструкция: "либо это
именно этот uid, либо это общий uid 33 на сервере с нужным IP/именем".

### Таблица версий

| uid | Fallback (uid=33) | Версия | `VERSION_NAME` | `GAME_HOST` | Домен | `CACHING_PREFIX` | Шаблоны (`TEMPLATES_PATH`) |
|---|---|---|---|---|---|---|---|
| 200 | `SERVER_ADDR` = 94.242.35.153 или 94.242.60.30 | **Classic** | `RU_CLASSIC` | `http://destinysphere.ru` | destinysphere.ru | `classic_` | `/home/www/ds/templates/classic/` |
| 201 | `SERVER_ADDR` = 94.242.35.154 или 94.242.60.31 | **Unlimited** | `RU_UNLIM` | `http://ds-game.ru` | ds-game.ru | `unlim_` | `/home/www/ds/templates/` |
| 202 | `SERVER_NAME` = ds-game.su или www.ds-game.su | **Pulsar** | `LIMITED` | `http://ds-game.su` | ds-game.su | `pulsar_` | `/home/www/ds/templates/classic/` |

Прочие константы, различающиеся по версии (там же, `settings.php:100-145` и `:314-322`):

| Параметр | Classic | Unlimited | Pulsar |
|---|---|---|---|
| `BILLING_PARTNER_ID` | 4 | 6 | 9 |
| `SPONSORPAY_ID` | 284 | 283 | 284 |
| `FREETOPAY_ID` | 28 | 26 | 28 |
| `CAN_ENTER_WITHOUT_ACTIVATION` | 10 | 2 | 2 |
| Папка логов (`LOGING_DEBUG_LOGS_PATH['MAIN']`, settings.php:22-27) | `ds/log_classic/` | `ds/log_unlim/` | `ds/log_pulsar/` |
| Форум-регистрация (`URL_FORUM_REGISTRATION_GATE`) | `http://forum.destinysphere.ru/reg_gate.php` | `http://forum.ds-game.ru/reg_gate.php` | `http://forum.ds-game.ru/reg_gate.php` (то же, что у Unlimited) |

Любопытная деталь: Pulsar использует шаблоны `classic/` (`settings.php:143`) и переиспользует форумный шлюз
Unlimited (`settings.php:144`) — то есть Pulsar технически "младший брат" Classic по вёрстке, но привязан к
форуму/биллингу Unlimited-стороны. У Pulsar также **не определён** `$GLOBALS['dbForum']` (в отличие от двух
других версий, см. раздел 2) — открытый вопрос, есть ли у Pulsar форум вообще.

---

## 2. Полная таблица игровых миров по версиям

Каждая версия хранит список миров в `$GLOBALS['dbWorldAccess']` — массиве с ключами-`dbId` (0, 1, 2, …).
Все базы физически висят на одном хосте `94.242.60.26` (пароли вычищены как `REDACTED_DB_PASSWORD`).

### Classic (`settings.php:150-293`), uid=200

| dbId | dbname | server (алиас) | shortname |
|---|---|---|---|
| 0 | classic | master | gc05 |
| 1 | parusa | phoenix | g11 |
| 2 | eridan | eridan | g11 |
| 3 | lion | lion | g11 |
| 4 | voron | voron | db11 |
| 5 | orion | orion | db2_2 |
| 6 | zmei | premium | db8 |
| 7 | vodolei | vodolei | db7 |
| 8 | strelez | beta | db10 |
| 9 | blackhole | hell | db14 |
| 10 | teranova | priemka | g15 |
| 11 | volk | volk | g12 |
| 12 | edinorog | edinorog | gdb1 |
| 13 | turnir | turnir | db17 |

Портальная (locking) БД: `MySQLBases['LOCKING']` = host 94.242.60.26, dbname `portal_ru`, user `ruportal`
(`settings.php:295-304`). Форум: `dbForum` = dbname `forum_ru`, user `dsgame` (`settings.php:306-312`).

### Unlimited (`settings.php:33-78`), uid=201

| dbId | dbname | server (алиас) | shortname |
|---|---|---|---|
| 0 | unlim | master | gc05 |
| 1 | orel | orel | g11 |
| 2 | drakon2 | level_2_drk | gc05 (в коде закомментирован прежний вариант `db17`, см. `settings.php:64-65`, помечено `//!!!!!!!!!`-подобным вниманием) |
| 3 | telez2 | level_3_tlc | g11 |

Портальная БД: `MySQLBases['LOCKING']` = dbname `portal_unlim`, user `ruportal` (`settings.php:79-90`).
Форум: `dbForum` = dbname `forum`, user `dsgame` (`settings.php:93-99`).

### Pulsar (`settings.php:111-124`), uid=202

| dbId | dbname | server (алиас) | shortname |
|---|---|---|---|
| 0 | pulsar_otkat | pulsar | pulsar |

Портальная БД: `MySQLBases['LOCKING']` = dbname `pulsar`, user `ruportal` (`settings.php:126-135`).
`dbForum` — **отсутствует** в этой ветке конфига.

### Наблюдения по таблице миров

- `dbId = 0` всегда — специальный "мастер"-мир (`server => 'master'` либо, у Pulsar, единственный мир). Это
  не просто мир №0, а центральная БД версии: в неё пишутся сводные таблицы `ds_worldcollection` и
  `ds_userinstancecollection`, используемые для меж-мировых операций (см. раздел 3 и 7).
- `shortname` — не имеет отношения к языковым или другим "shortname" в БД (которые есть в других таблицах,
  например языки/альянсы) — это отдельный ярлык группы серверов, используемый исключительно в
  диагностике/мониторинге репликации: `ds/classes/SU/SU_TestSystemServerTimes.class.php:36,73,83` и
  `ds/classes/SU/SU_TestSystemDBConnectionAndReplication.class.php:63,117,261,278,285`. Судя по тому, что
  несколько разных `dbname` в Classic и Unlimited делят один и тот же `shortname` (например, `g11` — это
  parusa/eridan/lion в Classic и telez2 в Unlimited; `gc05` — master в обеих версиях, а также drakon2 в
  Unlimited), это исторический ярлык физического сервера/группы репликации, оставшийся от более раннего
  этапа инфраструктуры (когда миры физически размещались на разных хостах, а не на одном
  `94.242.60.26`) — прямых подтверждений в коде для точной семантики нет, это предположение.
- Поле `dir` (везде `/ds/`) не используется нигде, кроме объявления — возможно, задел на будущее для разных
  директорий инсталляции по мирам (не найдено использования через grep).

---

## 3. Как код узнаёт "текущий мир" в рамках запроса

Ядро — синглтон `DS_Common` (`ds/classes/DS/DS_Common.class.php`). Он копирует конфиг миров себе в
`$this->dbWorldAccess` в конструкторе (`DS_Common.class.php:63-70`), и там же читает `$_SESSION["dbId"]` как
изначальный "текущий мир":

```php
if(@$this->dbWorldAccess[@$_SESSION["dbId"]])       // DS_Common.class.php:75
{
    $this->dbId = $_SESSION["dbId"];
}else{
    $this->dbId = 0;                                 // по умолчанию — "master" мир
}
```

Публичные точки входа:

- `DS_Common::CreateForCurrentDB($DBID = null)` (`DS_Common.class.php:98-122`) — если `$DBID` не передан,
  берёт `$_SESSION["dbId"]` (если есть) и переключает синглтон на него через `setDbId()`.
- `DS_Common::setDbId($dbId)` (`DS_Common.class.php:297-320`) — устанавливает `$this->dbId`, при
  необходимости лениво коннектится (`connectSingl`) и выставляет `SET NAMES 'WIN1251'` на соединении.
- `DS_Common::connectSingl($dbId)` (`DS_Common.class.php:338-374`) — реально открывает соединение
  через `pg_connect()`, используя `host/dbname/user/password` из `$this->dbWorldAccess[$dbId]` (см. раздел 4).

Момент, когда `$_SESSION['dbId']` реально устанавливается при входе игрока в конкретный мир — это
вход в игру (выбор мира на "воротах"):

```php
$_SESSION['dbId'] = self::$DBID;                                   // GE_ExecStartGameEnter.class.php:109
self::$common = DS_Common::CreateForCurentDB(self::$DBID);         // GE_ExecStartGameEnter.class.php:110
```

(`ds/classes/GE/GE_ExecStartGameEnter.class.php`). После этого `$_SESSION['dbId']` живёт в сессии игрока и
на каждый следующий HTTP-запрос `DS_Common`-синглтон при создании подхватывает именно этот мир — то есть
"текущий мир" — это server-side session-переменная, а не что-то, что передаётся явно в каждом запросе.

Административные и batch-скрипты (утилиты в `ds/utils/`) вместо этого явно перебирают все миры:
`foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db) { $common->setDbId($dbId); ... }` — см. раздел 7.

Также есть флаг `$GLOBALS['DB_SINGL_MODE']`, который проверяется в `DS_Common::connect()`
(`DS_Common.class.php:399`) и в `DS_Common2` (аналогично) — если он не выставлен (а по факту в кодовой базе
он нигде не присваивается, только читается), метод `connect()` без аргументов **сразу коннектится ко всем
мирам версии** (цикл `foreach($this->dbWorldAccess as $k=>$v) { $this->connectSingl($k); }`,
`DS_Common.class.php:403-413`). Именно так работают кросс-мировые утилиты вроде `ranking_csv.php`, которые
в начале вызывают `$common->connect()` (`ds/utils/ranking_csv.php:20`), открывая пачку соединений разом (для
Classic — сразу 14 подключений к Postgres).

---

## 4. PostgreSQL vs MySQL — где что используется и почему

Это ключевая находка, которая противоречит "внешнему виду" конфига: несмотря на то, что
`$GLOBALS['dbWorldAccess']` выглядит как типичный MySQL-конфиг (ключи `host/dbname/user/password`), **основной
игровой движок работает на PostgreSQL**, а не на MySQL.

### 4.1 Игровые миры (`dbWorldAccess`) → PostgreSQL

`DS_Common::connectSingl()` собирает строку подключения в формате libpq и вызывает `pg_connect()`:

```php
$conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
$conn = pg_connect ($conn_string);                                  // DS_Common.class.php:349-351
...
pg_query($this->conn, "SET NAMES 'WIN1251'");                       // DS_Common.class.php:317
```

Это же видно по SQL-синтаксису в `ds/classes/WorldUtils.class.php` (используется для всех игровых миров):
`UPDATE ... FROM (subquery) ...` (Postgres-специфичный `UPDATE...FROM`), `SELECT currval('ds_userinstance_id_userinstance_seq')`
(`WorldUtils.class.php:414`), явные Postgres-последовательности и `::REAL`-каст (`WorldUtils.class.php:666`).
Дублирующий класс `ds/classes/DS/DS_Common2.class.php` устроен идентично (тоже `pg_connect`, строки 350, 389).

Таким образом, все "миры" из таблиц раздела 2 — это отдельные **PostgreSQL-базы**, физически расположенные
(судя по конфигу) на одном хосте `94.242.60.26`, но как отдельные `dbname`.

**Практическое следствие для настроек мира.** Таблица `ds_gamemodifier` (правила/константы конкретного
игрового мира — лимиты армий, боевые множители, налоги и т.д., см.
[mechanics-races-world-ranking.md, §2](mechanics-races-world-ranking.md)) существует **в каждой из этих
отдельных БД по отдельности**, а не как одна общая таблица со столбцом "мир" на всех миров сразу. Значение
конкретного модификатора (например, `MAX_COLONIES` или `ALLIANCE_PEACE_TAX`) для мира A хранится физически в
БД мира A и никак не видно из БД мира B — сравнение значений между мирами возможно только подключением к
каждой БД отдельно (или через кросс-мировой инструмент, если такой есть — см. раздел 7 ниже) и никогда одним
запросом. Это подтверждается и на уровне кода: `DE_WorldSettings` (конструктор которого делает `SELECT * FROM
ds_gamemodifier` без `WHERE id_world` — см. `mechanics-races-world-ranking.md`, §2) прямо полагается на то,
что таблица физически содержит только строки своего мира, а не общий список по всем мирам сразу; отдельный,
общий для целой версии сайта (не помировой) справочник настроек — это другая таблица/класс,
`DE_GlobalSettings` (`DS_Misc::GetGlobalSettings()`), не путать одно с другим. На практике (источник: БД,
live-запрос, 2026-09-02, см. `mechanics-races-world-ranking.md` §5.3/§6.1, `mechanics-combat.md` §14.5) значения
одних и тех же ключей заметно различаются между мирами — например, `BATTLE_ROUND_TIME_MULTIPLIER = 0.3` на трёх
проверенных мирах, но `0.6` на турнирном «Сфера Гончих Псов» — то есть это не теоретическая возможность
конфигурации, а реально используемая на практике особенность архитектуры.

### 4.2 Портальная/locking БД (`MySQLBases['LOCKING']`) → настоящий MySQL

В отличие от `dbWorldAccess`, `$GLOBALS['MySQLBases']` реально используется через `mysql_connect()`/
`mysql_query()` в классе `DS_MySQLWork` (`ds/classes/DS/DS_MySQLWork.class.php:50,76,90`):

```php
$conn = mysql_connect($host, $user, $password);          // DS_MySQLWork.class.php:50
assert(mysql_select_db($dbName, $conn));                  // DS_MySQLWork.class.php:56
```

Практическое применение — распределённая блокировка пользовательских действий, чтобы исключить гонки при
одновременных запросах игрока (ход, обновление ресурсов, денежные операции и т.п.):
`ds/classes/SU/SU_LockerStoreWorkMySQL.class.php` вставляет/удаляет строки в таблице
`ds_locking_<DBID>` через `$this->dbWork->connect('LOCKING')` (строка 21) и `INSERT ... ON DUPLICATE`-подобную
логику (ловит `Duplicate entry`, строка 48) — то есть использует уникальный ключ MySQL как атомарный примитив
блокировки. Список объектов блокировки — `ds/classes/SU/SU_Locker.class.php:52-61`
(`REFRESH`, `USERACTION`, `EVENT`, `ACTION`, `MONEY_ACCOUNT`, `TEST_SYSTEM_FUNCTION`, `EVENT_QUEUE`).

**Гипотеза (не 100% подтверждена в коде), почему два движка**: MySQL с быстрыми короткоживущими
lock-строками на уникальном ключе — простой и дешёвый способ сериализовать конкурентные действия игрока в
рамках одной версии сайта (общая для всех миров этой версии портальная БД: `portal_ru` / `portal_unlim` /
`pulsar`), тогда как тяжёлые реляционные игровые данные (комбайны, здания, юниты, армии, атрибуты) исторически
писались на PostgreSQL. Прямого комментария в коде, объясняющего выбор, не найдено — это разумная
реконструкция по устройству кода, но не факт из документации.

### 4.3 Форум (`dbForum`) → неоднозначно

`DS_Common::connect('forum')` (тот же класс) **тоже использует `pg_connect()`** для форумной БД
(`DS_Common.class.php:382-396`), даже несмотря на то, что `dbForum` в `settings.php` называется `forum`/
`forum_ru` — типичное имя базы phpBB, а phpBB канонически работает на MySQL. Вызовы `$common->connect('forum')`
встречаются в реальных, не тестовых сценариях: `ds/classes/GE/GE_ExecStartCreateInstance.class.php:316`,
`ds/classes/BG/BG_ChangeLogin.class.php:101`, `ds/index_profile.php:399`, `ds/templates/classic/index_registration.php:251`.
**Открытый вопрос**: либо форум в этой инсталляции реально держат на PostgreSQL (нестандартно для phpBB),
либо этот код в реальности не работает / форумная интеграция идёт другим путём — по коду это не установить
однозначно.

### 4.4 "Мёртвый" PostgreSQL через PEAR DB / DSN — не путать с боевым путём

В задании упомянуты `ds/ClassFactory.class.php` и `Admin/include/classfactory.php` — по факту правильные
пути в репозитории: **`ds/ClassFactory.php`** (не `.class.php`) и `Admin/include/classfactory.php` (это два
почти идентичных файла). Оба содержат класс `ClassFactory::getInstance($classname, $dbname)`, который для
`$classname == "DB"` строит DSN вида:

```php
$dsn = "pgsql://$user:$pass@$host/$dbname";               // ds/ClassFactory.php:59
echo"<hr><pre>"; var_dump($dsn); echo "</pre><hr>";
$db = DB::connect ( $dsn );
...
die();                                                      // ds/ClassFactory.php:66 — после этого код недостижим
```

Здесь используется PEAR `DB::connect()` с PostgreSQL-DSN, а креды — старые (`gamedb2.destinysphere.ru`,
`forum.destinysphere.ru`), не совпадающие с текущим `94.242.60.26`. Проверка по всей кодовой базе
(`grep getInstance\s*\(\s*["']DB["']`) не находит **ни одного** места, где `ClassFactory::getInstance` реально
вызывается с `"DB"` — везде вызывается `"DS_Common"` или другие боевые классы. Более того, код содержит
отладочный `var_dump` и безусловный `die()` сразу после подключения — то есть даже если бы кто-то вызвал
`getInstance("DB", ...)`, метод сразу завершал бы выполнение. **Вывод: эта ветка кода мертва** — рудимент
раннего прототипа (вероятно, до появления мульти-версионной схемы с `dbWorldAccess`), реального отношения к
текущей архитектуре не имеет. Реальная фабрика, которую использует весь боевой код — тривиальный
`ds/classes/ClassFactory.class.php:3-10` (`import($classname); return new $classname;`), без какой-либо
логики подключения к БД — вся логика выбора БД инкапсулирована в `DS_Common`, а не в `ClassFactory`.

### 4.5 Прочие СУБД-слои (для полноты)

- `sys/include/classes/BaseClasses/MYSQLInterface.class.php` — обёртка над `mysql_*` для **сайтового CMS-слоя**
  (`sys/`, `sys_dyn_components/`) — отдельная подсистема для статических/полу-динамических страниц портала,
  не связанная напрямую с игровым движком `ds/`.
- `ds/GateKey_Master/` и `ds/DBSync/` — обе на PostgreSQL (см. разделы 5 и 6).

### Сводная таблица

| Подсистема | Globals-конфиг | Функции подключения | СУБД |
|---|---|---|---|
| Игровые миры (`ds/classes/DS/DS_Common*`) | `dbWorldAccess` | `pg_connect`, `pg_query` | **PostgreSQL** |
| Portal/Locking (`DS_MySQLWork`) | `MySQLBases['LOCKING']` | `mysql_connect`, `mysql_query` | **MySQL** |
| Форум (`DS_Common::connect('forum')`) | `dbForum` | `pg_connect` (! нетипично для phpBB) | Postgres по коду, вопрос открыт |
| GateKey Master | `ds/GateKey_Master/config.php` | `pg_connect` | PostgreSQL |
| DBSync | `ds/DBSync/config/config.cfg.php` | `pg_connect` | PostgreSQL |
| Старый `ClassFactory` "DB"-ветка | hardcoded в файле | PEAR `DB::connect('pgsql://...')` | PostgreSQL, но код **мёртв** |
| CMS-слой сайта (`sys/`) | отдельно | `mysql_connect` | MySQL |

---

## 5. Подсистема GateKey (`ds/GateKey_Master/`, `ds/classes/GK/`)

### Назначение

GateKey — механизм одноразовых ключей для авторизации межсерверных (межсайтовых) запросов без повторного
логина/пароля — что-то вроде HMAC-подобного handshake на общем секрете ("magic") плюс предварительно
сгенерированный пул одноразовых токенов. Судя по коду, он используется для доверенного взаимодействия между
основным игровым сайтом версии и внешними системами — как минимум биллингом/SMS-платежами (
`ds/gate/classes/SMSPayBillingLogger.class.php:23` вызывает `GK_GateKeyController::GetNewKey($this->gateName)`).
Прямых свидетельств использования GateKey именно для перехода на форум в коде не найдено (форумная
регистрация в `settings.php` использует отдельный механизм `URL_FORUM_REGISTRATION_GATE`/
`FORUM_REGISTRATION_THROUGH_GATE`, без видимой связи с классами `GK_*`) — это стоит считать предположением
задания, не подтверждённым напрямую.

### Устройство

- **Master** (`ds/GateKey_Master/`) — центральный сервис-эмитент ключей. Конфиг —
  `ds/GateKey_Master/config.php:6-25`: своя, отдельная от игровых миров, PostgreSQL БД
  (`gamedb14.destinysphere.ru`, dbname `destiny3`), таблицы `ds_gate_keys` (`GATE_KEY_DB_TABLE`) и
  `ds_gate_bad_keys` (`GATE_BAD_KEY_DB_TABLE`), а также `GATE_KEY_MAGIC` — общий секрет для подписи запросов,
  и `GATE_KEY_EXPIRE_TIME` = 7200 сек (время жизни ключа).
- **Slave** (`ds/classes/GK/` — `GK_GateKeyController.class.php`, `GK_Reciever.class.php`) — сторона каждого
  конкретного сайта (например, версии Classic/Unlimited/Pulsar), которая **сама выступает "мастером" для
  собственного локального пула ключей**: конфиг `ds/config/gp_config.php:8-11` явно переиспользует
  `$GLOBALS['dbWorldAccess'][0]` (то есть "мастер"-мир текущей версии, см. раздел 2/3) как хранилище своих
  gate-keys:
  ```php
  define('GATE_KEY_DB_HOST', $GLOBALS['dbWorldAccess'][0]['host']);      // gp_config.php:8
  define('GATE_KEY_DB_NAME', $GLOBALS['dbWorldAccess'][0]['dbname']);    // gp_config.php:9
  ```
  Это значит, что таблицы `ds_gate_keys`/`ds_gate_bad_keys` живут прямо в PostgreSQL-базе мира №0 каждой
  версии, а не в отдельной инфраструктурной БД — то есть GateKey не централизован на уровне всего проекта, а
  скорее многослоен: есть "большой" мастер (`GateKey_Master`, `destiny3`) и локальные копии на уровне каждой
  версии сайта.
- **Slaves-получатели** регистрируются в таблице `ds_gate_slave` (см. `GK_SlaveSearch.class.php:18`,
  `GK_Slave.class.php`) с полями `name`, `use_htaccess`/`htaccess_login/password`, `use_ip_limit`/`allowed_ip`,
  `domain`, `reciever_script` — то есть каждый "слейв" — это внешний HTTP-эндпоинт (`domain` +
  `reciever_script`), которому мастер по HTTP (`GK_HTTPRequest.class.php`) присылает ключи POST-запросом.

### Протокол (по `gate_main.php`/`test_master.php`/`GK_Reciever.class.php`)

1. Мастер выдаёт ключ: `GK_GateKeyMasterController::GetNewKey($slaveID)` → берёт неиспользованный,
   непросроченный ключ из `ds_gate_keys` (`GK_KeyMasterSearch::getKey`, `.../classes/GK_KeyMasterSearch.class.php:5-42`),
   помечает `send=1`.
2. Ключ передаётся принимающей стороне вместе с `md5(GATE_KEY_MAGIC . date('Y-d-m'))` ("gate_magic") —
   подпись, зависящая от даты и общего секрета (`gate_main.php:52`, `test_master.php:20`).
3. Принимающая сторона предъявляет ключ обратно на проверку:
   `GK_GateKeyMasterController::CheckKey($key, $slaveID)` (`GK_GateKeyMasterController.class.php:13-24`) —
   если ключ валиден (не просрочен, `recieved=0`, привязан к нужному `id_slave`), помечает `recieved=1`;
   если нет — при наличии таблицы `ds_gate_bad_keys` логирует попытку через `addToBadKeys()`
   (`GK_KeyMasterSearch.class.php:176-204`).
4. Устаревшие ключи чистятся `DeleteOldKeys()` (`GK_KeyMasterSearch.class.php:206-233`, TTL 7/14 дней).

Итого GateKey — это внутренний, самописный аналог одноразовых токенов доверия между связанными частями
инфраструктуры (сайт версии ↔ биллинг/платёжные шлюзы, и потенциально другие внешние сервисы), а не именно
"переход между мирами" в игровом смысле.

### 5.1 Точная структура БД и полный протокол пополнения ключей (по `schema.sql` и `ds/classes/GK/`)

Раздел выше был написан по чтению кода без доступа к дампу схемы; здесь — точные структуры трёх таблиц
GateKey и вторая половина протокола, которая раньше не была прослежена: **как локальный пул ключей вообще
пополняется**, а не только как один уже готовый ключ предъявляется и проверяется (шаги 1-4 выше).

#### Точная структура таблиц

`schema.sql` — это дамп схемы одного игрового мира (судя по присутствию в нём `ds_worldcollection` и
остального "мирового" набора таблиц — это мир `dbId=0`, "мастер" версии, см. раздел 2). Все три таблицы
GateKey физически лежат **в той же самой** PostgreSQL-базе, что и сам игровой мир №0 — на уровне
конкретной версии сайта (Classic/Unlimited/Pulsar) отдельной инфраструктурной БД под GateKey нет, несмотря
на то что подсказывает название `GateKey_Master`. Отдельная физическая БД (`destiny3` на
`gamedb14.destinysphere.ru`) существует только у **центрального** экземпляра (см. ниже).

| Таблица | Поля (`schema.sql`) | PK / FK / уникальность |
|---|---|---|
| `ds_gate_keys` (`schema.sql:15589-15595`) | `id_slave integer NOT NULL`, `key varchar(32) NOT NULL`, `expire_date timestamp NOT NULL`, `send integer`, `recieved integer` | **Без первичного ключа вообще** — только `FOREIGN KEY (id_slave) REFERENCES ds_gate_slave(id_slave)` (`schema.sql:32588-32592`) |
| `ds_gate_bad_keys` (`schema.sql:15578-15582`) | `id_slave integer`, `key varchar(32) NOT NULL`, `date timestamp DEFAULT now() NOT NULL` | Тоже без PK; `FOREIGN KEY (id_slave) REFERENCES ds_gate_slave(id_slave)` (`schema.sql:32580-32584`) |
| `ds_gate_slave` (`schema.sql:15602-15614`) | `id_slave serial`, `name varchar(50)`, `use_htaccess bool DEFAULT false NOT NULL`, `htaccess_login/htaccess_password varchar(30)`, `use_ip_limit bool DEFAULT false NOT NULL`, `allowed_ip varchar(15)`, `domain varchar(50) NOT NULL`, `reciever_script varchar(255) NOT NULL`, `last_keys_add_date timestamp`, `keys_add_rate integer NOT NULL` | `PRIMARY KEY (id_slave)` (`schema.sql:24690-24694`), `UNIQUE (name)` (`schema.sql:24682-24686`) |

`ds_gate_keys`/`ds_gate_bad_keys` без PK — это чисто журнальные таблицы, записи в них идентифицируются
только значением `key` (32-символьный hex). Показательная находка: `keys_add_rate` — единственное поле
`ds_gate_slave`, которое нигде не встречается в PHP-коде (проверено `grep -rn keys_add_rate` по всему
`old-sphere/` — ни одного обращения ни в `ds/classes/GK/`, ни в `ds/GateKey_Master/`). Реальный интервал
пополнения на деле общий и захардкожен в коде (см. ниже) — похоже, `keys_add_rate` задумывался как
индивидуальная частота пополнения на каждого "собеседника", но так и не был подключён.

#### Два независимых экземпляра одного протокола

В кодовой базе сосуществуют два почти дословно одинаковых, но не связанных друг с другом набора классов:

1. **`ds/GateKey_Master/`** — единственный центральный экземпляр проекта. Своя физическая БД
   (`gamedb14.destinysphere.ru`, dbname `destiny3`, `ds/GateKey_Master/config.php:7-10`),
   `GATE_KEY_MAGIC = 'TEST'` (`config.php:16`). Загружается через `gate_main.php`, которая руками
   подключает 9 классов (`GK_DBInterface`, `GK_DBPostgreSQL`, `GK_SearchBase`, `GK_HTTPRequest`,
   `GK_SlaveSearch`, `GK_Slave`, `GK_KeyMasterSearch`, `GK_GateKeyMasterController`, `GK_Generator`).
   Два входа: `generator.php` (`GK_Generator::Exec()` без аргумента — пополняет ключи сразу для всех
   зарегистрированных слейвов) и `test_master.php` — ручной диагностический скрипт: либо проверяет
   входящий тестовый ключ через `CheckKey()`, либо сам генерирует и пушит один ключ слейву `id=1` **по
   захардкоженному пути** `http://<slave.domain>/ds/GateKeyProtection/test_slave.php`
   (`test_master.php:57`) — заметно, что это **не** `$slave->getRecieverScript()` (поле `reciever_script`
   из БД), а отдельный, нигде больше не встречающийся в дереве репозитория путь `GateKeyProtection/` —
   похоже на устаревший диагностический скрипт, указывающий на давно не существующий эндпоинт.
   `GK_KeyMasterSearch::getKey()` (`GateKey_Master/classes/GK_KeyMasterSearch.class.php:5-42`) **не
   самопополняется** — если ключей для слейва не осталось, просто бросает исключение "No unused keys
   left"; значит, кто-то должен проактивно запускать `generator.php` (по аналогии с остальными `ds/utils/`
   вероятно, cron — прямых подтверждений в репозитории нет).

2. **`ds/classes/GK/`** — копия для конкретной версии сайта, подключаемая через `ds/config/gp_config.php`.
   Этот конфиг **переиспользует** `$GLOBALS['dbWorldAccess'][0]` как хранилище (`gp_config.php:8-11`),
   добавляет константу `GATE_INTERLOCUTOR = 'ds_gate_slave'` (`gp_config.php:15` — то есть в этом слое
   `ds_gate_slave` официально называется "собеседник", не "слейв") и свой `GATE_KEY_MAGIC = 'DS2'`
   (`gp_config.php:18`). В отличие от центрального экземпляра, `GK_KeySearch::getKey()`
   (`ds/classes/GK/GK_KeySearch.class.php:72-121`) **самопополняется**: если ключей для слейва не осталось,
   она сама вызывает `GK_Generator::Exec($slaveID)` и повторяет попытку один раз — то есть на этом уровне
   отдельный cron-джоб для пополнения не обязателен, генерация может произойти лениво, синхронно, прямо
   внутри запроса, которому впервые не хватило готового ключа. При этом `grep` по всему дереву не находит
   ни одного места, которое вызывало бы `GK_Generator::Exec()` **без** аргумента (то есть проактивно, сразу
   для всех "собеседников") для этого слоя — в отличие от центрального `generator.php`. Получается
   асимметрия: у центрального экземпляра пул рассчитан на проактивное пополнение (и падает с ошибкой, если
   его не пополнили вовремя), а у версионного — пополняется реактивно, по одному "собеседнику" за раз.

#### Протокол пополнения (push, `ds/classes/GK/GK_Generator.class.php`)

Раздел 5 выше разбирал только "предъявление готового ключа" (`GetNewKey`/`CheckKey`). Вот как ключи
попадают в локальный пул до этого момента:

1. `GK_Generator::Exec()` обходит все строки `ds_gate_slave` ("собеседников") и для каждой вызывает
   `GenKeysForSlave($id_slave)`.
2. Генерируется пачка из `GATE_KEY_COUNT` (100) случайных 32-символьных hex-ключей (`_genRandomKey`,
   `GK_Generator.class.php:110-126`). Если с момента `last_keys_add_date` прошло меньше
   `$keysRenewInterval` (захардкожено `3600` сек = 1 час, `GK_Generator.class.php:14`) **и** не был явно
   запрошен принудительный режим (`$genOnlyTrueKeys`), пачка помечается "фейковой" (`self::$fake = true`,
   `_genKeys`, строки 91-108) — она всё равно отправляется по сети, но **не** сохраняется локально и не
   продвигает `last_keys_add_date`. Иначе один случайный слот пачки подменяется маркерным значением
   `md5(date('H-Y-d-Y-m-d-d-DD').' vot takoi vot herovii den')` — буквальная шутливая русская фраза,
   зашитая в исходный код и пересчитываемая каждый час от текущего времени сервера (`_genKeys`,
   строка 106).
3. Пачка POST-ится на `<domain собеседника>/<reciever_script собеседника>`
   (`GK_Slave::getRecieverScript()`) с именами полей-хэшей — `md5('gate_magic')`, `md5('gate_action')`,
   `md5('gate_keys')`, `md5('gate_keys_expire_date')` (`_setupRequest`/`_sendKeysToSlave`,
   `GK_Generator.class.php:128-164`) — обфускация имён полей это, по сути, единственная защита протокола
   от случайного разбора трафика тем, кто не читал исходный код. `gate_magic = md5(GATE_KEY_MAGIC .
   date('Y-d-m'))`; несмотря на название "magic", собственный комментарий разработчика к константе
   (`gp_config.php:18`, `GateKey_Master/config.php:16`, после декодирования из windows-1251) гласит
   **«Название клиента»** — то есть функционально это слабый, посоленный датой общий секрет,
   именующий отправителя, а не просто случайная "магия".
4. Только для "настоящей" (не фейковой) пачки: ключи сохраняются локально через `GK_KeySearch::storeKeys()`
   под `id_slave` этого собеседника, и обновляется `last_keys_add_date` (`UpdateLastKeysAddDate`) — всё
   внутри одной транзакции вместе с отправкой (`GenKeysForSlave`, `GK_Generator.class.php:55-89`). То есть
   сторона-отправитель хранит копию пачки у себя **и** одновременно рассылает ту же пачку "собеседнику" —
   после этого шага обе стороны обладают одинаковым набором ключей для этой пары.
5. Принимающая сторона — реальный, подключённый в проде эндпоинт `ds/gate/reciever.php`
   (подключает `gp_config.php`, затем `GK_Reciever::Exec($_POST)`) → `GK_Reciever::RecieveKeys()`
   (`GK_Reciever.class.php:74-104`): сканирует входящий список ключей на совпадение с **собственным**,
   локально пересчитанным маркерным значением; только если маркер найден, определяется, какой из
   зарегистрированных у **этой** стороны "собеседников" прислал пачку — через
   `GK_SlaveSearch::GetSlaveByMagic()`, которая ищет строку `ds_gate_slave`, чей `name` даёт
   `md5(name || сегодня) = входящий gate_magic` (`GK_SlaveSearch.class.php:84-105`). То есть присланный
   отправителем `GATE_KEY_MAGIC` должен буквально совпадать со значением `name`, под которым получатель
   зарегистрировал этого отправителя в своей `ds_gate_slave`. Если маркер найден — **вся** пачка (включая
   сам маркер, он не отфильтровывается) сохраняется локально под `id_slave` этого собеседника
   (`GK_KeySearch::storeKeys`). Если пачка была "фейковой" (маркера нет), `RecieveKeys()` просто
   возвращает `'FAKE'` и ничего не сохраняет — то есть большинство пушей чаще раза в час на практике
   безрезультатны и служат лишь тому, чтобы отправителю не приходилось отдельно обрабатывать транспортный
   уровень.

Итог: это не классический push одноразовых токенов от master к slave, а фактически **синхронизация общего
пула токенов между двумя named-"собеседниками"**, после которой любая из сторон может достать
(`GetNewKey`) один ещё не использованный токен из своей копии пула и предъявить его другой стороне, которая
проверит совпадение по своей копии (`CheckKey`) — то есть предварительно распределённый, регулярно
обновляемый набор одноразовых bearer-токенов, а не PKI/HMAC в привычном смысле.

#### Подтверждённый боевой потребитель — оплата по SMS

`ds/gate/classes/SMSPayBillingLogger.class.php` — единственное место, где полный цикл проверен построчно.
При покупке игровых кредитов по SMS сайт версии берёт один ключ для собеседника с именем `'BILLING'`
(`GK_GateKeyController::GetNewKey('BILLING')`, `SMSPayBillingLogger.class.php:23`) и POST-ит его вместе с
данными платежа (`uid`/`sum`/`phone`/`pid`) и заново посчитанным `gate_magic` на
`https://billing.destinysphere.ru/Receiver/smsLogReceiver.php` (строки 30-44) — это доказывает биллингу,
что лог платежа действительно пришёл от игрового сервера, а не подделан напрямую клиентом.

Отдельная находка про **обратное** направление (входящие запросы от биллинга/партнёров в игровые
`BG_*`-гейты): `ds/classes/BG/BG_Common.class.php` содержит метод `checkGateProtection()`
(строки 145-170), который проделывает точно такую же проверку `CheckKey`/`GetSlaveByMagic` для входящих
запросов — но его **первая исполняемая строка** — голый `return;` (`BG_Common.class.php:147`), то есть
весь метод на практике мёртв и никогда не доходит до собственно проверки. Реальная защита входящих
`BG_*`-запросов — отдельная, не связанная с GateKey MD5-подпись (`checkSign()`/`createSign()`,
`BG_Common.class.php:131-142,172-188`, секрет `$secret_password` уже вычищен в код-базе как
`REDACTED_API_SECRET`, строка 22). Иными словами: GateKey **был спроектирован** и для защиты входящих
биллинговых вызовов тоже, но в текущем состоянии кода этот путь отключён — единственный подтверждённо
рабочий сценарий использования GateKey во всей кодовой базе — это исходящее логирование SMS-платежей,
описанное выше.

#### Открытые вопросы (дополнение к разделу 8)

- **`keys_add_rate`** — колонка есть в схеме (`NOT NULL`, без дефолта), но не используется нигде в PHP;
  вероятно, недоделанная функция индивидуальной частоты пополнения на "собеседника".
- **`test_master.php`** пушит тестовый ключ на путь `ds/GateKeyProtection/test_slave.php`, которого нет
  нигде в остальном дереве репозитория и который не совпадает с полем `reciever_script` из БД — похоже на
  рудимент более раннего варианта протокола/каталога.
- **Является ли `checkGateProtection()` в `BG_Common` отключённым намеренно** (например, после
  какого-то разбора безопасности, в пользу более простой MD5-подписи) **или это забытая недоделка** — по
  коду не устанавливается.
- **Кто и как проактивно запускает `ds/GateKey_Master/generator.php`** (единственный проактивный вход для
  центрального экземпляра) — прямых свидетельств (crontab) в репозитории, как и для прочих `ds/utils/`
  скриптов, нет.

---

## 6. Подсистема DBSync (`ds/DBSync/`)

DBSync — административный веб-инструмент для **сравнения и синхронизации схемы (DDL) двух PostgreSQL-баз**
("Master" и "Slave"), выбираемых вручную из списка (`$GLOBALS['List_of_Bases']`,
`ds/DBSync/config/config.cfg.php:3-36`). В текущем конфиге список содержит всего 2 записи — `Master` (в
файле указана `parusa`, комментарий в этом же файле показывает, что раньше стояли `destiny3`/`destiny2` на
`gamedb14`/`gamedb4`) и `Slave` (`pulsar_otkat`) — то есть это не универсальный список "все миры", а
конкретная пара БД, настроенная под конкретный последний прогон синхронизации/сравнения; какие именно базы
сравнивать, задаётся оператором вручную в веб-форме (`ds/DBSync/index.php:114-144`, выпадающие списки
Master/Slave).

Что умеет сравнивать (`ds/DBSync/index.php:23-34`, класс `Comparison` в `ds/DBSync/classes/Comparison.class.php`):

- `CompareTables` — какие таблицы есть в одной БД и нет в другой (генерирует `CREATE TABLE ... OIDS`).
- `CompareColumns`, `CompareUniqueConstraint`, `ComparePrimaryKeyConstraint`, `CompareCheckConstraint`,
  `CompareForeignKeyConstraint`, `CompareFunction`, `CompareIndex`, `CompareTrigger` — аналогично для
  соответствующих объектов схемы.
- `CompareData` — сравнение данных таблиц.

Результат — сгенерированные ALTER/CREATE-скрипты с подсветкой (`compareresult.php`), которые администратор,
судя по всему, вручную просматривает и применяет. Подключение — через `Connection.class.php` (Singleton),
которое строго использует `pg_connect`/`pg_query` (`ds/DBSync/classes/Connection.class.php:79,106,128`).

**Назначение в контексте мульти-мировой архитектуры**: поскольку каждый игровой мир — это физически
отдельная PostgreSQL-база с (в теории) идентичной схемой, DBSync — инструмент для проверки/восстановления
консистентности схемы между мирами (например, после ручного патча одного мира — свериться, что во всех
остальных мирах структура таблиц синхронизирована), либо для сверки основной ветки с тестовой/обкаточной
базой (`pulsar_otkat` в текущем конфиге). Это **не автоматическая репликация данных**, а инструмент
сравнения схемы/данных "по кнопке" с ручным выбором пары баз.

---

## 7. Кросс-мировые операции

Общий паттерн во всех нижеописанных скриптах — цикл `foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db) { $common->setDbId($dbId); ... }`,
то есть последовательный обход всех миров текущей версии сайта с переключением активного PostgreSQL-соединения.

### `ds/utils/CollectWorlds.php` + `ds/classes/SU/SU_CollectWorlds.class.php`

Обходит миры из `allowBases` (можно ограничить через `?servers[]=` в браузере или аргументы командной строки
при запуске из CLI, `CollectWorlds.php:15-73`) и для каждого мира (`SU_CollectWorlds::collect()`,
`SU_CollectWorlds.class.php:54-92`):
1. Чистит в мастер-БД (`dbId=0`) строки `ds_userinstancecollection` для этого мира
   (`clearMaster()`, `SU_CollectWorlds.class.php:94-100`, `DELETE ... WHERE dbname = '<server>'`).
2. Заново собирает данные о мирах/инстансах/allowed-флагах (`collectWorlds`, `collectInstances`,
   `collectAllowed`) и пишет их назад в мастер-БД в рамках транзакции.

Это тот же механизм, который поддерживает таблицу `ds_userinstancecollection`, обновляемую и "на лету" при
создании нового инстанса игрока (`ds/classes/WorldUtils.class.php:611-652`, где после коммита в мире делается
`$common->setDbId(0)` и `INSERT INTO ds_userinstancecollection ...`). То есть CollectWorlds — это, по сути,
полная пересборка сводного "реестра пользователей по мирам" с нуля (используется, видимо, для восстановления
консистентности реестра, если он разошёлся с реальными данными миров).

### `ds/utils/ClearWorld.php`

Вызывает `DU_ClearWorld::Clear($worldId, $langId)` (`ClearWorld.php:53`, класс `DU_ClearWorld` не найден в
явном виде в текущем срезе кода в `ds/classes/DU/` под этим именем — либо файл не включён в снапшот, либо
класс называется иначе/подгружается динамически через `import()`). Судя по параметрам (мир + язык) и общему
контексту утилит очистки, скрипт производит полную очистку/пересоздание конкретного игрового мира (например,
перед стартом нового мира или после архивации старого) в одной конкретной PostgreSQL-базе, а не по всем мирам
сразу — в этом он отличается от большинства других утилит из этого раздела.

### `ds/utils/CloseWar.php`

Административный UI (голая HTML-форма) с четырьмя действиями, применяемыми **разом ко всем мирам версии**
(`CloseWar.php:70-178`, класс `CloseWar`):
- `close` — вставляет во все миры период "мира" (`ds_peacepreiods`) на ~1 год вперёд (запрет войны).
- `open` — удаляет все записи `ds_peacepreiods` (снятие запрета войны).
- `close_trade` — вставляет во все миры запись `ds_notradeperiods` на ~1 год (запрет торговли) по каждому
  `id_world` внутри мира.
- `open_trade` — удаляет все `ds_notradeperiods`.

Есть аналогичные админ-контролы под GUI: `Admin/controls/GameWorlds/CloseWarAndTrade/` и
`Admin/controls/GameSettings/CloseWarAndTrade/` (классы `PeriodsHandler.class.php`, `Period.class.php`,
`World.class.php`) — судя по названиям, более новая/структурированная версия того же функционала, но уже,
видимо, применяемая к одному конкретному миру (аргумент `World.class.php` намекает на выбор мира), в отличие
от `ds/utils/CloseWar.php`, который жёстко проходит по всем мирам сразу.

### `ds/utils/DBCompare.php`

Не путать с DBSync (раздел 6) — это независимый тестовый/отладочный скрипт (класс
`SU_DBCompareForeignKeys`, `DBCompare.php:49-80+`), который **переопределяет** `$GLOBALS['dbWorldAccess']`
прямо в файле хардкодом (`DBCompare.php:26-45`, IP `192.168.0.10` и `gamedb16.destinysphere.ru` с логином
`defo`/паролем в открытом виде `14528914` — не через общий `settings.php`!) и сравнивает внешние ключи
(foreign keys) между "master" (dbId 0) и остальными мирами. Судя по хардкоду локальных/тестовых адресов —
это разовый диагностический скрипт для конкретного инцидента, не часть регулярного обслуживания. Стоит
считать его практически мёртвым/устаревшим кодом, привязанным к инфраструктуре, которой уже нет в основном
`settings.php`.

### `ds/utils/ranking_csv.php`

Генерирует CSV/файловый кэш рейтингов (`FILENAME` = `ds/csv/ranking`, `ranking_csv.php:15`), обходя все миры
версии (`ranking_csv.php:50-79`, класс `RankingCSV::generateRanking()`) и агрегируя данные о мирах/языках из
таблиц `ds_worldhaslanguage`/`ds_world` в каждом из них. Перед циклом делает `$common->connect()` без
аргумента (`ranking_csv.php:20`), что (см. раздел 3) сразу открывает PostgreSQL-соединения ко всем мирам
разом, а не лениво по одному.

### `ds/utils/DeleteDeadUsers.php` (+ `ds/classes/SU/SU_DeleteDeadUsers.class.php`)

В отличие от предыдущих, это **не** авто-цикл по всем мирам, а страница-меню: показывает ссылки на каждый мир
(`DeleteDeadUsers.php:30-33`, `foreach($GLOBALS['dbWorldAccess'] as $k=>$v) echo '<a href="?...dbid='.$k...`),
и уже по клику/явному `dbid` в CLI-аргументе запускает `SU_DeleteDeadUsers($DBID)->Scan()` для одного
конкретно выбранного мира — то есть "мёртвых" (неактивных/без построек) пользователей чистят по одному миру
за раз, вручную выбирая, какой. Также ограничена версией: `if (VERSION_NAME=="LIMITED") exit;`
(`DeleteDeadUsers.php:6`) — для Pulsar скрипт не работает вовсе.

### `ds/utils/ArchiveRankings.php`

Ежедневный (судя по `current_date` в SQL) архиватор рейтингов: обходит **все** миры
(`ArchiveRankings.php:7-10`, `foreach($GLOBALS['dbWorldAccess'] as $dbId => $db) { ... $_SESSION['dbId'] = $dbId; }`)
и в каждом сохраняет снепшот `ds_ranking_archive` (общий/археологический/строительный/научный/боевой рейтинг
и NPC-опыт) с последующим пересчётом мест (`total`, `arch`, `building`, …) — то есть строит историю рейтингов
по дням, отдельно на каждый мир.

### `ds/utils/ServerLoad.php` (+ `ds/classes/SU/SU_ServerLoadStat.class.php`)

Тонкая обёртка (`echo SU_ServerLoadStat::MainInterface();`, `ServerLoad.php:16`) над классом статистики
нагрузки — судя по названию и наличию в `Admin/controls/Stats/Statistic/` аналогичного контрола, это
дашборд нагрузки/производительности по мирам (детали внутренней реализации `SU_ServerLoadStat` не
разбирались подробно в рамках этого анализа).

### Вспомогательные меж-мировые методы в `DS_Common`

Кроме отдельных утилит, сам `DS_Common` предоставляет два generic-метода для "сделать один и тот же запрос
по всем мирам и склеить результат": `getMultyArray()` (`DS_Common.class.php:420-459`) и `getMultySql()`
(`DS_Common.class.php:461-499`) — оба перебирают `$this->dbWorldAccess`, временно переключая `dbId`, помечая
каждую строку результата исходным `dbId`/`server`, и в конце возвращают исходный `dbId` на место. Это,
похоже, основной низкоуровневый примитив, на котором в теории могли бы быть построены произвольные
кросс-мировые отчёты (не только перечисленные выше утилиты).

---

## 8. Открытые вопросы

1. **Форум на PostgreSQL или MySQL?** Код `DS_Common::connect('forum'|'forum_ru')` использует `pg_connect`
   (раздел 4.3), что нетипично для phpBB (обычно MySQL). Не установлено, реально ли форум крутится на
   Postgres в проде, либо этот путь кода на практике не работает / форум интегрирован иначе.
2. **У Pulsar отсутствует `$GLOBALS['dbForum']`** в `settings.php` — означает ли это, что у Pulsar нет
   собственного форума, или что для Pulsar форумные функции полагаются на какой-то умолчательный/чужой
   конфиг (в коде явных признаков fallback-а не найдено).
3. **Почему два движка БД (Postgres для игры, MySQL для locking)?** В разделе 4.2 предложена рабочая
   гипотеза (быстрые атомарные lock-и на уникальном ключе MySQL против тяжёлой реляционной модели на
   Postgres), но явного архитектурного комментария/документации, подтверждающей мотивацию, в коде не
   найдено.
4. **Точная связь GateKey с переходом на форум/биллинг.** Прямое использование `GK_*`-классов найдено только
   для SMS-биллинга (`SMSPayBillingLogger.class.php`). Связь с форумной регистрацией (`URL_FORUM_REGISTRATION_GATE`)
   не подтверждена — это разные, судя по всему, механизмы, несмотря на общее слово "gate" в названии (ср.
   также `ds/gate/dsregistration.php`, который на самом деле про SMS-регистрацию через внешний шлюз SMSX,
   а не про GateKey). **Уточнение (см. раздел 5.1):** обратное направление — проверка входящих запросов
   от биллинга в `BG_*`-гейты — в коде тоже реализовано через GateKey (`BG_Common::checkGateProtection()`),
   но эта проверка мертва (метод начинается с `return;`, `BG_Common.class.php:147`); реально работающий,
   подтверждённый построчно сценарий использования GateKey во всей кодовой базе — один: исходящее
   логирование SMS-платежей в `SMSPayBillingLogger`.
5. **Класс `DU_ClearWorld`**, вызываемый из `ds/utils/ClearWorld.php:53`, не был обнаружен явным файлом в
   `ds/classes/DU/` в этом срезе кода — не проверено, подгружается ли он динамическим автозагрузчиком
   (`import()`, `ds/include/Import.inc.php`) из нестандартного места, либо отсутствует в переданном срезе
   репозитория.
6. **Значение поля `dir` в `dbWorldAccess`** (всегда `/ds/`) — не найдено ни одного места использования;
   вероятно, неиспользуемый рудимент.
7. **Физический смысл `shortname`.** Есть основания считать это ярлыком группы серверов/репликации
   (раздел 2), но точная семантика (что означает конкретно `g11`, `gc05`, `db17` и т.д. с точки зрения
   инфраструктуры) не восстановима из кода — это должны знать только те, кто администрировал сервера.
8. **Список баз в `ds/DBSync/config/config.cfg.php`** — не общий реестр миров, а ручная пара для конкретного
   прогона; не установлено, обновляется ли этот файл под каждую задачу синхронизации вручную, или есть
   более широкий, не найденный в этом срезе, конфиг со всеми базами.
9. **`ds/utils/DBCompare.php` и `ds/ClassFactory.php`/`Admin/include/classfactory.php`** — по всем признакам
   являются мёртвым/устаревшим кодом (хардкод старых адресов, отладочные `die()`/`var_dump`), но нет
   стопроцентной гарантии, что они не запускаются вручную кем-то из администраторов при особых
   обстоятельствах.
