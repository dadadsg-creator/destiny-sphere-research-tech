# Реинкарнация юнитов

> **Статус документа: код найден и разобран, частично — с расхождениями от
> игровой справки.** В отличие от первой версии этого документа, PHP-код
> механики идентифицирован: команда игрока — `UA_ReincarnateUnit`
> (`ds/classes/UA/UA_ReincarnateUnit.class.php`), обработчик отложенного
> события — `EH_ReincarnateUnit` (`ds/classes/EH/EH_ReincarnateUnit.class.php`),
> библиотека запросов — `DS_ArmyWeight` (`ds/classes/DS/DS_ArmyWeight.class.php`)
> и её сущность-обёртка `DE_ArmyWeight` (`ds/classes/DE/DE_ArmyWeight.class.php`).
> Таблицы БД подтверждены по `schema.sql` (`pg_dump --schema-only` живой
> игровой БД, см. ниже §2).
>
> **Формула из §3 (70% от меньшего счётчика) подтверждена кодом дословно.**
> Формула из бывшего §2.1 (списание `N/0.7` и `N*0.3/0.7`) —
> **НЕ подтверждена, реальный код списывает иначе** (`N` и `N*0.3` без
> деления на 0.7) — расхождение разобрано в §4.3, не подменено молча.
> Отдельно не подтверждён код одного конкретного игрового факта из справки
> («проданная армия увеличивает счётчик Построено») — см. «Открытые
> вопросы» п.6.
>
> Player-facing части (интерфейс выбора юнитов, графики статистики) по-прежнему
> взяты с `https://destinysphere.ru/?p=reincarnation` и раздела «2.5–2.6.
> Реинкарнация» `https://destinysphere.ru/ds/help/?name=INFO_GAME_MAIN` — код
> не покрывает конкретную вёрстку экрана, только XML, который отдаёт сервер
> (см. §5, подтверждено по `PN_ReincarnationPanel`).
>
> Кодировка PHP-файлов — Windows-1251, читались через
> `iconv -f CP1251 -t UTF-8`. Код и `schema.sql` — read-only, не
> редактировались.
>
> Клиентская сторона (`reincarnate_menu_class.as`, `reincarnate.as`,
> `reincarnate_menu_parser` в `xmlparsers.as`) частично разбиралась в
> [client-economy-social-ui.md](client-economy-social-ui.md) — см. там
> XML-протокол экрана реинкарнации.

## 1. Что это

**Реинкарнация** — сервис, доступный в **Храме** (постройка религии, см.
[mechanics-races-world-ranking.md](mechanics-races-world-ranking.md) §4),
позволяющий воскресить часть юнитов, погибших в бою, взамен на ресурсы
(либо на серебряные кредиты — отдельная вкладка с тем же интерфейсом, но
одним «ресурсом» вместо пяти). Принятие религии для этого **не
обязательно** — храма как постройки достаточно (это по-прежнему
описание с игровой справки, отдельно код-проверка наличия храма как
предусловия команды в этом проходе не выполнялась — `UA_ReincarnateUnit`
такую проверку в `checkLocalParams()`/`_checkAction()` явно не делает,
видимо потому что вход в панель `PN_ReincarnationPanel` уже ограничен на
уровне меню строений, см. `getBuildingTabs()` в
`PN_ReincarnationPanel.class.php:146`).

Условия доступности подтверждены кодом:
- Отправка «за ресурсы» требует world-настройку (game modifier, см. §2.4)
  `UNIT_REINCARNATION_IN_RESOURCE_ENABLED`
  (`UA_ReincarnateUnit.class.php:91-92`).
- Отправка «за серебро» требует `UNIT_REINCARNATION_IN_SILVER_ENABLED`
  (`UA_ReincarnateUnit.class.php:93-94`).
- Обе настройки читаются через `$this->worldSettings->get(...)`, то есть
  это per-world флаги (не глобальные), как и предполагал прежний текст
  документа.

> ✅ **Точные значения на 4 конкретных живых мирах (источник: БД,
> live-запрос, 2026-09-02).**
>
> | Мир | `UNIT_REINCARNATION_IN_RESOURCE_ENABLED` | `UNIT_REINCARNATION_IN_SILVER_ENABLED` |
> |---|:---:|:---:|
> | Чёрная дыра | 0 (выключено) | 0 (выключено) |
> | Сфера Парусов | 1 (включено) | 1 (включено) |
> | Сфера Змееносца | 1 (включено) | 1 (включено) |
> | Сфера Гончих Псов (турнирный) | 1 (включено) | 0 (выключено) |
>
> Оба флага различаются по мирам — реинкарнация целиком отключена на «Чёрной
> дыре» (оба флага 0), полностью включена на «Сфере Парусов»/«Сфере
> Змееносца», а на турнирном мире «Сфера Гончих Псов» доступна только «за
> ресурсы», но не «за серебро». Глобальное значение по умолчанию в каталоге
> модификаторов (`ds_gamemodifiertemplate.value`) для обоих ключей — `0`
> (выключено) — то есть реинкарнация не работает «из коробки» и требует
> явного включения администрацией на конкретном мире. Это данные 4
> конкретных названных миров, не универсальная константа кода.

## 2. Таблицы БД

Подтверждено по `schema.sql` (полные `CREATE TABLE`/`CONSTRAINT`,
`schema.sql:12446-12576`, `20331-20337`, `24098-24142`, `26114-26118`,
`32292-32336`, `33964-33984`).

### 2.1 Текущие счётчики: `ds_army_weight`

Одна строка на игрока (`id_userinstance` уникален) — живые значения
«Построено»/«Потеряно»:

| Колонка | Тип | Комментарий |
|---|---|---|
| `id_army_weight` | `integer NOT NULL` | PK |
| `id_userinstance` | `integer` | `UNIQUE`, `FK → ds_userinstance(id_userinstance) ON DELETE CASCADE` |
| `army_weight_built` | `double precision` | «Построено» — накопительный продовес |
| `army_weight_lost` | `double precision` | «Потеряно» — накопительный продовес |

### 2.2 История счётчиков: `ds_army_weight_history`

Снапшоты `ds_army_weight` во времени (по одной записи на день на игрока —
см. §4.2 про `DS_ArmyWeight::UpdateArmyWeight`), питает графики недели/месяца
на экране реинкарнации:

| Колонка | Тип | Комментарий |
|---|---|---|
| `id_army_weight_history` | `integer NOT NULL` | PK |
| `id_userinstance` | `integer` | `FK → ds_userinstance ON DELETE CASCADE` |
| `army_weight_built` | `double precision` | снапшот на `change_date` |
| `army_weight_lost` | `double precision` | снапшот на `change_date` |
| `change_date` | `timestamp without time zone DEFAULT now()` | |

### 2.3 Счётчик «Реинкарнировано»: `ds_army_reincarnation` / `_hystory` / `_unit_hystory`

`ds_army_reincarnation` — одна строка на игрока, **живой накопительный**
счётчик «Реинкарнировано»:

| Колонка | Тип | Комментарий |
|---|---|---|
| `ds_army_reincarnation` | `integer NOT NULL` | PK (колонка названа так же, как таблица) |
| `id_userinstance` | `integer NOT NULL` | `UNIQUE`, `FK → ds_userinstance ON DELETE CASCADE` |
| `army_weight` | `double precision` | суммарный продовес всего, что игрок когда-либо отправил на реинкарнацию |

`ds_army_reincarnation_hystory` — журнал операций (по одной строке на
каждый вызов «отправить на реинкарнацию»):

| Колонка | Тип | Комментарий |
|---|---|---|
| `id_army_reincarnation_hystory` | `integer NOT NULL` | PK |
| `id_userinstance` | `integer` | `FK → ds_userinstance ON DELETE CASCADE` |
| `date` | `timestamp DEFAULT now()` | |
| `army_weight` | `double precision NOT NULL` | **не дельта операции, а НОВОЕ значение общего счётчика ПОСЛЕ этой операции** — подтверждено кодом, см. §4.3 (`$newValue = $armyWeight + $res[0]['army_weight']`, `DS_ArmyWeight.class.php:460`, затем записывается как `army_weight` в хистори) |
| `total_price` | `text` | цена операции одной строкой, формат `"<value> <res_abbr>;..."`, собирается в `UA_ReincarnateUnit.class.php:305-322` |

`ds_army_reincarnation_unit_hystory` — построчная разбивка одной операции
по типам юнитов:

| Колонка | Тип | Комментарий |
|---|---|---|
| `id_army_reincarnation_hystory` | `integer` | `FK → ds_army_reincarnation_hystory ON DELETE CASCADE` |
| `id_unit` | `integer` | `FK → ds_unit(id_unit)` |
| `amount` | `double precision` | сколько юнитов этого типа отправлено |
| `price` | `text` | ресурсы, потраченные конкретно на этот тип юнита |

### 2.4 Цены реинкарнации: `ds_unit_reincarnation_cost`

| Колонка | Тип | Комментарий |
|---|---|---|
| `id_unit_reincarnation_cost` | `integer NOT NULL` | PK |
| `id_world` | `integer` | `FK → ds_world(id_world)` |
| `id_unit` | `integer` | `FK → ds_unit(id_unit)` |
| `value` | `real` | цена за одну единицу юнита |
| `id_tradecourse` | `integer` | `FK → ds_trade_course(id_tradecourse)` — какой ресурс/серебро |

Одна строка = цена одного юнита в одном из ~6 «курсов» (`id_tradecourse`
1..6 — судя по отключённому скрипту-загрузчику
`ds/updateReincarnationCosts.php:16`, который циклом `for ($i=1;$i<7;$i++)`
обновляет `value` по этим курсам из CSV — 5 обычных ресурсов + серебро,
как и написано в §1 старой версии документа).

**Ни одна PL/pgSQL-функция или триггер в `schema.sql` не упоминает ни одну
из шести таблиц выше** (проверено `grep -i reincarnat` и `grep -i
army_weight` по всему дампу — совпадений в телах `CREATE FUNCTION` нет).
Вся логика реинкарнации реализована целиком в PHP-слое, не в БД.

## 3. Счётчики и формула лимита (подтверждено кодом)

На аккаунт ведутся три накопительных счётчика в продиуме (см. таблицы §2):
«Построено» (`ds_army_weight.army_weight_built`), «Потеряно»
(`ds_army_weight.army_weight_lost`), «Реинкарнировано»
(`ds_army_reincarnation.army_weight`).

**Потенциал к реинкарнации = 70% от МЕНЬШЕГО из «Построено» и «Потеряно»**
— формула буквально в коде, в двух независимых местах:

```php
// UA_ReincarnateUnit.class.php:239-240 — проверка допустимости операции
$armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($this->uiid);
$targetProdium = 0.7*min($armyWeight->getArmyWeightLost(), $armyWeight->getArmyWeightBuilt());
```
```php
// PN_ReincarnationPanel.class.php:22 — то же число, что рисуется на экране
$targetProdium = 0.7*min($lostProdium, $biuldProdium);
```

### 3.1 Что реально увеличивает «Построено» и «Потеряно»

Прослежены все вызовы `DE_ArmyWeight::deltaArmyWeightBuilt()` /
`deltaArmyWeightLost()` по всей кодовой базе (`grep -rn
deltaArmyWeightBuilt\|deltaArmyWeightLost old-sphere/`) — их всего 4
места:

**«Построено» увеличивается только в одном месте** —
`ME_AddUnitsToHomeArmy::AddUnit()` (`ds/classes/ME/ME_AddUnitsToHomeArmy.class.php:25-34`),
и только если этой функции явно передан пятый параметр
`$increaseWeight = true`, **и** включена мировая настройка
`CALC_ARMY_WEIGHT`:

```php
// ME_AddUnitsToHomeArmy.class.php:25-34
if ($increaseWeight && DE_WorldSettings::CreateForCurentWorld()->get('CALC_ARMY_WEIGHT')) {
    $addPower = $addPower/10;               // unitPower * amount / 10
    $armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($comb->getUserInstanceID());
    $armyWeight->deltaArmyWeightBuilt($addPower);
}
```

Из всех вызовов `AddUnit()` в кодовой базе (`EH_BattleRound.class.php:313`
— подкрепление, `EH_ReincarnateUnit.class.php:78` — возврат
реинкарнированных юнитов, `EH_TransferResource.class.php:116` —
внутриигровой трансфер юнитов, `ME_DeltaResourcesOnComb.class.php:73` и
`ME_TradeDeltaResourcesOnComb.class.php:73` — торговый движок (покупка/
продажа на рынке), `SI_PrepareUserForTransfer.class.php:180` —
межмировой перенос аккаунта, `SU_NPC.class.php:111,156` — спавн армии
NPC) **`$increaseWeight = true` передаётся только в**
`EH_TrainWarUnit.class.php:167` **и** `:210` — то есть только для юнитов,
достроенных на военном заводе через обычную очередь тренировки
(`TRAIN_UNIT`). Реинкарнированные юниты возвращаются с explicit `false`
(`EH_ReincarnateUnit.class.php:78`) — счётчик «Построено» не растёт,
что совпадает с прежним текстом документа.

**«Потеряно» увеличивается только в одном месте** —
`ME_CleanArmyLooses::_applyeLoses()` (`ds/classes/ME/ME_CleanArmyLooses.class.php:78-87`),
пост-боевой шаг очистки потерь, применяется к любой группе юнитов
(независимо от того, как они попали в армию — построены, куплены,
перенесены), при условии `CALC_ARMY_WEIGHT` и что владелец группы —
не системный аккаунт (`id_userinstance != 0`):

```php
// ME_CleanArmyLooses.class.php:78-87
if (DE_WorldSettings::CreateForCurentWorld()->get('CALC_ARMY_WEIGHT') && $loses >= 1 && $unitsGroup->getArmy()->getUserInstance()->getUserInstanceID() != 0) {
    $delta = floor($loses)*($unitsGroup->getUnit()->getStazis()/10);
    $armyWeight = DE_ArmyWeight::CreateFromUserInstanceID(...);
    $armyWeight->deltaArmyWeightLost($delta);
}
```

`getStazis()` — просто алиас на `getUnitPower()`
(`DE_Unit.class.php:158-161: return $this->getUnitPower();`), которая
сама читает атрибут «стазис» юнита (`DE_Unit.class.php:148-156:
$this->getAttributes()->getStasis()`) — то есть «вес юнита» в продиуме
везде в этой механике (реинкарнация, постройка, потери) — это одна и та
же величина: **стазис юнита / 10**, умноженная на количество.

**Важное наблюдение по сравнению со старым текстом документа**: заявленный
в справке особый случай «проданная армия увеличивает „Построено“, но не
увеличивает „Потеряно“» **в коде не подтверждён**. Единственные два места,
которые вообще трогают эти счётчики, перечислены выше, и продажа армии
(`ME_TradeDeltaResourcesOnComb.class.php:73`, торговый движок) вызывает
`AddUnit()` с параметром `$increaseWeight` по умолчанию (`false`) — то
есть **не увеличивает «Построено»**. Ни в одном месте кодовой базы не
найдено кода, который увеличивал бы «Построено» специально по факту
продажи армии. Это либо устаревшее/неточное описание в игровой справке,
либо путь реализован не через `DE_ArmyWeight`/`ME_AddUnitsToHomeArmy`, а
где-то ещё, не найденном в этом проходе. Не подменяю молча — см.
«Открытые вопросы» п.6.

Случай «купленная армия не увеличивает „Построено“, но увеличивает
„Потеряно“ при гибели» — согласуется с кодом лишь частично: код
действительно не увеличивает «Построено» для купленной/перенесённой
армии (не проходит через `EH_TrainWarUnit`), и действительно увеличивает
«Потеряно» при её гибели в бою — но `ME_CleanArmyLooses` не делает
различия по происхождению юнитов вообще, любая гибель любых юнитов (кроме
юнитов id_userinstance=0) увеличивает «Потеряно» одинаково. Так что это не
специальный случай «для купленной армии», а общее правило для всех
юнитов.

## 4. Отправка юнитов на реинкарнацию — `UA_ReincarnateUnit`

Команда игрока: XML-атрибуты `for` (`"Res"` или `"Sera"` — за ресурсы или
за серебро) и `lst` (список `unitId:amount,unitId:amount,...`).

### 4.1 Проверка параметров — `checkLocalParams()` (`UA_ReincarnateUnit.class.php:84-176`)

1. `for` должен быть `'Res'` или `'Sera'`, соответствующая world-настройка
   должна быть включена (§1).
2. Квестовые юниты (`id_unit` 99, 98, 93 — захардкожено) реинкарнации не
   подлежат (`:110`).
3. Для каждого выбранного юнита — `DS_ArmyWeight::GetUnitListToReincarnation()`
   (`DS_ArmyWeight.class.php:253-337`) проверяет, что юнит вообще
   доступен игроку на этой соте: есть нужное здание
   (`ds_produceableunit`/`ds_attachedbuilding`/`ds_comb`), изучены нужные
   науки (`ds_unitneedsscience`/`ds_userhasscience`), раса может строить
   этот юнит (`ds_unitcanbebuildbyrace`).
4. Стоимость юнита в ресурсах/серебре — `DS_ArmyWeight::GetUnitReincarnationPrice()`
   (`DS_ArmyWeight.class.php:344-380`), SQL-джойн
   `ds_unit_reincarnation_cost` → `ds_trade_course` → (`ds_attributename`
   при `id_tradecategory=1` для обычного ресурса, либо `ds_money` при
   `id_tradecategory=4` для `SILVER_CREDIT`) → умножение на два
   мультипликатора мира: `UNIT_REINCARNATION_MULTIPLIER` и
   **`UNIT_REINCARNATION_MULTIPLIER_LEAGUE_<N>`**, где `<N>` — id лиги
   игрока (`DE_UserInstance::getLeagueId()`) — то есть **цена
   реинкарнации зависит от лиги игрока**, это не было отражено в старой
   версии документа. Точная формула по коду (`:361-362`): для оплаты
   ресурсом — `trcd.value * gm1.value(MULTIPLIER) * gm2.value
   (MULTIPLIER_LEAGUE_N)`; для оплаты серебром — **базовый
   `MULTIPLIER` НЕ применяется**, только `trcd.value *
   gm2.value(MULTIPLIER_LEAGUE_N)`.
   >
   > ✅ **Реальные значения на мире «Сфера Ориона» (источник: БД,
   > live-запрос, 2026-09-08)**: `UNIT_REINCARNATION_MULTIPLIER = 1`,
   > `UNIT_REINCARNATION_MULTIPLIER_LEAGUE_1 = 0.1`,
   > `..._LEAGUE_2 = 0.5`, `..._LEAGUE_3 = 1`. То есть на этом мире цена
   > реинкарнации для игроков лиги 1 (предположительно самая младшая/
   > новичковая, см. `mechanics-newbie-protection-leagues.md`) — **в 10
   > раз ниже**, чем для лиги 3 (ветераны платят полную цену); лига 2 —
   > скидка вдвое. Это данные одного конкретного мира, не универсальная
   > константа — на других мирах могут быть другие числа.
5. Вес («продовес») отправляемых юнитов: `$this->needProdium +=
   DE_Unit::CreateFromID($unitId)->getUnitPower()/10*$unitAmount`
   (`:159`) — тот же «стазис/10», что и везде (§3.1).
6. Время реинкарнации: `$this->timetotrainone =
   $this->worldSettings->get('UNIT_REINCARNATION_TIME')*60*60` — то есть
   **это настраиваемое per-world число часов, а не захардкоженная
   константа**; если платим серебром, время дополнительно делится на
   `$this->worldSettings->get('UNIT_REINCARNATION_SILVER_TIME_DIVISOR')`
   (`:169-172`) — **оплата серебром ускоряет ожидание**, деталь,
   отсутствовавшая в исходном документе. «48 часов» из справки — это,
   по всей видимости, значение по умолчанию на большинстве миров.
   >
   > ✅ **Резолвнуто (2026-09-08, live-запрос к БД)**: на мире «Сфера
   > Ориона» `UNIT_REINCARNATION_TIME = 48` (часов) и
   > `UNIT_REINCARNATION_SILVER_TIME_DIVISOR = 4` — то есть реинкарнация
   > «за ресурсы» занимает 48 часов, а «за серебро» — 12 часов (48/4).
   > Справочные «48 часов» подтверждены числом из реальной БД, а не
   > только предположением. Как и с мультипликаторами выше — конкретное
   > число для одного названного мира, не универсальная константа кода.

### 4.2 Проверка допустимости — `_checkAction()` (`:214-248`)

- Хватает ли ресурсов/серебра на соте (`:219-236`, серебро проверяется
  через `UC_PaymentServiceMoneyOperation::CheckMoney`).
- Хватает ли потенциала: `0.7*min(Lost, Built) >= needProdium` (`:239-245`,
  формула §3).

### 4.3 Выполнение — `_perfomAction()` (`:250-368`)

Перед списанием — три блокирующие проверки:
1. **Уже есть очередь на этой соте** — `DS_ArmyWeight::GetUnitOnReincarnation()`
   (`:256-261`) ищет незавершённые события `REINCARNATE_UNIT` для этой
   соты через `ds_eventqueue`/`ds_eventparticipant`/`ds_eventhasoption`;
   если что-то уже реинкарнируется — новая заявка тихо отклоняется
   (системное сообщение `CANT_REINCARNATE_UNIT_MESS`, действие
   считается «выполненным», но фактически ничего не происходит). Это и
   есть механизм «не чаще одного раза», упомянутый в старом документе —
   **не таймер по часам после завершения операции, а блокировка «пока не
   пуста очередь на этой соте»** (что на практике эквивалентно, пока
   очередь одна и юниты возвращаются по завершении таймера).
2. **Режим отпуска** (`GL_IsUserInVocationMode`, `:264-269`) — блокирует.
3. **Активный энергощит** (`DE_PowerShild::isWorks()`, `:271-276`) —
   блокирует. Ни одна из этих двух блокировок не упоминалась в исходном
   документе.

Затем — **списание счётчиков** (`:300-302`):

```php
// UA_ReincarnateUnit.class.php:300-302
$armyWeight->deltaArmyWeightBuilt(-$this->needProdium*0.3);
$armyWeight->deltaArmyWeightLost(-$this->needProdium);
```

**Это расходится с формулой из прежней версии документа.** Прежний текст
(взятый со справочных страниц сайта) утверждал:

```
Потеряно  -= N / 0.7
Построено -= N * 0.3 / 0.7
```

Код же делает буквально:

```
Потеряно  -= N            (без деления на 0.7)
Построено -= N * 0.3      (без деления на 0.7)
```

где `N = needProdium` — вес отправленных на реинкарнацию юнитов (тот же
`N`, что пишется как дельта в `ds_army_reincarnation.army_weight`, см.
§4.4 ниже).

**Разбор эффекта этого расхождения**: если «Потеряно» — меньший из двух
счётчиков (обычный случай, иначе операция не прошла бы проверку §4.2),
то после списания разрыв `Построено − Потеряно` увеличивается на `0.7*N`
(было неотрицательным, растёт дальше), то есть «Потеряно» остаётся
меньшим счётчиком и после операции. Тогда новый потенциал:

```
0.7 * min(Построено', Потеряно') = 0.7 * (Потеряно − N) = старый_потенциал − 0.7*N
```

То есть трата `N` продовеса на реинкарнацию реально уменьшает доступный
на будущее потенциал не на `N`, а только на `0.7*N` — заметно мягче, чем
предполагала формула из справки (которая была явно сконструирована так,
чтобы уменьшать потенциал ровно на `N`). Играбельно это значит, что
активная реинкарнация «сама себя подпитывает» за счёт разницы между
списанием `Потеряно` (полностью) и `Построено` (только 30%). Случай, когда
именно «Построено» — меньший счётчик (менее типичный, но не запрещённый
инвариантами `DE_ArmyWeight`), в этом проходе алгебраически не
прорабатывался — после списания `0.3*N` из уже минимального «Построено»
не гарантировано, что оно останется минимумом; краевой случай оставлен
открытым.

### 4.4 Списание ресурсов и запись истории (`:304-356`)

- Списание ресурсов с соты (`combAttributes->deltaAttribute(...)`) либо
  списание серебра через
  `UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(...,
  'UNIT_REINCARNATION', ...)` (`:307-322`).
- `DS_ArmyWeight::InsertArmyReincarnation($uiid, $needProdium, $unitArr,
  $finalPrice)` (`:325`, реализация в `DS_ArmyWeight.class.php:429-496`):
  прибавляет `needProdium` к текущему значению
  `ds_army_reincarnation.army_weight` (INSERT, если строки ещё нет, иначе
  UPDATE), затем пишет одну строку в `ds_army_reincarnation_hystory`
  (с уже накопленным новым значением счётчика, не дельтой — см. §2.3) и
  по одной строке в `ds_army_reincarnation_unit_hystory` на каждый тип
  юнита.
- На каждый тип юнита создаётся событие `REINCARNATE_UNIT`
  (`$this->createEvent('REINCARNATE_UNIT')`, `:343`) — стандартный
  механизм очереди `ds_eventqueue`/`EH_*`, см.
  [mechanics-events-turns.md](mechanics-events-turns.md). Параметры
  события (`createEventParams()`, `:382-389`): `CountToReincarnate`,
  `ID_Unit`, `ID_Comb`.

  Отдельное наблюдение: SQL в `DS_ArmyWeight::GetUnitOnReincarnation()`
  (`:404-406`) комментирует те же три id опций события как `ID_Unit`,
  `ID_Comb`, **`CountToTrain`** (а не `CountToReincarnate`) — похоже на
  переиспользование той же нумерации `ds_eventoption`, что и у события
  `TRAIN_UNIT` (постройка юнитов), с устаревшим комментарием. Точные
  строки `optionname` в `ds_eventoption` не проверялись — `schema.sql`
  это `--schema-only` дамп, данных в справочных таблицах там нет.

## 5. Возврат юнитов — `EH_ReincarnateUnit`

Стандартный `EH_*`-обработчик отложенного события (`ds/classes/EH/EH_ReincarnateUnit.class.php`):

- Пока время не истекло (`_isTimeToHandle()` вернул false) —
  `_preHandle()` (`:102-113`) просто шлёт игроку сообщение
  `REINCARNATE_UNIT_IN_PROGRESS_MESS` с датой окончания.
- Когда время истекло — `_handle()` (`:75-100`):
  1. `ME_AddUnitsToHomeArmy::AddUnit($comb, $unit, $countToTrain, $chekDate,
     false)` (`:78`) — юниты возвращаются в домашнюю армию соты; **пятый
     параметр явно `false`** — счётчик «Построено» от этого не растёт
     (см. §3.1).
  2. Событие помечается использованным (`setTrash()`).
  3. `GL_NewbDefendTestArmyPower::SetNeedCheckCurrentUserInstanceFlag()` —
     переоценка «защиты новичка» (сила армии изменилась).
  4. Отметка в трассе апкипа (`DE_LiveUpkeepPoints`).
  5. Системное сообщение `REINCARNATE_UNIT_MESS`.

## 6. Персистентность и графики истории — `DE_ArmyWeight` / `DS_ArmyWeight`

`DE_ArmyWeight` (`ds/classes/DE/DE_ArmyWeight.class.php`) — сущность-обёртка
над `ds_army_weight`, стандартный паттерн движка (см.
[game-engine.md](game-engine.md)): ленивая загрузка через
`DS_ArmyWeight::getByUserInstanceId()`, если строки ещё нет — вставляется
`(0.0, 0.0)` (`:44-51`); `deltaArmyWeightBuilt()` не даёт уйти в минус
(`ds_error()` при попытке, `:107-110`), `deltaArmyWeightLost()` тихо
клампит к 0 (`:124`); `commit()` (`:139-150`) в конце запроса вызывает
`DS_ArmyWeight::UpdateArmyWeight()`.

`DS_ArmyWeight::UpdateArmyWeight()` (`DS_ArmyWeight.class.php:12-67`) —
пишет и в живую таблицу `ds_army_weight`, и в `ds_army_weight_history`:
сначала смотрит, есть ли уже запись в хистори за **сегодня**
(`change_date > '<сегодня 00:00:01>'`); если есть — обновляет её на месте,
если нет — вставляет новую. То есть `ds_army_weight_history` хранит
**одну точку в день на игрока** (последнее состояние на конец дня), а не
журнал каждого изменения — это и есть данные для недельного/месячного
графика в §7.

## 7. Интерфейс выбора юнитов — `PN_ReincarnationPanel`

Подтверждено по `ds/classes/PN/PN_ReincarnationPanel.class.php:12-182`
(генерирует XML `<reincarnate>` для клиента, см. также
[client-economy-social-ui.md](client-economy-social-ui.md)):

- Корневые атрибуты: `lostProdium`, `biuldProdium` (да, с опечаткой —
  «Построено»), `reincProdium`, `targetProdium` (потенциал по формуле
  §3), `res`/`sil` — включена ли реинкарнация за ресурсы/за серебро на
  этом мире.
- `<lost w="..." m="...">`, `<build w="..." m="...">`, `<reinc w="..."
  m="...">` — недельные (`w`, последние 7 из 30 точек) и месячные (`m`,
  30 точек) серии для графиков; данные — `ds_army_weight_history` (§6) для
  `lost`/`build`, `ds_army_reincarnation_hystory` для `reinc`
  (`DS_ArmyWeight::GetReincarnateHistoryByUserInstanceID()`,
  `DS_ArmyWeight.class.php:207-244`, считает **разницу** между соседними
  снапшотами накопительного счётчика, то есть отдаёт клиенту приросты за
  период, а не сам накопительный счётчик).
- `<unit id unitname amount price PW>` — построчно по каждому доступному
  юниту: id, имя, цена (`price`, склеенная строка `"<value>
  <res_short>;..."`), вес одной единицы (`PW` = `getUnitPower()/10`).
- `<reunit id unitname amount time>` — юниты, уже стоящие в очереди на
  реинкарнацию (из `DS_ArmyWeight::GetUnitOnReincarnation()`) либо только
  что заказанные в этом же запросе.

Остальное по-прежнему описание с игровой справки (детали конкретной
вёрстки/анимации, групповые кнопки сброса и т.п. — код это не
детализирует за пределами перечисленных XML-атрибутов):

Экран реинкарнации показывает:
- Текущие счётчики «Потеряно»/«Построено» в продиуме и вычисленный из них
  максимальный продовес к реинкарнации.
- Построчно по каждому типу юнита: стоимость производства единицы,
  стоимость выбранного количества, продовес единицы и выбранного
  количества, максимально возможное количество ЭТОГО юнита (если
  реинкарнировать только его одного, с учётом уже сделанного выбора по
  другим типам).
- Общую сумму по выбранным типам, сравнение с доступными на соте
  ресурсами.
- Групповые кнопки: сбросить всё, изменить количество сразу у всех типов
  на одинаковое число, либо регулировать каждый тип по отдельности.

Статистика подаётся также в виде понедельных/помесячных графиков потерь/
постройки/реинкарнации с всплывающими подсказками по конкретному дню —
чисто UI-фича, не влияющая на формулы выше (но теперь известен точный
источник данных на сервере, см. выше).

## 8. Стоимость реинкарнации и админ-инструменты

- `DS_ArmyWeight::GetUnitReincarnationPrice()` (§4.1 п.4) — расчёт цены
  для игрока.
- `Admin/controls/GameSettings/TradeAdmin/classes/UnitReincarnatePriceSearch.class.php` —
  админ-редактор `ds_unit_reincarnation_cost`: `updateUnitReincarnatePrice()`
  и `insertUnitReincarnatePrice()` ищут нужный `id_tradecourse` через
  `ds_trade_course` по паре `(id_tradecategory, id_article)` и
  проставляют/обновляют `value` для конкретных `(id_unit, id_world)`.
- `ds/updateReincarnationCosts.php` — одноразовый (и сейчас **отключённый**
  — файл начинается с `exit;`, `:2`) скрипт массовой загрузки цен из
  `urcosts.csv`: находит юнита по локализованному имени, циклом
  `for ($i=1;$i<7;$i++)` обновляет `ds_unit_reincarnation_cost.value` для
  `id_tradecourse` от 1 до 6 (5 ресурсов + серебро).
- `Admin/controls/Stats/Statistic/UnitReinarnation/index.php`
  (класс `UnitReincarnationStat`) — админский отчёт/лог: список операций
  реинкарнации по всем игрокам (`ds_army_reincarnation_hystory` LEFT JOIN
  `ds_army_reincarnation_unit_hystory`), с детализацией по клику на
  конкретную операцию. Формул механики не содержит, чисто отчётность.

## 9. Историческая деталь: миграционный скрипт (подтверждено)

Старый текст документа предполагал, что где-то был разовый скрипт,
пересчитавший счётчики «Построено»/«Потеряно» задним числом при введении
фичи. Найден и подтверждён: **`SU_CalcArmyWeight::Exec($dbId)`**
(`ds/classes/SU/SU_CalcArmyWeight.class.php:18-220`), запускаемый через
`ds/utils/CalcArmyWeight.php` — либо через браузер
(`?action=scan&dbid=N`, выбор мира из `$GLOBALS['dbWorldAccess']`), либо
из консоли (`php CalcArmyWeight.php <dbid>`).

Актуальный (не закомментированный) SQL-блок внутри `Exec()`
(`:56-115`) делает буквально:

1. `DELETE FROM ds_army_weight` — **стирает все существующие записи**.
2. `INSERT INTO ds_army_weight (id_userinstance, army_weight_built,
   army_weight_lost) SELECT ..., 0` — пересчитывает `army_weight_built`
   с нуля как суммарную «ранговую» стоимость текущей армии игрока
   (джойн `ds_unitcontainerhasunit`/`ds_unitcontainer` через
   вспомогательный view `subqry_armyranking_with_transport`, взвешенную
   через `ds_unithasattribute`/`ds_resource.exchangerate`, плюс поправка
   на юнитов, полученных через ещё необработанные события
   `TRANSFER_RESOURCE` с типом «юниты»), делённую на 10; **`army_weight_lost`
   при этом всегда обнуляется в 0**, для всех игроков, а не только для
   новых — это шире, чем формулировка справки («у новых игроков параметры
   считаются с момента создания соты»): скрипт **сбрасывает историю потерь
   всем** в момент запуска, а не только заводит её заново для новичков.

Скрипт **не одноразовый по коду** (это обычный вызываемый метод, можно
перезапустить на любом мире в любой момент), но по смыслу игровой справки
(«некоторое время назад был запущен скрипт...») он был запущен один раз
исторически при введении фичи на каждом действующем мире — код это не
опровергает и не подтверждает частоту запусков, только то, что скрипт
существует и делает именно это.

Отдельно найден (но не разбирался подробно, вне рамок задачи) администраторский
инструмент отката: `Admin/controls/Players/EditUser/ChangeLevel/index.php:489-552`
позволяет вручную поправить `army_weight_built`/`army_weight_lost`
конкретного игрока через админку (с записью в `actionLog`), а
`ds/classes/SU/SU_ViewBattleSystemLogs.class.php:425` умеет напрямую
(минуя `DE_ArmyWeight`, сырым SQL) вычесть из `army_weight_lost` вес
конкретного боя — судя по всему, инструмент отмены ошибочно засчитанных
потерь. Также при межмировом переносе аккаунта (`SI_*`, см.
[mechanics-world-transfer.md](mechanics-world-transfer.md))
`SI_SearchUserInstance::SetArmyWeight()` (`ds/classes/SI/SI_SearchUserInstance.class.php:207-221`)
переносит оба счётчика на новый мир как есть, без пересчёта.

## Открытые вопросы

1. ~~Полностью не идентифицирован код~~ — **закрыто**: код найден и
   разобран (§3-§9 выше).
2. **Расхождение формулы §4.3** объяснено количественно для случая, когда
   «Потеряно» — меньший счётчик (типичный случай); для случая, когда
   меньше «Построено», точный эффект на потенциал алгебраически не
   выведен в этом проходе.
3. ~~Разовый скрипт-миграция не идентифицирован~~ — **закрыто**, см. §9
   (`SU_CalcArmyWeight`/`ds/utils/CalcArmyWeight.php`).
4. ~~Название и код настройки «включена/выключена для мира» не найдено~~ —
   **закрыто**: `UNIT_REINCARNATION_IN_RESOURCE_ENABLED`,
   `UNIT_REINCARNATION_IN_SILVER_ENABLED`, плюс попутно найдены
   `UNIT_REINCARNATION_TIME`, `UNIT_REINCARNATION_SILVER_TIME_DIVISOR`,
   `CALC_ARMY_WEIGHT`, `UNIT_REINCARNATION_MULTIPLIER`,
   `UNIT_REINCARNATION_MULTIPLIER_LEAGUE_<N>` — все это строки
   `ds_gamemodifier.modifiername` (таблица подтверждена в `schema.sql:15513-15518`,
   класс-читатель `DE_WorldSettings` подтверждён —
   `ds/classes/DE/DE_WorldSettings.class.php:74`, `SELECT * FROM
   ds_gamemodifier`). Конкретные значения (например, реально ли
   `UNIT_REINCARNATION_TIME` = 48 на большинстве миров) не проверены —
   `schema.sql` бесполезен для этого, это `--schema-only` дамп почти без
   данных.

   > ✅ **Частично закрыто прямым запросом к живой БД для 2 из этого списка
   > ключей** (источник: БД, live-запрос, 2026-09-02) — точные значения
   > `UNIT_REINCARNATION_IN_RESOURCE_ENABLED`/`UNIT_REINCARNATION_IN_SILVER_ENABLED`
   > на 4 конкретных живых мирах см. в §1 выше. Остальные ключи из этого
   > списка (`UNIT_REINCARNATION_TIME`, `UNIT_REINCARNATION_SILVER_TIME_DIVISOR`,
   > `CALC_ARMY_WEIGHT`, `UNIT_REINCARNATION_MULTIPLIER[_LEAGUE_<N>]`) в этот
   > запрос не входили — их реальные значения по-прежнему не проверены.
5. **Влияние реинкарнации на общий рейтинг/статистику** (см.
   `mechanics-races-world-ranking.md` §6) — не проверялось в этом проходе,
   остаётся открытым.
6. **«Проданная армия увеличивает „Построено“»** (утверждение из игровой
   справки) — **не подтверждено кодом**, см. разбор в §3.1: единственный
   код-путь, увеличивающий «Построено» — `EH_TrainWarUnit`, продажа армии
   через торговый движок (`ME_TradeDeltaResourcesOnComb`) его не
   затрагивает. Либо справка устарела/неточна, либо соответствующий
   код-путь не найден в этом проходе (например, если продажа реализована
   иначе, не через `ME_AddUnitsToHomeArmy`).
7. **Точное значение `optionname` для `id_eventoption` события
   `REINCARNATE_UNIT`** (наблюдение о несовпадении комментария
   `CountToTrain` в SQL и ключа `CountToReincarnate` в
   `createEventParams()`, §4.4) — не проверено, `ds_eventoption` в
   `schema.sql` без данных.
8. **Проверка наличия храма** как предусловия команды `UA_ReincarnateUnit`
   — не найдена явно в `checkLocalParams()`/`_checkAction()`; вероятно
   обеспечивается на уровне доступности пункта меню
   (`getBuildingTabs()`), а не проверкой внутри самой команды — не
   прослеживалось до конца в этом проходе.
