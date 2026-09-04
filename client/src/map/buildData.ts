/**
 * Разбор <build> — плоский список полей построек через сплиттер "^"
 * (`parser.as:5` — `static var spliter = "^"`). Портировано 1:1 из
 * `parser.as:964-1025`: каждая запись переменной длины, первое число
 * (`length_`) определяет, сколько дальше полей читать — см.
 * docs/client-map-and-grid.md §4.3.
 *
 * Коды `status` — из `sota.as:830-884` (switch по build[i].status):
 * "1" стройка, "2" активация, "3" деактивация, "4" неактивно, "5" апгрейд,
 * иначе (в т.ч. "OK", дефолт в parser.as при отсутствии поля) — обычное
 * активное здание.
 */
const SPLITTER = '^';

export type BuildStatus = 'OK' | '1' | '2' | '3' | '4' | '5' | string;

export interface BuildRecord {
  /** Индекс ячейки сетки (0-based, совпадает с num грунта). */
  num: number;
  /** id_building — см. buildingTypes.ts BUILDING_NAMES. */
  id: number;
  art: string;
  status: BuildStatus;
  /** Процент готовости на момент разбора (снапшот, не тикающий) — только если length_>4. */
  percent?: number;
  beginbuild?: number;
  endbuild?: number;
  dateEnd?: string;
  desc?: string;
}

/** getServerTime() в клиенте — здесь снапшот на момент разбора (см. docs §5.1: sota.as отдельно тикает это в реальном времени, здесь не реализовано). */
export function parseBuild(xmlbuild: string, serverTimeMs: number = Date.now()): BuildRecord[] {
  if (!xmlbuild) return [];

  const fields = xmlbuild.split(SPLITTER);
  const out: BuildRecord[] = [];
  let j = 0;

  while (j < fields.length) {
    const length_ = Number(fields[j++]);

    const num = Number(fields[j++]);
    const id = Number(fields[j++]);
    const art = fields[j++];

    const record: BuildRecord = { num, id, art, status: 'OK' };

    if (length_ > 3) {
      record.status = fields[j++];
    }

    if (length_ > 4) {
      const [beginStr, endStr] = fields[j++].split('|');
      const beginbuild = Number(beginStr);
      const endbuild = Number(endStr);
      const percentStep = (endbuild - beginbuild) / 100;
      const rawPercent = Math.floor((serverTimeMs - beginbuild) / percentStep);
      record.percent = Math.min(rawPercent, 100);
      record.beginbuild = beginbuild;
      record.endbuild = endbuild;
      record.dateEnd = fields[j++];
    }

    if (length_ > 6) {
      record.desc = fields[j++];
    }

    out.push(record);
  }

  return out;
}
