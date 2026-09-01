# Монетизация и биллинг

Документ описывает, как в коде `old-sphere` устроены платежи и монетизация:
премиум-аккаунты, SMS-платежи, внешние платёжные/офферволл-интеграции
(FreeToPay, SponsorPay), партнёрская программа/лидогенерация, и то, как
игровой код стыкуется с внешним биллинговым сайтом
[billing.destinysphere.ru](https://billing.destinysphere.ru) (описан в
[external-sites.md](external-sites.md)). Детальный разбор `ds/GateKey_Master/`
как инфраструктуры мульти-мирового обмена ключами — предмет отдельного
документа; здесь эта подсистема упоминается только там, где она реально
используется для биллинговых сценариев.

Все пути ниже — относительно `D:\Projects\Destiny Sphere\old-sphere`, если не
указано иное.

## Обзор найденных способов монетизации

| Способ | Статус в коде | Ключевые файлы |
|---|---|---|
| Премиум-аккаунт (подписка) | Ядро экономики, к нему сходятся почти все остальные способы | `ds/classes/PU/PU_ExecChangePremiumDays.class.php`, `ds/classes/DS/DS_Premium.class.php` |
| Внутренняя валюта GC/SC (Gold Credits / Silver Credits) | Общий "движок денег", используется всеми способами оплаты | `ds/classes/UC/UC_PaymentServiceMoneyOperation.class.php` |
| SMS-платежи (короткие номера) | Живая интеграция с несколькими SMS-агрегаторами | `ds/sms.php`, `ds/classes/SMS/*`, `ds/gate/dsmessage.php`, `ds/gate/classes/SMS_TranslateParamsFrom*.class.php`, `ds/gate/smspay.php` |
| FreeToPay (внешний платёжный шлюз) | Живая интеграция, вход + постбэк | `ds/freetopay.php`, `ds/gate/freetopay.php`, `ds/gate/freetopay_fail.php`, `ds/classes/UC/UC_FreeToPay.class.php` |
| SponsorPay (офферволл) | Постбэк-обработчик есть, точка входа для игрока похоже отключена (см. ниже) | `ds/sponsorpay.php`, `ds/gate/sponsorpay.php`, `ds/classes/UC/UC_SponsorPay.class.php` |
| Партнёрка / лидогенерация (реферальные сайты) | Живая, включая обмен статистикой с billing.destinysphere.ru | `ds/classes/DS/DS_Partner.class.php`, `ds/classes/MP/MP_GameLeads.class.php`, `ds/classes/MP/MP_PartnerData.class.php`, `ds/gate/sitepartners.php`, `ds/gate/gameleads.php` |
| Прямой биллинговый шлюз (входящий, от billing.destinysphere.ru к игре) | Живой, обрабатывает начисление денег/премиума/списание | `ds/gate/billing_receiver.php`, `ds/classes/BG/*` |
| SSO-переход игрок → биллинг (исходящий) | Живой | `ds/billing.php`, `ds/classes/UserAction.class.php:152` |
| "GC"-заказ (возможно для прибалтийского рынка) | Похоже на неиспользуемый/устаревший код (см. раздел ниже) | `ds/classes/UC/UC_PaymentGC.class.php` |

Способы оплаты, которые видны на самом billing.destinysphere.ru (банковские
переводы, WebMoney, Яндекс.Касса, Qiwi, Moneymail, PayPal, Flypay, терминалы,
банковские карты) в коде `old-sphere` **не реализованы** — они целиком на
стороне отдельного биллингового сайта. Игровой код видит только результат:
либо входящий вызов от биллинга (раздел про `billing_receiver.php`), либо
SSO-переход туда.

## Таблица версий (BILLING_PARTNER_ID / SPONSORPAY_ID / FREETOPAY_ID)

Из `ds/config/settings.php` — три ветки конфигурации, выбираемые по
`posix_getuid()` веб-сервера (см. также `external-sites.md`):

| Версия (`VERSION_NAME`) | `BILLING_PARTNER_ID` | `SPONSORPAY_ID` | `FREETOPAY_ID` | Строки в settings.php |
|---|---|---|---|---|
| `RU_UNLIM` (Unlimited, ds-game.ru) | **6** | 283 | 26 | 103–105 |
| `LIMITED` (Pulsar, ds-game.su) | **9** | 284 | 28 | 140–142 |
| `RU_CLASSIC` (Classic, destinysphere.ru) | **4** | 284 | 28 | 317–319 |

Обратите внимание: Unlimited и Pulsar/Classic используют **разные**
`SPONSORPAY_ID`/`FREETOPAY_ID` (283/26 vs 284/28), т.е. в SponsorPay/FreeToPay
Pulsar и Classic зарегистрированы как один и тот же "рекламный" сайт/приложение,
а Unlimited — как отдельный. Комментарий в коде (`settings.php:103` и др.)
гласит "4 - классик, 6 - анлим" — про Pulsar (9) комментарий не обновлён,
хотя константа задана верно.

В `ds/classes/DS/DS_Partner.class.php:76` встречается закомментированный блок
с условием `if (constant('BILLING_PARTNER_ID')==9) $maxn = 2; else $maxn = 6;`
— судя по всему, раньше список партнёров фильтровался по-разному для Pulsar,
сейчас (после строки 81, `$maxn = 3;`) это отключено и одинаково для всех
версий.

## 1. Премиум-аккаунты (ядро)

Центральная точка, куда сходятся все способы "дать премиум-дни" —
`PU_ExecChangePremiumDays::Exec()` (`ds/classes/PU/PU_ExecChangePremiumDays.class.php`).
Метод принимает пользователя, дельту дней, тип источника (`'BILLING'`, `'SMS'`,
логин админа и т.п.) и комментарий, и:

- проверяет, не пытаются ли второй раз выдать pos "триальные" дни
  (`type == 'BILLING' && $comments == 'trial_days'`, строка 38);
- заводит запись премиум-регистрации, если её ещё нет (`DS_Premium::insertFakePremiumRegistration`);
- продлевает/переустанавливает срок действия (`DS_Premium::changePremiumEndDate`);
- переводит статус пользователя в "profi" (`DS_Premium::changeUserStatusToProfi`);
- логирует изменение в `ds_premium_change_log` (`DS_Premium::logPremiumChange`).

Все методы работы с состоянием премиума живут в
`ds/classes/DS/DS_Premium.class.php`. `PU_ExecChangePremiumDays::Exec()`
вызывается минимум из:

- `ds/classes/BG/BG_Premium2.class.php` — входящий вызов от биллингового шлюза (см. раздел 6);
- `ds/gate/smspay.php` (класс `SMSPay`) — при подтверждённой SMS-оплате премиума;
- `Admin/controls/PremiumAccaunts/AddPremiumToWorld/index.php:452` — массовая выдача премиума админом;
- `Admin/controls/PremiumAccaunts/Support/addPremium.php:106` — точечная выдача/списание премиум-дней одному игроку.

### Розничная цена премиума (данные сайта, не кода)

В `old-sphere` цены не хранятся (тарификация — на стороне billing.destinysphere.ru, см.
преамбулу документа). Официальная справочная страница
`https://destinysphere.ru/?p=premium_info_page` называет игроку конкретную розничную цену:
**240 рублей в месяц, либо 650 рублей при единовременной оплате 3 месяцев** (то есть
скидка ~10% к помесячной цене при квартальной предоплате), и явно предупреждает: «По
истечении премиум-абонемента следует автоматическое преобразование в стандартную версию» —
это согласуется с §1.3 `mechanics-premium-gameplay-effects.md` (в коде действительно нет
отдельной логики "деградации" аккаунта: `isProfy()`/`profi_game` просто становятся `false`
по истечении `enddate`, никакого автопродления с списанием денег не найдено).

### Внутренняя валюта GC/SC

Все денежные операции (пополнение, списание, обмен) в итоге идут через
`UC_PaymentServiceMoneyOperation` (`ds/classes/UC/UC_PaymentServiceMoneyOperation.class.php`),
поверх общего "денежного движка" (`MU_EntityOperation`, `DE_MoneyActionType`,
таблицы `ds_money_action_types` / `ds_money_payments_price`). Валюты две:

- **GC** — Gold Credits (судя по SMS-тарифам это премиум/твёрдая валюта,
  дороже: 1–3 условные "золотые кредита" стоят как несколько тысяч "серебряных");
- **SC** — Silver Credits (мягкая/игровая валюта, начисляется, например,
  пачками по 500–3000 за SMS).

`UC_PaymentServiceMoneyOperation::EnterMoney()` (строка 226) — точка входа,
которой пользуются и FreeToPay (`ds/gate/freetopay.php:22`), и SponsorPay
(`ds/gate/sponsorpay.php:21`), и `SMS_BuyGameCredits` (`ds/classes/SMS/SMS_BuyGameCredits.class.php:46`),
и `BG_MoneyEnter` (`ds/classes/BG/BG_MoneyEnter.class.php:157`) — то есть это
общая "касса" для всех внешних источников денег.

### Административный модуль (`Admin/controls/PremiumAccaunts/`)

Что видит и делает администратор:

- **`index.php` / `Support/index.php`** — основной список игроков (класс
  `EditProfiData` в `Support/index.php`) с фильтрами и поиском, завязанный на
  таблицу `ds_payment`; из строки списка есть ссылки на:
  - `Support/showsmspays.php` — история SMS-платежей игрока (таблица
    `ds_smspaylog`: дата, результат, номер телефона, текст SMS);
  - `Support/addPremium.php` — точечно добавить/отнять премиум-дни игроку с
    обязательным комментарием (комментарий обязателен даже на клиенте, см.
    JS-проверку `formSubmit()`); тут же (`?log=1`) — журнал изменений;
  - `Support/premiumChangeLog.php` — таблица `ds_premium_change_log` (тип,
    дата, дельта дней, старая/новая дата окончания, комментарий) для
    конкретного пользователя;
  - `Support/showInvoice.php`, `showsingleinvoice.php`, `invoice.php.lang` —
    показ счетов/инвойсов;
  - `Support/IT.php` — большой (32 КБ) вспомогательный модуль поддержки,
    не разбирался детально.
- **`AddPremiumToWorld/index.php`** — массовая выдача премиум-дней: выбор
  миров, диапазон даты регистрации и/или фильтр "только уже премиумные",
  затем список игроков и ввод количества дней + обязательный комментарий.
  В итоге просто вызывает `PU_ExecChangePremiumDays::Exec()` в цикле.
- **`CreatePayment/index.php`** — ручное создание записи об оплате
  (`INSERT INTO ds_payment ...`) с выбором пользователя, тарифа
  (`ds_paymentmodel`/`ds_paymentduration`) и признаком `verified`. По сути
  инструмент "задним числом отметить, что человек заплатил" — не проводит
  реальные деньги, только пишет в БД.
- **`MoneyAccounts/index.php`** — обёртка над `Admin/controls/PremiumAccaunts/classes/Core/MoneyAccount.class.php`
  (файл не разбирался подробно; судя по названию — просмотр/управление
  денежными счетами).
- **`PremiumStat/index.php`** — простая статистика: количество активных
  премиум-аккаунтов (`ds_userprofidata.profi = true AND enddate > now()`) по
  каждому игровому миру.
- **`Report/`** — построение отчётов об успешных заказах
  (`createSuccessOrderReport.php`).
- **`ViewLogs/index.php`** — просмотр логов (не разбирался подробно).
- **`gen/17gen.php`** — генератор кодов (похоже, активационных/подарочных;
  не разбирался подробно).

## 2. SMS-платежи

SMS-платежи реализованы в двух слоях:

### 2.1. Витрина для игрока (`ds/sms.php` → `SMS_Controller`)

`ds/sms.php` создаёт `SMS_Controller` (`ds/classes/SMS/SMS_Controller.class.php`),
который по стране игрока (`SMS_DataDase::GetUserCountry`) подбирает:

- номер короткого кода и стоимость (метод `GetNumberAndCoastBySmsType`,
  захардкоженные таблицы номеров/цен на страну — Россия, Украина, Молдова,
  Германия, Эстония, Латвия, Литва, Швейцария, Румыния и т.д.);
- текст SMS, который должен отправить игрок, с префиксом сервиса
  (`PREMIUM`, `ARMY`, `AINF`, `ARCH`, `ART`, `CREDIT`, `CREDIT500`,
  `CREDIT1/2/3` — конкретные игровые "услуги", покупаемые по SMS: премиум,
  просмотр армии/инфо, ускорение археологии, оптимизация артефактов,
  покупка серебряных/золотых кредитов).

Реальная покупка/начисление по этому пути не происходит на этой странице —
она только показывает номер и текст; см. серверную обработку ниже.

### 2.2. Приём входящих SMS от агрегаторов (`ds/gate/dsmessage.php`)

`ds/gate/dsmessage.php` — вебхук, куда агрегаторы шлют данные о полученном
SMS. Класс `BaseDSMessage` (`ds/gate/classes/baseDSMessage.class.php`)
определяет агрегатора **по IP отправителя** (массив `$translateParams`,
строки 7–41) и подключает соответствующий транслятор:

- `SMS_TranslateParamsFromIricom.class.php` — агрегатор Iricom;
- `SMS_TranslateParamsFromPlasticMedia.class.php` — Plastic Media;
- `SMS_TranslateParamsFromRamblerPlus.class.php` — Rambler+ (несколько IP
  диапазонов Rambler, включая тестовый);
- `SMS_TranslateParamsFromLithuania` — упоминается в таблице IP (строка 20),
  но файла `SMS_TranslateParamsFromLithuania.class.php` в `ds/gate/classes/`
  не найдено — похоже, это устаревшая/удалённая интеграция.

Каждый транслятор приводит специфичные для агрегатора поля (телефон,
короткий номер, id транзакции, текст) к общему формату и (для некоторых)
сам проверяет цифровую подпись присланных данных — см. раздел
"Безопасность" про `SMS_TranslateParamsFromRamblerPlus.class.php:97`.

Далее `BaseDSMessage` разбирает текст SMS (`parseBody`, `wordProcess`) и, в
частности, для покупки премиума в `ds/gate/dsmessage.php:1595` создаёт
`new SMSPay($userID, $this->phone, $this->smsText, $countOfDays, $this->testMode)`
(`ds/gate/smspay.php`, класс `SMSPay`) — тот вызывает
`PU_ExecChangePremiumDays::Exec($user, $countOfDays, 'SMS', $comments, true)`.

Отдельно `ds/classes/SMS/SMS_BuyGameCredits.class.php` — начисление именно
кредитов (SC) по имени пользователя/сумме (используется, судя по коду,
для другого класса SMS-сервисов, например `CREDIT`/`CREDIT500`), через
`UC_PaymentServiceMoneyOperation`-совместимый вызов `MU_ControlExecOperations::ExecOperation`.

Есть также вспомогательные обработчики других SMS-сервисов:
`ds/classes/SMS/SMS_ArhiologyController.class.php` (ускорение
"археологических" команд) и `SMS_ArtefactController.class.php`
(оптимизация артефактов на постройках) — они не про деньги напрямую, но
запускаются тем же SMS-путём как "премиальная" механика.

### 2.3. Отчёт о SMS-платеже обратно в биллинг

`ds/gate/classes/SMSPayBillingLogger.class.php` — после начисления
премиума по SMS отправляет уведомление на
`https://billing.destinysphere.ru/Receiver/smsLogReceiver.php` (строка 10),
используя инфраструктуру `GateKey_Master`: получает одноразовый ключ через
`GK_GateKeyController::GetNewKey('BILLING')` (строка 23) и подписывает запрос
`md5(constant('GATE_KEY_MAGIC').date('Y-d-m'))` (строка 36). Это единственное
явное свидетельство в коде, что **billing.destinysphere.ru зарегистрирован
как "slave" в системе GateKey** (под именем `'BILLING'`) — то есть GateKey
используется не только для мультимировой синхронизации, но и как канал
подтверждения SMS-платежей в биллинг.

## 3. FreeToPay

FreeToPay — внешний платёжный шлюз/агрегатор, используемый для пополнения
серебряных кредитов (SC).

- **Инициирование оплаты** — `ds/freetopay.php`. Заводит транзакцию
  (`UC_FreeToPay::insertTransaction`, `ds/classes/UC/UC_FreeToPay.class.php:9`,
  вставляет строку в `ds_freetopay` с `id_transaction` из последовательности
  `ds_freetopay_id_transaction_seq`), считает подпись
  `md5(FREETOPAY_ID:uid:transid:sex:age:location:FREETOPAY_ApiSecretKeyA)`
  (`ds/freetopay.php:19`) и редиректит игрока на
  `http://freetopay.ru/ifrm.php?...&sig=...` (строка 22).
- **Постбэк об успешной оплате** — `ds/gate/freetopay.php`. Проверяет, что
  транзакция существует и ещё не оплачена (`UC_FreeToPay::checkTransaction`),
  проверяет подпись `UC_FreeToPay::checkSign()`
  (`ds/classes/UC/UC_FreeToPay.class.php:50-57`:
  `md5(transac_id:uid:amount:FREETOPAY_ApiSecretKeyB)`), помечает транзакцию
  оплаченной (`commitTransaction`) и начисляет деньги через
  `UC_PaymentServiceMoneyOperation::EnterMoney($uid, $amount, 'SC', 'FREETOPAY_PAYMENT')`
  (`ds/gate/freetopay.php:22`).
- **Постбэк о неуспешной оплате** — `ds/gate/freetopay_fail.php`, отдельная
  подпись `checkSignfail()` (`UC_FreeToPay.class.php:59-63`,
  без `amount` в строке подписи), помечает транзакцию как "amount = -1".

Обратите внимание: и `insertTransaction`, и оба постбэка (`ds/gate/freetopay.php`,
`ds/gate/freetopay_fail.php`) не требуют аутентификации по IP — единственная
защита это MD5-подпись на секретном ключе (`FREETOPAY_ApiSecretKeyA` для
исходящего запроса на оплату, `FREETOPAY_ApiSecretKeyB` для входящих
постбэков). Про сами значения ключей — см. раздел "Безопасность".

## 4. SponsorPay (офферволл)

SponsorPay в 2010-х был офферволл-агрегатором (игрок выполняет задание —
рекламный оффер, установка приложения и т.п. — получает игровую валюту).

- `ds/sponsorpay.php` — судя по коду, страница-обёртка с iframe на
  `http://iframe.sponsorpay.com/?appid={SPONSORPAY_ID}&uid={uid}` (строка 48).
  **Однако на строке 2 стоит `exit;`** — то есть скрипт завершает выполнение
  до того, как что-либо выведет. Похоже, этот вход в офферволл в текущем
  состоянии кода **отключён** (возможно, намеренно, при отказе от
  SponsorPay, но код-заглушка не удалена).
- `ds/gate/sponsorpay.php` — постбэк-обработчик, судя по всему всё ещё
  рабочий: проверяет, что `_trans_id_` ещё не встречался
  (`UC_SponsorPay::isExistsTransID`), проверяет подпись
  (`UC_SponsorPay::checkSign`, `ds/classes/UC/UC_SponsorPay.class.php:39-43`:
  `sha1($controlworld.uid.amount._trans_id_)` — где `$controlworld` — это на
  самом деле общий секрет, см. "Безопасность"), записывает лог в
  `ds_sponsorpay_log` и начисляет деньги через
  `UC_PaymentServiceMoneyOperation::EnterMoney($id_user, $paym_sum, 'SC', 'SPONSORPAY_PAYMENT')`.
- `ds/classes/UC/UC_SponsorPay.class.php` — вся бизнес-логика (проверка
  дубликатов, вставка лога, проверка подписи).

Отдельный класс `ds/classes/UC/UC_SponsorPay.class.php` не стоит путать с
похожим по названию `ds/classes/UC/UC_FreeToPay.class.php` (комментарий в
шапке `UC_FreeToPay.class.php` ошибочно гласит "Клиент-серверный протокол
для работы с SponsorPay" — судя по всему, скопирован с соседнего класса и не
поправлен).

## 5. Партнёрская программа / лидогенерация

Есть внутренняя "партнёрка" (реферальные/арбитражные сайты, приводящие
игроков за вознаграждение), не путать с офферволлами:

- `ds/classes/DS/DS_Partner.class.php` — CRUD партнёров в таблице
  `ds_partner` (имя, логин, список сайтов, контактное лицо, email, процент
  вознаграждения, пароль) и выборки статистики
  (`getPartnerStat`, `getPartnerRegStat`, `getPartnerRegStatSimple`,
  `getAllTotalPartnerStat`).
- `ds/gate/sitepartners.php` — удалённый API для управления партнёрами
  (`add`/`upd`/`del`), защищённый простой подписью
  `md5($secretWord.$_REQUEST['email'])` (строка 16) — см. "Безопасность"
  про `$secretWord`.
- `ds/classes/MP/MP_GameLeads.class.php` и `ds/classes/MP/MP_PartnerData.class.php` —
  крон-патчи (наследуются от `MP_Patch`), которые:
  1. считают по конкретному партнёру (`$partner_id`, для `MP_GameLeads` он
     захардкожен как `109`, строка 5; для `MP_PartnerData` передаётся в
     конструктор) активацию аккаунта, постройку "пресса" и открытие
     "центральных ворот" — то есть отслеживают выполнение партнёром условий
     оффера (аналог "целевых действий" в CPA-сети);
  2. **звонят на сам billing.destinysphere.ru**, чтобы получить встречные
     данные об оплатах пользователей:
     - `MP_GameLeads::patch()` → `POST https://billing.destinysphere.ru/gate/moneystat.php`
       (`ds/classes/MP/MP_GameLeads.class.php:67`), подпись
       `md5($partner_id.$secretkey)` (строка 60), секрет — см. ниже;
     - `MP_PartnerData::patch()` → `POST https://billing.destinysphere.ru/gate/statforpartners.php`
       (`ds/classes/MP/MP_PartnerData.class.php:71`), тот же паттерн подписи
       (строка 63), плюс передаёт `game_id = constant('BILLING_PARTNER_ID')`
       (строка 67) — то есть биллинг умеет отличать статистику по трём
       версиям игры (4/6/9) внутри одного запроса от лидоген-подсистемы;
  3. пишут собранную статистику в `ds_user_actionstat` /
     `ds_user_partnerstat` и отдают её наружу через
     `ds/gate/gameleads.php` (XML, защищено связкой `pkey`/`skey`, см.
     "Безопасность") — то есть это ещё и API **из игры наружу**, которым,
     предположительно, пользуется сам партнёр/лидоген-агрегатор, а не
     billing.destinysphere.ru.

Таким образом, для партнёрской программы есть **двусторонний обмен**: игра
дергает `billing.destinysphere.ru/gate/{moneystat,statforpartners}.php`,
чтобы узнать, кто из приведённых партнёром пользователей реально заплатил
(значит, факт оплаты хранится на биллинге, а не только в игровой БД), и сама
отдаёт партнёрскую статистику наружу через `ds/gate/gameleads.php`.

## 6. Прямой шлюз биллинга → игры (`ds/gate/billing_receiver.php`)

Это, по всей видимости, основной канал, которым **billing.destinysphere.ru
сообщает игре о зачислении денег/премиума** (ответ на вопрос из задания №6).

`ds/gate/billing_receiver.php`:

1. Проверяет IP отправителя по белому списку (`$gate_allowed_ip`, строка 18:
   `127.0.0.1, 94.242.35.153, 94.242.35.154, 94.242.35.155, 94.242.60.30`) —
   это внутренние адреса дата-центра, а не публичный IP биллинг-сайта, из
   чего можно предположить, что вызов идёт либо с самого биллинг-сервера по
   приватной сети, либо через какой-то внутренний прокси.
2. По полю `act` в `$_POST` диспатчит на один из ~30 классов `BG_*`
   (`ds/classes/BG/*.class.php`), например:
   - `act=premium` → `BG_Premium2::proceed()` — начисление премиум-дней
     (`ds/classes/BG/BG_Premium2.class.php:37`, вызывает
     `PU_ExecChangePremiumDays::Exec($user, $days, 'BILLING', $paymentcode)`);
   - `act=balance` → `BG_Balance::proceed()` — отдаёт биллингу даты
     премиума и сериализованный баланс аккаунта (GC/SC);
   - `act=money_enter` / `act=check_money_enter` → `BG_MoneyEnter` — начисление
     GC/SC (`ds/classes/BG/BG_MoneyEnter.class.php`, тот же
     `MU_ControlExecOperations::ExecOperation`, что и у FreeToPay/SponsorPay);
   - `act=drawout_credits` → `BG_CreditsBillingPayment::Exec()` — **списание**
     кредитов с игрового счёта в пользу биллинга (обратный перевод, видимо
     когда игрок платит "из игры", а не с сайта);
   - `act=user_auth` / `act=user_auth_by_hash` → `BG_UserData` — проверка
     логина/пароля или id+hash (используется для того самого SSO, см. раздел 7);
   - плюс операции на управление мирами, кланами, рефералами, статистикой
     премиум-игроков и т.д. (`BG_ChangeWorld`, `BG_AddWorldAbility`,
     `BG_UserReferals`, `BG_PremiumPlayers`, `BG_PartnerStat`, ...).
3. Каждый `BG_*`-класс наследует `BG_Common` (`ds/classes/BG/BG_Common.class.php`),
   который на входе:
   - проверяет подпись запроса `checkSign()` (строки 131–142) —
     `md5($sorted_params_as_k=v; . $this->secret_password)`, где
     `secret_password` захардкожен в самом классе (строка 22) — см.
     "Безопасность";
   - **пытается** проверить дополнительно ключ через GateKey
     (`checkGateProtection()`, строки 145–170, использует
     `GK_SlaveSearch::GetSlaveByMagic` и `GK_GateKeyController::CheckKey`),
     **но метод начинается с безусловного `return;` на строке 147** — то
     есть эта вторая проверка фактически не выполняется, несмотря на то что
     код под `return` выглядит вполне рабочим. В шапке файла (строки 11–16)
     есть комментарий "Ahtung!!! Gate protection is off -- only for
     testing" — судя по всему, отключение было временным и не вернули
     обратно.

Итог: единственная реальная защита входящего биллингового шлюза сегодня —
статичный пароль в `BG_Common::$secret_password` плюс список разрешённых IP
в `billing_receiver.php`. Подробнее — в разделе "Безопасность".

## 7. SSO-переход игрок → биллинг (исходящий)

Когда игроку нужно попасть на сам billing.destinysphere.ru (пополнить счёт,
посмотреть витрину и т.п.), игра формирует "авторизованную" ссылку без
повторного логина:

- `ds/billing.php:21` — `Location: https://billing.destinysphere.ru/LoginOutside.php?partner_id={BILLING_PARTNER_ID}&user_id={uid}&hash={md5(md5($_SESSION['s_pass']."5GhkdoWM94mI"))}` (+ `mode`/`method`/`quantity` пробрасываются из query),
- то же самое (тот же хардкод соли `5GhkdoWM94mI`) в
  `ds/classes/UserAction.class.php:152` (используется для ссылки "пополнить
  золото" внутри XML-протокола игрового клиента) и в
  `ds/language/R/user_cabinet_payment_services.lang:115` (ссылка "пополнить"
  в кабинете, когда не хватает денег на операцию).

То есть хэш — это `md5(md5(пароль_в_сессии + статичная_соль))`, доступный
только внутри активной сессии игрока с его паролем; сам билинг, видимо,
умеет проверить этот хэш (probably потому что хранит пароли пользователей в
таком же виде — вне `old-sphere` не проверялось). Это же поле `partner_id`
= `BILLING_PARTNER_ID` (4/6/9) — то, как биллинг понимает, из какой версии
игры пришёл переход.

Мелкая находка: `ds/classes/UserAction.class.php:154` для ссылки
"clickonallyicon" хардкодит `id_partner=6` вместо использования
`BILLING_PARTNER_ID` — то есть на Classic/Pulsar эта конкретная ссылка,
похоже, ведёт на витрину с идентификатором партнёра от Unlimited. Не стал
проверять, насколько это критично для реального поведения биллинга.

## 8. "GC"-заказ — вероятно неиспользуемый код

`ds/classes/UC/UC_PaymentGC.class.php` содержит методы `AddOrder`,
`getOrder`, `commitOrder` (создание/подтверждение заказа в таблице
`ds_payment_gc` с "номером счёта" вида `L{последняя_цифра_года}-{номер}`,
комментарий в логе `'litva.log'` намекает на прибалтийский/литовский
контекст) — но ни один вызов `UC_PaymentGC::AddOrder|getOrder|commitOrder`
не найден нигде в `old-sphere` (см. поиск ниже). Реально используемая часть
этого класса — `GetParam`/`SetParam`/`GetCurrentDiscount`, но они работают
как **обобщённое хранилище конфигурации** (таблица `ds_payment_conf`) и в
найденных вызовах используются не для платежей, а для параметров реферальной
программы (`REF_PROC`, `GATE_OPEN_SUM`, `COLONY_OPEN_SUM` в
`Admin/controls/GameSettings/Referral/index.php`). Похоже, класс
исторически задумывался как ещё один платёжный шлюз (для Прибалтики?), но
либо не был доведён до продакшена, либо его "живая" часть (`AddOrder`/
`commitOrder`) была удалена вместе со своей точкой входа. Отмечено как
открытый вопрос.

## Безопасность: обнаруженные захардкоженные секреты

Ниже — все места, где в коде монетизации/биллинга обнаружен захардкоженный
секрет (общий пароль/ключ подписи). Часть уже вычищена перед публикацией на
GitHub (заменена на `REDACTED_API_SECRET`/`REDACTED_DB_PASSWORD`) — для них
указано **место**, но не значение. Часть **осталась в открытом виде** в
репозитории — это отдельная и более срочная находка, эти значения нужно
считать скомпрометированными и ротировать при следующей ревизии.

### Уже вычищены (были реальные секреты, сейчас `REDACTED_*`)

| Файл : строка | Константа/переменная | Использование |
|---|---|---|
| `ds/config/settings.php:394` | `FREETOPAY_ApiSecretKeyA` | Подпись исходящего запроса на оплату в `ds/freetopay.php:19` |
| `ds/config/settings.php:395` | `FREETOPAY_ApiSecretKeyB` | Подпись входящих постбэков FreeToPay, `ds/classes/UC/UC_FreeToPay.class.php:54,61` |
| `ds/classes/MP/MP_GameLeads.class.php:56` | `$secretkey` (локальная переменная) | Подпись запроса `MP_GameLeads` → `https://billing.destinysphere.ru/gate/moneystat.php` (строка 60: `md5($partner_id.$secretkey)`) |
| `ds/classes/MP/MP_PartnerData.class.php:59` | `$secretkey` (локальная переменная) | Подпись запроса `MP_PartnerData` → `https://billing.destinysphere.ru/gate/statforpartners.php` (строка 63) |

Во всех четырёх случаях это **API-секреты для платёжной/биллинговой
интеграции** — ровно то, что просили явно отметить: раньше здесь лежали
реальные ключи для (а) подписи транзакций FreeToPay в обе стороны и (б)
аутентификации исходящих запросов игры к billing.destinysphere.ru за
статистикой оплат партнёрских пользователей.

### Не вычищены — открытый текст прямо в репозитории

Это находки, сделанные в ходе этого анализа и не входившие в список
известных плейсхолдеров — их стоит зафиксировать отдельно, так как формально
они не были "вычищены", но по факту это точно такие же секреты биллинга:

| Файл : строка | Значение (как есть в коде) | Что защищает |
|---|---|---|
| `ds/classes/BG/BG_Common.class.php:22` | `'secretmasafaka'` | **Главный секрет всего входящего биллингового шлюза** `ds/gate/billing_receiver.php` — подписывает/проверяет буквально все действия `BG_*` (начисление денег `money_enter`, выдача премиума `premium`, чтение баланса `balance`, списание `drawout_credits`, авторизация пользователя `user_auth*` и т.д., см. раздел 6). Единственная реальная защита этого шлюза, так как вторая проверка (GateKey) отключена безусловным `return;` на строке 147 того же файла. |
| `ds/gate/sponsorpay.php:5` | `'voranerpsolao2003'` | Секрет для проверки подписи постбэка SponsorPay (используется как `$controlworld` в `UC_SponsorPay::checkSign()`, `ds/classes/UC/UC_SponsorPay.class.php:39-43`) — от него зависит, можно ли подделать зачисление денег через SponsorPay-постбэк. |
| `ds/gate/classes/SMS_TranslateParamsFromRamblerPlus.class.php:97` | `'annita'` | Часть строки для проверки `Digest` во входящих данных от SMS-агрегатора Rambler+ (`md5($TransactionId.':'.$ExtTransactionId.':'.$Amount.':'.$Custom.':'.$Service.':annita')`) — то есть это факто общий секрет с Rambler+ для подтверждения SMS-оплаты. |
| `ds/gate/sitepartners.php:15` | `'partnerka_zaebala'` | Секрет для удалённого API управления партнёрами (`add`/`upd`/`del` в `ds_partner`, включая процент вознаграждения) — `md5($secretWord.$_REQUEST['email'])`. Утечка этого значения потенциально позволяет создавать/менять партнёров и их проценты выплат удалённо. |
| `ds/billing.php:21`, `ds/classes/UserAction.class.php:152`, `ds/language/R/user_cabinet_payment_services.lang:115` | `'5GhkdoWM94mI'` | Статичная "соль", добавляемая к паролю сессии при формировании SSO-хэша для перехода на `billing.destinysphere.ru/LoginOutside.php`. Ниже критичности остальных (нужен ещё и пароль пользователя), но всё равно статичный секрет в открытом виде, задействованный в авторизации на биллинге. |
| `ds/gate/gameleads.php:6` | `'dsgstat'` | Секрет для входящего API статистики лидов (`ds/gate/gameleads.php`, отдаёт агрегированную статистику пользователей по XML) — проверяется как `$pkey` и как часть подписи `md5($pkey.$date_from.$date_to)`. |

### Дополнительное наблюдение о защите биллингового шлюза

Даже если бы `secretmasafaka` был секретным, схема подписи в
`BG_Common::createSign()` (`ds/classes/BG/BG_Common.class.php:172-188`) —
это простой `md5(отсортированные_параметры_как_k=v; + секрет)` без соли,
временной метки или номера сессии внутри самой подписи (то, что защищает от
replay — это, по-видимому, только бизнес-логика конкретных `BG_*`-классов,
не общий механизм). В сочетании с тем, что "вторая линия защиты" (GateKey)
отключена явным `return;`, это стоит считать приоритетной находкой при
следующем аудите безопасности.

## Открытые вопросы

1. **Кто именно дергает `ds/gate/billing_receiver.php`?** IP из белого списка
   (`94.242.35.153-155`, `94.242.60.30`) — внутренние адреса, а не публичный
   адрес billing.destinysphere.ru. Не установлено, вызывает ли биллинг-сайт
   этот шлюз напрямую (из той же сети) или через промежуточный сервис.
2. **Как биллинг проверяет SSO-хэш** (`md5(md5(пароль.'5GhkdoWM94mI'))`) —
   код самого billing.destinysphere.ru не в этом репозитории, поэтому нельзя
   подтвердить, что там пароли хранятся в том же формате double-md5.
3. **Жива ли SponsorPay-интеграция вообще?** Точка входа для игрока
   (`ds/sponsorpay.php`) обрывается на `exit;` (строка 2), при этом
   постбэк-обработчик (`ds/gate/sponsorpay.php`) выглядит рабочим. Возможно,
   ссылка на офферволл сейчас формируется откуда-то ещё (не найдено), либо
   функциональность фактически мертва, а постбэк остался просто
   "на всякий случай".
4. **`ds/classes/UC/UC_PaymentGC.class.php`** — `AddOrder`/`getOrder`/
   `commitOrder` не вызываются нигде в найденном коде. Мёртвый код,
   отсутствующий (не входящий в снапшот) внешний вызывающий модуль, или
   заказы создаются напрямую в БД внешним скриптом, которого нет в этом
   репозитории?
5. **`SMS_TranslateParamsFromLithuania`** упомянут в таблице IP-адресов
   `ds/gate/classes/baseDSMessage.class.php:20-21`, но файла с этим классом
   в `ds/gate/classes/` нет — интеграция удалена не полностью (осталась
   мёртвая ссылка) или файл не попал в этот срез кода?
6. **Формат ответа биллинга на `moneystat.php`/`statforpartners.php`**
   (`MP_GameLeads`/`MP_PartnerData`) не документирован явно — код просто
   парсит CSV-подобную строку через `explode(',')`/`explode(';')`, реальная
   схема данных живёт только на стороне billing.destinysphere.ru.
7. Насколько актуальны короткие номера/тарифы в `SMS_Controller::GetNumberAndCoastBySmsType`
   (правки датированы годами, часть закомментирована) — без доступа к
   продовому конфигу нельзя сказать, какие из них ещё активны.
