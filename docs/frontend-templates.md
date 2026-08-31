# Фронтенд: Flash-клиент, mini-ds, WAP, шаблоны и CSS

Документ описывает клиентскую часть Destiny Sphere: Flash-клиент игры и способ его
встраивания в HTML, каталоги `mini-ds/` и `wap/`, три независимые друг от друга
системы шаблонов (`ds/templates/`, портальные `templates/`, обычные PHP-страницы) и
набор CSS-файлов разных версий интерфейса.

Все пути ниже — относительно `old-sphere/`, если не указано иное. Смежные темы уже
разобраны в других документах: серверная архитектура миров и `VERSION_NAME` —
[multi-world-architecture.md](multi-world-architecture.md), CMS-слой `sys/` и
`sys_dyn_components/` (движок `FastTemplate`/`PageManager`, локализация `local()`) —
[portal-cms.md](portal-cms.md), состояние живых сайтов — [external-sites.md](external-sites.md).

---

## 1. Клиент игры: Flash

### 1.1. Где физически лежит клиент — важное уточнение

Задание предполагало, что сам SWF-клиент и `detect_player.swf` лежат в `flash/`.
Проверка показала обратное:

- **`old-sphere/flash/`** (535 МБ) — это **не клиент игры**, а дистрибутивы
  **Adobe Flash Player** и вспомогательные файлы для его установки:
  `flashplayer32_0r0_371_win.exe`/`.msi`, `..._winax.exe`/`.msi` (ActiveX-версия для IE),
  `..._mac*.dmg`, `..._linux*.tar.gz`, деинсталляторы, плюс `flash_linux.zip`/`flash_mac.zip`.
  Версия **32.0.0.371** — это последняя официальная сборка Flash Player перед
  прекращением поддержки Adobe в конце 2020 года, то есть эти инсталляторы раздавались
  игрокам вплоть до самого конца жизни технологии.
  - `chrome_ds.reg` — патч реестра Windows, добавляющий домены игры
    (`http://ds-game.ru`, `http://dsga.me`, `http://ds-game.su`, `http://destinysphere.ru`)
    в политику Chromium `PluginsAllowedForUrls`, — стандартный обходной приём,
    которым сайты с Flash-контентом позволяли посетителям держать NPAPI-плагин
    включённым в Chrome после того, как браузер начал блокировать Flash по умолчанию.
  - `chrome_ds_flash.exe` — сопутствующий инсталлятор/скрипт (бинарник не
    распаковывался, только определён по имени и соседству с `.reg`).
- **Сам клиент лежит в `ds/flash/`** (~11 МБ, ~150 файлов `*.swf`), а
  `DS_WORLD_OPTIONS.swf` и `detect_player.swf` — прямо в `ds/` (`ds/DS_WORLD_OPTIONS.swf`,
  `ds/detect_player.swf`; в `ds/flash/` лежит их копия `ds/flash/detect_player.swf`).

Главный исполняемый файл — **`ds/flash/ds3.swf`** (в раздаваемом виде —
`ds/flash/ds3-<version>.swf`, см. §1.3). Остальные ~150 SWF в `ds/flash/` — это
подгружаемые самим клиентом библиотеки-модули: интерфейс (`interface.swf`,
`bginterface*.swf`, `menu.lib.swf`, `buttons.lib.swf`, `buttons_rus.lib.swf`),
графика построек/юнитов по расам и уровням (`build_bio.lib.swf`, `build_psi.lib.swf`,
`build_tech.lib.swf`, `war_units.lib.swf`), иконки (`icons*.swf`, `iconsg.swf`,
`iconsi.swf`, `iconsr.swf`), звук (`sound_bkg.lib.swf`), обучающие подсказки для
новичков (`NUB_HELP_LEVEL_1.swf` … `NUB_HELP_LEVEL_26.swf`), встроенный видеоплеер
(`playermp.swf`), окна алертов (`alert.swf`, `dialog_alert.swf`), интро (`intro.swf`),
рекламные ролики Premium/кредитов (`600x400xpro_DS_CREDIT.swf`,
`600x400xpro_DS_PREMIUM.swf`, `600x400xpro_DSU_CREDIT.swf`, `600x400xpro_DSU_PREMIUM.swf`)
и подкаталог `ds/flash/code/` (`code.swf`, `code_read.swf`, `mov.swf`, `mc/path.swf`,
`mc/swf.swf`).

### 1.2. Как HTML встраивает Flash

Кода, генерирующего `<object>`/`<embed>`, найдено три независимых места:

**`ds/flash_code.php`** — основной генератор тега для игрового клиента. Вызывается
из `ds/game.php` и `ds/index.php` с параметрами `$width`/`$height`/`$mode`/`$location`/`$comb`.
Формирует и `<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="...swflash.cab#version=6,0,65,0">`
(для IE/ActiveX), и вложенный `<embed src="..." type="application/x-shockwave-flash">`
(для остальных браузеров через NPAPI), оба указывают на `flash/ds3-<version>.swf`.
В `FlashVars` передаётся весь стартовый контекст клиента: `vers`, `location`, `lang`
(из `$_SESSION['shortname']`), `id_comb`, `race`, `windowKey`, `mode`,
`rl=ds_text_logo.swf`, `ck` (client key), `VERS` (константа `VERSION_NAME`,
см. multi-world-architecture.md), `chatServer=noname.gamespace.ru`, опционально `sport`,
и, если есть сессия, — имя и значение сессионной куки. Заявленная в `codebase` минимальная
версия (6.0) — это версия для авто-установки ActiveX-контрола, а не реальное минимальное
требование к клиенту (см. §1.4 — фактически проверяется Flash 7/8+).

**`ds/flash.php`** — отдельный маленький скрипт, который просто отдаёт
`ds/flash/ds3.swf` через `readfile()` с заголовками `Cache-Control: max-age=525600` и
`Content-Type: application/octet-stream` — то есть выступает прокси/CDN-подобной
раздачей самого SWF с годовым кэшированием на клиенте, в обход прямой отдачи файла
веб-сервером.

**`sys_dyn_components/flash_wnd.php`** и **`sys_dyn_components/top_bannerl.php`** —
не игровой клиент, а декоративные Flash-баннеры на портале (см. `docs/portal-cms.md`,
таблица §5): `flash_wnd.php` случайно выбирает один из двух роликов
(`img/graph/main_page_flash1.swf`/`2.swf`, 195×114) через `<object codebase="...#version=7,0,0,0">`.
Аналогично **`templates/Publication/vitrina.php`** (портальная домашняя страница,
рендерится через FastTemplate, см. §4.4) напрямую вшивает ещё два декоративных ролика:
`../img/flash/header.swf` (500×86) и `img/v4/swf/header_01.swf` (860×88) — то есть Flash
на портале используется не только для самой игры, но и как элемент оформления шапки сайта.

### 1.3. Версионирование SWF-модулей

`ds/flash/version.ini` — плоский INI-файл вида:

```ini
[localdata]
version = 118
timestamp = 1459517519

[ds3.swf]
version = 79
timestamp = 1459511002

[buttons.lib.swf]
version = 7
timestamp = 1215083270
```

Класс `ds/classes/SU/SU_FlashVersions.class.php` читает этот файл (`getVersions()` →
`parse_ini_file()`) и по нему строит переименование `<имя>-<version>.<ext>`
(`makeNewName()`) — этим объясняются встречающиеся в `ds/flash/` файлы с суффиксом
номера версии (`ds3-79.swf`, `artefact-20.lib.swf`, `buttons_rus-111.lib.swf` и т.п.,
рядом с файлами без суффикса). `ds/version.php` отдаёт эти версии клиенту в виде XML
(`SU_FlashVersions::makeXML()`), а `ds/flash_code.php` подставляет актуальный номер
версии `ds3.swf` прямо в `<param name="movie">`/`<embed src>`. Это классическая
cache-busting-схема для «горячего» обновления клиентских модулей без пересборки
единого большого SWF.

### 1.4. Определение версии Flash Player и совместимости браузера

`ds/detect_player.php` + Smarty-шаблон `ds/templates/classic/detect_player.tpl` (и
одноимённый в `ds/templates/`) реализуют страницу диагностики: встроенный
`detect_player.swf` определяет установленную версию Flash Player и браузер
пользователя и передаёт результат через `?set_swf=...`. Класс `DetectSystem` в
`detect_player.php` сверяет пару (браузер, версия) с жёстко зашитой матрицей
совместимости:

- IE 5/6/7 — ОК; Flash < 6 — критическая ошибка, Flash 6 — «сниженная
  совместимость», Flash 7+ — ОК.
- Mozilla/Netscape/Opera — сразу помечаются как «сниженная совместимость» на уровне
  браузера (в свиче `case "Opera"` результат всегда `Bad_compatibility`, независимо от
  версии), Flash < 7/8 — ошибка разной степени.
- Любой другой браузер (`default`) — везде «Bad_compatibility».
- Дополнительно проверяются: ОС = Windows, поддержка таблиц/JavaScript/iframe/cookies
  (через встроенный PHP `get_browser()`).

Результат подставляется в шаблон через `$tpl->assign("CONTENT", [...])`, с
автоматическим редиректом на игру (`meta refresh`, `$tpl->assign("REFRESH", 0 или 10)`
в зависимости от наличия ошибок) — то есть это не блокирующая страница, а
информационное предупреждение, после которого пользователя всё равно пускают в игру.

### 1.5. Находка: эмуляция Flash через Ruffle (модернизация клиента)

В нескольких местах кода обнаружена **более поздняя правка**, добавляющая загрузку
[Ruffle](https://ruffle.rs/) — open-source эмулятора Flash Player на Rust/WebAssembly,
позволяющего проигрывать SWF в современных браузерах без установленного Flash Player
(который Adobe отключила повсеместно с 12 января 2021 года):

- `ds/index.php:175` — `<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>`
  в `<head>` главной игровой страницы (эта строка не зависит от `TEMPLATES_PATH` и
  выполняется для всех версий/миров).
- `ds/templates/classic/index_login.tpl:28` и
  `ds/templates/classic/index_start_new.tpl:13` — тот же тег, но **только в
  `templates/classic/`**.

Важно: тот же тег **не найден** в `ds/templates/index_login.tpl` и
`ds/templates/index_start_new.tpl` (корневых, без `classic/`). По `TEMPLATES_PATH`
(см. §4.2) `classic/`-версия обслуживает `VERSION_NAME = RU_CLASSIC` (destinysphere.ru)
и `LIMITED`/Pulsar (ds-game.su), а корневая — `RU_UNLIM` (ds-game.ru). То есть патч на
Ruffle внесён на страницы логина/старта только для Classic/Pulsar, но не для
Unlimited — похоже на не до конца докатанное обновление (см. «Открытые вопросы»).
Ruffle подтверждён и в скомпилированном Smarty-кэше
(`CacheFiles/*.file.index_start_new.tpl.php`, `CacheFiles/*.file.index_login.tpl.php`),
то есть правка реально компилировалась и отдавалась, а не осталась черновиком.

### 1.6. Заявленные / фактические требования

Согласно `docs/history-and-background.md` (по статье Википедии) официально заявлено:
Internet Explorer + Flash Player 8.0.25.0+, разрешение экрана 1024×768. Это согласуется
с кодом: `codebase=...#version=6,0,65,0` в `flash_code.php` — это минимум для
автоустановки ActiveX-контрола, а не для игры; реальная проверка в `detect_player.php`
уже требует Flash 7 (IE) / 7–8 (остальные браузеры) для «полной совместимости». Ширина
клиента в коде — `840×500` (фиксированный режим/старт, `ds/game.php`,
`ds/index.php`) либо `100%`/`96%` (если у пользователя включён режим
`$_SESSION['scaling'] != 'f'`).

---

## 2. `mini-ds/` — вероятно НЕ облегчённая версия клиента, а мини-игры

Задание предполагало, что `mini-ds/` — облегчённая/мобильная версия клиента для
слабых ПК. Прямых подтверждений этому в коде не найдено; напротив, единственная
найденная точка подключения указывает на другое назначение.

Содержимое (8,3 МБ, **только 5 файлов, без единого `.php`/`.html`-обвязчика**):

| Файл | Размер |
|---|---|
| `orcs.swf` | 3,7 МБ |
| `EvilWarrior2.swf` | 1,6 МБ |
| `MageTroll.swf` | 1,2 МБ |
| `Sculls.swf` | 1,1 МБ |
| `minirts.swf` | 880 КБ |

Единственное найденное в коде упоминание — **`ds/address.php`**, скрипт, отдающий
Flash-клиенту (`ds3.swf`) XML-конфигурацию с адресами разных разделов. В этом XML есть
блок `<game>`:

```xml
<game>
  <item name="EVILWARRIOR" url="http://www.destinysphere.ru/mini-ds/EvilWarrior2.swf" width="800" height="600"/>
  <item name="BLACKJACK" url="/ds/minigames/bj/bj.php" width="600" height="500"/>
</game>
```

Это выглядит как список **отдельных мини-игр**, доступных из меню самого игрового
клиента (соседствует с блэкджеком из `ds/minigames/bj/`, у которого тоже есть
самостоятельная точка входа `bj.php` + `layOut4.swf`), а не как альтернативный
облегчённый клиент основной игры. `EvilWarrior2.swf` подключён явно; для `orcs.swf`,
`MageTroll.swf`, `Sculls.swf`, `minirts.swf` **не найдено ни одной ссылки** нигде в
проверенном дереве кода (PHP/HTML/шаблоны) — либо они подключались из данных в БД
(не входит в статический анализ), либо это неиспользуемые/устаревшие ассеты одного
и того же набора мини-игр. Названия (`minirts` = вероятно мини-стратегия в реальном
времени, `MageTroll`/`Sculls`/`orcs` — похоже на аркадные/файтинг-игры про
фэнтезийных существ) не пересекаются с игровым лором Destiny Sphere (расы Воранеры/
Лиенсу/Псолао, см. `docs/history-and-background.md`), что подтверждает версию про
самостоятельные бонусные мини-игры, а не урезанный клиент.

**Важная оговорка про названия ссылок на живых сайтах**: `docs/external-sites.md`
отмечает на ds-game.ru отдельную ссылку на «мобильную версию» — по смыслу это,
вероятнее, соответствует каталогу `wap/` (см. §3), а не `mini-ds/`. Однозначно
сопоставить label на живом сайте с конкретным каталогом кода без доступа к текущей
вёрстке сайта нельзя — отмечено в «Открытых вопросах».

---

## 3. `wap/` — WAP-версия для мобильных телефонов

Каталог занимает всего 2 КБ и содержит **только два файла**:

- `wap/.htaccess` — `php_flag register_globals off`, кастомная страница ошибки 403
  (`ErrorDocument 403 .../?p=error_403`), закомментированные строки `allow from`/`deny from all`
  (следы IP-ограничения доступа, отключённого в текущем виде).
- `wap/login.php` — 7 строк: подключает `ds/Main.php` и `GameDown.inc.php`, затем
  `$login = new WAP_Login($_POST);`.

Класс **`WAP_Login` нигде не определён** в проверенном дереве кода (полнотекстовый
поиск `class WAP_Login` по всему `old-sphere/` не дал совпадений) — рабочий на данный
момент срез кода **не содержит** тела WAP-модуля, только точку входа. Возможно, класс
подключался автозагрузчиком по конвенции имён из каталога, отсутствующего в этом
чек-ауте, либо был удалён при переносе/чистке репозитория.

Единственный сохранившийся артефакт логики — Smarty-шаблон
**`ds/templates/wap/login.tpl`**:

```smarty
<form action="login.php" method="POST">
    Login: <input type="text" name="login"><br>
    Password: <input type="password" name="password"><br>
    World: <select name="world">{foreach from=$WORLDS item=WORLD key=ID}<option value={$ID}>{$WORLD}{/foreach}</select><br>
    <input type="submit" value="enter">
</form>
```

Это самая обычная HTML-форма (а не WML, как можно было бы ожидать от классического
WAP 1.x) — то есть, по всей видимости, речь об упрощённой HTML/xHTML-MP странице для
браузеров старых мобильных телефонов, а не о полноценном WML-стеке. Дальше страницы
логина код в этом чек-ауте не заходит: что происходит после успешного входа (открывается
ли какой-то WAP-клиент игры, аналог `ds3.swf`, или просто редирект на обычную
`ds/index.php`) — не установлено, соответствующий код не найден.

---

## 4. Система шаблонов

В кодовой базе одновременно живут **три независимых механизма рендеринга HTML**,
не связанных друг с другом:

| Механизм | Где используется | Примеры входа |
|---|---|---|
| **Smarty** (`ds/include/Smarty/`, полноценный движок с `sysplugins`) | Обвязка вокруг игры: логин/регистрация/рейтинги/кабинет/детект плеера | `ds/detect_player.php`, `ds/ranking.php`, `ds/reg_wizard.php`, `ds/index_registration.php` и др. (полный список — 25+ файлов, см. ниже) |
| **FastTemplate** (`sys/include/classes/BaseClasses/FastTemplate.class.php`, старый, до-Smarty движок с плейсхолдерами `{ИМЯ}` и блоками `<!-- BEGIN DYNAMIC BLOCK: x -->`) | Портал: новости/публикации/справочники построек и юнитов | `templates/Publication/*.tpl.html`, `templates/Buildings/Buildings.tpl.html`, `templates/Units/*.tpl.html`, `templates/message/*` — подробный разбор в `docs/portal-cms.md` |
| **Обычный PHP + inline HTML** (без шаблонизатора вообще) | Сама игровая страница-контейнер для Flash-клиента | `ds/game.php`, `ds/index.php` — HTML собирается напрямую через `?>`/`<?`, без `$tpl` |

Кроме того, есть ещё **два отдельных экземпляра Smarty**, не относящихся к фронтенду
игры: `Admin/controls/GameSettings/Localization/ifloc/_smarty/libs/Smarty.class.php`
(инструмент локализации в админке) и `ds/masmail/classes/Smarty/Smarty.class.php`
(модуль массовой рассылки писем). Задание предполагало именно такое разделение —
предположение подтвердилось: Smarty для фронтенда игры (`ds/include/Smarty/`) — это
**третья**, отдельная копия библиотеки, физически не связанная с этими двумя.

### 4.1. `ds/templates/` (11 МБ) — Smarty-шаблоны игры

Структура:

```
ds/templates/
├── *.tpl                    (64 файла — "текущая"/дефолтная версия скина)
├── cabinet/                 (личный кабинет: 6 .tpl)
├── wap/
│   └── login.tpl
└── classic/
    ├── *.tpl                (73 файла — расширенный набор, включая DE-локализацию,
    │                          турнирный рейтинг, копирование мира и т.п.)
    ├── cabinet/              (тот же набор, что и в ds/templates/cabinet/)
    ├── index_registration.php   (единственный не-.tpl файл в classic/)
    ├── reg_wiz_de/           (13 .tpl — немецкая версия визарда регистрации)
    ├── reg_wiz_eng/          (13 .tpl — английская версия)
    └── reg_wiz_rus/          (13 .tpl + img/content, img/layout — русская версия)
```

**Ключевая находка**: `ds/templates/classic/` — это не архивная/неиспользуемая
копия, а полноценно эксплуатируемый альтернативный скин, выбираемый на уровне
конфигурации. В `ds/config/settings.php` константа `TEMPLATES_PATH` задаётся по-разному
в зависимости от того, какая инсталляция (см. `docs/multi-world-architecture.md`)
обслуживается текущим сервером:

| `VERSION_NAME` | Домен | `TEMPLATES_PATH` (`ds/config/settings.php`, строка) |
|---|---|---|
| `RU_UNLIM` (Unlimited) | ds-game.ru | `/home/www/ds/templates/` — строка 106 |
| `LIMITED` (Pulsar) | ds-game.su | `/home/www/ds/templates/classic/` — строка 143 |
| `RU_CLASSIC` (Classic) | destinysphere.ru | `/home/www/ds/templates/classic/` — строка 320 |

Контринтуитивный момент: несмотря на название, ветку `templates/classic/` использует
**и** «Classic», **и** «Pulsar», а не Unlimited — притом что версия называется именно
«Unlimited», а не «Classic», использует корневой (не-`classic`) набор шаблонов.
Названия папок и брендов версий, судя по всему, разошлись исторически.

Отдельные `.php`-обработчики в `ds/` (например `ds/ranking.php:332`,
`ds/index_profile.php:229`) в основном хардкодят `$tpl->template_dir = "templates/";`
(то есть всегда корневой каталог, независимо от `TEMPLATES_PATH`), тогда как более
поздние/переиспользуемые классы (`ds/classes/GE/GE_Login.class.php`,
`ds/reg_wizard.php`, `ds/QuestForm.php`, `ds/index_registration.php`,
`ds/classes/SI/SI_ControlRestoreUserInstance.class.php` и др.) читают именно
константу `TEMPLATES_PATH`, то есть **корректно** переключаются между
`templates/`/`templates/classic/` в зависимости от инсталляции. Это значит, что часть
страниц (жёстко использующих `"templates/"`) может не иметь варианта под `classic/`
вообще, либо игнорирует выбор скина — не проверялось построчно для каждого файла.

### 4.2. Корневой `templates/` (457 КБ) — портальные FastTemplate-шаблоны

Отдельный, физически не связанный с `ds/templates/` каталог. Используется движком
`sys/`+`sys_dyn_components/` (подробно — `docs/portal-cms.md`). Основные разделы:

- `templates/Publication/*.tpl.html` — списки новостей/публикаций разных лент
  (`news_list.tpl.html`, `news_top.tpl.html`, `art_list.tpl.html`,
  `aliance_page_*.tpl.html`, `people_page.tpl.html`, `comrade_page.tpl.html`,
  `dealmakers_page.tpl.html`, `linkus*.tpl.html`), формы заказа доп.серверов по 10
  регионам (`add_serv/add_serv_<regio>.tpl.html` + `_alert` вариант) и
  `templates/Publication/vitrina.php` — сама «витрина» (главная страница портала),
  собирающая PHP+FastTemplate-фрагменты и подключающая `dsstyle_v4.css`
  (см. §5) и два декоративных Flash-баннера (§1.2).
- `templates/Buildings/Buildings.tpl.html`, `templates/Units/{Units,Units_NEW}.tpl.html` —
  игровые справочники (постройки/юниты по расам), рендерятся классами
  `sys_dyn_components/Buildings.php`/`Units.php`/`Units_NEW.php` напрямую через
  `FastTemplate`, в обход `PubWork` (см. `docs/portal-cms.md`, §2.2).
- `templates/Admin/` — `tmpl.tmp.html` (единственный файл, назначение не выяснялось
  отдельно — вне фокуса задачи).
- `templates/message/message_template.php` — используется `ShowCMSMessage()` из
  `sys/define.php` (см. `docs/portal-cms.md`).

Синтаксис шаблонов — не Smarty: подстановки вида `{BASE_URL}`, `{ID}`, `{ALERT}`,
повторяющиеся блоки оформлены HTML-комментариями
`<!-- BEGIN DYNAMIC BLOCK: row --> … <!-- END DYNAMIC BLOCK: row -->` — это API именно
`FastTemplate::define()`/`defineDynamic()`/`assign()`/`parse()`/`fetch()`, наглядно
показанный на примере `sys_dyn_components/Buildings.php` (см. `docs/portal-cms.md`).

### 4.3. `ds/UI_templates/` (8,2 МБ) — НЕ HTML/UI-шаблоны

Название вводит в заблуждение. Каталог содержит **24 файла без расширений**
(`Raila_race1`, `Stariy_1_race2`, `turnir_voraner_race1`, `1lvlvoraner_race1` и т.п.,
от 300 КБ до 472 КБ каждый). Проверка сигнатуры показала, что это не изображения и не
HTML, а **сериализованные PHP-объекты**:

```
O:21:"SI_ResultUserInstance":21:{ ... }
```

Использование подтверждено в коде:

- `ds/StoreComb.php:39` — `file_put_contents('UI_templates/ui_template_1', serialize($userInstanceResult));`
  — запись шаблона.
- `ds/classes/SI/SI_ExecStoreCombTemplate.class.php:54` — формирует имя файла как
  `<ИмяЮзера>_race<N>` и сохраняет туда сериализованный объект.
- `ds/classes/SI/SI_ControlApplyTemplate.class.php:22` — читает файл обратно через
  `file_get_contents()` (видимо, с последующим `unserialize()`).

То есть `UI_templates` — это на самом деле «**шаблоны экземпляров юнитов**»
(UserInstance-templates): заранее подготовленные PHP-сериализованные армии
NPC/учебных противников (имена вроде `Raila`, `Stariy`, `Psolao`, с суффиксами
`_race1/2/3` и `turnir_*` для турнирных версий) для быстрого клонирования боевой
ситуации — например, для обучающих боёв или турнирных сценариев — без пересчёта
армии с нуля на каждый вызов. К вёрстке/UI отношения не имеет; совпадение в
названии с «UI» — по всей видимости, историческое (или сокращение от чего-то
другого, не установлено).

---

## 5. Обзор CSS/стилей

| Файл | Строк | Кто подключает | Назначение (по фактическому использованию) |
|---|---|---|---|
| `dsstyle.css` (корень `ds/`, 686 строк) | — | `ds/templates/classic/ranking*.tpl`, `index_profile.tpl`, `questform.tpl`, `detect_player.tpl`, `desciplinary_request.tpl`, `dv_request_select.tpl`, `index_art_mode_demo.tpl`, `index_war_mode_demo.tpl`, `ds/copycomb/index*.php`, `ds/index_bugreport.php`, `ds/ranking_archive.php` | Основной скин «внешних»/сервисных игровых страниц (рейтинги, профиль, спорные ситуации, детект плеера) |
| `ds/cabinetstyle.css` (344 строки) | — | `ds/templates/{,classic/}cabinet/user_cabinet_{login,confirm,error,stat}.tpl`, `index_registration*.tpl`, `index_start_new.tpl`, `index_video{1,2}.tpl`, `index_welcome.tpl`, `password_change.tpl`, `new_password.tpl`, `ds/help/faq.php`, `ds/help/peace.php`, `ds/language/R/index_registration_adv.tpl` | Личный кабинет + весь поток регистрации/приветствия/логина (не считая самой формы логина, см. `style_il.css`) |
| `ds/cabinetstyle2.css` (433 строки) | — | `ds/templates/{,classic/}cabinet/{user_cabinet_index,payment_services}.tpl` | Второй, более новый вариант стиля кабинета — только для главной страницы кабинета и раздела оплаты (Premium/платежи) |
| `ds/style_il.css` (35 строк, короткий) | — | `ds/templates/classic/index_login.tpl`, `index_login_de.tpl` | Актуальный стиль страницы логина («il» = index_login). В тех же файлах закомментирован более старый вариант — `<!--../dsstyle_il.css-->` |
| `dsstyle_il.css` (корень, 398 строк) | — | **Не найдено ни одного активного подключения** — везде только в HTML-комментариях (`<!--../dsstyle_il.css-->` в `index_login.tpl`, `index_welcome.tpl`, `new_password.tpl`) | Судя по всему, **предыдущая версия** стиля страницы логина, заменённая на `style_il.css`, но оставленная в дереве и в закомментированных ссылках |
| `ds/reg.css` (593 строки) | — | `ds/templates/classic/reg_wizard{1,2,3}.tpl` (+ `_tmp` варианты, `_about`) | Мастер регистрации (reg wizard) — отдельный визуальный поток, не пересекается с cabinetstyle |
| `dsstyle_v4.css` (корень, 301 строка) | — | `templates/Publication/vitrina.php` (портальная **главная страница**, каталог с картинками `img/v4/...`) | Скин версии «v4» **портала** (не игровой сессии!) — начальная путаница в задании («игровой интерфейс v4») не подтвердилась: файл относится к оформлению сайта-визитки, а не к самому клиенту игры |
| корневой `main.css` (39 строк) | — | Не найдено активных подключений в проверенном дереве (`href="main.css"`/аналоги отсутствуют) | Простая типографика (`a.topstyle`, `h2`, `h3`) — назначение и текущая востребованность не установлены |
| `css/main.css` (150 строк) | — | Не найдено ни одного `<link>`/упоминания в PHP/шаблонах | Набор мелких классов (`.forma`, `.menu`, `.submit`, `.nobody`) — похоже на общий "рабочий" стиль форм старого интерфейса; действующий потребитель не найден |
| `styles/all.css` (57 строк) | — | Не найдено подключений | CSS с ID-селекторами табличной вёрстки (`#module_1`, `#header`, `#navigation`, `#search`) в духе очень ранней (начало 2000-х) раскладки страницы — похоже на реликт ещё более старой версии сайта, чем `dsstyle*` |
| `ds/DestinySphere.css` (470 строк), `ds/DestinySphere2.css` (383 строки) | — | Не проверялось отдельно (найдены при обзоре `ds/`, не входили в явный список задания) | Ещё одна пара стилей, по названию — вероятно альтернативный/старый брендинг; не разбирались подробно |
| `sys_dyn_components/sms/dsstylesms.css` (261 строка) | — | Виджеты SMS-рассылок в `sys_dyn_components/sms/` | Стили для отдельного SMS-раздела портала |
| `images/dsstyle.css` (290 строк) | — | Не проверялось отдельно | Ещё одна одноимённая копия `dsstyle.css`, лежащая в `images/` — не установлено, дубликат ли это старой версии или отдельный файл под другой раздел |

Итого достоверно подтверждён следующий расклад «версий» интерфейса:

- **`dsstyle.css` / `cabinetstyle.css` / `cabinetstyle2.css` / `style_il.css` / `reg.css`** —
  живой, актуальный набор стилей самой игровой обвязки (`ds/templates/classic/`),
  разделённый по функциональным зонам (рейтинги/профиль ↔ кабинет ↔ логин ↔ регвизард),
  а не по хронологическим версиям.
- **`dsstyle_il.css`** — единственный однозначно устаревший файл с явным следом замены
  в самом коде (закомментированная ссылка рядом с актуальным `style_il.css`).
- **`dsstyle_v4.css`** — не альтернативная версия игрового интерфейса, а стиль
  портальной домашней страницы (`templates/Publication/vitrina.php`), из другой,
  FastTemplate-системы шаблонов (§4.2).
- **`main.css`, `css/main.css`, `styles/all.css`** — три файла, для которых в
  проверенном дереве **не нашлось ни одного активного потребителя**; либо это
  реликты более ранних версий сайта, либо они подключаются из кода/данных вне
  рассмотренного скоупа (БД `sys/`, см. `docs/portal-cms.md`).

---

## 6. Открытые вопросы

1. **Класс `WAP_Login` отсутствует в коде.** `wap/login.php` его вызывает, но нигде
   не определяет и не подключает явно — WAP-модуль в этом чек-ауте нерабочий/неполный.
   Не установлено, был ли он вообще перенесён при формировании этого среза кода, и
   что происходит после успешного входа (полноценный WAP/WML-клиент игры или редирект
   в обычный `ds/index.php`).
2. **Реальное назначение файлов `mini-ds/orcs.swf`, `MageTroll.swf`, `Sculls.swf`,
   `minirts.swf`** не подтверждено — ни одной ссылки на них не найдено в PHP/HTML/
   шаблонах. Только `EvilWarrior2.swf` подтверждён как мини-игра через
   `ds/address.php`. Нужен либо дамп БД (если список мини-игр хранится в таблице),
   либо доступ к текущей структуре сайта, чтобы понять, используются ли остальные
   четыре файла до сих пор.
3. **Соответствие label «мобильная версия» на живом ds-game.ru** (см.
   `docs/external-sites.md`) конкретному каталогу — предположительно `wap/`, но не
   подтверждено прямым просмотром текущей вёрстки сайта.
4. **Непоследовательное внедрение Ruffle.** Скрипт `@ruffle-rs/ruffle` добавлен в
   `ds/index.php` (для всех версий) и в `ds/templates/classic/{index_login,index_start_new}.tpl`
   (Classic/Pulsar), но не в одноимённые файлы корневого `ds/templates/` (Unlimited).
   Не ясно, преднамеренно ли это (например, Unlimited раздаёт Flash Player иначе) или
   это недокатанное обновление.
5. **`ds/DestinySphere.css`/`DestinySphere2.css` и `images/dsstyle.css`** не были
   разобраны так же подробно, как основная пятёрка `dsstyle*`/`cabinetstyle*` —
   не установлено, действующие это файлы или более старые дубликаты/черновики.
6. **Логика выбора `TEMPLATES_PATH` не проверена для абсолютно всех потребителей.**
   Часть страниц (`ds/ranking.php`, `ds/index_profile.php` и др.) хардкодят
   `template_dir = "templates/"` вместо чтения `TEMPLATES_PATH` — не установлено,
   баг ли это (страница всегда рендерится в скине Unlimited независимо от реальной
   инсталляции) или осознанное решение, что для этих конкретных страниц скины не
   различаются.
7. **Совпадение количества файлов в `ds/UI_templates/` (24) с конкретными расами/
   уровнями** не проверялось на полноту — не установлено, покрывает ли этот набор
   все нужные комбинации раса×уровень или это только часть, а остальные генерируются
   на лету без кэша-шаблона.
