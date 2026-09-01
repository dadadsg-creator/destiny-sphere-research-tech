# Механика зданий — техническая спецификация для ремастера

> Источник: `old-sphere/ds/classes/{DE,DS,UA,EH,GP,MP,ME,PN,CR}/*Building*.php`,
> `old-sphere/ds/classes/WorldUtils.class.php` (метод `setBeginningBuildings`),
> `old-sphere/ds/classes/building_class.class.php`,
> `old-sphere/ds/classes/buildings.class.php`,
> `old-sphere/Admin/controls/GameSettings/Localization/Export_CVS/data/ranking_building.csv`.
> Все перечисленные в задании файлы прочитаны полностью (построчно), не
> выборочно. Кодировка большинства файлов — Windows‑1251, чтение велось через
> `iconv -f CP1251 -t UTF-8`.
>
> Документ ориентирован на реализацию с нуля: даны точные имена таблиц/полей,
> формулы, константы вида `DE_WorldSettings::get('KEY')` (значения которых
> хранятся в БД мира и в этом коде не видны — см. «Открытые вопросы»), и
> прямые цитаты кода со ссылками `файл:строка`. Там, где вывод — предположение
> (не подтверждено чтением кода), это указано явно словом «предположительно»
> (см. также раздел «Открытые вопросы»).

## 1. Где живёт логика зданий (карта по слоям)

| Слой | Классы | Роль |
|---|---|---|
| Доменные сущности | `DE_Building`, `DE_BuildingAttributes`, `DE_BuildingByRace`, `DE_AttachedBuilding` | Типизированный доступ к статическим данным здания и к конкретному экземпляру на соте |
| Доступ к данным | `DS_Building` | Сырой SQL к `ds_building`, `ds_attachedbuilding`, `ds_buildinghasattribute` и т.д. |
| Команды игрока | `UA_ConstructBuilding`, `UA_UpgradeBuilding`, `UA_ActivateBuilding`, `UA_DeactivateBuilding`, `UA_CancelBuilding`, `UA_CancelUpgradeBuilding`, `UA_CancelActivateBuilding`, `UA_DropBuilding`, общий предок `UA_Building` | Проверка + немедленное исполнение команды из Flash‑клиента (списание ресурсов, постановка события) |
| Обработчики отложенных событий | `EH_ConstructBuilding`, `EH_UpgradeBuilding`, `EH_ActivateBuilding`, `EH_DeactivateBuilding` | Срабатывают, когда время `endtime` события настало (при рефреше инстанса игрока) — переводят здание в конечное состояние и начисляют эффекты |
| Исполнение отмены (общее для UA и EH) | `ME_CancelBuilding`, `ME_CancelUpgradeBuilding` | Возврат части ресурсов при досрочной отмене |
| Пересчёт атрибутов соты | `ME_AddBuildingAttributesToComb` | Единая функция «прибавить/вычесть атрибуты здания к атрибутам соты», используется UA/EH‑классами при каждом изменении состояния здания |
| Проверка целостности (крон/патч) | `GP_CombTurnProduction`, `MP_CombTurnProduction` | Сверяют пересчитанное с нуля производство соты с тем, что реально хранится в `ds_combhasresource`, чинят рассинхрон |
| Панели (UI) | `PN_BuildingInfo`, `PN_BuildingArtefact`, `PN_BuildingArtefact2` | Сборка XML для конкретной вкладки окна здания |
| Кеш вида соты | `CR_CombView` | Кеширует XML списка зданий/ячеек по частям соты (HOME/CENTRAL/NEUTRAL), сбрасывается методом `resetBuildings($cellTypeID)` при любом изменении здания |
| Заселение соты | `WorldUtils::setBeginningBuildings()` | Ставит стартовые здания по шаблону при создании персонажа/колонии |
| Историческая находка (не используется) | `building_class.class.php`, `buildings.class.php` | Ранний до‑UA/DE рудимент (немецкие комментарии), не подключается актуальным кодом |

Все действия игрока над зданием (`UA_*`) наследуются от `UA_Building extends
UA_Action` (`ds/classes/UA/UA_Building.class.php`), который даёт общие
проверки: `isAllowBuildOn`, `isBuildingAllowRace`, `isDisabledBuilding`,
`isUniqueTrue`, `isNeedsBuildingPresent`, `isResourcesEnough`,
`isResourcesEnoughToUpgrade`, `isScienceEnough`, `isSimiltaneousBuildsEnough`,
`getBuildingTo`, `checkUnique`, а также сборку вкладок панели
(`getBuildingTabs`/`getChildTabs`/`addChildTabs`).

Обработчики отложенных событий `EH_*` наследуются от `EH_Event` и
запускаются диспетчером очереди событий, когда наступает `endtime` (общий
паттерн для всей игры, не специфичный для зданий).

## 2. Модель данных (таблицы и поля)

### 2.1. Статическое описание типа здания

| Таблица | Ключевые поля | Назначение |
|---|---|---|
| `ds_building` | `id_building`, `buildingname` (машинное имя, англ., используется в коде как ключ спецэффектов), `id_localizedname`, `id_buildingtype` (FK → `ds_buildingtype.type`), `onlyasupgrade` (bool — здание достижимо только апгрейдом, не строится напрямую), `buildable` (bool — можно ли строить в принципе), `building_score` (int — очки рейтинга за здание, см. §7) | Базовая карточка типа здания. Источник: `DS_Building::GetByID` (`ds/classes/DS/DS_Building.class.php:5-30`), `GetBuildingScoreNew` (`:320-338`) |
| `ds_buildingtype` | `id_buildingtype`, `type` | Категория здания (текстовый тип, значение не выяснено по коду — не встречено ни одного конкретного значения `type` в прочитанных файлах) |
| `ds_buildinghasattribute` | `id_building`, `id_attribute`, `value` | Статические числовые атрибуты типа здания (стоимости, время, производство, флаги) — см. §2.3 |
| `ds_buildingcanbuildbyrace` | `id_building`, `id_race`, `id_localizedname`, `id_localizeddescription` | Разрешение здания для расы **+** локализованное имя/описание для конкретной пары раса×здание (источник `DE_BuildingByRace`) |
| `ds_buildingcanbeupgraded` | `id_from_building`, `id_to_building` | Путь апгрейда (какое здание вырастает из какого) |
| `ds_buildingcanbebuildon` | `id_building`, `id_celltype`, `id_groundtype` | На каких типах ячеек/грунта можно строить |
| `ds_buildingneedsbuilding` | `id_building_1`, `id_building_2` | Здание 1 требует присутствия активного здания 2 на той же соте |
| `ds_buildingneedsscience` | `id_building`, `id_science` | Требуемый уровень науки (через `ds_science.level`, поиск по `sciencename LIKE '...%'`) |
| `ds_buildingdeactivatepriority` | `id_building`, `priority` | Приоритет автоматической деактивации при нехватке энергии (чем меньше число, тем раньше гасится) |
| `ds_buildinghastradescope` | `id_building`, `id_tradescope` | Какие торговые области (`ds_trade_scope`) открывает здание при активации |
| `ds_buildinghasspecialfunction` | `id_building`, `id_specialfunction`, `level` | Какие вкладки панели здания доступны |
| `ds_disabledbuilding` | `id_world`, `id_building` | Здание отключено для всего мира (админский рубильник) |
| `ds_disabledbuilding_colony` | `id_world`, `id_building` | Здание отключено дополнительно для *захватываемых колоний* (`DE_Comb::getIsCapture()`) |
| `ds_buildingsalowedtouserinstance` | `id_building`, `id_userinstance` | Точечное разрешение недоступного (`buildable=false`) здания конкретному игроку (квестовые/уникальные постройки) |
| `ds_artefacttypeforbuilding` | `id_building`, `id_artefactscope`, `id_artefacttype` | Какие типы артефактов можно вставлять в здание |

### 2.2. Экземпляр здания на соте

Таблица `ds_attachedbuilding` — одна строка = одно физически стоящее
здание на конкретной ячейке:

| Поле | Тип/смысл |
|---|---|
| `id_attachedbuilding` | PK |
| `id_building` | Текущий тип здания (при апгрейде **перезаписывается** на новый id — здание не пересоздаётся, см. §5) |
| `id_cell`, `id_comb` | Ячейка и сота |
| `active` | Здание работает — его атрибуты учтены в атрибутах соты |
| `underconstruction` | Идёт первичная постройка |
| `inupgrade` | Идёт апгрейд |
| `inactivation` | Идёт переход состояния — **общий флаг и для активации, и для деактивации** (см. §6, там же — как это различить) |
| `busy` | Встречается в проверках (`PN_BuildingInfo`, `UA_DeactivateBuilding`), но в прочитанных файлах не найдено места, которое его *устанавливает* — предположительно означает «здание занято подпроцессом» (например, тренировкой юнитов в военном здании). Не подтверждено. |
| `starttime`, `endtime` | Период текущего процесса (постройка/апгрейд/(де)активация); `NULL` у зданий, поставленных напрямую при заселении соты |

`ds_attachedbuildingbindeventqueue` (`id_attachedbuilding`, `id_eventqueue`)
— связка здания с его текущим отложенным событием в `ds_eventqueue`
(генерик‑система событий всей игры, не специфична для зданий). Прогресс
события считается БД‑функцией `getProgress(id_eventqueue)` (0–100,
исходники функции не входят в PHP‑кодовую базу — она хранится в схеме
PostgreSQL, см. «Открытые вопросы»).

`ds_attachedbuildinghasattribute` (`id_attachedbuilding`, `id_attribute`)
— **бинарные** флаги на конкретном экземпляре: сам факт наличия строки =
`true`. В коде используется только для `CURRENT_FLAG/DESTROYABLE` и
`CURRENT_FLAG/DEACTIVEABLE` (можно ли *в данный момент* снести/деактивировать
именно этот экземпляр — см. §2.3 про разницу `PERMANENT_FLAG` vs
`CURRENT_FLAG`).

### 2.3. Схема атрибутов (типы, имена, значения)

Атрибуты организованы через три связанные таблицы:

```
ds_attributetype (id_attributetype, typename)      -- "категория"
ds_attributename (id_attributename, attributename) -- "что именно"
ds_attribute      (id_attribute, id_attributetype, id_attributename) -- пара тип×имя, суррогатный PK
```

Все «плоские» таблицы (`ds_buildinghasattribute`, `ds_combhasresource`,
`ds_attachedbuildinghasattribute`) ссылаются на `id_attribute`, а не
напрямую на тип/имя.

Встреченные в коде `typename` (категории атрибутов):

| typename | Где хранится значение | Смысл |
|---|---|---|
| `PRODUCTION_COSTS` | `ds_buildinghasattribute` (по типу здания) | Стоимость постройки/апгрейда: по одной строке на ресурс + отдельная строка `attributename='CONSTRUCTION_TIME'` (время в турнах) |
| `TURN_PRODUCTION` | `ds_buildinghasattribute` → суммируется в `ds_combhasresource` | Производство здания за турн, по видам ресурса |
| `TURN_UPKEEP` | аналогично | Потребление здания за турн (в частности `STASIS_ENERGY`, см. §8) |
| `MAX_AMOUNT` | аналогично | Лимиты, которые здание добавляет соте (напр. `WORKER` — рабочие места, `SIMILTANEOUS_BUILDS` — сколько построек можно вести одновременно, `ARTEFACT_SLOTS` — слоты артефактов) |
| `STORAGE_FACTOR`, `STORAGE_FACTOR_ABSOLUT` | аналогично | Модификатор вместимости хранилищ ресурсов (кто именно — Убежище/`SHELTER`, силосы) |
| `PERMANENT_FLAG` | `ds_buildinghasattribute`, привязан к **типу** здания | Задаёт, ставить ли `CURRENT_FLAG`‑атрибут новому экземпляру при постройке/апгрейде. Есть подвох: `value = 0` у строки `PERMANENT_FLAG` **подавляет** установку соответствующего `CURRENT_FLAG` (см. цитату ниже); отсутствие строки вообще — эквивалентно «флаг ставится» (по умолчанию сносимо/деактивируемо) |
| `CURRENT_FLAG` | `ds_attachedbuildinghasattribute`, привязан к **экземпляру** | Фактическое «можно снести сейчас» / «можно деактивировать сейчас» на конкретном здании |
| `BEGINNING_FLAG` | `ds_buildinghasattribute` | То же самое (`DESTROYABLE`/`DEACTIVEABLE`), но проверяется отдельно при расстановке **стартовых** зданий (`WorldUtils::setBeginningBuildings`, см. §9) |

Встреченные `attributename` (значения внутри `PRODUCTION_COSTS`/
`TURN_PRODUCTION`/`TURN_UPKEEP`/`MAX_AMOUNT`): `CONSTRUCTION_TIME`,
`KADERIUM`, `NANO_CRYSTAL`, `STASIS_ENERGY`, `FOOD`, `PRODIUM`, `WORKER`,
`SILVER_CREDIT`, `GOLD_CREDIT`, `SIMILTANEOUS_BUILDS`, `ARTEFACT_SLOTS`,
`DESTROYABLE`, `DEACTIVEABLE`, `LONG_DEACTIVATION`, `ARCHEOLOGIST`,
`SCIENTIST`.

Шесть «основных» ресурсов постройки — константа в коде, не в БД:

```php
// ds/classes/GB/GB_ResourcesList.class.php:10
static public $allBuildResources = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY', 'WORKER');
```

**Важная находка‑нестыковка**: `UA_Building::isResourcesEnough()`
(`ds/classes/UA/UA_Building.class.php:232-306`) внутри цикла по
`$allBuildResources` содержит ветку `if ($resourceName == 'SILVER_CREDIT' OR
$resourceName == 'GOLD_CREDIT')`, которая проверяет баланс игрока и
блокирует постройку за «недостаточно денег» — но `SILVER_CREDIT`/
`GOLD_CREDIT` **не входят** в массив `$allBuildResources` (см. цитату выше),
поэтому эта ветка в реальности **никогда не выполняется** в этом методе (в
`UA_ConstructBuilding::performAction`, где реально списываются
`PRODUCTION_COSTS`, аналогичная ветка тоже есть — строки 207‑224 — и она
куда до конца не реализована: комментарий `//Здесь будет снятие денег` без
кода списания). Итог: **платная постройка за золото/серебро в коде,
похоже, не завершена / не активна** несмотря на наличие переключателя
`BUILDING_CAN_BUIL_BY_MONEY`. Явно фиксирую как открытый вопрос ниже.

### 2.4. Атрибуты соты (динамика)

`ds_combhasresource` (`id_comb`, `id_attribute`, `value`) — единственное
хранилище всех «текущих» и «максимальных» показателей соты. Здесь лежат и
`CURRENT_AMOUNT`/`MAX_AMOUNT` ресурсов, и просуммированные
`TURN_PRODUCTION`/`TURN_UPKEEP`/`STORAGE_FACTOR(_ABSOLUT)` от всех активных
зданий соты. Доменная обёртка — `DE_CombAttributes` (не входит в список
файлов задания, здесь не разбирается построчно; используется вездесущим
методом `deltaAttribute(type, name, delta)`/`getAttribute`/`haveAttribute`/
`setAttribute`).

## 3. Жизненный цикл здания (состояния и переходы)

Состояние экземпляра здания целиком описывается четырьмя булевыми полями
`ds_attachedbuilding`: `underconstruction`, `active`, `inupgrade`,
`inactivation`. Комбинации, реально возникающие в коде:

| # | underconstruction | active | inupgrade | inactivation | Смысл |
|---|---|---|---|---|---|
| 1 | true | false | false | false | Строится впервые |
| 2 | false | true | false | false | Работает нормально |
| 3 | false | true | false | true | Активно, но одновременно идёт **долгая** деактивация (см. §6) — атрибуты соте всё ещё начислены |
| 4 | false | false | false | true | Неактивно, идёт **активация** (`ACTIVATE_BUILDING`) |
| 5 | false | true | true | false | Активно, но одновременно апгрейдится (кроме числа рабочих мест — см. §5, атрибуты производства/потребления временно сняты) |
| 6 | false | false | false | false | Неактивно, простаивает (можно нажать «активировать») |

```
                          UA_ConstructBuilding
                                  │  (ресурсы списаны сразу,
                                  ▼   событие CONSTRUCT_BUILDING)
                    ┌───── underconstruction=true ─────┐
                    │                                   │
         UA_CancelBuilding                    EH_ConstructBuilding
     (частичный возврат ресурсов,                 (по истечении endtime)
      строка ds_attachedbuilding                          │
      УДАЛЯЕТСЯ целиком)                                  ▼
                    │                          underconstruction=false
                    ▼                               active=true
                (нет строки)              (+production/upkeep/max к соте)
                                                          │
                        ┌─────────────────────────────────┼───────────────────────┐
                        ▼                                 ▼                       ▼
              UA_DeactivateBuilding                UA_UpgradeBuilding      UA_DropBuilding
           (LONG_DEACTIVATION? событие         (ресурсы/UPGRADE_COSTS_DIVISOR    (снос, только если
            DEACTIVATE_BUILDING : мгновенно)     списаны сразу, событие          инстанс‑флаг
                        │                          UPGRADE_BUILDING,             DESTROYABLE стоит)
             ┌──────────┴──────────┐               старое здание временно
             ▼                     ▼                "выключено" кроме WORKER)
    active=false сразу      inactivation=true,               │
   (мгновенный путь)        active остаётся true    UA_CancelUpgradeBuilding
                                    │                (полный возврат старых
                          EH_DeactivateBuilding        атрибутов, частичный
                          (по истечении endtime)        возврат потраченного,
                                    │                    inupgrade=false)
                                    ▼                            │
                            active=false                EH_UpgradeBuilding
                        (+ каскад для CENTRAL_GATE,      (по истечении endtime)
                          см. §6)                                 │
                                                                    ▼
                                                        id_building = новый тип,
                                                        inupgrade=false,
                                                        active=true, атрибуты нового
                                                        здания добавлены к соте

    UA_ActivateBuilding (из состояния "неактивно простаивает")
                        │  (без списания ресурсов!)
                        ▼
          inactivation=true, active остаётся false
                        │
             UA_CancelActivateBuilding ──► inactivation=false (без побочных эффектов)
                        │
             EH_ActivateBuilding (по endtime)
                        ▼
          active=true, inactivation=false
          (+production/upkeep к соте; если CENTRAL_GATE — доп. эффекты, см. §11)
```

## 4. Постройка здания

### 4.1. Команда игрока — `UA_ConstructBuilding`

Источник: `ds/classes/UA/UA_ConstructBuilding.class.php`. Особенность:
клиент может прислать сразу несколько ячеек через параметр `c` (список
через запятую) — команда пытается построить здание **на каждой** из них по
очереди, останавливаясь, как только не хватает ресурсов или упёрлось в
лимит одновременных строек/уникальность:

```php
// ds/classes/UA/UA_ConstructBuilding.class.php:126-146
foreach ($this->cells as $key => $cell) {
    ...
    if(!$this->isResourcesEnough($this->comb, $this->bid, $lastTestResInfo)) {
        $this->processNotEnoughtResources();
        break;
    }
    if (!$this->isUniqueTrue($this->comb, $this->bid)) { break; }
    if (!$this->isSimiltaneousBuildsEnough($this->comb)) { break; }
    ...
}
```

Проверки в `checkAction()` (все — обязательные предусловия):
1. `isBuildingAllowRace($bid, $race)` — `ds_buildingcanbuildbyrace`.
2. `isUniqueTrue($comb, $bid)` — БД‑функция `isUniqueTrue_2` (не входит в
   PHP‑код, см. открытые вопросы), предположительно проверяет, что здание с
   таким же ключом уникальности ещё не построено на соте.
3. `isNeedsBuildingPresent($comb, $bid)` — БД‑функция
   `isNeedsBuildingPresent_2` (на основе `ds_buildingneedsbuilding`).
4. `!isDisabledBuilding($world, $bid)` — `ds_disabledbuilding` /
   `ds_disabledbuilding_colony` (для захватываемых колоний) + проверка
   `ds_building.buildable` с точечным оверрайдом через
   `ds_buildingsalowedtouserinstance`.
5. `isAllowBuildOn($bid, $cell, $groundtype)` — `ds_buildingcanbebuildon`.
6. `isResourcesEnough(...)` — **не строгая assert‑проверка**: если ресурсов
   не хватает, флаг `isResourceEnought=false`, но `checkAction()` всё равно
   возвращает `true` (и `performAction()` сама остановится и пришлёт
   сообщение `BUILD_NOT_ENOUGHT_RESOURCES`).
7. `isScienceEnough($uiid, $bid)` — БД‑функция `isScienceEnough`.
8. `isSimiltaneousBuildsEnough($comb)` — см. формулу в §4.3.

Списание ресурсов происходит **сразу в момент отправки команды**, а не по
завершении строительства (`ds/classes/UA/UA_ConstructBuilding.class.php:196-230`):

```php
$attributeNames = $buildingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
foreach ($attributeNames as $k=>$attributeName) {
    if ( $attributeName == 'CONSTRUCTION_TIME') { continue; }
    if ($attributeName == 'SILVER_CREDIT' || $attributeName == 'GOLD_CREDIT') { continue; /* см. §2.3 */ }
    $value = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);
    $combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, -$value);
}
```

Затем создаётся строка `ds_attachedbuilding` (`active=false,
underconstruction=true`), событие `CONSTRUCT_BUILDING` (через общий
`createEvent()` из `UA_Action`) и связка в
`ds_attachedbuildingbindeventqueue`. `SIMILTANEOUS_BUILDS` соты
инкрементируется на 1.

### 4.2. Формула времени постройки

```php
// ds/classes/UA/UA_ConstructBuilding.class.php:278-293
select now() as starttime,
       now() + (text((<TURN_TIME>*60*bha.value))::interval) as endtime
from ... where att.typename = 'PRODUCTION_COSTS' and atn.attributename = 'CONSTRUCTION_TIME'
      and b.id_building = <bid>
```

**Формула**: `время постройки (сек) = CONSTRUCTION_TIME(здание) * TURN_TIME(мир) * 60`,
где `CONSTRUCTION_TIME` — значение атрибута `PRODUCTION_COSTS/CONSTRUCTION_TIME`
здания (в турнах), `TURN_TIME` — мировая настройка «сколько минут в одном
турне» (`DE_WorldSettings::get('TURN_TIME')`, значение — в БД мира, не в
коде).

### 4.3. Лимит одновременных строек

```php
// ds/classes/UA/UA_Building.class.php:591-607
$max = $combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS')
     + $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');
if ($current >= $max) { /* запрет */ }
```

`SIMILTANEOUS_BUILDS_ADDON` — глобальная надбавка мира (например, премиум‑
бонус или настройка баланса), значение — в БД. `MAX_AMOUNT/SIMILTANEOUS_BUILDS`
соты растёт от конкретных зданий (обычно `CONSTRUCTION_FACTORY` — судя по
названию из справочника зданий, не подтверждено конкретным числом в коде).
Один и тот же счётчик `CURRENT_AMOUNT/SIMILTANEOUS_BUILDS` расходуется и
постройкой, и апгрейдом (не раздельные пулы). Конкретные итоговые значения
этого лимита на нескольких живых мирах 2026 года (портал, не код) — см.
§17.3.

### 4.4. Завершение — `EH_ConstructBuilding`

Источник: `ds/classes/EH/EH_ConstructBuilding.class.php` (372 строки,
прочитан полностью). Если время ещё не настало — шлёт игроку прогресс
(`_getComplitPart()*100`, из `EH_Event`) сообщением `PROGRESS_BUILDING_MESS`
и выходит. Если настало:

1. `underconstruction=false, active=true` (`:76-85`).
2. `ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes,
   $combAttributes, 'plus')` — прибавляет `TURN_PRODUCTION`, `MAX_AMOUNT`,
   `TURN_UPKEEP`, `STORAGE_FACTOR`, `STORAGE_FACTOR_ABSOLUT` здания к соте
   (см. точную формулу в §7).
3. `SIMILTANEOUS_BUILDS` соты `-1`.
4. Проставляет инстанс‑флаги `CURRENT_FLAG/DESTROYABLE` и
   `CURRENT_FLAG/DEACTIVEABLE` в `ds_attachedbuildinghasattribute`, **если**
   `PERMANENT_FLAG` этого типа здания не равен `0` (см. §2.3).
5. Если у здания есть строки в `ds_buildinghastradescope` — прописывает
   `ds_comballowedtradescope` (открывает торговые области соте), с защитой
   от дублей.
6. Спецэффекты по машинному имени здания (`buildingName`, поле
   `ds_building.buildingname`) — таблица ниже.
7. `DE_Comb::deltaCombScore(GetBuildingScore($bid))` — рейтинг соты растёт
   на `ds_building.building_score`.
8. `DE_Ranking::deltaBuildingRanking(GetBuildingScoreNew($bid))` — тот же
   `building_score` идёт и в личный строительный рейтинг игрока.
9. Сброс кеша вида соты (`CR_CombView::resetBuildings`), системное
   сообщение `FINISH_BUILDING_MESS`, событие помечается `setTrash()`.

### 4.5. Спецэффекты по имени здания при завершении постройки

| `buildingname` | Эффект | Источник |
|---|---|---|
| `ARCHEOLOGICAL_INSTITUTE` | Если на соте ещё нет ни одного *построенного* (не в стройке) здания с `id_building=12` (Институт) **и** `id_building=47` (Лагерь археологов, `ARCHEOLOGICAL_OUTPOST`) — добавляет `CURRENT_AMOUNT/ARCHEOLOGIST` в размере `DE_WorldSettings::get('COUNT_ADD_ARCHEOLOGISTS_CONSTRUCT_INSTITUT')` | `EH_ConstructBuilding.class.php:46-59`. **ID 12/47 захардкожены** в PHP, не резолвятся по имени — фрагильная зависимость от конкретных ID в БД |
| `RESEARCH_CENTER` | Аналогично: если на соте ещё нет действующего здания `id_building=29` — добавляет `CURRENT_AMOUNT/SCIENTIST` в размере `COUNT_ADD_CSIENTISTS_CONSTRUCT_CENTER` (опечатка `CSIENTISTS` — так называется ключ настройки в реальном коде) | `:62-74`. ID 29 захардкожен |
| `COLONIZATION_CENTER` | Создаёт запись `ds_unitcontainer` типа `COLONIZATION` (`moveable=true, inaction=false`), привязанную к соте и игроку — судя по всему, «модуль колонизации», которым потом можно основать новую соту | `:254-279` |
| `ALLIANCE_CONNECTOR` («Посольство») | Если игрок состоит в **глобальном** (межмировом) альянсе (`DS_GlobalAlliance::GetUserGlobalAlliance`), находит соответствующий локальный альянс этого мира (`ds_alliance.id_global_alliance`) и автоматически вступает в него (`ME_AddUserToAlliance::Add`) | `:282-324` |
| `PROTECTIVE_FIELD_GENERATOR` | Ставит соте защитное поле на 100% (`DE_CombProtectiveField::setProtectiveField(100)`) | `:327-332` |

Плюс — универсальные для всех зданий: применение trade scope
(`ds_buildinghastradescope`) и очки рейтинга (`building_score`) — см. §4.4.

## 5. Апгрейд здания

### 5.1. Команда — `UA_UpgradeBuilding`

Источник: `ds/classes/UA/UA_UpgradeBuilding.class.php` (354 строки).

Целевое здание апгрейда определяется join'ом `ds_buildingcanbeupgraded` ×
`ds_buildingcanbuildbyrace` (по расе игрока), с исключением
`ds_disabledbuilding` (и `ds_disabledbuilding_colony` для колоний)
(`:87-116`) — то есть **у одного исходного здания на одну расу
подразумевается единственный доступный путь апгрейда** (запрос ожидает
ровно одну строку результата).

Проверки: `isSimiltaneousBuildsEnough`, `isNeedsBuildingPresent`,
`isResourcesEnoughToUpgrade` (не блокирует, только флаг
`isResourceEnought`), `isScienceEnough`. Дополнительно **нельзя начать
апгрейд, пока в здании тренируются юниты**:

```php
// ds/classes/UA/UA_UpgradeBuilding.class.php:174-183
$sql = "SELECT * FROM ds_unitinproduction WHERE id_attachedbuilding = ".$this->attachedbid;
if(count($res) > 0) { /* CANT_UPGRADE_BUILDING_WITH_UNITS, отмена */ }
```

### 5.2. Формулы стоимости и времени апгрейда

```php
// ds/classes/UA/UA_UpgradeBuilding.class.php:233
$delta = -round($buildingToAttributes->getAttribute('PRODUCTION_COSTS', $attributeName)
                / $this->worldSettings->get('UPGRADE_COSTS_DIVISOR'));
```

**Стоимость апгрейда по каждому ресурсу = `PRODUCTION_COSTS` ЦЕЛЕВОГО (не
исходного!) здания, делённая на мировую константу `UPGRADE_COSTS_DIVISOR` и
округлённая (`round`, банковское/математическое округление PHP)**. Это
значит, что стоимость апгрейда — не «дельта» между уровнями, а полная
базовая стоимость следующего здания, уменьшенная в фиксированное число раз.
Списывается **только** если у соты вообще есть такой атрибут
(`haveAttribute`) — на практике всегда есть.

```php
// ds/classes/UA/UA_UpgradeBuilding.class.php:315-330
now() + (text((<TURN_TIME>*60*bha.value)/(<UPGRADE_COSTS_DIVISOR>)))::interval
```

**Время апгрейда (сек) = `CONSTRUCTION_TIME`(целевое здание) * `TURN_TIME` *
60 / `UPGRADE_COSTS_DIVISOR`** — тот же делитель, что и для стоимости.

### 5.3. Что происходит с атрибутами во время апгрейда

Здание **не пересоздаётся** — та же строка `ds_attachedbuilding` просто
получает `inupgrade=true`. Атрибуты старого здания временно снимаются с
соты (кроме рабочих мест):

```php
// ds/classes/UA/UA_UpgradeBuilding.class.php:244-254
if($buildingFromAttributes->haveAttribute('MAX_AMOUNT', 'WORKER')) {
    $combAttributes->deltaAttribute('MAX_AMOUNT', 'WORKER',
        $buildingFromAttributes->getAttribute('MAX_AMOUNT', 'WORKER')); // возвращаем обратно
}
ME_AddBuildingAttributesToComb::AddAttributes($buildingFromAttributes, $combAttributes, 'minus'); // снимает всё, включая WORKER
ME_AddBuildingAttributesToComb::AddArtefactAttributes($this->attachedbid, $combAttributes, 'minus'); // снимает бонусы артефактов
```

То есть **лимит рабочих мест здания сохраняется на всё время апгрейда**
(чтобы уже нанятых туда рабочих не «выкинуло»), а вот
производство/потребление/прочие `MAX_AMOUNT` (и бонусы вставленных
артефактов) на время апгрейда **отключаются** — здание фактически «встаёт
на паузу». `SIMILTANEOUS_BUILDS` соты `+1`.

### 5.4. Завершение — `EH_UpgradeBuilding`

Источник: `ds/classes/EH/EH_UpgradeBuilding.class.php` (302 строки).
При наступлении `endtime`:

1. `ds_attachedbuilding.ID_Building` **перезаписывается** на новый id
   (`:57-59`), `inupgrade=false`.
2. Старый `MAX_AMOUNT/WORKER` (временно оставленный по §5.3) вычитается,
   затем добавляются **все** атрибуты нового здания (`plus`) и бонусы
   вставленных артефактов (`plus`) — т.е. рабочие места пересчитываются под
   новое здание с нуля.
3. `SIMILTANEOUS_BUILDS` соты `-1`.
4. Инстанс‑флаги `DESTROYABLE`/`DEACTIVEABLE` пересчитываются **для нового**
   типа здания (та же логика через `PERMANENT_FLAG`, что и при постройке).
5. **Рейтинг соты**: `DE_Comb::deltaCombScore(GetBuildingScore($id_to))` —
   прибавляется **полный** `building_score` НОВОГО здания.
   **Рейтинг игрока**: `DE_Ranking::deltaBuildingRanking(scoreTo - scoreFrom)`
   — прибавляется именно **дельта**.

   ```php
   // ds/classes/EH/EH_UpgradeBuilding.class.php:242-254
   $buildinScore = DS_Building::GetBuildingScore((int)$this->eventparams["ID_BuildingTo"]);
   DE_Comb::CreateFromID(...)->deltaCombScore($buildinScore);           // ПОЛНЫЙ score нового
   $buildinScore2 = GetBuildingScoreNew($to) - GetBuildingScoreNew($from);
   DE_Ranking::...->deltaBuildingRanking($buildinScore2);                // ТОЛЬКО дельта
   ```

   **Возможная особенность поведения (не баг ли — не проверено на живой
   игре)**: при постройке рейтинг соты уже увеличивается на
   `building_score` исходного здания (§4.4 п.7); при каждом апгрейде он
   растёт **ещё раз на полный `building_score` нового уровня**, а не на
   разницу. То есть `deltaCombScore` при апгрейдах, в отличие от
   `deltaBuildingRanking`, накапливает потенциально больше очков, чем
   «итоговая ценность» цепочки апгрейдов. Раз это два разных счётчика с
   разной формулой в одном и том же обработчике — при реализации ремастера
   стоит явно решить, воспроизводить ли это 1:1 или считать багом
   оригинала и исправлять (см. «Открытые вопросы»).
6. Особый случай — **если новое здание `CENTRAL_GATE`** и у игрока ещё
   действует защита новичка: защита продлевается до
   `now + NEWB_DEFEND_AFTER_GATE_OPEN*24 * TURN_TIME*60` секунд, шлётся
   сообщение `NEWB_OPEN_GATE`, и если игрока кто‑то привёл по рефералке —
   рефереру начисляется `GATE_OPEN_SUM` золотых кредитов
   (`ds/classes/EH/EH_UpgradeBuilding.class.php:263-282`). Это подтверждает,
   что «открытие ворот» на старте — это апгрейд руин `CENTRAL_GATE_WRECK`
   → `CENTRAL_GATE` (а не отдельная команда).

### 5.5. Отмена апгрейда — `ME_CancelUpgradeBuilding::Cancel()`

Вызывается из `UA_CancelUpgradeBuilding::performAction()`. Полностью
возвращает атрибуты СТАРОГО здания на соту (`plus`, отменяя временное
`minus` из §5.3) и бонусы артефактов, `inupgrade=false`.

Возврат ресурсов:

```php
// ds/classes/ME/ME_CancelUpgradeBuilding.class.php:92-124
$prog = getProgress($bindeqid);                          // % готовности апгрейда, 0..100
$minlost = $worldSettings->get('MIN_RES_LOST_ON_CANCEL_BUILDING') * 100;
if ($prog < $minlost) { $prog = $minlost; }               // не даём вернуть больше, чем позволяет минимальный штраф
$devisor = $worldSettings->get('UPGRADE_COSTS_DIVISOR');
foreach (PRODUCTION_COSTS ЦЕЛЕВОГО здания as $attributeName) {
    if ($attributeName == 'CONSTRUCTION_TIME') continue;
    $value = ...;
    if ($attributeName != 'WORKER') {
        $delta = round(($value - ($value*$prog)/100) / $devisor);   // (100-prog)% стоимости, тот же UPGRADE_COSTS_DIVISOR
    } else {
        $delta = round($value / $devisor);                          // рабочие возвращаются ПОЛНОСТЬЮ, без учёта прогресса
    }
    $combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $delta);
}
```

**Формула возврата**: игрок получает назад `(100 − max(prog,
MIN_RES_LOST_ON_CANCEL_BUILDING·100))%` от суммы, которая была списана при
старте апгрейда (сама сумма уже была поделена на `UPGRADE_COSTS_DIVISOR`,
поэтому здесь тот же делитель применяется повторно к номинальной
`PRODUCTION_COSTS`, что арифметически эквивалентно). `WORKER` — исключение:
возвращается полностью, независимо от прогресса.
`SIMILTANEOUS_BUILDS` соты `-1` выполняет сам `UA_CancelUpgradeBuilding`
(не `ME_CancelUpgradeBuilding`).

## 6. Активация и деактивация

### 6.1. Активация — `UA_ActivateBuilding` / `EH_ActivateBuilding`

Применимо к зданию в состоянии «неактивно, не строится, не активируется»
(`active=false, underconstruction=false, inactivation=false`). Если здание
не `onlyasupgrade`, требуются присутствующие `ds_buildingneedsbuilding`
(`isNeedsBuildingPresent`). **Активация бесплатна** — нигде в
`UA_ActivateBuilding` не списываются ресурсы, только время.

```php
// ds/classes/UA/UA_ActivateBuilding.class.php:181-197
now() + (text(bha.value*((<TURN_TIME>*60)/(<REACTIVATION_DIVISOR>)))::interval)
-- где bha.value = PRODUCTION_COSTS/CONSTRUCTION_TIME здания
```

**Время активации (сек) = `CONSTRUCTION_TIME`(здание) * `TURN_TIME` * 60 /
`REACTIVATION_DIVISOR`** — та же база (`CONSTRUCTION_TIME`), что и время
первичной постройки, но с отдельным делителем `REACTIVATION_DIVISOR`
(другая мировая константа, не `UPGRADE_COSTS_DIVISOR`).

Устанавливается `inactivation=true` (при `active` всё ещё `false`).
`UA_CancelActivateBuilding` откатывает это простым сбросом
`inactivation=false` (без каких‑либо возвратов, т.к. активация ничего не
стоила).

`EH_ActivateBuilding` по завершении: `active=true, inactivation=false`,
атрибуты и бонусы артефактов добавляются к соте (`plus`). Особый случай —
**если это `CENTRAL_GATE`**:
- Прописывает соте все `ds_buildinghastradescope` этого здания в
  `ds_comballowedtradescope`.
- Если игрок состоит в (локальном) альянсе — дополнительно открывает
  торговую область `ALLIANCE_TRADING`, если её ещё нет.
- Если `DE_WorldSettings::get('MAX_TIME_RANDOM_DEFEND_TIME') != 0` — выдаёт
  игроку случайную защиту (`rand(0, MAX_TIME_RANDOM_DEFEND_TIME)` турнов) —
  `RandomDefendEndDate`/`RandomDefendEndDateMax`.

  (`ds/classes/EH/EH_ActivateBuilding.class.php:88-190`)

### 6.2. Деактивация — `UA_DeactivateBuilding` / `EH_DeactivateBuilding`

Применимо только если у **конкретного экземпляра** уже стоит
`CURRENT_FLAG/DEACTIVEABLE` (не только у типа здания — важно: проверяется
именно инстанс‑флаг из `ds_attachedbuildinghasattribute`) и здание
`active=true, inactivation=false, underconstruction=false, inupgrade=false`.

Дальше — развилка на «мгновенно» / «долго», по наличию у **типа** здания
атрибута `PERMANENT_FLAG/LONG_DEACTIVATION` (проверяется сам факт наличия
строки, **не её значение** — если строка есть, путь всегда «долгий», даже
если `value=0`):

```php
// ds/classes/UA/UA_DeactivateBuilding.class.php:113-131
select bha.value from ... where atn.attributename = 'LONG_DEACTIVATION'
  and att.typename = 'PERMANENT_FLAG' and bha.id_building = <bid>
if ( sizeof(res) > 0 ) { /* долгий путь, событие */ } else { /* мгновенно */ }
```

**Долгий путь**: `inactivation=true` (при этом `active` остаётся `true` —
атрибуты соте всё ещё начислены до завершения!), событие
`DEACTIVATE_BUILDING`, время:

```php
// ds/classes/UA/UA_DeactivateBuilding.class.php:472-475 (формула идентична активации)
now() + (text(bha.value*((<TURN_TIME>*60)/(<REACTIVATION_DIVISOR>)))::interval)
-- НО bha.value здесь берётся из того же запроса что и для CONSTRUCTION_TIME
```

(технически запрос ищет `att.typename = 'PRODUCTION_COSTS' and
atn.attributename = 'CONSTRUCTION_TIME'` — то есть та же база
`CONSTRUCTION_TIME`, что и для активации, тем же `REACTIVATION_DIVISOR`;
значение `LONG_DEACTIVATION` используется только как **булев переключатель
пути**, а не как множитель времени).

**Мгновенный путь**: сразу вызывается внутренний метод `deactivate()`.
Метод `getBuildingToDeact()` формально задуман как поиск каскада зависимых
зданий (через `ds_buildingneedsbuilding`), но **сама рекурсивная логика
целиком закомментирована в коде**
(`ds/classes/UA/UA_DeactivateBuilding.class.php:258-316`, блок `/* ... */`)
— в реальности деактивируется **только само здание**, зависимые постройки
автоматически не гасятся при ручной деактивации (в отличие от каскада
`CENTRAL_GATE`, см. ниже — там своя отдельная явная логика).

`deactivate($todeact, $comb, $uiid, $lang)` (общий метод, используется и
`UA_DropBuilding`, и `EH_DeactivateBuilding`, и авто‑деактивацией §8) для
каждого здания из списка:
1. `active=false`.
2. `ME_AddBuildingAttributesToComb::AddAttributes(..., 'minus')` — снимает
   атрибуты с соты.
3. `ME_AddBuildingAttributesToComb::AddArtefactAttributes(..., 'minus')` —
   снимает бонусы артефактов.
4. Если в здании шла тренировка юнитов (`ds_unitinproduction`) — тренировка
   отменяется с **частичным** возвратом ресурсов (по формуле
   `round(cost * amount * MIN_RES_LOST_ON_CANCEL_UNIT)` для обычных
   ресурсов; рабочие возвращаются полностью, но не выше свободного
   `MAX_AMOUNT-CURRENT_AMOUNT`), юнит‑запись удаляется, шлётся сообщение
   `CANCEL_TRAIN_UNIT_MESS`.
5. Привязанное событие (если есть) помечается `isTrash=true`.

### 6.3. Каскад Центральных Ворот (`CENTRAL_GATE`)

Особый случай в `EH_DeactivateBuilding.class.php:80-248`: если
деактивируемое здание — `CENTRAL_GATE`, при завершении деактивации:

1. Все здания в ячейках типа **не `HOME` и не `NEUTRAL`** (то есть
   `CENTRAL`), у которых сейчас идёт `underconstruction`/`inupgrade`/
   `inactivation`, принудительно обрываются: строящиеся — через
   `ME_CancelBuilding::Cancel()` (полная отмена постройки с частичным
   возвратом), апгрейдящиеся — через `ME_CancelUpgradeBuilding::Cancel()`,
   а те, что просто были в `inactivation` (активировались или
   деактивировались) — у них флаг `inactivation` тупо сбрасывается в
   `false` **без отмены события** (то есть их прогресс «замораживается» на
   месте, а не откатывается) — это единственный найденный в коде путь,
   которым «отменяется» именно **долгая деактивация** отдельного здания
   (у игрока нет прямой команды `UA_CancelDeactivateBuilding` — такого
   класса в кодовой базе нет).
2. Все ещё активные здания в CENTRAL‑ячейках с `CURRENT_FLAG/DEACTIVEABLE`
   деактивируются (тем же методом `deactivate()`).
3. У соты убираются торговые области `RING_TRADING`, `INTERRING_TRADING`,
   `ALLIANCE_TRADING`.
4. Все армии на соте возвращаются домой
   (`ME_ReturnAllArmysToComb::ReturnArmys`,
   `ME_KickAllArmysFromCombOnDefend::ReturnArmys`).

Иными словами, `CENTRAL_GATE` — «рубильник» всей центральной (не домашней и
не нейтральной) части соты: без открытых ворот там ничего не может ни
строиться, ни работать, ни торговать за пределы соты.

## 7. Пересчёт производства/потребления соты

### 7.1. `ME_AddBuildingAttributesToComb::AddAttributes()` — единая точка входа

Источник: `ds/classes/ME/ME_AddBuildingAttributesToComb.class.php` (107
строк). Вызывается **при каждом** изменении состояния здания (постройка,
апгрейд, (де)активация, снос) с параметром `'plus'` или `'minus'`:

```php
$syncAttributeTypes = array('TURN_PRODUCTION', 'MAX_AMOUNT', 'TURN_UPKEEP', 'STORAGE_FACTOR', 'STORAGE_FACTOR_ABSOLUT');
foreach ($syncAttributeTypes as $attributeTypeName) {
    foreach ($buildingAttributes->getAttributeNamesByTypeName($attributeTypeName) as $attributeName) {
        if (!$combAttributes->haveAttribute($attributeTypeName, $attributeName)) continue;
        $delta = $buildingAttributes->getAttribute($attributeTypeName, $attributeName);
        $delta = ($addType == 'minus') ? -$delta : $delta;
        if ($attributeTypeName == 'MAX_AMOUNT') {
            $combAttributes->deltaAttribute('MAX_AMOUNT', $attributeName, $delta);
            // если после уменьшения MAX_AMOUNT текущее значение стало больше нового максимума — обрезаем CURRENT_AMOUNT до MAX_AMOUNT
            if ($combAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName) && $addType == 'minus') {
                if (CURRENT_AMOUNT > MAX_AMOUNT) { setAttribute('CURRENT_AMOUNT', $attributeName, MAX_AMOUNT); }
            }
        } else {
            $combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
        }
    }
}
```

То есть **производство/потребление/лимиты — это просто сумма
соответствующих атрибутов всех активных зданий соты**, поддерживаемая
инкрементально (при каждом plus/minus), а не пересчитываемая с нуля на
каждый запрос. `STORAGE_FACTOR`/`STORAGE_FACTOR_ABSOLUT` — закомментированный
блок в коде (`:37-55`) показывает, что раньше для них было особое
поведение (при снятии здания `SHELTER` фактор откатывался к базовому
значению `SHELTER`, а не просто вычитался) — в текущей версии эта ветка
**отключена**, и `STORAGE_FACTOR*` идёт через тот же `default:` (обычное
инкрементальное `deltaAttribute`), что и остальные типы.

`AddArtefactAttributes($attachedBuildingID, $combAttributes, $addType)` —
отдельно прибавляет/вычитает бонусы **артефактов, вставленных в конкретное
здание** (`DS_Artefact::GetSummaryBonnusByAttachedBuildingID`), той же
формулой plus/minus.

### 7.2. Сверка/патч — `GP_CombTurnProduction` vs `MP_CombTurnProduction`

Оба класса решают одну задачу: **пересчитать производство соты с нуля по
всем активным зданиям** и сравнить с тем, что реально хранится в
`ds_combhasresource`, залогировав/исправив расхождение. Базовый SQL
идентичен:

```sql
SELECT atn.attributename, SUM(ba.value) AS production
FROM ds_attachedbuilding ab
  JOIN ds_building b ON (ab.id_building = b.id_building)
  JOIN ds_buildinghasattribute ba ON (b.id_building = ba.id_building)
  JOIN ds_attribute atr ON (ba.id_attribute = atr.id_attribute)
  JOIN ds_attributename atn ON (atr.id_attributename = atn.id_attributename)
  JOIN ds_attributetype att ON (atr.id_attributetype = att.id_attributetype)
WHERE ab.id_comb = :combID
  AND ab.active = true
  AND ab.underconstruction = false
  AND ab.inactivation = false
  AND ab.inupgrade = false
  AND att.typename = 'TURN_PRODUCTION'   -- см. отличие ниже
GROUP BY atn.attributename
```

Отличия (сравнение построчное, оба файла прочитаны полностью):

| | `GP_CombTurnProduction` (`ds/classes/GP/GP_CombTurnProduction.class.php`, 70 строк) | `MP_CombTurnProduction` (`ds/classes/MP/MP_CombTurnProduction.class.php`, 82 строки) |
|---|---|---|
| Типы атрибутов в `WHERE att.typename` | Только `'TURN_PRODUCTION'` | `IN ('TURN_PRODUCTION', 'STORAGE_FACTOR', 'STORAGE_FACTOR_ABSOLUT')` — шире |
| Поведение при несовпадении | Только `SetCombAttributeByID` на уже существующую строку `ds_combhasresource` | То же, но если атрибута у соты вообще нет (`$productionID == 0`) — **добавляет** новую строку через `InsertCombAttribute` — то есть умеет чинить полностью отсутствующие записи, `GP`‑версия — нет |
| Родитель | `GP_CombBase` | `MP_CombBase` |

Условие «здание учитывается» одинаковое в обоих: `active=true AND
underconstruction=false AND inactivation=false AND inupgrade=false` — то
есть **пока здание в состоянии перехода (стройка/апгрейд/(де)активация),
его TURN_PRODUCTION/STORAGE_FACTOR не учитываются вовсе**, даже если формально
`active=true` (актуально для «долгой» деактивации из §6.2, где `active` ещё
`true`, но `inactivation` уже `true` — на этот период здание выпадает из
пересчёта производства **до того**, как `EH_DeactivateBuilding` явно вычтет
его атрибуты). Согласно комментарию в шапке `MP_CombTurnProduction.class.php:3-6`,
это «проверка производства за турн — сравниваем, сколько должно
производиться по расчётам, с тем, что записано в атрибутах соты» — то есть
`GP`/`MP` версии не являются частью основного игрового цикла, а
служебные патч/аудит‑скрипты (согласно `docs/game-engine.md`, `GP`/`MP` —
параллельные версии одной и той же служебной подсистемы для разных версий
мира; для основного цикла актуальны инкрементальные `plus`/`minus` из §7.1,
`GP`/`MP` лишь ловят рассинхрон).

## 8. Автоматическая деактивация при нехватке энергии

Источник: `ds/classes/ME/ME_DeactivateBuildingsForUpkeep.class.php` (105
строк, прочитан полностью). Вызывается извне (вероятно, из общего пересчёта
хода `ME_TurnCalc`, не входит в список файлов задания) с параметрами
`$needEnergy`, `$haveEnergy`, `$turns`; гасит здания по одному, начиная с
наименее приоритетных, пока дефицит не покрыт:

```php
// ds/classes/ME/ME_DeactivateBuildingsForUpkeep.class.php:37-83
SELECT ab.id_attachedbuilding, ab.id_building, bha.value AS upkeep,
       COALESCE(bdp.priority, 10000000) AS priority,
       COALESCE(SUM(бонус артефактов здания на STASIS_ENERGY), 0) AS bonus
FROM ds_attachedbuilding ab
  LEFT JOIN ds_buildingdeactivatepriority bdp ON (...)
  JOIN ds_attachedbuildinghasattribute abha ON (... abha.id_attribute = 42 /*CURRENT_FLAG=>DEACTIVEABLE*/)
  JOIN ds_buildinghasattribute bha ON (... bha.id_attribute = 7 /*TURN_UPKEEP=>STASIS_ENERGY*/)
WHERE ab.id_comb = :comb AND ab.active = true
ORDER BY priority, bonus  -- меньший priority гасится первым; при равном приоритете — меньший артефактный бонус экономии энергии гасится первым

$diff = needEnergy - haveEnergy;
foreach (сортированный список) {
    $diff -= upkeep * turns;
    добавить в список на деактивацию;
    if ($diff <= 0) break;
}
deactivate(список);  // тот же метод из UA_DeactivateBuilding, см. §6.2 — МГНОВЕННО, без событий
```

Только здания с инстанс‑флагом `CURRENT_FLAG/DEACTIVEABLE`
(`id_attribute=42` — жёстко закодированный числовой ID атрибута прямо в
SQL, см. «магические числа» ниже) и потреблением `STASIS_ENERGY`
(`id_attribute=7`) рассматриваются. Приоритет — из
`ds_buildingdeactivatepriority` (чем меньше число, тем раньше гасится;
здания без строки в этой таблице получают приоритет `10000000`, то есть
гасятся в последнюю очередь). Деактивация здесь **мгновенная** (тот же
`deactivate()`, что и «мгновенный путь» в §6.2, без создания события DEACTIVATE_BUILDING).

## 9. Снос здания — `UA_DropBuilding`

Источник: `ds/classes/UA/UA_DropBuilding.class.php` (247 строк). Требует
инстанс‑флаг `CURRENT_FLAG/DESTROYABLE` (`id_attribute = 43`, захардкожен в
SQL‑комментарии: `-- 'CURRENT_FLAG' => 'DESTROYABLE'`,
`ds/classes/UA/UA_DropBuilding.class.php:50`) и мировую настройку
`CAN_DROP_BUILDINGS` (бинарный рубильник, если выключен — снос запрещён
целиком для мира). Действия:

1. Логирует удаление (`SU_LogDeleteCombObjects`).
2. Если здание активно — снимает бонусы вставленных артефактов
   (`AddArtefactAttributes(..., 'minus')`).
3. Артефакты **не пропадают**: переносятся из здания в общее хранилище
   соты (`ds_combhasartefact`), затем удаляются из
   `ds_artefactattachedbuilding`.
4. Каскадно деактивирует зависимые постройки через тот же
   `getBuildingToDeact`/`deactivate` (с теми же оговорками из §6.2 —
   рекурсивный поиск зависимостей фактически не работает).
5. Трогает событие в мусор, удаляет строки `ds_attachedbuildinghasattribute`
   и `ds_attachedbuilding`.
6. Особый случай: если снесённое здание — `id_building=91`
   (`PROTECTIVE_FIELD_GENERATOR`, захардкоженный ID,
   `ds/classes/UA/UA_DropBuilding.class.php:204`) — защитное поле соты
   сбрасывается на 0%.
7. Если здание было активно — атрибуты снимаются с соты (`minus`, ещё раз —
   через `ME_AddBuildingAttributesToComb::AddAttributes`, отдельно от п.2
   выше, который снимал только артефакты).
8. Рейтинг соты и игрока уменьшаются на `building_score` снесённого здания
   (`deltaCombScore(-score)`, `deltaBuildingRanking(-score)`).

**Возврата ресурсов при сносе нет вообще** — в отличие от отмены постройки
(§4) и отмены апгрейда (§5.5), снос уже готового здания ничего не
компенсирует.

## 10. Панели интерфейса (что видит игрок)

### 10.1. `PN_BuildingInfo` — главная вкладка окна здания

Источник: `ds/classes/PN/PN_BuildingInfo.class.php` (793 строки, прочитан
полностью), класс `extends UA_GetBuildMenu`. Формирует XML `<getbuildinfo>`
со всей информацией для UI: текущее состояние/прогресс (`un`/`ia`/`up` —
проценты постройки/(де)активации/апгрейда), доступные действия
(`destr`/`deact`/`activate`/`upgrade`), тип грунта ячейки, потребление
(`<tu>`), производство с учётом бонусов артефактов (`<tp>`, форматируется
как `"значение+бонус"` в URL‑кодировке), список допустимых
`ячейка×грунт` (`<bo>`), и данные апгрейда (`<upgradedata>`) — стоимости и
требуемые/текущие уровни наук, где стоимости и время **уже поделены на
`UPGRADE_COSTS_DIVISOR`** для показа игроку (`:447-459`), то есть панель
показывает игроку именно ту сумму, которая будет реально списана по
формуле §5.2.

Также содержит логику **платной моментальной достройки/докрутки апгрейда**
(«Услуга завершения строительства», `MSG id=1`/`desc=FAST_BUILD`) —
стоимость = `MU_EntityMoneySumm` для `PS_BUY_TRAINING_COMPLETION` ×
оставшееся число турнов (`leftTime`), недоступно для `id_building=18`
(`GONDOLA_TRANSPORTER`, захардкожен, `:697`) и для самого апгрейда (только
постройка). Это функциональность донат‑системы `MU_*`, не относится к
базовой формуле стройки.

### 10.2. `PN_BuildingArtefact` / `PN_BuildingArtefact2` — вкладки артефактов

Источник: `ds/classes/PN/PN_BuildingArtefact.class.php` (299 строк) и
`PN_BuildingArtefact2.class.php` (276 строк) — **два практически
идентичных** класса (совпадают на 90% дословно), различаются только:

| | `PN_BuildingArtefact` (`n=1`) | `PN_BuildingArtefact2` (`n=2`) |
|---|---|---|
| Число слотов | `MAX_AMOUNT/ARTEFACT_SLOTS` здания (`ds_buildinghasattribute`) | `DE_WorldSettings::get('OFFENSIVE_ARTEFACTS_SLOTS')` (мировая константа, не завязана на конкретное здание) |
| Фильтр вставленных артефактов | `ds_artefactattachedbuilding.collection = 1` | `ds_artefactattachedbuilding.collection = 2` |

То есть у здания де‑факто два независимых «слота артефактов» —
«защитная» коллекция (`collection=1`, лимит зависит от здания) и
«наступательная» (`collection=2`, единый лимит на весь мир) — вторая
видна/доступна только если в мире включена настройка
`ALLOW_OFFENSIVE_ARTEFACTS` (см. `UA_Building::getBuildingTabs`,
`ds/classes/UA/UA_Building.class.php:1094-1096`, вкладка `id_specialfunction=41`
скрывается для здания `id_building=26`/«Военная база», если модификатор
выключен — оба числа захардкожены).

### 10.3. `CR_CombView` — кеш вида соты

Источник: `ds/classes/CR/CR_CombView.class.php` (307 строк). Не содержит
игровых формул — чистый слой кеширования: `createView($cellTypeID)`
формирует XML списка зданий/ячеек **отдельно по каждой части соты**
(`HOME`/`CENTRAL`/`NEUTRAL`, через `id_celltype`), кладёт в `DS_Cache` на 1
час (`3600` сек, `:177,194`), и обязательно инвалидируется
(`resetBuildings($cellTypeID)`/`resetCells($cellTypeID)`) при любом
изменении здания — этот вызов встречается в буквально каждом
`UA_*`/`EH_*`‑обработчике зданий, разобранном выше. Формирование самих
данных вида делегируется `ME_CombViewFormBuildings`/`ME_CombViewFormCells`
(не входят в список файлов задания).

## 11. Различия между зданиями по расам

`DE_BuildingByRace` (`ds/classes/DE/DE_BuildingByRace.class.php`, 101
строка) — обёртка над таблицей `ds_buildingcanbuildbyrace`, ключ записи —
составной `raceID_buildingID` (например, `"1_15"`). Единственное, что
гарантированно хранится в этой записи и подтверждено чтением кода —
**локализованное имя** здания для конкретной пары раса×здание
(`id_localizedname`, метод `getLocalizedName()`); объект‑обёртка не хранит
и не отдаёт никаких иных отличающихся по расе атрибутов — сами
производственные атрибуты (`ds_buildinghasattribute`) привязаны к
`id_building`, то есть **к базовому типу**, общему для всех рас, которым он
разрешён (`DE_Building::getAttributes()` не принимает `raceID`).

Однако для военных построек (заводы/тренировочные лагеря) в БД, по всей
видимости, заведены **отдельные `id_building` на каждую расу** — тот же
"логический" тип здания под разными числовыми ID и разными
`buildingname`. Прямое подтверждение — метод
`DS_Building::ConvertTrainBuildings($combID, $idRace)`
(`ds/classes/DS/DS_Building.class.php:345-413`), который при смене расы
соты **перепрописывает** `id_building` существующих военных зданий на
эквивалент новой расы:

```php
// ds/classes/DS/DS_Building.class.php:350-392 (комментарии — из оригинального кода)
$bild = array(
    1 => array( 70=>67, 73=>67, 71=>68, 74=>68, 72=>69, 75=>69,
                44=>43, // Огромный нано-кристаллизатор(псолао) => Большой нано-кристаллизатор
                49=>17, // Ферма клонов => Ферма
                86=>40, // Огромная кузница кадериума (линсу) => Огромная кузница кадериума (вор)
                87=>43, 90=>42, 15=>38 ), // Город => Деревня
    2 => array( 67=>70, 73=>70, 68=>71, 74=>71, 69=>72, 75=>72,
                40=>86, 44=>87 ),
    3 => array( 67=>73, 70=>73, 68=>74, 71=>74, 69=>75, 72=>75,
                40=>41, 49=>17, 86=>41, 87=>44, 90=>42, 15=>9, 38=>9 ),
);
```

Это единственное подтверждённое кодом место с конкретными числовыми ID
расовых вариантов зданий: тройки `{67,70,73}`, `{68,71,74}`, `{69,72,75}`
— один и тот же «слот» военного здания в трёх расовых обличиях (судя по
названиям в справочнике §12 — вероятно `TRAINING_CAMP`/`TRAINING_CAMP2V`/
`TRAINING_CAMP2L`/`TRAINING_CAMP2P` и аналогичные `LIGHT_/HEAVY_WEAPON_MANUFACTURE`
пары, но точное сопоставление конкретных ID из массива с конкретными
`buildingname` из ranking‑справочника **не установлено** — CSV не содержит
`id_building`, только имя/очки/локализацию, см. открытые вопросы).

Комментарии в этом же массиве прямо называют расовые суффиксы —
«(вор)», «(линсу)», «(псолао)» — что соответствует топонимам стартовых
колец мира, встреченным в `TMP_ControllClearComplex.class.php:121`:
`array(1=>'VORANIA', 2=>'LIENSU', 3=>'PSOLAO', 4=>'MELSION')`. Отсюда с
высокой вероятностью (но не 100% подтверждено — не встречена сама таблица
`ds_race` с явным маппингом `id_race → racename`) следует, что три игровые
расы физически привязаны к трём стартовым кольцам/континентам —
**Vorania (раса 1), Liensu (раса 2 или 3), Psolao (раса 3 или 2)**, а
`Melsion` — четвёртое кольцо, вероятно `RING_COLONIZE` (кольцо
колонизации, упомянутое в `WorldUtils::setBeginningBuildings`, §13) или
нейтральная территория. Соответствие `id_race ↔ ringname` из массива
`ConvertTrainBuildings` (индексы 1/2/3 массива) совпадает по порядку с
`$rings` (`1=VORANIA, 2=LIENSU, 3=PSOLAO`), что и даёт указанное выше
предположение — но однозначного текстового доказательства «раса 1 =
Vorania» в прочитанном коде нет.

Также подтверждено (из первоначального листинга файлов проекта) наличие
трёх *разных спрайтов* одного логического здания «жилой дом» —
`img/support/builds/{bio,psi,tech}_residential_building.gif` — что
указывает на игровые названия рас/фракций **BIO / PSI / TECH**, но прямого
связывания этих трёх идентификаторов с `id_race` 1/2/3 в прочитанном PHP‑коде
не найдено (см. открытые вопросы).

## 12. Полный список зданий (по данным экспорта рейтинга)

Источник: `Admin/controls/GameSettings/Localization/Export_CVS/data/ranking_building.csv`
(105 строк данных, прочитан полностью). Формат файла: `buildingname;
building_score; localname;` — то есть это прямой дамп содержимого столбца
`ds_building.building_score` вместе с `buildingname` и локализованным
именем, используемый экспортным скриптом статистики/рейтинга
(`LG_CVSRankingExport`‑подобная подсистема — сам класс не читался, но
назначение файла соответствует). Это **самый полный список машинных имён
зданий**, найденный во всём просмотренном коде — именно `buildingname`
проверяется во всех `if ($buildingName == '...')` конструкциях §4.5/§6.3/§9.
`id_building` в этом файле не хранится (только имя, очки, локаль) — числовые
ID из §11/§4.5/§9 сопоставлены с этим списком только по логике названий,
не подтверждены построчно.

| buildingname | building_score | Название (рус.) |
|---|---:|---|
| `CENTRAL_GATE_WRECK` | 0 | Обломки центральных ворот |
| `CENTRAL_GATE` | 200 | Центральные ворота |
| `CLONE_FARM` | 20 | Ферма клонов |
| `FARM` | 15 | Ферма |
| `PLANTATION` | 4 | Плантация |
| `HYDRO_FARM` | 70 | Гидроферма |
| `ENERGY_COLLECTOR` | 20 | Компактный сборщик энергии |
| `SOURCE` | 35 | Источник |
| `WELL_LADLER` | 25 | Колодец |
| `SAIL_FOREST` | 15 | Лес Парусов |
| `SAIL_TRAP` | 7 | Парус‑уловитель |
| `KADERIUM_FORGE` | 20 | Кузница кадериума |
| `LARGE_KADERIUM_FORGE` | 40 | Большая кузница кадериума |
| `HUGE_KADERIUM_FORGE` | 80 | Огромная кузница кадериума (раса «вор») |
| `HUGE_KADERIUM_FORGE_LIENSU` | 80 | Огромная кузница кадериума (раса «линсу») |
| `NANO_CRYSTALLIZER` | 20 | Нано‑кристаллизатор |
| `LARGE_NANO_CRYSTALLIZER` | 40 | Большой нано‑кристаллизатор |
| `HUGE_NANO_CRYSTALLIZER` | 80 | Огромный нано‑кристаллизатор (раса «вор») |
| `HUGE_NANO_CRYSTALLIZER_LIENSU` | 80 | Огромный нано‑кристаллизатор (раса «линсу») |
| `PRODIUM_COLLECTOR` | 40 | Коллектор продиума |
| `LARGE_PRODIUM_COLLECTOR` | 70 | Большой сборщик продиума |
| `HUGE_PRODIUM_COLLECTOR` | 100 | Огромный продиумный коллектор |
| `CONSTRUCTION_FACTORY` | 0 | Строительная фабрика |
| `SHELTER` | 0 | Убежище (задаёт начальный `STORAGE_FACTOR`, см. §13) |
| `GONDOLA_TRANSPORTER` | 170 | Гондола‑транспортер |
| `KADERIUM_PRESS` | 150 | Кадериумный пресс |
| `MESH_BOILER` | 150 | Нагреватель материи / Преобразователь материи / Призма (расовые варианты одного имени) |
| `MIND_FACTORY` | 150 | Фабрика микрочипов / Протоплазменная печь / Собор |
| `CRYSTAL_WEAVER` | 150 | Конструктор кристаллов |
| `RING_GATE` | 100 | Межкольцевые ворота |
| `ALLIANCE_CONNECTOR` | 75 | Посольство (см. §4.5) |
| `RUSHERS_TEMPLE` | 250 | Храм штурмовиков |
| `CLOUD_ENDERS_TEMPLE` | 250 | Храм созерцателей облаков |
| `COLONIZATION_CENTER` | 500 | Колонизационный центр (см. §4.5) |
| `SECRET_SERVICE_CENTER` | 150 | Управление разведки |
| `SPYING_CENTER` | 120 | Центр шпионажа |
| `TUNNEL_RIVER_SCANNER` | 73 | Сканер туннельной реки |
| `RESIDENTIAL_BUILDING` | 6 | Жилой дом |
| `VILLAGE` | 15 | Деревня |
| `CITY` | 40 | Город |
| `INTERRING_TRADE_CONNECTOR` | 200 | Межкольцевой коннектор торговли |
| `RING_TRADE_CONNECTOR` | 30 | Внутрикольцевой коннектор торговли |
| `LOCAL_TRADE_CONNECTOR` | 25 | Локальный коннектор торговли |
| `SMALL_STASIS_SILO` / `MEDIUM_STASIS_SILO` | 30 / 35 | Хранилище энергии (малое/среднее) |
| `SMALL_FOOD_SILO` / `MEDIUM_FOOD_SILO` | 30 / 35 | Хранилище еды |
| `SMALL_KADERIUM_SILO` / `MEDIUM_KADERIUM_SILO` | 30 / 35 | Хранилище кадериума |
| `SMALL_NANO_SILO` / `MEDIUM_NANO_SILO` | 30 / 35 | Хранилище нано‑кристаллов |
| `SMALL_PRODIUM_SILO` / `MEDIUM_PRODIUM_SILO` | 30 / 35 | Хранилище продиума |
| `SMALL_*_DUMP` / `MEDIUM_*_DUMP` (`PRODIUM`,`NANO`,`KADERIUM`,`FOOD`,`STASIS`) | 30 / 35 | «Склады» — по названию отдельная линейка от `*_SILO`, той же вместимости — вероятно альтернативная (расовая?) визуальная линейка тех же складов; отличие от `*_SILO` по коду не установлено |
| `ARCHEOLOGICAL_INSTITUTE` | 70 | Археологический институт (см. §4.5; вероятно `id_building=12`) |
| `ARCHEOLOGICAL_OUTPOST` | 25 | Лагерь археологов (вероятно `id_building=47`) |
| `RESEARCH_CENTER` | 15 | Научный центр (см. §4.5; вероятно `id_building=29`) |
| `ARTEFACT_TOWER` | 50 (база) | Башня артефактов — **отдельные строки `ARTEFACT_TOWER\|N`** (N=2..21) с очками от 100 до 1050 — судя по всему, это градация очков **по числу вставленных артефактов**, а не отдельные типы зданий (единственное место в CSV с таким составным ключом `имя|число`) |
| `TERRAMORPH_TOWER` | 0 | Терраформер |
| `VOCATION_BUILD` | 0 | Дом отдыха |
| `SHIELD_BUILD` | 0 | Силовой щит |
| `PROTECTIVE_FIELD_GENERATOR` | 200 | Генератор Защитного Поля (см. §4.5; вероятно `id_building=91`) |
| `MILITARY_BASE` | 80 | Военная база (вероятно `id_building=26`) |
| `TRAINING_CAMP` | 80 | Тренировочный лагерь / Зона мутантов / Площадка для медитации (расовые варианты) |
| `TRAINING_CAMP2V` / `TRAINING_CAMP2L` / `TRAINING_CAMP2P` | 240 | Улучшенный тренировочный лагерь — 3 расовых варианта (V/L/P — вероятно Vorania/Liensu/Psolao, см. §11) |
| `LIGHT_WEAPON_MANUFACTURE` | 100 | Лёгкая фабрика вооружений / Лаборатория монстров / Пилон духа |
| `LIGHT_WEAPON_MANUFACTURE2V` / `2L` / `2P` | 300 | Улучшенные версии, по расе |
| `HEAVY_WEAPON_MANUFACTURE` | 140 | Тяжёлая фабрика вооружений / Инкубатор / Монолит |
| `HEAVY_WEAPON_MANUFACTURE2V` / `2L` / `2P` | 350 | Улучшенные версии, по расе |

Всего в исходном CSV — 105 строк (с учётом 20 строк `ARTEFACT_TOWER|N`
как единого «типа» с вариацией по числу артефактов — итого различимых
машинных имён зданий **86**). Файл полностью, без сокращений, использован
при составлении таблицы выше.

Про открытый вопрос строкой выше (различие `*_SILO` vs `*_DUMP`) — портал
даёт частичную зацепку, см. §17.2.

## 13. Стартовые здания соты — `WorldUtils::setBeginningBuildings()`

Источник: `ds/classes/WorldUtils.class.php:762-1018` (прочитан полностью),
вызывается из `applyTemplate($id_comb)` (`:206-257`) для каждого из 3 типов
ячеек (`id_celltype` 1..3) при разметке новой соты по случайно выбранному
шаблону `DS_CellTemplate`.

Логика:
1. Определяет, находится ли сота на кольце `RING_COLONIZE` (по
   `ds_ring.ringname`).
2. Берёт список `(номер ячейки, id_building)` из `DS_CellTemplateElement`
   для выбранного шаблона и типа ячейки.
3. Для каждой позиции — резолвит `id_cell` по номеру и вставляет строку в
   `DS_AttachedBuilding` сразу как **готовую** (`active=true,
   underconstruction=false, starttime=NULL, endtime=NULL`) — то есть
   стартовые здания **не проходят обычный цикл постройки**.
4. **Особый случай колоний**: если сота на `RING_COLONIZE` и шаблонное
   здание — `CENTRAL_GATE_WRECK`, вместо него подставляется уже рабочее
   `CENTRAL_GATE` (по имени, резолвится через `DS_Building`, не
   захардкоженным ID) — то есть **колонии стартуют с открытыми воротами**,
   обычные соты — с руинами, которые нужно апгрейдить (§5.4, п.6).
5. Для НЕ‑колонии (`else`‑ветка, `:862-1013`) дополнительно:
   - Атрибуты здания (`TURN_PRODUCTION`, `TURN_UPKEEP`, `MAX_AMOUNT`)
     прибавляются к `ds_combhasresource` **напрямую одним UPDATE**
     (`:890-914`), а не через `ME_AddBuildingAttributesToComb` — отдельная,
     самостоятельная реализация той же логики специально для массовой
     инициализации.
   - Инстанс‑флаги `DESTROYABLE`/`DEACTIVEABLE` проставляются по атрибуту
     `BEGINNING_FLAG` (не `PERMANENT_FLAG`, как при обычной постройке!) —
     то есть у стартовых зданий снос/деактивируемость управляются
     **отдельной парой атрибутов**, независимой от той, что действует при
     обычной постройке/апгрейде (§2.3).
   - **Если здание — `SHELTER`**: соте сразу проставляется начальный
     `STORAGE_FACTOR`/`STORAGE_FACTOR_ABSOLUT` копированием значений из
     `ds_buildinghasattribute` этого здания в `ds_combhasresource`
     (`:993-1013`) — то есть базовая вместимость хранилищ ресурсов соты
     задаётся именно зданием `SHELTER` с самого начала игры.

Здания, поставленные при колонизации (внутри `if` для `CENTRAL_GATE_WRECK`
→ `CENTRAL_GATE`), **не** получают шаг 5 (прибавление атрибутов/флагов) —
в этой ветке кода после вставки строки сразу присваивается `$mab` и цикл
идёт на следующую позицию, минуя весь блок начисления атрибутов
соте/флагов/`SHELTER`‑инициализации, который есть только в `else`‑ветке.
Это, скорее всего, означает, что при колонизации центральные ворота
считаются готовыми зданием только формально (визуально/структурно), а их
вклад в производство/потребление соты насчитывается отдельным путём
(не входит в разобранные файлы) — зафиксировано как открытый вопрос ниже.

## 14. Наследие: `building_class.class.php` / `buildings.class.php`

Оба файла (прочитаны полностью) — рудимент раннего немецкого этапа проекта
(см. `docs/game-engine.md`, раздел про немецкие комментарии), не
подключаются актуальным игровым кодом (ни один из разобранных выше файлов
их не использует). Полезны как **исторический артефакт исходной модели
данных**:

- `building_class.class.php` определяет класс `building` с полями
  `b_kadcosts`, `b_nanocosts`, `b_stasiscosts`, `b_prodcosts`,
  `b_foodcosts`, `b_workercosts`, `b_production`/`b_productionRes`,
  `b_upkeep`/`b_upkeepRes`, `b_buildtime` — то есть изначально стоимость
  каждого ресурса была **отдельным полем**, а не гибкой парой
  тип‑атрибут/значение (как в текущей схеме `ds_buildinghasattribute`).
  Содержит устаревшую формулу очков за здание, отличную от нынешнего
  `ds_building.building_score`:

  ```php
  // ds/classes/building_class.class.php:174-176
  function get_b_points(){
      return ($this->b_kadcosts*2 + $this->b_stasiscosts + $this->b_nanocosts*4
              + $this->b_foodcosts + $this->b_prodcosts*10)/10;
  }
  ```

  (кадериум ×2, нано ×4, продиум ×10, стазис‑энергия и еда ×1, сумма /10 —
  **не используется** нигде в актуальном коде, но показывает изначальный
  замысел «веса» ресурсов друг относительно друга).
- `buildings.class.php` — HTML‑страница‑справочник (класс
  `neededAttributes`, безусловно исполняется при подключении файла,
  `$r = new neededAttributes;` в самом конце). Содержит захардкоженный
  `switch` по `id_attribute` (`fillBuilding()`, `:185-206`), из которого
  можно восстановить **исторические** (возможно, уже не актуальные) ID
  атрибутов: `1`=кадериум(costs), `2`=нано(costs), `3`=стазис(costs),
  `4`=еда(costs), `5`=продиум(costs), `6`=рабочие(costs), `7`=потребление
  стазис‑энергии, `8`=время постройки, `9`=производство кадериума,
  `10`=производство нано, `11`=производство еды, `12`=производство
  продиума, `13`=производство стазис‑энергии, `49`=потребление еды. Эти же
  числа (`7`, `42`, `43`) встречаются захардкоженными в *текущем* коде
  (§8, §9) — то есть, несмотря на переход на гибкую схему
  тип/имя/значение, часть новых модулей (`ME_DeactivateBuildingsForUpkeep`,
  `UA_DropBuilding`) всё равно обращается к конкретным `id_attribute`
  напрямую по числу, а не резолвит их по имени — прямая зависимость от
  того, что справочник атрибутов в БД никогда не меняет нумерацию.

## 15. Сводная таблица «магических» числовых ID, захардкоженных в PHP

Критично для ремастера: при переносе на новую БД эти конкретные числа либо
нужно сохранить как есть, либо заменить весь перечисленный код на
резолвинг по имени.

| ID | Таблица/поле | Предполагаемое значение | Где используется |
|---|---|---|---|
| `id_building = 12` | `ds_building` | `ARCHEOLOGICAL_INSTITUTE` | `EH_ConstructBuilding.class.php:53` |
| `id_building = 47` | `ds_building` | `ARCHEOLOGICAL_OUTPOST` | `EH_ConstructBuilding.class.php:54` |
| `id_building = 29` | `ds_building` | `RESEARCH_CENTER` | `EH_ConstructBuilding.class.php:69` |
| `id_building = 91` | `ds_building` | `PROTECTIVE_FIELD_GENERATOR` | `UA_DropBuilding.class.php:204` |
| `id_building = 26` | `ds_building` | «Военная база» (`MILITARY_BASE`) | `UA_Building.class.php:1094` (getBuildingTabs) |
| `id_building = 18` | `ds_building` | `GONDOLA_TRANSPORTER` | `PN_BuildingInfo.class.php:697` |
| `id_specialfunction = 41` | `ds_specialfunction` | Вкладка наступательных артефактов | `UA_Building.class.php:1096`, `PN_BuildingArtefact2` косвенно |
| `id_attribute = 42` | `ds_attribute` | `CURRENT_FLAG` / `DEACTIVEABLE` | `ME_DeactivateBuildingsForUpkeep.class.php:63` |
| `id_attribute = 43` | `ds_attribute` | `CURRENT_FLAG` / `DESTROYABLE` | `UA_DropBuilding.class.php:50` |
| `id_attribute = 7` | `ds_attribute` | `TURN_UPKEEP` / `STASIS_ENERGY` | `ME_DeactivateBuildingsForUpkeep.class.php:65` |
| `id_building` тройки `{67,70,73}`, `{68,71,74}`, `{69,72,75}`, пары `{40,86}`, `{44,87}`, `{15,38,9}`, `{49,17}`, `{90,42}` | `ds_building` | Расовые варианты военных/жилых зданий (§11) | `DS_Building.class.php:350-392` |

## 16. Открытые вопросы (нельзя восстановить только по PHP‑коду)

1. **Точные числовые значения мировых настроек** — весь код опирается на
   `DE_WorldSettings::get('KEY')` для десятков ключей: `TURN_TIME`,
   `UPGRADE_COSTS_DIVISOR`, `REACTIVATION_DIVISOR`,
   `SIMILTANEOUS_BUILDS_ADDON`, `MIN_RES_LOST_ON_CANCEL_BUILDING`,
   `MIN_RES_LOST_ON_CANCEL_UNIT`, `BUILDING_CAN_BUIL_BY_MONEY`,
   `COUNT_ADD_ARCHEOLOGISTS_CONSTRUCT_INSTITUT`,
   `COUNT_ADD_CSIENTISTS_CONSTRUCT_CENTER`, `NEWB_DEFEND_AFTER_GATE_OPEN`,
   `MAX_TIME_RANDOM_DEFEND_TIME`, `CAN_DROP_BUILDINGS`,
   `ALLOW_OFFENSIVE_ARTEFACTS`, `OFFENSIVE_ARTEFACTS_SLOTS`. Сами значения
   хранятся в таблице настроек мира в БД (не найдена в просмотренном PHP‑
   дереве — вероятно, `ds_worldsettings`/аналог, заполняемая
   `SyncTables`, см. `docs/game-engine.md`), недоступной без живой БД.
   **Без этих чисел нельзя воспроизвести абсолютные тайминги/дели­тели**,
   только сами формулы (что и приведено в этом документе).
2. **Все конкретные значения `ds_buildinghasattribute`** (стоимости,
   производство, потребление, время постройки по каждому из ~86 типов
   зданий) — данные лежат в БД, не в PHP. §12 даёт `building_score`
   (итоговую метрику), но не первичные costs/production/upkeep. Раздел
   14 восстанавливает только *исторический* (возможно устаревший) вес
   ресурсов через формулу `get_b_points`, не сами числа.
3. **Тексты хранимых процедур PostgreSQL**, вызываемых из PHP как чёрные
   ящики: `isUniqueTrue_2(id_comb, id_building)`,
   `isNeedsBuildingPresent_2(id_comb, id_building)`,
   `isResourcesEnoughToUpgrade(id_world, id_comb, id_building)`,
   `isScienceEnough(id_userinstance, id_building)`,
   `getProgress(id_eventqueue)` — их поведение восстановлено только по
   именам/сигнатурам и способу использования, точная реализация
   (например — «уникальность» проверяется в рамках всей цепочки апгрейдов
   или только текущего уровня?) не подтверждена.
4. **Поле `busy` в `ds_attachedbuilding`** — читается в нескольких местах
   (`PN_BuildingInfo`, `UA_DeactivateBuilding` в закомментированном блоке),
   но ни в одном из прочитанных файлов не найдено место, где оно
   устанавливается в `true`. Гипотеза «здание занято подпроцессом
   (тренировкой юнитов)» не подтверждена чтением кода.
5. **`ds_buildingtype.type`** — какие конкретно категории зданий существуют
   (жилое/военное/производственное/и т.д.) — таблица упомянута только как
   `LEFT JOIN` в `DS_Building::GetByID`, ни одного конкретного значения
   `type` в прочитанных файлах не встретилось.
6. **Точное соответствие `id_race` ↔ название расы/фракции.** Косвенные
   улики (см. §11): комментарии «(вор)/(линсу)/(псолао)» в
   `DS_Building::ConvertTrainBuildings`, массив колец `VORANIA/LIENSU/
   PSOLAO/MELSION` в `TMP_ControllClearComplex`, спрайты
   `bio_/psi_/tech_residential_building.gif`. Ни разу не встречена
   исходная таблица `ds_race` с явным перечислением
   `id_race → racename`, поэтому итоговое сопоставление (1=?, 2=?, 3=?)
   — предположение, не факт.
7. **Что именно происходит с производством/потреблением `CENTRAL_GATE`
   при заселении соты через колонизацию** (§13, п. про `RING_COLONIZE`) —
   в этой ветке `setBeginningBuildings` атрибуты соте не прибавляются
   (в отличие от обычной ветки), но нигде в прочитанных файлах не найден
   код, который бы делал это отдельно для колоний — возможно, это
   происходит в другом, не входящем в задание участке `WorldUtils`
   (например, в `addNewInstance`) или через отдельный вызов
   `ME_AddBuildingAttributesToComb` где‑то в коде колонизации
   (`EH_Colonize`, не читался).
8. **Реализация платной постройки за `SILVER_CREDIT`/`GOLD_CREDIT`**
   выглядит незавершённой в исходном коде (см. §2.3) — комментарий
   `//Здесь будет снятие денег` без реализации, и условие в
   `isResourcesEnough` на эти два ресурса физически не может сработать
   (эти имена отсутствуют в переборе `$allBuildResources`). Не установлено,
   является ли это осознанно выключенной фичей (флаг
   `BUILDING_CAN_BUIL_BY_MONEY` действительно всегда `0` в проде) или
   недоделанным куском кода, который где‑то доделан в необследованных
   файлах (`MU_*`).
9. **Соответствие числовых `id_building` из `DS_Building::ConvertTrainBuildings`
   конкретным именам `buildingname` из справочника §12** — установлено
   только по совпадению паттернов названий (`TRAINING_CAMP` /
   `TRAINING_CAMP2V/2L/2P`, `LIGHT_/HEAVY_WEAPON_MANUFACTURE` с их
   `2V/2L/2P`‑вариантами), не подтверждено прямым запросом к БД.
10. **Логика деактивации по каскаду зависимостей** (в
    `UA_DeactivateBuilding::getBuildingToDeact`) присутствует в коде только
    как закомментированный черновик — неизвестно, работала ли она в более
    ранней версии игры и почему отключена (баг, производительность,
    осознанный отказ от фичи) — не установлено.
11. **Формула `deltaCombScore` при апгрейде, начисляющая полный
    `building_score` нового здания вместо дельты** (§5.4, п.5) — не
    установлено, баг это оригинала или осознанное поведение (в отличие от
    рейтинга игрока, который считается корректной дельтой в той же
    функции). Для ремастера решение (воспроизводить как есть или
    исправлять) нужно явно согласовать с командой/владельцем продукта.

## 17. Портал: player-facing описания уникальных зданий и рекомендации по застройке

> Источники (официальные справочные страницы сайта — player-facing текст,
> не код и не дамп БД): `https://destinysphere.ru/?p=buildings_voraner`,
> `https://destinysphere.ru/?p=buildings_liensu`,
> `https://destinysphere.ru/?p=buildings_psolao` (каталоги зданий по расе —
> формулировки описаний уникальных зданий на всех трёх страницах почти
> дословно совпадают, различаются только расовые названия/спрайты) и
> `ds/help/?name=INFO_GAME_MAIN` (главы 1.3, 1.8, 2.2-2.4 официального
> внутриигрового мануала — консолидированная версия того же текста). Раздел
> **дополняет**, а не заменяет §1-§16: где ниже повторяется факт, уже
> подтверждённый кодом (например, конкретный `building_score` из §12), он
> не дублируется — приводится то, чего код не даёт: **для чего конкретное
> здание нужно игроку**. Расхождения между кодом/FAQ и текстом портала
> отмечены явно, а не подменяют друг друга.

### 17.1 Что даёт каждое уникальное/специальное здание (кроме уже разобранных в §4.5/§6.3)

| Здание | Расовые варианты | Player-facing описание (портал) |
|---|---|---|
| Кадериумный пресс | одно и то же для всех трёх рас | Открывает: починку Центральных Ворот, постройку Большой кузницы кадериума, постройку Колодца |
| Конструктор кристаллов | одно и то же | Открывает: Большой нанокристаллизатор, Коллектор продиума, Гондолу-транспортёр, Внутрикольцевой Коннектор Торговли |
| Нагреватель материи / Преобразователь материи / Призма | Воранеры / Лиенсу / Псолао | Открывает: Огромную кузницу кадериума, Гидроферму, Межкольцевые Ворота, Терраформер, Храмы |
| Фабрика микрочипов / Протоплазменная печь / Собор | Воранеры / Лиенсу / Псолао | Открывает: Большой сборщик продиума, Межкольцевой Коннектор Торговли, тяжёлую военную постройку (Тяжёлая фабрика вооружения / Инкубатор / Монолит соответственно). Портал прямо указывает: строить «в центре на земле» (`id_celltype=CENTRAL`, грунт «земля») |
| Гондола-транспортёр | одно и то же | Без неё невозможно перемещение юнитов на вражеские/союзные соты (атака, защита союзника), торговля с другими игроками, трансфер ресурсов/артефактов/юнитов на колонии. Портал советует строить её сразу после открытия ворот |
| Межкольцевые Ворота (МВ) | одно и то же | Строятся в центральной соте. Дают: полёты на другие кольца (в мирах «все против всех»); в связке с МКТ — межкольцевую торговлю; в связке с КЦ — колонизацию. **В мирах «раса против расы» МВ служат только для колонизации** — межкольцевых перелётов там нет (расхождение по типу мира, не встречалось в §1-§16 как код-подтверждённый факт) |
| Колонизационный центр (КЦ) | одно и то же | Открывает колонизацию (детали — §5.4-5.5 в `mechanics-races-world-ranking.md`) и трансфер ресурсов/юнитов/артефактов на колонии; для трансфера **с** колонии на домашнюю соту КЦ нужно построить ещё раз, уже на самой колонии |
| Посольство | одно и то же | Строится в нейтральной соте. Даёт создание/вступление в альянс (по приглашению, по заявке на форуме или по рекомендации соседа), заключение перемирий |
| Военная база | одно и то же | Управление войсками, журнал сражений, хранилище 10 военных артефактов (действуют только при обороне **на домашней соте**). Все юниты с военных заводов автоматически попадают во внутреннюю армию военной базы |
| Башня артефактов | одно и то же | Базово вмещает 20 артефактов (градация очков `ARTEFACT_TOWER\|N` в §12, вероятно, соответствует расширению этого лимита); артефакты повышают рейтинг и объёмы производства |
| Убежище | одно и то же | Показывает баланс производства/расхода по всем ресурсам (премиум-игрокам — детальнее); **защищает от разграбления продукцию последних 2 часов** — при набеге противник не может унести то, что произведено позже этого окна |
| Локальный / Внутрикольцевой / Межкольцевой Коннекторы Торговли | одно и то же | ЛКТ — торговля с соседями по комплексу, удобно на старте (обмен, подарки, «спрятать» ресурсы завышенной ценой перед набегом). ВКТ — торговля внутри кольца; портал прямо называет его **«довольно бесполезным зданием, не рекомендуется к строительству»** после появления МКТ. МКТ — торговля с двумя другими кольцами + альянсовый аукцион, покупка ресурсов/юнитов/артефактов других рас (в мирах «раса против расы» — только ресурсы и артефакты, юниты чужой расы не купить) |
| Терраформер | одно и то же | Меняет тип грунта ячейки: постройка терраформера — 4 часа, активация (сама перестройка) — 20 часов. Разрешённые переходы: вода↔пустыня, трава→{вода, пустыня, земля}, земля→{вода, пустыня, трава}. После завершения здание исчезает и снова доступно к постройке |
| Дом отдыха | одно и то же | Одноразовое здание, доступное только после оплаты в биллинге. Включает режим длительного отпуска (минимум 3 суток, максимум 1 год); все процессы на соте замораживаются. Исчезает при отмене отпуска |
| Силовой щит | одно и то же | Краткосрочная заморозка соты. **Числовой диапазон расходится между источниками**: страницы каталога зданий по расе называют «от 12 часов до 3 суток», а консолидированный мануал `INFO_GAME_MAIN` — «от 6 часов до 12 суток, зависит от настроек мира» (то есть диапазон явно варьируется по миру, конкретное число в каталоге — лишь один из примеров, не универсальная константа). Стоимость в статической энергии зависит от рейтинга игрока, списывается сразу за весь срок. Повторная активация — после перезарядки (Х−2 хода, где Х — срок заморозки) |
| Генератор Защитного Поля | одно и то же | Строится в домашней соте **и только на захватываемой колонии** (см. §5.5 `mechanics-races-world-ranking.md`), в одном экземпляре. Защищает от мгновенного захвата — при деактивации самого здания защита колонии всегда равна 0% |
| Храмы (Созерцателей / Штурмовиков) | одно и то же для всех рас | Помимо выбора религии (числа бонусов — см. §4 `mechanics-races-world-ranking.md`, дословно совпадают с порталом), мануал `INFO_GAME_MAIN` добавляет факт, не встречавшийся ни в коде, ни в `faq.php`: храмы **также нужны для реинкарнации погибших**. Механика реинкарнации здесь не разбирается — см. `docs/mechanics-reincarnation.md` |
| Центр шпионажа / Управление разведки | одно и то же | Тренировка зондов-шпионов для разведки чужих сот и защиты своей — работает только в мирах, где включён шпионаж |
| Строительная фабрика | одно и то же | «Огромное сооружение, построенное конструкторами кольцевого мира» — даёт возможность вести несколько строек одновременно (источник `MAX_AMOUNT/SIMILTANEOUS_BUILDS`, см. §4.3), попутно производит минимум разных ресурсов |

**Ремонт Центральных Ворот (`CENTRAL_GATE_WRECK` → `CENTRAL_GATE`) — конкретные требования с портала.** Все три расовые страницы дают идентичный текст дословно: нужны научные уровни Кадериум 5, Нанокристаллы 3, Статическая энергия 3, Еда 3, построенный Кадериумный пресс, и ресурсы Кадериум 4000 / Нанокристаллы 1500 / Статическая энергия 4000. Ремонт занимает **72 часа** и прямо называется «строительством» — портал предупреждает: *«должно протекать строительство 2 зданий, т.к апгрейд также считается строительством»*, то есть у соты в момент починки ворот должно быть свободно минимум 2 слота `SIMILTANEOUS_BUILDS` (на живых мирах 2026 года, см. §17.3 ниже, лимит варьируется от 5 до 11 — требование «≥2» выполнимо всегда, без специальной подготовки). Деактивация уже открытых ворот занимает 24 часа.

### 17.2 Застройка центральной и нейтральной соты (дополняет §1.4 `mechanics-races-world-ranking.md`)

Портал уточняет состав поверхности по типам грунта для каждой части соты (`ds/help/?name=INFO_GAME_MAIN`, глава 1.3):

| Часть соты | Типы грунта и количество | Сумма |
|---|---|---:|
| Домашняя (HOME) | земля 92, пустыня 35, трава 45, вода 44 | **216** |
| Центральная (CENTRAL) | земля 16, скалы 12 | **28** |
| Нейтральная (NEUTRAL) | пустыня 217 | **217** |

Суммы по Центральной (28) и Нейтральной (217) точно совпадают с числом
ячеек этих частей соты, установленным по коду в §1.4
`mechanics-races-world-ranking.md`. **Расхождение**: сумма по Домашней соте
(216) на 1 меньше заявленных там же 217 ячеек — портал либо не упоминает
какой-то тип грунта, либо теряет единицу (например, ячейка под стартовым
«Обломки центральных ворот» может не входить в перечисление типов грунта).
Зафиксировано как расхождение источников, не как установленный факт.

Рекомендованный состав застройки **центральной соты** (`INFO_GAME_MAIN`,
глава 2.2): 8 заводов тяжёлых юнитов (по расе — Тяжёлая фабрика вооружения
/ Инкубатор / Монолит), МККТ, Фабрика микрочипов/Протоплазменная
печь/Собор (строго на земле — экономит скалу под другое), Гондола-
транспортёр, Межкольцевые Ворота, Колонизационный центр, Центр шпионажа и
Управление разведки (если в мире включён шпионаж), сборщики продиума на
оставшемся месте.

Рекомендованный состав застройки **нейтральной соты** (глава 2.3): склады
и хранилища (см. ниже), посольство, силовой щит (можно и в домашней, на
песке), лагерь археологов, компактный сборщик энергии, дом отдыха
(платно). Портал советует **не спешить** с застройкой нейтральной соты,
пока есть место в домашней/центральной — сразу стоит поставить только
посольство, силовой щит и по несколько складов/хранилищ на каждый ресурс.
Про лагеря археологов портал даёт иллюстрацию отдачи: «в лагерях строится
всего 50 археологов [на здание], [но] застроив всю пустыню [217 ячеек], вы
получите **10050 археологов**» — что на 50 меньше формального
произведения 217×50=10850; портал явно закладывает часть ячеек под другие
обязательные постройки нейтральной соты (посольство, склады и т.д.), это
не арифметическая ошибка, а фигура речи «почти вся пустыня».

**Частичное закрытие открытого вопроса §12 (различие `*_SILO` vs
`*_DUMP`).** Портал называет ровно два разных по функции здания хранения —
«Склады» (сохраняют фиксированное количество ресурса от разграбления,
можно строить сколько угодно, эффект суммируется) и «Хранилища» (работают
как Убежище, но на 4 часа вместо 2, отдельное здание на каждый вид
ресурса). Предположительно, это и есть содержательная разница между
линейками `*_SILO` и `*_DUMP` из §12 (а не просто визуальный/расовый
вариант одного и того же, как предполагалось ранее) — но какое конкретно
машинное имя (`SILO` или `DUMP`) закреплено за какой из двух функций,
портал не уточняет. Открытый вопрос закрыт лишь частично: теперь известно,
что различие функциональное, но маппинг имя→функция всё ещё не
установлен.

### 17.3 Иллюстративные значения `SIMILTANEOUS_BUILDS` по текущим мирам (живые данные, 2026)

> Источник: `https://destinysphere.ru/?p=game_option` (таблица
> «Одновременное количество строящихся зданий на соте», актуальна на дату
> архивации, сентябрь 2026) — живые эксплуатационные данные по 11 мирам, в
> дополнение к формуле из §4.3 (итоговое значение уже включает и
> `MAX_AMOUNT/SIMILTANEOUS_BUILDS` соты, и мировую надбавку
> `SIMILTANEOUS_BUILDS_ADDON`, без разбивки на составляющие).

| Мир | Тип мира | Лимит одновременных строек |
|---|---|---:|
| Змееносец | каждый против каждого | 10 |
| Орион | каждый против каждого | 7 |
| Феникс | каждый против каждого | 7 |
| Ворон | каждый против каждого | 7 |
| Волк | каждый против каждого | 6 |
| Стрелец | каждый против каждого | 11 |
| Эридан | раса против расы | 7 |
| Пегас | раса против расы | 7 |
| Водолей | раса против расы | 7 |
| Лев | раса против расы | 5 |
| Терра Нова | раса против расы | 11 |

Разброс 5-11 по 11 живым мирам заметно шире единственного значения «2»,
необходимого для починки Центральных Ворот (§17.1) — на практике этот
лимит почти никогда не оказывается узким местом уже на старте игры.
