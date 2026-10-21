param(
  [Parameter(Mandatory = $true)]
  [string] $BasePath,

  [Parameter(Mandatory = $true)]
  [string] $ShieldPath,

  [Parameter(Mandatory = $true)]
  [string] $CompositePath
)

Add-Type -AssemblyName System.Drawing

$baseFullPath = [System.IO.Path]::GetFullPath($BasePath)
$shieldFullPath = [System.IO.Path]::GetFullPath($ShieldPath)
$compositeFullPath = [System.IO.Path]::GetFullPath($CompositePath)
$base = [System.Drawing.Bitmap]::FromFile($baseFullPath)

try {
  $width = $base.Width
  $height = $base.Height
  $shield = [System.Drawing.Bitmap]::new($width, $height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)

  try {
    $graphics = [System.Drawing.Graphics]::FromImage($shield)
    try {
      $graphics.Clear([System.Drawing.Color]::Transparent)
      $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
      $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceOver

      $left = [single]($width * 0.17)
      $right = [single]($width * 0.83)
      $top = [single]($height * 0.018)
      $baseline = [single]($height * 0.59)
      $centerX = [single]($width * 0.5)

      $domePath = [System.Drawing.Drawing2D.GraphicsPath]::new()
      try {
        $domePath.StartFigure()
        $domePath.AddBezier(
          $left, $baseline,
          [single]($width * 0.18), [single]($height * 0.19),
          [single]($width * 0.34), $top,
          $centerX, $top
        )
        $domePath.AddBezier(
          $centerX, $top,
          [single]($width * 0.66), $top,
          [single]($width * 0.82), [single]($height * 0.19),
          $right, $baseline
        )
        $domePath.AddLine($right, $baseline, $left, $baseline)
        $domePath.CloseFigure()

        $outerArc = [System.Drawing.Drawing2D.GraphicsPath]::new()
        $outerArc.StartFigure()
        $outerArc.AddBezier(
          $left, $baseline,
          [single]($width * 0.18), [single]($height * 0.19),
          [single]($width * 0.34), $top,
          $centerX, $top
        )
        $outerArc.AddBezier(
          $centerX, $top,
          [single]($width * 0.66), $top,
          [single]($width * 0.82), [single]($height * 0.19),
          $right, $baseline
        )

        # The shield is rendered as an independent optical layer.  The very
        # light fill keeps the volume readable at game scale while leaving the
        # command body and its material lighting untouched.
        $fieldBrush = [System.Drawing.SolidBrush]::new(
          [System.Drawing.Color]::FromArgb(4, 42, 148, 235)
        )
        try { $graphics.FillPath($fieldBrush, $domePath) } finally { $fieldBrush.Dispose() }

        foreach ($stroke in @(
          @{ A = 15; W = 32.0 },
          @{ A = 45; W = 16.0 },
          @{ A = 155; W = 4.5 }
        )) {
          $pen = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb($stroke.A, 50, 180, 255), [single]$stroke.W)
          try {
            $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
            $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
            $graphics.DrawPath($pen, $outerArc)
          }
          finally { $pen.Dispose() }
        }

        $oldClip = $graphics.Clip
        $graphics.SetClip($domePath)
        try {
          # Large projected honeycomb cells remain legible after the source is
          # reduced to a 70 px logical building.  They describe an energy
          # membrane rather than a rigid glass cage.
          $hexPen = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb(28, 80, 205, 255), 4.0)
          try {
            $hexWidth = [single]($width * 0.092)
            $hexHeight = [single]($height * 0.075)
            $stepX = [single]($hexWidth * 0.75)
            $stepY = [single]$hexHeight
            $columnIndex = 0
            for ($x = [single]($left - $hexWidth); $x -lt $right; $x += $stepX) {
              $offsetY = if (($columnIndex % 2) -eq 0) { 0.0 } else { $stepY * 0.5 }
              for ($y = [single]($height * 0.10 + $offsetY); $y -lt $baseline; $y += $stepY) {
                $points = [System.Drawing.PointF[]]@(
                  [System.Drawing.PointF]::new([single]($x + $hexWidth * 0.25), $y),
                  [System.Drawing.PointF]::new([single]($x + $hexWidth * 0.75), $y),
                  [System.Drawing.PointF]::new([single]($x + $hexWidth), [single]($y + $hexHeight * 0.5)),
                  [System.Drawing.PointF]::new([single]($x + $hexWidth * 0.75), [single]($y + $hexHeight)),
                  [System.Drawing.PointF]::new([single]($x + $hexWidth * 0.25), [single]($y + $hexHeight)),
                  [System.Drawing.PointF]::new($x, [single]($y + $hexHeight * 0.5))
                )
                $graphics.DrawPolygon($hexPen, $points)
              }
              $columnIndex++
            }
          }
          finally { $hexPen.Dispose() }
        }
        finally { $graphics.Clip = $oldClip }

        $outerArc.Dispose()
      }
      finally { $domePath.Dispose() }
    }
    finally { $graphics.Dispose() }

    [System.IO.Directory]::CreateDirectory([System.IO.Path]::GetDirectoryName($shieldFullPath)) | Out-Null
    $shield.Save($shieldFullPath, [System.Drawing.Imaging.ImageFormat]::Png)

    $composite = [System.Drawing.Bitmap]::new($width, $height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    try {
      $compositeGraphics = [System.Drawing.Graphics]::FromImage($composite)
      try {
        $compositeGraphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceOver
        $compositeGraphics.DrawImage($shield, 0, 0, $width, $height)
        $compositeGraphics.DrawImage($base, 0, 0, $width, $height)
      }
      finally { $compositeGraphics.Dispose() }

      [System.IO.Directory]::CreateDirectory([System.IO.Path]::GetDirectoryName($compositeFullPath)) | Out-Null
      $composite.Save($compositeFullPath, [System.Drawing.Imaging.ImageFormat]::Png)
    }
    finally { $composite.Dispose() }
  }
  finally { $shield.Dispose() }
}
finally { $base.Dispose() }

[pscustomobject]@{
  Base = $baseFullPath
  Shield = $shieldFullPath
  Composite = $compositeFullPath
}
