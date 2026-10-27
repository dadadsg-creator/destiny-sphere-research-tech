import { HOME_ART_GROUND_V14_AUDIT } from './locationGround';
import { isHomeTerrainCode, type HomeTerrainCode } from './buildingGroundRules';

export interface TerrainV22Audit {
  version: 1;
  reference: string;
  actual: string;
  reviewed: number[];
}

function validMap(value: unknown): value is string {
  return typeof value === 'string' && value.length === 217 && [...value].every(isHomeTerrainCode);
}

/** Keep the accepted assignment separate from the new image classification. */
export function restoreV22Audit(stored: string | null, acceptedV14: string | null): TerrainV22Audit {
  if (stored) {
    try {
      const parsed = JSON.parse(stored) as Partial<TerrainV22Audit>;
      if (parsed.version === 1 && validMap(parsed.reference) && validMap(parsed.actual) && Array.isArray(parsed.reviewed)) {
        return {
          version: 1,
          reference: parsed.reference,
          actual: parsed.actual,
          reviewed: [...new Set(parsed.reviewed.filter((n): n is number => Number.isInteger(n) && n >= 0 && n < 217))],
        };
      }
    } catch { /* A broken audit must not replace the accepted map. */ }
  }
  const reference = validMap(acceptedV14) ? acceptedV14 : HOME_ART_GROUND_V14_AUDIT;
  return { version: 1, reference, actual: reference, reviewed: [] };
}

export function v22AuditCounts(reference: readonly HomeTerrainCode[], actual: readonly HomeTerrainCode[], reviewed: ReadonlySet<number>): { reviewed: number; mismatches: number } {
  const cells = [...reviewed].filter(n => Number.isInteger(n) && n >= 0 && n < reference.length && n < actual.length);
  return { reviewed: cells.length, mismatches: cells.filter(n => reference[n] !== actual[n]).length };
}
