$ErrorActionPreference = 'Stop'
$url = 'http://127.0.0.1:4174/prototype/'

try {
    $response = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 2
    if ($response.StatusCode -ne 200) { throw "HTTP $($response.StatusCode)" }
} catch {
    $prototypeDirectory = Join-Path $PSScriptRoot '..\prototype'
    Start-Process -FilePath 'python' `
        -ArgumentList '-m', 'http.server', '4174', '--directory', '..' `
        -WorkingDirectory $prototypeDirectory `
        -WindowStyle Hidden
    for ($attempt = 0; $attempt -lt 20; $attempt += 1) {
        Start-Sleep -Milliseconds 150
        try {
            $response = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 1
            if ($response.StatusCode -eq 200) { break }
        } catch {
            if ($attempt -eq 19) { throw }
        }
    }
}

Start-Process $url
