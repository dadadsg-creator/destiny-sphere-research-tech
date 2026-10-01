import assert from 'node:assert/strict';
import test from 'node:test';

import { DIAGNOSTIC_THEME, VORANER_THEME, resolveComplexTheme } from '../src/complexTheme.mjs';

test('diagnostic packages retain visible technical connectors', () => {
  const theme = resolveComplexTheme({ id: 'tech/residential/diagnostic-blue', race: 'tech' });
  assert.equal(theme, DIAGNOSTIC_THEME);
  assert.equal(theme.connector.highlight, true);
  assert.equal(theme.showTopology, true);
});

test('Voraner art packages hide procedural topology and rely on fused assets', () => {
  const theme = resolveComplexTheme({ id: 'tech/residential/voraner-v01-concept', race: 'tech' });
  assert.equal(theme, VORANER_THEME);
  assert.equal(theme.connector.highlight, false);
  assert.equal(theme.showTopology, false);
  assert.ok(theme.connector.outerWidth < DIAGNOSTIC_THEME.connector.outerWidth);
  assert.ok(theme.connector.innerWidth < DIAGNOSTIC_THEME.connector.innerWidth);
});

test('missing packages fall back to the diagnostic theme', () => {
  assert.equal(resolveComplexTheme(null), DIAGNOSTIC_THEME);
});
