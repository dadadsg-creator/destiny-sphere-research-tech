export type BuildingRace = 'tech' | 'bio' | 'psi' | 'common';
export type Vector2 = readonly [number, number];
export type Footprint = readonly [number, number];
export type NormalizedAnchor = readonly [number, number];
export type PortMask = readonly [boolean, boolean, boolean, boolean, boolean, boolean];

export interface BuildingVisualLayers {
  color: string;
  shadow?: string;
  castShadow?: string;
  emissive?: string;
  construction?: string;
  disabled?: string;
  damage?: string;
}

export interface BuildingVisualManifest {
  id: string;
  race: BuildingRace;
  complexGroup: string | null;
  variant?: number;
  footprint: Footprint;
  logicalWidth: number;
  anchor: NormalizedAnchor;
  shadowOffset?: Vector2;
  emissiveOffset?: Vector2;
  ports: PortMask;
  layers: BuildingVisualLayers;
}

export interface ResolvedBuildingVisual extends Omit<BuildingVisualManifest, 'layers'> {
  manifestUrl: string;
  layers: { color: string } & Partial<Record<Exclude<keyof BuildingVisualLayers, 'color'>, string>>;
}
