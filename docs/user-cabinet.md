# Личный кабинет игрока (`UC`)

> Разбор основан на 84 файлах `ds/classes/UC/`. **Прочитаны полностью** (все
> строки) 46 файлов — все `UC_Control*`/`UC_Page*` (маршрутизация и вывод),
> инфраструктурные классы (`UC_PaymentServiceBase`, `UC_OutUserCabinet`,
> `UC_LogicGetPageControllerForUser`, `UC_ExecLogin`, `UC_ExecUserCabinetIndex`,
> `UC_ExecGetXMLData`, `UC_ExecUserProfile`, `UC_ExecMergeAccount`,
> `UC_ExecMoneyExchange`, `UC_ExecDonation`, `UC_ExecReserveComb`,
> `UC_ExecMultyWorldAccess`, `UC_ExecPaymentServiceIndex`, `UC_AddAvatar`,
> `UC_ExecAccountStat`, весь семейство `UC_Stat*`, `UC_LocalizationStat`,
> `UC_LoginAndMoneyActionsLog`/`UC_ExecLoginAndMoneyActionsLog`) и точка входа
> `ds/user_cabinet.php` плюс её Flash-аналог `ds/classes/UA/UA_GetCabinet.class.php`.
> Остальные ~30 файлов (в основном однотипные `UC_ExecBy*`/`UC_Exec*` —
> обработчики конкретных платных услуг, почти все — потомки одного и того же
> `UC_PaymentServiceBase` с одинаковым паттерном `Index → SecondMenu → Confirm
> → Exec → Successful`) прочитаны частично (заголовок класса + 40–90 первых
> строк, для части — построчно до конца при наличии интересной логики).
> Три файла (`UC_ExecByEntryTurnir`, `UC_ExecByScienceAcceleration`,
> `UC_ExecTransferCombToBlackHole`/`UC_ExecRestoreCombFromBlackHole`,
> `UC_ExecMoneyTransfer`) не перечитывались заново — они уже разобраны
> построчно в других документах (см. таблицу перекрёстных ссылок ниже), здесь
> только даётся сводка и ссылка. Файлы в основном в кодировке
> **Windows-1251** — прочитаны через `iconv -f CP1251 -t UTF-8`.
>
> Секретов (паролей БД, API-ключей) в `ds/classes/UC/` не найдено — есть
> только обращения к константам вида `FREETOPAY_ApiSecretKeyB`
> (`UC_FreeToPay.class.php:54,61`), сами значения которых определены и уже
> редактированы в `ds/config/settings.php` (см.
> [billing-and-monetization.md](billing-and-monetization.md)). Постороннего
> контента (не по теме игры) не обнаружено.

## 1. Что это и как соотносится с остальной архитектурой

«Личный кабинет» (User Cabinet, `UC`) — это **отдельный веб-сайт поверх того
же движка**, живущий вне Flash-игры: обычные HTML-страницы, генерируемые
Smarty-шаблонами (`ds/templates/cabinet/*.tpl`), без единого XML-обмена с
Flash-клиентом, который использует остальной игровой протокол (см.
[protocol-client-server.md](protocol-client-server.md)). По терминологии
[game-engine.md, §2](game-engine.md) и
[multi-world-architecture.md](multi-world-architecture.md) кабинет — часть
`ds/` (игрового движка), а не портального CMS-слоя `sys/` (описан в
[portal-cms.md](portal-cms.md)): он работает **внутри** игровой сессии
(`$_SESSION['uid']`, тот же логин, что и в игре), просто рисует не Flash, а
HTML-страницы своими собственными шаблонами.

Кабинет — это точка, где сходятся три вещи, которые физически не помещаются
ни в один конкретный игровой мир:

1. **Деньги, общие для всех миров игрока.** Внутриигровая валюта (Gold/Silver
   Credits) хранится в специальном "мире" с идентификатором `'UC'`
   (`DE_WorldFromCollection::CreateFromID('UC')`) в центральной БД (`dbId =
   0`) — это отдельный денежный счёт, не привязанный ни к одному
   `userinstance` в конкретном мире. Из кабинета деньги можно переводить в
   мир и обратно (см. §8, полностью разобрано в
   [mechanics-world-transfer.md, §2](mechanics-world-transfer.md)) и платить
   ими за не-игровые платные услуги (§6-7).
2. **Глобальный (межмировой) альянс** — сущность, которая по определению не
   принадлежит одному миру (см. §11 и полный разбор в
   [mechanics-alliances-diplomacy.md, §7](mechanics-alliances-diplomacy.md)).
3. **Учётные операции с самим аккаунтом**: логин без входа в конкретный мир,
   смена пароля/языка/аватара, слияние нескольких аккаунтов в один, удаление
   инстанса игрока в мире (с каскадным удалением на уровне БД).

## 2. Точка входа и маршрутизация

### 2.1. Веб-путь: `ds/user_cabinet.php`

```php
SU_GameSessionWork::SessionContinue();
if (!isset($_SESSION['cab_uid'])) { UC_ExecLogin::execLogin(NULL); }
DS_Common::SetConnectWithTransaction(true);
$cab = new UC_ControlUserCabinet(array_merge($_GET, $_POST));
ME_EndDBTransactions::Exec();
```

(`ds/user_cabinet.php:17-27`). Продолжает игровую HTTP-сессию (та же кука,
что и в игре), проверяет геймдаун, автоматически "прологинивает" кабинет
(`cab_uid` в сессию, без пароля — см. §5), затем создаёт
`UC_ControlUserCabinet`, конструктор которого и есть весь диспетчер:

```php
static private $controls = array(
    'login'                         => 'UC_ControlLogin',
    'index'                         => 'UC_ExecUserCabinetIndex',
    'logout'                        => array('UC_ExecLogin', 'execLogout'),
    'exchange_confirm'              => array('UC_ExecMoneyExchange', 'confirm'),
    'transfer_to_instance_confirm'  => array('UC_ExecMoneyTransfer', 'ConfirmTransferToInstance'),
    'transfer_to_user_confirm'      => array('UC_ExecMoneyTransfer', 'ConfirmTransferToUser'),
    'exchange'                      => 'UC_ControlExchange',
    'transfer_to_user'              => 'UC_ControlTransferToUser',
    'transfer_to_instance'          => 'UC_ControlTransferToInstance',
    'account_stat'                  => 'UC_ExecAccountStat',
    'get_xml'                       => 'UC_ExecGetXMLData',
    'login_page'                    => 'UC_ControlLoginPage',
    'donate_confirm'                => array('UC_ExecDonation', 'ConfirmDonation'),
    'donate'                        => array('UC_ExecDonation', 'Exec'),
    'global_alliance'               => 'UC_ControlGlobalAlliance',
    'user_profile'                  => 'UC_ControlUserProfile',
    'payment_services'              => 'UC_ControlPaymentServices',
);
```

(`UC_ControlUserCabinet.class.php:35-70`). Каждое значение — либо имя класса
(вызывается его статический `Exec($user, $data)`), либо пара
`[класс, метод]`. При отсутствии `action` в запросе — `'index'`.

Перед диспетчеризацией конструктор:
- проверяет наличие `uid` в сессии (`_validateSession()`, строка 126-147) —
  если нет, печатает `'YOUR SESSION IS CLOSED. PLEASE, RELOGIN.'` и `exit`;
- для всех действий, кроме `login`/`login_page`, вызывает
  `UC_ExecLogin::checkStartSessionParams()` — см. §5.2;
- сохраняет и после выполнения действия восстанавливает `$_SESSION['dbId']`/
  `$_SESSION['world']` (строки 84-85, 117-119) — чтобы, если игрок зашёл в
  кабинет прямо из игры (не выходя из мира), после операции в кабинете он мог
  беспрепятственно продолжить играть в том же мире, хотя сама операция кабинета
  могла временно переключать `dbId`/`world` на `0`/`'UC'` или на любой другой
  мир игрока.

### 2.2. Второй, независимый путь: встроенная панель во Flash-клиенте (`UA_GetCabinet`)

Важная находка: у кабинета есть **второй, полностью отдельный вход**,
работающий не через `user_cabinet.php`+HTML/Smarty, а как обычная
XML-команда `UA_*` внутри самого Flash-клиента —
`ds/classes/UA/UA_GetCabinet.class.php` (тег действия — судя по имени класса
и составу методов, что-то вроде `getcabinet`; связка тег→класс лежит в БД,
`ds_useractiontype`, см. [game-engine.md, §3.5](game-engine.md)). Это
всплывающая внутриигровая панель "Магазин/Кабинет" — тот же набор платных
услуг и переводов денег, но:

- строит ответ вручную через `domxml_open_mem`/`domXML` (как и весь обычный
  игровой протокол), а не через Smarty-шаблоны кабинета;
- дублирует часть логики перевода денег кабинет↔мир своим кодом
  (`giftMoney()`, `moneyFormWorld()`, `moneyToWorld()`,
  `changeGoldTOSelver()`, `UA_GetCabinet.class.php:141-326`) поверх того же
  `MU_EntityOperation`/`MU_ExecTransferInterworld`, что и веб-версия — то
  есть логика перевода денег написана **дважды**, для двух разных UI;
- для собственно платных услуг (`callsubmenu()`/`confirmsubmenu()`,
  строки 329-504) **переиспользует** классы кабинета напрямую:
  `UC_PaymentServiceBase::GetPaymentServicesHeader()`,
  `UC_PaymentServiceMoneyOperation::CheckMoneyForOperation()`, и вызывает у
  найденного `UC_Exec*`-класса те же методы `FormMenu`/`FormConfirmMenu`/
  `FormPreConfirmMenu`/`Exec`, что вызывает и веб-контроллер — то есть
  бизнес-логика платных услуг (§6-7) **общая** для двух UI, разный только
  слой представления.
- отдельно рисует "магазин артефактов" (`ShowArtefactsShop()`, строки
  704-796) — это не относится к `UC_PaymentService*`, а напрямую читает
  `DS_Artefact::GetArtefactsForShop()`.

**Находка, требующая внимания при ремастере (скрытое админ-меню по IP/uid):**
в `open()` (`UA_GetCabinet.class.php:625-637`) для пунктов "выдать уникальное
здание" (`PS_GRANT_BUILDING`), "удалить уникальное здание"
(`PS_DELETE_UNIQUE_BUILDING`) и "бонус на ресурсы" (`PS_BY_BONUS_RESOURCE`)
видимость в меню включена только если `$_SERVER['REMOTE_ADDR']` совпадает с
одним из двух захардкоженных IP (`195.182.156.98`, `89.112.59.226`) **или**
`$_SESSION['uid'] == 98` — то есть скрытое "админское"/тестовое меню,
завязанное на конкретный IP и конкретный ID аккаунта прямо в коде. Это
**не является контролем доступа**, а только прячет пункт меню — см. §14 о
том, что реальные обработчики этих услуг (`UC_ExecGrantBuilding`,
`UC_ExecDeleteUniqueBuilding`, `UC_ExecByBonus`) никакой аналогичной проверки
по IP/uid не делают.

## 3. Обзор разделов кабинета (по ключу `action`)

| `action` | Класс/метод | Что делает |
|---|---|---|
| `index` (по умолчанию) | `UC_ExecUserCabinetIndex::Exec` | Главная страница: баланс кабинета (GC/SC), баланс во всех мирах игрока (там, где `ALLOW_MONEY_ACTION=1`), последние 5 операций |
| `login` | `UC_ControlLogin::Exec` → `UC_ExecLogin::execLogin` | "Вход" в кабинет (см. §5 — фактически без проверки пароля) |
| `login_page` | `UC_ControlLoginPage::Exec` | Показ формы логина (`user_cabinet_login.tpl`) |
| `logout` | `UC_ExecLogin::execLogout` | Выход, редирект на `index_start.php` |
| `exchange` / `exchange_confirm` | `UC_ControlExchange` / `UC_ExecMoneyExchange` | Обмен GC → SC по курсу `GC_COURSE` (см. §8.2) |
| `transfer_to_user` / `_confirm` | `UC_ControlTransferToUser` / `UC_ExecMoneyTransfer::ExecTransferToUser` | Вывод денег из мира в кабинет — разобрано в [mechanics-world-transfer.md, §2.4](mechanics-world-transfer.md) |
| `transfer_to_instance` / `_confirm` | `UC_ControlTransferToInstance` / `UC_ExecMoneyTransfer::ExecTransferToInstance` | Перевод денег из кабинета в мир — там же |
| `donate` / `donate_confirm` | `UC_ExecDonation` | Добровольный перевод денег разработчикам (см. §8.3) |
| `account_stat` | `UC_ExecAccountStat` | История операций по счетам (кабинет + все миры), см. §10 |
| `get_xml` | `UC_ExecGetXMLData` | XML-выгрузка баланса для встраиваемого мини-виджета, см. §12 |
| `user_profile` | `UC_ControlUserProfile` (**класс отсутствует в архиве**, см. §9 и §14) | Профиль: пароль/язык/часовой пояс/аватар |
| `payment_services` | `UC_ControlPaymentServices` | Список и обработка ~25 платных услуг, см. §6-7 |
| `global_alliance` | `UC_ControlGlobalAlliance` → `UC_LogicGetPageControllerForUser` | Управление глобальным (межмировым) альянсом, см. §11 |

Все страницы, кроме `login`/`login_page`, требуют активной игровой сессии
(`$_SESSION['uid']`) — но НЕ отдельного пароля кабинета (см. §5).
Каждый `UC_Page*`-класс (`extends UC_PageBase`) рендерит один
Smarty-шаблон из `ds/templates/cabinet/` (кодировка ответа выбирается по
языку сессии — `windows-1251` для русского/английского, `iso-8859-1` для
немецкого, `UC_PageBase.class.php:20-44`).

## 4. Логин в кабинет (`UC_ExecLogin`)

Особенность архитектуры: `$_SESSION['uid']` — это обычная игровая сессия
(проверяется/устанавливается ещё до входа в кабинет, `SU_GameSessionWork`).
"Логин в кабинет" — это **отдельный**, дополнительный флаг
`$_SESSION['cab_uid']`, который в реальности почти никогда не требует
повторного ввода пароля:

```php
static public function execLogin($data)
{
    // Убираем проверку логина и пароля при входе в кабинет
    /* ...закомментированный блок VD_User::Validate(...)... */
    $_SESSION['cab_uid'] = $_SESSION['uid'];
    self::_setStartSessionParams();
    return true;
}
```

(`UC_ExecLogin.class.php:22-68`) — код проверки пары логин/пароль **закомментирован
целиком**, а `cab_uid` просто копируется из уже существующего `uid`. То есть
фактически "вход в кабинет" в текущем коде — это не аутентификация, а
служебная пометка "кабинет открыт в рамках уже идущей игровой сессии".

### 4.1. "Отпечаток" сессии кабинета

При первом входе (`_setStartSessionParams()`, строки 103-138) в сессию
записываются:
- **первые два октета IP** (`$_SERVER['REMOTE_ADDR']`, плюс отдельно первые
  два октета каждого адреса из `X-Forwarded-For`) — `cab_start_ip`;
- **ID сессии** (`session_id()`) — `cab_start_sid`;
- **MD5 от `User-Agent`** — `cab_start_ua`.

При каждом последующем запросе (кроме `login`/`login_page`)
`checkStartSessionParams()` (строки 157-183) пересчитывает те же три
значения и сверяет их через `assert()` с сохранёнными в сессии — то есть это
защита от угона кабинета через кражу одной только PHP-сессионной куки (смена
подсети/устройства обнуляет доступ). Поскольку сверка идёт через `assert()`,
а не явный `if`/`exit`, её реальная строгость на проде зависит от
`assert.active`/`zend.assertions` в PHP-конфигурации, которая в архиве не
представлена (см. §15).

### 4.2. Мёртвый переключатель "спрашивать пароль на каждое действие"

`UC_ControlUserCabinet::$loginProtection` — статический флаг, жёстко
инициализированный как `false` (`UC_ControlUserCabinet.class.php:28`) и
**нигде в кодовой базе не переключаемый в `true`** (проверено по всем
вхождениям `loginProtection`). Три контроллера денежных операций
(`UC_ControlExchange`, `UC_ControlTransferToUser`,
`UC_ControlTransferToInstance`) написаны так, чтобы при `$loginProtection ==
true` перед выполнением требовать повторного ввода пароля через
`UC_ExecLogin::confirmLogin()` (полноценная проверка `VD_User::Validate()` +
сверка `$_SESSION['uid']`/`$_SESSION['cab_uid']`, строки 70-97 в
`UC_ExecLogin.class.php`) — то есть в коде **есть готовая** реализация
"подтверди пароль перед переводом денег", но она принудительно выключена
константой `false`, а не настройкой. Разработчики, судя по всему, начали
делать доп. защиту денежных операций паролем, но отключили её (возможно,
временно, для отладки — комментариев в коде на этот счёт нет).

## 5. Профиль пользователя — предположительно мёртвый маршрут (`UC_ExecUserProfile`)

`UC_ExecUserProfile::Exec()` (`ds/classes/UC/UC_ExecUserProfile.class.php`) —
полноценно реализованная страница профиля:
- смена пароля (сверка старого через `md5`, валидация нового
  `ValidatePassword`), языка интерфейса, часового пояса (`time_delta`),
  флага масштабирования интерфейса (`scaling`, только для "профи"-аккаунтов);
- смена имени персонажа (`changeName()`) и добавление/смена аватара
  (`UC_AddAvatar`) — обе операции **платные**, списываются через
  `UC_PaymentServiceMoneyOperation::GetMoneyForOperation('PS_CHANGE_NAME'
  | 'PS_CHANGE_AVATAR' | 'PS_ADD_AVATAR')` (строки 68-321) — то есть с
  формальной точки зрения это тоже платная услуга, просто вызываемая не
  через общий `payment_services`-роутер, а напрямую.

**Находка:** маршрутизатор (`UC_ControlUserCabinet`, §2.1) ссылается на класс
`UC_ControlUserProfile` для `action=user_profile`
(`UC_ControlUserCabinet.class.php:67`), но такого класса **нет нигде в
архиве** — ни файла `UC_ControlUserProfile.class.php` (что нарушало бы
единственное жёсткое правило `import()`, см.
[game-engine.md, §1](game-engine.md): классы `UC_*` обязаны резолвиться в
`ds/classes/UC/`), ни определения класса под этим именем где-либо ещё
(проверено полнотекстовым поиском по всему `old-sphere/`). Обращение к
`action=user_profile` в текущем виде архива приводит к фатальной ошибке PHP
("Class 'UC_ControlUserProfile' not found"), а не к странице профиля. Раз
`UC_PageUserProfile` тоже ссылается на шаблон `user_profile_index.tpl`,
которого в архиве тоже нет (см. §14), похоже, что **вся страница профиля
недостижима в снятом архиве** — либо это баг оригинала (класс/шаблон
переименовали или удалили, забыв почистить роутер), либо артефакт неполного
снятия слепка с продакшена. `UC_ExecUserProfile` и `UC_AddAvatar` при этом
не мёртвый код в смысле "не вызывается никогда" — видно, что задумывался
именно как реализация этого раздела, просто без рабочего входа в
архивированном виде.

## 6. Платные услуги — общий паттерн (`UC_PaymentServiceBase`)

Точка входа — `action=payment_services`
(`UC_ControlPaymentServices.class.php`):

1. Список активных услуг тянется из БД (не хардкодится в PHP) —
   `UC_PaymentServiceBase::GetPaymentServices()`
   (`UC_PaymentServiceBase.class.php:36-64`):
   ```sql
   SELECT ps.service_name, ps.handler_class_name, ps.section
   FROM ds_payment_services ps
   LEFT JOIN ds_money_action_types mat ON (mat.action_name = ps.money_ation_tupe_accordance)
   WHERE mat.is_active = 't'
   ORDER BY sort_order
   ```
   Результат кешируется целиком (`GLOBAL_CACHE`). Это значит, что набор и
   порядок платных услуг конфигурируется через таблицы `ds_payment_services`/
   `ds_money_action_types`, а не через код — включение/отключение конкретной
   услуги на проде не требует деплоя.
2. Если в запросе есть `ps_action`, совпадающий с известной услугой, —
   создаётся её обработчик (`$services[$ps_action]['header']`, класс из
   БД-колонки `handler_class_name`) и вызывается его `Exec()`. Если нет —
   выводится список услуг, сгруппированных по `section`.
3. **Найденное мёртвое звено:** `UC_ExecPaymentServiceIndex.class.php`
   содержит захардкоженный PHP-массив ровно тех же ~25 услуг с привязкой к
   классам-обработчикам — но **весь класс целиком закомментирован**
   (`/* ... */`, файл — единственная строка кода после комментария —
   `?>`). Судя по идентичному набору услуг, это более ранняя, статическая
   версия реестра, вытесненная BD-driven версией в `GetPaymentServices()`
   выше (аналогично тому, как `SU_ReplicationControl` вытеснила разовый
   `SyncTables`, см. [game-engine.md, §4](game-engine.md)) — оставлена в
   дереве как справочник/архив, а не рабочий код.

Каждый конкретный обработчик — статический класс, `extends
UC_PaymentServiceBase`, с общим для всех циклом:

- **`_ShowIndex()`** — если у игрока несколько миров, просит выбрать мир;
  если мир один — сразу переходит на второй шаг;
- **`_ShowSecondMenu()`** — конкретная форма услуги, попутно
  `_CheckEnoughMoney()` (сверка баланса кабинета через
  `UC_PaymentServiceMoneyOperation::CheckMoneyForOperation`, с fallback на
  проверку в золоте, если не хватает серебра, `UC_PaymentServiceBase.class.php:134-162`);
- **основной exec-метод** (имя разное у каждого класса) — исполняет саму
  операцию внутри БД-транзакции, **повторно** проверяет баланс (защита от
  гонки между показом формы и подтверждением), списывает деньги
  (`UC_PaymentServiceMoneyOperation::GetMoneyForOperation`), пишет запись в
  лог (`_Log()`, таблицы `ds_paymentserviceslog`/`_users`,
  `UC_PaymentServiceBase.class.php:253-388`), коммитит транзакцию
  (`ME_EndDBTransactions::Exec()`) и делает `header("Location:
  ...&exec_action=successful...")` — классический паттерн
  Post/Redirect/Get, защищающий от повторной покупки по F5;
- **`_ShowSuccessful()`** — страница успеха, читает параметры из
  `$_REQUEST` (данные результата пробрасываются через query string
  редиректа, а не через сессию).

Общая проверка баланса (`_CheckEnoughMoney`) и списание денег
(`GetMoneyForOperation`) в конечном счёте идут через `UC_PaymentServiceMoneyOperation`
(`ds/classes/UC/UC_PaymentServiceMoneyOperation.class.php`) — тонкую обёртку
над `DE_MoneyActionType`/`MU_EntityMoneySumm`/денежным движком `MU_*`,
которая уже подробно разобрана как "общая касса" в
[billing-and-monetization.md, §"Внутренняя валюта GC/SC"](billing-and-monetization.md)
и упоминается в [mechanics-reincarnation.md](mechanics-reincarnation.md),
[mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md) —
здесь не переразбирается.

Видимость всего раздела платных услуг для конкретного мира управляется
настройкой мира `ALLOW_PAYMENT_SERVICES` (`DE_WorldSettings`,
`UC_PaymentServiceBase.class.php:117-125`) — рядом, но отдельно от
`ALLOW_MONEY_ACTION` (видимость баланса мира на главной странице кабинета,
`UC_ExecUserCabinetIndex.class.php:76`) и `ALLOW_USER_CABINET` (используется
только в закомментированном, неактуальном коде
`UC_ExecGetXMLData.class.php:78`, не найдено эквивалента в живой ветке того
же файла — см. §12) — три похожих, но разных переключателя на уровне
настроек мира.

## 7. Каталог платных услуг

Услуги сгруппированы (по секциям из `ds_payment_services.section`, где
удалось восстановить группировку из закомментированного `UC_ExecPaymentServiceIndex`,
§6). Полностью или частично прочитанные в этой сессии — со звёздочкой;
уже подробно разобранные в других документах — с прямой ссылкой вместо
пересказа.

| Класс (`UC_Exec*`) | Услуга (`PS_*`) | Что делает |
|---|---|---|
| `UC_ExecByResources`* | `PS_BUY_RESOURCE_{FOOD,STASIS_ENERGY,KADERIUM,NANO_CRYSTAL,PRODIUM}` | Покупка пакета ресурса за GC/SC в выбранном мире (гейт `ALLOW_MONEY_ACTION`+`BUY_RESOURCE_FOR_MONEY_ALLOWED`) |
| `UC_ExecByPremium`* | `PS_BUY_PREMIUM_{30,90,180,360}` (+ комбо с анализатором) | Покупка премиум-дней напрямую за игровую валюту, минуя внешний биллинг — см. также [billing-and-monetization.md](billing-and-monetization.md) |
| `UC_ExecByPremiumBan`* | `PS_BY_PREMIUM_BAN[_30]` | «ВИП-сауна» — платная самоблокировка от атак, детально разобрано в [mechanics-moderation-vacation-messaging.md](mechanics-moderation-vacation-messaging.md) и [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md) |
| `UC_ExecByAnalizator`* | `PS_BUY_ANALIZATOR` | Покупка/продление "анализатора" (см. [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md)) через `PU_ExecChangeAnalizerPremium::Exec` |
| `UC_ExecByScienceAcceleration` | `PS_BY_SCIENCE_ACCELERATION` | Ускорение текущего исследования — детально в [mechanics-research.md, §5.6](mechanics-research.md) |
| `UC_ExecByEntryTurnir` | `PS_BUY_TURNIR` | Платный вход в турнир — детально в [mechanics-tournaments.md, §6.1](mechanics-tournaments.md) |
| `UC_ExecByTraining`* | `PS_BUY_TRAINING_COMPLETION` | Мгновенное завершение обучения/постройки — единственная услуга, явно исключённая из общего списка на странице кабинета (`UC_ControlPaymentServices.class.php:30-32`, показывается только там, где вызвана напрямую) |
| `UC_ExecByUnit`* | `PS_BY_UNIT_SCIENTIIFIC`, `PS_BY_UNIT_ARCHEOLOG` | Покупка учёных/археологов на соту за деньги (в обход обычного найма) |
| `UC_ExecByBonus`* | `PS_BY_BONUS_RESOURCE[_SINGLE]`, `PS_BY_BONUS_MILITARY` (ветка пуста) | Временный бонус к производству ресурсов (пакетом или по одному) на 14/30/90/180/360 дней; ветка `_MILITARY` объявлена, но не реализована (`switch` без `case`-тела, `UC_ExecByBonus.class.php:218-219,337-338`) |
| `UC_ExecChangeCombName`* | `PS_CHANGE_COMB_NAME` | Платное переименование соты |
| `UC_ExecChangeCoords`* | `PS_CHANGE_COORDS[_MELS]` | Переезд на свободные координаты — см. [mechanics-world-transfer.md, §3](mechanics-world-transfer.md) |
| `UC_ExecExchangeCoords`* | `PS_EXCHANGE_COORDS[_MELS]` | Обмен координатами с другим игроком, с выбором "кто платит" — там же |
| `UC_ExecReserveComb`* | `PS_RESERVE_COMB` | Платное резервирование конкретных координат под будущую регистрацию в мире (таблица `ds_reservedcomb`); есть закомментированная проверка `_CheckIfUserCanRegInWorld()` (см. [mechanics-premium-gameplay-effects.md](mechanics-premium-gameplay-effects.md) про `checkIfUserProfi`) |
| `UC_ExecDeleteInstance`* | `PS_DELETE_INSTANCE` | Удаление персонажа в мире — требует **повторного ввода пароля** (`strcmp(md5($password), ...)`, `UC_ExecDeleteInstance.class.php:110-114`), переводит остаток денег в кабинет, вызывает БД-функцию `drop_uiid($userInstanceID)` (см. §14, [mechanics-combat.md](mechanics-combat.md) про `DU_*`-каскад) |
| `UC_ExecDeliveryInstance`* | `PS_DELYVERY_INSTANCE` | Передача персонажа другому игроку (по логину) |
| `UC_ExecMergeAccount`* | `PS_MERGE_ACCOUNTS` | Слияние нескольких аккаунтов в один — см. §13 |
| `UC_ExecWorldAbility`* | `PS_WORLD_ABILITY` | Открытие возможности регистрации в мире, где игрок ещё не зарегистрирован |
| `UC_ExecMultyWorldAccess`* | `PS_MYLTY_WORLD_ACCESS` | Открытие доступа сразу ко всем мирам разом, через биллинговый `BG_AddAllWorldAbility` |
| `UC_ExecTransferCombToBlackHole` | `PS_TRANSFER_COMB_TO_BLACK_HOLE` | Отправка соты в "Чёрную дыру" — см. [mechanics-world-transfer.md, §1.9](mechanics-world-transfer.md) |
| `UC_ExecRestoreCombFromBlackHole` | `PS_RESTORE_COMB_FROM_BLACK_HOLE` | Восстановление из "Чёрной дыры" — там же |
| `UC_ExecGrantBuilding`* | `PS_GRANT_BUILDING` | Выдаёт захардкоженное `id_building = 65` на соту игрока (`UC_ExecGrantBuilding.class.php:162`) — скрытое меню, см. §2.2/§14 |
| `UC_ExecDeleteUniqueBuilding`* | `PS_DELETE_UNIQUE_BUILDING` | Удаление "уникального" здания (посольство, храм религии, торговые коннекторы и т.д. из белого списка 10 `id_building`, `UC_ExecDeleteUniqueBuilding.class.php:16-39`) — тоже скрытое меню, с содержательными доменными проверками (нельзя удалить храм религии, если это последний, нельзя удалить посольство, если игрок ещё в альянсе, и т.д.) |
| `UC_ExecLoginAndMoneyActionsLog`* | `PS_GET_LOG` | Платная отправка на email истории визитов/сделок (`BG_UserStatistics::getTradeAndVisitStatistics`) |
| `UC_ExecTrainSpeedup`* | (страница вне общего `ps_action`-реестра выше) | Ускорение тренировки войск за деньги, по мирам, с флагом `TRAIN_SPEEDUP` |

Отдельно есть **бесплатная** страница статистики визитов/операций
`UC_LoginAndMoneyActionsLog::Exec()` (не путать с платной пересылкой на
почту выше) — просто показывает ту же статистику `BG_UserStatistics` прямо
на странице кабинета, без списания денег (`log_action=show_stat`,
`UC_LoginAndMoneyActionsLog.class.php`); использует тот же шаблон
`login_and_money_actions_log.tpl`, которого нет в архиве (см. §14).

## 8. Переводы, обмен и добровольные пожертвования денег

Основная механика перевода денег кабинет↔мир и межмирового перевода игрок→игрок
уже полностью разобрана в
[mechanics-world-transfer.md, "Часть 2: денежные переводы между мирами"](mechanics-world-transfer.md) —
включая `UC_ExecMoneyTransfer::ExecTransferToUser`/`ExecTransferToInstance`,
комиссию `USER_CABINET_TRANSFER_TAX`, и обходной путь без комиссии
(`_returnMoneyToCabinet()`). Здесь — то, что в тот документ не попало.

### 8.1. Обмен GC → SC (`UC_ExecMoneyExchange`)

`action=exchange` → `UC_ExecMoneyExchange::Exec()`
(`UC_ExecMoneyExchange.class.php`). Простая операция: списывает `gc` золотых
кредитов со счёта кабинета в пользу системного аккаунта `DestinySphere`
(`DE_User::CreateFromName('DestinySphere')`) через `MU_EntityOperation` с
типом `EXCHANGE_CREDITS` — фактического начисления SC взамен **в этом коде
не видно** (сама конвертация GC→SC по курсу `GC_COURSE`, судя по всему,
происходит на стороне БД-триггера/константы курса, отображаемого в UI
(`UC_PageExchangeConfirm.class.php:19`), а не явным вторым `MU_EntityOperation`
в этом файле — **предположительно**, конверсия зашита в саму трактовку
операции `EXCHANGE_CREDITS` денежным движком `MU_*`, не переразобрано
построчно в этом документе). Защита от повторного выполнения при обновлении
страницы — флаг `$_SESSION['exchanged']`.

### 8.2. Добровольное пожертвование (`UC_ExecDonation`)

`action=donate` (`UC_ExecDonation.class.php`) — перевод произвольной суммы
GC/SC от игрока на аккаунт `DestinySphere` тем же примитивом
`MU_EntityOperation`/`PVP_TRANSFER`, что и обычный игрок-игроку перевод.
После выполнения отправляет email на 4 захардкоженных адреса разработчиков
(`maxim@`, `dmitri@`, `netnet@`, `annita@destinysphere.com`,
`UC_ExecDonation.class.php:5-10,106-131`) с суммой и данными плательщика —
единственное место в `UC/`, отправляющее email напрямую разработчикам, а не
игроку/через шаблон.

## 9. Статистика операций (`UC_ExecAccountStat` + семейство `UC_Stat*`)

`action=account_stat` (`UC_ExecAccountStat.class.php`) собирает по каждому
денежному счёту игрока (кабинет + каждый мир, где есть аккаунт) последние
N (по умолчанию 50, за последние `statPeriod=60` дней) строк из
`ds_money_list` (джойн на `ds_money_action_types` — тот же справочник типов
денежных операций, что использует общий движок `MU_*`).

Каждая строка истории рендерится через **паттерн Strategy**: класс-хендлер
выбирается по имени операции через
`MU_SearchOperations::GetActionsStatHandler()` (маппинг живёт вне `UC/`, в
`MU`-подсистеме), с классом `UC_StatNull` как безопасным дефолтом, если для
операции хендлера не нашлось (`UC_StatNull::formStat()` просто возвращает
`false`, строка не показывается). Обработчики — все `extends UC_StatBase`
(общий конструктор, читающий доп. данные по `id_list`/`log_action_table` из
таблицы, специфичной для типа операции, `UC_StatBase.class.php:26-44`) и
реализуют один метод `formStat()`, форматирующий `DATE`/`ACTION`/`INFO`:

| Класс | Операция |
|---|---|
| `UC_StatBillingPayment` | Пополнение через внешний биллинг |
| `UC_StatEnterMoney` | Пополнение (SMS/FreeToPay/SponsorPay и т.п. — общий `EnterMoney`) |
| `UC_StatExchange` | Обмен GC→SC (см. §8.1), с курсом в тексте |
| `UC_StatPVPTransfer` | Перевод другому игроку — показывает имя получателя |
| `UC_StatReciveFromWorld` / `UC_StatSendToWorld` | Межмировой перевод, с именем мира-источника/назначения |
| `UC_StatReserveMoney` | Резервирование денег (вероятно, залог мира между альянсами/игроками, см. [mechanics-alliances-diplomacy.md](mechanics-alliances-diplomacy.md)) |
| `UC_StatServicePayment` | Оплата платной услуги (§6-7), с именем мира, если применимо |
| `UC_StatTrade` | Операция биржи |
| `UC_StatNull` | Заглушка/неизвестный тип — строка не показывается |

Локализация названий операций — через отдельный простой class-cache
`UC_LocalizationStat::Get($text)` (грузит `.lang`-файл
`user_cabinet_stat_out.lang` один раз на запрос,
`UC_LocalizationStat.class.php`).

## 10. XML-виджет баланса (`UC_ExecGetXMLData`)

`action=get_xml` (`UC_ExecGetXMLData.class.php`) — не HTML-страница, а
маленький "сырой" XML-ответ (собранный конкатенацией строк, не через
`domXML`/Smarty) с логином, email, балансом кабинета и списком
`<sphere name=... gold=... silver=... pic=.../>` по всем мирам игрока,
целиком в `iconv('cp1251', 'utf-8', ...)`. Судя по форме ответа
(теги-атрибуты под конкретную свёрстанную мини-виджет-разметку, а не
JSON/обычный XML-протокол игры) — **предположительно** это данные для
встраиваемого небольшого Flash- или JS-виджета баланса на других страницах
портала (например, в шапке сайта), но сам потребитель этого XML физически
не найден ни в `old-sphere/` (кроме самой точки генерации), ни ранее
задокументирован в `docs/client-*` — открытый вопрос, см. §16.

## 11. Слияние аккаунтов (`UC_ExecMergeAccount`)

`ps_action=PS_MERGE_ACCOUNTS` (`UC_ExecMergeAccount.class.php`) — платная
услуга, позволяющая объединить несколько игровых аккаунтов (разных логинов)
под одним "главным" (тем, из-под которого зашли в кабинет). Механизм:

1. Игрок вводит список логинов + **пароль каждого** из объединяемых
   аккаунтов (`self::$logins`/`self::$passwords`, параллельные массивы).
2. Для каждого логина `_ShowSecondMenu()` проверяет: аккаунт существует
   (`_GetUserIdByName`), пароль верен (`md5($password)` сравнивается с
   хэшем в `ds_user`, строки 108-117) — то есть перед слиянием **доказывается
   владение** каждым присоединяемым аккаунтом, а не просто его существование;
   дополнительно отклоняется, если у двух объединяемых логинов есть
   персонаж в одном и том же мире (`DOUBLE_WORLD`).
3. `_MergeAccount()` — для каждого мира каждого присоединяемого аккаунта:
   переводит остаток GC/SC этого мира в кабинет (`MU_ExecTransferInterworld`),
   затем **напрямую SQL-запросом** переписывает владельца
   (`UPDATE ds_userinstance SET id_user = <главный> WHERE id_userinstance =
   ...`, аналогично `ds_userinstancecollection` в центральной БД) — то есть
   персонаж физически "усыновляется" другим аккаунтом, а не пересоздаётся;
   затем переводит весь остаток денег присоединяемого аккаунта из кабинета
   на счёт главного (`PVP_TRANSFER`).
4. Итог логируется в `ds_paymentserviceslog`/`_users` с массивом всех
   вовлечённых игроков (не только инициатора — `_Log(..., $usersArr)`,
   единственная услуга из всех прочитанных, где лог явно перечисляет
   нескольких пользователей как участников одной операции).

Прямой `UPDATE id_user` в обход обычного пайплайна создания/удаления
персонажа (`WorldUtils::addNewInstance`/`DU_*`-каскад, см.
[game-engine.md, §4](game-engine.md) и чеклист `DU` в
[docs/README.md](README.md)) — заметная архитектурная особенность: слияние
не проходит через штатное каскадное удаление, а просто "перевешивает" FK.

## 12. Глобальный (межмировой) альянс

`action=global_alliance` диспетчеризуется через
`UC_LogicGetPageControllerForUser::Get($user)`
(`UC_LogicGetPageControllerForUser.class.php`) — три ветки в зависимости от
роли игрока (лидер существующего глобального альянса → `LEADER_CONTROL_PANEL`
→ `UC_ControlGlobalAllianceControlPanel`; рядовой участник →
`MEMBER_PANEL` → `UC_ControlGlobalAllianceMemberPanel`; никто →
`REGISTRATION_PANEL` → `UC_ControlGlobalAllianceRegistration`), плюс
заготовленная, но нереализованная четвёртая роль "заместитель"
(`ASSISTANT_CONTROL_PANEL`, пустая ветка,
`UC_ControlGlobalAlliance.class.php:32-34`). Вся страница целиком выключается
общим рубильником `GLOBAL_ALLIANCE_ACTIVE` (`DE_GlobalSettings`,
`UC_ControlGlobalAlliance.class.php:38-46`) — при выключенном флаге
выводится "Эта страница находится в разработке" и `exit`.

Это единственный раздел кабинета, который **уже полностью и подробно**
разобран в отдельном документе — модель данных (`ds_global_alliance` и
сопутствующие таблицы), роли, здание `ALLIANCE_CONNECTOR` как мост
локальный↔глобальный альянс, полный жизненный цикл (создание, вступление,
исключение, выход, смена лидера, денежные операции через
`UC_GlobalAllianceMoneyOperations`) и найденные несогласованности
оригинала — см.
**[mechanics-alliances-diplomacy.md, раздел 7 "Глобальный (межмировой) альянс"](mechanics-alliances-diplomacy.md)**.
Здесь эта тема не пересказывается повторно.

## 13. Найденные особенности безопасности и целостности

Сводка находок этой сессии, важных при проектировании ремастера:

1. **"Вход в кабинет" не проверяет пароль.** `UC_ExecLogin::execLogin()`
   копирует `$_SESSION['uid']` в `$_SESSION['cab_uid']` без какой-либо
   проверки — реальная (закомментированная) проверка логина/пароля осталась
   в виде мёртвого кода (§4).
2. **Защита "спрашивать пароль перед денежной операцией" отключена
   константой.** `UC_ControlUserCabinet::$loginProtection = false`,
   переключателя в `true` нигде нет — рабочая инфраструктура (`confirmLogin`)
   существует, но не используется (§4.2).
3. **Скрытое меню по IP/uid — это не авторизация.** Во Flash-панели
   (`UA_GetCabinet::open()`) три платные услуги (`PS_GRANT_BUILDING`,
   `PS_DELETE_UNIQUE_BUILDING`, `PS_BY_BONUS_RESOURCE`) показываются в меню
   только для двух IP или `uid=98` — но их серверные обработчики
   (`UC_ExecGrantBuilding`, `UC_ExecDeleteUniqueBuilding`, `UC_ExecByBonus`)
   доступны через обычный веб-путь `user_cabinet.php?action=payment_services
   &ps_action=PS_GRANT_BUILDING` **без какой-либо проверки IP или uid** —
   единственное условие — активность услуги в `ds_payment_services`
   (БД-конфигурация, не код) и достаточный баланс. То есть, если эти услуги
   реально включены в БД для обычных игроков (что по коду не устанавливается),
   любой игрок может ими воспользоваться, зная имя `ps_action` — "защита"
   существует только на уровне видимости пункта меню в одном из двух UI (§2.2).
4. **Удаление персонажа требует повторного пароля**, в отличие от
   большинства других платных услуг (`UC_ExecDeleteInstance`,
   `UC_ExecDeleteInstance.class.php:104-114`) — единственная услуга из
   прочитанных с этим требованием, не считая слияния аккаунтов.
5. **Слияние аккаунтов доказывает владение паролем** каждого
   присоединяемого аккаунта перед объединением (§11) — сильнее, чем просто
   проверка существования логина.
6. **`action=user_profile` ведёт к отсутствующему классу**
   `UC_ControlUserProfile` (§9) — не проблема безопасности сама по себе, но
   означает, что раздел смены пароля/аватара недостижим в архивированном
   виде кода.
7. **Шесть Smarty-шаблонов, на которые ссылается код, отсутствуют в
   архиве:** `global_alliance_index.tpl`, `global_alliance_cp_index.tpl`,
   `global_alliance_member_index.tpl` (все три — `UC_PageGlobalAllianceIndex`/
   `ControlPanel`/`MemberPanel`), `user_profile_index.tpl`
   (`UC_PageUserProfile`), `change_coords.tpl` (`UC_PageChangeCoords`),
   `login_and_money_actions_log.tpl` (`UC_PageLoginAndMoneyActionsLog`) —
   проверено поиском по всему дереву `old-sphere/`, ни один файл с таким
   именем не найден ни в `ds/templates/`, ни где-либо ещё. Для трёх
   глобально-альянсовых шаблонов есть правдоподобное объяснение — раздел
   целиком выключаем флагом `GLOBAL_ALLIANCE_ACTIVE` (§12), возможно, шаблоны
   не были даже доделаны/выгружены на прод, пока флаг стоял выключенным.
   Для `user_profile_index.tpl`/`change_coords.tpl`/
   `login_and_money_actions_log.tpl` подобного гейта не найдено — открытый
   вопрос, см. §16.

## 14. Полный список файлов `ds/classes/UC/`

Компактно, по алфавиту. Ссылки на другие документы — там, где файл уже
разобран подробно в другом месте.

| Файл | Назначение |
|---|---|
| `UC_AddAvatar.class.php` | Загрузка файла аватара на сервер (сотрудник профиля, §9) |
| `UC_ControlExchange.class.php` | Контроллер обмена GC→SC, опциональный `loginProtection` |
| `UC_ControlGlobalAlliance.class.php` | Диспетчер ролей глобального альянса — см. [mechanics-alliances-diplomacy.md §7](mechanics-alliances-diplomacy.md) |
| `UC_ControlGlobalAllianceControlPanel.class.php` | Панель лидера глобального альянса — там же |
| `UC_ControlGlobalAllianceMemberPanel.class.php` | Панель участника глобального альянса — там же |
| `UC_ControlGlobalAllianceRegistration.class.php` | Создание/вступление в глобальный альянс — там же |
| `UC_ControlIndex.class.php` | Тонкая обёртка над `UC_ExecUserCabinetIndex` |
| `UC_ControlInterface.class.php` | Интерфейс `Exec(DE_User, array)` — контракт всех `UC_Control*` |
| `UC_ControlLogin.class.php` | Обработка формы логина, ошибка → `UC_OutUserCabinet::Out('Error', ...)` |
| `UC_ControlLoginPage.class.php` | Показ формы логина |
| `UC_ControlPaymentServices.class.php` | Диспетчер платных услуг (§6) |
| `UC_ControlTransferToInstance.class.php` | Контроллер перевода деньги→мир |
| `UC_ControlTransferToUser.class.php` | Контроллер вывода деньги мир→кабинет |
| `UC_ControlUserCabinet.class.php` | Главный диспетчер (§2.1) |
| `UC_ExecAccountStat.class.php` | История операций по счетам (§10) |
| `UC_ExecByAnalizator.class.php` | Покупка "анализатора" (§7) |
| `UC_ExecByBonus.class.php` | Временный бонус к ресурсам/военке (§7) |
| `UC_ExecByEntryTurnir.class.php` | Платный вход в турнир — [mechanics-tournaments.md](mechanics-tournaments.md) |
| `UC_ExecByPremium.class.php` | Покупка премиум-дней (§7) |
| `UC_ExecByPremiumBan.class.php` | "ВИП-сауна" — [mechanics-moderation-vacation-messaging.md](mechanics-moderation-vacation-messaging.md) |
| `UC_ExecByResources.class.php` | Покупка ресурсов (§7) |
| `UC_ExecByScienceAcceleration.class.php` | Ускорение исследования — [mechanics-research.md §5.6](mechanics-research.md) |
| `UC_ExecByTraining.class.php` | Мгновенное завершение обучения/постройки (§7) |
| `UC_ExecByUnit.class.php` | Покупка учёных/археологов (§7) |
| `UC_ExecChangeCombName.class.php` | Платное переименование соты (§7) |
| `UC_ExecChangeCoords.class.php` | Платный переезд — [mechanics-world-transfer.md §3](mechanics-world-transfer.md) |
| `UC_ExecDeleteInstance.class.php` | Удаление персонажа, с паролем (§7, §13) |
| `UC_ExecDeleteUniqueBuilding.class.php` | Удаление "уникального" здания (§7) |
| `UC_ExecDeliveryInstance.class.php` | Передача персонажа другому игроку (§7) |
| `UC_ExecDonation.class.php` | Добровольное пожертвование (§8.2) |
| `UC_ExecExchangeCoords.class.php` | Обмен координатами с игроком — [mechanics-world-transfer.md §3](mechanics-world-transfer.md) |
| `UC_ExecGetXMLData.class.php` | XML-виджет баланса (§10) |
| `UC_ExecGrantBuilding.class.php` | Выдача здания `id_building=65` (§7, §13) |
| `UC_ExecLogin.class.php` | Логин/логаут кабинета, "отпечаток" сессии (§4) |
| `UC_ExecLoginAndMoneyActionsLog.class.php` | Платная отправка статистики на email (§7) |
| `UC_ExecMergeAccount.class.php` | Слияние аккаунтов (§11) |
| `UC_ExecMoneyExchange.class.php` | Обмен GC→SC (§8.1) |
| `UC_ExecMoneyTransfer.class.php` | Переводы мир↔кабинет — [mechanics-world-transfer.md §2.4](mechanics-world-transfer.md) |
| `UC_ExecMultyWorldAccess.class.php` | Доступ ко всем мирам разом (§7) |
| `UC_ExecPaymentServiceIndex.class.php` | **Мёртвый код** — старый хардкод-реестр услуг, вытеснен БД (§6) |
| `UC_ExecReserveComb.class.php` | Резервирование координат (§7) |
| `UC_ExecRestoreCombFromBlackHole.class.php` | Восстановление из "Чёрной дыры" — [mechanics-world-transfer.md §1.9](mechanics-world-transfer.md) |
| `UC_ExecTrainSpeedup.class.php` | Ускорение тренировки войск (§7) |
| `UC_ExecTransferCombToBlackHole.class.php` | Отправка в "Чёрную дыру" — [mechanics-world-transfer.md §1.9](mechanics-world-transfer.md) |
| `UC_ExecUserCabinetIndex.class.php` | Главная страница кабинета (§2.1, §3) |
| `UC_ExecUserProfile.class.php` | Профиль (пароль/язык/пояс/аватар) — недостижим, §9 |
| `UC_ExecWorldAbility.class.php` | Открытие регистрации в новом мире (§7) |
| `UC_FreeToPay.class.php` | FreeToPay — [billing-and-monetization.md](billing-and-monetization.md) |
| `UC_GlobalAllianceMoneyOperations.class.php` | Деньги для операций глобального альянса — [mechanics-alliances-diplomacy.md §7](mechanics-alliances-diplomacy.md) |
| `UC_LocalizationStat.class.php` | Мини-кеш локализации для истории операций (§10) |
| `UC_LogicGetPageControllerForUser.class.php` | Определение роли в глобальном альянсе (§12) |
| `UC_LoginAndMoneyActionsLog.class.php` | Бесплатная версия страницы статистики визитов (§7) |
| `UC_OutUserCabinet::Out()` | Универсальный рендерер: `action` → `UC_Page<Action>` → `echo` |
| `UC_PageBase.class.php` | Базовый класс всех `UC_Page*`: Smarty + язык + кодировка (§3) |
| `UC_PageChangeCoords.class.php` | Шаблон `change_coords.tpl` (отсутствует в архиве, §13) |
| `UC_PageDonateConfirm.class.php` | Подтверждение пожертвования |
| `UC_PageError.class.php` | Общая страница ошибки |
| `UC_PageExchangeConfirm.class.php` | Подтверждение обмена GC→SC |
| `UC_PageGlobalAllianceControlPanel.class.php` | Шаблон панели лидера альянса (§13) |
| `UC_PageGlobalAllianceIndex.class.php` | Шаблон общей страницы альянса (§13) |
| `UC_PageGlobalAllianceMemberPanel.class.php` | Шаблон панели участника альянса (§13) |
| `UC_PageIndex.class.php` | Главная страница кабинета |
| `UC_PageLoginAndMoneyActionsLog.class.php` | Шаблон статистики визитов (отсутствует в архиве, §13) |
| `UC_PageLoginEnter.class.php` | Форма логина |
| `UC_PagePaymantServices.class.php` | Шаблон списка/форм платных услуг (заметна опечатка "Paymant" в имени класса — так и осталась) |
| `UC_PageStat.class.php` | Шаблон истории операций |
| `UC_PageTransferToInstanceConfirm.class.php` | Подтверждение перевода деньги→мир |
| `UC_PageTransferToUserConfirm.class.php` | Подтверждение вывода мир→кабинет |
| `UC_PageUserProfile.class.php` | Шаблон профиля (отсутствует в архиве, §13) |
| `UC_PaymentGC.class.php` | "GC"-заказ, похоже на неиспользуемый код — [billing-and-monetization.md](billing-and-monetization.md) |
| `UC_PaymentServiceBase.class.php` | Общий базовый класс всех платных услуг (§6) |
| `UC_PaymentServiceMoneyOperation.class.php` | "Касса" платных услуг поверх денежного движка `MU_*` (§6) |
| `UC_SponsorPay.class.php` | SponsorPay — [billing-and-monetization.md](billing-and-monetization.md) |
| `UC_StatBase.class.php` | Базовый класс истории операций (§10) |
| `UC_StatBillingPayment.class.php` | Строка истории: пополнение через биллинг (§10) |
| `UC_StatEnterMoney.class.php` | Строка истории: пополнение (§10) |
| `UC_StatExchange.class.php` | Строка истории: обмен GC→SC (§10) |
| `UC_StatNull.class.php` | Строка истории: заглушка (§10) |
| `UC_StatPVPTransfer.class.php` | Строка истории: перевод игроку (§10) |
| `UC_StatReciveFromWorld.class.php` | Строка истории: приём из мира (§10) |
| `UC_StatReserveMoney.class.php` | Строка истории: резервирование денег (§10) |
| `UC_StatSendToWorld.class.php` | Строка истории: отправка в мир (§10) |
| `UC_StatServicePayment.class.php` | Строка истории: оплата платной услуги (§10) |
| `UC_StatTrade.class.php` | Строка истории: биржевая сделка (§10) |

## 15. Открытые вопросы

- **Потребитель `UC_ExecGetXMLData` (`get_xml`) не найден.** Формат ответа
  выглядит как данные для встраиваемого мини-виджета баланса, но сам виджет
  (Flash/JS, использующий этот XML) не обнаружен ни в `old-sphere/`, ни в
  ранее прочитанном декомпилированном Flash-клиенте — предположение, не
  факт (§10).
- **Механизм конвертации курса при `EXCHANGE_CREDITS`** не прослежен до
  конца в `UC_ExecMoneyExchange` — видно списание GC, но не явное начисление
  SC по курсу `GC_COURSE` в этом файле; вероятно, обрабатывается внутри
  денежного движка `MU_*` при интерпретации типа операции, не
  переразобрано построчно (§8.1).
- **Отсутствие 6 Smarty-шаблонов в архиве** (§13, №7) — не установлено,
  баг ли это оригинала, часть заведомо недоделанной функциональности
  (правдоподобно для трёх шаблонов глобального альянса, гейт
  `GLOBAL_ALLIANCE_ACTIVE`), или пробел самого архивирования (шаблоны не
  были на диске на момент снятия слепка) — при доступе к живому хостингу
  стоит перепроверить их наличие.
- **`assert()` как единственный механизм проверки "отпечатка" сессии
  кабинета** (`UC_ExecLogin::checkStartSessionParams`, §4.1) — реальная
  строгость зависит от настройки `zend.assertions`/`assert.active` в
  PHP-конфигурации прода, которая не входит в архив; не установлено,
  реально ли эта проверка блокирует запрос при несовпадении, или на проде
  `assert()` мог быть выключен (в PHP 5 `assert.active=0` не бросает
  исключение и не завершает выполнение).
- **Реальный статус трёх "скрытых" платных услуг в БД**
  (`PS_GRANT_BUILDING`, `PS_DELETE_UNIQUE_BUILDING`, `PS_BY_BONUS_RESOURCE`,
  §13 №3) не установлен — включены ли они вообще в `ds_payment_services`/
  `ds_money_action_types.is_active` для обычных игроков, или доступны
  только тем самым двум IP/uid=98 через какой-то внешний, не найденный в
  коде механизм (например, ручное включение в БД администратором перед
  использованием) — по коду однозначно не определяется.
