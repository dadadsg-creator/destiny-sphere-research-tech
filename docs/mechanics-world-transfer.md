# Механика переноса игрока и денег между мирами

Документ описывает три связанные, но технически независимые механики движка «Сферы Судьбы»:

- **Часть 1** — перенос игрового инстанса игрока (сота/соты, армии, здания, наука и т.д.) из одного
  мира (отдельной PostgreSQL БД) в другой — подсистема `ds/classes/SI/` (34 файла) плюс несколько
  классов `DS_`/`GL_`/`SU_`/`RD_`, которые вокруг неё организуют весь бизнес-процесс.
- **Часть 2** — перевод внутриигровой валюты (Золотые кредиты `GC` / Серебряные кредиты `SC`) между
  разными мирами того же пользователя — подсистема `ds/classes/MU/*Interworld*` плюс личный кабинет
  `UC_ExecMoneyTransfer`.
- **Часть 3** — переезд домашней соты **внутри одного мира**, по своему кольцу (смена
  комплекса/соты либо обмен местами с другим игроком) — не путать с Частью 1: другие таблицы
  (`ds_free_ring_transfer`), другой движок (`SU_FreeTransfer`/`SU_ExecFreeTransfer`/
  `SU_ChangCombCoords`, прямая перестановка координат без снятия снапшота), нет пересечения границы
  между мирами вообще.

Обе межмировые механики (Часть 1 и Часть 2) физически независимы (разные таблицы, разные процессы),
но пересекаются в одной точке: подготовка инстанса к переносу (Часть 1) автоматически выгребает
деньги инстанса в личный кабинет через ровно тот же примитив, что использует Часть 2 (см. раздел
1.7.1 и 2.5). Часть 3 с ними не пересекается технически, но по описанию для игрока (раздел 1.12,
player-facing источник) это тоже «переезд» — отсюда и объединение всех трёх механик в одном
документе.

Документ написан **с нуля по коду** (`old-sphere/`, read-only), без опоры на предыдущие ремастер-версии.
Там, где вывод сделан по косвенным признакам (нет явного текста/комментария), это помечено словом
«**предположение**». Часть 1.12 и Часть 3 дополнительно используют player-facing источник —
официальные справочные страницы сайта `destinysphere.ru` (не код) — такие места явно отмечены
отдельно и не смешиваются с выводами по коду. Уже существующий контекст
(`multi-world-architecture.md`, `game-engine.md`) не повторяется, только используется как основа
терминологии (мир = отдельная PostgreSQL БД, `dbId`/`world_identifier`, таблицы
`ds_worldcollection`/`ds_userinstancecollection` в мастер-БД `dbId=0`).

---

## Часть 1. Перенос инстанса игрока между мирами

### 1.1. Участвующие подсистемы — обзор файлов

| Каталог/файл | Роль |
|---|---|
| `ds/classes/SI/*` (34 файла) | Снятие снапшота инстанса и его восстановление в другом мире |
| `ds/classes/DS/DS_WorldTransfer.class.php`, `DS_RecieverWorld.class.php` | SQL-шлюзы к таблицам заказов на перенос (`ds_worldtransfer*`) |
| `ds/classes/GL/GL_RecieverWorldGetWorldForTransfer.class.php` | Подбор целевого мира для принудительного (авто) переноса |
| `ds/classes/SU/SU_FreeWorldTransfer.class.php` | Самостоятельный (self-service) заказ/отмена переноса игроком |
| `ds/classes/SU/SU_RecieverWorldTransfers.class.php` | Плановый обход мира: кто должен/может переехать |
| `ds/classes/SU/SU_ExecWorldTransfer.class.php` | Пакетный запуск фактического переноса по всем заказам со статусом «в очереди» |
| `ds/classes/SU/SU_TransferValidate.class.php` | Плановый аудит: не осталось ли «задвоенных» инстансов после переноса |
| `ds/classes/RD/RD_AdeptUserActionToConsole.class.php` | CLI-обвязка `useraction.php`, включает режим подготовки к переносу |
| `ds/FreeWorldTransfer.php`, `ds/movecomb/index_old.php`, `ds/movecomb/updateTransferInfo.php`, `ds/utils/RecieverWorld.php` | HTTP/CLI точки входа (см. 1.3) |
| `ds/classes/BG/BG_ChangeWorld.class.php` | Незавершённый гейт «переезд за деньги» (billing) — см. 1.9 |

### 1.2. Модель данных: таблица `ds_worldtransfer` (мастер-БД, `dbId=0`)

Заказ на перенос — это одна строка в `ds_worldtransfer` (`id_worldtransfer, id_user, db_from,
id_world_from, db_to, id_world_to, transfer_type, status, transfer_date, temp_colony`).

**`transfer_type`** (буквально комментарий из кода, `SU_FreeWorldTransfer.class.php:625-630`):

| Значение | Смысл |
|---|---|
| 0 | free — самостоятельный (бесплатный) переезд игрока |
| 1 | admin — ручной перенос администратором |
| 2 | billing — переезд за деньги (**не реализован до конца**, см. 1.9) |
| 5 | reciever auto — принудительный авто-переезд, а также служебные «запрещающие» записи (см. ниже) |

**`status`** (реконструировано по всем местам, где поле читается/пишется):

| Значение | Смысл | Где выставляется |
|---|---|---|
| 0 | нет активного заказа (по умолчанию либо после отмены) | `SU_FreeWorldTransfer::_cancelTransfer()` (`SU_FreeWorldTransfer.class.php:1077-1092`) |
| 1 | заказ поставлен в очередь на выполнение | `SU_FreeWorldTransfer::_setTransferInfo()` (`:1161-1204`), `DS_RecieverWorld::AddToTransferList()` (`DS_RecieverWorld.class.php:281-325`) |
| 2 | перенос успешно завершён (финализирован) | `SI_SearchFinalizeTransfer::UpdateTransferStatus()` (`SI_SearchFinalizeTransfer.class.php:251-261`) |
| 3 | ручной переезд из этого (уже отработанного) исходного мира заблокирован навсегда | `SI_SearchFinalizeTransfer::DisableManualTransfer()`, вставляет строку `transfer_type=5, status=3` (`:99-114`) |

Отдельная таблица `ds_worldtransfer_allowed_user (id_userinstance, add_date, transfer_required,
transfer_done)` хранит флаг «этому инстансу МОЖНО/НУЖНО переехать», выставляемый плановым обходом
(1.4). Таблица `ds_worldtransfer_worlds_relation (id_world_from, id_world_to, only_for_premium, free,
priority, frequency)` (+`ds_worldtransfer_worlds_list (id_worldtransfer_world, world_identifier)`,
трёхбуквенный код мира) описывает граф разрешённых направлений мир→мир: `only_for_premium`, `free`
(бесплатно или только по персональному разрешению), `frequency` (минимальный интервал в часах между
повторными переездами по этой конкретной паре миров, БД-дефолт — **60 часов**, если для пары не
задано иное), `priority` (порядок перебора при автоподборе цели). Таблица `ds_worldtransfer_log` —
журнал одного технического исполнения переноса (см. 1.9), с булевыми флагами каждого этапа
финализации (структура подтверждена по `schema.sql`: `id_worldtransfer_log, id_user, id_world,
transfer_date, account_freezed, transfer_disabled, checksum_validated, added_to_delete_list,
added_to_collection, transfer_status_updated, transfer_valid`).

### 1.2.1. Сверка со схемой БД (`schema.sql`, pg_dump живой игровой БД) — триггеры на `ds_worldtransfer`

Этот раздел добавлен по прямому чтению SQL: `pg_dump --schema-only` живого мира (файл `schema.sql`,
в репозиторий не входит, read-only источник) даёт то, что не видно из PHP построчно — реальные
PL/pgSQL-триггеры, привязанные к `ds_worldtransfer`/`ds_worldtransfer_allowed_user`/`ds_comb`/
`ds_combname`/`ds_free_ring_transfer`. Два из них ощутимо уточняют, а местами расходятся с картиной
из разделов 1.2–1.9, реконструированной только по PHP.

**`worldtransfer_insert()` — `ds_worldtransfer` не append-log, INSERT может стать UPDATE.**
`CREATE TRIGGER ds_worldtransfer_tr1 BEFORE INSERT ON ds_worldtransfer FOR EACH ROW EXECUTE
PROCEDURE worldtransfer_insert()` (`schema.sql:31896`). Тело функции (`schema.sql:12025-12076`):
при попытке вставить новую строку функция ищет уже существующую строку с тем же `(id_user, db_from,
id_world_from)` и максимальным `transfer_date`. Если такая находится — **вставки не происходит**
(`RETURN NULL`): вместо этого **все** строки с этим `(id_user, db_from, id_world_from)` получают
`status = 2`, а затем именно та, «последняя» строка перезаписывается на месте: `status = NEW.status,
db_to = NEW.db_to, id_world_to = NEW.id_world_to, transfer_date = now()`. Если подходящей строки нет —
обычный `INSERT` (`RETURN NEW`).

Практически это значит: `ds_worldtransfer` — не история попыток (одна строка на одну заявку), а
«текущее состояние заказа на пару (пользователь, мир-источник)» — повторная заявка из уже
использованного мира-источника не добавляет новую строку, а переписывает самую свежую существующую,
независимо от её прежнего статуса (отменена, завершена, заблокирована). Ни один из читанных классов
(`SU_FreeWorldTransfer`, `DS_RecieverWorld`, `SI_SearchFinalizeTransfer`) этого не учитывает — они
просто делают `INSERT`, ожидая новую строку. Практическое следствие для шага 4 в разделе 1.9
(`_disableSrcTransfer`, блокирующая вставка `transfer_type=5, status=3`): к этому моменту у
пользователя уже есть строка с тем же `db_from`/`id_world_from` — это только что обработанный
исходный заказ — поэтому «блокирующая вставка» на самом деле **не создаёт отдельную строку**, а
переписывает эту же строку заказа на `status=3` (с `db_to`/`id_world_to` из блокирующей записи).
Далее шаг 6 («`ds_worldtransfer.status` выставляется в `2`») должен адресовать её отдельным `UPDATE
... WHERE id_worldtransfer = <PK>` (по первичному ключу, не через этот триггер), чтобы итоговое
состояние совпало с описанным в разделе 1.9 — точный SQL этого шага не читался построчно в исходном
анализе, поэтому это **предположение**, а не подтверждённый факт.

Есть и побочный эффект: внутренний `UPDATE ... SET status = 2` внутри `worldtransfer_insert()` — это
обычный `UPDATE`, поэтому он запускает соседний AFTER-триггер `ds_worldtransfer_stat()` (см. ниже) для
каждой задетой строки. Если у задетой строки уже стоял `status = 2`, в лог `ds_worldtransfer_stat`
всё равно добавляется новая запись — то есть при каждой новой попытке переезда из уже использованного
мира-источника в `ds_worldtransfer_stat` может дублироваться запись об уже когда-то завершённом
переезде. Не проверено, мешает ли это `_GetUserTransferData()` в разделе 1.9.1 (она ищет **любую**
подходящую запись, возможно это не критично) — отмечено как риск, не как подтверждённый баг.

**`ds_worldtransfer_stat()` — источник журнала для восстановления из «Чёрной Дыры», теперь с точной
структурой.** `CREATE TRIGGER ds_worldtransfer_tr AFTER UPDATE ON ds_worldtransfer FOR EACH ROW
EXECUTE PROCEDURE ds_worldtransfer_stat()` (`schema.sql:31889`). При `NEW.status = 2` вставляет строку
`(id_user, db_from, db_to, id_world_from, id_world_to, transfer_date=now())` в `ds_worldtransfer_stat`
(`schema.sql:1458-1471`, структура таблицы — `schema.sql:22937-22945`). Это и есть «журнал уже
выполненных переносов», упомянутый в разделе 1.9.1 без структуры — раньше не было известно, какими
именно колонками и по какому условию (`status=2`, через `UPDATE`, не `INSERT`) он наполняется.

**`drop_transfer_to_drk2()` — НЕ про «Чёрную Дыру», и сейчас не подключена ни к одному триггеру.**
Название наводит на мысль о «Чёрной Дыре» (`hell`, dbId=9, id_world=3, раздел 1.9.1), но это неверно —
здесь задание на разбор ошибалось в гипотезе, и это стоит явно зафиксировать. Тело функции
(`schema.sql:858-871`):

```sql
IF NEW.db_to = 'level_2_drk' AND NEW.id_world_to = 1 THEN
    NEW.status = 2;
    NEW.transfer_date = now() - '10 day'::interval;
END IF;
RETURN NEW;
```

`'level_2_drk'` — это не сокращение от «дыра», а буквальный `server`-лейбл записи `dbId=2` в массиве
`$GLOBALS['dbWorldAccess']` (`ds/config/settings.php:59-68`): `dbname='drakon2'`, `server='level_2_drk'`
— мир **«Дракон»**, второй уровень («level_2»): `drk` = «ДРаКон», не «дыРКа». Этого мира нет в
текущем player-facing списке миров (раздел 1.12) — похоже на уже выведенный из эксплуатации мир
прежней версии сайта/более старого среза конфигурации (сам `dbWorldAccess`-массив в `settings.php`,
где это найдено, целиком относится к ветке `if ($uid==201 || …)`, то есть к конкретной, возможно
устаревшей, версии сайта — см. `multi-world-architecture.md` про переключение версий по `posix_getuid()`).

Функция задумана как «глушитель»: если новый заказ на перенос целится именно в
`db_to='level_2_drk'`/`id_world_to=1`, она подменяет его перед вставкой на уже как бы «успешно
завершённый 10 дней назад» — так `SU_ExecWorldTransfer` (который выбирает только `status=1`, раздел
1.5) никогда не подхватит такую заявку, и она тихо «исчезает» без ошибки игроку. Правдоподобный трюк
для отключения направления на уже не существующий мир без правки `ds_worldtransfer_worlds_relation`.

**Но**: в этом дампе схемы нет ни одного `CREATE TRIGGER`, ссылающегося на `drop_transfer_to_drk2`
(проверено по всему `schema.sql`, `grep` находит только определение функции) — то есть функция
объявлена, но **не подключена** ни к `ds_worldtransfer`, ни к какой-либо другой таблице на момент
снятия этого дампа. Мертва наравне с уже описанными в PHP примерами недостижимого кода (закомментированный
`_freezUser()` в разделе 1.10, сломанный путь `EH_ArmyMovementTo`/`EH_EventCreate` в
`mechanics-events-turns.md` §4.1). Если направление на «Дракон, уровень 2» ещё где-то физически
присутствует в `ds_worldtransfer_worlds_relation`, заказы на него сейчас пройдут по обычному
конвейеру, а не будут тихо гаситься этим триггером — не проверено, есть ли такая строка в живых
данных (в этом срезе — только схема, без данных).

**`insert_to_allowed_transfer()` — тоже orphaned-триггер, для `ds_worldtransfer_allowed_user`.**
Функция объявлена (`schema.sql:1758-1789`) и реализует «не понижать `transfer_required` с `true` на
`false`, а при совпадении id_userinstance — `UPDATE` вместо `INSERT`», но ни одного `CREATE TRIGGER`,
использующего её, в дампе тоже нет. Живая защита от дублей — только на стороне PHP:
`DS_RecieverWorld::AllowTransferForUser($UIID, $worldID, $dbId, $required)`
(`DS_RecieverWorld.class.php:135-181`) сам делает `SELECT ... WHERE id_userinstance=X AND
transfer_required=$r AND transfer_done=false` перед `INSERT`, но проверяет точное совпадение
`transfer_required`, а не просто «есть ли уже строка». Если у инстанса раньше появилась строка с
`transfer_required=false` (шаг «Разрешение», раздел 1.4.2, пункт 1), а затем срабатывает шаг
«Требование» (`transfer_required=true`, пункт 2) — PHP-проверка не находит совпадения и вставляет
**вторую** строку для того же `id_userinstance`, вместо того чтобы обновить первую (то, что умел бы
делать orphaned-триггер, будь он подключён). Не проверено, ломает ли задвоение строк что-то ниже по
цепочке — открытый вопрос.

Отдельно, в том же файле, соседний метод `DS_RecieverWorld::RequireTransferForUser($UIID, $dbId)`
(`DS_RecieverWorld.class.php:182-201`) содержит буквальную опечатку в SQL — `WHERE id_userinstance
$UIID` без оператора `=`. Это должно приводить к синтаксической ошибке PostgreSQL при каждом реальном
вызове; идущий следом `assert(empty($common->errormsg))` либо означает, что эта ветка на практике
никогда не вызывается (не проверено, кто её зовёт), либо что ассерты отключены в проде и ошибка
просто молча проглатывается — не выяснено в рамках этого прохода, стоит перепроверить отдельно.

### 1.3. Кто может инициировать перенос

| Способ | Точка входа | `transfer_type` | Кто инициирует |
|---|---|---|---|
| Самостоятельный (self-service) | `ds/FreeWorldTransfer.php` → `SU_FreeWorldTransfer` (`$_REQUEST['world']`/`action=cancel`) | 0 | Сам игрок, из личного кабинета/игрового интерфейса |
| Принудительный автоматический | `ds/utils/RecieverWorld.php` (крон) → `SU_RecieverWorldTransfers` → `DS_RecieverWorld::AddToTransferList()` | 5 | Система, по достижении лимитов мира (см. 1.4.2) |
| Ручной (админский) | `ds/movecomb/index_old.php?action=move` (CLI, параметры вручную) / `ds/movecomb/search.php` → `SI_ControlSearchStoreUser` (поиск игрока для переноса) | — (вызывает движок напрямую, минуя таблицу `ds_worldtransfer`) | Администратор |
| Billing (за деньги) | `ds/classes/BG/BG_ChangeWorld.class.php` | 2 | Заявлено, но **не реализовано** — см. 1.9 |

Игрок **не может** сам выбрать точные координаты соты в новом мире при самостоятельном переезде —
`SU_FreeWorldTransfer` позволяет разве что зарезервировать конкретные координаты под конкретные
**дополнительные** колонии (`coorld_mels`/`complex`+`comb`, вставка в `ds_reservedcomb` +
`ds_worldtransfercoordrelation`, `SU_FreeWorldTransfer.class.php:1209-1247`); базовая (домашняя) сота
всегда встаёт автоматически (режим координат `RESERVE`, см. 1.6).

### 1.4. Ограничения на перенос

#### 1.4.1. Самостоятельный (free) переезд — `SU_FreeWorldTransfer`

Проверка исходного мира, `_checkSourceWorldForTransfer()` (`SU_FreeWorldTransfer.class.php:873-913`):
- у игрока должен существовать инстанс в исходном мире (`DS_WorldTransfer::getPresenceInWorld`);
- из исходного мира не должно быть уже запущенного переезда (`status > 0` в `ds_worldtransfer`);
- не должно быть параллельно запущенного «квестового» переезда (`ds_worldtransferquest`, отдельный
  механизм для ивент/квестовых миров — не разбирался подробно, только его блокирующая проверка);
- исходный мир не должен быть заблокирован для ручных переездов (`status = 3`, см. 1.9).

Проверка целевого мира, `_checkTargetWorldForTransfer()` (`:922-1025`):
- игрок ещё не заказал переезд именно в этот мир (`DS_WorldTransfer::getUserTransferToWorld`, фильтр
  `transfer_type = 0`);
- у игрока ещё нет инстанса в целевом мире;
- пара миров разрешена (`ds_worldtransfer_worlds_relation` через `getInterWorldTransferInfo`,
  `DS_WorldTransfer.class.php:99-133`); если `only_for_premium = true`, требуется активный премиум
  (`DS_User::IsPremium`); если `free = false`, требуется персональное разрешение
  (`DS_WorldTransfer::isUserAllowedWorldTransfer`, `:410-430` — присутствие строки в
  `ds_worldtransfer_allowed_user` с `NOT transfer_done`);
- частота переездов по конкретной паре миров: если `frequency` (часы) > 0, за этот период не должно
  быть **ни одного** завершённого (`status = 2`) переезда между этими двумя мирами в любую сторону
  (`checkTransferFrequency`, `DS_WorldTransfer.class.php:530-583`, проверяются оба направления `A→B` и
  `B→A`);
- общий кулдаун — не более одного free-переезда за `freeTransferPeriod` часов (по умолчанию **168 часов
  = 7 дней**, `SU_FreeWorldTransfer.class.php:674`, переопределяется значением `frequency` конкретной
  пары миров), проверка `checkFreeTransferTime` (`DS_WorldTransfer.class.php:268-286`, статус `= 2`,
  `transfer_date > now() - period`);
- лимит на число колоний: если `(число_сот_у_игрока − (MAX_COLONIES_целевого_мира + 1)) > 1`, переезд
  запрещён; если разница `∈ (0, 1]`, игрок обязан явно выбрать одну колонию, которая после переезда
  станет «временной»/захватываемой (`tempColony`, помечается `is_capture`+защитное поле, см. 1.7.2).
  Формула (реконструкция из кода, `:986-1019`):
  ```
  allowedCombs = MAX_COLONIES(мир_назначения) + 1
  excess = countUserCombs(мир_источник) − allowedCombs
  если excess > 1        → перенос запрещён
  если 0 < excess ≤ 1     → обязателен выбор tempColony
  если excess ≤ 0         → без ограничений
  ```

Отмена (`action=cancel`) возможна только для заказа со статусом `1` (`_cancelTransfer()`,
`:1050-1142`) — переводит `status` обратно в `0`, удаляет резервирование координат
(`ds_reservedcomb`, `ds_worldtransfercoordrelation`).

#### 1.4.2. Принудительный (авто) переезд — `SU_RecieverWorldTransfers`

Запускается по крону (`ds/utils/RecieverWorld.php:8-45`) для каждого мира, где игровой модификатор
`WORLD_TRANSFER_ENABLE_AUTO_TRANSFER = 1` (`ds_gamemodifier`, per-world). Алгоритм
(`SU_RecieverWorldTransfers.class.php:41-58`):

1. **Разрешение** (`_getUsersForFreeTransfer`, `:93-116`) — помечает инстансы как «можно переехать
   бесплатно» (`transfer_required = false`), если игрок входит в топ-N рейтинга мира
   (`WORLD_TRANSFER_TOP_USERS_FOR_FREE_TRANSFER`) или играет в мире дольше
   `WORLD_TRANSFER_ENABLE_AFTER_DAYS` дней.
2. **Требование** (`_getUsersForRequiredTransfer`, `:68-91`) — аналогично, но
   `transfer_required = true`, по порогам `WORLD_TRANSFER_TOP_USERS_FOR_REQUIRED_TRANSFER` и
   `WORLD_TRANSFER_MAX_STAY_IN_WORLD` (дни).
3. Каждому вновь помеченному инстансу отправляется системное игровое письмо с параметрами условия
   (`_sendGameMessage`, `:118-127`).
4. **Принудительная постановка в очередь** (`_transferUserWithExceededTime`, `:197-244`) — среди уже
   помеченных `transfer_required` игроков, которые не начали переезд сами за
   `WORLD_TRANSFER_FREE_TRANSFER_DAYS` дней с момента пометки:
   - подбирается целевой мир через `GL_RecieverWorldGetWorldForTransfer::Get()`
     (`GL_RecieverWorldGetWorldForTransfer.class.php:7-45`) — перебор `ds_worldtransfer_worlds_relation`
     по убыванию `priority`, с проверкой, что мир вообще открыт для регистрации игрока
     (`GE_LogicWorldsList::GetWorldsToReg`) и что переезд именно туда ещё не заказан
     (`_checkWorldForTransfer`, `:47-79`);
   - если подходящий мир **не найден** — инстанс **удаляется немедленно**, минуя весь механизм заморозки
     из 1.9 (`DS_RecieverWorld::DeleteUser`, `SELECT drop_uiid(uiid)`,
     `DS_RecieverWorld.class.php:328-341`), кроме хардкод-списка из 8 защищённых логинов
     (`SU_RecieverWorldTransfers.class.php:218-222` — по всей видимости, тестовые/служебные аккаунты,
     **предположение**);
   - если мир найден — создаётся заказ (`DS_RecieverWorld::AddToTransferList`, `status=1,
     transfer_type=5`, `DS_RecieverWorld.class.php:281-325`), который дальше проходит **тот же** пакетный
     процесс переноса, что и free-переезд (раздел 1.6).

### 1.5. Пакетный запуск переноса — `SU_ExecWorldTransfer`

Отдельный крон-процесс (точный вызывающий скрипт не найден в срезе кода — **открытый вопрос**), который
в мастер-БД выбирает **все** строки `ds_worldtransfer` со `status = 1` независимо от их
`transfer_type` (`SU_ExecWorldTransfer.class.php:36-58`), формирует текстовый файл-очередь
`ds/utils/files/<VERSION_NAME>.move` (по строке на переезд:
`username;fromDbId;fromWorld;toDbId;toWorld;RESERVE;tempColony;updateTransferInfo.php`,
`:63-73`) и передаёт его в `SI_ExecMassMove`.

### 1.6. Фактическое исполнение — `SI_ExecMassMove` → `movecomb/index_old.php`

`SI_ExecMassMove::move()` (`SI_ExecMassMove.class.php:47-71`) построчно читает файл очереди и на **каждую
строку порождает отдельный OS-процесс**:

```
php -f movecomb/index_old.php <fromDBID> <fromUIID> <toDBID> <toWorldID> <tempColony> "combName|RING|COMPLEX|COMB" ...
```

Координаты новых сот вычисляются заранее в PHP-родителе, режим `RESERVE` (`_formCoordinatesFromReserve`,
`:261-271`, использует `GL_CombCoordsInNewWorld::Get()` — сопоставляет ранее зарезервированные через
`ds_reservedcomb`/`ds_worldtransfercoordrelation` координаты); есть и режимы `SAME` (те же
ring/complex/comb, что и в исходном мире) и `FREE` (первая свободная сота на том же кольце) — судя по
коду, используются другими, не автоматическими путями запуска того же класса.

Дочерний процесс — `SI_ControlMainInterfaceOld::main()` с `action='move'`. Комментарий в самом файле
прямо объясняет разделение с «новым» интерфейсом:

> «Хоть и завётся этот класс Old, он используется чаще чем новый вариант
> `SI_ControlMainInterface` — [тот] это интерфейс для отправки юзера в отпуск, который превратился в
> интерфейс ручного перевоза игрока между мирами из админки. Этот класс… используется для автоматических
> переездов» (`SI_ControlMainInterfaceOld.class.php:3-10`).

`_execMove()` (`:120-139`):
1. `_validateCombMoveAbility()` (`:222-241`) → `SI_TestRestoreUserInWorld::Test()` — все целевые
   координаты должны существовать и быть свободны, у игрока (по имени/e-mail) не должно быть уже
   инстанса в целевом мире (`SI_TestRestoreUserInWorld.class.php:5-46`).
2. `_storeUser()` (`:145-163`) — подключение к БД **источника**, `SET STATEMENT_TIMEOUT to 0`,
   `startTrans()`, вызов `SI_ControlStoreUserInstance::Exec()` (снятие снапшота, 1.7), `COMMIT`,
   блокировка исходного UIID через `SU_Locker('USERACTION')`.
3. `_restoreUser()` (`:178-196`) — переключение на БД **назначения**, аналогичная транзакция,
   `SI_ControlRestoreUserInstance::Restore()` (восстановление, 1.8), `COMMIT`, блокировка нового UIID.
4. При успехе печатает маркер `COMPLIT RESTORE OF UserInstance; Log ID: <id>` — это распознаёт
   родительский процесс (`SI_ExecMassMove::_callMoveScript()`, regexp на `:121`) и передаёт `<id>` как
   код результата (`moveResult`) в колбэк-скрипт `updateTransferInfo.php` (запускается ещё одним
   `exec()`, `:133-161`) → `SI_FinalizeWorldTransfer::Finalize()` (раздел 1.9).

### 1.7. Снятие снапшота — `SI_ControlStoreUserInstance::Exec()`

#### 1.7.1. Подготовка инстанса — `SI_PrepareUserForTransfer`

Перед тем как что-либо читать, `_refreshUser()` (`SI_ControlStoreUserInstance.class.php:76-86`) порождает
ещё один дочерний процесс: `php -f useraction.php <dbId> <UIID> 0 default 1`. Пятый CLI-аргумент `1`
включает `RD_AdeptUserActionToConsole::$prepareTransfer = true`
(`RD_AdeptUserActionToConsole.class.php:60-64`), из-за чего обычный конвейер `useraction.php`, прежде
чем выполнить (пустой) запрос, вызывает `SI_PrepareUserForTransfer::Exec($UIID)`
(`useraction.php:317-320`). Это единственное место, где инстанс **приводится к переносимому виду**
(`SI_PrepareUserForTransfer.class.php:21-61`):

| Шаг | Действие |
|---|---|
| `_endSpyEvents()` | Все летящие разведзонды (`SPY_MOVE_*`, физически — события с юнитом-зондом) возвращаются на исходную соту: `CURRENT_AMOUNT += amount`, `AWAY_AMOUNT -= amount`, событие помечается `isTrash` |
| `_endTrades()` | `ME_CancelAllUserTrades::Cancel()` — снимаются все активные лоты/ставки игрока на бирже (не разбирался в деталях — вне рамок задания) |
| `_returnMoneyToCabinet()` | **Всё** содержимое денежного счёта инстанса (`GC`, `SC` по отдельности, если `> 0`) переводится в личный («UC») кабинет тем же примитивом `MU_ExecTransferInterworld::Exec()`, что описан в Части 2 — **напрямую, в обход `UC_ExecMoneyTransfer` и его комиссии** (см. 2.5) |
| `_clearTradeDeliveryEvents()` | Завершаются события доставки товара/оплаты по уже совершённым сделкам |
| `_endTransfers()` | Завершаются события `TRANSFER_RESOURCE` (передачи ресурса/артефакта другому игроку) в процессе доставки |
| `_endBattles()` | Все бои с участием инстанса принудительно завершаются (`SI_SearchUserInstance::EndBattles`) |

Вывод: **на момент снятия снапшота у инстанса не может быть**: активных боёв, летящих армий/зондов,
активных лотов на бирже — это гарантируется кодом, а не является «мягким» ограничением. Долги по
ресурсам (отрицательный апкип, недостроенные здания в процессе — construction-события) переносятся как
есть (см. таблицу событий ниже) — деньги-долги как таковые в игре не встречаются (баланс `GC`/`SC` не
может уйти в минус, см. 2.2).

#### 1.7.2. Что именно переносится

`SI_ControlStoreUserInstance::Exec()` (`SI_ControlStoreUserInstance.class.php:23-73`) последовательно
собирает в один PHP-объект `SI_ResultUserInstance` (**не таблица БД** — чистый in-memory value-object,
живущий ровно в рамках одного PHP-процесса `movecomb/index_old.php`, между `_storeUser()` и
`_restoreUser()` того же запуска):

- журнал-запись `ds_worldtransfer_log` (id для последующей сверки, `AddLogRecord`);
- события инстанса (кроме боевых/перемещений, см. таблицу ниже);
- религия (`id_religion`, `id_status`);
- науки (`id_science`, флаг «в процессе исследования»);
- боевой опыт (`value`, `absolute_value`);
- **по каждой соте** (`SI_ExecStoreComb::Exec`, `SI_ExecStoreComb.class.php:4-33`):
  клетки и прикреплённые здания + их артефакты и атрибуты; артефакты в хранилище археологов; юниты
  домашней армии; атрибуты соты (ресурсы, производство и т. п.); торговые области (`tradescope`), кроме
  альянсовой (`id_tradescope = 4` исключается явно, `:93-95`); если сота выбрана как `tempColony` —
  флаг `is_capture` + защитное поле;
- бонусы игрока, вес построенных/потерянных армий (`army_weight_built`/`army_weight_lost`), снапшот
  рейтинга, цепочка колонизации (родитель→дочерняя колония), список «колонизаторов», список ID
  доступных способностей (`ability`).

**Что не переносится вовсе** (нет ни одного шага сбора для этого): альянс (членство в локальном альянсе
мира не переносится — при переезде игрок выходит из альянса; глобальный/межмировой альянс, если он есть,
живёт отдельно и не завязан на конкретный локальный инстанс, см. `game-engine.md`, раздел про альянсы);
логи боёв/шпионажа/чата; персональная переписка (`ds_message`) — переносятся только базовые
`messagescope` (кому можно писать), сами письма нет.

**События**: список исключённых из сохранения событий жёстко задан
(`SI_ExecStoreUserEvents.class.php:14-25`):

| Событие | Судьба |
|---|---|
| `ARMY_MOVEMENT_TO/FROM`, `ATTACK`, `BATTLE_ROUND`, `MOVE_TO_DEFEND`, `DEFEND`, `MOVE_TO_COMB`, `HIDE_ARMY` | Не сохраняются вовсе — но их и не может быть на момент снятия снапшота, т.к. 1.7.1 уже завершил все бои и вернул все армии/зонды на соту |
| Все остальные (стройка, апгрейд, обучение юнитов, наука, терраформинг, археология, колонизация, доставка по бирже/трансферу) | Сохраняются с датами, пересчитанными в «дельту ходов»: `startdelta = (event_start − now) / turnTime_source`, `enddelta = (event_end − now) / turnTime_source` (`SI_ExecStoreUserEvents.class.php:65-70`) — это то, что переживает разницу в длительности хода между мирами |

Для событий торговли/доставки перед сохранением подменяются поля продавец/покупатель на сторону,
относящуюся к переезжающему игроку (`_processGoodDelivery`/`_processTrading`/`_processPaymentDelivery`,
`:109-153`); для `TRAIN_WAR_UNIT` заново считается время окончания как
`startdelta + CountToTrain × CONSTRUCTION_TIME` (`_precessTrainWarUnits`, `:155-165`).

### 1.8. Восстановление — `SI_ControlRestoreUserInstance::Restore()`

(`SI_ControlRestoreUserInstance.class.php:20-91`)

1. Повторная проверка целевых координат (`SI_TestRestoreUserInWorld::Test`, те же условия, что в 1.6.1).
2. Создаётся **новый** `ds_userinstance` (новый `UIID`, тот же `id_user`) —
   `newbDefend' = max(newbDefend_старый, now()) + WORLD_TRANSFER_NEWB_DEFEND(мир_назначения)·24·3600`
   (`:166-179`, `WORLD_TRANSFER_NEWB_DEFEND` — настройка мира в днях) — то есть переезд **всегда
   продлевает/устанавливает заново** защиту новичка в целевом мире на настраиваемый срок.
3. Восстанавливаются науки, религия, боевой опыт.
4. **По каждой соте** (`SI_ExecRestoreComb::Exec`, `SI_ExecRestoreComb.class.php:24-59`): сота
   привязывается к новым координатам (имя соты при конфликте получает случайный суффикс из 3 цифр,
   `_testCombName`, `:94-109`); клетки/здания/артефакты/атрибуты и «домашняя» армия воссоздаются;
   **если мир назначения имеет тип `RACE_VS_RACE`** и юнит не соответствует новой расе игрока — юнит не
   восстанавливается, а логируется как погибший (`TestSameRaceOfUserAndUnit`,
   `SI_ExecRestoreComb.class.php:211-218`); торговые области восстанавливаются как есть.
5. События воссоздаются с обратным пересчётом дат под **турн целевого мира**:
   `new_start = now() + round(startdelta·turnTime_целевой)`, аналогично `new_end`
   (`SI_ExecRestoreUserEvents.class.php:166-174`); все внутренние ссылки (ID соты/здания/клетки/юзера)
   переписываются через построенный на лету словарь соответствий `idDictionary` (старый ID → новый ID).
6. Особый режим для нескольких типов событий (`SI_ExecRestoreUserEvents.class.php:14-20`):

   | Событие | Обработка при восстановлении |
   |---|---|
   | `TRADING` | Не восстанавливается как активный лот/ставка — вместо этого содержимое (ставка деньгами или выставленный товар) **возвращается в ресурсы/юниты/артефакты** новой соты через `SI_ExecReturnResources::Exec()` (`_returnResourcesFromTrade`, `:313-365`) |
   | `COLONIZE`, `SPY_MOVE_TO_RESEARCH`, `SPY_MOVE_FROM_RESEARCH` | Просто отбрасываются — **без возврата/компенсации** (в коде не найдено ни одной компенсирующей ветки для этих трёх типов — **открытый вопрос**, возможно осознанная потеря) |

`SI_ExecReturnResources::Exec()` (`SI_ExecReturnResources.class.php:5-59`) умеет зачислить обратно
`RESOURCE` (дельта на `ds_combhasresource`), `ARTEFACT` (создание инстанса артефакта в хранилище
археологов) или `UNIT` (создание/увеличение группы юнитов в домашней армии) — то есть единый механизм
возврата на все три категории биржевых лотов.

### 1.9. Финализация — `SI_FinalizeWorldTransfer::Finalize()`

Вызывается колбэком `updateTransferInfo.php` (CLI, аргументы: `uid, fromDBID, fromWorldID, toDBID,
toWorldID, fromUIID, moveResult`), где `moveResult` — распарсенный `id_worldtransfer_log` (см. 1.6) или
`0`, если процесс переноса не дошёл до маркера успеха. Пошагово
(`SI_FinalizeWorldTransfer.class.php:39-83`), каждый шаг условно пропускается, если предыдущий уже
выставил `self::$error`:

1. `moveResult < 1` → сразу выход, печатает `OK. RESULT: N` без каких-либо изменений в БД (`:103-111`).
2. Если исходный инстанс уже был в отпуске/заморозке ДО переноса (пароль `ds_vocationmode` вида
   `pereezd_*`) — такая же заморозка ставится и новому инстансу (перенос «замороженного» состояния,
   `:118-131`).
3. **Заморозка исходного инстанса** — вставка `ds_vocationmode(startdate=now(), enddate=NULL,
   password='pereezd_'.rand())` (`SI_SearchFinalizeTransfer::AddVocation`, `:150-163`). Это и есть
   технический механизм «отключения» старого инстанса — сота не удаляется сразу, а замораживается
   бессрочно (как отпуск без даты окончания).
4. **Блокировка повторных переездов** из старого мира: вставка `ds_worldtransfer(transfer_type=5,
   status=3)` (см. таблицу статусов, 1.2) плюс `ds_worldtransfer_allowed_user(transfer_required=true,
   transfer_done=true)` (`_disableSrcTransfer`, `:169-183`).
5. **Контрольная сумма**: MD5-хэши состава армии, суммы ресурсов, списка ID артефактов и списка ID
   зданий считаются **отдельно на источнике и на назначении** и построчно сравниваются
   (`_validateSrcAndDstCheckSum`/`_getInstanceCheckSum`, `:189-232`, хэши — SQL-агрегаты в
   `SI_SearchFinalizeTransfer::GetUserArmySum/GetUserResourceSum/GetUserArtefactSum/GetUserBuildingSum`,
   `SI_SearchFinalizeTransfer.class.php:118-236`). При **любом** расхождении выставляется `$error`, и все
   оставшиеся шаги (постановка в очередь на удаление, обновление статуса, регистрация в
   `ds_userinstancecollection`) **пропускаются** — то есть при сбое чек-суммы перенос остаётся
   технически «подвешенным»: заморозка и блокировка уже применены, но `ds_worldtransfer.status`
   останется `1`, а новый инстанс не попадёт в кросс-мировой реестр. Каждый шаг отдельно фиксируется
   булевым полем в `ds_worldtransfer_log` (`account_freezed`, `transfer_disabled`, `checksum_validated`,
   `added_to_delete_list`, `added_to_collection`, `transfer_status_updated` —
   `SI_SearchFinalizeTransfer::MarkSuccessFinalizeStage`, `:262-284`), что даёт возможность точечно
   диагностировать, на каком шаге остановился конкретный перенос.
6. Если чек-сумма сошлась: старый инстанс ставится в глобальную очередь на удаление
   (`SU_DeleteUserList::AddToList($user, $world, 'TRANSFER')`, `_addSrcToDeleteList`, `:238-249`) — **само
   удаление происходит не здесь**, а отдельным (не найденным в этом срезе кода) процессом, читающим эту
   очередь — **открытый вопрос**, аналогичный уже отмеченному в `multi-world-architecture.md` для
   `DU_ClearWorld`; `ds_worldtransfer.status` выставляется в `2`; новый `UIID` регистрируется в
   мастер-таблице `ds_userinstancecollection` (`AddUserToCollection`, `SI_SearchFinalizeTransfer.class.php:198-224`)
   — та же таблица, что поддерживают `WorldUtils::addNewInstance` и `CollectWorlds` (см.
   `multi-world-architecture.md`, разделы 2 и 7).

**Судьба старого инстанса, итог:** он не удаляется мгновенно и не архивируется в отдельную БД — он
переводится в бессрочный «отпуск» (`ds_vocationmode`, без даты окончания), блокируется от повторных
переездов, и ставится в очередь на физическое удаление, которое выполняется позже вне рамок
разобранного кода.

**Билинг-переезд (`transfer_type=2`) не реализован до конца**: `BG_ChangeWorld::check()`
(`BG_ChangeWorld.class.php`) полностью проверяет пригодность направления (регистрация в мирах, тип
мира, наличие свободных сот), но `move()` — пустая заглушка с закомментированным вызовом
`//$userInstanceResult = SI_ControlStoreUserInstance::Exec($this->fromUIID);` (`:80-86`) — то есть гейт
может проверить возможность переезда, но реально его не выполняет. **Предположение**: либо
функциональность была свёрнута до релиза, либо перенесена в закрытую часть биллинга, не попавшую в этот
срез кода. **Важная оговорка** (см. 1.9.1 ниже): этот вывод верен только для *обобщённого* платного
переезда «из любого мира в любой» через `BG_ChangeWorld` — платный переезд в/из специального мира
«Чёрная Дыра» устроен принципиально иначе и **полностью реализован**, минуя `BG_ChangeWorld`.

### 1.9.1. Особый случай — переезд в «Чёрную Дыру» и восстановление из неё (реализовано, но не через `BG_ChangeWorld`)

Player-facing источник (`https://destinysphere.ru/?p=user_move`, раздел 1.12 ниже) описывает
специальный мир «Чёрная Дыра»: время в нём остановлено, сота может там находиться «в течение года»,
восстановление в обычный мир — платное, через `https://billing.destinysphere.ru/`. Это подтверждается
кодом — но не через `BG_ChangeWorld` (который выше назван нереализованной заглушкой), а через
отдельную пару классов личного кабинета:

- «Чёрная Дыра» — это не абстракция, а **конкретный, жёстко закодированный мир**:
  `dbId = 9`, `id_world = 3`, `dbname = 'hell'` (внутреннее кодовое имя, буквально — «ад»)
  (`UC_ExecTransferCombToBlackHole.class.php:12-16`, `UC_ExecRestoreCombFromBlackHole.class.php:12-16`).
- **Отправка в «Чёрную Дыру»** — `UC_ExecTransferCombToBlackHole::Exec()`
  (`ds/classes/UC/UC_ExecTransferCombToBlackHole.class.php`), платная услуга `PS_TRANSFER_COMB_TO_BLACK_HOLE`
  (тарификация — общий `UC_PaymentServiceBase`/`UC_PaymentServiceMoneyOperation`, как у любой другой
  платной услуги кабинета). Проверяет, что у игрока ещё нет соты в «Чёрной Дыре» и нет уже
  запущенного переноса в этом или в целевом мире (`_ShowSecondMenu`, `:92-169`), затем списывает
  деньги и **вставляет обычную строку в `ds_worldtransfer`** с `transfer_type = 0` (`_SetTransferInfo`,
  `:253-275`) — то есть дальше это в точности тот же пакетный конвейер `SU_ExecWorldTransfer` →
  `SI_ExecMassMove` → `SI_ControlStoreUserInstance`/`SI_ControlRestoreUserInstance` →
  `SI_FinalizeWorldTransfer`, что описан в разделах 1.5–1.9 — никакого отдельного движка для «Чёрной
  Дыры» нет, только другой (специальный) мир назначения и оплата вместо бесплатного лимита.
- **Восстановление из «Чёрной Дыры»** — `UC_ExecRestoreCombFromBlackHole::Exec()`
  (`ds/classes/UC/UC_ExecRestoreCombFromBlackHole.class.php`), платная услуга
  `PS_RESTORE_COMB_FROM_BLACK_HOLE`. Ищет, из какого мира игрок когда-то отправил соту в «Чёрную
  Дыру» — по `ds_worldtransfer_stat` (журнал уже выполненных переносов) либо, если там не нашлось,
  по `ds_storeuserdata` (`_GetUserTransferData`, `:101-165`, таблица `ds_storeuserdata` — не
  разбиралась подробно в остальном документе, вероятно ещё один способ регистрации "заморозки",
  отдельный от `ds_vocationmode` из 1.9) — затем так же вставляет обратную строку в `ds_worldtransfer`
  (`hell` → мир-источник, `transfer_type = 0`) и она уходит по тому же стандартному конвейеру.
- Явной проверки «прошёл ли ровно год» **не найдено** ни в одном из двух классов — ограничение «до
  года», названное на сайте, либо проверяется где-то ещё (не найдено в этом срезе кода), либо это не
  жёсткий технический лимит, а данные могут физически лежать в `hell`-мире куда дольше.
  > ✅ **Резолвнуто (2026-09-08, подтверждено владельцем проекта)**: это не жёсткий
  > технический лимит — «Чёрная Дыра» служит **кладбищем старых аккаунтов**: соты неактивных
  > игроков отправляются туда на **вечное** (не годичное) хранение по запросу самого игрока,
  > никакой другой функции у этого мира нет. «До года» на сайте — устаревшая/неточная
  > формулировка маркетингового текста, не отражающая реальный технический лимит (которого,
  > судя по коду, и правда нет). Согласуется с независимой находкой в
  > `mechanics-events-turns.md` §9.2 — аномальный `TURN_TIME=6000` минут на этом мире
  > фактически замораживает экономику, что естественно для мира, предназначенного только для
  > долгосрочного хранения неактивных сот, а не для игры.

Таким образом, вывод «билинг-переезд не реализован» (предыдущий абзац) относится строго к попытке
переехать в **произвольный** мир за деньги (`BG_ChangeWorld`, вызывается из
`ds/gate/billing_receiver.php` как `act=change_world` или аналогичный — сам диспетчер описан в
`billing-and-monetization.md`, раздел 6). Перенос в фиксированный мир-«отстойник» «Чёрная Дыра» и
обратно — рабочая, полностью реализованная платная функция личного кабинета, независимая от
`BG_ChangeWorld`.

### 1.10. Аудит и защита от дублей — `SU_TransferValidate`

Отдельный плановый прогон (`SU_TransferValidate::Validate($dbID, $worldID)`,
`SU_TransferValidate.class.php:281-318`) читает из `ds_worldtransfer_log` все ещё не помеченные
(`transfer_valid` не `true`) записи и для каждой проверяет состояние **исходного** инстанса
(`_validate()`, `:441-496`):

| Результат | Условие |
|---|---|
| `OK` | у пользователя вовсе нет соты в исходном мире, либо явного дубля не найдено |
| `INFO` | сота стоит в очереди на удаление (`ds_user_delete_list`) — штатный путь после успешного переноса |
| `WARNING` | сота заморожена (`ds_vocationmode` с `enddate IS NULL`) — штатная заморозка (1.9, шаг 3) |
| `ERROR` | найден **дубль**: у того же `id_user` появился **второй** инстанс (в любом мире версии) с `creation_date` в пределах ±30 минут (1800 сек) от даты переноса, и этот дубль **не заморожен** (`_checkDoubleComb`, `:499-540`) |

Результаты собираются в HTML-таблицу и отправляются письмом на хардкоженный адрес
(`nikita@destinysphere.com`, `_formStat`, `:345-386`) — это ручной («по e-mail») контур обратной связи,
не автоматическое исправление. Ветка автоматической заморозки дубля-нарушителя присутствует в коде, но
**закомментирована и никогда не вызывается** (`_freezUser()`, `:415-439` — содержит опечатку `ISNERT
INTO` вместо `INSERT INTO`, что дополнительно подтверждает, что этот путь никогда не выполнялся).
Прошедшие проверку записи помечаются `transfer_valid = true` (`_markAsValid`, `:572-588`).

Это прямой аналог `MU_InterworldController` из Части 2 — только там сверяется целостность денег, а
здесь — отсутствие задвоенных инстансов игрока.

### 1.11. Побочное использование инфраструктуры SI_*

`SI_ControlApplyTemplate::Apply()` (вызывается из `GE_ExecStartCreateInstance` при создании **нового**
персонажа и из `SU_PopulateNPC` при заселении NPC) использует ту же связку
«снять снапшот эталонного шаблона-комба → восстановить в целевой соте» (через
`SI_ExecStoreCombTemplate`/`SI_ExecRestoreComb` в режиме `SetTemplateMode(true)`), но это **не перенос
существующего игрока между мирами**, а способ раздать «стартовый набор» построек новому инстансу —
упомянуто здесь только чтобы не спутать с собственно межмировым переносом при последующем чтении кода.

Отдельная находка по схеме БД, тоже касающаяся создания **нового** (не переносимого) инстанса:
`WorldUtils::addNewInstance()` (`Admin/include/WorldUtils.php:409-411`, дублируется в
`ds/classes/WorldUtils.class.php`) создаёт строку `ds_userinstance` не прямым `INSERT`, а вызовом
PL/pgSQL-функции `SELECT insertUserInstance(id_user, id_race, id_world) AS uiid`
(`schema.sql:2081-2100`), которая жёстко выставляет `LastRefresh = now()` при создании. Это закрывает
открытый вопрос №9 из `mechanics-events-turns.md` §14 (где не было найдено место инициализации
`lastrefresh` для нового игрока) — подробности и точная формулировка там же.

### 1.12. Player-facing подтверждения и расхождения (источник: `https://destinysphere.ru/?p=user_move`)

Официальная справочная страница сайта для игроков (не код) описывает межмировой переезд с точки
зрения пользователя. Ниже — сверка с выводами по коду выше; расхождения отмечены явно.

**Подтверждается кодом:**

- **Направление «младший мир → старший мир»** и общий принцип, что не любой мир достижим из любого:
  страница прямо говорит про цепочку миров по «уровню развития» и что «переехать можно… как из мира
  класса Раса против Расы в мир Каждый против Каждого, так и наоборот», но не куда угодно. Это ровно
  граф `ds_worldtransfer_worlds_relation` из раздела 1.2/1.4.1. Отдельно собранная выгрузка
  сводной страницы настроек мира (`https://destinysphere.ru/?p=game_option`, поле «Из мира возможен
  переезд в миры», player-facing данные, не код) даёт живой пример этого графа на момент сбора данных
  по всем ~11 текущим мирам:

  | Мир-источник | Тип | Уровень | Доступные направления |
  |---|---|---|---|
  | Змееносец | Каждый против Каждого | IV | Черная Дыра |
  | Волк | Каждый против Каждого | II | Эридан |
  | Орион | Каждый против Каждого | IV | Черная Дыра, Змееносец |
  | Ворон | Каждый против Каждого | III | Черная Дыра, Эридан, Орион, Змееносец |
  | Феникс | Каждый против Каждого | III | Черная Дыра, Пегас, Эридан, Орион, Змееносец |
  | Стрелец | Каждый против Каждого (новички) | I | Черная Дыра, Волк, Пегас, Водолей, Ворон, Феникс, Эридан, Орион, Змееносец |
  | Эридан | Раса против Расы | IV | Черная Дыра, Змееносец |
  | Пегас | Раса против Расы | III | Черная Дыра, Феникс, Эридан, Орион, Змееносец |
  | Водолей | Раса против Расы | III | Черная Дыра, Пегас, Ворон, Феникс, Эридан, Орион, Змееносец |
  | Лев | Раса против Расы | II | Черная Дыра, Пегас, Водолей, Ворон, Феникс, Эридан, Орион, Змееносец |
  | Терра Нова | Раса против Расы («приёмка») | I | Черная Дыра, Лев, Волк, Пегас, Водолей, Ворон, Феникс, Эридан, Орион, Змееносец |

  Закономерность видна наглядно: чем ниже «уровень развития» мира-источника, тем шире список
  разрешённых направлений (у обоих миров для новичков — Стрелец и Терра Нова — самый широкий список),
  а у самых развитых миров (Змееносец, Волк) — один-два направления. Это независимое,
  количественное подтверждение механизма `priority`/направленного графа из раздела 1.2, но именно
  **числа** (какой мир куда именно пускает) взяты с сайта, а не из `ds_worldtransfer_worlds_relation` —
  в коде эта таблица не выгружена.
- **Уничтожение юнитов чужой расы при переезде в мир `RACE_VS_RACE`** — «ВАЖНО! При переезде из мира
  класса «Каждый против Каждого» в мир класса «Раса против Расы» юниты не вашей расы будут
  уничтожены!» дословно совпадает с `SI_ExecRestoreComb::TestSameRaceOfUserAndUnit`
  (`SI_ExecRestoreComb.class.php:211-218`, раздел 1.8) — юнит несоответствующей расы не
  восстанавливается и логируется как погибший.
- **Обязательный переезд из Терра Новы** — «ВАЖНО! Переезд из Терра Новы в другие миры обязательный
  для всех игроков. Если игрок… не выбрал самостоятельно… мир… в течение недели… это будет сделано
  автоматически» — прямое player-facing описание механизма принудительного авто-переезда
  (`SU_RecieverWorldTransfers`, раздел 1.4.2): Терра Нова — это в терминах сайта «мир-приёмка»
  (соответствует `WORLD_TRANSFER_ENABLE_AUTO_TRANSFER=1` для этого мира), а «неделя» с точностью до
  выбора конкретного числа — player-facing конкретизация настройки
  `WORLD_TRANSFER_FREE_TRANSFER_DAYS`, точное значение которой в коде не захардкожено (world-setting).
- **Апкип юнитов различается по мирам** — «ВАЖНО! В каждом мире есть свой индивидуальный параметр
  апкипа военных юнитов» соответствует `game_option`-параметру «Коэффициент расчёта стоимости
  проживания юнитов на соте» (per-world, см. пример значений в `mechanics-economy-trade.md`).
- **Платный переезд в/из «Чёрной Дыры»** и **бронирование соты за плату** — оба подтверждены кодом,
  см. раздел 1.9.1 (`UC_ExecTransferCombToBlackHole`/`UC_ExecRestoreCombFromBlackHole`) и
  `BG_CombReserv.class.php` (резервирование конкретных координат по запросу от
  billing.destinysphere.ru — та же таблица `ds_reservedcomb`, что использует бесплатный self-service
  в разделе 1.3).

**Не подтверждено напрямую (правдоподобно, но не проверено по коду в рамках этого документа):**

- «Платные переезды между мирами проводятся только исходя из схемы разрешённых переездов» — логично
  ожидать, что платный путь (`BG_ChangeWorld`) переиспользует ту же таблицу
  `ds_worldtransfer_worlds_relation`, что и self-service (раздел 1.4.1), но поскольку
  `BG_ChangeWorld::move()` — незавершённая заглушка (раздел 1.9), а `check()` не разбирался построчно
  в этом документе, это осталось предположением, а не подтверждённым фактом.
- Обмен местами с другим игроком **при переезде между мирами** («Переезд из игрового мира в
  специальный мир Черная Дыра» соседствует на странице с фразой про обмен, но пример с обменом на
  странице разобран только для переезда **по кольцу внутри одного мира** — см. Часть 3 ниже). Не
  найдено кода для обмена сотами **между мирами** — вероятно, для межмировых переездов обмена не
  существует вовсе, только для внутримирового (Часть 3).

---

## Часть 2. Межмировые переводы денег

### 2.1. Модель денежного счёта

Каждый **(игрок, мир)** имеет ровно один счёт в таблице `ds_money_account (id_user, world_identifier,
gc, sc, is_active, id_account)`, физически хранящейся **в БД того самого мира**
(`MU_SearchOperations::GetAccountData`, `MU_SearchOperations.class.php:80-115` — читает по
`(id_user, world_identifier)`; если счёта ещё нет, лениво создаёт его хранимой процедурой
`create_money_account(id_user, world_identifier)`). Особый случай — `world_identifier = 'UC'`:
виртуальный «мир», представляющий **личный кабинет** игрока (не имеет сот/армий, существует только как
денежный хаб между реальными мирами; физически эта таблица лежит в мастер-БД версии — **предположение**,
исходя из того, что весь `UC_*`-функционал работает вне контекста конкретного игрового мира).

Каждый мир, помимо игроков, имеет служебный **системный счёт** — тот же `ds_money_account`, но с
`id_user = 0` (`MU_EntityOperationMember::CreateSystemAccount()`,
`MU_EntityOperationMember.class.php:37-42`). Именно через системный счёт проходят обе половинки
межмирового перевода (2.3).

Валюты: `GC` (Золотые кредиты) и `SC` (Серебряные кредиты) — `MU_EntityMoneySumm::getCreditName() ∈
{'GC','SC'}`. Баланс никогда не уходит в минус — единственная проверка достаточности выполняется прямо
перед списанием (2.2); отдельного понятия «долг» в денежном движке нет.

Типы денежных операций (`ds_money_action`, обёртка `DE_MoneyActionType`,
`DE_MoneyActionType.class.php:2-160`) заданы в БД, а не хардкодом: у каждого типа есть имя обработчика
(`handler_class_name`), обязательные `from_world`/`to_world`/`id_user_from`/`id_user_to` (жёстко
фиксируют, между какими ролями допустима эта операция), флаг «нужен ли внешний коррелирующий ID»
(`need_additionl_id` — включён именно для межмировых операций, это и есть `transfer_key`, см. 2.3),
флаг «нужен ли GateKey» (`need_gate_key` — отдельная инфраструктура одноразовых ключей, см.
`multi-world-architecture.md` §5, к переводам напрямую не относится по найденным вызовам), флаг
`is_active` (можно централизованно выключить конкретный тип операции) и имя таблицы лога
(`log_action_table`).

### 2.2. Примитив внутримирового перевода — `MU_ExecTransfer`

Все деньги в конечном счёте двигаются только этим методом (`MU_ExecTransfer::Exec($listID,
$operation)`, `MU_ExecTransfer.class.php:5-60`), и **только между двумя счетами одного и того же
мира**:

```
assert(memberFrom.world == memberTo.world)     // оба счёта — в одном ds_money_account одной БД
assert(memberFrom.userID != memberTo.userID)   // нельзя перевести самому себе
assert(accountFrom.is_active && accountTo.is_active)
assert(accountFrom[currency] >= summ.amount)   // средств должно хватать

UPDATE ds_money_account SET <currency> = <currency> - amount WHERE (memberFrom)
INSERT INTO ds_money_account_payments (..., -amount, id_list=listID) -- для memberFrom
UPDATE ds_money_account SET <currency> = <currency> + amount WHERE (memberTo)
INSERT INTO ds_money_account_payments (..., +amount, id_list=listID) -- для memberTo
```

Диспетчеризация (`MU_ControlExecOperations::ExecOperation`, `MU_ControlExecOperations.class.php:5-18`):
`$operation->validate()` → блокировка (`MU_ExecLockOperation::Lock`, не разбиралась подробно, по
косвенным признакам — блокировка по ключу счёта через ту же MySQL-locking-инфраструктуру, что описана в
`multi-world-architecture.md` §4.2, **предположение**) → создаётся обработчик по имени класса из
`DE_MoneyActionType::getHandlerName()` → `$handler->exec()`.

### 2.3. Примитив межмирового перевода — `MU_ExecTransferInterworld`

Поскольку `MU_ExecTransfer` физически не может тронуть две разные PostgreSQL-базы одной SQL-операцией,
межмировой перевод — это **две независимые локальные транзакции**, связанные только общим случайным
ключом (`MU_ExecTransferInterworld.class.php:5-30`):

```
MU_ExecTransferInterworld::Exec(memberFrom [мир A], memberTo [мир B], summ):
    assert(memberFrom.userID == memberTo.userID)     // перевод только самому себе между своими счетами
    assert(memberFrom.world != memberTo.world)

    key = случайная строка, 25 символов (GB_RandomPassword::Generate(25))

    // Шаг 1 — в БД мира A (одна локальная транзакция):
    операция SEND_INTERWORLD_TRANSFER: пользователь@A → системный_счёт@A
        логируется в ds_money_list + ds_money_list_interworld_send(to_world_idetifier = B, transfer_key = key)
        затем выполняется как обычный MU_ExecTransfer (реальное списание с игрока)

    // Шаг 2 — в БД мира B (другая, полностью независимая локальная транзакция):
    операция RECIVE_INTERWORLD_TRANSFER: системный_счёт@B → пользователь@B
        логируется в ds_money_list + ds_money_list_interworld_recive(from_world_identifier = A, transfer_key = key)
        затем выполняется как обычный MU_ExecTransfer (реальное зачисление игроку)
```

Ключевой архитектурный факт: **между шагом 1 и шагом 2 нет распределённой транзакции** — это два
раздельных `COMMIT` в двух разных PostgreSQL-базах. Если процесс упадёт между ними (сеть/краш/деплой),
получится либо «список без прихода» (деньги списаны в мире A, но не зачислены в мире B), либо, в редких
случаях ручного вмешательства — обратная ситуация. Именно для этого существует плановая сверка (2.6).

`MU_ExecOperationSendInterworldTransfer`/`MU_ExecOperationReciveInterworldTransfer`
(`MU_ExecOperationSendInterworldTransfer.class.php:5-28`,
`MU_ExecOperationReciveInterworldTransfer.class.php:5-28`) — это конкретные обработчики (те самые
`handler_class_name` из 2.1) для действий `SEND_INTERWORLD_TRANSFER`/`RECIVE_INTERWORLD_TRANSFER`:
проверяют, что операция идёт от/к системному счёту (`getToUser()->getUserID() == 0` для отправки,
`getFromUser()->getUserID() == 0` для приёма), пишут строку лога через
`MU_SearchOperations::InsertOperationLog()` (общий `ds_money_list.id_list`, затем таблично-специфичные
поля через `MU_EntityOperationLogRecord`), и делегируют реальное движение денег в
`MU_ExecTransfer::Exec()`.

### 2.4. Точка входа игрока — личный кабинет `UC_ExecMoneyTransfer`

Игрок не переводит деньги «из мира A прямо в мир B» одним действием — интерфейс личного кабинета всегда
работает через хаб `'UC'` (`UC_ExecMoneyTransfer.class.php`):

- **`ExecTransferToUser`** (`:177-243`) — вывод денег **из конкретного мирового инстанса в кабинет**
  (мир → `UC`).
- **`ExecTransferToInstance`** (`:246-285`) — обратное: **из кабинета в конкретный мировой инстанс**
  (`UC` → мир).

Оба метода в конечном счёте вызывают `MU_ExecTransferInterworld::Exec()` (2.3) отдельно для `GC` (если
`> 0`) и отдельно для `SC` (если `> 0`) — то есть перевод обеих валют одним действием технически
разбивается на до двух независимых межмировых переводов с **разными** ключами `transfer_key`.

**Комиссия.** Применяется **только** при выводе `SC` из мира в кабинет (`ExecTransferToUser`,
`:205-236`), только если у мира-источника задана настройка `USER_CABINET_TRANSFER_TAX` (проценты,
`DE_WorldSettings`, per-world) `> 0`:

```
tax_sc = ceil(USER_CABINET_TRANSFER_TAX × sc / 100)
sc_к_переводу = sc − tax_sc
```

Комиссия списывается отдельной внутримировой операцией `PAY_TRANSFER_TAX` (пользователь → системный
счёт **того же мира**, `:214-223`) **до** того, как оставшаяся сумма уходит межмировым переводом. `GC`
комиссией никогда не облагается; перевод в обратную сторону (`UC → мир`) комиссии не имеет вовсе — в
`ExecTransferToInstance` налоговой логики нет совсем.

**Валидация** (общая для обоих направлений, `_checkTransferToUser`/`_checkTransferToInstance`,
`:33-114`):

| Код ошибки | Условие |
|---|---|
| `NO_ACCOUNT` / `NO_ACCOUNT_IN_WORLD` | счёт ещё не существует (создаётся лениво при первом обращении к `GetAccountData`, но проверка идёт до этого — **проверить в реализации**, возможно счёт всегда успевает создаться) |
| `ACCOUNT_INACTIVE` / `ACCOUNT_INACTIVE_IN_WORLD` | `is_active = false` на счёте |
| `WRONG_CREDITS_COUNT` | запрошено отрицательное количество, либо `gc = 0 И sc = 0` |
| `NOT_ENOUGHT_GOLD_CREDITS` / `NOT_ENOUGHT_SILVER_CREDITS` | запрошенная сумма превышает баланс соответствующей валюты |

Никакого фиксированного **лимита на сумму** (ни минимума, ни максимума за операцию/за период) в этом
классе не найдено — единственное ограничение по объёму — сам баланс счёта.

**Защита от повторной отправки** — сессионный флаг (`$_SESSION['transfered_to_user']` /
`$_SESSION['transfered_to_instance']`), выставляется в `1` сразу после первого успешного выполнения
(`:238-243`, `:280-285`) и блокирует повторное срабатывание той же confirm-формы (double-submit по
кнопке/обновлению страницы); это **не** идемпотентный ключ на уровне HTTP-запроса и не имеет отношения к
`transfer_key` из 2.3 — при параллельных вкладках/запросах теоретически возможен обход этой защиты
(гонка не была протестирована в рамках анализа — **открытый вопрос**).

### 2.5. Связь с переносом инстанса (Часть 1)

`SI_PrepareUserForTransfer::_returnMoneyToCabinet()` (`SI_PrepareUserForTransfer.class.php:68-89`)
вызывает **тот же самый** `MU_ExecTransferInterworld::Exec()` напрямую (мир → `'UC'`) для `GC` и `SC` по
отдельности, **минуя** `UC_ExecMoneyTransfer` целиком — то есть без проверки `USER_CABINET_TRANSFER_TAX`
и без списания `PAY_TRANSFER_TAX`. Практический вывод: деньги, автоматически выметенные из инстанса
перед переносом (обязательный шаг подготовки, раздел 1.7.1), **не облагаются комиссией**, в отличие от
точно такой же по смыслу операции, инициированной игроком вручную через личный кабинет. Деньги остаются
в `UC`-кабинете и не переносятся автоматически в новый инстанс — игрок должен сам вызвать
`ExecTransferToInstance` после завершения переноса, чтобы вернуть их (уже без комиссии, т.к. это
направление её не имеет).

### 2.6. Плановая сверка — `MU_InterworldController`

`MU_InterworldController::Exec()` (`MU_InterworldController.class.php:12-39`) — конструктивный аналог
`SU_TransferValidate` из Части 1, но для денег. Точка входа (крон-скрипт, который его вызывает) не
найдена в этом срезе кода — **открытый вопрос**, аналогичный нескольким другим утилитам без явного
внешнего раннера, отмеченным в `multi-world-architecture.md`.

Алгоритм: для **каждого** мира версии (`ds_worldcollection` в мастер-БД) выполняются две проверки:

**a) Проверка исходящих (`MU_InterworldExecTestSend`, `:13-87`)** — все строки
`ds_money_list_interworld_send` этого мира с `check_date IS NULL` («ещё не сверены»,
`MU_InterworldSearch::GetAllSendedNotChecked`, `MU_InterworldSearch.class.php:16-38`). Для каждой ищется
соответствующая запись `ds_money_list_interworld_recive` в мире-получателе по составному ключу
`(transfer_key, gc_amount, sc_amount, world_identifier↔to/from_world_identifier)`
(`GetRecivedTransfersByKey`, `:79-124`):
- **найдена** → помечается `check_date = now()` в обеих (`MarkSendAsValid`).
- **не найдена** (деньги списаны в A, но не дошли до B) → чинится **вперёд, а не откатом**:
  1. Создаётся недостающая операция `RECIVE_INTERWORLD_TRANSFER` в мире B тем же ключом
     (`MU_InterworldExecCreateRecive::Exec`, `MU_InterworldExecCreateRecive.class.php:12-56`) — игрок
     получает деньги в B.
  2. Заново проводится (напрямую через `MU_ExecTransfer::Exec`, не через
     `MU_ControlExecOperations`) списание `SEND_INTERWORLD_TRANSFER` в мире A под тем же `id_list`
     (`MU_InterworldExecTestSend.class.php:71-82`).
  3. Обе стороны помечаются проверенными.
  
  Если счёт получателя временно неактивен (`is_active = false`), сверка сама активирует его на время
  операции и деактивирует обратно (`ChangeAcountActiveStatus`, вызывается из
  `MU_InterworldExecCreateRecive`/`MU_InterworldExecCreateSend`).

**b) Проверка входящих (`MU_InterworldExecTestRecive`, `:15-84`)** — симметрично, по
`ds_money_list_interworld_recive` этого мира с `check_date IS NULL`. Если для зачисления **нет**
соответствующего списания в мире-источнике — недостающий `SEND_INTERWORLD_TRANSFER` создаётся
(`MU_InterworldExecCreateSend::Exec`, `MU_InterworldExecCreateSend.class.php:12-66`), но **только если на
счету игрока в мире-источнике достаточно средств** (`accountData[currency] >= summ.amount`, `:29-35`).
Если средств не хватает — исправление **не выполняется**, расхождение накапливается в статической
in-memory карте `MU_InterworldController::$tmp[username][worldShortName] += amount`
(`MU_InterworldExecCreateSend.class.php:31`), и в лог-файл (`InterworldMoneyTransferValidate.log`)
пишется строка `CANT FIX! Not enought money on sorce account`. **В этом срезе кода не найдено ни одного
места, которое бы позже читало, эмитило письмом или иначе выгружало `$tmp`** — то есть, в отличие от
`SU_TransferValidate` (Часть 1, e-mail с ERROR-строками), необратимая денежная недостача **не имеет
видимого канала уведомления администратора** за пределами текстового лог-файла — **открытый вопрос /
вероятный пробел в эксплуатационном контуре**.

Итог: механизм сверки — **самовосстанавливающийся в сторону завершения перевода** (никогда не
откатывает уже начатый перевод), с одним известным тупиковым случаем — недостаточный баланс на стороне
списания при исправлении «зависшего» зачисления.

### 2.7. Лимиты и ограничения — сводка

| Ограничение | Есть? | Где |
|---|---|---|
| Фиксированный лимит суммы за операцию | Нет | — |
| Лимит по количеству операций в срок (антифрод) | Не найден | — |
| Комиссия | Только на `SC`, только при выводе мир→кабинет, `% = USER_CABINET_TRANSFER_TAX` (per-world) | `UC_ExecMoneyTransfer::ExecTransferToUser` |
| Проверка достаточности средств | Да, на этапе `MU_ExecTransfer` (жёстко) и заранее в `UC_ExecMoneyTransfer` (для UX) | `MU_ExecTransfer.class.php:36`, `UC_ExecMoneyTransfer.class.php:61-69,103-111` |
| Блокировка неактивных счетов | Да, `is_active` проверяется перед любым переводом | `MU_ExecTransfer.class.php:29-30`, `UC_ExecMoneyTransfer.class.php:46-49,88-91` |
| Перевод самому себе разных пользователей | Запрещён на уровне `MU_ExecTransfer` (`memberFrom.userID != memberTo.userID`); межмировой перевод, наоборот, **требует** одного и того же `userID` с обеих сторон | `MU_ExecTransfer.class.php:26`, `MU_ExecTransferInterworld.class.php:7-8` |

---

## Часть 3. Переезд по домашнему кольцу (внутримировая перестановка координат)

Это **третья, независимая от Части 1 механика**, которую легко спутать с межмировым переносом,
потому что игрок на сайте видит их рядом (см. 1.12) под общим словом «переезд». Технически это
совершенно другой код: не снятие снапшота и восстановление (`SI_*`), а **прямая перестановка
координат существующей соты** внутри одного и того же мира (одна и та же PostgreSQL БД, ни одна
запись игровых данных — здания, армия, наука — не читается и не переписывается, кроме привязки к
`id_combcomplex`/`combnumber`).

### 3.1. Player-facing описание (источник: `https://destinysphere.ru/?p=user_move`)

Страница прямо называет это «переезд соты в вашем текущем игровом мире по домашнему кольцу (колонии
остаются на своём месте)» и описывает два бесплатных варианта из одного и того же меню:

- **«Переезд»** — игрок сам вписывает новые координаты (комплекс/сота) на **своём кольце расы**
  (кольцо сменить нельзя — «нельзя поменять свою игровую расу»); можно посмотреть карту кольца, чтобы
  найти свободное место; итоговый перенос выполняется «в течение суток», «обычно поздно вечером» —
  то есть не мгновенно, а пакетно по расписанию.
- **«Обмен»** — игрок указывает ник другого игрока; переезд состоится, только когда **второй игрок
  подтвердит согласие**, проделав ту же процедуру у себя (то есть это симметричная, обоюдная заявка).
- Ограничение: **раз в 60–90 дней, в зависимости от мира** — использовать бесплатный переезд по
  кольцу повторно раньше нельзя.
- Отдельно упомянуты **платные** варианты той же механики без ограничения по частоте: «переезд соты
  по кольцу на свободное место» (обычные кольца или отдельно Мелсион) и «обмен местами между сотами
  двух игроков» (обычные кольца или Мелсион) — через `billing.destinysphere.ru` или через игровой
  Кабинет, если хватает игровой валюты.

### 3.2. Код: бесплатный self-service — `SU_FreeTransfer` / `SU_ExecFreeTransfer` / `SU_ChangCombCoords`

Всё подтверждается кодом почти дословно:

- **Точка входа** — `ds/FreeTransfer.php` создаёт `SU_FreeTransfer($_REQUEST)`
  (`ds/classes/SU/SU_FreeTransfer.class.php`). Действия (`action`): `transfer` (обычный переезд),
  `exchange` (обмен с другим игроком), `swap`/`exchange_swap` (тот же переезд/обмен, но для
  **колонии**, т.е. соты на кольце Мельсион, `ringnumber = 4` — отдельным флагом от «основной» домашней
  соты), `cancel` (отмена ещё не выполненного заказа).
- **Хранение заявки** — одна строка на игрока в таблице `ds_free_ring_transfer` (`id_user, id_world,
  transfer_to, transfer_from, exchange_with, used_free_transfer, transfer_complite, transfer_date`).
  Целевая сота дополнительно резервируется в `ds_reservedcomb` (комментарий `'free ring transfer'`,
  `_reserveComb()`, `:774-792`) — та же таблица, что использует Часть 1 для резервирования координат
  под переезд между мирами.
- **Симметричность обмена** — при `exchange` заявка ставится только со стороны инициатора; чтобы
  переезд состоялся, вторая сторона должна отдельно прислать встречную заявку с обратными ролями
  (`_isExchangingWith`, `:473-500`) — фактическое перемещение выполняется позже, батчем (см. ниже), и
  только когда обе заявки на месте.
- **Лимит частоты** — читается как world-setting через `DE_WorldSettings::CreateForCurentWorld()`:
  `FREE_RING_TRANSFER_ALLOWED` (вкл/выкл, `_checkTransferAllow`, `:580-595`) и
  `FREE_RING_TRANSFER_LIMIT` (число дней, `_getFreeTransferLimit`, `:597-612`, используется и как
  окно проверки «уже использовал» в `_isUsedTransfer`, `:326-358`, и как срок хранения/автоочистки
  старых записей в `_clearOldTransfers`, см. ниже). Живые значения этих двух настроек по всем текущим
  мирам сайт публикует прямо в сводной таблице (`?p=game_option`, не код): «Бесплатный переезд по
  домашнему кольцу разрешён» = `да` на всех ~11 мирах, «через сколько дней можно повторно
  использовать» = **60 дней** на обоих мирах для новичков (Стрелец, Терра Нова) и **90 дней** на всех
  остальных — то есть код и живая конфигурация совпадают буква в букву с описанием на странице
  игрока («60–90 дней в зависимости от мира»).
- **Колонии/Мельсион — только один раз** (не по таймеру, а вообще): `_canSwapColonies()`
  (`:984-1002`) проверяет присутствие строки в отдельной таблице `used_transfer_colony` — если она
  уже есть, повторный обмен/переезд колонии запрещён навсегда, независимо от `FREE_RING_TRANSFER_LIMIT`.
  Доступность этой ветки вообще управляется world-setting `ALLOW_TRANSFER_COLONIES` (`:550-551`).
- **Пакетное исполнение** — крон `ds/utils/FreeTransfer.php` обходит все миры всех версий
  (`$GLOBALS['dbWorldAccess']`) и для каждого вызывает `SU_ExecFreeTransfer::Exec($dbId, $worldId)`
  (`ds/classes/SU/SU_ExecFreeTransfer.class.php`). Тот выбирает из `ds_free_ring_transfer` все заявки
  с `used_free_transfer=true AND NOT transfer_complite` (`_getCombsToMove`, `:126-147`), для обмена
  дополнительно проверяет наличие встречной заявки (`_checkExchange`, `:149-178`), и для каждой валидной
  пары вызывает `SU_ChangCombCoords::change($to, $from)` — **никакого крон-таймера с точным временем в
  коде не найдено**, но лог-файл `FreeTransfer.log`/сообщение "включается каждый день поздно вечером"
  с сайта согласуются с обычной ночной cron-задачей (не подтверждено расписанием напрямую в этом срезе
  кода).
- **Сама перестановка координат** — `SU_ChangCombCoords::change()` (`ds/classes/SU/SU_ChangCombCoords.class.php:146-225`)
  работает **без** какого-либо `SI_*`-снапшота: одной транзакцией меняет местами `id_combcomplex`/
  `combnumber` двух существующих строк `ds_comb` (через временное значение `combnumber=7`, чтобы не
  словить уникальный конфликт координат), переносит `COMBCOMPLEX`-артефакты вслед за сотой
  (`updateComplexArtefacts`) и снимает привязанное здание `GONDOLA_TRANSPORTER` вместе с его событиями
  в очереди (`delCondoms`/`delCondomsBuildingEvent`) — то есть здание гондолы-транспортёра **не
  переживает** переезд по кольцу и должно быть заново построено на новом месте. Никакие другие
  здания/юниты/наука не трогаются вообще — в отличие от Части 1, тут физически нечему потеряться,
  кроме этого одного здания.
- **Подтверждение и уточнение по схеме БД (`schema.sql`)** — каждое изменение координат/имени соты
  автоматически логируется PL/pgSQL-триггером на уровне БД, независимо от того, кто вызвал `UPDATE`
  (`SU_ChangCombCoords`, billing-шлюз из 3.3 или что-то ещё не разобранное):
  - `comb_coords_change()` (`CREATE TRIGGER ds_comb_tr BEFORE UPDATE ON ds_comb`, `schema.sql:535-570`,
    `:29236`) — при изменении `id_combcomplex`/`combnumber` пишет строку `(id_comb, old_combnumber,
    new_combnumber, old_id_complex, new_id_complex, date)` в `ds_comb_coords_history`. Ровно то, что
    ожидается от временного `combnumber=7`-обмена в `SU_ChangCombCoords::change()`: каждая сота даёт
    **две** строки истории (промежуточная и финальная перестановка), не одну.
  - `combname_change()` (`CREATE TRIGGER ds_combname_tr BEFORE UPDATE ON ds_combname`,
    `schema.sql:577-627`, `:29243`) — при изменении `combname` логирует `(id_comb, old_combname,
    new_combname, date)` в `ds_combname_history`, но **до того** ещё и сама валидирует новое имя на
    уровне БД: уникальность без учёта регистра (`RAISE EXCEPTION 'New combname not unique!'`), длина
    3–20 символов, разрешённые символы `^[a-zA-Z0-9_-]+$`. Это независимое, DB-level подтверждение
    того, что делает `SI_ExecRestoreComb::_testCombName()` при восстановлении после межмирового
    переноса (раздел 1.8) — конфликт имени соты в новом мире решается добавлением случайного
    3-значного суффикса именно потому, что без этого сработало бы вот это самое исключение БД.
  - `ds_free_ring_transfer_log()` (`CREATE TRIGGER ds_free_ring_transfer_tr AFTER INSERT OR UPDATE ON
    ds_free_ring_transfer`, `schema.sql:1095-1108`, `:29257`) — как только у заявки в
    `ds_free_ring_transfer` одновременно `transfer_complite = true` и заполнен `transfer_date`,
    автоматически пишется строка `(id_user, id_world, transfer_date)` в отдельную таблицу
    `ds_free_ring_transfer_log` — журнал уже исполненных переездов по кольцу, ранее в этом документе
    не упоминавшийся (не путать с `ds_worldtransfer_log`/`ds_worldtransfer_stat` из Части 1 — это
    отдельная, третья по счёту лог-таблица, специфичная для Части 3).

### 3.3. Код: платные варианты (без ограничения по частоте)

Ровно та же механика (`ds_free_ring_transfer`), но без проверки `FREE_RING_TRANSFER_LIMIT`, доступна
двумя платными путями — они совпадают со списком услуг, который называет сайт:

- **Через игровой Кабинет (GC/SC)** — `UC_ExecChangeCoords` (услуга `PS_CHANGE_COORDS`, обычные
  кольца/`PS_CHANGE_COORDS_MELS` для Мельсиона — суффикс `_MELS` не подтверждён дословно в этом файле,
  реконструирован по аналогии с `UC_ExecExchangeCoords`, см. ниже; **предположение**) — простой переезд
  на свободную сотую) и `UC_ExecExchangeCoords` (услуга `PS_EXCHANGE_COORDS`/`_MELS` — обмен с другим
  игроком, `ds/classes/UC/UC_ExecExchangeCoords.class.php`). Оплата — общий
  `UC_PaymentServiceMoneyOperation`, с явным выбором «кто платит» при обмене (`self::$whoPay`, инициатор
  или партнёр по обмену, `:26, 272-351`) и возвратом денег при отмене ещё не завершённой заявки
  (`_CanselExchange`, `UC_PaymentServiceMoneyOperation::ReturnMoney`, `:455-551`).
- **Через сам billing.destinysphere.ru** — входящий шлюз (`ds/gate/billing_receiver.php`, см.
  `billing-and-monetization.md` раздел 6) диспетчерит на `BG_CombMovingInsideRing`
  (`ds/classes/BG/BG_CombMovingInsideRing.class.php`) — вызывает тот же `SU_ChangCombCoords::change()`
  напрямую, без очереди `ds_free_ring_transfer` и без пакетного крона (перестановка происходит
  синхронно, в момент вызова от биллинга), плюс `BG_CheckCombMovingInsideRing` (по имени — проверка
  возможности хода без реального выполнения, не разбирался построчно) и `BG_CombReserv`
  (`ds/classes/BG/BG_CombReserv.class.php`) — резервирование конкретных координат под будущий платный
  переезд (та же таблица `ds_reservedcomb`), что подтверждает фразу с сайта «забронировать себе соту
  за определённую плату… через платные услуги службы Биллинга».

Итог: у одной и той же пользовательской идеи «поставить свою соту на другое место того же кольца» в
коде есть **три независимых точки входа** (бесплатный self-service раз в 60–90 дней, платный через
Кабинет, платный через сам биллинг-сайт), но все три в конечном счёте используют один и тот же примитив
перестановки координат `SU_ChangCombCoords` — разница только в том, кто и как разрешает вызов и кто за
него платит.

---

## Открытые вопросы

1. **Кто и как физически запускает пакетные крон-процессы `SU_ExecWorldTransfer::Exec()` и
   `MU_InterworldController::Exec()`?** В обоих случаях класс полностью реализован, но обёрточный
   CLI-скрипт (аналог `ds/utils/RecieverWorld.php` для `SU_RecieverWorldTransfers`) не найден в этом
   срезе кода. Аналогичная находка уже отмечена в `multi-world-architecture.md` для `DU_ClearWorld`.
2. **Куда реально попадает статическая карта `MU_InterworldController::$tmp`** с необратимыми денежными
   расхождениями (2.6б)? В коде не найдено чтения/выгрузки этой переменной — похоже на незавершённый или
   утраченный код оповещения администратора.
3. **Физическое удаление старого инстанса после переноса** (`SU_DeleteUserList::AddToList(...,
   'TRANSFER')`, раздел 1.9) — сам класс `SU_DeleteUserList` и потребитель этой очереди не разбирались
   подробно; не подтверждено, идёт ли удаление через `DU_ControllerDeleteUserInstance` (упомянут в
   `game-engine.md` как каскадный «удалятор» инстансов) или иным путём, и с какой задержкой.
4. **`BG_ChangeWorld` (billing-переезд, `transfer_type=2`) — заглушка `move()`.** Не установлено, было ли
   это функцией «в разработке» на момент снятия среза, либо намеренно не поставляемой публично
   возможностью. Для ремастера это, вероятно, стоит просто спроектировать заново, а не пытаться
   реконструировать отсутствующую логику.
5. **`ds_worldtransferquest` / «квестовый» перенос** (упомянут только как блокирующая проверка в
   `SU_FreeWorldTransfer::_checkSourceWorldForTransfer()`) — отдельный, не разобранный в этом документе
   параллельный механизм переноса для ивентовых/квестовых миров.
6. **Судьба событий `COLONIZE`, `SPY_MOVE_TO_RESEARCH`, `SPY_MOVE_FROM_RESEARCH` при восстановлении**
   (раздел 1.8, таблица) — они безусловно отбрасываются без компенсации. Не установлено, баг это,
   осознанный компромисс, или эти события физически не могут существовать на момент снятия снапшота
   благодаря шагу подготовки (1.7.1) и поэтому ветка мертва по построению — код `_endSpyEvents()`
   закрывает именно перелёт зондов, но не «переезд на исследование» как таковой.
7. **`ME_CancelAllUserTrades::Cancel()`** (вызывается в подготовке к переносу, 1.7.1) не разбирался
   построчно — не подтверждено, возвращаются ли деньги/товар со снятых лотов немедленно на счёт/соту
   игрока или это происходит иначе.
8. **Блокировка (`MU_ExecLockOperation::Lock`)**, используемая диспетчером денежных операций
   (`MU_ControlExecOperations::ExecOperation`), не была прочитана — точный примитив блокировки
   (внутрипроцессный / MySQL-locking из `multi-world-architecture.md` §4.2 / что-то третье) —
   предположение, не факт.
9. **Гонка при двойной отправке в `UC_ExecMoneyTransfer`** (раздел 2.4) — защита только на уровне
   `$_SESSION`, не проверялось поведение при параллельных запросах одной сессии (например, два открытых
   окна браузера).
10. **`ds/copycomb/`, `ds/CopyComb.php`, `ds/CopyComb2.php`, `ds/vacationMode.php`, `ds/StoreComb.php`,
    `ds/utils/TransferAllUser.php`** — соседние инструменты, использующие классы `SI_*`/`DE_UserInstance`,
    упомянутые при поиске по ключевым словам, но не разобранные подробно в рамках этого задания; не
    исключено, что они содержат дополнительные пути ручного переноса/копирования, не описанные выше.
11. **Годовой лимит хранения в «Чёрной Дыре»** (раздел 1.9.1) — player-facing источник называет «в
    течение года», но ни в `UC_ExecTransferCombToBlackHole`, ни в `UC_ExecRestoreCombFromBlackHole` нет
    проверки прошедшего времени. Либо лимит проверяется административным/крон-скриптом вне этого среза
    кода (например, тем же семейством, что физически удаляет старые инстансы, см. открытый вопрос №3),
    либо это не жёсткое техническое ограничение, а рекомендация/маркетинговая формулировка.
12. **Таблица `ds_storeuserdata`** (упомянута в `UC_ExecRestoreCombFromBlackHole::_GetUserTransferData()`,
    раздел 1.9.1, как запасной источник данных о мире-источнике, если нет строки в
    `ds_worldtransfer_stat`) — не разбиралась подробно; не установлено, кто и когда в неё пишет и чем
    она принципиально отличается от `ds_worldtransfer_stat`.
13. **Точные строковые имена платных услуг Мельсиона в Части 3** (`PS_CHANGE_COORDS_MELS`/аналог для
    `PS_EXCHANGE_COORDS_MELS`, раздел 3.3) реконструированы по аналогии с `PS_EXCHANGE_COORDS`/ветвлением
    `self::$ring` в `UC_ExecExchangeCoords`, но дословно не встречены в прочитанных файлах — нужно
    перепроверить по `ds_paymentserviceslog`/языковым файлам личного кабинета, прежде чем полагаться на
    точное имя константы.
