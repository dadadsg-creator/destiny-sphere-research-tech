param(
  [string]$RepositoryRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

function Export-EmissiveMask {
  param(
    [Parameter(Mandatory)][string]$Source,
    [Parameter(Mandatory)][string]$Target
  )

  $input = [System.Drawing.Bitmap]::FromFile($Source)
  try {
    $output = New-Object System.Drawing.Bitmap $input.Width, $input.Height, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    try {
      for ($y = 0; $y -lt $input.Height; $y += 1) {
        for ($x = 0; $x -lt $input.Width; $x += 1) {
          $pixel = $input.GetPixel($x, $y)
          if ($pixel.A -eq 0 -or $pixel.B -lt 88) { continue }
          $blueExcess = $pixel.B - [Math]::Max($pixel.R, $pixel.G)
          $cyan = [Math]::Min($pixel.B, $pixel.G) - $pixel.R
          if ($blueExcess -lt 14 -and $cyan -lt 10) { continue }
          $energy = [Math]::Max($blueExcess * 3, $cyan * 2)
          $alpha = [Math]::Min(255, [Math]::Max(0, $energy + 40))
          if ($alpha -le 0) { continue }
          $color = [System.Drawing.Color]::FromArgb(
            [Math]::Min($pixel.A, $alpha),
            [Math]::Min(255, [Math]::Max($pixel.R, 40)),
            [Math]::Min(255, [Math]::Max($pixel.G, 150)),
            [Math]::Min(255, $pixel.B + 35)
          )
          $output.SetPixel($x, $y, $color)
        }
      }
      $output.Save($Target, [System.Drawing.Imaging.ImageFormat]::Png)
    } finally {
      $output.Dispose()
    }
  } finally {
    $input.Dispose()
  }
}

$assetRoot = Join-Path $RepositoryRoot 'client\public\assets\hive-lab'
$pairs = @(
  @('ring-gate-v02.png', 'ring-gate-v02-emissive.png'),
  @('light-weapons-factory-v02.png', 'light-weapons-factory-v02-emissive.png'),
  @('heavy-weapons-factory-v02.png', 'heavy-weapons-factory-v02-emissive.png'),
  @('military-base-v03.png', 'military-base-v03-emissive.png')
)

foreach ($pair in $pairs) {
  Export-EmissiveMask -Source (Join-Path $assetRoot $pair[0]) -Target (Join-Path $assetRoot $pair[1])
}

Get-ChildItem -File $assetRoot | Where-Object Name -Match '(ring-gate|weapons-factory|military-base).*emissive' | Select-Object Name, Length
