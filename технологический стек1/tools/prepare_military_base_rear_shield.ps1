param(
  [Parameter(Mandatory = $true)]
  [string] $InputPath,

  [Parameter(Mandatory = $true)]
  [string] $MasterOutputPath,

  [Parameter(Mandatory = $true)]
  [string] $RuntimeOutputPath,

  [single] $AlphaMultiplier = 3.0,
  [int] $RuntimeWidth = 576,
  [int] $RuntimeHeight = 491,
  [switch] $RemoveOuterBoundary
)

Add-Type -AssemblyName System.Drawing

function Write-AlphaAdjustedImage {
  param(
    [System.Drawing.Image] $Source,
    [string] $Destination,
    [int] $Width,
    [int] $Height,
    [single] $Multiplier,
    [bool] $EraseBoundary
  )

  $bitmap = [System.Drawing.Bitmap]::new($Width, $Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
  try {
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    try {
      $graphics.Clear([System.Drawing.Color]::Transparent)
      $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
      $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
      $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
      $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
      $attributes = [System.Drawing.Imaging.ImageAttributes]::new()
      try {
        $matrix = [System.Drawing.Imaging.ColorMatrix]::new()
        $matrix.Matrix33 = $Multiplier
        $attributes.SetColorMatrix($matrix)
        $destinationRect = [System.Drawing.Rectangle]::new(0, 0, $Width, $Height)
        $graphics.DrawImage(
          $Source,
          $destinationRect,
          0,
          0,
          $Source.Width,
          $Source.Height,
          [System.Drawing.GraphicsUnit]::Pixel,
          $attributes
        )

        if ($EraseBoundary) {
          $left = [single]($Width * 0.17)
          $right = [single]($Width * 0.83)
          $top = [single]($Height * 0.018)
          $baseline = [single]($Height * 0.59)
          $centerX = [single]($Width * 0.5)
          $outerArc = [System.Drawing.Drawing2D.GraphicsPath]::new()
          try {
            $outerArc.StartFigure()
            $outerArc.AddBezier(
              $left, $baseline,
              [single]($Width * 0.18), [single]($Height * 0.19),
              [single]($Width * 0.34), $top,
              $centerX, $top
            )
            $outerArc.AddBezier(
              $centerX, $top,
              [single]($Width * 0.66), $top,
              [single]($Width * 0.82), [single]($Height * 0.19),
              $right, $baseline
            )
            $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
            $eraser = [System.Drawing.Pen]::new([System.Drawing.Color]::Transparent, [single]($Width * 0.036))
            try {
              $eraser.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
              $eraser.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
              $graphics.DrawPath($eraser, $outerArc)
            }
            finally { $eraser.Dispose() }
          }
          finally { $outerArc.Dispose() }
        }
      }
      finally { $attributes.Dispose() }
    }
    finally { $graphics.Dispose() }

    [System.IO.Directory]::CreateDirectory([System.IO.Path]::GetDirectoryName($Destination)) | Out-Null
    $bitmap.Save($Destination, [System.Drawing.Imaging.ImageFormat]::Png)
  }
  finally { $bitmap.Dispose() }
}

$inputFullPath = [System.IO.Path]::GetFullPath($InputPath)
$masterFullPath = [System.IO.Path]::GetFullPath($MasterOutputPath)
$runtimeFullPath = [System.IO.Path]::GetFullPath($RuntimeOutputPath)
$source = [System.Drawing.Image]::FromFile($inputFullPath)
try {
  Write-AlphaAdjustedImage -Source $source -Destination $masterFullPath -Width $source.Width -Height $source.Height -Multiplier $AlphaMultiplier -EraseBoundary $RemoveOuterBoundary
  Write-AlphaAdjustedImage -Source $source -Destination $runtimeFullPath -Width $RuntimeWidth -Height $RuntimeHeight -Multiplier $AlphaMultiplier -EraseBoundary $RemoveOuterBoundary
}
finally { $source.Dispose() }

[pscustomobject]@{
  Source = $inputFullPath
  Master = $masterFullPath
  Runtime = $runtimeFullPath
  AlphaMultiplier = $AlphaMultiplier
}
