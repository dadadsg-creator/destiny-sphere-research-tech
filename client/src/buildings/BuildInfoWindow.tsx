/**
 * Окно «Информация о здании» — buildinfo.as, см.
 * docs/client-economy-social-ui.md §1.8. Данные — buildInfoData.ts
 * (parseInfoBuild). Кнопки/стоимость апгрейда — общий `BuildInfoActions.tsx`
 * (переиспользуется докнутой панелью главного экрана, см. её докстринг).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { BuildInfoActions, type BuildInfoActionsProps } from './BuildInfoActions';

export interface BuildInfoWindowProps extends BuildInfoActionsProps {
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
}

export function BuildInfoWindow({ tabs, onTabClick, onClose, ...actionsProps }: BuildInfoWindowProps) {
  return (
    <Window title="Информация о здании" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <BuildInfoActions {...actionsProps} />
    </Window>
  );
}
