# Справочник схемы БД (`schema.sql`)

> **Что это.** Каталог всех 499 таблиц живой игровой PostgreSQL БД, полученный
> из `pg_dump --schema-only` (сам файл `schema.sql` — **не в git**, см.
> `.gitignore` в корне репозитория; это чувствительная инфраструктурная
> информация, полученная напрямую от администратора проекта, не из
> `old-sphere/`). Дамп содержит только структуру (таблицы/колонки/FK/
> представления/функции/триггеры), **данных в нём почти нет** (2 строки
> INSERT на весь файл).
>
> **Методология и границы точности — читайте перед использованием.** Этот
> документ, в отличие от остальных `docs/*.md`, написан **быстрым
> каталогизирующим проходом**, не построчным анализом с проверкой по
> PHP-коду. Для каждой таблицы указано:
> - **Назначение** — короткая догадка по одному только имени таблицы/колонок
>   за секунды, БЕЗ чтения `old-sphere/`. Это предположение, не факт.
> - **Колонки** — реальные, дословно взятые из `CREATE TABLE` (имена/типы
>   сокращены для компактности: `integer`→`int`, `text`→`txt`,
>   `boolean`→`bool`, `timestamp`→`ts`, `double precision`→`float`),
>   с пометкой `[PK]`/`[FK→таблица]`, где это видно из констрейнтов.
>
> **Если хочешь код-подтверждённую документацию по конкретной таблице —
> её нужно доследовать отдельно** (grep по имени таблицы в `old-sphere/`,
> найти читающий/пишущий PHP-класс, как это уже сделано для десятков
> таблиц в других `mechanics-*.md` — см. таблицу перекрёстных ссылок в
> конце этого документа). Цель этого документа — дать полную карту того,
> что вообще есть в БД, чтобы не гадать вслепую, когда понадобится
> нырнуть в конкретную таблицу.
>
> Часть таблиц уже подробно (код-подтверждённо) разобрана в других
> документах этой сессии — там, где так, вместо колонок стоит отсылка
> «см. `docs/....md`» без повторной каталогизации.
>
> **Важная оговорка (по итогам live-запросов к продакшен-БД,
> 2026-09-02): `schema.sql` — это снимок на момент снятия дампа, не
> гарантированно точное текущее состояние живых игровых БД.** Прямой
> запрос `SELECT COUNT(*)` к живым мирам показал, что таблицы
> **`ds_worldcopy`, `ds_userprofidata2` и `ds_user2`** — все три
> присутствующие в этом дампе как `CREATE TABLE` — **физически не
> существуют** ни на одном из проверенных живых миров (`hell` /
> «Чёрная дыра», `phoenix` / «Сфера Парусов»): запрос падает с
> `ERROR: relation "..." does not exist`, а не просто возвращает `0`
> строк. Это не «код не используется» (как для действительно пустых, но
> существующих таблиц ниже) — это расхождение самой схемы: live-схема
> конкретных игровых БД уже успела разойтись с тем, что описывает
> `schema.sql`. **Вывод: любую ссылку на конкретную таблицу в этом
> документе стоит воспринимать как «была в дампе на момент снятия», а не
> как подтверждённо существующую прямо сейчас** — при заведении зависимости
> на конкретную таблицу в ремастере её стоит перепроверить `SELECT` к
> живой БД, а не полагаться только на `schema.sql`.

## Оглавление по разделам

- [A–C (ability … buildingtype)](#a–c-ability--buildingtype)
- [C–G (buildingsalowedtouserinstance … help_section)](#c–g-buildingsalowedtouserinstance--help_section)
- [I–P (inoutusersstats … protective_field_restore_price)](#i–p-inoutusersstats--protective_field_restore_price)
- [Q–T (quest … trade_history_bid)](#q–t-quest--trade_history_bid)
- [T–U (trade_history_sells … userban)](#t–u-trade_history_sells--userban)
- [U–X (userban … xft)](#u–x-userban--xft)
- [21 представление (views) — рейтинг](#представления-views)
- [Перекрёстные ссылки на код-подтверждённую документацию](#перекрёстные-ссылки)

---

## A–C (ability … buildingtype)

### ds_ability
Назначение: способности/улучшения различных наук (согласно комментарию в дампе).
Колонки: id_ability int [PK], id_localizedname int, id_localizeddescription int, abilityname varchar(50)

### ds_alliance
Назначение: альянсы игроков (название, тег, пароль вступления, мир).
Колонки: id_alliance int [PK], id_religion int, alliancename varchar(50), id_world int, alliancetag varchar(10), joinpassword varchar(30), creationdate ts, members_amount int, id_global_alliance int

### ds_alliance_has_image
Назначение: изображения (гербы/флаги), привязанные к альянсу.
Колонки: id_alliance int NOT NULL [FK→ds_alliance], image_url varchar(250) NOT NULL

### ds_alliance_new_members
Назначение: новые участники, добавленные в глобальный альянс.
Колонки: id_global_alliance int NOT NULL, id_user int NOT NULL, add_date date, comments text

### ds_alliancehasrelation
Назначение: связь между двумя альянсами (дипломатическое отношение).
Колонки: id_alliance_1 int NOT NULL [FK→ds_alliance], id_alliance_2 int NOT NULL [FK→ds_alliance], id_alliancerelation int [FK→ds_alliancerelation]

### ds_allianceranking
Назначение: денормализованный рейтинг альянсов (вероятно, представление/материализованная выгрузка для рейтинга).
Колонки: members bigint, alliancetag varchar(10), id_world int, alliancename varchar(50), totalpoints numeric, averagepoints bigint, id_alliance int

### ds_alliancerelation
Назначение: справочник типов дипломатических отношений между альянсами (война/мир/союз и т.п.).
Колонки: id_alliancerelation int [PK], id_localizedtype int, relationtype varchar(30)

### ds_analizer_premium
Назначение: премиум-подписка на "анализатор" (доп. игровой инструмент) для пользователя.
Колонки: id_user int NOT NULL [PK?/FK→ds_user], active bool, start_date ts(0), end_date ts(0), creation_date ts(0)

### ds_analizer_premium_change
Назначение: журнал изменений премиум-подписки "анализатора" (не установлено детально).
Колонки: id_user int, type varchar(50), date ts(0), days_delta int, end_date_old ts(0), end_date_new ts(0), comments text

### ds_antibot_answers
См. [protocol-client-server.md, §3.2](protocol-client-server.md) (код-подтверждено).

### ds_antibot_botlist
См. [protocol-client-server.md, §3.2](protocol-client-server.md) (код-подтверждено).

### ds_antibot_questions
См. [protocol-client-server.md, §3.2](protocol-client-server.md) (код-подтверждено).

### ds_antibot_stat
См. [protocol-client-server.md, §3.2](protocol-client-server.md) (код-подтверждено).

### ds_antibot_userinstance
См. [protocol-client-server.md, §3.2](protocol-client-server.md) (код-подтверждено).

### ds_archeologicalteam
Назначение: археологические команды, ищущие артефакты в клетках соты (comb).
Колонки: id_comb int NOT NULL [FK→ds_comb], teamnumber smallint NOT NULL, id_celltype int, starttime ts, endtime ts, amount int, id_eventqueue int, size int

### ds_army_reincarnation / ds_army_reincarnation_hystory / ds_army_reincarnation_unit_hystory / ds_army_weight / ds_army_weight_history
См. [mechanics-reincarnation.md, §2](mechanics-reincarnation.md) (код-подтверждено, все 5 таблиц дословно).

### ds_artefact
Назначение: справочник артефактов (тип, мощность, свойства уничтожаемости/бессмертности).
Колонки: id_artefact int [PK], id_localizedname int, id_localizeddescription int, id_artefactscope int [FK→ds_artefactscope], id_artefacttype int [FK→ds_artefacttype], id_artefactprobability int [FK→ds_artefactprobability], artefactname varchar(50), artefactpower int, destroyable bool, quest bool, calc_in_ranking bool, can_be_found bool, immortal bool, artefact_score int

### ds_artefactattachedalliance
Назначение: привязка артефакта к альянсу (артефакт закреплён за альянсом/зданием).
Колонки: id_artefact int [FK→ds_artefact], id_alliance int [FK→ds_alliance], id_artefactattachedalliance int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefactattachedbuilding
Назначение: привязка артефакта к конкретной постройке (attachedbuilding).
Колонки: id_artefact int [FK→ds_artefact], id_attachedbuilding int [FK→ds_attachedbuilding], id_artefactattachedbuilding int [PK], collection smallint NOT NULL

### ds_artefactattachedcomb
Назначение: привязка артефакта к соте (comb).
Колонки: id_comb int [FK→ds_comb], id_artefact int [FK→ds_artefact], id_artefactattachedcomb int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefactattachedcomplex
Назначение: привязка артефакта к комплексу построек (combcomplex).
Колонки: id_artefact int [FK→ds_artefact], id_combcomplex int [FK→ds_combcomplex], id_artefactattachedcomplex int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefactattachedrace
Назначение: привязка артефакта к расе.
Колонки: id_race int [FK→ds_race], id_artefact int [FK→ds_artefact], id_artefactattachedrace int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefactattachedreligion
Назначение: привязка артефакта к религии.
Колонки: id_artefact int [FK→ds_artefact], id_religion int [FK→ds_religion], id_artefactattachedreligion int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefactattachedring
Назначение: привязка артефакта к "кольцу" (ring — географическому кольцу карты).
Колонки: id_artefact int [FK→ds_artefact], id_ring int, id_artefactattachedring int [PK], id_attachedbuilding int [FK→ds_attachedbuilding]

### ds_artefacthasbonus
Назначение: бонусы (атрибуты), которые даёт артефакт.
Колонки: id_artefacthasbonus int [PK], id_artefact int [FK→ds_artefact], id_attribute int [FK→ds_attribute], value real

### ds_artefactinstance
Назначение: экземпляр артефакта (аналог userinstance, но для артефактов).
Колонки: id_artefactinstance int [PK], id_artefact int [FK→ds_artefact], creation_date ts(0), death_time ts, immortal bool, birth_time ts

### ds_artefactprobability
Назначение: диапазоны вероятности/префиксы редкости артефактов.
Колонки: id_artefactprobability int [PK], id_localizedprefix int, rangefrom int NOT NULL, rangeto int NOT NULL, prefix varchar(30), min_chance float NOT NULL, max_chance float NOT NULL

### ds_artefactscope
Назначение: "область" поиска артефактов, привязанная к типу клетки.
Колонки: id_artefactscope int [PK], id_celltype int, id_localizedscope int, scopename varchar(30), baseprobability real

### ds_artefactscopehasprobability
Назначение: связь области поиска артефактов с диапазонами вероятности.
Колонки: id_artefactscope int NOT NULL [FK→ds_artefactscope], id_artefactprobability int NOT NULL [FK→ds_artefactprobability]

### ds_artefactsorttype
Назначение: справочник типов сортировки артефактов (для UI).
Колонки: id_artefactsorttype int [PK], sortname varchar(20) NOT NULL, localizedname int

### ds_artefacttype
Назначение: справочник типов (постфиксов) артефактов по типу ресурса.
Колонки: id_artefacttype int [PK], id_localizedtypename int, typename varchar(50)

### ds_artefacttypeforbuilding
Назначение: связь типа артефакта/области со зданием (какие артефакты применимы к зданию).
Колонки: id_building int NOT NULL [FK→ds_building], id_artefactscope int NOT NULL [FK→ds_artefactscope], id_artefacttype int NOT NULL [FK→ds_artefacttype]

### ds_attachedbuilding
Назначение: экземпляр здания, построенного на конкретной клетке соты (comb) — активность, стройка, деактивация.
Колонки: id_building int [FK→ds_building], id_cell int, id_attachedbuilding int [PK], active bool, underconstruction bool, starttime ts, endtime ts, inactivation bool, busy bool, inupgrade bool, id_comb int [FK→ds_comb]

### ds_attachedbuildingbindeventqueue
Назначение: связь построенного здания с очередью событий (таймер завершения действия).
Колонки: id_attachedbuilding int NOT NULL [FK→ds_attachedbuilding], id_eventqueue int NOT NULL [FK→ds_eventqueue]

### ds_attachedbuildinghasattribute
Назначение: атрибуты, присвоенные конкретному экземпляру здания.
Колонки: id_attachedbuilding int NOT NULL [FK→ds_attachedbuilding], id_attribute int NOT NULL [FK→ds_attribute]

### ds_attribute
Назначение: справочник атрибутов (характеристик), связывает тип атрибута и его имя.
Колонки: id_attribute int [PK], id_attributetype int [FK→ds_attributetype], id_attributename int [FK→ds_attributename]

### ds_attributename
Назначение: справочник имён атрибутов.
Колонки: id_attributename int [PK], id_localizedname int, attributename varchar(30)

### ds_attributetype
Назначение: справочник типов атрибутов.
Колонки: id_attributetype int [PK], id_localizedtype int, typename varchar(30)

### ds_auctionsort
Назначение: категории/сортировка лотов аукциона.
Колонки: id_sort int [PK], sortname varchar(50), id_localizedname int, id_parent int [FK→ds_auctionsort], isactive bool

### ds_avatar
Назначение: аватары игроков, привязанные к расе.
Колонки: id_avatar int [PK], id_race int [FK→ds_race], imagefilename varchar(255)

### ds_average_resources
Назначение: средние показатели ресурсов/производства по миру (используется в отчётах шпионажа для оценки чужой соты, согласно комментарию).
Колонки: id_world int NOT NULL, id_attribute int NOT NULL [FK→ds_attribute], value bigint NOT NULL, add_date ts(0) NOT NULL, is_current bool NOT NULL

### ds_battle
Назначение: бой между сотами/участниками.
Колонки: id_battle int [PK], id_battlecomb int, battlestartdate ts, battleenddate ts, winner char(1), id_battleendreason int [FK→ds_battleendreasons], id_attackercomb bigint, haveotherbattles bigint, retreat_side varchar(1), retreat_round_number int, retreat_with_empty_damage bool

### ds_battleaction
Назначение: справочник типов боевых действий (атака/защита и т.п.) для лога боя.
Колонки: id_battleaction int [PK], id_localizedname int, battleactionname varchar(50), numberofparams int

### ds_battleendreasons
Назначение: справочник причин завершения боя (согласно комментарию в дампе).
Колонки: id_battleendreason int [PK], reasonname varchar, armytype char(1), id_localizedname int

### ds_battleformation
Назначение: справочник боевых построений (формаций) армии.
Колонки: id_battleformation int [PK], id_localizedname int, formationname varchar(50)

### ds_battleformationhasbonus
Назначение: бонусы взаимодействия одной формации против другой.
Колонки: id_battleformation_1 int NOT NULL [FK→ds_battleformation], id_battleformation_2 int NOT NULL [FK→ds_battleformation], id_attribute int [FK→ds_attribute], value real

### ds_battlehasattribute
Назначение: атрибуты (параметры), привязанные к конкретному бою.
Колонки: id_battlehasattribute int [PK], id_battle int NOT NULL [FK→ds_battle], id_attribute int NOT NULL [FK→ds_attribute], value real NOT NULL

### ds_battleparticipant
Назначение: участник боя (атакующий/защищающийся userinstance), результат участия.
Колонки: id_battle int NOT NULL [FK→ds_battle], id_userinstance int NOT NULL [FK→ds_userinstance], id_battleparticipant int [PK], participantflag char(1), iswinner bool, iscombowner bool, isstartround int, battlexp real

### ds_battleparticipanthasunitcontainer
Назначение: контейнеры юнитов (отряды), которыми участник боя владеет в бою.
Колонки: id_battleparticipant int NOT NULL [FK→ds_battleparticipant], id_unitcontainer int NOT NULL [FK→ds_unitcontainer], unitcontainername varchar(50), id_battleparticipanthasunitcontainer int [PK], dieafterround int, startround int

### ds_battlereport
Назначение: текстовый отчёт (лог) о бое.
Колонки: report text, id_battle int [FK→ds_battle], id_battlereport int [PK], participantflag varchar(1)

### ds_battleresult
Назначение: результат по юнитам за раунд боя (потери, урон нанесённый/полученный).
Колонки: id_battleround int NOT NULL [FK→ds_battleround], id_battleparticipant int NOT NULL [FK→ds_battleparticipant], id_unitcontainer int NOT NULL [FK→ds_unitcontainer], id_unit int NOT NULL [FK→ds_unit], amount real, receivedamage real, outputdamage real, loses real, unitcontainername varchar(50), dieafterround int, id_battleresult int [PK]

### ds_battleround
Назначение: раунд боя (нумерация раундов, время начала/конца, отступление).
Колонки: id_battle int NOT NULL [FK→ds_battle], roundnumber int NOT NULL, id_battleround int [PK], roundstarttime ts, roundendtime ts, haveadds bigint, retreat_side char(1), retreat_started bool

### ds_battleround_burned_arts
Назначение: артефакты, сгоревшие/уничтоженные в конкретном раунде боя.
Колонки: id_battleround int NOT NULL [FK→ds_battleround], id_artefact int [FK→ds_artefact]

### ds_battleroundhasbattleaction
Назначение: связь раунда боя с выполненными боевыми действиями участников/отрядов.
Колонки: id_battleround int NOT NULL [FK→ds_battleround], id_battleaction int NOT NULL [FK→ds_battleaction], id_battleparticipant int NOT NULL [FK→ds_battleparticipant], id_unitcontainer int NOT NULL [FK→ds_unitcontainer]

### ds_battleroundhasformation
Назначение: формация, использованная отрядом участника в конкретном раунде боя.
Колонки: id_battleformation int NOT NULL [FK→ds_battleformation], id_battleround int NOT NULL [FK→ds_battleround], id_battleparticipant int NOT NULL [FK→ds_battleparticipant], id_unitcontainer int NOT NULL [FK→ds_unitcontainer]

### ds_battlestats
Назначение: статистика по боям в разрезе статистики мира (кол-во текущих/завершённых боёв).
Колонки: id_battlestats int [PK], id_worldstats int NOT NULL [FK→ds_worldstats], endbattles int, currentbattles int, id_statistic int NOT NULL [FK→ds_statistic]

### ds_battlesystemreport
Назначение: системный (технический) лог обработки боя движком.
Колонки: id_battlesystemreport int [PK], id_battle int [FK→ds_battle], add_date ts, id_userinstance int [FK→ds_userinstance] (комментарий: "UIID that refresh battle"), log_record text

### ds_battlexp / ds_battlexp2
Назначение: боевой опыт userinstance; `2` — вторая версия/альтернативный учёт (чем отличается — не установлено).
Колонки (`ds_battlexp`): id_userinstance int NOT NULL [PK/FK→ds_userinstance], value real NOT NULL, absolute_value real NOT NULL, npc_xp float
Колонки (`ds_battlexp2`): id_userinstance int NOT NULL [PK/FK→ds_userinstance], value real NOT NULL

**Статус (проверено на живом мире `phoenix` / «Сфера Парусов», БД,
live-запрос, 2026-09-02):** `ds_battlexp` — **живая**, 1551 строка (растёт
по мере боёв). `ds_battlexp2` — **пустая/мёртвая**, 0 строк. Похоже на то,
что `2` — заброшенная попытка альтернативного учёта, так и не введённая в
эксплуатацию (проверено только на одном мире — не исключено, что на других
мирах ситуация иная).

### ds_billing_gate_action_options
Назначение: параметры (опции) действия платёжного шлюза биллинга.
Колонки: id_option int [PK], id_action int NOT NULL [FK→ds_billing_gate_actions], option_name varchar(120) NOT NULL, option_value text, option_value2 text

### ds_billing_gate_actions
Назначение: журнал действий/запросов к платёжному шлюзу биллинга (включая ошибки, IP, ответ шлюза).
Колонки: id_action int [PK], add_date ts(0) NOT NULL, action_name varchar(60) NOT NULL, ip varchar(120) NOT NULL, have_error bool NOT NULL, error_text varchar(255), gate_ansver text, key varchar(255)

### ds_bj_game / ds_bj_winsum
Назначение: мини-игра Blackjack (партии/банк) — **вне рамок документации проекта по решению владельца**, каталогизировано только для полноты списка таблиц, не разбирается дальше.

### ds_building
Назначение: справочник типов зданий (уникальность, возможность постройки/апгрейда, тип).
Колонки: id_building int [PK], id_localizedname int, id_localizeddescription int, buildingname varchar(30), imagefilename varchar(255), isunique bool, buildable bool, onlyasupgrade bool, isalliancebuilding bool, id_buildingtype int [FK→ds_buildingtype], building_score int

### ds_buildingcanbebuildon
Назначение: правило — на каком типе клетки/грунта можно строить здание.
Колонки: id_building int NOT NULL [FK→ds_building], id_celltype int NOT NULL, id_groundtype int NOT NULL

### ds_buildingcanbeupgraded
Назначение: правило апгрейда — из какого здания в какое можно перестроить.
Колонки: id_from_building int NOT NULL [FK→ds_building], id_to_building int NOT NULL [FK→ds_building]

### ds_buildingcanbuildbyrace
Назначение: доступность здания по расе (плюс локализованное название/описание для расы).
Колонки: id_building int NOT NULL [FK→ds_building], id_race int NOT NULL [FK→ds_race], id_localizedname int, id_localizeddescription int, id_extendeddescription int

### ds_buildingdeactivatepriority
Назначение: приоритет деактивации здания (при нехватке ресурсов и т.п.).
Колонки: id_building int NOT NULL [FK→ds_building], priority smallint

### ds_buildinghasattribute / ds_buildinghasattribute_log
См. [mechanics-buildings.md, §18](mechanics-buildings.md) (код-подтверждено).

### ds_buildinghasspecialfunction
Назначение: связь здания со специальной функцией (особой механикой).
Колонки: id_specialfunction int NOT NULL [FK→ds_specialfunction], id_building int NOT NULL [FK→ds_building], isdefault bool

### ds_buildinghastradescope
Назначение: связь здания с торговой областью/зоной (для торговли/рынка).
Колонки: id_tradescope int NOT NULL [FK→ds_tradescope], id_building int NOT NULL [FK→ds_building]

### ds_buildingneedsbuilding
Назначение: зависимость постройки здания от наличия другого здания.
Колонки: id_building_1 int NOT NULL [FK→ds_building], id_building_2 int NOT NULL [FK→ds_building]

### ds_buildingneedsscience / ds_buildingneedsscience_log
См. [mechanics-buildings.md, §18](mechanics-buildings.md) (код-подтверждено).

### ds_buildingsalowedtouserinstance
Назначение: индивидуальные разрешения на строительство конкретных типов построек для игрока.
Колонки: id_allowedbuilding int [PK], id_building int [FK→ds_building], id_userinstance int [FK→ds_userinstance], adddate ts

### ds_buildingtype
Назначение: справочник типов/категорий построек.
Колонки: id_buildingtype int [PK], id_localizedtype int, type varchar(20)

---

## C–G (buildingsalowedtouserinstance … help_section)

*(`ds_buildingsalowedtouserinstance`/`ds_buildingtype` дублируются с концом
предыдущего раздела — это граница между двумя проходами каталогизации,
таблицы идентичны, см. выше.)*

### ds_capture_colony_history
См. [mechanics-combat.md, §8](mechanics-combat.md) (код-подтверждено: пишется только `ME_CaprureColony::Capture()`).

### ds_cell
Назначение: клетки игрового поля внутри comb'а (планетарная сетка).
Колонки: id_cell int [PK], id_comb int [FK→ds_comb], id_celltype int [FK→ds_celltype], id_groundtype int [FK→ds_groundtype], cellnumber int

### ds_celltemplate
Назначение: шаблон раскладки клеток для типа комба.
Колонки: id_celltemplate int [PK], id_celltype int [FK→ds_celltype], name varchar(30), templatenumber int

### ds_celltemplateelement
Назначение: отдельные клетки внутри шаблона ds_celltemplate.
Колонки: id_celltype int [FK→ds_celltype], id_groundtype int [FK→ds_groundtype], cellnumber int, id_celltemplateelement int [PK], id_celltemplate int [FK→ds_celltemplate], id_building int [FK→ds_building]

### ds_celltype
Назначение: справочник типов клеток.
Колонки: id_celltype int [PK], id_localizedname int, celltypename varchar(30)

### ds_chat_admin / ds_chat_port
См. [admin-panel.md, §2.4.1](admin-panel.md) (код-подтверждено: внешний чат-сервер, эти таблицы — только список модераторов и порт мира).

### ds_colony
Назначение: связь "материнский comb → колония".
Колонки: id_comb_from int [FK→ds_comb], id_comb_to int [FK→ds_comb]

### ds_comb
Назначение: основная таблица игровых планет/комбов игрока.
Колонки: id_comb int [PK], id_combcomplex int [FK→ds_combcomplex], id_userinstance int [FK→ds_userinstance], combname varchar(50), combnumber smallint, combscore int, is_capture bool

### ds_comb_coords_history
Назначение: история изменения координат comb'а (перемещение внутри комплекса).
Колонки: id_comb int [FK→ds_comb], old_combnumber int, new_combnumber int, old_id_complex int [FK→ds_combcomplex], new_id_complex int [FK→ds_combcomplex], date ts

### ds_comballowedtradescope
Назначение: разрешённые торговые зоны (scope) для конкретного comb'а.
Колонки: id_tradescope int, id_comb int [FK→ds_comb]

### ds_combbindeventqueue
Назначение: привязка comb'а к событию в очереди событий.
Колонки: id_comb int [FK→ds_comb], id_eventqueue int [FK→ds_eventqueue]

### ds_combcomplex
Назначение: комплекс (группа комбов) внутри кольца.
Колонки: id_ring int, id_combcomplex int [PK], id_complextype int [FK→ds_combcomplextype], complexnumber int

### ds_combcomplextype
Назначение: справочник типов комплексов комбов.
Колонки: id_complextype int [PK], id_localizedtype int, complextype varchar(20)

### ds_combhasartefact
Назначение: артефакты, найденные (но не обязательно прикреплённые) игроком на comb'е; согласно комментарию в дампе — "contains", не "attached".
Колонки: id_comb int [FK→ds_comb], id_artefact int [FK→ds_artefact]

### ds_combhasresource
Назначение: значения ресурсов/атрибутов, привязанных к комбу.
Колонки: id_comb int [FK→ds_comb], id_attribute int, value varchar(25), id_combhasresource int [PK]

### ds_combname / ds_combname_history
Назначение: текущее имя комба (вынесено отдельно от ds_comb) + лог переименований.
Колонки (`ds_combname`): id_combname int [PK], id_comb int [FK→ds_comb], combname varchar(50)

### ds_combstats
Назначение: агрегированная статистика комбов по кольцу (свободные/занятые/удалённые).
Колонки: id_combstats int [PK], id_worldstats int [FK→ds_worldstats], id_ring int, id_statistic int, freecombs int, occupycombs int, delcombs int

### ds_commander / ds_commandercarriesartefact
Назначение: командиры игрока (юниты для переноски артефактов) и переносимые ими артефакты.
Колонки (`ds_commander`): id_commander int [PK], id_userinstance int [FK→ds_userinstance]
Колонки (`ds_commandercarriesartefact`): id_artefact int [FK→ds_artefact], id_commander int [FK→ds_commander], id_commandercarriesartefact int [PK]

### ds_count
Назначение: не установлено по одному названию — похоже на снимок объёмов производства ресурсов по пользователю/комбу.
Колонки: username varchar(255), production_k int, production_nk int, production_se int, production_f int, production_p int, id_comb int [FK→ds_comb]

### ds_cron_data
Назначение: служебные параметры выполнения cron-задач (даты последнего запуска по имени параметра).
Колонки: param_name varchar(20), param_date ts

### ds_currency
Назначение: справочник валют.
Колонки: id_currency int [PK], currency_short varchar(3), id_localizedcurrency int

### ds_daily_stat
Назначение: суточная агрегированная статистика проекта (логины, регистрации, оплаты, бои).
Колонки: date date [PK], totallogins int, uniquelogins int, totalregs int, activeregs int, conversion float, shopnum int, shopamount int, battles int, billingnum int, billingamount int

### ds_detailedranking
Назначение: детализированный рейтинг игрока по категориям (армия/артефакты/постройки/производство/наука) — см. также [mechanics-races-world-ranking.md, §6.2](mechanics-races-world-ranking.md).
Колонки: id_userinstance int [FK→ds_userinstance], armyranking real, artefactranking real, buildingranking real, productionranking real, scienceranking real

### ds_disabledbuilding / ds_disabledbuilding_colony
Назначение: постройки, отключённые для конкретного игрового мира (обычные / доступные в колониях).
Колонки: id_world int [FK→ds_world], id_building int [FK→ds_building]

### ds_disabledscience
Назначение: исследования, отключённые для конкретного игрового мира.
Колонки: id_science int, id_world int [FK→ds_world]

### ds_disabledsciencebranch
Назначение: ветки исследований, отключённые для конкретного игрового мира.
Колонки: id_world int [FK→ds_world], id_branch int

### ds_dropped_artefacts
Назначение: артефакты, "выпавшие"/потерянные у игрока, с метками времени появления и исчезновения.
Колонки: id_dropped_artefact int [PK], id_artefact int [FK→ds_artefact], id_userinstance int [FK→ds_userinstance], birth_time ts, death_time ts, drop_date ts

### ds_dv_request
Назначение: не установлено по одному названию — похоже на лог неких проверочных запросов (DV) пользователя по типу и содержимому.
Колонки: id_user int [FK→ds_user], date ts, request_type varchar(150), data txt

### ds_event
Назначение: справочник типов игровых событий и их обработчиков.
Колонки: id_event int [PK], id_eventhandler int [FK→ds_eventhandler], eventname varchar(50), need_create_lock bool, eventhandlerclass varchar(50), lock_only_in_end bool, weight int

### ds_eventacceleration
Назначение: события в очереди, доступные для платного ускорения.
Колонки: id_eventqueue int [FK→ds_eventqueue]

### ds_eventcanproducesystemmessage
Назначение: связь типа события с системным сообщением, которое оно может породить.
Колонки: id_event int [FK→ds_event], id_systemmessage int

### ds_eventhandler
Назначение: справочник классов-обработчиков событий.
Колонки: id_eventhandler int [PK], eventhandler varchar(50), eventhandlerclass varchar(50)

### ds_eventhandlerlog
Назначение: лог сообщений от обработчиков событий (по комментарию в дампе: "EventHandler can log various messages here").
Колонки: id_eventhandler int [FK→ds_eventhandler], id_eventqueue int [FK→ds_eventqueue], id_eventhandlerlog int [PK], creationdate ts, logtext txt

### ds_eventhasoption
Назначение: значения параметров (опций) конкретного события в очереди.
Колонки: id_eventqueue int [FK→ds_eventqueue], id_eventoption int [FK→ds_eventoption], value bigint

### ds_eventoption
Назначение: справочник возможных опций/параметров для типов событий.
Колонки: id_eventoption int [PK], id_event int [FK→ds_event], optionname varchar(50)

### ds_eventparticipant
Назначение: участники (игроки) конкретного события в очереди.
Колонки: id_eventqueue int [FK→ds_eventqueue], id_userinstance int [FK→ds_userinstance]

### ds_eventproducesevent
Назначение: цепочки событий — одно событие порождает другое в заданном порядке.
Колонки: id_event_1 int [FK→ds_event], id_event_2 int [FK→ds_event], eventordernumber int

### ds_eventqueue
Назначение: очередь запланированных/выполняемых игровых событий — см. также [mechanics-events-turns.md](mechanics-events-turns.md).
Колонки: id_eventqueue int [PK], id_event int [FK→ds_event], eventstarttime ts, eventendtime ts, istrash bool

### ds_finalranking / ds_finalrankinghistory
См. [mechanics-races-world-ranking.md, §6.2](mechanics-races-world-ranking.md) (код-подтверждено).

### ds_flashkey
Назначение: ключ/токен flash-клиента игрока (вероятно, для защиты протокола).
Колонки: id_userinstance int [FK→ds_userinstance], key int

### ds_flashversion / ds_flashversion_ru
Назначение: версионирование файлов flash-интерфейса (комментарий в дампе: "Containts flash interface versioning") — см. также [client-engine-and-protocol.md](client-engine-and-protocol.md) (`version_files.as`).
Колонки (`ds_flashversion`): id_file int [PK], filename varchar(50), version int, lastmodified ts, main bool, id_language int

### ds_form* (кластер из ~25 таблиц)
Назначение: маркетинговая анкета (`questions.php`) — см. [mechanics-quests-events.md](mechanics-quests-events.md), не детализируется здесь повторно. Включает: `ds_form`, `ds_form_age`, `ds_form_avto`, `ds_form_busy`, `ds_form_buy_own(_variants)`, `ds_form_buy_price_quality_variants`, `ds_form_city`, `ds_form_country`, `ds_form_ds_how_often`, `ds_form_family`, `ds_form_gender`, `ds_form_hours`, `ds_form_income`, `ds_form_interest`, `ds_form_internetwhere`, `ds_form_kids`, `ds_form_online`, `ds_form_place`, `ds_form_profeduc`, `ds_form_profession`, `ds_form_userhas_buy_own`, `ds_form_userhas_buy_price_quality`, `ds_form_userhasinterest`, `ds_form_userhasinternetwhere`, `ds_form_worker_amount`.

### ds_forum_user
Назначение: связь игрового пользователя с учётной записью на форуме.
Колонки: id_user int [FK→ds_user], id_forum_user int

### ds_free_ring_transfer / ds_free_ring_transfer_log
См. [mechanics-world-transfer.md, Часть 3](mechanics-world-transfer.md) (код-подтверждено: `SU_FreeTransfer`).

### ds_freetopay
Назначение: переход игрока из free-to-play в платящего (транзакция первой оплаты).
Колонки: id_transaction int [PK], id_user int [FK→ds_user], date_start ts, date_pay ts, amount real

### ds_gamehelper
Назначение: игроки со статусом "помощник игры" (волонтёр-модератор).
Колонки: id_userinstance int [PK, FK→ds_userinstance]

### ds_gamemaster
Назначение: игроки со статусом "гейммастер" (администратор внутри игрового мира).
Колонки: id_userinstance int [PK, FK→ds_userinstance]

### ds_gamemodifier / ds_gamemodifiertemplate / ds_gamemodifiertype
См. [mechanics-buildings.md, §18](mechanics-buildings.md) (код-подтверждено: `ds_gamemodifier` — физическая таблица за `DE_WorldSettings`).

### ds_gate_bad_keys / ds_gate_keys / ds_gate_slave
См. [multi-world-architecture.md, §5.1](multi-world-architecture.md) (код-подтверждено).

### ds_global_alliance / ds_global_alliance_user_black_list / ds_global_alliance_users
Назначение: глобальный альянс (объединяет игроков вне привязки к одному игровому миру), чёрный список, участники — см. также [mechanics-alliances-diplomacy.md, §7](mechanics-alliances-diplomacy.md).
Колонки (`ds_global_alliance`): id_global_alliance int [PK], alliance_name varchar, short_name varchar, id_leader int [FK→ds_user], join_password varchar, join_payment int, join_confirmation bool, creation_date ts

### ds_global_settings
Назначение: глобальные (кросс-мировые) настройки проекта в виде пар ключ-значение.
Колонки: id_settings int [PK], settings_name varchar(60), value varchar(30), comment varchar(255)

### ds_groundtype
Назначение: справочник типов поверхности клеток (комментарий в дампе: "stein", "wьste", "wasser" — немецкие названия, см. также немецкую версию портала).
Колонки: id_groundtype int [PK], id_localizedname int, id_localizeddescription int, groundname varchar(30)

### ds_help / ds_help_section
Назначение: тексты и разделы встроенной справки по языкам.
Колонки (`ds_help`): id_help_section int [FK→ds_help_section], id_language int, help_content txt, help_title varchar(200)

---

## I–P (inoutusersstats … protective_field_restore_price)

### ds_inoutusersstats
Назначение: агрегированная статистика притока/оттока пользователей.
Колонки: id_inoutusersstats int [PK], users_in int, users_out int, id_statistic int

### ds_inoutworldusers
Назначение: статистика притока/оттока пользователей (в т.ч. премиум) по конкретному игровому миру.
Колонки: id_inoutworldusers int [PK], id_statistic int, id_worldstats int, newusers int, del_users int, newpremiumusers int, delpremiumusers int

### ds_interface
Назначение: справочник вариантов игрового интерфейса (тема/версия).
Колонки: id_interface int [PK], interfacename varchar(50), id_localizedname int

### ds_language
Назначение: справочник языков клиента.
Колонки: id_language int [PK], language varchar(30), shortname char(1)

### ds_last_refresh
Назначение: не установлено по одному названию — вероятно метка последнего обновления кэша/данных пользователя.
Колонки: id_user int [PK][FK→ds_user], lastrefresh ts

### ds_league
См. [mechanics-newbie-protection-leagues.md](mechanics-newbie-protection-leagues.md) (код/вики-подтверждено).
Колонки: id_league int [PK], league_name varchar(10), min_level int, min_life_time int, min_armies_weight bigint, min_one_army_stars int

### ds_localizedtext / ds_localizedtextlanguage
Назначение: контейнер локализуемого текста + переводы по языкам.
Колонки (`ds_localizedtextlanguage`): id_language int [PK][FK→ds_language], id_localizedtext int [PK][FK→ds_localizedtext], text txt, last_change_date ts

### ds_lockedevents
Назначение: заблокированные/удерживаемые события очереди событий.
Колонки: id_eventqueue int [FK→ds_eventqueue]

### ds_log_delete_game_object_types / ds_log_delete_game_objects
Назначение: справочник типов игровых объектов для лога удаления + сам лог удаления (артефакты и т.п.) по мирам.
Колонки (`ds_log_delete_game_objects`): id_delete_log int [PK], id_world int, id_comb int, id_object_type int [FK→ds_log_delete_game_object_types], delete_date ts, id_object int, id_cell int, artefact_death_time ts

### ds_logincookie / ds_loginhistory
Назначение: cookie для автологина + история входов пользователей (IP, cookie).
Колонки (`ds_loginhistory`): id_user int [FK→ds_user], lastvisit ts, ip varchar(20), ip_proxy varchar(255), id_loginhistory int [PK], id_cookie int [FK→ds_logincookie], ip_internal varchar(20)

### ds_loginqueue
Назначение: очередь одновременных входов (защита от нагрузки при логине).
Колонки: id_userinstance int [PK][FK→ds_userinstance], logintime ts, lastupdate ts

### ds_lotery_artefacts
Назначение: лотерея артефактов — выданные призы.
Колонки: id_artefactinstance int, id_comb int, id_user int [FK→ds_user], id_artefact_present int, date ts

### ds_mail_disabled_mails / ds_mail_message / ds_mail_recepient
Назначение: email-рассылки (отписки, шаблоны, получатели) — НЕ внутриигровая почта (см. `ds_message` ниже).
Колонки (`ds_mail_message`): id_message int [PK], subject varchar(255), body txt, headers txt, datetosend ts, sendiscomplit bool, startdate ts, enddate ts

### ds_message / ds_messagereserv / ds_messagescope
См. [mechanics-moderation-vacation-messaging.md, §3.7](mechanics-moderation-vacation-messaging.md) (код-подтверждено: внутриигровая почта).

### ds_money
Назначение: справочник видов внутриигровой валюты.
Колонки: id_money int [PK], money_name varchar(50), id_locolizedname int [FK→ds_localizedtext], short_name varchar(2)

### ds_money_account* / ds_money_list* / ds_money_action_types / ds_money_payments_price
См. [billing-and-monetization.md, §9](billing-and-monetization.md) (код-подтверждено: money-account движок). Включает: `ds_money_account`, `ds_money_account_balance`, `ds_money_account_history`, `ds_money_account_payments`, `ds_money_action_types`, `ds_money_list`, `ds_money_list_billing_payments`, `ds_money_list_enter`, `ds_money_list_exchange`, `ds_money_list_from_reserv`, `ds_money_list_interworld_recive`, `ds_money_list_interworld_send`, `ds_money_list_pvp`, `ds_money_list_reserv`, `ds_money_list_service_payment`, `ds_money_list_to_reserv`, `ds_money_list_trade_payments`, `ds_money_list_trade_tax`, `ds_money_payments_price`.

### ds_money_reserv_reason
Назначение: справочник причин резервирования средств (используется `ds_money_list_reserv`/`to_reserv`/`from_reserv`).
Колонки: id_reason int [PK], reason_name varchar(50)

### ds_multiworldaccess
Назначение: доступ пользователя к мультимировому функционалу.
Колонки: id_multiworldaccess int [PK], id_user int [FK→ds_user], adddate ts

### ds_notradeperiods
Назначение: периоды запрета торговли по миру.
Колонки: id_notradeperiod int [PK], start_date ts, end_date ts, id_world int [FK→ds_world]

### ds_npc_army_settings / ds_npc_attack_chance / ds_npc_battle_log / ds_npc_lottery / ds_npc_repeat_attack / ds_npc_spawn_army / ds_npc_user / ds_npchastype
См. [mechanics-npc-terraforming.md, §1.2.1](mechanics-npc-terraforming.md) (код-подтверждено). **Важно:** `ds_npc_lottery`, несмотря на название, к NPC не относится — используется торговым админ-модулем (лотерея в магазине).

### ds_oauthinfo
См. [client-engine-and-protocol.md, §8.8](client-engine-and-protocol.md) (код-подтверждено: НЕ про VK, это Klase.lt/Meta.ua).
Колонки: open_key varchar(100) [PK], secret_key varchar(100), token_username varchar(100), game_uid int

### ds_occurrence / ds_occurrenceaction
Назначение: справочник типов игровых событий/происшествий + их обработчики.
Колонки (`ds_occurrence`): id_occurrence int [PK], occurrence_name varchar
Колонки (`ds_occurrenceaction`): id_occurrenceaction int [PK], occurrenceaction_name varchar, occurrenceaction_handler varchar

### ds_optimize / ds_optimize2
Назначение: лог медленных/профилируемых SQL-запросов (для оптимизации); `2` — вторая такая же таблица.
Колонки: id_query int [PK/-], sql_string txt, exec_time real

**Статус (проверено на живом мире `phoenix` / «Сфера Парусов», БД,
live-запрос, 2026-09-02):** **обе таблицы пустые** — `ds_optimize` = 0
строк, `ds_optimize2` = 0 строк. Судя по всему, профилирование SQL через
эти таблицы либо отключено, либо никогда не было реально включено на этом
мире (проверено только на одном мире).

### ds_partner
Назначение: партнёры/рекламные площадки (реферальная программа), включая учётку для партнёрского кабинета. Комментарий из БД: "Any Partners. The standard partner is \"DestinySphere\"."
Колонки: id_partner int [PK], name varchar(100), public_id varchar(32), premium_present int, site_list txt, contact_person varchar(255), email varchar(30), reg_date ts, active bool, password varchar(255), login varchar(20), procent real

### ds_partner_has_ip_range
Назначение: диапазоны IP, привязанные к партнёру (атрибуция трафика).
Колонки: id_partner int [FK→ds_partner], from_ip inet, to_ip inet, id_partner_has_ip_range int [PK]

### ds_partnerhaspaymentmodel / ds_partnerhaspaymenttype
Назначение: связь партнёра с доступными ему моделями/типами оплаты (M:N).

### ds_payment
Назначение: основная запись платежа пользователя (премиум-подписка). Комментарий из БД: "The main payment data for an user."
Колонки: id_payment int [PK], id_user int [FK→ds_user], id_paymentmodel int [FK→ds_paymentmodel], id_paymenttype int [FK→ds_paymenttype], recurring bool, nextpayment ts, paymentdate ts, price float, tax float, totalprice float, startdate ts, enddate ts, verified bool, bill_number varchar(15)

### ds_payment_conf
Назначение: конфигурация модуля платежей (ключ-значение).
Колонки: param varchar(20) [PK], pval varchar(255)

### ds_payment_services
Назначение: справочник платных сервисов (обработчики, привязка к типу денежного действия).
Колонки: id_payment_service int [PK], service_name varchar(60), handler_class_name varchar(60), section varchar(60), is_active bool, sort_order int, money_ation_tupe_accordance varchar(60) [FK→ds_money_action_types]

### ds_paymentduration
Назначение: справочник длительностей оплаты (в днях). Комментарий: "Duration as days".
Колонки: id_duration int [PK], duration real

### ds_paymentkind / ds_paymentkindhascurrency / ds_paymentkindhastax
Назначение: справочник видов/тарифов оплаты (напр. "Standard Abo") + связи с валютой/налогом.
Колонки (`ds_paymentkind`): id_paymentkind int [PK], kindofpayment varchar(50)

### ds_paymentmodel
Назначение: конкретная модель оплаты (вид + длительность + цена).
Колонки: id_paymentmodel int [PK], id_paymentkind int [FK→ds_paymentkind], id_duration int [FK→ds_paymentduration], price real, paypal_sign txt

### ds_paymentserviceslog / ds_paymentserviceslog_users
Назначение: лог заказа/оплаты/выполнения/отмены платных сервисов + участники операции.
Колонки (`ds_paymentserviceslog`): id_paymentserviceslog int [PK], action_name varchar(60), gc_price int, sc_price int, is_paid bool, paid_date ts, is_ordered bool, ordered_date ts, is_complete bool, complite_date ts, is_canceled bool, cancel_date ts, info txt

### ds_paymenttransaction
Назначение: транзакции платёжного шлюза (входящие/исходящие) по платежу. Комментарий: type "I"=inbound, "O"=outbound.
Колонки: id_payment int [PK][FK→ds_payment], type char(1) [PK], xml txt, transactiontime ts, result varchar(3), transactionid varchar(30)

### ds_paymenttype / ds_paymenttypeassist / ds_paymenttypebank / ds_paymenttypepaypal / ds_paymenttypewebmoney
Назначение: справочник способов оплаты + доп. данные по каждому способу (карта/банк/PayPal/WebMoney).

### ds_peace_alliance / ds_peace_user
См. [mechanics-alliances-diplomacy.md, §5-6](mechanics-alliances-diplomacy.md) (код-подтверждено).

### ds_peacepreiods
Назначение: периоды принудительного мира по миру (аналог `ds_notradeperiods`).
Колонки: id_peaceperiod int [PK], start_date ts, end_date ts, id_world int [FK→ds_world]

### ds_poll_answer / ds_poll_question / ds_poll_question_worlds / ds_poll_result
Назначение: опросы сайта/игры (вопросы, ответы, привязка к мирам, голоса пользователей).

### ds_powershild_current_status / ds_powershild_on_userinstance / ds_powershild_status_cc2 / ds_powershild_status_name
Назначение: энергощит (powershield) — текущий статус, параметры работы, граф допустимых переходов, справочник статусов — см. также [mechanics-combat.md](mechanics-combat.md) (силовой щит).

### ds_premium_change_log / ds_premiumonlog / ds_premiumstats
Назначение: журналы изменения/включения премиум-статуса + агрегированная статистика — см. также [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md).

### ds_present
Назначение: подарки премиум-дней пользователю.
Колонки: id_present int [PK], id_user int [FK→ds_user], days int, type int, add_date ts

### ds_produceableunit
Назначение: связь здания с юнитом, который оно может производить (M:N).
Колонки: id_building int [PK][FK→ds_building], id_unit int [PK][FK→ds_unit]

### ds_promo_dtf_artefactbox
Назначение: промо-акция (кросс-промо с сайтом DTF) — см. [mechanics-quests-events.md](mechanics-quests-events.md).

### ds_protective_field / ds_protective_field_restore_price
Назначение: защитное поле комбината/базы, время окончания временной защиты + цена восстановления.

---

## Q–T (quest … trade_history_bid)

### ds_quest / ds_quest_artefact_search_log / ds_quest_unit / ds_quest_unit_search_log
См. [mechanics-quests-events.md](mechanics-quests-events.md) (детально задокументировано).

### ds_race / ds_raceavatarhasbonus / ds_racehasattribute(_log) / ds_racehasbasering
См. [mechanics-races-world-ranking.md](mechanics-races-world-ranking.md) (детально задокументировано).

### ds_ramblerlogin
Назначение: легаси-авторизация через Rambler ID (внешний логин-провайдер).
Колонки: rambler_login varchar(150), login_enable bool, registration_enable bool

### ds_ranking / ds_ranking_archive(_places) / ds_ranking_level
См. [mechanics-races-world-ranking.md, §6](mechanics-races-world-ranking.md) (детально задокументировано).

### ds_recommendation
Назначение: сбор email для реферальной функции «порекомендовать другу».
Колонки: id_recommendation int [PK], email varchar(100), recommenddate ts

### ds_region
Назначение: не установлено по одному названию — возможно географический регион/зона (геолокация платежей или статистика).
Колонки: id_region int [PK], regionname varchar(100), amount int

### ds_religion / ds_religionhasattribute / ds_religionneedsbuilding / ds_religionstatus
См. [mechanics-races-world-ranking.md, §4](mechanics-races-world-ranking.md) (детально задокументировано).

### ds_replace_id_artefact
Назначение: таблица соответствия старый→новый id артефакта, вероятно для миграции/слияния данных.
Колонки: old_value int, new_value int [PK]

### ds_replication / ds_replication_slave / ds_replication_tables
См. [utils-and-maintenance.md, §9](utils-and-maintenance.md) (код-подтверждено).

### ds_request_log
Назначение: журнал (UNLOGGED) запросов пользователей к серверу (действие + время выполнения).
Колонки: id_userinstance int [FK→ds_userinstance], id_useractiontype int [FK→ds_useractiontype], add_date ts, xmldata varchar, exec_time float

### ds_reservedcomb
Назначение: зарезервированные (недоступные для заселения) участки карты.
Колонки: id_reservedcomb int [PK], id_comb int [FK→ds_comb], username varchar(50), priority int, add_date ts, comment txt

### ds_resource
Назначение: справочник ресурсов и курс обмена на «энергию стазиса» (комментарий в БД) — см. также [mechanics-races-world-ranking.md, §6.2](mechanics-races-world-ranking.md) (`exchangerate` используется в формуле рейтинга).
Колонки: id_attributename int [PK][FK→ds_attributename], exchangerate real, startamount int, id_locolizedshortname bigint, trade_resources bool

### ds_resource_stealing_tax_log
Назначение: лог-таблица налога/потерь при краже ресурсов между участками.
Колонки: id_log int [PK], id_comb int, id_userinstance int, id_comb_from int, id_userinstance_from int, resource varchar, stealing_tax real, before_amount real, loosed_amount real, check_date ts, add_date ts

### ds_reward
Назначение: разовая награда пользователю набором ресурсов (значения хранятся как varchar, не числа).
Колонки: id_userinstance int [FK→ds_userinstance], kaderium varchar(25), nano_crystal varchar(25), stasis_energy varchar(25), food varchar(25), prodium varchar(25)

### ds_ring / ds_ringtemplate
Назначение: «кольцо» — сектор игрового мира с лимитом числа участков (комментарий в БД про MaxNumberOfCombs) + шаблон кольца для генерации новых миров.
Колонки (`ds_ring`): id_ring int [PK], id_world int [FK→ds_world], id_localizedname int [FK→ds_localizedname], ringname varchar(30), ringnumber int

### ds_roundreport
Назначение: текстовый отчёт по раунду боя.
Колонки: id_battleround int [PK][FK→ds_battleround], report txt

### ds_science / ds_science_acceleration_price / ds_sciencebranch / ds_sciencebranchattributename / ds_sciencecanberesearchedbyrace / ds_sciencehasability / ds_sciencehasattribute / ds_scienceneedsbuilding / ds_scienceneedsscience
Назначение: справочник наук/технологий и все их зависимости/бонусы/ограничения — см. также [mechanics-research.md](mechanics-research.md).
Колонки (`ds_science`): id_science int [PK], id_branch int [FK→ds_sciencebranch], id_localizedname int, level int, researchcosts real, sciencename varchar(30), science_score int

### ds_serverload_average / ds_serverload_checkdate / ds_serverload_cpuusage / ds_serverload_servers
Назначение: мониторинг нагрузки серверов (load average, CPU, реестр серверов, метки замеров).

### ds_serversideinterface
Назначение: не установлено по одному названию — вероятно справочник серверных интерфейсов/внутренних действий с локализованным текстом.
Колонки: id_serversideinterface int [PK], name varchar(25), id_localizedtext int [FK→ds_localizedtext]

### ds_singleuseraction / ds_singleuseraction2
Назначение: не установлено по одному названию — вероятно отметка о разовом выполненном действии пользователя; `2` — вторая реализация с собственным id.

**Статус (проверено на живом мире `phoenix` / «Сфера Парусов», БД,
live-запрос, 2026-09-02):** **обе таблицы пустые** — `ds_singleuseraction`
= 0 строк, `ds_singleuseraction2` = 0 строк (проверено только на одном
мире). Не позволяет отличить «функциональность не используется игроками
этого мира» от «код, который должен туда писать, не вызывается вовсе» —
для этого нужен grep по коду, не входивший в эту задачу.

### ds_sms_countries / ds_sms_log / ds_sms_user_country / ds_smspaylog
Назначение: SMS-биллинг (страны, журнал отправленных SMS, привязка пользователя к стране, журнал платежей через SMS) — см. также [billing-and-monetization.md](billing-and-monetization.md).

### ds_specialattack
Назначение: справочник спецатак с вероятностью срабатывания.
Колонки: id_specialattack int [PK], id_localizedname int, id_localizeddescription int, specialattackname varchar(50), probability real, imagefilename varchar(255)

### ds_specialfunction / ds_specialfunctionrelation
Назначение: справочник спецфункций интерфейса (уровень доступа, премиум-флаг, иконка вкладки/URL) + иерархия родитель-потомок.
Колонки (`ds_specialfunction`): id_specialfunction int [PK], id_localizedname int, specialfunctionname varchar(50), level int, onlyforpremium bool, tab_image varchar(30), url varchar, affected_by_modifier varchar(50)

### ds_sponsorpay_log
Назначение: журнал платежей/начислений через партнёра SponsorPay (мобильная/офферволл монетизация) — см. также [billing-and-monetization.md](billing-and-monetization.md).
Колонки: id_sponsorpay_log int [PK], trans_id varchar(255), id_user int [FK→ds_user], paym_sum int, add_date ts

### ds_spy_action_type / ds_spy_group_action / ds_spy_log
Назначение: справочник шпионских действий, активное действие группы зондов, журнал разведдонесений — см. также [mechanics-combat.md, §12](mechanics-combat.md) (Шпионаж).
Колонки (`ds_spy_group_action`): id_spy_group_action int [PK], id_spy_action int [FK→ds_spy_action_type], zonds_amount int, id_comb_from int [FK→ds_comb], id_comb_to int [FK→ds_comb], id_eventqueue int [FK→ds_eventqueue], id_unit int [FK→ds_unit], end_time ts, id_spy_log int [FK→ds_spy_log], one_round bool

### ds_start_resource
Назначение: стартовое количество ресурса при создании мира.
Колонки: id_world int [FK→ds_world], id_resource int [FK→ds_resource], amount int

### ds_statistic
Назначение: метка снятия общей игровой статистики (дата среза).
Колонки: id_statistic int [PK], statsdate ts

### ds_storeusercombs / ds_storeuserdata / ds_storeuserinstancedata / ds_storeuserreason
См. [mechanics-world-transfer.md](mechanics-world-transfer.md) (перенос пользователя между мирами).

### ds_systemmessage
Назначение: справочник шаблонов системных сообщений (с параметрами) для локализованных уведомлений игрокам.
Колонки: id_systemmessage int [PK], id_localizedtext int, messagename varchar(50), numberofparams int, id_sysmsg_localizedtext int

### ds_tax
Назначение: не установлено по одному названию — вероятно глобальное/текущее значение налога (единственная запись).
Колонки: id_tax int [PK], tax float

### ds_terraforming / ds_terraformingbindeventqueue / ds_terraforminghasattribute / ds_terraformingneedscience
См. [mechanics-npc-terraforming.md, Часть 2](mechanics-npc-terraforming.md) (код-подтверждено).

### ds_trade / ds_trade_ability / ds_trade_artefact_course_data / ds_trade_bids / ds_trade_bindeventqueue / ds_trade_category / ds_trade_course(_recount) / ds_trade_deliverygood_artefactinstances / ds_trade_history(_artefactinstances) / ds_trade_history_bid
См. [mechanics-economy-trade.md](mechanics-economy-trade.md) (детально задокументировано).

---

## T–U (trade_history_sells … userban)

### Кластер ds_trade_* (продолжение) — см. [mechanics-economy-trade.md](mechanics-economy-trade.md)

`ds_trade_history_sells`, `ds_trade_lot_artefactinstances`, `ds_trade_lots`,
`ds_trade_resorce_course_data`, `ds_trade_scope`, `ds_trade_start_cources`,
`ds_trade_type`, `ds_trade_type_data`, `ds_trade_units_cours_data`,
`ds_tradebindeventqueue`, `ds_tradecategory`, `ds_tradehistory`,
`ds_tradescope`, `ds_tradestats` — все про биржу/торговлю (курсы, лоты,
история, налоги, ставки), тема покрыта в `mechanics-economy-trade.md`.

### ds_trade_menu_filters / ds_trade_menu_sorting
Назначение: UI-конфигурация фильтров/сортировки в меню биржи.

### ds_trade_user_ban / ds_trade_user_ban_reason
Назначение: запрет игроку на торговлю + справочник причин. Комментарий: «Запрет выбранному игроку на торговлю».
Колонки (`ds_trade_user_ban`): id_user int, id_ban_reason int [FK→ds_trade_user_ban_reason], end_date ts, start_date ts, id_world int

### ds_transfer_artefactinstance
Назначение: передача экземпляра артефакта (не установлено, в каком контексте — возможно, дар/сделка вне биржи).
Колонки: id_transfer_artefactinstance int [PK], id_eventqueue int [FK→ds_eventqueue], id_artefactinstance int [FK→ds_artefactinstance]

### ds_transfer_log
Назначение: лог переводов ресурсов между комбами.
Колонки: id_transfer_log int [PK], id_comb_from int [FK→ds_comb], id_comb_to int [FK→ds_comb], amount bigint, id_article int, categoryname varchar, id_eventqueue int [FK→ds_eventqueue], start_date ts, is_delivered bool, end_date ts

### ds_turnir_pause
См. [mechanics-tournaments.md](mechanics-tournaments.md).

### ds_unit
Назначение: справочник юнитов — см. также [mechanics-combat.md, §3.11.1](mechanics-combat.md) (числовые характеристики с портала).
Колонки: id_unit int [PK], id_localizedname int, id_localizeddescription int, id_unitcontainertype int [FK→ds_unitorcontainertype], unitname varchar(30), id_extendeddescription int

### ds_unit_reincarnation_cost
См. [mechanics-reincarnation.md, §2.4](mechanics-reincarnation.md) (код-подтверждено).

### ds_unitattacktype / ds_unitcanbebuildbyrace / ds_unitclass(bonus)
Назначение: типы атаки юнитов, доступность юнита по расе, классы юнитов и их взаимные бонусы.

### ds_unitcontainer / ds_unitcontaineraction(type) / ds_unitcontainerbindeventqueue / ds_unitcontainerhasattribute / ds_unitcontainerhasunit / ds_unitcontaineroccurrence
Назначение: армия/гарнизон как «контейнер юнитов» — состав, текущее действие, привязка к событиям. Комментарий в БД про `ds_unitcontainer.isdefault`: «армия комба по умолчанию, неперемещаемая» — вероятно, это и есть значение `id_unitcontainertype = 1`, использованное в формуле армейского рейтинга (см. [mechanics-races-world-ranking.md, §6.2](mechanics-races-world-ranking.md)), но однозначно не подтверждено.
Колонки (`ds_unitcontainer`): id_unitcontainer int [PK], id_basecomb int [FK→ds_comb], id_commander int, id_unitcontainertype int [FK→ds_unitorcontainertype], id_userinstance int, moveable bool, inaction bool, unitcontainername varchar(40), isdefault bool, id_currentcomb int, lastcoord varchar(8), speed int, use_artefacts bool

### ds_unithasattacktype
Назначение: значение урона юнита по типу атаки.
Колонки: id_unithasattacktype int [PK], id_unit int [FK→ds_unit], id_unitattacktype int [FK→ds_unitattacktype], value int

### ds_unithasattribute(_log) / ds_unithasclass / ds_unithasspecialattack / ds_unithasvulnerability / ds_unitinproduction / ds_unitneedsbuilding / ds_unitneedsscience(_log) / ds_unitorcontainertype / ds_unitvsunit(_log)
См. [mechanics-buildings.md, §18](mechanics-buildings.md) (код-подтверждено — `ds_unithasattribute` подтверждена как физическая таблица характеристик `FIGHT_ATTRIBUTE`).

### ds_units_death_log / ds_units_death_reasons
См. [mechanics-npc-terraforming.md](mechanics-npc-terraforming.md)/[mechanics-combat.md, §9.1](mechanics-combat.md) (код-подтверждено: **боевые потери НЕ проходят через эту таблицу**, только апкип/трансфер).

### ds_user
Назначение: аккаунт пользователя (портальный уровень, общий для миров одной версии сайта). Комментарий: `vacationmode` приостанавливает производство и защищает от атак; `accountactive=false` до появления места в кольце.
Колонки: id_user int [PK], id_language int, username varchar(30), email varchar(50), lastrefresh ts, registrationdate ts, vacationmode bool, admin bool, vacationstarttime ts, accountactive bool, selfdescription txt, password varchar(50), time_delta real, activate_mail int, from_partner smallint (0=free,1=rambler), reg_referer varchar(255), ip_addr inet, from_site_id int, npc bool

### ds_user2
Назначение: не установлено по одному названию — вероятно резервная/устаревшая копия структуры `ds_user` (без части полей).

**Статус (БД, live-запрос, 2026-09-02):** `ds_user` — жива и активно
используется (центральная таблица аккаунтов, статус не проверялся отдельным
`COUNT`, но её использование подтверждено кодом повсеместно). `ds_user2` —
**таблица физически не существует** ни на одном из проверенных живых миров
(`hell`/«Чёрная дыра», `phoenix`/«Сфера Парусов»), `ERROR: relation
"ds_user2" does not exist`, хотя присутствует как `CREATE TABLE` в
`schema.sql` — см. методологическую оговорку про расхождение `schema.sql`
и live-схемы в начале этого документа.

---

## U–X (userban … xft)

### ds_userban / ds_userbanreason
Назначение: бан пользователя (причина, период действия) + справочник причин — см. также [mechanics-moderation-vacation-messaging.md](mechanics-moderation-vacation-messaging.md).
Колонки (`ds_userban`): id_user bigint [FK→ds_user], id_userbanreason int [FK→ds_userbanreason], startdate ts, enddate ts, comments txt

### ds_userbattlexp
Назначение: боевой опыт userinstance (текущее значение) — см. также `ds_battlexp` выше (не установлено, чем отличаются).
Колонки: battlexp bigint, id_userinstance int NOT NULL [FK→ds_userinstance]

### ds_usercheckcode
Назначение: код подтверждения/проверки для пользователя (e-mail/регистрация?).
Колонки: id_user int [PK/FK→ds_user], code varchar(20)

### ds_userdata_change_log
Назначение: лог изменений учётных данных пользователя (логин/email/пароль, до/после).
Колонки: id_user int [FK→ds_user], old_username varchar(30), new_username varchar(30), old_email varchar(50), new_email varchar(50), change_date ts, old_password varchar(50), new_password varchar(50)

### ds_userhasability / ds_userhasalliance(status) / ds_userhasbonus / ds_userhasmessage(reserv) / ds_userhasmobile / ds_userhasreligion / ds_userhasscience(bindeventqueue)
Назначение: связующие таблицы «что есть у игрока» (способности, членство в альянсе, временные бонусы, почта, телефон, религия, изучаемые науки).
Колонки (`ds_userhasscience`): id_science int NOT NULL [FK→ds_science], id_userinstance int NOT NULL [FK→ds_userinstance], inresearch bool, starttime ts, endtime ts

### ds_userinstance
Назначение: центральная таблица «игрок в мире» (аккаунт+раса+мир = инстанс со своими координатами прогресса).
Колонки: id_user int [FK→ds_user], id_race int [FK→ds_race], id_world int [FK→ds_world], id_userinstance int [PK], lastrefresh ts, window_key int, newb_defend_enddate ts, creation_date ts, nubhelplevel int, help_var varchar(255), random_defend_enddate ts, random_defend_enddate_max ts, id_league int [FK→ds_league], build_speedup_active bool, build_speedup_enddate ts

### ds_userinstance_got_present / ds_userinstancesystemmessages / ds_userinstancewindowkey
Назначение: подарки/системные сообщения/ключ окна клиента для конкретного userinstance.

### ds_userinstancecollection
Назначение: сводная запись «где физически живёт userinstance» (в какой БД/мире) — обвязка мульти-мировой архитектуры, см. также [multi-world-architecture.md](multi-world-architecture.md).
Колонки: id_user bigint [FK→ds_user], id_userinstance bigint, id_world bigint, dbname varchar(20), admin bool, id_worldcollection int [FK→ds_worldcollection]

### ds_userland
Назначение: справочник стран пользователя (Германия/Австрия/Швейцария/др.) — для немецкого портала.
Колонки: id_land int [PK], id_localizedland int

### ds_usermessage
Назначение: справочник типовых/системных текстов сообщений.
Колонки: id_usermessage int [PK], usermessage varchar(50), id_localizedmessage int

### ds_useroccupation / ds_usersex / ds_userstatistics / ds_useradvertisedby
Назначение: анкетные данные регистрации (род занятий, пол, страна/город/год рождения, источник привлечения).

### ds_userprofidata / ds_userprofidata2
Назначение: персональные/платёжные данные premium-пользователя (ФИО, адрес, телефоны, email, статус подписки); `2` — вероятно вторая версия/копия структуры.
Колонки: id_user int [PK/FK→ds_user], profi bool, trial bool, password varchar(50), firstname/surname varchar(50), gender char(1), birthdate date, street/postcode/city/country/state varchar, phone1/phone2/fax varchar(30), email varchar(100), creationdate/startdate/enddate/canceldate ts, cancelled bool, active bool, comments txt

**Статус (БД, live-запрос, 2026-09-02):** `ds_userprofidata2` —
**таблица физически не существует** ни на одном из проверенных живых миров
(`hell`/«Чёрная дыра», `phoenix`/«Сфера Парусов»), `ERROR: relation
"ds_userprofidata2" does not exist`, хотя присутствует как `CREATE TABLE`
в `schema.sql` — см. методологическую оговорку про расхождение `schema.sql`
и live-схемы в начале этого документа. Статус `ds_userprofidata` отдельным
`COUNT` не проверялся в рамках этой задачи.

### ds_userprofitech* (кластер из ~7 таблиц)
Назначение: технические характеристики окружения premium-пользователя (браузер/CPU/ОС/RAM/скорость интернета/Flash) — `ds_userprofitechbrowser`, `ds_userprofitechcpu`, `ds_userprofitechdata` (центральная), `ds_userprofitechflash`, `ds_userprofitechinternetspeed`, `ds_userprofitechos`, `ds_userprofitechram`.

### ds_userprovidedbypartner / ds_usersallowed(collection)
Назначение: привязка пользователя к партнёру/реферальной программе + лимит разрешённых миров на пользователя.

### ds_usersupportrequest
Назначение: обращение пользователя в поддержку.
Колонки: id_user int NOT NULL [FK→ds_user], date ts NOT NULL, world_name varchar(50), is_premium bool NOT NULL, text txt NOT NULL

### ds_user_allow_instances / ds_user_buy_resource_count / ds_user_delete_list(log/reason) / ds_user_got_present / ds_user_has_image / ds_user_help_show / ds_user_locking_error / ds_user_password_change / ds_user_premium_ban / ds_user_refresh_black_list / ds_user_refresh_process_list / ds_user_registration_recomend / ds_user_stat_in / ds_user_stat_out / ds_user_temp20040528
Назначение: разрозненный кластер служебных таблиц уровня `ds_user` — квоты миров, счётчики покупок, удаление аккаунта (список/лог/причины), подарки, аватар-изображение, показанные подсказки, ошибки locking-слоя (см. [multi-world-architecture.md](multi-world-architecture.md)), восстановление пароля (комментарий: «Table for remaind password service»), премиум-бан, чёрные списки refresh, реферальная регистрация, статистика входа/выхода из миров. `ds_user_temp20040528` — миграционный артефакт от 2004 года, практического значения не имеет.

### ds_useraction / ds_useractionproducesevent / ds_useractiontype
Назначение: протоколирование всех действий пользователя (анти-чит, согласно комментарию в БД) + связь типа действия с генерируемым событием + справочник типов действий.
Колонки (`ds_useraction`): id_useractiontype int [FK→ds_useractiontype], actionstarttime ts, result txt, id_useraction int [PK], id_userinstance int

### ds_useradsstatistics
Назначение: статистика показов/кликов рекламы на пользователя.
Колонки: id_user int, views int, clicks int, remainingviews int, nextstep int, lastclick ts, lastview ts

### ds_useralliancestatus / ds_userallowedmessagescope
Назначение: справочник статусов участника альянса + разрешённые области видимости сообщений для userinstance.

### ds_useravatar / ds_useravatarhasbonus
Назначение: аватар пользователя (уровень/опыт — вероятно отдельная RPG-механика прогресса аккаунта) + бонусы атрибутов от аватара.
Колонки (`ds_useravatar`): id_avatar int, id_useravatar int [PK], avatarname varchar(30), level int, xp real

### ds_version
Назначение: служебная таблица версии БД/схемы.
Колонки: id_version int [PK], version varchar(10), lastupdated ts

### ds_victor / ds_victor_answer / ds_victor_question / ds_victor_result(_detailed)
Назначение: система викторин «Виктор» — см. [mechanics-quests-events.md](mechanics-quests-events.md).

### ds_vocationmode
Назначение: режим «отпуска» (vacation mode) userinstance — см. [mechanics-moderation-vacation-messaging.md](mechanics-moderation-vacation-messaging.md).
Колонки: id_vocationmode int [PK], startdate ts, enddate ts, id_userinstance int [FK→ds_userinstance], password varchar(20)

### ds_world / ds_world_copy / ds_worldcollection / ds_worldcopy / ds_worldhaslanguage / ds_worldhastype / ds_worldsstatisic / ds_worldstatus / ds_worldtype / ds_worldunionuserinfo
Назначение: справочник миров и мульти-мировая архитектура (реестр по всей коллекции БД, языки, типы, статусы, статистика) — см. также [multi-world-architecture.md](multi-world-architecture.md).
Колонки (`ds_worldcollection`): id_world bigint, dbname varchar(20) («DB name in settings»), max_users bigint NOT NULL, worldname varchar(100), profi bool, description txt, id_language bigint [FK→ds_language], id_worldtype bigint [FK→ds_worldtype], multy_access bigint («Multy registration»), id_worldcollection int [PK], world_identifier varchar(3), sort_order int, for_new_players bool, world_status int, short_desc txt

**Статус `ds_worldcopy` (БД, live-запрос, 2026-09-02):** **таблица
физически не существует** ни на одном из проверенных живых миров
(`hell`/«Чёрная дыра», `phoenix`/«Сфера Парусов»), `ERROR: relation
"ds_worldcopy" does not exist`, хотя присутствует как `CREATE TABLE` в
`schema.sql` — см. методологическую оговорку про расхождение `schema.sql`
и live-схемы в начале этого документа. **`ds_world_copy` (с подчёркиванием)
— отдельное имя таблицы в дампе, live-статус отдельно не проверялся** —
не путать эти два похожих имени: проверка касалась именно `ds_worldcopy`
без подчёркивания.

### ds_worldtransfer / ds_worldtransfer_allowed_user / ds_worldtransfer_artefact_log / ds_worldtransfer_log / ds_worldtransfer_stat / ds_worldtransfer_worlds_list / ds_worldtransfer_worlds_relation / ds_worldtransfercoordrelation / ds_worldtransferquest
См. [mechanics-world-transfer.md](mechanics-world-transfer.md) (детально задокументировано).

### payments_summ_v
Назначение: несмотря на суффикс `_v` («view»), это TABLE, не представление — вероятно материализованный/кэшированный агрегат сумм платежей (голд/серебро).
Колонки: gc_sum bigint, sc_sum bigint

### tmp_ds_unitcontainerhasunit / tmp_premium_stat / tmp_stat_user / tmpds_cell
Назначение: временные/рабочие копии таблиц для миграций/пересчётов/выгрузок статистики.

### x_temp_read / xft
Назначение: служебные одностолбцовые таблицы (`line txt`) для построчного чтения — вероятно вспомогательные при импорте/обработке текстовых данных через `COPY`.

---

## Представления (views)

Все 21 представление (`qry_overallranking`, `qry_armyranking`,
`subqry_armyranking(_average/_final/_with_transport)`,
`subqry_artefactranking(_average/_final)`,
`subqry_buildingranking(_average/_final)`,
`subqry_productionranking_raw(_old)/(_average/_final/_max)`,
`subqry_scienceranking(_average/_final)`, `subqry_currentbuilds`) —
формула общего рейтинга игрока — **разобраны дословно и полностью** в
[mechanics-races-world-ranking.md, §6.2](mechanics-races-world-ranking.md),
не дублируются здесь.

## Перекрёстные ссылки

Таблицы из этого справочника, уже код-подтверждённо разобранные в других
документах (сводка для быстрой навигации):

| Тема | Документ |
|---|---|
| Реинкарнация (`ds_army_weight*`, `ds_army_reincarnation*`, `ds_unit_reincarnation_cost`) | [mechanics-reincarnation.md](mechanics-reincarnation.md) |
| Antibot/CAPTCHA (`ds_antibot_*`) | [protocol-client-server.md, §3.2](protocol-client-server.md) |
| Gate Key (`ds_gate_*`) | [multi-world-architecture.md, §5.1](multi-world-architecture.md) |
| Репликация (`ds_replication*`) | [utils-and-maintenance.md, §9](utils-and-maintenance.md) |
| VK/OAuth (`ds_oauthinfo`) | [client-engine-and-protocol.md, §8.8](client-engine-and-protocol.md) |
| Чат (`ds_chat_*`) | [admin-panel.md, §2.4.1](admin-panel.md) |
| Захват колоний (`ds_capture_colony_history`) | [mechanics-combat.md, §8](mechanics-combat.md) |
| Гибель юнитов (`ds_units_death_*`) | [mechanics-combat.md, §9.1](mechanics-combat.md) |
| NPC (`ds_npc_*`) | [mechanics-npc-terraforming.md, §1.2.1](mechanics-npc-terraforming.md) |
| Здания/наука/юниты — валидаторы (`ds_*hasattribute`, `ds_*needsscience`, `ds_unitvsunit`, `ds_gamemodifier*`) | [mechanics-buildings.md, §18](mechanics-buildings.md) |
| Деньги/платежи (`ds_money_account*`, `ds_money_list*`) | [billing-and-monetization.md, §9](billing-and-monetization.md) |
| Альянсы/мир (`ds_peace_*`, глобальный альянс) | [mechanics-alliances-diplomacy.md](mechanics-alliances-diplomacy.md) |
| Сообщения (`ds_message*`) | [mechanics-moderation-vacation-messaging.md, §3.7](mechanics-moderation-vacation-messaging.md) |
| Торговля/биржа (весь кластер `ds_trade_*`) | [mechanics-economy-trade.md](mechanics-economy-trade.md) |
| Перенос между мирами (весь кластер `ds_worldtransfer*`, `ds_storeuser*`, `ds_free_ring_transfer*`) | [mechanics-world-transfer.md](mechanics-world-transfer.md) |
| Расы/религия/рейтинг (`ds_race*`, `ds_religion*`, `ds_ranking*`) | [mechanics-races-world-ranking.md](mechanics-races-world-ranking.md) |
| Квесты/викторины/анкета (`ds_quest*`, `ds_victor*`, `ds_form*`) | [mechanics-quests-events.md](mechanics-quests-events.md) |
| Терраформинг (`ds_terraforming*`) | [mechanics-npc-terraforming.md, Часть 2](mechanics-npc-terraforming.md) |
| Турниры (`ds_turnir_pause`) | [mechanics-tournaments.md](mechanics-tournaments.md) |
