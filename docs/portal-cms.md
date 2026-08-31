# Портальный CMS-слой: `sys/` и `sys_dyn_components/`

Документ описывает самописный мини-фреймворк, на котором построен публичный портал
Destiny Sphere (новости, статьи, формы регистрации/заказа доп.серверов, локализация
интерфейса) — физически отдельный от игровой логики (`old-sphere/ds`), хотя на практике
между ними есть прямые связи в рантайме (см. раздел «Открытые вопросы»).

Рассмотренные каталоги: `old-sphere/sys/` (~771 КБ), `old-sphere/sys_dyn_components/`
(60 файлов, из них 5 не-PHP), а также верхнеуровневые точки входа `index.php`, `conf.php`,
`aliance.php`, `vitrina.php`, `mail.php`, `news_export.php`, `up.php`. Пароли/хосты в коде
заменены на `REDACTED_DB_PASSWORD`/`REDACTED_DB_HOST` — не восстанавливались, не обсуждаются.

Все пути ниже — относительно `old-sphere/`, если не указано иное.

---

## 1. Общая архитектура: как рендерится страница портала

### 1.1. Точка входа — `index.php`

`index.php` — единственная «боевая» точка входа публичного портала:

1. **Строки 3–6**: если хост — `ds-game.su`/`www.ds-game.su`, сразу редирект на
   `/ds/index_login.php` (это уже игровой вход, не портал).
2. **Строка 8**: `require "./sys/define.php"` — центральный бутстрап-файл: выбирает БД
   портала, задаёт константы (`DEFAULT_LANG`, `GAME_PATH`, `CACHE_STORE_PATH`,
   `CACHE_PAGES_INFO_FILE`) и объявляет глобальные функции `getMysqlConnection()`,
   `local()`, `getLocalize()`, `actionLog()`, `getLanguages()` (см. разделы 3–4).
3. **Строки 14–37**: партнёрские cookie (`partner`, `ref`) по `HTTP_REFERER`/`idp`
   (реферальная программа `onlineguru.ru`, `DS_Partner::getPartner()` — уже обращение
   к игровому коду, `ds/Main.php`).
4. **Строка 42**: повторный `require .../ds/Main.php` — бутстрап **игры** прямо из
   точки входа портала (`GE_RamblerLogin` для Rambler ID).
5. **Строки 52–67**: постраничный кэш (`PageCache`, см. раздел 4) — если есть валидный
   кэш и не выставлен `$_REQUEST['nfs'] == 79`, отдаётся закэшированный HTML; иначе
   буферизуется вывод `require "./sys/lib/engine.php"`, в результате подменяется
   плейсхолдер `{TITLE_END}` на `$GLOBALS['TITLE_END']`, и результат кладётся в кэш.

Т.е. `sys/lib/engine.php` — это фактический «контроллер» рендера одной страницы;
`index.php` вокруг него — только кэш, cookies и хост-роутинг.

### 1.2. Мультитенантность и конфигурация — `sys/define.php` vs `sys/portal_define.php`

`sys/define.php` выбирает БД и стартовую страницу **по адресу/имени сервера**
(строки 12–23):

| Условие | `DBName` | `$default_page` |
|---|---|---|
| `$_SERVER['SERVER_ADDR'] == '94.242.60.31'` | `portal_unlim` | 316 |
| `SERVER_NAME` = `ds-game.su` / `www.ds-game.su` | `pulsar` | 228 |
| иначе (по умолчанию, `destinysphere.ru`) | `portal_ru` | 228 |

Т.е. один и тот же файловый код обслуживает как минимум 3 портальных сайта/бренда
(что согласуется с находками `docs/external-sites.md` о трёх версиях игры —
Classic/Unlimited/Pulsar). `DEFAULT_LANG` во всех трёх случаях — `"ru"`.

Рядом лежит файл **`sys/portal_define.php`** — почти дословная копия `define.php`
(та же структура, те же функции `local()`/`getMysqlConnection()`/`getLanguages()`,
но со старым API `mysql_db_query()` вместо `mysql_query()`), с `DBName = "portal_bono"`
и `$default_page = 216`. Проверено через `grep` по всему дереву: **этот файл нигде не
подключается** ни одной точкой входа в текущем срезе кода — похоже на конфиг для ещё
одного (несохранившегося или так и не задеплоенного здесь) тенанта того же движка.

Дополнительное подтверждение того, что весь `sys/` — переиспользуемый, изначально не
для Destiny Sphere написанный движок: `sys/include/configs/config.cfg.php` (строки 11–12)
жёстко прописывает `define("SITE_URL", "http://www.seafood.su")` и
`define("SITE_NAME", "Seafood")` — бренд, не имеющий отношения к Destiny Sphere.

### 1.3. Движок рендеринга — `sys/lib/engine.php` + `PageManager`

`sys/lib/engine.php`:

- Подключает `sys/define.php`, `sys/lib/templates.php` (глобальные хелперы шаблонов:
  `title()`, `short_title()`, `meta_description()`, `meta_keywords()`,
  `template()`/`template_ret()` — обёртки над `$P->ApplyTemplate()`), `sys/lib/MYSQLManager.php`
  (класс `PageManager_MYSQL` — держатель `conn_id` и форматтер ошибок).
- Строки 14–18: в зависимости от `$VIS_ADMIN` подключает `sys/PageManagerBase2.php`
  (админ-режим подсветки редактируемых блоков) либо `sys/PageManagerBase.php` (обычный
  публичный путь). На публичном сайте `$VIS_ADMIN` нигде не устанавливается в 1 (везде,
  где встречается, — `$VIS_ADMIN = 0` в `Admin/include/*`), т.е. на практике всегда
  используется `PageManagerBase.php`.
- Строка 20: `require "sys/lib/PageManager5.php"` — актуальная версия класса
  `PageManager` (расширяет `PageManager_BASE` из `PageManagerBase.php`, который в свою
  очередь расширяет `PageManager_MYSQL`). Рядом лежат неиспользуемые более старые версии
  движка — `sys/lib/PageManager3.php`, `PageManager4.php` — не подключаются нигде
  (не найдено ни одного `require` на них).
- Строки 22–43: `$p = $_GET['p']` (наивная фильтрация пробела/кавычек/`;`, не полноценное
  экранирование — см. «Открытые вопросы»); если у страницы `Pages.Auth = 'Y'`, требуется
  совпадение `SiteUsers.Email/Password` с сессией, иначе редирект на форму логина.
- Строки 46–54: `new PageManager(["PageID"=>$p])`; если страница не найдена **или**
  `$P->Page['lang_id'] != $default_lang` — откат на `new PageManager(["PageID"=>$default_page])`.
- Строка 72: `echo $P->ApplyTemplate(1, "main");` — рекурсивная сборка страницы.

`sys/lib/PageManager5.php` — сердце движка:

- `setPageID()` резолвит `Pages` (по числовому `ID` либо `ShortName`) в связке с
  `AssemblyTemplate` (макет страницы).
- `InitTemplates()` загружает дерево `Templates` + `PageTemplate` для найденного
  `AssemblyTemplate_id`, плюс таблицу `Sentense` (Template_id, Lang_id, Sentense,
  SentenseValue) — точечные подмены текста внутри `Templates.Body` по маркерам вида
  `<!-- #ИмяВставки# //-->` (строки 397–402).
- `ApplyTemplate($OrderInTemplate, $TemplateType)` (строка 387) — рекурсивный обход:
  `getNextTemplate()` находит дочерний `PageTemplate` с нужным `OrderInTemplate` под
  текущим родителем в рамках текущего `AssemblyTemplate_id`, затем в зависимости от
  `Templates.TypeID` рендерит один из 4 типов контента: `banner` (таблица `BannerWText`),
  `image` (`Images`), `text` (`TextWHeader`) — все три через модель
  `Element`/`Element_Data` (версионируемый CMS-контент с `Approbation`/`Status`) — и
  **`DynComponent`** (таблица `DynPHPContent`) — см. раздел 2.

### 1.4. Роли `sys/` vs `sys_dyn_components/` vs `templates/`

- **`sys/`** — код фреймворка: бутстрап/конфиг (`define.php`), движок страниц
  (`PageManagerBase*.php`, `lib/PageManager*.php`, `lib/engine*.php`), локализация
  (`local()`/`getLocalize()` в `define.php`), кэш (`lib/PageCache.class.php`,
  `lib/Cache.class.php`), генерик-инфраструктура CMS (`sys/include/classes/*` — 58
  файлов: `BaseClasses` — БД/шаблоны/файлы/логирование/параметры запроса,
  `PublicationClasses` — движок «списков публикаций» `Publication`/`PubSearch`/
  `PubManager`/`PubWork` (см. раздел 2.2), `ListClasses`/`ProductClasses`/
  `TechOverClasses` — унаследованный от прежнего проекта (см. 1.2) конструктор
  списков/фильтров для каталога товаров, в портале Destiny Sphere, судя по всему,
  не используется — актуальны только `publications` и часть `BaseClasses`), а также
  сама админку `sys/lib/simpleedit*.php`, `sys/lib/AdminMenu*.php`, `sys/lib/UserManager.php`,
  `sys/lib/session.class.php`, `sys/lib/tree.class.php` (не разбирались подробно — вне
  фокуса задачи, частично описаны в `docs/admin-panel.md`).
- **`sys_dyn_components/`** — папка с исходниками «динамических компонентов»: PHP-сниппетов,
  которые *может* исполнить движок вместо/внутри блока типа `DynComponent` (раздел 2)
  либо которые подключаются напрямую как виджет верстки (`<script src=".../RamblerID_JS.php">`,
  `require_once(".../add_serv_worlds.inc.php")`). Сама по себе эта папка — не реестр и не
  роутер: связь «файл → страница» нигде на диске не прописана, она целиком живёт в БД
  (см. раздел 2.1).
- **`templates/`** — статические `.tpl.html`/`.tpl.php` фрагменты для механизма
  `FastTemplate` (`sys/include/classes/BaseClasses/FastTemplate.class.php`), используемого
  исключительно классами `PubManager`/`PubWork` (списки новостей/публикаций,
  `templates/Publication/*.tpl.html`), плюс отдельные подпапки для других подсистем
  (`templates/Admin`, `templates/Buildings`, `templates/Units`, `templates/message` —
  последняя используется `ShowCMSMessage()` из `sys/define.php`). Это **второй**,
  независимый шаблонизатор — не путать с классом `template` (да, буквально так и
  называется) в `sys/lib/templates.php`, который парсит `Templates.Body` (HTML,
  хранящийся в БД) с плейсхолдерами вида `<!-- КЛЮЧ -->` через `PageManager::ApplyTemplate()`.

Итоговая цепочка запроса: `index.php` → `sys/define.php` (конфиг/локализация) →
`PageCache` (кэш) → `sys/lib/engine.php` → `PageManager5.php::ApplyTemplate()` → (для
блоков `DynComponent`) → `eval()` тела из `DynPHPContent.Body`, зачастую делегирующего в
`sys_dyn_components/*.php` → (для публикационных виджетов) → `PubWork`/`FastTemplate` →
`templates/Publication/*.tpl.html`.

---

## 2. Механизм «динамических компонентов»

### 2.1. Как компонент привязывается к странице

Привязка полностью декларативна и живёт в БД, не в файлах:

1. В `Pages`/`AssemblyTemplate`/`PageTemplate` описано, что на странице с таким-то
   `AssemblyTemplate_id` в позиции `OrderInTemplate` стоит шаблон (`Templates`) с
   `TypeID = 'DynComponent'`.
2. `PageTemplate_PageData.Data_id` для этой связки указывает на конкретную запись
   в таблице **`DynPHPContent`** (`ID`, `Title`, `Body`, `Lang_id`) — подтверждено
   чтением админ-редактора `Admin/controls/CMS/DynComponents/{common.php,edit.php}`:
   `Body` — поле типа `Text` (текстовая area 75×20 в форме редактирования), т.е.
   **администратор портала буквально вставляет PHP-код в текстовое поле в БД**;
   `Lang_id` — varchar(5) со списком из `Langs` (см. раздел 3).
3. `PageManagerBase.php::getDynPHPContentData()` делает
   `SELECT Title, Body FROM DynPHPContent WHERE ID='$ID'` и кладёт `Body` в массив
   `$Data[Body]`.
4. `PageManager5.php::ApplyTemplate()` (case `"DynComponent"`, строки 464–482) вызывает
   `TryEval($ApplyTemplate_res, "process")`, что в `TryEval()` (строка 551) на практике
   означает буквальный **`eval("?>".$SOURCE."<?")`** — исполнение хранимого в БД текста
   как PHP-кода **внутри метода объекта `PageManager`**. Поэтому в теле `Body`
   (а значит и в файлах `sys_dyn_components/*.php`, которые этот `Body`, как правило,
   либо содержит целиком, либо `require_once`-ит) доступны `$this->Page[...]`,
   `$this->getMainMenu()`, `$this->conn_id` и т.п. — это методы/поля именно `PageManager`.

Наглядные подтверждения того, что `$this` внутри компонента — это `PageManager`:
`sys_dyn_components/MenuHTML.php:6` (`$mainMenuItems = $this->getMainMenu();`),
`sys_dyn_components/MenuJavaScript.php:6`, `sys_dyn_components/NewsSphere.php:14`
(`new PubWork($db, $tpl, $this, 'newssphere')` — четвёртый аргумент `PubWork`/`PubManager`
это как раз объект `PageManager`, используемый затем для `$this->pageManager->Page['id']`
внутри `PubManager::listAll()`/`listProduct()`, см. `sys/include/classes/PublicationClasses/PubManager.class.php:74-75,110-111`).

**Важно**: без дампа БД нельзя увидеть, какая именно страница/слот ссылается на какой
конкретно `DynPHPContent.ID`/файл — эта информация полностью в данных, не в файлах
(см. «Открытые вопросы», п.1). То, что можно установить статически — это *набор
доступных компонентов* и то, как каждый из них устроен изнутри.

### 2.2. Два «движка вывода» внутри `sys_dyn_components/`

Просмотр ~20 файлов показал, что подавляющее большинство компонентов (все `News*.php`,
`add_serv_*.php`, `PeoplePage.php`, `ComradePage.php`, `DealmakersPage.php`, `linkus*.php`,
`sms/smsNews*.php` и т.д.) устроены по единому шаблону — не используют
`$this`/`ApplyTemplate` напрямую, а являются тонкими обёртками над отдельной подсистемой
«публикаций» (`sys/include/classes/PublicationClasses/`):

```php
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
$inc = new IncludeManager();
$inc->inc('publications');                     // грузит Publication/PubSearch/PubManager/PubWork
$db  = new DBinterface(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$tpl = new FastTemplate($_SERVER['DOCUMENT_ROOT'].'/templates');
$pub = new PubWork($db, $tpl, $this, 'newssphere');   // 'newssphere' — pubType = условное имя набора данных
$pub->links['mainUrl'] = 'newssphere';
$pub->setMode('top');                                  // необязательно — режим вывода
echo $pub->process();
```

`IncludeManager` (`sys/include/IncludeManager.class.php`) — простой загрузчик по
именованным группам файлов (`inc_dirs`/`files`, строки 15-245): группа `"publications"`
подтягивает `Publication.class.php`, `PubSearch.class.php`, `PubManager.class.php`,
`PubWork.class.php` из `sys/include/classes/PublicationClasses/`.

`PubWork::process()` (файл `PubWork.class.php`, строки 60-139) — гигантский `switch` по
значению `pubMode` (параметр запроса/явно выставленный `setMode()`), который выбирает
шаблон из `PubWork::$tplNames` (например `'top' => 'news_top.tpl.html'`,
`'alert' => 'news_anons.tpl.html'`, `'aliancepage' => 'aliance_page.tpl.html'` и т.д.) и
дергает `PubManager::listAll()`/`listProduct()`, которые ходят в БД через `PubSearch`
(таблица, судя по всему, называется как `pubType`, например `NewsCompany`/`news`/
`newssphere`/`newstourn` — см. также `news_export.php:31,110` — там прямо видно, что
таблица публикаций называется `NewsCompany` с колонкой `placer` = один из
`'news'|'newssphere'|'newsweek'`, т.е. `pubType`, переданный в `PubWork`, — это фильтр
`WHERE placer = 'pubType'` по общей таблице, а не имя отдельной таблицы на каждый
компонент) и заполняет `FastTemplate` (`templates/Publication/*.tpl.html`).

Второй, гораздо более редкий паттерн — компонент, написанный «напрямую» под
`ApplyTemplate`/`$this` без `PubWork` (например `MenuHTML.php`, `MenuJavaScript.php`,
`right_panel.php`, `techtree.php`, `Units.php`/`Units_NEW.php`, `Buildings.php`,
`ArtefactDropCompensation.php`) — эти либо строят HTML вручную через `echo`, либо (в
случае `Units*`/`Buildings.php`) реализуют собственные PHP-классы с игровыми
справочными данными, не имеющие отношения к CMS-моделям `Publication`/`Element`.

### 2.3. Прямые точки входа (не через `ApplyTemplate`)

Не все файлы `sys_dyn_components/` обязательно исполняются через `eval()` внутри
`PageManager`. Часть явно подключается напрямую как самостоятельный HTTP-ресурс или
через `require`:

- `sys_dyn_components/RamblerID_JS.php` — вызывается как `<script src="sys_dyn_components/RamblerID_JS.php">`
  (видно в `right_panel.php:63,83`) — то есть отдаётся напрямую сервером как `.js`-подобный
  ответ, не через `PageManager`.
- `sys_dyn_components/add_serv/add_serv_worlds.inc.php` — подключается через `require_once`
  и из `add_serv_rus.php:14` (компонента), и из корневого `mail.php:283` (обработчика
  формы заказа доп.услуг) — общий хелпер, а не отдельный виджет.
- `sys_dyn_components/UserOnline.php` — самостоятельный скрипт со своей веткой
  `if ($_GET['mode']==...)`, явно спроектированный на вызов как отдельный URL (JS-виджет
  «игроков онлайн», встраиваемый разными способами: `document.write`, XML-фрагмент для
  портала, значение для Rambler).

---

## 3. Локализация

### 3.1. `local()` / `Words` / `Dictionary` / `Langs`

Функция `local($name, $params='')` определена в **`sys/define.php:129-217`**
(и продублирована дословно, но со старым MySQL API, в неиспользуемом `sys/portal_define.php:107-195`):

1. `SELECT Word FROM Words WHERE Word = '$name'` — по сути проверка «существует ли
   такой ключ» (таблица `Words`, похоже, хранит валидные ключи словаря).
2. `SELECT D.Translation FROM Words W JOIN Dictionary D ON W.id=D.Word_id JOIN Langs L ON L.ID=D.Lang_id WHERE L.ID = '$lang' AND W.Word = '$name'`
   — сам перевод, где `$lang = constant('DEFAULT_LANG')` (строка 146) — **не язык
   посетителя, а фиксированный язык сайта** («ru» во всех трёх текущих
   конфигурациях `sys/define.php`, см. 1.2). Внутри просмотренного кода нет ни одного
   места, где `local()` вызывается бы с другим языком, чем `DEFAULT_LANG` — переключения
   языка интерфейса по запросу пользователя не найдено.
3. `$debug_mode = 1` (строка 131, захардкожено) — при отсутствии ключа/перевода
   `local()` тихо возвращает сам ключ (`$name`) вместо текста ошибки — т.е. в проде
   пропущенный перевод выглядит как «сырой» технический идентификатор на странице,
   а не как явная ошибка.
4. Поддержка `%`-плейсхолдеров: если передан `$params` (строка/массив), в переводе
   заменяется каждый символ `%` по порядку (или везде, если `$params` — строка).

`getLocalize($str)` (`sys/define.php:257-275`) — второй, вспомогательный механизм:
ищет в строке маркеры `[:КЛЮЧ:]` (через `preg_match_all("/\[:(.*):\]/i", ...)`) и
заменяет каждый на `local(КЛЮЧ)`. Используется, например, в
`sys/PageManagerBase.php::getMainMenu()`/`getMenuLevel2()`/`getMenuLevel3()` — заголовки
пунктов меню (`PageCategories.Title`, `Pages.Title`) могут содержать `[:КЛЮЧ:]` и
подтягивать перевод из словаря, даже когда сама таблица `Pages`/`PageCategories` не
хранит переводы построчно.

### 3.2. Поддерживаемые языки на практике

- **Портал**: во всех живых конфигурациях (`portal_unlim`, `pulsar`, `portal_ru` в
  `sys/define.php`, плюс неиспользуемый `portal_bono` в `portal_define.php`)
  `DEFAULT_LANG = "ru"`. Других значений `DEFAULT_LANG` в коде `sys/` не встречено.
- В `index.php:11`, `up.php:12`, `vitrina.php:4` объявлена таблица кодировок
  `$charset = ['RU'=>'windows-1251', 'DE'=>'iso-8859-1', 'EN'=>'windows-1251']` —
  формально движок спроектирован под RU/DE/EN, но ни один найденный конфиг не
  включает `DE`/`EN` как `DEFAULT_LANG`.
- `Langs`/`Dictionary`/`DynPHPContent.Lang_id`/`Pages.lang_id` используют, судя по
  редактору `Admin/controls/CMS/DynComponents/common.php:18-24` (поле `Lang_id` —
  `Varchar(5)`, "Lookup"→`Langs.ID`/`Langs.Title`), **короткие строковые коды** (`'ru'`
  и т.п.), а не числовые id. Это согласуется с сравнением в `sys/lib/engine.php:47`:
  `$P->Page['lang_id'] != $default_lang`, где `$default_lang` — строка `'ru'` из
  `$GLOBALS['default_lang']` (`sys/define.php:34`).
- `sys_dyn_components/techtree.php:7-42` реализует **собственный, полностью
  независимый** словарь на чистом PHP-массиве (`$l = [...]`) для игрового инструмента
  «дерево исследований» — только `de` (по умолчанию) и `en`, никак не связан с
  `local()`/`Words`/`Dictionary`.
- **`fr/` — не французская локализация портала.** См. раздел 3.4.

### 3.3. Отдельная, несвязанная языковая система — `DEFAULT_LANG_ID`

`DEFAULT_LANG_ID = "3"` определена в `sys/define.php:30` (и `portal_define.php:18`), но
**ни разу не используется функцией `local()`** и вообще не встречается больше нигде под
`sys/`. Полнотекстовый поиск по всему `old-sphere/` показывает, что `DEFAULT_LANG_ID`
активно используется — но только в **игровом бэкенде** (`Admin/controls/GameSettings/*`,
`Admin/controls/Players/*`) как `id_language` в таблицах вида
`ds_localizedtextlanguage`/`ds_localizedtext` — это отдельная, игровая система
локализации контента (названия построек/юнитов и т.п.), никак не пересекающаяся с
портальными `Words`/`Dictionary`/`Langs`. Наличие константы `DEFAULT_LANG_ID` в
`sys/define.php` рядом с `DEFAULT_LANG` — по всей видимости, историческая заготовка
«на будущее» или заимствование из шаблона конфигурации, которая портальным `local()`
не используется.

### 3.4. `fr/` и `securite/` — обнаруженный посторонний контент (не локализация)

При обзоре структуры каталогов рядом с `sys/` выяснилось, что каталог **`fr/`**,
который по названию можно было принять за французскую версию портала, **не содержит
кода портала вообще**. Его содержимое:

- `fr/index.php` — `header("Location:login.php");`
- `fr/df.php` — собирает URL вида `accesd.desjardins.com.<параметр из GET>.secure.superza.com/fr/login.php`
  и делает на него редирект.
- Остальные файлы/картинки (`fr/login.php`, `fr/affaires.php`, `fr/card_classic.jpg`,
  `fr/cvv2.gif`, `fr/vericode.gif`, `fr/desjardins.ico`, `fr/verifiez.html` и т.п.) по
  именам и структуре соответствуют фишинговому набору, имитирующему интернет-банкинг
  канадского **Desjardins**.

Аналогично, каталог **`securite/`** (по названию похож на «security», а не на
локализацию, но упомянут здесь для полноты картины кэш/каталоговой структуры рядом с
порталом) содержит файлы вида `cibc-logo.gif`, `epost-logo.gif`,
`anchor-introducingcibcmobilebanking-en.gif`, `c.php` — то есть похож на аналогичный
фишинговый набор, имитирующий **CIBC** (Canadian Imperial Bank of Commerce).

Это, по всей видимости, следы взлома этого legacy-хостинга в прошлом (веб-шелл или
уязвимость в аплоаде использовались, чтобы разместить фишинговые страницы двух разных
банков прямо в дереве сайта). К порталу Destiny Sphere эти каталоги отношения не имеют.
Код из `fr/`/`securite/` не выполнялся в рамках этого анализа — только читался как
текст.

**Подтверждено дополнительной проверкой (после первого прохода этого документа):**
`fr/login.php` — не статичная подделка, а прозрачный MITM-прокси: скрипт через `curl`
в реальном времени проксирует настоящий `accesd.desjardins.com`, переписывая ссылки,
чтобы жертва оставалась на фишинговом домене, и по пути логирует каждое отправленное
POST-поле (логин, пароль, ответы на контрольные вопросы) в локальный файл, а затем
пересылает собранные данные на внешний сервер. По датам файлов (июль 2010, январь
2011) компрометация хостинга произошла ориентировочно в этот период — за ~15 лет до
создания этого архива.

**Ремедиация:** оба каталога (`fr/`, `securite/`) удалены из рабочей копии и полностью
вычищены из истории git этого репозитория (включая уже опубликованные на GitHub
коммиты, история была переписана и перезалита force-push). На момент этой правки
неизвестно, остаются ли эти страницы доступны на живом продакшн-хостинге игры —
это отдельный вопрос эксплуатации, не связанный с данным архивом кода.

---

## 4. Кэширование страниц

### 4.1. Манифест `CachePagesInfo/PageCache.txt`

`CACHE_PAGES_INFO_FILE` (`sys/define.php:44`) указывает на
`CachePagesInfo/PageCache.txt` — плоский файл вида `PageID;PageName;TTL_секунд` на
строку. На момент анализа в нём 88 строк, например:

```
216;main_newspage_welcome;600
251;newstourn;60
231;Error_403;60
317;under_construction;6000
```

Кэшируются **только** страницы, перечисленные в этом файле — `PageCache::_getPageInfo()`
(`sys/lib/PageCache.class.php`) выставляет `needCache=true` только при совпадении
`pageID`/`pageName`; всё остальное рендерится каждый раз заново.

### 4.2. Ключ кэша и хранение

`PageCache::_formMark()` формирует ключ так:
`pageName . '_' . md5(строка из GET+POST без 'p'/'nfs'/имени сессионной куки) . DBName`
(строки 78-129) — т.е. кэш **разделён по тенанту** (суффикс `DBName` — `portal_ru`,
`portal_unlim`, `pulsar`) и по фактическим query/POST-параметрам запроса.

Хранение — в `CACHE_STORE_PATH` = `CacheFiles/`, имя файла `PAGE_<ключ>`
(`Cache::_formFileName()`). Запись — через `flock()`; «время жизни» реализовано не
явным полем, а через `touch()` на `now + lifetime - timeDecriment`, где
`timeDecriment = 1_000_000` секунд (~11.6 суток) — при чтении `get()` проверяет
`filemtime < time() - timeDecriment`, что математически эквивалентно «срок истёк».
Приём рабочий, но нетривиальный для понимания без чтения кода.

Проверка по факту: сейчас в `CacheFiles/` лежит 14 файлов с префиксом `PAGE_`
(например `PAGE_main_pageportal_unlim`, `PAGE_news_059ccbb057b0fe3ab1b625bf3d5e6e9bportal_ru`)
— то есть кэш реально использовался как минимум для тенантов `portal_ru`/`portal_unlim`.

### 4.3. Сборка мусора

`PageCache::Collector()` — вероятностный GC: с шансом 1/21 (`rand(0,20)==5`) на каждый
запрос сканирует весь `CacheFiles/` и удаляет файлы, чьё время жизни (по формуле выше)
истекло — без cron, «на живую» во время обычных запросов.

### 4.4. Обход кэша: `nfs=79`

`index.php:55`: `if (!$strOut || ($_REQUEST['nfs'] == 79))` — недокументированный
«магический» GET/POST-параметр, форсирующий полную перегенерацию страницы вне
зависимости от наличия валидного кэша. `nfs` также входит в список параметров,
исключаемых из ключа кэша (`_formMark()`), — то есть сам факт передачи `nfs` не создаёт
отдельную кэш-запись. Смысл значения `79` и происхождение имени `nfs` в коде не
поясняются — см. «Открытые вопросы».

### 4.5. `CacheFiles/` разделяется с посторонней подсистемой

Из 88 файлов, физически лежащих в `CacheFiles/`, только 14 соответствуют схеме имён
портального `Cache`/`PageCache` (`PAGE_*`). Остальные ~74 — файлы вида
`005227aa2cf3c16ed83930c6c6b6539e0a872b2e.file.reg_wizard_impressum.tpl.php`, что похоже
на компилированный кэш шаблонизатора Smarty (`<sha1>.file.<имя_шаблона>.tpl.php`) для
шаблонов вида `reg_wizard_*`, `index_login`, `ring_view`, `payment_services` — это имена
игровых (`ds/`) страниц регистрации/входа, а не портальных. То есть `CacheFiles/` —
общий каталог минимум для двух независимых систем кэширования (портальной `Cache` и,
судя по всему, Smarty-кэша игровой стороны, не разбиравшейся в рамках этой задачи).
Кроме того, в `sys_dyn_components/UserOnline.php:10` и `BeautyFrame.php:14` используется
**третий** механизм — `DS_Cache::Singleton()` (игровой, из `ds/Main.php`) с TTL 120/180
секунд — тоже не имеет отношения к `sys/lib/PageCache.class.php`.

Отдельно от `PageCache.class.php` в дереве лежит `sys/lib/Cache.class.php` — более
старая версия класса `Cache` с идентичной сигнатурой, но без `flock()` и с
`implode("\n", file(...))` вместо `file_get_contents()` при чтении. Нигде не найдено
её отдельного подключения (класс `Cache`, вероятно, переопределяется/затирается тем,
что определён в `PageCache.class.php` — держать оба файла одновременно
недопустимо из-за конфликта имени класса; похоже на неудалённый черновик прежней
версии).

---

## 5. Таблица: `sys_dyn_components/*` → назначение

Все компоненты без пометки «page-manager context» подключены через `IncludeManager` →
`PubWork`/`PubManager` (раздел 2.2); pubType/pubMode указаны в скобках.

| Файл | Назначение |
|---|---|
| `AliancesPage.php` | Список альянсов по игровым мирам (`Alliance_Pages`/`Worlds`), напрямую через `FastTemplate`, без `PubWork` — исключение из общего паттерна |
| `ArtefactDropCompensation.php` | Игровой калькулятор: таблица компенсации за дроп артефактов по мирам, читает напрямую игровые таблицы (`ds_artefactprobability`, `ds_gamemodifier`, `ds_artefactscope`) через `Admin/include/DS_Common.php` |
| `Articles.php` | Лента «упоминаний в СМИ» (pubType `article`, ссылка `from_smi`) |
| `BeautyFrame.php` | Встраивание внешнего IRC/«beauty»-виджета с `beauty.destinysphere.ru`, кэш 180с через `DS_Cache` |
| `Buildings.php` | Класс `Buildings` — игровой справочник построек по расам (данные из отдельного `buildings.php`, не входящего в этот каталог); не через `PubWork` |
| `ComradePage.php` | Страница «команда/соратники» (pubType `comradepages`, режим `comradepage`, сортировка по `authors`) |
| `DealmakersPage.php` | Каталог «дилмейкеров»/партнёров (pubType `dealpages`, режим `dealpage`) |
| `LoginForm.php` | Мини-форма логина (логин/пароль → `ds/index_login.php`), + ветка Rambler ID |
| `LoginFormU.php` | Расширенный вариант формы логина/регистрации (138 строк, не разбирался построчно) |
| `MenuHTML.php` | HTML верхнего меню, строится из `$this->getMainMenu()` (page-manager context) |
| `MenuHTML_old.php` | Старая, статичная (без БД) версия верхнего меню — похоже на неудалённый черновик |
| `MenuJavaScript.php` | JS-код выпадающего меню (библиотека `Menu()`), данные из `getMainMenu()`/`getMenuLevel2/3()` (page-manager context) |
| `MenuJavaScript_old.php` | Полностью захардкоженная (без БД) версия JS-меню с зашитыми ссылками на `forum.destinysphere.ru` — легаси |
| `News.php` / `NewsAlert.php` | Основная лента новостей портала / её тизер-вариант (pubType `news`) |
| `NewsSphere.php` / `NewsSphereAlert.php` | Второй новостной канал «сферы» (pubType `newssphere`) |
| `NewsTourn.php` / `NewsTournAlert.php` / `NewsTournLastAlert.php` | Новости турниров: общая лента / «предстоящие» (`NewsDate > now`, встроено прямо в pubType-строку) / «прошедшие» (`NewsDate < now`) |
| `NewsWeek.php` / `NewsWeekAlert.php` | Еженедельный дайджест новостей (pubType `newsweek`) |
| `PeoplePage.php` | Страницы игроков/персон (pubType `peoplepages`, режим `peoplepage`) |
| `RamblerFooter.php` | Прямая загрузка (`fopen`/`fread`) и перепечатка HTML-футера партнёрской программы Rambler Top100, конвертация кодировки KOI8-R→CP1251 |
| `RamblerID_JS.php` | JS-сниппет со статусом входа через Rambler ID (`GE_RamblerLogin`); подключается как `<script src=...>` |
| `Units.php` / `Units_NEW.php` | Игровой справочник юнитов по расам — два параллельно живущих варианта (разные `pageparent`-слаги: `warunits_*` и `warunits_*_new`), не через `PubWork` |
| `UserOnline.php` | Счётчик игроков онлайн (3 режима вывода: Rambler-виджет / XML для портала / JS `document.write`), кэш 120с через `DS_Cache` |
| `flash_wnd.php` | `document.write`-блок с ротацией 2 Flash-баннеров (главная страница) |
| `top_bannerl.php` | `document.write`-блок с ротацией 6 Flash-баннеров (левая колонка) |
| `linkus.php` / `linkusShow.php` | Страница «обменяться ссылками» — список / детальная карточка (pubType `linkus`) |
| `right_panel.php` | Блок логина в правой колонке; помимо обычной формы — отдельная ветка SSO через `meta.ua` (`GlobalClassAuth`) и ветки Rambler-логина/регистрации |
| `techtree.php` | Полностью самостоятельный (931 строка, без БД/`PageManager`) визуализатор дерева исследований игры с собственным DE/EN-словарём; `include "buildings.php"` (строка 61) ссылается на файл данных, которого нет в этом каталоге — см. «Открытые вопросы» |
| `add_serv/add_serv_<regio>.php` (10 регионов: `azr`,`bal`,`bel`,`eur`,`izr`,`mol`,`moscow`,`rus`,`tad`,`ukr`) | Форма заказа «дополнительного сервера» для конкретного региона (pubType `addserv<regio>`) |
| `add_serv/add_serv_<regio>_Alert.php` (те же 10 регионов) | Тизер-вариант той же формы (`pubMode` `addserv<regio>alert`) |
| `add_serv/add_serv_worlds.inc.php` | Общий хелпер: список активных игровых миров (`ds_worldcollection`) для `<select>`; подключается и компонентом `add_serv_rus.php`, и корневым `mail.php` |
| `sms/smsNews1.php` / `smsNews2.php` / `smsNews3.php` | Три параллельные ленты для SMS-сервиса (pubType `smsnews1/2/3`, общий режим `smsnews`) |
| `sms/sms_chat.php` | Статичная HTML-заглушка (буквально текст «test») — похоже на недоделанный/тестовый файл |
| `sms/dsstylesms.css` | Стили для SMS-виджетов (не PHP) |

---

## 6. Открытые вопросы

1. **Нет дампа БД.** Без содержимого таблиц `Pages`, `AssemblyTemplate`, `PageTemplate`,
   `PageTemplate_PageData`, `DynPHPContent`, `Templates`, `Words`, `Dictionary`, `Langs`,
   `NewsCompany` и т.п. невозможно установить: (а) какая именно страница/слот использует
   какой конкретно `DynPHPContent.ID`/файл из `sys_dyn_components/`; (б) хранит ли
   `DynPHPContent.Body` код целиком или короткий `require_once` на файл; (в) реальные
   значения словаря переводов. Это главный пробел для полной картины «страница → компонент».
2. **`sys/portal_define.php` — мёртвый код или конфиг стороннего/утерянного тенанта?**
   Не подключается нигде в этом срезе кода, но по структуре и наличию `DBName =
   "portal_bono"` явно был рабочим конфигом какого-то деплоя того же движка.
3. **`sys/include/configs/config.cfg.php` содержит бренд «Seafood» (`seafood.su`)** —
   похоже, весь `sys/` + `sys_dyn_components/` изначально написан для другого проекта
   и переиспользован/скопирован для Destiny Sphere без полной зачистки старых констант.
4. ~~`fr/` и `securite/` — похожи на фишинговые наборы~~ — **подтверждено и устранено**,
   см. раздел 3.4: это работающий MITM-фишинг-прокси на Desjardins/CIBC, датированный
   2010–2011 годом (след взлома хостинга), оба каталога удалены из репозитория и из
   истории git. Остаётся открытым только вопрос, доступны ли эти страницы на живом
   продакшн-хостинге игры (вне этого архива) — это стоит проверить отдельно тому, кто
   администрирует боевые сервера.
5. **Несогласованность языковых идентификаторов.** `local()` сравнивает
   `Langs.ID = DEFAULT_LANG` (строка `'ru'`), `Pages.lang_id`/`DynPHPContent.Lang_id`
   тоже, судя по всему, строковые коды (`Varchar(5)` в админ-редакторе) — но отдельно
   существует `DEFAULT_LANG_ID = "3"`, которая используется только игровым бэкендом
   (`ds_localizedtextlanguage.id_language`) и, похоже, не пересекается с первой системой
   вообще. Без дампа `Langs`/игровой БД нельзя на 100% исключить, что это одна путаница,
   а не две намеренно разные системы.
6. **Смысл `nfs=79`** (`index.php:55`, обход кэша) — не документирован нигде в коде;
   происхождение имени параметра и числа 79 не установлено.
7. **`techtree.php:61`**: `include "buildings.php"` — файла с таким именем нет ни в
   `sys_dyn_components/`, ни в проверенных соседних каталогах; либо `techtree.php` уже
   нерабочий (сломанная зависимость), либо файл данных лежит где-то в `ds/` (вне
   рассмотренного скоупа).
8. **`PageManager3.php`/`PageManager4.php`/`PageManagerBase2.php`/`sys/lib/Cache.class.php`
   и `MenuHTML_old.php`/`MenuJavaScript_old.php`** выглядят неиспользуемыми (не найдено
   ни одного `require`/`include` на первые три; `_old`-файлы не встречаются ни в одном
   `require` в `sys_dyn_components/`), но это не исчерпывающе доказано для каждого пути
   исполнения — часть кода в `Admin/` не была перепроверена на предмет ссылок на них.
9. **Смешение «портального» и «игрового» слоёв в рантайме.** Несмотря на заявленное
   физическое разделение, `index.php` (строка 42), `right_panel.php`, `UserOnline.php`,
   `BeautyFrame.php`, `RamblerID_JS.php`, `ArtefactDropCompensation.php`,
   `Units*.php`/`Buildings.php` напрямую подключают `ds/Main.php` или
   `Admin/include/DS_Common.php` и обращаются к игровой БД/кэшу. Разделение — скорее
   архитектурная договорённость, чем жёсткая граница на уровне кода.
10. **SQL-инъекции / доверие ко входу.** Множество запросов в `PageManager5.php`,
    `PubSearch`/`PubManager`, `sys/define.php::local()` собираются прямой конкатенацией
    `$_GET`/`$_POST` без `mysql_real_escape_string()` (местами есть, местами — нет,
    например `sys/lib/engine.php:22-27` фильтрует лишь пробел/кавычки/`;` в `$p`, а не
    экранирует). Отдельный полноценный security-аудит этого слоя не проводился — не
    входило в задачу, но стоит иметь в виду при дальнейшей работе с этим кодом.
