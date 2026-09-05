/**
 * Порт `help_class.as` — Фаза 3.13, см. docs/client-economy-social-ui.md
 * §0.1. Каждая панель зовёт `help_class.initHelp("<KEY>")`, что открывает
 * ВЕБ-страницу (не Flash) в новой вкладке: `/ds/help/?name=<KEY>
 * [&buildid=<локализованное_имя_здания>&race=<raceId>]`
 * (`help_class.as:18-49`). На VK-сборке префикс — `http://ds.rambler.ru`
 * (`help_class.as:54-58`, историческое подтверждение хостинга VK-версии
 * через rambler.ru).
 *
 * Каталог ключей — см. таблицу в докладе §0.1 (собрана по всем
 * прочитанным файлам клиента за все фазы).
 */

export type HelpKey =
  | 'TRADE_MENU'
  | 'AUCTION_MENU'
  | 'BUILD_MENU'
  | 'BUILD_INFO'
  | 'BUILD_MENU_GATE'
  | 'BUILDNIG_ARTEFACT' // опечатка в самом клиенте (BUILDNIG вместо BUILDING) — воспроизведена как есть
  | 'ART_DEPOT'
  | 'ARCHEOLOGY_MENU'
  | 'TERRAFORM_MENU'
  | 'RESEARCH_MENU'
  | 'ALLIANCE_MENU'
  | 'ALLIANCE_MEMBER'
  | 'ALLIANCE_SETTING'
  | 'ALLIANCE_PEACE'
  | 'PRIVATE_PEACE_HELP'
  | 'COLONIZATION'
  | 'COLONY_NAVIGATE'
  | 'RELIGION'
  | 'REINCARNATE_MENU'
  | 'OTPUSK_MENU'
  | 'QUEST_MENU'
  | 'TRANSFER'
  | 'CREATE_NEW_MAIL';

/** help_class.as:18-49 getHelp() — buildid/race добавляются, только если открыто из контекста ячейки (debug.link.active_cell). */
export function buildHelpUrl(key: HelpKey, cellContext?: { buildName: string; raceId: string }): string {
  let path = `/ds/help/?name=${key}`;
  if (cellContext) {
    path += `&buildid=${cellContext.buildName}&race=${cellContext.raceId}`;
  }
  return path;
}

/** help_class.as:51-59 redirect() — на VK-сборке хелп хостится через rambler.ru. */
export function resolveHelpUrl(path: string, isVk: boolean): string {
  return isVk ? `http://ds.rambler.ru${path}` : path;
}
