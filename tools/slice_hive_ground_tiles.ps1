param(
  [string]$Source = 'client/public/assets/hive-lab/home-hive-large-v14-max-detail-4608.png',
  [string]$OutputDirectory = 'client/public/assets/hive-lab/home-hive-large-v14-tiles',
  [string]$Preview = 'client/public/assets/hive-lab/home-hive-large-v14-preview.png',
  [int]$Columns = 6,
  [int]$Rows = 4,
  [int]$PreviewWidth = 1536
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $repositoryRoot $Source
$outputPath = Join-Path $repositoryRoot $OutputDirectory
$previewPath = Join-Path $repositoryRoot $Preview

Add-Type -AssemblyName System.Drawing
New-Item -ItemType Directory -Force -Path $outputPath | Out-Null

$image = [System.Drawing.Bitmap]::FromFile($sourcePath)
try {
  if ($image.Width % $Columns -ne 0 -or $image.Height % $Rows -ne 0) {
    throw "Source dimensions $($image.Width)x$($image.Height) are not divisible by ${Columns}x${Rows}."
  }

  $tileWidth = [int]($image.Width / $Columns)
  $tileHeight = [int]($image.Height / $Rows)
  for ($row = 0; $row -lt $Rows; $row += 1) {
    for ($column = 0; $column -lt $Columns; $column += 1) {
      $tile = New-Object System.Drawing.Bitmap $tileWidth, $tileHeight, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
      try {
        $graphics = [System.Drawing.Graphics]::FromImage($tile)
        try {
          $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
          $sourceRect = New-Object System.Drawing.Rectangle ($column * $tileWidth), ($row * $tileHeight), $tileWidth, $tileHeight
          $targetRect = New-Object System.Drawing.Rectangle 0, 0, $tileWidth, $tileHeight
          $graphics.DrawImage($image, $targetRect, $sourceRect, [System.Drawing.GraphicsUnit]::Pixel)
        } finally {
          $graphics.Dispose()
        }
        $tile.Save((Join-Path $outputPath "r$row-c$column.png"), [System.Drawing.Imaging.ImageFormat]::Png)
      } finally {
        $tile.Dispose()
      }
    }
  }

  $previewHeight = [int][Math]::Round($image.Height * $PreviewWidth / $image.Width)
  $previewBitmap = New-Object System.Drawing.Bitmap $PreviewWidth, $previewHeight, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
  try {
    $graphics = [System.Drawing.Graphics]::FromImage($previewBitmap)
    try {
      $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
      $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
      $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
      $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
      $graphics.DrawImage($image, 0, 0, $PreviewWidth, $previewHeight)
    } finally {
      $graphics.Dispose()
    }
    $previewBitmap.Save($previewPath, [System.Drawing.Imaging.ImageFormat]::Png)
  } finally {
    $previewBitmap.Dispose()
  }
} finally {
  $image.Dispose()
}

Get-ChildItem -File $outputPath | Sort-Object Name | Select-Object Name, Length
Get-Item $previewPath | Select-Object Name, Length
