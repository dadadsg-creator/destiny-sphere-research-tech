# Экономика, апкип, торговля и артефакты «Сферы Судьбы» — техническая спецификация

> Документ написан для ремастера (реализация с нуля, без чтения оригинального
> PHP) на основе построчного разбора исходников `old-sphere/ds/classes/`.
> Кодировка большинства файлов — Windows‑1251, при чтении использовалась
> перекодировка `iconv -f CP1251 -t UTF-8`. Это углублённое продолжение
> раздела 3.4 «Ресурсы и экономика» в
> [game-engine.md](game-engine.md) — здесь разобраны точные формулы,
> константы и порядок операций, а не общий обзор подсистем.
>
> **Полностью прочитаны построчно** (список для проверяемости):
> `GP/GP_CombBase.class.php`, `MP/MP_CombBase.class.php`,
> `DE/DE_CombAttributes.class.php`, `ME/ME_TurnCalc.class.php`,
> `DE/DE_RaceAttributes.class.php`, `DE/DE_WorldSettings.class.php`,
> `ME/ME_KillArmysForUpkeep.class.php`, `ME/ME_KillArmysForUpkeep2.class.php`,
> `ME/ME_KillArmysForUpkeepOld.class.php`,
> `ME/ME_DeactivateBuildingsForUpkeep.class.php`,
> `ME/ME_KillHumansForUpkeep.class.php`,
> `ME/ME_GetUpkeepResourcesFromComb.class.php`,
> `ME/ME_DeltaResourcesOnComb.class.php`, `GB/GB_ResourcesList.class.php`,
> `DE/DE_LiveUpkeepPoints.class.php`,
> `GL/GL_CountCombProductionOfResource.class.php`,
> `GL/GL_IsArmyOnUpkeepOfComb.class.php`, `GL/GL_LiveUpkeep.class.php`,
> `GL/GL_ReturnUnitResourcesAfterDie.class.php`,
> `ME/ME_CountPeriodLiveUpkeep.class.php`, `DE/DE_CombProductionBonus.class.php`,
> `GP/GP_CombTurnProduction.class.php`, `BG/BG_BuyResources.class.php`,
> `DE/DE_UserBoughtResources.class.php`, `DE/DE_TradeLot.class.php`,
> `DE/DE_TradeArticle.class.php`, `DE/DE_TradeType.class.php`,
> `DE/DE_TradeLotPrototipe.class.php`, `ME/ME_TradeExecLotBay.class.php`,
> `ME/ME_TradeExecLotSell.class.php`, `ME/ME_TradeExecLotReturn.class.php`,
> `PN/PN_TradePanel.class.php`, `GL/GL_TradeCalcBestPrice.class.php`,
> `GL/GL_TradeCountMinAndMaxPrices.class.php`,
> `GL/GL_TradeCanByThisLotByThisPrice.class.php`,
> `GL/GL_TradeCanSellByThisPriceAndScope.class.php`,
> `GL/GL_TradeCountLotTaxSumm.class.php`,
> `GL/GL_TradeCountMinSellAmount.class.php` (фактически пустой, см. открытые
> вопросы), `GL/GL_CombAllowedTradeScopes.class.php`,
> `GL/GL_TradeCalcWeight.class.php` (класс помечен в самом коде как
> неиспользуемый), `ME/ME_TradeCheckNeedAmountOfGood.class.php`,
> `ME/ME_TradeGetCourse.class.php`, `DE/DE_Money.class.php`,
> `MU/MU_ExecOperationExchange.class.php`, `ArtTradeLogger.class.php`,
> `DE/DE_Artefact.class.php`, `DE/DE_ArtefactAttributes.class.php`,
> `DE/DE_CombHasArtefact.class.php`, `EH/EH_ArcheologyTeam.class.php`,
> `UA/UA_ArcheologyTeam.class.php`, `UA/UA_ArmyUseArtefacts.class.php`,
> `UA/UA_DropArtefact.class.php`, `UA/UA_BuyArtefacts.class.php`,
> `MC/MC_CompilerArcheologyTeam.class.php`, `GL/GL_ArtefactSearch.class.php`,
> `GL/GL_CombArmyArtefactBonus.class.php`, `GP/GP_CombArtefactSlots.class.php`,
> `GP/GP_CombArtefactBonus.class.php`, `DC/DC_MilitaryBaseArtefacts.class.php`.
>
> **Прочитаны частично / выборочно** (класс большой, взяты релевантные
> фрагменты — конкретные методы указаны в тексте с номерами строк):
> `UA/UA_AttachBuildingArtefact.class.php` (584 строки, прочитаны 1–130 и
> 255–520 — блок `checkAction`/`switch($this->scopename)`),
> `DS/DS_Artefact.class.php` (989 строк, прочитаны фрагменты
> `GetDropCompensation`, `getArtInfo`/цены, `AddArtefactToComb`),
> `DS/DS_Archeology.class.php` (412 строк, прочитаны ключевые
> SQL‑методы поиска артефактов). Не читались построчно: `UA_DetachBuildingArtefact.class.php`,
> `PN_ArcheologyTeam.class.php`, `PN_ArcheologyArtefact.class.php`,
> `BG_Artefact.class.php` — по именам и связям это, соответственно,
> обратная операция к attach (снятие артефакта), UI‑панели для чтения списка
> экспедиций/артефактов, и биллинговый гейт покупки артефактов, аналогичный
> `BG_BuyResources` (уже разобран в
> [billing-and-monetization.md](billing-and-monetization.md)).
>
> Числовые **константы мировых настроек** (`DE_WorldSettings`/
> `ds_gamemodifier`) в этом документе — это **имена модификаторов**, а не их
> значения: конкретные числа хранятся в БД per-world и в исходниках не
> захардкожены (кроме нескольких PHP‑констант вроде `GC_COURSE`, которые
> помечены явно). Не выдумываю значений, которых не видел.

## 1. Модель ресурсов соты

### 1.1 Типы ресурсов (внутренние имена)

Единственный источник истины по составу ресурсов — статический класс
`GB_ResourcesList` (`ds/classes/GB/GB_ResourcesList.class.php:5-11`):

```php
static public $allResources               = array('KADERIUM', 'NANO_CRYSTAL', 'STASIS_ENERGY', 'FOOD', 'PRODIUM');
static public $unitsLiveUpkeepResources    = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY');
static public $unitsMoveUpkeepResources    = array('STASIS_ENERGY');
static public $unitsMoveFullPriceUpkeepResources = array('FOOD', 'STASIS_ENERGY');
static public $stealingResources           = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY');
static public $allBuildResources           = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY', 'WORKER');
static public $allCombResources            = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY', 'WORKER', 'SCIENTIST', 'ARCHEOLOGIST');
```

| Внутреннее имя | Категория | Роль |
|---|---|---|
| `KADERIUM` | базовый ресурс | сырьё, производится/добывается на соте, тратится на стройку/юниты/апкип |
| `NANO_CRYSTAL` | базовый ресурс | второй базовый строительный ресурс («нано») |
| `PRODIUM` | базовый ресурс | третий базовый ресурс |
| `STASIS_ENERGY` | базовый ресурс | «энергия», единственный ресурс, из-за нехватки которого **деактивируются здания** (см. §3); также единственный ресурс в апкипе перемещения армии (`$unitsMoveUpkeepResources`) |
| `FOOD` | базовый ресурс | еда, потребляется населением (рабочие/учёные/археологи) и юнитами; нехватка убивает людей (см. §3) |
| `WORKER` | население/рабочая сила | не входит в `$allResources` (не «производится» через `TURN_PRODUCTION` как сырьё), но растёт естественным приростом (§2.2), тратится на стройку (`$allBuildResources`) |
| `SCIENTIST` | население | учёные на соте (наука); в апкипе еды учитывается наравне с рабочими |
| `ARCHEOLOGIST` | население | археологи; частично в поле `CURRENT_AMOUNT` (на соте), частично в `AWAY_AMOUNT` (в экспедиции) |

Обратите внимание: `GB_ResourcesList::$allResources` (5 базовых ресурсов) —
это именно те ресурсы, у которых есть `TURN_PRODUCTION`/`TURN_UPKEEP` как у
«сырья». `WORKER`/`SCIENTIST`/`ARCHEOLOGIST` — это население, у них своя
логика роста/содержания, не «производство» в обычном смысле.

Названия полей `b_kadcosts`/`b_nanocosts`/`b_stasiscosts`/`b_prodcosts`/
`b_foodcosts`/`b_workercosts` из упомянутого в задании старого рудиментного
`building_class.class.php` — это устаревшие человекочитаемые алиасы ровно
этих пяти ресурсов + рабочих (kad=KADERIUM, nano=NANO_CRYSTAL,
stasis=STASIS_ENERGY, prod=PRODIUM, food=FOOD, worker=WORKER); этот файл, как
отмечено в `game-engine.md` §3, не используется актуальной игровой логикой.

### 1.2 Хранение и типы атрибутов

Все ресурсы соты хранятся построчно в таблице `ds_combhasresource`
(`id_comb`, `id_attribute` → `value`), где `id_attribute` ссылается на
`ds_attribute` (пара `id_attributename` × `id_attributetype`). Один и тот же
ресурс (например, `KADERIUM`) существует в БД как **несколько разных
атрибутов** — один на каждый `attributetype`. Типы атрибутов, встреченные в
разобранном коде:

| `attributetype` (typename) | Смысл | Где используется |
|---|---|---|
| `CURRENT_AMOUNT` | текущее количество на соте | склад ресурса/населения |
| `MAX_AMOUNT` | максимум (склад/лимит) | ограничивает `CURRENT_AMOUNT` при валидации (§1.4) |
| `TURN_PRODUCTION` | производство за 1 ход (до бонусов) | сумма `TURN_PRODUCTION` активных зданий, кешируется на соте и сверяется патчером `GP_CombTurnProduction` |
| `TURN_UPKEEP` | потребление за 1 ход | и у зданий (энергия), и у юнитов (апкип жизни) |
| `AWAY_AMOUNT` | «в пути» / вне соты | используется для `ARCHEOLOGIST` (в экспедиции) |
| `BONUS_RELATIVE` | относительный бонус (доля, обычно ×100 = %) | бонус производства/прочего от артефактов, науки |
| `BONUS_ABSOLUTE` | абсолютный бонус | то же, в абсолютных единицах |

Максимальный кэп значения атрибута — константа
`DE_CombAttributes::$maxAttributeSize = 999999999999`
(`DE/DE_CombAttributes.class.php:15`) — любое значение, которое превышает
этот порог, обрезается сверху и при импорте из БД, и при каждом
`setAttribute`/`deltaAttribute`.

### 1.3 Низкоуровневый доступ: `GP_CombBase` vs `MP_CombBase`

Как и предполагалось в `game-engine.md`, эти два класса — почти полный
дубликат. Построчное сравнение (`GP/GP_CombBase.class.php` целиком, 106
строк, vs `MP/MP_CombBase.class.php` целиком, 135 строк):

- `GetAttributeByID($combID, $attributeID)`, `GetCombAttributeByTypeAndName(...)`,
  `SetCombAttributeByID($id, $value)`, `GetWorldIDByCombID($combID)`,
  `GetWorldSettingByWorldIDSettingName($worldID, $name)` — **текстуально
  идентичны** между `GP_CombBase` (`GP_CombBase.class.php:5-103`) и
  `MP_CombBase` (`MP_CombBase.class.php:21-131`), включая SQL-запросы
  один в один.
- Единственные различия: `MP_CombBase` добавляет конструктор
  `__construct($combData)` (строки 11-19) и метод
  `InsertCombAttribute($combID, $value, $attributeID)` (строки 84-95),
  которых нет в `GP_CombBase`. `GP_CombBase` этих методов не содержит,
  потому что реально используется только как родитель для «патчеров»
  (`GP_Comb*`), у которых собственный конструктор с тем же кодом
  (сравните `GP_CombTurnProduction.class.php:9-17` и
  `GP_CombArtefactSlots.class.php:9-17` — они дублируют конструктор,
  которого в `GP_CombBase` нет).

Практический вывод для ремастера: оба класса — это просто «статический SQL
шлюз к `ds_combhasresource`», семантической разницы между `GP_*` и `MP_*`
версией **для механики ресурсов** нет; исторически это, видимо, копипаста
между двумя параллельными деревьями патч-скриптов (см. `game-engine.md`
раздел 2, версии мира Classic/Unlimited/Pulsar/Limited — константа
`VERSION_NAME` встречается и в торговом коде, см. §6.2).

### 1.4 Доменная обёртка `DE_CombAttributes`

`DE/DE_CombAttributes.class.php` — единственная точка, через которую
игровая логика (`UA`/`EH`/`ME`) читает и пишет ресурсы соты. Ключевые
механизмы:

- **Импорт** (`_importAttributes()`, строки 298-343): для каждой строки
  `ds_combhasresource` определяется `attributeName`/`typeName` через
  `ST_Attributes`, и если это один из `GB_ResourcesList::$allResources`, к
  значению привязывается `EXCHANGERATE` через `DE_Resource::getExchangeRate()`
  — курс обмена ресурса в условный «стазис» (единая единица измерения веса/
  ценности ресурсов, см. §6 про вес в торговле). Каждому значению
  дополнительно считается `STASIS = value * exchangeRate`.
- **Валидация** (`_validateAttributes()`, строки 38-94), вызывается и в
  конструкторе, и перед каждым `commit()`:
  - `MAX_AMOUNT` не может быть отрицательным (обрезается в 0);
  - `CURRENT_AMOUNT` округляется вниз (`floor`, ресурсы **не бывают
    дробными** на выходе, кроме поля `SIMILTANEOUS_BUILDS`, которое отдельно
    исключено из этого правила), не может быть отрицательным, и **обрезается
    сверху по `MAX_AMOUNT`** этого же ресурса, если такой лимит для него
    определён;
  - `TURN_PRODUCTION` не может быть отрицательным.
- **`setAttribute`/`deltaAttribute`** (строки 199-295): при установке `delta`
  на `CURRENT_AMOUNT` значение в диапазоне `(-1, 0)` (то есть отрицательное,
  но с модулем < 1) принудительно превращается в `0` — защита от «минус
  ноль целых и мелкая дробь» из-за погрешностей округления
  (`_validateSetAttribute`, строки 232-249). `deltaAttribute` дополнительно
  округляет входной `$delta` до 5 знаков после запятой (строка 268,
  комментарий в коде: *«Это надо так как с большим кол-вом знаков после
  запятой пхп корячит»*).
- **`commit()`** (строки 354-366) — сохраняет в БД одним batch-вызовом
  `DS_Comb::UpdateCombAttribute($this->attributes)` только если объект
  «грязный» (`$this->isModifyed`), и перед сохранением **ещё раз** прогоняет
  `_validateAttributes()`.

## 2. Пересчёт хода: `ME_TurnCalc::Count(DE_Comb $comb, $checkDate = null)`

Это центральная точка внешнего/фонового рефреша экономики соты
(`ME/ME_TurnCalc.class.php:58-118`). Порядок операций **строго
последовательный** и важен — каждый следующий шаг видит уже изменённое
состояние ресурсов от предыдущего:

```
Count(comb, checkDate):
    userInstance   = comb.userInstance
    combAttributes = comb.attributes
    raceAttributes = RaceAttributes(userInstance.raceID)
    worldSettings  = WorldSettings(currentWorld)

    lockComb()                                   # блокировка через SU_Locker, иначе клиенту шлётся USER_LOCK_DELAY

    if userInstance в режиме отпуска:  return     # GL_IsUserInVocationMode
    if на соте активен энергощит:      return     # DE_PowerShild::isWorks()

    checkDate = checkDate ?? now()

    turns = countTurns(checkDate)                 # см. §2.1
    if turns == 0: return                          # ходов не накопилось — выходим

    countWorkersBorn()                             # §2.2 — рост рабочих
    countResourceProduction()                      # §2.3 — производство ресурсов
    countBuildingsUpkeep()                         # §2.4 — потребление зданий → возможна деактивация
    countHumanUpkeep()                             # §2.5 — еда → возможна гибель людей
    countArmyUpkeep()                              # §2.6 — апкип армии → возможна гибель юнитов
```

### 2.1 Количество «ходов» (`_countTurns`, строки 135-146)

```
turns = (checkDate - userInstance.lastRefresh) / (worldSettings.TURN_TIME * 60)
if turns < 0: turns = 0
```

`TURN_TIME` — модификатор мира, задаёт длительность одного игрового «хода» в
**минутах** (умножается на 60 → секунды). `turns` — вещественное число (не
округляется), то есть вся экономика считается пропорционально прошедшему
времени, а не целыми дискретными «тиками».

### 2.2 Естественный прирост рабочих (`_countWorkersBorn`, строки 152-182)

```
max     = combAttributes.MAX_AMOUNT.WORKER
current = combAttributes.CURRENT_AMOUNT.WORKER

if max == current: skip                      # уже максимум — прирост не считаем

bonus = raceAttributes.BONUS_RELATIVE.WORKER ?? 0     # относительный бонус расы к росту рабочих

growth = max * worldSettings.WORKER_GROWTH_RATE * turns
growth = growth + growth * bonus

if userInstance.buildSpeedupEndTime > now():  growth *= 2   # активный «ускоритель стройки» удваивает и рост рабочих

work = min(growth, max - current)             # не даём перелиться через MAX_AMOUNT
combAttributes.CURRENT_AMOUNT.WORKER += work
```

`WORKER_GROWTH_RATE` — модификатор мира (доля прироста от `MAX_AMOUNT` за
один ход). Раса влияет через `DE_RaceAttributes` (`BONUS_RELATIVE`/`WORKER`,
источник — `ds_racehasattribute`).

### 2.3 Производство ресурсов (`_countResourceProduction`, строки 188-211, + `GL_CountCombProductionOfResource`)

Для каждого атрибута из `CURRENT_AMOUNT`, у которого есть парный
`TURN_PRODUCTION` (то есть только для `GB_ResourcesList::$allResources`):

```
turnProduction = GL_CountCombProductionOfResource::Count(comb, resourceName)
if turnProduction <= 0: continue
produced = round(turnProduction * turns)
combAttributes.CURRENT_AMOUNT[resourceName] += produced
```

Собственно формула `turnProduction` (`GL_CountCombProductionOfResource.class.php:5-57`)
— самая насыщенная модификаторами часть экономики:

```
base = combAttributes.TURN_PRODUCTION[resource]     # кэшированная сумма TURN_PRODUCTION активных зданий
                                                      # (сверяется/чинится патчером GP_CombTurnProduction)
if base <= 0: return 0

bonusAbsolute = combProductionBonus.getBonus(resource, 'BONUS_ABSOLUTE')
bonusRelative = combProductionBonus.getBonus(resource, 'BONUS_RELATIVE')

bonusRelative += CountWorldBonus(comb, resource)     # см. ниже — премиум/расовый мировой бонус
bonusAbsolute += bonusAbsolute * CountWorldBonus(comb, resource)

if worldSettings.LOCAL_ARTEFACT_OLD_STYLE == 1:
    production = round(base + bonusAbsolute + (bonusAbsolute + base) * bonusRelative)
else:
    production = round(base + bonusAbsolute + base * bonusRelative)

bonusArt = DS_Economical::GetUserBonus(userInstance, resource)   # временный бонус, покупаемый в кабинете
production += production * bonusArt

bonusColony = CountColonyBonus(comb, resource)      # бонус для захватываемых колоний
production += production * bonusColony

return max(0, production)
```

- **`CountWorldBonus`** (строки 59-87): читает модификатор мира по имени
  `BONUS_{PREMIUM|NON_PREMIUM}_{RESOURCE}_{RACE_NAME}` (например,
  `BONUS_PREMIUM_KADERIUM_HUMAN`), делённый на 100. `PREMIUM`/`NON_PREMIUM`
  выбирается по `user.isProfy()` (премиум-аккаунт); для «ничьих» колоний
  DestinySphere (`comb.userInstanceID == 0`) всегда берётся `NON_PREMIUM`.
  Раса подставляется по `userInstance.race.name`.
- **`CountColonyBonus`** (строки 89-108): если `comb.getIsCapture()` (это
  захватываемая колония), добавляет модификатор `BONUS_COLONY_{RESOURCE}`
  (тоже /100). Для обычных сот — `0`.
- **`DE_CombProductionBonus`** (`DE/DE_CombProductionBonus.class.php`) —
  агрегатор бонусов `BONUS_RELATIVE`/`BONUS_ABSOLUTE` из трёх источников:
  1. собственные атрибуты соты `BONUS_RELATIVE`/`BONUS_ABSOLUTE` (в них уже
     «впечатаны» артефакты со скоупом `SPHERE`/`BUILDING`/`COMB`/`LOVE` —
     см. §7.4, они применяются как прямая дельта в момент attach/detach);
  2. `DS_Artefact::GetCombAlianceAndComplexAttribute(comb)` — динамически
     суммируемый бонус артефактов со скоупом `COMBCOMPLEX`/`ALLIANCE`
     (не «впечатывается» напрямую в атрибуты соты, а пересчитывается каждый
     раз, видимо потому что альянс/комплекс могут поменяться);
  3. **бонус науки**: `researchBonus = (worldSettings.RESEARCH_BASE_BONUS / exchangeRate) * (level - 1)`
     добавляется к `BONUS_RELATIVE`, где `level` — уровень исследованной
     науки (`DS_Science::GetScienceBonus`), `exchangeRate` — курс обмена
     конкретного ресурса (см. §1.4). То есть чем «дешевле» ресурс в стазисе,
     тем сильнее относительный бонус науки на него при том же уровне —
     **предположение**: это нормализация, чтобы наука одинаково выгодно
     прокачивалась для любого ресурса; текстом в коде это не объяснено.

**Предположение**: `LOCAL_ARTEFACT_OLD_STYLE` — судя по имени и логике
(старый вариант считает бонус от суммы `base+bonusAbsolute`, новый — только
от `base`), это флаг обратной совместимости со старой формулой бонуса от
локальных артефактов, оставленный для миров, где баланс подгонялся под
старую версию расчёта. Точное происхождение по коду не установлено.

### 2.4 Потребление зданиями энергии/ресурсов (`_countBuildingsUpkeep`, строки 217-241)

Цикл **по порядку** `('KADERIUM','NANO_CRYSTAL','STASIS_ENERGY','FOOD','PRODIUM')`
(порядок в массиве именно такой в коде, а не алфавитный):

```
for resource in ['KADERIUM','NANO_CRYSTAL','STASIS_ENERGY','FOOD','PRODIUM']:
    energyUpkeep = combAttributes.TURN_UPKEEP[resource] * turns
    if energyUpkeep == 0: continue
    have = combAttributes.CURRENT_AMOUNT[resource]

    if have >= energyUpkeep:
        combAttributes.CURRENT_AMOUNT[resource] -= energyUpkeep
    else:
        combAttributes.CURRENT_AMOUNT[resource] = 0     # обнуляем остаток

        if resource != 'STASIS_ENERGY' OR worldSettings.DISABLE_UPKEEP_DEACTIVATE_BUILDINGS:
            return                                       # для прочих ресурсов при нехватке — просто выходим, зданий не деактивируем!
        else:
            ME_DeactivateBuildingsForUpkeep::Exec(comb, energyUpkeep, have, turns)
```

**Важная деталь механики**: деактивация зданий по нехватке ресурса
происходит **только для `STASIS_ENERGY`** — для остальных четырёх ресурсов
код при нехватке просто обнуляет остаток и `return`-ится из метода **до
конца цикла** (то есть если, например, не хватило `KADERIUM`, проверка
`NANO_CRYSTAL`/`STASIS_ENERGY`/`FOOD`/`PRODIUM` в этом вызове вообще не
происходит — `return true` внутри `foreach` прерывает весь метод). Реальный
эффект нехватки не-энергетических ресурсов на здания в этой ветке
**отсутствует** — здания не потребляют `KADERIUM`/`NANO_CRYSTAL`/`FOOD`/
`PRODIUM` за ход в заметном для геймплея смысле (или это не реализовано
для этих ресурсов); только `STASIS_ENERGY` реально приводит к деактивации.
Это стоит внимательно перепроверить на реальных данных `TURN_UPKEEP` зданий
в БД (в коде значения не видны) — возможно, у зданий фактически заполнен
только `TURN_UPKEEP`/`STASIS_ENERGY`.

**Деактивация зданий** (`ME_DeactivateBuildingsForUpkeep::Exec`,
`ME/ME_DeactivateBuildingsForUpkeep.class.php:18-104`):

```
diff = needEnergy - haveEnergy
building_list = SELECT ... FROM ds_attachedbuilding
                WHERE id_comb = comb
                  AND attribute(DEACTIVEABLE, id=42) на здании
                  AND attribute(TURN_UPKEEP → STASIS_ENERGY, id=7) на здании
                  AND active = true
                ORDER BY COALESCE(ds_buildingdeactivatepriority.priority, 10000000), bonus ASC
                # bonus = сумма бонусов артефактов, прикреплённых к этому зданию (COMB-скоуп)

for building in building_list:
    diff -= building.upkeep * turns
    деактивировать(building)
    if diff <= 0: break
```

Порядок деактивации — **по возрастанию приоритета** из справочника
`ds_buildingdeactivatepriority` (здания без явного приоритета получают
`10000000`, то есть деактивируются в последнюю очередь), а при равном
приоритете — по возрастанию суммы артефактных бонусов, прикреплённых к
зданию (сначала гасятся здания с меньшим бонусом от артефактов). Игра
деактивирует ровно столько зданий, сколько нужно, чтобы закрыть дефицит
энергии (`diff <= 0`), не больше. После деактивации сбрасывается кэш вида
соты для всех трёх «слоёв» отображения (домашний/центральный/нейтральный,
`resetBuildings(1/2/3)`).

### 2.5 Потребление еды населением (`_countHumanUpkeep`, строки 247-293 + `ME_KillHumansForUpkeep`)

```
combUnitsCount = CURRENT_AMOUNT.WORKER + CURRENT_AMOUNT.SCIENTIST
               + CURRENT_AMOUNT.ARCHEOLOGIST + AWAY_AMOUNT.ARCHEOLOGIST   # включая археологов "в пути"

unitFoodUpkeep = Unit('WORKER').attributes.TURN_UPKEEP.FOOD   # апкип еды считается по нормативу РАБОЧЕГО для всех категорий населения
haveFood = CURRENT_AMOUNT.FOOD
needFood = combUnitsCount * unitFoodUpkeep * turns

if haveFood >= needFood:
    CURRENT_AMOUNT.FOOD -= needFood
else:
    CURRENT_AMOUNT.FOOD = 0
    if worldSettings.DISABLE_UPKEEP_DEATH_HUMANS: return    # смерть от голода можно выключить в настройках мира
    killPart = ME_KillHumansForUpkeep::Exec(comb, needFood, haveFood, unitFoodUpkeep, combUnitsCount)
    # рассылается системное сообщение по каждой убитой категории
```

Норматив потребления еды на человека **всегда** берётся по юниту `WORKER`
(`DE_Unit::CreateFromName('WORKER')->getAttributes()->getAttribute('TURN_UPKEEP','FOOD')`)
— то есть учёные и археологи «едят» столько же, сколько рабочий, отдельного
норматива на `SCIENTIST`/`ARCHEOLOGIST` в этой формуле нет.

**`ME_KillHumansForUpkeep::Exec`** (строки 9-122) — двойная оценка «сколько
народу должно умереть», берётся **минимум** из двух вариантов:

```
countToDie1 = ceil((needFood - haveFood) / unitFoodUpkeep)
    # "почти правильный" вариант — комментарий в коде: убивает слишком много,
    # если долго был минус на соте и игрок не обновлялся

countToDie2 = ceil(combUnitsCount * haveFood / needFood)
    # "почти неправильный" вариант — комментарий в коде: убивает слишком
    # много, если минус был, но игрок быстро обновился

countToDie = min(countToDie1, countToDie2)
```

Порядок гибели (**приоритет**, не пропорционально по всем сразу):

1. **Сначала рабочие** (`WORKER`): `workerDelta = min(countToDie, workerAmount)`
   — вычитаются напрямую из `CURRENT_AMOUNT.WORKER`.
2. Если рабочих хватило, чтобы покрыть весь `countToDie` — на этом
   останавливаемся (`return $kill`), учёные и археологи не трогаются.
3. Иначе остаток `countToDie -= workerAmount`, считается доля
   `partToDie = min(1.0, remainingCountToDie / (combUnitsCount - workerAmount))`,
   и **этой же долей** пропорционально убиваются `SCIENTIST` и
   `ARCHEOLOGIST` (`amount * partToDie`, округление вверх `ceil`).
4. **Отдельно** той же долей `partToDie` уменьшается численность каждой
   активной экспедиции археологов (`ds_archeologicalteam`, поле `amount`),
   но **не ниже 1** (`if newAmount <= 0: newAmount = 1` — экспедиция
   вымирает почти полностью, но не исчезает целиком «в пути»), и
   `CountOfArcheologist` события экспедиции обновляется соответствующим
   образом (влияет на итог, который вернётся при завершении экспедиции,
   §7.3).

Каждая смерть логируется через `DE_LogUnitsDeathOnComb::log('UPKEEP', ...)`.

### 2.6 Апкип армии — «живой апкип» (`_countArmyUpkeep`, строки 300-376)

Это самая сложная часть — апкип армии считается не «за один ход
здесь-и-сейчас», а **накопительно по трассе точек**, потому что за время
между рефрешами игрока могли произойти события, менявшие состав армии на
соте (прибытие/уход войск, возврат из атаки и т.п.), и апкип должен
считаться раздельно за каждый интервал с реальным составом армии на этом
интервале.

**Трасса точек апкипа** — `DE_LiveUpkeepPoints`
(`DE/DE_LiveUpkeepPoints.class.php`): singleton-коллектор, в который разные
части кода (события EH_*, влияющие на состав армии на соте) добавляют точки
через `addPoint(comb, date)`, каждая точка хранит `UPKEEP_VALUE` —
посчитанный на тот момент апкип-«скорость» соты
(`GL_LiveUpkeep::CountComb(comb)`, строки 99-120 в `GL_LiveUpkeep.class.php`).
`ME_TurnCalc::_countArmyUpkeep()` забирает все накопленные с последнего
рефреша точки (`flushPointsForComb`) и, если их нет — апкип не считается
вовсе (`if(!$upkeepPoints) return true`).

**Интегрирование по трассе** — `ME_CountPeriodLiveUpkeep::Count`
(`ME/ME_CountPeriodLiveUpkeep.class.php:7-49`): точки сортируются по времени
(`ksort`), и для каждой пары соседних точек считается «сколько ходов длился
этот интервал» (`_getTurns`, строки 51-67, та же формула деления на
`TURN_TIME*60`, что и в §2.1), а стоимость апкипа за интервал =
`pointCurrent.UPKEEP_VALUE[resource] * turnsInInterval`, все интервалы
суммируются. Последняя точка тянется до `checkTime` (момента текущего
рефреша).

**`GL_LiveUpkeep::CountComb`/`CountArmy`/`CountUnit`**
(`GL/GL_LiveUpkeep.class.php`):

```
CountUnit(unit, amount, multiply=true):
    amount = ceil(amount)                                    # апкип всегда считается от целого числа юнитов
    multiplier = multiply ? worldSettings.LIVE_UPKEEP_UNITS_START : 1
    for resource in unit.attributes.TURN_UPKEEP:
        cost[resource] += unit.TURN_UPKEEP[resource] * amount * multiplier
    return cost

CountArmy(army):
    armyStars = min(14, GL_ArmyPowerToStars::Count(army.power))
    # если существует таблица ds_dynamic_upkeep — множитель берётся оттуда по числу "звёзд" армии,
    # иначе используется мировой модификатор LIVE_UPKEEP_UNITS_START
    multiplier = dynamicUpkeepTable[armyStars] ?? worldSettings.LIVE_UPKEEP_UNITS_START
    for unitsGroup in army.unitGroups:
        cost += CountUnit(unitsGroup.unit, unitsGroup.amount, multiply=false) * multiplier
    return cost

CountComb(comb):
    for army in comb.armies:
        if not GL_IsArmyOnUpkeepOfComb::Count(comb, army): continue   # см. ниже
        cost += CountArmy(army)
    return cost
```

**Какая армия вообще «на апкипе» у соты** —
`GL_IsArmyOnUpkeepOfComb::Count(comb, army)`
(`GL/GL_IsArmyOnUpkeepOfComb.class.php:9-31`):

```
if army.baseCombID != comb.ID: return false
if army не в действии (armyAction.isNull()): return true          # стоит дома
if army.armyAction.type == 'IN_DEFEND': return true                # в обороне на этой соте (даже если это не родная сота — но проверка выше уже фильтрует по baseCombID)
else: return false                                                 # в движении/атаке/и т.п. — апкип не идёт от этой соты
```

**Возможен «динамический» апкип** (`ds_dynamic_upkeep`, `stars` →
`upkeep`-множитель) как альтернатива фиксированному `LIVE_UPKEEP_UNITS_START`
— **предположение**: это более позднее добавление балансировки, где
множитель апкипа растёт/зависит нелинейно от суммарной мощи армии («звёзд»,
`GL_ArmyPowerToStars`, до 14 уровней), а не фиксирован на весь мир. Точная
таблица значений в коде не видна (это данные в БД).

**Если ресурсов хватает на весь накопленный апкип** (строки 368-375) —
просто вычитаем `needResources` из `CURRENT_AMOUNT` каждого ресурса
(с `assert(>= 0)` — не должно уйти в минус).

**Если ресурсов не хватает** (строки 325-367):

- Если `worldSettings.DISABLE_UPKEEP_DEATH_UNITS` — смерть юнитов от апкипа
  выключена: ресурсы всё равно списываются (`deltaAttribute` по каждому
  дефицитному ресурсу), но не ниже 0 — юниты не гибнут, долг просто
  прощается / ресурс уходит в 0.
- Иначе — вызывается **`ME_KillArmysForUpkeep2::Exec(comb, needResources, haveResources, turns)`**
  (это единственная реально работающая версия убийства армии — см. ниже §3
  про три файла с похожим именем).

## 3. Три версии «убийства армии по апкипу» — что реально используется

В `ds/classes/ME/` есть **три** файла:

| Файл | Имя PHP-класса внутри | Используется? |
|---|---|---|
| `ME_KillArmysForUpkeep.class.php` | `ME_KillArmysForUpkeep` | Нет — не вызывается из `ME_TurnCalc`, но класс с этим именем существует |
| `ME_KillArmysForUpkeepOld.class.php` | `ME_KillArmysForUpkeep` **(то же самое имя класса!)** | **Мёртвый код**: файл определяет класс с именем `ME_KillArmysForUpkeep`, а не `ME_KillArmysForUpkeepOld` — при автозагрузке PHP (`import()` резолвит файл по имени класса + префиксу, см. `game-engine.md` §1) для класса `ME_KillArmysForUpkeep` всегда будет подключён файл `ME_KillArmysForUpkeep.class.php`, а не `...Old.class.php`. Файл `...Old.class.php` физически не может быть загружен автозагрузчиком ни при каких условиях — это архивная копия, оставленная в дереве для истории |
| `ME_KillArmysForUpkeep2.class.php` | `ME_KillArmysForUpkeep2` | **Да** — именно этот класс вызывается из `ME_TurnCalc::_countArmyUpkeep()` (`ME_TurnCalc.class.php:343`) |

Сравнение `ME_KillArmysForUpkeep` и `ME_KillArmysForUpkeepOld` построчно:
единственное отличие — в `...Old` версии в приватном методе
`_setNewAmmount` добавлен закомментированный код/пустой комментарий
«Логирование помирания юнитов по апкипу» (строки 214-217) и объявлено
неиспользуемое приватное статическое поле `$comb`. Логики это не меняет —
это, по всей видимости, промежуточный снэпшот перед доработкой в сторону
`ME_KillArmysForUpkeep2`.

**Оба неиспользуемых класса** (`ME_KillArmysForUpkeep`/`...Old`) реализуют
**более простой одношаговый** алгоритм: для каждой юнит-группы считается доля
`killPart = max(1 - have[resource]/need[resource])` по всем дефицитным
ресурсам этого юнита, юнит-группа уменьшается на `floor(amount*killPart)`
разом, без итераций.

### 3.1 Актуальный алгоритм — `ME_KillArmysForUpkeep2`

Это **пятиитерационный** пропорциональный алгоритм
(`ME/ME_KillArmysForUpkeep2.class.php`), константа
`$iterationsCount = 5` (строка 112, захардкожена в PHP, не в
`DE_WorldSettings`):

```
init(comb, needResources, haveResources, turns):
    собрать все армии этой соты, у которых GL_IsArmyOnUpkeepOfComb == true
    haveUnits[unitID].AMOUNT = суммарное количество юнитов этого типа по всем таким армиям
    unitResorcesList[unitID] = { UPKEEP: {...}, RETURN: {...}, POWER: юнит.stasis, NAME }
    unitPowers[unitID] = юнит.stasis                     # для сортировки при добивании

for iteration in 1..5:
    if endOfDeathFlag: break
    killUnits()                                            # один "проход" добивания — см. ниже

normalizeLoses()          # округление, расчёт итогового % потерь армии
applyLosesToUnitGroups()  # применение к реальным DE_UnitsGroup в случайном порядке
cleanEmptyArmys()         # удаление опустевших не-дефолтных армий
formReturnResources()     # сколько ресурсов вернуть на соту от смерти юнитов
```

**`killUnits()` — один проход** (строки 319-356): юниты обрабатываются в
порядке **возрастания их "мощи" (`stasis`)** (`asort(unitPowers)`) — то
есть **сначала гибнут самые слабые/дешёвые юниты**, а не пропорционально
всем сразу. Для каждого типа юнита:

```
killPart = countKillPart(unitID)      # см. ниже
killUnitsCount = amount * killPart
haveUnits[unitID].AMOUNT -= killUnitsCount
haveUnits[unitID].DEAD   += killUnitsCount
returnResourcesFromDeath(unitID, killUnitsCount)   # частичный возврат ресурсов + уменьшение needResources на апкип уже "списанных" юнитов
```

Если за проход **никого не пришлось добивать** (`killUnitsCount == 0` для
всех типов) — выставляется `endOfDeathFlag`, и итерации прекращаются
досрочно.

**`countKillPart(unitID)`** (строки 402-433) — доля юнита, которую нужно
«погасить» **в этом проходе**:

```
part = [ max(0, 1 - have[resource]/need[resource])
         for resource in unit.UPKEEP if have[resource] < need[resource] ]
if part пуст: return 0
worstPart = max(part)     # (rsort + берём part[0] — наибольший дефицит)

if currentIteration == iterationsCount (последняя, 5-я):
    return worstPart                       # добиваем всё, что осталось
else:
    return worstPart / iterationsCount     # иначе размазываем добивание по итерациям
```

То есть алгоритм **постепенно** (за 5 проходов) добивает армию: сначала
пробует убить по 1/5 дефицита у самых слабых юнитов, пересчитывает
оставшийся дефицит ресурсов (`_returnResourcesFromDeath` уменьшает
`needResources` на апкип уже погибших юнитов — они больше не должны его
«есть»), и повторяет для следующих по мощи юнитов; если после прохода
дефицит остаётся, на 5-м проходе добивается всё, что не хватило.

**Возврат ресурсов при гибели** — `GL_ReturnUnitResourcesAfterDie::CountUnit`
(`GL/GL_ReturnUnitResourcesAfterDie.class.php:17-35`):

```
KILL_UNITS_RETURN_PART = worldSettings.KILL_UNITS_RETURN_PART   # доля возврата, 0..1
productionCost = unit.attributes.getProductionCost()             # сколько ресурсов стоило произвести 1 юнита
for resource in [KADERIUM, NANO_CRYSTAL, FOOD, PRODIUM, STASIS_ENERGY]:
    return[resource] = productionCost[resource] * killedAmount * KILL_UNITS_RETURN_PART
```

Тот же класс и та же формула используются и при отмене строительства/апгрейда
(общее имя метода `CountUnitGroup`/`CountUnit`), не только при апкипе —
подтверждает, что «сколько вернуть за уничтоженного юнита» — единая
механика в игре.

**Итоговый процент потерь армии** (`_normolizeLoses`, строки 231-253):

```
deadProcent = round(deadPower / (lifePower + deadPower) * 100, 2)
if deadProcent == 0 and deadPower > 0: deadProcent = 0.01   # чтобы не показывать "0% потерь" при ненулевых жертвах
```

где `lifePower`/`deadPower` — сумма (`количество * unitPower`) по выжившим/
погибшим соответственно.

**Порядок применения к реальным юнит-группам**
(`_upplyLosesToUnitGroups`, строки 259-298) — **случайный** (`shuffle` списка
юнит-групп перед применением потерь), что не влияет на итоговые числа (они
уже посчитаны), но важно для детерминизма/тестируемости при портировании —
это **не воспроизводимо** без фиксации seed, если для ремастера важна
побитовая совместимость воспроизведения истории (см. открытые вопросы).

### 3.2 Итоговый приоритет нехватки ресурсов (сводка §2.4–2.6 + §3.1)

Порядок обработки внутри одного вызова `ME_TurnCalc::Count`:

1. **Здания** (`_countBuildingsUpkeep`) — при нехватке `STASIS_ENERGY`
   деактивируются здания по приоритету (низкий приоритет = деактивируется
   первым), пока дефицит не закрыт. Нехватка прочих 4 ресурсов **не имеет
   выраженного эффекта на здания** в этом методе (см. предупреждение в §2.4).
   Флаг отключения: `DISABLE_UPKEEP_DEACTIVATE_BUILDINGS`.
2. **Люди** (`_countHumanUpkeep`) — при нехватке `FOOD` гибнут сначала
   рабочие, потом (пропорционально) учёные/археологи на соте и в
   экспедициях (не ниже 1 на экспедицию). Флаг отключения:
   `DISABLE_UPKEEP_DEATH_HUMANS`.
3. **Армия** (`_countArmyUpkeep`) — при нехватке любого из 5 базовых
   ресурсов для содержания войск на соте гибнут юниты, начиная с самых
   слабых (`stasis`), за 5 итераций пропорционального добивания. Флаг
   отключения: `DISABLE_UPKEEP_DEATH_UNITS` (при включённом флаге ресурсы
   всё равно списываются в минус до нуля, юниты не гибнут).

Эти три шага **независимы друг от друга** (нехватка энергии для зданий не
блокирует и не форсирует апкип еды/армии) и выполняются строго в этом
порядке за один вызов `Count()`.

## 4. Покупка ресурсов за деньги — `BG_BuyResources`

Внешний биллинговый гейт (`ds/classes/BG/BG_BuyResources.class.php`),
вызывается извне (не из игрового Flash-клиента напрямую, а из платёжной
системы, см. [billing-and-monetization.md](billing-and-monetization.md)).
Полный флоу:

```
check(fromCheck=true):
    _checkParams()       # обязательные поля: id_user, worldname, res (∈ GB_ResourcesList::$allResources), count > 0
    _getUIID()            # находит id_userinstance/id_world/dbID игрока по (userID, worldname) через DS_BillingBuyResource
    _checkPermission()     # worldSettings.BUY_RESOURCE_FOR_MONEY_ALLOWED == 1 ?
    _checkUserLimit()      # см. ниже
    if fromCheck: вернуть "OK" без изменений

proceed():
    check(fromCheck=false)
    в транзакции:
        combAttr = CombAttributes(домашняя сота игрока)
        boughtRes = UserBoughtResources(userID_worldID)
        combAttr.deltaAttribute('CURRENT_AMOUNT', res, +count)
        boughtRes.delta(res, +count)
        commit both
```

**Лимит на пользователя** (`_checkUserLimit`,
`BG_BuyResources.class.php:142-161`):

```
new = boughtRes.get(resource) + count            # уже купленное этим игроком в этом мире + запрошенное сейчас
max = worldSettings.get('BUY_RESOURCE_' + resource + '_LIMIT')   # напр. BUY_RESOURCE_KADERIUM_LIMIT
if new > max:
    вернуть в ответе res_available = max(0, max - boughtRes.get(resource))
    отказ
```

Лимит **накопительный и постоянный** (не сбрасывается по ходам/дням) — это
модификатор мира на **каждый вид ресурса отдельно** (`BUY_RESOURCE_<RES>_LIMIT`,
5 разных модификаторов для 5 ресурсов `GB_ResourcesList::$allResources`).
Счётчик уже купленного хранится в `DE_UserBoughtResources`
(`DE/DE_UserBoughtResources.class.php`) — простая пара
`(userID, worldID) → {resource: count}`, ключ синглтона `"userID_worldID"`,
таблица в БД — через `DS_BillingBuyResource` (не разбиралась отдельно, но
видно из вызовов `getUserBuyResourceCount`/`commitUserBuyResource`).

**Важно**: `BUY_RESOURCE_FOR_MONEY_ALLOWED` включается/выключается **на
уровне мира целиком** (один флаг на все 5 ресурсов), а индивидуальные
лимиты — по каждому ресурсу отдельно.

## 5. Биржа (торговля между игроками)

### 5.1 Категории и типы товара

Товар на бирже характеризуется парой `(id_tradecategory, id_article)`.
Категории (`id_tradecategory`, встречены как числовые константы в коде,
имена — через `DS_Trade::GetTradeCategoryNameByID`):

| ID | Имя категории | Что это | Где видно в коде |
|---|---|---|---|
| 1 | `RESOURCE` | один из 5 базовых ресурсов | `DE_TradeArticle::CreateFromTradeCategoryIDAndArticleIDForCurrentWorld` switch, `GL_TradeCalcWeight.class.php:18` |
| 2 | `UNIT` | боевой юнит | `GL_TradeCanSellByThisPriceAndScope::_checkBattle` (категория 2 = юниты, их нельзя продавать во время боя на соте), `GL_TradeCalcWeight.class.php:23` |
| 3 | `ARTEFACT` | артефакт (инстанс) | `DE_TradeLot::CreateInDBFromTradePrototipeAndEventQueue` (`if(...getTradeCategoryID() == 3)`), `GL_TradeCalcWeight.class.php:28` |
| 4 | `MONEY` | игровая валюта (SC/GC) | `ME_TradeExecLotBay::_execMoneyTransfer`, `PN_TradePanel.class.php:154` |

`DE_TradeType` (`DE/DE_TradeType.class.php`) описывает **разрешённую пару**
«что продаём → за что» (`id_trade_on_category/type` → `id_trade_by_category/type`)
и параметры этой пары: `is_allowed`, `max_price_delta`/`min_price_delta`
(коридор цены в % от рыночного курса, см. §5.4), `tax` (базовый налог, %,
см. §5.6). `DE_TradeArticle` (`DE/DE_TradeArticle.class.php`) — конкретный
предмет внутри категории (ресурс/юнит/артефакт/валюта) с локализованным
названием.

### 5.2 Скоупы (области) торговли — `GL_CombAllowedTradeScopes`

`GL_CombAllowedTradeScopes::GetAllScopes(comb)`
(`GL/GL_CombAllowedTradeScopes.class.php:18-43`) вычисляет, какие «зоны
видимости» лота доступны данной соте:

| `id_tradescope` | Имя | Условие доступности |
|---|---|---|
| 1 | `COMB_COMPLEX_TRADING` | всегда доступен (торговля внутри своего сотового комплекса) |
| 2 | `RING_TRADING` | `(newbDefence ИЛИ открыты Центральные Ворота) И (есть Кольцевой Транспортёр ИЛИ Транспортёр) И VERSION_NAME != "LIMITED"` |
| 3 | `INTERRING_TRADING` | `(newbDefence И межкольцевой коннектор И VERSION_NAME != "LIMITED") ИЛИ (открыты ЦВ И есть Кольцевые Ворота И межкольцевой коннектор)` |
| 4 | `ALLIANCE_TRADING` | `(доступен INTERRING_TRADING ИЛИ мировая настройка TRADING_ALLIANCE_ONLY_FROM_INTERRING_CONNECTOR выключена) И открыты ЦВ И игрок состоит в непустом альянсе` |

Здание-идентификаторы, зашитые в код: `id_building=25` — Кольцевые Ворота,
`id_building=20` — межкольцевой коннектор (IRT), `id_building=31` —
транспортёр (RT). Каждый скоуп дополнительно можно выключить целиком на
уровне мира модификаторами `DISABLE_COMPLEX_TRADING`/`DISABLE_RING_TRADING`/
`DISABLE_INTERRING_TRADING`/`DISABLE_ALLIANCE_TRADING`
(`GL_TradeCanSellByThisPriceAndScope::_checkScope`, строки 258-279).
`VERSION_NAME == "LIMITED"` — версия мира, где кольцевая/межкольцевая
торговля **всегда закрыта** независимо от построек (ещё одно подтверждение
множественности версий игры Classic/Unlimited/Limited/Pulsar,
см. `game-engine.md`).

### 5.3 Выставление лота на продажу

Флоу: `UA_Sell` (не разбирался построчно) собирает `DE_TradeLotPrototipe`
(`DE/DE_TradeLotPrototipe.class.php`) — «черновик» лота с сеттерами
(`setTradeArticles`, `setPriceAndAmount`, `setStartDateAndDuration`,
`setIsAuction`/`setIsPersonal`, `setIsAnonymous`, `setSellerIP`) и валидацией
структурной целостности (`validate()`, строки 324-352: нельзя одновременно
`isAuction` и `isPersonal`).

**`setPriceAndAmount`** (строки 188-197): цена округляется **вверх**
(`ceil`), количество — **вниз** (`floor`); оба должны быть `> 0`.

**`setStartDateAndDuration`** (строки 199-211):
`endDate = startDate + duration * worldSettings.TURN_TIME * 60` — то есть
`duration` лота задаётся в тех же «ходах», что и весь остальной таймер игры,
ограничение сверху на `duration` — модификатор `TRADE_MAX_TURN`
(проставляется в панели, `PN_TradePanel.class.php:57`).

**Проверка допустимости выставления** — `GL_TradeCanSellByThisPriceAndScope::Test`
(`GL/GL_TradeCanSellByThisPriceAndScope.class.php:81-147`), цепочка проверок
в порядке:

1. `_checkExists` — существует ли вообще `DE_TradeType` для пары
   from-article/to-article в этом мире.
2. `_checkAllowed` — разрешён ли этот `DE_TradeType` (`is_allowed`).
3. `_checkPrice` — цена входит в коридор `[MIN_PRICE, MAX_PRICE]`
   (см. §5.4), **только если** включён `TRADE_USE_TRANDS`.
4. `_checkScope` — есть ли у продавца нужный скоуп (§5.2) и не выключен ли
   он глобально; для скоупа 4 (альянс) — дополнительно проверяется, что
   альянс не пустой.
5. `_checkNeedAmount` — хватает ли реально на соте того, что выставляют
   (`ME_TradeCheckNeedAmountOfGood`, см. §5.7).
6. `_checkPerson` — для персонального лота: включена ли механика
   (`TRADE_USE_PERSONAL_TRADES`), существует ли указанный пользователь.
7. `_checkBattle` — юниты (категория 2) нельзя выставлять на продажу, если
   на соте идёт незавершённый бой (`DS_Battle::NotEndedBattlesOnComb`).
8. `_checkTradeBan` / `_checkPremiumBan` — персональный бан на торговлю /
   премиум-бан (если бан не даёт разрешения `PremiumBanAllowTrade`).
9. `_checkLevel` — есть ли у игрока «умение торговли» этим товаром
   (`DS_Science::GetTradeAbility`, завязано на дерево наук).
10. `_checkRandomDefend` — под «случайной защитой» продавать нельзя, кроме
    персонального лота, выставленного самому себе (владельцу защиты).
    Колонии (кольцо №4) исключены из этой проверки.

**После валидации** (`ME_TradeExecLotSell::Exec`,
`ME/ME_TradeExecLotSell.class.php:22-64`):

```
if fromCategory == 'UNIT':
    списать апкип с продавца за оставшееся до конца лота время (GL_FullUnitsMovementCost::CountSellUnit)
создать DE_EventQueue типа 'TRADING' на [startDate, endDate]
создать DE_TradeLot в БД из прототипа
if fromCategory != 'ARTEFACT':
    ME_DeltaResourcesOnComb::Exec(fromCategory, comb, article, -amount, startDate)   # списать товар с соты сразу при выставлении
    # для артефактов списание сделано раньше, при создании лота (ME_TradeArtefactInstancesOnTradeLotWork)
DS_Trade::LogSell(tradeLot)
GL_TradeCalcBestPrice::Calc(tradeLot)   # пересчёт цены за единицу товара, см. §5.5
```

То есть **товар списывается с соты сразу в момент выставления лота**, а не
в момент продажи — продавец физически не может тратить выставленные на
продажу ресурсы/юнитов, пока лот активен.

### 5.4 Ценовые коридоры — `GL_TradeCountMinAndMaxPrices`

(`GL/GL_TradeCountMinAndMaxPrices.class.php:8-55`)

```
if worldSettings.TRADE_USE_TRANDS:
    price = ME_TradeGetCourse::Get(fromArticle, toArticle)     # текущий рыночный курс, см. ниже
    MAX_PRICE = ceil((price + tradeType.maxPriceDelta/100 * price) * sellAmount)
    MIN_PRICE = floor((price + tradeType.minPriceDelta/100 * price) * sellAmount)
else:
    MAX_PRICE = 2000000000
    MIN_PRICE = 1

if currentPrice указана (для аукциона):
    if есть текущая ставка (haveCurrentCustomer):
        NEXT_PRICE = ceil(currentPrice + currentPrice * worldSettings.MIN_BID_STEP)
    else:
        NEXT_PRICE = currentPrice
```

`ME_TradeGetCourse::Get` (`ME/ME_TradeGetCourse.class.php:5-30`) читает
текущий «курс» пары товаров из справочника (`DS_Trade::GetTradeCourse*`,
раздельно для RESOURCE/UNIT/ARTEFACT) — это отдельная витрина «средних
рыночных цен», обновляемая, судя по составу каталога `SU_` (`SU_RecountCourses`,
`SU_RecountCoursesSearch`, не разбирались построчно), периодическим
cron-пересчётом на основе истории сделок. `tradeType.minPriceDelta` в коде
хранится как **отрицательное** число (`assert($this->minPriceDelta < 0 && ...)`,
`DE_TradeType.class.php:88`) — то есть нижняя граница коридора формулой
`price + minPriceDelta/100*price` естественно оказывается ниже `price`.

`MIN_BID_STEP` — минимальный шаг повышения ставки на аукционе, в % от
текущей цены.

Если `TRADE_USE_TRANDS` выключен — ценовые коридоры фактически сняты
(`MIN_PRICE=1`, `MAX_PRICE=2×10⁹`), торговля свободная по любой цене в
этих пределах.

### 5.5 «Лучшая цена» лота — `GL_TradeCalcBestPrice`

(`GL/GL_TradeCalcBestPrice.class.php:10-57`) — цена за единицу товара, для
сортировки/сравнения лотов в UI:

```
price = isAuction ? (currentPrice ?: startPrice) : startPrice

if fromCategory != ARTEFACT (3):
    count = lot.amount
else:
    artAttributes = артефакт.бонус-атрибут (первый попавшийся)
    if нет бонус-атрибута:
        count = lot.amount
    else:
        count = artAttributes.value
        if artAttributes.typename == 'BONUS_RELATIVE':
            count *= 100                     # относительный бонус выражается в "процентных пунктах"
        count *= lot.amount
        if count == 0: count = lot.amount

bestPrice = price / count
```

Для артефактов «единица товара» — это не штука артефакта, а его **сила
бонуса** (если он есть) — то есть цена за единицу — это фактически «цена за
1 пункт бонуса», что позволяет сравнивать разные артефакты по
эффективности/цене.

### 5.6 Налог на сделку — `GL_TradeCountLotTaxSumm`

(`GL/GL_TradeCountLotTaxSumm.class.php:9-50`)

```
if not worldSettings.TRADE_USE_TAX: return 0

taxBase = tradeType.tax / 100
premiumTaxDelta = worldSettings.TRADE_PREMIUM_TAX_DELTA / 100

tax = seller.isProfy() ? (taxBase + premiumTaxDelta) : taxBase
tax = max(0, tax)

price = lot.currentPrice
if toCategory == MONEY and валюта == GC:
    price *= GC_COURSE          # налог всегда считается и списывается в SC (серебре), см. §7

taxSumm = ceil(price * tax)
```

Налог **вычитается из выплаты продавцу**, не добавляется к цене покупателя
(см. `getPriceWithTaxForSendToSeller()`,
`DE_TradeLot.class.php:455-462`: `ceil(currentPrice - taxSumm)`).
`TRADE_PREMIUM_TAX_DELTA` может быть и отрицательным (пониженный налог для
премиум-аккаунтов) — учитывая, что итоговый `tax` явно обрезается снизу
нулём (`if($tax < 0) $tax = 0`), похоже, что для премиум-игроков налог
обычно **ниже**, чем базовый (то есть `premiumTaxDelta` отрицательна).

> ✅ **Реальные значения на мире «Сфера Ориона» (источник: БД, live-запрос,
> 2026-09-08)**: `TRADE_PREMIUM_TAX_DELTA = 0` (`ds_gamemodifier`) — на
> этом конкретном мире премиум-скидка по налогу сейчас **не активна**
> (ни положительная, ни отрицательная), предположение выше не
> опровергнуто, но и не подтверждено числом — просто на этом мире дельта
> обнулена. Базовый `tradeType.tax` (`ds_trade_type_data.tax`,
> `DE_TradeType`) реально варьируется по паре категорий
> «продаём→покупаем» (id_tradecategory: 1=RESOURCE, 2=UNIT, 3=ARTEFACT,
> 4=MONEY, см. §5.1):
>
> | Продаём → Покупаем | `tax` | Коридор `min/max_price_delta` |
> |---|---|---|
> | RESOURCE → RESOURCE | 0% | −100% / 0% |
> | RESOURCE → MONEY | 0% | −100% / 0% |
> | UNIT → RESOURCE | 0% | −100% / +10000% |
> | UNIT → MONEY | 0% | −100% / +10000% |
> | ARTEFACT → RESOURCE | 0% | три варианта в зависимости от конкретного `id_trade_on_type` (артефакта): −100/+1000%, −100/+10000%, −10/+10000% |
> | ARTEFACT → MONEY | 0%, 1% или 30% | соответственно −100/+10000%, −1/+1% (очень узкий коридор), −100/+10000% — тоже зависит от конкретного артефакта |
>
> То есть на практике налог на этом мире реально ненулевой **только** для
> части сделок «артефакт → валюта» (до 30%) — обмен ресурсов и юнитов
> сейчас беспошлинный. Это данные одного конкретного мира и снапшота на
> дату запроса, не универсальная константа кода — `ds_trade_type_data`
> явно проектировалась как редактируемая администрацией таблица
> (`is_current` намекает на версионирование правок).

### 5.7 Проверка наличия нужного количества — `ME_TradeCheckNeedAmountOfGood`

(`ME/ME_TradeCheckNeedAmountOfGood.class.php:5-56`) — единая точка проверки
«хватает ли у соты нужного количества товара», используется и при
выставлении (что продают), и при покупке (чем платят):

```
switch category:
    RESOURCE: comb.attributes.CURRENT_AMOUNT[resource]
    UNIT:     DS_Army::GetUnitsAmountInHomeArmyByCombAndUnit(comb, unitID)   # только домашняя армия!
    ARTEFACT: DS_Artefact::GetArtefactsAmountOnCombStore(comb, articleID)
    MONEY:    MU_SearchOperations::GetAccountData(member)[money.shortName]  # баланс SC/GC пользователя, не соты!
return have >= needAmount
```

Важно: для юнитов проверяется **только домашняя армия** соты
(`GetUnitsAmountInHomeArmyByCombAndUnit`) — юнитов в обороне/защите на
других сотах или в движении для торговли использовать нельзя. Деньги
(категория `MONEY`) проверяются на уровне **аккаунта пользователя**, а не
конкретной соты — баланс SC/GC общий на весь аккаунт в мире.

### 5.8 Покупка / ставка на аукционе — `GL_TradeCanByThisLotByThisPrice` + `ME_TradeExecLotBay`

Проверка перед покупкой (`GL_TradeCanByThisLotByThisPrice::Test`,
`GL/GL_TradeCanByThisLotByThisPrice.class.php:57-107`), цепочка:

1. `_isNotThisPersonLot` — нельзя купить свой собственный лот.
2. `_testPerson` — для персонального лота: совпадает ли имя покупателя и
   пароль (если задан).
3. `_haveThisTradeScope` — есть ли у покупателя доступ к скоупу лота.
4. `_validNewPrice` (только для аукциона) — новая ставка должна быть в
   коридоре `[MIN_PRICE, MAX_PRICE]` (если `maxPriceDelta > 0`,
   иначе верхняя граница не проверяется) и **не меньше `NEXT_PRICE`**
   (текущая цена + минимальный шаг); если ставка больше `NEXT_PRICE`, она
   принимается как есть (перебивать можно сразу выше минимального шага).
5. `_haveResources` — хватает ли у покупателя средств
   (`ME_TradeCheckNeedAmountOfGood`, цена — либо `newPrice` для аукциона,
   либо фиксированная `lot.price`).
6. `_testEndDate` — лот не просрочен.
7. `_isThisWorld` — лот принадлежит текущему миру (защита от «пришельцев» —
   комментарий в коде дословно про это).
8. `_checkTradeBan` / `_checkPremiumBan` — аналогично §5.3.
9. `_checkRandomDefend` — под случайной защитой покупать нельзя (кроме
   колоний, кольцо 4).
10. `_rvrTest` — специфика миров «раса против расы» (`worldtype ==
    'RACE_VS_RACE'`): для юнитов/ресурсов/артефактов отдельные флаги
    `TRADE_RVR_ALLOW_UNITS`/`TRADE_RVR_ALLOW_RESOURCE`/
    `TRADE_RVR_ALLOW_ARTEFACTS` разрешают межрасовую торговлю категорией,
    иначе требуется совпадение расы продавца и покупателя
    (`RVR_RACE_DIFFERENCE`). Для обычных миров — отдельный, более мягкий
    флаг `TRADE_NON_RVR_INTERRACE_ALLOW_UNITS` именно для юнитов (только
    для юнитов проверка расы вообще применяется вне RvR-миров).

**Немедленная покупка** (не аукцион) сразу исполняется через
`ME_TradeExecLotBay::Exec(lot, buyDate, buyAnonymous)`
(`ME/ME_TradeExecLotBay.class.php:59-114`):

```
tradeLot.setCurrentCustomer(...)         # уже должно быть установлено до вызова (лот "готов к покупке")
tradeLot.taxSumm = GL_TradeCountLotTaxSumm::Count(tradeLot)
countEventTimes()                         # время доставки = время перелёта между сотами продавца/покупателя (GL_TravelTime, скорость = 1)

if fromCategory == 'UNIT':
    getUpkeepResources()                  # покупатель сразу платит апкип за купленных юнитов (если это не аукцион — там апкип снят раньше, при ставке)
    if lot.endDate > buyDate:
        returnUpkeepResources()           # если купили раньше срока окончания — часть уже списанного апкипа продавцу возвращается пропорционально

ME_TradeAddHistory::Exec(lot, buyDate)    # запись в историю торгов (для графиков курса)
createGoodDeliveryEvent()                 # событие TRADING_DELIVERY_GOOD, время = время полёта между сотами
if toCategory == 'MONEY':
    execMoneyTransfer()                   # см. ниже — мгновенно, без события доставки
else:
    createPaymentDeliveryEvent()          # событие TRADING_DELIVERY_PAYMENT + мгновенное списание ресурсов с покупателя (если не аукцион)

tradeLot.delete(); tradeLot.lotEvent.setIsTrash(true)
```

**Товар** доставляется покупателю **не мгновенно**, а через отложенное
событие `TRADING_DELIVERY_GOOD` с задержкой, равной времени перелёта между
сотами продавца и покупателя (`GL_TravelTime::Count`, скорость перелёта
фиксирована `= 1`, то есть не зависит от скорости конкретных юнитов
покупателя). Оплата **деньгами** переводится мгновенно
(`_execMoneyTransfer`), оплата **другими ресурсами/товаром** — тоже через
отложенное событие `TRADING_DELIVERY_PAYMENT` с той же задержкой (одно и то
же время долёта в обе стороны).

**Перевод денег** (`_execMoneyTransfer`, строки 236-280):

```
if isAuction: сначала вернуть деньги покупателя из резерва (ME_TradeExecReturnUserBid) — резерв аукционных ставок
перевести сумму lot.currentPrice продавцу (MU_EntityOperation, actionType='TRADE_PAYMENT')
if валюта продажи == GC:
    сразу же обменять полученные продавцом GC → SC по курсу GC_COURSE (actionType='EXCHANGE_CREDITS', см. §7)
if taxSumm > 0:
    списать с продавца налог в SC (actionType='PAY_TRADE_TAX') — налог ВСЕГДА в серебре, даже если сделка была в золоте
```

### 5.9 Возврат непроданного/отменённого лота — `ME_TradeExecLotReturn`

(`ME/ME_TradeExecLotReturn.class.php:9-73`) — вызывается и по истечении
срока лота без покупателя, и (предположительно) при ручной отмене:

```
if lot.haveCurrentCustomer():             # если уже была ставка/покупатель (аукцион с торгом, отменённый до финализации)
    вернуть покупателю то, чем он платил:
        MONEY  → операция MOVE_FROM_RESERV (деньги были зарезервированы при ставке)
        иначе  → ME_DeltaResourcesOnComb.Exec(+currentPrice покупателю)

if fromCategory == ARTEFACT (3):
    вернуть артефакты из ивента продавцу (ME_TradeArtefactInstancesOnTradeLotWork)
else:
    if fromCategory == RESOURCE and withComission (по умолчанию true):
        amount = ceil(lot.amount - lot.amount * worldSettings.TRADE_PENALTY)   # штраф за возврат ресурса!
    else:
        amount = lot.amount                                                     # юниты/аукционные лоты без штрафа
    ME_DeltaResourcesOnComb.Exec(fromCategory, sellerComb, articleID, +amount, ...)
    отправить продавцу сообщение "CANCEL_TRADE_LOT"

lotEvent.setIsTrash(true); lot.delete()
```

**Штраф за возврат ресурсов** (`TRADE_PENALTY`, модификатор мира, доля 0..1)
применяется **только к категории `RESOURCE`** и **только если** явно не
отключён параметром `withComission=false` — то есть продавец, выставивший
ресурсы и не дождавшийся покупателя, теряет часть выставленного объёма при
возврате на склад. Юниты и артефакты возвращаются без потерь.

### 5.10 Пересчёт рыночных курсов — это PL/pgSQL, не PHP (`trade_courses_recount`)

> Этот подраздел написан по итогам отдельного разбора `schema.sql`
> (`pg_dump --schema-only` живой игровой БД: 499 таблиц, 451 функция,
> 387 триггеров — файл не в репозитории, только структура, без данных) —
> в дополнение к построчному разбору PHP выше. Обозначения файла: `schema.sql:NNN`.

Формула `ME_TradeGetCourse::Get`/`DS_Trade::GetTradeCourseFor{Resources,Units,Artefacts}`
(§5.4) читает «текущий курс» из трёх версионированных per-world таблиц-кэшей:
`ds_trade_resorce_course_data` (`schema.sql:19896-19905`),
`ds_trade_units_cours_data` (`schema.sql:20035-20044`),
`ds_trade_artefact_course_data` (`schema.sql:19451-19460`) — в каждой строка
вида `(id_world, id_source_article, id_tradecourse → ds_trade_course.(id_tradecategory,
id_article), value, delta_course, is_current)`, и на комбинацию
«(мир, товар-источник, товар-цель)» в любой момент времени активна **только
одна** строка с `is_current = true`. Находка этого разбора: **сам расчёт
курса — не PHP, а PL/pgSQL**, выполняемый отдельным батч-джобом.

**Запись нового значения курса** — три структурно идентичные функции (одна
на категорию источника, различается только имя таблицы/столбца):
`insert_resource_trade_course` (`schema.sql:1691-1751`),
`insert_unit_trade_course` (`schema.sql:1796-1867`),
`insert_artefact_trade_course` (`schema.sql:1620-1684`), одна и та же
сигнатура `(id_world, id_article_from, id_trade_category_to, id_article_to,
new_value) RETURNS integer`:

```
найти текущую (is_current=true) строку курса для этой пары
if новое значение == текущему: return 0        -- курс не изменился, ничего не пишем
пометить текущую строку is_current = false
вставить новую строку (value=new_value, delta_course = new_value - old_value, is_current=true)
return 1
```

Версионирование «мягкое» (старые строки не удаляются, только гасятся
флагом) — в БД накапливается полная история изменения курса каждой пары
товаров, а не только текущее значение, и `delta_course` — уже готовое поле
для графика динамики курса, PHP его не пересчитывает.

**Сам батч-пересчёт** — `trade_courses_recount(id_world, gc_course)`
(`schema.sql:11607-11801`) — для каждой из трёх категорий (`RESOURCE`,
`UNIT`, `ARTEFACT`) агрегирует **реальную историю сделок** `ds_trade_history`
(`schema.sql:19634` — та же таблица, в которую пишет `ME_TradeAddHistory::Exec`
при каждой покупке, см. §5.8) за всё время (ограничения по дате в этой
функции нет):

```
для каждой пары (id_from_article, id_to_category, id_to_article) с id_trade_scope NOT IN (4,5):
    course = sum(to_ammount [* gc_course, если цель — GC]) / sum(from_ammount)
    PERFORM insert_{resource|unit|artefact}_trade_course(id_world, id_from_article, id_to_category, id_to_article, course)
INSERT INTO ds_trade_course_recount (id_world) VALUES (id_world)   -- отметка "пересчёт для этого мира выполнен"
```

Рыночный курс — это **средневзвешенная цена по всем завершённым сделкам
этой пары товаров за всё время** (не скользящее окно/EMA). Параметр
`gc_course` передаётся вызывающим — то есть `GC_COURSE = 1000` из
`ds/config/settings.php` (§7) должен прокидываться в эту функцию явно при
вызове; внутри функции обращений к `ds_gamemodifier`/другим PHP-константам
нет. Фильтр `id_trade_scope NOT IN (4,5)` — **предположение**: §5.2 этого
документа описывает только скоупы 1–4 (`COMB_COMPLEX_TRADING`/
`RING_TRADING`/`INTERRING_TRADING`/`ALLIANCE_TRADING`); скоуп 5 в PHP-коде
трейд-панели в этом разборе не встречался, возможно, это отдельный
числовой скоуп персональных (`isPersonal`) лотов, специально исключённый
здесь, чтобы именные сделки по договорной цене не искажали рыночную
статистику — не подтверждено чтением справочника `ds_tradescope`.

**`trade_courses_recount2(id_world, gc_course)` — пустая заглушка**
(`schema.sql:11808-11823`): та же сигнатура, тело состоит только из
`SET STATEMENT_TIMEOUT` и `RETURN 1`, никакой бизнес-логики. Это тот же
паттерн «вторая версия функции, оставленная недописанной», что и
`ME_KillArmysForUpkeep`/`ME_KillArmysForUpkeep2` в PHP (§3) — только здесь,
в отличие от апкипа армии, рабочей осталась **первая** версия (`recount`,
без суффикса), а `recount2`, судя по имени, задумывалась как замена или
оптимизация, но брошена в зачаточном состоянии.

**Кто вызывает `trade_courses_recount`?** — открытый вопрос: поиск по всему
`old-sphere/` не находит ни одного обращения к `trade_courses_recount`,
`trade_courses_recount2` или к `clear_trade_history(days)`
(`schema.sql:494-528` — чистит `ds_trade_history`/`ds_trade_history_sells`/
`ds_trade_history_bid` старше `days` дней простым `DELETE ... WHERE
end_date < now() - days`) ни из одного PHP-файла. Судя по всему, все три
вызываются планировщиком напрямую на уровне PostgreSQL (внешний cron/
`pgAgent`-джоб с `psql -c "SELECT trade_courses_recount(...)"`), а не через
игровой PHP-код — это согласуется с уже отмеченным в преамбуле этого
документа фактом, что классы `SU_RecountCourses`/`SU_RecountCoursesSearch`
не разбирались построчно; не исключено, что они лишь читают результат
этого пересчёта (через `ds_trade_course_recount`), а не выполняют его сами.
Не установлено.

**Вывод**: рыночный курс биржи (то, что задаёт коридор цен в §5.4 и
«лучшую цену» лота в §5.5) — единственный найденный в этом разборе случай,
где расчёт значимого игрового показателя целиком выполняется в PL/pgSQL, а
не в PHP: PHP только пишет сырые сделки в `ds_trade_history`
(`ME_TradeAddHistory`) и читает уже готовый агрегат
(`ME_TradeGetCourse`/`DS_Trade::GetTradeCourseFor*`); сама агрегация —
батч-функция в БД, запускаемая, по всей видимости, вне игрового приложения.
Подробный разбор денежного (не товарного) движка на уровне БД —
`ds_money_account*`, версионирование `is_current`-цен, аудит баланса — см.
[billing-and-monetization.md, раздел 9](billing-and-monetization.md).

## 6. Артефакты и археология

### 6.1 Модель данных

- **`DE_Artefact`** (`DE/DE_Artefact.class.php`) — конкретный **инстанс**
  артефакта (`id_artefactinstance`) либо, в «шаблонном» режиме
  (`CreateFromArtefactID`), описание типа артефакта (`id_artefact`).
  Поля: `artefactscopeID` (см. §6.4), `artefacttypeID`, `artefactprobabilityID`
  (определяет «размер»/редкость, см. §6.3), `artefactpower` (используется
  как «стазис»/вес артефакта, `getStazis()`), `artifactscore` (очки в
  археологический рейтинг игрока, всегда `> 0` по `assert`), `destroyable`
  (можно ли уничтожить через `UA_DropArtefact`), `quest` (квестовый ли
  артефакт).
- **`DE_ArtefactAttributes`** (`DE/DE_ArtefactAttributes.class.php`) —
  бонусы артефакта, тот же паттерн типизированных атрибутов
  (`typename`/`attributename` → `value`), что и у соты/расы; источник —
  таблица `ds_artefacthasbonus`.
- **`DE_CombHasArtefact`** (`DE/DE_CombHasArtefact.class.php`) — факт
  нахождения инстанса артефакта **в хранилище (депо)** конкретной соты
  (таблица `ds_combhasartefact`); отдельная сущность от «прикреплён к
  зданию» (`ds_artefactattachedbuilding`/`ds_artefactattachedcomb`/
  `ds_artefactattachedcomplex`/`ds_artefactattachedalliance` — по одной
  таблице на каждый scope, см. §6.4).

### 6.2 Отправка археологической экспедиции — `UA_ArcheologyTeam`

(`ds/classes/UA/UA_ArcheologyTeam.class.php`)

Параметры команды игрока: `dest` (тип клетки-цели — `CENTRAL`/`NEUTRAL`/
домашняя часть и т.п., резолвится в `id_celltype` через `ds_celltype`),
`sendtype` (`singl`/пусто → `all` — отправить архов пачками до
`countgroup`, либо всех разом до полного исчерпания), `count` (сколько
археологов в группе), `time` (на сколько ходов, **валидация: `1..12`
включительно** — жёсткий хардкод в коде,
`UA_ArcheologyTeam.class.php:58`), `size` (минимальный желаемый размер
находимых артефактов — фильтр, см. §6.3), `countgroup` (только для
`profi_game`-режима — сколько отдельных групп отправить одной командой).

**Проверки перед отправкой** (`checkAction`, строки 80-118):

- если запрошено больше археологов, чем есть на соте — **отправляется
  столько, сколько есть** (не отказ, а автоматическое урезание);
- если археологов 0 — команда не создаётся, но выполняется «облегчённый»
  рефреш соты (`exitwrefresh`);
- для `dest ∈ {CENTRAL, NEUTRAL}` — обязательно открытые Центральные Ворота;
- **лимит числа одновременных экспедиций** игрока:
  `DS_Archeology::getCountArcheologyTeam(uiid) + countgroups > worldSettings.ARCHEOLOGICAL_TEAM_LIMIT`
  → отказ с сообщением `ARCHEOLOGICAL_TEAM_LIMIT`;
- **лимит артефактов на соте**:
  `DS_Archeology::getCountArefacts(comb) > worldSettings.MAX_COMB_ARTEFACT_COUNT`
  → отказ с сообщением `ARTEFACT_ON_COMB_LIMIT` (эта же проверка **повторно**
  выполняется при завершении экспедиции — см. §6.3, «нашли, но девать
  некуда»).

**Отправка одной группы** (`_sendTeam`, строки 268-320): создаётся событие
типа `SEND_ARCHEOLOGY_TEAM` со временем окончания
`now + worldSettings.TURN_TIME * 60 * timeToSearch`, запись в
`ds_archeologicalteam` (`id_comb, teamnumber, id_celltype, starttime, endtime,
amount, id_eventqueue, size`), из `CURRENT_AMOUNT.ARCHEOLOGIST` вычитается
отправленное количество и добавляется в `AWAY_AMOUNT.ARCHEOLOGIST`.

**Player-facing ориентиры количества археологов в экспедиции** (источник:
`ds/help/?name=INFO_GAME_MAIN`, раздел 3.1 «Количество археологов в группе
на экспедицию» — текст официальной справки для игроков, не значение из
кода/БД, но напрямую поясняет, как реально используется параметр `count` из
`UA_ArcheologyTeam` выше): справка рекомендует отправлять 2000–3000
археологов за раз в домашнюю соту (несколькими группами, если археологов
больше), 5000–15000 — в центральную и не менее 15000 — в нейтральную;
итоговый ориентир — порядка 25 000 археологов на соте одновременно для
«относительно успешных» поисков. Текст прямо оговаривает, что сама формула
поиска (`GL_ArtefactSearch::isFound`, см. §6.3 ниже и
[mechanics-races-world-ranking.md, §6.4](mechanics-races-world-ranking.md))
**всегда рандомна**, и эти числа — не механический порог в коде, а
эмпирическая рекомендация игроков/администрации. Отдельно справка называет
жёсткое число «300 экспедиций на всех сотах» — по всей видимости, широко
распространённое значение модификатора мира, который в коде проверяется как
`ARCHEOLOGICAL_TEAM_LIMIT` (см. проверки `checkAction` выше), но само число
300 нигде не хардкожено в PHP — это настройка per-world, справка лишь
описывает типичный дефолт.

**Институт археологии** — здание, которое собственно даёт возможность вести
поиск (детальный разбор построек — вне области этого документа, см.
`mechanics-buildings.md`); по той же справке, один институт вмещает 500
археологов (тренируются за кадериум), и после постройки **первого**
института игроку единоразово дарят 50 археологов бесплатно. Пока
Центральные Ворота закрыты, экспедиции возможны только в домашнюю соту (за
Локальными и Сотовыми артефактами, см. §6.9 ниже); после открытия ЦВ
становятся доступны экспедиции в центральную (за Военными) и нейтральную
(за Комплексными и Альянсовыми) соту.

### 6.3 Обработка возврата экспедиции — `EH_ArcheologyTeam`

(`ds/classes/EH/EH_ArcheologyTeam.class.php:31-80`)

```
handleEvent(eqid):
    получить параметры события
    if событие ещё не наступило по времени рефреша: return

    _updateArcheologistCount()      # возврат археологов на соту, см. ниже
    _getArchBonus()                  # DS_Archeology::getRaceBonus(raceID) — расовый бонус к археологии (BONUS_RELATIVE/ARCHEOLOGY)
    _questSearch()                   # поиск сезонных/квестовых юнитов, отдельная механика (см. ниже)
    _artefactSearch()                # основной поиск артефактов, см. ниже

    if найдено что-то (scores != 0):
        Ranking(userInstance).deltaAtefactRanking(scores)   # археологический рейтинг игрока

    setTrash()  # событие отработано
```

**Возврат археологов** (`_updateArcheologistCount`, строки 169-199): убирает
из `AWAY_AMOUNT.ARCHEOLOGIST`, прибавляет в `CURRENT_AMOUNT.ARCHEOLOGIST`, но
**с обрезкой по `MAX_AMOUNT.ARCHEOLOGIST`** — если на соте институт снесли
или лимит уменьшился, пока команда была в пути, «лишние» археологи **молча
теряются** (`eventparams["CountOfArcheologist"] = max - current` перед
прибавлением).

**Поиск артефакта — вероятность находки**, `GL_ArtefactSearch::isFound`
(`GL/GL_ArtefactSearch.class.php:9-37`):

```
endProbability = scopeProbability * (archCount + archCount * raceBonus) * searchTime
endProbability *= 1000
endProbability *= worldSettings.ARTEFACTS_PROBABILITY_BOOST     # мировой множитель шансов
endProbability = min(endProbability, 900)                        # жёсткий кап — не выше 90% (900 из 1000)

roll = randomInt(1, 1000)
found = roll <= endProbability
```

Вызывается **отдельно для каждого «скоупа» артефактов**, доступного данному
типу клетки (`DS_Archeology::getArtefactScopeByCellType(destination)`,
`_artefactSearch`, строки 207-244) — то есть за одну экспедицию проверяется
несколько независимых бросков (по одному на каждый `id_artefactscope`,
привязанный к этому `id_celltype` через `ds_artefactscope.id_celltype`), и
поиск останавливается **при первом же** результате `found == 2`
(«нашли, но места на соте нет» — жёсткий стоп всего цикла по скоупам,
`break`); при `found == 1` («нашли и есть куда положить») — накопление
продолжается по следующим скоупам этой же клетки, то есть **за одну
экспедицию потенциально можно найти несколько артефактов** (по одному на
каждый подходящий скоуп клетки).

**Ограничение вместимости** (`_searchForArtefactScope`, строки 246-263):
после успешного `isFound`, **до** определения конкретного артефакта,
повторно проверяется `DS_Archeology::getCountArefacts(comb) > worldSettings.MAX_COMB_ARTEFACT_COUNT`
— если хранилище уже полно, устанавливается `found = 2` («нашли, но
девать некуда») **без создания артефакта** — то есть находка формально
пропадает, игроку об этом просто сообщается статистикой в письме (см.
`MC_CompilerArcheologyTeam`).

**Определение размера («редкости»)** —
`DS_Archeology::getArtefactSizeByScopeAndScience($id_scope, $science)`
(`ds/classes/DS/DS_Archeology.class.php:47-77`):

```
prob_rows = ds_artefactprobability JOIN ds_artefactscopehasprobability WHERE id_artefactscope = id_scope
                                    AND (min_chance != 0 OR max_chance != 0)
            ORDER BY id_artefactprobability

lastMin = 0; lastMax = 0
for row in prob_rows:
    lastMin += row.min_chance
    lastMax += row.max_chance
    # линейная интерполяция кумулятивного порога между "минимальным" и "максимальным" распределением
    # в зависимости от уровня науки TRADE (0..100)
    probs[row.id_artefactprobability] = lastMin * (1 - science/100) + lastMax * science/100

roll = random() * probs[последний_id_artefactprobability]     # случайное число от 0 до максимума кумулятивного порога
for (size, threshold) in probs (в порядке добавления):
    if roll <= threshold: return size
```

`science` — уровень научной ветки `TRADE` игрока
(`DS_Science::GetScienceBranchLevel('TRADE', uiid)`) — то есть **чем выше
прокачана наука `TRADE`, тем сильнее распределение размера артефакта
сдвигается от `min_chance` к `max_chance`** (предположительно, `max_chance`
даёт более высокий шанс на крупные/редкие артефакты — сама семантика
`min`/`max` в коде не расшифрована текстом, это вывод по контексту
названия столбцов).

Если найденный размер (`id_prefix`) **меньше** заявленного игроком минимума
(`size`, поле `MinSearchArtSize` из параметров события) — находка **тоже
аннулируется** (`found = 0`), **кроме** артефактов скоупа `QUEST` (они не
попадают под фильтр минимального размера). Специальный костыль:
`if($this->id_prefix==7 && $this->id_scope==5) $this->id_prefix = 6;` с
комментарием `// Temporary block for GIANT WAR` — временная подмена размера
7→6 для скоупа 5 (**предположение**: связано с ивентом «Giant War», деталей
в коде нет).

> 🆕 **`id_prefix==7` расшифрован живым снимком БД** (источник: БД,
> live-запрос, 2026-09-02, таблица `ds_artefactprobability`, см. §6.9.1).
> Семь «размерных» префиксов, реально участвующих в случайном выборе по
> весам (`MINOR`/`SMALL`/`AVERAGE`/`MAJOR`/`GREAT`/`SUPERIOR`/`GIANT`),
> отсортированы по возрастанию редкости и по возрастанию диапазона
> `rangefrom` на шкале 1-500 в этом самом порядке — `GIANT` идёт седьмым и
> **самый редкий** (вес 2 из 500, диапазон 499-500). Это ровно
> соответствует догадке комментария: `id_prefix==7` = `GIANT`, «понижение»
> до `id_prefix=6` (`SUPERIOR`) для скоупа 5 — это принудительная замена
> **самой редкой** категории на предпоследнюю по редкости конкретно для
> сценария «Giant War» (вероятно, `id_scope=5` — один из боевых/сотовых
> скоупов, где организаторы события не хотели давать игрокам топ-тир
> случайно). Само сопоставление `id_artefactprobability → 1..7` по
> порядку `rangefrom` — **вывод по данным, не подтверждён явным чтением
> значения `id_artefactprobability` из БД** (снимок дал только
> `prefix`/`rangefrom`/`rangeto`/`min_chance`/`max_chance`, без самого
> числового ID).

**Выбор конкретного типа и артефакта**: из подходящих `id_artefacttype` для
данного `(scope, prefix)` выбирается случайный
(`DS_Archeology::getArtefactTypeByScopeAndPrefix`, простой `array_rand`), и
из него определяется `id_artefact`
(`DS_Archeology::getArtefactIdByScopPrefixAndType`). Затем создаётся
инстанс (`DE_Artefact::CreateInDB`) и запись в хранилище соты
(`DE_CombHasArtefact::CreateInDB("{instanceID}_{combID}")`); очки
(`artefactInstance.getScore()`) суммируются в `$this->scores`.

**Квестовый поиск** (`_questSearch`, строки 87-167) — отдельная,
независимая от обычного поиска механика: включается флагом
`ARCHEOLOGY_CAN_FIND_QUEST_UNITS`, базовая вероятность **захардкожена в PHP**
`scopeBaseProbablility = 0.0000005` (а не взята из БД!), домножается на
модификатор мира `ARCHEOLOGY_FIND_QUEST_UNITS_PROBABLILITY` через тот же
`GL_ArtefactSearch::isFound`. При успехе — `rand(1,100)` в диапазоне
`[76, 88]` определяет находку одного из двух артефактов
(`COMMON_ICE_TREE1`/`COMMON_ICE_TREE2`, судя по названию — новогодний
сезонный контент, `lifetime = 0` месяцев). Это выглядит как остаток
конкретного новогоднего ивента, а не общая архитектурная механика —
**предположение**.

**Батчинг результатов писем** — `MC_CompilerArcheologyTeam`
(`MC/MC_CompilerArcheologyTeam.class.php`): накапливает результаты всех
экспедиций одного рефреша (может вернуться несколько групп одновременно) по
соте и типу клетки, группирует найденные артефакты по названию с счётчиком
(`Название (x3)`), и формирует **одно** агрегированное системное сообщение
`ARCHEOLOGY_TEAM_RETURN` на комбинацию (сота, тип клетки) вместо потока
отдельных писем на каждую группу; отдельно — сообщение
`ARCHEOLOGY_TEAM_DONT_HAVE_PLACE`, если хоть одна находка пропала из-за
переполненного хранилища.

### 6.4 Применение эффекта артефакта — скоупы (`ds_artefactscope`)

Артефакт «работает», только когда он **прикреплён** к зданию через
`UA_AttachBuildingArtefact` (`ds/classes/UA/UA_AttachBuildingArtefact.class.php`,
метод `performAction`, `switch($this->scopename)`,
строки 267-447 — прочитаны). Значение `scopename` — атрибут артефакта,
определяющий, куда и как накатывается его бонус:

| `scopename` | Что происходит при прикреплении | Хранение связи |
|---|---|---|
| `SPHERE` | Бонус (все типы атрибутов артефакта) применяется **напрямую** дельтой к атрибутам соты (`combAttributes.deltaAttribute`) — но **только если здание не в апгрейде** | не сохраняется отдельно — бонус «впечатан» в саму соту |
| `BUILDING` | То же самое (прямая дельта в атрибуты соты), тоже с пропуском при апгрейде здания | не сохраняется отдельно |
| `COMB` | Прямая дельта в атрибуты соты **плюс** запись связи в `ds_artefactattachedcomb (id_comb, id_artefact, id_attachedbuilding)` | `ds_artefactattachedcomb` |
| `LOVE` | Аналогично `SPHERE`/`BUILDING` — прямая дельта, пропуск при апгрейде | не сохраняется отдельно |
| `COMBCOMPLEX` | **Только** запись связи в `ds_artefactattachedcomplex (id_combcomplex, id_artefact, id_attachedbuilding)`, без прямой дельты в атрибуты — бонус пересчитывается динамически (см. `DE_CombProductionBonus`, §2.3) | `ds_artefactattachedcomplex` |
| `ALLIANCE` | Требует непустого альянса игрока (иначе отказ `NOT_IN_ALLIANCE`); **только** запись в `ds_artefactattachedalliance (id_artefact, id_alliance, id_attachedbuilding)`, без прямой дельты | `ds_artefactattachedalliance` |
| `WAR` | Военные (боевые) артефакты для военной базы: если сота — захватываемая колония (`getIsCapture()`), проверяется лимит `MAX_WAR_ARTEFACT_SLOTS_ON_COLONIY` (если `>0`) на число уже прикреплённых к этой военной базе артефактов; затем запись в `ds_artefactattachedcomb` (та же таблица, что и `COMB`, без прямой дельты в этом случае) | `ds_artefactattachedcomb` |

**Наблюдение**: `SPHERE`/`BUILDING`/`LOVE` не сохраняют факт прикрепления
отдельной строкой (только эффект-дельту), в отличие от `COMB`/`WAR`
(таблица `ds_artefactattachedcomb`) и `COMBCOMPLEX`/`ALLIANCE` — это
объясняет, зачем нужен отдельный класс `DE_CombHasArtefact` (артефакт «в
депо», ещё не прикреплён) отдельно от «прикреплённого» состояния: до
прикрепления артефакт просто лежит в `ds_combhasartefact`, при прикреплении
выбывает оттуда (не подтверждено явным `DELETE` в прочитанном фрагменте —
**предположение** по аналогии с симметричным флоу detach) и либо
преобразуется в постоянную прибавку к атрибутам соты (`SPHERE`/`BUILDING`/
`LOVE`/`COMB`), либо в динамически суммируемую связь (`COMBCOMPLEX`/
`ALLIANCE`/`WAR`).

**Последующая сверка/починка бонусов от `COMB`-скоупа** — «патчер»
`GP_CombArtefactBonus` (`ds/classes/GP/GP_CombArtefactBonus.class.php:19-73`,
идентичный по архитектуре `GP_CombTurnProduction`, §1.3): пересчитывает
сумму бонусов активных зданий с прикреплёнными `COMB`-артефактами из
`ds_artefactattachedcomb` и сверяет с текущим значением атрибута соты,
исправляя расхождение. Это подтверждает, что для `COMB`-скоупа бонус **в
норме кэшируется прямо в атрибутах соты** (см. таблицу выше), а патчер —
инструмент фоновой проверки целостности этого кэша (аналогично §1.3/§2.3
для производства). Аналогичный патчер для слотов —
`GP_CombArtefactSlots.class.php:19-58` — сверяет максимальное число слотов
под артефакты (`MAX_AMOUNT/ARTEFACT_SLOTS`, атрибут `id=17`) с суммой
`ARTEFACT_SLOTS` активных зданий соты.

### 6.5 Военные артефакты и армия — `GL_CombArmyArtefactBonus`

(`GL/GL_CombArmyArtefactBonus.class.php:5-36`) — определяет, **чью** соту
(и, соответственно, чьи прикреплённые `WAR`-артефакты) учитывать для бонуса
конкретной армии, в зависимости от её текущего состояния:

| Состояние армии (`armyAction.type`) | Чья сота даёт бонус |
|---|---|
| `IN_BATTLE` (уже ввязалась в бой на чужой соте) | `army.getBaseComb()` — родная сота |
| `IN_DEFEND` (стоит в обороне на какой-то соте) | `army.getCurentComb()` — текущая (не обязательно родная) сота |
| `MOVE_TO_ATTACK` (на подлёте, вот-вот вступит в бой) | `army.getBaseComb()` — родная сота |
| не в действии (дома) | `army.getBaseComb()` |
| прочие/`MOVE_FROM` | по умолчанию `army.getBaseComb()` (для `MOVE_FROM` в код добавлено отладочное логирование неожиданного состояния — похоже, разработчики сами не были до конца уверены в этом случае) |

Вывод: боевые (`WAR`) артефакты военной базы усиливают армию **либо когда
она физически стоит на этой соте (в обороне)**, **либо когда она в неё
"логически принадлежит"** (`baseComb`) во всех остальных состояниях,
включая уже идущий бой в атаке — то есть артефакты родной военной базы
продолжают действовать на атакующую армию даже вдали от дома, пока армия не
меняет `baseComb`.

`DC_MilitaryBaseArtefacts` (`DC/DC_MilitaryBaseArtefacts.class.php`) —
типизированная коллекция артефактов, прикреплённых к конкретному зданию
военной базы, с двумя «наборами» (`collection` — 1 или 2, закодировано в
битовой арифметике `id << 1`), считывается через
`DS_Artefact::GetArtefactsByBuildAttachedID` — **предположение**: два набора,
видимо, соответствуют разным «слотам»/вкладкам артефактов военной базы
(например, атака/защита), деталей в этом фрагменте не видно.

### 6.6 Уничтожение артефакта и компенсация — `UA_DropArtefact`

(`ds/classes/UA/UA_DropArtefact.class.php`) — команда «выбросить артефакт из
хранилища» (`ds_combhasartefact`), доступна только для `destroyable`-типов
и только если в мире не выключена настройка `CAN_DROP_ARTS`.

**Компенсация ресурсами** — `DS_Artefact::GetDropCompensation($worldId, $artefactInstanceID)`
(`DS/DS_Artefact.class.php:527-573`):

```
type = имя атрибута первого бонуса артефакта (ds_artefacthasbonus → attributename)
if type ∉ {PRODIUM, KADERIUM, FOOD, STASIS_ENERGY, NANO_CRYSTAL, ATTACK, DEFENCE, HITPOINTS, DAMAGE}:
    вернуть пусто (компенсации нет)

if type ∈ {ATTACK, DEFENCE, HITPOINTS, DAMAGE}:
    type = PRODIUM         # боевые бонусы компенсируются продиумом

resource_mult = {PRODIUM: 1, KADERIUM: 5, FOOD: 10, STASIS_ENERGY: 10, NANO_CRYSTAL: 2.5}   # захардкожено в PHP

probability_percent = artefactprobability.rangeto - artefactprobability.rangefrom   # ширина "размерного" диапазона артефакта
base_probability     = artefactscope.baseprobability                                 # базовая вероятность его скоупа

if probability_percent > 0 and base_probability > 0:
    modifier = worldSettings.ARTEFACT_DROP_COMPENSATION
    if modifier > 0:
        compensation = round(resource_mult[type] / (probability_percent * base_probability * modifier))
        # добавляется на соту через deltaAttribute(CURRENT_AMOUNT, type, +compensation)
```

Чем **реже** артефакт (меньше `probability_percent * base_probability`), тем
**больше** компенсация — обратная зависимость от вероятности находки, то
есть игра компенсирует пропорционально «редкости» уничтожаемого предмета.
Множители `resource_mult` — единственные захардкоженные в PHP-коде (не в
БД) числовые константы экономики артефактов, встреченные в разборе.

**Подтверждение player-facing описанием** (источник:
`https://destinysphere.ru/?p=artefact_destruction`, «Компенсация ресурсами
за удаление артефактов»): справка портала описывает эту механику игроку тем
же текстом, что фактически делает код выше: *«артефакт будет разобран на те
ресурсы, на которые он даёт бонус. Военные артефакты всегда будут разобраны
на продиум. В зависимости от размера и типа артефакта будет начислено разное
количество ресурса»* — дословное совпадение с формулой `GetDropCompensation`
(тип ресурса = атрибут первого бонуса артефакта, боевые бонусы → PRODIUM,
итоговое количество зависит от `probability_percent`/`base_probability`,
то есть от размера и скоупа артефакта). Страница со справкой предлагает
выбор конкретного мира из списка (Сфера Парусов, Сфера Эридана, Сфера
Кентавра, Сфера Ориона-Ворона-Феникса, Сфера Ориона, Сфера Змееносца, Сфера
Водолея-Льва, Сфера Стрельца, Чёрная дыра, Терра Нова, Сфера Волка, Сфера
Сириуса, Сфера Гончих Псов) для отображения конкретных чисел компенсации —
то есть `worldSettings.ARTEFACT_DROP_COMPENSATION` (и, видимо, конкретные
проявления `resource_mult`) действительно настраиваются или хотя бы
отображаются по-разному для каждого мира, что согласуется с кодом.
**Конкретные числовые примеры компенсации по отдельным мирам в это
документирование не попали**: снятая копия страницы зафиксирована без
выбора конкретного мира из выпадающего списка и показывает только
заглушку «Для данного мира при удалении артефактов компенсация не
производится» — для получения реальных чисел нужно было выбрать мир на
живом портале, что не было сделано при сборе исходных страниц.

### 6.7 Покупка артефактов за игровую валюту — `UA_BuyArtefacts`

(`ds/classes/UA/UA_BuyArtefacts.class.php`) — это **игровой кабинетный**
способ купить артефакт за GC/SC (отличается от `SMS_Artefacts.php`/
`BG_Artefact.class.php`, которые про оплату **настоящими деньгами** через
SMS/биллинг — та часть уже разобрана в `billing-and-monetization.md`).

```
для каждого запрошенного артефакта (id, type='silver'|'gold', count, life):
    artInfo = DS_Artefact::getArtInfo(id, world)        # тянет price{life}{sc|gc} из ds_artefactprice
    цена = artInfo["price{life}{sc|gc}"] * count
    накопить в ts['sc'] / ts['gc']

if суммарная стоимость > баланса SC или GC пользователя: отказ (алерт "недостаточно денег")
else:
    для каждого артефакта:
        DS_Artefact::AddArtefactToComb(id, life, combID, count)   # count раз вызывает SQL-функцию add_artefact_to_comb(comb, artefact, lifetime)
        UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(...)  # списание денег с аккаунта
```

Прайс хранится в таблице `ds_artefactprice(id_artefact, id_world, price6gc,
price6sc, price12gc, price12sc)` (`DS_Artefact.class.php:859-874`,
`964-971`) — то есть артефакты за игровую валюту продаются в **двух
вариантах срока жизни** (6 и 12 месяцев, судя по именам полей — предположение
по названию, явного текста «месяцы» в этом фрагменте нет, но согласуется с
UI-параметром `life`), каждый с отдельной ценой в золоте и в серебре.
Куда именно кладётся купленный артефакт — вычисляется автоматически: первая
не-колониальная (`getCombRing()->getNumber() != 4`) сота игрока.

### 6.8 Логирование биржевых сделок с артефактами — `ArtTradeLogger`

(`ArtTradeLogger.class.php:19-103`, лежит вне подкаталогов, в корне
`ds/classes/`) — простой построчный логгер в таблицу `ds_tradeartloger`
(`logdate, id_artefactinstance, id_saler, id_salercomb, id_customer,
id_customercomb, type, msg`), с флагом `activeMode` (если `false` — лог не
пишется физически, только копится в памяти текстом через `msg()`) — то
есть это, судя по всему, диагностический/отладочный логгер, включаемый
точечно, а не постоянно работающий аудит всех сделок (в отличие от общей
истории торгов `DS_Trade::LogSell`/`ME_TradeAddHistory`, которая **не**
опциональна). Где именно вызывается `ArtTradeLogger` — в прочитанных файлах
торговли артефактами (`ME_TradeExecLotBay`, `ME_TradeArtefactInstancesOnTradeLotWork`)
явного вызова не встречено; **открытый вопрос**, из какого именно места
кода он используется.

### 6.9 Категории и размеры артефактов — player-facing номенклатура (источник: справочные страницы портала)

Официальные справочные страницы сайта (`https://destinysphere.ru/?p=art_zd`
— «Артефакты Здания», `?p=art_s` — «Артефакты Соты», `?p=art_k` — «Артефакты
Комплекса», `?p=art_a` — «Артефакты Альянса», плюс текстовое описание
категорий в `ds/help/?name=INFO_GAME_MAIN`, раздел 3.2 «Какие артефакты
бывают и что делать с найденным артефактом») дают игроку номенклатуру
категорий, которая в коде выражена через `scopename` артефакта (§6.4 выше).
Соответствие между player-facing названием и `scopename` подтверждено
по-разному для разных категорий:

| Категория (справка) | Где искать / куда вставляется (справка) | Тип бонуса | `scopename` в коде |
|---|---|---|---|
| **Локальные** | Домашняя сота; здания-производители | абсолютный, к производству одного ресурса | предположительно `BUILDING` — прямая дельта к атрибутам соты без сохранения привязки к конкретному зданию (§6.4), что согласуется с тем, что абсолютный ресурсный бонус не зависит от того, в какое именно здание он вставлен |
| **Сотовые** | Домашняя сота; Башня Артефактов | процент от общего производства ресурса **на соте** | предположительно `COMB` — единственный из «процентных» скоупов, который и пишет прямую дельту в атрибуты соты, и хранит привязку к конкретному зданию (`ds_artefactattachedcomb`), что соответствует «вставлен в конкретную Башню Артефактов» |
| **Комплексные** | Нейтральная сота; Башня Артефактов; бонус на все 6 сот комплекса | процент | **`COMBCOMPLEX`** — название и механика совпадают буквально: код не пишет прямую дельту в атрибуты соты, а пересчитывает бонус динамически через `DS_Artefact::GetCombAlianceAndComplexAttribute` (§2.3), что и даёт эффект «на весь комплекс» |
| **Альянсовые** | Нейтральная сота; Башня Артефактов; бонус на все соты альянса | процент | **`ALLIANCE`** — совпадение по названию и по проверке `NOT_IN_ALLIANCE` при попытке прикрепить без альянса (§6.4) |
| **Военные** | Центральная сота; Военная База | разные бонусы к силе армии | **`WAR`** — совпадение подтверждено ещё и §6.6: боевые атрибуты (`ATTACK`/`DEFENCE`/`HITPOINTS`/`DAMAGE`) при разборе всегда компенсируются продиумом, ровно как описано в справке про удаление («военные артефакты всегда будут разобраны на продиум») |
| **Сферы** | Нельзя найти — только купить или получить за турнир/квест; здания-производители; «очень большой» бонус | абсолютный, к производству одного ресурса | **`SPHERE`** — совпадение имени буквальное; по применению бонуса (прямая дельта, пропуск при апгрейде здания) ведёт себя как `BUILDING`, отличаясь способом получения и масштабом бонуса |
| **Серебряные/Золотые** | Нельзя найти — только купить или получить за турнир/квест; Башня Артефактов; бонус выше обычных Сотовых | процент | отдельного `scopename` не найдено — вероятно, это те же артефакты `COMB`-скоупа, но с иным `id_artefactprobability`/`id_artefacttype`, чем обычная размерная линейка; подтверждается кодом покупки `UA_BuyArtefacts` (§6.7), где параметр `type` явно принимает значения `'silver'`/`'gold'` |
| **Временные** | любая из категорий выше может быть временной (пример из справки — 180 дней); обычно продаются в биллинге, но есть миры, где **все** артефакты (в т.ч. найденные) временные (пример из справки — мир «Единорог») | — | соответствует мировой настройке `ALL_ARTEFACTS_ARE_TEMPORARY` (упомянута по имени в [mechanics-races-world-ranking.md, §2.4](mechanics-races-world-ranking.md), не разбиралась по коду в этом документе); срок жизни как параметр знаком по §6.7 (там же — конкретные 6/12 месяцев для покупных Серебряных/Золотых) |

Обратите внимание: соответствие `BUILDING`/`COMB` ↔ Локальные/Сотовые —
**предположение** (наименования и механики не противоречат гипотезе, но
прямого текстового подтверждения в коде, что «COMB» называется именно
Сотовым, нет); `SPHERE`/`COMBCOMPLEX`/`ALLIANCE`/`WAR` — куда надёжнее, так
как имя скоупа дословно совпадает с русским термином, либо поведение
(пересчёт по альянсу/комплексу, требование альянса, продиумная компенсация
военных) уникально описывает именно эту категорию.

**Шесть размеров**, общие для Локальных/Сотовых/Комплексных/Альянсовых:
крохотный, малый, средний, крупный, большой, огромный. Внутри каждого
размера — 7 «типов» на ресурс, например для Продиума: плазмы, высокого
давления, сверхпроводимости, ядерного синтеза, квантовой оптики,
сенсорики, термодинамики. По данным справочных таблиц значение бонуса
растёт почти линейно и по типу, и по размеру внутри одного ресурса
(Продиум, Локальные: «плазмы» — от +1 на «крохотном» до +36 на «огромном»,
«термодинамики» — от +7 до +42, с постоянным шагом +7 между размерами и
небольшим фиксированным сдвигом между типами) — **предположение**: похоже
на чисто косметическую нумерацию 7 «типов» одного ресурса (разные
названия/иконки при близких по балансу значениях), а не 7 механически
разных эффектов; прямого текстового подтверждения в коде для этой гипотезы
нет.

**Масштаб бонуса заметно разный между базовыми ресурсами** на одинаковом
размере: у Локальных артефактов «огромного» размера (тип №1 каждого
ресурса) — Продиум +36, Кадериум +110, Нанокристаллы +72.5, Энергия
(`STASIS_ENERGY`) +185, Еда +185. Кадериум и особенно Энергия/Еда
масштабированы заметно выше Продиума и Нанокристаллов — это качественно
согласуется с идеей разных `EXCHANGERATE` ресурсов в «стазис» (§1.4 этого
документа): чем «дешевле» единица ресурса, тем больше номинальных единиц
даёт артефакт сопоставимой редкости, чтобы примерно выровнять
стазис-эквивалент бонуса. Прямой формулы, которая бы это подтверждала в
коде, не найдено — это наблюдение по данным справки, а не факт из
`ds/classes`.

**Процентная линейка Сотовые : Комплексные : Альянсовые ≈ 10 : 5 : 1** —
для одного и того же типа/размера ресурса разница между тремя «процентными»
категориями оказывается на удивление точным соотношением: например,
Продиум-«плазма» на «огромном» размере — Сотовые +3.6%, Комплексные +1.8%,
Альянсовые +0.36%; Кадериум-«сварка» на «огромном» — Сотовые +11%,
Комплексные +5.5%, Альянсовые +1.1%. Соотношение 10:5:1 воспроизводится и
на других размерах/типах, проверенных при сборе этого материала — похоже
на намеренный дизайнерский выбор (чем шире область действия артефакта —
одна сота / 6 сот комплекса / все соты альянса, — тем меньше его удельный
эффект на одну сот), а не совпадение, но это наблюдение по справочным
таблицам, не формула, найденная в PHP-коде.

Отдельная линейка — **Сферы**: только 3 размера (могучая/великая/
колоссальная, не 6), очень большой абсолютный бонус: Сфера Терминуса
(Продиум) +80/+120/+200, Сфера Саниториума (Кадериум) +400/+600/+1000,
Сфера Неомортана (Нанокристаллы) +200/+300/+500, Сфера Энервитрума
(Энергия) +800/+1200/+2000, Сфера Виталое (Еда) +800/+1200/+2000
(`art_zd`). **Расхождение внутри самой справки сайта**: текстовое описание
категорий (`ds/help/?name=INFO_GAME_MAIN`, §3.2) утверждает, что Сферы дают
«бонус до 1000 к производству соответствующего ресурса», но таблица
конкретных Сфер (`art_zd`) показывает значения вплоть до **2000** (Энергия,
Еда) — то есть один player-facing источник противоречит другому; таблица
конкретных артефактов выглядит более надёжной (это прямая справочная
таблица значений, а не общее текстовое описание), но само расхождение
стоит иметь в виду при переносе баланса в ремастер.

> 🆕 **Расхождение «до 1000» vs «до 2000» разрешено живым снимком БД**
> (источник: БД, live-запрос, 2026-09-02, точные строки `ds_artefact` для
> префиксов `MIGHTY`/`OUTSTANDING`/`SUPREME` — это, судя по значениям и
> количеству тиров, и есть машинные имена «могучая»/«великая»/«колоссальная»
> из справки, приведённых в этом же порядке возрастания редкости):
>
> | `artefactname` (шаблон) | `prefix` | Продиум | Кадериум | Нанокристаллы | Еда | Энергия |
> |---|---|---:|---:|---:|---:|---:|
> | `*_SPHERE_*_EGG` | `MIGHTY` (могучая) | 80 | 400 | 200 | 800 | 800 |
> | `*_SPHERE_*_EGG` | `OUTSTANDING` (великая) | 120 | 600 | 300 | 1 200 | 1 200 |
> | `*_SPHERE_*_EGG` | `SUPREME` (колоссальная) | 200 | 1 000 | 500 | 2 000 | 2 000 |
>
> Числа **дословно совпадают** с таблицей `art_zd`, приведённой абзацем
> выше — независимое подтверждение, что справочная таблица сайта точна.
> Это и разрешает расхождение с текстовым FAQ: **оба источника были
> правы, просто описывали разные ресурсы, не уточняя это явно**. Портальное
> «до 2000» — это максимум по Еде/Энергии на топовом тире `SUPREME`.
> FAQ-шное «до 1000» — это максимум по Кадериуму на том же топовом тире
> `SUPREME` (`1000`), а не по Еде. Бонус Сферы, иными словами,
> **масштабируется вниз для более «дорогих» по стазис-эквиваленту
> ресурсов** — та же логика §1.4/масштабирования по `EXCHANGERATE`, что
> уже отмечена выше для обычных (не-сферных) артефактов: Еда/Энергия
> получают номинально самый большой бонус (2000 на `SUPREME`), Кадериум —
> вдвое меньше (1000), Нанокристаллы — ещё вдвое меньше (500), Продиум —
> самый маленький номинально (200), при том же относительном соотношении
> 1:1 между Едой и Энергией и 4:2:1:0.4 между Едой/Кадериумом/Нано/Продиумом
> на каждом из трёх тиров. Живой снимок также подтвердил два «одиночных»
> артефакта, упомянутых в конце этого раздела как исторические — `GOLD_SPHERE_MOBILEPHONE`
> (`GOLD`, +250 еды) и `GOLD_SPHERE_MOUSE` (`GOLD`, +150 еды) — это и есть
> «Телефон Игромира» (+250 к Еде) и «Мышь Игромира» (+150 к Еде) из
> `art_zd`, только с машинным префиксом `GOLD`, а не `MIGHTY`/`OUTSTANDING`/
> `SUPREME` — то есть промо-артефакты фестиваля используют отдельный,
> не размерный префикс, что согласуется с их статусом одиночных
> исторических предметов, а не элементов обычной линейки.

Отдельно на странице `art_s` («Соты») зафиксированы два узких player-facing
подраздела, не покрытые кодовым разбором в этом документе:

- **Сотовые Квестовые Артефакты** — три тира вместо шести размеров
  (простой/серебряный/золотой), например «Путь Геккона» (Продиум) +5%/+7%/
  +10%, «Сила Прогресса» (Кадериум) +12%/+15%/+20%, «Магия Кристалла»
  (Нанокристаллы) +7%/+10%/+15%; золотой тир «Силы Прогресса» (+20%
  Кадериум) в точности совпадает с примером «+20% кадериум» из общего
  текстового описания категории «Серебряные/Золотые» в
  `ds/help/?name=INFO_GAME_MAIN` — то есть именно квестовые артефакты и есть
  наглядный пример этой категории, а не отдельная восьмая категория.
- **Сотовые Спонсорские Артефакты Игромира-2008** — пять одиночных (без
  размерных тиров) артефактов, привязанных к реальному игровому фестивалю
  «Игромир-2008» (Продиумный +12.5%, Кадериумный +25%, Кристаллический
  +17.7%, Энергетический +30%, Питательный +30%) — историческая деталь
  промо-раздачи, не общая механика; в `art_zd` аналогично встречаются
  «Мышь Игромира» (+150 к Еде) и «Телефон Игромира» (+250 к Еде) в разделе
  Сфер.

### 6.9.1 Веса редкости `ds_artefactprobability` — живой снимок БД (источник: БД, live-запрос, 2026-09-02)

Дополняет §6.3 (`DS_Archeology::getArtefactSizeByScopeAndScience`) — полный
дамп таблицы `ds_artefactprobability` на живом мире, 18 строк:

| `prefix` | `rangefrom` | `rangeto` | `min_chance` | `max_chance` |
|---|---:|---:|---:|---:|
| `MINOR` | 1 | 250 | 250 | 250 |
| `SMALL` | 251 | 375 | 125 | 125 |
| `AVERAGE` | 376 | 440 | 65 | 65 |
| `MAJOR` | 441 | 475 | 35 | 35 |
| `GREAT` | 476 | 490 | 15 | 15 |
| `SUPERIOR` | 491 | 498 | 8 | 8 |
| `GIANT` | 499 | 500 | 2 | 2 |
| `PAID` | 91 | 91 | 0 | 0 |
| `SILVER` | 100 | 100 | 0 | 0 |
| `NORMAL` | 100 | 100 | 0 | 0 |
| `UNIQUE` | 100 | 100 | 0 | 0 |
| `GOLD` | 100 | 100 | 0 | 0 |
| `WHITE` | 100 | 100 | 0 | 0 |
| `COMMON` | 100 | 101 | 0 | 0 |
| `MIGHTY` | 100 | 100 | 0 | 0 |
| `REDS` | 101 | 101 | 0 | 0 |
| `OUTSTANDING` | 101 | 101 | 0 | 0 |
| `SUPREME` | 101 | 101 | 0 | 0 |

Строки естественно распадаются на две группы:

- **`MINOR`/`SMALL`/`AVERAGE`/`MAJOR`/`GREAT`/`SUPERIOR`/`GIANT`** — реальная
  возрастающая по редкости шкала, покрывающая всю шкалу `artefactpower`
  1-500 **без пропусков и перекрытий** (250+125+65+35+15+8+2 = 500), с
  весом, убывающим по мере роста редкости. Это ровно те 7 значений
  `id_prefix` (1..7 по возрастанию редкости, судя по порядку диапазонов),
  которые перебирает `getArtefactSizeByScopeAndScience` (§6.3) — включая
  расшифровку `id_prefix==7` = `GIANT` для костыля «Giant War» (см. 🆕 в
  §6.3). Это, судя по всему, и есть 6 «человеческих» размеров из player-facing
  таблицы §6.9 (крохотный…огромный = `MINOR`…`SUPERIOR`) плюс седьмой,
  ещё более редкий тир `GIANT` (0.4% шанс), не упомянутый ни в одной
  найденной справочной странице портала явно как отдельный размер —
  **предположение**, прямого текстового сопоставления «`GIANT`=огромный+1»
  в справке не найдено.
- **`PAID`/`SILVER`/`NORMAL`/`UNIQUE`/`GOLD`/`WHITE`/`COMMON`/`MIGHTY`/
  `REDS`/`OUTSTANDING`/`SUPREME`** — у всех `min_chance = max_chance = 0`
  и вырожденный диапазон (`rangefrom` ≈ `rangeto`, в основном 100-101).
  С нулевым весом эти строки **физически не могут быть выбраны** циклом
  `getArtefactSizeByScopeAndScience` (кумулятивный порог для них равен
  порогу предыдущей строки — шанс попадания в интервал `[lastMin;
  lastMin]` при случайном вещественном числе равен нулю). Это означает,
  что все 11 префиксов этой группы — **не случайно выпадающие категории
  редкости, а категории, назначаемые каким-то другим путём** (вручную
  админом, за квест/турнир, за покупку — само имя `PAID` прямо на это
  намекает, `MIGHTY`/`OUTSTANDING`/`SUPREME` — это, как показано в §6.9
  выше, три тира Сфер, которые действительно **не находятся** археологией,
  а только покупаются/выдаются). **Механизм присвоения этим строкам
  конкретного артефакта не установлен** (не найден код, который читает
  `id_artefactprobability` этой группы напрямую, минуя случайный ролл) —
  зафиксировано как наблюдение по данным, не как факт из PHP-кода.

**Отдельно закрывает часть открытого вопроса §9.4** (природа `min_chance`
vs `max_chance`): на этом живом снимке **`min_chance` всегда равен
`max_chance`** для каждой без исключения строки — то есть линейная
интерполяция `lastMin*(1-science/100) + lastMax*science/100` из §6.3
арифметически **вырождается в константу** (результат не зависит от
`science` вовсе, потому что `lastMin == lastMax`). На этом конкретном
мире уровень науки `TRADE` **не влияет** на распределение размера
найденного артефакта, несмотря на то что код это предусматривает — сам
вопрос «какое из двух распределений лучше для игрока» остаётся
теоретическим (не из чего сделать вывод, если оба числа всегда совпадают),
но практический эффект научной ветки `TRADE` на размер находок, похоже,
**отключён через данные**, а не через код, ровно как в §8.6
[mechanics-research.md](mechanics-research.md) описан похожий паттерн
«функция не выключена в коде, но эффективно не работает на живых данных».

## 7. Внутриигровая валюта (GC/SC)

- **`DE_Money`** (`DE/DE_Money.class.php`) — справочник видов валюты
  (`short_name`, из кода видно как минимум `GC` — золотые кредиты, `SC` —
  серебряные кредиты; полностью повторяет паттерн `DE_CombAttributes`/
  `DE_RaceAttributes` по типизации, но источник — `DS_Misc::GetMoneyByID`,
  отдельная таблица, не `ds_attribute`).
- **Курс обмена — константа `GC_COURSE = 1000`**
  (`old-sphere/ds/config/settings.php:384`, PHP-константа, **не**
  `DE_WorldSettings`/`ds_gamemodifier`, то есть **единая на все миры**, не
  настраивается per-world): **1 золотой кредит (GC) = 1000 серебряных
  кредитов (SC)**.
- **Обмен GC → SC** — `MU_ExecOperationExchange::Exec`
  (`MU/MU_ExecOperationExchange.class.php:7-45`): списывает `amountGC` у
  пользователя (перевод на системный/пустой аккаунт через
  `MU_ExecTransfer`), затем **начисляет** `amountGC * GC_COURSE` в SC тем же
  механизмом (`ExecTransfer` в обратную сторону) — то есть это буквально
  «сжечь GC, начислить SC по фиксированному курсу», операция
  **необратима** (обратного обмена SC→GC в разобранном коде не встречено).
- **Где валюта стыкуется с игровой экономикой сот**:
  1. **Биржа** (§5): категория товара `MONEY` (id=4) позволяет выставлять
     лоты «ресурс/юнит/артефакт за SC или GC»; при продаже **за GC**
     полученная продавцом сумма **автоматически** обменивается в SC сразу
     же (`ME_TradeExecLotBay::_execMoneyTransfer`, §5.8) — то есть **GC на
     руках у продавца от торговли не задерживается**, любая выручка от
     продажи товара за золото немедленно конвертируется в серебро. Налог с
     сделки (§5.6) тоже всегда считается и списывается в SC, даже если
     сделка изначально была в GC (пересчитывается по курсу `GC_COURSE`
     перед вычислением суммы налога).
  2. **Покупка артефактов за игровую валюту** (§6.7, `UA_BuyArtefacts`) —
     прямая трата SC/GC на предмет, попадающий на соту.
  3. **Покупка ресурсов за деньги** (§4, `BG_BuyResources`) — это **не**
     GC/SC, а внешний биллинговый гейт с реальной оплатой (см.
     [billing-and-monetization.md](billing-and-monetization.md)); в
     разобранном коде `BG_BuyResources` **не завязан** на `MU_*`
     денежный движок — ресурсы начисляются напрямую, без прохода через
     баланс SC/GC. Это два **параллельных, не пересекающихся** способа
     получить игровые ресурсы за деньги: (а) прямая покупка ресурсов через
     платёжный гейт, (б) покупка артефактов/премиума за внутриигровую
     валюту, которая сама пополняется через отдельный биллинг
     (`MU_ExecOperationEnterMoney`, не разбирался в этом документе, см.
     `game-engine.md` §3.4 и `billing-and-monetization.md`).
- **Учёт валюты полностью отделён от `ds_combhasresource`** — GC/SC не
  является атрибутом соты (`DE_CombAttributes`), это атрибут **аккаунта
  пользователя** внутри мира, хранящийся и обрабатываемый отдельным
  движком `MU_*` (`MU_EntityOperation`, `MU_SearchOperations::GetAccountData`,
  `MU_ControlExecOperations::ExecOperation`) с собственным логом операций
  (`MU_EntityOperationLogRecord`) и типами действий (`DE_MoneyActionType`,
  замечены имена: `TRADE_PAYMENT`, `EXCHANGE_CREDITS`, `PAY_TRADE_TAX`,
  `MOVE_FROM_RESERV`).

## 8. Сводная таблица модификаторов мира (`DE_WorldSettings`), встреченных в этом разборе

| Модификатор | Где используется | Влияние |
|---|---|---|
| `TURN_TIME` | `ME_TurnCalc`, `ME_CountPeriodLiveUpkeep`, лоты биржи | длительность 1 хода в минутах — базовая единица времени всей экономики |
| `WORKER_GROWTH_RATE` | `ME_TurnCalc::_countWorkersBorn` | доля прироста рабочих от `MAX_AMOUNT` за ход |
| `DISABLE_UPKEEP_DEACTIVATE_BUILDINGS` | `ME_TurnCalc::_countBuildingsUpkeep` | выключает деактивацию зданий при нехватке `STASIS_ENERGY` |
| `DISABLE_UPKEEP_DEATH_HUMANS` | `ME_TurnCalc::_countHumanUpkeep` | выключает гибель людей от голода |
| `DISABLE_UPKEEP_DEATH_UNITS` | `ME_TurnCalc::_countArmyUpkeep` | выключает гибель юнитов от апкипа (ресурсы всё равно списываются до 0) |
| `LIVE_UPKEEP_UNITS_START` | `GL_LiveUpkeep` | базовый множитель апкипа армии (если нет `ds_dynamic_upkeep`) |
| `KILL_UNITS_RETURN_PART` | `GL_ReturnUnitResourcesAfterDie` | доля стоимости производства юнита, возвращаемая при его гибели/отмене стройки |
| `BUY_RESOURCE_FOR_MONEY_ALLOWED` | `BG_BuyResources` | разрешена ли покупка ресурсов за деньги в мире (глобальный флаг) |
| `BUY_RESOURCE_<RES>_LIMIT` (×5) | `BG_BuyResources` | накопительный лимит покупки конкретного ресурса на пользователя в мире |
| `LOCAL_ARTEFACT_OLD_STYLE` | `GL_CountCombProductionOfResource` | переключатель старой/новой формулы применения бонуса производства |
| `BONUS_{PREMIUM\|NON_PREMIUM}_{RESOURCE}_{RACE}` | `GL_CountCombProductionOfResource::CountWorldBonus` | относительный бонус производства ресурса для расы/премиум-статуса |
| `BONUS_COLONY_{RESOURCE}` | `GL_CountCombProductionOfResource::CountColonyBonus` | относительный бонус производства для захватываемых колоний |
| `RESEARCH_BASE_BONUS` | `DE_CombProductionBonus` | база формулы бонуса производства от уровня науки |
| `TRADE_USE_TRANDS` | `GL_TradeCountMinAndMaxPrices`, панель биржи | включает ценовые коридоры вокруг рыночного курса |
| `MIN_BID_STEP` | `GL_TradeCountMinAndMaxPrices` | минимальный шаг повышения ставки на аукционе, % |
| `TRADE_USE_TAX` | `GL_TradeCountLotTaxSumm` | включает налог на сделки |
| `TRADE_PREMIUM_TAX_DELTA` | `GL_TradeCountLotTaxSumm` | поправка налога для премиум-аккаунтов |
| `TRADE_PENALTY` | `ME_TradeExecLotReturn` | штраф (доля) при возврате непроданных ресурсов с биржи |
| `TRADE_MAX_TURN` | `PN_TradePanel` | максимальная длительность лота в ходах |
| `TRADE_USE_PERSONAL_TRADES` | `GL_TradeCanSellByThisPriceAndScope` | разрешены ли персональные (именные) лоты |
| `TRADE_ANONYMOUS_SELL` | `PN_TradePanel` (флаг для клиента) | разрешена ли анонимная продажа |
| `TRADING_ALLIANCE_ONLY_FROM_INTERRING_CONNECTOR` | `GL_CombAllowedTradeScopes` | ограничивает альянсовую торговлю требованием межкольцевого коннектора |
| `DISABLE_COMPLEX_TRADING` / `DISABLE_RING_TRADING` / `DISABLE_INTERRING_TRADING` / `DISABLE_ALLIANCE_TRADING` | `GL_TradeCanSellByThisPriceAndScope` | точечное отключение конкретного скоупа торговли |
| `TRADE_RVR_ALLOW_RESOURCE` / `_UNITS` / `_ARTEFACTS` | `GL_TradeCanByThisLotByThisPrice::_rvrTest` | разрешает межрасовую торговлю категорией в RvR-мирах |
| `TRADE_NON_RVR_INTERRACE_ALLOW_UNITS` | `GL_TradeCanByThisLotByThisPrice::_rvrTest` | разрешает межрасовую торговлю юнитами вне RvR-миров |
| `MAX_COMB_ARTEFACT_COUNT` | `UA_ArcheologyTeam`, `EH_ArcheologyTeam` | лимит артефактов в хранилище одной соты |
| `ARCHEOLOGICAL_TEAM_LIMIT` | `UA_ArcheologyTeam` | лимит одновременных экспедиций игрока |
| `ARTEFACTS_PROBABILITY_BOOST` | `GL_ArtefactSearch` | глобальный множитель вероятности находки артефакта (кап 90%) |
| `ARCHEOLOGY_CAN_FIND_QUEST_UNITS` | `EH_ArcheologyTeam::_questSearch` | включает поиск сезонных/квестовых предметов |
| `ARCHEOLOGY_FIND_QUEST_UNITS_PROBABLILITY` | `EH_ArcheologyTeam::_questSearch` | множитель вероятности квестовой находки |
| `ARTEFACT_DROP_COMPENSATION` | `DS_Artefact::GetDropCompensation` | множитель компенсации за уничтожение артефакта |
| `MAX_WAR_ARTEFACT_SLOTS_ON_COLONIY` | `UA_AttachBuildingArtefact` (scope `WAR`) | лимит боевых артефактов на военной базе колонии (0 = без лимита) |
| `CAN_DROP_ARTS` | `UA_DropArtefact` | глобально разрешает/запрещает уничтожение артефактов |
| `LEAGUE_ENABLED` | `ME_DeltaResourcesOnComb::_checkLevelLeague` | включает систему лиг (побочно завязана на добавление юнитов) |

Значения всех этих модификаторов — per-world данные в таблице
`ds_gamemodifier`, в исходном коде не хардкожены (кроме отмеченных явно PHP-
констант `GC_COURSE`, `resource_mult` в компенсации за дроп артефакта,
`iterationsCount=5` в апкипе армии, диапазона времени экспедиции `1..12` и
вероятности `0.0000005` в квестовом поиске).

## 9. Открытые вопросы

1. **Нехватка не-энергетических ресурсов для зданий** (§2.4): код
   `ME_TurnCalc::_countBuildingsUpkeep` при дефиците `KADERIUM`/
   `NANO_CRYSTAL`/`FOOD`/`PRODIUM` просто обнуляет остаток и выходит из
   метода целиком (`return true` внутри цикла), не деактивируя ничего и не
   проверяя остальные ресурсы в этом же вызове. Нужно уточнить на реальных
   данных БД (`ds_buildinghasattribute`, `TURN_UPKEEP`), заполнен ли у
   зданий апкип чем-то, кроме `STASIS_ENERGY`, — иначе этот код мёртв по
   данным, а не только по логике.
2. **`GL_TradeCountMinSellAmount`** (`GL/GL_TradeCountMinSellAmount.class.php`)
   — метод `Count` читает `sellStazis`/`priceStazis`, но ничего с ними не
   делает и ничего не возвращает (`return` отсутствует). Похоже на
   неоконченную реализацию/заглушку. Не найдено вызовов этого класса в
   прочитанном коде — возможно, минимальный объём продажи в реальности **не
   применяется** нигде, либо применяется через другой, не найденный в этом
   разборе путь.
3. **`GL_TradeCalcWeight`** — сам файл содержит комментарий «на данный
   момент класс не используется» (`GL_TradeCalcWeight.class.php:4-5`) —
   подтверждено, но не проверено окончательно отсутствием любых вызовов по
   всему дереву (не делался полный `grep` по всему `ds/`, только по
   `ds/classes/`).
4. ~~**Природа `min_chance`/`max_chance`** в `ds_artefactprobability`
   (§6.3) — по коду очевидно, что уровень науки `TRADE` сдвигает
   распределение размера артефакта от `min_chance` к `max_chance`, но какое
   из двух распределений «лучше» для игрока (даёт более крупные артефакты)
   — вывод по названию столбцов и общей логике «наука должна помогать», не
   подтверждён явным текстом/данными.~~

   > 🆕 **Переоткрыт в другую сторону живым снимком БД (2026-09-02),
   > см. §6.9.1.** На живом мире `min_chance` буквально равен `max_chance`
   > во всех 18 строках — то есть вопрос «что лучше» неотвечаем на этих
   > данных (интерполяция вырождается в константу), но заодно выясняется,
   > что **практического эффекта науки `TRADE` на размер находок на этом
   > мире нет вообще**, вне зависимости от того, как понимать `min`/`max`.
   > Не установлено, это общая настройка всех миров по умолчанию, или
   > `min_chance ≠ max_chance` где-то всё же встречается (например, на
   > мирах с включённым premium-ускорением науки) — снимок покрывает
   > только один мир на одну дату.
5. **`ArtTradeLogger`** — не найдено ни одного места вызова в прочитанных
   файлах торговли артефактами; не установлено, используется ли он вообще в
   актуальном коде или это ещё один «мёртвый», но подключаемый компонент.
6. **`DS_Artefact` (989 строк) и `DS_Archeology` (412 строк)** прочитаны
   только фрагментами, относящимися к разобранным механикам (цены,
   компенсация, вероятности, добавление в хранилище). Остальные методы
   (например, полный список операций с `ds_trade_lot_artefactinstances`,
   административные функции восстановления артефактов, используемые в
   `TMP_RestoreDeletedArtefacts`) не разбирались — не исключено, что там
   есть дополнительные правила/ограничения, не отражённые в этом документе.
7. **`UA_DetachBuildingArtefact.class.php`** (378 строк) не читался — открыт
   вопрос, снимается ли бонус `SPHERE`/`BUILDING`/`COMB`/`LOVE`-скоупа
   зеркально (обратной дельтой) при откреплении, и что происходит с
   артефактом дальше (возврат в `ds_combhasartefact`, предположительно, по
   аналогии с `DE_CombHasArtefact::CreateInDB`, но не подтверждено чтением
   кода).
8. **Точное происхождение `LOCAL_ARTEFACT_OLD_STYLE`** и разница в
   долгосрочном балансе между двумя ветками формулы производства (§2.3) —
   не установлено, для каких именно миров/версий каждая ветка
   исторически предназначалась.
9. **`ds_dynamic_upkeep`** (§2.6) — таблица динамического мультипликатора
   апкипа армии по «звёздам» существует не во всех мирах (код явно
   проверяет её наличие через `pg_tables`); нет данных о том, в каких
   версиях/мирах она включена и какие в ней значения — для ремастера нужно
   решить, реализовывать ли этот динамический вариант или ограничиться
   фиксированным `LIVE_UPKEEP_UNITS_START`.
10. **Комиссия/бронирование ставок на аукционе** — из кода видно, что при
    ставке на аукцион деньги покупателя резервируются (`MOVE_FROM_RESERV`
    при возврате, `ME_TradeExecReturnUserBid` при перебитии ставки), но сам
    момент резервирования (`ME_TradeExecLotBay` для аукциона не считает
    апкип юнитов заново — «апкип на юниты снимается при ставке», согласно
    комментарию в коде) находится в файле, который не запрашивался явно в
    задании и не читался (вероятно, отдельный `UA_*`/`ME_*` класс «сделать
    ставку»); точный флоу резервирования ставки в этом документе не описан.
