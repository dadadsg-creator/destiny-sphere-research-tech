param(
  [Parameter(Mandatory=$true)][string]$Machine,
  [Parameter(Mandatory=$true)][string]$Source,
  [string]$HtmlPath = "unit-atlas.html",
  [int]$MaxSide = 512
)

$ErrorActionPreference = "Stop"

$root = Resolve-Path ".codex\unit-redraws"
$bgPath = Join-Path $root "$Machine-redraw-bg.png"
$outPath = Join-Path $root "$Machine-redraw.png"
Copy-Item -LiteralPath $Source -Destination $bgPath -Force

$code = @'
using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;

public static class UnitRedrawResize {
  public static string Resize(string input, string output, int maxSide) {
    using (var src = new Bitmap(input)) {
      int w = src.Width, h = src.Height, max = Math.Max(w, h);
      double scale = max > maxSide ? (double)maxSide / max : 1.0;
      int nw = Math.Max(1, (int)Math.Round(w * scale));
      int nh = Math.Max(1, (int)Math.Round(h * scale));
      using (var dst = new Bitmap(nw, nh, PixelFormat.Format32bppArgb)) {
        using (var g = Graphics.FromImage(dst)) {
          g.Clear(Color.Transparent);
          g.CompositingQuality = CompositingQuality.HighQuality;
          g.InterpolationMode = InterpolationMode.HighQualityBicubic;
          g.SmoothingMode = SmoothingMode.HighQuality;
          g.PixelOffsetMode = PixelOffsetMode.HighQuality;
          g.DrawImage(src, new Rectangle(0, 0, nw, nh), new Rectangle(0, 0, w, h), GraphicsUnit.Pixel);
        }
        dst.Save(output, ImageFormat.Png);
      }
      return w + "x" + h + " -> " + nw + "x" + nh;
    }
  }
}
'@

if (-not ("UnitRedrawResize" -as [type])) {
  Add-Type -ReferencedAssemblies System.Drawing -TypeDefinition $code
}

$resize = [UnitRedrawResize]::Resize($bgPath, $outPath, $MaxSide)

Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Bitmap]::FromFile($outPath)
$w = $img.Width
$h = $img.Height
$samples = @(@(0,0), @(($w - 1),0), @(0,($h - 1)), @(($w - 1),($h - 1)), @(10,10))
$alphas = foreach ($s in $samples) { $img.GetPixel([int]$s[0],[int]$s[1]).A }
$img.Dispose()

if ([Math]::Max($w, $h) -gt $MaxSide) {
  throw "Resized image is too large: ${w}x${h}"
}

$htmlFullPath = Resolve-Path $HtmlPath
$html = [IO.File]::ReadAllText($htmlFullPath, [Text.Encoding]::UTF8)
$dataUri = "data:image/png;base64," + [Convert]::ToBase64String([IO.File]::ReadAllBytes($outPath))
$entryPattern = "(?m)^(\s*)" + [regex]::Escape($Machine) + ": 'data:image/png;base64,[^']+',?"

if ([regex]::IsMatch($html, $entryPattern)) {
  $html = [regex]::Replace($html, $entryPattern, "`$1${Machine}: '$dataUri',", 1)
} else {
  $marker = "const REDRAW_DATA = {"
  $idx = $html.IndexOf($marker)
  if ($idx -lt 0) { throw "REDRAW_DATA marker not found" }
  $insertAt = $idx + $marker.Length
  $html = $html.Insert($insertAt, "`r`n    ${Machine}: '$dataUri',")
}

[IO.File]::WriteAllText($htmlFullPath, $html, [Text.UTF8Encoding]::new($false))

[PSCustomObject]@{
  Machine = $Machine
  Resize = $resize
  Width = $w
  Height = $h
  MaxSide = [Math]::Max($w, $h)
  SampleAlphas = ($alphas -join ",")
  Bytes = (Get-Item $outPath).Length
}
