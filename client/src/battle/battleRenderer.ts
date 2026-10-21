/**
 * "Наглядное поле боя" — отрисовка одного раунда <battlinfo> (см.
 * battleData.ts). Две колонки (сторона A слева, сторона D справа),
 * юниты внутри стороны сгруппированы по армии — как в оригинальном
 * warplateclass.as (docs/client-combat-ui.md §4: "даже наглядное поле
 * боя различает, из какой конкретно армии пришли юниты").
 */
import { Application, Container, Sprite, Text, Assets } from 'pixi.js';
import type { BattleRound, BattleUnit } from './battleData';
import { unitDisplayName } from '../data/resourceIcons';
import { publicAssetUrl } from '../assets/publicAssetUrl';

const ROW_HEIGHT = 40;
const ARMY_GAP = 12;
const ICON_SIZE = 32;
const COLUMN_WIDTH = 260;

function groupByArmy(units: BattleUnit[]): Map<string, BattleUnit[]> {
  const groups = new Map<string, BattleUnit[]>();
  for (const unit of units) {
    const list = groups.get(unit.army) ?? [];
    list.push(unit);
    groups.set(unit.army, list);
  }
  return groups;
}

export class BattleRenderer {
  readonly app: Application;
  private readonly root: Container;

  private constructor(app: Application) {
    this.app = app;
    this.root = new Container();
    this.app.stage.addChild(this.root);
  }

  static async create(parent: HTMLElement): Promise<BattleRenderer> {
    const app = new Application();
    await app.init({ resizeTo: parent, backgroundColor: 0x040d23, antialias: true });
    parent.appendChild(app.canvas);
    return new BattleRenderer(app);
  }

  async renderRound(round: BattleRound): Promise<void> {
    this.root.removeChildren();

    const libnames = new Set([...round.sideA, ...round.sideD].map((u) => u.libname));
    await Assets.load(
      [...libnames].map((name) => ({ alias: name, src: publicAssetUrl(`/assets/units/${name}.png`) })),
    );

    this.renderSide(round.sideA, 0);
    this.renderSide(round.sideD, COLUMN_WIDTH);
  }

  private renderSide(units: BattleUnit[], columnX: number): void {
    const groups = groupByArmy(units);
    let y = 0;

    for (const [army, armyUnits] of groups) {
      const label = new Text({ text: army || '(без армии)', style: { fill: 0xffffff, fontSize: 14 } });
      label.x = columnX;
      label.y = y;
      this.root.addChild(label);
      y += ROW_HEIGHT * 0.6;

      for (const unit of armyUnits) {
        const row = new Container();
        row.x = columnX;
        row.y = y;

        if (Assets.cache.has(unit.libname)) {
          const icon = Sprite.from(unit.libname);
          icon.width = ICON_SIZE;
          icon.height = ICON_SIZE;
          row.addChild(icon);
        } else {
          console.warn('[BattleRenderer] нет текстуры для юнита:', unit.libname);
        }

        const text = new Text({
          text: `${unit.amount}× ${unitDisplayName(unit.libname)}`,
          style: { fill: 0xffffff, fontSize: 13 },
        });
        text.x = ICON_SIZE + 8;
        text.y = (ICON_SIZE - text.height) / 2;
        row.addChild(text);

        this.root.addChild(row);
        y += ROW_HEIGHT;
      }

      y += ARMY_GAP;
    }
  }

  destroy(): void {
    this.app.destroy(true, { children: true });
  }
}
