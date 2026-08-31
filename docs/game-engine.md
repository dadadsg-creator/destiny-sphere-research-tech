# ds/classes — игровой движок «Сферы Судьбы»

> Анализ основан на просмотре списка всех файлов `ds/classes/` (при подсчёте
> через `find` получилось **1227 файлов** в поддереве — из них 1220 с
> расширением `.class.php`, плюс несколько посторонних файлов, см. раздел
> «Открытые вопросы». В задании фигурирует цифра 1223 — расхождение
> некритичное, возможно, оно возникло из-за пары файлов вроде
> `detect_player.php`/`domXML.class.php`, которые формально лежат в этой
> папке, но классом в обычном смысле не являются). Прочитаны полностью все
> перечисленные в задании инфраструктурные файлы (`GameBase.class.php`,
> `WorldUtils.class.php`, `Validator.class.php`, `Logger.class.php`,
> `SyncTables.class.php`, `SyncUsers.class.php`, `building_class.class.php`,
> `buildings.class.php`), а также `Base.class.php` и `ClassFactory.class.php`.
> По каждой префиксной группе прочитано выборочно от 2 до ~10 показательных
> файлов (в сумме больше 130 файлов из 1220) — не построчно весь каталог.
> Файлы в основном хранятся в кодировке **Windows-1251** (для чтения русских
> комментариев использовалась перекодировка `iconv -f CP1251 -t UTF-8`), лишь
> отдельные файлы оказались в UTF-8 или ASCII. Плейсхолдеры
> `REDACTED_DB_PASSWORD`/`REDACTED_API_SECRET`, встреченные в коде (например,
> в `SyncTables.class.php`), не анализировались как часть логики — это
> намеренно вычищенные секреты. Там, где расшифровка префикса не установлена
> однозначно, так и написано — «предположительно» / «не установлено».

## 1. Введение

### Что такое `ds/classes`

`ds/classes/` — это каталог с ~1220 файлами игровой логики движка «Сферы
Судьбы». Практически все файлы называются `ИмяКласса.class.php` и содержат
ровно один PHP-класс/интерфейс с точно таким же именем. Файлы сгруппированы
по подкаталогам с 2–4-буквенными префиксами (`BG/`, `UA/`, `DE/`, …) —
префикс подкаталога совпадает с префиксом имени всех классов внутри него
(например, всё в `ds/classes/UA/` называется `UA_ЧтоТо`). Небольшое число
файлов (16 штук) лежит прямо в корне `ds/classes/` без префикса и подчёркивания
в имени — это сквозная инфраструктура, не привязанная к одной подсистеме:
`Base.class.php`, `GameBase.class.php`, `ClassFactory.class.php`,
`Validator.class.php`, `Logger.class.php`, `OutputHandler.class.php`,
`SyncTables.class.php`, `SyncUsers.class.php`, `UserAction.class.php`,
`WorldUtils.class.php`, `building_class.class.php`, `buildings.class.php`,
`ArtTradeLogger.class.php`, `domXML.class.php`, а также два файла, которые
классами не являются вовсе (`detect_player.php` — старый скрипт-редирект,
`Start.class.php` — на деле не класс, а гигантский процедурный скрипт
инициализации на 41 КБ, оформленный с расширением `.class.php` по инерции).

### Как классы загружаются

Работают **два** параллельных механизма, оба в итоге приводят к одной и той
же функции `import()`:

1. **`ClassFactory::GetInstance($classname)`**
   (`ds/classes/ClassFactory.class.php:5-9`):
   ```php
   static public function GetInstance ( $classname ) {
       import ($classname);
       return new $classname;
   }
   ```
   То есть `ClassFactory` — это тонкая обёртка «подключить файл класса и
   тут же создать экземпляр». Используется относительно нечасто, в основном
   там, где имя класса вычисляется в рантайме и сразу нужен объект
   (пример: `WorldUtils.class.php:11` — `ClassFactory::getInstance
   ("DS_Common")`).

2. **`import($class)`** (`ds/include/Import.inc.php:8-58`) — резолвит путь к
   файлу по имени класса: если в имени есть `_`, префикс до первого `_`
   считается именем подкаталога (`CLASS_PATH/<PREFIX>/<Класс>.class.php`),
   иначе файл ищется прямо в `CLASS_PATH` (это и есть 16 файлов верхнего
   уровня). Дополнительно `import()` умеет распознавать классы `Smarty_*`
   (`Import.inc.php:22-25`) и отправлять их в отдельный `smartyAutoload()`.

   Там же, в `Import.inc.php:91-94`, определена функция `__autoload
   ($className)`, которая просто вызывает `import($className)`. Это
   означает, что **в подавляющем большинстве мест кода классы вообще не
   подключаются явно** — они подхватываются автозагрузчиком PHP4/PHP5 при
   первом обращении (`new ИмяКласса`, `ИмяКласса::метод()`). Например, в
   `UserAction.class.php:307` строка `$action = new $className;` не
   сопровождается никаким `import()`/`ClassFactory` — файл класса
   подключается автоматически через `__autoload`. `ClassFactory` в этом
   смысле не единственная и даже не основная точка загрузки классов, а
   скорее явный «синтаксический сахар» для тех мест, где сразу нужен и
   `import`, и `new`.

Итого: **имя файла = префикс-каталог + имя класса**, и это единственное
жёсткое правило именования во всём дереве.

### Базовые классы: два параллельных поколения архитектуры

В коде сосуществуют **две независимые иерархии базовых классов**, что важно
понимать при чтении любого файла:

- **«Старая» линия** (даты CVS-версий в комментариях — в основном
  2004–2010): `Base` (`Base.class.php`) → `GameBase` (`GameBase.class.php`)
  → `UA_Action` (родитель всех `UA_*`-обработчиков команд клиента) и
  `EH_Event` (родитель всех `EH_*`-обработчиков событий), а также часть
  классов `GL_*` (например, `GL_CanAttackComb extends GameBase`). Стиль:
  много «сырого» SQL прямо в методах через `$this->common->getResultArray()`,
  плотная работа с `$_SESSION` и `$GLOBALS`, ссылка на глобальный контейнер
  инструментов через `$this->tools` (см. раздел 4).
- **«Новая» (рефакторенная) линия**: `BS_Base` (`BS/BS_Base.class.php`) →
  `DE_Base`/`DC_Base` (доменные сущности и их коллекции) с паттерном
  «грязного» состояния и коммита (`$isModifyed`, метод `commit()`),
  статическими фабричными методами `CreateFromID()` и общим Identity Map
  через `GB_SingletonObjectStorage` (см. раздел 4). Судя по комментарию в
  `Core/ObjectBase.class.php:4-8` («*Represents base class of refactored
  Destiny Sphere server code*», автор — Akzhan Abdulin, **DestinySphere
  GmbH**), это результат отдельного, более позднего этапа рефакторинга
  движка в сторону полноценной доменной модели.

  Упоминание **DestinySphere GmbH** прямо в исходниках подтверждает немецкое
  происхождение проекта, о котором говорится в
  [history-and-background.md](history-and-background.md); та же догадка
  независимо подтверждается находкой в `building_class.class.php` —
  единственном файле во всём `ds/classes`, где комментарии и печатаемый текст
  написаны **по-немецки** (см. раздел 4).

Обе линии не изолированы, а тесно переплетены: например, `UA_Attack`
(старый стиль, `extends UA_Action extends GameBase`,
`ds/classes/UA/UA_Attack.class.php:6`) внутри метода `checkLocalParams()`
создаёт и использует объекты нового стиля — `DE_Comb::CreateFromID(...)`,
`DE_Army::CreateFromID(...)` (`UA_Attack.class.php:56,89`). То есть
рефакторинг затронул в первую очередь доменную модель (`DE`/`DC`), а
command-слой (`UA`/`EH`) остался процедурным по духу и просто зовёт новые
сущности как библиотеку.

## 2. Таблица префиксов

Число файлов — по факту в подкаталоге (рекурсивно, все файлы, не только
`.class.php`).

| Префикс | Файлов | Подсистема | Назначение | Примеры ключевых классов |
|---|---|---|---|---|
| **BG** | 41 | Биллинговый гейт (Billing Gate) | Внешние точки входа для партнёрских/биллинговых систем: покупка ресурсов за деньги, премиум, статистика по рефералам/партнёрам, перенос аккаунта между мирами. Все классы — потомки `BG_Common`, работают через `incoming_info`/`error_status`/`outputAnswer()` | `ds/classes/BG/BG_BuyResources.class.php`, `BG_Premium.class.php`, `BG_ChangeWorld.class.php`, `BG_UserData.class.php`, `BG_MoneyEnter.class.php` |
| **BS** | 25 (в т.ч. вложенный `BS/Core/`, 8 файлов) | Базовая система нового поколения (Base System) | Корень «рефакторенной» иерархии: базовые типы, исключения, прямой доступ к БД, логирование | `ds/classes/BS/BS_Base.class.php`, `BS_DataBase.class.php`, `BS_Assertion.class.php`, `BS_Exception.class.php`, `BS_Integer.class.php` |
| **CR** | 5 | Контроллеры (Controller) | «Класс контроллера» по собственному описанию в коде — управляют поведением сложных составных операций: рефреш инстанса игрока, отображение соты, бой | `ds/classes/CR/CR_Base.class.php`, `CR_RefreshUserInstance.class.php`, `CR_RefreshUserInstanceCommand.class.php`, `CR_CombView.class.php`, `CR_Battle.class.php` |
| **DC** | 26 | Коллекции сущностей (предположительно Data/Domain Collections) | Типизированные коллекции над множествами `DE_*`-сущностей: армии на соте, участники события/боя, лоты и ставки на бирже | `ds/classes/DC/DC_Base.class.php`, `DC_CombArmys.class.php`, `DC_EventInstanceCollection.class.php`, `DC_BattleParticipantArmys.class.php`, `DC_TradeUserLots.class.php` |
| **DE** | 123 | Доменные сущности (Domain Entities) — крупнейшая группа | Объектная модель игры целиком: сота, армия, здание, бой, пользователь, альянс, торговый лот, раса, религия, настройки мира и т.д. Оборачивают данные `DS_*`, отслеживают изменения, коммитят в БД | `ds/classes/DE/DE_Comb.class.php`, `DE_Army.class.php`, `DE_Battle.class.php`, `DE_Building.class.php`, `DE_UserInstance.class.php`, `DE_Alliance.class.php`, `DE_WorldSettings.class.php` |
| **DS** | 75 | Слой доступа к данным (Data Storage/Source) | «Старые» статические классы-шлюзы к таблицам БД на чистом SQL (`getResultArray`), плюс центральный класс-синглтон подключения `DS_Common`, плюс подсистема кеширования `DS_Cache*` | `ds/classes/DS/DS_Common.class.php`, `DS_Comb.class.php`, `DS_Building.class.php`, `DS_User.class.php`, `DS_Cache.class.php` |
| **DU** | 16 | Удаление инстанса пользователя (Delete User) | Каскадное удаление данных аккаунта/инстанса при закрытии/переносе: события, армии, бои, здания, соты, альянсы, сообщения | `ds/classes/DU/DU_ControllerDeleteUserInstance.class.php`, `DU_DeleteBase.class.php`, `DU_DeleteCombs.class.php`, `DU_DeleteArmys.class.php`, `DU_ClearWorld.class.php` |
| **EH** | 34 | Обработчики событий (Event Handlers) | Выполняются, когда наступает время запланированного игрового события (`ds_eventqueue`): достройка/апгрейд здания, движение армии, раунд боя, колонизация, шпионаж, торговля, терраформинг | `ds/classes/EH/EH_Event.class.php`, `EH_ConstructBuilding.class.php`, `EH_ArmyMovementToAttack.class.php`, `EH_Colonize.class.php`, `EH_TrainUnit.class.php` |
| **GB** | 30 | Базовые типы и глобальный контейнер (Global Base) | Типизированная система «значений» игры по паттерну Flyweight (`GB_Date`, `GB_Integer`, `GB_MoveSpeed`…), контейнер текущего мира/БД/языка (`GB_GlobalContainer`), Identity Map (`GB_SingletonObjectStorage`) | `ds/classes/GB/GB_GlobalContainer.class.php`, `GB_SingletonObjectStorage.class.php`, `GB_TypeFactory.class.php`, `GB_DataType.class.php`, `GB_Date.class.php` |
| **GE** | 25 | Портал входа в игру (предположительно Game Entrance) | Логика «внешнего» портала до входа в конкретный мир: логин, список миров, регистрация, смена пароля, привязка Rambler ID, антибот-защита | `ds/classes/GE/GE_ExecStartGameEnter.class.php`, `GE_Login.class.php`, `GE_LogicCanEnterWorld.class.php`, `GE_LogicWorldsList.class.php`, `GE_RamblerLogin.class.php` |
| **GH** | 2 | Помощь новичкам (предположительно Game Help) | Крошечная подсистема встроенной справки по зданиям для новичков (`Nub` = newbie) | `ds/classes/GH/GH_MainInterface.class.php`, `GH_NubHelper.class.php` |
| **GK** | 11 | Обмен ключами между серверами (Gate Key) | Изолированная мини-система с собственным интерфейсом доступа к БД (`GK_DBInterface`, не связана с `DS_Common`), реализующая master/slave-обмен «ключами ворот» между серверами | `ds/classes/GK/GK_GateKeyController.class.php`, `GK_KeySearch.class.php`, `GK_Reciever.class.php`, `GK_Slave.class.php`, `GK_SlaveSearch.class.php` |
| **GL** | 84 (в т.ч. 1 файл с чужим префиксом, см. раздел 5) | Игровая логика/расчёты (Game Logic) | Формулы и бизнес-правила игры: можно ли атаковать/защищаться, время перелёта, боевая мощь, боевой опыт, цены на бирже, апкип, энергощиты и т.д. Часть классов — потомки `GameBase` (старый стиль), часть — чистые статические калькуляторы | `ds/classes/GL/GL_CanAttackComb.class.php`, `GL_TravelTime.class.php`, `GL_ArmyPower.class.php`, `GL_TradeCalcBestPrice.class.php`, `GL_BattleXP.class.php` |
| **GP** | 21 | Атрибуты соты и патчи, версия «A» (расшифровка не установлена) | Низкоуровневый доступ к атрибутам соты (`ds_combhasresource`) и утилиты патчинга БД. Почти дословно дублируется группой `MP` (см. раздел 5) | `ds/classes/GP/GP_CombBase.class.php`, `GP_Patch.class.php`, `GP_PatchComb.class.php`, `GP_CombTurnProduction.class.php`, `GP_CombArtefactBonus.class.php` |
| **LG** | 11 | Гейт для внешних инструментов и точечных админ-правок (расшифровка не установлена) | Авторизация внешнего «анализатора» статистики по SID сессии, экспорт данных для сторонних рейтинговых/статистических сайтов (CVS/XML), ручные правки (имя соты/игрока, размер кольца) | `ds/classes/LG/LG_Authorization.class.php`, `LG_XMLBattleExport.class.php`, `LG_CVSRankingExport.class.php`, `LG_CombNameChange.class.php`, `LG_ColoniesListExport.class.php` |
| **MC** | 5 | Компилятор сообщений (Message Compiler) | Батчевая сборка и рассылка объединённых игровых писем (например, по итогам работы отряда археологов) вместо потока отдельных сообщений | `ds/classes/MC/MC_CoompilerBase.class.php`, `MC_Base.class.php`, `MC_CompileAndSendMessages.class.php`, `MC_CompilerArcheologyTeam.class.php`, `MC_CompilerTrainWarUnits.class.php` |
| **ME** | 84 | Исполнение игровой механики (предположительно Mechanics Engine) | Непосредственное выполнение изменений состояния игры: старт/окончание боя и раунда, гибель юнитов от апкипа, торговые сделки, бан/заморозка аккаунта, удаление инстанса, пересчёт хода (`ME_TurnCalc`) | `ds/classes/ME/ME_StartBattle.class.php`, `ME_EndBattle.class.php`, `ME_TurnCalc.class.php`, `ME_TradeExecLotBay.class.php`, `ME_KillArmysForUpkeep.class.php` |
| **MP** | 33 | Атрибуты соты и патчи, версия «B» + проверки целостности БД | Почти полный дубликат `GP` (тот же набор `CombBase`/`Patch*`/`Comb*`) плюс уникальные скрипты поиска «осиротевших» событий/армий/боёв без событий — судя по всему, параллельная ветка для другой версии мира (Classic/Unlimited/Pulsar) | `ds/classes/MP/MP_CombBase.class.php`, `MP_Patch.class.php`, `MP_AlliancePeace.class.php`, `MP_DBEventsWithDeletedUsers.class.php`, `MP_DBNotEndedBattlesWithoutEvent.class.php` |
| **MU** | 52 | Денежный движок (предположительно Money Unit/Users) | Полная «бухгалтерия» внутриигровой валюты: пополнение, обмен, оплата биржи/услуг, включая **межмировые** переводы с отдельной проверкой консистентности транзакций | `ds/classes/MU/MU_ExecOperation.class.php`, `MU_ExecOperationEnterMoney.class.php`, `MU_InterworldController.class.php`, `MU_ExecTransferInterworld.class.php`, `MU_EntityOperation.class.php` |
| **PN** | 39 | Панели (Panel) — сборщики данных для UI | Формируют данные конкретных окон/панелей Flash-клиента (альянс, биржа, армия, шпионаж, экономика, война), обычно поверх `UA_GetBuildMenu` | `ds/classes/PN/PN_AllianceManagement.class.php`, `PN_TradePanel.class.php`, `PN_ArmyOverview.class.php`, `PN_SpyAction.class.php`, `PN_WarCalculator.class.php` |
| **PU** | 2 | Премиум-утилиты (Premium Utility) | Точечные операции с длительностью премиум-статуса и его «анализатором» | `ds/classes/PU/PU_ExecChangePremiumDays.class.php`, `PU_ExecChangeAnalizerPremium.class.php` |
| **RD** | 3 | Тикающий движок мира / консольный раннер (Refresh Daemon) | Долгоживущий демон, циклически рефрешащий «протухшие» инстансы игроков, вызывая `useraction.php` из командной строки; адаптер CLI-режима для `UserAction` | `ds/classes/RD/RD_RefreshDemon.class.php`, `RD_RefreshDemonSearch.class.php`, `RD_AdeptUserActionToConsole.class.php` |
| **SI** | 34 | Перенос игрока между мирами (снэпшоты инстанса) | Сохранение полного состояния игрока (события, наука, религия, боевой опыт, соты, бонусы) в промежуточные таблицы и восстановление в целевом мире | `ds/classes/SI/SI_ControlStoreUserInstance.class.php`, `SI_ExecRestoreUser.class.php`, `SI_FinalizeWorldTransfer.class.php`, `SI_ExecStoreComb.class.php`, `SI_SearchInterface.class.php` |
| **SMS** | 7 | SMS-биллинг | Покупка премиума/кредитов/артефактов через платные SMS, рендеринг форм через шаблонизатор Smarty | `ds/classes/SMS/SMS_Controller.class.php`, `SMS_BuyGameCredits.class.php`, `SMS_ArtefactController.class.php`, `SMS_ArhiologyController.class.php`, `SMS_DataDase.class.php` |
| **ST** | 7 | Статические справочники (предположительно Static Tables) | Read-only справочные данные, кешируемые целиком в `GLOBAL_CACHE`: локализация текстов, системные сообщения, типы действий | `ds/classes/ST/ST_Localization.class.php`, `ST_SystemMessages.class.php`, `ST_Attributes.class.php`, `ST_ArmyActionTypes.class.php`, `ST_Base.class.php` |
| **SU** | 151 | Служебные утилиты (Service Utilities) — крупнейшая группа | Разнородная инфраструктура: вывод ответа клиенту, работа с сессией, кеширование, репликация между БД миров, диагностика/самотестирование, крон-скрипты очистки «мусора», контроль версий Flash-клиента | `ds/classes/SU/SU_OutputHandler.class.php`, `SU_GameSessionWork.class.php`, `SU_ReplicationControl.class.php`, `SU_ClientKey.class.php`, `SU_DeleteTrash.class.php` |
| **TI** | 14 | Входные данные панели (предположительно Template/Transport Input) | DTO-объекты с «сырыми» данными для конкретной панели — пара к `TL` (см. ниже) | `ds/classes/TI/TI_CombView.class.php`, `TI_TradePanel.class.php`, `TI_SpyLogs.class.php`, `TI_AuctionPanel.class.php`, `TI_BaseMenu.class.php` |
| **TL** | 15 | Сборка XML-ответа панели (предположительно Template Layer) | Статические классы вида `Form(TI_X $in)`, сериализующие `TI`-объект в XML-протокол для Flash-клиента | `ds/classes/TL/TL_CombView.class.php`, `TL_TradePanel.class.php`, `TL_SpyLogs.class.php`, `TL_AuctionPanel.class.php`, `TL_BaseMenu.class.php` |
| **TMP** | 19 | Разовые/сезонные скрипты (Temporary) | Одноразовые ивент-механики (Новый год, квизы) и разовые скрипты миграции/очистки конкретных данных | `ds/classes/TMP/TMP_NewYear.class.php`, `TMP_QuizController.class.php`, `TMP_ConvertUnitsToCholera.class.php`, `TMP_CheckDoubleCombs.class.php`, `TMP_RestoreDeletedArtefacts.class.php` |
| **TN** | 17 | Турниры (Turnir) | Управление отдельными турнирными мирами: старт/пауза/стоп, регистрация участников, шаблоны настроек (Classic/Quest), рассылка наград | `ds/classes/TN/TN_TurnirController.class.php`, `TN_StartManager.class.php`, `TN_Registration.class.php`, `TN_SettingsTemplateClassic.class.php`, `TN_RewardSender.class.php` |
| **UA** | 96 | Пользовательские действия (User Actions) — ядро игрового цикла | По одному классу на каждую XML-команду от Flash-клиента (атака, стройка, колонизация, торговля, альянсы…), реализуют общий шаблон `checkGlobalParams → checkLocalParams → checkAction → performAction` | `ds/classes/UA/UA_Action.class.php`, `UA_Attack.class.php`, `UA_ConstructBuilding.class.php`, `UA_Colonize.class.php`, `UA_TransferResource.class.php` |
| **UC** | 84 | Личный кабинет пользователя (User Cabinet) | Веб-кабинет вне Flash-игры: логин, глобальные (межмировые) альянсы, платежи, обмен кредитов, переводы между мирами; условно-MVC разделение `Control`/`Exec`/`Page`/`Stat` | `ds/classes/UC/UC_ControlUserCabinet.class.php`, `UC_ExecLogin.class.php`, `UC_PageIndex.class.php`, `UC_ExecMoneyTransfer.class.php`, `UC_ControlGlobalAlliance.class.php` |
| **VD** | 12 | Валидаторы данных (предположительно Value/Validation Data) | Более новый набор статических безсессионных валидаторов ввода (логин, пароль, e-mail, имя соты/альянса), частично дублирующий старый `Validator.class.php` | `ds/classes/VD/VD_Base.class.php`, `VD_UserName.class.php`, `VD_Password.class.php`, `VD_Email.class.php`, `VD_CombName.class.php` |

Кроме перечисленных в задании префиксов, в `ds/classes/` обнаружен ещё один
каталог верхнего уровня — **`Core/`** (8 файлов: `ObjectBase`,
`PropertyStorage`, `StaticVariableStorage`, `LowLevelStaticVariableStorage`,
`Trace`, `TraceListener`, `TextFileTraceListener`, `TestTraceListener`). Это
экспериментальный низкоуровневый фреймворк для хранения свойств объекта и
трассировки вызовов, лежащий в основе идей `BS_Base`/`DE_Base`. Подробнее и
про странность с его дублированием внутри `BS/Core/` — в разделе 5.

## 3. Ключевые подсистемы подробно

### 3.1. Здания

Здания живут сразу в нескольких слоях:

- **Данные/сущности**: `DE_Building`, `DE_BuildingAttributes`,
  `DE_BuildingByRace`, `DE_AttachedBuilding` (`DE/`), `DS_Building` (сырые
  SQL-запросы, `DS/`).
- **Команды игрока**: `UA_ConstructBuilding`, `UA_UpgradeBuilding`,
  `UA_ActivateBuilding`, `UA_DeactivateBuilding`, `UA_CancelBuilding`,
  `UA_DropBuilding`, `UA_CancelUpgradeBuilding`,
  `UA_CancelActivateBuilding` (`UA/`).
- **Обработка по завершении** (когда время строительства истекло):
  `EH_ConstructBuilding`, `EH_UpgradeBuilding`, `EH_ActivateBuilding`,
  `EH_DeactivateBuilding` (`EH/`).
- **Панели/просмотр**: `PN_BuildingInfo`, `PN_BuildingArtefact(2)` (`PN/`),
  `CR_CombView` — кеш отрисованного вида соты.
- **Пересчёт производства**: `GP_CombTurnProduction`/`MP_CombTurnProduction`
  и `ME_AddBuildingAttributesToComb`.

Полностью прочитан и разобран `EH_ConstructBuilding.class.php` — он хорошо
иллюстрирует, что происходит по завершении стройки:

1. Читает параметры завершившегося события (`getEventParams()`, унаследовано
   от `EH_Event`).
2. Помечает `DS_AttachedBuilding` активным и снимает флаг
   `UnderConstruction` (`EH_ConstructBuilding.class.php:76-85`).
3. Прибавляет производство/потребление здания к атрибутам соты через
   `ME_AddBuildingAttributesToComb::AddAttributes(...)` (строка 96).
4. Обрабатывает здания со спецэффектами по имени (`buildingName`):
   - `ARCHEOLOGICAL_INSTITUTE`/`RESEARCH_CENTER` — добавляют
     археологов/учёных на соту (если на ней их ещё нет), количество берётся
     из настроек мира (`DE_WorldSettings`) (строки 46-74);
   - `COLONIZATION_CENTER` — создаёт юнит-контейнер типа `COLONIZATION`
     (строки 254-279);
   - `ALLIANCE_CONNECTOR` («посольство») — если игрок состоит в глобальном
     (межмировом) альянсе (`DS_GlobalAlliance::GetUserGlobalAlliance`),
     автоматически вступает в соответствующий **локальный** альянс этого
     мира через `ME_AddUserToAlliance::Add(...)` (строки 282-323) — то есть
     здание физически связывает локальные альянсы с глобальными;
   - `PROTECTIVE_FIELD_GENERATOR` — ставит соте защитное поле на 100%
     (`DE_CombProtectiveField`, строки 327-332).
5. Обновляет рейтинг соты (`DE_Comb::deltaCombScore`) и строительный рейтинг
   игрока (`DE_Ranking::deltaBuildingRanking`), сбрасывает кеш отображения
   соты (`CR_CombView::CreateFromCombID(...)->resetBuildings(...)`, строка
   91), отправляет локализованное системное сообщение
   (`DE_SystemMessages::AddMessageToEventParticipants`) и помечает событие
   как отработанное (`setTrash()`).
6. Если строительство ещё не завершено (частичный рефреш «на будущее»),
   вместо этого отправляется сообщение с процентом готовности.

### 3.2. Бои и атаки

Полный жизненный цикл боя прослеживается по трём слоям (`UA` → `GL`/`ME` →
`EH`), которые удалось разобрать по коду напрямую:

1. **Запуск атаки — `UA_Attack`** (`ds/classes/UA/UA_Attack.class.php`).
   `checkLocalParams()` (строка 53) разбирает координаты цели, ресурс для
   грабежа, скорость и грузит `DE_Comb`/`DE_Army` игрока. `checkAction()`
   (строка 95) целиком делегирует проверку допустимости атаки классу
   `GL_CanAttackComb::canAttack()`. `performAction()` (строка 117) считает
   время полёта (`GL_TravelTime::Count()`), нужные ресурсы на перемещение
   (`GL_FullUnitsMovementCost::CountAttack()`), списывает их
   (`ME_GetUpkeepResourcesFromComb::GetMoovUpkeep()`), создаёт отложенное
   игровое событие типа `MOVE_TO_ATTACK` через
   `ME_CreateArmyAction::CreateAction()` (упаковано в объекты-значения
   `GB_ArmyActionCreationData`/`GB_DatePeriod`/`GB_TravelPath`), и уже потом
   рассылает системные сообщения и обновляет интерфейс клиента.

2. **Проверка допустимости атаки — `GL_CanAttackComb`**
   (`ds/classes/GL/GL_CanAttackComb.class.php:45-154`, метод `canAttack()`)
   — цепочка примерно из 20 отдельных проверок, каждая кидает своё
   именованное `Exception` (текст исключения = ключ системного сообщения
   для игрока): заморозка после «фриза», бан аккаунта (обычный и
   премиум-бан), существование цели, открытость собственных Центральных
   Ворот, наличие гондолы-транспортёра, открытость Кольцевых Ворот при
   межкольцевом перелёте, активные периоды перемирия
   (`DS_Battle::GetCurrentPeacePeriod`), принадлежность к одному альянсу,
   отпускной режим цели, принадлежность к одному комплексу/кольцу, бан
   цели, «военная этика» (запрет атаки намного более слабых по силе армии
   через `POSSIBLE_ATTACK_SCOPE`), защита новичка (newb defend), правила лиг
   (для миров с включёнными лигами) и — отдельным блоком для
   захватываемых колоний — временная защита колонии и «тихий час».

3. **Наступление события — `EH_ArmyMovementToAttack`**
   (`ds/classes/EH/EH_ArmyMovementToAttack.class.php`). Когда время полёта
   истекает (демон `RD_RefreshDemon` инициирует рефреш инстанции — см.
   раздел 4), обработчик события повторно проверяет `GL_CanAttackComb`
   (условия могли измениться за время полёта) и либо стартует бой через
   `ME_StartBattle::Exec()`, либо отменяет вылет (`_endBattle()`).

4. **Старт боя — `ME_StartBattle::Exec()`**
   (`ds/classes/ME/ME_StartBattle.class.php`) создаёт сущность `DE_Battle`
   и «нулевой» раунд `DE_BattleRound` с начальным состоянием войск через
   `ME_AddRoundResults::exec()`.

5. **Раунды боя** дальше обрабатывает `EH_BattleRound` (по коду не
   прочитан подробно — вывод по составу каталогов и именам классов, не по
   тексту метода). Судя по составу `GL/`/`ME/` рядом (`GL_ArmysHit`,
   `ME_ArmysHit`/`ME_ArmysHitNew`, `GL_CountBattleHit`,
   `GL_SeparateDemageBetweenUnitGroups`, `GL_TestEndBattle`), раунд считает
   урон между группами юнитов и проверяет условие окончания боя, повторяясь,
   пока `GL_TestEndBattle` не даст положительный ответ, после чего
   `ME_EndBattle` завершает бой, `GL_BattleXP`/`ME_BattleXPManager` считают
   боевой опыт, а `ME_FormBattleLog`/`DE_BattleReport` формируют отчёт о
   бое. Этот шаг — единственный некритический вывод в документе, отмечен
   явно как не подтверждённый построчным чтением.

6. **Готовый лог боя** доступен игроку через панель `PN_BattleOverview`
   (пара `TI_*`/`TL_*` для рендера), а внешним инструментам/администраторам
   — через экспорт `LG_XMLBattleExport` и просмотр `SU_ViewBattleSystemLogs`.

Отдельные виды перемещения армии (атака/защита/увод/переселение) оформлены
как параллельный набор событий с общим предком `EH_Army`:
`EH_ArmyMovementFrom`, `EH_ArmyMovementTo`, `EH_ArmyMovementToAttack`,
`EH_ArmyMovementToDefend`, `EH_Defend`, `EH_HideArmy`, `EH_MoveArmyToComb`.

### 3.3. Альянсы

- **Сущности**: `DE_Alliance`, `DE_AllianceEmpty` (null-object для
  «отсутствия альянса»), `DE_GlobalAlliance` — межмировое объединение
  локальных альянсов разных миров в одну «глобальную» структуру.
- **Команды игрока**: `UA_CreateAlliance`, `UA_JoinAlliance`,
  `UA_LeaveAlliance`, `UA_ExpelAlliance`, `UA_ChangeAllianceLeader`,
  `UA_ChangeAllianceJoinpassword`, `UA_AlliancePeace`, `UA_PrivatePeace`.
- **Панели**: `PN_AllianceManagement` (прочитан — здание `ALLIANCE_CONNECTOR`
  открывает доступ к этой панели, см. 3.1), `PN_AllianceOverview`,
  `PN_AllianceSettings`, `PN_PeaceAlliance`, `PN_PeacePrivate`.
- **Веб-кабинет для глобальных (межмировых) альянсов**:
  `UC_ControlGlobalAlliance`, `UC_ControlGlobalAllianceControlPanel`,
  `UC_ControlGlobalAllianceMemberPanel`,
  `UC_ControlGlobalAllianceRegistration` — то есть управление
  «надмировым» альянсом вынесено в отдельный веб-интерфейс личного
  кабинета, а не только во Flash-клиент конкретного мира.
- **Связка локального и глобального альянса** конкретно реализована в
  `EH_ConstructBuilding` при постройке `ALLIANCE_CONNECTOR` (см. 3.1) —
  единственное найденное место, где код напрямую мостит
  `DS_GlobalAlliance::GetUserGlobalAlliance()` и локальный
  `DE_Alliance`/`ME_AddUserToAlliance`.
- **Мир/перемирие между альянсами**: `DS_PeaceAlliance`, `MP_AlliancePeace`,
  `MP_CheckPeace` — из UA_Attack (см. 3.2) видно, что при атаке на игрока
  из альянса, с которым заключён «мир», сначала запрашивается подтверждение
  у атакующего, а после подтверждения мир расторгается автоматически с
  рассылкой уведомлений обеим сторонам и возвратом внесённого залога.
- **Удаление**: `DU_DeleteAlliance`.
- **Проверка целостности БД**: `MP_DBAllianceMembers` — судя по имени,
  поиск альянсов с «битыми» членами (аналогично другим
  `MP_DB*WithoutEvent`/`MP_DB*WithDeletedUsers`, см. раздел 3.4).

### 3.4. Ресурсы и экономика

- Ресурсы каждой соты хранятся построчно в таблице `ds_combhasresource`
  (`id_comb`, `id_attribute`, `value`). Низкоуровневый доступ к ним
  реализован **дважды**, почти дословно одинаково — статическими классами
  `GP_CombBase` и `MP_CombBase` (`GetAttributeByID`,
  `GetCombAttributeByTypeAndName`, `SetCombAttributeByID`, …), см. раздел 5.
  Доменная обёртка над этими данными — `DE_CombAttributes`
  (используется практически всеми UA/EH/ME-классами, например
  `EH_ConstructBuilding`, `BG_BuyResources`).
- **Пошаговый пересчёт производства/потребления** — `ME_TurnCalc::Count
  (DE_Comb $comb, $checkDate)` (`ds/classes/ME/ME_TurnCalc.class.php:58`):
  учитывает атрибуты расы (`DE_RaceAttributes`), настройки мира
  (`DE_WorldSettings`) и число «ходов», прошедших с последнего рефреша
  игрока. Рядом — классы последствий нехватки ресурсов:
  `ME_KillArmysForUpkeep`(`2`/`Old`), `ME_DeactivateBuildingsForUpkeep`,
  `ME_KillHumansForUpkeep`, `ME_GetUpkeepResourcesFromComb`,
  `ME_DeltaResourcesOnComb`.
- **Покупка ресурсов за реальные деньги** — внешний гейт
  `BG_BuyResources` (`ds/classes/BG/BG_BuyResources.class.php`, разобран
  полностью): проверяет, разрешена ли такая покупка в конкретном мире
  (`DE_WorldSettings::get('BUY_RESOURCE_FOR_MONEY_ALLOWED')`), лимит уже
  купленного (`DE_UserBoughtResources`), и напрямую прибавляет ресурс через
  `DE_CombAttributes::deltaAttribute('CURRENT_AMOUNT', $res, $count)`
  внутри транзакции (`DS_Common::startTrans()`/`closeTrans('COMMIT')`).
- **Межигровая биржа** (торговля ресурсами/товарами между игроками одного
  мира): сущности `DE_TradeLot`, `DE_TradeArticle`, `DE_TradeType`;
  исполнение сделок — `ME_TradeExecLotBay` (в имени файла опечатка: «Bay»
  вместо «Buy» — так исторически и осталось), `ME_TradeExecLotSell`,
  `ME_TradeExecLotReturn`; панель — `PN_TradePanel` (прочитан) → собирает
  `TI_TradePanel` → рендерится в XML через `TL_TradePanel::Form()`;
  бизнес-правила цен — `GL_TradeCalcBestPrice`,
  `GL_TradeCountMinAndMaxPrices`, `GL_TradeCanByThisLotByThisPrice`,
  `GL_TradeCanSellByThisPriceAndScope`.
- **Внутриигровая валюта для доната** (Золотые/Серебряные кредиты, см.
  [history-and-background.md](history-and-background.md)) обслуживается
  отдельным, полностью самостоятельным «денежным движком» — см. `MU` в
  таблице раздела 2. Ядро паттерна: абстрактный `MU_ExecOperation::exec()`
  (`ds/classes/MU/MU_ExecOperation.class.php:7-25`, «должен быть
  переопределён в потомке») и десятки конкретных операций
  (`MU_ExecOperationEnterMoney`, `MU_ExecOperationExchange`,
  `MU_ExecOperationTradePayment`, `MU_ExecOperationPVPTransfer`,
  `MU_ExecOperationSendInterworldTransfer`/
  `MU_ExecOperationReciveInterworldTransfer` — перевод денег **между
  разными игровыми мирами**, то есть между разными физическими БД). Отдельно
  стоит `MU_InterworldController` (прочитан) — фоновая сверка: для каждого
  известного мира создаёт `MU_InterworldExecTestSend`/`TestRecive` и
  валидирует, что межмировые денежные операции симметричны (нет «зависших»
  переводов).

### 3.5. Пользовательские действия (`UA`) — общий шаблон обработки команд

`UA` — самая частотная точка входа игровой логики: каждая команда, которую
Flash-клиент присылает через XML, обрабатывается ровно одним классом `UA_*`.
Маршрутизация происходит в `UserAction::getRequest()`
(`ds/classes/UserAction.class.php:69-419`, разобран полностью):

1. Клиентский XML декодируется, отфильтровывается повторная отправка по
   `LAST_PROCESSED_KEY`/`LAST_PROCESSED_COMMAND` (защита от двойной отправки
   при, например, нажатии F5) — строки 90-118.
2. Есть отдельный список «прямых ссылок» (`clientDirectLinks`, строки
   148-168) для нескольких специальных тегов (`moneypage`,
   `clickonallyicon`, `artefactactivate`), которые вместо обработки просто
   отдают клиенту URL для перехода (например, на биллинг
   `billing.destinysphere.ru`).
3. Есть отдельный блок «защиты от фейкового премиума» (строки 180-215) —
   список тегов-действий (`sell`, `buy`, `attack`, `createarmy`, …),
   повторный вызов которых без промежуточного `getbuildmenu` считается
   подозрительным для непрофи-аккаунтов.
4. Класс-обработчик для тега определяется через таблицу `ds_useractiontype`
   (`SELECT ... FROM ds_useractiontype WHERE tagname = '...'`, строка
   291-294) — то есть **связка «тег XML → класс `UA_*`» хранится в БД**, а
   не хардкодится в PHP.
5. Найденный класс создаётся (`$action = new $className;`, строка 307,
   загрузка — через `__autoload`, см. раздел 1) и проходит фиксированную
   последовательность методов, определённых в общем предке `UA_Action`
   (`ds/classes/UA/UA_Action.class.php`, `extends GameBase`):
   - `checkGlobalParams()` — общие для всех действий проверки (строка 312);
   - `checkLocalParams()` — разбор и валидация параметров конкретной
     команды из XML (строка 337);
   - `checkAction()` — бизнес-правило «можно ли вообще это действие сейчас
     выполнить» (строка 362), часто целиком делегируется классу из `GL/`;
   - `performAction()` — собственно исполнение (строка 391), обычно
     заканчивается либо немедленным изменением состояния (`ME_*`), либо
     постановкой отложенного события в очередь (`DE_EventQueue` через
     `ME_Create*Action`), которое позже подхватит соответствующий `EH_*`.
   На любом шаге неудача откатывает наработанное состояние интерфейса
   (`refresh`/`combres` из `$_SESSION`) и отправляет игроку локализованную
   ошибку через `ds_error($action->errormsg)`.

Общий предок `UA_Action` (как и `EH_Event`) добавляет в конструктор вызов
`GP_SystemStatistic::AddToArray('USERACTION', 'HAHDLERS_STARTED', ...)` —
собственная лёгкая телеметрия «какие обработчики отрабатывали в этом
запросе» (опечатка `HAHDLERS` в самом коде, не в этом документе).

## 4. Инфраструктурные базовые классы

### `Base` / `GameBase`

`Base.class.php` — корень вообще всех классов старой линии. Даёт каждому
созданному объекту уникальный `objectId` на основе микросекундного времени
(`setObjectId()`/`getmicrotime()`), метод форматирования сообщения об ошибке
инварианта (`invariantError()`, подставляет класс/файл/строку/код в шаблон),
и «аварийный» вывод ошибки клиенту в обход обычного `OutputHandler`
(`outError()` — просто `echo` + `flush()`).

`GameBase.class.php` (`class GameBase extends Base`) добавляет ровно одну
вещь: в конструкторе, если в `$GLOBALS['GC']` лежит объект
`GB_GlobalContainer`, сохраняет ссылку на него в `$this->tools`
(`GameBase.class.php:21-25`). Это делает `GameBase` предком всех
классов, которым нужен доступ к «текущему миру/БД/языку» по старой схеме —
в первую очередь `UA_Action` (→ все `UA_*`) и `EH_Event` (→ все `EH_*`), а
также отдельные классы `GL/` вроде `GL_CanAttackComb`.

### `WorldUtils`

`WorldUtils.class.php` (класс без родителя, подключает `DS_Common` через
`ClassFactory::getInstance("DS_Common")` при первом обращении к файлу,
строки 9-13) — процедурный набор операций **создания игрового контента**,
по собственному комментарию в шапке файла: «создание сотового комплекса,
создание юзера, подготовка соты к заселению, поселение юзера на соту».

- **`createCombComplex($worldid, $ringnum, $complextypeid, $count)`**
  (строка 33) — массово создаёт `$count` новых сотовых комплексов
  (`DS_CombComplex`) на заданном кольце мира, для каждого — 6 сот
  (`DS_Comb`), для каждой соты — ячейки трёх типов: 217 ячеек типа 1,
  28 ячеек типа 2, 217 ячеек типа 3 (`DS_Cell`, строки 138-174) — судя по
  числам, это, вероятно, «городская застройка», «орбитальный пояс» и
  «глубокий космос» одной соты (сам термин в коде не встречается, вывод по
  количеству ячеек и порядку типов — не подтверждён напрямую). Всё
  оборачивается в одну транзакцию (`begin`/`commit`) с отключённым таймаутом
  запроса.
- **`applyTemplate($id_comb)`** (строка 206) — для каждого из 3 типов
  ячеек выбирает случайный `DS_CellTemplate`, раскладывает по нему типы
  грунта (`DS_CellTemplateElement`) и вызывает `setBeginningBuildings()`.
- **`setBeginningBuildings($templateid, $id_comb, $celltype)`** (строка
  762) — ставит стартовые здания шаблона (`DS_AttachedBuilding`), с
  отдельной обработкой кольца колонизации (`RING_COLONIZE`): здание
  `CENTRAL_GATE_WRECK` в шаблоне подменяется на уже работающие
  `CENTRAL_GATE` (строки 823-846) — то есть колонии стартуют с уже открытыми
  воротами, а не с их руинами, как обычные соты. Дальше проставляются флаги
  `DESTROYABLE`/`DEACTIVEABLE`, прибавляется производство/потребление
  здания к соте (кроме колоний) и для здания `SHELTER` заводится начальный
  `STORAGE_FACTOR`.
- **`addNewInstance($uid, $id_world, $id_race, $combname)`** (строка 269) —
  собственно «создание персонажа»: подбирает свободную/зарезервированную
  соту нужной расы на нужном кольце (`GL_GetCombForPlacement::Get()`),
  валидирует имя соты (длина 3–20, только буквы/цифры/`_`, уникальность),
  создаёт `DS_UserInstance` с годовой «защитой новичка»
  (`newb_defend_enddate = now() + 31622400 сек`, строка 400), стартовый
  рейтинг (`ds_ranking`), заселяет игрока на выбранную соту, сбрасывает её
  ландшафт по тому же шаблонному механизму, выдаёт стартовые ресурсы
  (`setCombResource`) и расовые бонусы, применяет бонусы религии (если
  есть, `setReligionBonuses`), выдаёт науки 1-го уровня (`sciencename ~
  '_1$'`), создаёт дефолтную домашнюю армию
  (`ds_unitcontainer.../isDefault = true`), выдаёт стандартные
  `messagescope` (кому игрок может писать) и flash-ключ сессии. В самом
  конце (строки 610-652), уже переключившись на «портальную» БД (`dbId =
  0`), регистрирует новый инстанс в `ds_userinstancecollection` и
  уменьшает счётчик свободных мест мира (`ds_worldcollection.max_users`) —
  то есть синхронизирует список миров в центральной БД со списком реальных
  игроков в БД конкретного мира.
- **`setCombResource`**/**`setReligionBonuses`** (строки 658, 715) —
  вспомогательные функции выдачи стартовых ресурсов и бонусов религии.

### `Validator` (и его более новая замена `VD_*`)

`Validator.class.php` — базовый класс с шаблонным методом `validate()`,
вызываемым прямо из конструктора родителя (паттерн Template Method).
Ошибки локализуются через таблицы `ds_systemmessage`/
`ds_localizedtextlanguage` (`setError()` подгружает готовый текст по имени
сообщения и языку из `DEFAULT_LANGUAGE`, с кешем в `GLOBAL_CACHE`) и
копятся в массиве `$errorMsg`; `isValid()`/`getError()` — публичный
интерфейс проверки результата.

Наследники — по одному на каждое проверяемое поле формы регистрации/входа:

| Класс | Проверяет |
|---|---|
| `ValidateUser` | логин: допустимые символы, длина 3–20, уникальность в `DS_User` |
| `ValidatePassword` | пароль: символы, длина 6–16, совпадение с подтверждением, чёрный список из файла `passwordblacklist`, запрет совпадения с логином |
| `ValidateEmail` | формат e-mail, DNS-проверка домена (`checkdnsrr` на A/MX), длина, совпадение подтверждения, уникальность |
| `ValidateComb` | имя соты: символы, длина 3–20, уникальность |
| `ValidateLogin` | полноценный вход: пароль сверяется по MD5-хэшу в `DS_User`, проверяется `accountactive`/`activate_mail` (с частным случаем для `partner=139` в куке) |
| `ValidateAdminLogin` | то же, что `ValidateLogin`, но требует `admin = true` |
| `ValidateUiid`, `ValidateWorldName`, `ValidateLang`, `ValidateCheckBox1`, `ValidateGameModif` | прочие точечные проверки (существование инстанса, имя мира, код языка, чекбокс согласия, числовые игровые модификаторы) |

Каждый наследник побочным эффектом пишет очищенное значение обратно в
`$_SESSION` — то есть класс одновременно и валидирует, и является частью
пайплайна многошаговой регистрации через сессию.

Более новая, независимая от сессии замена — статические классы `VD_*`
(`VD_Base`, `VD_UserName`, `VD_Password`, `VD_Email`, `VD_CombName`,
`VD_Alliance`/`VD_AllianceName`/`VD_AlliancePassword`/`VD_AllianceTag`,
`VD_User`, `VD_UserInstance`, `VD_Avatar`) с единым интерфейсом
`Validate(...)`/`FetchErrorCode()` (`ds/classes/VD/VD_Base.class.php`,
`VD_UserName.class.php`) без записи в `$_SESSION`. Судя по местам вызова
(`BG_UserData`, `UC_ExecLogin` используют `VD_User::Validate(...)`), это
переписанная под гейты `BG_`/`UC_` версия того же функционала —
`Validator`/`Validate*` при этом из кода не убраны и продолжают
использоваться в основном (Flash-related) сценарии регистрации.

### `Logger`

Простейший файловый логгер: открывает файл в режиме дозаписи, пишет одну
строку `"дата ::: REMOTE_ADDR ::: пользователь ::: текст"`, закрывает файл
— и всё это заново на каждый вызов `log()` (не держит хендл открытым между
вызовами). Показательно, что во всей кодовой базе он не единственный:
рядом существуют независимые друг от друга `SU_DebugTools::WriteToLog`,
`GP_ProtocolLog`, `ArtTradeLogger.class.php` (лог биржевых сделок с
артефактами, отдельная таблица `ds_tradeartloger`), `BS_Logger`,
`GK_Logger` — единого логирования на весь проект нет, каждая подсистема (или
эпоха разработки) обзавелась своим.

### `SyncTables`

Разовый инструмент массового копирования ~90 «справочных» таблиц (здания,
юниты, науки, атрибуты, системные сообщения и т.д. — полный список
перечислен прямо в коде, `ds/classes/SyncTables.class.php:28-151`) с
«мастер»-БД на «слейв»-БД через `pg_copy_to`/`pg_copy_from` в транзакции;
таблицы, которые не удалось скопировать с первого раза, перемешиваются
(`shuffle`) и повторяются на следующей итерации, пока все не будут
скопированы. Судя по всему, это инструмент первичного наполнения новой
(только что созданной) БД мира общими справочными данными. Учётные данные
подключения (хост/база/пользователь/пароль) прописаны прямо в классе
(`$settings`, строки 17-20) — в загруженной версии кода пароль слейва
заменён на плейсхолдер согласно требованиям задания. Похоже, что в проде
эту разовую операцию со временем вытеснила постоянно работающая
триггерная репликация (см. `SU_ReplicationControl` ниже) — она
реплицирует не только начальное состояние, но и дальнейшие изменения
справочников.

### `SyncUsers`

Ещё один разовый инструмент, но другого назначения: построчная синхронизация
двух конкретных «исторических» таблиц (`ds_user_history`,
`ds_userprofidata_history`) из «мастер»-БД в БД каждого известного мира
(перечисленных в `$GLOBALS['dbWorldAccess']`), с ручным разбором
insert/update/delete по флагу `isdeleted` и построением SQL вручную
(`QueryHelper::insert()`/самодельный `UPDATE ... SET ...`). Класс нигде не
инстанцируется автоматически — в шапке файла закомментированный пример
ручного запуска (`//$syncUsers = new SyncUsers(); //$syncUsers->Start();`),
что указывает на разовый инструмент миграции/сведения данных (например,
после объединения аккаунтов или переноса профильных данных из другой
системы), а не на постоянно работающую инфраструктуру.

### `building_class` / `buildings` — историческая находка

Это единственное место во всём `ds/classes`, где явно видны немецкие корни
проекта:

- **`building_class.class.php`** определяет старый процедурный класс
  `building` (свойства `b_id_building`, `b_name`, `b_localizedname`,
  `b_kadcosts`, `b_nanocosts`, `b_stasiscosts`, `b_prodcosts`,
  `b_foodcosts`, `b_workercosts`, `b_production`, `b_upkeep` и т.д.) с
  методами, которые прямо печатают HTML построчно (`layoutBuilding()`,
  `printbuilding()`). Комментарии и печатаемый текст — **на немецком**:
  `"Benötigte Forschungen"` (нужные исследования), `"Benötigte Gebäude"`
  (нужные здания), `"Bauzeit"` (время постройки), `"Verbrauch"`
  (потребление) — `building_class.class.php:251-253`. Это прямое
  текстовое подтверждение немецкого происхождения движка (компания
  **DestinySphere GmbH**, см. раздел 1 и
  [history-and-background.md](history-and-background.md)).
- **`buildings.class.php`** использует `building_class` (`import
  ('building_class')`, строка 5) и определяет класс `neededAttributes`,
  который вручную SQL-запросами (в стиле, характерном для запросов,
  сгенерированных когда-то мастером запросов MS Access — множественные
  вложенные скобки `((((a INNER JOIN b ON ...) INNER JOIN c ON ...))))`)
  вытаскивает для заданной расы/языка все здания, нужные для них науки,
  требуемые здания и типы грунта, раскладывает атрибуты по `id_attribute`
  через захардкоженный `switch` (1 = стоимость кадерия, 2 = стоимость нано,
  …, 9 = производство кадерия, 49 = потребление еды — `fillBuilding()`,
  строки 185-206) и печатает HTML-таблицу, подключая либо немецкую
  (`req_header_de.php`), либо русскую (`req_header_ru.php`) шапку по языку
  (строки 207-216). Важная деталь: в самом конце файла, **безусловно**, при
  простом подключении файла создаётся объект и тут же исполняется вся
  логика (`$r = new neededAttributes;`, строка 222) — то есть это не
  переиспользуемый класс, а старая HTML-страница-справочник по зданиям,
  оформленная как `.class.php` по инерции старого стиля именования файлов.

Оба файла выглядят как рудимент раннего этапа проекта (задолго до
`ClassFactory`/`UA`/`DE`-архитектуры): ни один из прочитанных в этом
исследовании файлов их не подключает, актуальная игровая логика зданий
работает через `DE_Building`/`DS_Building` (раздел 3.1).

### Прочая инфраструктура, обнаруженная попутно

Формально не входит в явный список задания, но необходима для понимания
того, «как всё это работает целиком»:

- **`ds/include/Import.inc.php`** — реализация `import()`/`importPack()`/
  `__autoload()`, на которую опирается `ClassFactory` (раздел 1).
- **`domXML.class.php`** — совместимостный шим PHP4→PHP5: заново
  реализует убранные из PHP5 процедурные функции `domxml_open_mem()`/
  `domxml_new_xmldoc()` поверх современного `DOMDocument`. Практически весь
  разбор входящего XML от Flash-клиента (`UserAction::getRequest()` и
  большинство `UA_*`) до сих пор написан на этом старом процедурном API —
  без этого шима движок бы не запустился на современном PHP5+.
- **`GB_SingletonObjectStorage`** (`ds/classes/GB/GB_SingletonObjectStorage.class.php`)
  — Identity Map в пределах одного HTTP-запроса: `GetSingleton($className,
  $id)` гарантирует, что для одной и той же пары (класс, ID) в рамках
  запроса всегда возвращается один и тот же PHP-объект. Используется
  практически во всех фабричных методах `DE_*::CreateFromID()`/
  `CreateFromCombID()`/`CreateFromUserInstanceID()` и т.д. Дополнительно
  ведёт «кладбище» удалённых объектов (`$odjectsForAdditionalDeleteProcessing`),
  чтобы прочие части того же запроса могли узнать, что объект уже удалён.
- **`GB_TypeFactory`/`GB_DataType`** — обобщённая система типизированных
  «значений» игры по паттерну Flyweight с тремя режимами создания (`BASE`,
  `MASTER`, `CONSTANT` — последний кешируется и переиспользуется по значению,
  `GB_TypeFactory.class.php:24-37`). На ней построены `GB_Date`,
  `GB_Integer`, `GB_TimePeriod`, `GB_MoveSpeed` и другие типы — вместо
  «голых» скаляров игровая логика почти everywhere оперирует такими
  типизированными объектами-обёртками.
- **`SU_ReplicationControl`** (`ds/classes/SU/SU_ReplicationControl.class.php:4-13`,
  комментарий в коде: «*Самопальная система репликации*») — постоянно
  работающий механизм: на таблицах мастер-БД триггерами логируются
  изменения (таблица/поля/значения/действие I·U·D/ID слейва), затем пачками
  переносятся на подчинённые БД миров. Это, по-видимому, «боевая» замена
  разового `SyncTables` для текущих изменений (а не только начального
  наполнения).
- **`SI_ControlStoreUserInstance`/`SI_FinalizeWorldTransfer`** — механизм
  переноса игрока между мирами: сохранение (`_storeEvents`,
  `_storeReligion`, `_storeScience`, `_storeBattleXP`, `_storeCombs`,
  `_storeUserBonuses`, `_storeArmyWeight`, …) в промежуточные таблицы,
  восстановление на другом конце, и `Finalize()` — заморозка/удаление
  исходного аккаунта после успешного переезда.
- **Точка входа для Flash-клиента — `useraction.php`** (в корне `ds/`, не в
  `ds/classes/`, но связывает всё описанное выше в один запрос): бутстрап
  через `Main.php`, адаптация CLI-режима
  (`RD_AdeptUserActionToConsole::Exec()`), продолжение/проверка сессии
  (`SU_GameSessionWork::SessionContinue()`), проверка «мир не на
  техобслуживании» (`GameDown()`), и уже затем —
  `UserAction::getRequest()` (раздел 3.5).
- **DS_Common и опечатка `CreateForCurentDB`** — забавная и показательная
  деталь стиля кода: в `DS_Common.class.php` есть **два** метода —
  `CreateForCurrentDB()` (строка 98, правильное написание) и
  `CreateForCurentDB()` (строка 133, с опечаткой — пропущена буква «r»),
  причём второй явно помечен в самом коде как алиас первого:
  ```php
  /**
   * Синоним предыдущего метода, нужен для смвместимости,
   * имеет неправльное название, если встретите где-то в тексте
   * программы, смело исправляйте!
   * @deprecated
   */
  public static function CreateForCurentDB($DBID = null) {
     return self::CreateForCurrentDB($DBID);
  }
  ```
  По факту опечатанная версия используется чаще правильной (297 обращений
  `CreateForCurentDB` против 48 `CreateForCurrentDB` по всему
  `ds/classes/`) — комментарий разработчика («смело исправляйте!») за годы
  так и не воплотился в жизнь. Хороший маркер общего технического долга
  кодовой базы.

## 5. Открытые вопросы / что не удалось однозначно определить

- **Точная расшифровка ряда префиксов не установлена.** Уверенно
  расшифрованы по прямым текстовым подсказкам в коде: `BG` (гейт биллинга,
  комментарий «Класс гейта…»), `CR` (Controller, комментарий «Класс
  контроллера»), `EH` (Event Handler, метод `handleEvent`), `UA`
  (User Action — так называется и родительский `UserAction.class.php`),
  `UC` (User Cabinet, комментарий «личный кабинет»), `GK` (Gate Key, имя
  класса `GK_GateKeyController`), `SMS`, `TN` (Turnir), `DU` (Delete User).
  Остальные — рабочая гипотеза по составу и логике файлов, не подтверждённая
  прямой текстовой подсказкой: `DC`, `DE`, `DS`, `GB`, `GE`, `GH`, `GL`,
  `GP`/`MP`, `LG`, `MC`, `ME`, `MU`, `PN`, `PU`, `RD`, `SI`, `ST`, `SU`,
  `TI`/`TL`, `VD`.
- **Дублирование `GP`/`MP`.** Оба содержат практически идентичный код
  (`GP_CombBase`/`MP_CombBase` различаются буквально одним конструктором и
  одним дополнительным методом). Что именно разделяет эти две ветки —
  разные версии игры (Classic/Unlimited/Pulsar), разное время создания
  (одна ветка форкнута от другой и с тех пор живёт параллельно), или
  что-то ещё — по коду `ds/classes` однозначно не установлено. У `MP`
  дополнительно есть набор скриптов проверки целостности БД
  (`MP_DBEventsWithDeletedUsers`, `MP_DBNotEndedBattlesWithoutEvent`,
  `MP_DBBadBuildingEvents` и т.п.), которых в `GP` нет — возможно, `MP`
  просто более новая и более развитая версия того же слоя, а `GP` оставлен
  для миров, которые до этой ревизии не обновили.
- **Дублирование каталога `Core`.** `ds/classes/Core/` и
  `ds/classes/BS/Core/` содержат файлы с одинаковыми именами
  (`ObjectBase.class.php`, `Trace.class.php` и т.д.), но **разным
  содержимым** (проверено через `diff`). Причина раздвоения — не
  установлена (похоже на оставленную в дереве ветку рефакторинга).
- **`LG_XMLBattleExport.class.php` физически лежит в каталоге `GL/`**, а не
  `LG/`, при этом называется с префиксом `LG_`. Поскольку `import()`
  вычисляет путь к файлу как `CLASS_PATH/<префикс из имени класса>/<имя
  класса>.class.php` (раздел 1), автозагрузка класса `LG_XMLBattleExport`
  по этому правилу искала бы файл в `LG/`, а не в `GL/`, где он на самом
  деле лежит — то есть **обычный `__autoload` этот класс не найдёт**. Не
  проверено, подключается ли он где-то напрямую через `require`/`include`
  в обход автозагрузки — если нет, это, вероятно, мёртвый/неработающий код,
  оставшийся после переименования каталога или самого класса.
- **`ME_TradeExecLotBay.class.php`** — похоже на многолетнюю опечатку
  «Bay» вместо «Buy» (соседние классы называются `ME_TradeExecLotSell`,
  `ME_TradeExecLotReturn`, то есть по смыслу нужен `...LotBuy`). Файла с
  исправленным названием в дереве нет, значит опечатка «канонизировалась».
- **Расхождение с заявленным числом файлов.** В задании фигурирует цифра
  1223 файла; собственный подсчёт (`find ds/classes -type f`) даёт 1227,
  из них 1220 с расширением `.class.php`. Разница на пару-тройку файлов
  не проверялась отдельно — вероятно, дело в паре пограничных файлов вроде
  `detect_player.php` (это не класс, а старый скрипт-редирект,
  использующий встроенное определение браузера и `SU_GameSessionWork`) и
  `domXML.class.php` (класс, но фактически полифилл, а не игровая логика),
  которые можно по-разному засчитывать при подсчёте «файлов игровой
  логики».
- **Раунды боя и точная боевая формула не разобраны построчно** — описание
  в разделе 3.2 после старта боя (`ME_StartBattle`) опирается на состав
  каталогов `GL/`/`ME/` и общую архитектуру событий, а не на прочитанный
  код `EH_BattleRound`. Для точного описания формулы урона нужен отдельный,
  более глубокий проход по `GL_ArmysHit`, `ME_ArmysHit`/`ME_ArmysHitNew`,
  `GL_CountBattleHit`, `GL_SeparateDemageBetweenUnitGroups`,
  `GL_TestEndBattle`, `ME_EndBattle`.
- **Соотношение `TN` (турниры) с версией Pulsar**, упомянутой в
  [history-and-background.md](history-and-background.md) как отдельный,
  плохо задокументированный проект — не установлено. `TN_TurnirController`
  хардкодит конкретные `turnirDbID = 3`/`turnirWorldID = 4` и хост
  `turnir.destinysphere.ru` — похоже на отдельную функцию основной
  кодовой базы (Classic/Unlimited), а не синоним Pulsar, но однозначного
  подтверждения в `ds/classes` для этого нет.
- **Значение отдельных PvP-механик лиг** (`LEAGUE_ENABLED`,
  `LEAGUE_CAN_ATTACK_*_LEAGUE`, встреченные в `GL_CanAttackComb`/`UA_Attack`)
  не сопоставлено с игровым лором из
  [history-and-background.md](history-and-background.md) (расы, типы
  миров РпР/ВпВ) — не исключено, что это отдельная, введённая позже
  игровая механика («лиги» силы игрока), не совпадающая с делением на расы
  или типы PvP.
- **`GK` (обмен ключами)** использует собственный `GK_DBInterface`,
  полностью независимый от `DS_Common`/`dbWorldAccess` — не установлено,
  для обмена данными между какими именно системами он предназначен
  (кандидаты по косвенным признакам: между `destinysphere.ru` и
  `ds-game.ru`/`ds-game.su`, либо между игрой и внешним статистическим
  «анализатором», либо ещё какой-то третьей системой) — прямого указания
  в прочитанных файлах `GK/` нет.
