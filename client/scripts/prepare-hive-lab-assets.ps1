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
  @{ Source = 'residential\exports\voraner-residential-v02-color.png'; Target = 'house-v02.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v02-emissive.png'; Target = 'house-v02-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v03-color.png'; Target = 'house-v03.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v03-emissive.png'; Target = 'house-v03-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v05-color.png'; Target = 'house-v05.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-v05-emissive.png'; Target = 'house-v05-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-3-color.png'; Target = 'complex-3.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-3-emissive.png'; Target = 'complex-3-emissive.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-7-color.png'; Target = 'complex-7.png'; Width = 512 },
  @{ Source = 'residential\exports\voraner-residential-complex-7-emissive.png'; Target = 'complex-7-emissive.png'; Width = 512 }
)

foreach ($asset in $assets) {
  Export-ScaledPng `
    -Source (Join-Path $sourceRoot $asset.Source) `
    -Target (Join-Path $targetRoot $asset.Target) `
    -TargetWidth $asset.Width
}

Get-ChildItem -File $targetRoot | Select-Object Name, Length
