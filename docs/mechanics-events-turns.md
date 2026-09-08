# Игровое время: очередь отложенных событий и пересчёт ходов

> Анализ основан на полном прочтении: `ds/classes/EH/EH_Event.class.php`,
> всех 30 конкретных `EH_*` обработчиков, `ds/classes/DE/DE_EventQueue.class.php`,
> `DE_Event.class.php`, `DE_EventQueueAttributes.class.php`,
> `DE_EventQueueInterface.class.php`, `DE_EventQueueNull.class.php`,
> `ds/classes/DS/DS_Event.class.php`, `ds/classes/DC/DC_EventInstance.class.php`,
> `ds/classes/ME/ME_EventMonitor.class.php`, `ME_TurnCalc.class.php`,
> `ds/classes/CR/CR_RefreshUserInstance.class.php`,
> `CR_RefreshUserInstanceCommand.class.php`, `ds/classes/RD/RD_RefreshDemon.class.php`,
> `RD_RefreshDemonSearch.class.php`, `RD_AdeptUserActionToConsole.class.php`,
> `ds/classes/UserAction.class.php`, `ds/classes/UA/UA_Action.class.php`,
> `UA_Refresh.class.php`, `UA_Empty.class.php`, `ds/useraction.php`,
> `ds/classes/SU/SU_GameSessionWork.class.php`, `ds/classes/SU/SU_Locker.class.php`
> (частично — сам механизм блокировок уже описан в
> [multi-world-architecture.md](multi-world-architecture.md), здесь разбирается только его
> применение к очереди/рефрешу), `ds/classes/DE/DE_UserInstance.class.php` (фрагменты про
> `lastrefresh`), `ds/classes/DE/DE_WorldSettings.class.php`, `ds/classes/TN/TN_SettingsTemplateClassic.class.php`,
> `TN_SettingsTemplateQuest.class.php`, `ds/classes/GB/GB_ArmyActionCreationData.class.php`,
> `ds/classes/EH/EH_EventCreate.class.php`, `ds/classes/ME/ME_CreateArmyAction.class.php` (фрагмент),
> `ds/classes/SI/SI_ExecStoreUserEvents.class.php` (фрагмент), `ds/config/settings.php` (константы).
> Файлы читались через `iconv -f CP1251 -t UTF-8`. Плейсхолдеры
> `REDACTED_DB_PASSWORD`/`REDACTED_API_SECRET` не анализировались.
>
> Демон `RD_RefreshDemon` как долгоживущий процесс (цикл `while(true)`, heartbeat-файлы,
> запуск `useraction.php` из консоли) уже описан в
> [utils-and-maintenance.md](utils-and-maintenance.md) — здесь он разбирается только со
> стороны того, **кого и как часто** он выбирает для рефреша и как это связано с очередью
> событий, без повторного описания процесса-демона как такового.

## 1. Общая архитектура

Игра не имеет отдельного «серверного тика» (нет процесса, который раз в N секунд одним
проходом обновляет всех игроков разом). Вместо этого используются два независимых, но
использующих один и тот же код механизма:

1. **Очередь отложенных событий** (`ds_eventqueue` + `ds_eventparticipant` + `ds_eventoption`/
   `ds_eventhasoption`) — таблица «что должно произойти и когда» (стройка, движение армии,
   раунд боя, торговая сделка и т. д.). Каждое событие при наступлении срока обрабатывается
   классом `EH_*` («Event Handler»).
2. **Пересчёт хода** (`ME_TurnCalc`) — начисление ресурсов/апкипа/рождения рабочих на
   конкретной соте, основанное на разнице между «сейчас» и `ds_userinstance.lastrefresh`,
   делённой на длительность игрового «хода» (`TURN_TIME`, мировая настройка в минутах).

Оба механизма запускаются **вместе, одним и тем же контроллером**
`CR_RefreshUserInstance` (см. раздел 5), который может быть вызван либо демоном
(`RD_RefreshDemon`), либо самим клиентом при каждом обращении к `useraction.php`. Настоящего
«мирового тика» с фиксированной частотой нет — обновление одного игрока происходит только
тогда, когда до его инстанса «доходит очередь» (лениво, по запросу/выборке), а не по
глобальному расписанию.

## 2. Модель данных события

### 2.1 Таблицы (восстановлены по SQL в `DS_Event`/`DE_EventQueue`/`DE_Event`)

| Таблица | Назначение | Ключевые поля (по коду) |
|---|---|---|
| `ds_event` | Справочник **типов** событий | `id_event`, `eventname` (строковый код типа, например `CONSTRUCT_BUILDING`), `eventhandlerclass` (имя PHP-класса `EH_*`, читается через `DS_Event::GetAllEventsAndOptions()` из связки с `ds_eventhandler`), `need_create_lock` (bool), `lock_only_in_end` (bool) |
| `ds_eventqueue` | Конкретный **экземпляр** отложенного события | `id_eventqueue`, `id_event` (FK на тип), `eventstarttime`, `eventendtime`, `istrash` (bool — «обработано/снесено») |
| `ds_eventparticipant` | Многие-ко-многим: какие `id_userinstance` являются участниками события (у боевых событий их минимум двое — атакующий и обороняющийся) | `id_eventqueue`, `id_userinstance` |
| `ds_eventoption` | Справочник допустимых именованных параметров для типа события | `id_eventoption`, `id_event`, `optionname` |
| `ds_eventhasoption` | Значения параметров конкретного экземпляра события | `id_eventqueue`, `id_eventoption`, `value` |

Источники: `ds/classes/DS/DS_Event.class.php:9-33` (`GetInstanceEvents`), `:39-63`
(`GetEventQueueByID`), `:257-273` (`GetEventDataByID` — читает `ds_event.*`), `:299-320`
(`GetAllEventsAndOptions` — джойн `ds_event`+`ds_eventoption`+`ds_eventhandler`).

### 2.2 Доменные классы

- **`DE_Event`** (`ds/classes/DE/DE_Event.class.php`) — обёртка над строкой `ds_event`, то
  есть над **типом** события: имя (`eventName`), класс-обработчик (`handlerName`), два
  булевых флага блокировки (см. раздел 6.3).
- **`DE_EventQueue`** (`ds/classes/DE/DE_EventQueue.class.php`) — обёртка над строкой
  `ds_eventqueue`, то есть над **экземпляром**: `startTime`/`endTime` (объекты `GB_Date`,
  внутри — обычный Unix-timestamp в секундах, см. `GB_Date::_validateValue`), ссылка на
  `DE_Event` (тип), `isTrash`, ленивая загрузка `attributes` (параметры) и `handler`
  (создаётся через `new $name` от `getHandlerName()`, строка 191-199). Есть отдельный
  `DE_EventQueueNull` (Null Object) для случая «у армии/сущности нет привязанного события»
  (id=0), реализующий тот же интерфейс `DE_EventQueueInterface::isNull()`.
- **`DE_EventQueueAttributes`** — типизированный доступ к параметрам события
  (`ds_eventhasoption`), с ленивой записью только изменённых значений (`IS_MODIFAED`) при
  `commit()`.

### 2.3 Что значит «время события»

У события есть `startTime` и `endTime`. Между ними событие считается «в процессе»
(`_getComplitPart()` в `EH_Event.class.php:283-317` считает процент выполнения как
`(checkTime - startTime) / (endTime - startTime)`, используется для отображения прогресса
стройки/тренировки клиенту). Событие «созревает» (готово к завершению), когда
`endTime <= текущее время проверки` — эта проверка называется `_isTimeToHandle()`
(`EH_Event.class.php:258-265`) и вызывается **изнутри каждого конкретного обработчика**, а не
на уровне SQL-выборки (см. раздел 3).

## 3. Как выбираются «созревшие» события — важный нюанс

В коде **нет** SQL-запроса вида `SELECT ... WHERE eventendtime <= now()`, который бы отбирал
только просроченные события конкретного игрока для обработки. Вместо этого:

1. `DS_Event::GetInstanceEvents($uiid)` (`DS_Event.class.php:9-33`) выбирает **вообще все**
   ещё не снесённые (`istrash = false`) события пользователя, без фильтра по времени,
   отсортированные по `eventendtime` (по возрастанию — самое раннее первым).
2. `DC_EventInstance` (`ds/classes/DC/DC_EventInstance.class.php`) загружает этот список в
   память как две параллельные структуры `GB_Array`: массив `endDates` (для сортировки) и
   массив `events` (сами объекты `DE_EventQueue`). `fetchNextEvent()` отдаёт события строго в
   порядке возрастания `endTime` (см. раздел 7).
3. `ME_EventMonitor::Scan()` (`ds/classes/ME/ME_EventMonitor.class.php:114-267`) перебирает
   **все** события пользователя (и созревшие, и ещё нет) в этом порядке и для каждого вызывает
   `$event->getHandler()->handleEvent($event->getID())`.
4. **Каждый обработчик сам решает**, вызывая `_isTimeToHandle()`, завершать ли действие
   (`setTrash()`, применение итоговых эффектов) или просто обновить игроку сообщение о
   прогрессе (см. пример `EH_ConstructBuilding` ниже) — событие при этом остаётся в очереди
   до следующего скана.

То есть очередь одного игрока — это не «список дел», отфильтрованный по времени, а полный
список активных действий, который сканируется целиком при каждом рефреше, а решение «пора
или не пора» принимается для каждого события индивидуально его же обработчиком. Это
объясняет, почему у `EH_Event` есть метод `handleInExternalRefresh()` (переопределяемый),
используемый ещё на один уровень выше — при **чужом** рефреше (см. раздел 8.2).

Пример из `EH_ConstructBuilding.class.php:36-38, 260-273`:
```php
if ($this->_isTimeToHandle())
{ // здание достроено: активируем, начисляем эффекты, setTrash()
    ...
    $this->setTrash();
}else{ // ещё не достроено: только сообщение о проценте прогресса
    $prog = $this->_getComplitPart() * 100;
    ...
    DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_BUILDING_MESS", $messparams, null, null, 'NOACCUMULATE');
}
```

Отдельно от «полной» обработки — тренировка юнитов (`EH_TrainUnit`, `EH_TrainWarUnit`)
использует ещё один режим: даже до `_isTimeToHandle()` она считает **промежуточное**
количество уже произведённых юнитов через `_countTmpTrainedUnitsCount($timetoTrainOne)`
(`EH_Event.class.php:319-333`, формула `floor((checkTime - startTime) / timetoTrainOne)`) —
то есть юниты «капают» по одному по мере прохождения времени, а не появляются все разом
в конце. Комментарий в самом файле: *«Дико кривая я вещь, я её просто ненавижу!!! особенно
из-за того, что она обрабатывается не только по окончанию»* (`EH_TrainWarUnit.class.php:4-5`).

## 4. Полный список обработчиков событий (`EH_*`)

30 конкретных обработчиков + инфраструктурные классы. Столбец «Событие (`ds_event.eventname`)»
подтверждён явной строкой в коде там, где это возможно (создание события по имени); там, где
единственное основание — соответствие имени класса и логики (мэппинг `eventname → eventhandlerclass`
физически хранится в таблице `ds_event`, не в PHP-файлах), это помечено «*выведено по
контексту*» — не 100%-но верифицировано без доступа к БД.

| Событие (`ds_event.eventname`) | Класс-обработчик | Что делает при завершении |
|---|---|---|
| `CONSTRUCT_BUILDING` | `EH_ConstructBuilding` | Активирует здание (`UnderConstruction=false, Active=true`), спецэффекты по типу здания (археологи/учёные, колонизационный модуль, посольство → привязка к глобальному альянсу, генератор щита, tradescope), начисляет очки рейтинга |
| `UPGRADE_BUILDING` | `EH_UpgradeBuilding` | Апгрейд уровня здания, пересчёт производимых/потребляемых атрибутов, возможна временная защита соты (`defendEndDate`, кратно `TURN_TIME`) |
| `ACTIVATE_BUILDING` | `EH_ActivateBuilding` | Активация ранее построенного, но выключенного здания; тоже может ставить временный щит защиты |
| `DEACTIVATE_BUILDING` | `EH_DeactivateBuilding` | Деактивация здания (в т. ч. принудительная — по нехватке ресурсов, см. `ME_TurnCalc`) |
| `NEW_RESEARCH` | `EH_NewResearch` | Завершение исследования науки |
| `SEND_ARCHEOLOGY_TEAM` | `EH_ArcheologyTeam` | Возврат археологической экспедиции |
| `TERRAFORMING` | `EH_Terraforming` | Завершение терраформирования |
| `TRAIN_UNIT` | `EH_TrainUnit` | Тренировка мирных юнитов (рабочие/учёные/археологи); частичное, «покапельное», начисление до завершения |
| `TRAIN_UNIT` (тот же тип для военных зданий по коду) / фактически различается по обработчику | `EH_TrainWarUnit` | Тренировка военных юнитов; тоже частичное начисление, самый «кривой» по признанию автора кода обработчик |
| `TRANSFER_RESOURCE` | `EH_TransferResource` | Доставка ресурсов между сотами игрока |
| `RELIGION_CONNECT` | `EH_ReligionConnect` | Принятие религии: бонусы/пенальти, смена статуса |
| `RELIGION_CANCEL` | `EH_ReligionCancel` | Отказ от религии: снятие бонусов/пенальти |
| `REINCARNATE_UNIT` | `EH_ReincarnateUnit` | Реинкарнация военных юнитов |
| `COLONIZE` | `EH_Colonize` | Завершение колонизации новой соты |
| `TRADING` | `EH_Trading` | Завершение торгового лота: если есть покупатель — порождает `TRADING_DELIVERY_GOOD` (+опционально `TRADING_DELIVERY_PAYMENT`) через `ME_TradeExecLotBay`; если нет — возврат лота продавцу (`ME_TradeExecLotReturn`) |
| `TRADING_DELIVERY_GOOD` | `EH_TradingDeliveryGood` | Доставка проданного товара покупателю |
| `TRADING_DELIVERY_PAYMENT` | `EH_TradingDeliveryPayment` | Доставка оплаты продавцу |
| `NPC_ATTACK` | `EH_NPCAttack` | Обработка атаки NPC (создаётся через `ME_LogNPCBattle::CreateInDB`) |
| `ARMY_MOVEMENT_TO` | `EH_ArmyMovementToAttack`* | Прибытие атакующей армии на соту цели → инициирует бой (`_createRoundEvent` → создаёт `BATTLE_ROUND`) |
| `MOVE_TO_DEFEND` | `EH_ArmyMovementToDefend`* | Прибытие армии в оборону на чужую/союзную соту |
| `BATTLE_ROUND` | `EH_BattleRound` | Один раунд боя; при продолжении боя создаёт **новое** событие `BATTLE_ROUND` (следующий раунд) вместо повторной обработки текущего — см. раздел 4.1 |
| `ARMY_MOVEMENT_FROM` | `EH_ArmyMovementFrom` | Возврат армии домой после похода/боя |
| `MOVE_TO_COMB`* | `EH_MoveArmyToComb`* | Обычное перемещение армии на другую свою соту (не бой) |
| `HIDE_ARMY` | `EH_HideArmy` | Возврат армии из «укрытия» |
| `DEFEND`* | `EH_Defend` | Окончание срока стояния в обороне на чужой соте |
| `SPY_MOVE_TO_RESEARCH` | `EH_SpyMoveToResearch` | Прибытие зондов на разведку |
| `SPY_MOVE_FROM_RESEARCH` | `EH_SpyMoveFromResearch` | Возврат зондов с разведки |
| `SPY_IN_DEFEND`* | `EH_SpyInDefend` | Окончание стояния зондов в обороне соты |
| `SPY_ZONDS_REPAIR`* | `EH_SpyZondsRepair` | Завершение починки зондов |
| — (не событие очереди) | `EH_Event` | Базовый класс, см. раздел 5 |
| — (вспомогательный) | `EH_EventCreate` | **Похоже на мёртвый код**, см. раздел 4.1 |
| — (вспомогательный) | `EH_Event_sel_for_update.class.php` | Отдельная копия класса `EH_Event` (совпадающее имя!) — не исследовалась подробно, похоже на файл-черновик/дубликат, не участвующий в автозагрузке основного `EH_Event` (см. «Открытые вопросы») |
| — (базовый для боевых) | `EH_Army` | Общий предок `EH_ArmyMovementFrom/ToAttack/ToDefend/BattleRound/HideArmy/MoveArmyToComb` — здесь реализованы `_createComeBackEvent()` и `_createRoundEvent()`, которые и создают цепочки событий (раздел 4.1) |
| — (устаревший?) | `EH_ArmyMovementTo` | Отдельный класс `extends EH_Event` (не `EH_Army`!), создаёт события через сломанный `EH_EventCreate::Create()` — см. раздел 4.1, вероятно не используется в боевой цепочке |

\* — событие/класс сопоставлены по логике и списку `containerTails` в `EH_Event::setTrash()`
(`EH_Event.class.php:127`: `array('EH_ArmyMovementToAttack', 'EH_ArmyMovementToDefend', 'EH_BattleRound')`)
и по карте действие→событие в `GB_ArmyActionCreationData::$allowActionsEvents`
(`ds/classes/GB/GB_ArmyActionCreationData.class.php:9-16`:
`'MOVE_TO_ATTACK'=>'ARMY_MOVEMENT_TO', 'IN_BATTLE'=>'BATTLE_ROUND', 'MOVE_FROM'=>'ARMY_MOVEMENT_FROM',
'MOVE_TO_DEFEND'=>'MOVE_TO_DEFEND', 'IN_DEFEND'=>'', 'MOVE_TO_COMB'=>'MOVE_TO_COMB', 'HIDE_ARMY'=>'HIDE_ARMY'`),
а не прочитаны напрямую из `ds_event.eventhandlerclass` в БД — этой таблицы у нас нет. Матрица
`SPY_IN_DEFEND`/`SPY_ZONDS_REPAIR` — по совпадению текста системных сообщений в самих
обработчиках (`EH_SpyInDefend.class.php:104`, `EH_SpyZondsRepair.class.php:79`) с именем класса;
формального литерала `createEvent('SPY_IN_DEFEND', ...)` в коде найти не удалось (вероятно,
эти события порождаются похожей на `ME_CreateArmyAction`/`EH_Army` цепочкой внутри самих
`EH_SpyMove*`-обработчиков, которая не была прочитана построчно ради экономии бюджета).

### 4.1 Как создаются события — правильный и, похоже, «мёртвый» путь

Есть три способа завести строку в `ds_eventqueue`:

1. **Из команды игрока** — `UA_Action::createEvent($eventName, ...)`
   (`ds/classes/UA/UA_Action.class.php:352-378`) вызывает
   `DE_EventQueue::CreateInDB($eventName, $participants, $this->eventparams, $datePeriod)`.
   Так создаются, например, `CONSTRUCT_BUILDING` (`UA_ConstructBuilding.class.php:179`),
   `COLONIZE`, `TERRAFORMING`, `TRANSFER_RESOURCE`, `NEW_RESEARCH`, `TRAIN_UNIT`,
   `RELIGION_CONNECT/CANCEL`, `SEND_ARCHEOLOGY_TEAM`, `ACTIVATE_BUILDING`/`DEACTIVATE_BUILDING`,
   `UPGRADE_BUILDING` — то есть почти все «одноразовые» игровые действия.
2. **Цепочкой из другого события** («создать потомка») — универсальный паттерн:
   обработчик вычисляет параметры следующего шага, создаёт новую строку в `ds_eventqueue`
   (через `DE_EventQueue::CreateInDB()`, обычно косвенно — через специализированный
   `ME_Create*Action`), выставляет на себе `$this->new = 1` и
   `$this->newevents[] = array('id_eventqueue' => $id)`. `ME_EventMonitor::Scan()`
   (`ME_EventMonitor.class.php:243-259`) после вызова `handleEvent()` проверяет этот флаг и
   регистрирует новое событие как участие для всех его `ds_eventparticipant` через
   `DC_EventInstance::addEvent()` — то есть **новое событие сразу попадает и в очередь
   текущего скана** (может быть обработано в том же проходе, если игрок оказался и
   инициатором, и целью).
   - Пример «движение армии → бой → возврат домой»: `EH_Army::_createRoundEvent()`
     (`EH_Army.class.php:233-262`) и `_createComeBackEvent()` (`EH_Army.class.php:161-227`)
     оба используют `ME_CreateArmyAction::CreateAction($actionData)`
     (`ds/classes/ME/ME_CreateArmyAction.class.php:5-40`), который внутри вызывает
     `DE_EventQueue::CreateInDB($creationData->getEventName(), ...)` — где имя события берётся
     из `GB_ArmyActionCreationData::getEventName()`.
   - Пример «торговый лот истёк → доставка товара/оплаты»: `EH_Trading::_sendGoodAndPayment()`
     (`EH_Trading.class.php`) вызывает `ME_TradeExecLotBay::Exec()`, который создаёт
     `TRADING_DELIVERY_GOOD` (и опционально `TRADING_DELIVERY_PAYMENT`), и точно так же
     выставляет `$this->new=1`/`$this->newevents[]`.
3. **`EH_EventCreate::Create($eventName, $participants, $options, $times)`**
   (`ds/classes/EH/EH_EventCreate.class.php`) — используется **только один раз** во всём
   дереве классов, в `EH_ArmyMovementTo.class.php:167,191` (создание `BATTLE_ROUND` и
   `ARMY_MOVEMENT_FROM`). Проблема: этот метод вызывает
   `DS_Event::InsertEventQueue($eventID, $times)` (передавая **числовой ID**, хотя реальный
   метод `DS_Event::InsertEventQueue()` по факту прочитанному коду
   (`ds/classes/DS/DS_Event.class.php:69-93`) принимает **строку `$eventName`** и сам внутри
   резолвит её в ID через `GetEventIDByName()`), а также вызывает
   `DS_Event::InsertEventParticiPant()`/`InsertEventParam()` (единственное число) — методов с
   такими именами **не существует** нигде в `DS_Event.class.php` (там только
   `InsertEventParticiPants()`/`InsertEventParams()`, множественное число, с другой сигнатурой).
   При реальном вызове это должно приводить к фатальной PHP-ошибке. Судя по всему,
   `EH_ArmyMovementTo`/`EH_EventCreate` — старый, вышедший из употребления путь (боевая логика
   переехала на `EH_Army`+`EH_ArmyMovementToAttack`/`ToDefend`+`ME_CreateArmyAction`, которые и
   перечислены в `containerTails` внутри `EH_Event::setTrash()`, а `EH_ArmyMovementTo` там не
   упомянут). **Для ремастера этот путь копировать не нужно** — ориентируйтесь на
   `EH_Army`/`ME_CreateArmyAction`.

**Дополнение по схеме БД (`schema.sql`, pg_dump живой игровой БД, вне `old-sphere/`)**: в БД есть
готовая PL/pgSQL-функция `public.inserteventqueue(id_event integer, starttime varchar, endtime
varchar)` (`schema.sql:2030-2052`) — судя по сигнатуре и старому идиоматическому стилю (`GET
DIAGNOSTICS oid1 = RESULT_OID; SELECT ... WHERE oid = oid1` — паттерн для БД с ещё не отключённым по
умолчанию `WITH OIDS`), задумывалась как единая точка вставки строки в `ds_eventqueue`. Проверка по
всей кодовой базе (`grep -ri inserteventqueue`) не находит **ни одного** места, где PHP её вызывает.
Оба живых пути создания события — `DS_Event::InsertEventQueue($eventName, $times)`
(`DS_Event.class.php:70-98`, основной путь, раздел 4.1 пункты 1-2) и `SI_SearchEvents::InsertEventQueue($eventData)`
(`SI_SearchEvents.class.php:86-100`, используется при восстановлении событий после межмирового
переноса, см. `mechanics-world-transfer.md` §1.8 шаг 5) — вместо вызова этой функции сами делают
`SELECT nextval('ds_eventqueue_id_eventqueue_seq')`, а затем прямой `INSERT INTO ds_eventqueue (...)`.
То есть `inserteventqueue()` в БД — такой же орфанный (неиспользуемый) легаси-хелпер, как и
`createcombcomplex()`/`insertartefact()`/`insertattachedbuilding*()`/`insertbattle()`/`insertmessage()`
из той же исторической OID-семьи функций рядом в `schema.sql` — оставлен в схеме, но текущий код его
не зовёт нигде из проверенных путей создания событий.

Также стоит отметить: свойство `rehandle` (объявлено в `EH_Event.class.php`, проверяется в
`ME_EventMonitor::Scan()` как «повторно обработать это же событие в этом же проходе, не
дожидаясь следующего скана») **нигде не устанавливается в 1** ни одним из 30 конкретных
обработчиков — похоже на задуманный, но не реализованный/уже не используемый механизм
(многораундовый бой вместо этого создаёт отдельную новую строку `BATTLE_ROUND` на каждый
раунд, через путь №2 выше).

## 5. `EH_Event` — базовый класс обработчика

Каждый `EH_*`-обработчик наследует `EH_Event` (`ds/classes/EH/EH_Event.class.php`), которая, в
свою очередь, наследует `GameBase`. Ключевые механизмы базового класса:

- **Получение параметров события**: `getEventParams()` (строки 84-93) подгружает
  `DE_EventQueue::CreateFromID($this->eqid)`, копирует `eventstarttime`/`eventendtime` в
  `$this->eventstarttime`/`eventendtime`, а параметры (`ds_eventhasoption`) — в
  `$this->eventparams` (плоский ассоциативный массив `optionname => value`).
- **Пометка «обработано»**: `setTrash($eqtotrash = "")` (строки 121-140) — ставит
  `DE_EventQueue->setIsTrash(true)` и вызывает один из двух методов очистки «хвостов»
  (`DS_Event::CleanEventTailsUnitContainer()` для армейских событий из списка
  `containerTails`, иначе `CleanEventTailsGlobal()`) — хотя оба метода на момент чтения кода
  реально являются no-op (`return true;` первой строкой, до всех `DELETE`, — снос по каскадам
  и триггерам БД, согласно комментарию в самом коде). **Уточнение по схеме БД
  (`schema.sql`)**: комментарий про «триггеры БД» подтверждается и теперь именуется дословно —
  `CREATE TRIGGER ds_eventqueue_tr AFTER UPDATE ON ds_eventqueue FOR EACH ROW EXECUTE PROCEDURE
  tr_delete_event_when_trash()` (`schema.sql:29250`). Тело функции (`schema.sql:11565-11582`)
  простое: `IF NEW.istrash = true THEN DELETE FROM ds_eventqueue WHERE id_eventqueue =
  NEW.id_eventqueue; END IF` — то есть физическое удаление строки `ds_eventqueue` происходит
  **синхронно**, в рамках той же транзакции, что и `UPDATE ... SET istrash = true` из
  `setTrash()`, а не когда-то позже отдельным батчем. Внутри функции есть и закомментированная
  вставка в `ds_eventqueue_history` (`id_eventqueue, id_event, eventstarttime, eventendtime`) —
  ещё один пример недостроенной/заброшенной функциональности (история событий по факту нигде не
  сохраняется, ни в PHP, ни в БД).

  Отдельно, батчевым классом (не найден при исходном чтении, но напрямую относится к этой же
  таблице) существует `SU_DeleteTrashEvents extends SU_DeleteTrashBase`
  (`ds/classes/SU/SU_DeleteTrashEvents.class.php`), который периодически выбирает `id_eventqueue`
  всех строк с `istrash = true` пачками по 1000 и для каждой вызывает ещё одну PL/pgSQL-функцию,
  `SELECT drop_event_queue(id_eventqueue)` (`schema.sql:764-783` — тело тоже простое: `DELETE FROM
  ds_eventqueue WHERE id_eventqueue = id_eventqueue_v`, с несколькими `DELETE`-строками на связанные
  таблицы `ds_*bindeventqueue`/`ds_eventparticipant`/`ds_eventhasoption`, закомментированными по той
  же причине — снос по каскадам/триггерам БД). Поскольку `tr_delete_event_when_trash()` уже удаляет
  строку **в момент** простановки `istrash=true` (см. выше), в норме `SU_DeleteTrashEvents` должен
  находить ноль «висящих» строк на каждый прогон — похоже на защитный батч-процесс post factum
  (например, на случай прямого `INSERT ... istrash=true`, который не прошёл бы через `UPDATE` и
  поэтому не разбудил бы AFTER-UPDATE-триггер), а не на основной механизм удаления. Точка входа/крон
  для `SU_DeleteTrashEvents` не искалась в рамках этого прохода — не исключено, что она уже описана в
  `utils-and-maintenance.md`.
- **Проверка «пора ли обрабатывать»**: `_isTimeToHandle()` (258-265) сравнивает
  `event->getEndDate()->value` с «моментом проверки» — либо `time()` (обычный рефреш), либо
  `$this->checktime` (при внешнем/частичном рефреше, раздел 8.2).
- **Принудительный рефреш ДРУГОГО игрока**: `refreshUserinstance($refreshedUserInstanceID, $witEventHandling, $eventID)`
  (строки 165-186) — обработчик одного игрока может «дотянуть» другого участника события
  (например, при бое — обе стороны) до момента окончания текущего события, создавая
  `CR_RefreshUserInstanceCommand` с `setExternalRefreshMode()`. Это и есть механизм, которым
  игра гарантирует, что к моменту разрешения боя оборонявшийся уже успел «увидеть» все свои
  события вплоть до этого момента (см. предупреждение в шапке `ME_EventMonitor.class.php` про
  гонку «прилетела армия — но её ещё не обработали»).

## 6. Блокировки и «прогретость» события

### 6.1 `SU_Locker` — уже задокументирован в multi-world-architecture.md

Список типов объектов блокировки (`SU_Locker::$allowedStoreObjectTypes`,
`ds/classes/SU/SU_Locker.class.php:52-61`): `REFRESH` (помечен в коде как «не используется в
данный момент»), `USERACTION`, `EVENT`, `ACTION` (тоже помечен как «на самом деле не нужно»),
`MONEY_ACCOUNT`, `TEST_SYSTEM_FUNCTION`, `EVENT_QUEUE`. Реализация — строки в MySQL-таблице
`ds_locking_<DBID>` с уникальным ключом (см. multi-world-architecture.md §4.2).

Применительно к очереди/рефрешу используются два объекта:

- **`USERACTION`**, ключ — `id_userinstance`. Берётся **на трёх разных уровнях**, все — тем же
  самым `SU_Locker::CerateForCurrentDB('USERACTION')->lockWithTest($uiid)`:
  1. в самом `useraction.php:227-230` (для любого запроса, ещё до диспетчеризации команды);
  2. в `CR_RefreshUserInstance::_lock()` (`CR_RefreshUserInstance.class.php:109-121`);
  3. в `ME_EventMonitor::Scan()` (`ME_EventMonitor.class.php:132-135`);
  4. в `ME_TurnCalc::_lockComb()` (`ME_TurnCalc.class.php:122-129`, лочит `comb->getUserInstance()->getID()`).
  Таким образом, и явный игровой запрос игрока, и вызов демона идут через один и тот же
  замок на `uiid` — это и есть защита от двойной (гоночной) обработки одного и того же
  инстанса демоном и самим игроком одновременно.
- **`EVENT_QUEUE`**, ключ — `id_eventqueue`. Берётся точечно, для конкретного события, в
  `DE_EventQueue::_locking()` (`DE_EventQueue.class.php:135-155`) — но не всегда, а только если
  у типа события выставлен `need_create_lock = true` в `ds_event`, и (если ещё и
  `lock_only_in_end = true`) только когда до конца события осталось меньше
  `USERACTION_LOCK_TIME_OUT` секунд. Если залочить не удалось — событие помечается
  `lockedForHandl = true`, и его `setIsTrash()` становится запрещён (`assert(!$this->lockedForHandl)`
  в `setIsTrash()`), а `EH_Trading` явно проверяет этот флаг и высылает клиенту
  `DS_ClientLockHandler('USER_LOCK_DELAY', ...)` (`EH_Trading.class.php`, метод `_handle()`).

### 6.2 Таймауты (константы, `ds/config/settings.php`)

| Константа | Значение | Смысл |
|---|---|---|
| `USERACTION_LOCK_TIME_OUT` | `600` (сек = 10 мин) | Значение по умолчанию для тайм-аута любого лока `SU_Locker` (защита от «зависшего» лока, если процесс упал, не сняв его) — `settings.php:359` |
| `USER_LOCK_DELAY` | `5` (сек) | Через сколько секунд клиенту предлагается повторить запрос при обнаружении чужого лока — `settings.php:360` |

### 6.3 Флаги типа события `need_create_lock`/`lock_only_in_end`

Хранятся в `ds_event` (не в PHP), читаются через `DE_Event`. Значения по конкретным типам
событий из кода не обнаружены (это данные, не код) — открытый вопрос, какие именно типы
событий требуют лока (вероятно, боевые/торговые, где важна атомарность между несколькими
`id_userinstance`-участниками).

## 7. Порядок обработки нескольких событий

Порядок строго один: **по возрастанию `eventendtime`** (раньше заканчивается — раньше
обрабатывается), без учёта типа события. Источник:

- Начальная загрузка — SQL `ORDER BY eq.eventendtime` в `DS_Event::GetInstanceEvents()`
  (`DS_Event.class.php:26`).
- Довставка новых (порождённых цепочкой, раздел 4.1) событий через `DC_EventInstance::addEvent()`
  сразу пересортировывает список: `$this->endDates->sortByVals()` (`DC_EventInstance.class.php:74`),
  что транслируется в `asort()` — сортировка по значению (`endTime`) по возрастанию,
  с сохранением ключей (`GB_Array::sortByVals()`, `ds/classes/GB/GB_Array.class.php:198-202`).
- `fetchNextEvent()`/`fetchNextKey()` просто идут по массиву в этом порядке (без пересчёта на
  каждый вызов) — `GB_Array.class.php:159-173`.

Если у двух событий одинаковый `eventendtime` до секунды — порядок между ними не определён
явно (нет вторичного ключа сортировки), полагается на исходный порядок строк, который вернул
Postgres (не детерминирован без `ORDER BY id_eventqueue` в качестве tie-breaker'а).

Никакого приоритета «сначала бои, потом стройки» и т. п. в коде нет — единственный
приоритетный момент — обработка событий одного игрока целиком в рамках одного `Scan()`, но
между **разными** игроками порядок задаётся демоном (раздел 9), а не самой очередью.

## 8. `CR_RefreshUserInstance` — единая точка «рефреша»

### 8.1 Что происходит и в каком порядке

`CR_RefreshUserInstance::Exec()` (`ds/classes/CR/CR_RefreshUserInstance.class.php:36-77`):

1. Если у команды выключены и `withEvents`, и `withTurnCalc` — сразу выход (ничего не делать).
2. Если пользователь в режиме отпуска (`GL_IsUserInVocationMode::Test`) или под «силовым щитом»
   (`DE_PowerShild::isWorks()`) — тоже выход, рефреш полностью пропускается.
3. Берётся `USERACTION`-лок на `uiid` (раздел 6.1).
4. Инициализируется лог смертей юнитов на всех сотах игрока (`_initUnitsDeathLog()`).
5. **Если `withEvents`** → `_events()` → создаётся/переиспользуется `ME_EventMonitor` и
   вызывается `scan()` (раздел 3).
6. **Если `withTurnCalc`** → `_resources()` → для **каждой соты** игрока вызывается
   `ME_TurnCalc::Count($comb, $checkDate)`, а затем **один раз**, после всех сот,
   `$userInstance->setLastRefresh($checkDate)`.

**Важно: события обрабатываются раньше пересчёта ресурсов**, оба — в рамках одного вызова.
Это значит, что если за время простоя было и завершение стройки (которое, например, меняет
`MAX_AMOUNT`/потребление), и накопление ресурсов, то к моменту `ME_TurnCalc` эффект от
завершённых событий уже применён — расчёт производства идёт по **новым**, а не по старым
атрибутам соты. Технически `ME_TurnCalc` при этом всё равно использует единый `$turns`,
рассчитанный по всему интервалу разом (см. раздел 9) — то есть здание, достроенное «в
середине» простоя, задним числом получает бонус производства **за весь** интервал `$turns`,
а не только за часть после своей достройки. Это, по всей видимости, сознательное упрощение
модели (иначе пришлось бы разбивать пересчёт ресурсов на подынтервалы между событиями) —
**важно для баланса ремастера**: реализация «в лоб» одним `$turns` на весь интервал будет
воспроизводить оригинальное поведение, а «честное» посегментное начисление — не будет и
изменит баланс.

### 8.2 Три режима команды (`CR_RefreshUserInstanceCommand`)

| Режим | Устанавливается | Смысл |
|---|---|---|
| Обычный (по умолчанию) | `withEvents=true`, `withTurnCalc=true`, `checkDate` не задан | `_isTimeToHandle()`/`ME_TurnCalc` используют `time()` |
| **Внешний** (`isExternalRefresh`) | `setExternalRefreshMode(GB_Date $checkDate, DE_EventQueue $externalEvent)` | Вызывается из `EH_Event::refreshUserinstance()` — рефреш **другого** участника события, до момента окончания текущего события (или до `time()`, если оно ещё не наступило); в `ME_EventMonitor` это переключает поведение `Scan()`: события, у которых `handleInExternalRefresh()` возвращает `false` (по умолчанию в базовом классе — `endDate < checkDate`, т.е. «событие ещё не должно обрабатываться на этот момент»), пропускаются и **возвращаются обратно** в очередь пользователя (`eventsIDToReturn`), не будучи снятыми |
| **Частичный** (`isPartialRefresh`) | `setPartialRefreshMode(GB_Date $checkDate)` | Похожий механизм с явным `checkDate`, без привязки к конкретному «внешнему» событию |

Оба спецрежима передают `checktime` внутрь конкретного обработчика (`$event->getHandler()->checktime = ...`),
который используют все внутренние методы `_isTimeToHandle()`/`_getComplitPart()`/
`_countTmpTrainedUnitsCount()` вместо `time()`.

## 9. `ME_TurnCalc` — формула «хода»

### 9.1 Точная формула

```php
// ME_TurnCalc.class.php:135-143
self::$turns = (float)(
    (self::$checkDate->value - self::$userInstance->getLastRefresh()->value) /
    (self::$worldSettings->get('TURN_TIME') * 60)
);
if (self::$turns < 0) { self::$turns = 0; }
```

- `checkDate` — момент, до которого считаем (обычно `time()`, либо переданный `checkDate` при
  внешнем/частичном рефреше).
- `getLastRefresh()` — `ds_userinstance.lastrefresh`, обновляется **только** в
  `CR_RefreshUserInstance::_resources()` (раздел 8.1), то есть только тогда, когда
  `withTurnCalc=true`.
- `TURN_TIME` — мировая настройка (`DE_WorldSettings`, хранится в БД-таблице
  `ds_gamemodifier`, столбец `modifiername='TURN_TIME'`), **в минутах** — везде в коде
  встречается как `TURN_TIME * 60` (перевод в секунды), например `GL_TravelTime.class.php:78`,
  `GL_FullUnitsMovementCost.class.php:281`, `CR_CombView.class.php:156` и десятки других мест.
- **`$turns` — вещественное число**, не целое. Это значит, что «ход» не является дискретным
  тактом (нет округления/пола) — производство ресурсов и апкип начисляются **непрерывно
  пропорционально** прошедшему реальному времени, а `TURN_TIME` — просто единица измерения
  («сколько минут в одном ходе»), на которую нормированы все «за-ход»-показатели баланса
  (`TURN_PRODUCTION`, `TURN_UPKEEP` и т. п. атрибуты зданий/юнитов). По сути это continuous-rate
  экономика, просто исторически названная «ходами».
- Если накопленных ходов 0 (т. е. рефреш случился практически сразу после предыдущего) — вся
  функция `Count()` выходит рано, ничего не пересчитывая (`ME_TurnCalc.class.php:100-103`).

### 9.2 Значение `TURN_TIME` в продовых игровых мирах

> ✅ **Резолвнуто (2026-09-08, live-запрос к БД) — блокирующий вопрос
> баланса закрыт.** Реальные значения `TURN_TIME` (минут) на 11
> named боевых мирах:
>
> | Мир | `TURN_TIME` (мин) |
> |---|---|
> | Сфера Сириуса | 10 |
> | Сфера Ориона | 20 |
> | Сфера Змееносца | 30 |
> | Сфера Стрельца | 30 |
> | Сфера Парусов | 60 |
> | Сфера Эридана | 60 |
> | Сфера Кентавра | 60 |
> | Сфера Ориона-Ворона-Феникса | 60 |
> | Сфера Водолея-Льва | 60 |
> | Сфера Волка | 60 |
> | Чёрная дыра (закрыт) | 6000 |
>
> Предположение из старой версии этого раздела подтвердилось: подавляющее
> большинство активных боевых миров используют **60 минут** (1 час) за
> ход, младшие/более быстрые миры — 10-30 минут. «Чёрная дыра» —
> аномальные 6000 минут (100 часов); **подтверждено владельцем проекта**:
> это не игровой мир вовсе, а кладбище старых аккаунтов — соты
> неактивных игроков отправляются туда на вечное хранение по запросу
> самого игрока, никакой другой функции у мира нет (подробнее и
> технически — `mechanics-world-transfer.md` §1.9.1). Огромный
> `TURN_TIME` для такого мира логичен — экономика там намеренно не
> нужна. Остальные значения — снапшот на дату запроса, администрация
> могла менять их со временем.

`TURN_TIME` — не PHP-константа, а строка в БД-таблице `ds_gamemodifier`, редактируемая через
админку (`Admin/controls/GameSettings/GameModifaers/...`) индивидуально для каждого мира.
Дефолтного значения в PHP-коде для обычных (не турнирных) игровых миров нет — она
устанавливается при создании мира (SQL/админка, не в исходниках). Единственные конкретные
числа, встреченные в коде, — из шаблонов **турнирных** мини-миров:
- `TN_SettingsTemplateClassic.class.php:21`: `TURN_TIME = 1` (минута — с комментарием
  «0.5 !!!» рядом, видимо, историческая правка);
- `TN_SettingsTemplateQuest.class.php:21`: `TURN_TIME = 12` (минут).

Предположение из старой версии этого раздела («скорее всего, единицы-десятки минут») —
подтверждено реальными числами выше, см. блок ✅ в начале раздела.

### 9.3 Что именно считается за один вызов `ME_TurnCalc::Count()`

Для одной соты (`comb`), в следующем порядке:

1. `_countWorkersBorn()` — естественный прирост рабочих: `growth = MAX_WORKER * WORKER_GROWTH_RATE * turns`,
   плюс бонус расы (`BONUS_RELATIVE`), плюс удвоение при активном ускорении построек
   (`buildSpeedupEndTime > time()`); прирост не превышает `MAX_AMOUNT`.
2. `_countResourceProduction()` — по каждому ресурсному атрибуту типа `CURRENT_AMOUNT`, у
   которого есть парный `TURN_PRODUCTION`: `produced = round(TURN_PRODUCTION * turns)`
   (считается через `GL_CountCombProductionOfResource`, начисляется без явного ограничения
   потолком в этой функции — см. «Открытые вопросы»).
3. `_countBuildingsUpkeep()` — списание `KADERIUM`/`NANO_CRYSTAL`/`STASIS_ENERGY`/`FOOD`/`PRODIUM`
   по `TURN_UPKEEP * turns`; при нехватке `STASIS_ENERGY` (если в мире не выключено —
   `DISABLE_UPKEEP_DEACTIVATE_BUILDINGS`) — принудительная деактивация зданий
   (`ME_DeactivateBuildingsForUpkeep`).
4. `_countHumanUpkeep()` — потребление еды популяцией (рабочие+учёные+археологи, включая
   «в отлучке»); при нехватке (если не выключено `DISABLE_UPKEEP_DEATH_HUMANS`) — вымирание
   части населения (`ME_KillHumansForUpkeep`).
5. `_countArmyUpkeep()` — апкип армии, но **не** через `$turns` напрямую, а через отдельный
   аккумулятор точек апкипа `DE_LiveUpkeepPoints::flushPointsForComb()` +
   `ME_CountPeriodLiveUpkeep::Count($upkeepPoints, $checkDate)`; при нехватке ресурсов (и если не
   выключено `DISABLE_UPKEEP_DEATH_UNITS`) — гибель юнитов (`ME_KillArmysForUpkeep2`).

Перед всем этим блоком — повторная проверка «отпуска»/«щита» (`GL_IsUserInVocationMode`,
`DE_PowerShild`) уже внутри `ME_TurnCalc::Count()` (дублирует проверку из
`CR_RefreshUserInstance`, но на уровне отдельной соты — не факт, что это когда-то реально
различается, так как режим отпуска/щита привязан к `userInstance`, а не к соте, но защитный
код на всякий случай есть в обоих местах).

## 10. Два пути обработки: демон vs пассивный рефреш игроком

Оба пути **в конечном счёте вызывают один и тот же** `CR_RefreshUserInstance`. Разница — в том,
кто и с каким XML-тегом обращается к `ds/useraction.php`.

### 10.1 Активный путь — демон

`RD_RefreshDemon::_refreshUIID()` (`RD_RefreshDemon.class.php:257-267`) выполняет команду
```
php -f useraction.php <DBID> <UIID>
```
**без** третьего аргумента (XML) — `RD_AdeptUserActionToConsole::_formRequest()`
(`RD_AdeptUserActionToConsole.class.php:88-98`) в этом случае подставляет `<empty/>` по
умолчанию. Тег `empty` маршрутизируется в `UA_Empty` (`ds/classes/UA/UA_Empty.class.php`) — тот
же обработчик, что реагирует на перезагрузку страницы клиентом. `UA_Empty::performAction()`
проверяет `isAllowRefresh($uiid)` (см. врезку про throttle ниже) и, если разрешено, делает
**полный** рефреш: `$this->refresh("", "", 1, 1)` → внутри `UA_Refresh::_refreshUserInstance()`
это транслируется в `withEvents=true, withTurnCalc=true`.

**Кого выбирает демон**: `RD_RefreshDemon::_selectUIIDToRefresh()` →
`RD_RefreshDemonSearch::GetUIIDWithMaxEndEventsCount()`
(`RD_RefreshDemonSearch.class.php:24-64`):

```sql
SELECT ep.id_userinstance, count(*) as colvo
FROM ds_userinstance ui, ds_eventqueue eq, ds_eventparticipant ep
    LEFT JOIN ds_vocationmode vm ON (vm.enddate IS NULL AND vm.id_userinstance = ep.id_userinstance)
WHERE ui.id_userinstance = ep.id_userinstance
    AND eq.id_eventqueue = ep.id_eventqueue
    AND eq.istrash = false
    AND eq.eventendtime < now()
    AND ui.lastrefresh < (now() - '10 mins'::interval)
    AND vm.id_userinstance IS NULL
    [AND ep.id_userinstance NOT IN (<уже обработанные в этом проходе демона>)]
GROUP BY ep.id_userinstance
ORDER BY colvo DESC
LIMIT 20
```

То есть демон берёт **до 20 кандидатов** — тех, у кого есть хотя бы одно реально просроченное
(`eventendtime < now()`) событие, кого не рефрешили последние **10 минут** (жёстко
захардкожено в SQL, не через `TURN_TIME`/`REFRESH_RATE`), и кто не в бессрочном отпуске, —
сортирует по количеству просроченных событий по убыванию (сначала самые «загруженные»
события игроки), и берёт **первого незалоченного** (`SU_Locker::isLocked()`) из этой двадцатки
(`RD_RefreshDemonSearch.class.php:230-260`). Кандидатов, обработанных с ошибкой в текущем
проходе демона, он запоминает в `$this->refreshedUIIDs` и исключает из следующей выборки
(`NOT IN`), чтобы не зациклиться на «плохом» игроке. Цикл демона: если подходящих нет — спать
`loadWaitQuant` (60 сек, см. `RD_RefreshDemon.class.php:23`), иначе — рефрешить и сразу
выбирать следующего (без паузы).

**Не используемый (мёртвый) альтернативный путь**: в `RD_RefreshDemonSearch.class.php` есть
второй набор методов — `GetUserInstanceIDForRefresh()`, `InsertThreadInfo()`,
`GetActiveThreadCount()`, `MarkThreadAsComplite()`, `GetNotEndedThreads()`,
`AddInstanceToBlackList()`, `GetNewThreadID()` — судя по названиям, задел под
**многопоточный** демон с явным реестром запущенных потоков (`ds_user_refresh_process_list`) и
чёрным списком проблемных инстансов (`ds_user_refresh_black_list`). Ни один из этих методов
нигде в кодовой базе не вызывается (проверено `grep` по всему дереву `ds/classes`), а в
некоторых SQL там опечатки (`ISNERT INTO`, `UDPATE`) — похоже на незаконченный/заброшенный
код. Реально используется только однопоточный путь через `GetUIIDWithMaxEndEventsCount()`.

**Подтверждение по схеме БД**: `GetUserInstanceIDForRefresh(DE_WorldFromCollection $world)`
(`RD_RefreshDemonSearch.class.php:144-193`, часть этого же мёртвого многопоточного пути) на
последнем шаге зовёт `SELECT ds_get_end_date_for_partial_refresh($userInstanceID) AS check_time`.
Тело этой PL/pgSQL-функции (`schema.sql:1115-1124`) — в буквальном смысле стаб с комментарием
`/* New function body */`: `BEGIN RETURN NOW(); END;` — параметр `uiid_v` не используется вовсе,
функция всегда возвращает текущее время независимо от переданного инстанса. Это независимое,
DB-level подтверждение того, что вся ветка «частичного рефреша по времени окончания события»
для многопоточного прототипа демона никогда не была доведена до конца ни в PHP, ни в БД — и,
в отличие от `isallowrefresh()` (см. §10.3), даже не притворяется рабочей.

### 10.2 Пассивный путь — явный рефреш игроком

Клиент (Flash) периодически шлёт команду с тегом `refresh` → маршрутизируется в `UA_Refresh`
(`ds/classes/UA/UA_Refresh.class.php`). `checkAction()` тоже проверяет `isAllowRefresh()`.
Если разрешено — `full=1`, `_refreshUserInstance()` выставляет `withEvents=true`/`withTurnCalc=true`
(при `noscan` не равном 1/2). Если рано — показывает сообщение `TO_FAST_REFRESH_MESS` (с
параметром `REFRESH_RATE` — тоже мировая настройка, не константа; точные значения на 4
конкретных живых мирах — см. §10.3 ниже)
и делает только «облегчённый» рефреш (`full=0` → внутри вызывается `CR_RefreshUserInstance`,
но с `withEvents=false, withTurnCalc=false`, то есть фактически СРАЗУ выходит, ничего не делая,
см. первую строку `CR_RefreshUserInstance::Exec()`).

Кроме явного тега `refresh`, **большинство** других команд игрока (стройка, атака, торговля и
т. д.) в конце `performAction()` тоже вызывают `$this->refresh(2, ...)` — но с `noscan=2`.
Это **специально исключает** и события, и пересчёт ресурсов
(`UA_Refresh::_refreshUserInstance()`: при `noscan==2` `withEvents=false`, и отдельно
`withTurnCalc` требует `noscan != 2` — тоже `false`). Такой вызов используется **только** чтобы
пересобрать и отправить клиенту актуальный вид соты (`_createCombView()`) и текущие остатки
ресурсов (`_updateCombRes()`), **без** повторного скана очереди. Это важно: очередь/ходы не
обрабатываются при каждом клике игрока — только по явному тегу `refresh` (или `empty` от
демона/перезагрузки страницы).

### 10.3 Важная находка: throttle `isAllowRefresh` частично «отключён»

Есть **два независимых** места проверки «не слишком ли рано рефрешить»:

- `UA_Refresh::checkAction()` вызывает `DS_UserInstance::isAllowRefresh($this->uiid)`
  (PHP-обёртка, `ds/classes/DS/DS_UserInstance.class.php:169-186`). Но у этого метода **первая
  исполняемая строка — `return true;`**, весь код ниже (реальный SQL-запрос к БД-функции
  `isAllowRefresh(uiid)`) — недостижим. То есть throttle для явного тега `refresh` от Flash-клиента
  **фактически не работает** — каждый такой запрос считается «разрешённым».
- `UA_Empty::performAction()` (используется демоном и при F5 в браузере) делает **прямой**
  SQL-запрос `select isAllowRefresh($uiid) as ar`, **в обход** PHP-обёртки — то есть здесь
  реальная Postgres-функция `isAllowRefresh()` действительно вызывается и, предположительно,
  применяет throttle по `REFRESH_RATE`. Сама эта функция — хранимая процедура в БД, её текст
  недоступен в PHP-исходниках.

Итог: ограничение частоты полного рефреша по факту применяется только к пути «демон/перезагрузка
страницы» (`UA_Empty`), а к явному клиентскому тегу `refresh` (`UA_Refresh`) — нет (баг или
намеренно отключённая в проде фича; неизвестно). Для ремастера стоит **не** копировать эту
асимметрию буквально, а спроектировать единый, работающий throttle — если только не требуется
байт-в-байт воспроизвести историческое поведение (включая баги).

**Уточнение по схеме БД — точная логика `isallowrefresh()`.** Раньше в этом документе (открытый
вопрос №3 ниже) отмечалось, что текст реальной Postgres-функции недоступен в PHP-исходниках. По
`schema.sql:2141-2162` он теперь известен дословно:

```sql
select LastRefresh into last from DS_UserInstance where ID_UserInstance = $1;
if last is null then
  update DS_UserInstance set LastRefresh = now() where ID_UserInstance = $1;
  return 1;
end if;

select ((text((select value from ds_gamemodifier where modifiername = 'REFRESH_RATE'
  and id_world = (select id_world from ds_userinstance where id_userinstance = $1)))
  ||' sec')::interval) into rate;
if now() > (last + rate) then
  return 1;
else
  return 0;
end if;
```

То есть предположение из §6.2/§10.3 подтверждается буква в букву: throttle сравнивает `now()` с
`lastrefresh + REFRESH_RATE` (секунды, per-world значение из `ds_gamemodifier`, как и `TURN_TIME`),
и явно обрабатывает случай «ещё ни разу не рефрешился» (`last IS NULL`) — сразу разрешает и
проставляет `lastrefresh = now()`.

> ✅ **Точные значения `REFRESH_RATE` на 4 конкретных живых мирах (источник:
> БД, live-запрос, 2026-09-02)** — прямой SQL-запрос к `ds_gamemodifier`,
> закрывает открытый вопрос №2 для этих 4 миров:
>
> | Мир | `REFRESH_RATE`, сек |
> |---|---:|
> | Чёрная дыра | 240 |
> | Сфера Парусов | 30 |
> | Сфера Змееносца | 60 |
> | Сфера Гончих Псов (турнирный) | 30 |
>
> Значение заметно варьируется по мирам (30-240 сек, разброс в 8 раз) — то
> есть throttle клиентского рефреша реально настраивается индивидуально, а
> не наследует какое-то общее для всех значение. Не установлено, что именно
> определяет выбор конкретного числа (скорость мира/`TURN_TIME`, нагрузка
> на сервер, или просто ручная настройка администрации) — четыре значения
> здесь не показывают явной корреляции с `TURN_TIME` мира без дополнительных
> данных. Не универсальная константа кода — это значения именно для этих 4
> названных миров, для остальных миров архива всё ещё неизвестны.

**Побочная находка**: в той же директории, что и `UA_Refresh.class.php`, лежат ещё два файла —
`UA_Refresh2.class.php` и `UA_Refresh3.class.php` — оба определяют класс с тем же именем
`UA_Refresh` (коллизия имён, аналогичная уже отмеченной для `EH_Event_sel_for_update.class.php` в
открытом вопросе №5). Автозагрузчик резолвит `PREFIX_ClassName` в файл с точным именем класса
(`multi-world-architecture.md`/`CLAUDE.md`), поэтому оба файла — не подключаемые старые копии/бэкапы
(имя файла с цифрой — ручное сохранение версии, не git). Важно другое: их метод `checkAction()`
делает **тот же** прямой `sql = "select isAllowRefresh (%d) as ar"`, что и `UA_Empty`/`UA_Refresh2`/`3`
сейчас, — то есть раньше (судя по этим более старым копиям) `UA_Refresh` тоже обходил PHP-обёртку и
вызывал функцию БД напрямую, и лишь в какой-то момент был отрефакторен на вызов через
`DS_UserInstance::isAllowRefresh()` (уже сломанную `return true;`-обёртку). Это косвенно подтверждает
версию «регрессия от рефакторинга», а не «намеренно отключённая фича», хотя точная причина/момент
изменения не устанавливались.

## 11. Что будет при простое сервера (демон долго не работал)

Прямой защиты «слишком большого долга по ходам» в прочитанном коде **не найдено**:

- `ME_TurnCalc::_countTurns()` не ограничивает `$turns` сверху — сколько бы реального времени
  ни прошло с `lastrefresh`, формула `(checkDate - lastRefresh) / (TURN_TIME*60)` даёт
  пропорционально большое число, и `_countResourceProduction()` начислит `TURN_PRODUCTION * turns`
  без верхнего клампа (кроме случаев, когда сам ресурс имеет `MAX_AMOUNT`, но это проверяется не
  в `ME_TurnCalc`, а, судя по всему, на уровне `DE_CombAttributes::deltaAttribute()` — не
  прочитан в рамках этой задачи, см. «Открытые вопросы»). Апкип и вымирание населения/армии
  считаются аналогично — тоже линейно по `$turns`, без явного лимита на «сколько юнитов может
  умереть за один пересчёт».
- Единственная явная защита — не от «слишком большого долга по ресурсам», а от **зависания
  самого PHP-процесса** при большом количестве накопленных **событий** (не ходов): в
  `ME_EventMonitor::Scan()` (`ME_EventMonitor.class.php:159-166`) есть проверка
  `if (time() - timeStart > 240) { ...сообщение игроку "PROCCESSED_NOT_ALL_EVENTS"...; return true; }`
  — если обработка одного рефреша занимает больше **4 минут**, скан прерывается досрочно,
  оставляя необработанный хвост очереди на следующий вызов (следующий заход игрока или
  следующий проход демона доделает остальное). Также есть проверка `connection_aborted()`
  (обрыв соединения с клиентом) с аварийным коммитом уже сделанного и `exit`.
- Старая защита от зацикливания по счётчику итераций (`$deadCirlceDetector > 20000`)
  **закомментирована** в текущем коде (`ME_EventMonitor.class.php:150-158`) — то есть тоже не
  действует.
- Ресурсы конкретной соты пересчитываются `ME_TurnCalc` **целиком за один вызов** (не
  постранично/не пачками) — то есть при очень большом `$turns` расчёт по формулам раздела 9.3
  выполняется мгновенно (это чистая арифметика, не цикл по «ходам»), поэтому таймаут в 240
  секунд практическую роль тут не играет — теоретически он актуален только для очереди
  **событий** (раздел 3), где реально требуется по циклу пройтись по каждому объекту.

**Вывод для ремастера**: если сервер простаивал, скажем, сутки, то при первом рефреше игрок
получит ресурсы/апкип/смертность, рассчитанные как единый линейный экстраполированный скачок
на всю длину простоя (без искусственного дробления на «ход за ходом»), а очередь событий будет
разгребаться постепенно (возможно, за несколько заходов игрока/проходов демона, если событий
слишком много для одного 4-минутного окна). Стоит явно решить в дизайне ремастера, воспроизводить
ли эту потенциальную «дыру баланса» (мгновенный большой скачок ресурсов после длинного даунтайма)
буквально, или сознательно добавить кламп/дробление — в оригинале кламп **не** обнаружен.

### 11.1 Игровое «Перемирие» на время техработ — по игровой справке, не по коду

Разделы выше (§11) разбирают, что происходит **вычислительно** при простое
сервера (ходы/ресурсы/события). Отдельно от этого на сайте
(`https://destinysphere.ru/ds/help/peace.php`, проверено 2026) описан
**игровой, player-facing механизм «Перемирие»** — общемировой (не
альянсовый, см. `docs/mechanics-alliances-diplomacy.md` §5 про мир между
альянсами — это другая сущность) режим, включаемый администрацией на время
профилактических работ или технических сбоев:

- **Плановые техработы**: анонс не менее чем за 24 часа, минимальная
  продолжительность перемирия — 1 час.
- **Внеплановые сбои**: перемирие включается сразу же, минимум на 3 часа;
  если включение происходит после 19:00 по Мск — минимум 14 часов (то есть
  не может закончиться раньше 9:00 следующего дня по Мск).
- **Что разрешено во время перемирия**: любые не боевые действия
  (строительство, наука, археология, торговля), а также ограниченный набор
  «мирных» военных операций — переброска юнитов между домашней сотой и
  колониями, отправка на защиту сот своего домашнего комплекса или своего
  альянса.
- **Что запрещено**: отправка армий **в атаку**. Армии, чей бой должен был
  бы состояться во время перемирия, **автоматически отзываются** обратно на
  соту отправки. Армии, вылетевшие ДО начала перемирия, чей бой приходится
  на время УЖЕ ПОСЛЕ его окончания, не отзываются и продолжают полёт как ни
  в чём не бывало.

Это не найдено и не разбиралось на уровне кода (не идентифицирован флаг
типа `WORLD_TRUCE`/`ds_worldsettings`-запись, не идентифицирован
класс-обработчик, который проверяет это состояние в `GL_CanAttackComb`
наряду с `PEACE_PERIOD` между альянсами — см. `docs/mechanics-combat.md`
§10.2) — добавлено сюда как player-facing описание существующей механики,
кандидат для отдельного код-подтверждённого раздела при следующем проходе.

## 12. `SU_GameSessionWork` — отдельно, не про игровое время

Прочитан полностью (`ds/classes/SU/SU_GameSessionWork.class.php`, 294 строки). Вопреки
формулировке задания, класс **не содержит** привязки к моменту последнего рефреша игрового
состояния — это чисто инфраструктурный менеджер HTTP/PHP-сессий (валидация cookie/session id
между несколькими доменами игры — `game7.destinysphere.ru`, `ds-game.ru`, `ds.rambler.ru` и
т. д., проверка User-Agent, обёртка над тремя бэкендами хранения сессий — `db`/`file`/`memC`).
Понятие «последний рефреш» (`lastrefresh`) целиком живёт в `ds_userinstance` (доступ через
`DE_UserInstance::getLastRefresh()`/`setLastRefresh()`, `ds/classes/DE/DE_UserInstance.class.php:184-193`)
и не пересекается с PHP-сессией пользователя.

## 13. Резюме: сквозной сценарий одного рефреша

```
Триггер (демон "empty" ИЛИ клиентский тег "refresh")
        │
        ▼
UA_Empty / UA_Refresh :: performAction()
        │  (если isAllowRefresh — см. §10.3 про асимметрию проверки)
        ▼
CR_RefreshUserInstanceCommand(withEvents=true, withTurnCalc=true)
        │
        ▼
CR_RefreshUserInstance::Exec()
    ├── проверка отпуска / силового щита → если да, выход без изменений
    ├── SU_Locker('USERACTION').lock(uiid)
    ├── _events():
    │     ME_EventMonitor::Scan() — по всем НЕ-trash событиям uiid,
    │     отсортированным по eventendtime ASC:
    │       для каждого: getHandler()->handleEvent(eqid)
    │         → внутри: _isTimeToHandle()? да → применить эффект, setTrash(),
    │                                            возможно создать событие-потомка (new=1/newevents[])
    │                    нет → просто обновить % прогресса и выйти
    │       (макс. 4 минуты на весь скан, иначе прерывание с "PROCCESSED_NOT_ALL_EVENTS")
    └── _resources():
          для каждой соты игрока: ME_TurnCalc::Count(comb, checkDate)
            turns = (checkDate - lastrefresh) / (TURN_TIME_minutes * 60)
            если turns > 0: рабочие → ресурсы → апкип зданий → еда → апкип армии
          userInstance.setLastRefresh(checkDate)   ← ТОЛЬКО здесь двигается "часы" игрока
```

## 14. Открытые вопросы

1. **Точное значение `TURN_TIME` для боевых (не турнирных) миров не найдено в PHP-коде** —
   это данные в БД (`ds_gamemodifier`), не в файлах. Известны только турнирные шаблоны:
   Classic = 1 минута, Quest = 12 минут (`TN_SettingsTemplateClassic.class.php:21`,
   `TN_SettingsTemplateQuest.class.php:21`). Без реального значения для «обычных» миров нельзя
   откалибровать скорость экономики ремастера — **критично для баланса**.
2. ~~**`REFRESH_RATE`** (throttle частоты клиентского рефреша) — тоже мировая настройка из БД, а
   не константа; значение в коде не встречено.~~ — **закрыто для 4 конкретных живых миров**:
   точные значения (30-240 сек, варьируются по мирам) — см. §10.3, источник — прямой запрос к
   БД, 2026-09-02. Не универсальная константа — для остальных миров архива по-прежнему неизвестно.
3. **`isAllowRefresh()` частично не работает** (см. §10.3): PHP-обёртка `DS_UserInstance::isAllowRefresh()`
   имеет `return true;` первой строкой (мёртвый код ниже) и используется тегом `refresh`
   (клиентский heartbeat); тег `empty` (демон/F5) обходит эту обёртку и делает прямой SQL-вызов
   одноимённой функции в БД. **Разрешено по `schema.sql`** (`schema.sql:2141-2162`, текст функции
   приведён в §10.3): логика ровно такая, как и предполагалось — `now() > lastrefresh +
   REFRESH_RATE::interval`, с явным «первым разом» (`lastrefresh IS NULL` → сразу разрешить и
   проставить `now()`). ~~Не закрыт только вопрос №2~~ — вопрос №2 теперь закрыт для 4 конкретных
   живых миров, см. §10.3.
4. **`EH_ArmyMovementTo` + `EH_EventCreate`** выглядят как нерабочий/устаревший код (вызывают
   несуществующие методы `DS_Event::InsertEventParticiPant()`/`InsertEventParam()`, единственное
   число — см. §4.1). Реальная боевая цепочка идёт через `EH_Army` + `ME_CreateArmyAction`.
   Не подтверждено на 100%, что `EH_ArmyMovementTo` вообще недостижим (мог сопоставляться со
   старым, ныне отключённым в `ds_event` типом события) — это реконструкция по коду, не факт из
   документации.
5. **`ds/classes/EH/EH_Event_sel_for_update.class.php`** содержит класс с именем `EH_Event`
   (то есть дублирует имя базового класса) — не разобран подробно; вероятно, черновик/старая
   копия, не подключаемая в боевом autoload'е (иначе была бы неизбежная коллизия имён класса
   с основным `EH_Event.class.php`). Не проверено, что именно в нём отличается и используется
   ли файл вообще.
6. **Мэппинг `eventname → eventhandlerclass`** физически лежит в таблице `ds_event`, а не в PHP.
   Для большинства строк таблицы 4 сопоставление подтверждено литеральными строками в коде
   (`createEvent('ИМЯ', ...)`, `CreateInDB('ИМЯ', ...)` и т. п.), но для `SPY_IN_DEFEND`,
   `SPY_ZONDS_REPAIR`, `MOVE_TO_COMB`→`EH_MoveArmyToComb`, `DEFEND`→`EH_Defend` — сопоставление
   выведено по имени класса/по системным сообщениям внутри обработчика, а не по явному вызову
   создания события с этим именем (создающий код для этих конкретных типов не был прочитан
   построчно ради экономии бюджета задачи). Рекомендуется свериться с дампом `ds_event`, если он
   станет доступен.
7. **Нет верхнего лимита на `$turns` / накопленный "долг"** в `ME_TurnCalc` (см. §11) — не
   проверено, есть ли где-то на уровне `DE_CombAttributes::deltaAttribute()` (не прочитан в
   этой задаче) хотя бы клампинг по `MAX_AMOUNT` для складов ресурсов, который бы косвенно
   ограничил ущерб от гигантского скачка `$turns`.
8. **Флаги `need_create_lock`/`lock_only_in_end`** в `ds_event` — конкретные значения по типам
   событий не установлены (данные в БД).
9. ~~**Место инициализации `ds_userinstance.lastrefresh` при создании нового инстанса игрока**~~ —
   **решено по `schema.sql`.** `WorldUtils::addNewInstance()` (`Admin/include/WorldUtils.php:409-411`,
   и его копия в `ds/classes/WorldUtils.class.php`) создаёt `ds_userinstance` не прямым `INSERT`, а
   вызовом `SELECT insertUserInstance(id_user, id_race, id_world) AS uiid` — PL/pgSQL-функция
   (`schema.sql:2081-2100`) делает `INSERT INTO DS_UserInstance (ID_User, ID_Race, ID_World,
   LastRefresh, Window_Key) VALUES (..., now(), 0)` — то есть `lastrefresh` жёстко проставляется в
   `now()` при создании, никакого «гигантского `$turns`» на первом рефреше не возникает. Не
   проверено только, идёт ли **вообще каждый** путь создания нового `ds_userinstance` (обычная
   регистрация через `GE_ExecStartCreateInstance`, заселение NPC через `SU_PopulateNPC`, см.
   `mechanics-world-transfer.md` §1.11) именно через эту функцию, а не какой-то другой прямой SQL —
   подтверждено только для `WorldUtils::addNewInstance()`.
10. **`rehandle`-флаг** объявлен и проверяется в `ME_EventMonitor::Scan()`, но ни один из 30
    прочитанных обработчиков не выставляет его в `1` — похоже на неиспользуемый/устаревший
    механизм «повторной обработки того же события в этом же скане» (см. §4.1).

## 15. Смежный, но не связанный механизм: антибот (`turing=`, `delta`, `ENABLE_ANTIBOT`)

`ds/useraction.php` (уже в списке прочитанных файлов для этого документа, см.
шапку) содержит блок `ds/useraction.php:117-179`, структурно соседствующий
именно с разбором `$gameenter`/`logout`, который здесь описывается: если у
мира включена настройка `ENABLE_ANTIBOT`
(`DE_WorldSettings::CreateForCurentWorld()->get('ENABLE_ANTIBOT')`) и процесс
работает под Apache, сервер считает `qdelta`/`att_status` через
`DS_Question::GetTimeBeforeQuestion()`, а при входе в игру добавляет к ответу
поле `delta` (`ds/useraction.php:399-400`).

**Это не имеет отношения к очереди отложенных событий/пересчёту ходов**,
описанным в этом документе. `ds_antibot_userinstance.delta` — обратный
отсчёт до показа CAPTCHA-подобного вопроса «жив ли пользователь», а не
`$turns`/`TURN_TIME` из `ME_TurnCalc` и не что-либо из `ds_eventqueue`/
`EH_*`. Единственная связь — оба механизма используют один и тот же входной
файл (`ds/useraction.php`) и оба реагируют на `$gameenter`, но дальше не
пересекаются: обработчики `EH_*` не читают и не пишут ничего из
`ds_antibot_*`-таблиц (проверено — в схеме БД нет PL/pgSQL-функций,
упоминающих обе группы таблиц одновременно).

Также стоит уточнить формулировку задачи, которая привела к этому разделу:
буквальный комментарий разработчика `// Запуск АНТИБОТа` на функции
`parser.deltastart()` **не находится в этом документе** — он относится к
клиентскому `SPHERE_client/parser.as:243`, задокументирован в
[client-engine-and-protocol.md](client-engine-and-protocol.md) (строка
таблицы `delta` → `parser.deltastart(delta)`, около строки 531 этого файла).
Полный разбор серверной стороны механизма (эндпоинт `ds/antibot.php`, классы
`DS_Question`/`DS_Botlist`, пять таблиц `ds_antibot_*`, два независимых
контура бан-листа) — в [protocol-client-server.md](protocol-client-server.md),
раздел 3.2.
    прочитанных обработчиков не выставляет его в `1` — похоже на неиспользуемый/устаревший
    механизм «повторной обработки того же события в этом же скане» (см. §4.1).
