# Клиентский UI боевой/военной механики — по декомпилированному Flash (AS2)

> **Источник.** Каталог `.../decompiled_menu/scripts/__Packages/`, декомпиляция
> `ds3.swf` через JPEXS (ActionScript 2). Разобраны построчно и полностью 12
> файлов: `army_overview_class.as`, `armycalcclass.as`, `armycreationclass.as`,
> `battleoverviewclass.as`, `warplateclass.as`, `warstatistic.as`,
> `scannermenuclass.as`, `spying_class.as`, `zondcreateclass.as`,
> `trainvarunitclass.as`, `powershieldclass.as`, `protectshieldclass.as`.
>
> **Как читать этот документ.** Это дополнение к серверной документации
> (`mechanics-combat.md`, `protocol-client-server.md`), написанной по PHP.
> Здесь — взгляд со стороны клиента. Важная оговорка по методологии: в
> разобранных файлах UI-классы **не парсят сырой XML напрямую** — они
> получают уже готовые ActionScript-объекты (`array_units`, `object_list`,
> `filter_obj`, `obj_shield` и т.п.), собранные где-то в `reqvestdata.as`/
> `xmlparsers.as` (эти два файла в это исследование не входили — их разбирают
> другие агенты параллельно). Поэтому большинство «полей» ниже — это имена
> свойств ActionScript-объектов, а не буквально подтверждённые имена
> XML-атрибутов. Исключение — фрагмент `debug.link.active_cell`: он
> используется в 6 из 12 файлов буквально как XML-строка
> (`new XML("<temp " + debug.link.active_cell + "/>")`), и это даёт
> **надёжно подтверждённые** имена атрибутов `c` (номер ячейки) и `b` (ID
> здания) — см. раздел 0.2.
>
> Второй важный источник информации — вызовы `reqvestdata.<метод>(...)`:
> это исходящие команды к серверу. Сами имена методов `reqvestdata` — это
> клиентские wrapper-функции (реализация в `reqvestdata.as`, не в этом
> срезе), поэтому это **не буквальные имена XML-тегов**, а лишь сильная
> подсказка к ним. Там, где имя метода совпадает по смыслу с именем класса
> `UA_*` из `protocol-client-server.md` §6, это отмечено как сопоставление
> (с пометкой «предположительно», если не 100% дословно).
>
> **Обновление.** В отличие от первого прохода, здесь также появился доступ
> к `old-sphere/SPHERE_client/` — SVN-копии разработчика клиента с
> настоящими исходниками AS2 (реальные имена переменных, комментарии на
> русском), включая `reqvestdata.as` (1077 строк, разобран полностью) и
> `xmlparsers.as`/`parser.as` (реальный парсер XML-ответов и диспетчер
> корневых тегов). Это позволило подтвердить **буквальные имена XML-тегов и
> атрибутов** для всех 12 экранов данного документа — они отмечены пометкой
> `client-source:` (в отличие от `client-decompiled:` для находок из
> декомпилированного `ds3.swf`). Важная оговорка: `SPHERE_client/` — это
> **другая ревизия** клиента, чем декомпилированный `ds3.swf` (например,
> `army_overview_class.as` здесь короче — 1390 строк против ссылок на
> строки за 1600 в декомпилированной версии, и в нём отсутствуют функции
> переименования армии и артефактов армии, описанные ниже). Поэтому там,
> где реальный источник **подтверждает** структуру/имена — это отмечено как
> факт; там, где реальный источник **не содержит** описанную decompiled-
> находку (а не прямо противоречит ей) — это отмечено отдельно как
> расхождение версий, а не как опровержение.

---

## 0. Сквозные находки (относятся сразу к нескольким файлам)

### 0.1. Конвейер экранов — как связаны файлы

Судя по вызовам друг друга и по `reqvestdata`-командам, за военно-боевую
часть интерфейса отвечает такой набор экранов:

```
Обзор армий (army_overview_class) ── создать армию ──> armycreationclass
     │                                    (калькулятор без отправки: armycalcclass)
     │
     ├─ ATTACK/DEFEND/MOVE_TO_COMB/HIDE_ARMY (army_action) ──> полёт армии, бой
     │
Обзор боёв (battleoverviewclass, список + фильтры)
     ├─ "просмотр отчёта" (getReportBattle)  → текстовый лог боя (HTML)
     └─ "наглядный бой" (getShowBattle)      → warplateclass (пораундовая картинка)

Военная база:
     ├─ trainvarunitclass  — тренировка одного типа боевого юнита (детальная панель)
     ├─ warstatistic       — пакетная загрузка нескольких "фабрик" одним типом юнита
     ├─ powershieldclass   — силовой щит (заряд → активация на N ходов)
     └─ protectshieldclass — восстановление защитного поля колонии (% брони)

Шпионаж (३ отдельных экрана, конвейер):
     zondcreateclass (тренировка зондов) → spying_class (отправка зондов на задание)
                                                    → scannermenuclass (журнал/лог сканирования)
```

Это стоит явно внести как уточнение структуры к разделу 12
(«Шпионаж») `mechanics-combat.md`: там были известны только имена
классов из PHP (`UA_SpySendZonds`, `GL_SpyCountZondPrice`,
`GL_SpyMaxCountZondsOnComb`, `GL_SpyLogAccuracy`, `ME_SpyZondsBattle`,
`SU_SpyZondsBattleCalculator`), но не была понятна структура экранов.
Теперь видно: это ровно три раздельных окна с разными задачами (тренировка →
отправка → журнал), а не один комбинированный интерфейс.

### 0.2. Единственное надёжно подтверждённое XML-соответствие: `c` и `b`

В 6 файлах (`powershieldclass.as:60-64`, `protectshieldclass.as:69-73`,
`battleoverviewclass.as:378-382`, `spying_class.as:96-100`,
`zondcreateclass.as:321-325`, `trainvarunitclass.as:193-194,326-327`)
встречается один и тот же паттерн:

```actionscript
_loc11_ = "<temp " + debug.link.active_cell + "/>";
_loc13_ = new XML(_loc11_);
_loc7_ = game.getLocalNames(sota.getBuildidByCell(_loc13_.firstChild.attributes.c)).name;
```

Это означает, что `debug.link.active_cell` — глобально хранимая **сырая
строка XML-атрибутов** активной (кликнутой) ячейки сотылистка, присланная
сервером, и что в ней **буквально** есть атрибут `c` (номер ячейки — по
нему ищется ID здания через `sota.getBuildidByCell()`). В
`trainvarunitclass.as:194` и `warplateclass`-родственных файлах виден и
второй атрибут этого же фрагмента:

```actionscript
var code_str_build = temp_xml.firstChild.attributes.c + temp_xml.firstChild.attributes.b;
```

— то есть в этом же фрагменте есть атрибут `b` (используется как ID
здания напрямую, без похода через `sota.getBuildidByCell()`). Это
согласуется с `protocol-client-server.md` §7.5, где атрибуты `c` (ячейка)
и `b` (ID здания) уже зафиксированы как входные параметры команды
`constructbuilding` — здесь мы видим их же, но уже как поля, которые
**сервер присылает обратно** клиенту про текущую активную ячейку (не
только принимает от клиента при постройке). Разумно предположить, что это
один и тот же короткий формат `c="…" b="…"`, используемый в обе стороны.

### 0.3. Расширенный список игровых ресурсов (сверх того, что видно в `protocol-client-server.md` §7.3)

`protocol-client-server.md` §7.3 показывает пример `<combres>` только с
4 ресурсами (`KADERIUM`, `NANO_CRYSTAL`, `GOLD_CREDIT`, `SILVER_CREDIT`).
В разобранных файлах (`armycalcclass.as:141-146`, `protectshieldclass.as:37-42`,
`warstatistic.as:65-88`, `trainvarunitclass.as:134-139` и др.) стабильно,
во всех местах в одном и том же порядке, встречается полный список:

```
debug.link.resourse_player.KADERIUM.current
debug.link.resourse_player.NANO_CRYSTAL.current
debug.link.resourse_player.STASIS_ENERGY.current
debug.link.resourse_player.FOOD.current
debug.link.resourse_player.PRODIUM.current
debug.link.resourse_player.WORKER.current      (в списках цены юнитов/зондов)
debug.link.resourse_player.SILVER_CREDIT.current (отдельно, как премиум-валюта)
```

Итого подтверждённый (по повторяемости во всех файлах) список ключей
ресурсов игрока: `KADERIUM`, `NANO_CRYSTAL`, `STASIS_ENERGY`, `FOOD`,
`PRODIUM`, `WORKER`, `SILVER_CREDIT` (+ `GOLD_CREDIT`, известный из
`protocol-client-server.md`). Сокращённые буквенные ключи цены юнита/зонда,
видимые почти во всех «панелях тренировки» — `price.K/N/S/F/P/W/C` (и,
только у зондов, `price.WR`) — почти наверняка это Kaderium / Nano /
Stasis / Food / Prodium / Worker / Creation-time(ходы) / War-Rank
соответственно; **не подтверждено буквально**, что именно такие
однобуквенные имена используются в исходном XML (парсинг вне периметра
этого исследования), но очень вероятно, судя по единообразию во всех
панелях.

### 0.4. Клиентские команды (`reqvestdata.*`) и их вероятное соответствие `UA_*`

Ниже — таблица всех исходящих вызовов `reqvestdata`, найденных в 12
файлах, и сопоставление с классами `UA_*` из `protocol-client-server.md`
§6 (там, где совпадение по смыслу выглядит убедительно). Совпадение имени
метода с именем класса — это **не подтверждение имени тега**, только
разумная гипотеза.

| Метод `reqvestdata` | Файл(ы) | Параметры (по коду) | Вероятный `UA_*` |
|---|---|---|---|
| `create_army(units[], name)` | armycreationclass:245 | список `{id, count}` + имя армии | `UA_CreateArmy` (тег `createarmy` подтверждён в §5.2 протокол-доки) |
| `call_tabs(id)` | почти все панели | ID вкладки/подменю | вероятно обёртка над `getbuildmenu`/`tab=` (§7.9 протокол-доки) |
| `dismissArmies(idsCSV?)` | army_overview_class:406,410 | список ID через запятую либо без параметров (= "все") | `UA_DismissArmies` |
| `renameArmy(id, name)` | army_overview_class:703 | ID армии, новое имя | `UA_RenameArmy` |
| `armyuseartefacts(id_army)` | army_overview_class:628 | ID армии | `UA_ArmyUseArtefacts` |
| `army_unit_transfer(arr)` | army_overview_class:1608 | массив `{id, value, to_army, from_army}` | `UA_TransferUnit` (тег `transferunit` подтверждён в §5.2) |
| `army_action(id, action, ring, complex, sota, speed, res, hours, min)` | army_overview_class:1250 | см. §2 ниже | не établи однозначно — вероятно веером на `attack`/`defend`/др. |
| `army_action_hide(id, action, time)` | army_overview_class:1246 | ID армии, `"HIDE_ARMY"`, время | не установлено |
| `calc_army_action(...)` | army_overview_class:1288 | те же параметры, но без выполнения — просимость | `UA_CalcTravel` |
| `trainWarUnit(count, id_unit)` | trainvarunitclass:329 | количество, ID юнита | `UA_TrainWarUnit` |
| `cancelTrainWarUnit()` | trainvarunitclass:345 | без параметров | `UA_CancelTrainWarUnit` |
| `load_pack_war_factory(cellIds[], id_unit, amount)` | warstatistic:173 | список ячеек-«фабрик», ID юнита, количество | не установлено (возможно, тоже `UA_TrainWarUnit` с другим набором атрибутов) |
| `trainzondunit(count, id_zond)` | zondcreateclass:221 | количество, ID типа зонда | `UA_TrainSpyUnit` |
| `canceltrainunit(id_zond)` | zondcreateclass:200 | ID зонда | **не** `UA_CancelTrainSpyUnit` (такого класса нет в списке §6!) — вероятно общий `UA_CancelTrainUnit` |
| `send_zond(type, count, action, time, ring, complex, sota, oneRound, speed)` | spying_class:278 | см. §11 ниже | `UA_SpySendZonds` |
| `getSpyLog(id)` | scannermenuclass:397, spying_class:343 | ID записи лога | `UA_SpyGetLog` |
| `requestScan(ids[])` | scannermenuclass:113 | массив ID целей на пересканирование | не установлено — нет очевидного класса в списке §6 |
| `filterScanner(colonyIds[], typeIds[], name, fromSec, toSec)` | scannermenuclass:229 | фильтр по колонии/типу/имени/времени | не установлено |
| `filterScanerLog(scanA, scanD, scanZ, name)` | scannermenuclass:304 | 3 булевых чекбокса + имя игрока | не установлено |
| `getReportBattle(id_battle)` | battleoverviewclass:575, warplateclass:114 | ID боя | `UA_GetBattleInfo` (тег `getbattleinfo` подтверждён в §5.2) |
| `getShowBattle(id_battle)` | battleoverviewclass:582 | ID боя | вероятно тоже `UA_GetBattleInfo` (другой режим ответа) |
| `SurrenderSampleBattle(id_battle)` | battleoverviewclass:530,553,560 | ID одного боя | `UA_Retreat` |
| `SurrenderArrBattle(ids[])` | battleoverviewclass:316,334 | массив ID боёв (пакетная капитуляция, премиум) | `UA_Retreat` |
| `ShowBattle(dayFrom, dayTo, opp, sota, status, winner)` | battleoverviewclass:138 | параметры фильтра списка боёв | не установлено |
| `getRewards()` | battleoverviewclass:412 | без параметров | `UA_GetRewards` |
| `poweractivation()` | powershieldclass:136 | без параметров (кнопка "CHARGE") | вероятно `UA_PowerShildActivate` |
| `powershieldenabled(turns)` | powershieldclass:169 | количество ходов активации | вероятно `UA_PowerShildStart` |
| `restoreProtectionShield("RES"\|"SILVER", turns)` | protectshieldclass:154,159 | способ оплаты + количество % (в "ходах"/пунктах) | `UA_RestoreProtection` |
| `globalCabinetRequest()` | trainvarunitclass:245 | без параметров (кнопка ускорения без премиума) | не установлено |
| `createnewmail(?, ?, nick)` | warplateclass:80, battleoverviewclass:126 | получатель — ник оппонента из боя | вероятно локально предзаполняет форму письма, не факт что это отдельная серверная команда |

**Уточнения по `reqvestdata.as` (реальный источник, полностью прочитан).**
Часть строк таблицы выше можно теперь заменить точными фактами:

- `create_army(arr_units, name_army)` **буквально** отправляет
  `<createarmy><armyname><![CDATA[...]]></armyname><unit id="" count=""/>...</createarmy>`
  (`client-source: reqvestdata.as:748-759`) — тег `createarmy` подтверждён
  дословно, не только по аналогии с `UA_CreateArmy`.
- `dismissArmies()` в реальном источнике **не принимает вообще никаких
  параметров** (`static function dismissArmies()`,
  `client-source: reqvestdata.as:104-109`) и отправляет `<dismissArmies />`
  без единого атрибута. Комментарий разработчика прямо над функцией:
  `//РАСПУСТИТЬ ВСЕ АРМИИ К ЕБЕНЯМ НАХ!` — то есть это **подтверждённый
  факт**, что пустой вызов означает «распустить все армии», а не «ничего не
  делать» (закрывает открытый вопрос №9, см. ниже). Реальный вызывающий код
  в `army_overview_class.as:297-308` (`client-source`) подтверждает то же
  самое: кнопка называется `DISMISS_ALL`, показывает предупреждение
  `DISMISS_ARMY_ALERT` и зовёт `reqvestdata.dismissArmies()` без аргументов
  — никакого списка выбранных ID в этой ревизии клиента нет вообще (в
  отличие от decompiled-находки — see оговорка о разных ревизиях выше).
- `army_action(id_army, action, ring_action, complex_action, sota_action, speed_army, resourse_action)`
  в реальном источнике имеет ровно **7 параметров, БЕЗ часов/минут**
  (`client-source: reqvestdata.as:670-709`), и собирает
  `<attack|defend|movetocomb|cancelattack|canceldefend res="" speed="" destination="ring.complex.sota" id_army=""/>`
  — то есть `action` литерально превращается в имя тега (`attack`, `defend`,
  `movetocomb`, `cancelattack`, `canceldefend`), а не передаётся как
  атрибут. Часы/минуты задержки (`rotatedRings`, см. §1 ниже) идут **только**
  в `calc_army_action(...,delh,delm)` → тег `calctravel` (`client-source:
  reqvestdata.as:730-735`), который отправляется по кнопке
  «рассчитать»/предпросмотр, а не по кнопке реальной отправки атаки — этим
  реальный источник **уточняет** (не отменяет) находку decompiled-версии,
  см. §1.
- `army_action_hide(id_army, "HIDE_ARMY", time_hide)` → тег `<hide_army id_army="" timehide=""/>`
  (`client-source: reqvestdata.as:711-729`).
- `calc_army_action(...)` → тег `<calctravel speed="" mode="" targetRing="" targetComplex="" targetComb="" id_army="" timeh="" timem=""/>`
  (`client-source: reqvestdata.as:730-735`) — здесь `mode` буквально равен
  имени действия (`"ATTACK"`/`"DEFEND"`/…), а не переименовывается, в
  отличие от `army_action`.
- `army_unit_transfer(...)` → `<transferunit><unit id="" count="" id_army_from="" id_army_to=""/>...</transferunit>`
  (`client-source: reqvestdata.as:736-746`) — атрибуты дословно
  `id_army_from`/`id_army_to`, не просто `to_army`/`from_army` (это только
  имена полей входного JS-объекта).
- `trainWarUnit`/`cancelTrainWarUnit` → буквально `<trainwarunit c="" count="" id=""/>` /
  `<canceltrainwarunit c=""/>` (`client-source: reqvestdata.as:554-564`).
- `trainzondunit`/`canceltrainunit` → `<trainspyunit c="" count="" id=""/>` /
  `<canceltrainunit c="" id=""/>` (`client-source: reqvestdata.as:311-343`).
- `getReportBattle`/`getShowBattle` → **один и тот же** тег `getBattleInfo`,
  различаются только атрибутом `report`: `<getBattleInfo id_battle="" report="1"/>`
  для отчёта и `<getBattleInfo id_battle=""/>` (без `report`) для наглядного
  боя (`client-source: reqvestdata.as:814-827`) — подтверждает
  гипотезу doc'а, что это один и тот же класс `UA_GetBattleInfo` с разным
  режимом ответа, теперь буквально видно, чем режимы отличаются на входе.
  Значит и **ответ** с высокой вероятностью — один и тот же XML-тег
  `battlinfo` для обоих запросов (см. `parser.as` §0.5 ниже) — сервер
  решает, класть туда текстовый отчёт или пораундовые данные, по тому же
  `report`.
- `SurrenderArrBattle`/`SurrenderSampleBattle` → **оба** буквально
  собирают тег `<retreat id_battle="..."/>` (только `SurrenderArrBattle`
  кладёт туда CSV-список ID) — `client-source: reqvestdata.as:918-927`.
  Прямое подтверждение, что «сдаться» и «отступить» — это дословно один и
  тот же тег `retreat` на проводе, не два разных.
- `restoreProtectionShield(typ, val)` → `<restoreProtection resource="" amount=""/>`
  (`client-source: reqvestdata.as:900-904`), `typ` буквально `"RES"` или
  `"SILVER"` — подтверждено вызовом из `protectshieldclass.as:165,171`
  (`client-source`).
- `poweractivation()` → `<psactivation />`; `powershieldenabled(turn)` →
  `<psstart time=""/>` (`client-source: reqvestdata.as:888-897`) — то есть
  реальные теги короче и не совпадают дословно с именами методов
  `UA_PowerShildActivate`/`UA_PowerShildStart` из §6 протокол-доки (только
  по смыслу).
- `requestScan(ids)` → `<scaninriver id=""/>`; `filterScanerLog(...)` →
  `<scaninriverfilter keya="" keya="" keya="" name=""/>` (реальный баг
  разработчика — все три флага записаны под одним и тем же именем атрибута
  `keya` три раза подряд, последний перезаписывает предыдущие; настоящие
  булевы флаги «атака/защита/зонд» **не долетают до сервера тремя разными
  атрибутами** — это баг, а не так и было задумано) (`client-source:
  reqvestdata.as:942-982`).
- `filterScanner(...)` → `<getbuildmenu c="" tab="PN_SpyLogs" name="" colony="" types="" datefrom="" dateto=""/>`
  (`client-source: reqvestdata.as:73-83`) — подтверждает, что это не
  отдельная команда, а частный случай общего `getbuildmenu`/`tab=`
  (закрывает часть открытого вопроса №10).
- `send_zond(...)` → `<sendzond id="" count="" action="" ring="" compl="" sota="" oneround="" sd="" />`
  (когда `oneround` задан) либо `<sendzond id="" count="" action="" time=""/>`
  (когда действие требует времени) — `client-source: reqvestdata.as:987-1000`.
- `globalCabinetRequest()` **не является отдельной серверной командой** —
  это чисто клиентское ветвление: для соцсетей `"VK"`/`"POP"` вызывает
  `cabinetRequest()` (тег `<cabinetRequest command="open"/>`), для всех
  остальных — открывает внешний URL `user_cabinet.php?action=payment_services&ps_action=PS_TRAIN_SPEEDUP`
  без обращения к игровому протоколу вообще (`client-source:
  reqvestdata.as:1051-1063`) — закрывает соответствующий пункт открытого
  вопроса №10: класса `UA_*` для этого не существует, потому что это не
  игровая команда.

### 0.5. Литеральные корневые XML-теги ответов сервера (по `parser.as`)

`parser.as` — реальный диспетчер ответов: он берёт `nodeName` корневого
элемента присланного XML и по `switch(...)` вызывает нужную функцию
`xmlparsers.*`. Это даёт **дословное** соответствие «экран → корневой тег»
для всех 12 разобранных файлов (`client-source: parser.as:1461-1611`),
чего не было видно по декомпилированным UI-классам:

| Экран (этот документ) | Корневой тег ответа | Функция-парсер (`xmlparsers.as`) |
|---|---|---|
| `armycreationclass` | `<armycreation>` | `army_create_menu`, :1430 |
| `army_overview_class` | `<armyoverview>` | `army_overview_menu`, :1461 |
| `armycalcclass` | `<armycalculator>` | `army_calc_menu`, :1666 |
| `battleoverviewclass` | `<battleoverview>` | `battle_overview_menu`, :1052 |
| `warplateclass` | `<battlinfo>` | `battle_show_menu`, :1152 |
| `powershieldclass` | `<powershild>` (без "e" — опечатка на сервере/в протоколе) | `parser_powershild_menu`, :1195 |
| `protectshieldclass` | `<protectedfield>` | `parser_protectshild_menu`, :1227 |
| `scannermenuclass` | `<spylog>` | `logs_zonds_menu`, :34 |
| `trainvarunitclass` | `<warunit>` (не `trainwarunit`!) | `trainwarunit_parser`, :396 |
| `warstatistic` | `<warcomplex>` | `war_complex_menu`, :324 |
| `spying_class` | `<spying>` | `spy_menu_parser`, :199 |
| `zondcreateclass` | `<zondcreate>` | `createzond`, :3434 |

Это закрывает бОльшую часть открытого вопроса №1: практически все находки
разделов 1-11 ниже теперь можно проверить по буквальному имени тега и
атрибута, а не только по имени AS-свойства.

Отдельно от полноэкранных ответов есть канал **realtime-обновлений**
(`<helper>...</helper>`, диспетчер `parser.as:helper_action()`, :1041) —
именно он стоит за примером `inaction="1"` из `protocol-client-server.md`
§2.5. Разобранные там кейсы, относящиеся к боевой/военной части:

- `case "army"` (`parser.as:1152-1197`) — с комментарием разработчика
  прямо в коде: `// Вроде бы ARMY_UPDATE - army_overview_class`. Обновляет
  ровно то же самое, что и полноэкранный `<armyoverview>`: `id_army`,
  `inaction`, `lastcoord`, `lastspeed`, вложенные `<transfers>`/`<actions>`
  — то есть realtime-канал и полный экран используют один и тот же формат
  вложенных тегов армии.
- `case "new_armypower"` (`parser.as:1199-1214`) — отдельное, более узкое
  обновление, только `armypower`/`PW` (пример из кода:
  `<army id_army="4" armypower="5"/>`), без `inaction`/координат.
- `case "sendzondok"` (`parser.as:1050-1063`) — подтверждает буквальный
  пример `<sendzondok id="SPY_ZOND_SCOUT" count="11" action="..." time="..." log="0" addr="..." zname="Зонд Скаут" zcolor="#FFFFFF"/>`
  — то есть `SPY_ZOND_SCOUT`/`SPY_ZOND_EXPLORER` действительно используются
  сервером как ID зонда, а не только как клиентский локализационный ключ
  для подписи кнопки (см. §11 ниже).
- `case "trainspyok"` (`parser.as:1065-1088`) — по массиву зондов приходит
  `id`(=namezond), `new_max`, `inaction`, `new_xp`, `awail` — подтверждает
  дословно поле `obj_upd[i].newxp` из §11 doc'а (реальный атрибут `new_xp`).

---

## 1. `army_overview_class.as` — Обзор армий

**Назначение** (help-топик `"ARMY_OVERVIEW"`, `army_overview_class.as:364`):
центральное окно управления всеми армиями игрока — список армий с
фильтрами по статусу, детальная карточка одной армии (состав юнитов,
кнопки действий), передача юнитов между армиями, переименование, роспуск.

### Найденные поля/литералы

| Поле/литерал | Где | Смысл |
|---|---|---|
| `obj.inaction` (0/1/2/3) | повсюду в файле | статус армии — см. гипотезу ниже |
| `obj.armypower` | :754, `complete_army_sample` | число «звёзд» силы армии — см. п. «Сопоставление» ниже |
| `obj.prodoweight` | :722-729 | «вес продиума» армии (видно только премиум-аккаунтам), подпись `PRODIUM_WEIGHT` |
| `obj.useArtefacts` | :628-647 | 1/0/undefined — использовать ли артефакты в след. бою этой армии |
| `obj.lastcoord`, `obj.lastspeed` | :774,839 | текущие координаты/скорость армии в полёте (формат `ring.complex.sota`, судя по `.split(".")`) |
| `obj.allow_action[]` (`{id, ln}`) | :913-918 | список действий, доступных ЭТОЙ армии прямо сейчас (id → пункт radio-переключателя) |
| `obj.allow_transfer[]` (`{id, ln}`) | :248-252 | список армий, куда можно передать юнитов из этой |
| `obj.remember_obj.*` | :584-593 | клиентский кэш последних введённых координат/скорости/действия — сохраняется через `setting()` (локальный LSO), не запрос к серверу |
| ID действия: `"ATTACK"`, `"DEFEND"`, `"MOVE_TO_COMB"`, `"HIDE_ARMY"`, `*.indexOf("CANCEL")` | :1026-1063, 1162 | словарь возможных действий армии — расширяет список `id_action` из `protocol-client-server.md` §7.4 (`CANCEL_ATTACK`) |
| `rotatedRings` (флаг мира) | :965 | см. «Что нового» ниже — не упомянут нигде в серверной документации |
| `obj.mst` | :1065-1074 | минимальное время (в ходах?) для HIDE_ARMY, дефолт 0 |

### Клиентская валидация

- **Переименование армии** (:693-699, decompiled): имя должно быть
  **≥ 3 символов** (иначе `WRONG_NAME_LENGTH`) и **отличаться** от
  текущего (иначе `SAME_ARMY_NAME`) — обе проверки чисто клиентские, до
  отправки `renameArmy`. **Оговорка по версии**: в `SPHERE_client/army_overview_class.as`
  (реальный источник) переименования армии и `armyuseartefacts` нет
  вообще — ни строки `rename`, ни строки `artefact` в файле не найдено
  (проверено полнотекстовым поиском). Это не противоречие decompiled-
  находке (та основана на реальном коде `ds3.swf`, просто другой ревизии),
  а признак того, что эта конкретная SVN-копия — более ранняя или другая
  ветка, где этих двух фич ещё/уже не было. Поэтому эти две строки таблицы
  §0.4 остаются неподтверждёнными этим источником (ни за, ни против).
- **Отправка действия (`go_action`)** (:1162): кнопка реально шлёт запрос,
  только если `complex_action > 0` **ИЛИ** действие — `"HIDE_ARMY"`
  **ИЛИ** действие содержит подстроку `"CANCEL"`. То есть для
  ATTACK/DEFEND/MOVE_TO_COMB клиент требует ненулевой номер комплекса
  цели, иначе кнопка молча ничего не делает (без явного сообщения об
  ошибке — просто нет отправки).
- **Массовый роспуск армий** (:394-413, decompiled): если игрок отметил
  чекбоксами конкретные армии, диалог подтверждения показывает их
  количество; если ничего не отмечено — `dismissArmies()` без параметров.
  **Подтверждено реальным источником**: в `SPHERE_client/army_overview_class.as:292-313`
  (`client-source`) кнопка роспуска в этой ревизии клиента вообще не имеет
  выбора конкретных армий — это одна кнопка `DISMISS_ALL` (только премиум),
  которая после подтверждения (`DISMISS_ARMY_ALERT`) всегда зовёт
  `reqvestdata.dismissArmies()` без единого аргумента. А сам метод
  `dismissArmies()` в `reqvestdata.as:104-109` **не принимает параметров
  вообще** (не только «может быть вызван без параметров» — в его сигнатуре
  физически нет места для списка ID) и отправляет `<dismissArmies />` без
  атрибутов; комментарий разработчика прямо над функцией — `РАСПУСТИТЬ ВСЕ
  АРМИИ К ЕБЕНЯМ НАХ!`. Это закрывает открытый вопрос №9: пустой вызов
  **подтверждённо** означает «распустить все армии игрока», а не «ничего не
  делать» — при реализации ремастера дожидаться списка ID для частичного
  роспуска не нужно, этой возможности в протоколе (по крайней мере в этой
  ревизии) нет.
- **Подтверждающее окно перед отправкой** (`game.check_confirm_win()`,
  :1173-1229): это **отдельная, чисто клиентская** настройка «показывать
  диалог подтверждения» (локальный тумблер игрока), не связанная с
  серверными «жёсткими» подтверждениями через `outside`/`<answer>` из
  `protocol-client-server.md` §7.4. Для ATTACK/DEFEND диалог **никогда**
  не показывается (сразу `sendRequest()`), для остальных действий и для
  HIDE_ARMY — показывается, если тумблер включён. Ветки `newbeeMode`
  вкл/выкл (`game.getNewbee()`) в текущем коде **идентичны** друг другу
  (:1164-1229) — либо это заготовка под будущее различие поведения для
  новичков, либо мёртвый дубль кода.

### Что нового по сравнению с `mechanics-combat.md`

1. **`rotatedRings` — «вращающиеся кольца»** (:965-979, decompiled). Если у
   мира включена эта настройка, при выборе действия `ATTACK` в карточке
   армии дополнительно показываются поля указания **задержки атаки** в
   днях/часах/минутах (`day_delta`/`hour_delta`/`min_delta`, подписи
   `DEF_PER_TIME`, `D_`/`H_`/`M_`). **Уточнение по реальному источнику**
   (`client-source: army_overview_class.as:883-911`, `reqvestdata.as:670-735`):
   поля задержки считываются и передаются **только** в кнопке-калькуляторе
   (`mov.calc_action.onRelease`, :893-910) — они уходят на сервер исключительно
   внутри `calc_army_action(...,delta_hour,delta_min)` → тег `<calctravel ... timeh="" timem=""/>`.
   Кнопка реальной отправки атаки (`mov.go_action.sendRequest`, :883-888)
   вызывает `reqvestdata.army_action(id_army,action,ring_action,complex_action,sota_action,speed_army,resourse_action)`
   — ровно 7 параметров, `delta_hour`/`delta_min` в неё **не передаются
   вообще** ни в одной из веток. Другими словами: часы/минуты задержки —
   это только вход для предпросмотра/калькулятора перелёта (`calctravel`),
   а не часть самой команды атаки/защиты — реальная атака всегда уходит
   без явно указанной задержки. Это уточняет (не отменяет) находку: сама
   механика вращающихся колец подтверждена (поля реальны, есть в реальном
   источнике), но конкретная передача задержки в `army_action` — не
   подтвердилась, что стоит учесть при поиске `GL_TravelTime`/`GL_TravelTime2`
   на сервере (искать эффект в `calctravel`, а не в командах атаки).
   Явно не упомянуто в `mechanics-combat.md` (разделы 4 «Длительность
   раунда» и 10 «Перелёт»).
2. **Визуализация «звёзд» силы армии совпадает с формулой сервера.**
   `obj.armypower` рисуется как `N` иконок-звёзд (:747-768,
   `army_overview_class.colorStartPower`, 20-цветная палитра, с 9-го цвета
   палитра зацикливается на фиолетовый) — это прямое визуальное
   подтверждение формулы `GL_ArmyPowerToStars::Count()` из
   `mechanics-combat.md` §4.1 (`stars = ceil(log₁₂(armyPower/1000))`):
   клиент действительно оперирует понятием «звёзды армии» как отдельным
   целым числом, а не просто показывает сырую силу.
3. **`obj.useArtefacts`** подтверждает механизм из `mechanics-combat.md`
   §3.13 п.3 (`$army->useArtefacts`, решение принимает игрок при
   отправке атаки, `DE_Army.class.php:530-541`) — здесь видно и
   UX-сторону: это чекбокс на карточке армии, отдельная команда
   `armyuseartefacts(id)` (не часть `army_action`), и он **блокируется**
   (`_alpha=50`, снят `onRelease`), если армия уже не в статусе `inaction
   != 0` — то есть решение об артефактах можно менять только пока армия
   «дома», не в полёте/бою.
4. **Значения `inaction` — теперь подтверждены дословным комментарием
   разработчика**, а не только гипотезой по поведению UI. Реальный
   источник (`client-source: xmlparsers.as:1504-1509`, парсер тега
   `<armyoverview>`, и дублирующий его комментарий в
   `xmlparsers.as:3151`) содержит буквально:

   ```text
   0 - стоит на соте
   1 - летит куда-то можно вернуть
   2 - летит домой ничего не доступно
   3 - домашка
   ```

   То есть `0` = армия стоит на своей базе; `1` = летит к цели, действие
   ещё можно отменить/вернуть; `2` = возвращается домой, никакие действия
   уже не доступны; `3` = разговорное «домашка» — судя по коду, это
   практически то же «дома», что и `0`. Это **уточняет** прежнюю гипотезу
   этого документа (которая предполагала для `2` «обороняет чужую соту», а
   для `3` — «армия спрятана после `HIDE_ARMY`») — оба предположения не
   подтвердились дословно: `2` на самом деле про возврат домой, а не про
   оборону чужой соты, и явной связи `3` с `HIDE_ARMY` в коде не найдено.
   Подтверждена и функциональная сторона: фильтры-счётчики армий
   (`client-source: army_overview_class.as:409-431`) считают ровно так —
   `armycount1` = число армий с `inaction==1` (подпись кнопки
   `MOVEMENT_OR_PROTECTION`), `armycount2` = `inaction==2` (подпись
   `TRANSFER_ARMY` — подпись действительно вводит в заблуждение, к
   передаче армий не относится), `armycount3` = `inaction==0` **или**
   `inaction==3` **вместе, одним счётчиком** (подпись `LOITER`) — то есть
   `0` и `3` для целей интерфейса неразличимы и считаются одной группой (и
   именно для них, `client-source: army_overview_class.as:1245,1323`,
   разрешена передача юнитов между армиями — `obj.inaction==0 || ==3`,
   что напрямую подтверждает decompiled-находку о доступности
   `createListUnit` для этих двух статусов). Численные значения `1` и `2`
   по-прежнему совпадают с `inaction="1"` из примера `helper` в
   `protocol-client-server.md` §2.5 — и теперь напрямую подтверждено, что
   это тот же самый realtime-канал: `client-source: parser.as:1152` имеет
   `case "army":` с комментарием разработчика `// Вроде бы ARMY_UPDATE -
   army_overview_class`, обновляющим ровно те же поля (`id_army`,
   `inaction`, `lastcoord`, `lastspeed`), что и полноэкранный ответ. Это
   закрывает открытый вопрос №3 в части буквальных значений `inaction`
   (правда, не выясняет прямо семантическую разницу между `0` и `3`, кроме
   того что интерфейс их не различает).
5. **Сортировка юнитов внутри армии** (:1517-1572, decompiled) — по `ln`
   (имя), `amount`, `attack`, `defence`, `damagecenter`, `hitpoint`,
   `level`. Поле `level` подтверждено и в реальном источнике —
   `sortUnitsInArmyBy` в `client-source: army_overview_class.as:947-984`
   содержит точно такой же список ключей сортировки, включая `level`
   (:973-975). **Важное уточнение по происхождению поля**: это НЕ
   индивидуальная характеристика конкретной группы юнитов в конкретной
   армии, присланная сервером за один раз с остальными данными армии.
   `client-source: xmlparsers.as:1534` при разборе `<armyoverview>`
   получает его так: `uobj.level = Number(game.getLocalNames(uobj.libname).level)`
   — то есть `level` берётся из клиентской статической
   библиотеки/локализации **по типу юнита** (`libname`, тот же источник,
   откуда берутся `ATTACK`/`DEFENCE`/`DAMAGE_MIN`/`DAMAGE_MAX`/`HITPOINTS`
   в этом же парсере), а не как отдельный XML-атрибут за юнита в составе
   армии — сервер в ответе `<armyoverview>` вообще не присылает `level`
   индивидуально. Это существенно **ослабляет** гипотезу «левелинг/
   ветеранство юнитов в бою»: похоже, `level` — это статический параметр
   типа юнита (что-то вроде тира/ранга юнита в дереве юнитов расы), а не
   динамически растущий опыт конкретной группы юнитов. Открытый вопрос
   частично закрыт: `level` — реальное, не декомпиляторское поле, но
   скорее всего это НЕ индивидуальное levelling, а статический атрибут
   типа юнита из клиентской библиотеки (аналог `ATTACK`/`DEFENCE`); что
   конкретно на сервере хранится под этим именем (и хранится ли вообще per-
   type в БД, раз клиент не запрашивает его отдельно) — по-прежнему не
   установлено.

---

## 2. `armycalcclass.as` и `armycreationclass.as` — Калькулятор и создание армии

**Назначение.** Два почти идентичных по структуре окна на военной базе:
`armycalcclass` — **калькулятор** («сколько будет стоить») без реальной
отправки команды создания (в файле нет ни одного вызова
`reqvestdata.create_army`, только `reqvestdata.call_tabs()` для смены
вкладки); `armycreationclass` — **боевая** форма создания армии, с полем
имени и кнопкой `CREATE_ARMY`, реально отправляющая
`reqvestdata.create_army(units[], name)`.

### Найденные поля/литералы

| Поле | Где | Смысл |
|---|---|---|
| `array_units[i].power[0..4]` | armycalcclass:198-238 | разложение «стоимости» юнита по 5 ресурсам (Kaderium/Nano/Stasis/Food/Prodium — по порядку совпадает с `resourse_player`-массивом) — прямая клиентская реализация идеи «стазис = сумма стоимости ресурсов» из `mechanics-combat.md` §4.1 (`DE_UnitAttributes::getStasis()`) |
| `array_units[i].prodiumWeight` | armycalcclass:203, armycreationclass:292,295 | «вес продиума» одного юнита — множится на количество и суммируется в `army_creation_bkg.PRODWEIGHT_VAL` — то же поле, что и `obj.prodoweight` армии в `army_overview_class` (см. п.1) |
| `this.koeff` (массив 6 чисел) | armycalcclass:94,197,209-213 | «коэффициенты» — подпись кнопки `RESOURCE_RELATIONS`, всплывающая помощь про курсы обмена ресурсов; похоже на клиентское отражение `exchangerate` из `DE_UnitAttributes::getStasis()` (`mechanics-combat.md` §4.1) |
| `array_unit_send[i] = {id, count}` | armycreationclass:236-239 | payload команды `create_army` |
| `army_current`/`army_maximum` | armycreationclass:38-44,54,97 | текущее и максимальное число армий игрока — подписи `ARMY_CURRENT`/`ARMY_MAXIMUM` |

**Подтверждено реальным источником** (`client-source: xmlparsers.as:1430-1723`,
парсеры тегов `<armycreation>` и `<armycalculator>`):

- `PW` — буквально имя XML-атрибута юнита в `<armycreation>` (`obj.prodoweight = xml.childNodes[i].attributes.PW`,
  :1453), а не только клиентское имя поля — то же самое верно для армии в
  `<armyoverview>` (:1520) и для realtime-обновления `new_armypower`
  (`parser.as:1209`, `<army id_army="" armypower="" PW=""/>`).
- `cur`/`max` — буквальные атрибуты корневого `<armycreation cur="" max="">`
  (:1435-1436), подтверждают `army_current`/`army_maximum`.
- `koeff` — буквальный атрибут корня `<armycalculator koeff="v1:v2:v3:v4:v5:v6">`
  (двоеточие как разделитель, :1672), подтверждает `this.koeff`.
- Разложение стоимости юнита в `<armycalculator>` — буквальный атрибут
  `price` на каждом `<unit>`, формат `"KA:v;NA:v;ST:v;FO:v;PR:v;SC:v"`
  (точка с запятой между парами, двоеточие внутри пары; :1693-1719).
  Это **другой** набор ключей, чем однобуквенные `K/N/S/F/P/W/C`, видимые
  в панелях тренировки юнитов/зондов (см. §0.3 и §11) — на разных экранах
  сервер использует разные соглашения об именах ключей ресурсов в цене
  (двухбуквенные для армий, однобуквенные для войсковых юнитов и зондов).
  Шестой ключ, `SC`, не укладывается в список из 5 ресурсов
  Kaderium/Nano/Stasis/Food/Prodium — что именно он означает (возможно,
  `SILVER_CREDIT`?), из кода `army_calc_menu` не следует; это отдельный
  открытый вопрос (см. ниже).

### Клиентская валидация

- **Создание армии** (armycreationclass:242): кнопка `CREATE_BUTTON`
  реально отправляет запрос, только если **имя армии непусто**
  (`_loc4_.length > 0`) **и** есть хотя бы один юнит с количеством `> 0`
  в списке (`array_unit_send.length > 0`). Никакой проверки на "хватает
  ли ресурсов" на клиенте нет вообще — это целиком отдаётся серверу
  (сравните с `protocol-client-server.md` §7.5, где для построек есть
  мягкая проверка `isResourcesEnough`, а здесь для армии её нет даже на
  клиенте).
- **`MAKE_PROSLOY`** («сделать прослойку», `extraMinus`, только
  премиум-аккаунты) — кнопка одним кликом уменьшает количество **во всех
  выбранных типах юнитов на 1**. Полезная UX-деталь для массового набора
  армии «под ресурс» — механики такого рода в PHP-документации нет и не
  должно быть, это чисто клиентское удобство.
- Без премиума (`!game.getPremium()`) поле общего количества (`AMS`)
  отключается (`_disabled()`), и `extraMinus` скрывается — то есть
  массовый ввод количества сразу по всем юнитам («глобальный скроллер») —
  привилегия премиум-аккаунта.

### Что нового

`armycalcclass` **как отдельный от `armycreationclass` экран без
отправки команды** — это находка сама по себе: сервер вообще не должен
получать никакого запроса при использовании «калькулятора армии», расчёт
полностью идёт на данных, уже присланных клиенту заранее (`array_units[i].power`).
Значит, при реализации ремастера серверу не обязательно поддерживать
отдельный «dry-run» эндпоинт для этого экрана — калькулятор воспроизводим
чисто на клиенте, если известны стоимости юнитов в ресурсах (та же
проблема с недоступностью базовых чисел юнитов, что и в
«Открытых вопросах» `mechanics-combat.md` п.1).

---

## 3. `battleoverviewclass.as` — Обзор боёв (список + фильтры)

**Назначение.** Список всех боёв игрока (текущих и прошедших) с фильтрами,
переход к текстовому отчёту или к наглядной картине боя, массовая/
одиночная капитуляция.

### Найденные поля/литералы (объект одной строки списка `this.arr[i]`)

| Поле | Локализационная подпись рядом | Смысл |
|---|---|---|
| `id_battle` | — | ID боя (используется во всех последующих командах про этот бой) |
| `start_battle` / `end_battle` | `BO_START_DATE` / `BO_END_DATE` | даты начала/конца |
| `comb_battle_name` / `comb_battle_complex` | `BO_BATTLECOMB` | сота, где идёт/шёл бой |
| `you_ranking` | `BO_RANKING` | «ваш ранг» в этом бою (не расшифровано подробнее) |
| `opponent_name` | `BO_OPPONENT` | ник противника |
| `opponent_complex_name`/`opponent_complex_num_` | — | сота противника |
| `battle_round_num` | `BO_ROUND` | номер текущего/последнего раунда |
| `winner_battle` | `BO_WINNER` | ник победителя |
| `type_battle` (`"A"`/`"D"`/**`"E"`**) | — | атакующая или защищающаяся сторона — **буквально совпадает** с `winnerCode` алфавитом `A`/`D` из `DE_Battle` (`mechanics-combat.md` §2). **Найден третий, ранее не документированный вариант — `"E"`.** Реальный парсер (`client-source: xmlparsers.as:1068,1080-1097`) читает `type_battle` из буквального XML-атрибута `battleaction` на теге `<battle>`, и рядом с ветками `"D"`/`"A"` есть третья ветка `else if(obj.type_battle == "E")` с комментарием разработчика: «Если армия на защите другой соты участвовала в бою» — то есть `"E"` это запись о бое **для игрока, чья армия участвовала в защите ЧУЖОЙ соты** (коалиционная/союзная защита), отдельно от собственно атакующей (`"A"`) или защищающей свою соту (`"D"`) стороны. Ветка в реальном источнике **пустая** (никаких полей `opponent_*` для неё не заполняется) — таким образом строка списка боёв для типа `E`, скорее всего, показывает бой без опознанного оппонента. Это прямо релевантно `mechanics-combat.md` §9 (`DE_CombDefendArmy`, коалиционная оборона) — стоит учесть в разделе о PHP-инициации боя. |
| `ra` (на объекте боя **и** на `filtersData`) | — | флаг «бой ещё идёт» (`ra=="1"`) — определяет, показывается ли кнопка капитуляции вместо кнопки отчёта |
| `sur_key` | — | локальный чекбокс «отметить для капитуляции» (клиентское состояние, не с сервера) |
| `reward` (на `filtersData`) | — | `"1"` — есть доступная награда, включает кнопку `BUT_TROFEI` → `getRewards()` |
| `filtersData.allowdays[]`, `.startday`, `.endday` | `SHOW_BATTLE_FROM/TO` | диапазон дат, доступный для фильтра списка |
| `filtersData.sotes[]`, `.opponents[]` | `ON_SOTA`/`TO_OPPONENT` | списки для комбобоксов фильтра (свои соты / противники, встречавшиеся в боях) |

**Подтверждено реальным источником** (`client-source: xmlparsers.as:1052-1150`,
парсер тега `<battleoverview>`): `ra` и `reward` — буквальные атрибуты
корневого тега (`<battleoverview ra="" reward="">`); на каждом `<battle>`
— буквальные атрибуты `id`,`starttime`,`endtime`,`battleaction`,`winner`,
`ra`,`battlecomb` (два значения через пробел: имя соты и номер комплекса),
`opponent` (для `battleaction="D"` — три значения через пробел: ник,
имя-соты-противника, номер-комплекса; для `"A"` — просто ник, без
разбивки), `opponent_race`, `ranking` (=`you_ranking`), `rc`
(=`battle_round_num`). Блок `<filter>` содержит буквальные `status`,
`winner`, а списки `sotes`/`opponents` — это **текстовое содержимое**
дочерних тегов `<sot>текст,через,запятую</sot>` / `<opp>...</opp>`
(строка, а не массив объектов), плюс `<dates cur="startday-endday">` с
перечислением диапазонов дней по месяцам внутри.

### Клиентская валидация

- Комбобоксы «сота» и «оппонент» в фильтре — **взаимоисключающие**
  (:252-265): выбор конкретной соты сбрасывает оппонента на `"ALL"` и
  наоборот. Это чисто UX-ограничение формы, не видно, требуется ли оно на
  сервере.
- Кнопка «Сдаться» (`SUR_BUT`/`checksurmov`) показывается только когда
  `obj.ra=="1"` **и** `filtersData.ra=="1"` — то есть только если конкретно
  этот бой ещё активен. Подпись кнопки при этом различается по
  `type_battle`: `"D"` → `SURRENDER` («сдаться», вы защищались),
  `"A"` → `RETREAT` («отступить», вы атаковали) — то есть в интерфейсе это
  визуально две разные концепции, но, судя по единой команде
  `SurrenderSampleBattle`/`SurrenderArrBattle`, обе ведут к одной и той же
  серверной команде (вероятно `UA_Retreat` — единственный подходящий класс
  в списке `UA_*`). **Подтверждено буквально**: обе функции в реальном
  источнике собирают один и тот же тег — `<retreat id_battle=""/>`
  (`client-source: reqvestdata.as:918-927`) — разница только в том, что
  `SurrenderArrBattle` кладёт в `id_battle` CSV-список нескольких ID.
  Это прямо расширяет незавершённый раздел 5.1
  `mechanics-combat.md` («Отступление... механика инициации отступления —
  `ME_RetreatBattle`/`UA_Retreat`, раздел не разобран детально»):
  теперь понятно, что с точки зрения игрока «отступление» и «капитуляция»
  — это одна и та же кнопка/команда, только с разной подписью в
  зависимости от того, атакующая сторона или защищающаяся.
- Массовая капитуляция (`PREM_BUT_SUR`, только премиум) собирает
  `id_battle` **только у боёв с `sur_key==true`** и отправляет их одним
  запросом `SurrenderArrBattle([...])` — то есть сервер поддерживает
  капитуляцию сразу из нескольких боёв одним вызовом.
- Подтверждение перед отступлением атакующего (:541-557) показывается
  только если `game.check_confirm_win()` включена — тот же клиентский
  тумблер, что и в `army_overview_class`.

### Что нового

- Явное подтверждение, что **отчёт о бое (текст)** и **наглядная картина
  боя (поле)** — это два разных запроса (`getReportBattle` vs
  `getShowBattle`), а не два представления одного и того же ответа.
- Механика **«награда за неравный бой» видна и на этом экране** — кнопка
  `BUT_TROFEI`/`getRewards()`, зависящая от `filtersData.reward`. Это,
  вероятно, тот же механизм, что описан в `mechanics-combat.md` §6.2
  (`_RewardNewb`) — подтверждает, что клиент действительно получает
  отдельный сигнал «у вас есть неполученная награда» и должен запросить
  её отдельной командой, а не она приходит автоматически вместе с логом
  боя.

---

## 4. `warplateclass.as` — Наглядное поле боя (по раундам)

**Назначение.** Визуальная раскладка юнитов обеих сторон боя, с
переключением между раундами (`prev_round`/`next_round`), открывается
командой `getShowBattle`.

### Найденные поля/литералы

| Поле | Где | Смысл |
|---|---|---|
| `this.arr_units` (массив раундов) | :90,105,137 | по одному элементу на раунд боя; длина массива = число раундов |
| `this.arr_units.id_battle` | :114 | ID боя (для перехода к текстовому отчёту) |
| `this.arr_units.nick[0]` | :61 | ник оппонента (для кнопки "написать письмо") |
| `round_obj.side_A[]` / `round_obj.side_D[]` | :150,182 | состав каждой стороны в конкретном раунде — **буквально `A`/`D`**, совпадает с `winnerCode`-алфавитом `A`/`D` из `DE_Battle` |
| `side_A[i].libname`, `.name`, `.amount`, `.army` | :156-177 | тип юнита (имя библиотечного графического символа), имя юнита, количество, **имя армии**, к которой юнит относится |

**Подтверждено буквально реальным источником** (`client-source:
xmlparsers.as:1152-1194`, парсер тега `<battlinfo>`): корень несёт атрибуты
`id_battle` и `nick` (список ников через запятую — может быть больше
одного, не только `nick[0]`, как в decompiled-находке — вероятно, ники
всех участников боя, а не только оппонента). Каждый `<round>` содержит
`<unit side="A" amount="" name="" army=""/>` — атрибут `side` со
значениями буквально `"A"`/`"D"` подтверждён дословно (не только по
аналогии с `winnerCode`), `name` — это XML-имя, которое клиент
переименовывает в `libname` (сам `.name` в UI-объекте после этого
заполняется через `game.getLocalNames(libname).ln`, то есть это два разных
поля с похожими именами на разных уровнях), `army` подтверждён дословно.
Есть и необработанная ветка: `<unit>` без атрибута `side` кладётся в
`obj_round.name` целиком как текст — то есть раунд может нести и не
юнит-теги, а какой-то текстовый узел (структура которого дальше не
разбирается в этой функции).

### Клиентская валидация

Валидации ввода нет — это чисто просмотровый экран (навигация по раундам
ограничена клиентски: `current_round` не выходит за `[0, arr_units.length-1]`,
:96-110).

### Что нового по сравнению с `mechanics-combat.md`

- **Раунд боя как единица UI подтверждает раздел 1** документа
  (`mechanics-combat.md`: «раунд боя — это отдельное отложенное игровое
  событие») — клиент действительно хранит и показывает данные **по
  каждому раунду отдельно**, с полным составом обеих армий на начало/конец
  этого раунда, а не только финальный итог.
- **Юниты группируются в интерфейсе по армии** (`side_A[i].army`) — то
  есть даже наглядное поле боя различает, из какой конкретно армии
  защиты пришли юниты (актуально при объединённой защите нескольких
  игроков — `DE_CombDefendArmy`, `mechanics-combat.md` §9). Это полезная
  UX-деталь для ремастера: даже когда защищается коалиция игроков, поле
  боя не «стирает» разбивку по армиям.
- Кнопка «скопировать в буфер» и «отправить по почте» (`COPY_TO_BUFFER`,
  `MAIL_TO`) на отчёте — обе берут **текст отчёта как есть**
  (`REPORT_FIELD.text`), то есть отчёт — это заранее готовый HTML/текст
  от сервера, клиент его не переформатирует.

---

## 5. `warstatistic.as` — Пакетное производство боевых юнитов ("фабрики")

**Назначение.** Экран военной базы, показывающий сетку **отдельных ячеек
производства** ("фабрик", `this.factory_array[]`, каждая со своим
`id_cell`, `type`, `status`, `unit`, `timeend`, `count`) и позволяющий
запустить производство одного типа юнита сразу на нескольких свободных
"фабриках" одним запросом. Помощь (`HELP_BUTTON`) в этом файле **не
подключена** — похоже на более простую/старую панель по сравнению с
`trainvarunitclass`.

### Найденные поля/литералы

| Поле | Смысл |
|---|---|
| `factory_array[i].id_cell` | номер ячейки-«фабрики» на военной базе |
| `factory_array[i].type[]` | список типов юнитов, которые эта фабрика умеет производить |
| `factory_array[i].status` (1 = занята) | активна ли фабрика прямо сейчас |
| `factory_array[i].unit`, `.timeend`, `.count` | что производится, когда закончится, сколько накоплено |
| `unit_array[i].price.{K,N,F,S,P,W,C}` | цена одного юнита по ресурсам + время (в ходах) на юнит |
| `this.speedUp` (`"0"`/`"1"`) | глобальный флаг ускорения производства **в 2 раза** (`this.turn_time * ... / 2`, :224-225) |

**Подтверждено реальным источником** (`client-source: xmlparsers.as:324-394`,
парсер тега `<warcomplex>`): `speedup` и `turn` — буквальные атрибуты
корневого тега; каждый `<factory id_cell="" type="" status="" unit=""
count="" timeend=""/>` — все шесть атрибутов подтверждены дословно.
**Важная деталь по цене юнита в этом парсере**: `unit_array[i].price.*`
(K/N/F/S/P/W/C) **не читаются из XML-ответа на этот запрос вообще** —
клиент берёт их из статической локализационной библиотеки
(`game.getLocalNames(unitname).K/.N/.F/.S/.P/.W/.C`, :357-363), а из
живого XML-тега `<unit unitname="" id="" type="" kad="" nano="" food=""
energy="" prod="" maxallow=""/>` читается только **содержание**
(`upkeep`, стоимость юнита в ходу — другие имена атрибутов: `kad`, а не
`K`!) и `maxallow`. То есть однобуквенные ключи `K/N/S/F/P/W/C`
подтверждены только как имена свойств клиентской локализационной
библиотеки для этого экрана, а не как имена XML-атрибутов живого ответа
сервера на запрос списка фабрик (сравните с §11 — там, наоборот, цена
зонда приходит именно живым атрибутом XML).

### Клиентская валидация

- Кнопка загрузки (`load_but`, :157-175) отправляет
  `load_pack_war_factory(cellIds, unitId, amount)` **только если** список
  выбранных активных фабрик непуст **и** `amount > 0`.
- Фабрика считается доступной для выбора («`glow_factory`», :286-327),
  только если тип выбранного юнита входит в список типов, которые эта
  фабрика умеет производить, **и** (если фабрика уже занята) — только
  если производимый ею юнит совпадает с выбранным (то есть можно
  «добавить в очередь» к уже идущему производству того же юнита, но
  нельзя выбрать занятую фабрику для другого юнита).
- `allowMax` для количества юнитов (:64-88) считается на клиенте как
  минимум по всем 6 ресурсам (`floor(текущий_ресурс / цена_юнита)`) —
  то есть клиент **заранее** ограничивает максимум в скроллере количества
  по нехватке ресурсов, до отправки запроса (аналог мягкой серверной
  проверки `isResourcesEnough` из `protocol-client-server.md` §7.5, но
  реализован здесь и на клиенте).

### Что нового

Подтверждает, что на военной базе физически несколько **независимых
слотов производства** («фабрик»), каждый со своим статусом занятости —
это архитектурная деталь производственного здания, отсутствующая в
`mechanics-combat.md` (который сфокусирован на самом бое, а не на
тренировке юнитов). Полезно для ремастера построек военной базы.

---

## 6. `trainvarunitclass.as` — Тренировка боевого юнита (детальная панель)

**Назначение.** Более подробная альтернатива/дополнение к `warstatistic` —
список типов боевых юнитов расы с полными характеристиками, ценой,
условиями (наука), кнопками тренировки/отмены. Help-топик
`"TRAIN_WAR_UNIT_MENU"`.

### Найденные поля/литералы

| Поле | Смысл |
|---|---|
| `unit_array[i].AWAIL` (`"1"`/иначе) | доступен ли юнит расе прямо сейчас |
| `unit_array[i].allowMax` | максимально допустимое количество юнита к постройке (сервер уже посчитал — не клиент) |
| `unit_array[i].sctarg` / `.sccur` (CSV-строки, до 7 значений) | требуемый vs текущий уровень до 7 наук-предпосылок — подсвечивается красным столбец, где `sccur < sctarg` |
| `obj.ATTACK` / `.DEFENCE` / `.DAMAGE_MIN` / `.DAMAGE_MAX` / `.HITPOINTS` | боевые характеристики юнита — **буквально совпадают по именам** с атрибутами `FIGHT_ATTRIBUTE` из `mechanics-combat.md` §3.11 (`ATTACK`, `DEFENCE`, `DAMAGE_MIN`, `DAMAGE_MAX`, `HITPOINTS`)! Это прямое клиентское подтверждение имён атрибутов, упомянутых в PHP-коде (`DE_UnitsGroupWarPower::_formClear()`) |
| `obj.upkeep.{kad,nano,energy,food,prod,work,time}` | стоимость содержания юнита за ход (отдельно от цены постройки `obj.price.*`) |
| `obj.inprogres`, `obj.enddate` | сколько юнитов сейчас в производстве и когда закончится |
| `new setting().loadSetting("buildCreateWarUnit"+c+b, ...)` | клиентское (локальное) запоминание последнего выбранного юнита **по конкретной ячейке-зданию** (`c`+`b` из XML-фрагмента активной ячейки, см. §0.2) |

**Подтверждено и уточнено реальным источником** (`client-source:
xmlparsers.as:396-474`, парсер тега `<warunit>` — обратите внимание, тег
называется `warunit`, а не `trainwarunit`, хотя команда запроса — именно
`<trainwarunit>`, см. §0.5): каждый `<unit unitname="" id="" type=""
sctarg="" sccur="" avail="" count="" enddate="" kad="" nano="" energy=""
food="" prod="" work=""/>`. Важные уточнения:

- `AWAIL` в таблице выше — имя клиентского свойства; буквальный XML-атрибут
  — `avail` (нижний регистр, без опечаток).
- `obj.inprogres`/`obj.enddate` — буквальные атрибуты `count`/`enddate`.
- `ATTACK`/`DEFENCE`/`DAMAGE_MIN`/`DAMAGE_MAX`/`HITPOINTS` **не приходят
  живым атрибутом в этом ответе вообще** — как и в `warstatistic` (см.
  выше), они читаются из статической библиотеки `game.getLocalNames(unitname)`
  (:414-418). Значит совпадение этих имён с `FIGHT_ATTRIBUTE` из
  `mechanics-combat.md` §3.11 подтверждает соглашение об именах в
  клиентской библиотеке юнитов, но не доказывает, что именно эти же имена
  используются как XML-атрибуты в живом сетевом трафике для этого экрана.
- `obj.price.*` (K/N/S/F/P/W) — тоже из библиотеки (`game.getLocalNames(id_name).K/.N/.../.W`,
  :432-438), а не из живого ответа; **время** (`obj.price.time`) — особый
  случай, вычисляется как `game.getLocalNames(id_name).C * xml.attributes.turn`,
  то есть библиотечная константа `C` умножается на длительность хода,
  присланную live-атрибутом `turn` на корне `<warunit turn="" speedup="">`.
- А вот `obj.upkeep.*` (kad/nano/energy/food/prod/work) **приходят живыми
  атрибутами** на каждом `<unit>` — то есть цена постройки юнита («сколько
  стоит построить») в этом ответе целиком клиентская (из локальной
  библиотеки), а содержание («сколько стоит держать в строю за ход»)
  реально приходит с сервера каждый раз.

### Клиентская валидация

- Кнопка "Тренировать" (:319-332) отправляет `trainWarUnit(count, id_unit)`
  **только если** `amountCount.getValue() > 0`.
- Кнопка "Отмена" (:334-348) активна и кликабельна, только если
  `obj.inprogres` — валидное положительное число; иначе показывается
  подсказка `FACTORY_IS_FREE` вместо реальной отмены.
- Всплывающая подсказка при наведении (:115-186) заранее подсвечивает
  красным (`0xFF0000`) конкретные ресурсы, которых не хватает у игрока
  для постройки юнита (`Number(current_resource) < Number(price)`), и
  отдельно подсвечивает недостающие уровни наук (`sccur < sctarg`) —
  **всё это происходит до нажатия кнопки**, чисто предупредительно.

### Что нового

Прямое подтверждение имён `ATTACK`/`DEFENCE`/`DAMAGE_MIN`/`DAMAGE_MAX`/
`HITPOINTS` в клиентских данных — важно, поскольку `mechanics-combat.md`
«Открытые вопросы» п.1 отмечал, что базовые числа юнитов «не встречаются
в PHP-коде» (лежат в БД). Реальный источник уточняет, ГДЕ именно клиент их
получает: не живым атрибутом в ответе на открытие панели тренировки, а из
статической клиентской библиотеки/локализации (`game.getLocalNames`,
загружается один раз при входе — см. `reqvestdata.get_localnames()`,
:352-372). То есть при восстановлении данных юнитов из снапшота БД для
ремастера нужно искать именно эти ключи, но не в трафике запроса
тренировки юнита, а в том, что сервер присылает как «локализацию»/
графическую библиотеку при логине.

---

## 7. `powershieldclass.as` — Силовой щит

**Назначение.** Постройка/функция военной базы с явной state-machиной
из пяти состояний (`obj_shield.status` — строки `"0".."4"`), help-топик
`"POWER_SHIELD"`. Ни разу не упомянута в `mechanics-combat.md`.

### Найденные поля/литералы

| `obj_shield.status` | UI-поведение | Метод |
|---|---|---|
| `"1"` | подпись `CHARGE`, кнопка "Зарядить" | `init_menu3()` → `reqvestdata.poweractivation()` |
| `"3"` | подпись `SELECT_TIME`, скроллер количества ходов (`minturn`..`maxturn`), кнопка "Активировать" | `init_menu1()` → подтверждение (`CONFIRM_POWERSHIELD`) → `reqvestdata.powershieldenabled(value)` |
| `"0"`, `"2"`, `"4"`, default | просто "в действии", `time_f`/`stat_f` жёстко = 0 (похоже на неполную реализацию — см. «Открытые вопросы») | `in_Action()` |

| Поле | Смысл |
|---|---|
| `obj_shield.turn` / `.price` | цена в стазисе за один ход активации; итоговая цена = `value * price`, время = `game.turnToTime(value * turn)` |
| `obj_shield.minturn` / `.maxturn` | границы скроллера длительности активации |
| `obj_shield.desc1` | текстовое описание текущего состояния (готовое, от сервера) |

**Подтверждено реальным источником** (`client-source: xmlparsers.as:1195-1222`,
`parser.as:1542-1544`): корневой тег ответа — буквально `<powershild>`
(опечатка/сокращение — без «e», не `<powershield>`!), с атрибутами `turn`,
`status`, `price` буквально, и **`mint`/`maxt`** (а не `minturn`/`maxturn`
— это только имена клиентских свойств после парсинга). `obj_shield.desc1`/`.desc2`
на самом деле приходят из **разных** дочерних тегов — `<desc>` и `<desc1>`
соответственно (то есть у клиентского `desc2` XML-имя `desc1`, что легко
перепутать при разборе протокола).

### Клиентская валидация

- Явной валидации ввода почти нет — сам скроллер (`initAmountScroll`)
  ограничивает диапазон `minturn..maxturn`, дальше значение просто
  умножается на цену без проверки, хватает ли ресурсов (в отличие от
  `protectshieldclass`, см. ниже, где такая проверка явно есть) — это
  расхождение стоит перепроверить: либо здесь и правда нет клиентской
  проверки ресурсов, либо она сделана в другом, не разобранном файле.
- Активация (`status=="3"`) требует подтверждения в модальном окне
  (`createConfirmWindow`, текст `CONFIRM_POWERSHIELD`) **всегда**, не
  зависит от тумблера `check_confirm_win()` — то есть это единственное
  найденное в 12 файлах действие с **обязательным**, не отключаемым
  подтверждением.

### Что нового

Это целиком новый, не описанный в `mechanics-combat.md` механизм — судя
по имени и по "заряд → выбор времени → активация", это, вероятно,
временный защитный эффект для военной базы/соты, отдельный от
"защитного поля колонии" (`protectshieldclass`, ниже) и от `TEMPORARY_DEFEND`
после захвата колонии (`mechanics-combat.md` §8). Нужно искать в PHP
`UA_PowerShildActivate`/`UA_PowerShildStart`/`UA_RestoreProtection`-
соседей (упомянуты только в списке §6 `protocol-client-server.md`, но не
разбирались построчно) для подтверждения точного эффекта.

---

## 8. `protectshieldclass.as` — Восстановление защитного поля колонии

**Назначение.** Прямое клиентское воплощение «защитного поля колонии»
(`DE_CombProtectiveField`) из `mechanics-combat.md` §8 — там оно
упоминается только как условие захвата («защитное поле колонии равно
0%»); здесь видно панель, где владелец колонии может **вручную
восстанавливать** это поле. Help-топики `PROTECT_FIELD_DESCRIPTION`/
`PROTECT_FIELD_ACTION`.

### Найденные поля/литералы

| Поле | Смысл |
|---|---|
| `obj_shield.proc` | текущий % защитного поля (0-100), рисуется как `bar.mask._xscale` + текст `"NN%"` |
| `obj_shield.price_res[]` (5 элементов) | цена восстановления **одного процентного пункта** по каждому из 5 базовых ресурсов (Kaderium/Nano/Stasis/Food/Prodium — по порядку совпадает с `currentRes[0..4]`) |
| `obj_shield.price_silver` | альтернативная цена одного пункта восстановления в `SILVER_CREDIT` |

**Подтверждено реальным источником, включая прямой ответ на открытый
вопрос №5** (`client-source: xmlparsers.as:1225-1238`, `parser.as:1545-1547`):
корневой тег ответа — буквально `<protectedfield>` (совпадает с именем
дампа `protectedfield.txt` из литеральных примеров), атрибуты
**`currentpercent`** (=`obj_shield.proc`), **`price_res`** (буквальный
атрибут — строка через запятую, `.split(",")`) и **`price_silver`** —
все три подтверждены дословно, включая точные имена (не только
клиентские алиасы). Прямо над функцией парсера, в реальном исходнике, стоит
комментарий разработчика:

```text
// НОВЫЙ ЗАЩИТНЫЙ ЩИТ ДЛЯ ЗАХВАЧЕННЫХ КОЛОНИЙ НАХ и ФПЕСДУ
```

Это **прямое, буквальное подтверждение**, что `protectshieldclass` —
механизм именно для **захваченных колоний** (а не для родной территории
игрока вообще), и что это отдельная, добавленная позже фича (слово
«НОВЫЙ»), отличная от `powershieldclass`. Это существенно продвигает
открытый вопрос №5: `protectshieldclass` — это подтверждённо не то же
самое, что силовой щит, и подтверждённо привязан к судьбе именно
захваченных колоний, что напрямую стыкуется с `mechanics-combat.md` §8.
Сама функция также содержит собственный комментарий
`trace("PROTECTIVE_FIELD_GENERATOR - NEED ДОРАБОТАТЬ")` («нужно
доработать») — разработчик сам считал эту панель незаконченной на момент
этой ревизии клиента.

### Клиентская валидация

- Скроллер количества процентов по умолчанию выставлен на
  `100 - obj_shield.proc` (:147) — то есть по умолчанию предлагается
  восстановить **до полных 100%** сразу.
- `recalc(val)` (:102-145) на каждое изменение значения скроллера заново
  проверяет **отдельно по каждому из 5 ресурсов** и **отдельно по
  серебру**, хватает ли текущего запаса игрока (`currentRes[i]`); если
  нет — сумма подсвечивается красным (`990000`) и соответствующая кнопка
  оплаты (`action_res_btn` либо `action_silver_btn`) блокируется
  (`enabled=false`, `_alpha=50`). Это две **независимые** кнопки оплаты
  (ресурсами или серебром), каждая блокируется по своей нехватке.
- Обе кнопки оплаты **полностью** заблокированы, если поле уже на 100%
  (:162-168) — восстанавливать нечего.
- Значение `val==0` тоже блокирует обе кнопки (нельзя отправить «восстановить
  0 пунктов»).

### Что нового

Прямое дополнение к `mechanics-combat.md` §8 «Захват колонии»: защитное
поле колонии — это **управляемый игроком, платный, восстанавливаемый**
ресурс (а не просто результат вычислений после боя), с двойным способом
оплаты (обычные ресурсы или премиум-серебро) и ценой за процентный пункт.
Раздел 8 серверного документа не упоминает никакого механизма ручного
восстановления поля — только его обнуление при захвате колонии
(`ME_CaprureColony::Capture()`) — стоит доискать `UA_RestoreProtection`
в PHP для точных формул цены/скорости восстановления.

---

## 9. `scannermenuclass.as` — Журнал сканирования (шпионаж)

**Назначение.** Хронологический лог всех «сканирований»/разведдействий
(входящих и исходящих), с фильтрами и возможностью пере-сканировать
выбранные записи. Help-топик `"SPY_LOGS"`.

### Найденные поля/литералы (запись лога, `object_list[i]`)

| Поле | Подпись | Смысл |
|---|---|---|
| `date` | `TIME` | время события |
| `action` | `TYPE_ACTION` | тип действия |
| `addrfrom` / `addrto` | `FROM`/`TO` | координаты источника/цели |
| `typelog` | `DATA`/`INFO` | сжатое текстовое описание результата |
| `zcolor` | — | цвет строки (для визуального различения своих/чужих/типов событий, как в `spying_class`, см. ниже) |
| `id` | — | ID записи (для `getSpyLog(id)`) |
| `scancheck` | — | клиентский чекбокс "выбрать для пересканирования" |
| `filter_obj.colonys[]` (`{id,name,addr,stat,act}`) | `SELECT_COLONY` | список своих колоний для фильтра |
| `filter_obj.typesmove[]` (`{id,name,stat,act}`) | `SELECT_TYPE` | список типов действий для фильтра |
| `filter_obj.time.{min,max,cur}` | `TIME_AREA` | диапазон дат для фильтра, формат `"D:H:M:S"` (день:час:мин:сек, судя по `initDateField`) |
| `filter_obj.maxAllowZond` | — | сколько всего зондов можно потратить на пересканирование за раз |

**Подтверждено реальным источником** (`client-source: xmlparsers.as:34-118`,
парсер тега `<spylog>`): в коде есть буквальный пример строки записи лога:
`<object id="56" scan="0" action="Razvedko" addrfrom="1.2.3" addrto="2.6.6"
namefrom="oleg123oleg" nameto="Anna" date="15.01 13:15" typelog="FullLog" />`
— подтверждает дословно атрибуты `id`,`action`,`addrfrom`,`addrto`,`date`,
`typelog`, и добавляет ранее не упомянутый `scan`. Текущая версия кода
`namefrom`/`nameto` больше не читает (закомментировано) — вместо них
читаются `zname`/`zcolor`, тоже буквальные атрибуты (пример в
комментарии: `zname="Зонд Исследователь" zcolor="#FFFFFF"`). Для `<filter
maxzond="">` — `maxAllowZond` подтверждён как буквальный атрибут `maxzond`.
Дочерние теги `<types><type id="" name="" cur=""/></types>`,
`<colonys><colony id="" name="" addr="" cur=""/></colonys>`, `<time val=""
min="" max=""/>`, `<name def=""/>` — все атрибуты подтверждены дословно.

### Клиентская валидация

- `recalc_price()`/`completePrice()` (:307-379): максимально допустимое
  количество зондов **на одну запись** пересчитывается динамически как
  `maxAllowZond / (число отмеченных записей)` — то есть общий лимит
  зондов **делится поровну** между всеми одновременно выбранными для
  пересканирования записями, и клиент сам следит, чтобы сумма не
  превысила лимит (`ZondCountAllow.text = maxAllowZond - countZond*selectedCount`).
- Кнопка `SCAN_SELECTED` (только премиум) отправляет `requestScan(ids[])`
  и **сбрасывает** `scancheck` у отправленных записей сразу на клиенте
  (оптимистичное обновление UI, до ответа сервера).

### Что нового

Существование отдельного «лимита зондов на одно сканирование», который
**делится** между выбранными целями при массовом пересканировании — это
клиентская UX-деталь поверх `GL_SpyMaxCountZondsOnComb`/
`GL_SpyCountZondPrice`, упомянутых как неразобранные в `mechanics-combat.md`
§12. Похоже, что `GL_SpyMaxCountZondsOnComb` — это именно тот самый общий
"maxAllowZond" лимит, который клиент здесь пытается заранее не превысить.

---

## 10. `spying_class.as` — Отправка зондов на задание

**Назначение.** Экран, где игрок выбирает тип зонда, действие и цель, и
отправляет группу зондов. Help-топик `"SPY_ACTION"`. Это самая ценная
находка по разделу «Шпионаж» — раньше в `mechanics-combat.md` про зонды
не было известно почти ничего конкретного.

### Найденные поля/литералы

| Поле | Смысл |
|---|---|
| `object_list.allow_zond[]` (`{id, ln, libname, allow, allow_count}`) | список типов зондов, доступных к отправке, с текущим наличным количеством |
| `object_list.actions[]` (`{id, name, addrallow, timeallow}`) | список возможных действий зонда; **`"RESEARCH"`** — единственное действие, буквально встреченное по имени (:400) |
| `object_list.ring_allow[]` | список колец, разрешённых как цель (зависит от типа зонда/действия) |
| `object_list.speed.{min,max,def}` | диапазон скорости отправки |
| `object_list.time.{min,max}` | диапазон длительности действия (для действий с `timeallow==1`) |
| `object_list.numring` | последнее выбранное кольцо (запоминается между открытиями окна) |

**Подтверждено и уточнено реальным источником** (`client-source:
xmlparsers.as:199-323`, парсер тега `<spying>`): корень несёт буквальные
атрибуты `turn`, **`battle_xp`** (=`object_base.battleexp`, см. ниже про
связь с «военным рангом» в §11) и `numring`. Внутри `<onbase>`:
`addrallow`/`timeallow` в таблице выше — это **имена клиентских
переменных**, реальные XML-атрибуты на `<action id="" name="" coord=""
time=""/>` — буквально `coord` и `time` (не `addrallow`/`timeallow`).
`object_list.time.{min,max,def}` подтверждены как `mintime`/`maxtime`/`deftime`;
`object_list.speed.{min,max,def}` — как `maxs`/`mins`/`defs`. Список
зондов собирается из трёх параллельных CSV-атрибутов на `<onbase>`:
`type_zond` (номера типов), `allow` (сколько доступно к отправке каждого
типа) и `id` (буквальный ID типа — см. §11, реально это `"Z1"`/`"Z2"`).
`ring_allow[]` строится из односимвольного (`"0"`/`"1"` на позицию кольца)
атрибута `rings`. Чекбокс **`ONE_ROUND_BATTLE`** — реален и в реальном
источнике: `client-source: spying_class.as:198` создаёт его через
`addcheck(...,game.getLocalNames("ONE_ROUND_BATTLE").ln,false)`, то есть
это не декомпиляторский артефакт — подтверждает существование фичи, но
её точный эффект по-прежнему не установлен (открытый вопрос №6 не
закрыт полностью).
| `filter_obj.listGroup[]` (`{id, log, action, count, time, coord, zname, zcolor}`) | список **активных** (уже отправленных) групп зондов — это отдельные записи от журнала сканирования |
| цвет группы `"0x006600"` (зелёный) / `"0x660000"` (тёмно-красный) | подсчитываются раздельно в `COUNT_ZONDS_EX` (зелёный) и `COUNT_ZONDS_SC` (красный) счётчиках | различие "исследователь"(EX)/"разведчик"(SC) по цвету статуса |

### Два типа зондов — ключевая находка

По коду `initAction()` (:367-416), в паре с `zondcreateclass.createListAllow()`
(см. §11), видно, что `allow_zond[0]` — это **"скаут"/разведчик**
(`SPY_ZOND_SCOUT`), а `allow_zond[1]` — **"исследователь"**
(`SPY_ZOND_EXPLORER`):

- Если выбранный тип зонда — **первый в списке (скаут)**: доступен
  **полный** список действий из `object_list.actions[]`.
- Если выбранный тип зонда — **второй в списке (исследователь)**:
  список действий **жёстко фильтруется до одного пункта** —
  `id == "RESEARCH"` (:400-408 — сравнение буквальной строки).

Это значит: скаут — многофункциональный зонд (разведка, вероятно и
что-то ещё), а исследователь **умеет только исследование** (`RESEARCH`,
скорее всего — ускорение/содействие науке или разведка технологий
противника — не установлено точно, что делает `RESEARCH` на цели).

### Поле цели и особый чекбокс

Если `object_list.actions[id].addrallow==1` (действие требует указания
координат — реально это атрибут `coord` на `<action>`, см. выше),
показываются: `COMBO_RING` (кольцо), `COMPLEX_NUM`, `SOTA_NUM` (номер
комплекса/соты цели, `ring.complex.sota`), и **чекбокс с локализационной
подписью `ONE_ROUND_BATTLE`** (:205; внутреннее имя MovieClip в реальном
источнике — `CHECK_ONE_ROUND`, `client-source: spying_class.as:198`). Это
прямая, ранее не документированная зацепка к
`ME_SpyZondsBattle`/`SU_SpyZondsBattleCalculator`, упомянутым в
`mechanics-combat.md` §12 как неразобранные — по названию похоже, что
при попытке разведать защищённую цель зонд может ввязаться в «бой в один
раунд» с обороной, и этот чекбокс, вероятно, включает/выключает такой
режим (или соглашается на риск). Точный эффект **не установлен** — это
гипотеза по имени строки.

Если действие требует времени (`timeallow==1`, например `RESEARCH`) —
показывается `count_time` (длительность в ходах) вместо поля скорости;
иначе — поле `SPEED_ZOND` (скорость полёта, как у обычных армий).

### Клиентская валидация

- Отправка (`SEND_ZOND.onRelease`, :259-284) реально шлёт
  `send_zond(...)`, только если `count_send.getValue() > 0` — иначе
  флаг `_loc3_=false` и запрос не уходит вообще, без сообщения об ошибке.
- Максимум зондов к отправке в одной группе (`initAmountScroll` для
  `count_send`) ограничен `allow_zond[i].allow_count` — то есть клиент
  заранее знает точное наличное количество зондов данного типа и не даёт
  ввести больше.

### Что нового по сравнению с `mechanics-combat.md`

Раздел 12 серверного документа был почти пустой заглушкой. Теперь ясно:

1. Есть минимум **два типа зондов** с разной ролью (скаут = разведка,
   исследователь = только `RESEARCH`).
2. Отправка зонда требует выбора **действия** (`action`), а не только
   координат — то есть один и тот же зонд может быть отправлен с разными
   «намерениями» (из которых подтверждено буквально только `RESEARCH`).
3. Существует потенциальный **режим боя в один раунд** при разведке
   (чекбокс `ONE_ROUND_BATTLE`) — важная зацепка для дальнейшего разбора
   `ME_SpyZondsBattle`/`SU_SpyZondsBattleCalculator` в PHP.
4. Часть действий требует времени (научные?), часть — скорости
   (перелётные) — то есть зонды делятся по механике на «летящие к цели»
   и «работающие на месте отправки в течение N ходов» в зависимости от
   выбранного действия.

---

## 11. `zondcreateclass.as` — Тренировка (постройка) зондов

**Назначение.** Постройка новых зондов на военной базе — по структуре
почти зеркало `trainvarunitclass`, но с прогрессивной ценой и
дополнительным «ресурсом» — очками военного ранга. Help-топик
`"SPY_TRAIN"`.

### Два типа зондов — подтверждение названий

`createListAllow()` (:238-282) жёстко, по индексу в массиве
`allow_zond`, назначает подписи:

```actionscript
if(_loc4_ == 0) { game.init_decor_button(_loc2_, ..., game.getLocalNames("SPY_ZOND_SCOUT").ln); }
if(_loc4_ == 1) { game.init_decor_button(_loc2_, ..., game.getLocalNames("SPY_ZOND_EXPLORER").ln); }
```

— то есть локализационные ключи `SPY_ZOND_SCOUT` и `SPY_ZOND_EXPLORER`
буквально подтверждены как имена двух типов зондов в игре (согласуется
с `spying_class`, §10 выше, и с `ZOND_GROUP_SCOUT`/`ZOND_GROUP_EXPLORER`
— похожими, но другими ключами, используемыми там же как подписи
счётчиков активных групп).

### Формула прогрессивной цены

`calculatePriceWithProgressiv(base_price, delta_price, current_ready, count_for_create)`
(:29-67) — цена **растёт геометрически** с каждым следующим построенным
зондом этого типа:

```
если delta_price == 1:
    цена = base_price * count_for_create      (линейно, без прогрессии)
иначе:
    цена = base_price * [(1 − delta_price^(ready+count)) / (1 − delta_price)
                        − (1 − delta_price^ready)         / (1 − delta_price)]
```

— это сумма геометрической прогрессии между позициями `ready` и
`ready+count`, то есть **каждый следующий зонд дороже предыдущего** в
`delta_price` раз (стандартная модель "цена растёт с накопленным
количеством"). Применяется отдельно к каждому виду цены — ресурсам
(`K/N/S/F/P/W`), **военному рангу** (`WR`, без округления — `keyRound=false`)
и времени постройки (`C`).

### Найденные поля/литералы

| Поле | Смысл |
|---|---|
| `allow_zond[i].price.{K,N,S,F,P,W,C,WR}` | базовая цена (за 1-й зонд) по каждому виду ресурса + `WR` |
| `allow_zond[i].delta.{K,N,S,F,P,W,C,WR}` | коэффициент роста цены на каждый следующий зонд, по каждому ресурсу отдельно |
| `allow_zond[i].ready` / `.inact` | сколько уже построено / сколько ещё "в очереди"/недоступно |
| `objectData.xprank` | текущий «военный ранг» игрока (в очках `WR`) |
| `"FULLVARRANK"` | подпись поля общего военного ранга |
| `obj_upd[i].newxp` | обновлённое значение ранга после события (например, после завершения постройки) |

**Подтверждено дословно реальным источником**
(`client-source: xmlparsers.as:3434-3500`, парсер тега `<zondcreate>`;
`client-source: zondcreateclass.as:57-111`):

```xml
<zondcreate battle_xp="0.0483">
<allowzond type="1" inaction="0" count="220" already="0"
  price="K:1,N:12,S:34,F:4,P:6,W:54,C:60,WR:2.2"
  delta="K:1.04,N:1.5,S:1.04,F:1.04,P:1.06,W:1,C:1,WR:1.2"
  id="Z1" awail="1"/>
<allowzond type="2" ... id="Z2" awail="0"/>
</zondcreate>
```

**Находка (2026-09-08) — исправление характеристики источника примера
выше.** Этот XML взят из `SPHERE_client/createzond.txt` — ранее
считался «живым тестовым дампом», но это неверно: другие атрибуты в том
же файле (`<tab ln="sdgdsfg"/>`, `ln="dsfgdfg"/>`, `ln="dfhfgh"/>`,
`ln="billl"/>`) — явно бессмысленные строки-заглушки, которые
разработчик вводил вручную при тестировании UI, а не локализованные
названия вкладок с реального сервера. Это клиентский тестовый файл
(аналогично найденной ранее заглушке `requestRanking.as` в Фазе 3.10),
а не подтверждённый живой захват трафика — характеристика «живой
тестовый дамп» из более раннего прохода была неточной.

**Серверная формула найдена и подтверждена кодом (2026-09-08,
`GL_SpyCountZondPrice.class.php`, `TL_SpyTrainUnit.class.php`)** —
`_getProgressionSum($start, $coef, $count) = $start * (1 -
pow($coef,$count)) / (1 - $coef)`, цена = `progressionSum(current+count)
- progressionSum(current)` — **алгебраически идентична** клиентской
`calculatePriceWithProgressiv` выше. НО коэффициент роста
(`$coef = 1 + worldSettings.get(<unit_name>_PRODUCTION_COST_INCREASE)`)
у сервера **один на юнит, не один на ресурс** —
`TL_SpyTrainUnit.class.php:37` буквально строит
`delta="K:$delta,N:$delta,S:$delta,F:$delta,P:$delta,W:1,C:1,WR:1"` с
ОДНОЙ переменной `$delta`, подставленной 5 раз. То есть пример выше
(разные `delta` по K/N/S/F/P) не может быть настоящим ответом текущего
сервера — реальный ответ всегда имеет одинаковый `delta` для K/N/S/F/P
у одного типа зонда (и `W:1,C:1,WR:1` — без роста).

> ✅ **Реальные значения на мире «Сфера Ориона» (источник: БД,
> live-запрос, 2026-09-08)**: `SPY_ZOND_SCOUT_PRODUCTION_COST_INCREASE
> = 0.01` (рост 1% за зонд), `SPY_ZOND_EXPLORER_PRODUCTION_COST_INCREASE
> = 0` (рост отсутствует — линейная цена, `delta=1` для этого типа на
> этом мире). Также подтверждён отдельный путь оплаты Explorer-зондов
> очками боевого опыта: `SPY_ZOND_EXPLORER_BATTLE_XP_COST = 0.2` — цена
> в `battle_xp` считается отдельной веткой `GetMaxByResource()`, не
> через обычный `PRODUCTION_COSTS`.

Это подтверждает **дословно, без единой оговорки**:

- `price`/`delta` — именно однобуквенные ключи `K/N/S/F/P/W/C` плюс
  `WR`, через запятую, `ключ:значение` — реальный клиентский код читает
  их именно так (`obj.price["K"]`, `obj.price["WR"]`,
  `client-source: zondcreateclass.as:59-111`). В отличие от
  `armycalculator` (§2), здесь ключи однобуквенные и разделитель —
  запятая, а не точка с запятой; конвенция различается по экрану.
- `allow_zond[i].ready` — буквальный XML-атрибут называется **`already`**
  (не `ready`), а `.inact` — буквально **`inaction`** (то же имя
  атрибута, что и у статуса армии, но другой смысл: здесь это «сколько
  ещё недоступно/в очереди», а не движение).
- Тип зонда идентифицируется буквальным `id="Z1"`/`id="Z2"` (не
  `SPY_ZOND_SCOUT` напрямую) — по этому `id` клиент ищет `desc`/`ln` в
  локализации (`game.getLocalNames(obj.id)`); судя по порядку и по
  базовой цене (`Z1` дешевле стартует, `Z2` в примере ещё `awail="0"`,
  то есть недоступен) это, вероятно, скаут и исследователь соответственно,
  но само значение `"Z1"`/`"Z2"` нейтрально и не содержит слов
  «scout»/«explorer» — подписи `SPY_ZOND_SCOUT`/`SPY_ZOND_EXPLORER` — это
  отдельный локализационный ключ, используемый только для подписи кнопки
  выбора зонда по индексу в массиве (`client-source: zondcreateclass.as:326-327`),
  а не значение поля `id`.
- **`objectData.xprank` подтверждён дословно как атрибут `battle_xp`** на
  корне `<zondcreate>` (`client-source: xmlparsers.as:3442`) — то есть это
  буквально то же имя поля, что и `object_base.battleexp` на экране
  `spying_class` (`<spying battle_xp="">`, §10 выше). Это напрямую
  закрывает основную часть открытого вопроса №8: «военный ранг» зондов —
  это подтверждённо тот же счётчик, что сервер называет `battle_xp`
  («опыт боя»), что делает связь с `ME_BattleXP`/боевым опытом из
  `mechanics-combat.md` §11 уже не гипотезой, а фактом соответствия
  имён. При этом в тестовом дампе значение дробное (`battle_xp="0.0483"`)
  — это, скорее всего, не «очки опыта» в чистом виде, а некоторая
  вычисленная доля/рейтинг (например, прогресс к следующему рангу или
  нормализованный показатель) — вопрос, **тратится ли** это значение при
  постройке зонда безвозвратно, или это только порог допуска, по-прежнему
  открыт (данных о списании в разобранном коде нет).
- `obj_upd[i].newxp` подтверждён как буквальный атрибут **`new_xp`** в
  realtime-обновлении `trainspyok` (`client-source: parser.as:1065-1088`,
  см. §0.5) — но там это поле приходит **по каждому типу зонда отдельно**
  внутри массива обновлений, а не одним общим значением, как
  `dataObject.xprank`/`battle_xp` на полноэкранном ответе — возможно, это
  разные величины со схожим названием (per-type прогресс против общего
  ранга), а не гарантированно один и тот же счётчик; стоит перепроверить
  на сервере при разборе `ME_BattleXP`.
- Реальный тег отправленной в «работу» группы зондов подтверждён примером
  из `parser.as:1062`: `<sendzondok id="SPY_ZOND_SCOUT" count="11" .../>`
  — здесь, в отличие от `zondcreate`, `id` уже буквально равен
  `SPY_ZOND_SCOUT` — то есть у сервера есть минимум два разных способа
  идентифицировать один и тот же тип зонда в разных ответах (`"Z1"` в
  `<zondcreate>`, `"SPY_ZOND_SCOUT"` в `<sendzondok>`), и клиент такие
  через `obj.libname = "ZOND_" + <раса> + type` /
  `game.getLocalNames(...)` сводит к единой картинке/подписи.

### Клиентская валидация

- Кнопка "Тренировать" (:215-227) шлёт `trainzondunit(count, id_zond)`
  только если `id_zond != undefined` **и** `count > 0`.
- Красная подсветка недостатка (:73-140) считается **отдельно по каждому
  из 6 ресурсов и по `WR`** — сравнение прогрессивной цены с текущим
  запасом игрока (`debug.link.resourse_player.*`) и с `objectData.xprank`
  для `WR`. То есть военный ранг **визуально трактуется как ресурс** —
  недостаточно ранга блокирует так же, как недостаточно кадериума.
  Неясно, тратится ли ранг безвозвратно при постройке, или это просто
  порог/ценз («нужно достичь ранга N, чтобы построить зонд типа X») —
  явно не следует из клиентского кода (см. «Открытые вопросы»).

### Что нового

- **Прогрессивное ценообразование** зондов (геометрическая прогрессия по
  количеству уже построенных) — это принципиально отличается от
  описанной в `mechanics-combat.md` линейной стоимости боевых юнитов
  (`price.K * amount` и т.п. в `armycalcclass`/`trainvarunitclass`) —
  зонды **единственный** найденный тип юнита с такой моделью цены.
- **«Военный ранг» (`WR`/`xprank`) как отдельная валюта**, нужная именно
  для постройки зондов — не описана вообще нигде в серверной
  документации. Вероятный кандидат в PHP — `ME_BattleXP`/производные
  классы военного опыта, упомянутые как неразобранные в
  `mechanics-combat.md` §11 («Опыт за бой... не завершено»). Похоже, что
  накопленный за счёт боёв игровой опыт — это тот же самый счётчик,
  который тратится/сверяется здесь при постройке зондов, то есть раздел
  11 серверного документа и раздел 12 (шпионаж) могут быть связаны через
  этот общий "ранг".

---

## Открытые вопросы

1. ~~`reqvestdata.as`/`xmlparsers.as` не входили в это исследование~~ —
   **закрыто.** Оба файла прочитаны полностью (`reqvestdata.as`, 1077
   строк) и по всем боевым/армейским/шпионским функциям (`xmlparsers.as`,
   `parser.as`), см. §0.4-0.5 и правки по разделам 1-11 выше. Почти
   каждая находка документа теперь имеет подтверждение буквальным именем
   XML-тега/атрибута со ссылкой `client-source:`. Остаточная
   неопределённость: точный список `UA_*`-имён на стороне PHP по-прежнему
   не проверялся (это отдельный файл вне периметра клиентского
   исследования) — совпадение тега с именем PHP-класса всё ещё
   «вероятное», а не подтверждённое чтением PHP.
2. **`rotatedRings`** (`army_overview_class.as:965`, decompiled) —
   частично уточнено: реальный источник подтверждает, что поля
   день/час/минута задержки реально существуют и считываются, но идут
   **только** в `calc_army_action`/`calctravel` (предпросмотр), а не в
   саму команду атаки/защиты (`army_action`, см. правку в §1). То есть
   вопрос теперь конкретнее: как контент `calctravel` (с `timeh`/`timem`)
   влияет на что-либо, если реальная атака отправляется без этих полей —
   возможно, калькулятор лишь подсказывает игроку, когда лучше нажать
   «атаковать» вручную (без явной серверной «отложенной» атаки), либо
   ring-геометрия учитывается сервером как-то иначе. По-прежнему нужно
   искать `GL_TravelTime`/`GL_TravelTime2`/настройки мира в PHP, но
   теперь известно, что смотреть нужно в контексте обработчика
   `calctravel`, а не `attack`/`defend`.
3. ~~Значения `obj.inaction` (0-3) для армии~~ — **в основном закрыто.**
   Найден буквальный комментарий разработчика в реальном источнике
   (`xmlparsers.as:1504-1509`, дублируется в `:3151`): `0` — «стоит на
   соте», `1` — «летит куда-то можно вернуть», `2` — «летит домой ничего
   не доступно», `3` — «домашка». Обе исходные гипотезы этого документа
   (что `2` = «обороняет чужую соту» и что `3` = «армия спрятана после
   `HIDE_ARMY`») не подтвердились дословно — реальность проще: `2` про
   возврат домой, а `3` практически идентична `0` (обе разрешают трансфер
   юнитов и считаются одним UI-счётчиком `LOITER`, см. §1). Остаточный
   открытый момент: чем именно семантически `3` отличается от `0` на
   сервере (одно ли это состояние с точки зрения PHP, или клиент просто
   не различает два разных состояния в UI) — не установлено.
4. **Поле `level` у юнита** — не декомпиляторский артефакт, подтверждено
   и в реальном источнике (`army_overview_class.as:973-975`,
   `xmlparsers.as:1534`), но найдено, ОТКУДА клиент его берёт:
   `game.getLocalNames(unitname).level` — статическая клиентская
   библиотека по типу юнита, а не индивидуальный XML-атрибут юнита в
   составе армии. Это заметно ослабляет гипотезу «левелинг/ветеранство» в
   пользу «статический параметр типа юнита» (что-то вроде тира в дереве
   юнитов), но не отвечает на вопрос, что именно сервер хранит под этим
   именем и как оно используется в бою — по-прежнему стоит искать в PHP
   что-то вроде `DE_UnitsGroup::getLevel()`, но уже не как «опыт группы
   юнитов», а скорее как атрибут типа юнита в БД/локализации.
5. ~~`powershieldclass` vs `protectshieldclass`~~ — **в основном закрыто.**
   Прямой комментарий разработчика над парсером `protectedfield`
   (`xmlparsers.as:1225`): «НОВЫЙ ЗАЩИТНЫЙ ЩИТ ДЛЯ ЗАХВАЧЕННЫХ КОЛОНИЙ» —
   подтверждает, что это два разных механизма, и что `protectshieldclass`
   привязан именно к **захваченным колониям** (не к родной территории
   вообще), что явно стыкует его с `mechanics-combat.md` §8. Остаточный
   вопрос: точная формула цены/скорости восстановления и соотношение с
   `TEMPORARY_DEFEND` по-прежнему требуют разбора PHP
   (`UA_PowerShildActivate`/`UA_PowerShildStart`/`UA_RestoreProtection`
   не читались).
6. **`ONE_ROUND_BATTLE` при отправке зонда** (`spying_class.as:205`,
   подтверждено и в `client-source: spying_class.as:198` под внутренним
   именем `CHECK_ONE_ROUND`) — существование чекбокса подтверждено дважды
   независимо, но его точный эффект («бой зонда с защитой цели в один
   раунд»?) по-прежнему не установлен из клиентского кода. Требует разбора
   `ME_SpyZondsBattle`/`SU_SpyZondsBattleCalculator` в PHP.
7. **Действие `"RESEARCH"` у зондов-исследователей** — не установлено,
   что конкретно оно делает: ускоряет собственную науку игрока,
   ворует/копирует уровень науки цели, или что-то ещё. Подтверждено, что
   на стороне XML это действие с `coord=0`/`time=1` на `<action
   id="RESEARCH">` (реальные имена атрибутов — `coord`/`time`, см. §10),
   но чему равен `coord` для `RESEARCH` конкретно в живом ответе сервера —
   не проверялось (не входит в разобранные литеральные дампы).
8. ~~Тратится ли `WR` (военный ранг) безвозвратно при постройке зонда~~ —
   **связь с `battle_xp` подтверждена, вопрос о списании остаётся
   открытым.** `objectData.xprank` в `zondcreateclass`/`createzond`
   подтверждён дословно как атрибут **`battle_xp`** на корне
   `<zondcreate>` (`xmlparsers.as:3442`) — то же самое имя атрибута, что и
   на экране `spying_class` (`<spying battle_xp="">`). Это делает связь с
   `ME_BattleXP` из `mechanics-combat.md` §11 фактом соответствия имён, а
   не гипотезой. При этом в реальном тестовом дампе значение дробное
   (`battle_xp="0.0483"`, см. §11) — похоже на рейтинг/долю, а не на
   целочисленный счётчик очков — и тратится ли оно при постройке зонда
   безвозвратно, по-прежнему не следует из клиентского кода. Стоит
   закрывать этот вопрос вместе с `ME_BattleXP` на стороне PHP.
9. ~~`dismissArmies()` без параметров~~ — **закрыто.** Реальный источник
   `reqvestdata.as:104-109` показывает, что `dismissArmies()` физически не
   принимает параметров (не просто «может вызываться без них»), отправляет
   `<dismissArmies />` без единого атрибута, и снабжён комментарием
   разработчика `РАСПУСТИТЬ ВСЕ АРМИИ К ЕБЕНЯМ НАХ!`. Реальный вызывающий
   код (`army_overview_class.as:292-313`) подтверждает: единственная
   кнопка `DISMISS_ALL` без выбора конкретных армий. Подтверждено: пустой
   вызов означает «распустить все армии игрока» — это не «ничего не
   делать» и не частичный роспуск по списку ID (такой возможности в этой
   ревизии протокола нет).
10. **Соответствие `ShowBattle`/`globalCabinetRequest` и части остальных
    команд конкретным `UA_*`-классам** — частично закрыто. Подтверждено
    буквально: `ShowBattle(...)` — это `<getbuildmenu tab="BattleOverview"
    days="" fromd="" opp="" sot="" stat="" winner=""/>`, то есть частный
    случай общего `getbuildmenu`/`tab=` (`protocol-client-server.md` §7.9),
    отдельного `UA_*`-класса для него, скорее всего, не существует.
    `globalCabinetRequest()` подтверждённо **не является серверной
    командой вообще** — это чисто клиентское ветвление между
    `<cabinetRequest command="open"/>` (для соцсетей VK/POP) и открытием
    внешнего URL `user_cabinet.php` (для всех остальных), см. §0.4.
    `getReportBattle`/`getShowBattle` подтверждённо используют один и тот
    же тег `getBattleInfo` (различаются только атрибутом `report`, см.
    §0.4) — значит один и тот же `UA_GetBattleInfo`, а не два класса.
    `requestScan`/`filterScanner`/`filterScanerLog` тоже теперь имеют
    буквальные теги (`scaninriver`/`getbuildmenu tab="PN_SpyLogs"`/
    `scaninriverfilter`, см. §0.4), но соответствие конкретным именам
    PHP-классов `UA_*` по-прежнему не проверялось (PHP не читался в рамках
    этого прохода).
11. **Новое: тип боя `"E"`** (`xmlparsers.as:1094-1097`, §3) — запись в
    списке боёв для случая, когда игрок участвовал в бою на защите **чужой**
    соты (коалиционная защита), с комментарием разработчика «Если армия на
    защите другой соты участвовала в бою». В реальном UI-классе
    (`battleoverviewclass.as`) для этого типа нет отдельной обработки
    полей оппонента — как это выглядит для игрока на экране, не
    установлено. Релевантно для `mechanics-combat.md` §9
    (`DE_CombDefendArmy`) — см. итоговый отчёт агента.
12. **Новое: шестой ключ цены `SC` в `<armycalculator>`** (§2,
    `xmlparsers.as:1715-1717`) — набор ключей цены юнита при создании армии
    (`KA/NA/ST/FO/PR/SC`) на один элемент длиннее, чем 5 базовых ресурсов
    (Kaderium/Nano/Stasis/Food/Prodium). Что означает `SC` (возможно,
    `SILVER_CREDIT`?) — не установлено ни из кода, ни из литеральных
    дампов.
