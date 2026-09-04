# Чеклист наличия графики: юниты, здания, артефакты

> Сверка того, что реально есть в игре (по данным живой БД, сессия
> 2026-09-02/03) с тем, что удалось извлечь из скомпилированных .swf
> в client-assets/ (FFDec, см. CLIENT_PORTING_PLAN.md, Фаза 0,
> 2026-09-03/04). «Найдено» = совпадение по линковочному имени символа
> (то самое имя, которым AS2-код обращается к клипу через attachMovie).
> Источник данных БД: unit_stats_clean.csv/building_attrs_clean.csv/
> artefacts.csv (не в git, scratchpad прошлой сессии — при необходимости
> пересобрать заново живым запросом по db-query-plan.md).

## Итог

| Категория | Найдено | Всего | % |
|---|---|---|---|
| Юниты | 95 | 99 | 95% |
| Здания | 84 | 84 | 100% |
| Типы артефактов (сгруппировано по scope+type, покрывает все 1303 конкретных artefactname) | 207 | 231 | 89% |

## Юниты (99)

| Юнит | Раса(ы) | Статус | Источник .swf | Кадров |
|---|---|---|---|---|
| ACID_THROWER | BIO | OK | war_units.lib | 1 |
| ADJUNKT | QUEST | OK | war_units.lib | 1 |
| ADVISOR | BIO | OK | war_units.lib | 1 |
| AKODEMEG | QUEST | OK | war_units.lib | 1 |
| ANIMIST | PSI | OK | war_units.lib | 1 |
| ANOMALIA | QUEST | OK | war_units.lib | 1 |
| ANTIBIOTIC | QUEST | OK | war_units.lib | 1 |
| ANTIDOT | QUEST | OK | artefact.lib | 1 |
| ARCHEOLOGIST | PSI;BIO;TECH | OK | menu.lib | 1 |
| AVENGER | BIO | OK | war_units.lib | 1 |
| BALL | QUEST | OK | war_units.lib | 1 |
| BEHEMOT | BIO | OK | war_units.lib | 1 |
| BIOTANK | BIO | OK | war_units.lib | 1 |
| BIOTECH | QUEST | OK | war_units.lib | 1 |
| CARRIER | PSI | OK | war_units.lib | 1 |
| CATERPILLAR | QUEST | OK | war_units.lib | 1 |
| CHANNELER | PSI | OK | war_units.lib | 1 |
| CHICKEN_BLIND | QUEST | OK | war_units.lib | 1 |
| CHIMERA | BIO | OK | war_units.lib | 1 |
| CHOLERA | QUEST | OK | war_units.lib | 1 |
| CONJURER | PSI | OK | war_units.lib | 1 |
| CRUISER | TECH | OK | war_units.lib | 1 |
| CYBORG | BIO | OK | war_units.lib | 1 |
| DED_MOROZ | QUEST | OK | war_units.lib | 1 |
| DEFENDER | TECH | OK | war_units.lib | 1 |
| DESTROYER | TECH | OK | war_units.lib | 1 |
| DEVASTATOR | BIO | OK | war_units.lib | 1 |
| DISCIPLE | PSI | OK | war_units.lib | 1 |
| DREADNAUT | TECH | OK | war_units.lib | 1 |
| DRONE | BIO | OK | war_units.lib | 1 |
| ELEMENTALIST | PSI | OK | war_units.lib | 1 |
| ELEMENT_OF_EAT | QUEST | OK | war_units.lib | 1 |
| ELEMENT_OF_ENERGY | QUEST | OK | war_units.lib | 1 |
| ELEMENT_OF_KADERIUM | QUEST | OK | war_units.lib | 1 |
| ELEMENT_OF_NANO | QUEST | OK | war_units.lib | 1 |
| ELEMENT_OF_PRODIUM | QUEST | OK | war_units.lib | 1 |
| EXPLORER | TECH | OK | war_units.lib | 1 |
| FIRESKUADE | TECH | OK | war_units.lib | 1 |
| FREIGHTER | TECH | OK | war_units.lib | 1 |
| GENMO | BIO | OK | war_units.lib | 1 |
| GLADIATOR | TECH | OK | war_units.lib | 1 |
| GRENADE_LAUNCHER | TECH | OK | war_units.lib | 1 |
| GRENADIER | TECH | OK | war_units.lib | 1 |
| GUARD | TECH | OK | war_units.lib | 1 |
| GUARDIAN | BIO | OK | war_units.lib | 1 |
| HATCHER | BIO | OK | build_bio.lib | 1 |
| HERETIC | PSI | OK | build_psi.lib | 1 |
| HIEROPHANT | PSI | OK | war_units.lib | 1 |
| HUNTER | TECH | OK | war_units.lib | 1 |
| ILLUSION | PSI | OK | artefact.lib | 1 |
| ILLUSIONIST | PSI | OK | war_units.lib | 1 |
| INFILTRATOR | BIO | OK | war_units.lib | 1 |
| INKVIZITOR | PSI | OK | war_units.lib | 1 |
| KAMIKAZE | TECH | OK | war_units.lib | 1 |
| KNIGHT | TECH | OK | war_units.lib | 1 |
| KOMBO | BIO | OK | war_units.lib | 1 |
| LEVIATHAN | BIO | OK | war_units.lib | 1 |
| LICENCIAT | QUEST | OK | war_units.lib | 1 |
| LIQUID | BIO | OK | war_units.lib | 1 |
| MAGISTER | QUEST | OK | war_units.lib | 1 |
| MECHWAR | TECH | OK | war_units.lib | 1 |
| MENTALIST | PSI | OK | war_units.lib | 1 |
| MIMICRY_DRONE | QUEST | OK | war_units.lib | 1 |
| MIMICRY_MASTER | QUEST | OK | war_units.lib | 1 |
| MIMICRY_SERVANT | QUEST | OK | war_units.lib | 1 |
| MISS_BEAUTY | QUEST | OK | war_units.lib | 1 |
| MONK | PSI | OK | war_units.lib | 1 |
| MONSTER | BIO | OK | war_units.lib | 1 |
| MYSTIC | PSI | OK | war_units.lib | 1 |
| NEKROMANT | PSI | OK | war_units.lib | 1 |
| NEOFANT | BIO | OK | war_units.lib | 1 |
| ORACLE | PSI | OK | war_units.lib | 1 |
| PHANTASMIST | PSI | OK | war_units.lib | 1 |
| PRELATE | PSI | OK | war_units.lib | 1 |
| PRIEST | PSI | OK | war_units.lib | 1 |
| ROCKET_LAUNCHER | TECH | OK | war_units.lib | 1 |
| ROY | BIO | OK | war_units.lib | 1 |
| SCIENTIST | TECH;PSI;BIO | OK | menu.lib | 1 |
| SERVANT | PSI | OK | war_units.lib | 1 |
| SNEGUROCHKA | QUEST | OK | war_units.lib | 1 |
| SNIPER | TECH | OK | war_units.lib | 1 |
| SOLDIER | TECH | OK | war_units.lib | 1 |
| SPIDER | BIO | OK | war_units.lib | 1 |
| SPORE_THROWER | BIO | OK | war_units.lib | 1 |
| STARCERRIER | TECH | OK | build_tech.lib | 1 |
| TANATOS | PSI | OK | war_units.lib | 1 |
| TANK | TECH | OK | war_units.lib | 1 |
| TEMPLAR | PSI | OK | war_units.lib | 1 |
| TRANSPORTER | TECH | OK | build_bio.lib | 1 |
| VAMPIRE | BIO | OK | war_units.lib | 1 |
| VETERAN | TECH | OK | war_units.lib | 1 |
| WORKER | PSI;BIO;TECH | OK | menu.lib | 1 |
| WORM | BIO | OK | war_units.lib | 1 |
| XAOZ | TECH | OK | war_units.lib | 1 |
| ZHRITZA | PSI | OK | war_units.lib | 1 |
| SPY_ZOND_EXPLORER | BIO;PSI;TECH | НЕ найден под этим именем — есть ZOND_BIO_1/2/3, ZOND_PSI_1/2/3, ZOND_TECH_1/2/3 (вероятно один из трёх номеров, какой конкретно — не проверено) | zond-файлы | - |
| SPY_ZOND_SCOUT | BIO;PSI;TECH | НЕ найден под этим именем — см. выше, вероятно другой номер ZOND_<раса>_N | zond-файлы | - |
| MIMICRY | QUEST | НЕ найден точно — есть MIMICRY_DRONE / MIMICRY_MASTER / MIMICRY_SERVANT, соответствие неясно | - | - |
| refractive | QUEST | НЕ найдено вообще (строчные буквы в имени — так записано в БД) | - | - |

## Здания (84) — 100% покрытие

| Здание | Источник .swf | Кадров |
|---|---|---|
| ALLIANCE_CONNECTOR | build_tech.lib | 200 |
| ARCHEOLOGICAL_INSTITUTE | build_tech.lib | 180 |
| ARCHEOLOGICAL_OUTPOST | build_bio.lib | 166 |
| ARTEFACT_TOWER | tree_scients_build_bio.lib | 189 |
| CENTRAL_GATE | build_bio.lib | 1 |
| CENTRAL_GATE_WRECK | build_bio.lib | 1 |
| CITY | build_tech.lib | 154 |
| CLONE_FARM | build_bio.lib | 1 |
| CLOUD_ENDERS_TEMPLE | build_bio.lib | 120 |
| COLONIZATION_CENTER | build_tech.lib | 128 |
| CONSTRUCTION_FACTORY | build_psi.lib | 141 |
| CRYSTAL_WEAVER | build_tech.lib | 330 |
| ENERGY_COLLECTOR | build_psi.lib | 115 |
| FARM | build_bio.lib | 130 |
| GONDOLA_TRANSPORTER | build_bio.lib | 280 |
| HEAVY_WEAPON_MANUFACTURE | build_tech.lib | 80 |
| HEAVY_WEAPON_MANUFACTURE2L | build_bio.lib | 1 |
| HEAVY_WEAPON_MANUFACTURE2P | build_psi.lib | 1 |
| HEAVY_WEAPON_MANUFACTURE2V | build_tech.lib | 1 |
| HUGE_KADERIUM_FORGE | build_tech.lib | 140 |
| HUGE_KADERIUM_FORGE_LIENSU | build_bio.lib | 140 |
| HUGE_NANO_CRYSTALLIZER | build_psi.lib | 1 |
| HUGE_NANO_CRYSTALLIZER_LIENSU | 3D_HUGE_PRODIUM_COLLECTOR_Files_PRODIUMS | 1 |
| HUGE_PRODIUM_COLLECTOR | 3D_HUGE_PRODIUM_COLLECTOR_Files_PRODIUMS | 1 |
| HYDRO_FARM | build_bio.lib | 1 |
| INTERRING_TRADE_CONNECTOR | build_tech.lib | 149 |
| KADERIUM_FORGE | build_psi.lib | 105 |
| KADERIUM_PRESS | build_bio.lib | 102 |
| LARGE_KADERIUM_FORGE | build_psi.lib | 105 |
| LARGE_NANO_CRYSTALLIZER | build_bio.lib | 1 |
| LARGE_PRODIUM_COLLECTOR | 3D_HUGE_PRODIUM_COLLECTOR_Files_PRODIUMS | 1 |
| LIGHT_WEAPON_MANUFACTURE | build_tech.lib | 65 |
| LIGHT_WEAPON_MANUFACTURE2L | build_bio.lib | 1 |
| LIGHT_WEAPON_MANUFACTURE2P | build_psi.lib | 1 |
| LIGHT_WEAPON_MANUFACTURE2V | build_tech.lib | 1 |
| LOCAL_TRADE_CONNECTOR | build_bio.lib | 110 |
| MEDIUM_FOOD_DUMP | build_bio.lib | 1 |
| MEDIUM_FOOD_SILO | build_bio.lib | 1 |
| MEDIUM_KADERIUM_DUMP | build_bio.lib | 1 |
| MEDIUM_KADERIUM_SILO | build_bio.lib | 1 |
| MEDIUM_NANO_DUMP | build_bio.lib | 1 |
| MEDIUM_NANO_SILO | build_bio.lib | 1 |
| MEDIUM_PRODIUM_DUMP | build_bio.lib | 1 |
| MEDIUM_PRODIUM_SILO | build_bio.lib | 1 |
| MEDIUM_STASIS_DUMP | build_bio.lib | 1 |
| MEDIUM_STASIS_SILO | build_bio.lib | 1 |
| MESH_BOILER | build_bio.lib | 175 |
| MILITARY_BASE | build_tech.lib | 152 |
| MIND_FACTORY | build_psi.lib | 105 |
| NANO_CRYSTALLIZER | build_bio.lib | 1 |
| PLANTATION | build_bio.lib | 145 |
| PRODIUM_COLLECTOR | 3D_HUGE_PRODIUM_COLLECTOR_Files_PRODIUMS | 1 |
| PROTECTIVE_FIELD_GENERATOR | build_bio.lib | 1 |
| RESEARCH_CENTER | build_tech.lib | 175 |
| RESIDENTIAL_BUILDING | build_tech.lib | 177 |
| RING_GATE | build_bio.lib | 1 |
| RING_TRADE_CONNECTOR | build_bio.lib | 133 |
| RUSHERS_TEMPLE | build_psi.lib | 105 |
| SAIL_FOREST | build_psi.lib | 140 |
| SAIL_TRAP | build_psi.lib | 130 |
| SECRET_SERVICE_CENTER | build_tech.lib | 235 |
| SHELTER | build_bio.lib | 150 |
| SHIELD_BUILD | build_bio.lib | 1 |
| SMALL_FOOD_DUMP | build_bio.lib | 1 |
| SMALL_FOOD_SILO | build_bio.lib | 1 |
| SMALL_KADERIUM_DUMP | build_bio.lib | 1 |
| SMALL_KADERIUM_SILO | build_bio.lib | 1 |
| SMALL_NANO_DUMP | build_bio.lib | 1 |
| SMALL_NANO_SILO | build_bio.lib | 1 |
| SMALL_PRODIUM_DUMP | build_bio.lib | 1 |
| SMALL_PRODIUM_SILO | build_bio.lib | 1 |
| SMALL_STASIS_DUMP | build_bio.lib | 1 |
| SMALL_STASIS_SILO | build_bio.lib | 1 |
| SOURCE | build_tech.lib | 95 |
| SPYING_CENTER | build_psi.lib | 115 |
| TERRAMORPH_TOWER | build_tech.lib | 59 |
| TRAINING_CAMP | build_bio.lib | 166 |
| TRAINING_CAMP2L | build_bio.lib | 1 |
| TRAINING_CAMP2P | build_psi.lib | 1 |
| TRAINING_CAMP2V | build_tech.lib | 1 |
| TUNNEL_RIVER_SCANNER | build_bio.lib | 80 |
| VILLAGE | build_bio.lib | 95 |
| VOCATION_BUILD | build_bio.lib | 125 |
| WELL_LADLER | build_tech.lib | 175 |

## Типы артефактов (231 группа scope+type, 1303 конкретных вариантов по редкости)

| Scope | Type | Статус | Совпало как | Источник | Вариантов редкости |
|---|---|---|---|---|---|
| ALLIANCE | ATOMIZATION | OK | ATOMIZATION | artefact.lib | 7 |
| ALLIANCE | CATALYZATION | OK | CATALYZATION | artefact.lib | 7 |
| ALLIANCE | CLONING | OK | CLONING | artefact.lib | 7 |
| ALLIANCE | COOLING | OK | COOLING | artefact.lib | 7 |
| ALLIANCE | CORE_SYNTHESIS | OK | CORE_SYNTHESIS | artefact.lib | 7 |
| ALLIANCE | CRYSTALLIZATION | OK | CRYSTALLIZATION | artefact.lib | 7 |
| ALLIANCE | DISPERSION | OK | DISPERSION | artefact.lib | 7 |
| ALLIANCE | ECOLOGY | OK | ECOLOGY | artefact.lib | 7 |
| ALLIANCE | FERTILIZATION | OK | FERTILIZATION | artefact.lib | 7 |
| ALLIANCE | FORGING | OK | FORGING | artefact.lib | 7 |
| ALLIANCE | FOUNDING | OK | FOUNDING | artefact.lib | 7 |
| ALLIANCE | GENETIC_ENGINEERING | OK | GENETIC_ENGINEERING | artefact.lib | 7 |
| ALLIANCE | HIGH_PRESSURE | OK | HIGH_PRESSURE | artefact.lib | 7 |
| ALLIANCE | HYDROPONIC | OK | HYDROPONIC | artefact.lib | 7 |
| ALLIANCE | IRRIGATION | OK | IRRIGATION | artefact.lib | 7 |
| ALLIANCE | MECHANIZATION | OK | MECHANIZATION | artefact.lib | 7 |
| ALLIANCE | MELTING | OK | MELTING | artefact.lib | 7 |
| ALLIANCE | MIXING_FODDER | OK | MIXING_FODDER | artefact.lib | 7 |
| ALLIANCE | NUCLEAR_FISSION | OK | NUCLEAR_FISSION | artefact.lib | 7 |
| ALLIANCE | PLASMA | OK | PLASMA | artefact.lib | 7 |
| ALLIANCE | POLYMERIZATION | OK | POLYMERIZATION | artefact.lib | 7 |
| ALLIANCE | QUANTUM_MECHANICS | OK | QUANTUM_MECHANICS | artefact.lib | 7 |
| ALLIANCE | QUANTUM_OPTICS | OK | QUANTUM_OPTICS | artefact.lib | 7 |
| ALLIANCE | RADIOLYSIS | OK | RADIOLYSIS | artefact.lib | 7 |
| ALLIANCE | REFINEMENT | OK | REFINEMENT | artefact.lib | 7 |
| ALLIANCE | SENSORING | OK | SENSORING | artefact.lib | 7 |
| ALLIANCE | SMITHING | OK | SMITHING | artefact.lib | 7 |
| ALLIANCE | STAMPING | OK | STAMPING | artefact.lib | 7 |
| ALLIANCE | SUPERCONDUCTIVITY | OK | SUPERCONDUCTIVITY | artefact.lib | 7 |
| ALLIANCE | SYNTHESIS | OK | SYNTHESIS | artefact.lib | 7 |
| ALLIANCE | TEMPERING | OK | TEMPERING | artefact.lib | 7 |
| ALLIANCE | THERMODYNAMICS | OK | THERMODYNAMICS | artefact.lib | 7 |
| ALLIANCE | TRANSPORTING | OK | TRANSPORTING | artefact.lib | 7 |
| ALLIANCE | UTILIZATION | OK | UTILIZATION | artefact.lib | 7 |
| ALLIANCE | WELDING | OK | WELDING | artefact.lib | 7 |
| BUILDING | ATOMIZATION | OK | ATOMIZATION | artefact.lib | 7 |
| BUILDING | CATALYZATION | OK | CATALYZATION | artefact.lib | 7 |
| BUILDING | CLONING | OK | CLONING | artefact.lib | 7 |
| BUILDING | COOLING | OK | COOLING | artefact.lib | 7 |
| BUILDING | CORE_SYNTHESIS | OK | CORE_SYNTHESIS | artefact.lib | 7 |
| BUILDING | CRYSTALLIZATION | OK | CRYSTALLIZATION | artefact.lib | 7 |
| BUILDING | DISPERSION | OK | DISPERSION | artefact.lib | 7 |
| BUILDING | ECOLOGY | OK | ECOLOGY | artefact.lib | 7 |
| BUILDING | FERTILIZATION | OK | FERTILIZATION | artefact.lib | 7 |
| BUILDING | FORGING | OK | FORGING | artefact.lib | 7 |
| BUILDING | FOUNDING | OK | FOUNDING | artefact.lib | 7 |
| BUILDING | GENETIC_ENGINEERING | OK | GENETIC_ENGINEERING | artefact.lib | 7 |
| BUILDING | HIGH_PRESSURE | OK | HIGH_PRESSURE | artefact.lib | 7 |
| BUILDING | HYDROPONIC | OK | HYDROPONIC | artefact.lib | 7 |
| BUILDING | IRRIGATION | OK | IRRIGATION | artefact.lib | 7 |
| BUILDING | MECHANIZATION | OK | MECHANIZATION | artefact.lib | 7 |
| BUILDING | MELTING | OK | MELTING | artefact.lib | 7 |
| BUILDING | MIXING_FODDER | OK | MIXING_FODDER | artefact.lib | 7 |
| BUILDING | NUCLEAR_FISSION | OK | NUCLEAR_FISSION | artefact.lib | 7 |
| BUILDING | PLASMA | OK | PLASMA | artefact.lib | 7 |
| BUILDING | POLYMERIZATION | OK | POLYMERIZATION | artefact.lib | 7 |
| BUILDING | QUANTUM_MECHANICS | OK | QUANTUM_MECHANICS | artefact.lib | 7 |
| BUILDING | QUANTUM_OPTICS | OK | QUANTUM_OPTICS | artefact.lib | 7 |
| BUILDING | RADIOLYSIS | OK | RADIOLYSIS | artefact.lib | 7 |
| BUILDING | REFINEMENT | OK | REFINEMENT | artefact.lib | 7 |
| BUILDING | SENSORING | OK | SENSORING | artefact.lib | 7 |
| BUILDING | SMITHING | OK | SMITHING | artefact.lib | 7 |
| BUILDING | STAMPING | OK | STAMPING | artefact.lib | 7 |
| BUILDING | SUPERCONDUCTIVITY | OK | SUPERCONDUCTIVITY | artefact.lib | 7 |
| BUILDING | SYNTHESIS | OK | SYNTHESIS | artefact.lib | 7 |
| BUILDING | TEMPERING | OK | TEMPERING | artefact.lib | 7 |
| BUILDING | THERMODYNAMICS | OK | THERMODYNAMICS | artefact.lib | 7 |
| BUILDING | TRANSPORTING | OK | TRANSPORTING | artefact.lib | 7 |
| BUILDING | UTILIZATION | OK | UTILIZATION | artefact.lib | 7 |
| BUILDING | WELDING | OK | WELDING | artefact.lib | 7 |
| COMB | ATOMIZATION | OK | ATOMIZATION | artefact.lib | 7 |
| COMB | CATALYZATION | OK | CATALYZATION | artefact.lib | 7 |
| COMB | CLONING | OK | CLONING | artefact.lib | 7 |
| COMB | COOLING | OK | COOLING | artefact.lib | 7 |
| COMB | CORE_SYNTHESIS | OK | CORE_SYNTHESIS | artefact.lib | 7 |
| COMB | CRYSTALLIZATION | OK | CRYSTALLIZATION | artefact.lib | 7 |
| COMB | DISPERSION | OK | DISPERSION | artefact.lib | 7 |
| COMB | ECOLOGY | OK | ECOLOGY | artefact.lib | 7 |
| COMB | FERTILIZATION | OK | FERTILIZATION | artefact.lib | 7 |
| COMB | FORGING | OK | FORGING | artefact.lib | 7 |
| COMB | FOUNDING | OK | FOUNDING | artefact.lib | 7 |
| COMB | GENETIC_ENGINEERING | OK | GENETIC_ENGINEERING | artefact.lib | 7 |
| COMB | HIGH_PRESSURE | OK | HIGH_PRESSURE | artefact.lib | 7 |
| COMB | HYDROPONIC | OK | HYDROPONIC | artefact.lib | 7 |
| COMB | IGROMIR_FOOD | OK | IGROMIR_FOOD | artefact.lib | 1 |
| COMB | IGROMIR_KADERIUM | OK | IGROMIR_KADERIUM | artefact.lib | 1 |
| COMB | IGROMIR_NANO | OK | IGROMIR_NANO | artefact.lib | 1 |
| COMB | IGROMIR_PRODIUM | OK | IGROMIR_PRODIUM | artefact.lib | 1 |
| COMB | IGROMIR_STATIC | OK | IGROMIR_STATIC | artefact.lib | 1 |
| COMB | IRRIGATION | OK | IRRIGATION | artefact.lib | 7 |
| COMB | JOYSTIK2 | OK | JOYSTIK2 | artefact.lib | 1 |
| COMB | MAGIC_CRISTAL | OK | MAGIC_CRISTAL | artefact.lib | 3 |
| COMB | MECHANIZATION | OK | MECHANIZATION | artefact.lib | 7 |
| COMB | MELTING | OK | MELTING | artefact.lib | 7 |
| COMB | MIXING_FODDER | OK | MIXING_FODDER | artefact.lib | 7 |
| COMB | NUCLEAR_FISSION | OK | NUCLEAR_FISSION | artefact.lib | 7 |
| COMB | PLASMA | OK | PLASMA | artefact.lib | 7 |
| COMB | POLYMERIZATION | OK | POLYMERIZATION | artefact.lib | 7 |
| COMB | POWER_PROGRESS | OK | POWER_PROGRESS | artefact.lib | 3 |
| COMB | QUANTUM_MECHANICS | OK | QUANTUM_MECHANICS | artefact.lib | 7 |
| COMB | QUANTUM_OPTICS | OK | QUANTUM_OPTICS | artefact.lib | 7 |
| COMB | RADIOLYSIS | OK | RADIOLYSIS | artefact.lib | 7 |
| COMB | REFINEMENT | OK | REFINEMENT | artefact.lib | 7 |
| COMB | ROAD_GEKKON | OK | ROAD_GEKKON | artefact.lib | 3 |
| COMB | SENSORING | OK | SENSORING | artefact.lib | 7 |
| COMB | SMITHING | OK | SMITHING | artefact.lib | 7 |
| COMB | STAMPING | OK | STAMPING | artefact.lib | 7 |
| COMB | SUPERCONDUCTIVITY | OK | SUPERCONDUCTIVITY | artefact.lib | 7 |
| COMB | SYNTHESIS | OK | SYNTHESIS | artefact.lib | 7 |
| COMB | TEMPERING | OK | TEMPERING | artefact.lib | 7 |
| COMB | THERMODYNAMICS | OK | THERMODYNAMICS | artefact.lib | 7 |
| COMB | TRANSPORTING | OK | TRANSPORTING | artefact.lib | 7 |
| COMB | UTILIZATION | OK | UTILIZATION | artefact.lib | 7 |
| COMB | WELDING | OK | WELDING | artefact.lib | 7 |
| COMBCOMPLEX | ATOMIZATION | OK | ATOMIZATION | artefact.lib | 7 |
| COMBCOMPLEX | CATALYZATION | OK | CATALYZATION | artefact.lib | 7 |
| COMBCOMPLEX | CLONING | OK | CLONING | artefact.lib | 7 |
| COMBCOMPLEX | COOLING | OK | COOLING | artefact.lib | 7 |
| COMBCOMPLEX | CORE_SYNTHESIS | OK | CORE_SYNTHESIS | artefact.lib | 7 |
| COMBCOMPLEX | CRYSTALLIZATION | OK | CRYSTALLIZATION | artefact.lib | 7 |
| COMBCOMPLEX | DISPERSION | OK | DISPERSION | artefact.lib | 7 |
| COMBCOMPLEX | ECOLOGY | OK | ECOLOGY | artefact.lib | 7 |
| COMBCOMPLEX | FERTILIZATION | OK | FERTILIZATION | artefact.lib | 7 |
| COMBCOMPLEX | FORGING | OK | FORGING | artefact.lib | 7 |
| COMBCOMPLEX | FOUNDING | OK | FOUNDING | artefact.lib | 7 |
| COMBCOMPLEX | GENETIC_ENGINEERING | OK | GENETIC_ENGINEERING | artefact.lib | 7 |
| COMBCOMPLEX | HIGH_PRESSURE | OK | HIGH_PRESSURE | artefact.lib | 7 |
| COMBCOMPLEX | HYDROPONIC | OK | HYDROPONIC | artefact.lib | 7 |
| COMBCOMPLEX | IRRIGATION | OK | IRRIGATION | artefact.lib | 7 |
| COMBCOMPLEX | MECHANIZATION | OK | MECHANIZATION | artefact.lib | 7 |
| COMBCOMPLEX | MELTING | OK | MELTING | artefact.lib | 7 |
| COMBCOMPLEX | MIXING_FODDER | OK | MIXING_FODDER | artefact.lib | 7 |
| COMBCOMPLEX | NUCLEAR_FISSION | OK | NUCLEAR_FISSION | artefact.lib | 7 |
| COMBCOMPLEX | PLASMA | OK | PLASMA | artefact.lib | 7 |
| COMBCOMPLEX | POLYMERIZATION | OK | POLYMERIZATION | artefact.lib | 7 |
| COMBCOMPLEX | QUANTUM_MECHANICS | OK | QUANTUM_MECHANICS | artefact.lib | 7 |
| COMBCOMPLEX | QUANTUM_OPTICS | OK | QUANTUM_OPTICS | artefact.lib | 7 |
| COMBCOMPLEX | RADIOLYSIS | OK | RADIOLYSIS | artefact.lib | 7 |
| COMBCOMPLEX | REFINEMENT | OK | REFINEMENT | artefact.lib | 7 |
| COMBCOMPLEX | SENSORING | OK | SENSORING | artefact.lib | 7 |
| COMBCOMPLEX | SMITHING | OK | SMITHING | artefact.lib | 7 |
| COMBCOMPLEX | STAMPING | OK | STAMPING | artefact.lib | 7 |
| COMBCOMPLEX | SUPERCONDUCTIVITY | OK | SUPERCONDUCTIVITY | artefact.lib | 7 |
| COMBCOMPLEX | SYNTHESIS | OK | SYNTHESIS | artefact.lib | 7 |
| COMBCOMPLEX | TEMPERING | OK | TEMPERING | artefact.lib | 7 |
| COMBCOMPLEX | THERMODYNAMICS | OK | THERMODYNAMICS | artefact.lib | 7 |
| COMBCOMPLEX | TRANSPORTING | OK | TRANSPORTING | artefact.lib | 7 |
| COMBCOMPLEX | UTILIZATION | OK | UTILIZATION | artefact.lib | 7 |
| COMBCOMPLEX | WELDING | OK | WELDING | artefact.lib | 7 |
| HOLIDAY | NEWYEAR1 | OK | NEWYEAR1 | artefact.lib | 1 |
| HOLIDAY | NEWYEAR2 | OK | NEWYEAR2 | artefact.lib | 1 |
| HOLIDAY2 | ANTIDOT | OK | ANTIDOT | artefact.lib | 1 |
| HOLIDAY2 | CALMNESS | OK | CALMNESS | artefact.lib | 1 |
| ICE | TREE1 | OK | TREE1 | artefact.lib | 1 |
| ICE | TREE2 | OK | TREE2 | artefact.lib | 1 |
| LOVE | ENERGYHEART | OK | ENERGYHEART | artefact.lib | 1 |
| LOVE | FOODHEART | OK | FOODHEART | artefact.lib | 1 |
| LOVE | KADERIUMHEART | OK | KADERIUMHEART | artefact.lib | 1 |
| LOVE | NANOHEART | OK | NANOHEART | artefact.lib | 1 |
| LOVE | PRODIUMHEART | OK | PRODIUMHEART | artefact.lib | 1 |
| QUEST | BOXIM | OK | BOXIM | artefact.lib | 1 |
| QUEST | JOYSTIKICON | OK | JOYSTIKICON | artefact.lib | 1 |
| QUEST | MOUSEICON | OK | MOUSEICON | artefact.lib | 1 |
| QUEST | PHONEICON | OK | PHONEICON | artefact.lib | 1 |
| SPHERE | FOOD_EGG | OK | FOOD_EGG | artefact.lib | 3 |
| SPHERE | KADERIUM_EGG | OK | KADERIUM_EGG | artefact.lib | 3 |
| SPHERE | MOBILEPHONE | OK | MOBILEPHONE | artefact.lib | 1 |
| SPHERE | MOUSE | OK | MOUSE | artefact.lib | 1 |
| SPHERE | NANO_CRYSTAL_EGG | OK | NANO_CRYSTAL_EGG | artefact.lib | 3 |
| SPHERE | PRODIUM_EGG | OK | PRODIUM_EGG | artefact.lib | 3 |
| SPHERE | STASIS_ENERGY_EGG | OK | STASIS_ENERGY_EGG | artefact.lib | 3 |
| WAR | ANGER | OK | ANGER | artefact.lib | 7 |
| WAR | ANNIHILATION | OK | ANNIHILATION | artefact.lib | 7 |
| WAR | BITTERNESS | OK | BITTERNESS | artefact.lib | 7 |
| WAR | BLAZING | OK | BLAZING | artefact.lib | 7 |
| WAR | CALMNESS | OK | CALMNESS | artefact.lib | 7 |
| WAR | CAMOUFLAGE | OK | CAMOUFLAGE | artefact.lib | 7 |
| WAR | COGNITION | OK | COGNITION | artefact.lib | 7 |
| WAR | COLLOQ | OK | COLLOQ | artefact.lib | 7 |
| WAR | CORROSION | OK | CORROSION | artefact.lib | 7 |
| WAR | COVERING | OK | COVERING | artefact.lib | 7 |
| WAR | DAZZLING | OK | DAZZLING | artefact.lib | 7 |
| WAR | DECEPTION | OK | DECEPTION | artefact.lib | 7 |
| WAR | DESTRUCTION | OK | DESTRUCTION | artefact.lib | 7 |
| WAR | DEVASTATION | OK | DEVASTATION | artefact.lib | 7 |
| WAR | FIRE | OK | FIRE | artefact.lib | 7 |
| WAR | FIRMNESS | OK | FIRMNESS | artefact.lib | 7 |
| WAR | HAVOC | OK | HAVOC | artefact.lib | 7 |
| WAR | ILLUSION | OK | ILLUSION | artefact.lib | 7 |
| WAR | LIGHT | OK | LIGHT | artefact.lib | 7 |
| WAR | LIVING | OK | LIVING | artefact.lib | 7 |
| WAR | LOCATION | OK | LOCATION | artefact.lib | 7 |
| WAR | MISLEAD | OK | MISLEAD | artefact.lib | 7 |
| WAR | PRECISION | OK | PRECISION | artefact.lib | 7 |
| WAR | PROTECTION | OK | PROTECTION | artefact.lib | 7 |
| WAR | RAGE | OK | RAGE | artefact.lib | 7 |
| WAR | RANCOUR | OK | RANCOUR | artefact.lib | 7 |
| WAR | RASHNESS | OK | RASHNESS | artefact.lib | 7 |
| WAR | REASON | OK | REASON | artefact.lib | 7 |
| WAR | RELIABILITY | OK | RELIABILITY | artefact.lib | 7 |
| WAR | RESISTANCE | OK | RESISTANCE | artefact.lib | 7 |
| WAR | RUSE | OK | RUSE | artefact.lib | 7 |
| WAR | SECURITY | OK | SECURITY | artefact.lib | 7 |
| WAR | STABILITY | OK | STABILITY | artefact.lib | 7 |
| WAR | STEADFASTNESS | OK | STEADFASTNESS | artefact.lib | 7 |
| WAR | UNYIELDING | OK | UNYIELDING | artefact.lib | 7 |
| WAR | ZEAL | OK | ZEAL | artefact.lib | 7 |
| ALLIANCE | IGROMIR_ALLIANCE_FOOD_SALE | НЕТ | - | - | 1 (пример: PAID_ALLIANCE_IGROMIR_FOOD) |
| ALLIANCE | IGROMIR_ALLIANCE_KADERIUM_SALE | НЕТ | - | - | 1 (пример: PAID_ALLIANCE_IGROMIR_KADERIUM) |
| ALLIANCE | IGROMIR_ALLIANCE_NANO_SALE | НЕТ | - | - | 1 (пример: PAID_ALLIANCE_IGROMIR_NANO) |
| ALLIANCE | IGROMIR_ALLIANCE_PRODIUM_SALE | НЕТ | - | - | 1 (пример: PAID_ALLIANCE_IGROMIR_PRODIUM) |
| ALLIANCE | IGROMIR_ALLIANCE_STATIC_SALE | НЕТ | - | - | 1 (пример: PAID_ALLIANCE_IGROMIR_STATIC) |
| BUILDING | IGROMIR_FOOD_SALE | НЕТ | - | - | 1 (пример: PAID_BUILDING_IGROMIR_FOOD) |
| BUILDING | IGROMIR_KADERIUM_SALE | НЕТ | - | - | 1 (пример: PAID_BUILDING_IGROMIR_KADERIUM) |
| BUILDING | IGROMIR_NANO_SALE | НЕТ | - | - | 1 (пример: PAID_BUILDING_IGROMIR_NANO) |
| BUILDING | IGROMIR_PRODIUM_SALE | НЕТ | - | - | 1 (пример: PAID_BUILDING_IGROMIR_PRODIUM) |
| BUILDING | IGROMIR_STATIC_SALE | НЕТ | - | - | 1 (пример: PAID_BUILDING_IGROMIR_STATIC) |
| COMB | IGROMIR_COMB_FOOD_SALE | НЕТ | - | - | 1 (пример: PAID_COMB_IGROMIR_FOOD) |
| COMB | IGROMIR_COMB_KADERIUM_SALE | НЕТ | - | - | 1 (пример: PAID_COMB_IGROMIR_KADERIUM) |
| COMB | IGROMIR_COMB_NANO_SALE | НЕТ | - | - | 1 (пример: PAID_COMB_IGROMIR_NANO) |
| COMB | IGROMIR_COMB_PRODIUM_SALE | НЕТ | - | - | 1 (пример: PAID_COMB_IGROMIR_PRODIUM) |
| COMB | IGROMIR_COMB_STATIC_SALE | НЕТ | - | - | 1 (пример: PAID_COMB_IGROMIR_STATIC) |
| COMBCOMPLEX | IGROMIR_COMBCOMPLEX_FOOD_SALE | НЕТ | - | - | 1 (пример: PAID_COMBCOMPLEX_IGROMIR_FOOD) |
| COMBCOMPLEX | IGROMIR_COMBCOMPLEX_KADERIUM_SALE | НЕТ | - | - | 1 (пример: PAID_COMBCOMPLEX_IGROMIR_KADERIUM) |
| COMBCOMPLEX | IGROMIR_COMBCOMPLEX_NANO_SALE | НЕТ | - | - | 1 (пример: PAID_COMBCOMPLEX_IGROMIR_NANO) |
| COMBCOMPLEX | IGROMIR_COMBCOMPLEX_PRODIUM_SALE | НЕТ | - | - | 1 (пример: PAID_COMBCOMPLEX_IGROMIR_PRODIUM) |
| COMBCOMPLEX | IGROMIR_COMBCOMPLEX_STATIC_SALE | НЕТ | - | - | 1 (пример: PAID_COMBCOMPLEX_IGROMIR_STATIC) |
| WAR | IGROMIR_WAR_ATTACK_SALE | НЕТ | - | - | 1 (пример: PAID_WAR_IGROMIR_ATTACK) |
| WAR | IGROMIR_WAR_DAMAGE_SALE | НЕТ | - | - | 1 (пример: PAID_WAR_IGROMIR_DAMAGE) |
| WAR | IGROMIR_WAR_DEFEND_SALE | НЕТ | - | - | 1 (пример: PAID_WAR_IGROMIR_DEFEND) |
| WAR | IGROMIR_WAR_HITPOINTS_SALE | НЕТ | - | - | 1 (пример: PAID_WAR_IGROMIR_HITPOINTS) |

**Все 24 отсутствующих — одна группа**: IGROMIR_*_SALE (скидочные промо-артефакты
с игровой конференции «Игромир», префикс PAID, единственный вариант редкости
каждый) — согласуется с уже задокументированной находкой
(mechanics-economy-trade.md §6.9), что PAID-префикс не выпадает случайно,
а назначается вручную — похоже, эти конкретные промо-предметы никогда не
получили собственной графики (или раздавались без визуализации в инвентаре).
