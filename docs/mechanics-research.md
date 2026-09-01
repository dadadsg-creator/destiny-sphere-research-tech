# Механика исследований / науки (Sciences / Research)

> Источник: `old-sphere` (PHP, read-only). Кодировка файлов — Windows-1251 (читалось через `iconv -f CP1251 -t UTF-8`).
> Документ создаётся "с нуля" — тема ранее не разбиралась ни в одном другом файле `docs/`.
> Все ссылки вида `file.php:строка` указывают на `old-sphere/<путь>`.

## TL;DR

Наука в игре организована как **7 независимых веток (branch)**, каждая из которых представляет собой линейную цепочку уровней (1, 2, 3, …). Наука — это сущность `ds_science` (одна строка = один уровень одной ветки, например условно «KADERIUM, level 3»). Игрок в любой момент времени может исследовать **только одну науку на весь аккаунт** (не на соту — глобально), тратя на это отряд юнитов-учёных (`SCIENTIST`), находящихся на любых его сотах. Стоимость исследования (`researchcosts`) — заранее заданное число в БД для каждого уровня; формула роста стоимости от уровня к уровню **в коде не обнаружена** — это данные, а не вычисляемая формула. Время исследования вычисляется из стоимости и числа учёных с расовым бонусом. Завершённая наука выдаёт способности (`ds_userhasability`), очки в научный рейтинг игрока и (для 5 из 7 веток) постоянный относительный бонус к производству соответствующего ресурса. Отдельного класса вида `DE_ScienceByRace` **нет** — расовые ограничения по науке реализованы через связующую таблицу `ds_sciencecanberesearchedbyrace`, проверяемую прямым SQL-запросом.

---

## 1. Где живёт код науки (карта классов)

Отдельного префикса вида `DE_Science*` для всей подсистемы нет — классы разбросаны по стандартным префиксам DS/UA/EH/PN/SU/UC/DU/MP, как и остальная игровая логика:

| Класс / файл | Роль |
|---|---|
| `ds/classes/DS/DS_Science.class.php` | DB-фасад сущности науки: бонусы, добавление науки игроку, очки рейтинга, привязка способностей |
| `ds/classes/UA/UA_Research.class.php` | Базовый класс команд науки — общие проверки (здания, зависимости науки, доступность расе/миру, «уже исследует») |
| `ds/classes/UA/UA_NewResearch.class.php` | Команда игрока «начать исследование» (extends `UA_Research`) |
| `ds/classes/UA/UA_CancelResearch.class.php` | Команда игрока «отменить исследование» (extends `UA_Research`) |
| `ds/classes/EH/EH_NewResearch.class.php` | Обработчик события `NEW_RESEARCH` — тикает прогресс, пересчитывает время при изменении числа учёных, завершает науку |
| `ds/classes/UC/UC_ExecByScienceAcceleration.class.php` | Платный сервис (донат) — ускорение текущего исследования за игровую валюту |
| `ds/classes/PN/PN_ResearchPanel.class.php` | UI-панель вкладки «Наука» на соте (XML для клиента): список доступных уровней, прогресс, бонусы, тренировка учёных |
| `ds/classes/SU/SU_ScienceTree.class.php` | Публичный XML-API дерева наук (кэш 48 часов) — список веток + список построек по расе с их научными требованиями |
| `ds/classes/DU/DU_DeleteScience.class.php` | Утилита очистки: удаляет все `ds_userhasscience` игрока (при удалении инстанса) |
| `ds/classes/MP/MP_DBScienceResearchWithoutEvent.class.php` | Патч целостности БД: чинит записи «наука в процессе исследования», у которых потерялось событие |
| `ds/addScience.php` | Отладочный/служебный скрипт (без проверки прав в коде, форма ввода имени пользователя) для ручной выдачи науки — по сути прообраз того, что позже стало `Admin/controls/GameWorlds/Add/Science` |
| `ds/science_tree.php` | Тонкая обёртка, вызывающая `SU_ScienceTree::Get()` — **игровая** (движковая) страница дерева наук |

**НЕ относится к науке** (ложные срабатывания по слову "Research"/"science"), несмотря на совпадение имён:
- `ds/classes/EH/EH_SpyMoveToResearch.class.php`, `EH_SpyMoveFromResearch.class.php` — обработчики полёта зондов-разведчиков «в разведку» (англ. reconnaissance переведено как "Research"/"разведка"). Это часть шпионской/боевой механики, не науки. Подробно см. будущий документ о шпионаже/бою.
- `ds/classes/DS/DS_Archeology.class.php`, `DS_Terraforming.class.php` — отдельные подсистемы (археологические команды, терраформинг), которые лишь **используют** науку как требование/модификатор (см. раздел 8), но не являются её частью.

## 2. Модель данных (реконструкция по SQL-запросам в коде)

Прямого доступа к SQL-дампу схемы БД не было (в репозитории только PHP), поэтому таблицы и связи ниже реконструированы по запросам в коде.

| Таблица | Назначение | Где встречается |
|---|---|---|
| `ds_sciencebranch` (`id_branch`, `branchname`, `id_localizedname`, `outorder`) | 7 веток науки | `SU_ScienceTree.class.php:143-155`, `ds/language/R/science_tree.lang` |
| `ds_science` (`id_science`, `id_branch`, `level`, `sciencename`, `researchcosts`, `science_score`, `id_localizedname`) | Уровень науки внутри ветки. `sciencename` кодирует ветку+уровень (см. §3) | `UA_Research.class.php:100-113` (getScienceID), `DS_Science.class.php` |
| `ds_userhasscience` (`id_userinstance`, `id_science`, `inresearch`, `starttime`, `endtime`) | Изученные/изучаемые науки игрока | повсеместно |
| `ds_userhassciencebindeventqueue` (`id_science`, `id_userinstance`, `id_eventqueue`) | Привязка текущего исследования к событию очереди | `UA_NewResearch.class.php:187-195` |
| `ds_scienceneedsbuilding` (`id_science`, `id_building`) | Требуемые постройки на соте для старта исследования данного уровня | `UA_Research.class.php:38-58` |
| `ds_scienceneedsscience` (`id_science_1`, `id_science_2`) | Граф зависимостей "наука → нужна другая наука" (не обязательно свой же предыдущий уровень — формально может ссылаться на любую другую науку/ветку) | `UA_Research.class.php:66-89` |
| `ds_sciencecanberesearchedbyrace` (`id_science`, `id_race`) | Доступность конкретного уровня науки конкретной расе | `PN_ResearchPanel.class.php:14-30` |
| `ds_sciencehasability` (`id_science`, `id_ability`) → `ds_userhasability` | Способности, выдаваемые по завершении науки | `DS_Science.class.php:189-201` (AddScinceToUser) |
| `ds_sciencebranchattributename` (`id_sciencebranch`, `id_attributename`) → `ds_resource(exchangerate)` | Связь ветки науки с ресурсом/атрибутом, на который она даёт производственный бонус | `DS_Science.class.php:6-40` (GetScienceBonus) |
| `ds_disabledscience` (`id_world`, `id_science`) | Наука отключена в конкретном мире (по конкретному уровню) | `UA_Research.class.php:180-201`; админка `GameWorlds/DisableScience` |
| `ds_disabledsciencebranch` (`id_world`, `id_branch`) | Целая ветка отключена в конкретном мире | `UA_Research.class.php:157-178` |
| `ds_science_acceleration_price` (`id_science`, `price`) | Цена ускорения (за 1 час) для конкретного уровня, донат-сервис | `DS_PaymentService.class.php:141-161` |
| `ds_eventacceleration` (`id_eventqueue`) | Отметка «это событие исследования уже ускорено платно» | `DS_Science.class.php:206-222` (IsScienceAccelerated) |
| `ds_racehasattribute` → `BONUS_RELATIVE`/`SCIENCE` | Расовый бонус к скорости исследования (доля, напр. 0.1 = +10%) | `DS_Science.class.php:246-269` (GetScienceBonusByRace) |
| `ds_unitneedsscience` | Требования науки для тренировки юнита (не постройки!) | `UA_TrainWarUnit.class.php:303-320`, `ME_AllowTrainWarUnit.class.php:66-84` |
| `ds_terraformingneedscience` | Требования науки для терраформинга | `DS_Terraforming.class.php:74-93, 188-207` |
| `ds_buildingneedsscience` | Требования науки для строительства/апгрейда здания | `buildings.class.php:93-96`, `PN_AllowBuildings.class.php:110-136`, `DS_Building.class.php:246-270` |

## 3. Дерево наук: 7 веток

Список веток (константный, встречается идентично в `ds/language/R/science_tree.lang`, `PN_BuildingInfo.class.php:465-473`, `sys_dyn_components/techtree.php`):

| branchname (внутр. код) | Русское название | Ресурс/эффект, к которому привязана (см. §8.1) |
|---|---|---|
| `KADERIUM` | Кадериум | производство ресурса KADERIUM |
| `NANO_CRYSTAL` | Нано-кристаллы | производство ресурса NANO_CRYSTAL |
| `STASIS_ENERGY` | Статическая энергия | производство ресурса STASIS_ENERGY |
| `FOOD` | Еда | производство ресурса FOOD |
| `PRODIUM` | Продиум | производство ресурса PRODIUM |
| `TRADE` | Торговля | торговая способность (проверка сейчас отключена, см. §8.4) + неожиданно влияет на размер археологических находок (см. §8.6, вероятная аномалия) |
| `SPYING` | Шпионаж | максимальное число зондов-разведчиков на соте (§8.5) |

Внутри каждой ветки — произвольное число уровней (1, 2, 3…), каждый — отдельная строка `ds_science`. **Точное число уровней на ветку и конкретные названия/стоимости конкретных наук в коде не найдены** — это наполнение таблицы `ds_science`, а не логика; SQL-дампов с данными в репозитории нет (см. «Открытые вопросы»).

### 3.1 Формат `sciencename`

Подтверждено в двух независимых местах:
- `WorldUtils.class.php:505` — `... from ds_science where sciencename ~ '_1$'` (регулярка "заканчивается на `_1`") — выдача науки 1-го уровня новому персонажу.
- `PN_ResearchPanel.class.php:344` — `int4(substring(s.sciencename from '[[:digit:]]+')) as curlev` — извлечение номера уровня как числа из имени.

Отсюда: `sciencename` = `<BRANCHNAME>_<level>`, например `KADERIUM_1`, `KADERIUM_2`, `TRADE_5` и т.д. (конкретные примеры не встречены буквально в коде — реконструкция по regex-паттернам, помечено как вывод/предположение).

### 3.2 Стартовые науки персонажа

При создании инстанса игрока (`WorldUtils::addNewInstance`, `WorldUtils.class.php:505`):
```sql
insert into ds_userhasscience (id_userinstance, id_science, inresearch)
select $uiid, id_science, false from ds_science where sciencename ~ '_1$'
```
То есть игроку сразу выдаются **все науки 1-го уровня во всех 7 ветках**, сразу изученные (`inresearch = false`), без затрат времени. Это подтверждает и то, что уже упоминалось в `docs/game-engine.md:477`.

## 4. Требования для начала исследования следующего уровня

Проверки выполняются в `UA_Research::checkAction` (общий базовый класс) и продублированы (те же SQL) в `PN_ResearchPanel::isScienceAllow` для UI-отображения доступности:

1. **Нет активного исследования у игрока вообще** (`UA_Research::isUserHasResearch`, `UA_Research.class.php:275-292`) — глобальная проверка по всему аккаунту (`id_userinstance`), не по соте.
2. **Нет исследования на конкретной соте** (`isCombHasResearch`, `UA_Research.class.php:249-273`) — избыточная проверка (следствие п.1, так как один игрок = одна наука), но выполняется отдельно.
3. **Есть хотя бы 1 учёный** на любой из сот игрока (`UA_NewResearch::checkAction`, `UA_NewResearch.class.php:47-58`) — иначе `errormsg`.
4. **Ветка не отключена в этом мире** (`ds_disabledsciencebranch`, `UA_Research::isBranchDisabled`, `UA_Research.class.php:157-178`).
5. **Конкретный уровень не отключён в этом мире** (`ds_disabledscience`, `UA_Research::isScienceDisabled`, `UA_Research.class.php:180-201`).
6. **Наука ещё не изучена** (`isUserHasScience`, `UA_Research.class.php:203-224`).
7. **Нужные постройки присутствуют именно на той соте**, откуда стартует исследование (`ds_scienceneedsbuilding`, `isNeedsBuildingPresent`, `UA_Research.class.php:38-58`) — важно: здания проверяются **только на этой конкретной соте**, а учёные суммируются **со всех сот игрока** (см. §5). Это осознанное расхождение объёма проверки — зафиксировано как факт, не как предположение.
8. **Все предпосылки-науки изучены** (`ds_scienceneedsscience`, `isScienceEnough`, `UA_Research.class.php:66-89`) — граф произвольный, формально может требовать науку из другой ветки, а не только предыдущий уровень своей.
9. **(только для отображения в панели, но не в самой команде UA_NewResearch)** доступность расе — `ds_sciencecanberesearchedbyrace` через `PN_ResearchPanel::isScienceAllowRace` (`PN_ResearchPanel.class.php:14-30`). *Замечание:* в `UA_NewResearch`/`UA_Research` явной проверки по расе не найдено — расовая фильтрация видна только в UI-панели. Помечаю как открытый вопрос (см. ниже) — возможно, отсутствие данной науки для расы в БД делает шаг 8 (или прямой запрос по id) невозможным физически, либо проверка расы в команде отсутствует намеренно/по недосмотру.
10. **Спецрежимы блокируют действие**: режим отпуска/вакации (`GL_IsUserInVocationMode`) и активный силовой щит (`DE_PowerShild`) — `UA_NewResearch.class.php:99-110`.

## 5. Стоимость и время исследования

### 5.1 Стоимость (`researchcosts`)

Хранится как готовое число в `ds_science.researchcosts` для каждого уровня. **Формула роста стоимости от уровня к уровню в коде не обнаружена** — стоимость не вычисляется программно, а читается напрямую (`UA_Research::getScienceID`, `UA_Research.class.php:100-113`). Значит рост стоимости — это данные наполнения БД (возможно, геометрическая или иная прогрессия, заданная вручную/скриптом заполнения при создании мира), которых нет в PHP-коде репозитория.

### 5.2 Время исследования при старте (`UA_NewResearch::getEventEndTime`, `UA_NewResearch.class.php:196-241`)

```
raceBonus  = DE_RaceAttributes(race).BONUS_RELATIVE.SCIENCE   // доля, 0 если нет атрибута
scientists = Σ CURRENT_AMOUNT.SCIENTIST по всем сотам игрока   // UA_NewResearch.class.php:47-58
delta_sec  = round( TURN_TIME_min * 60 * researchcosts / (scientists + scientists * raceBonus) )
           = round( TURN_TIME_min * 60 * researchcosts / (scientists * (1 + raceBonus)) )
```
(`UA_NewResearch.class.php:229`). `TURN_TIME` — настройка мира (минут в 1 «ход»/тик).

Если получившийся `endtime` (unix-timestamp) `>= 2147000000` (около предела 32-битного времени, ~2038 год) — исследование не стартует, игроку показывается системное сообщение `UA_NEW_RESEARCH_ERROR_NOT_ENOUGH_SCIENTISTS` (`UA_NewResearch.class.php:139-145`), то есть слишком малое число учёных трактуется как "исследование никогда не закончится".

### 5.3 Пересчёт «на лету» при каждом тике события (`EH_NewResearch::handleEvent`, `EH_NewResearch.class.php`)

Каждый обработчик события `NEW_RESEARCH` сверяет текущее число учёных с зафиксированным в параметрах события (`ScientistsCount`). Если оно изменилось:
```
science_bonus = DS_Science::GetScienceBonusByRace(raceID)     // та же расовая доля
newscientists = ceil( newscientistscount + newscientistscount*science_bonus )
newendtime    = round( eventStartTime + TURN_TIME_min*60*TotalResearchCosts / newscientists )
```
(`EH_NewResearch.class.php:131-132`). Здесь округление числа учёных — `ceil`, в отличие от старта, где деление идёт "как есть" без явного round числа учёных (расхождение зафиксировано как факт из кода, не унифицировано).

Особые случаи при пересчёте:
- Если новое число учёных = 0 → исследование **отменяется** (`DS_Science::CansecSciense`), сообщение `CANCEL_RESEARCH_MESS1` (`EH_NewResearch.class.php:117-125`).
- Если наука **платно ускорена** (`DS_PaymentService::IsScienceAcceleraded`) — пересчёт времени по числу учёных **пропускается полностью**, просто показывается прогресс (`EH_NewResearch.class.php:92-110`).
- Если пересчитанное время всё равно "за пределами разумного" (`>= 2147000000`) → исследование отменяется с сообщением `EH_NEW_RESEARCH_ERROR_NOT_ENOUGH_SCIENTISTS` (`EH_NewResearch.class.php:142-152`).
- Иначе — либо наука сразу засчитывается завершённой (если `newendtime` уже в прошлом), либо у события `DE_EventQueue` просто обновляется `endtime`, а также обновляется `ds_userhasscience.endtime` и атрибут события `ScientistsCount`.

### 5.4 Завершение исследования

При наступлении `endtime` (`EH_NewResearch::handleEvent`, ветка `_isTimeToHandle()`, `EH_NewResearch.class.php:52-66`):
1. `DS_Science::AddScinceToUser(uiid, scienceId)` (`DS_Science.class.php:172-201`) — помечает `inresearch=false`, вставляет строки в `ds_userhasability` по всем способностям науки (`ds_sciencehasability`).
2. `DS_Science::GetScienceScore(scienceId)` → `DE_Ranking::deltaScienceRanking(score)` — прибавка к научному рейтингу игрока (см. §8.7).
3. Системное сообщение `FINISH_RESEARCH_MESS` всем участникам события, событие помечается trash.

### 5.5 Отмена исследования (`UA_CancelResearch`, весь файл)

Удаляет привязку события (`ds_userhassciencebindeventqueue`) и саму запись `ds_userhasscience`, помечает событие очереди как `isTrash=true`. **Возврата затраченного времени/ресурсов не предусмотрено** — прогресс полностью теряется (наука не требует явных ресурсных затрат сверх времени учёных — самих ресурсов при старте исследования не списывается, судя по `UA_NewResearch::performAction`, который расходов ресурсов не производит вовсе, в отличие от построек/юнитов).

### 5.6 Платное ускорение (донат) — `UC_ExecByScienceAcceleration.class.php`

- Доступно только если текущая исследуемая наука имеет уровень `>= SCIENCE_ACCELERATION_MIN_LEVEL` (настройка мира) и ещё не была ускорена ранее (одно ускорение на одно исследование, флаг `ds_eventacceleration`).
- `SCIENCE_ACCELERATION_MULTIPLIER` (0..1, настройка мира) — доля оставшегося времени, которая "сгорает": `newEnd = oldEnd - multiplier*(oldEnd-now)`.
- Цена = `ds_science_acceleration_price.price` (за конкретную науку) × число полных часов, на которые сократилось время (`ceil((oldEnd-newEnd)/3600)`), валюта — `SC` (серебряные кредиты).
- После оплаты — `DS_PaymentService::SetNewScienceEndDate` + `SetEventAsAccelerated`, списание денег, запись в лог платежей.

### 5.7 Тренировка учёных (`SCIENTIST`)

`SCIENTIST` — юнит как любой другой (не отдельная сущность науки): тренируется через ту же механику, что рабочие (`PN_ResearchPanel::getPanel`, `PN_ResearchPanel.class.php:130-260`), расходует `WORKER`/`FOOD`/`KADERIUM`/`NANO_CRYSTAL`/`CONSTRUCTION_TIME` согласно атрибутам юнита `PRODUCTION_COSTS`. Максимум учёных на соте — атрибут постройки `MAX_AMOUNT.SCIENTIST`. Донат-покупка пакета учёных — сервис `PS_BY_UNIT_SCIENTIIFIC`, лимит `SCIENTIST_BUY_LIMIT` (настройка мира).

## 6. Ограничение параллельности

**Очереди исследований нет.** Проверка `isUserHasResearch` (`UA_Research.class.php:275-292`) жёстко запрещает начать вторую науку, пока не завершена или не отменена первая — причём ограничение действует **на весь аккаунт/инстанс игрока целиком**, а не на отдельную соту (в отличие, например, от построек, которые могут возводиться параллельно на разных сотах). Т.е. у игрока в один момент времени может изучаться максимум 1 наука суммарно по всей империи.

## 7. Расовые различия

Отдельного класса-аналога `DE_BuildingByRace` для науки **не найдено**. Расовая фильтрация реализована двумя механизмами:

1. **`ds_sciencecanberesearchedbyrace`** (`id_science`, `id_race`) — какие конкретные уровни доступны конкретной расе. Проверяется прямым SQL в `PN_ResearchPanel::isScienceAllowRace` (`PN_ResearchPanel.class.php:14-30`) при построении UI. Как отмечено в §4 п.9, в самой команде `UA_NewResearch` эта проверка явно не встречена — открытый вопрос.
2. **`ds_racehasattribute` → `BONUS_RELATIVE`/`SCIENCE`** — расовый множитель скорости исследования (см. формулу §5.2). Конкретные числовые значения по расам (Воранер/Лиенсу/Псолао) в коде не встречены — это данные БД.

## 8. Эффект наук на прочие подсистемы

### 8.1 Производственный бонус ресурсов (5 из 7 веток)

Формула идентична в двух независимых местах — `DE_CombProductionBonus::_importBonuses` (`DE_CombProductionBonus.class.php:64-73`, применяется реально к производству соты) и `DS_Economical::GetResearchBonus` / `PN_ResearchPanel` (для отображения в UI, `PN_ResearchPanel.class.php:307,413,418`):

```
brb  = RESEARCH_BASE_BONUS                         // настройка мира
exch = ds_resource.exchangerate                     // константа ресурса, привязанного к ветке через ds_sciencebranchattributename
level = максимальный изученный уровень ветки (для игрока, GetScienceBonus агрегирует по ветке, id_userinstance)

BONUS_RELATIVE(resource) += (brb / exch) * (level - 1)
```
То есть на уровне 1 бонус = 0 (это стартовая база), каждый следующий уровень добавляет **фиксированный** прирост `brb/exch` (линейный рост, не прогрессия). Бонус суммируется с бонусами от артефактов/зданий в `DE_CombProductionBonus` и применяется как `BONUS_RELATIVE` к тому ресурсу, с которым ветка ассоциирована через `ds_sciencebranchattributename` (INNER JOIN — то есть ветки без записи в этой таблице бонуса не дают: судя по всему, это `TRADE` и `SPYING`, у которых есть другие эффекты — см. ниже; прямого подтверждения через дамп БД нет, вывод по способу JOIN).

### 8.2 Требования для построек

`ds_buildingneedsscience` проверяется:
- при постройке (`UA_ConstructBuilding.class.php:83`, `assert($this->isScienceEnough(...))`),
- при апгрейде (`UA_UpgradeBuilding.class.php:140`),
- в списке доступных построек UI (`PN_AllowBuildings.class.php:110-136`, счётчик "все требуемые науки изучены = кол-во требуемых"),
- в справке по зданию + требуемый уровень по каждой из 7 веток для конкретного апгрейда (`DS_Building::GetScienceLevelForUpgradeBuilding`, `DS_Building.class.php:246-270`, используется в `PN_BuildingInfo.class.php:465-496`).

### 8.3 Требования для юнитов (боевых)

`ds_unitneedsscience` — аналогичная схема "число требуемых наук = число выполненных" — используется в проверке при тренировке юнита: `UA_TrainWarUnit.class.php:303-320` (действующий код) и продублировано в `ME_AllowTrainWarUnit.class.php:66-84`. Есть и закомментированный устаревший вариант в `DS_UnitTrain.class.php:180-235` (мёртвый код, не влияет на рантайм). Это относится и к боевой механике — но сам факт зависимости юнитов от науки — часть модели науки, поэтому зафиксирован здесь; подробности по конкретным юнитам/веткам — в документе о войсках.

### 8.4 Требования для терраформинга

`ds_terraformingneedscience`, та же схема проверки (`count(needed) == count(satisfied)`) — `DS_Terraforming.class.php:74-93` и `:188-207`.

### 8.5 Ветка SPYING → лимит зондов-разведчиков

```
GL_SpyMaxCountZondsOnComb::Get():
  max = SPY_MAX_ZONDS_COUNT_PER_SCIENCE_LEVEL   // настройка мира
  scienceLevel = DS_Science::GetSpyScienceBranchLevel(uiid)   // макс. изученный уровень ветки SPYING
  return max * scienceLevel
```
(`GL_SpyMaxCountZondsOnComb.class.php:7-13`, `DS_Science.class.php:74-97`). Линейная зависимость лимита зондов от уровня ветки SPYING.

### 8.6 Ветка TRADE → торговая способность (отключено) + аномалия с археологией

`DS_Science::GetTradeAbility` (`DS_Science.class.php:127-158`) должна проверять наличие способности через `ds_userhasability`/`ds_trade_ability`, но **функция начинается с `return true;`** (`DS_Science.class.php:128`), весь остальной код после — недостижим (мёртвый код). То есть **на практике проверка торговой способности по науке сейчас всегда проходит**, вне зависимости от изученных наук. Вызывается из `GL_TradeCanSellByThisPriceAndScope::_checkLevel` (`GL_TradeCanSellByThisPriceAndScope.class.php:228`).

Отдельно замечена вероятная аномалия: при определении размера найденного археологического артефакта (`EH_ArcheologyTeam::_searchForArtefactScope`, `EH_ArcheologyTeam.class.php:265-267`) используется именно **уровень ветки TRADE** игрока:
```php
$science = DS_Science::GetScienceBranchLevel('TRADE', $this->uiid);
$size = DS_Archeology::getArtefactSizeByScopeAndScience($id_scope, $science);
```
а внутри `getArtefactSizeByScopeAndScience` (`DS_Archeology.class.php:47-73`) значение трактуется как **проценты 0..100** (`$science / 100`) для интерполяции между `min_chance`/`max_chance` вероятностных таблиц. Уровни веток науки в этой игре обычно небольшие числа (судя по паттерну `_1$`, `_N`), поэтому использование "сырого" уровня ветки как процента выглядит как недосмотр/легаси-копипаста, а не продуманный дизайн. Зафиксировано как факт кода, не как рекомендация к копированию в ремастере — см. «Открытые вопросы».

### 8.7 Влияние на рейтинг игрока

По завершении науки: `DE_Ranking::deltaScienceRanking(science_score)` (`EH_NewResearch.class.php:58-61`, метод — `DE_Ranking.class.php:66-74`) прибавляет `ds_science.science_score` к полю `science_ranking` игрока. Итоговый общий рейтинг/уровень игрока считается в `DS_Ranking::GetAccordanceLevel` (`DS_Ranking.class.php:47-88`):
```
ranking = science_ranking * RANKING_SCIENCE_MULTIPLIER
        + artefact_ranking * artK + building_ranking * buildK + battle_ranking * battleK
```
(`DS_Ranking.class.php:65`, множители — настройки мира). Отдельный админ-раздел `Admin/controls/GameSettings/Ranking/Science` позволяет **экспортировать/импортировать `science_score` по CSV** и запускать пересчёт `science_ranking` всех игроков разом (см. §9).

### 8.8 Заморозка аккаунта (freeze/vacation) — обнаруженная аномалия

`DS_Freez::MoveScienceInTime($UIID, $delta)` (`DS_Freez.class.php:462-476`) должна сдвигать `starttime`/`endtime` текущего исследования игрока на `$delta` секунд (компенсация времени в заморозке), но SQL жёстко использует `WHERE id_userinstance = 1` вместо параметра `$UIID`:
```sql
UPDATE ds_userhasscience SET starttime = starttime + '$delta SECOND'::interval, endtime = endtime + '$delta SECOND'::interval
WHERE id_userinstance = 1
```
(`DS_Freez.class.php:470-474`). Похоже на баг оригинала (переменная `$UIID` объявлена и провалидирована через `assert`, но не используется в самом запросе) — реально сдвигает время только у пользователя с `id_userinstance = 1`, для всех остальных функция не делает ничего полезного. Зафиксировано как найденная аномалия, не как задуманное поведение — в ремастере копировать баг не нужно, но нужно решить, как корректно реализовать перенос времени науки при заморозке.

## 9. Административная сторона (`Admin/controls/`)

**Раздела для создания/редактирования самого дерева наук (веток, уровней, `researchcosts`, зависимостей building/science) не найдено.** Судя по всему, структура таблиц `ds_science`/`ds_sciencebranch`/`ds_scienceneeds*` наполняется напрямую в БД (SQL-скрипты инициализации мира) или через универсальный `Admin/controls/SQL`, а не через специализированный UI. Найденные разделы касаются только **эксплуатации** уже существующего дерева:

| Раздел | Файл | Что делает |
|---|---|---|
| `Admin/controls/GameSettings/Ranking/Science` | `index.php` | Экспорт/импорт CSV `sciencename;level;researchcosts` для поля `science_score` (используется только для рейтинга, не для игровой стоимости!) + кнопка "Пересчитать научную составляющую" (пересчёт `science_ranking` всех игроков по сумме `science_score` изученных наук) |
| `Admin/controls/GameWorlds/Add/Science` | `index.php` | GM-инструмент: выдать/снять конкретному игроку в конкретном мире науку до указанного уровня в указанной ветке (по сути ручной сеттер `ds_userhasscience`, с логированием комментария через `actionLog`). Прерывает текущее активное исследование игрока перед изменением (`endScienceEvent`) |
| `Admin/controls/GameWorlds/DisableScience` | `index.php` | Управление `ds_disabledscience` по мирам — список разрешённых/запрещённых конкретных уровней науки, переключение через простую форму |
| `ds/addScience.php` | — | Более старый/грубый прототип того же GM-функционала выдачи науки (без явных прав доступа в самом файле — вероятно, защищён на уровне веб-сервера/каталога) |

Раздел `TradeAdmin/SciencePrice`, который ожидался по подсказке в задании, **не найден** — реальный путь `Admin/controls/GameSettings/Ranking/Science` (для `science_score`, рейтинг), а цена платного ускорения (`ds_science_acceleration_price`) отдельного админ-раздела в коде не имеет — управляется, видимо, напрямую в БД.

## 10. Портальная (сайтовая) сторона — для полноты, не игровой движок

Как и просили — прочитано, но помечено явно как НЕ часть игрового движка:

- **`ds/science_tree.php`** (`ds/science_tree.php`, весь файл 13 строк) — тонкая обёртка, реально это **игровая** страница (лежит в `ds/`, использует `Main.php`, сессию), вызывает `SU_ScienceTree::Get()`.
- **`ds/classes/SU/SU_ScienceTree.class.php`** (весь файл) — генерирует XML: список из 7 веток с текущим уровнем игрока (заглушка нулей, если нет сессии) + список построек выбранной расы с их требуемым уровнем по каждой из 7 веток и списком построек-предпосылок. Кэшируется на 172800 сек (48 часов) через `DS_Cache`.
- **`sys_dyn_components/techtree.php`** — это старая версия **портальной витрины** (маркетинговый лендинг), полностью статическая: подключает `buildings.php` с **захардкоженными PHP-массивами** данных о зданиях/расах/языках (немецкий/английский), никаких запросов к БД в самом `techtree.php` не обнаружено. Список веток идентичен игровому (`kaderium`, `nano`, `energia`, `eda`, `prodium`, `trade`, `spy`), но конкретные уровни/стоимости не связаны с живой БД — похоже на устаревший артефакт раннего сайта, не отражающий актуальное состояние. **Вывод: этот файл вторичен и не должен считаться источником истины по актуальному дереву наук.**
- **`sys_dyn_components/Units.php`, `Units_NEW.php`** — витрина юнитов/армии для портала (использует `DS_Common`, реальные запросы к БД). **К теме исследований/науки не относится** — это отдельная система (боевые юниты), которая лишь потребляет науку как требование (см. §8.3). Подробности по самим юнитам должны идти в отдельный документ о войсках/юнитах, не здесь.

## 11. Итоговая сводная схема потока (для наглядности)

```
Игрок жмёт "исследовать <BRANCH>_<level+1>" на соте X
        │
        ▼
UA_NewResearch.checkAction():
  ├─ на соте X нет другого исследования?           (избыточно)
  ├─ у игрока (всего аккаунта) нет исследования?    ← ГЛОБАЛЬНЫЙ лимит "1 наука за раз"
  ├─ есть учёные (Σ по всем сотам) > 0 ?
  ├─ ветка/наука не отключена в мире?
  ├─ наука ещё не изучена?
  ├─ нужные постройки есть НА СОТЕ X?               (ds_scienceneedsbuilding)
  └─ все науки-предпосылки изучены?                 (ds_scienceneedsscience, граф произвольный)
        │ (всё ок)
        ▼
UA_NewResearch.performAction():
  delta = TURN_TIME*60 * researchcosts / (Σучёных * (1 + raceBonus_SCIENCE))
  создаётся событие NEW_RESEARCH, ds_userhasscience.inresearch=true
        │
        ▼  (каждый тик, пока не наступит endtime)
EH_NewResearch.handleEvent():
  если Σучёных изменилась И наука НЕ ускорена платно → пересчитать endtime
  если Σучёных == 0 → отменить (CansecSciense)
  если endtime уже прошёл → завершить
        │ (по завершении)
        ▼
DS_Science::AddScinceToUser():
  ds_userhasscience.inresearch=false
  + выдать способности (ds_sciencehasability → ds_userhasability)
  + DE_Ranking::deltaScienceRanking(science_score)
  + (для 5 веток) начиная со след. тика — BONUS_RELATIVE к производству ресурса
    в DE_CombProductionBonus по формуле (RESEARCH_BASE_BONUS/exchangerate)*(level-1)
```

## Открытые вопросы

1. **Точное наполнение `ds_science`/`ds_sciencebranch`** — сколько уровней в каждой из 7 веток, конкретные `sciencename`, `researchcosts`, `science_score` по каждому уровню. В коде это данные БД, не логика; дампа БД в репозитории не нашлось. Без него нельзя восстановить конкретную "кривую" стоимости — только сам факт, что стоимость **не вычисляется формулой**, а задаётся как число на уровень.
2. **Проверка расы (`ds_sciencecanberesearchedbyrace`) в самой команде `UA_NewResearch`** явно не встречена (только в UI-панели `PN_ResearchPanel`). Не выяснено, есть ли она на самом деле где-то ещё (например, косвенно через отсутствие подходящей строки `ds_science` для расы) или это дыра в серверной валидации оригинала. Требует либо дальнейшего поиска в неисследованных вспомогательных классах, либо трактовки как приемлемого поведения (раз расовая фильтрация физически ограничивает видимость через UI).
3. **Значения мировых настроек** (`TURN_TIME`, `RESEARCH_BASE_BONUS`, `SCIENCE_ACCELERATION_MIN_LEVEL`, `SCIENCE_ACCELERATION_MULTIPLIER`, `SPY_MAX_ZONDS_COUNT_PER_SCIENCE_LEVEL`, `RANKING_SCIENCE_MULTIPLIER`, `SCIENTIST_BUY_LIMIT`) — только имена ключей и допустимые диапазоны из `assert()` найдены в коде (например, `SCIENCE_ACCELERATION_MIN_LEVEL` в `[1,149]`, `SCIENCE_ACCELERATION_MULTIPLIER` в `[0,1]`). Конкретные значения по мирам не искались (вероятно, `Admin/controls/GameSettings/GameAttributes` / `GameModifaers`, но это отдельная админ-подсистема, не проверялась в рамках этой задачи).
4. **Связь веток `TRADE`/`SPYING` с `ds_sciencebranchattributename`** — вывод "у них нет производственного бонуса" сделан по способу JOIN (INNER JOIN, отсутствие бонуса при отсутствии строки), не по прямому просмотру данных таблицы. Высокая уверенность, но не 100%-но подтверждено дампом БД.
5. **Использование уровня TRADE в формуле размера археологических находок** (§8.6) — похоже на баг/легаси-копипасту, но не исключено, что это осознанный дизайн ("торговцы лучше оценивают находки"). Пометка ждёт подтверждения от геймдизайна при ремастере.
6. **`DS_Freez::MoveScienceInTime` хардкодит `id_userinstance = 1`** (§8.8) — почти наверняка баг оригинала; в ремастере не копировать, реализовать через параметр.
7. **`DS_Science::GetTradeAbility` всегда возвращает `true`** (§8.6) — отключённая в проде проверка. Решить осознанно, восстанавливать ли гейт в ремастере, или трактовать как окончательный дизайн (наука TRADE не блокирует торговлю).
8. **`Admin/controls/TradeAdmin/SciencePrice`**, упомянутый в исходной наводке задания, физически не существует в дереве каталогов (проверено `find`) — админки для цены ускорения науки нет; сама цена — таблица `ds_science_acceleration_price`, без выделенного UI управления в коде.
