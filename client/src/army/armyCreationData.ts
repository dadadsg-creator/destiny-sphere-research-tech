/**
 * Разбор `<armycreation>`/`<armycalculator>` — создание армии и
 * калькулятор «сколько будет стоить» (Фаза 3.1, источник:
 * docs/client-combat-ui.md §2, портировано 1:1 из `xmlparsers.as:1430-1458`
 * и `:1666-1724`).
 *
 * Важное отличие калькулятора от создания армии (см. доклад): у
 * armycalcclass.as НЕТ ни одного вызова reqvestdata.create_army — это
 * чисто просмотровый расчёт на уже присланных сервером данных, реальная
 * команда создания армии (`create_army(units[], name)`) отправляется
 * только с экрана armycreationclass.as.
 */
import { parseXmlFragment } from '../protocol/response';

export interface CreationUnit {
  id: string;
  /** unitname — машинное имя (= UNIT_NAMES из map/unitTypes.ts). */
  libname: string;
  amount: number;
  /** "Вес продиума" одного юнита — 0, если сервер не прислал (xmlparsers.as:1454). */
  prodoweight: number;
}

export interface ArmyCreationData {
  cur: number;
  max: number;
  units: CreationUnit[];
}

/** <armycreation cur="" max=""><unit id="" unitname="" amount="" PW=""/>...</armycreation> */
export function parseArmyCreation(xml: string): ArmyCreationData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'armycreation') {
    throw new Error(`Ожидался корневой тег <armycreation>, получено: ${root?.nodeName}`);
  }

  const units: CreationUnit[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'unit') continue;
    const pw = el.getAttribute('PW');
    units.push({
      id: el.getAttribute('id') ?? '',
      libname: el.getAttribute('unitname') ?? '',
      amount: Number(el.getAttribute('amount')),
      prodoweight: pw !== null ? Number(pw) : 0,
    });
  }

  return {
    cur: Number(root.getAttribute('cur')),
    max: Number(root.getAttribute('max')),
    units,
  };
}

/**
 * Разложение цены юнита по 5 ресурсам + шестой ключ `SC` — НЕ установлено,
 * что означает (не входит в набор Kaderium/Nano/Stasis/Food/Prodium,
 * предположительно SILVER_CREDIT — не подтверждено, см. открытые вопросы
 * client-combat-ui.md). Двухбуквенные ключи (KA/NA/ST/FO/PR/SC) — это
 * ДРУГОЕ соглашение, чем однобуквенные K/N/S/F/P/W на панелях тренировки
 * юнитов/зондов (см. §0.3/§11 того же документа).
 */
export interface UnitPower {
  kaderium?: number;
  nano?: number;
  stasis?: number;
  food?: number;
  prodium?: number;
  /** Не установлено, что это — см. комментарий выше. */
  sc?: number;
}

export interface CalcUnit {
  id: string;
  libname: string;
  /** 2_000_000_000 (условная "бесконечность"), если сервер не прислал amount (xmlparsers.as:1683). */
  amount: number;
  prodiumWeight: number;
  power: UnitPower;
}

export interface ArmyCalculatorData {
  /** 6 коэффициентов курса обмена ресурсов ("koeff", разделитель ":"). */
  koeff: number[];
  units: CalcUnit[];
}

const POWER_KEY_MAP: Record<string, keyof UnitPower> = {
  KA: 'kaderium',
  NA: 'nano',
  ST: 'stasis',
  FO: 'food',
  PR: 'prodium',
  SC: 'sc',
};

/** <armycalculator koeff="v1:v2:v3:v4:v5:v6"><unit id="" unitname="" amount="" PW="" price="KA:v;NA:v;..."/>...</armycalculator> */
export function parseArmyCalculator(xml: string): ArmyCalculatorData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'armycalculator') {
    throw new Error(`Ожидался корневой тег <armycalculator>, получено: ${root?.nodeName}`);
  }

  const koeff = (root.getAttribute('koeff') ?? '').split(':').map(Number);

  const units: CalcUnit[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'unit') continue;

    const power: UnitPower = {};
    for (const pair of (el.getAttribute('price') ?? '').split(';').filter(Boolean)) {
      const [key, value] = pair.split(':');
      const field = POWER_KEY_MAP[key];
      if (field) power[field] = Number(value);
    }

    const amountAttr = el.getAttribute('amount');
    const pw = el.getAttribute('PW');
    units.push({
      id: el.getAttribute('id') ?? '',
      libname: el.getAttribute('unitname') ?? '',
      amount: amountAttr !== null ? Number(amountAttr) : 2_000_000_000,
      prodiumWeight: pw !== null ? Number(pw) : 0,
      power,
    });
  }

  return { koeff, units };
}
