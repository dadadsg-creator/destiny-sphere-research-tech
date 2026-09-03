# Денежный движок (`MU`) — сводный обзор

Документ описывает `ds/classes/MU/` (52 файла, класс-префикс `MU`, вероятно
«Money Unit» или «Money User») — PHP-слой «бухгалтерии» внутриигровой валюты
(GC/SC). Это **сводный** документ: часть подсистемы уже подробно разобрана в
других файлах `docs/`, здесь она только резюмируется со ссылкой; основной
вклад этого документа — внутримировая часть (переводы между двумя счетами
одного мира, типы денежных операций, резервирование, аудит, комиссии),
которая раньше была описана только фрагментами.

Все пути ниже — относительно `old-sphere/`, если не указано иное; все файлы
без указания директории — из `ds/classes/MU/`.

## Что уже описано в других документах (не повторяется здесь)

| Тема | Где | Что там есть |
|---|---|---|
| Межмировые переводы денег (кабинет `UC` ↔ мир, перенос инстанса) | [mechanics-world-transfer.md, Часть 2](mechanics-world-transfer.md) | Модель счёта (`ds_money_account`, системный счёт `id_user=0`), `MU_ExecTransfer` (внутримировой примитив), `MU_ExecTransferInterworld` (два независимых `COMMIT`а по ключу), точка входа `UC_ExecMoneyTransfer` с комиссией `USER_CABINET_TRANSFER_TAX`, плановая сверка `MU_InterworldController` |
| Денежный движок на уровне БД (PostgreSQL) | [billing-and-monetization.md, §9](billing-and-monetization.md) | `money_account_transfer_money` — мёртвая заглушка; реальный перевод — в PHP; CHECK-инварианты неотрицательности баланса; триггер `money_account_update_history` (независимый от PHP аудит-журнал); блокировка `MU_ExecLockOperation`/`SU_Locker`; версионирование цен `ds_money_payments_price`; диспетчеризация по `ds_money_action_types.handler_class_name` |
| Внутренняя валюта GC/SC (общий обзор) | [billing-and-monetization.md, §1](billing-and-monetization.md) | `UC_PaymentServiceMoneyOperation::EnterMoney()` как общая «касса» для FreeToPay/SponsorPay/SMS/биллинга |
| Обмен GC → SC | [mechanics-economy-trade.md](mechanics-economy-trade.md), раздел про биржу | `MU_ExecOperationExchange::Exec`, курс `GC_COURSE` |
| Резервирование денег при ставке на аукционе | [mechanics-economy-trade.md](mechanics-economy-trade.md) | `MOVE_TO_RESERV`/`MOVE_FROM_RESERV` со стороны биржи (`ME_TradeDoBindOnLot` и др.) |
| Премиум-дни / SMS / FreeToPay / SponsorPay / партнёрка / билинг-шлюз | [billing-and-monetization.md](billing-and-monetization.md) | Целиком; `MU_*` там упоминается только как нижний слой |

Ниже эти темы **не пересказываются заново** — только внутримировая
«бухгалтерия», которую предыдущие документы не покрывали: диспетчер операций,
полный список типов операций и их обработчиков, резервирование, аудит счетов,
и найденная в ходе этого прохода мёртвая параллельная подсистема.

## 1. Общая архитектура: три слоя

```text
Внешние источники денег (не MU/):
  UC_PaymentServiceMoneyOperation  — общая "касса" личного кабинета (услуги, обмен, перевод)
  BG_MoneyEnter, BG_CreditsBillingPayment, BG_ReferalsPay  — входящий шлюз биллинга
  SMS_BuyGameCredits                — SMS-платежи
  ME_TradeExecLotBay, ME_TradeDoBindOnLot, ...  — биржа (оплата лота, резерв ставки)
  BJ.class.php, TN_RewardReciever   — мини-игра, турнирные призы
        │
        ▼  создают MU_EntityOperation(actionType, summ, fromUser, toUser)
┌───────────────────────────────────────────────────────────────────┐
│  MU_ControlExecOperations::ExecOperation()                        │
│    1. $operation->validate()                                      │
│    2. MU_ExecLockOperation::Lock()      — см. billing.md §9.4     │
│    3. handler = new {ds_money_action_types.handler_class_name}    │
│    4. handler->exec()  →  один из ~20 MU_ExecOperation* классов   │
└───────────────────────────────────────────────────────────────────┘
        │  каждый handler логирует (MU_EntityOperationLogRecord) и
        │  вызывает
        ▼
  MU_ExecTransfer::Exec($listID, $operation)  — см. billing.md §9.4/mechanics-world-transfer.md §2.2
        │
        ▼
  ds_money_account (PostgreSQL)  — CHECK-инварианты, триггер аудита — см. billing.md §9
```

Ядро подсистемы, которое эта секция раскрывает впервые (не описано в других
документах): (1) полный набор типов операций и их бизнес-правил, (2)
резервирование денег как отдельный примитив, (3) аудит счетов, (4) найденная
мёртвая параллельная «бухгалтерия для инстансов».

## 2. Value-объекты операции

- **`MU_EntityOperation`** (`MU_EntityOperation.class.php:1-314`) — неизменяемое
  описание одной денежной операции: тип действия (`DE_MoneyActionType`), сумма
  (`MU_EntityMoneySummInterface`), отправитель/получатель
  (`MU_EntityOperationMember`), опционально: доп. ID (`additionalID`, обычно
  `id_trade` или `transfer_key`), ключ гейта биллинга (`gateKey`), срок
  истечения + причина (для резервирования), пара миров (для межмировых
  операций). Метод `validate()` (`:248-306`) — единая точка проверки бизнес-
  правил по метаданным типа действия (взятым из БД через `DE_MoneyActionType`,
  см. billing.md §9.8): совпадение `fromUser`/`toUser` id/мира с
  `id_user_from`/`id_user_to`/`from_world`/`to_world` из `ds_money_action_types`,
  обязательность `additionalID` (`isNeedAdditionalID()`), обязательность
  `gateKey` (`getNeedGateKey()`), обязательность пары миров для
  `RECIVE_INTERWORLD_TRANSFER`/`SEND_INTERWORLD_TRANSFER`, обязательность
  `expireDate`/`reservReasonName` для `MOVE_TO_RESERV`.
- **`MU_EntityOperationMember`** (`MU_EntityOperationMember.class.php`) — пара
  (пользователь, мир): оборачивает `DE_User` + `DE_WorldFromCollection`.
  `CreateSystemAccount($worldName)` (`:37-42`) создаёt члена с
  `DE_User::CreateFromID(0)` — тот самый системный счёт мира, упомянутый в
  mechanics-world-transfer.md §2.1. Практический альтернативный способ
  получить того же системного пользователя, встречающийся во внешних
  вызывающих файлах (`BG_ReferalsPay`, `EnterMoney`/`DrawoutMoney` в админке,
  `TN_RewardReciever`, `UC_PaymentServiceMoneyOperation`) —
  `DE_User::CreateFromName('DestinySphere')` вместо `CreateFromID(0)`; оба пути
  ведут к одному и тому же `id_user = 0`, но это два разных способа получить
  один объект, разбросанных по кодовой базе без единообразия.
  `getLockID()` (`:64-67`) возвращает `"{worldShortName}_{userID}"` — именно
  этот идентификатор передаётся в `SU_Locker` (см. §7 ниже).
- **`MU_EntityMoneySumm`** (реализует `MU_EntityMoneySummInterface`) — сумма +
  валюта (`'GC'`/`'SC'`, `MU_EntityMoneySumm.class.php:19` —
  `$allowCreditName = array('GC' => 'gc_amount', 'SC' => 'sc_amount')`), с
  доп. полем `priceInGold` (используется только `CreateFromMoneyActionType()`
  как побочный атрибут, реального потребителя `getPriceInGolg()` — с опечаткой
  в имени метода — за пределами `MU/` не найдено). `MU_EntityMoneyNullSumm`
  — null-объект (сумма всегда 0, валюта захардкожена `'SC'`) для операций,
  цена которых берётся из `ds_money_payments_price` уже **после** создания
  операции (`setSummFromActionPrice()`, см. ниже).
- **`DE_MoneyActionType`** (`ds/classes/DE/DE_MoneyActionType.class.php`) — не
  входит в `MU/`, но это ключевой справочник, на который опирается вся
  диспетчеризация; подробности полей и таблицы `ds_money_action_types` — см.
  billing.md §9.8. Здесь важно: `isHavePrice()` **всегда возвращает `null`**
  (присвоение `$this->havePrice` закомментировано,
  `DE_MoneyActionType.class.php:139-145`) — то есть ветка `setSummFromActionPrice()`
  в `MU_EntityOperation`/`MU_ExecOperationServicePayment`/`MU_ExecOperationReturnMoney`
  технически недостижима в текущем коде (обходной путь `isHavePrice()`
  никогда не возвращает `true`), если только другая (не найденная в этом
  срезе) версия класса не переопределяет это поле — отмечено как открытый
  вопрос уже в billing.md, здесь только напоминается, так как затрагивает
  сразу три места в `MU/`.

## 3. Диспетчер — `MU_ControlExecOperations`

`MU_ControlExecOperations::ExecOperation($operation, $WI = '')`
(`MU_ControlExecOperations.class.php:5-13`) — вся диспетчеризация в три
строки: `validate()` → `MU_ExecLockOperation::Lock()` (лок аккаунтов, детали
в billing.md §9.4) → инстанцирование класса-обработчика по имени из
`$operation->getActionType()->getHandlerName()` → `$operationHandler->exec($WI)`.
Строка `//MU_ExecLogOperation::Log($operation);` (`:15`) закомментирована —
альтернативный текстовый лог операции (см. §6.2) никогда не вызывается из
этой точки.

Второй, независимый параметр `$WI` пробрасывается в `exec($WI)`, но ни один
из ~20 обработчиков ниже его не использует (везде `Exec()` без параметров,
PHP не проверяет сигнатуру потомка на совпадение с родителем) — похоже на
задел на будущее (например, для явного указания `world_identifier`, на что
намекает закомментированный блок в `MU_ExecOperationServicePayment.class.php:55-59`
— `if (!empty($WI)) { $logData->setField('world_idetifier', $WI); }`),
оставшийся неиспользуемым.

## 4. Примитив перевода — `MU_ExecTransfer` (кратко; подробности в других документах)

`MU_ExecTransfer::Exec($listID, $operation)` — единственный код, который
реально двигает деньги между двумя счетами **одного мира**: снять у
`fromUser`, начислить `toUser`, оба шага через `UPDATE ds_money_account` +
`INSERT ds_money_account_payments`. Полный разбор (включая ассерты
активности счёта, достаточности средств, запрет перевода себе) — в
[mechanics-world-transfer.md §2.2](mechanics-world-transfer.md) и
[billing-and-monetization.md §9.4](billing-and-monetization.md). Все ~20
обработчиков из §5 ниже в конце концов вызывают именно этот метод — то есть
у денежного движка **ровно одна** точка, где физически меняется баланс
(`MU_SearchOperations::DeltaAccount`, `MU_SearchOperations.class.php:5-29`), и
все различия между типами операций — это только *какая пара счетов* и *какая
сумма* передаются в этот примитив, плюс какие дополнительные поля пишутся в
лог (§6).

## 5. Типы операций и их обработчики (`MU_ExecOperation*`)

Каждый обработчик наследует `MU_ExecOperation` (`MU_ExecOperation.class.php:1-26`,
абстрактный метод `exec()`, потомки переопределяют его как `Exec()` — PHP
не различает регистр имён методов, поэтому это работает, но имя
непоследовательно между базовым классом и наследниками во всех ~20 файлах
без исключения). Общий паттерн тела каждого обработчика: 1) `assert()`
бизнес-правил специфичных для типа (роли `fromUser`/`toUser`, мир, знак
суммы), 2) чтение текущего счёта отправителя через
`MU_SearchOperations::GetAccountData()` (только чтобы взять `id_accaunt` для
лога — не для проверки достаточности средств, это делает уже
`MU_ExecTransfer`), 3) сборка `MU_EntityOperationLogRecord` под конкретный
`ds_money_list_*`-под-тип, 4) `MU_SearchOperations::InsertOperationLog()`, 5)
`MU_ExecTransfer::Exec($listID, $this->operation)`.

| Тип действия (`action_name`) | Обработчик | Направление | Кто вызывает (примеры) |
|---|---|---|---|
| `ENTER_MONEY`, `TURRNIR_PRIZE`, `REFERRAL_PAYMENT` | `MU_ExecOperationEnterMoney` | системный счёт → игрок (`fromUser.userID == 0`) | `UC_PaymentServiceMoneyOperation::EnterMoney()` (общая «касса», см. billing.md §1) — используется `BG_MoneyEnter`, FreeToPay/SponsorPay, `SMS_BuyGameCredits`, `TN_RewardReciever` (турнирный приз), `BG_ReferalsPay` (реферальная выплата) |
| `BILLING_PAYMENT` | `MU_ExecOperationBillingPayment` | игрок → системный счёт, **только** в мире `UC` | не найден вызывающий код внутри `old-sphere/` за пределами `MU/` — вероятно вызывается из закрытой части биллинга или устарел (открытый вопрос) |
| `BLACKJACK_BET` | `MU_ExecOperationBlackJackPayment` | игрок → (получатель по `toUser`, не фиксирован жёстко) | `ds/minigames/bj/BJ.class.php` — ставка в мини-игре «блэкджек» |
| `EXCHANGE_CREDITS` | `MU_ExecOperationExchange` | игрок → системный счёт (GC), затем системный счёт → игрок (SC) | Обмен GC→SC в личном кабинете, см. mechanics-economy-trade.md. Технические детали см. §5.1 ниже |
| `TRADE_PAYMENT` | `MU_ExecOperationTradePayment` | покупатель → продавец | `ME_TradeExecLotBay` (оплата выигранного лота биржи) |
| `PAY_TRADE_TAX` | `MU_ExecOperationPayTradeTax` | продавец → системный счёт | `ME_TradeExecLotBay` (комиссия биржи с продавца) |
| `PAY_TRANSFER_TAX` | `MU_ExecOperationPayTransferTax` | игрок → системный счёт **того же мира** | `UC_ExecMoneyTransfer::ExecTransferToUser` — комиссия за вывод SC из мира в кабинет, см. mechanics-world-transfer.md §2.4. См. §5.2 ниже про мёртвый код в этом файле |
| `PVP_TRANSFER` | `MU_ExecOperationPVPTransfer` | игрок(`UC`) → игрок(`UC`), либо игрок ↔ системный счёт | Прямой перевод игрок→игрок внутри кабинета; также переиспользован админ-инструментом `Admin/controls/GameSettings/TradeAdmin/DrawoutMoney/index.php` как «списать деньги с игрока» (`memberTo` = системный счёт) — нет отдельного `action_name` для админ-списания, используется этот же тип |
| `SEND_INTERWORLD_TRANSFER` | `MU_ExecOperationSendInterworldTransfer` | игрок → системный счёт мира-источника | `MU_ExecTransferInterworld` (шаг 1), см. mechanics-world-transfer.md §2.3 |
| `RECIVE_INTERWORLD_TRANSFER` | `MU_ExecOperationReciveInterworldTransfer` | системный счёт мира-назначения → игрок | `MU_ExecTransferInterworld` (шаг 2), см. mechanics-world-transfer.md §2.3 |
| `MOVE_TO_RESERV` | `MU_ExecOperationReserv` | игрок → системный счёт, с `expire_date`+`id_reserv_reason` | Резерв денег под активную ставку на аукционе (`ME_TradeDoBindOnLot` и связанные), см. §5.3 ниже и mechanics-economy-trade.md |
| `MOVE_FROM_RESERV` | `MU_ExecOperationReturnReserv` | системный счёт → игрок | Возврат резерва при перебитии ставки/отмене лота (`ME_TradeExecLotReturn`, `ME_TradeExecReturnUserBid`), см. §5.3 |
| `PS_ADD_AVATAR`, `PS_CHANGE_AVATAR`, `PS_CHANGE_NAME`, `PS_BUY_TRAINING_COMPLETION` и другие `PS_*`-услуги кабинета | `MU_ExecOperationServicePayment` (предположительно — см. ниже) | игрок → системный счёт, в пределах одного мира | `UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation*()` — общий платёж за платную услугу личного кабинета |
| `PS_RETURN_MONEY_EXCHANGE_COORDS`, `PS_RETURN_MONEY_EXCHANGE_COORDS_MELS` | `MU_ExecOperationReturnMoney` | системный счёт → игрок (жёсткий белый список из 2 имён действий, `MU_ExecOperationReturnMoney.class.php:5-8`) | `UC_ExecExchangeCoords::_CanselExchange()` через `UC_PaymentServiceMoneyOperation::ReturnMoney()` — возврат оплаты при отмене платного обмена координатами (см. mechanics-world-transfer.md, Часть 3) |

Привязка `PS_*`-услуг именно к `MU_ExecOperationServicePayment` — не
проверена по данным БД (в `schema.sql` нет данных, см. db-schema-reference.md),
а выведена из того, что: (а) этот обработчик — единственный, чья `exec()` не
проверяет конкретное имя действия через `assert`, а вместо этого проверяет
только `isActive()`/`isHavePrice()` — это подходит именно под «общую» платную
услугу; (б) он единственный вызывает `setSummFromActionPrice()` при
`isHavePrice()`, то есть рассчитан на цену из `ds_money_payments_price`, а не
на явно переданную сумму — это тоже типично для тарифицируемых услуг
кабинета, а не для фиксированных денежных операций вроде `TRADE_PAYMENT`.
Помечено как вероятное, не подтверждённое строкой в БД, сопоставление.

### 5.1. Обмен валют — техническая деталь `MU_ExecOperationExchange`

`MU_ExecOperationExchange::Exec()` (`MU_ExecOperationExchange.class.php:7-45`)
интересен тем, что физически выполняет **две** операции `MU_ExecTransfer::Exec()`
под **одним и тем же** `$listID` (`:37` и `:44`): сначала GC списывается у
игрока в пользу системного счёта (лог с полем `exchenge_course`, с опечаткой
в имени поля, задокументированной в БД-схеме), затем **новый**
`MU_EntityOperation` (тот же `actionType = EXCHANGE_CREDITS`, но развёрнутый —
`toUser`→`fromUser`) переводит `amountGC * GC_COURSE` в SC обратно игроку —
**без отдельной записи лога** для второй половины (используется тот же
`$listID`, но `MU_ExecTransfer` дополнительно не создаёт новую строку
`ds_money_list`, только `ds_money_account_payments`). То есть одна строка
`ds_money_list_exchange` формально описывает только списание GC, а
начисление SC отражено лишь в `ds_money_account_payments` (общем журнале
дельт, §9.5 billing.md) без собственной прикладной записи лога.

### 5.2. Мёртвый код внутри `MU_ExecOperationPayTransferTax`

Рабочая часть файла (`:5-30`) короткая и рабочая (списание налога у игрока в
пользу системного счёта того же мира — см. таблицу выше). Но после неё, в
теле того же файла, лежат **три вложенных друг в друга закомментированных
варианта** одной и той же логики (`:33-79`) — включая полностью повторяющую
рабочий блок версию и версию с межмировым резервированием
(`$operation->setInterworldTransferData(...)`, `DE_MoneyActionType::CreateFromName('PAY_TRANSFER_TAX')`
с `memberSystem`), которая явно относится к более ранней (или альтернативной,
несостоявшейся) архитектуре, где комиссия сама пересылалась бы между мирами.
Ни один из закомментированных вариантов не влияет на выполнение — чистая
мёртвая история изменений, оставленная в коде вместо VCS-истории (характерно
для всей кодовой базы, ср. `$CVS_Version` в шапках файлов).

### 5.3. Резервирование денег — примитив, отдельный от `MU_ExecTransfer`

`MOVE_TO_RESERV`/`MOVE_FROM_RESERV` — единственная пара типов операций,
где в дополнение к обычному переводу (игрок ⇄ системный счёт) операция несёт
`expireDate` + `reservReasonName` (`MU_ExecOperationReserv.class.php:5-27`,
поле `id_reserv_reason` в логе **захардкожено как `1`**, а не берётся из
`MU_SearchOperations::GetReservReasonIDByName()` — то есть в текущем коде
существует только одна причина резервирования с `id_reason = 1`, метод для
поиска по имени объявлен, но не используется этим обработчиком) и
`additionalID` (обычно `id_trade` — ставка на конкретный лот). Физически это
всё тот же `MU_ExecTransfer` (перевод в системный счёт и обратно), просто с
дополнительной парой полей в `ds_money_list_reserv`. Отдельные вспомогательные
методы `MU_SearchOperations::SetReservAsReturned()`/`GetReservRecordByTradeIDAndMember()`
(`MU_SearchOperations.class.php:118-155`) работают напрямую с
`ds_money_list_reserv`/`ds_money_list` в обход `MU_EntityOperation` — то есть
резерв можно как провести, так и прочитать/закрыть двумя разными путями
(через типизированную операцию `MOVE_FROM_RESERV`, и через прямую отметку
`return_date` без движения денег — вероятно, второе используется там, где
резерв просто снимается без компенсирующего перевода, например при полном
совпадении сторон; не проверялось построчно со стороны `ME_Trade*`, это вне
`MU/`).

## 6. Логирование операций

### 6.1. Рабочий путь — `MU_EntityOperationLogRecord` + `MU_SearchOperations::InsertOperationLog`

Каждая денежная операция всегда создаёт ровно две строки в одной
транзакции: одну в `ds_money_list` (общие поля: `id_list`, `id_user`,
`world_identifier`, `gc_amount`, `sc_amount`, `id_action`, `id_account`) и
одну в специфичной для типа действия таблице-расширении (`ds_money_list_trade_payments`,
`ds_money_list_reserv`, `ds_money_list_exchange` и т.д. — полный список см.
billing.md §9.5). `MU_EntityOperationLogRecord` (`MU_EntityOperationLogRecord.class.php`)
— построитель этих двух `INSERT`-запросов: `setField($name, $value)`
(`:47-59`) сам решает, в какую из двух таблиц отправить поле, по тому, есть
ли имя в предопределённом наборе `fieldsFirst` (`ds_money_list`); всё
остальное уходит в `fieldsSecond` (имя второй таблицы —
`$actionType->getLogActionTable()`, т.е. тоже из БД, колонка
`log_action_table` в `ds_money_action_types`). `id_list` берётся заранее из
последовательности `ds_money_list_id_list_seq`
(`MU_SearchOperations::InsertOperationLog`, `MU_SearchOperations.class.php:180-198`)
и подставляется в обе строки вручную (не полагаясь на `RETURNING`/FK
каскад) — то есть строго на PHP-стороне гарантируется совпадение `id_list`
между базовой и расширяющей таблицей, а не на уровне БД-констрейнта.

### 6.2. Мёртвый и сломанный альтернативный лог — `MU_ExecLogOperation`

`MU_ExecLogOperation::Log()` (`MU_ExecLogOperation.class.php:5-30`) — текстовый
построчный лог операций в файл `MoneyOperations.log`
(`SU_DebugTools::WriteToLog`), задуманный как человекочитаемый журнал
(«2006-06-06 12:01:01 PVP_TRANSFER Defo[TEL]=>DestinySphere[TEL] GC:200
LID:12133 AID:123421 GK:asdasd», комментарий в самом файле). Он не только
никогда не вызывается (единственный потенциальный вызов закомментирован в
`MU_ControlExecOperations::ExecOperation`, §3), но и **содержит баг**, из-за
которого не заработал бы, даже если бы был раскомментирован: строки
`$fromUserName = $memberTo->getUser()->getName();` и
`$fromWorldName = $memberTo->getWorldShortName();` (`:19-20`) повторно
присваивают переменные `$fromUserName`/`$fromWorldName` (уже заполненные
чуть выше данными `$memberFrom`), затирая их данными получателя — а
итоговая строка лога (`:24`) при этом ссылается на `$toUserName`/`$toWorldName`,
которые **вообще ни разу не объявлены** в теле метода (PHP выведет notice
«Undefined variable» и подставит пустую строку). Пример того же паттерна,
что уже отмечен в других документах для игрового движка: рабочий код рядом
с заброшенным альтернативным вариантом (см. `game-engine.md`/`billing-and-monetization.md`
про пары `.../...Old`).

## 7. Блокировка — `MU_ExecLockOperation` (кратко)

Полностью описано в [billing-and-monetization.md §9.4](billing-and-monetization.md):
`MU_ExecLockOperation::Lock()` (`MU_ExecLockOperation.class.php:10-26`)
берёт список счетов операции (`$operation->getAcountsListToLock()` —
`fromUser`+`toUser`+любые `systemUsers`, хотя в текущем коде `systemUsers`
никогда фактически не заполняется — присвоение закомментировано в
конструкторе `MU_EntityOperation`, `MU_EntityOperation.class.php:34-38`) и
для каждого уникального `getLockID()` (`"{world}_{userID}"`) вызывает
`SU_Locker::Create('MONEY_ACCOUNT', 0)->lockWithTest($ID)`, с откатом на
`DS_ClientLockHandler('USER_LOCK_DELAY', ...)` при неудаче — тот же
инструмент блокировки, что и `ME_TurnCalc::lockComb()` для сот, только с
типом `'MONEY_ACCOUNT'`. Локи копятся в статическом массиве
`self::$lockAccounts` на весь текущий PHP-процесс и не снимаются явно этим
классом (снятие — вероятно, глобальное, в конце запроса; ср.
`SU_Locker::UnlockAllInAllObjects()`, вызываемый явно, например, в
`DrawoutMoney/index.php` после операции — см. §5, `PVP_TRANSFER`).

## 8. Аудит счетов — `MU_CheckAccounts*`

Отдельная иерархия классов для offline-проверки консистентности денежных
счетов **одного** мира (не путать с межмировой сверкой §9 и `MU_InterworldController`).
Запускается только вручную/по расписанию через `ds/utils/MoneyAccountCheck.php`
(уже упомянут в [utils-and-maintenance.md](utils-and-maintenance.md) как
тяжёлый скрипт с лимитом ~59 минут/512 МБ) — либо в вебе (форма выбора миров,
`MU_CheckAccountsMainController::_firstScreen()`), либо в CLI с CSV-списком
идентификаторов миров в `argv[1]`; тот же контроллер используется из
`Admin/controls/GameSettings/TradeAdmin/classes/CheckMainController.class.php`.

- **`MU_CheckAccountsMainController`** (`MU_CheckAccountsMainController.class.php:17-30`)
  — точка входа, прогоняет по каждому выбранному миру список из двух
  проверок (`$checkList`, `:12-15`).
- **`MU_CheckAccountsManagerBalance`** (`MU_CheckAccountsManagerBalance.class.php:12-30`)
  — для каждого игрока с денежным счётом в мире вызывает
  `MU_CheckAccountsSearch::CountBalance()`
  (`MU_CheckAccountsSearch.class.php:37-51`), которая в свою очередь дёргает
  ту же самую БД-функцию `money_accaunt_balance()`, что подробно разобрана в
  [billing-and-monetization.md §9.2](billing-and-monetization.md) (сверка
  снэпшота/payments/history, гашение счёта при `BAD_CHEKSUMM`/`DISBALANCE`) —
  то есть этот класс не содержит собственной логики проверки, только цикл по
  игрокам плюс запись результата в лог `CheckMoneyAccounts_{world}.log` и
  общий `MoneyAccountErrors.log` (`MU_CheckAccountsManager::out()`/`processError()`,
  `MU_CheckAccountsManager.class.php:29-38`).
- **`MU_CheckAccountsManagerTotalSumm`** (`MU_CheckAccountsManagerTotalSumm.class.php:5-25`)
  — вторая, самостоятельная проверка: суммирует `gc_amount`/`sc_amount` по
  **всем** счетам мира разом (`MU_CheckAccountsSearch::GetMoneySummOnWorld()`,
  `SELECT sum(gc_amount), sum(sc_amount) FROM ds_money_account WHERE world_identifier = ...`)
  и сравнивает каждую сумму с захардкоженным эталоном
  **`$etalonSumm = 2000000000`** (`:5`) — **и для GC, и для SC используется
  одно и то же число**. Поскольку `MU_ExecTransfer` только *перемещает*
  деньги между парой счетов одного мира и никогда не создаёт/не уничтожает
  их (в том числе `ENTER_MONEY` — это перевод от системного счёта, а не
  эмиссия из ниоткуда), суммарный баланс всех счетов мира (включая системный
  счёт `id_user=0`) математически инвариантен относительно любых операций,
  прошедших через `MU_ExecTransfer` — то есть эта проверка, по всей
  видимости, ловит **утечку/недостачу денег в обход штатного движка** (прямой
  `UPDATE` в обход `MU_SearchOperations::DeltaAccount`, баг, вмешательство
  извне), а число `2 000 000 000` — предположительно исходный «уставной
  капитал», выданный системному счёту при создании мира (не подтверждено —
  начальные данные счетов нигде в доступном срезе кода/схемы не
  просматриваются; **предположение**, не факт).

## 9. Плановая межмировая сверка — доп. находка к уже описанному

Основной алгоритм (`MU_InterworldController`/`MU_InterworldExecTestSend`/
`MU_InterworldExecTestRecive`/`MU_InterworldExecCreateSend`/`MU_InterworldExecCreateRecive`/
`MU_InterworldSearch`/`MU_InterworldBase`) полностью разобран в
[mechanics-world-transfer.md §2.6](mechanics-world-transfer.md) — здесь не
повторяется.

**Новая находка этого прохода:** рядом с рабочим `MU_InterworldSearch`
(`MU_InterworldSearch.class.php`, используется из `MU_InterworldExecTestSend`/
`TestRecive`) в той же директории лежит почти дословный дубликат —
**`MU_CorrectInterworldSearch`** (`MU_CorrectInterworldSearch.class.php`,
272 строки против 294 у оригинала) с идентичным публичным интерфейсом
(`GetAllSendedNotChecked`, `GetRecivedTransfersByKey`, `MarkSendAsValid` и
т.д.). Поиск по всей `old-sphere/` не находит **ни одного** обращения к
`MU_CorrectInterworldSearch` откуда-либо (включая сам `MU/`) — класс мёртв.
Интересно, чем он отличается от рабочего аналога — это не просто копия:

- в `MU_CorrectInterworldSearch::GetRecivedTransfersByKey()`
  (`MU_CorrectInterworldSearch.class.php:75-131`) фильтр
  `iws.check_date IS NULL` **активен** (обычный SQL, не закомментирован,
  `:98`), тогда как в живом `MU_InterworldSearch::GetRecivedTransfersByKey()`
  (`MU_InterworldSearch.class.php:79-124`) этот же фильтр **закомментирован**
  (`--AND --iws.check_date IS NULL`, `:101-102`) — то есть живая версия
  сознательно ищет совпадение по ключу **среди всех** переводов, включая уже
  проверенные, а «мёртвая» версия искала бы только среди ещё не проверенных.
  Это меняет семантику сверки: если правка была намеренной, значит на каком-то
  этапе эксплуатации потребовалось находить пары даже после того, как одна
  из сторон уже помечена `check_date` (например, чтобы не плодить повторных
  «исправлений» при повторном запуске сверки на неконсистентных данных) —
  не установлено достоверно, баг это или осознанное исправление, только то,
  что старая («правильная» по названию класса) версия оставлена рядом и не
  используется;
- `MU_CorrectInterworldSearch` также содержит забытую отладочную строку
  `echo $sql."\n";` прямо перед проверкой на количество строк
  (`MU_CorrectInterworldSearch.class.php:106`, вне какого-либо условия — то
  есть выполнялась бы безусловно при каждом вызове) и **два** места, где при
  `count($res) > 1` код выводит SQL и вызывает `die()`
  (`:110-111`, `:165-166`) — в живом `MU_InterworldSearch` эти же проверки
  присутствуют по смыслу, но полностью закомментированы (`:110-114`,
  `:164-168`). Похоже на более раннюю, отладочную версию класса,
  впоследствии «начисто» переписанную под именем `MU_InterworldSearch`, а не
  наоборот (судя по количеству закомментированного отладочного кода, который
  обычно убирают по мере взросления, а не добавляют).

Практического риска эта находка не несёт (класс не вызывается), но полезна
как ещё один пример «двух версий одного класса рядом», уже отмеченного
шаблона для этой кодовой базы (billing.md §9.7, mechanics-economy-trade.md
раздел 3 и 5.10).

## 10. Мёртвая параллельная подсистема — «денежный счёт инстанса» (`*UserInstance*`)

Отдельная от всего вышеописанного (`ds_money_account`, привязка к
`(id_user, world_identifier)`) группа классов реализует **параллельный**
движок для денежного счёта, привязанного не к пользователю+миру, а к
**конкретному `ds_userinstance`** (таблицы `ds_money_account_userinstance`,
`ds_money_account_userinstance_reserve`, `ds_money_account_userinstance_actions`,
`ds_money_account_userinstance_balance` — ни разу не встречаются в остальном
`old-sphere/` за пределами этой группы файлов):

- `MU_ControlUserInstanceOperations::ExecOperation()`
  (`MU_ControlUserInstanceOperations.class.php:5-13`) — диспетчер, структурно
  копирующий `MU_ControlExecOperations` (§3), но для
  `GB_MoneyUserInstanceOperation` вместо `MU_EntityOperation`;
- `MU_ExecCreateOperationHandler::Create()` (`MU_ExecCreateOperationHandler.class.php:5-11`)
  — фабрика обработчика по имени класса, тоже читаемому из БД
  (`MU_SearchUserInstanceOperations::GetActionTypeDataByName()`, которая
  делает `INNER JOIN ds_money_action_scopes` к той же самой таблице
  `ds_money_action_types`, что использует «настоящий» движок — то есть обе
  системы, живая и мёртвая, читают один и тот же справочник типов действий,
  различая их по `id_scope`);
- `MU_ExecOperationUserInstance` (абстрактный) и три потомка —
  `MU_ExecOperationUserInstanceTransfer`/`...Reserv`/`...ReturnReserv`
  (`MU_ExecOperationUserInstance*.class.php`) — по смыслу зеркально повторяют
  `MU_ExecTransfer`/`MU_ExecOperationReserv`/`MU_ExecOperationReturnReserv` из
  §5, но напрямую дёргают `MU_SearchUserInstanceOperations::DeltaUserInstanceAccount()`/
  `InsertRezerv()`/`DeleteReservData()` вместо `MU_ExecTransfer`;
- `MU_ExecLockUserInstanceAccount` (`MU_ExecLockUserInstanceAccount.class.php`)
  — аналог `MU_ExecLockOperation`, но через
  `SU_Locker::CerateForCurrentDB('USER_INSTANCE_ACCOUNT')` (другой тип лока,
  другой конструктор — `CerateForCurrentDB`, а не `Create`);
- `MU_ExecLogUserInstanceOperation` (`MU_ExecLogUserInstanceOperation.class.php`)
  — аналог связки §6 (пишет и в БД через
  `MU_SearchUserInstanceOperations::InsertAction()`/`InsertBalance()`, и в
  текстовый лог `MoneyOperationsUserInstance.log`) — в отличие от «мёртвого»
  `MU_ExecLogOperation` (§6.2), этот метод **действительно вызывается** из
  `MU_ControlUserInstanceOperations::ExecOperation()` — но вызывать его
  некому, см. ниже;
- `MU_ControlUserOperations` (`MU_ControlUserOperations.class.php:5-24`) —
  предполагаемый публичный фасад над всем этим (`TransferFromUserToInstance`,
  `TransferFromInstanceToUser`, `EnterMoneyToUser`, `ExchangeMoney`) — **все
  четыре метода имеют полностью пустое тело**, ни одной строки кода;
- `MU_SearchUserOperations::GetCurrentAmmountOnAccount()`
  (`MU_SearchUserOperations.class.php:5-14`) — всегда возвращает
  `['GC' => 0, 'SC' => 0]`, независимо от переданного пользователя —
  тоже нереализованная заглушка;
- `GB_MoneyUserInstanceOperation` (`ds/classes/GB/GB_MoneyUserInstanceOperation.class.php`,
  **не в `MU/`**, но существует только ради этой подсистемы) — value-объект
  операции, аналог `MU_EntityOperation`. Его конструктор типизирует параметр
  как `MU_MoneySumm $summ` (`:54`), и `validate()` (`:141-157`) содержит
  `assert($this->summ instanceof MU_MoneySumm)` (`:150`) — но класс
  **`MU_MoneySumm` не существует нигде в `old-sphere/`** (реальный класс сумм
  называется `MU_EntityMoneySumm`, см. §2; ближайший похожий существующий
  класс — `GB_MoneySumm`, `ds/classes/GB/GB_MoneySumm.class.php`, но
  типизация ссылается не на него). Любая попытка вызвать конструктор
  `GB_MoneyUserInstanceOperation` с реальным объектом суммы упадёт с
  фатальной ошибкой PHP «Class `MU_MoneySumm` not found» ещё до объявления
  переменной.

**Проверка вызовов.** Поиск по всей `old-sphere/` не находит **ни одного**
обращения ни к `GB_MoneyUserInstanceOperation`, ни к
`MU_ControlUserInstanceOperations`, ни к `MU_ControlUserOperations`, ни к
`MU_SearchUserOperations`, ни к таблицам `ds_money_account_userinstance*` —
за пределами самих файлов этой подгруппы. **Вывод:** это полноценная,
архитектурно продуманная (лок, лог, диспетчер по типу из БД — тот же
паттерн, что у рабочего движка), но **никогда не завершённая и никогда не
подключённая** параллельная система учёта денег на уровне игрового
инстанса, а не пользователя+мира — вероятно, более ранний или альтернативный
дизайн (возможно, попытка привязать деньги к конкретной соте/инстансу вместо
аккаунта пользователя), брошенный до того, как были реализованы вызывающие
её точки входа, и не удалённый из репозитория. Для ремастера эта ветка не
несёт практической ценности как рабочий код, но интересна как исторический
артефакт альтернативного дизайна денежной модели.

## 11. Таблица всех 52 файлов

| Файл | Строк | Назначение |
|---|---|---|
| `MU_CheckAccountsMainController.class.php` | 93 | Точка входа offline-аудита счетов мира (веб-форма + CLI), см. §8 |
| `MU_CheckAccountsManager.class.php` | 144 | Абстрактный предок проверок аудита: общий `out()`/`processError()` (лог + echo) |
| `MU_CheckAccountsManagerBalance.class.php` | 35 | Проверка баланса каждого игрока через `money_accaunt_balance()` (см. billing.md §9.2) |
| `MU_CheckAccountsManagerTotalSumm.class.php` | 34 | Проверка суммарного баланса мира против эталона 2×10⁹, см. §8 |
| `MU_CheckAccountsSearch.class.php` | 85 | SQL-запросы для аудита: список миров, все счета мира, вызов `money_accaunt_balance()` |
| `MU_ControlExecOperations.class.php` | 20 | **Главный диспетчер** операций живого движка, см. §3 |
| `MU_ControlUserInstanceOperations.class.php` | 20 | Диспетчер мёртвой UserInstance-подсистемы, см. §10 |
| `MU_ControlUserOperations.class.php` | 25 | Пустой фасад-заглушка (4 метода без тела) над UserInstance-подсистемой, см. §10 |
| `MU_CorrectInterworldSearch.class.php` | 272 | Мёртвый дубликат `MU_InterworldSearch` с отладочным кодом, см. §9 |
| `MU_EntityMoneyNullSumm.class.php` | 34 | Null-объект суммы (0, валюта `'SC'`) для операций с ценой из БД |
| `MU_EntityMoneySumm.class.php` | 89 | Value-объект «сумма + валюта», см. §2 |
| `MU_EntityMoneySummInterface.class.php` | 15 | Интерфейс для `MU_EntityMoneySumm`/`MU_EntityMoneyNullSumm` |
| `MU_EntityOperation.class.php` | 314 | **Ядро**: описание одной денежной операции + `validate()`, см. §2 |
| `MU_EntityOperationLogRecord.class.php` | 116 | Построитель двух `INSERT` для лога операции (`ds_money_list` + расширение), см. §6.1 |
| `MU_EntityOperationMember.class.php` | 80 | Value-объект «пользователь + мир», системный счёт, `getLockID()`, см. §2 |
| `MU_ExecCreateOperationHandler.class.php` | 17 | Фабрика обработчика для UserInstance-подсистемы, см. §10 |
| `MU_ExecLockOperation.class.php` | 35 | Блокировка счетов живого движка через `SU_Locker`, см. §7 |
| `MU_ExecLockUserInstanceAccount.class.php` | 39 | Блокировка счетов UserInstance-подсистемы, см. §10 |
| `MU_ExecLogOperation.class.php` | 37 | Мёртвый и сломанный текстовый лог операций, см. §6.2 |
| `MU_ExecLogUserInstanceOperation.class.php` | 53 | Лог операций UserInstance-подсистемы (БД + файл), см. §10 |
| `MU_ExecOperation.class.php` | 26 | Абстрактный предок всех `MU_ExecOperation*`-обработчиков живого движка |
| `MU_ExecOperationBillingPayment.class.php` | 34 | Обработчик `BILLING_PAYMENT` (игрок → системный счёт, только `UC`), см. §5 |
| `MU_ExecOperationBlackJackPayment.class.php` | 29 | Обработчик `BLACKJACK_BET` (ставка в мини-игре), см. §5 |
| `MU_ExecOperationEnterMoney.class.php` | 40 | Обработчик `ENTER_MONEY`/`TURRNIR_PRIZE`/`REFERRAL_PAYMENT` (начисление игроку), см. §5 |
| `MU_ExecOperationExchange.class.php` | 46 | Обработчик `EXCHANGE_CREDITS` (обмен GC→SC), см. §5.1 |
| `MU_ExecOperationPayTradeTax.class.php` | 29 | Обработчик `PAY_TRADE_TAX` (комиссия биржи), см. §5 |
| `MU_ExecOperationPayTransferTax.class.php` | 83 | Обработчик `PAY_TRANSFER_TAX` (комиссия вывода в кабинет) + мёртвый код, см. §5.2 |
| `MU_ExecOperationPVPTransfer.class.php` | 40 | Обработчик `PVP_TRANSFER` (перевод игрок↔игрок/системный счёт в `UC`), см. §5 |
| `MU_ExecOperationReciveInterworldTransfer.class.php` | 29 | Обработчик `RECIVE_INTERWORLD_TRANSFER`, см. mechanics-world-transfer.md §2.3 |
| `MU_ExecOperationReserv.class.php` | 35 | Обработчик `MOVE_TO_RESERV` (резервирование под ставку), см. §5.3 |
| `MU_ExecOperationReturnMoney.class.php` | 41 | Обработчик возврата денег за отменённый платный обмен координатами, см. §5 |
| `MU_ExecOperationReturnReserv.class.php` | 37 | Обработчик `MOVE_FROM_RESERV` (возврат резерва), см. §5.3 |
| `MU_ExecOperationSendInterworldTransfer.class.php` | 29 | Обработчик `SEND_INTERWORLD_TRANSFER`, см. mechanics-world-transfer.md §2.3 |
| `MU_ExecOperationServicePayment.class.php` | 42 | Обработчик generic-платежа за `PS_*`-услугу кабинета (предположительно), см. §5 |
| `MU_ExecOperationTradePayment.class.php` | 28 | Обработчик `TRADE_PAYMENT` (оплата лота биржи), см. §5 |
| `MU_ExecOperationUserInstance.class.php` | 26 | Абстрактный предок обработчиков UserInstance-подсистемы, см. §10 |
| `MU_ExecOperationUserInstanceReserv.class.php` | 28 | Обработчик резерва UserInstance-подсистемы, см. §10 |
| `MU_ExecOperationUserInstanceReturnReserv.class.php` | 17 | Обработчик возврата резерва UserInstance-подсистемы, см. §10 |
| `MU_ExecOperationUserInstanceTransfer.class.php` | 17 | Обработчик перевода UserInstance-подсистемы, см. §10 |
| `MU_ExecTransfer.class.php` | 62 | **Единственный примитив**, реально двигающий баланс внутри мира, см. §4 |
| `MU_ExecTransferInterworld.class.php` | 33 | Примитив межмирового перевода (2 независимых транзакции), см. mechanics-world-transfer.md §2.3 |
| `MU_InterworldBase.class.php` | 17 | Общий `Out()` — лог в `InterworldMoneyTransferValidate.log` для всей сверки §9 |
| `MU_InterworldController.class.php` | 41 | Точка входа плановой межмировой сверки, см. mechanics-world-transfer.md §2.6 |
| `MU_InterworldExecCreateRecive.class.php` | 59 | Исправление «зависшего» перевода — досоздание приёма, см. mechanics-world-transfer.md §2.6 |
| `MU_InterworldExecCreateSend.class.php` | 68 | Исправление «зависшего» перевода — досоздание отправки, см. mechanics-world-transfer.md §2.6 |
| `MU_InterworldExecTestBase.class.php` | 17 | Общий предок для проверки исходящих/входящих переводов |
| `MU_InterworldExecTestRecive.class.php` | 88 | Проверка входящих переводов мира, см. mechanics-world-transfer.md §2.6 |
| `MU_InterworldExecTestSend.class.php` | 90 | Проверка исходящих переводов мира, см. mechanics-world-transfer.md §2.6 |
| `MU_InterworldSearch.class.php` | 294 | SQL для межмировой сверки (живая версия — см. §9 про отличия от дубликата) |
| `MU_SearchOperations.class.php` | 295 | **Ядро SQL живого движка**: `DeltaAccount`, `GetAccountData`, `InsertPayment`, `InsertOperationLog`, резервы, см. §4/§6.1 |
| `MU_SearchUserInstanceOperations.class.php` | 226 | SQL UserInstance-подсистемы (`ds_money_account_userinstance*`), см. §10 |
| `MU_SearchUserOperations.class.php` | 15 | Заглушка, всегда возвращает нулевой баланс, см. §10 |

## Безопасность

В отличие от `UC/`/`BG/`/`SMS/` (внешние платёжные интеграции, разобранные в
`billing-and-monetization.md`), 52 файла `ds/classes/MU/` — это внутренний
движок, не делающий ни одного исходящего HTTP-запроса и не проверяющий
никакой внешней подписи/секрета. При построчном чтении всех файлов **не
обнаружено** ни одного захардкоженного пароля, API-ключа или иного секрета
(`REDACTED_*`-плейсхолдеров тоже нет — заменять было нечего). Единственные
«секретные» строки в этом слое — случайные ключи корреляции межмировых
переводов (`GB_RandomPassword::Generate(25)`, используется как `transfer_key`/
`additionalID`, не как секрет аутентификации, а как техническая метка для
последующей сверки, см. mechanics-world-transfer.md §2.3) — они не требуют
занесения в `REDACTED_CREDENTIALS.txt`, так как не являются учётными данными.

## Открытые вопросы

1. **`MU_ExecOperationBillingPayment` (`BILLING_PAYMENT`)** — не найден ни
   один вызывающий код за пределами `MU/`. Либо вызывается из закрытой части
   биллинга (`billing.destinysphere.ru`, вне этого среза кода), либо
   устаревший/неиспользуемый тип операции.
2. **Привязка `PS_*`-услуг кабинета к `MU_ExecOperationServicePayment`** (§5)
   выведена по косвенным признакам (отсутствие явной проверки имени действия
   + использование `setSummFromActionPrice()`), а не подтверждена строкой
   `handler_class_name` из `ds_money_action_types` (данных БД нет в
   репозитории). Не установлено на 100%.
3. **`DE_MoneyActionType::isHavePrice()` всегда возвращает `null`** (поле не
   инициализируется, см. §2) — затрагивает три места в `MU/`
   (`MU_EntityOperation::setSummFromActionPrice()`,
   `MU_ExecOperationServicePayment`, `MU_ExecOperationReturnMoney`). Уже
   отмечено как открытый вопрос в billing.md §9.8; здесь дополнительно
   подтверждено, что ветка `isHavePrice()==true` физически недостижима как
   минимум в трёх обработчиках `MU/`, а не только теоретически в БД-схеме.
4. **`MU_CorrectInterworldSearch` vs `MU_InterworldSearch`** (§9) — не
   установлено, какая версия хронологически более ранняя и было ли снятие
   фильтра `check_date IS NULL` в живой версии осознанным решением или
   регрессией.
5. **Мёртвая UserInstance-подсистема** (§10) — не установлено, была ли она
   заброшена на этапе разработки в пользу текущей модели «счёт на
   (пользователь, мир)», или задумывалась для отдельной, так и не
   реализованной фичи (например, отдельный баланс на конкретный игровой
   инстанс вместо аккаунта). Для ремастера, вероятно, не имеет значения —
   современная модель `ds_money_account` полностью её заменяет.
6. **Захардкоженный `id_reserv_reason = 1`** в `MU_ExecOperationReserv`
   (§5.3) — не установлено, единственная ли это причина резервирования в
   реальной БД, или это ещё не завершённая типизация (метод поиска причины
   по имени существует, но не используется этим обработчиком).
7. **`MU_SearchOperations::GetActionsStatHandler()`/`GetAllowedPaymentServices()`**
   (`MU_SearchOperations.class.php:256-283`) — читают
   `ds_money_action_types.stat_handler`/весь список `action_name`, но их
   вызывающий код не разбирался в рамках этого документа (за пределами
   `MU/` не искался целенаправленно) — вероятно, статистика/отчётность в
   `Admin/`.

---

*Связанные документы: [mechanics-world-transfer.md](mechanics-world-transfer.md)
(межмировые переводы, Часть 2), [billing-and-monetization.md](billing-and-monetization.md)
(биллинг, БД-инварианты §9), [mechanics-economy-trade.md](mechanics-economy-trade.md)
(биржа, обмен валют), [game-engine.md](game-engine.md) (раздел про `MU` в общей
таблице подсистем).*
