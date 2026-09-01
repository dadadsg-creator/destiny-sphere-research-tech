# Клиентский взгляд: экономика, альянсы, мир, наука, личный кабинет

> Источник: декомпилированный Flash-клиент (ActionScript 2, JPEXS) —
> `scripts/__Packages/*.as`. Клиент читался ПОЛНОСТЬЮ (не выборочно) по
> списку файлов из задания. Локальные переменные (`_loc1_`, `_loc2_`…) в
> AS2-декомпиляции обезличены — их смысл не восстанавливается наугад, там,
> где он важен, он выведен из контекста (имена методов `reqvestdata.*`,
> строковые литералы, порядок использования) и помечен как «выведено из
> контекста», а не как точный факт.
>
> Документ **дополняет** уже существующую серверную документацию
> (`mechanics-buildings.md`, `mechanics-economy-trade.md`,
> `mechanics-alliances-diplomacy.md`, `mechanics-research.md`,
> `mechanics-races-world-ranking.md`, `mechanics-npc-terraforming.md`,
> `mechanics-quests-events.md`, `mechanics-moderation-vacation-messaging.md`,
> `mechanics-world-transfer.md`) — здесь **не повторяются** уже описанные
> там формулы/таблицы БД, а фиксируется то, что видно только со стороны
> клиента: имена XML-тегов/атрибутов протокола запросов, клиентская
> валидация ввода, UI-паттерны и найденные несоответствия с серверными
> документами.
>
> Все ссылки вида `client-decompiled: <файл>.as` — на файлы в
> `scripts/__Packages/`. Класс `reqvestdata` (`reqvestdata.as`, 1016 строк)
> **не входил в объём этой задачи** (его разбирает другой агент) — поэтому
> вызовы вида `reqvestdata.XXX(a, b, c)` цитируются только как **имя
> команды + порядок/предположительный смысл параметров, выведенный из
> локального кода**, а не как подтверждённые имена XML-атрибутов (в
> отличие от `DS_Classes.MN_Alliance.RequestDataAlli` и
> `DS_Classes.MN_Menu.MN_Cabinet.requestDataCabinet`, которые сами строят
> XML-строку прямо в теле метода — там теги/атрибуты процитированы дословно
> и это гарантированно точно).
>
> **Обновление**: часть файлов, разобранных в этом документе, впоследствии
> была сверена с настоящими (не декомпилированными) исходниками AS2 из
> SVN-копии разработчика клиента (`old-sphere/SPHERE_client/`, реальные
> имена переменных и комментарии, включая `reqvestdata.as` — точечно, для
> проверки конкретных вызовов, упомянутых ниже, без полного разбора этого
> файла). Пометки `client-source: файл.as:строка` ниже — это подтверждение
> по настоящему исходнику (точные номера строк могут отличаться от исходных
> `client-decompiled`-ссылок, так как декомпилированный и настоящий файлы
> набраны по-разному). Там, где сверка выявила расхождение с изначальным
> декомпилированным прочтением, это отмечено явно.

---

## 0. Сквозные находки (актуальны для всех панелей)

### 0.1 Схема справки — `help_class.as`

Каждая панель вызывает `help_class.initHelp("<KEY>")`, что открывает **веб**-страницу (не Flash) в новой вкладке:

```
/ds/help/?name=<KEY>[&buildid=<локализованное_имя_здания>&race=<raceId>]
```

На VK-сборке (`_level0.socialNet == "VK"`) префикс — `http://ds.rambler.ru` (`client-source: help_class.as:54-58`, подтверждено настоящим исходником — было `client-decompiled: help_class.as:35-38`) — историческое подтверждение, что VK-версия хостилась через rambler.ru.

Собранный по всем прочитанным файлам каталог ключей `<KEY>` (пригодится как список страниц веб-справки, которые надо будет либо портировать, либо заново написать для ремастера):

| Ключ | Панель |
|---|---|
| `TRADE_MENU` | Биржа/торговля (`trade_menu_class.as`) |
| `AUCTION_MENU` | Аукцион (`auctionmenuclass.as`) |
| `BUILD_MENU` | Список построек (`build_menu.as`) |
| `BUILD_INFO` | Информация о здании (`buildinfo.as`) |
| `BUILD_MENU_GATE` | То же, но специально для здания с `id=5` (Центральные Ворота) |
| `BUILDNIG_ARTEFACT` | Артефакты здания (`buildartefactclass.as`) — **опечатка в самом клиенте** (`BUILDNIG` вместо `BUILDING`), см. §9 |
| `ART_DEPOT` | Депо артефактов (`art_depot_menu.as`) |
| `ARCHEOLOGY_MENU` | Институт археологии (`archeology_class.as`) |
| `TERRAFORM_MENU` | Терраформинг (`terraform_class.as`) |
| `RESEARCH_MENU` | Наука (`researchmenu.as`) |
| `ALLIANCE_MENU` | Вход в альянс/создание (`alliance_menu_class.as`) |
| `ALLIANCE_MEMBER` | Список участников альянса (`alliancememberclass.as`) |
| `ALLIANCE_SETTING` | Настройки альянса (`alliance_setting_class.as`) |
| `ALLIANCE_PEACE` | Мир между альянсами (`peace_aliance.as`) |
| `PRIVATE_PEACE_HELP` | Приватный мир (`peace_private.as`) |
| `COLONIZATION` | Колонизация (`colonyzationclass.as`) |
| `COLONY_NAVIGATE` | Навигатор колоний (`colonynavigate.as`) |
| `RELIGION` | Религия (`religion_class.as`) |
| `REINCARNATE_MENU` | Реинкарнация (`reincarnate_menu_class.as`) |
| `OTPUSK_MENU` | Отпуск (`otpusk_class.as`) |
| `QUEST_MENU` | Квесты игрока (`quest_menu_class.as`) |
| `TRANSFER` | Передача ресурсов между сотами (`transfer_class.as`) |
| `CREATE_NEW_MAIL` | Написать письмо (`create_new_mail.as`) |

### 0.2 Общий паттерн окна

Почти каждая панель — подкласс `window` и содержит одинаковый набор:
предзагрузка библиотеки картинок зданий/юнитов/артефактов через
`debug.link.loader.addLoader(...)` → крутится `loader_timer` (`onEnterFrame`,
проверка `getBytesLoaded()>=getBytesTotal()`) → только когда добежало,
вызывается `create_menu()`. Кнопка закрытия всегда `close_button` →
`this._parent.close_menu()`. Кнопка справки — `HELP_BUTTON` рядом с
крестиком. Значок текущего здания (если открыто из контекста ячейки)
берётся из `debug.link.active_cell` (мини-XML `<temp c="..."/>`) →
`sota.getBuildidByCell(...)` → `game.getLocalNames(id).name` — если не
найдено, дефолт **`"STROIKA"`** (буквально «стройка» — заглушка-иконка).
Переключение вкладок верхнего уровня (`tabs_array`) всегда отправляет
`reqvestdata.call_tabs(id)` и предварительно сохраняет
`debug.link.temp_active_cell`/`temp_pointCell`, чтобы новая панель могла
восстановить контекст открытой ячейки.

### 0.3 Клиентские настройки (не протокол, чисто локальный `SharedObject`)

Через `new setting().saveSetting(key, value[, worldName])` /
`loadSetting(...)` персистятся **чисто клиентские** предпочтения — ни один
из них не уходит на сервер:

| Ключ | Где | Смысл |
|---|---|---|
| `tradeBtns` | `auctionmenuclass.as` | выбранный размер страницы аукциона (7/25/75) |
| `ANONYMOUSLY_BUY` | `auctionmenuclass.as` | чекбокс «покупать анонимно» на аукционе |
| `unit_table`, `art_filter_table` | `auctionmenuclass.as` | локально кэшированные справочники для построения дерева фильтров аукциона (загружаются per-world, `game.getnameLocalNames()`) |
| `arch_count_def`, `arch_time_def`, `arch_dest_def`, `arch_size_def` | `archeology_class.as` | запомненные значения формы отправки экспедиции (только премиум) |
| `advhint` | `adviceclass.as` | номер текущей подсказки адвайзера, per-world |
| `color_red`/`color_green`/`color_blue` | `settingmenu.as` | пользовательский тон окон интерфейса |
| `ShowSotes`, `AlienComp`, `AnimateStatus`, `DeleteSysMessage`, `ShowShadowMenu`, `ShowShadowBuild`, `ShowResCounters`, `ConfirmationsWin`, `Advice`, `Reducing`, `qualityFlash` | `settingmenu.as` | чисто визуальные переключатели, весь файл `settingmenu.as` не делает ни одного сетевого вызова |

---

## 1. Экономика, торговля, здания, артефакты

### 1.1 Биржа — `trade_menu_class.as`

Панель выставления лота на продажу (одиночная, не аукцион). 4 вкладки
товара: `RESOURSE`(id `"1"`)/`UNITS`(`"2"`)/`ARTEFACTS`(`"3"`) — и, только
если `game.getPremium()`, четвёртая — `CALCULATOR`(`"4"`), которая **не
шлёт ничего на сервер**: это чисто клиентский калькулятор «сколько ресурса
X стоит юнит/артефакт Y по текущему курсу», доступный только премиум-
аккаунтам (`drawcalc()` — весь расчёт локальный, по данным, уже присланным
в `this.matrix`/`this.price`).

**Клиентская валидация перед отправкой** (`init_button`, кнопка
`But_trade.onRelease`, `trade_menu_class.as:1069-1196`) — всё это
**дополнительно** к тому, что проверяет сервер (`GL_TradeCanSellByThisPriceAndScope`
и др. из `mechanics-economy-trade.md`):
- предмет для продажи выбран (`trade_for` не `undefined`);
- если включён режим «личная продажа» (галочка `check_isauction`,
  внутренний id `"NO_TRADE"`) — введённое имя покупателя должно быть 1–20
  символов, иначе поле молча очищается и флаг продажи «для всех» ставится
  автоматически (`check_isauction.setState(true)` при вводе текста);
- цена > 0, время (`timeTrade`) > 0, количество > 0, локация (скоуп,
  `combo_locat`) выбрана;
- если категория товара — ресурс (`id_cat.charAt(0)=="1"`) — количество
  должно быть `>= minquntity` (минимальный лот, приходит от сервера);
- если продаётся артефакт (категория 3) — при выборе конкретного стека
  клиент сверяет, что у выбранного стека `amount <= запрошенное количество`
  (защита от продажи больше, чем реально есть в стеке).

**Точный протокол вызова** (подтверждено настоящим исходником —
`client-source: trade_menu_class.as:1213` вызывает
`reqvestdata.sale(scope,article,category,amount,tradetime,tradeby,cattradeby,price,isauction,ispersonal,nick,pass,isanon,death)`,
а `reqvestdata.as:617-626` сам строит XML прямо в теле метода, то есть это
гарантированно точно, а не выведено из контекста):

```
<sell id_tradescope="<scope>" id_fromcategory="<category>" id_fromarticle="<article>"
      id_fromrealarticle="<article>" amount="<amount>" id_tocategory="<cattradeby>"
      id_toarticle="<tradeby>" price="<price>" tradetime="<tradetime>" isauction="<isauction>"
      ispersonal="<ispersonal>" nick="<nick>" pass="<pass>" death="<death>"
      [anonymous="<isanon>"] />
```

Атрибут `anonymous` присутствует, только если `anon` (13-й параметр)
определён. `isauction` — `1` (не `0`), когда галочка «не аукцион»
(`check_isauction`/`NO_TRADE`) **не** выставлена — то есть по умолчанию лот
идёт как обычный аукционный, а «личная продажа без торга» — явно
отключаемый режим. `pass` в этом вызове с биржи всегда `""` (зарезервировано
под другой сценарий, не встреченный в этом файле). `id_fromarticle` и
`id_fromrealarticle` дублируют друг друга — оба равны `article`, второе имя
поля, видимо, задел под сценарий, где «реальный» товар отличается от
показанного (например, будущая многоуровневая подмена лота), не
реализованный в этом клиенте.

**Расхождение/новая деталь vs `mechanics-economy-trade.md`**: коридор цены
(`min_price`/`max_price` от `total_price ± total_price*min_order/100`,
`client-source: trade_menu_class.as:988-999`, было `client-decompiled:
trade_menu_class.as:947-966`) считается клиентом **точно по той же
формуле**, что описана в серверном документе для `GL_TradeCountMinAndMaxPrices`
— полезное независимое подтверждение формулы, а не расхождение.
Видимость полей налога/цены/тренда/личной продажи/анонимности управляется
булевыми флагами `this.matrix.nalogkey/trendkey/personkey/anonymouskey` —
это, по всей видимости, клиентское отражение мировых
`DISABLE_*`-модификаторов из §5.2 серверного документа (`RING_TRADING` и
т.п.), но однозначного маппинга имя-в-имя не видно — это предположение.

### 1.2 Аукцион — `auctionmenuclass.as`

Отдельная панель (не то же самое, что «Биржа» — другой список лотов,
другая механика: аукцион с текущей ценой/следующей ставкой/торговцем).

**Найдено — живое обновление лота с сервера** (`update_menu(obj_new_data)`,
`:76-153`): панель поддерживает **incremental push-обновление** одного
конкретного лота без перезапроса всего списка, по трём статусам:
`obj_new_data.status == "BUY"` (лот куплен — блокируется), `"BID"` (сделана
ставка — обновляются цена/следующая цена/покупатель/дата), `"NOT"` (лот
больше недоступен, но без покупки). Это подтверждает, что аукцион в игре —
не просто REST-опрос, а поддерживает адресные обновления конкретного лота,
пока панель открыта.

**Подсказка цены покупки** (`onRollOver_` на кнопке `but_buy`,
`:768-783`): всплывающий `TITLEHINTAUCTION` показывает разбивку стоимости
по `KAD/NANO/STAS/FOOD/PROD/TIME` (`obj.getprice.{K,N,S,F,P,T}`) — то есть
сервер отдаёт для каждого лота предрасчитанную стоимость **в разбивке по
пяти ресурсам плюс время**, а не только единую цену — деталь, не
встреченная в прочитанном фрагменте `mechanics-economy-trade.md`.

**Дерево фильтров** строится по трём разным схемам в зависимости от
категории:
- `UNITS` — дерево раса → уровень → юнит (`arrayTestTree[race].childArr[level]`);
- `ARTEFACTS` — дерево скоуп → (`SIZE`|`BONUS`) → конкретное значение;
- `RESOURCES` — плоский список.

Отправка фильтра — `reqvestdata.applyFiltersAuction(sortId, page,
treeSelectStr, filterStr, traderName, buyFromStr, scopeComboId, sortString,
pageSize)` (`client-source: auctionmenuclass.as:984`, было `:997`). Деревья
сериализуются в компактную строку вида `id1,id2[child1,child2],id3` через
рекурсивный `ArrToStr` — **клиентский, не XML-протокол** формат
(человекочитаемая CSV-с-скобками нотация, специфичная для этого экрана).

**Точный протокол подтверждён** (`reqvestdata.as:646-661` сам строит XML,
параметр `nameprivate` — это и есть `traderName`):

```
<setfilterauction page="<page>" page_size="<pageSize>" typebuy="<sortId>"
                  select="<treeSelectStr>" sort_place="<scopeComboId>"
                  buy_by="<buyFromStr>" filter="<filterStr>" sort="<sortString>"
                  [trader="<traderName>"] />
```

`trader` добавляется, только если `nameprivate.length > 3` — короткие имена
фильтра по продавцу молча игнорируются. Покупка лота —
`reqvestdata.buy_lot(id, price)` → `<buy id_trade="<id>" newprice="<price>"
anonym="0|1"/>` (`reqvestdata.as:836-842`), где `anonym` берётся из
`debug.link.BYU_ANONYMOUSLY` — того же чекбокса «покупать анонимно»
(`ANONYMOUSLY_BUY`), что описан в §0.3.

### 1.3 Институт археологии — `archeology_class.as`

Панель тренировки археологов + список активных экспедиций + форма отправки
новой экспедиции. **Найдено, чего нет в `mechanics-economy-trade.md`**:
групповая отправка экспедиций (`amount_group`, до 300 в одной группе,
`client-source: archeology_class.as:232-242`, было `:236-238`) доступна
**только премиум**-аккаунтам — у обычных `amount_group` жёстко = 1 и
заблокирован (`mov_team_send.amount_group._disabled()`). Обычным игрокам
разрешена только отправка одиночных экспедиций по одному человеку за раз в
цикле.

Вызов и протокол подтверждены дословно (`client-source:
archeology_class.as:302`, `reqvestdata.as:548-552`, оба реальные исходники):
`reqvestdata.sendArchExpedition(count, local, time, size_min, countgroup)` →

```
<archeologyteam dest="<local>" count="<count>" time="<time>" size="<size_min>" countgroup="<countgroup>"/>
```

то есть порядок параметров (кол-во людей, направление поиска, срок
возврата, минимальный размер артефакта, размер группы) — не догадка, а
факт, читаемый прямо из XML-строки.

### 1.4 Артефактная статистика соты — `artefactstatclass.as`

Панорамный вид **всей соты** (не одного здания) в виде мозаики
шестиугольных ячеек, раскрашенных по типу артефактных слотов:

| `array_arts[n].type` | Спрайт ячейки |
|---|---|
| `STASIS_ENERGY` | `STAT_CELL` |
| `KADERIUM` | `KAD_CELL` |
| `NANO_CRYSTAL` | `NANO_CELL` |
| `FOOD` | `FOOD_CELL` |
| `PRODIUM` | `PROD_CELL` |
| `WAR` | `WAR_CELL` |
| `SOTES` | `SOTA_CELL` |
| (иное) | `SOTA_CELL` (дефолт) |

Клик по ячейке → `reqvestdata.get_artefact(cellNum)` (открывает окно
вставки/снятия артефактов для этого здания) →
`<getbuildmenu c="<активная ячейка>" tab="BuildingArtefact"/>` (`client-source:
reqvestdata.as:191-196`). Вставка/снятие — `reqvestdata.insert_artefact(id)`
→ `<attachartefact c="..." id="<id>"/>` / `delete_artefact(id)` →
`<detachartefact c="..." id="<id>"/>` (`reqvestdata.as:761-772`) — оба
метода принимают **только один параметр** `id`, без второго параметра
номера вкладки (см. уточнение в §1.6 — предположение о `tabNumber` для
этих двух методов не подтвердилось).

**Найдено — премиум-фича предупреждения об истечении артефакта**
(`glowMinArt()`, `client-source: artefactstatclass.as:590-676`, было
`:554-636`, работает только внутри `if(game.getPremium())`):
для каждой ячейки ищется артефакт с минимальным `remain` (оставшееся время
жизни, в секундах) и подсвечивается цветным маркером:

| `remain` | Цвет |
|---|---|
| ≥ 2 592 000 сек (30 дней) | зелёный |
| 604 800 – 2 592 000 сек (7–30 дней) | жёлтый |
| < 604 800 сек | красный |

(пороги дословно подтверждены — `client-source: artefactstatclass.as:659-671`)

Это **не задокументировано** в серверных доках — чисто клиентская
премиум-фича «скоро истекает артефакт», использующая уже присланное поле
`remain`.

### 1.5 Депо артефактов — `art_depot_menu.as`

Список неприкреплённых артефактов игрока, с сортировкой
(`reqvestdata.artdepotsort(sortType, sortSize, sortScope)` →
`<getbuildmenu c="..." tab="ArcheologyArtefact" sorttype="<sortType>"
sortsize="<sortSize>" sortscope="<sortScope>"/>`, `client-source:
reqvestdata.as:868-872`, порядок параметров подтверждён дословно) и
массовым удалением по чекбоксам (`reqvestdata.delArtFromDepot(idArray)` →
`<dropartefact id="<idArray.join(',')>"/>`, `reqvestdata.as:828-834`).

**Найдено**: у части артефактов есть поле `link` (`{id, text, hinttext}`)
— вместо текста бонуса показывается кликабельная кнопка, отправляющая
`reqvestdata.artActivate(this.id)` → `<artefactactivate id="<id>"/>`
(`client-source: art_depot_menu.as:229-232`, `reqvestdata.as:947-951`). Это означает, что часть
артефактов в депо — **разовые активируемые предметы** (не пассивные
бонусы, а что-то вроде расходуемого баффа/подарка), что не встречалось в
прочитанном фрагменте серверной документации по артефактам.

### 1.6 Артефакты конкретного здания — `buildartefactclass.as`

Отличается от §1.4 тем, что оперирует **слотами одного конкретного
здания** через отдельный набор данных (`array_art`/`array_art_attachment`,
`tabs_array` — вкладки самого окна, не параметр команды).

**Исправление найденного в декомпилированном чтении**: в реальном
исходнике вызовы из этого файла — `reqvestdata.insert_artefact(this.id_art)`
(`client-source: buildartefactclass.as:281`) и
`reqvestdata.delete_artefact(this._parent._parent.id_art)`
(`buildartefactclass.as:391`) — передают **только `id`**, без второго
параметра. Сам `reqvestdata.as:761-772` (реальный исходник, XML строится
прямо в теле метода) подтверждает, что `insert_artefact`/`delete_artefact`
принимают ровно один аргумент:

```
<attachartefact c="<активная ячейка>" id="<id>" />
<detachartefact c="<активная ячейка>" id="<id>" />
```

То есть предположение из декомпилированного чтения про параметр
`tabNumber` **не подтвердилось** — такого параметра в клиенте нет вовсе.
Привязка к конкретному зданию/вкладке идёт исключительно через
`debug.link.active_cell` (атрибут `c`), общий для всех вызовов работы с
артефактами (§1.4 и этот раздел используют один и тот же протокол, разница
только в том, какие данные подставлены в `array_art`/слоты на экране).

### 1.7 Список построек — `build_menu.as`

Список зданий, доступных к постройке на выбранной пустой ячейке, с
подсветкой недостающих ресурсов красным (`obj.kad_res.textColor =
16711680` и т.д. для `K/N/S/F/P/W/GC/SC`).

**Важное несоответствие с `mechanics-buildings.md`**: серверный документ
(§2.3) утверждает, что списание `SILVER_CREDIT`/`GOLD_CREDIT` за постройку
**не реализовано до конца** в `UA_ConstructBuilding` (комментарий «Здесь
будет снятие денег» без кода). Клиент же **полноценно рендерит** колонки
`GC`/`SC` в списке построек (`obj.gc_res.text = obj.GC; obj.sc_res.text =
obj.SC;`, с тем же самым покраснением при нехватке, `client-source:
build_menu.as:127-141`, было `:170-186`),
и даже переключает визуальный стиль плашки (`build_block_2` вместо
`build_block`), если у здания `GC<=0 && SC<=0` (т.е. когда постройка **не**
требует денег). Это значит, что клиентский UI полностью готов показывать
денежную стоимость постройки — расхождение с (предположительно
недоделанной) серверной логикой списания стоит зафиксировать явно как
открытый вопрос (см. §9).

Постройка — `reqvestdata.putbuild(id)` → `<putbuild c="<активная ячейка>"
b="<id>"/>` (`client-source: reqvestdata.as:219-223`, дословно), **без
явного параметра ячейки в самом методе** — контекст ячейки берётся из
`debug.link.active_cell`, установленного до открытия панели, и передаётся
как атрибут `c`. Не видно клиентской
поддержки **батч-постройки по нескольким ячейкам** (параметр `c` через
запятую из `UA_ConstructBuilding.class.php:126`, описанный в
`mechanics-buildings.md` §4.1) — судя по этому файлу, обычный игровой UI
строит по одной ячейке за раз; открытый вопрос, реализует ли эту
многоячеечную отправку какой-то другой (не входивший в объём) клиентский
класс.

### 1.8 Информация о здании — `buildinfo.as`

Показывает состояние конкретного (уже поставленного/строящегося) здания и
доступные действия — маппинг «поле от сервера → кнопка → команда → XML»
(вся таблица подтверждена настоящими исходниками — `client-source:
buildinfo.as:162-305` вызовы, `reqvestdata.as:219-259` строит XML прямо в
теле метода, всегда через контекст `debug.link.active_cell`, атрибут `c`):

| Поле `info_build.info.*` | Кнопка | Команда | XML |
|---|---|---|---|
| `un` (не `undefined`) | Отменить постройку | `reqvestdata.cancelbuild()` | `<cancelbuild c="..."/>` |
| `destr` | Снести | `reqvestdata.delbuild()` | `<delbuild c="..."/>` |
| `up` | Отменить апгрейд | `reqvestdata.cancelupgrade()` | `<cancelupgrade c="..."/>` |
| `activate` | Активировать | `reqvestdata.activatebuild()` | `<activatebuild c="..."/>` |
| `ia` **и** `ac` | Отменить активацию | `reqvestdata.cancelactivatebuild()` | `<cancelactivate c="..."/>` (**не** `cancelactivatebuild` — метод и XML-тег называются по-разному) |
| `ia` **без** `ac` | (статус-текст `DEACTIVATION`, кнопки нет) | — | — |
| `deact` | Деактивировать | `reqvestdata.deactivatebuild()` | `<deactivatebuild c="..."/>` |
| `upgrade` (гейт `dataupgrade.allow`) | Апгрейд | `reqvestdata.upgradebuild()` | `<upgradebuild c="..."/>` |

**Прямое клиентское подтверждение находки из `mechanics-buildings.md`
§2.2**: серверный документ отмечал, что поле `inactivation` — общий флаг и
для активации, и для деактивации, различаемый лишь косвенно. Клиент **это
подтверждает буквально**: одно и то же поле `ia` используется и для
«идёт активация» (когда есть `ac`), и для «идёт деактивация» (когда `ac`
нет) — т.е. серверная и клиентская модели совпадают 1:1.

**Подтверждение «7 веток науки» из независимого источника**: подсказка
апгрейда (`HINT_UPGRADE`, `client-source: buildinfo.as:347-367`, было
`:329-349`) раскладывает `sctarg`/`sccur`
(целевой/текущий уровень науки по каждой ветке) в ровно 7 полей `f1..f7`,
подсвечивая красным те, где `sccur < sctarg` — независимое подтверждение
количества веток науки (7) из `mechanics-research.md`.

**Найдено — платное ускорение постройки** (`pay_service` иконка внизу
панели, `msg_array[1]`, desc `"BLD"`, `client-source: buildinfo.as:501-525`,
было `:456-478`): аналогично «ускорению науки» (`FAST_RESEARCH` в §1.9
ниже), у зданий тоже есть донат-сервис мгновенного/частичного ускорения
текущей стройки/апгрейда — `reqvestdata.getPayService(desc, val, "BLD")` →
`<getPayService c="..." for="<desc>" amount="<val>" type="<type>"/>`
(`reqvestdata.as:92-96`, XML строится дословно в теле метода — это общий
метод для всех донат-ускорений в игре, не только зданий: та же команда с
разными `type` встречается в §1.9/§4.1 ниже). Это не упомянуто явно в
прочитанном фрагменте `mechanics-buildings.md`/`billing-and-monetization.md`
— стоит проверить отдельно.

### 1.9 Терраформинг — `terraform_class.as`

Список типов грунта с ценой/временем и статусом:
`allow==1` → кнопка «Терраформировать» (`reqvestdata.start_terraform(type)`
→ `<terraform c="..." type="<type>"/>`); `allow==2` **и** `game.getPremium()`
→ кнопка «Остановить терраформинг» (`reqvestdata.stop_terraform(type)` →
`<cancelterraform c="..." type="<type>"/>`, `client-source:
reqvestdata.as:774-786`, оба метода строят XML дословно); `allow==2` без
премиума **или** любое другое значение → неактивная кнопка
«TERRAFORMING_NOT_AWALIBLE» (буквально такая опечатка в игровом ключе
локализации — `NOT_AWALIBLE` вместо `NOT_AVAILABLE`, встречается и в
`auctionmenuclass.as`; подтверждено дословно `client-source:
terraform_class.as:130,144`).
**Найденная деталь**: отмена уже идущего терраформинга — **премиум-фича**;
у обычных аккаунтов кнопка просто показывает «недоступно», даже если
`allow==2` (терраформинг идёт). Это стоит сверить с
`mechanics-npc-terraforming.md` §2.6 (UA_CancelTerraform) — не проверено
построчно в рамках этой задачи, но клиентский гейт зафиксирован здесь как
факт клиентского поведения.

---

## 2. Альянсы и дипломатия

### 2.1 Вход/создание/вступление — `alliance_menu_class.as`

Одна панель на два состояния: если `in_alliance_object.inal` — показывает
карточку своего альянса с кнопкой «Выйти» (`reqvestdata.exitAlliance(id)`);
иначе — форма создания **или** вступления в существующий (выбор из
комбобокса `alliance_array`).

**Клиентская валидация создания** (`client-source:
alliance_menu_class.as:239-244`, было `:204-231`, дублирует, но не
расходится с серверной `VD_Alliance` из `mechanics-alliances-diplomacy.md`
§2): имя 3–50 символов, тег 1–8 символов, пароль 3–30 символов —
**точное совпадение** границ длины с серверными.
`reqvestdata.createAlliance(name, tag, pass)` →
`<createalliance><name><![CDATA[<name>]]></name><tag><![CDATA[<tag>]]></tag><joinpassword><![CDATA[<pass>]]></joinpassword></createalliance>`,
`reqvestdata.connectAlliance(comboId, pass)` →
`<joinalliance id_alliance="<comboId>"><joinpassword><![CDATA[<pass>]]></joinpassword>></joinalliance>`
(`reqvestdata.as:587-608`, XML строится дословно в теле метода).

**Найденный дефект протокола**: XML, который строит `connectAlliance`,
содержит лишний символ `>` сразу после закрывающего
`</joinpassword>` и перед `</joinalliance>` (буквально так в исходнике,
не опечатка при переносе сюда) — то есть клиент отправляет технически
некорректный XML на вступление в альянс; парсер на сервере, судя по
тому, что вступление в альянс в игре работало, либо терпим к этому лишнему
символу, либо отбрасывает всё после первого валидного узла — стоит сверить
при переносе в ремастер, а не копировать этот билдер как образец.

**Найдено — защита новичка от случайного действия**: если
`game.getNewbee()` (игрок ещё под щитом новичка), и создание, и вступление
в альянс сначала показывают доп. диалог-подтверждение
(`ALERT_TEXT1`, `client-source: alliance_menu_class.as:210-223,284-297`)
прежде чем реально отправить запрос — согласуется с
находкой в серверном документе о том, что создание/вступление в альянс
**снимает** щит новичка (`clearNewbDefend`) — клиент явно предупреждает
об этом побочном эффекте перед действием.

### 2.2 Список участников — `alliancememberclass.as`

Постраничный список (`addPages`) с кнопкой «Кикнуть»
(`reqvestdata.kickUserAlliance(userId, allianceId)` →
`<expelalliance id_alliance="<allianceId>" id_userinstance="<userId>"/>`,
`reqvestdata.as:906-910`), видимой только если **одновременно** (условие
подтверждено дословно, `client-source: alliancememberclass.as:188`, было
без точной ссылки): `data_obj.leader=="1"` (текущий игрок — лидер), цель не
сам лидер (`this.arr[i].status != "LEADER"`), и **новое поле, не
встреченное в серверном документе** — `delete_button_disabled` (если `1`
на конкретном участнике — кнопка «кикнуть» скрыта именно для него). Это
может соответствовать защите участников, привязанных через глобальный
альянс (§7.3 серверного документа), но прямого подтверждения в клиентском
коде (комментариев, объясняющих природу поля) нет — остаётся открытым
вопросом, что именно на сервере выставляет этот флаг.

### 2.3 Настройки альянса — `alliance_setting_class.as`

Смена пароля (`reqvestdata.changeAlliancePass(text)` →
`<changepassword><![CDATA[<text>]]></changepassword>`) и передача
лидерства по комбобоксу участников (`reqvestdata.changeAllianceLeader(userId)`
→ `<changeleader newleader="<userId>"/>`, `reqvestdata.as:592-603`, оба
метода строят XML дословно в теле) — обе кнопки визуально гасятся
(`_alpha=50`), если игрок не лидер, **без** назначения `onRelease` вовсе
(то есть кнопки реально неактивны, не только визуально;
`client-source: alliance_setting_class.as:136-149`).

**Подтверждённый клиентский баг** (реальный исходник снимает последнюю
неопределённость — было «потенциальный»): поле пароля предзаполняется
литералом-плейсхолдером `"********"` (`client-source:
alliance_setting_class.as:125`, дословно
`mov_info.PASS_F.text = "********";`). Единственный обработчик
`onChanged` на этом поле (`:128-131`) лишь **один раз** выставляет
`restrict`-паттерн ввода и тут же удаляет сам себя — он **не очищает**
текст поля. Если лидер нажмёт «Сменить пароль» (`:137-139`), **не** тронув
поле, `reqvestdata.changeAlliancePass(this._parent.PASS_F.text)` отправит
буквально строку `"********"` как новый пароль альянса — в коде нет
проверки «поле ещё не редактировалось». Стоит явно закрыть в ремастере
(например, не предзаполнять плейсхолдер текстом, годным для отправки).

### 2.4 Мир между альянсами — `peace_aliance.as`

5 вкладок: `1` нейтральные альянсы (можно предложить мир), `2` уже
действующий мир, `3` «жду подтверждения от вас» (свой оффер), `4` «оффер
вам» (мигает кнопка `BUT_4.play()`, если есть непринятые), `5` заявки на
расторжение (доп. мигание `blinkDismiss`).

**Прямое клиентское подтверждение бага #7 из `mechanics-alliances-diplomacy.md`
§9** («`UA_AlliancePeace::checkAction()` всегда возвращает `true`, защита
только в UI»): статический флаг `peace_aliance.allowaction` (из
`allianceData.allowaction=="1"`) — **единственное**, что скрывает кнопки
Да/Нет на панели предложения/подтверждения мира
(`peace_aliance.as:483-489, 651-657`); `peace_aliance.viewdata` —
единственное, что делает строки списка альянсов кликабельными вообще.
Оба флага приходят от сервера как обычные данные, но нет никакой
клиентской технической защиты сверх «не рисовать кнопку» — что напрямую
подтверждает вывод серверного документа.

**Точный протокол** (единственный файл, кроме `requestDataCabinet`,
который сам собирает XML прямо в клиенте —
`client-decompiled: DS_Classes/MN_Alliance/RequestDataAlli.as`):

| Метод | XML |
|---|---|
| `dataAboutPeace(id)` | `<getInfoAboutPeace allianceId="<id>"/>` |
| `sendOfferPeace(idAlli, my, he, time)` | `<getAlliancePeace status="setPeace" alliId="<id>" time="<t>" my_k=".." my_n=".." my_f=".." my_p=".." my_s=".." my_e=".." he_k=".." he_n=".." he_f=".." he_p=".." he_s=".." he_e=".."/>` |
| `sendConfirmPeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="confirmPeace"/>` |
| `sendDeclinePeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="declinePeace"/>` |
| `sendOfferFinishPeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="terminatePeace"/>` |
| `sendConfirmFinishPeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="confirmTerminatePeace"/>` |
| `sendDeclineFinishPeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="declineTerminatePeace"/>` |
| `sendCancelOfferPeace(idAlli)` | `<getAlliancePeace alliId="<id>" status="cancelPeace"/>` |
| `sendCancelOfferPeaceDecline(idAlli)` | `<getAlliancePeace alliId="<id>" status="cancelTerminate"/>` |

Это **дословно** совпадает с таблицей статусов `UA_AlliancePeace` из
`mechanics-alliances-diplomacy.md` §5.2 (`setPeace`/`confirmPeace`/
`declinePeace`/`cancelPeace`/`terminatePeace`/`declineTerminatePeace`/
`cancelTerminate`/`confirmTerminatePeace`) — и **впервые даёт точные имена
XML-атрибутов**: `alliId`, `time`, `my_k`/`my_n`/`my_f`/`my_p`/`my_s`/`my_e`,
`he_k`/`he_n`/`he_f`/`he_p`/`he_s`/`he_e` (k=кадериум, n=нано, f=еда,
p=продиум, s=серебро, e=энергия/стазис). Эта таблица уже была подтверждена
основным настоящим исходником `RequestDataAlli.as`; повторное чтение того
же файла в рамках сверки с полным `SPHERE_client` дало **идентичный**
результат посимвольно — расхождений не найдено. Форма ввода в
`peace_aliance.as` (`showInfoAboutPeace`, `client-source:
peace_aliance.as:424-559`) собирает эти же суммы под короткими именами
`k/n/f/e/p/s` в объектах `objPrice1`/`objPrice2`, которые затем передаются в
`RequestDataAlli.sendOfferPeace(id, objPrice1, objPrice2, days)` — то есть
`my_*`/`he_*` в XML это `price1_obj`/`price2_obj` с точки зрения
"своя сторона / сторона оппонента", а не что-то более специфичное.

Клиент дополнительно **ограничивает** ввод длительности мира верхней
границей `this.allianceData.max_peace_length` через скроллер `DAY_COUNT`
(`client-source: peace_aliance.as:506`, было `:448`) — это прямое
клиентское воплощение `ALLIANCE_PEACE_DURATION_MAX`,
и **подтверждает** находку сервера (§9 п.6), что это лишь «подсказка
клиенту», не защита: сам скроллер не пускает дальше, но сервер, согласно
серверному документу, значение из XML не перепроверяет.

### 2.5 Приватный (личный) мир — `peace_private.as`

Два списка на экране: заявки (`request_array`) и уже установленные связи
мира (`member_array`). Одна универсальная команда
`reqvestdata.getPPeace(targetName, UID, reason)` со значениями `reason`,
**дословно совпадающими** с таблицей действий `UA_PrivatePeace` из
серверного документа §6.2: `PRIVATEPEACE_REQUEST`, `PEACE_CONFIRMATION`,
`PEACE_REJECTING`, `LIST_REQUEST_CANCEL_REQUEST`, `LIST_REQUEST_REJECT_PEACE`
(все пять литералов подтверждены дословно в реальном исходнике —
`client-source: peace_private.as:63,284,297,349,355`).

**Забавная находка, подтверждённая настоящим исходником**: при первом
отправлении заявки (когда `UID` получателя ещё не известен клиенту) в
качестве второго параметра передаётся **буквальный литерал-заглушка**
`"DANONMUSTDIE"` (`client-source: peace_private.as:63`, было
`client-decompiled: peace_private.as:61`) — судя по всему,
шуточный/технический placeholder разработчика, оставленный в проде.

---

## 3. Мир, колонизация, раса

### 3.1 Колонизация — `colonyzationclass.as`

Панель здания `COLONIZATION_CENTER` (модуль колонизации, см.
`mechanics-buildings.md` §4.5). Три состояния по `data_allow_colony.action`:
`"1"` — готов к настройке цели (`colony_ready`), `"2"` — уже в процессе
(`colony_action`, кнопка отмены), `"3"`/`"4"` — завершено (`colony_ok`,
всё скрыто).

**Формат адреса цели** (подтверждает и обогащает
`mechanics-races-world-ranking.md` §5.3): клиент собирает строку одним из
двух видов —
```
"<ring>.rand"                          — если отмечена галочка "случайная сота в кольце"
"<ring>.<complex>.<sota>"              — если указаны точные координаты
```
и шлёт как один параметр в `reqvestdata.sendColonyModule(addr, moduleId,
name)` → `<colonize id_module="<moduleId>" destination="<addr>">
<colonyname><![CDATA[<name>]]></colonyname></colonize>` (`client-source:
reqvestdata.as:565-569`, дословно; вызов в клиенте —
`client-source: colonyzationclass.as:191`, было `:179-182` в
декомпилированном чтении). Имя новой соты валидируется клиентом: 3–25
символов (`client-source: colonyzationclass.as:157,186-188`: `maxChars=25`
на поле плюс явная проверка `name.length > 25 || name.length < 3`
непосредственно перед отправкой). Разрешённые символы — restrict-паттерн
`"a-z A-Z 0-9 \\-\\"` (`client-source: colonyzationclass.as:158`, дословно
из настоящего исходника — компилируется в `a-z A-Z 0-9 \-\`, то есть
буква/цифра/дефис плюс висящий одиночный экранированный обратный слэш в
конце набора; в декомпилированном чтении эта escape-последовательность
была неоднозначна, теперь подтверждена буква в букву как маленький, но
реальный дефект самого restrict-паттерна, а не артефакт декомпиляции).
Отмена — `reqvestdata.cancelColonyModule(id)` →
`<cancelcolonize id_module="<id>"/>` (`reqvestdata.as:570-574`).

### 3.2 Навигатор колоний — `colonynavigate.as`

Список уже основанных игроком колоний с переходом
(`reqvestdata.navigate(this._parent.ID_colony)`, `client-source:
colonynavigate.as:105`), выходом из захватываемой колонии (`capture==1` →
кнопка «Покинуть», `reqvestdata.leaveColony(id)` — прямое соответствие
`getIsCapture()` из серверных доков), и заблокированным состоянием
(`disable==1`).

**Уточнение по реальному исходнику `reqvestdata.navigate`**: метод в
`reqvestdata.as:267-292` на самом деле принимает 4 параметра
(`location, direction, ring, complex`) и ветвится по значению `location`
на три случая — `"COMPLEX"` (переход по кольцу/комплексу,
`<complexview direction=".." ring=".." complexnum=".."/>`), `"COLONY"`
(открыть панель навигатора, `<getbuildmenu tab="ColonyNavigatePanel"/>`,
именно так и открывается сам этот экран) и **дефолтный** случай (любое
другое значение `location` трактуется как координата назначения). Вызов
из `colonynavigate.as:105` передаёт только координату колонии одним
аргументом, поэтому попадает именно в дефолтную ветку и отправляет:
```
<navigate cell="1" dest="<coord>" />
```
(и попутно выставляет `debug.link.CHANGELOCATION = true`). Отмена/выход —
`reqvestdata.leaveColony(id)` → `<leaveColony id="<id>" />`
(`reqvestdata.as:296-302`, дословно).

**Найдено — премиум-фича «удалённая военная база»**: если
`colony_array[i].warbase=="1"` **и** `game.getPremium()` — доступна кнопка
`reqvestdata.request_remote_warbase(coord)` (`client-source:
colonynavigate.as:145-153`, было `:148-159`) →
`<getremotewarbase coord="<coord>" />` (`reqvestdata.as:854-860`, тут же
выставляется `debug.link.CHANGELOCATION = true` — то же самое, что и при
обычном переходе по координате, то есть открытие удалённой военной базы
клиент трактует как полноценную смену текущего местоположения), не
встреченная в прочитанных серверных документах — судя по всему, позволяет
управлять военной инфраструктурой удалённой колонии без физического
перехода на неё. Открытый вопрос — что именно она открывает на сервере (не
входило в объём).

### 3.3 Религия — `religion_class.as`

Панель с 4 статусами (`obj_data.status`): `RELIGI_KICK` (выйти,
`reqvestdata.religyOut(id)` → `<religycancel religy="<id>"/>`),
`RELIGI_CONNECT` (вступить, `reqvestdata.religyConnect(id)` →
`<religyconnect religy="<id>"/>`, `reqvestdata.as:575-584`, обратите
внимание — XML-теги входа/выхода называются не симметрично команде:
`religyconnect`/`religycancel`, а не `religyconnect`/`religyout`),
`RELIGI_NOTACCESS` (недоступно, кнопка затемнена),
`RELIGI_CONNECT_ACTION`/`RELIGI_KICK_ACTION` — **процесс уже идёт**, с
текстовым процентом выполнения и датой окончания (`obj_data.percent`,
`obj_data.datum`).

**Новая деталь, не встреченная в заголовках `mechanics-races-world-ranking.md`**:
вступление/выход из религии — это не мгновенное действие, а **длящийся
процесс** (по аналогии с реинкарнацией, см. §4.2), с процентом и датой
завершения — то есть у религии тоже есть своя очередь событий с
`endtime`, аналогичная другим долгим операциям в игре.

**Найдено — иконка религии жёстко закодирована только для двух имён**:
```as
if(obj_data.name == "RELIGI_WOLKENENDER") { icon = "RELIGI_WOLKENENDER" }
else { icon = "RELIGI_WOLKENSTURMER" }  // дефолт для ЛЮБОГО другого имени
```
(`client-source: religion_class.as:173-182`, было `:151-158`, дословно —
включая имя переменной `obj_data.name`). Это значит, что **в клиенте
зашито ровно две** узнаваемые по имени религии (`RELIGI_WOLKENENDER`,
`RELIGI_WOLKENSTURMER`) — третья и далее (если существуют в БД) будут
отрисованы с иконкой «Wolkensturmer» по умолчанию. Стоит явно решить при
ремастере, было ли в игре реально только 2 религии, либо это недоделанный
участок клиента.

Также панель **показывает только `this.religiondata[0]`** — первый элемент
массива данных о религии (`:89`), без явного переключения между
несколькими религиями внутри этого файла — открытый вопрос, обрабатывается
ли переключение где-то снаружи (через `tabs_array`, не разбиравшийся
подробно здесь) или в игре у игрока всегда ровно один кандидат-религия на
экране одновременно.

### 3.4 Реинкарнация — `reincarnate_menu_class.as` — **механика, отсутствующая в прочитанных серверных доках**

Это, вероятно, самая значимая новая находка в задании. Ни в одном из
просмотренных заголовков `mechanics-*.md` не встретилось слово
«реинкарнация» — то есть **вся эта подсистема пока не задокументирована
со стороны сервера** и требует отдельного разбора PHP (`UA_Reincarnate*`/
`EH_Reincarnate*`, класс не проверялся — имя предположительное).

**Суть механики (реконструкция по клиенту)**: игрок может **утилизировать
собственные боевые юниты**, превращая их в ресурс. Обнаружены **два
параллельных режима**, независимо переключаемых мировыми флагами
`fork.RES` и `fork.SIL` (строки `"1"`/`"0"`, подтверждено дословно
`client-source: reincarnate_menu_class.as:18-19`, комментарии разработчика
прямо называют исходные XML-атрибуты: `fork.RES = Number(node.attributes.res)`,
`fork.SIL = Number(node.attributes.sil)` — то есть на проводе это атрибуты
`res`/`sil` корневого узла ответа, не переименованные клиентом):

- **`Res`-режим** (`setunits1()`) — юниты конвертируются в **Продиум**
  (`PRODIUM`), с целевым лимитом `fork.targetProdium` (сколько ещё можно
  «нареинкарнировать» до какого-то потолка `fork.maxProdium`);
- **`Sera`-режим** (`setunits2()`) — юниты конвертируются в **серебряные
  кредиты** (`SILVER_CREDIT`).

Отправка — `reqvestdata.getReincarnate(dataString, "Res"|"Sera")` →
`<getReincarnate for="<Res|Sera>" lst="<dataString>"/>` (`client-source:
reqvestdata.as:85-89`, XML строится дословно), где `dataString` —
самодельный формат `"<unitId>:<amount>,<unitId>:<amount>,..."` (собирается
вручную конкатенацией строк, `client-source:
reincarnate_menu_class.as:588-608` для Res-режима и `:946-969` для
Sera-режима, было `:506-513`).

**Клиентские ограничения по количеству** (`calcAmount()`, `client-source:
reincarnate_menu_class.as:636-653` для Res-режима, `:996-1013` для
Sera-режима, было `:537-562`/`:937-958`) — сколько юнитов данного типа
можно реинкарнировать: минимум из (а) сколько позволяют имеющиеся ресурсы
по каждому виду апкипа юнита (по формуле `floor(currentResource /
unitCostPerResource)`, аналогично формуле возврата ресурсов при роспуске
юнитов из `mechanics-economy-trade.md`), и (б) сколько ещё «влезает» в
оставшийся целевой Продиум/Серебро лимит
(`floor((target - alreadyQueued) / prodiumWeightOfUnit)`). Формулы
подтверждены дословно настоящим исходником. Это чисто клиентский
предрасчёт для UX — реальная проверка, разумеется, тоже на сервере, но
точный алгоритм ограничения здесь виден дословно.

**Очередь** (`array_queue`, вкладка `setQueue()`, `client-source:
reincarnate_menu_class.as:1233-1330`): реинкарнация — это **не
мгновенная**, а событийная операция с `starttime`/`endtime` и
прогресс-баром (аналогично постройке/апгрейду/исследованию) — сервер шлёт
`time` как строку `"start|finish"` через `|`, парсится дословно как
`array_queue[i].time.split("|")` (`:1300`).

**Статистика/графики** (`setgraph()`, `client-source:
reincarnate_menu_class.as:87-496`, вкладка «Статистика», самая большая
функция в файле — построение гистограммы «за неделю» и линейного графика
«за месяц»): показывает три ряда данных — `fork.lostProdium` (потери
продиума), `fork.biuldProdium` (потрачено на стройку), `fork.reincProdium`
(получено реинкарнацией; подписи `LOSTS`/`BUILDED`/`REINCARNATED`
подтверждены дословно, `:100-120,479-490`), с историческими данными по
дням (`dw_array`/`dm_array`, comma-separated числовые строки на каждый из
3 рядов) — то есть сервер ведёт **персональную дневную статистику расхода/
дохода Продиума по игроку** для этого экрана, не встреченную ни в одном
из просмотренных ранее документов.

**Рекомендация**: эта механика заслуживает отдельного полноценного
разбора соответствующего PHP-кода (`UA_*`/`EH_*` с «Reincarnate» в
названии) — здесь зафиксирована только клиентская сторона.

---

## 4. Наука и социальные панели

### 4.1 Наука — `researchmenu.as`

Плитки по 7 веткам (полное совпадение состава с `mechanics-research.md`
§3, независимое клиентское подтверждение через `switch` на иконки:
`KADERIUM`/`NANO_CRYSTAL`/`STASIS_ENERGY`/`PRODIUM`/`FOOD`/`TRADE`
(иконка `"trade"`)/`SPYING` (иконка `"spy"`)).

**Прямое подтверждение реконструированного в серверном документе формата
`sciencename`** (там это было явно помечено как «не подтверждено, только
regex-паттерн»): клиент **буквально** строит id для отправки как
```as
_ID_RESEARCH = research_array[i].id + "_" + (Number(research_array[i].nextlev) + 1)
```
(`client-source: researchmenu.as:261`, было `:210`) — то есть
`<BRANCH>_<level>` подтверждён кодом, формирующим сетевой запрос, а не
только эвристикой по regex на стороне сервера.
`reqvestdata.start_reserach(id)` → `<research id="<id>"/>`
(`reqvestdata.as:418-422`, дословно). **Найдена асимметрия**: у кнопки
отмены исследования (`reqvestdata.stop_reserach(id)` →
`<cancelresearch id="<id>"/>`, `reqvestdata.as:424-428`) `id` строится
**без** `+1` — `research_array[i].id + "_" + research_array[i].level`
(`researchmenu.as:354`, текущий, а не следующий уровень) — то есть формат
`<BRANCH>_<level>` один и тот же, но при старте исследования это
«целевой» уровень (текущий+1), а при отмене — «текущий» уровень
исследования, которое как раз идёт.

Кнопка «Ускорить науку» (донат) — `reqvestdata.getPayService(desc, val,
"FAST_RESEARCH")` → `<getPayService c="..." for="<desc>" amount="<val>"
type="FAST_RESEARCH"/>` (`reqvestdata.as:92-96`), где `desc` = имя ветки —
подтверждает связь с `UC_ExecByScienceAcceleration` из §5.6 серверного
документа. Тренировка учёных — тот же шаблон train/cancel/buy, что и у
археологов (`reqvestdata.trainscientists(count)` →
`<trainunit c="..." count="<count>"/>` / `canceltrainscientists()` →
`<canceltrainunit c="..."/>` / `getPayService(desc,val,"BUY_SCIENTIST")`,
`reqvestdata.as:436-446`).

### 4.2 Отпуск (вакация) — `otpusk_class.as`

Простая панель с 3 статусами (`data_vacation.status`, `client-source:
otpusk_class.as:76-109`): `"1"` — не в отпуске (кнопка «Уйти в отпуск»,
предварительно требует доп. подтверждение `CONFIRM_VACATION` + вводится
**отдельный пароль отпуска**), `"3"` — в отпуске (кнопка «Выйти» + «Забыл
пароль» → `reqvestdata.vacationpasssend()` → `<vacationsedpass />`, см.
опечатку в §9 — литерал буквально так в реальном исходнике, закомментирован
прямо над вызовом на строке 98 как напоминание разработчика), `"2"` — не
отрисовывает никаких кнопок вовсе (переходное/заблокированное состояние,
назначение не установлено по этому файлу). `reqvestdata.vacation(password)`
→ `<vacationstart password="<password>"/>` / `vacationstop(password)` →
`<vacationstop password="<password>"/>` (`reqvestdata.as:873-882`, все три
метода строят XML дословно). Подтверждает (со стороны клиента) факт
наличия отдельного пароля для входа/выхода из режима отпуска, о чём уже
сказано в `mechanics-moderation-vacation-messaging.md` §2.

### 4.3 Квесты игрока — `quest_menu_class.as` — вероятно другая система, чем в `mechanics-quests-events.md`

Судя по заголовкам прочитанного серверного документа, там разбирались
DTF-квиз, «квестовый турнир» и новогодние раздачи — **не** обычный
игровой журнал заданий. Этот же клиентский класс — полноценный **игровой
UI списка заданий** с тремя вкладками и наградами, что не совпадает по
описанию ни с одним из разделов, увиденных в оглавлении
`mechanics-quests-events.md`. Рекомендуется явно сверить, не пропущена ли
в серверном документе основная система квестов (`UA_*Quest*`/`PN_*Quest*`),
либо этот UI относится именно к «квестовому турниру» — не установлено в
рамках этой задачи (см. §9).

**Найденное несоответствие меток вкладок и данных — подтверждено на 100%
настоящим исходником** (было помечено как декомпилированная находка,
теперь факт: `client-source: quest_menu_class.as:333-352` — вкладки
собираются функцией `getArrayForTabs()`: `OLD_QUEST`(id `0`) /
`CURRENT_QUEST`(id `1`) / `NEW_QUEST`(id `2`); `client-source:
quest_menu_class.as:112-134` — метод `showListQuest` при этом дословно
рисует):
- id `0` → `quest_data.current_quest`,
- id `1` → `quest_data.new_quest`,
- id `2` → `quest_data.finished_quest`.

То есть подпись вкладки **не соответствует** реально показываемому
массиву данных (сдвиг на один шаг: «Старые» показывает «текущие», «Текущие»
показывает «новые», «Новые» показывает «завершённые»). Похоже на
однопозиционный сдвиг/баг именования в клиенте — стоит перепроверить при
переносе в ремастер, а не копировать вслепую.

**Найденный незавершённый код — подтверждено буквально настоящим
исходником**: детальное окно квеста (`openDetailInfo`→`createDetailText`→
`textToArrayObject`, `client-source: quest_menu_class.as:207-303`, было
`:188-203`) парсит описание как псевдо-XML (заменяя `[`/`]` на `<`/`>` через
`parser.replacesign`, оборачивает в `new XML(str_text)`), но результат
парсинга **нигде не используется для отрисовки**: `createDetailText`
(`:281-286`) вызывает `textToArrayObject` и полностью игнорирует
возвращённое значение, а сам `textToArrayObject` (`:288-303`) создаёт
`ret_array` и возвращает его **не заполненным ни одним элементом** — весь
разобранный XML только логируется в `trace()`. Никакого текстового поля
для отображения описания в этом коде нет вообще (в отличие от
закомментированного альтернативного варианта чуть выше, `:241-262`,
который создавал `TextField` и выводил туда HTML — этот путь тоже не
активен). То есть детальное окно квеста реально **не показывает** текст
описания ни одним из двух путей — подтверждённая, а не предположительная
недописанная функциональность в этой версии клиента.

### 4.4 Передача ресурсов между своими сотами — `transfer_class.as` — **это НЕ межмировой перенос!**

**Важное несоответствие с формулировкой задания**: класс называется
`transfer_class`, и в задании он был сгруппирован с намёком на
`mechanics-world-transfer.md` («перенос между мирами»). По факту это
**внутримировая** логистическая панель — передача ресурсов/юнитов/
артефактов **между собственными сотами игрока в одном и том же мире**
(`reqvestdata.transfer_to_colony(id, category, amount, targetCombId,
deathKey)` → `<transferresource combto="<targetCombId>" id_article="<id>"
id_category="<category>" amount="<amount>" death="<deathKey>" />`
(`reqvestdata.as:633-637`, дословно, порядок параметров подтверждён 1:1),
`reqvestdata.calc_transfer_to_colony(id, category, amount, targetCombId)` —
расчёт времени/стоимости доставки без реальной отправки. Ничего общего с
`ds_worldtransfer`/`SI_ControlStoreUserInstance`/веб-кабинетом переноса
персонажа между игровыми мирами (та механика управляется не из игрового
Flash-клиента, а из отдельного веб-интерфейса, согласно
`mechanics-world-transfer.md`). **Правильное русское название этой панели
— что-то вроде «Транспорт/Логистика между колониями», а не «перенос
между мирами»** — стоит явно развести эти два понятия в документации
ремастера во избежание путаницы.

Три блока (ресурсы/юниты/артефакты), у каждого — выбор предмета из
раскрывающегося инлайн-списка, количество, комбобокс целевой соты
(переиспользует те же «скоупы» `allow_scope_array`, что и биржа/аукцион —
т.е. логистика ограничена теми же зонами видимости `ds_comballowedtradescope`,
что и торговля). Найден мелкий дефект: ветки `if(cat==2){...}else{...}`
для кнопки «Рассчитать» (`client-source: transfer_class.as:408-441`, было
`:369-396`) содержат **идентичный** код в обеих ветках (оба вызывают
`reqvestdata.calc_transfer_to_colony(...)` с одинаковым набором
аргументов) — то есть условие в `transfer_class.as` само по себе
действительно ничего не меняет. **Найдено объяснение**: реальная
дифференциация по категории происходит **на уровень ниже**, внутри самого
`reqvestdata.calc_transfer_to_colony` (`client-source:
reqvestdata.as:638-643`) — там `id_cat==2` определяет режим запроса
(`mode="TRANSFER"` с атрибутом `id_unit`) против любого другого значения
(`mode="TRANSFERRES"`), то есть эта проверка **дублирует** уже
существующую внутри `reqvestdata`, а не является недоделанной —
`if(cat==2)` в `transfer_class.as` просто избыточен (мёртвый, но не
свидетельствующий о неполной реализации самой фичи различения ресурсов и
юнитов при расчёте).

### 4.5 Почта — `mailmenu.as` / `create_new_mail.as` / `sys_mess_menu.as` / `logwindow.as`

**`mailmenu.as`**: входящие/исходящие с фильтром по типу
(`lscopename`) — набор типов собирается **динамически из уже пришедших
писем**, а не из фиксированного enum на клиенте (`typearrcreate()`,
`client-source: mailmenu.as:13-30`, было `:52-77`) — то есть сервер
присылает читаемое имя скоупа прямо в каждом письме, и фильтрация по типу
происходит целиком на клиенте (без повторного запроса к серверу). Команды
(все подтверждены реальным `reqvestdata.as`, который сам строит XML):
`reqvestdata.get_mail("in"|"out")` → `<getbuildmenu tab="MessageOverview"
type="<in|out>" />` (по умолчанию `"in"`, если передано что-то другое);
`getMailMessage(id)` → `<showmemail id="<id>" />`;
`deletemessages(idArray)`/`readmessages(idArray)` → id через запятую в
одном XML-запросе; `createnewmail(type, id, name)` →
`<getbuildmenu tab="CreateMessage" type="<type>" [id="<id>"] [name="<name>"] />`
— третий параметр `name` (не упомянутый в изначальном декомпилированном
чтении) добавляется в XML только если определён и используется для
предзаполнения получателя (см. ниже про `logwindow.as`).

**`create_new_mail.as`**: rich-text редактор письма (жирный/курсив/ссылка/
цвет через ручную вставку HTML-тегов по индексам выделения `Selection.
getBeginIndex/getEndIndex`). **Клиентская валидация перед отправкой**
(`client-source: create_new_mail.as:332-354`, было `:346-360`): получатель
> 2 символов **или** тип получателя не «личное» (`type_scope != 1`, т.е.
для широковещательных скоупов поле «кому» не обязательно), тема > 3
символов, текст > 3 символов — при нарушении поле мигает красной обводкой
7 раз (`warning()`). На VK-сборке (`_level0.socialNet=="VK" ||
_level0.VKONTAKTE`) поле «Кому» **ограничено только цифрами**
(`restrict = "0-9."`) — получатель на VK адресуется по числовому VK-ID, а
не по игровому нику. `reqvestdata.send_mail(to, subject, body, typeScope)`
подтверждён дословно (`reqvestdata.as:467`).

**Предзаполнение получателя письма приходит от сервера, а не от клиента —
уточнение, меняющее вывод из §4.5 про `logwindow.as` ниже**: поле «Кому»
(`input_block_to`) в `create_new_mail.createNewMail()` заполняется классовой
переменной `name` (`client-source: create_new_mail.as:9,211`), а не
параметром `name_to` самой функции. Значение `name` устанавливает **парсер
ответа сервера** `xmlparsers.createNewMail(xml)` (`client-source:
xmlparsers.as:2734-2758`) из дочернего узла `<name>...</name>` XML-ответа
на команду `CreateMessage` — то есть сервер сам решает, кого показать
получателем, эхом того, что клиент отправил в атрибуте `name` запроса
`createnewmail(type, id, name)` выше.

**`sys_mess_menu.as`**: универсальное всплывающее окно системного
сообщения/отчёта (используется, судя по всему, и для боевых
отчётов/логов, не только почты). Найдена деталь, подтверждённая дословно
(`client-source: sys_mess_menu.as:18`): кнопка «посмотреть лог разведки»
показывается только если `type_win=="SPY"` **и** `Number(debug.link.gate)
== 1` — то есть просмотр журнала шпионажа обусловлен открытыми
Центральными Воротами (`CENTRAL_GATE`), перекликается с ролью
`CENTRAL_GATE` как «рубильника внешних взаимодействий соты» из
`mechanics-buildings.md` §6.3. Кнопка шлёт `reqvestdata.getSpyMenu()` (не
встречавшийся ранее в этом документе вызов) и тут же закрывает сам лог.

**`logwindow.as`** — **находка требует исправления, а не просто уточнения
цитаты**: при чтении настоящего исходника выяснилось, что кнопка «Написать
письмо этому игроку» (`MAIL_TO`) в этом файле **не работает** в том виде,
как было описано изначально. `create_menu()` вызывает
`completeWindow(bg_menu, title, titleData, textData)` **ровно четырьмя**
аргументами (`client-source: logwindow.as:68`), а сигнатура метода —
`completeWindow(mov, title, titleData, textData, mail_to)`
(`logwindow.as:71`) — пятый параметр `mail_to` в единственном месте вызова
в этом файле никогда не передаётся, поэтому `if (mail_to != undefined)`
(`:90`) всегда ложно, и код всегда идёт по ветке `mov.MAIL_TO._alpha = 50;`
(`:105`) — кнопка визуально показывается притушенной и **не получает**
`onRelease`-обработчик вовсе (в отличие от активной ветки `:92-103`,
которая как раз и должна была устанавливать `windowFull.temp_reply_name =
this.nick` и звать `reqvestdata.createnewmail()` без аргументов). То есть
для `logwindow.as` в этой версии клиента «написать письмо субъекту отчёта»
— мёртвый, никогда не активируемый код, а не рабочая функция.

Дополнительно: переменная, которую *должен был бы* заполнить этот
обработчик — не `debug.link.temp_reply_name`, как было написано изначально,
а свойство `temp_reply_name` на **текущем корневом клипе открытого меню**
(в других живых местах кода это явно `debug.link.menu_mov.temp_reply_name`
— `client-source: sota.as:1218,1234`, `battleoverviewclass.as:138`; в
`logwindow.as` это тот же объект, но записывается как
`this._parent._parent.temp_reply_name`, `:99`). Полнотекстовый поиск по
всему `SPHERE_client/` (`client-source`, `*.as`) показывает, что
`temp_reply_name` **везде только записывается, но нигде не читается** —
ни в `create_new_mail.as`, ни где-либо ещё. Реальный механизм
предзаполнения получателя — другой (см. новую находку в описании
`mailmenu.as`/`create_new_mail.as` выше): он идёт через параметр `name` у
`reqvestdata.createnewmail(type, id, name)`, эхо которого сервер
возвращает в `<name>` узле XML-ответа, а `xmlparsers.createNewMail`
кладёт это значение в `create_new_mail.name`. Рабочие примеры такого
вызова — `sota.as:1219,1235` и `battleoverviewclass.as:140` (все три
передают ник третьим параметром `reqvestdata.createnewmail(undefined,
undefined, <nick>)`), в отличие от `logwindow.as:101`, которая передаёт
`reqvestdata.createnewmail()` без аргументов — и даже если бы `mail_to` был
не `undefined`, это всё равно не отправило бы имя на сервер. Так что
`temp_reply_name` — по всей видимости, вымершая от рефакторинга переменная
(её пишут «на всякий случай» в трёх местах по старой памяти, но нигде не
используют), а собственно фича «написать письмо этому игроку прямо из
отчёта» реализована только там, где вызов `createnewmail` передаёт третий
параметр явно — то есть не в `logwindow.as`.

### 4.6 Личный кабинет (не MN_Cabinet) — `cabinetmenuclass.as`

Верхнеуровневый контроллер личного кабинета: рисует меню услуг слева
(`HEAD`/`SPLITER`/`SUB` — заголовки/разделители/пункты, приходят массивом
`serviceList.arrServ`), правый блок (`RightBlock`, см. §5.4) и по клику на
пункт `SUB` шлёт `DS_Classes.MN_Menu.MN_Cabinet.requestDataCabinet.getSubMenuCabinet(idAction)`
→ `<cabinetRequest command="callsubmenu" menu="<id>"/>`. Именно этот класс
— точка входа в универсальный «движок форм» §5.

### 4.7 Прочее: `settingmenu.as` (см. §0.3), `adviceclass.as`, `loopbackmenu.as`

**`adviceclass.as`** — система обучающих подсказок «адвайзер»:
последовательные хинты с навигацией «вперёд/назад», кликабельными
внутристрочными ссылками на другие хинты по номеру
(`<a href="asfunction:substnick,<id>">`), опциональной иллюстрацией здания
«было» / «станет» (`obj.build`/`obj.tobuild`) и маркером, указывающим на
область экрана (`obj.surface`). `reqvestdata.getNubHelp(id, "HINT")` →
`<getNubHelp id="<id>" stat="<HINT>"/>` (`client-source:
reqvestdata.as:127-132`, дословно). Текущий номер подсказки сохраняется
локально (`advhint`, per-world). Это похоже на систему туториала для
новичков, отдельный от справки `/ds/help/` — контент, судя по всему,
хранится где-то на сервере (возможно, через Admin-CMS, не входившую в
объём этой задачи) — открытый вопрос.

**`loopbackmenu.as`** — очень тонкий класс: фон, идентичный
`researchmenu.as` (`research_manu_bg`), заголовок и иконка текущего
здания, `createTabs(...)` (другой, более общий хелпер, чем
`add_icon_tabs`/`add_second_tabs`, используемый почти везде). Никакой
другой специфичной логики в файле нет — открытый вопрос, что именно за
этим экраном скрывается (возможно, generic-контейнер, наполняемый другим
кодом снаружи объёма задания).

---

## 5. Личный кабинет — движок `MN_Cabinet` (`DS_Classes/MN_Menu/MN_Cabinet/*`)

Это отдельный, архитектурно значимый универсальный **«протокол форм»**:
сервер присылает XML-описание произвольной формы (набор типизированных
полей + кнопки, каждая кнопка знает, какие именно поля отправлять), а
клиент строит по этому описанию UI **без какой-либо специфичной для
конкретной формы клиентской логики**. Это низкоуровневый
generic-конструктор экранов, который можно (и вероятно стоит) в ремастере
воспроизвести как отдельный универсальный компонент «форма из JSON/XML»,
а не хардкодить каждый экран кабинета отдельно.

### 5.1 Поток данных

```
Клиент → сервер:  <cabinetRequest command="callsubmenu" menu="<id>"/>
Сервер → клиент:  <... action="submenuopen"> <form idmenu=".." title=".." closebut="0|1">
                       <textfield .../> <combobox .../> <button .../> ...
                   </form> </...>
                   → parserCabinetData.parseSubMenu() → SubmenuData → subMenuEngine рисует форму

Клиент → сервер (по нажатию конкретной кнопки):
  <cabinetRequest command="confirmsubmenu" button="<buttonId>" menu="<idmenu>"
                  <fieldId1>="<value1>" <fieldId2>="<value2>" ... />
```
(`client-decompiled: parserCabinetData.as:83-151`, `subMenuEngine.as:174-208`,
`requestDataCabinet.as:6-14, 31-40`)

**Ключевая архитектурная деталь**: каждая кнопка (`<button data="id1,id2,..">`)
несёт список `arrayIdForSend` — id **тех конкретных полей формы**, чьи
значения нужно приложить к запросу именно при нажатии **этой** кнопки. То
есть одна форма может содержать несколько кнопок, каждая из которых
отправляет **свой** поднабор полей — это не «единая форма → единый
сабмит», а «набор виджетов + несколько независимых частичных действий над
ними». Кнопка может нести необязательный `confirm="<текст>"` — тогда перед
реальной отправкой показывается Да/Нет-диалог (`SubmenuEngine.
createConfirmWindow`).

### 5.2 Таблица типов полей (XML-теги и атрибуты)

| XML-тег | Класс-данные | Атрибуты (из `parserCabinetData.as`) | Виджет на клиенте | Что уходит обратно |
|---|---|---|---|---|
| `<textfield>` | `DataTestfield` | `id,x,y,width,height` + внутренний текст (`xml.childNodes.toString()`) | статичный `TextField` (HTML, `[`/`]`→`<`/`>` через `parser.replacesign`) | ничего (не входит в submit) |
| `<addrcomb>` | `DataAddrcomb` | `id,x,y,width,height` | 3 связанных `AmountScroll8` (кольцо 1–4, комплекс 1–2000, сота 1–6) | строка `"<ring>.<complex>.<sota>"` |
| `<combobox defIndex="..">` + `<list id=".." val="..">` | `DataCombobox` | `id,x,y,width,height,defIndex` + список `<list>` | `combo_box_standart` | выбранный `id` |
| `<button label=".." data="id1,id2" confirm="..">` | `DataButton` | `id,x,y,width,height,label,data,confirm` | `BUTTON_TYPE_7` | (сама кнопка не шлёт значение, инициирует отправку остальных) |
| `<amount max=".." min=".." def="..">` | `DataAmount` | `id,x,y,width,height,max,min,def` | `AmountScroll5` (степпер) | числовое значение |
| `<inputtext maxchars=".." allowletters="..">` | `DataInputText` | `id,x,y,width,height,maxchars,allowletters` + текст по умолчанию | `input_block_2` (текстовое поле, `restrict`=`allowletters`, `maxChars`) | введённый текст |
| `<radio>` + `<point id=".." label=".." cur="0\|1">` / `<br/>` | `DataRadio` | `id,x,y,width,height` + список `<point>`/`<br>` | набор кружков `radio`, авто-выбор первого `cur=1` (иначе первого вообще) | `id` выбранной точки |
| `<br/>` | `DaraBr` (опечатка в самом имени класса, безобидная) | — | нет виджета, просто сброс раскладки на новую строку | — |
| `<hidden data="..">` | `DataHidden` | `id,x,y,width,height,data` | нет виджета | значение `data` как есть, без участия пользователя |

Базовый класс `submenuElementsData.BaseDatasubmenu` хранит все 8 констант
типа как строковые литералы: `addrcomb`, `textfield`, `combobox`,
`button`, `amountf`, `inputtext`, `radio`, `br` — плюс отдельно `hidden`
(`"hidden"`) — все эти строки и есть значения атрибута `type`/имени тега.

**Найдена «мёртвая» часть протокола — подтверждено на 100%, открытый
вопрос закрыт**: атрибуты `x`/`y` **парсятся**
(`completePosition`, `client-source: parserCabinetData.as:191-199`, было
`:153-160`, метод вызывается из `parseHidden`, `parseRadioSelect`,
`parseTextfield`, `parseCombobox`, `parseAddrComb`, `parseAmount`,
`parseInputText`, `parseButton` — то есть для всех 8 типов элементов без
исключения) в `objData.x/y`, но настоящий исходник `subMenuEngine.as`
показывает, что метод `positionElement()` (`client-source:
subMenuEngine.as:209-243`) читает и пишет только собственные счётчики
`this.max_x`/`this.max_y`/`max_height_in_str`/`border_size`/`this.posWid`
— атрибуты `element_data.x`/`element_data.y`, переданные вторым
параметром, **нигде внутри метода не упоминаются вообще** (объект
`element_data` в теле функции не используется ни разу, кроме как для
предупреждающего `trace()` в закомментированной строке). Реальное
позиционирование всегда идёт через собственный автоматический
flow-layout (перенос строки при превышении ширины окна, либо явный
`<br/>`). Только `width` иногда переиспользуется (например, для ширины
`inputtext`/`combobox`). То есть сервер может присылать `x`/`y` в этом
протоколе, но клиент их **гарантированно игнорирует** во всех восьми
типах полей формы — стоит иметь в виду при проектировании такого же
движка для ремастера: эти поля можно не тащить.

### 5.3 Главный экран кабинета (не подменю) — `parserCabinetData.parseMainMenu`

Разбирает корневой ответ `action="enter"`:

```xml
<... billing_url="...">
  <user name=".." email="..">
    <allow_worlds><world name=".." id=".." gold=".." silver=".."/>...</allow_worlds>
  </user>
  <current_stat><money_gold value=".."/><money_silver value=".."/></current_stat>
  <service_list>
    <head value=".."/> <split/> <sub id=".." name=".."/> ...
  </service_list>
  <popup value=".."/>              <!-- опционально: модалка сразу при входе -->
  <last_operation date=".." text=".."/>
</...>
```

И статистика операций (`action="operationStatistic"`):
```xml
<...>
  <filter><types><type id=".." name=".."/>...</types></filter>
  <operationlist><operation date=".." text=".." concurency=".."/>...</operationlist>
</...>
```

### 5.4 Правый блок (`RightBlock.as`) и денежные операции кабинета

Постоянно видимая панель (имя/золото/серебро, листалка «мои миры» с
gold/silver по каждому, 4 денежные операции). Все 4 напрямую бьют в
`DS_Classes.MN_Menu.MN_Cabinet.requestDataCabinet` (XML строится прямо в
клиенте — 100% точно):

| Действие | XML |
|---|---|
| Обменять золото → серебро | `<cabinetRequest command="changeGoldTOSelver" count="<n>"/>` |
| Перевести деньги **в** конкретный мир | `<cabinetRequest command="moneyToWorld" worldId="<id>" gold="<g>" silver="<s>"/>` |
| Перевести деньги **из** конкретного мира | `<cabinetRequest command="moneyFormWorld" worldId="<id>" gold="<g>" silver="<s>"/>` — **опечатка в самом клиенте**: `moneyFormWorld`, не `moneyFromWorld` (см. §9) |
| Подарить деньги (аккаунту/в игру) | `<cabinetRequest command="giftMoney" gold="<g>" silver="<s>"/>` |

Кнопка «Купить золото» (`BUT_BUY_GOLD`) ветвится по платформе:
`_level0.socialNet == "VK"` → вызывает внутриигровой `_level0.initGold()`
(VK-платёж); `"POP"` без `_level0.bill` → открывает `_level0.popmog_service`
во внешней вкладке; `"POP"` с `_level0.bill == "ext"` → открывает
`billingUrl` (пришедший с сервера) во внешней вкладке — три разных пути
монетизации золота в зависимости от того, где запущен клиент. Это
подтверждает мультиплатформенность биллинга, уже частично описанную в
`billing-and-monetization.md` (не проверялось построчно в рамках этой
задачи, но клиентская развилка зафиксирована здесь как факт).

### 5.5 Магазин артефактов — `artefact_shop_engine.as`

Отдельная (не через generic-форму §5.1–5.2, а свой специализированный
класс) панель покупки артефактов за реальные деньги (золото/серебро,
переключатель `changeConcurency(1|2)`). Поддерживает **корзину**
(`cache_array`) — можно добавить несколько разных артефактов с разным
количеством, прежде чем подтвердить покупку разом:

```
DS_Classes.MN_Menu.MN_Cabinet.requestDataCabinet.buyArtInShop(arr)
→ <buy_artefact_in_shop>
     <artefact count=".." type="gold|silver" id=".." life=".."/>
     ...
   </buy_artefact_in_shop>
```
(это отдельный корневой тег, **не** обёрнут в `<cabinetRequest>`, в
отличие от остальных команд кабинета — асимметрия протокола, зафиксирована
как факт).

Два независимых фильтра-комбобокса (`filter_1`/`filter_2`, из
`filter_list[0]`/`filter_list[1]`) с логикой AND (`checkFilter`, оба
должны совпасть или быть «Все» с `id=0`). Артефакт в списке несёт поля
`id, name, bonus_text, exp, price_g, price_s, maxCount, icon, filter_data[]`.

---

## 6. Открытые вопросы

1. **Реинкарнация (`reincarnate_menu_class.as`, §3.4) — целая механика
   без серверной документации.** Не найдено ни одного упоминания
   «реинкарнации» в заголовках прочитанных `mechanics-*.md`. Клиентская
   сторона теперь подтверждена настоящим исходником полностью (два режима
   Res/Sera, точный протокол `getReincarnate`, формулы `calcAmount`,
   формат очереди `"start|finish"`, атрибуты `res`/`sil` источника флагов
   `fork.RES`/`fork.SIL`) — остаётся только серверная часть. Нужен
   отдельный разбор соответствующего PHP-кода (по аналогии с `UA_*`/`EH_*`
   классами для построек/науки) — тема достаточно велика, чтобы
   заслуживать отдельного документа `mechanics-*.md`.
2. **Религия как длящийся процесс** (§3.3) — вступление/выход занимают
   время (`percent`+`datum`), аналогично реинкарнации/апгрейду. Не
   проверено, есть ли это в серверной документации (заголовки её не
   раскрывают этот уровень детализации) — нужна сверка построчно.
3. **`quest_menu_class.as` (§4.3) vs `mechanics-quests-events.md`** — этот
   файл описывает обычный игровой журнал заданий с наградами (3 вкладки),
   а прочитанный по заголовкам серверный документ, похоже, посвящён в
   основном разовым акциям (DTF-квиз, новогодние раздачи, «квестовый
   турнир»). Неясно, это один и тот же движок под разными именами, или в
   серверной документации пропущена основная система квестов — требуется
   явная сверка (построчное чтение `mechanics-quests-events.md`, не
   выполненное в рамках этой задачи, либо поиск `UA_*Quest*` классов).
4. **`build_menu.as` показывает GC/SC-стоимость построек** (§1.7, теперь
   подтверждено настоящим исходником — не декомпилированная догадка),
   тогда как `mechanics-buildings.md` §2.3 утверждает, что списание денег
   за постройку не реализовано в `UA_ConstructBuilding` (комментарий-заглушка
   без кода). Нужно перепроверить: либо это устаревшая клиентская
   вёрстка под фичу, которая позже не была включена на сервере, либо
   списание денег реализовано где-то в другом месте (не найденном при
   чтении экономического документа) — сейчас это открытое противоречие,
   решить которое можно только со стороны PHP.
5. **`transfer_class.as` — не межмировой перенос** (§4.4). Явно
   зафиксировано здесь: это внутримировая логистика между собственными
   сотами. Файл не имеет отношения к `mechanics-world-transfer.md` — при
   дальнейшей работе над темой «перенос между мирами» этот класс
   учитывать не нужно.
6. **`loopbackmenu.as` (§4.7)** — назначение экрана осталось не
   установленным даже после сверки с настоящим исходником: минимальный
   класс без специфичной логики, использующий иной, более общий, хелпер
   `createTabs`. Возможно, это generic-контейнер, наполняемый другим (не
   прочитанным) кодом.
7. **Точный смысл `busy`-подобных/пограничных состояний** типа
   `otpusk status=="2"` (без кнопок, §4.2) и `colony_ok` при `action`
   `"3"` vs `"4"` (одинаковая отрисовка для двух разных кодов, §3.1) не
   установлен — коды приходят от сервера, их точное бизнес-значение не
   восстановить только по клиенту (проверено и по настоящему исходнику —
   дополнительных подсказок в коде нет).
8. ~~`x`/`y` в протоколе форм MN_Cabinet, вероятно, мёртвые данные~~ —
   **закрыто**. Настоящий исходник `subMenuEngine.as:209-243` подтверждает:
   метод `positionElement()` не читает `element_data.x`/`.y` вообще ни разу,
   для всех 8 типов полей формы позиционирование идёт только через
   собственный flow-layout. См. §5.2.
9. **Опечатки-литералы и мелкие дефекты протокола, которые могут быть
   частью реального протокола** (нельзя «исправлять» при портировании без
   проверки серверной стороны, иначе сломается совместимость со старыми
   данными/логами) — весь список ниже теперь подтверждён настоящим
   исходником `reqvestdata.as`/`requestDataCabinet.as`, а не только
   декомпилированным чтением:
   - `"BUILDNIG_ARTEFACT"` (вместо `BUILDING_ARTEFACT`) — ключ справки
     `buildartefactclass.as`;
   - `"moneyFormWorld"` (вместо `moneyFromWorld`) — команда кабинета в
     `requestDataCabinet.as:29-32`;
   - `"NOT_AWALIBLE"` (вместо `NOT_AVAILABLE`) — ключ локализации, минимум
     в `auctionmenuclass.as` и `terraform_class.as`;
   - `"vacationsedpass"` (вместо `vacationsendpass`) — XML-тег команды
     «забыл пароль отпуска» (`reqvestdata.as:883-886`, `otpusk_class.as:98`
     — опечатка настолько прижилась, что разработчик сам оставил
     закомментированную копию правильного вызова рядом как памятку);
   - лишний символ `>` в XML, который строит `connectAlliance(id, pass)`
     (`reqvestdata.as:604-608`): `<joinalliance
     id_alliance="..."><joinpassword>...</joinpassword>></joinalliance>` —
     технически невалидный XML на вступление в альянс, сервер, видимо,
     терпим к этому (см. §2.1);
   - имя класса `DaraBr` (вместо `DataBr`) — не протокол, а внутреннее имя
     класса, безобидно.
10. **`reqvestdata.as` не входил в объём этой задачи как отдельный файл для
    полного построчного разбора — его всё ещё разбирает другой агент.**
    При этом в ходе этой сверки было **точечно прочитано около 40 методов**
    `reqvestdata.as`, которые вызываются из файлов, входивших в объём (все
    перечислены выше с пометкой `client-source: reqvestdata.as:<строка>` —
    `sale`, `putbuild`, `cancelbuild`/`delbuild`/`activatebuild`/
    `deactivatebuild`/`cancelactivatebuild`/`upgradebuild`/`cancelupgrade`,
    `sendArchExpedition`, `get_artefact`/`insert_artefact`/`delete_artefact`,
    `artdepotsort`, `delArtFromDepot`, `artActivate`, `start_terraform`/
    `stop_terraform`, `createAlliance`/`connectAlliance`/`exitAlliance`/
    `changeAlliancePass`/`changeAllianceLeader`/`kickUserAlliance`,
    `sendColonyModule`/`cancelColonyModule`, `navigate`, `leaveColony`,
    `request_remote_warbase`, `religyConnect`/`religyOut`, `getReincarnate`,
    `start_reserach`/`stop_reserach`, `trainscientists`/
    `canceltrainscientists`, `getPayService`, `getNubHelp`, `vacation`/
    `vacationstop`/`vacationpasssend`, `get_mail`, `getMailMessage`,
    `deletemessages`/`readmessages`, `createnewmail`, `send_mail`,
    `transfer_to_colony`/`calc_transfer_to_colony`,
    `applyFiltersAuction`, `buy_lot`). Для этих конкретных вызовов имена
    XML-атрибутов в документе выше — уже не «порядок и предполагаемый
    смысл», а подтверждённый факт, так как `reqvestdata.as` сам строит
    XML-строку в теле каждого метода (тот же принцип, что и у
    `RequestDataAlli.as`/`requestDataCabinet.as`). Полный систематический
    разбор всего файла (порядка 1000+ строк, десятки методов, не
    процитированных в этом документе, поскольку соответствующие
    вызывающие файлы не входили в объём) по-прежнему остаётся задачей
    другого агента — при объединении результатов стоит свести оба взгляда
    воедино и проверить на противоречия.
