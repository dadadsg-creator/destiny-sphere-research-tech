param(
  [string]$Source = 'client/public/assets/hive-lab/home-hive-large-v09.png',
  [string]$Destination = 'client/public/assets/hive-lab/home-hive-large-v10-sharp.png',
  [int]$Width = 3072,
  [int]$Height = 2048,
  [double]$SharpenAmount = 0.12
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = Split-Path -Parent $PSScriptRoot
$sourcePath = [System.IO.Path]::GetFullPath((Join-Path $repositoryRoot $Source))
$destinationPath = [System.IO.Path]::GetFullPath((Join-Path $repositoryRoot $Destination))

if (-not (Test-Path -LiteralPath $sourcePath)) {
  throw "Source image not found: $sourcePath"
}

Add-Type -AssemblyName System.Drawing

if (-not ('HiveGroundPixelTools' -as [type])) {
  Add-Type -TypeDefinition @'
using System;

public static class HiveGroundPixelTools
{
    public static void Sharpen(byte[] pixels, byte[] original, int width, int height, int stride, double amount)
    {
        for (int y = 1; y < height - 1; y++)
        {
            for (int x = 1; x < width - 1; x++)
            {
                int index = y * stride + x * 3;
                for (int channel = 0; channel < 3; channel++)
                {
                    int center = original[index + channel];
                    int neighbors = original[index - 3 + channel]
                        + original[index + 3 + channel]
                        + original[index - stride + channel]
                        + original[index + stride + channel];
                    int sharpened = (int)Math.Round(center * (1.0 + 4.0 * amount) - neighbors * amount);
                    pixels[index + channel] = (byte)Math.Max(0, Math.Min(255, sharpened));
                }
            }
        }
    }
}
'@
}

$destinationDirectory = Split-Path -Parent $destinationPath
[System.IO.Directory]::CreateDirectory($destinationDirectory) | Out-Null

$sourceImage = [System.Drawing.Image]::FromFile($sourcePath)
$outputImage = [System.Drawing.Bitmap]::new($Width, $Height, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
try {
  $outputImage.SetResolution(96, 96)
  $graphics = [System.Drawing.Graphics]::FromImage($outputImage)
  try {
    $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
    $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $graphics.DrawImage($sourceImage, [System.Drawing.Rectangle]::new(0, 0, $Width, $Height))
  } finally {
    $graphics.Dispose()
  }

  $rectangle = [System.Drawing.Rectangle]::new(0, 0, $Width, $Height)
  $bitmapData = $outputImage.LockBits(
    $rectangle,
    [System.Drawing.Imaging.ImageLockMode]::ReadWrite,
    [System.Drawing.Imaging.PixelFormat]::Format24bppRgb
  )
  try {
    $stride = [Math]::Abs($bitmapData.Stride)
    $pixels = [byte[]]::new($stride * $Height)
    [System.Runtime.InteropServices.Marshal]::Copy($bitmapData.Scan0, $pixels, 0, $pixels.Length)
    $original = [byte[]]$pixels.Clone()
    [HiveGroundPixelTools]::Sharpen($pixels, $original, $Width, $Height, $stride, $SharpenAmount)
    [System.Runtime.InteropServices.Marshal]::Copy($pixels, 0, $bitmapData.Scan0, $pixels.Length)
  } finally {
    $outputImage.UnlockBits($bitmapData)
  }

  $outputImage.Save($destinationPath, [System.Drawing.Imaging.ImageFormat]::Png)
} finally {
  $outputImage.Dispose()
  $sourceImage.Dispose()
}

$result = Get-Item -LiteralPath $destinationPath
[PSCustomObject]@{
  Source = $sourcePath
  Destination = $destinationPath
  Width = $Width
  Height = $Height
  SharpenAmount = $SharpenAmount
  Bytes = $result.Length
}
