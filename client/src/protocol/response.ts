/**
 * Парсер ответов ds/useraction.php.
 *
 * Формат ответа — НЕ единый XML-документ, а Flash LoadVars-строка вида
 * `mark=value&mark=value...`, где отдельные значения сами являются
 * XML-фрагментами (см. docs/protocol-client-server.md §2 в основном
 * репозитории документации). Разбирать простым split('&') нельзя —
 * значения (XML) могут содержать сырой `&` внутри себя. Вместо этого ищем
 * позиции top-level ключей (`(?:^|&)ident=`) и режем строку по ним.
 */

export type ParsedMarks = Record<string, string>;

const MARK_RE = /(?:^|&)([a-zA-Z_][a-zA-Z0-9_]*)=/g;

export function parseMarks(raw: string): ParsedMarks {
  const matches: { key: string; start: number; valueStart: number }[] = [];
  let m: RegExpExecArray | null;
  MARK_RE.lastIndex = 0;
  while ((m = MARK_RE.exec(raw)) !== null) {
    matches.push({ key: m[1], start: m.index, valueStart: m.index + m[0].length });
  }

  const result: ParsedMarks = {};
  for (let i = 0; i < matches.length; i++) {
    const cur = matches[i];
    const next = matches[i + 1];
    const end = next ? next.start : raw.length;
    result[cur.key] = raw.slice(cur.valueStart, end);
  }
  return result;
}

/** Разобрать XML-фрагмент через DOMParser (браузер) без внешних зависимостей. */
export function parseXmlFragment(xml: string): Document {
  const parser = new DOMParser();
  return parser.parseFromString(xml, 'text/xml');
}
