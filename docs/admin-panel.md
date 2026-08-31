# Админ-панель Destiny Sphere / «Сфера Судьбы»

Документ описывает административную веб-панель GM/саппорта игры, расположенную в
`old-sphere/Admin`. Это отдельное PHP-приложение (~8.4 МБ, HTML в разметке 2002-2003 гг.,
вёрстка таблицами), которое управляет игровыми мирами, аккаунтами игроков, премиум/донат-
подпиской, SMS-платежами, контентом портала и внутренней CMS. Работает поверх той же
кодовой базы, что и сам игровой движок (`old-sphere/ds`) — многие классы (`DS_Common`,
`GameBase`, `WorldUtils`) используются и там, и там.

Все пароли и секреты в исходниках заменены на `REDACTED_DB_PASSWORD` / `REDACTED_API_SECRET`
перед публикацией — в документе они не восстанавливаются и не обсуждаются.

## 1. Общая архитектура

### 1.1. Точки входа

- **`Admin/login.php`** — форма логина/логаута. Обрабатывает `action=login` (сверяет
  `login`/`md5(password)` через `UserManager::Login()` по MySQL-таблице `Users` из
  «административной» БД) и `action=logout`. При успехе регистрирует сессию через
  `sys/lib/session.class.php` и делает редирект на `/Admin/`.
- **`Admin/index.php`** — не содержит собственного UI: создаёт `AdminMenu`, берёт
  `getFirstChilde()` (первый доступный текущему пользователю пункт меню) и делает
  `headerLocation()` на него. Т.е. корень админки всегда «перекидывает» на первый
  разрешённый раздел.
- **Уровень Apache**: `Admin/.htaccess` включает `AuthType Basic` (общий HTTP-пароль на
  весь `/Admin`, `AuthUserFile /etc/apache2/passwd`) — это первый, сетевой контур защиты,
  до какого-либо PHP-кода. Второй контур — само приложение (логин/пароль в `Users`).
- **`Admin/edit.php`** — единая точка входа в редакторы CMS-контента разных типов
  (`mode=image|text|banner|DynComponent`), которая просто редиректит в
  соответствующий редактор.
- **`Admin/help.php`** — набор статичных текстов подсказок для GM, выбираемых по
  `?action=<ИмяРаздела>` (например, `Messages`, `PremiumLog`, `Mergeaccounts`,
  `TradeBan`, `DeleteUser`, `Menu`, `Connectionslist`…). Подключается ссылкой «HELP» из
  многих модулей.
- **`Admin/zz.php`** — служебный мусорный файл (`<?var_dump($_SERVER)?>`), не относится
  к функциональности, похоже на забытый отладочный скрипт.

### 1.2. Авторизация и права доступа

Двухуровневая модель, реализованная в `sys/lib/UserManager.php` + `sys/lib/AdminMenu*.php`
(общие для сайта библиотеки, не только для `/Admin`):

- Таблица `Users` (MySQL) хранит GM/админов: `Login`, `Password` (MD5), ФИО
  (`LastName`/`FirstName`/`SecondName`), флаг `Active`, флаг `isSuperAdmin` и, судя по
  `UserManager::GetAllowedDB()` (поле `dbAccess`), — список игровых миров (БД), к которым
  у конкретного админа есть доступ. Управляется в разделе **Admins** (см. ниже).
- Таблица `AdminMenu` (MySQL) — дерево пунктов меню на 3 уровня (`Parent_ID`, `Title`,
  `Url`, `Active`, `ShowOrder`). Каждый пункт 2-го/3-го уровня соответствует физической
  папке в `Admin/controls/...`, `Url` должен совпадать с именем папки.
- Таблица `Users_AdminMenu` — связь «пользователь → доступные пункты меню 2/3 уровня».
  Для не-суперадминов `AdminMenuDB` (`sys/lib/AdminMenuDB.php`) строит меню только из
  пунктов, явно выданных в этой таблице; суперадмины видят всё дерево `AdminMenu`
  целиком.
- `Admin/include/security.php` подключается в начале практически каждого файла
  `index.php` внутри `controls/*` (кроме файлов-«заглушек», см. ниже). Он: поднимает
  сессию, проверяет `UserManager::CheckUser()`, и для не-суперадминов вызывает
  `AdminMenu::chekMenu()` — если текущий URL не входит в разрешённое дерево меню
  (`menuFlat`), сессия обнуляется и происходит редирект на `login.php`. Есть обходной
  флаг `$GLOBALS['easyadmin']` («easyadmin»), отключающий эту проверку — используется,
  например, в `Portal/PageCreation/Pages/easyadmin.php`.
- Аудит: по коду рассыпаны вызовы `actionLog(...)` (логируются бан/анбан, смена премиум-
  дней, создание мира, merge-аккаунтов и т.д.). Просмотр — раздел **Admins → Statistic**.

### 1.3. Механизм «модулей» (`controls/*`)

Роутинга в привычном смысле нет — **путь URL и есть маршрут**: каждая папка внутри
`Admin/controls/<Модуль>/...` является отдельной «страницей» со своим `index.php`.
Иерархия папок 1:1 отражает дерево `AdminMenu`:

- Папки верхнего уровня (`Admin/controls/Admins`, `.../GameWorlds` и т.д.) обычно не
  содержат собственной страницы, а лишь файл-пересылку:
  ```php
  <?php
  require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/MenuThrow.php";
  ?>
  ```
  `Admin/include/MenuThrow.php` подключает `security.php`, строит `AdminMenu` и
  редиректит на первый доступный дочерний пункт — то есть клик по разделу верхнего
  уровня просто открывает первый его подраздел.
- Реальная логика лежит в листовых папках (`.../GameWorlds/Create/World/index.php`,
  `.../Players/EditUser/UserBan/index.php` и т.п.) — обычный процедурный/слабо-ООП PHP,
  без общего фреймворка контроллеров. Типовой шаблон файла:
  `security.php` → `set_work_category.php` → `admintop.php` (шапка/меню) → бизнес-логика
  класса → `adminbottom.php` (подвал).
- Общий код лежит в `Admin/include/`:
  - **`classfactory.php`** — примитивная фабрика классов из `Admin/include/*.php` по
    имени; отдельная (похоже, нерабочая/устаревшая) ветка для `getInstance("DB", ...)`,
    использующая PEAR DB и `pgsql://` DSN — содержит отладочные `var_dump`/`die()` и
    жёстко зашитый (в исходнике, до редактирования) хост/пользователя одного мира;
    реальный доступ к БД миров идёт не через неё, а через `DS_Common`.
  - **`DS_Common.php`** (расширяет `GameBase.class.php`) — центральный класс доступа к
    PostgreSQL-базам игровых миров: держит массив подключений `dbWorldAccess` (ключ —
    числовой `dbId`), умеет переключаться между мирами (`setDbId()`), гонять один и тот
    же SQL по всем мирам сразу (`getMultyArray()`/`getMultySql()`), кэшировать через
    Zend/output-cache. Этот же класс используется и в игровом движке `ds/`.
  - **`WorldUtils.php`** — тяжёлая логика «инициализации мира»: создание секторов/колец/
    ячеек (`createCombComplex`), выдача игроку стартовой базы (`addNewInstance`),
    применение шаблонов застройки. Используется при создании миров/ручном выделении баз.
  - **`settings.php`** — место, где в боевом окружении определялся
    `$GLOBALS['dbWorldAccess']` (см. §4); в этом репозитории массивы закомментированы,
    остался только `require_once .../ds/Main.php`.
  - **`security.php`, `set_work_category.php`, `admintop.php` / `adminleft.php` /
    `adminbottom.php`** — общая обвязка (авторизация, верхнее меню, футер).
  - **`class/Forms.class.php`** — хелпер для генерации HTML-форм; **`class/anyDB/`** —
    сторонняя (кажется, невостребованная) абстракция БД; **`class/DropInstance.class.php`,
    `DropUserComplete.class.php`, `DropUserDead.class.php`, `DropUserInstance.class.php`,
    `delete_dead_users.php`** — обслуживающие скрипты для удаления «мёртвых»
    пользователей/инстансов, используются разделом **Players → Delete**.
- CMS-разделы (см. ниже) в основном построены на общем `sys/lib/simpleedit.php` —
  генератор CRUD-таблиц (список/добавление/редактирование/удаление записей MySQL) по
  декларативному описанию полей `$Fields`/`$Table`/`$Options`; это же используется в
  `News/*`, `CMS/*`, части `Portal/*`, `Admins/Admins`, `Admins/SuperAdmins`.

## 2. Разделы (`Admin/controls/*`)

### 2.1. Admins — управление админами и мета-администрирование панели

- `Admin/controls/Admins/Admins/index.php` — CRUD-список GM-аккаунтов (Login/Password/
  ФИО), `rules.php` — вероятно, права/роли.
- `Admin/controls/Admins/SuperAdmins/index.php` — отдельный CRUD для супер-админов.
- `Admin/controls/Admins/Menu/index.php` — редактор дерева `AdminMenu` (тот самый источник
  RBAC из §1.2): здесь создаются/переименовываются разделы и настраивается их `Url`.
- `Admin/controls/Admins/Statistic/{Activity,ConnectionJournal,EventsList,UnionJournal}` —
  журналы: `ConnectionJournal` — сессии входа админов в панель (таблица `Sessions`,
  время/длительность); остальные — журналы событий/активности (вероятно, потребители
  вызовов `actionLog()`).
- `Admin/controls/Admins/BugTrace/{index.php,Import,View}` — просмотрщик присланных
  трейсов ошибок игры (`Import` — загрузка файла трейса, `View` — вывод содержимого;
  в репозитории лежит и пример `View/111.txt`).

Назначение: кто имеет доступ к панели, что именно ему разрешено, и аудит их действий.

### 2.2. CMS — контент и локализация портала

- `Admin/controls/CMS/Templates/index.php` (+`edittemplate.php`, `template_type.php`,
  `sentence.php`) — редактор шаблонов страниц портала.
- `Admin/controls/CMS/Localization/index.php` (+`dictionary.php`, `wordlist.php`) —
  словарь фраз/переводов интерфейса (то, что подставляется функцией `local('КЛЮЧ')`,
  используемой практически во всей панели).
- `Admin/controls/CMS/DynComponents/index.php` — редактор переиспользуемых «динамических
  компонентов» (виджетов, встраиваемых в страницы).
- `Admin/controls/CMS/Models/index.php` — редактор неких контентных «моделей» (детали не
  изучались, предположительно ещё один тип переиспользуемого CMS-контента).

Назначение: обслуживающий CMS-движок портала (шаблоны/переводы/виджеты), которым
пользуются другие разделы (`News`, `Portal`) — не игровая логика, а сайтовая.

### 2.3. GameSettings — баланс и экономика игры (кросс-мировые настройки)

Самый крупный по числу файлов раздел; в основном общие для всех миров игровые
настройки и инструменты:

- `Admin/controls/GameSettings/TradeAdmin/*` — администрирование торговли: цены
  (`ArtefactPrice`, `ResourcePrice`, `SciencePrice`, `UnitPrice`,
  `UnitReincarnatePrice`), типы сделок (`TradeTypes`), пересчёт курсов
  (`TradeCourseRecount`), логи и статистика сделок (`TradeLogs`, `TradeStatistic`),
  бан конкретного игрока в торговле (`TradeBan`), ручной ввод/вывод денег
  (`EnterMoney`, `DrawoutMoney`) и лотерея ресурсов (`ResourceLottery`).
- `Admin/controls/GameSettings/GameModifaers/*` — параметры-множители баланса мира
  (`ds_gamemodifier`), те же, что задаются при создании мира.
- `Admin/controls/GameSettings/CreateArtefact/index.php` и
  `Admin/controls/GameSettings/PaidArtefacts/index.php` — создание артефактов и
  отдельно управление **платными** (донатными) артефактами — прямая связь с монетизацией.
- `Admin/controls/GameSettings/PaymentPrice/index.php` — тарифы (по всей видимости,
  цены премиум-услуг).
- `Admin/controls/GameSettings/Ranking/{Artefact,Building,Leagues,Levels,Science}` —
  настройка рейтингов/лиг.
- `Admin/controls/GameSettings/SQL/index.php` — **консоль произвольных SQL-запросов**
  напрямую к выбранной PostgreSQL-базе мира (`$GLOBALS['dbWorldAccess']`), без видимых
  дополнительных подтверждений — потенциально самый опасный инструмент панели.
- `Admin/controls/GameSettings/CloseWarAndTrade/index.php` — плановые окна отключения
  войны/торговли (есть почти идентичный дубль на уровне конкретного мира,
  `GameWorlds/CloseWarAndTrade`, — похоже на скопированный, а не переиспользованный код).
- Прочее: `Localization/*` (внутриигровые тексты, включая старую подсистему `ifloc` со
  своими Smarty-шаблонами и импорт/экспорт CSV), `Referral/Sites` (партнёрские сайты
  реферальной программы), `Poll`, `Questions`, `Victorina` (викторина), `UnitVsUnit`
  (справочник «юнит против юнита»), `CompensationCalculator` (расчёт компенсаций),
  `GameHelp`, `GameAttributes`.

### 2.4. GameWorlds — управление игровыми мирами (акцент задания)

Каждый «мир» — отдельная PostgreSQL-БД (см. §4), выбираемая по числовому `dbId`. Это
операционная консоль GM по конкретным серверам-мирам.

- **Создание мира**: `Admin/controls/GameWorlds/Create/World/index.php` (класс
  `NewWorld`) — выбор целевого Postgres-сервера (`dbId`/`DbServerId`), языка, типа мира,
  стартовых игровых модификаторов и `world_identifier`; пишет одновременно в
  `ds_world`/`ds_worldhaslanguage`/`ds_worldhastype` конкретной базы мира и в
  общий реестр `ds_worldcollection` на мастер-БД (`dbId = 0`), плюс сидирует кольца
  (`ds_ringtemplate` → `ds_ring`).
- **Открытие/закрытие мира**: `Admin/controls/GameWorlds/Edit/index.php` (класс
  `EditWorld`) — редактирование названия/описания/типа/**`world_status`** мира,
  синхронно в БД самого мира и в реестр `ds_worldcollection`. По коду игрового движка
  (`ds/classes/GE/GE_LogicWorldsList.class.php`) встречаются статусы `CLOSE`,
  `REGISTRATION_CLOSE`, `TURNIR_STARTED` (значение по умолчанию при создании — `4`,
  остальные статусы явно не установлены — «предположительно» есть ещё
  открытый/активный статус). Это и есть механизм открытия/закрытия мира для новых
  регистраций.
- `Admin/controls/GameWorlds/CloseWarAndTrade/` — плановые окна отключения войны/торговли
  для конкретного мира (Smarty-шаблоны, класс `PeriodsHandler`).
- `Admin/controls/GameWorlds/Turnir/*` — полный жизненный цикл турнирных
  («ускоренных») миров: `StartTurnir`, `StopTurnir`, `PauseTurnir`, `RegUserInTurnir`,
  `CreateCombTemplate`, `CreateBuildingOnAllComb`, `ClearWorld`, `UpdateRanking`,
  `UpdateWordInfo`, `ProlongPremium` (продление премиума участникам турнира).
- `Admin/controls/GameWorlds/RollBackBattles/index.php` — массовый откат результатов
  боёв мира за диапазон дат (`SU_ViewBattleSystemLogs::returnBattleById`) — инструмент
  на случай багов/эксплойтов боевой системы.
- `Admin/controls/GameWorlds/Transfer/{WorldTransfer,DoubleCombs,Clear}` — перенос баз
  игроков между мирами (слияние/миграция миров) и поиск/чистка дублирующихся баз;
  есть также независимый `GameWorlds/WorldTransfer/index.php` верхнего уровня —
  похоже на устаревший/параллельный вариант той же функции.
- `Admin/controls/GameWorlds/SendMessages/index.php` — массовая рассылка внутриигровой
  почты по игрокам мира (с фильтром по расе).
- `Admin/controls/GameWorlds/Chat/index.php` — модерация чата (управление списком
  допущенных/забаненных в чате пользователей).
- `Admin/controls/GameWorlds/DisableBuilding/{DisableBuilding,DisableBuildingOnColony}`
  и `Admin/controls/GameWorlds/DisableScience/` — экстренное отключение конкретных
  типов построек/исследований в масштабе мира (рычаг баланса/аварийный фикс).
- `Admin/controls/GameWorlds/NPC/`, `.../Quest/`, `.../ProtectiveField/`,
  `.../EndEvents/` — управление NPC-базами, квестами, «защитным полем» баз и
  сюжетными эвент-таймерами мира.
- `Admin/controls/GameWorlds/ShowBattleReports/` и
  `.../ShowBattleReportsWithoutEdit/` — просмотр боевых отчётов (редактируемый и
  read-only варианты).
- `Admin/controls/GameWorlds/upkeepdeath/` — обработка «смерти» построек/юнитов по
  содержанию (upkeep).
- `Admin/controls/GameWorlds/Add/{Artefacts,Arts,EndEvents,Resourses,Science,Units}` —
  добавление новых определений артефактов/ресурсов/наук/юнитов в конкретный мир.
- `Admin/controls/GameWorlds/AddUserAllowed/` — выдача конкретному игроку доступа к
  этому миру (мирозависимый вариант общей функции из `Players`).
- `Admin/controls/GameWorlds/DeleteLog/`, `.../DeleteSpyLog/` — очистка логов/логов
  разведки мира.
- `Admin/controls/GameWorlds/Clear/` — «мёртвая» папка: вместо кода в ней остались
  только служебные файлы CVS (`Entries`, `Repository`, `Root`) — рудимент старой
  системы контроля версий, не функциональность панели.

Это самый операционно-мощный раздел: именно здесь GM открывают/закрывают миры,
управляют турнирами, откатывают бои, отключают сломанный контент и рассылают
уведомления игрокам.

### 2.5. News — новости портала

Тонкие CRUD-обёртки (`sys/lib/simpleedit.php`) над одной общей MySQL-таблицей
`NewsCompany`, различаемые полем `Placer`:

- `Admin/controls/News/NewsSphere` (`Placer='newssphere'`) — основная лента новостей
  портала.
- `Admin/controls/News/NewsWeek`, `.../NewsLast` — «новости недели» / «последние
  новости» (виджеты).
- `Admin/controls/News/NewsCategory` — категории новостей.
- `Admin/controls/News/BillingNews` — новости, показываемые на биллинговых/платёжных
  страницах (например, промо премиума) — примыкает к монетизации.
- `Admin/controls/News/TournamentNewsNew` — новости/анонсы турниров.

### 2.6. Players — управление игроками (акцент задания)

Самый насыщенный «саппортовый» раздел, CRM-подобный набор инструментов по аккаунтам и
персонажам:

- **Поиск мультиаккаунтов**: `Admin/controls/Players/SearchMultyAccaunts/index.php` —
  ищет пересекающиеся IP/прокси/куки в `ds_loginhistory` (с настраиваемым порогом
  «минимум пересечений»), подсвечивает премиум-игроков, и **обращается к внешнему
  биллинговому сервису** `https://billing.destinysphere.ru/gate/payers.php` за суммой
  платежей по каждому найденному пользователю — прямое доказательство существования
  отдельного биллингового бэкенда вне этой админки. Рядом лежит
  `Admin/controls/Players/SearchMultyForWorlds/SearchMultyAccaunts_<мир>/` — **буквально
  скопированная под каждый игровой сервер** копия того же инструмента (папки-суффиксы
  `beta`, `edinorog`, `eridan`, `kassandro`, `lion`, `master`, `orion`, `pegassus`,
  `phoenix`, `premium`, `priemka`, `turnir`, `vodolei`, `volk`, `voron` — это, судя по
  всему, реальные кодовые имена игровых серверов/миров), — признак копипаст-эксплуатации
  вместо параметризации по `dbId`.
- **Баны**: `Admin/controls/Players/EditUser/UserBan/index.php` и
  `.../Ban_Unban/index.php` — переключение `ds_user.accountactive` (простой бан/анбан),
  с автоматическим отзывом флота в защиту (`ME_BanUser::ReturnUserArmyOnDefend`).
  `Admin/controls/Players/EditUser/PremiumBan/index.php` — отдельный, ограниченный по
  времени «премиум-бан» (`ds_user_premium_ban`) с флагом «разрешить торговлю» — то есть
  бан именно премиум-статуса/функций, а не всего аккаунта.
- `Admin/controls/Players/MergeAccaunts/index.php` — слияние нескольких логинов
  («мультов») в один основной: перепривязывает `ds_userinstance`/
  `ds_userinstancecollection` во всех мировых БД к главному `id_user`.
- `Admin/controls/Players/Delete/{DeleteUser,DeleteColony,Delete_Unique}` — удаление
  аккаунта/колонии/уникальных данных, с поиском по логину/email и режимом «точного»
  (compliance) поиска — похоже на инструмент под запросы на удаление персональных данных.
- `Admin/controls/Players/EditUser/*` — прочее редактирование карточки игрока:
  `FineUser` (штраф), `ChangeLevel`, `EndVocation` (снятие режима отпуска), `UserData`,
  `UserInfo`, `AddAvatar`/`AddAllianceAvatar` (загрузка аватаров), `AddUserAllowed`/
  `AllowMultyWorldAccess` (расширение доступа к мирам сверх обычных правил регистрации).
- `Admin/controls/Players/GrantBuilding/`, `.../ChangeCombCoords/`, `.../ReservComb/` —
  точечные правки состояния игрока: выдать постройку, изменить координаты базы,
  зарезервировать стартовый слот.
- `Admin/controls/Players/Activate/{All,Recurring}` — массовая активация ожидающих
  аккаунтов (по email-подтверждению), в т.ч. периодический вариант.
- `Admin/controls/Players/RegistrationStatistic/index.php` — статистика регистраций за
  период с фильтром «email подтверждён».
- `Admin/controls/Players/UserStore/{search,transfer,transferOrders}` — просмотр и
  ручной перенос заказов/товаров премиум-магазина игрока (билинг-примыкающий инструмент).
- Дублирующиеся top-level копии части функций: `ShowBattleReports/`, `UserInfo/`
  (доступны и через `EditUser`).

### 2.7. Portal — контент публичного сайта

- `Admin/controls/Portal/PageCreation/Pages/index.php` (+`page.php`,
  `PageCacheReset.php`, `PageCacheDataSync.php`, `easyadmin.php`) и
  `Admin/controls/Portal/PagesTree/index.php` — универсальный редактор дерева страниц
  портала, с упрощённым режимом «easyadmin» (обходит проверку `AdminMenu::chekMenu()`).
- `Admin/controls/Portal/TextBlocks/index.php` (+`history.php`) — редактируемые текстовые
  блоки с историей изменений.
- `Admin/controls/Portal/Worlds/index.php` — публичный (маркетинговый) список игровых
  миров на сайте — отдельно от операционного `GameWorlds`.
- `Admin/controls/Portal/AdditionalServices/{Azr,Bal,Bel,Eur,Izr,Mol,Moscow,Rus,Tad,Ukr}`
  — по одному разделу на регион/страну (похоже на Россию, Украину, Беларусь, Молдову,
  Израиль, Европу, Таджикистан, Москву и ещё два кода, не расшифрованных однозначно) —
  информационные таблицы «Service Name»/«Price»: справочники доступных в регионе
  способов пополнения/оплаты — билинг-примыкающий контент, но не сама транзакционная
  логика.
- `Admin/controls/Portal/{AliancesPage,ComradePage,DealmakersPages,PeoplePages}` —
  админка «социальных» страниц-каталогов портала (альянсы, «соратники», «дилеры»,
  «люди»).
- `Admin/controls/Portal/{LastNews,Additional/LinkUs,Poll,ImageUpload}` — второстепенные
  портальные виджеты (последние новости, «расскажи о нас», опрос, загрузка картинок).

### 2.8. PremiumAccaunts — премиум-подписка / донат (акцент задания)

Консоль по платной подписке «Профи»/«Премиум» (общие таблицы на мастер-БД:
`ds_userprofidata`, `ds_payment`, `ds_smspaylog`):

- `Admin/controls/PremiumAccaunts/Support/index.php` (класс `EditProfiData`) — основной
  экран: поиск премиум-аккаунтов по логину/email/фамилии/ID платежа/ID пользователя;
  готовые фильтры «неактивные», «неподтверждённые платежи», «зарегистрированы сегодня»,
  «trial», «отменённые», «активные-профи», «валидные-профи», «валидные-trial»; прямо в
  таблице — активация аккаунта, сброс премиум-пароля, смена премиум-email,
  переключение trial↔profi; экспорт в CSV; ссылки на инвойс, лог изменений и лог
  SMS-платежей по пользователю.
- `Admin/controls/PremiumAccaunts/Support/addPremium.php` — попап ручного начисления/
  списания N дней премиума с обязательным комментарием (пишет в лог через
  `premiumlogger.php`/`premiumChangeLog.php`).
- `Admin/controls/PremiumAccaunts/Support/showsmspays.php` — история SMS-платежей
  пользователя (`ds_smspaylog`: дата, результат, номер телефона, текст SMS) — прямое
  подтверждение, что премиум можно было оплатить платным SMS.
- `Admin/controls/PremiumAccaunts/Support/{showInvoice.php,showsingleinvoice.php,
  invoice.php.lang}` + `InvoiceNumberGenerator.php` — генерация/показ номеров и текста
  инвойсов (шаблонизируемый, `post.tpl`/`post2.tpl`).
- `Admin/controls/PremiumAccaunts/CreatePayment/index.php` — ручная регистрация платежа
  для пользователя (логин + модель/тип платежа, опционально «задним числом») — для
  поддержки при сбоях автоматического биллинга.
- `Admin/controls/PremiumAccaunts/AddPremiumToWorld/index.php` — массовая выдача
  премиум-времени выбранным пользователям в выбранных мирах, с фильтром «только
  премиум» и диапазоном дат.
- `Admin/controls/PremiumAccaunts/MoneyAccounts/index.php` — работа с внутренним
  «денежным счётом» (класс `MoneyAccount`, `classes/Core/MoneyAccount.class.php`) —
  похоже на биллинговый леджер/баланс. Каталог `classes/Core`, `classes/Validation`,
  `classes/Tests` — самодостаточная мини-библиотека (базовые объекты,
  хранилище свойств, трассировка, ассерты-валидаторы и юнит-тест-подобные
  `*TestCase`-классы), стилистически выделяющаяся на фоне остальной панели —
  похоже на более позднюю/качественную часть кода, написанную специально под биллинг.
- `Admin/controls/PremiumAccaunts/PremiumStat/index.php` — агрегированное число
  премиум-пользователей с фильтром по дате/миру.
- `Admin/controls/PremiumAccaunts/Report/` — генератор исторических отчётов по заказам
  (`createSuccessOrderReport.php`), с архивом выгруженных CSV за 2004–2008 гг. в
  `Report/data/` — бухгалтерские/финансовые отчёты.
- `Admin/controls/PremiumAccaunts/ViewLogs/index.php` — журнал активаций премиума за
  период (с группировкой, аналогично отчёту по SMS-сообщениям).

Вместе с разделами **SMS** и **Stats → PaymentServices** это ближайший аналог
«биллинг-консоли» внутри самой админки; фактическая обработка платежей, судя по
обращению к `https://billing.destinysphere.ru/gate/...`, происходит во внешнем сервисе.

### 2.9. SMS — SMS-платежи и SMS-рассылки (акцент задания)

- `Admin/controls/SMS/Dsmsg/Search/index.php` — CRUD/просмотр таблицы
  `dsmsg_messages` (`MESSAGEID`, `PHONE`, `TIME`, `TRIGGER`) — журнал отдельных
  транзакций SMS-шлюза (`TRIGGER` — игровое событие/пользователь-инициатор).
- `Admin/controls/SMS/Dsmsg/Sync/index.php` — подтягивает статистику с внешнего
  эндпоинта `http://portal_release/ds/gate/dsmessage_stat.php` — точка интеграции с
  SMS-агрегатором/шлюзом.
- `Admin/controls/SMS/Messages/index.php` — отчёт по **исходящим маркетинговым** SMS
  (фильтры: диапазон дат, короткий номер, провайдер, тип, группировка по провайдеру).
- `Admin/controls/SMS/SMSStatistic/index.php` — отчёт по **платёжным** SMS (фильтры:
  телефон, короткий номер, текст, флаг «только с пустым результатом» — то есть поиск
  неудачных/нераспознанных платежей) — экран сверки SMS-биллинга.
- `Admin/controls/SMS/News/{smsNews1,smsNews2,smsNews3}` — три независимых
  SimpleEdit-редактора над всё той же таблицей `NewsCompany`
  (`Placer='smsnews1'|'smsnews2'|'smsnews3'`) — контент для трёх разных SMS-рассылочных
  списков/кампаний.

Судя по полям `shortNumber`/`provider`/`phone`, раздел обслуживает классическую для
рунета схему «оплата SMS на короткий номер» для пополнения премиума/игровой валюты, а
также SMS-информирование игроков. **Это прямо относится к будущему документу про
биллинг.**

### 2.10. Stats — статистика и отчётность

Read-only BI-раздел, никаких изменяющих операций не обнаружено:

- `Admin/controls/Stats/Charts/index.php` — дашборд на Highstock/Highcharts
  (библиотеки `highstock.js`, `exporting.js` включены прямо в репозиторий).
- `Admin/controls/Stats/Statistic/All/index.php` — сводная панель по всем мирам
  (таблица `ds_worldsstatisic`/аналог, с графиками).
- `Admin/controls/Stats/Statistic/{Battles,BlackJack,CaptureColonies,GoldStat,
  LockingErrors,MoneyStat,NewStat,QuestCholera,QuestSearch,RamblersUsers,ramblerlist,
  ServerLoad,TransferResource,TransferStat,UnitReinarnation,UserAction,UserStat,
  UniqueLogin}` — по одному отчёту на игровую подсистему: бои, мини-игра «BlackJack»,
  захваты колоний, статистика игровой валюты («Gold»), ошибки блокировок в БД, денежная
  масса, воронка по квесту «Cholera», уникальные входы, активность по пользователям,
  трафик/регистрации с «Rambler» (вероятно, интеграция с порталом Rambler.ru как
  рекламным партнёром/источником трафика — не проверено глубоко).
- `Admin/controls/Stats/Statistic/PaymentServices/index.php` — **биллинговый отчёт**:
  разбивка по платёжному сервису (`action_name`), миру и периоду купленных
  «Silver Credits»/«Gold Credits» (платных внутриигровых валют) из таблиц
  `ds_paymentserviceslog`/`ds_paymentserviceslog_users` на мастер-БД — сводный аналог
  SMS-статистики, но по всем **не-SMS** платёжным сервисам сразу.

## 3. Как это связано с биллингом (сводка для отдельного документа)

Явные признаки монетизации, найденные в админ-панели:

1. **Премиум-подписка «Профи»** — таблицы `ds_userprofidata`/`ds_payment` на мастер-БД,
   управляется разделом `PremiumAccaunts` (§2.8): триал/профи режимы, ручное продление,
   инвойсы, отчёты по заказам.
2. **Оплата через SMS на короткий номер** — таблица `ds_smspaylog`, отдельный раздел
   `SMS` (§2.9) с журналом транзакций шлюза (`dsmsg_messages`), сверкой неудачных
   платежей и статистикой по провайдерам/коротким номерам.
3. **Прочие платёжные сервисы** (не SMS) — таблицы `ds_paymentserviceslog`/
   `ds_paymentserviceslog_users`, отчёт в `Stats/Statistic/PaymentServices` (§2.10), с
   покупкой «Silver Credits» и «Gold Credits» — платных игровых валют.
4. **Платные артефакты** — `GameSettings/PaidArtefacts` и `GameSettings/CreateArtefact`
   (§2.3) — донат-предметы.
5. **Магазин игрока** — `Players/UserStore` (§2.6) с заказами и их ручным переносом.
6. **Внешний биллинговый сервис** `https://billing.destinysphere.ru/gate/...` — минимум
   один эндпоинт (`payers.php`, отдаёт суммы платежей по `id_user`) вызывается прямо из
   `Players/SearchMultyAccaunts/index.php` — то есть авторитетные данные о платежах
   хранятся не (только) в игровых БД, а в отдельном биллинговом бэкенде, с которым
   админка интегрируется по HTTP.
7. **Региональные страницы способов оплаты** — `Portal/AdditionalServices/<регион>`
   (§2.7) — витринный (не транзакционный) контент про доступные в регионе способы
   пополнения.

## 4. `Admin/include/settings.php` и БД игровых миров

`Admin/include/settings.php` — место объявления `$GLOBALS['dbWorldAccess']`, массива
подключений ко всем PostgreSQL-базам игровых миров, используемого классом `DS_Common`
(§1.3) практически во всех модулях, где нужен доступ к игровым данным (`GameWorlds`,
`Players`, `GameSettings/TradeAdmin`, `PremiumAccaunts` и т.д.).

В опубликованном репозитории содержимое массива закомментировано/вычищено — виден
только `require_once .../ds/Main.php` и в комментариях — структура одной записи:

```php
0 => array (
    'host'     => 'game12',
    'dbname'   => 'destiny1',
    'user'     => 'dsgame',
    'password' => 'REDACTED_DB_PASSWORD',
    'server'   => 'volk',
    'dir'      => '/ds/'
),
```

То есть каждая запись — это: хост Postgres, имя БД, логин/пароль, короткое «кодовое имя»
сервера-мира (`server`, используется как значение `dbname` в общем реестре
`ds_worldcollection` на мастер-БД, `dbId = 0`) и путь к игровому коду. Реальный список
кодовых имён миров в этом репозитории напрямую не сохранился, но косвенно
восстанавливается по именам папок
`Admin/controls/Players/SearchMultyForWorlds/SearchMultyAccaunts_<имя>` (см. §2.6):
`beta, edinorog, eridan, kassandro, lion, master, orion, pegassus, phoenix, premium,
priemka, turnir, vodolei, volk, voron`. В закомментированном примере в
`Admin/include/security.php` также фигурируют кодовые имена `volk` и `phoenix`.

Отдельный (упомянутый в задании) файл `ds/config/settings.php` целенаправленно не
изучался подробно — по инструкции задания это не обязательно; поверхностно видно, что
`Admin/include/DS_Common.php` и игровой движок в `ds/` читают один и тот же
`$GLOBALS['dbWorldAccess']`, то есть админка и живой игровой сервер используют общий
источник конфигурации подключений к мирам (в проде это, вероятно, обеспечивается общим
`settings.php`/`ds/Main.php`, а не двумя независимыми копиями).

Помимо `dbWorldAccess`, тот же файл в проде задавал `$GLOBALS['dbForum']` (доступ к БД
форума) — используется, например, в `ClassFactory::getInstance("DB", "forum")`.

Собственные данные админ-панели (учётки GM, `AdminMenu`, `Users_AdminMenu`, CMS-таблицы,
`NewsCompany`, `dsmsg_messages`, `Sessions`) хранятся отдельно, судя по коду — в MySQL
(вызовы `mysql_db_query`/`DBName`), в то время как игровые данные миров — в PostgreSQL
(`pg_connect`/`pg_query` внутри `DS_Common`). Это две разные СУБД в одном приложении.

## 5. Открытые вопросы

- Точный список статусов `world_status` (`ds_worldstatus`) не восстановлен полностью:
  в коде встречены `CLOSE`, `REGISTRATION_CLOSE`, `TURNIR_STARTED` и числовое значение
  `4` по умолчанию при создании мира — какие ещё статусы существуют (открыт/активен и
  т.п.) и их точная семантика — не установлено без доступа к содержимому таблицы
  `ds_worldstatus`.
- Реальное содержимое `$GLOBALS['dbWorldAccess']` (полный список миров/серверов, их
  количество, распределение по физическим хостам) в репозитории вычищено; в документе
  список кодовых имён миров восстановлен только косвенно, по именам папок
  `SearchMultyForWorlds/SearchMultyAccaunts_*`, и может быть неполным или устаревшим.
- Не установлено точное назначение `Admins/Admins/rules.php` и `Admins/Admins/test.php`,
  а также различие между `Admins/Statistic/Activity`, `EventsList` и `UnionJournal`
  (файлы не изучались построчно) — предположительно все три являются разными
  представлениями одного и того же журнала действий (`actionLog`).
- Не установлено точное назначение `CMS/Models` (какие именно «модели» редактируются).
- Расшифровка кодов регионов в `Portal/AdditionalServices` дана предположительно
  (`Rus`=Россия, `Ukr`=Украина, `Bel`=Беларусь, `Mol`=Молдова, `Izr`=Израиль,
  `Eur`=Европа, `Tad`=Таджикистан, `Moscow`=Москва); коды `Azr` и `Bal` не
  расшифрованы однозначно (возможно, Азербайджан и страны Балтии/Балканы).
- Не установлено, что такое `PremiumAccaunts/Support/IT.php` и чем отличаются друг от
  друга `Players/SearchMultyAccaunts` (общий) и множество его копий в
  `SearchMultyForWorlds/SearchMultyAccaunts_<мир>` помимо жёстко прописанного мира —
  файлы не сверялись построчно на предмет более тонких отличий логики.
- `Admin/include/classfactory.php`, ветка `getInstance("DB", ...)` через PEAR DB,
  выглядит нерабочей/мёртвой (содержит `var_dump`+`die()` до реального использования
  результата) — не подтверждено, вызывается ли она вообще где-либо в актуальном коде,
  или это исключительно наследие более старой версии панели.
- `Admin/controls/GameWorlds/Clear/` фактически не содержит функционального кода (только
  файлы CVS-метаданных) — не установлено, было ли там раньше что-то рабочее, что
  потерялось при миграции с CVS, или папка изначально пустая.
- Модель прав `Users.dbAccess` (ограничение админа конкретными мирами) упомянута в
  закомментированном коде `Admin/include/settings.php`, но актуальность/включённость
  этой проверки в реально работающем приложении не подтверждена (в основном пути кода,
  которые читались, ограничение по мирам не применяется явно — большинство экранов
  просто перебирают весь `$GLOBALS['dbWorldAccess']`).
