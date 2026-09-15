/**
 * Окно «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts. В отличие от большинства окон этого
 * порта корневая марка не содержит `<alltabs>` (см. `TL_ComplexView.
 * class.php`) — здесь нет вкладок и своего `getbuildmenu`-семейства,
 * поэтому заголовок и структура окна заданы напрямую.
 *
 * Цветовая раскладка ячейки (sota.as:1155-1168, воспроизведена CSS-
 * модификаторами, не пиксель-в-пиксель): своя сота — акцент "home",
 * та же альянс — "ally", иначе — нейтральный жёлтый "other". Открытые
 * ворота центра подсвечиваются отдельно (`gateOpen`).
 *
 * VK-профиль/кнопки (`PROFILE_BUT`, `ComplexProfiles`) сознательно не
 * портированы — см. memory remaster_vk_integration_rebuild.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { ComplexViewData, ComplexViewNavigateCommand } from './complexViewData';

export interface ComplexViewWindowProps {
  data: ComplexViewData;
  onClose: () => void;
  onNavigate: (cmd: ComplexViewNavigateCommand) => void;
  onWriteMail: (nick: string) => void;
}

export function ComplexViewWindow({ data, onClose, onNavigate, onWriteMail }: ComplexViewWindowProps) {
  const [ring, setRing] = useState(data.rings.find((r) => r.ln === data.ringName)?.id ?? data.rings[0]?.id ?? '');
  const [complexNum, setComplexNum] = useState(data.complexNumber);

  const slots = Array.from({ length: 6 }, (_, i) => data.combs.find((c) => c.num === i + 1));

  return (
    <Window title={`Комплекс ${data.ringName}.${data.complexNumber}`} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Кольцо:</span>
        <select value={ring} onChange={(e) => setRing((e.target as HTMLSelectElement).value)}>
          {data.rings.map((r) => (
            <option key={r.id} value={r.id}>
              {r.ln ?? r.id}
            </option>
          ))}
        </select>
        <span class="ds-spying__label">№ комплекса:</span>
        <input
          type="number"
          class="ds-stepper__input"
          value={complexNum}
          onInput={(e) => setComplexNum(Number((e.target as HTMLInputElement).value))}
        />
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ ring, complexnum: complexNum })}>
          Перейти
        </button>
      </div>

      <div class="ds-btn-row">
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'LEFT' })}>
          ← Пред.
        </button>
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'HOME' })}>
          Домой
        </button>
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'RIGHT' })}>
          След. →
        </button>
      </div>

      <table>
        <thead>
          <tr>
            <th>Сота</th>
            <th>Игрок</th>
            <th>Название</th>
            <th>Рейтинг</th>
            <th>Лига</th>
            <th>Альянс</th>
            <th>Статус</th>
          </tr>
        </thead>
        <tbody>
          {slots.map((comb, i) => {
            const num = i + 1;
            if (!comb || !comb.userName) {
              return (
                <tr key={num}>
                  <td>
                    {data.ringName}.{data.complexNumber}.{num}
                  </td>
                  <td colSpan={5}>— свободно —</td>
                </tr>
              );
            }

            const statusParts: string[] = [];
            if (comb.premium) statusParts.push('премиум');
            if (comb.frozen) statusParts.push('заморожен/отпуск');
            if (comb.banned) statusParts.push('забанен');
            if (comb.newbee) statusParts.push('защита новичка');
            if (comb.npc) statusParts.push('NPC');
            if (comb.captured) statusParts.push('захвачена');
            if (comb.gateOpen) statusParts.push('ворота открыты');

            const rowClass = comb.home ? 'ds-army-row' : undefined;

            return (
              <tr key={num} class={rowClass}>
                <td>
                  {data.ringName}.{data.complexNumber}.{num}
                </td>
                <td>
                  {comb.userName}{' '}
                  <button type="button" class="ds-popup__btn" onClick={() => onWriteMail(comb.userName!)}>
                    ✉
                  </button>
                </td>
                <td>{comb.combName ?? ''}</td>
                <td>{comb.cscore}</td>
                <td>{comb.league}</td>
                <td>{comb.allianceTag ?? ''}</td>
                <td>{statusParts.join(', ')}</td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
