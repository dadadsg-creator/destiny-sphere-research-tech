param(
    [string]$MaterialDirectory = "D:\ProjektDS\concept-art\terrain-prototypes\home-hive-large-v15-cell-locked\material-plates",
    [string]$BaseImage = "D:\ProjektDS\concept-art\terrain-prototypes\home-hive-large-v15-cell-locked\home-hive-large-v15-cell-locked-candidate-03.png",
    [string]$Output = "D:\ProjektDS\concept-art\terrain-prototypes\home-hive-large-v15-cell-locked\home-hive-large-v15-cell-locked-final.png",
    [int]$BlendRadius = 4
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$groundSource = Get-Content -LiteralPath 'D:\ProjektDS\client\src\map\locationGround.ts' -Raw
if ($groundSource -notmatch "HOME_ART_GROUND\s*=\s*'([^']+)'" ) {
    throw 'Не удалось прочитать HOME_ART_GROUND'
}
$layout = $Matches[1]
if ($layout.Length -ne 217) { throw "HOME_ART_GROUND содержит $($layout.Length) позиций вместо 217" }

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

if (-not ('CellLockedTerrainComposer' -as [type])) {
    $drawingAssembly = [System.Drawing.Bitmap].Assembly.Location
    $gdiAssembly = Join-Path (Split-Path $drawingAssembly) 'System.Private.Windows.GdiPlus.dll'
    $windowsCoreAssembly = Join-Path (Split-Path $drawingAssembly) 'System.Private.Windows.Core.dll'
    $drawingPrimitivesAssembly = [System.Drawing.Point].Assembly.Location
    Add-Type -ReferencedAssemblies @($drawingAssembly, $gdiAssembly, $windowsCoreAssembly, $drawingPrimitivesAssembly) -TypeDefinition @'
using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.Runtime.InteropServices;

public static class CellLockedTerrainComposer
{
    private static Bitmap ToArgb(string path, int width, int height)
    {
        using (var source = Image.FromFile(path))
        {
            var result = new Bitmap(width, height, PixelFormat.Format32bppArgb);
            using (var graphics = Graphics.FromImage(result))
            {
                graphics.DrawImage(source, 0, 0, width, height);
            }
            return result;
        }
    }

    private static byte[] Read(Bitmap bitmap)
    {
        var rect = new Rectangle(0, 0, bitmap.Width, bitmap.Height);
        var data = bitmap.LockBits(rect, ImageLockMode.ReadOnly, PixelFormat.Format32bppArgb);
        try
        {
            var bytes = new byte[Math.Abs(data.Stride) * bitmap.Height];
            Marshal.Copy(data.Scan0, bytes, 0, bytes.Length);
            return bytes;
        }
        finally { bitmap.UnlockBits(data); }
    }

    private static byte[] BoxBlur(byte[] source, int width, int height, int stride, int radius)
    {
        if (radius <= 0) return source;
        var horizontal = new byte[source.Length];
        var output = new byte[source.Length];
        for (int y = 0; y < height; y++)
        {
            int sum = 0;
            for (int x = -radius; x <= radius; x++)
            {
                int sx = Math.Max(0, Math.Min(width - 1, x));
                sum += source[y * stride + sx * 4];
            }
            for (int x = 0; x < width; x++)
            {
                horizontal[y * stride + x * 4] = (byte)(sum / (radius * 2 + 1));
                int removeX = Math.Max(0, x - radius);
                int addX = Math.Min(width - 1, x + radius + 1);
                sum += source[y * stride + addX * 4] - source[y * stride + removeX * 4];
            }
        }
        for (int x = 0; x < width; x++)
        {
            int sum = 0;
            for (int y = -radius; y <= radius; y++)
            {
                int sy = Math.Max(0, Math.Min(height - 1, y));
                sum += horizontal[sy * stride + x * 4];
            }
            for (int y = 0; y < height; y++)
            {
                byte value = (byte)(sum / (radius * 2 + 1));
                int index = y * stride + x * 4;
                output[index] = value;
                output[index + 1] = value;
                output[index + 2] = value;
                output[index + 3] = 255;
                int removeY = Math.Max(0, y - radius);
                int addY = Math.Min(height - 1, y + radius + 1);
                sum += horizontal[addY * stride + x * 4] - horizontal[removeY * stride + x * 4];
            }
        }
        return output;
    }

    public static void Compose(string basePath, string[] platePaths, string[] maskPaths, string activeMaskPath, string outputPath, int radius)
    {
        using (var baseBitmap = ToArgb(basePath, 1536, 1024))
        using (var activeBitmap = ToArgb(activeMaskPath, 1536, 1024))
        {
            int width = baseBitmap.Width;
            int height = baseBitmap.Height;
            int stride = width * 4;
            var basePixels = Read(baseBitmap);
            var activePixels = Read(activeBitmap);
            var platePixels = new byte[platePaths.Length][];
            var maskPixels = new byte[maskPaths.Length][];
            var bitmaps = new Bitmap[platePaths.Length * 2];
            try
            {
                for (int i = 0; i < platePaths.Length; i++)
                {
                    bitmaps[i] = ToArgb(platePaths[i], width, height);
                    platePixels[i] = Read(bitmaps[i]);
                    bitmaps[platePaths.Length + i] = ToArgb(maskPaths[i], width, height);
                    maskPixels[i] = BoxBlur(Read(bitmaps[platePaths.Length + i]), width, height, stride, radius);
                }

                var result = new Bitmap(width, height, PixelFormat.Format32bppArgb);
                var rect = new Rectangle(0, 0, width, height);
                var resultData = result.LockBits(rect, ImageLockMode.WriteOnly, PixelFormat.Format32bppArgb);
                try
                {
                    var output = new byte[basePixels.Length];
                    Array.Copy(basePixels, output, basePixels.Length);
                    var weights = new double[platePaths.Length];
                    for (int y = 0; y < height; y++)
                    {
                        for (int x = 0; x < width; x++)
                        {
                            int index = y * stride + x * 4;
                            if (activePixels[index] < 128) continue;
                            double total = 0.0;
                            for (int i = 0; i < weights.Length; i++)
                            {
                                double weight = maskPixels[i][index] / 255.0;
                                if (weight > 0.01 && weight < 0.99)
                                {
                                    double noise = Math.Sin(x * 0.115 + i * 1.71) * 0.55 + Math.Sin(y * 0.093 - i * 2.13) * 0.45;
                                    weight = Math.Max(0.0, Math.Min(1.0, weight + noise * 0.34 * weight * (1.0 - weight)));
                                }
                                weights[i] = weight;
                                total += weight;
                            }
                            if (total < 0.001) continue;
                            for (int channel = 0; channel < 3; channel++)
                            {
                                double value = 0.0;
                                for (int i = 0; i < weights.Length; i++)
                                    value += platePixels[i][index + channel] * weights[i] / total;
                                output[index + channel] = (byte)Math.Max(0, Math.Min(255, Math.Round(value)));
                            }
                            output[index + 3] = 255;
                        }
                    }
                    Marshal.Copy(output, 0, resultData.Scan0, output.Length);
                }
                finally { result.UnlockBits(resultData); }
                result.Save(outputPath, ImageFormat.Png);
                result.Dispose();
            }
            finally
            {
                foreach (var bitmap in bitmaps) if (bitmap != null) bitmap.Dispose();
            }
        }
    }
}
'@
}

$outputDirectory = Split-Path -Parent $Output
$maskDirectory = Join-Path $outputDirectory 'material-masks'
New-Item -ItemType Directory -Force -Path $outputDirectory, $maskDirectory | Out-Null

$terrainCodes = @('D', 'S', 'G', 'W', 'X')
$masks = @{}
$graphicsByCode = @{}
$activeMask = New-Object System.Drawing.Bitmap 1536, 1024
$activeGraphics = [System.Drawing.Graphics]::FromImage($activeMask)
try {
    $activeGraphics.Clear([System.Drawing.Color]::Black)
    $activeGraphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::None
    foreach ($code in $terrainCodes) {
        $bitmap = New-Object System.Drawing.Bitmap 1536, 1024
        $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
        $graphics.Clear([System.Drawing.Color]::Black)
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::None
        $masks[$code] = $bitmap
        $graphicsByCode[$code] = $graphics
    }
    $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
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
                $graphicsByCode[$code].FillPolygon($brush, $points)
                $activeGraphics.FillPolygon($brush, $points)
                $num++
            }
            $nn += if ($row -lt 8) { 1 } else { -1 }
        }
    } finally { $brush.Dispose() }

    $maskPaths = @()
    foreach ($code in $terrainCodes) {
        $path = Join-Path $maskDirectory ("terrain-{0}.png" -f $code.ToLowerInvariant())
        $masks[$code].Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
        $maskPaths += $path
    }
    $activeMaskPath = Join-Path $maskDirectory 'terrain-active.png'
    $activeMask.Save($activeMaskPath, [System.Drawing.Imaging.ImageFormat]::Png)
} finally {
    $activeGraphics.Dispose()
    foreach ($code in $terrainCodes) {
        if ($graphicsByCode[$code]) { $graphicsByCode[$code].Dispose() }
        if ($masks[$code]) { $masks[$code].Dispose() }
    }
    $activeMask.Dispose()
}

$platePaths = @(
    (Join-Path $MaterialDirectory 'desert.png'),
    (Join-Path $MaterialDirectory 'soil.png'),
    (Join-Path $MaterialDirectory 'grass.png'),
    (Join-Path $MaterialDirectory 'water.png'),
    $BaseImage
)

[CellLockedTerrainComposer]::Compose($BaseImage, $platePaths, $maskPaths, $activeMaskPath, $Output, $BlendRadius)

Get-Item -LiteralPath $Output | Select-Object FullName, Length, LastWriteTime
