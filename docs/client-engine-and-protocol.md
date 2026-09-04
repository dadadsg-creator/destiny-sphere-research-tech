# Клиентский движок Flash (AS2) и сверка протокола с декомпилированным кодом

> **Как читать этот документ.** В отличие от [protocol-client-server.md](protocol-client-server.md)
> (реконструкция протокола только со стороны PHP), здесь впервые используется
> **декомпилированный клиент** — результат прогона `.swf` через JPEXS
> (ActionScript 2, байткод → псевдо-AS2-исходники).
>
> Важное ограничение декомпиляции: **имена локальных переменных потеряны**
> (`_loc1_`, `_loc2_`, …) — декомпилятор AS2 их не восстанавливает, это
> нормально и ожидаемо. Всё остальное — имена классов, имена
> методов/полей, и, самое ценное, **строковые литералы** (имена XML-тегов,
> атрибутов, значения по умолчанию) — сохранились ровно так, как их набрал
> разработчик в 2000-х. Весь фактический материал этого документа опирается
> только на них; там, где по контексту не удалось понять смысл `_loc_`-переменной,
> это явно написано, а не домыслено.
>
> **Обновление: сверка с настоящими (не декомпилированными) исходниками.**
> Отдельным проходом часть материала ниже дополнительно подтверждена, уточнена
> или исправлена по **реальным AS2-исходникам** из `old-sphere/SPHERE_client/`
> (SVN-копия разработчика клиента, предоставлена отдельно от основного архива
> и от декомпилированных деревьев выше) — то есть не байткод, прогнанный
> через JPEXS, а буквальный код, набранный разработчиком. Такие места
> помечены `client-source: файл.as:строка` (в отличие от `client-decompiled:
> файл.as` — то, что описано выше в этом врезе). Там же нашлись буквальные
> захваченные протокольные дампы (`.txt`-файлы вида `autentification.txt`,
> `outside.txt`, `refresh.txt`, `useraction.txt.full` — реальные пары
> запрос/ответ, а не код) — такие места помечены `client-capture: файл.txt`.
> Где decompiled-анализ разошёлся с настоящим кодом, это явно отмечено как
> исправление, а не тихо переписано.
>
> Кодировка файлов `SPHERE_client/` неоднородна и отличается от описанной
> выше проблемы декомпиляции: `.as`-файлы — UTF-8 с BOM, но часть русских
> комментариев внутри них испорчена мойбейком (байты CP1251 ошибочно
> прочитаны как Latin-1 и заново сохранены как UTF-8 — чинится
> `line.encode('latin-1').decode('cp1251')`); буквальные `.txt`-дампы
> протокола испорчены **другим** способом (UTF-8 ошибочно прочитан как
> CP1251 и пересохранён — чинится `text.encode('utf-8').encode('cp1251',
> errors='replace').decode('utf-8', errors='replace')`); один файл
> (`useraction.txt.full`) — вообще сырой CP1251 с частичным
> percent-encoding.
>
> **Прочитаны полностью** (пути ниже — относительно
> `decompiled_ds3/scripts/__Packages/`, если не указано иное):
> `parser.as` (1587 строк), `reqvestdata.as` (1016 строк), `loader.as` (234),
> `setting.as` (17), `debug.as` (88), `version_files.as` (117), `sota.as`
> (первые ~90 строк + выборочно), `game.as` (выборочно — полный список
> `static function`, плюс подробно `getLibrary`, `loadLib`, `ENCRYPTTEXT`,
> `getSIDIX`, `create_confirm_window`, `check_view_advice`), `contact.as`
> (выборочно, первые ~70 строк), `window.as` (полный список функций +
> подробно `createConfirmWindow`/`createConfirmWindowTiny`),
> `decompiled_menu/scripts/__Packages/tablecreate.as` (выборочно).
> **`xmlparsers.as` (3487 строк) прочитан не полностью** — построчно разобраны
> первые ~1300 строк (все XML-парсеры от `datawindow` до
> `artefact_depot_menu_parser`, `battle_show_menu`, `init_interface_button`) и
> ещё несколько функций точечно (`trade_menu_parser`, `research_menu`,
> `repars_table_old_to_new`), остальные ~15-20 парсеров панелей (например
> `army_overview_menu`, `auction_menu_parser`, `colonycenter_parser`,
> `mailListParser`) **не читались построчно** — их наличие и место в
> диспетчере (`parser.xmldata_action`) подтверждено, но их внутренняя
> структура не разбиралась (*это ограничение снято последующим проходом
> по реальным исходникам* `SPHERE_client` — см. §8.6, реальный
> `xmlparsers.as` теперь прочитан полностью). Файлы `decompiled_menu/…/window.as`, `game.as`,
> `parser.as`, `xmlparsers.as`, `sota.as`, `loader.as`, `setting.as`,
> `debug.as`, `reqvestdata.as`, `version_files.as`, `contact.as` сверены с
> одноимёнными файлами `decompiled_ds3` **точечно** (grep по ключевым
> строкам: `cancelactivatebuild`, `ENCRYPTTEXT`, `attack_confirm`,
> `constructbuilding`, `getcabinet`, `swloader`) — во всех проверенных
> местах код идентичен байт-в-байт (включая номера строк). Полного
> построчного diff двух копий не делалось.
>
> Файлы `window.as`/`game.as`/`parser.as`/`xmlparsers.as`/`sota.as` в
> `decompiled_menu/` **не анализировались как отдельная сущность** —
> это те же файлы, что и в `decompiled_ds3` (см. проверку выше), просто
> присутствуют в обоих SWF. Специфичные для меню классы окон
> (`trade_menu_class.as`, `army_overview_class.as`, `battleoverviewclass.as`
> и т.д.) **сознательно не читались** — по договорённости их разбирает
> параллельно другой агент.

Смежные документы: [protocol-client-server.md](protocol-client-server.md) —
реконструкция протокола со стороны PHP (то, что этот документ проверяет);
[game-engine.md](game-engine.md) — обзор `ds/classes/` (префиксы `UA_`/`PN_`/`TL_`/`TI_`);
[frontend-templates.md](frontend-templates.md) — встраивание SWF в HTML,
версионирование модулей (`version.ini`).

---

## 1. Архитектура клиентского движка

Разобранные файлы — это не набор независимых утилит, а один связный слой
"инфраструктуры", на который опираются все конкретные игровые окна
(`trade_menu_class` и т.п., см. отдельный документ). Ключевая находка:
почти весь этот слой держится на одном глобальном объекте —
`debug.link` — который, судя по обращениям (`debug.link.interface_mov`,
`debug.link.menu_mov`, `debug.link.setting_mov`, `debug.link.sending_lc`,
`debug.link.resourse_player`, `debug.link.localNames`, `debug.link.ck`, …),
является ссылкой на корневой таймлайн главного SWF (или его главный
document-class instance) — своего рода "God object"/service locator,
через который любой класс достаёт нужный MovieClip или кусок состояния.
Он не встречается как отдельный `.as`-файл (значит, либо задан прямо на
таймлайне FLA, либо это отдельный класс, не входящий в переданный набор
13 файлов) — сам факт его существования и форма API видны только по
обращениям к нему из `parser.as`/`game.as`/`xmlparsers.as`/`reqvestdata.as`.

| Файл | Роль |
|---|---|
| **`debug.as`** | Тонкая обёртка логирования: `_trace`/`_error`/`server_error`, которая на самом деле не пишет лог сама, а форвардит в `debug.link._trace()`/`debug.link._traceSE()` (лог, видимо, реализован на таймлайне — окно отладки, доступное в билдах для локальной отладки, `game.local()`). Побочный эффект: `debug._error()` дублирует ошибку в постоянное хранилище через `setting().saveSetting("errorlog", …)`. Класс `debug` также хранит статическое поле `debug.link` — по факту это общий "указатель на корень", проставляемый снаружи (не в этом файле). |
| **`setting.as`** (17 строк) | Двухстрочная прокладка: `saveSetting`/`loadSetting`/`clearSetting` — все три метода **немедленно** делегируют в `debug.link.setting_mov.saveSetting(...)`/`loadSetting(...)`. Сама реализация хранения (вероятно, `SharedObject`, судя по типичной для AS2-клиентов схеме, но текста `SharedObject` в этом файле нет) находится в `setting_mov` — MovieClip, не входящем в переданный список файлов. |
| **`loader.as`** | Единая очередь всех сетевых и ресурсных загрузок клиента (см. §2). Хранит массив `mass[]` задач, обрабатывает по одной (`keyLoad`-семафор), показывает общий прелоадер (`iPreloader`), различает два типа задачи — `"RES"` (довешивание `.swf`-библиотеки через `MovieClipLoader.loadClip`) и всё остальное (`"TXT"`, по умолчанию) — POST-запрос на `useraction.php` через `LoadVars.sendAndLoad`. |
| **`game.as`** (2222 строки, ~95 `static function`) | Не "игровой контроллер" в смысле бизнес-логики, а гигантский статический класс-утилита: инициализация и раскраска UI-виджетов (`init_decor_button`, `MovieGlow`, `dropShadow`, `ColorTransform`), форматирование чисел/времени (`Kilo_K`, `Number_`, `turnToTime`), обёртки локализации (`getLocalNames`/`getLocalNamesUser`/`getLocalNamesArt` — читают из `debug.link.localNames`, заполняемого в `parser.local_names()`), резолвинг путей к подгружаемым `.swf`-модулям (`getLibrary`, см. §1.9), lock/unlock интерфейса на время запроса, и — важно для протокола — `game.ENCRYPTTEXT()` (см. §4.7) и `game.getSIDIX()` (см. §2). |
| **`parser.as`** | **Центральный диспетчер ответа сервера.** `parser.parse_respond(obj)` — единственная функция, которая знает про все `mark`-имена верхнего уровня (`obj.combres`, `obj.xmldata`, `obj.outside`, …) — см. §5. Плюс сами разборщики этих marks: `combres_action`, `outside_action`, `sysmsg_action`, `helper_action`, `overload_lock`, `local_names` (обработка статического `loaddata`), `refresh_action`. |
| **`xmlparsers.as`** (3487 строк) | Диспетчер и коллекция парсеров содержимого `xmldata` (то есть конкретных игровых панелей). `xmlparsers.undefined_data`/`xmlparsers.datawindow`/… — десятки `static function`, каждая разбирает XML одной панели в плоский JS-объект и передаёт его в `debug.link.menu_mov.create_XXX_menu(...)` — то есть "скармливает" готовые данные конкретному окну (см. §7). Диспетчер — `parser.xmldata_action()` (физически лежит в `parser.as`, не в `xmlparsers.as`! — см. §6). |
| **`reqvestdata.as`** | **Единственное место сборки исходящих XML-команд.** ~90 статических функций, каждая строит строку вида `"<tag attr=\"...\" />"` и передаёт объект `{xmldata: "..."}` в `reqvestdata.send_loader(obj)`, который добавляет `ck` и кладёт задачу в очередь `loader`. Полный разбор — §4. |
| **`sota.as`** (`class sota extends MovieClip`) | Отрисовка **шестигранной сетки текущей соты** ("клетки" в игровой терминологии, ср. `DE_Comb`/`CR_CombView` на сервере). `sota.createSample()` рисует правильный шестиугольник через `beginFill`/`lineTo` (6 точек) — то есть "сота" в имени файла **буквально означает шестиугольную ячейку** (совпадает с интуитивным прочтением слова, вопреки предположению в задании, что стоило бы проверить это отдельно). Хранит захардкоженные "маски" типов рельефа как строки-битфилды (`center_loopback`, `neutral_loopback`, `home_loopback`, `unknown_loopback` — длинные строки из букв `D/S/G/W/E/F/R` = типы грунта на 217 клетках) и таблицу имён грунта `array_names = ["WATER","GRASS","SOIL","DESERT","E_FIELD","FROG","ROCK"]`. `sota.create(id_comb, location)`/`sota.createComplex(ring, complex, vk)` вызываются из `parser.refresh_action()` (§5) — то есть `sota` это визуальный слой поверх ответа `refresh`/`combcomplex`. |
| **`window.as`** (5191 строк, `class window extends MovieClip`) | **Не "менеджер окон конкретных панелей"**, а низкоуровневый тулкит переиспользуемых UI-виджетов, от которого наследуются конкретные классы окон (пример из переданного набора — `tablecreate extends window`, а по методам, которые дёргают `debug.link.menu_mov.createXXX(...)`-функции, можно предположить, что и все игровые панели вроде `trade_menu_class`/`army_overview_class` тоже наследуются от него или его потомков — не проверялось напрямую, эти классы вне периметра задачи). Предоставляет: `createWindow`/`createInnerWindow`/`resizeWindow`/`close_menu` (базовая рамка окна с заголовком и уголками), `createTabs`/`add_icon_tabs`/`createTabsCombo` (вкладки — именно они рендерят массив, который возвращает `parser.selectTabs()`, см. §6), скроллбары (`addScroll`, `addTextScroll`), чекбоксы/радио (`addcheck`, `addRadio`), календарь (`initCalendar`/`initDateField`), комбобоксы (`initCombo`), дерево (`createTree`), универсальные модальные окна: `createConfirmWindow(text)`/`createConfirmWindowTiny(text)`/`createAlertWindow(text)`/`createPopupWindow(text)`/`createBUYWindow`/`createBUYBONUSWindow`. **Важно**: `createConfirmWindow` здесь принимает **строку** текста и вешает колбэки `func_y`/`func_n`, которые обязан выставить вызывающий код **после** создания окна — это единая, переиспользуемая "да/нет" форма, используемая по всему интерфейсу (не только для протокольных подтверждений). |
| **`version_files.as`** | `version_files.save(xml)` разбирает mark `xmldata` с корнем `version` (см. диспетчер §6) — список `<file name="..." version="N"/>`, строит для каждого известного модуля (`localdata`, `ds3.swf`→`mainlib`, `menu.lib.swf`→`menulib`, `buttons.lib.swf`→`butlib`, `build_tech.lib.swf`→`buildlibt`, и т.д. — см. код) путь вида `"flash/" + basename + "-" + version + ext` — это подтверждает со стороны клиента cache-busting-схему из [frontend-templates.md](frontend-templates.md) §1.3 буквально один в один: **сервер шлёт список файл→версия, а клиент сам собирает суффиксованное имя**, а не наоборот. |
| **`tablecreate.as`** (`decompiled_menu`, `class tablecreate extends window`) | Не про строительство — это UI-таблица покупки внутриигровой валюты/услуг (кнопки вида `TAKE_ALL_MONEY`, вызывает `reqvestdata.getPayService(desc, val, type)`). Наследуется от `window`, что подтверждает паттерн "конкретное окно = подкласс `window`". |
| **`contact.as`** (573 строки декомпилированной версии; **client-source, 660 строк** — см. §8.5) | **Не относится к игровому XML-протоколу.** Это интеграция с соцсетью ВКонтакте (`ContactAPI`-подобный слой): `contact.ComplexProfiles`, `contact.contactLog` — грузят профиль пользователя (`nickname`, `photo_medium`) через отдельный `LoadVars.sendAndLoad` на URL из `contact.GetProfiles2(...)` (не `useraction.php`!), парсят XML-ответ соцсети. Актуально только для сборки клиента под `_level0.socialNet == "VK"` (в актуальном коде — `"VK"`/`"POP"`, см. §8.4). **Подтверждено полным чтением реального файла** (client-source): исключительно VK, без Одноклассников/mail.ru/Facebook и без wall-posting/invite/share функций. |

### 1.1. Общая схема потока данных

```
reqvestdata.XXX(...)                    — построение "<tag .../>"
        │
        ▼
reqvestdata.send_loader(obj)            — добавляет obj.ck = debug.link.ck
        │
        ▼
loader.obj_loader.addLoader(url, obj, "TXT", ...)   — в очередь mass[]
        │
        ▼
loader.internal_func() → loader.loadData(url, obj, i, ...)
        │
        ▼
new LoadVars(); temp.xmldata=obj.xmldata; temp.ck=obj.ck; [temp.SIDIX=...]
temp.sendAndLoad(url, temp, "POST")     — POST на useraction.php
        │
        ▼  (ответ — LoadVars-строка mark=value&mark=value...)
temp.onLoad = function(valid) { ... parser.parse_respond(temp) ... }
        │
        ▼
parser.parse_respond(obj)               — obj.combres / obj.xmldata / obj.outside / ...
        │
        ▼ (если obj.xmldata существует)
parser.xmldata_action(obj.xmldata) → xmlparsers.XXX_parser(rootNode)
        │
        ▼
debug.link.menu_mov.create_XXX_menu(...)   — передача готовых данных окну
```

---

## 2. Транспорт: как физически уходит и приходит запрос

Это прямое подтверждение §2.1 и §2.4 `protocol-client-server.md` из
реального клиентского кода — `loader.as`, метод `loadData()`:

```as2
// loader.as, конденсировано
function loadData(url, mov, i, func, ti) {
   var temp = new LoadVars();
   if (mov.xmldata.length > 0 || mov.xmldata != undefined) {
      temp.xmldata = mov.xmldata;
   }
   temp.ck = mov.ck;
   if (mov.SIDIX != undefined) { temp.SIDIX = mov.SIDIX; }
   temp.onLoad = function(valid) {
      ...
      parser.parse_respond(temp);   // <-- LoadVars-объект передаётся как есть
      ...
   };
   temp.sendAndLoad(url, temp, "POST");
}
```

Это **буквально** подтверждает:
- Транспорт — Flash `LoadVars.sendAndLoad(url, target, "POST")`, то есть
  классический `application/x-www-form-urlencoded` POST (доктрина
  `protocol-client-server.md` §2.1 подтверждена, не предположение).
- Поля тела запроса — ровно `xmldata` и `ck`, как и предполагал документ.
  Третье поле, `SIDIX`, добавляется **только если `mov.SIDIX` определено**.
- Ответ приходит в тот же объект `temp` (`LoadVars` **сам** разбирает
  `mark=value&mark=value…` в свойства объекта — это и есть механизм из
  §2.4 документа), и передаётся в `parser.parse_respond(temp)` **без
  дополнительного разбора** — то есть decode пар `mark=value` целиком на
  совести Flash-плеера, в клиентском ActionScript-коде для этого нет ни
  строчки кода.

### 2.1. Идентификатор сессии — `ck` и `SIDIX`

`reqvestdata.send_loader()`:
```as2
obj.ck = debug.link.ck;
if (obj.ck == undefined) {
   obj.ck = new setting().loadSetting("locall", game.getnameLocalNames());
}
...
if (debug.link.SIDIX != "undefined" && debug.link.SIDIX != undefined) {
   loader.obj_loader.addLoader(reqvestdata.getua() + "?SIDIX=" + debug.link.SIDIX, obj, "TXT", ...);
} else {
   obj.SIDIX = debug.link.SIDIX;
   loader.obj_loader.addLoader(reqvestdata.getua(), obj, "TXT", ...);
}
```
- `ck` берётся из `debug.link.ck` (обновляется на каждый ответ —
  `parser.central_komitet_action(ck)` сохраняет его туда и параллельно
  в постоянное хранилище через `setting`), что подтверждает ротацию ключа
  на каждый ответ, описанную в §2.5 `protocol-client-server.md`.
- **Новая находка, не описанная в серверном документе:** идентификатор
  сессии передаётся клиентом под именем **`SIDIX`**, либо GET-параметром
  `?SIDIX=...` к `useraction.php`, либо (если он ещё не известен) как
  дополнительное поле тела POST (`obj.SIDIX = debug.link.SIDIX`). Это,
  вероятно, и есть то самое "имя, равное `session_name()`", о котором
  говорит `protocol-client-server.md` §2.1 (не подтверждено напрямую по
  PHP-коду — там имя сессионной cookie не называлось явно, — но крайне
  вероятно, что `session.name` на сервере выставлен в `"SIDIX"`, раз
  клиент называет параметр именно так).

  **Резолвнуто живым тестом протокола (2026-09-04, источник: тестовый
  запрос обычным HTTP-клиентом, см. `CLIENT_PORTING_PLAN.md`, Фаза 1):**
  гипотеза не подтвердилась — `session_name()` на живом сервере равен
  **`PHPSESSID`** (стандартное имя PHP по умолчанию, не переопределено),
  подтверждено напрямую заголовком `Set-Cookie: PHPSESSID=...` при логине
  и явным `PHPSESSID=` в query string редиректа
  (`Location: index_start.php?ck=...&PHPSESSID=...`, ровно тот механизм
  фолбэка на query-параметр с именем `session_name()`, что описан в
  `protocol-client-server.md` §2.1). Значит `SIDIX` — это отдельное,
  клиентское по происхождению имя параметра (используется как
  синоним/обёртка при передаче ID сессии через `useraction.php`), не
  реальное имя PHP-сессионной куки — предположение из абзаца выше можно
  считать закрытым.
  **Важная оговорка по итогам сверки с настоящими протокольными дампами:**
  буквальные захваченные ответы сервера на **вход в игру**
  (`client-capture: autentification.txt`, `autentification2.txt`,
  `autentification3.txt`, `autentificationout.txt`, `unlogin.txt` — все
  18 просмотренных `.txt`-дампов протокола) не содержат подстроки
  `SIDIX` вообще — ни разу. То есть это предположение остаётся
  неподтверждённым именно для веб-логина (§3 серверного документа);
  `SIDIX`, описанный здесь, — это идентификатор для уже идущего игрового
  XML-протокола (`useraction.php`), а не для входа. Что реально происходит
  при входе — см. новый §2.2 ниже (`SIDIX`/`SIDID` в промо-ссылках, другой
  механизм) и `protocol-client-server.md` §3.1 (найденный вместо ожидаемого
  `SIDIX` механизм `turing=`).
- `getua()` возвращает буквально `"useraction.php"` (относительный путь) —
  подтверждение единой точки входа §2.1.

### 2.2. `SIDIX`/`SIDID` — та же подстрока, но два разных, не связанных друг с другом механизма

Важно не путать три вещи, которые называются похоже:

1. **`SIDIX` из §2.1 выше** — идентификатор игровой сессии, добавляемый
   `reqvestdata.send_loader()`/`loader.as` к каждому запросу на
   `useraction.php` (уже идущий XML-протокол).
2. **`game.getSIDIX()`** (**client-source: `game.as:1038-1044`**) —
   отдельная утилитная функция, которая возвращает `debug.link.SIDIX`
   либо строку-заглушку `"00000000"`, если он ещё не известен. У неё
   два независимых потребителя:
   - как query-параметр в кнопке премиума/VK (`game.as:1084`);
   - как **подстановка внутри URL, присланного сервером**: сервер кладёт
     в XML-панель кнопок интерфейса ссылки с буквальным
     плейсхолдером-подстрокой `"SIDID"` внутри строки URL
     (**client-source: `game.as:1307-1311, 1351-1355`**), клиент находит
     эту подстроку через `indexOf` и заменяет её на реальное значение
     `getSIDIX()` — то есть session id "прокатывается" в исходящие
     промо/кросс-ссылки текстовой подстановкой прямо внутри уже готового
     URL, а не отдельным XML-атрибутом.
3. **Механизм `turing=` при логине** (§2.1 выше, `protocol-client-server.md`
   §3.1) — вообще не связан ни с одним из двух пунктов выше, никакого
   `SIDIX`/`SIDID` в нём нет.

Итого — `SIDID` (без второй `I`) и `SIDIX` в коде клиента формально разные
строки, но обозначают один и тот же логический session id, просто в двух
разных точках его использования (транспортное поле запроса vs.
подставляемый плейсхолдер в промо-ссылках).

---

## 3. `parser.as` — диспетчер `mark`-полей ответа (`parse_respond`)

`parser.parse_respond(obj, arr)` — единственное место, которое перечисляет
**все** известные клиенту `mark`-имена. Полный список (порядок — как в коде):

```
nick, complex, world, league, newbee, stime, userGMT, ck, mode, delta,
loaddata, newsexist, combres, refresh, loadkey, sysmsg, xmlerror, xmldata,
outside, overload, popuphelp, muzlist, typeforfilter, quest, advice,
multiselect, refresh_rate, newmessage, helper
```

См. таблицу сверки в §5 — это прямое, построчное сопоставление с таблицей
`protocol-client-server.md` §2.5.

---

## 4. Точный формат исходящих команд (`reqvestdata.as`)

`reqvestdata.as` строит XML **прямой конкатенацией строк** — ни одного
DOM/XML-билдера, ни одного `escape()`/`encodeURIComponent()` на весь файл.
Свободный текст (имена, пароли, сообщения) почти всегда оборачивается в
`<![CDATA[...]]>` — но не экранируется от `]]>` внутри, и `&` в принципе
бессмысленно передавать (сервер вырежет его целиком, см.
`protocol-client-server.md` §2.1.3).

### 4.1. Транспортная обёртка (общая для всех команд)

Каждая функция строит `_loc1_.xmldata = "<tag .../>"` и вызывает
`reqvestdata.send_loader(_loc1_)` — сама обёртка (`ck`, `SIDIX`, POST)
не видна вызывающему коду, см. §2.

### 4.2. Полный каталог команд (подтверждённые буквально в коде)

Обозначения в колонке «Статус»: **✅ совпадает** — тег/параметры совпали
с реконструкцией `protocol-client-server.md`; **✏️ уточняет** — сервер-документ
верно угадал класс/тег, но клиент даёт больше деталей (порядок, доп.
атрибуты); **❌ ОПРОВЕРГАЕТ** — клиент использует другой тег, чем
предполагал серверный документ; **🆕 новое** — тег, отсутствовавший в
серверном документе вовсе.

| Функция `reqvestdata.*` | Собираемый XML (шаблон) | Статус | Комментарий |
|---|---|---|---|
| `putbuild(id)` | `<putbuild c="N" b="ID"/>` | ❌ ОПРОВЕРГАЕТ | `protocol-client-server.md` §7.5 предполагал тег `constructbuilding` (явно помечено «не подтверждено, реконструкция по имени класса `UA_ConstructBuilding`»). Реальный тег — **`putbuild`**. Строка `"constructbuilding"` не встречается нигде в `reqvestdata.as`/`xmlparsers.as` ни в одном из двух декомпилированных деревьев (проверено grep). Атрибут `c` — **один** номер ячейки (не список через запятую, как предполагал документ) |
| `cabinetRequest()` | `<cabinetRequest command="open" />` | ❌ ОПРОВЕРГАЕТ | `protocol-client-server.md` §7.10 предполагал тег `getcabinet` («не подтверждено»). Реальный тег — **`cabinetRequest`**. Строка `"getcabinet"` также нигде не встречается в декомпилированном коде |
| `cancelactivatebuild()` | `<cancelactivate c="N"/>` | ⚠️ несоответствие | PHP-список `$abpa` в `UserAction.class.php` (§5.2 серверного документа) содержит буквальный тег **`cancelactivatebuild`**. Клиентская функция называется `cancelactivatebuild()`, но реально отправляет тег **`cancelactivate`** (без суффикса `build`). Одно из двух: либо это разные, не связанные команды, либо в `$abpa`-список PHP попал "мёртвый"/устаревший вариант имени и реальная команда `cancelactivate` в `$abpa` не значится (см. «Открытые вопросы») |
| `start_reserach(id)` | `<research id="ID"/>` | ✅ совпадает | Полностью совпадает с §7.8 |
| `stop_reserach(id)` | `<cancelresearch id="ID"/>` | ✏️ уточняет | Серверный документ (§7.8) не был уверен, есть ли у `cancelresearch` параметры («вероятно, без параметров вовсе — не проверялось»). Клиент **всегда** шлёт `id` |
| `createAlliance(name, tag, joinpass)` | `<createalliance><name><![CDATA[name]]></name><tag><![CDATA[tag]]></tag><joinpassword><![CDATA[pass]]></joinpassword></createalliance>` | ✏️ уточняет | Теги/структура совпадают с §7.6, но **порядок дочерних тегов другой**: `name, tag, joinpassword`, а не `tag, name, joinpassword`, как в реконструкции документа (для XML-парсинга порядок обычно неважен, но для нового клиента лучше воспроизводить точно) |
| `send_mail(to, subject, body, type_scope)` | `<sendmessage id_messagescope="N"><to><![CDATA[to]]></to><subject><![CDATA[HEX]]></subject><messagetext><![CDATA[HEX]]></messagetext></sendmessage>` | ✏️ уточняет | Структура тегов совпадает с §7.7. **Важное уточнение:** `<subject>`/`<messagetext>` дополнительно всегда обёрнуты в CDATA, и их содержимое — не обычный текст, а результат `game.ENCRYPTTEXT(...)` (см. §4.7 ниже — это разрешает «открытый вопрос №4» серверного документа). `<to>` (логин получателя) через `ENCRYPTTEXT` **не** пропускается — идёт как обычный CDATA-текст |
| `army_action(id_army, action, ...)` | `<attack res=".." speed=".." destination="R.C.S" id_army=".."/>` (при `action=="ATTACK"`) | ✅ совпадает | Атрибуты те же (`res`, `speed`, `destination`, `id_army`), порядок в строке другой, но для XML это неважно. Атрибута `confirm` клиент **сам не добавляет никогда** (см. ниже) |
| `army_action(...)`, `action=="CANCEL_ATTACK"` | `<cancelattack .../>` | ✅ совпадает | Тег `cancelattack` подтверждён и в `$abpa` PHP, и здесь |
| `army_action(...)`, `action=="CANCEL_DEFEND"` | `<canceldefend .../>` | ✅ совпадает | — |
| `army_action(...)`, `action=="DEFEND"` | `<defend .../>` | ✅ совпадает | Тег `defend` — из `alertTags`, §5.3 серверного документа |
| `army_action(...)`, `action=="MOVE_TO_COMB"` | `<movetocomb .../>` | 🆕 новое | Не встречалось в серверном документе явно (класс `UA_MoveArmyToComb` в §6 значился без подтверждённого тега) |
| `army_action(...)`, `action=="ATTACK_CONFIRM"` | `<attack_confirm .../>` | 🆕 новое | Отдельный, самостоятельный тег, не описанный в серверном документе. **Не путать** с механизмом `outside`/`<answer>` (см. §4.3 ниже) — неясно, как эти два способа подтверждения атаки соотносятся друг с другом (см. «Открытые вопросы») |
| `army_action_hide(id_army, "HIDE_ARMY", time)` | `<hide_army id_army=".." timehide=".."/>` | 🆕 новое | Тег для класса `UA_HideArmy` (в §6 серверного документа — без подтверждённого тега) |
| `sale(...)` | `<sell id_tradescope=".." id_fromcategory=".." id_fromarticle=".." id_fromrealarticle=".." amount=".." id_tocategory=".." id_toarticle=".." price=".." tradetime=".." isauction=".." ispersonal=".." nick=".." pass=".." death=".." [anonymous=".."] />` | ✏️ уточняет | Тег `sell` подтверждён в `alertTags` (§5.3), но серверный документ не разбирал его атрибуты — здесь их **13** |
| `transfer_to_colony(...)` | `<transferresource combto=".." id_article=".." id_category=".." amount=".." death=".." />` | ✏️ уточняет | Тег `transferresource` подтверждён в `alertTags`, атрибуты — новая деталь |
| `army_unit_transfer(array)` | `<transferunit><unit id=".." count=".." id_army_from=".." id_army_to=".." />...</transferunit>` | ✏️ уточняет | Тег `transferunit` подтверждён (`$abpa`), но структура (дочерние `<unit>`) — новая деталь |
| `create_army(units, name)` | `<createarmy><armyname><![CDATA[name]]></armyname><unit id=".." count=".." />...</createarmy>` | ✏️ уточняет | Тег `createarmy` подтверждён (`alertTags`), структура — новая деталь |
| `insert_artefact(id)` | `<attachartefact c=".." id=".." />` | ❌ ОПРОВЕРГАЕТ (decompiled-анализ) | **client-source: `reqvestdata.as:761-770`.** Параметра `tabNum` в реальной сигнатуре нет вообще — decompiled-анализ его придумал/ошибочно восстановил. Тег `attachartefact` сам по себе подтверждён (`$abpa`) |
| `delete_artefact(id)` | `<detachartefact c=".." id=".." />` | ❌ ОПРОВЕРГАЕТ (decompiled-анализ) | Аналогично, **client-source: `reqvestdata.as:761-770`** — без `tabNum` |
| `sendColonyModule(dest, id, name)` | `<colonize id_module=".." destination=".."><colonyname><![CDATA[name]]></colonyname></colonize>` | 🆕 новое | Тег `colonize` подтверждён (`$abpa`), но структура (атрибут `id_module`, не просто `id`; дочерний `<colonyname>`) в серверном документе не описывалась вовсе |
| `dismissArmies()` | `<dismissArmies />` | 🆕 новое, ❌ уточняет decompiled-анализ | **client-source: `reqvestdata.as:104-107`.** Функция не принимает параметров вообще (decompiled-анализ ошибочно предполагал список `ids`) и всегда шлёт голый тег без атрибутов — распускает **все** армии игрока разом. Комментарий разработчика прямо над кодом: «РАСПУСТИТЬ ВСЕ АРМИИ К ЕБЕНЯМ НАХ!». camelCase-тег, не встречался в серверном документе (класс `UA_DismissArmies` в §6 — без тега) |
| `getReincarnate(list, typ)` | `<getReincarnate for=".." lst=".." />` | 🆕 новое | camelCase, для `UA_ReincarnateUnit`? — соответствие не очевидно по имени |
| `renameArmy` | *(не найдено в этом источнике)* | ⚠️ атрибуция не подтверждена | Полный grep по подстроке `"rename"` в реальном `reqvestdata.as` — **ноль совпадений**. Такой функции в этом файле нет вообще. Decompiled-анализ, видимо, либо ошибочно восстановил её из чужого/несвязанного кода, либо она реально существует, но не в `reqvestdata.as` (другой класс, другой снимок клиента, код на таймлайне) — понижено с «подтверждено» до «не найдено», не переносить в новый клиент без отдельной проверки |
| `getPremium()` | `<getPremium />` | 🆕 новое | — |
| `getNubHelp(num, stat)` | `<getNubHelp id=".." stat=".." />` | 🆕 новое | Для `UA_GetNubHelp` |
| `getPPeace(nick, UID, reason)` | `<getprivatepeace nick=".." [definition=".."] reason=".." />` | 🆕 новое | lowercase, в отличие от многих других camelCase-тегов — непоследовательность именования в самом клиенте |
| `getSpyLog(id)` | `<getspylog id=".." />` | 🆕 новое | — |
| `terraform`/`cancelterraform` | `<terraform c=".." type=".." />` / `<cancelterraform c=".." type=".." />` | 🆕 новое | Для `UA_Terraforming`/`UA_CancelTerraform` — короче имени класса, как и предупреждал серверный документ про ненадёжность угадывания по имени класса |
| `getReportBattle`/`getShowBattle` | `<getBattleInfo id_battle=".." [report="1"] />` | ⚠️ несоответствие регистра | PHP `$abpa` содержит букву в букву **`getbattleinfo`** (нижний регистр). Клиент шлёт **`getBattleInfo`** (camelCase). Имена тегов XML регистрозависимы — см. «Открытые вопросы» |
| `vacation`/`vacationstop`/`vacationpasssend` | `<vacationstart password=".."/>` / `<vacationstop password=".."/>` / `<vacationsedpass />` | 🆕 новое | Обратите внимание на опечатку в последнем теге — **`vacationsedpass`**, пропущена буква `n` в "send" (буквально так в декомпилированном коде, не опечатка в этом документе) |
| `powershieldenabled`/`poweractivation` | `<psstart time=".."/>` / `<psactivation />` | 🆕 новое | Для `UA_PowerShildStart`/`UA_PowerShildActivate` |
| `restoreProtectionShield` | `<restoreProtection resource=".." amount=".." />` | 🆕 новое | camelCase |
| `kickUserAlliance` | `<expelalliance id_alliance=".." id_userinstance=".." />` | ✏️ уточняет | Тег подтверждён (`$abpa`), атрибуты — новая деталь |
| `SurrenderArrBattle`/`SurrenderSampleBattle` | `<retreat id_battle=".." />` | 🆕 новое | Для `UA_Retreat` |
| `openHelpMoney()` | `<moneypage/>` | ✅ совпадает | Точное совпадение с `clientDirectLinks` (§10 серверного документа) |
| `clickOnAlly(alliance, player)` | `<clickonallyicon player=".." alliance=".." />` | ✏️ уточняет | Тег подтверждён, атрибуты — новая деталь (в серверном документе `clientDirectLinks` не показывал, что панель передаёт `id` — реально это `player`+`alliance`, и `id`/`get_attribute("id")`, используемый в PHP-обработке `clientDirectLinks`, тут не задействован — то есть для этого тега подстановка `{id}` в PHP, видимо, не сработает, см. «Открытые вопросы») |
| `requestScan(arr)` | `<scaninriver id=".." />` | 🆕 новое | — |
| `artActivate(id)` | `<artefactactivate id=".." />` | ✅ совпадает | Точное совпадение с `clientDirectLinks` |
| `filterScanerLog(...)` | `<scaninriverfilter keya=".." keya=".." keya=".." [name=".."] />` | 🆕 новое | Обратите внимание — **три атрибута с одинаковым именем `keya`** в одном теге (баг клиента: очевидно, задумывались разные имена типа `keya`/`keyd`/`keyz`, но при сборке строки везде подставлено одно и то же имя атрибута — XML-парсер сервера увидит только последнее значение `keya`, первые два будут молча перезаписаны) |
| `send_zond(...)` | `<sendzond id=".." count=".." action=".." [ring=".." compl=".." sota=".." oneround=".." sd=".."] [time=".."] />` | 🆕 новое | — |
| `activateAllProducers` | `<activateall res=".." switch=".." />` | 🆕 новое | — |
| `confirmSend(id_confirm)` | `<confirmAction id=".." />` | 🆕 новое | Возможный **отдельный, третий** механизм подтверждения (см. §4.3) |
| `get_sota()` / `get_data()` | буквальная строка `"get_sota"` / `"get_data"` (не XML!) | ⚠️ вероятно мёртвый код | По аналогии с `logout` (единственная документированная в §2.3 серверного документа не-XML команда) — но **ни один вызов `reqvestdata.get_sota()`/`get_data()` не найден** ни в одном из двух декомпилированных деревьев (проверено grep). Похоже на неиспользуемый остаток кода |
| `send_logout()` | буквальная строка `"logout"` | ✅ совпадает | Точное подтверждение §2.3 |
| остальные ~55 функций (`refreshgo`, `activatebuild`, `deactivatebuild`, `upgradebuild`, `cancelupgrade`, `cancelbuild`, `delbuild`, `navigate`, `complexview`/`NavigateComplex`, `leaveColony`, `trainunit`/`trainspyunit`/`trainwarunit`/`trainzondunit` + их `cancel*`-пары, `getbuildmenu`-обёртки с разными `tab=`, `showmemail`, `deletemessage`/`readmessage`, `joinalliance`/`leavealliance`/`changeleader`/`changepassword`, `dropartefact`, `buy`, `setfilterauction`, `getwarfactory`, `getremotewarbase`, `retreat`, `religyconnect`/`religycancel`, `calctravel` в двух режимах `TRANSFER`/`TRANSFERRES` и наборе режимов для `calc_army_action`) | см. код `reqvestdata.as` | смешанный | Каждая строит один самозакрывающийся тег вида `<tag attr="value" .../>`; полный текст — в файле, здесь не дублируется построчно ради объёма документа |

### 4.3. Три параллельных механизма "подтверждения" — важная находка

В клиенте одновременно существуют **минимум три** разных способа получить
подтверждение действия от игрока, и неясно, как они распределены между
собой:

1. **Готовая команда от сервера** (`outside`/`<answer>`, подтверждён и
   разобран в `parser.outside_action()`): сервер присылает XML с
   `action_id` и дочерним `<answer>`, содержащим **уже готовый следующий
   XML** (например, `<attack ... confirm="1" />` — из реконструкции §7.4
   серверного документа). Клиент извлекает текст `<answer>`
   (`_loc4_.customAnswer = _loc1_.childNodes[_loc2_].firstChild.nodeValue`)
   и передаёт объект в `game.create_confirm_window(_loc4_)` →
   `debug.link.createConfirmWindow(p_data)`. Обработчик кнопки "да"
   (который реально шлёт `customAnswer` обратно на сервер) физически
   находится **не в разобранных `__Packages`-классах** — видимо, это
   код на таймлайне главного SWF, не экспортированный как отдельный
   `.as`-класс. Поэтому нельзя подтвердить буквально, что кнопка "да"
   форвардит `customAnswer` как есть — это только следует логически из
   того, что сервер явно готовит готовую XML-строку под этим именем.
2. **Клиентский тег `attack_confirm`** (`reqvestdata.army_action`,
   `action=="ATTACK_CONFIRM"`) — самостоятельная команда, не зависящая от
   присланного сервером `<answer>`. Судя по всему, используется в другом
   UI-сценарии (не в модальном алерте `outside`, а где-то в самом окне
   армии — но конкретное место вызова `army_action(..., "ATTACK_CONFIRM", ...)`
   не разбиралось, вне периметра задачи).
3. **Универсальный `<confirmAction id="..." />`** (`reqvestdata.confirmSend`)
   — судя по имени, это может быть третий, максимально общий механизм
   подтверждения (по `id`, а не по готовому XML) для той же системы
   `outside`-алертов с `action_id` (`_loc4_.action_id` из
   `outside_action()` очень похоже коррелирует именно с этим `id`) — но
   прямой связки в коде (вызов `confirmSend` из обработчика алерта) не
   найдено, это предположение по совпадению названий полей.

### 4.4. Значения атрибута `locations` тега `load`, которые реально шлёт клиент

`reqvestdata.get_localnames()` — единственное место, которое строит тег
`<load .../>`:
```as2
if (game.getStatusLocal()) {
   _loc1_.xmldata = "<load locations=\"2\"/>";
} else {
   _loc1_.xmldata = "<load locations=\"0\"/>";
}
```
Клиент **никогда не запрашивает `locations="1"` или `locations="3"`** —
только `"0"` (нет сохранённой локализации нужной версии — запросить всё) и
`"2"` (локализация уже закеширована локально — не переприсылать статику).
`protocol-client-server.md` §7.1 предполагал (по PHP-коду), что `locations="3"`
включает список `id_comb` игрока — если это действительно так, а клиент
никогда явно не запрашивает `"3"`, то либо: (a) этот путь используется в
каком-то другом месте клиента вне разобранных файлов, либо (b) сервер сам
трактует "просто вход в игру" как `locations=3` где-то на другом уровне
(не в клиенте), либо (c) поведение с `id_comb` привязано не к `load`,
а к чему-то ещё. Не удалось проверить — открытый вопрос.

### 4.5. `getbuildmenu` — реальные значения `tab=`, встреченные в клиенте

Серверный документ (§7.9) предполагал, что `tab` — "буквальное имя
серверного класса-панели без префикса `PN_`, который сервер добавляет
сам, если отсутствует". Из `reqvestdata.as` видно, что клиент **в разных
местах передаёт то с префиксом, то без** — то есть защитный код на
сервере (`if (!is_int(strpos($this->tab,'PN_'))) $this->tab = 'PN_'.$this->tab;`)
действительно необходим и реально используется:

| Значение `tab=`, отправляемое клиентом | С префиксом `PN_`? |
|---|---|
| `PN_SpyLogs` | да (явно) |
| `ColonyNavigatePanel` | нет |
| `BuildingArtefact` | нет |
| `MessageOverview` | нет |
| `CreateMessage` | нет |
| `ArcheologyArtefact` | нет |
| `BattleOverview` | нет |
| `AllianceOverview` | нет |
| произвольная строка через `menu_call(tab)`/`call_tabs(tab_call)` | зависит от вызывающего кода (вне периметра задачи — там же наверняка передаются и все остальные, например `TradePanel`) |

### 4.6. Свободный текст в командах — экранирование

Ни разу не встречен вызов `escape()`/`encodeURIComponent()` в
`reqvestdata.as`. Практика такая:
- Имена/пароли/текст сообщений — почти всегда в `<![CDATA[...]]>`
  (колонии, альянсы, армии, письма) — защищает от `<`/`>` внутри текста,
  но **не** от последовательности `]]>` внутри пользовательского текста
  (потенциальный баг для нового клиента — стоит либо экранировать вручную,
  либо валидировать на вводе).
- Отдельные поля (например, `getPPeace.nick`) идут **как обычный
  XML-атрибут**, без CDATA и без экранирования кавычек — если значение
  содержит `"`, собранная строка технически ломает XML. Не проверялось,
  есть ли клиентская валидация ввода до вызова этих функций (скорее
  всего есть, на уровне UI-полей — вне периметра задачи). (Пример
  `renameArmy.newName` из более ранней редакции этого документа убран —
  сама функция `renameArmy` не найдена в реальном `reqvestdata.as`,
  см. §4.2.)

### 4.7. `game.ENCRYPTTEXT()` — разгадка "открытого вопроса №4" серверного документа

`protocol-client-server.md` §7.7 нашёл на сервере два независимых
декодера текста писем — `utf8_decode()` для полей `subject`/`messagetext`
и собственный `convert_to_utf8()` (режет строку на куски по 3 символа,
трактует как HEX-код) для полей `original_subject`/`original_messagetext`
— и не смог установить, какой из них реально соответствует тому, что шлёт
клиент. Ответ найден в `game.as:2191`:

```as2
static function ENCRYPTTEXT(body) {
   var _loc6_ = ["0","1", ..., "F"];   // hex-алфавит
   var _loc10_ = "";
   for (var i = 0; i < body.length; i++) {
      var code = body.charCodeAt(i);          // Unicode code point символа
      var hi   = _loc6_[Math.floor(code/256)]; // 1 hex-цифра (0-F, т.е. код должен быть < 4096)
      code %= 256;
      var mid  = _loc6_[Math.floor(code/16)];  // 1 hex-цифра
      var lo   = _loc6_[code % 16];             // 1 hex-цифра
      _loc10_ += hi + mid + lo;                 // ровно 3 hex-цифры на символ, без разделителей
   }
   return _loc10_;
}
```

Это **буквально то же самое** преобразование, что и серверный
`convert_to_utf8()` (3 hex-символа на символ), только в обратную сторону
(кодирование вместо декодирования). Вызывается **всегда**, для каждого
письма, из `reqvestdata.send_mail()` — `subject`/`messagetext` (но не
`to`!) проходят через `ENCRYPTTEXT` перед отправкой.

**Вывод, закрывающий открытый вопрос №4:** клиент **всегда** отправляет
тему/текст письма в 3-hex-цифровом формате. Значит, серверный декодер
`convert_to_utf8()` (для полей `original_subject`/`original_messagetext`)
— это тот, что реально восстанавливает читаемый текст. Декодер
`utf8_decode()` (для "рабочих" полей `subject`/`messagetext`), применённый
к строке вида `"3431443434..."`, даст **бессмысленный набор ASCII-символов
0-9A-F**, а не читаемый текст — то есть, по всей видимости, поля
`subject`/`messagetext` в БД либо не используются для показа игроку,
либо показывают "битый" текст, а реально видимый игроку текст берётся из
`original_subject`/`original_messagetext`. Прямого чтения кода отображения
письма (`ME_CompileAndSendMessages`/панель почты) для окончательного
подтверждения не делалось — это вне периметра этой задачи, но находка
клиентского `ENCRYPTTEXT` устраняет неопределённость именно о том, что шлёт
клиент.

**Сверка с настоящим исходником:** алгоритм в реальном `game.as`
(**client-source**, метод `ENCRYPTTEXT`) побитово идентичен описанному
выше decompiled-варианту (3 hex-цифры на символ, без разделителей) —
здесь decompiled-анализ ничего не исказил. Единственный вызывающий —
`send_mail`, как и написано выше.

**Новая находка — известный, но неисправленный баг с кириллицей в именах
альянсов/армий.** В `reqvestdata.as:585` (`createAlliance`) и `:752`
(`create_army`) — **client-source** — есть закомментированные попытки
применить `ENCRYPTTEXT` к именам альянсов/армий, с комментарием
разработчика в коде: «Проблема с русскими именами альянсов. Попробовать
кодировать, как имена армий». То есть проблема с кириллицей в этих полях
была разработчикам известна и один раз даже была предпринята попытка её
решить тем же приёмом, что и для писем — но эта попытка закомментирована
и не работает: реально имена альянсов/армий уходят на сервер сырым CDATA
(см. §4.2, `createAlliance`/`create_army`), без кодирования. Баг остался
неисправленным.

---

## 5. Сверка протокола: таблица `mark` (входящие top-level поля)

| `mark` из `protocol-client-server.md` §2.5 | Подтверждено клиентом? | Где в коде |
|---|---|---|
| `loadkey` | ✅ Да | `parser.loadkey_action()` — `if (_loc1_.attributes.valid != 1) getURL(addr, target_load ?? "_top")`. Подтверждает семантику `valid`/`addr`/`target_load` буквально |
| `ck` | ✅ Да | `parser.central_komitet_action(ck)` — `debug.link.ck = ck` + сохранение в `setting` |
| `overload` | ✅ Да | `parser.overload_lock(data)` — `data.split("|")`, второй элемент — число секунд; рисует блокирующий оверлей `OVERLOAD_BLOCK` с обратным отсчётом. Формат `"REASON|SECONDS"` подтверждён буквально |
| `newbee` | ✅ Да | `parser.newbee_action(newbee)` — `Number(newbee) > 0` |
| `xmldata` | ✅ Да | `parser.xmldata_action()` — диспетчер по `nodeName` корня, см. §6 |
| `combres` | ✅ Да | `parser.combres_action()` — перебирает `childNodes`, `debug.link.resourse_player[nodeName] = attributes`. Подтверждает, что имя дочернего элемента = внутреннее имя ресурса (`KADERIUM`, `NANO_CRYSTAL`, `GOLD_CREDIT`, …), атрибуты читаются как есть (`current`/`max` и т.д. — конкретные имена атрибутов не переименовываются клиентом, значит именно они видны напрямую как `debug.link.resourse_player.KADERIUM.current` — используется в десятках мест `xmlparsers.as`) |
| `helper` | ✅ Да, и разобрана внутренняя структура | `parser.helper_action()` — switch по `nodeName` вложенного XML (см. §5.1 ниже) |
| `outside` | ✅ Да (частично иначе, чем предполагалось) | `parser.outside_action()`; клиент разбирает содержимое **не проверяя имя корневого тега** (не ищет `nodeName=="swfloader"`), просто берёт "первый узел после XML-пролога" через `parser.getXMLdata()`. Это значит, что опечатка `</swloader>` в закрывающем теге (§9 серверного документа) **скорее всего не имеет значения для клиента** — строка `"swloader"`/`"swfloader"` не встречается нигде в `xmlparsers.as`/`parser.as` ни разу. Буквальные захваченные дампы (`outside.txt`, `outsidehtml.txt`) подтверждают саму опечатку дважды в реальном трафике (не декомпилятор ошибся) и добавляют атрибуты `override`/`res`/`speed`/`destination`/`id_army` плюс `<txt>` как CDATA-child — см. §8.7 |
| `loaddata` | ✅ Да, но decompiled-анализ ошибался в деталях | `parser.local_names()` — **client-source: `parser.as:647-868`.** Никакого тега/атрибута `<load v="N">` внутри самой функции нет — грепом по всей папке `SPHERE_client` строка `v="N"`-паттерн для `load` не встречается. Реально `local_names()` разбирает дочерние узлы по именам `artscope`/`artsize`/`artbonus`/`artefact`/`unit`/`systemtype`, плюс общие узлы локализации по атрибутам `id`/`ln`, заполняя `debug.link.localNames`; дополнительно сохраняет `art_filter_table`/`unit_table`/`art_table`/`local`/`versionLocalNames` через `setting().saveSetting(...)`. (Корневой тег ответа сервера всё же называется `<load v="...">` — это подтверждено со стороны PHP в `protocol-client-server.md` §7.1 — но сама функция-парсер клиента к этому имени/атрибуту не привязана, она просто перебирает дочерние узлы по их собственным именам) |
| `refresh_rate` | ✅ Да | `game.initrefreshbutton(obj.refresh_rate)` |
| `mode` | ✅ Да | `parser.prem_data(mode)` — `Number(mode) > 0` ⇒ премиум включён, сохраняется в `setting` как `"CHECK_BOXPremiumMode"` |
| `newmessage` | ✅ Да | `game.init_mail_button(obj.newmessage)` |
| `nick`/`complex`/`world`/`league` | ✅ Да | Читаются как простые (не-XML) строки прямо в `parse_respond`: `debug.link.usernick = obj.nick` и т.д. Подтверждает "обычные текстовые пары" из серверного документа |
| `delta` | ✅ Да | `parser.deltastart(delta)` → `game.init_deltastart(delta)` (антибот) |
| `xmlerror` | ✅ Да | `parser.error_action()` — минимальная обработка: `debug.server_error("PARS.SERV.ERROR:" + xml)`, просто логирование, без специального UI (для игрока ошибка, судя по всему, не показывается отдельным алертом на этом уровне — не проверялось, есть ли обработка в `debug.link._traceSE`) |
| *(не описан в серверном документе)* **`refresh`** | 🆕 новый mark | `parser.refresh_action()` — отдельный от `xmldata` mark! Несёт вид соты (`sota`/`build`-данные, атрибуты `id_comb`, `gate`, `location`) **или**, при `location=="COMPLEX"`, список сот комплекса + кольца. Внутри тот же самый разбор запускается и при получении тега `combcomplex` **внутри `xmldata`** (см. §6) — то есть один и тот же код `refresh_action` обслуживает два разных пути доставки одних и тех же данных |
| *(не описан)* **`sysmsg`** | 🆕 новый mark | `parser.sysmsg_action()` — **client-source: `parser.as:494-620`.** Системные сообщения, с тремя категориями по имени дочернего тега: `<m>` → `sys_msg` (обычные системные), `<w>` → `war_msg` (военные), `<s>` → `spy_msg` (шпионские — эта ветка появилась позже: в более старом снимке `parser_for_old_refersh.as` её нет вовсе, см. §8). Подсветка красным по маркеру `#A90101` **не подтверждена**: полный grep всей папки `SPHERE_client` по строке `A90101` даёт ноль совпадений. Если такая подсветка вообще есть, она либо формируется на сервере (PHP уже присылает готовую разметку — см. ниже, §5.2), либо реализована в другом, не разобранном файле |
| *(не описан)* **`stime` + `userGMT`** | 🆕 новый mark | `parser.init_clock()` — синхронизация серверного времени с локальными часами клиента |
| *(не описан)* **`newsexist`** | 🆕 новый mark | Флаг "есть непрочитанные новости" — включает анимацию кнопки новостей |
| *(не описан)* **`popuphelp`** | 🆕 новый mark | `parser.init_help_popup()` — разбирается, но результат никуда не используется в прочитанном коде (`xmlhelp = parser.getXMLdata(...)`, без продолжения) |
| *(не описан)* **`muzlist`** | 🆕 новый mark | `parser.init_music()` — плейлист фоновой музыки (`<track url=".." name=".."/>`-подобный список) |
| *(не описан)* **`typeforfilter`** | 🆕 новый mark | `parser.init_filter_array()` — разбирается, но результат тоже никуда дальше не передаётся в прочитанном коде |
| *(не описан)* **`quest`** | 🆕 новый mark | `parser.questDataParser()` — разбирается, но также не используется дальше в прочитанном коде (в отличие от полноценного разбора квестового меню, которое приходит через `xmldata`/`questmenu`, см. §6) |
| *(не описан)* **`advice`** | 🆕 новый mark | `parser.advice_action()` — всплывающая подсказка/совет (здание, число построек, артефакты) |
| *(не описан)* **`multiselect`** | 🆕 новый mark | Просто флаг `"0"`/другое → `debug.link.SWITHC` (переключатель мультивыбора построек, судя по имени) |

### 5.1. Внутренняя структура `helper` (подтверждение §7.4 серверного документа + новое)

`parser.helper_action()` — switch по `nodeName` вложенного элемента:

```
sendzondok, trainspyok, readmess, transres, arch, sellok, army,
new_armypower, armycreate, artbuild, buyok, army_train, artdepotdelete,
updatebattleoverview, delalliancemember, updatescanner, cabinet
```

Случай `"army"` **буквально подтверждает** реконструкцию §7.4 серверного
документа: атрибуты `id_army`, `inaction`, `lastcoord`, `lastspeed` на
корне; дочерний `<transfers>` со списком `<transer_to armyname=".." id_army=".."/>`
(да, `transer_to`, буквально без второй `s` — реальная опечатка в коде,
а не в этом документе); дочерний `<actions>` со списком `<possible id_action=".." actionname=".."/>`,
причём после `<possible/>` в коде явно ожидается **произвольный текстовый
узел** (`nodeName == null` ⇒ это TEXT-нода, читается в `status_`) — это
однозначно подтверждает странную конструкцию из реконструкции §7.4
серверного документа (`<possible .../>Армия атакует ...`, текст сразу
после самозакрывающегося тега, без обёртки).

### 5.2. `sysmsg` — буквальный формат разметки (найдено по протокольным дампам)

**client-capture: `refresh.txt`, `useraction.txt.full`.** Буквальные
захваченные ответы сервера показывают, что содержимое `<m>`/`<w>`/`<s>`
внутри `sysmsg` (см. таблицу выше) размечено не HTML и не BBCode, а
**фигурными псевдо-тегами**:
```
<m>{font color="#FF0000"}text{/font}</m>
```
То есть если подсветка цветом действительно применяется к системным
сообщениям (см. неподтверждённое предположение про `#A90101` выше), она,
похоже, приходит уже готовой строкой с сервера в виде `{font color="..."}
...{/font}` — а не как-то распознаётся клиентом по спецсимволу-маркеру.
Кто именно на клиенте разворачивает `{font}`/`{/font}` в реальную Flash-
разметку (`<font>`-теги `TextField.htmlText` или собственный
рендер-парсер) — не установлено, соответствующий код не входит в
разобранный набор файлов.

---

## 6. Диспетчер `xmldata` (`parser.xmldata_action`, физически в `parser.as`)

Важная деталь по расположению кода: диспетчер, который решает, какой из
парсеров `xmlparsers.as` вызвать по имени корневого тега внутри `xmldata`,
находится **в файле `parser.as`** (метод `xmldata_action`), а не в
`xmlparsers.as` — несмотря на то, что и имя, и вся терминология
("панель"/"меню") ближе к `xmlparsers.as`. Файл `xmlparsers.as` содержит
только сами парсеры-функции, вызываемые из этого диспетчера, плюс два
собственных статических метода-диспетчера рангом ниже (`logs_zonds_menu`,
`questMenuParser` и т.п. сами вызывают `debug.link.menu_mov.create_*`).

Полная таблица (тег корня `xmldata` → функция-парсер → предположительная
`PN_*`/`TL_*` панель на сервере, где угадывается по составу
`game-engine.md`/`protocol-client-server.md`):

| Корневой тег внутри `xmldata` | Функция в `xmlparsers.as` | Вероятная серверная панель |
|---|---|---|
| `address` | `init_interface_button` | (входит в состав ответа при инициализации интерфейса — ссылки, игры-баннеры) |
| `alliancemanagement` | `alliance_menu_parser` | `PN_AllianceManagement` |
| `allianceoverview` | `allianceoverview_menu_parser` | `PN_AllianceOverview` |
| `alliancesettings` | `alliancesetting_menu_parser` | `PN_AllianceSettings` |
| `allianceprivatepeace` | `allianceprivatepeace_menu_parser` | `PN_PeacePrivate`? |
| `alliancepeace` | `alliancepeace_menu_parser` | `PN_PeaceAlliance` |
| `alliancePeaceData` | `alliancepeacedata_menu_parser` | (детальные данные конкретного мира, camelCase-тег — необычно на фоне остальных lowercase) |
| `archeology` | `archeology_parser` | — |
| `archeology_artefact` | `artefact_depot_menu_parser` | — |
| `armycreation` | `army_create_menu` | — |
| `armyoverview` | `army_overview_menu` | `PN_ArmyOverview` |
| `armycalculator` | `army_calc_menu` | `PN_WarCalculator`? |
| `artefact_shop` | `artefact_shop_parser` | — |
| `arthint` | `artefact_info_menu` | (тег ответа совпадает с тегом запроса `<arthint/>`) |
| `auction` | `auction_menu_parser` | — |
| `battleoverview` | `battle_overview_menu` | `PN_BattleOverview` |
| `battlinfo` | `battle_show_menu` | (детали конкретного боя; заметьте — `battlinfo`, без второй `e`) |
| `buildingartefact` | `building_artefact_menu` | `PN_BuildingArtefact`/`PN_BuildingArtefact2` |
| `colonize` | `colonycenter_parser` | — |
| `colonizestatus` | `colonycenter_parser_update` | — |
| `combcomplex` | *(переадресуется в `parser.refresh_action` с добавленным `location="COMPLEX"`)* | см. §5, `refresh` |
| `createmessage` | `createNewMail` | — |
| `getbuildmenu` | `ParseListBuild` | (общий список вкладок/меню) |
| `getbuildinfo` | `ParseInfoBuild` | `PN_BuildingInfo` |
| `log` | `datawindow` | (окно лога — `<title>`/`<data>`/`<text>`) |
| `messageoverview` | `mailListParser` | `PN_MessageOverview`? |
| `message` | `messageParser` | — |
| `otpusk` | `otpusk_menu` | (режим отпуска/вакейшена — `UA_VacationStart` и т.п.) |
| `powershild` | `parser_powershild_menu` | — |
| `protectedfield` | `parser_protectshild_menu` | — |
| `questmenu` | `questMenuParser` | (разобран полностью, см. §1 файла) |
| `religion` | `relygion_parser_update` | — |
| `research` | `research_menu` | (разобран полностью — `<science>`/`<train>`/`<MSG>`, см. ниже) |
| `spylog` | `logs_zonds_menu` | `PN_SpyLogs` (совпадает с реальным `tab=` из §4.5) |
| `colonynavigate` | `colony_navigate` | `ColonyNavigatePanel` (совпадает с §4.5) |
| `table` | `repars_table_old_to_new` | (переходник "старый формат таблицы → новый") |
| `trade` | `trade_menu_parser` | `PN_TradePanel`/`TL_TradePanel` — **разобран полностью, буквально подтверждает реконструкцию §8** серверного документа (см. ниже) |
| `reincarnate` | `reincarnate_menu_parser` | — |
| `transferresource` | `transfer_menu_parser` | — |
| `terraforming` | `terraforming_menu` | — |
| `version` | *(`version_files.save` + `reqvestdata.get_localnames()`)* | версии модулей, см. §1 |
| `warunit` | `trainwarunit_parser` | — |
| `warcomplex` | `war_complex_menu` | — |
| `spying` | `spy_menu_parser` | — |
| `zondcreate` | `createzond` | — |
| `cabinet` | `DS_Classes.MN_Menu.MN_Cabinet.parserCabinetData.parse` | Внутриигровой веб-кабинет — **подтверждает** корневой тег `<cabinet>` из реконструкции §7.10 серверного документа |
| *(что угодно другое)* | `undefined_data` | Особая ветка "разбор без XML" — специальный формат отчёта о бое через `#`/`$`-разделители (используется вместе с `debug.link.keyRequestReportBattle`, выставляемым в `reqvestdata.getReportBattle`) |

### 6.1. `research_menu` — разбор ответа на команду `research`/навигацию по науке

Не описано в серверном документе вовсе. Корень `<questmenu>`... то есть
`<research>` содержит:
- `<science branchid=".." ln=".." ir=".." nextlev=".." lev=".." allow=".."
  bonusall=".." bonus=".." [C=".."] [enddate=".."]>` — по одному на ветку науки;
- один `<train turn=".." work=".." scientistmax=".." scientistallow=".."
  inprogress=".." W=".." N=".." F=".." C=".."/>` — параметры обучения учёных;
- ноль или более `<MSG id=".." titl=".." head=".." txt=".." desc=".."
  res=".." avail=".." price=".." status=".." rem=".."/>` — судя по всему,
  всплывающие описания/подсказки по конкретным веткам науки.

**Уточнение по итогам полного чтения `xmlparsers.as` (client-source,
3507 строк, см. §8):** фрагмент `<MSG>` — это **не специфичный для науки
формат**, а общий, переиспользуемый в нескольких разных меню диалог
подтверждения действия: он же разбирается в `ParseInfoBuild`
(здания) и `archeology_parser` (экспедиции), с тем же набором атрибутов
(`id`/`titl`/`head`/`txt`/`desc`/`res`/`avail`/`price`/`status`/`rem`).
То есть у сервера, судя по всему, есть один общий "диалог подтверждения
со стоимостью" (наука/здание/экспедиция), а не три независимых формата.

### 6.2. `trade` — буквальное подтверждение реконструкции §8 серверного документа

`trade_menu_parser` читает атрибуты корня: `turn`, `minquntity`
(опечатка — без второй `a`, как и предполагалось), `time2trade`, `notrend`,
`nonalog`, `nopersonaly`, `anonymous` — **дословно совпадает** с
реконструированным примером `<trade turn="1.5" time2trade="99"
minquntity="1" notrend="1" nonalog="1" nopersonaly="1" anonymous="1">`
из §8 серверного документа. Дочерние элементы:
- `<matrix><trades>CSV id-шников категорий</trades><reses>CSV записей
  `name:price:category_id`</reses><data>CSV ячеек матрицы вида
  `min-max-tax` (либо короче 3 частей — тогда `allow=false`)</data></matrix>`
  — подтверждает и структуру, и формат `<data>` (`"10-50-5,X"`-подобные
  строки) из реконструкции документа практически буквально.
- `<articles>` — товары/юниты/артефакты на продажу, разбор по 7 категориям
  `_loc11_[0..6]` (`cat="N_..."`). **По итогам полного чтения
  `trade_menu_parser` (client-source, `xmlparsers.as:1985`, см. §8)**: это
  разобрано до конца — категории с индексами 5-7 (`cat="5_..."`..`"7_..."`)
  относятся к юнитам рас Воранер/Лиенсу/Псолао соответственно, то есть
  биржа поддерживает кросс-расовую торговлю юнитами, не только ресурсами
  и артефактами.

---

## 7. Мостик к документу другого агента (окна `menu.lib.swf`)

Все функции-парсеры `xmlparsers.as` заканчиваются вызовом вида
`debug.link.menu_mov.create_XXX_menu(...)`/`update_XXX_menu(...)` —
то есть `menu_mov` это MovieClip (подгружаемый через
`game.getLibrary(4)` → `debug.link.loader.addLoader(..., debug.link.menu_mov, "RES")`,
см. `game.loadLib()`), который выступает точкой входа во всю библиотеку
конкретных окон.

**Важная методологическая находка**: ни одна из этих `create_XXX_menu`
функций **не найдена как метод класса** ни в одном из файлов
`decompiled_menu/scripts/__Packages/*.as` (проверено grep по нескольким
образцам — `create_battleoverview_menu`, `createAllianceMenu`,
`create_trade_menu` и т.п. не встречаются нигде как определения функций).
Значит, они определены **не в отдельных `.as`-классах**, а прямо на
таймлайне библиотеки меню (frame-скрипты `menu.lib.swf`) — то есть
JPEXS декомпилировал их не в `__Packages/`, а куда-то в структуру
таймлайна конкретных MovieClip-символов, что не входило в переданный
набор файлов. Практическое следствие для второго агента: точную привязку
"какая `create_XXX_menu`-функция создаёт экземпляр какого класса
(`trade_menu_class`, `army_overview_class`, …)" **нельзя установить только
по `__Packages`**-классам самих окон — нужно смотреть либо структуру
таймлайна библиотеки (вне текущего набора файлов), либо ориентироваться
по естественному сопоставлению имён (`create_battleoverview_menu` ↔
`battleoverviewclass.as`, `createAllianceMenu`/`alliance_menu_parser` ↔
`alliance_menu_class.as`, `trade_menu_parser` ↔ `trade_menu_class.as`,
`army_overview_menu` ↔ `army_overview_class.as` — совпадения по смыслу
названий, не проверено буквальной ссылкой в коде).

Таблица §6 выше (тег `xmldata` → функция-парсер) — это и есть тот самый
"мостик": она даёт второму агенту для каждого класса окна (который он
читает в `decompiled_menu`) соответствующий входной XML-формат и то,
какой командой (§4) этот XML был запрошен.

---

## 8. Сверка с настоящими (не декомпилированными) исходниками `SPHERE_client`

Этот раздел собирает находки отдельного прохода по реальным AS2-файлам из
`old-sphere/SPHERE_client/` (см. методологическую врезку в начале
документа), которые не укладывались точечно в существующие разделы выше.

### 8.1. `window.as` и `window_original.as` — не дубликат, а пара версий

**client-source.** `window.as` (5724 строки) — актуальная версия класса,
`window_original.as` (4561 строка) — более старый снимок того же класса
(аналогично паре `game.as`/`Копия game.as`, см. §8.3). `window.as`
добавляет по сравнению с `window_original.as`:
- `createBUYWindow`/`createBUYBONUSWindow`/`createPopupWindow` — окна
  покупки за донат/ресурсы;
- `createConfirmWindowTiny` — уменьшенная копия confirm-окна
  (**client-source: `window.as:4360-4427`**, полное `createConfirmWindow`
  — **`window.as:4280-4358`**);
- `addPagesFast`;
- режим "широких вкладок" через флаг `_level0.WIDETABS`
  (`add_icon_tabs_WIDE`/`add_icon_tabs2_WIDE`) — этого флага и функций
  нет в `window_original.as` вовсе.

Во всех проверенных окнах подтверждён паттерн, уже описанный в таблице
§1: `win_confirm.func_y`/`func_n`/`func_ok` в `createConfirmWindow`/
`createConfirmWindowTiny` только объявляются (bare-statement, без
присвоения) — реальное присвоение всегда делает вызывающий код уже после
того, как `createConfirmWindow(...)` вернул объект окна.

### 8.2. `parser.as` и `parser_for_old_refersh.as` — целый более ранний снимок диспетчера, не просто "про рефреш"

**client-source.** Несмотря на название, `parser_for_old_refersh.as`
(721 строка) — это не узкая функция про рефреш, а более ранний полный
снимок всего диспетчера ответов сервера, для сравнения с актуальным
`parser.as` (1630 строк):
- в `xmldata_action` заметно меньше кейсов (32 против 46 в актуальном
  файле — таблица дисптчера в §6 выше перечисляет ровно эти 46 именованных
  тегов + ветку по умолчанию `undefined_data`; теперь их полный список и
  количество **подтверждены на 100% реальным кодом**, `parser.as:1413-1620`,
  а не декомпиляцией);
- нет ветки `"s"` (spy) в `sysmsg_action` (см. §5.2 выше — эта ветка
  появилась позже);
- `local_names` заметно проще (не разбирает часть узлов, добавленных
  позднее);
- `outside_action` — заглушка в 1 строку, против 74 строк полноценной
  логики confirm-диалогов в актуальном `parser.as`.

### 8.3. `reqvestdata.as` (1077 строк) — новые находки после полной сверки

**client-source.**
- **Асимметрия `active_cell`.** `debug.link.active_cell` устанавливается
  по-разному двумя разными точками входа: `cell_click(id)` делает
  `Number(id)+1` (**`reqvestdata.as:167`**), а `cell_click_m(id)` —
  без сдвига (**`reqvestdata.as:182`**). При реализации нового клиента
  важно не унифицировать эти два пути бездумно — сдвиг на 1 в одном из
  них не случайность декомпиляции, а разница в реальном коде.
- **`trainunit` — общий тег на все типы юнитов.** Функции `train_unit`,
  `trainunit`, `trainscientists`, `trainarchelogist` шлют **один и тот же**
  тег `<trainunit .../>` — сервер не может отличить тип обучаемого юнита
  по XML-содержимому команды, только по тому, какое здание активно
  (атрибут `c`).
- **`<complexview>` — общий тег, два вызывающих места.** Шлётся и из
  `navigate("COMPLEX", ...)`, и из `NavigateComplex(...)`.
- **`<calctravel>` — общий тег, два разных набора атрибутов.** Используют
  и `calc_transfer_to_colony` (режимы `mode="TRANSFER"`/`"TRANSFERRES"`),
  и `calc_army_action` (`mode` = один из режимов скорости/цели) —
  различаются только значением `mode`, набор остальных атрибутов у двух
  вызывающих разный.
- **`cancelactivatebuild()` подтверждённо шлёт `<cancelactivate>`**
  (без суффикса `build`) — расхождение имени функции и тега (см.
  «Открытые вопросы» №2 ниже) теперь подтверждено реальным кодом, а не
  только декомпиляцией; причина расхождения по-прежнему не объяснена ни
  в одном комментарии в коде.

### 8.4. `game.as`/`Копия game.as` и `sota.as`/`Копия sota.as` — старые снимки, не дубли

**client-source.** `game.as` (2694 строки) vs `Копия game.as` (2658
строк, снимок 2010 года) — старый снимок, отличия:
- добавлена телеметрия — десятки новых вызовов
  `debug.link.sending_lc.send(..., "getTRACE", ...)`, которых нет в старом
  снимке;
- новая ветка соцсети **"POP"** (немецкий портал): актуальный `game.as`
  вместо булева `_level0.VKONTAKTE` (как было в старом снимке) использует
  `_level0.socialNet == "VK" | "POP"`, где `"POP"` — немецкая версия
  (`destinysphere.de`) со своим набором иконок (`iconsempty`), ссылкой на
  помощь (`http://wiki.destinysphere.de/index.php/Hauptseite`, подпись
  `"WIKI"`) и отдельными константами разметки. Это тот самый "немецкий
  проект", уже упомянутый в [history-and-background.md](history-and-background.md)
  — здесь он впервые подтверждён буквально в клиентском коде;
- кнопка помощи переименована из `main_help_btn` в
  `debug.link.interface_mov.FUCKEN_HELP`;
- `loadLib` получил двухветочную логику "локализация уже загружена / ещё
  нет", которой нет в старом снимке.

Аналогично `sota.as` (1712 строк) vs `Копия sota.as` (1455 строк) — старый
снимок без VK-логина в `createComplex`/`fillComplex`, без динамического
масштаба построек (`buildRescale`), с ещё активной (не отключённой)
загрузкой ландшафта из внешнего `grounds.swf`.

### 8.5. `contact.as` — подтверждена исключительность VK-интеграции

**client-source, `contact.as` (660 строк) целиком.** Полный grep по файлу
подтверждает отсутствие любых упоминаний других соцсетей (Одноклассники,
mail.ru, Facebook) и отсутствие функций wall-posting/invite/share — файл
целиком посвящён логину, профилям/друзьям/балансу и рекламе ВКонтакте,
плюс MD5-подпись запросов к VK API. Подтверждает вывод из таблицы §1
(строка `contact.as`), полученный ранее по декомпилированной версии.

### 8.6. `xmlparsers.as` (3507 строк) прочитан полностью

**client-source.** В отличие от декомпилированного анализа (доступны были
только первые ~1300 строк, см. врезку в начале документа), реальный файл
прочитан целиком. Подтверждает и дополняет таблицу диспетчера §6 —
уточнены номера строк ряда функций: `army_overview_menu`(1461),
`auction_menu_parser`(1803), `colonycenter_parser`/`_update`(2805,2813),
`mailListParser`(2990), `messageParser`(3063), `terraforming_menu`(1247),
`war_complex_menu`(324), `createzond`(3434),
`alliancepeace_menu_parser`(731)+`parseAllianceDataForPeace`(850),
`alliancepeacedata_menu_parser`(658),
`allianceprivatepeace_menu_parser`(610), `archeology_parser`(2842),
`ParseInfoBuild`/`ParseListBuild`(2577,2629), `reincarnate_menu_parser`(2436),
`artefact_shop_parser`(1600), `trade_menu_parser`(1985, см. §6.2 — теперь
дочитан полностью), `undefined_data`(999), `otpusk_menu`(3415),
`repars_table_old_to_new`/`table_create`(3321,3396). Диспетчера **внутри**
файла нет — каждая функция вызывается извне, из `parser.as:xmldata_action`
(таблица §6).

### 8.7. `<swfloader>`/`</swloader>` — баг подтверждён буквально в захваченных данных, не декомпилятором

**client-capture: `outside.txt`, `outsidehtml.txt`.** Опечатка в
закрывающем теге — `</swloader>` (без второй `f`) при открывающем
`<swfloader ...>` — подтверждена **дважды** в реальных захваченных
ответах сервера, то есть это не артефакт декомпиляции и не гипотеза: это
буквально то, что сервер посылает по проводу. `outsidehtml.txt`
дополнительно показал ранее не задокументированные атрибуты `<swfloader>`:
`override`, `res`, `speed`, `destination`, `id_army` — и что `<txt>` внутри
— CDATA-дочерний элемент, а не атрибут (см. также
`protocol-client-server.md` §9).

### 8.8. Серверная сторона VK-логина — отдельный проход по `schema.sql` и PHP

**Сервер: `schema.sql`, `old-sphere/ds/classes/{GE,DS}/*.php`, `old-sphere/
ds/config/settings.php`.** Клиентская сторона VK-интеграции (`contact.as`,
§8.5) была разобрана без привязки к серверу. Отдельным проходом это
дополнено по свежему дампу схемы БД (`schema.sql`, вне `git`) и PHP-классам
игрового движка — чтобы понять, куда клиент реально шлёт данные VK-логина.

**Главная находка: у ВКонтакте нет ничего общего с таблицей `ds_oauthinfo`.**
`ds_oauthinfo` (`schema.sql:16870-16875`: `open_key`, `secret_key`,
`token_username`, `game_uid`) по именам колонок выглядит как классическое
хранилище OAuth 1.0a-токенов (`open_key`/`secret_key` ~ `oauth_token`/
`oauth_token_secret`) — и это действительно так, но её реальные потребители
(`GE_SearchStart::GetUserIdByToken`/`GetUserByToken`/`GetUserIdByMetaUALogin`,
`GE_SearchStart.class.php:36-67`) обслуживают только две **другие**
интеграции:
- **Klase.lt** (литовский портал) — `GE_Login.class.php:143-184`
  (`_checkKlaseLogin()`): классический OAuth 1.0a, `GetToken($callback_url)`/
  `GetUserData()` (глобальные функции, не входящие в разобранный набор
  классов — не найдены нигде в архиве), куки `oauth_token`/
  `oauth_token_secret`, затем `new GE_KlaseLogin($token)`. **Класс
  `GE_KlaseLogin` нигде не найден в этом архиве** (`ds/classes/GE/
  GE_KlaseLogin.class.php` отсутствует, хотя на него есть прямая ссылка в
  коде) — не домысливать его поведение, известно только, что он принимает
  `$token` с полями `->key`/`->secret` (по сигнатуре `GE_SearchStart::
  GetUserByToken`, которая их читает).
- **Meta.ua** (украинский портал) — `GE_MetaUALogin.class.php`, вызывает
  `GE_SearchStart::GetUserIdByMetaUALogin($userinfo)` (поиск по
  `token_username`).

VKontakte в этой части схемы не участвует вообще.

**Как реально привязан VK-аккаунт.** У `ds_user` есть отдельная колонка
`id_user_vkontakte` — числовой VK ID прямо на записи пользователя (не в
`ds_oauthinfo`). Три метода в `DS_User.class.php`:
- `GetByIDVkontakte($id)` (`DS_User.class.php:574-594`) — `id_user` по VK ID;
- `GetIDVkontakteByUserId($id)` (`:635-655`) — обратный поиск;
- `InsertUserVkontakte($idVkontakte, $userName)` (`:657-699`) — создаёт
  нового `ds_user` с `id_user_vkontakte` и **без пароля и email**
  (`Password`/`Email` = `NULL`) — то есть это полноценная альтернативная
  регистрация «через ВК», а не просто привязка к уже существующему
  аккаунту.

Кто это использует:
- `GE_ExecGetPremiumPresent::GetPremPresentVk($userIdVkontakte)`
  (`GE_ExecGetPremiumPresent.class.php:104-130`) — выдаёт премиум-подарок
  владельцу привязанного VK-аккаунта. **Ни одного вызова этого метода не
  найдено нигде в архиве** — похоже на мёртвый код или остаток
  promo-кампании конкретного VK-приложения; не исключено, что вызывался
  из отсутствующего в архиве `authorization.php` (см. ниже).
- `UA_ComplexView.class.php:414-417` — при `VERSION_NAME == 'VKONTAKTE'`
  добавляет `combData['VKUID'] = DS_User::GetIDVkontakteByUserId(...)` в
  XML-ответ `combcomplex`/`refresh`. **Это закрывает открытый вопрос
  документа о параметре `vk` в `sota.createComplex(ring, complex, vk)`**
  (§8.4) — `vk` и есть `VKUID`, положенный сервером в ответ.
- Админка: `Admin/controls/Players/{UserInfo,EditUser/UserInfo}/index.php`
  упоминают `Vkontakte` — судя по всему, отображают привязанный VK ID в
  карточке игрока (построчно не проверялось, вне периметра задачи).

**`VERSION_NAME == 'VKONTAKTE'` — отдельная, полноценная версия сайта.**
Наряду с `RU_UNLIM`/`LIMITED`/`RU_CLASSIC` (объявлены в
`ds/config/settings.php:101,138,315`) и `LOCAL`/`LITHUANIA`/`TEST`/
`DE_TEMP` (перечислены в `ds/chatAuthorize.php`, см. раздел про чат в
[admin-panel.md](admin-panel.md) §2.4.1), в коде есть отдельная ветка
`VERSION_NAME == 'VKONTAKTE'` (собственный `define()`-блок этой версии в
снимке `settings.php` из этого архива отсутствует — видны только точки
потребления константы), которая меняет игровые правила под VK-аудиторию:
- email не обязателен при регистрации (`DE_User.class.php:112`);
- приватный мир/сообщения адресуются по координатам базы, а не по
  игровому нику (`UA_PrivatePeace.class.php:40-107`) — потому что у
  VK-пользователя может не быть обычного логина;
- письма несуществующим игровым логинам молча отбрасываются вместо ошибки
  (`UA_SendMessage.class.php:414-417`);
- «мирные часы» перед атакой обнуляются (`GL_CanAttackComb.class.php:
  369-372`);
- письмо-напоминание об неактивности перед авто-удалением аккаунта не
  отправляется (`SU_DeleteDeadUsers.class.php:167`).

**Открытый вопрос, а не находка: сама точка входа не найдена.**
`SPHERE_client/DS_KONTAKT_MODULE/VKGATE.as:9` хардкодит `mainurl =
"http://vk.destinysphere.ru/ds/authorization.php"` — это, судя по всему,
и есть настоящий обработчик входа, который проверяет MD5-подпись VK API
(см. §8.5) и вызывает `DS_User::InsertUserVkontakte`/`GetByIDVkontakte` +
стартует сессию. **Файла `ds/authorization.php` нет нигде в этом архиве**
(проверено поиском по всему репозиторию) — как и класс `GE_KlaseLogin`
выше, это не домысливается, а честно отсутствует: либо не попал в
переданный дамп, либо жил только на инфраструктуре `vk.destinysphere.ru`,
которая не архивирована в этом репозитории.

---

## 9. Открытые вопросы

1. **Где реализован `debug.link`?** Ссылка используется буквально
   в каждом файле (`debug.link.interface_mov`, `debug.link.menu_mov`,
   `debug.link.setting_mov`, `debug.link.ck`, …), но нигде не объявлена
   как класс среди переданных 13+13 файлов. Скорее всего это либо код на
   главном таймлайне (не экспортированный как `__Packages`-класс), либо
   отдельный класс, не входящий в переданный список. Без него нельзя до
   конца проверить, как в реальности выглядит хранилище настроек
   (`setting_mov`) и как физически устроен previously упомянутый
   "да/нет" резолвер для `outside`-подтверждений (`createConfirmWindow`,
   реально определённый в `window.as`, но вызывающий код кнопки "да" —
   нет).
2. **`cancelactivatebuild` vs `cancelactivate`.** PHP `$abpa` в
   `UserAction.class.php` содержит буквальный тег `cancelactivatebuild`
   (см. §5.2 серверного документа), но клиентская функция
   `reqvestdata.cancelactivatebuild()` реально шлёт тег `cancelactivate`.
   **Расхождение теперь подтверждено дважды** — и декомпиляцией, и
   реальным `reqvestdata.as` (client-source, см. §8.3) — то есть это не
   артефакт декомпилятора. Причина по-прежнему не установлена: нужно либо
   сверить с `ds_useractiontype` (недоступна), либо признать, что данная
   конкретная команда не входит в защитный список `$abpa` на сервере
   (т.е. каждый вызов лишний раз сбрасывает `allow_buildings`/`allow_units`
   кеш) — это не баг клиент-сервер связи per se, а просто несовпадение с
   конкретным списком оптимизации. Ни в реальном коде клиента, ни в
   комментариях к нему причина расхождения имени функции и тега не
   объясняется.
3. **Регистр `getBattleInfo` vs `getbattleinfo`.** Аналогично — клиент
   шлёт camelCase, PHP `$abpa`-литерал — lowercase. Если сравнение в PHP
   строгое по регистру (стандартное поведение `in_array()`), эта команда
   тоже не подпадает под `$abpa`-оптимизацию несмотря на то, что
   формально числится в списке (буквы разного регистра — это разные
   строки).
4. **Механизм подтверждения атаки/действия** — сосуществуют минимум три
   разных пути (§4.3): переотправка серверного `<answer>` "как есть",
   отдельный тег `attack_confirm`, отдельный тег `confirmAction id=".."`.
   Не установлено, как они распределены по UI-сценариям и не дублируют
   ли они друг друга.
5. **`clickonallyicon`** — клиент шлёт атрибуты `player`+`alliance`,
   а не атрибут `id` (который PHP-код `clientDirectLinks` подставляет в
   `art={id}`-подобный плейсхолдер согласно §10 серверного документа —
   правда, для `clickonallyicon` там плейсхолдера `{id}` в URL и не было,
   так что подстановка `get_attribute("id")` в PHP-обработчике этой ветки
   может быть просто мёртвым кодом для этого конкретного тега — не
   проверялось повторным чтением PHP).
6. **`get_sota()`/`get_data()` в `reqvestdata.as`** строят не-XML
   литеральные строки `"get_sota"`/`"get_data"` (по аналогии с `"logout"`),
   но ни разу не вызываются нигде в обоих декомпилированных деревьях —
   похоже на мёртвый код, но не 100% исключено, что они вызываются из
   кода вне переданного набора файлов (например, с таймлайна).
7. **`popuphelp`/`typeforfilter`/`quest` (marks)** разбираются в
   `parser.as`, но результат разбора никуда не передаётся в прочитанном
   коде (переменная создаётся и не используется дальше) — либо это
   мёртвый/недоделанный код, либо результат используется процедурно
   внутри самой функции способом, который декомпилятор не смог красиво
   восстановить (маловероятно, но не исключено).
8. **Полный список функций `getbuildmenu`-вызовов с `tab=`** — в
   `reqvestdata.as` разобраны не все вызывающие места (`menu_call(tab)`/
   `call_tabs(tab_call)` принимают `tab` параметром снаружи) — то есть
   полный список реальных значений `tab` требует чтения кода конкретных
   окон в `decompiled_menu` (вне периметра этой задачи, у второго
   агента).
9. ~~**`xmlparsers.as` прочитан не полностью**~~ — **закрыто.** Реальный
   файл (client-source, 3507 строк) теперь прочитан полностью, все
   парсеры панелей разобраны и внесены в таблицу §6 с точными номерами
   строк (см. §8.6). Декомпилированный анализ (первые ~1300 строк) был
   верным в том, что успел разобрать, но неполным по охвату.
10. **Обработчик кнопки "да" в `outside`-подтверждении** (передача
    `customAnswer` обратно на сервер) не найден ни в одном
    `__Packages`-классе — см. вопрос №1. Нельзя стопроцентно подтвердить,
    что клиент пересылает готовую команду сервера буквально без изменений
    (это лишь наиболее вероятная интерпретация по совпадению структуры
    данных). Реальный `reqvestdata.as`/`parser.as` этот вопрос тоже не
    закрывают — код кнопки, судя по всему, действительно живёт на
    таймлайне, а не в разобранных классах.
11. **`renameArmy` — статус понижен до "не найдено".** Ранее (по
    декомпилированному анализу) числилась как подтверждённая функция
    `reqvestdata.renameArmy(id, newName)` → `<renameArmy id=".." newName=".." />`.
    Полный grep по подстроке `"rename"` в реальном `reqvestdata.as`
    (client-source) даёт ноль совпадений — функции в этом файле нет.
    Возможные объяснения: (а) неверная атрибуция decompiled-анализом
    (перепутан класс/файл), (б) функция реально существует, но не в
    `reqvestdata.as` — например, на таймлайне или в файле, не входившем в
    периметр ни одного из двух проходов. Не переносить в новый клиент без
    отдельной проверки происхождения.
12. ~~Реальное имя сессионной cookie веб-логина по-прежнему не
    подтверждено.~~ — **резолвнуто живым тестом протокола, 2026-09-04**
    (см. §2.1 выше): `session_name()` = **`PHPSESSID`** (стандартное имя
    PHP по умолчанию). Гипотеза про `"SIDIX"` не подтвердилась. Механизм
    `turing=` из дампов логина (`protocol-client-server.md` §3.1) остаётся
    отдельным, не связанным с этим вопросом.
13. **Реальный обработчик VK-логина (`ds/authorization.php`) и класс
    `GE_KlaseLogin` не найдены в архиве** (см. §8.8) — на оба есть прямые
    ссылки в клиентском (`VKGATE.as`) и серверном (`GE_Login.class.php`)
    коде соответственно, но самих файлов в `old-sphere/` нет. Это значит,
    что финальный шаг VK-логина — проверка MD5-подписи VK API и вызов
    `DS_User::InsertUserVkontakte`/`GetByIDVkontakte` — восстановлен только
    по косвенным уликам (какие методы существуют и что они делают), а не
    прочитан напрямую. Не переносить в новый клиент/сервер без явной
    отдельной реализации этого шага с нуля.
