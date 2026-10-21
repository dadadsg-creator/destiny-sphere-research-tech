param(
  [string]$RepositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
)

Add-Type -AssemblyName System.Drawing

$sourceRoot = Join-Path $RepositoryRoot 'технологический стек1\assets\tech'
$targetRoot = Join-Path $RepositoryRoot 'client\public\assets\hive-lab'
New-Item -ItemType Directory -Force -Path $targetRoot | Out-Null

function Export-ScaledPng {
  param(
    [Parameter(Mandatory)][string]$Source,
    [Parameter(Mandatory)][string]$Target,
    [Parameter(Mandatory)][int]$TargetWidth
  )

  $input = [System.Drawing.Image]::FromFile($Source)
  try {
    $targetHeight = [Math]::Round($input.Height * $TargetWidth / $input.Width)
    $output = New-Object System.Drawing.Bitmap $TargetWidth, $targetHeight, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    try {
      $graphics = [System.Drawing.Graphics]::FromImage($output)
      try {
        $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
        $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
        $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $graphics.DrawImage($input, 0, 0, $TargetWidth, $targetHeight)
      } finally {
        $graphics.Dispose()
      }
      $output.Save($Target, [System.Drawing.Imaging.ImageFormat]::Png)
    } finally {
      $output.Dispose()
    }
  } finally {
    $input.Dispose()
  }
}

$assets = @(
  @{ Source = 'environment\exports\voraner-sandy-hive-background-v01.png'; Target = 'hive-ground.png'; Width = 1280 },
  @{ Source = 'environment\exports\voraner-sandy-hive-background-v01.png'; Target = 'hive-ground-hd.png'; Width = 3072 },
  @{ Source = 'residential\exports\voraner-residential-v02-color.png'; Target = 'house-v02.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v02-emissive.png'; Target = 'house-v02-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v03-color.png'; Target = 'house-v03.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v03-emissive.png'; Target = 'house-v03-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v05-color.png'; Target = 'house-v05.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v05-emissive.png'; Target = 'house-v05-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-3-color.png'; Target = 'complex-3.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-3-emissive.png'; Target = 'complex-3-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-7-color.png'; Target = 'complex-7.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-7-emissive.png'; Target = 'complex-7-emissive.png'; Width = 512 },
  @{ Source = 'energy\exports\voraner-stasis-collector-v01-color.png'; Target = 'stasis-collector-v01.png'; Width = 512 },
  @{ Source = 'industry\exports\voraner-kaderium-forge-v03-dispatch-color.png'; Target = 'kaderium-forge-v03-dispatch.png'; Width = 512 },
  @{ Source = 'materials\exports\voraner-nano-crystallizer-v02-dispatch-color.png'; Target = 'nano-crystallizer-v02-dispatch.png'; Width = 512 },
  @{ Source = 'food\exports\voraner-greenhouse-farm-v02-color.png'; Target = 'greenhouse-farm-v02.png'; Width = 512 },
  @{ Source = 'military\exports\voraner-light-weapons-factory-v02-color.png'; Target = 'light-weapons-factory-v02.png'; Width = 512 },
  @{ Source = 'military\exports\voraner-heavy-weapons-factory-v01-color.png'; Target = 'heavy-weapons-factory-v01.png'; Width = 576 },
  @{ Source = 'military\exports\voraner-heavy-weapons-factory-v02-color.png'; Target = 'heavy-weapons-factory-v02.png'; Width = 512 },
  @{ Source = 'military\exports\voraner-military-base-v03-color-base.png'; Target = 'military-base-v03.png'; Width = 576 },
  @{ Source = 'military\exports\voraner-military-base-v03-shield-v04-soft-back.png'; Target = 'military-base-v03-shield.png'; Width = 576 },
  @{ Source = 'infrastructure\exports\voraner-ring-gate-v02-color.png'; Target = 'ring-gate-v02.png'; Width = 512 }
)

foreach ($asset in $assets) {
  Export-ScaledPng `
    -Source (Join-Path $sourceRoot $asset.Source) `
    -Target (Join-Path $targetRoot $asset.Target) `
    -TargetWidth $asset.Width
}

& (Join-Path $RepositoryRoot 'tools\generate_hive_emissive_layers.ps1') -RepositoryRoot $RepositoryRoot

# Full ring-world surroundings keep their authored 217-cell projection and are
# copied without another resampling pass.
Copy-Item `
  -LiteralPath (Join-Path $RepositoryRoot 'concept-art\terrain-prototypes\home-hive-large-v04\home-hive-large-v04-client-3072x1272.png') `
  -Destination (Join-Path $targetRoot 'home-hive-large-v04.png') `
  -Force

Copy-Item `
  -LiteralPath (Join-Path $RepositoryRoot 'concept-art\terrain-prototypes\home-hive-large-v05\home-hive-large-v05-client-3072x1272.png') `
  -Destination (Join-Path $targetRoot 'home-hive-large-v05.png') `
  -Force

Copy-Item `
  -LiteralPath (Join-Path $RepositoryRoot 'concept-art\terrain-prototypes\home-hive-large-v07\home-hive-large-v07-client-1536x1024.png') `
  -Destination (Join-Path $targetRoot 'home-hive-large-v07.png') `
  -Force

Copy-Item `
  -LiteralPath (Join-Path $RepositoryRoot 'concept-art\terrain-prototypes\home-hive-large-v08\home-hive-large-v08-source.png') `
  -Destination (Join-Path $targetRoot 'home-hive-large-v08.png') `
  -Force

Copy-Item `
  -LiteralPath (Join-Path $RepositoryRoot 'concept-art\terrain-prototypes\home-hive-large-v09\home-hive-large-v09-source.png') `
  -Destination (Join-Path $targetRoot 'home-hive-large-v09.png') `
  -Force

# V10 is a non-destructive 2x runtime derivative. At the 160% gameplay
# camera and DPR 1.5 the 1536 px V09 was enlarged by the GPU, which softened
# terrain detail. The source and V09 remain untouched for direct comparison.
& (Join-Path $RepositoryRoot 'tools\prepare_hive_ground_sharp.ps1') `
  -Source 'client/public/assets/hive-lab/home-hive-large-v09.png' `
  -Destination 'client/public/assets/hive-lab/home-hive-large-v10-sharp.png' `
  -Width 3072 `
  -Height 2048 `
  -SharpenAmount 0.12

& (Join-Path $RepositoryRoot 'tools\slice_hive_ground_tiles.ps1')

Get-ChildItem -File $targetRoot | Select-Object Name, Length
