param(
  [Parameter(Mandatory = $true)]
  [string] $InputPath,

  [Parameter(Mandatory = $true)]
  [string] $OutputPath,

  [ValidateRange(0, 255)]
  [int] $AlphaThreshold = 4,

  [ValidateRange(0, 256)]
  [int] $Padding = 8
)

Add-Type -AssemblyName System.Drawing

$inputFullPath = [System.IO.Path]::GetFullPath($InputPath)
$outputFullPath = [System.IO.Path]::GetFullPath($OutputPath)
$bitmap = [System.Drawing.Bitmap]::FromFile($inputFullPath)

try {
  $rect = [System.Drawing.Rectangle]::new(0, 0, $bitmap.Width, $bitmap.Height)
  $data = $bitmap.LockBits(
    $rect,
    [System.Drawing.Imaging.ImageLockMode]::ReadOnly,
    [System.Drawing.Imaging.PixelFormat]::Format32bppArgb
  )

  try {
    $stride = [Math]::Abs($data.Stride)
    $bytes = [byte[]]::new($stride * $bitmap.Height)
    [System.Runtime.InteropServices.Marshal]::Copy($data.Scan0, $bytes, 0, $bytes.Length)

    $minX = $bitmap.Width
    $minY = $bitmap.Height
    $maxX = -1
    $maxY = -1

    for ($y = 0; $y -lt $bitmap.Height; $y++) {
      $rowOffset = $y * $stride
      for ($x = 0; $x -lt $bitmap.Width; $x++) {
        $alpha = $bytes[$rowOffset + $x * 4 + 3]
        if ($alpha -gt $AlphaThreshold) {
          if ($x -lt $minX) { $minX = $x }
          if ($y -lt $minY) { $minY = $y }
          if ($x -gt $maxX) { $maxX = $x }
          if ($y -gt $maxY) { $maxY = $y }
        }
      }
    }
  }
  finally {
    $bitmap.UnlockBits($data)
  }

  if ($maxX -lt $minX -or $maxY -lt $minY) {
    throw "The image does not contain pixels above the alpha threshold."
  }

  $left = [Math]::Max(0, $minX - $Padding)
  $top = [Math]::Max(0, $minY - $Padding)
  $right = [Math]::Min($bitmap.Width - 1, $maxX + $Padding)
  $bottom = [Math]::Min($bitmap.Height - 1, $maxY + $Padding)
  $crop = [System.Drawing.Rectangle]::new($left, $top, $right - $left + 1, $bottom - $top + 1)
  $result = [System.Drawing.Bitmap]::new($crop.Width, $crop.Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)

  try {
    $graphics = [System.Drawing.Graphics]::FromImage($result)
    try {
      $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
      $graphics.DrawImage($bitmap, [System.Drawing.Rectangle]::new(0, 0, $crop.Width, $crop.Height), $crop, [System.Drawing.GraphicsUnit]::Pixel)
    }
    finally {
      $graphics.Dispose()
    }

    $outputDirectory = [System.IO.Path]::GetDirectoryName($outputFullPath)
    [System.IO.Directory]::CreateDirectory($outputDirectory) | Out-Null
    $result.Save($outputFullPath, [System.Drawing.Imaging.ImageFormat]::Png)
  }
  finally {
    $result.Dispose()
  }

  [pscustomobject]@{
    Input = $inputFullPath
    Output = $outputFullPath
    SourceSize = "$($bitmap.Width)x$($bitmap.Height)"
    Crop = "$left,$top $($crop.Width)x$($crop.Height)"
  }
}
finally {
  $bitmap.Dispose()
}
