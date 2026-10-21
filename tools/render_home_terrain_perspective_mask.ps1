param(
    [string]$Source = "D:\ProjektDS\concept-art\terrain-prototypes\home-hive-large-v14-max-detail\home-hive-large-v14-max-detail-source.png",
    [string]$OutputDirectory = "D:\ProjektDS\concept-art\terrain-prototypes\home-hive-large-v15-cell-locked"
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$groundSource = Get-Content -LiteralPath 'D:\ProjektDS\client\src\map\locationGround.ts' -Raw
if ($groundSource -notmatch "HOME_ART_GROUND\s*=\s*'([^']+)'" ) {
    throw 'Не удалось прочитать HOME_ART_GROUND'
}
$layout = $Matches[1]
if ($layout.Length -ne 217) { throw "HOME_ART_GROUND содержит $($layout.Length) позиций вместо 217" }

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null

$canvasWidth = 1536.0
$canvasHeight = 1024.0
$mapWidth = 816.0
$mapHeight = 338.0
$halfHeight = $mapHeight / 2.0
$groundWidth = $mapWidth * 1.242352941
$groundHeight = $mapHeight * 1.706666667
$groundX = -$mapWidth * 0.121176471
$groundY = -$mapHeight / 3.0

$frame = @{
    TopLeft = [System.Drawing.PointF]::new(155.88, -89.2885)
    TopRight = [System.Drawing.PointF]::new(634.38, -89.2885)
    Right = [System.Drawing.PointF]::new(856.14, 115.765)
    BottomRight = [System.Drawing.PointF]::new(686.52, 341.943)
    BottomLeft = [System.Drawing.PointF]::new(87.9, 341.943)
    Left = [System.Drawing.PointF]::new(-61.26, 115.765)
}

function Get-LogicalInset([double]$Y) {
    if ($Y -le 6.5) { return 216.0 - $Y * (24.0 / 6.5) }
    if ($Y -le 162.5) { return 192.0 * (162.5 - $Y) / 156.0 }
    if ($Y -le 175.5) { return 0.0 }
    if ($Y -le 331.5) { return 192.0 * ($Y - 175.5) / 156.0 }
    return 192.0 + ($Y - 331.5) * (24.0 / 6.5)
}

function Convert-ToWorld([double]$X, [double]$Y) {
    $topHalf = $Y -le $halfHeight
    $t = if ($topHalf) { $Y / $halfHeight } else { ($Y - $halfHeight) / $halfHeight }
    $logicalInset = Get-LogicalInset $Y
    $logicalLeft = $logicalInset
    $logicalRight = $mapWidth - $logicalInset
    $leftA = if ($topHalf) { $frame.TopLeft } else { $frame.Left }
    $leftB = if ($topHalf) { $frame.Left } else { $frame.BottomLeft }
    $rightA = if ($topHalf) { $frame.TopRight } else { $frame.Right }
    $rightB = if ($topHalf) { $frame.Right } else { $frame.BottomRight }
    $targetLeftX = $leftA.X + ($leftB.X - $leftA.X) * $t
    $targetRightX = $rightA.X + ($rightB.X - $rightA.X) * $t
    $rowPosition = ($X - $logicalLeft) / [Math]::Max(1.0, $logicalRight - $logicalLeft)
    return [System.Drawing.PointF]::new(
        $targetLeftX + ($targetRightX - $targetLeftX) * $rowPosition,
        $leftA.Y + ($leftB.Y - $leftA.Y) * $t
    )
}

function Convert-ToSource([double]$X, [double]$Y) {
    $world = Convert-ToWorld $X $Y
    return [System.Drawing.PointF]::new(
        [single](($world.X - $groundX) * $canvasWidth / $groundWidth),
        [single](($world.Y - $groundY) * $canvasHeight / $groundHeight)
    )
}

function Get-HexPoints([double]$X, [double]$Y) {
    $local = @(
        @(24.0, 0.0), @(48.0, 6.5), @(48.0, 19.5),
        @(24.0, 26.0), @(0.0, 19.5), @(0.0, 6.5)
    )
    return [System.Drawing.PointF[]]@($local | ForEach-Object { Convert-ToSource ($X + $_[0]) ($Y + $_[1]) })
}

$colors = @{
    D = [System.Drawing.Color]::FromArgb(255, 214, 164, 83)
    S = [System.Drawing.Color]::FromArgb(255, 126, 126, 105)
    G = [System.Drawing.Color]::FromArgb(255, 84, 137, 83)
    W = [System.Drawing.Color]::FromArgb(255, 42, 132, 176)
    X = [System.Drawing.Color]::FromArgb(255, 142, 104, 203)
}

$sourceImage = [System.Drawing.Image]::FromFile($Source)
try {
    $overlay = New-Object System.Drawing.Bitmap $sourceImage.Width, $sourceImage.Height
    $mask = New-Object System.Drawing.Bitmap $sourceImage.Width, $sourceImage.Height
    try {
        $overlayGraphics = [System.Drawing.Graphics]::FromImage($overlay)
        $maskGraphics = [System.Drawing.Graphics]::FromImage($mask)
        try {
            foreach ($graphics in @($overlayGraphics, $maskGraphics)) {
                $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
                $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
            }
            $overlayGraphics.DrawImage($sourceImage, 0, 0, $sourceImage.Width, $sourceImage.Height)
            $maskGraphics.Clear([System.Drawing.Color]::FromArgb(255, 9, 15, 22))
            $font = New-Object System.Drawing.Font 'Consolas', 8.0, ([System.Drawing.FontStyle]::Bold), ([System.Drawing.GraphicsUnit]::Pixel)
            $outline = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(220, 235, 248, 255)), 1.2
            $overlayOutline = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(190, 245, 251, 255)), 1.1
            try {
                $num = 0
                $nn = 0
                for ($row = 0; $row -lt 17; $row++) {
                    $rowLength = 9 + $nn
                    for ($col = 0; $col -lt $rowLength; $col++) {
                        $x = ($col * 2 - $nn + 8) * 24.0
                        $y = $row * 19.5
                        $code = [string]$layout[$num]
                        [System.Drawing.PointF[]]$points = @(Get-HexPoints $x $y)
                        $solidBrush = New-Object System.Drawing.SolidBrush $colors[$code]
                        $overlayBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(102, $colors[$code]))
                        try {
                            $maskGraphics.FillPolygon($solidBrush, $points)
                            $maskGraphics.DrawPolygon($outline, $points)
                            $overlayGraphics.FillPolygon($overlayBrush, $points)
                            $overlayGraphics.DrawPolygon($overlayOutline, $points)
                            $cx = ($points | Measure-Object X -Average).Average
                            $cy = ($points | Measure-Object Y -Average).Average
                            $label = "{0:D3}{1}" -f $num, $code
                            $labelSize = $maskGraphics.MeasureString($label, $font)
                            $labelX = [single]($cx - $labelSize.Width / 2)
                            $labelY = [single]($cy - $labelSize.Height / 2)
                            $shadowBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 5, 10, 16))
                            $textBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
                            try {
                                $maskGraphics.DrawString($label, $font, $shadowBrush, $labelX + 1, $labelY + 1)
                                $maskGraphics.DrawString($label, $font, $textBrush, $labelX, $labelY)
                                $overlayGraphics.DrawString($label, $font, $shadowBrush, $labelX + 1, $labelY + 1)
                                $overlayGraphics.DrawString($label, $font, $textBrush, $labelX, $labelY)
                            } finally {
                                $shadowBrush.Dispose()
                                $textBrush.Dispose()
                            }
                        } finally {
                            $solidBrush.Dispose()
                            $overlayBrush.Dispose()
                        }
                        $num++
                    }
                    $nn += if ($row -lt 8) { 1 } else { -1 }
                }
            } finally {
                $font.Dispose()
                $outline.Dispose()
                $overlayOutline.Dispose()
            }
        } finally {
            $overlayGraphics.Dispose()
            $maskGraphics.Dispose()
        }
        $overlay.Save((Join-Path $OutputDirectory 'home-hive-v15-terrain-overlay.png'), [System.Drawing.Imaging.ImageFormat]::Png)
        $mask.Save((Join-Path $OutputDirectory 'home-hive-v15-terrain-mask.png'), [System.Drawing.Imaging.ImageFormat]::Png)
    } finally {
        $overlay.Dispose()
        $mask.Dispose()
    }
} finally {
    $sourceImage.Dispose()
}

Get-ChildItem -LiteralPath $OutputDirectory | Select-Object Name, Length
