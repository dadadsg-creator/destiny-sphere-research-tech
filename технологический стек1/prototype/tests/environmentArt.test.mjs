import assert from 'node:assert/strict';
import test from 'node:test';

import {
  RING_GATE_CELL_NUMS,
  RING_GATE_ENTRANCE_OFFSET,
  hitTestAncientInfrastructure,
} from '../src/environmentArt.mjs';

test('ring gate reserves a stable two-cell footprint', () => {
  assert.deepEqual(RING_GATE_CELL_NUMS, [22, 42]);
});

test('ring gate sprite hit area resolves independently from the ground cells', () => {
  const center = [24, 13];
  assert.equal(hitTestAncientInfrastructure(center, 178, 0), 'ringGate');
  assert.equal(hitTestAncientInfrastructure(center, 80, 0), null);
});

test('ring gate energy channel terminates at the front of the entry platform', () => {
  assert.deepEqual(RING_GATE_ENTRANCE_OFFSET, [178, 15]);
});
