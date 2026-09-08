/**
 * Сборка исходящих XML-команд протокола — доменные `build*Command()`
 * функции (Фаза 3, по всем файлам `*Data.ts`) возвращают только объект
 * атрибутов, без имени тега (тег знает вызывающий код, см.
 * CLIENT_PORTING_PLAN.md, «Сшивание» — 2026-09-08). `tag()` — общий
 * случай (плоские атрибуты, self-closing), покрывает подавляющее
 * большинство команд. Для редких вложенных случаев (`sendmessage`,
 * `buy_artefact_in_shop`) — свои функции рядом.
 */

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

/** reqvestdata.as:467-505 send_mail() — единственная команда с вложенными детьми в этом первом проходе. */
export function sendMessageXml(idMessagescope: number, to: string, subject: string, messagetext: string): string {
  return (
    `<sendmessage id_messagescope="${escapeXmlAttr(String(idMessagescope))}">` +
    `<to><![CDATA[${to}]]></to>` +
    `<subject><![CDATA[${subject}]]></subject>` +
    `<messagetext><![CDATA[${messagetext}]]></messagetext>` +
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
