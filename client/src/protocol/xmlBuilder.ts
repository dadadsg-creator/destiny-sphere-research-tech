/**
 * Сборка исходящих XML-команд протокола — доменные `build*Command()`
 * функции (Фаза 3, по всем файлам `*Data.ts`) возвращают только объект
 * атрибутов, без имени тега (тег знает вызывающий код, см.
 * CLIENT_PORTING_PLAN.md, «Сшивание» — 2026-09-08). `tag()` — общий
 * случай (плоские атрибуты, self-closing), покрывает подавляющее
 * большинство команд. Для редких вложенных случаев (`sendmessage`,
 * `buy_artefact_in_shop`) — свои функции рядом.
 */

import type { SellCommand } from '../trade/tradeData';
import type { TransferCommand } from '../trade/transferData';
import type { ArcheologyTeamCommand } from '../world/archeologyData';
import type { GetPPeaceCommand } from '../alliance/privatePeaceData';
import type { SendColonyModuleCommand } from '../world/colonizationData';
import type { CreateAllianceCommand, JoinAllianceCommand } from '../alliance/allianceData';
import type { AlliancePeaceCommand } from '../alliance/alliancePeaceData';
import { encodeMailMessage } from '../mail/mailData';

export function tag(name: string, attrs: Record<string, string | number | boolean | undefined>): string {
  let out = `<${name}`;
  for (const [key, value] of Object.entries(attrs)) {
    if (value === undefined) continue;
    out += ` ${key}="${escapeXmlAttr(String(value))}"`;
  }
  out += '/>';
  return out;
}

function escapeXmlAttr(value: string): string {
  return value.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/**
 * reqvestdata.as:467-505 send_mail() — единственная команда с вложенными
 * детьми в этом первом проходе. `subject`/`messagetext` кодируются
 * hex-триплетами через `game.ENCRYPTTEXT` (зеркало `decodeMailMessage`,
 * см. mailData.ts) ПЕРЕД оборачиванием в CDATA — оригинал делает и то, и
 * то одновременно, не одно вместо другого. `to` — обычный текст без
 * кодирования, как и в оригинале.
 */
export function sendMessageXml(idMessagescope: number, to: string, subject: string, messagetext: string): string {
  return (
    `<sendmessage id_messagescope="${escapeXmlAttr(String(idMessagescope))}">` +
    `<to><![CDATA[${to}]]></to>` +
    `<subject><![CDATA[${encodeMailMessage(subject)}]]></subject>` +
    `<messagetext><![CDATA[${encodeMailMessage(messagetext)}]]></messagetext>` +
    `</sendmessage>`
  );
}

/** requestDataCabinet.as:61-88 buyArtInShop() — не обёрнут в <cabinetRequest>, отдельный корневой тег. */
export function buyArtefactInShopXml(items: { id: string; count: number; life: string; concurency: string }[]): string {
  const inner = items
    .map((i) => tag('artefact', { count: i.count, type: i.concurency, id: i.id, life: i.life }))
    .join('');
  return `<buy_artefact_in_shop>${inner}</buy_artefact_in_shop>`;
}

/** requestDataCabinet.as — все прочие команды кабинета оборачиваются в <cabinetRequest command="...">. */
export function cabinetRequestXml(command: string, attrs: Record<string, string | number | undefined> = {}): string {
  return tag('cabinetRequest', { command, ...attrs });
}

/**
 * trade/tradeData.ts:262-286 — поля SellCommand в camelCase/строчными без
 * подчёркиваний, а подтверждённые атрибуты XML — `id_fromcategory` и т.п.,
 * поэтому не годится обычный `tag(name, cmd)` (спред полей 1-в-1).
 */
export function sellXml(cmd: SellCommand): string {
  return tag('sell', {
    id_tradescope: cmd.idTradescope,
    id_fromcategory: cmd.idFromcategory,
    id_fromarticle: cmd.idFromarticle,
    id_fromrealarticle: cmd.idFromrealarticle,
    amount: cmd.amount,
    id_tocategory: cmd.idTocategory,
    id_toarticle: cmd.idToarticle,
    price: cmd.price,
    tradetime: cmd.tradetime,
    isauction: cmd.isauction,
    ispersonal: cmd.ispersonal,
    nick: cmd.nick,
    pass: cmd.pass,
    death: cmd.death,
    anonymous: cmd.anonymous,
  });
}

/**
 * trade/transferData.ts:81-92 — докстринг там буквально подтверждает
 * атрибут `combto`, но поле интерфейса называется `comto` (без "b",
 * похоже на опечатку исходного кода Фазы 3) — сериализуем в
 * подтверждённое имя атрибута, а не в имя поля.
 */
export function transferResourceXml(cmd: TransferCommand): string {
  return tag('transferresource', {
    combto: cmd.comto,
    id_article: cmd.idArticle,
    id_category: cmd.idCategory,
    amount: cmd.amount,
    death: cmd.death,
  });
}

/**
 * world/archeologyData.ts:170-181 — поле `countGroup` в camelCase, а
 * подтверждённый атрибут XML — `countgroup` (строчными, без разделителя).
 */
export function archeologyTeamXml(cmd: ArcheologyTeamCommand): string {
  return tag('archeologyteam', {
    dest: cmd.dest,
    count: cmd.count,
    time: cmd.time,
    size: cmd.size,
    countgroup: cmd.countGroup,
  });
}

/**
 * alliance/privatePeaceData.ts:89-97 — подтверждено `reqvestdata.as:
 * 134-149` (`getPPeace`): `<getprivatepeace nick="" definition="" reason=""/>`,
 * поля команды названы иначе (`targetName`/`uid`), чем атрибуты XML.
 */
export function getPPeaceXml(cmd: GetPPeaceCommand): string {
  return tag('getprivatepeace', { nick: cmd.targetName, definition: cmd.uid, reason: cmd.reason });
}

/**
 * world/colonizationData.ts:72-80 — подтверждено `reqvestdata.as:
 * 565-568` (`sendColonyModule`): `<colonize id_module="" destination="">
 * <colonyname><![CDATA[name]]></colonyname></colonize>` — единственная
 * ещё, помимо `sendmessage`, команда с вложенным CDATA-child, без `c`.
 */
export function colonizeXml(cmd: SendColonyModuleCommand): string {
  return (
    `<colonize id_module="${escapeXmlAttr(cmd.idModule)}" destination="${escapeXmlAttr(cmd.destination)}">` +
    `<colonyname><![CDATA[${cmd.colonyName}]]></colonyname></colonize>`
  );
}

/** alliance/allianceData.ts:90-97 — reqvestdata.as:587-590 (createAlliance): три CDATA-child. */
export function createAllianceXml(cmd: CreateAllianceCommand): string {
  return (
    `<createalliance><name><![CDATA[${cmd.name}]]></name><tag><![CDATA[${cmd.tag}]]></tag>` +
    `<joinpassword><![CDATA[${cmd.joinPassword}]]></joinpassword></createalliance>`
  );
}

/**
 * alliance/allianceData.ts:100-107 — reqvestdata.as:603-606 (connectAlliance):
 * `id_alliance` — атрибут корня, пароль — вложенный CDATA-child. В самом
 * оригинале лишний `>` перед `</joinalliance>` (опечатка конкатенации
 * строк) — не воспроизводим, он ни на что не влияет (одиночный `>` в
 * текстовом узле — валидный XML, не баг протокола).
 */
export function joinAllianceXml(cmd: JoinAllianceCommand): string {
  return (
    `<joinalliance id_alliance="${escapeXmlAttr(cmd.idAlliance)}">` +
    `<joinpassword><![CDATA[${cmd.joinPassword}]]></joinpassword></joinalliance>`
  );
}

/** reqvestdata.as:609-612 (exitAlliance) — <leavealliance id_alliance=""/>. */
export function leaveAllianceXml(idAlliance: string): string {
  return tag('leavealliance', { id_alliance: idAlliance });
}

/**
 * reqvestdata.as:748-757 (create_army) — <createarmy><armyname>
 * <![CDATA[name]]></armyname><unit id="" count=""/>...</createarmy>,
 * переменное число `<unit>` — не годится плоский `tag()`, без `c`.
 */
export function createArmyXml(name: string, units: { id: string; count: number }[]): string {
  const unitsXml = units.map((u) => tag('unit', { id: u.id, count: u.count })).join('');
  return `<createarmy><armyname><![CDATA[${name}]]></armyname>${unitsXml}</createarmy>`;
}

/**
 * army_overview_class.as (`allowTransfer`/`trasfer_unit`, не
 * портировано при первом проходе Фазы 3.1) + `UA_TransferUnit.
 * class.php:33-58` — по одному `<unit>` на вызов (протокол
 * технически допускает несколько сразу, но UI этого порта — один
 * перевод за клик, как и остальные действия армии).
 */
export function transferUnitXml(idUnit: string, count: number, idArmyFrom: string, idArmyTo: string): string {
  return `<transferunit>${tag('unit', { id: idUnit, count, id_army_from: idArmyFrom, id_army_to: idArmyTo })}</transferunit>`;
}

/**
 * spying/spyingData.ts + spying_class.as:196-274 (send_zond,
 * reqvestdata.as:987-999) — ветвление подтверждено буквально:
 * чекбокс "один раунд боя" в оригинале существует, только когда действие
 * требует координат цели (`allow_coord`/`needsCoord`), поэтому именно
 * тогда отправляются `ring`/`compl`/`sota`/`oneround`/`sd` (скорость), а
 * иначе — только `time`; оба набора никогда не шлются одновременно.
 */
export function sendZondXml(cmd: {
  id: string;
  count: number;
  action: string;
  needsCoord: boolean;
  ring: string;
  complex: number;
  sota: number;
  oneRound: boolean;
  speed: number;
  time: number;
}): string {
  const attrs: Record<string, string | number> = { id: cmd.id, count: cmd.count, action: cmd.action };
  if (cmd.needsCoord) {
    attrs.ring = cmd.ring;
    attrs.compl = cmd.complex;
    attrs.sota = cmd.sota;
    attrs.oneround = cmd.oneRound ? 1 : 0;
    attrs.sd = cmd.speed;
  } else {
    attrs.time = cmd.time;
  }
  return tag('sendzond', attrs);
}

/** reqvestdata.as:592-597 (changeAlliancePass) — <changepassword><![CDATA[текст]]></changepassword>, без атрибутов. */
export function changeAlliancePasswordXml(newPassword: string): string {
  return `<changepassword><![CDATA[${newPassword}]]></changepassword>`;
}

/**
 * alliancePeaceData.ts / `RequestDataAlli.as:13-86` — единая команда
 * `<getAlliancePeace status="" alliId="" [time="" my_k="" ... he_e=""]/>`.
 * Поле `time`/`my`/`he` присутствуют только у статуса `setPeace`
 * (`sendOfferPeace`), у остальных 7 статусов — только `alliId`+`status`.
 */
export function getAlliancePeaceXml(cmd: AlliancePeaceCommand): string {
  if (cmd.status === 'setPeace' && cmd.my && cmd.he) {
    return tag('getAlliancePeace', {
      status: cmd.status,
      alliId: cmd.alliId,
      time: cmd.time,
      my_k: cmd.my.k,
      my_n: cmd.my.n,
      my_f: cmd.my.f,
      my_p: cmd.my.p,
      my_s: cmd.my.s,
      my_e: cmd.my.e,
      he_k: cmd.he.k,
      he_n: cmd.he.n,
      he_f: cmd.he.f,
      he_p: cmd.he.p,
      he_s: cmd.he.s,
      he_e: cmd.he.e,
    });
  }
  return tag('getAlliancePeace', { alliId: cmd.alliId, status: cmd.status });
}

/** `RequestDataAlli.as:13-16` (dataAboutPeace) — <getInfoAboutPeace allianceId=""/>, ответ приходит маркой `alliancePeaceData`. */
export function getInfoAboutPeaceXml(allianceId: string): string {
  return tag('getInfoAboutPeace', { allianceId });
}
