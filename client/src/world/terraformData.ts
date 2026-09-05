/**
 * Разбор mark `terraforming` — терраформинг ячеек (Фаза 3.7,
 * `terraform_class.as`, источник: docs/client-economy-social-ui.md §1.9),
 * портировано 1:1 из `xmlparsers.as:1247-1303` (`terraforming_menu`).
 *
 * <terraforming>
 *   <terra type="" time="" price="K100:N50:P0:S20:W5:F30" [s=""] [action=""]/>
 *   ...
 * </terraforming>
 *
 * ВАЖНО: формат `price` здесь ДРУГОЙ, чем везде — токены через `:`, но
 * буква и число внутри токена БЕЗ разделителя ("K100", не "K:100").
 *
 * Кнопка (буквально из доклада):
 *  - `allow=="1"` → «Терраформировать»;
 *  - `allow=="2"` И премиум → «Остановить терраформинг» (иначе —
 *    недоступно, даже если терраформинг идёт — премиум-фича отмены);
 *  - иначе → недоступно (ключ локализации `TERRAFORMING_NOT_AWALIBLE` —
 *    буквальная опечатка в оригинале, "AWALIBLE" вместо "AVAILABLE",
 *    сохранена как есть в комментарии, не в коде — мы просто ссылаемся
 *    на локализационный ключ по смыслу, не жёстко кодируем опечатку).
 */
import { parseXmlFragment } from '../protocol/response';

export interface TerraformOption {
  typeGround: string | null;
  time: string | null;
  kad: number;
  nano: number;
  prod: number;
  stas: number;
  work: number;
  food: number;
  /** Живой атрибут "s" — undefined, если сервер не прислал (в оригинале нет дефолта). */
  allow: string | undefined;
  action: string;
}

export function parseTerraforming(xml: string): TerraformOption[] {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'terraforming') {
    throw new Error(`Ожидался корневой тег <terraforming>, получено: ${root?.nodeName}`);
  }

  const options: TerraformOption[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'terra') continue;

    const priceRaw = el.getAttribute('price') ?? '';
    const price = { kad: 0, nano: 0, prod: 0, stas: 0, work: 0, food: 0 };
    for (const token of priceRaw.split(':').filter(Boolean)) {
      const key = token.slice(0, 1);
      const value = Number(token.slice(1)) || 0;
      if (key === 'K') price.kad = value;
      else if (key === 'N') price.nano = value;
      else if (key === 'P') price.prod = value;
      else if (key === 'S') price.stas = value;
      else if (key === 'W') price.work = value;
      else if (key === 'F') price.food = value;
    }

    options.push({
      typeGround: el.getAttribute('type'),
      time: el.getAttribute('time'),
      ...price,
      allow: el.getAttribute('s') ?? undefined,
      action: el.getAttribute('action') ?? '',
    });
  }
  return options;
}

/** reqvestdata.as:774-786 — <terraform c="" type=""/> / <cancelterraform c="" type=""/>. */
export interface TerraformCommand {
  type: string;
}
export function buildStartTerraformCommand(type: string): TerraformCommand {
  return { type };
}
export function buildCancelTerraformCommand(type: string): TerraformCommand {
  return { type };
}

/** Кнопка "Остановить терраформинг" — премиум-фича, даже если allow=="2". */
export function canCancelTerraform(option: TerraformOption, isPremium: boolean): boolean {
  return option.allow === '2' && isPremium;
}
