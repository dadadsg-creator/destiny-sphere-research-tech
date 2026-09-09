/**
 * Карточка мира с одним альянсом — peace_aliance.as, см.
 * docs/client-economy-social-ui.md §2.4. Данные — alliancePeaceData.ts
 * (parseAlliancePeaceDetail). Какие кнопки показывать, решает СЕРВЕР
 * через пять живых флагов `data.buttons` — не гадаем, какая группа
 * списка сюда привела, просто рисуем то, что разрешено (см.
 * RequestDataAlli.as: 8 возможных статусов `<getAlliancePeace status=
 * "..."/>`, 7 из них покрыты этими пятью флагами; `cancelTerminate` —
 * восьмой статус, условие его показа в клиенте не установлено, кнопка
 * для него сознательно не добавлена, а не гадаем).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import { ResourceValue } from '../ui/ResourceValue';
import { RESOURCE_NAME_RU, resourceIconUrl } from '../data/resourceIcons';
import type { AlliancePeaceDetailData, AlliancePeaceOfferPrice } from './alliancePeaceData';

export interface AlliancePeaceDetailWindowProps {
  data: AlliancePeaceDetailData;
  onClose: () => void;
  onBack: () => void;
  onOfferPeace: (myPrice: AlliancePeaceOfferPrice, hisPrice: AlliancePeaceOfferPrice, time: number) => void;
  onCancelOffer: () => void;
  onConfirmPeace: () => void;
  onDeclinePeace: () => void;
  onOfferTerminate: () => void;
  onConfirmTerminate: () => void;
  onDeclineTerminate: () => void;
}

const ZERO_PRICE: AlliancePeaceOfferPrice = { k: 0, n: 0, f: 0, p: 0, s: 0, e: 0 };

function SideInfo({ title, side }: { title: string; side: AlliancePeaceDetailData['youSide'] }) {
  return (
    <div class="ds-spying__row">
      <span class="ds-spying__label">{title}</span>
      <span class="ds-resource-list">
        {side.k && Number(side.k) > 0 && <ResourceValue name="KADERIUM" value={side.k} />}
        {side.n && Number(side.n) > 0 && <ResourceValue name="NANO_CRYSTAL" value={side.n} />}
        {side.f && Number(side.f) > 0 && <ResourceValue name="FOOD" value={side.f} />}
        {side.p && Number(side.p) > 0 && <ResourceValue name="PRODIUM" value={side.p} />}
        {side.s && Number(side.s) > 0 && <ResourceValue name="STASIS_ENERGY" value={side.s} />}
      </span>
    </div>
  );
}

/** Порядок и имена полей формы предложения мира — k/n/f/p/s, `e` не установлено (см. докстринг alliancePeaceData.ts), в форму не выведено. */
const PRICE_FIELDS: { key: keyof Omit<AlliancePeaceOfferPrice, 'e'>; resource: string }[] = [
  { key: 'k', resource: 'KADERIUM' },
  { key: 'n', resource: 'NANO_CRYSTAL' },
  { key: 'f', resource: 'FOOD' },
  { key: 'p', resource: 'PRODIUM' },
  { key: 's', resource: 'STASIS_ENERGY' },
];

function PriceRow({ label, price, onChange }: { label: string; price: AlliancePeaceOfferPrice; onChange: (p: AlliancePeaceOfferPrice) => void }) {
  return (
    <div class="ds-peace-price">
      <span class="ds-spying__label">{label}</span>
      <div class="ds-peace-price__fields">
        {PRICE_FIELDS.map(({ key, resource }) => (
          <span key={key} class="ds-peace-price__field" title={RESOURCE_NAME_RU[resource]}>
            <img class="ds-resource__icon" src={resourceIconUrl(resource)} alt="" />
            <Stepper value={price[key]} min={0} onChange={(v) => onChange({ ...price, [key]: v })} />
          </span>
        ))}
      </div>
    </div>
  );
}

export function AlliancePeaceDetailWindow({
  data,
  onClose,
  onBack,
  onOfferPeace,
  onCancelOffer,
  onConfirmPeace,
  onDeclinePeace,
  onOfferTerminate,
  onConfirmTerminate,
  onDeclineTerminate,
}: AlliancePeaceDetailWindowProps) {
  const [myPrice, setMyPrice] = useState(ZERO_PRICE);
  const [hisPrice, setHisPrice] = useState(ZERO_PRICE);
  const [time, setTime] = useState(1);

  return (
    <Window
      title={`Мир — ${data.opponentSide.name ?? ''}`}
      customHeader={
        <div class="ds-window__titlebar">
          <button type="button" class="ds-popup__btn" onClick={onBack}>
            ← Назад
          </button>
          <span class="ds-window__title">Мир — {data.opponentSide.name}</span>
          <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
            ×
          </button>
        </div>
      }
      onClose={onClose}
    >
      <SideInfo title="Наши войска у них:" side={data.youSide} />
      <SideInfo title="Их войска у нас:" side={data.opponentSide} />
      {data.desc && <p class="ds-power-shield__desc">{data.desc}</p>}

      {data.buttons.allow === '1' && (
        <>
          <h4>Предложить мир</h4>
          <PriceRow label="Мы отдаём:" price={myPrice} onChange={setMyPrice} />
          <PriceRow label="Они отдают:" price={hisPrice} onChange={setHisPrice} />
          <div class="ds-spying__row">
            <span class="ds-spying__label">Срок (ходов):</span>
            <Stepper value={time} min={1} onChange={setTime} />
            <button type="button" class="ds-popup__btn" onClick={() => onOfferPeace(myPrice, hisPrice, time)}>
              Отправить предложение
            </button>
          </div>
        </>
      )}

      {data.buttons.offer === '1' && (
        <button type="button" class="ds-popup__btn" onClick={onCancelOffer}>
          Отменить предложение
        </button>
      )}

      {data.buttons.confirm === '1' && (
        <div class="ds-spying__row">
          <button type="button" class="ds-popup__btn" onClick={onConfirmPeace}>
            Принять мир
          </button>
          <button type="button" class="ds-popup__btn" onClick={onDeclinePeace}>
            Отклонить
          </button>
        </div>
      )}

      {data.buttons.dismiss === '1' && (
        <button type="button" class="ds-popup__btn" onClick={onOfferTerminate}>
          Предложить расторжение мира
        </button>
      )}

      {data.buttons.confirmDismiss === '1' && (
        <div class="ds-spying__row">
          <button type="button" class="ds-popup__btn" onClick={onConfirmTerminate}>
            Подтвердить расторжение
          </button>
          <button type="button" class="ds-popup__btn" onClick={onDeclineTerminate}>
            Отклонить расторжение
          </button>
        </div>
      )}
    </Window>
  );
}
